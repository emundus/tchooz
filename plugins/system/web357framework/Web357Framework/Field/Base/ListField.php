<?php
/* ======================================================
 # Web357 Framework for Joomla! - v2.0.0 (free version)
 # -------------------------------------------------------
 # For Joomla! CMS (v4.x)
 # Author: Web357 (Yiannis Christodoulou)
 # Copyright: (©) 2014-2024 Web357. All rights reserved.
 # License: GNU/GPLv3, https://www.gnu.org/licenses/gpl-3.0.html
 # Website: https://www.web357.com
 # Support: support@web357.com
 # Last modified: Monday 27 October 2025, 03:04:38 PM
 ========================================================= */

namespace Web357Framework\Field\Base;

use Web357Framework\Compatibility\BaseListField;

defined('_JEXEC') or die();

/**
 * Compatibility Layer for ListField
 *
 * This script ensures compatibility between Joomla 3.x and Joomla 4.x/5.x by
 * aliasing the appropriate ListField class. If the Joomla 4.x/5.x `ListField`
 * class exists, it will be used. Otherwise, the Joomla 3.x `JFormFieldList`
 * class will be used.
 */
if (class_exists('\Joomla\CMS\Form\Field\ListField')) {
    class_alias('\Joomla\CMS\Form\Field\ListField', '\Web357Framework\Compatibility\BaseListField');
} else {
    class_alias('JFormFieldList', '\Web357Framework\Compatibility\BaseListField');
}

/**
 * Base ListField Class
 *
 * This class extends the appropriate ListField class (either from Joomla 3.x or 4.x/5.x)
 * to provide a consistent interface for list fields across different Joomla versions.
 */
class ListField extends BaseListField
{

}
