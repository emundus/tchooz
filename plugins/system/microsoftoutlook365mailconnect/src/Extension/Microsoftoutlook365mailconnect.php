<?php

/* ======================================================
 # Microsoft/Outlook 365 Mail Connect for Joomla! - v1.0.8 (pro version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html   
 # Website: https://www.web357.com
 # Demo: 
 # Support: support@web357.com
 # Last modified: Tuesday 03 February 2026, 10:20:16 AM
 ========================================================= */
declare(strict_types=1);

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Extension;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Database\DatabaseDriver;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\EventInterface;
use Joomla\Event\SubscriberInterface;
use Joomla\Registry\Registry;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Controller\Administrator\Msoutlook365connectController;

class Microsoftoutlook365mailconnect extends CMSPlugin implements SubscriberInterface
{
    protected $app;

    public function __construct(&$subject, $config = [])
    {
        parent::__construct($subject, $config);
        $this->app = Factory::getApplication();
        Log::addLogger(['text_file' => 'plg_system_microsoftoutlook365mailconnect.log.php'], Log::ALL, ['plg_system_microsoftoutlook365mailconnect']);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareForm' => 'overrideEmailSettingsForm',
            'onAfterInitialise' => 'registerWeb35Controller',
        ];
    }

    /**
     * Overrides Joomla Email Settings form
     *
     * @param \Joomla\Event\Event|\Joomla\CMS\Event\Model\PrepareFormEvent $event
     * @return void
     */
    public function overrideEmailSettingsForm(EventInterface $event): void
    {
        [$form] = array_values($event->getArguments());

        if (!($form instanceof Form) || $form->getName() !== 'com_config.application') {
            return;
        }

        Form::addFormPath(JPATH_PLUGINS . '/system/microsoftoutlook365mailconnect/forms');
        $form->loadFile('config', false);
    }

    /**
     * Registers Web357 Controller for Microsoft Outlook integration
     *
     * @return void
     */
    public function registerWeb35Controller(): void
    {
        $this->loadLanguage('plg_system_microsoftoutlook365mailconnect', JPATH_PLUGINS . '/system/microsoftoutlook365mailconnect');

        // Controller runs only in the administration area
        if (!$this->app->isClient('administrator')) {
            return;
        }

        // Only super admin can configure
        $user = $this->app->getIdentity();
        $isSuper = !is_null($user) && $user->authorise('core.admin');
        $url = (string)($_SERVER['REQUEST_URI'] ?? '');
        $url = explode('ms365', $url);
        $controller = $_GET['web357controller'] ?? '';
        $action = $_GET['web357task'] ?? '';
        if (isset($url[1]) && substr($url[1], 0, 45) === '/microsoft-outlook-365-mail-connect-authorize') {
            $controller = 'microsoft-outlook-controller';
            $action = 'authorize';
        }

        if ($isSuper && $controller === 'microsoft-outlook-controller') {
            (new Msoutlook365connectController())->handle($action);
        }
    }

    /**
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return PluginHelper::isEnabled('system', 'microsoftoutlook365mailconnect');
    }

    /**
     * @return Registry
     */
    public static function getPluginParams(): Registry
    {
        $plugin = PluginHelper::getPlugin('system', 'microsoftoutlook365mailconnect');
        return new Registry($plugin ? $plugin->params : []);
    }

    /**
     * Saves the plugin parameters to the database.
     *
     * @param array $updateValues The parameters to be saved, usually in key-value format.
     * @return void
     */
    public static function savePluginParams(array $updateValues): void
    {
        $plugin = PluginHelper::getPlugin('system', 'microsoftoutlook365mailconnect');
        $params = new Registry($plugin->params);
        foreach ($updateValues as $key => $value) {
            $params->set($key, $value);
        }

        /** @var DatabaseDriver $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->update($db->quoteName('#__extensions'))
            ->set($db->quoteName('params') . ' = ' . $db->quote($params->toString()))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('microsoftoutlook365mailconnect'));

        $db->setQuery($query)->execute();
        Factory::getCache()->clean('com_plugins');
        Factory::getCache()->clean('_system');
    }

}