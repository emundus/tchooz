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

 
defined('JPATH_BASE') or die;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;

require_once(JPATH_PLUGINS . DIRECTORY_SEPARATOR . "system" . DIRECTORY_SEPARATOR . "web357framework" . DIRECTORY_SEPARATOR . "elements" . DIRECTORY_SEPARATOR . "elements_helper.php");

class JFormFieldcheckextension extends FormField {
	
	protected $type = 'checkextension';

	protected function getLabel()
	{
		$option = (string) $this->element["option"];

		if (!empty($option) && !$this->isActive($option))
		{
            return '<div style="color:red">'.sprintf(Text::_('W357FRM_EXTENSION_IS_NOT_ACTIVE'), $option).'</div>';
		}
		else
		{
            return '<div style="color:darkgreen">'.sprintf(Text::_('W357FRM_EXTENSION_IS_ACTIVE'), $option).'</div>';
		}
	}

	// Check if the component is installed and is enabled
	public function isActive($option) // e.g. $option = com_k2
	{
		if (!empty($option))
		{
			if(!ComponentHelper::isEnabled($option))
			{
				return false;
			}
			else
			{
				return true;
			}
		}
		else
		{
			die('The extension name is not detected.');
		}
		
	}

	protected function getInput() 
	{
		return '';
	}
	
}