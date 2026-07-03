<?php

/**
 * @Securitycheckpro plugin - Shared helper
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */

namespace Joomla\Plugin\System\Securitycheckpro\Helper;

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Mail\MailerFactoryInterface;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\BaseModel;
use SecuritycheckExtensions\Component\SecuritycheckPro\Administrator\Model\FirewallconfigModel;

class SecuritycheckProHelper
{
    public static function grabarLog(
        string|bool $logs_attacks,
        string $ip,
        string $tag_description,
        string $description,
        string $type,
        string $uri,
        string $original_string,
        string $username,
        string $component
    ): void {
        if (!$logs_attacks) {
            return;
        }

        $pro_plugin = new BaseModel();

        /** @var DatabaseInterface $db */
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $ip = filter_var((string) $ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';

        $allowedTagKeys = [
            'TAGS_STRIPPED','DUPLICATE_BACKSLASHES','LINE_COMMENTS','SQL_PATTERN','IF_STATEMENT',
            'INTEGERS','BACKSLASHES_ADDED','LFI','IP_BLOCKED','IP_BLOCKED_DINAMIC','IP_PERMITTED',
            'FORBIDDEN_WORDS','SESSION_PROTECTION','UPLOAD_SCANNER','FAILED_LOGIN_ATTEMPT_LABEL','HEURISTIC_SQL',
            'SPAM_PROTECTION','URL_FORBIDDEN_WORDS','CMD_INJECTION','CRLF_INJECTION'
        ];
        $tag_description = strtoupper(trim((string) $tag_description));
        if (!in_array($tag_description, $allowedTagKeys, true)) {
            $tag_description = 'UNKNOWN_EVENT';
        }

        $stripCtl = static function (?string $s): string {
            $s = (string) $s;
            $s = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s) ?? '';
            return trim($s);
        };
        $clip = static function (string $s, int $max): string {
            return mb_substr($s, 0, $max, 'UTF-8');
        };

        $username    = $clip($stripCtl((string) $username), 150);
        $description = $clip($stripCtl((string) $description), 255);
        $type        = $clip($stripCtl((string) $type), 50);
        $uri         = $clip($stripCtl((string) $uri), 1000);
        $component   = $clip($stripCtl((string) $component), 150);

        $rawOriginal = (string) $original_string;
        $MAX_ORIGINAL_BYTES = 16384;
        if (strlen($rawOriginal) > $MAX_ORIGINAL_BYTES) {
            $rawOriginal = substr($rawOriginal, 0, $MAX_ORIGINAL_BYTES);
        }
        $original_b64 = base64_encode($rawOriginal);

        $logs_per_ip = (int) $pro_plugin->getValue('log_limits_per_ip_and_day', 30, 'pro_plugin');

        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app    = Factory::getApplication();
        $config = $app->getConfig();
        $offset = $config->get('offset') ?: 'UTC';
        $tz     = new \DateTimeZone($offset);
        $now    = (new \DateTime('now', $tz))->format('Y-m-d H:i:s');
        $start  = (new \DateTime('today', $tz))->format('Y-m-d H:i:s');
        $end    = (new \DateTime('tomorrow', $tz))->format('Y-m-d H:i:s');

        try {
            $q = $db->getQuery(true)
                ->select('COUNT(*)')
                ->from($db->quoteName('#__securitycheckpro_logs'))
                ->where($db->quoteName('ip')   . ' = ' . $db->quote($ip))
                ->where($db->quoteName('time') . ' >= ' . $db->quote($start))
                ->where($db->quoteName('time') . ' < '  . $db->quote($end));
            $db->setQuery($q);
            $logs_recorded = (int) $db->loadResult();
        } catch (\Throwable $e) {
            $logs_recorded = 0;
        }
        if ($logs_per_ip > 0 && $logs_recorded >= $logs_per_ip) {
            return;
        }

        $q = $db->getQuery(true)
            ->select('1')
            ->from($db->quoteName('#__securitycheckpro_logs'))
            ->where($db->quoteName('ip')              . ' = ' . $db->quote($ip))
            ->where($db->quoteName('tag_description') . ' = ' . $db->quote($tag_description))
            ->where($db->quoteName('original_string') . ' = ' . $db->quote($original_b64))
            ->where($db->quoteName('time')            . ' >= ' . $db->quote($start))
            ->where($db->quoteName('time')            . ' < '  . $db->quote($end))
            ->setLimit(1);
        $db->setQuery($q);
        if ($db->loadResult()) {
            return;
        }

        $limits = [
            'ip'              => 255,
            'username'        => 150,
            'time'            => 19,
            'tag_description' => 50,
            'description'     => 300,
            'type'            => 50,
            'uri'             => 100,
            'component'       => 150,
        ];

        $fit = static function (?string $value, ?int $max): string {
            $value = (string) ($value ?? '');
            if ($max !== null && $max > 0 && mb_strlen($value, 'UTF-8') > $max) {
                return mb_substr($value, 0, $max, 'UTF-8');
            }
            return $value;
        };

        $uri_original_completo = (string) $uri;

        $ip              = $fit((string) $ip,              $limits['ip']);
        $username        = $fit((string) $username,        $limits['username']);
        $now             = Factory::getDate()->toSql();
        $now             = $fit($now,                      $limits['time']);
        $tag_description = $fit((string) $tag_description, $limits['tag_description']);
        $description     = $fit((string) $description,     $limits['description']);
        $type            = $fit((string) $type,            $limits['type']);
        $uri             = $fit((string) $uri,             $limits['uri']);
        $component       = $fit((string) $component,       $limits['component']);

        if (!empty($uri_original_completo) && $uri_original_completo !== $uri) {
            $original_string .= "\n[full_uri]: " . $uri_original_completo;
            $original_b64 = base64_encode($original_string);
        }
        $original_b64 = (string) $original_b64;

        $q = $db->getQuery(true)
            ->insert($db->quoteName('#__securitycheckpro_logs'))
            ->columns([
                $db->quoteName('ip'),
                $db->quoteName('username'),
                $db->quoteName('time'),
                $db->quoteName('tag_description'),
                $db->quoteName('description'),
                $db->quoteName('type'),
                $db->quoteName('uri'),
                $db->quoteName('component'),
                $db->quoteName('original_string'),
            ])
            ->values(implode(', ', [
                $db->quote($ip),
                $db->quote($username),
                $db->quote($now),
                $db->quote($tag_description),
                $db->quote($description),
                $db->quote($type),
                $db->quote($uri),
                $db->quote($component),
                $db->quote($original_b64),
            ]));

        try {
            $db->setQuery($q);
            $db->execute();
        } catch (\Throwable $e) {
            Log::add(
                'SecuritycheckProHelper::grabarLog: error al insertar entrada. ' . $e->getMessage(),
                Log::ERROR,
                'com_securitycheckpro',
                null,
                ['uri_len' => mb_strlen((string) $uri, 'UTF-8')]
            );
        }

        $blacklist_email      = 1;
        $send_email_inspector = 0;

        if ($tag_description === 'IP_BLOCKED' || $tag_description === 'IP_BLOCKED_DINAMIC') {
            $blacklist_email = (int) $pro_plugin->getValue('blacklist_email', 0, 'pro_plugin');
        }
        $send_email_inspector = (int) $pro_plugin->getValue('send_email_inspector', 0, 'pro_plugin');

        $email_active = (int) $pro_plugin->getValue('email_active', 0, 'pro_plugin');

        if ($email_active) {
            $shouldSend =
                (
                    $tag_description !== 'IP_BLOCKED' &&
                    $tag_description !== 'URL_FORBIDDEN_WORDS'
                )
                || ($tag_description === 'IP_BLOCKED' && $blacklist_email)
                || ($tag_description === 'URL_FORBIDDEN_WORDS' && $send_email_inspector);

            if ($shouldSend) {
                $lang = $app->getLanguage();
                $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

                $subject = sprintf(
                    '%s %s | %s %s | IP: %s',
                    $lang->_('COM_SECURITYCHECKPRO_RULE'),
                    $lang->_('COM_SECURITYCHECKPRO_' . $tag_description),
                    $lang->_('COM_SECURITYCHECKPRO_USERNAME'),
                    ($username !== '' ? $username : '-'),
                    $ip
                );
                $subject = preg_replace("/[\r\n]+/", ' ', $subject) ?: 'SecuritycheckPro alert';

                self::mandarCorreo($subject, $pro_plugin);
            }
        }
    }

