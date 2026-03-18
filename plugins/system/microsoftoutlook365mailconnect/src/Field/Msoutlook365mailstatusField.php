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
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\Database\DatabaseInterface;

class Msoutlook365mailstatusField extends FormField
{
    /** @var string */
    protected $type = 'msoutlook365mailstatus';

    protected function getInput()
    {
        // Check if mail is enabled in global config
        $sendMailEnabled = (bool)Factory::getApplication()->get('mailonline', 1);
        if (!$sendMailEnabled) {
            return ''; // Return empty if mail is disabled
        }

        // Get plugin ID for the link
        $db = Factory::getContainer()->get(DatabaseInterface::class);
        $query = $db->getQuery(true)
            ->select('extension_id')
            ->from('#__extensions')
            ->where('type = ' . $db->quote('plugin'))
            ->where('folder = ' . $db->quote('system'))
            ->where('element = ' . $db->quote('microsoftoutlook365mailconnect'));
        $pluginId = $db->setQuery($query)->loadResult();

        // Build the plugin edit link
        $pluginLink = Route::_('index.php?option=com_plugins&task=plugin.edit&extension_id=' . $pluginId);

        $html = [];
        $html[] = '<div class="microsoft-outlook-365-mail-connect-status alert alert-success">';
        $html[] = '    <span class="icon-check" aria-hidden="true"></span>';
        $html[] = '    <strong>' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_STATUS_ENABLED') . '</strong>';
        $html[] = '    <p>' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_STATUS_DESC') . '</p>';
        $html[] = '    <a href="' . $pluginLink . '" class="btn btn-info btn-sm" style="color:white;">';
        $html[] = '        <span class="icon-edit" aria-hidden="true"></span> ';
        $html[] = '        ' . Text::_('PLG_SYSTEM_MICROSOFTOUTLOOK365MAILCONNECT_EDIT_SETTINGS');
        $html[] = '    </a>';
        $html[] = '</div>';

        // Add CSS to disable core fields
        $css = '
            joomla-field-send-test-mail button#sendtestmail {
                display: none;
            }
            joomla-field-send-test-mail .options-form .control-group:not(:first-child):not(:last-child) {
                opacity: 0.5;
                pointer-events: none;
                background-color: #f8f9fa;
            }
        ';

        /** @var \Joomla\CMS\WebAsset\WebAssetManager $assetManager */
        $assetManager = Factory::getApplication()->getDocument()->getWebAssetManager();
        $assetManager->addInlineStyle($css);
        return implode("\n", $html);
    }

    protected function getLabel()
    {
        return '';
    }
}