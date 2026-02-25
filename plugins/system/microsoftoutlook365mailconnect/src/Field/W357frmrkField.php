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

namespace Web357\Plugin\System\Microsoftoutlook365mailconnect\Field;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Web357FrameworkHelperClass;

class W357frmrkField extends FormField
{
    protected $type = 'w357frmrk';

    protected function getLabel()
    {
        return '';
    }

    protected function getInput()
    {
        if (!PluginHelper::isEnabled('system', 'web357framework')) {
            Factory::getApplication()->enqueueMessage(Text::_('WEB357FRAMEWORK_PLUGIN_IS_REQUIRED'), 'error');
            return '';
        }

        $web357FrameworkClassFile = JPATH_PLUGINS . '/system/web357framework/web357framework.class.php';

        if (is_file($web357FrameworkClassFile)) {
            require_once $web357FrameworkClassFile;
            $w357FrameworkHelper = new Web357FrameworkHelperClass;
            $w357FrameworkHelper->apikeyChecker();
        } else {
            Factory::getApplication()->enqueueMessage(Text::_('WEB357FRAMEWORK_PLUGIN_IS_REQUIRED'), 'error');
        }

        return '';
    }
}
