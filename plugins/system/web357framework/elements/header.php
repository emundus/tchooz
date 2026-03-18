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
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

require_once(JPATH_PLUGINS . DIRECTORY_SEPARATOR . "system" . DIRECTORY_SEPARATOR . "web357framework" . DIRECTORY_SEPARATOR . "elements" . DIRECTORY_SEPARATOR . "elements_helper.php");

class JFormFieldHeader extends FormField {
	
	function getHeaderHTML()
	{
		// Retrieving request data using JInput
		$jinput = Factory::getApplication()->input;

		if (method_exists($this, 'fetchTooltip')):
			$label = $this->fetchTooltip($this->element['label'], $this->description, $this->element, $this->options['control'], $this->element['name'] = '');
		else:
			$label = parent::getLabel();
		endif;
		
		// Get Joomla's version
		$jversion = new Version;
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
		
		if (version_compare($mini_version, "2.5", "<=")) :
			// v2.5
			$jversion_class = 'vj25x';
		elseif (version_compare($mini_version, "3.0", "<=")) :
			// v3.0.x
			$jversion_class = 'vj30x';
		elseif (version_compare($mini_version, "3.1", "<=")) :
			// v3.1.x
			$jversion_class = 'vj31x';
		elseif (version_compare($mini_version, "3.2", "<=")) :
			// v3.2.x
			$jversion_class = 'vj32x';
		elseif (version_compare($mini_version, "3.3", "<=")) :
			// v3.3.x
			$jversion_class = 'vj33x';
		elseif (version_compare($mini_version, "3.4", "<=")) :
			// v3.4.x
			$jversion_class = 'vj34x';
		else:
			// other
			$jversion_class = 'j00x';
		endif;
		
		// There are two types of class, the w357_large_header, w357_small_header, w357_xsmall_header.
		$class = (!empty($this->element['class'])) ? $this->element['class'] : '';

		return '<div class="w357frm_param_header '.$class.' '.$jversion_class.' '.$jinput->get('option').'">'.$label.'</div>';
	}

	function getInput()
	{
		if (version_compare(JVERSION, '4.0', '>='))
		{
			return $this->getInput_J4();
		}
		else
		{
			return $this->getInput_J3();
		}
	}

	function getLabel()
	{
		if (version_compare(JVERSION, '4.0', '>='))
		{
			return $this->getLabel_J4();
		}
		else
		{
			return $this->getLabel_J3();
		}
	}

	protected function getLabel_J3()
	{	
		return $this->getHeaderHTML();
	}

	protected function getInput_J3()
	{
		return ' ';
	}

	protected function getLabel_J4()
	{
		return $this->getHeaderHTML();
	}

	protected function getInput_J4()
	{
		return ' ';
	}
}