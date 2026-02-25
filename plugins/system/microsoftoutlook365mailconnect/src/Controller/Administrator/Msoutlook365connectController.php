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

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Controller\Administrator;

defined('_JEXEC') or die;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Uri\Uri;
use Joomla\Database\DatabaseInterface;
use RuntimeException;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Extension\Microsoftoutlook365mailconnect;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Helper\MicrosoftOutlookApplicationHelper;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Service\MicrosoftOutlook365MailConnectMailer\MicrosoftOutlook365MailConnectMailer;

class Msoutlook365connectController extends AbstractAdministratorController
{

    /**
     * Handles the authorization process for the Microsoft Outlook application.
     * @return void
     */
    public function actionAuthorize(): void
    {
        try {
            MicrosoftOutlookApplicationHelper::getInstance()->authorize($_GET['code'] ?? '');
            $this->application->enqueueMessage(Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_AUTHORIZATION_WAS_SUCCESSFUL'), 'success');
            Factory::getCache()->clean('com_plugins');
            Factory::getCache()->clean('_system');
        } catch (\Exception $e) {
            $this->application->enqueueMessage(sprintf(Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_ERROR_IN_AUTHORIZATION'), $e->getMessage()), 'error');
        }
        $this->redirectToPluginConfiguration();
    }

    /**
     * Deletes the OAuth access token from the database.
     * @return void
     */
    public function actionRevokeToken(): void
    {
        if (!Session::checkToken() || !isset($_POST['revoke'])) {
            return;
        }
        try {
            Microsoftoutlook365mailconnect::savePluginParams([
                'oauth_access_token' => '',
            ]);
            $this->application->enqueueMessage(Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_REVOKE_SUCCESS'), 'success');
            Factory::getCache()->clean('com_plugins');
            Factory::getCache()->clean('_system');
        } catch (\Exception $e) {
            $this->application->enqueueMessage(sprintf(Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_ERROR_IN_REVOKE'), $e->getMessage()), 'error');
        }
        $this->redirectToPluginConfiguration();
    }

    public function actionSendTestEmail(): void
    {
        if (!Session::checkToken()) {
            return;
        }

        try {
            $testEmail = $_POST['email'] ?? '';
            if (filter_var($testEmail, FILTER_VALIDATE_EMAIL) === false) {
                throw new \Exception(Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_ERROR_EMAIL_FORM'));
            }

            $mailer = new MicrosoftOutlook365MailConnectMailer(true);

            // Get site info
            $sitename = Factory::getApplication()->getConfig()->get('sitename');
            $siteurl = Uri::root();

            // Get user info
            $user = $this->application->getIdentity();
            $username = $user->username;

            // Get current date and time
            $date = Factory::getDate()->format('Y-m-d H:i:s');

            $helper = MicrosoftOutlookApplicationHelper::getInstance();
            $configuredEmail = $helper->getConfiguredEmail();
            $oauthFromEmail = $helper->getOauthFromEmail();
            $fromEmail = $oauthFromEmail ? $oauthFromEmail . ' (sent by delegated account: ' . $configuredEmail . ')' : $configuredEmail;

            // Set subject with site name
            $mailer->setSubject(sprintf('Microsoft Auth 2.0 Mail Test Email from %s', $sitename));
            $mailer->isHTML(true);

            // Create detailed HTML body
            $body = <<<HTML
                    <h1>Test Email from Microsoft/Outlook 365 Mail Connect</h1>
                    <p>This is a test email sent from your Microsoft/Outlook 365 Mail Connect settings.</p>
                    
                    <h2>Email Details:</h2>
                    <table style="border-collapse: collapse; width: 100%; max-width: 600px; margin: 20px 0;">
                        <tr>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5;">Site Name:</th>
                            <td style="padding: 8px; border: 1px solid #ddd;">{$sitename}</td>
                        </tr>
                        <tr>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5;">Site URL:</th>
                            <td style="padding: 8px; border: 1px solid #ddd;">{$siteurl}</td>
                        </tr>
                        <tr>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5;">Sent From:</th>
                            <td style="padding: 8px; border: 1px solid #ddd;">{$fromEmail}</td>
                        </tr>
                        <tr>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5;">Sent To:</th>
                            <td style="padding: 8px; border: 1px solid #ddd;">{$testEmail}</td>
                        </tr>
                        <tr>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5;">Sent By User:</th>
                            <td style="padding: 8px; border: 1px solid #ddd;">{$username}</td>
                        </tr>
                        <tr>
                            <th style="text-align: left; padding: 8px; border: 1px solid #ddd; background-color: #f5f5f5;">Date & Time:</th>
                            <td style="padding: 8px; border: 1px solid #ddd;">{$date}</td>
                        </tr>
                    </table>
                    
                    <p><strong>Note:</strong> If you received this email, it means your Microsoft/Outlook 365 account settings are configured correctly.</p>
                    
                    <p style="margin-top: 20px; font-size: 12px; color: #666;">
                        This is an automated message sent from Microsoft/Outlook 365 Connect plugin for Joomla! by Web357.
                        Please do not reply to this email.
                    </p>
                    HTML;

            $mailer->addRecipient($testEmail);
            $mailer->setBody($body);

            // Add a timeout to prevent infinite waiting
            $mailer->Timeout = 30; // Set timeout to 30 seconds

            // Start time tracking
            $startTime = microtime(true);

            // Use set_time_limit to prevent PHP timeout
            set_time_limit(40); // Give slightly more than Auth 2.0 timeout

            $sendResult = $mailer->Send();
            $executionTime = microtime(true) - $startTime;

            if (!$sendResult) {
                throw new RuntimeException($mailer->ErrorInfo ?: 'Email sending failed without specific error');
            }
            Log::add(sprintf('Email sending took %.2f seconds', $executionTime), Log::INFO, 'plg_system_microsoftoutlook365mailconnect');
            $this->application->enqueueMessage(sprintf(Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_TEST_EMAIL_SUCCESS'), $testEmail), 'success');
        } catch (Exception $e) {
            Log::add('Microsoft/Outlook 365 Mail Connect Send Error: ' . $e->getMessage(), Log::ERROR, 'plg_system_microsoftoutlook365mailconnect');
            $this->application->enqueueMessage(Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_TEST_EMAIL_FAILED') . ': ' . $e->getMessage(), 'error');
        }

        $this->redirectToPluginConfiguration();
    }

    /**
     * Redirects to the plugin configuration page for the Microsoft/Outlook 365 Mail Connect plugin.
     * @return void
     */
    protected function redirectToPluginConfiguration(): void
    {
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select($db->quoteName('extension_id'))
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('system'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('microsoftoutlook365mailconnect'));
        $redirectUrl = 'index.php?option=com_plugins&view=plugin&layout=edit&extension_id=' . $db->setQuery($query)->loadResult();
        $this->application->redirect($redirectUrl);
    }
}