<?php
/**
 * @Url_inspector plugin
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

declare(strict_types=1);

namespace Joomla\Plugin\System\Url_inspector\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Plugin\System\Securitycheckpro\Extension\Securitycheckpro;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\IpModel;

final class Url_inspector extends CMSPlugin
{
    /**
     * Plugin parameters loaded from storage
     *
     * @var array<string,mixed>|null
     */
    private static ?array $parameters = null;

    /**
     * Firewall class object
     *
     * @var Securitycheckpro|null
     */
    private static ?Securitycheckpro $objeto = null;

    /**
     * Language object (may be null if not available)
     *
     * @var Language|null
     */
    private static ?Language $lang_firewall = null;

    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var bool
     */
    protected $autoloadLanguage = false;

    /**
     * Constructor.
     *
     * @param object $subject The object to observe
     * @param array<string,mixed> $config
     */
    public function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        // Only run if Securitycheck Pro component exists (pro services provider file)
        $providerPath = JPATH_ADMINISTRATOR . DIRECTORY_SEPARATOR . 'components'
            . DIRECTORY_SEPARATOR . 'com_securitycheckpro'
            . DIRECTORY_SEPARATOR . 'services'
            . DIRECTORY_SEPARATOR . 'provider.php';

        if (!is_file($providerPath)) {
            return;
        }

        self::$parameters = $this->load('pro_plugin');

        // Create a new object to use Securitycheck Pro functions
        self::$objeto = new Securitycheckpro($subject, $config);

        // Get language safely (Joomla 5/6)
        $lang = null;

        try {
            $container = Factory::getContainer();
            if ($container->has(Language::class)) {
                /** @var Language $lang */
                $lang = $container->get(Language::class);
            } else {
                $app = Factory::getApplication();
                if (method_exists($app, 'getLanguage')) {
                    /** @var Language $lang */
                    $lang = $app->getLanguage();
                }
            }
        } catch (\Throwable $e) {
			Log::add('Url_inspector. Error retrieving the language: ' . $e->getMessage(), Log::ERROR, 'plg_url_inspector');
            $lang = null;
        }

        self::$lang_firewall = $lang;

        // Load admin language strings for com_securitycheckpro
        if (self::$lang_firewall instanceof Language) {
            self::$lang_firewall->load('com_securitycheckpro', JPATH_ADMINISTRATOR, null, true);
        }
    }

    /**
     * Overwrite the onAfterInitialise method
     *
     * @return void
     */
    public function onAfterInitialise(): void
    {
        // Ensure firewall object exists (component missing / not initialized)
        if (!(self::$objeto instanceof Securitycheckpro)) {
            return;
        }

        // Ensure language is loaded (extra safety)
        if (!(self::$lang_firewall instanceof Language)) {
            $app = Factory::getApplication();
            if (method_exists($app, 'getLanguage')) {
                /** @var Language $lang */
                $lang = $app->getLanguage();
                self::$lang_firewall = $lang;
                self::$lang_firewall->load('com_securitycheckpro', JPATH_ADMINISTRATOR, null, true);
            }
        }

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        // Get remote IP
        $ipmodel = new IpModel();
        $remote_ip = $ipmodel->getClientIpForSecuritycheckPro();

        // Invalid IP
        if ($remote_ip === null || $remote_ip === '') {
            return;
        }

        // Check if IP is already in blacklist
        /** @var \Joomla\CMS\Extension\MVCComponentInterface $component */
        $component  = Factory::getApplication()->bootComponent('com_securitycheckpro');
        $mvcFactory = $component->getMVCFactory();

        /** @var BaseModel $basemodel */
        $basemodel = $mvcFactory->createModel('Base', 'Administrator');

        $aparece_lista_negra = (bool) $basemodel->ChequearIpEnLista($remote_ip, 'blacklist');

        // If not blacklisted, inspect the URL
        if ($aparece_lista_negra) {
            return;
        }

        // Get uri and url
        $uri = Uri::getInstance();

        $rawUrl = $uri->toString(['scheme', 'host', 'port', 'path', 'query', 'fragment']);

        // rawurldecode puede introducir bytes raros; normalizamos a UTF-8 lo mejor posible
        $decoded = rawurldecode($rawUrl);
        if (!mb_check_encoding($decoded, 'UTF-8')) {
            $decoded = mb_convert_encoding($decoded, 'UTF-8', 'UTF-8');
        }

        $url = htmlspecialchars($decoded, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        // Params with defaults
        $write_log_inspector = (int) ($this->getParam('write_log_inspector', 1));
        $inspector_forbidden_words = (string) ($this->getParam(
            'inspector_forbidden_words','wp-login.php,.git,owl.prev,tmp.php,home.php,Guestbook.php,aska.cgi,default.asp,jax_guestbook.php,bbs.cg,gastenboek.php,light.cgi,yybbs.cgi,wsdl.php,wp-content,cache_aqbmkwwx.php,.suspected,seo-joy.cgi,google-assist.php,wp-main.php,sql_dump.php,xmlsrpc.php'
        ));
        $action_inspector = (int) ($this->getParam('action_inspector', 2));

        $inspector_forbidden_words_array = array_filter(
            array_map('trim', explode(',', $inspector_forbidden_words)),
            static fn(string $v): bool => $v !== ''
        );

        $found = false;
        $forbidden_word_found = '';

        foreach ($inspector_forbidden_words_array as $word) {
            $safeWord = htmlspecialchars($word, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // Match substring
            if ($safeWord !== '' && str_contains($url, $safeWord)) {
                $found = true;
                $forbidden_word_found = $safeWord;
                break;
            }
        }

        // Forbidden words found; take actions
        if (!$found) {
            return;
        }

        // Adds IP, uri and date to url_inspector database
        $data = (object) [
            'ip'              => $remote_ip,
            'uri'             => $url,
            'forbidden_words' => $forbidden_word_found,
            'date_added'      => Factory::getDate()->toSql(),
        ];

        try {
            $db->insertObject('#__securitycheckpro_url_inspector_logs', $data, 'id');
        } catch (\Throwable $e) {
			Log::add('Url_inspector. onAfterInitialise method. Error inserting data into the securitycheckpro_url_inspector_logs table: ' . $e->getMessage(), Log::ERROR, 'plg_url_inspector');
            // ignore DB insertion errors (do not break request)
        }

        // Write a log in Securitycheck Pro logs       
        $not_applicable = Text::_('COM_SECURITYCHECKPRO_NOT_APPLICABLE');

        // Grabar log en el firewall
        self::$objeto->grabar_log(
            $write_log_inspector,
            $remote_ip,
            'URL_FORBIDDEN_WORDS',
            $forbidden_word_found,
            'URL_INSPECTOR',
            $url,
            $not_applicable,
            '---',
            '---'
        );

        // Actions
        if ($action_inspector === 1) {
            // Add to dynamic blacklist
            self::$objeto->actualizar_lista_dinamica($remote_ip);
            return;
        }

        if ($action_inspector === 2) {
            // Add to blacklist
            $firewallconfig_object = new FirewallconfigModel();
            $firewallconfig_object->manage_list('blacklist', 'add', $remote_ip, false);

            // Redirect to stop request
            $error_403 = Text::_('COM_SECURITYCHECKPRO_403_ERROR');
            self::$objeto->redirection(403, $error_403, true);
        }
    }

    /**
     * Safe param getter for storage-loaded parameters.
     *
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    private function getParam(string $key, $default)
    {
        if (self::$parameters !== null && array_key_exists($key, self::$parameters)) {
            return self::$parameters[$key];
        }

        return $default;
    }

    /**
     * Loads a JSON payload from storage by key.
     *
     * @param string $key_name The value of the key in storage_key
     * @return array<string,mixed>|null
     */
    private function load(string $key_name): ?array
    {
        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = $db->getQuery(true)
            ->select($db->quoteName('storage_value'))
            ->from($db->quoteName('#__securitycheckpro_storage'))
            ->where($db->quoteName('storage_key') . ' = ' . $db->quote($key_name));

        $db->setQuery($query);

        $res = $db->loadResult();

        if (!is_string($res) || $res === '') {
            return null;
        }

        try {
            /** @var mixed $decoded */
            $decoded = json_decode($res, true, 512, JSON_THROW_ON_ERROR);

            if (is_array($decoded)) {
                /** @var array<string,mixed> $decoded */
                return $decoded;
            }
        } catch (\Throwable $e) {
			Log::add('Url_inspector. load method. Error retrieving data from the securitycheckpro_storage table: ' . $e->getMessage(), Log::ERROR, 'plg_url_inspector');
            return null;
        }

        return null;
    }
}
