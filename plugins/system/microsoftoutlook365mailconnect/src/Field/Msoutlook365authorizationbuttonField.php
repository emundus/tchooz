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

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Extension\Microsoftoutlook365mailconnect;
use Web357\Plugin\System\Microsoftoutlook365mailconnect\Helper\MicrosoftOutlookApplicationHelper;

/**
 * Form Field class for Gmail OAuth authentication button
 */
class Msoutlook365authorizationbuttonField extends FormField
{

    /** @var MicrosoftOutlookApplicationHelper */
    protected $microsoftOutlookApplicationHelper;

    /**
     * The form field type.
     *
     * @var    string
     */
    protected $type = 'msoutlook365authorizationbutton';

    public function __construct($form = null)
    {
        parent::__construct($form);
        $this->microsoftOutlookApplicationHelper = MicrosoftOutlookApplicationHelper::getInstance();
    }

    public function getLabel()
    {
    }

    /**
     * Method to get the field input markup.
     *
     * @return  string  The field input markup.
     */
    protected function getInput()
    {
        $html = [];

        if (!Microsoftoutlook365mailconnect::isEnabled()) {
            $html[] = '<p>' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_PLUGIN_DISABLED_AUTHENTICATE') . '</p>';
        } elseif ($this->microsoftOutlookApplicationHelper->isAuthorized()) {
            $html[] = $this->getApplicationIsAuthorizedSection();
            $html[] = $this->getSendTestEmailSection();
        } elseif (!$this->microsoftOutlookApplicationHelper->isConfigured()) {
            $html[] = '<p>' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_OAUTH_BUTTON_DISABLED') . '</p>';
        } else {
            $html[] = HTMLHelper::_('link', $this->microsoftOutlookApplicationHelper->getAuthorizationUrl(), '', [
                'class' => 'web357-microsoft-login-btn',
            ]);
        }

        return implode("\n", $html);
    }

    /**
     * Generates and returns HTML content for the authenticated section.
     * This section includes information about the authentication status
     * and provides a revoke instructions for the user.
     *
     * @return string The authenticated section as an HTML string.
     */
    protected function getApplicationIsAuthorizedSection(): string
    {
        $html = [];
        $html[] = '<div class="gmail-oauth-status alert alert-info">';
        $html[] = '    <h4 class="alert-heading">' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_AUTHENTICATED') . '&nbsp;(' . $this->microsoftOutlookApplicationHelper->getConfiguredEmail() . ')&nbsp; ✅</h4>';
        $html[] = '    <p class="my-3">' . Text::sprintf('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_REVOKE_INSTRUCTIONS', 'https://docs.web357.com/article/157-microsof-toutlook-365-mail-connect-configuration#disconnect') . '</p>';

        // Revoke button with CSRF token
        $html[] = '        <button class="btn btn-danger" id="web357-revoke-microsoft-token" osnclick="return confirm(\'' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_REVOKE_CONFIRM') . '\');">';
        $html[] = '            <span class="icon-trash"></span> ';
        $html[] = '            ' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_REVOKE_BUTTON');
        $html[] = '        </button>';
        $html[] = '</div>';
        return implode("\n", $html);
    }

    /**
     * Generates and returns the HTML content for the "Send Test Email" section.
     * This section allows the user to input an email address and send a test email.
     * It includes conditions for enabling or disabling the email input and button
     * based on the authentication status and global mail settings.
     *
     * @return string The "Send Test Email" section as an HTML string.
     */
    protected function getSendTestEmailSection()
    {
        // Check if mail is enabled in global config
        $sendMailEnabled = (bool)Factory::getApplication()->get('mailonline', 1);

        $html = [];
        $html[] = '<div class="send-test-email-wrapper">';

        // default email address from global config
        $defaultEmail = Factory::getApplication()->get('mailfrom');

        // Send To field
        $html[] = '    <div class="control-group mb-2">';
        $html[] = '        <div class="controls">';
        $html[] = '           <input type="email" id="web357_microsoft_test_email" class="form-control" value="' . $defaultEmail . '"' . ($sendMailEnabled ? '' : ' disabled') . '>';
        $html[] = '            <div class="form-text">' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_TEST_EMAIL_TO_DESC') . '</div>';

        // Show message when email sending is disabled
        if (!$sendMailEnabled) {
            $html[] = '            <div class="alert alert-warning mt-2">';
            $html[] = '                <span class="icon-exclamation-circle" aria-hidden="true"></span> ';
            $html[] = '                ' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_TEST_EMAIL_DISABLED');
            $html[] = '            </div>';
        }

        $html[] = '        </div>';
        $html[] = '    </div>';

        $html[] = '    <div class="control-group">';
        $html[] = '        <div class="controls">';
        $html[] = '                <button id="web357-microsoft-test-email" class="btn btn-primary"' . ($sendMailEnabled ? '' : ' disabled') . '>';
        $html[] = '                    <span class="icon-envelope"></span> ';
        $html[] = '                    ' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_TEST_EMAIL_SEND_BUTTON');
        $html[] = '                </button>';
        $html[] = '        </div>';
        $html[] = '    </div>';
        $html[] = '</div>';

        return implode("\n", $html);
    }
}