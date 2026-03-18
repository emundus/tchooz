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
use Joomla\CMS\Language\Text;

require_once(JPATH_PLUGINS . DIRECTORY_SEPARATOR . "system" . DIRECTORY_SEPARATOR . "web357framework" . DIRECTORY_SEPARATOR . "elements" . DIRECTORY_SEPARATOR . "elements_helper.php");

class JFormFieldProfeature extends FormField {
	
	protected $type = 'profeature';

	protected function getLabel()
	{
		// Get Joomla's version
		$jversion = new Version;
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
		$major_version = 'v'.$short_version[0].'x'; // v3x

		// Data
		$id = $this->element["id"];
		$label = Text::_($this->element["label"]);
		if (version_compare($mini_version, "3.8", ">="))
		{
			// is Joomla! 4.x
			$title = '';
			$data_content = Text::_($this->element["description"]);
			$data_original_title = $label;
			$class = 'hasPopover';
		}
		else
		{
			// Joomla! 2.5.x and Joomla! 3.x
			$title = '&lt;strong&gt;'.Text::_($this->element["label"]).'&lt;/strong&gt;&lt;br /&gt;'.Text::_($this->element["description"]);
			$data_content = '';
			$data_original_title = '';
			$class = 'hasTooltip';
		}

		// an einai j4 den to deixneis, alliws to deixneis
		
		return '<label id="jform_params_'.$id.'-lbl" for="jform_params_'.$id.'" class="'.$class.'" title="'.$title.'" data-content="'.$data_content.'" data-original-title="'.$data_original_title.'">'.$label.'</label>';	
	}

	protected function getInput() 
	{
		// Get Joomla's version
		$jversion = new Version;
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
		$major_version = 'v'.$short_version[0].'x'; // v3x

		// Data
		$id = $this->element["id"];
		$label = Text::_($this->element["label"]);
		if (version_compare($mini_version, "2.5", "<="))
		{
			// is Joomla! 2.5.x
			$style = ' style="padding-top: 5px; font-style: italic; display: block; clear: both;"';
		}
		else
		{
			$style = ' style="padding-top: 5px; font-style: italic; display: inline-block;"';
		}

		$class = '';
		if (isset($this->element["class"]))
		{
			$class = $this->element["class"];
			$class = str_replace('btn-group btn-group-yesno', '', $class);
			$class = ' class="'.$class.'"';
		}

		// Get the Product ID from web357.com
		$component = Factory::getApplication()->input->get('component', '', 'STRING');
		$product_id = Functions::getProductId($component);

		// Build the link to the pro version
		$link_to_pro = '<a href="//www.web357.com/joomla-pricing?product_id='.$product_id.'&utm_source=CLIENT&utm_medium=CLIENT-ProLink-web357&utm_content=CLIENT-ProLink&utm_campaign=radiofelement" target="_blank">PRO</a>';
		$html = '<div'.$style.''.$class.'>'.sprintf(Text::_('W357FRM_ONLY_IN_PRO'), $link_to_pro).'</div>';

		return $html;
	}
}