    public static function actualizarListaDinamica(string $attack_ip): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $pro_plugin = new BaseModel();

        $dynamic_blacklist = $pro_plugin->getValue('dynamic_blacklist', 1, 'pro_plugin');

        $ip_valid = filter_var($attack_ip, FILTER_VALIDATE_IP);

        $attack_ip = $db->escape($attack_ip);

        if ((!empty($attack_ip)) && ($ip_valid) && ($dynamic_blacklist)) {
            try {
                $dbtype = Factory::getContainer()->get('DatabaseDriver')->getName();

                if (str_contains($dbtype, 'mysql')) {
                    $query = "INSERT INTO #__securitycheckpro_dynamic_blacklist (ip, timeattempt) VALUES ('{$attack_ip}', NOW()) ON DUPLICATE KEY UPDATE timeattempt = NOW(), counter = counter + 1;";
                } elseif (str_contains($dbtype, 'pgsql')) {
                    $query = "INSERT INTO #__securitycheckpro_dynamic_blacklist (ip, timeattempt) VALUES ('{$attack_ip}', NOW()) ON CONFLICT (ip) DO UPDATE SET timeattempt = NOW(), counter = #__securitycheckpro_dynamic_blacklist.counter + 1;";
                } else {
                    return;
                }

                $db->setQuery($query);
                $db->execute();

                $firewall_model = new FirewallconfigModel();

                $control_center_enabled = $firewall_model->control_center_enabled();

                if ($control_center_enabled) {
                    $firewall_model->add_info_control_center($attack_ip, 'dynamic_blacklist');
                }
            } catch (\Exception $e) {
            }
        }
    }

    public static function redirection(int $code, string $message, bool $blacklist = false, ?string $ip = null, ?int $time = null): void
    {
        $pro_plugin = new BaseModel();

        $redirect_after_attack = $pro_plugin->getValue('redirect_after_attack', 0, 'pro_plugin');
        $redirect_options      = $pro_plugin->getValue('redirect_options', 1, 'pro_plugin');
        $redirect_url          = $pro_plugin->getValue('redirect_url', '', 'pro_plugin');
        $custom_code           = $pro_plugin->getValue('custom_code', 'The webmaster has forbidden your access to this site', 'pro_plugin');
        $dynamic_blacklist     = $pro_plugin->getValue('dynamic_blacklist', 1, 'pro_plugin');

        /** @var \Joomla\CMS\Application\CMSApplication $app */
        $app  = Factory::getApplication();
        $lang = $app->getLanguage();
        $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

        if ($ip !== null) {
            $custom_code .= "<br/>" . Text::sprintf($lang->_('COM_SECURITYCHECKPRO_YOUR_IP'), $ip);
        }

        if ($time !== null) {
            $custom_code .= "<br/>" . Text::sprintf($lang->_('COM_SECURITYCHECKPRO_COME_BACK_IN'), $time / 60);
        }

        $is_admin = $app->isClient('administrator');

        if ($redirect_after_attack) {
            if (!$blacklist) {
                if (($is_admin) || !($dynamic_blacklist)) {
                    header('HTTP/1.1 403 Forbidden');
                    die($custom_code);
                }
                if ((int) $redirect_options === 1) {
                    $app->enqueueMessage($message, 'error');
                } elseif ((int) $redirect_options === 2) {
                    $app->redirect(Uri::root() . $redirect_url);
                }
            } else {
                header('HTTP/1.1 403 Forbidden');
                die($custom_code);
            }
        } else {
            header('HTTP/1.1 403 Forbidden');
            die($custom_code);
        }
    }

    public static function mandarCorreo(string $alerta, ?BaseModel $pro_plugin = null): void
    {
        if ($pro_plugin === null) {
            $pro_plugin = new BaseModel();
        }

        $subject             = $pro_plugin->getValue('email_subject', '', 'pro_plugin');
        $body                = $pro_plugin->getValue('email_body', '', 'pro_plugin');
        $email_add_applied_rule = $pro_plugin->getValue('email_add_applied_rule', 1, 'pro_plugin');
        $email_to            = $pro_plugin->getValue('email_to', '', 'pro_plugin');
        $to = array_values(array_filter(
            array_map('trim', explode(',', $email_to)),
            static fn(string $v): bool => $v !== ''
                && filter_var($v, FILTER_VALIDATE_EMAIL) !== false
                && !in_array(strtolower(substr($v, (int)strpos($v, '@') + 1)), ['yourdomain.com', 'mydomain.com'], true)
        ));
        if ($to === []) {
            return;
        }
        $email_from_domain   = $pro_plugin->getValue('email_from_domain', '', 'pro_plugin');
        $email_from_name     = $pro_plugin->getValue('email_from_name', '', 'pro_plugin');
        $from                = [$email_from_domain, $email_from_name];
        $email_limit         = $pro_plugin->getValue('email_max_number', 20, 'pro_plugin');
        $today               = date("Y-m-d");

        $db = Factory::getContainer()->get(DatabaseInterface::class);

        $query = "UPDATE #__securitycheckpro_emails SET envoys=0, send_date='{$today}' WHERE (send_date < '{$today}')";
        $db->setQuery($query);
        $db->execute();

        $query = "SELECT envoys FROM #__securitycheckpro_emails WHERE (send_date = '{$today}')";
        $db->setQuery($query);
        (int) $envoys = $db->loadResult();

        if ($envoys < $email_limit) {
            /** @var \Joomla\CMS\Application\CMSApplication $app */
            $app  = Factory::getApplication();
            $lang = $app->getLanguage();
            $lang->load('com_securitycheckpro', JPATH_ADMINISTRATOR);

            if ($email_add_applied_rule) {
                $body = $body . '<br />' . $alerta;
            }

            try {
                $mailer = Factory::getContainer()->get(MailerFactoryInterface::class)->createMailer();
                $mailer->setSender($from);
                $mailer->addRecipient($to);
                $mailer->setSubject($subject);
                $mailer->setBody($body);
                $mailer->isHTML(true);
                $mailer->Encoding = 'base64';
                $send = $mailer->Send();
            } catch (\Throwable $e) {
                $send = false;
            }

            if ($send === true) {
                $db = Factory::getContainer()->get(DatabaseInterface::class);
                $query = "UPDATE `#__securitycheckpro_emails` SET envoys=envoys+1 WHERE (send_date = '{$today}')";
                $db->setQuery($query);
                $db->execute();
            }
        }
    }
}
