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
use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Version;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;

require_once(JPATH_PLUGINS . DIRECTORY_SEPARATOR . "system" . DIRECTORY_SEPARATOR . "web357framework" . DIRECTORY_SEPARATOR . "elements" . DIRECTORY_SEPARATOR . "elements_helper.php");

class JFormFieldDescription extends FormField {
	
	protected $type = 'description';

	/**
	 * Get the description after installation with useful buttons and links.
	 * 
	 * @extension_type = "plugin"
	 * @extension_name = "loginasuser"
	 * @plugin_type = "system"
	 * @real_name = "Login as User"
	 */
	function getHtmlDescription($extension_type = '', $extension_name = '', $plugin_type = '', $real_name = '')
	{
		// Get extension's details from XML
		$extension_type = (!empty($extension_type)) ? $extension_type : $this->element['extension_type']; // component, module, plugin 
		$extension_name = (!empty($extension_name)) ? $extension_name : preg_replace('/(plg_|com_|mod_)/', '', $this->element['extension_name']);
		$plugin_type = (!empty($plugin_type)) ? $plugin_type : $this->element['plugin_type'].' '; // system, authentication, content etc.
		$real_name = (!empty($real_name)) ? $real_name : $this->element['real_name'];
		$real_name = Text::_($real_name);
		
		// Retrieving request data using JInput
		$jinput = Factory::getApplication()->input;
		$juri_base = str_replace('/administrator', '', Uri::base());

		// Get Joomla's version
		$jversion = new Version;
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
		$major_version = 'v'.$short_version[0].'x'; // v3x
		
		/**
		 *  Get extension details from the json file
		 */
		$web357_items_json_file = 'https://cdn.web357.com/extension-info/'.$extension_name.'-info.json';

		$web357_items_data = '';
		if (self::url_exists($web357_items_json_file))
		{
			if (self::_allowUrlFopen())
			{
				$web357_items_data = file_get_contents($web357_items_json_file);
				$web357_items_data = json_decode($web357_items_data);
			}
			elseif (self::_isCurl()) // check if extension=php_curl.dll is enabled from php.ini
			{
				// cUrl method
				$ch = curl_init();

				$options = array(
					CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification
					CURLOPT_RETURNTRANSFER => true, // // Will return the response, if false it print the response
					CURLOPT_URL            => $web357_items_json_file, // Set the url
					CURLOPT_CONNECTTIMEOUT => 120,
					CURLOPT_TIMEOUT        => 120,
					CURLOPT_MAXREDIRS      => 10,
				);

				curl_setopt_array( $ch, $options ); // Add options to array
				
				$web357_items_data = curl_exec($ch); // Execute

				curl_close($ch); // Closing

				// get data in a json
				$web357_items_data = json_decode($web357_items_data);

			}
		}
	
		if (!isset($web357_items_data->$extension_name))
		{
			return Text::_('W357FRM_NO_DESCRIPTION_FOR_THIS_EXTENSION');
		}

		// item vars
		$web357_item = $web357_items_data->$extension_name;
		$extension_type = str_replace('_', ' ', $web357_item->extension_type);
		$live_demo_url = $web357_item->live_demo_url;
		$more_info_url = $web357_item->more_info_url;
		$documentation_url = $web357_item->documentation_url;
		$changelog_url = $web357_item->changelog_url;
		$support_url = $web357_item->support_url;
		$jed_url = $web357_item->jed_url;
		$backend_settings_url = $web357_item->backend_settings_url;
		$ext_desc_html = $web357_item->description;
		$ext_desc_features_html = $web357_item->description_features;

		if (version_compare( $mini_version, "2.5", "<="))
		{
			$backend_settings_url = str_replace('filter[search]', 'filter_search', $backend_settings_url);
		}

		// output
		$html = '';

		// Header
		$html .= '<h1>'.$real_name.' - Joomla! '.$extension_type.'</h1>';

		// begin container
		$container_style = $jinput->get('option') == 'com_installer' ? ' style="margin: 30px !important;"' : '';

		// Header
		$html .= '<div class="web357framework-description-text w357-container '.$major_version.' w357 '.$jinput->get('option').'"'.$container_style.'>';
		$html .= '<div class="row row-fluid">';


		// BEGIN: get product's image and buttons
		$product_image = $juri_base.'media/plg_system_web357framework/images/joomla-extensions/'.$extension_name.'.png';
		$product_image_path = JPATH_SITE.'/media/plg_system_web357framework/images/joomla-extensions/'.$extension_name.'.png';

		if (!is_file($product_image_path))
		{
			$product_image = $juri_base.'media/plg_system_web357framework/images/joomla-extensions/product-image-coming-soon.png';
		}

		$html .= '<div class="span3 col text-center" style="max-width: 220px;">';

		// image
		$desc_img_style = $jinput->get('option') == 'com_installer' ? ' style="overflow: hidden; margin-bottom: 20px;"' : '';
		$html .= '<div class="web357framework-desc-img"'.$desc_img_style.'>';
		$html .= (!empty($more_info_url)) ? '<a href="'.$more_info_url.'" target="_blank">' : '';
		$html .= '<img src="'.$product_image.'" alt="'.$real_name.'" />';
		$html .= (!empty($more_info_url)) ? '</a>' : '';
		$html .= '</div>';

		// buttons
		$desc_btn_style = $jinput->get('option') == 'com_installer' ? ' style="display: inline-block; margin: 0 0 10px 10px;"' : '';
		if (!empty($backend_settings_url))
		{
			$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'><a href="'.$backend_settings_url.'" class="btn btn-secondary">'.Text::_('W357FRM_SETTINGS').'</a></div>';
		}

		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($live_demo_url)) ? '<a href="'.$live_demo_url.'" class="btn btn-sm btn-primary" target="_blank">'.Text::_('W357FRM_VIEW_DEMO').'</a> ' : '';
		$html .= '</div>';

		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($more_info_url)) ? '<a href="'.$more_info_url.'" class="btn btn-sm btn-success" target="_blank">'.Text::_('W357FRM_MORE_DETAILS').'</a> ' : '';
		$html .= '</div>';

		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($documentation_url)) ? '<a href="'.$documentation_url.'" class="btn btn-sm btn-warning" target="_blank">'.Text::_('W357FRM_DOCUMENTATION').'</a> ' : '';
		$html .= '</div>';
		
		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($changelog_url)) ? '<a href="'.$changelog_url.'" class="btn btn-info" target="_blank">'.Text::_('W357FRM_CHANGELOG').'</a> ' : '';
		$html .= '</div>';

		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($support_url)) ? '<a href="'.$support_url.'" class="btn btn-sm btn-danger" target="_blank">'.Text::_('W357FRM_SUPPORT').'</a> ' : '';
		$html .= '</div>';

		$html .= '</div>'; // .span3
		// END: get product's image and buttons
		
		// Description
		$full_desc_style = $jinput->get('option') == 'com_installer' ? ' style="margin: 30px 0 0 10px !important;"' : '';
		$desc = <<<HTML

		<div class="w357_item_full_desc"{$full_desc_style}>
			
			<p class="uk-text-large">{$ext_desc_html}</p>

			{$ext_desc_features_html}

		</div><!-- end .w357_item_full_desc -->
HTML;
				
		if (!empty($desc))
		{
			$html .= '<div class="span9 col">';

			// description
			$html .= $desc;

			// jed review
			$html .= (!empty($jed_url)) ? '<div class="w357_item_full_desc"><h4>'.Text::_('W357FRM_HEADER_JED_REVIEW_AND_RATING').'</h4><p>'.sprintf(Text::_('W357FRM_LEAVE_REVIEW_ON_JED'), $jed_url, $real_name).'</p></div>' : '';

			$html .= '</div>'; // end .span9
		}
		else
		{
			$html .= Text::_('W357FRM_NO_DESCRIPTION_FOR_THIS_EXTENSION');
		}
		
		$html .= '</div>'; // end .row

		$html .= '</div>'; // end .container

		return $html;
	}


	/**
	 * Get the description after installation with useful buttons and links.
	 * 
	 * @extension_type = "plugin"
	 * @extension_name = "loginasuser"
	 * @plugin_type = "system"
	 * @real_name = "Login as User"
	 */
	function getHtmlDescription_j4_label($extension_type = '', $extension_name = '', $plugin_type = '', $real_name = '')
	{
		// Get extension's details from XML
		$extension_type = (!empty($extension_type)) ? $extension_type : $this->element['extension_type']; // component, module, plugin 
		$extension_name = (!empty($extension_name)) ? $extension_name : preg_replace('/(plg_|com_|mod_)/', '', $this->element['extension_name']);
		$plugin_type = (!empty($plugin_type)) ? $plugin_type : $this->element['plugin_type'].' '; // system, authentication, content etc.
		$real_name = (!empty($real_name)) ? $real_name : $this->element['real_name'];
		$real_name = Text::_($real_name);
		
		// Retrieving request data using JInput
		$jinput = Factory::getApplication()->input;
		$juri_base = str_replace('/administrator', '', Uri::base());

		// Get Joomla's version
		$jversion = new Version;
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
		$major_version = 'v'.$short_version[0].'x'; // v3x
		
		/**
		 *  Get extension details from the json file
		 */
		$web357_items_json_file = 'https://cdn.web357.com/extension-info/'.$extension_name.'-info.json';

		$web357_items_data = '';
		if (self::url_exists($web357_items_json_file))
		{
			if (self::_allowUrlFopen())
			{
				$web357_items_data = file_get_contents($web357_items_json_file);
				$web357_items_data = json_decode($web357_items_data);
			}
			elseif (self::_isCurl()) // check if extension=php_curl.dll is enabled from php.ini
			{
				// cUrl method
				$ch = curl_init();

				$options = array(
					CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification
					CURLOPT_RETURNTRANSFER => true, // // Will return the response, if false it print the response
					CURLOPT_URL            => $web357_items_json_file, // Set the url
					CURLOPT_CONNECTTIMEOUT => 120,
					CURLOPT_TIMEOUT        => 120,
					CURLOPT_MAXREDIRS      => 10,
				);

				curl_setopt_array( $ch, $options ); // Add options to array
				
				$web357_items_data = curl_exec($ch); // Execute

				curl_close($ch); // Closing

				// get data in a json
				$web357_items_data = json_decode($web357_items_data);
			}
		}

		if (!isset($web357_items_data->$extension_name))
		{
			return;
		}

		// item vars
		$web357_item = $web357_items_data->$extension_name;
		$extension_type = str_replace('_', ' ', $web357_item->extension_type);
		$live_demo_url = $web357_item->live_demo_url;
		$more_info_url = $web357_item->more_info_url;
		$documentation_url = $web357_item->documentation_url;
		$changelog_url = $web357_item->changelog_url;
		$support_url = $web357_item->support_url;
		$jed_url = $web357_item->jed_url;
		$backend_settings_url = $web357_item->backend_settings_url;
		$ext_desc_html = $web357_item->description;
		$ext_desc_features_html = $web357_item->description_features;

		if (version_compare( $mini_version, "2.5", "<="))
		{
			$backend_settings_url = str_replace('filter[search]', 'filter_search', $backend_settings_url);
		}

		// output
		$html = '';


		// begin container
		$container_style = $jinput->get('option') == 'com_installer' ? ' style="margin: 30px !important;"' : '';

		// is Joomla! 4.x
		$html .= '<div class="web357framework-description-text w357-container '.$major_version.' w357 '.$jinput->get('option').'"'.$container_style.'>';
		$html .= '<div class="row">';
	
		// BEGIN: get product's image and buttons
		$product_image = $juri_base.'media/plg_system_web357framework/images/joomla-extensions/'.$extension_name.'.png';
		$product_image_path = JPATH_SITE.'/media/plg_system_web357framework/images/joomla-extensions/'.$extension_name.'.png';

		if (!is_file($product_image_path))
		{
			$product_image = $juri_base.'media/plg_system_web357framework/images/joomla-extensions/product-image-coming-soon.png';
		}

		$html .= '<div class="span3 col text-center" style="max-width: 220px;">';

		// image
		$desc_img_style = $jinput->get('option') == 'com_installer' ? ' style="overflow: hidden; margin-bottom: 20px;"' : '';
		$html .= '<div class="web357framework-desc-img"'.$desc_img_style.'>';
		$html .= (!empty($more_info_url)) ? '<a href="'.$more_info_url.'" target="_blank">' : '';
		$html .= '<img src="'.$product_image.'" alt="'.$real_name.'" />';
		$html .= (!empty($more_info_url)) ? '</a>' : '';
		$html .= '</div>';

		// buttons
		$desc_btn_style = $jinput->get('option') == 'com_installer' ? ' style="display: inline-block; margin: 0 0 10px 10px;"' : '';
		if (!empty($backend_settings_url))
		{
			$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'><a href="'.$backend_settings_url.'" class="btn btn-sm btn-secondary">'.Text::_('W357FRM_SETTINGS').'</a></div>';
		}

		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($live_demo_url)) ? '<a href="'.$live_demo_url.'" class="btn btn-sm btn-primary" target="_blank">'.Text::_('W357FRM_VIEW_DEMO').'</a> ' : '';
		$html .= '</div>';

		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($more_info_url)) ? '<a href="'.$more_info_url.'" class="btn btn-sm btn-success" target="_blank">'.Text::_('W357FRM_MORE_DETAILS').'</a> ' : '';
		$html .= '</div>';

		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($documentation_url)) ? '<a href="'.$documentation_url.'" class="btn btn-sm btn-warning" target="_blank">'.Text::_('W357FRM_DOCUMENTATION').'</a> ' : '';
		$html .= '</div>';
		
		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($changelog_url)) ? '<a href="'.$changelog_url.'" class="btn btn-sm btn-info" target="_blank">'.Text::_('W357FRM_CHANGELOG').'</a> ' : '';
		$html .= '</div>';

		$html .= '<div class="web357framework-desc-btn"'.$desc_btn_style.'>';
		$html .= (!empty($support_url)) ? '<a href="'.$support_url.'" class="btn btn-sm btn-danger" target="_blank">'.Text::_('W357FRM_SUPPORT').'</a> ' : '';
		$html .= '</div>';

		$html .= '</div>'; // .span3
		// END: get product's image and buttons
		
		$html .= '</div>'; // end .row

		$html .= '</div>'; // end .container

		return $html;
	}

	/**
	 * Get the description after installation with useful buttons and links.
	 * 
	 * @extension_type = "plugin"
	 * @extension_name = "loginasuser"
	 * @plugin_type = "system"
	 * @real_name = "Login as User"
	 */
	function getHtmlDescription_j4_input($extension_type = '', $extension_name = '', $plugin_type = '', $real_name = '')
	{
		// Get extension's details from XML
		$extension_type = (!empty($extension_type)) ? $extension_type : $this->element['extension_type']; // component, module, plugin 
		$extension_name = (!empty($extension_name)) ? $extension_name : preg_replace('/(plg_|com_|mod_)/', '', $this->element['extension_name']);
		$plugin_type = (!empty($plugin_type)) ? $plugin_type : $this->element['plugin_type'].' '; // system, authentication, content etc.
		$real_name = (!empty($real_name)) ? $real_name : $this->element['real_name'];
		$real_name = Text::_($real_name);
		
		// Retrieving request data using JInput
		$jinput = Factory::getApplication()->input;
		$juri_base = str_replace('/administrator', '', Uri::base());

		// Get Joomla's version
		$jversion = new Version;
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
		$major_version = 'v'.$short_version[0].'x'; // v3x
		
		/**
		 *  Get extension details from the json file
		 */
		$web357_items_json_file = 'https://cdn.web357.com/extension-info/'.$extension_name.'-info.json';

		$web357_items_data = '';
		if (self::url_exists($web357_items_json_file))
		{
			if (self::_allowUrlFopen())
			{
				$web357_items_data = file_get_contents($web357_items_json_file);
				$web357_items_data = json_decode($web357_items_data);
			}
			elseif (self::_isCurl()) // check if extension=php_curl.dll is enabled from php.ini
			{
				// cUrl method
				$ch = curl_init();

				$options = array(
					CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification
					CURLOPT_RETURNTRANSFER => true, // // Will return the response, if false it print the response
					CURLOPT_URL            => $web357_items_json_file, // Set the url
					CURLOPT_CONNECTTIMEOUT => 120,
					CURLOPT_TIMEOUT        => 120,
					CURLOPT_MAXREDIRS      => 10,
				);

				curl_setopt_array( $ch, $options ); // Add options to array
				
				$web357_items_data = curl_exec($ch); // Execute

				curl_close($ch); // Closing

				// get data in a json
				$web357_items_data = json_decode($web357_items_data);
			}
		}

		
		if (!isset($web357_items_data->$extension_name))
		{
			return Text::_('W357FRM_NO_DESCRIPTION_FOR_THIS_EXTENSION');
		}

		// item vars
		$web357_item = $web357_items_data->$extension_name;
		$extension_type = str_replace('_', ' ', $web357_item->extension_type);
		$live_demo_url = $web357_item->live_demo_url;
		$more_info_url = $web357_item->more_info_url;
		$documentation_url = $web357_item->documentation_url;
		$changelog_url = $web357_item->changelog_url;
		$support_url = $web357_item->support_url;
		$jed_url = $web357_item->jed_url;
		$backend_settings_url = $web357_item->backend_settings_url;
		$ext_desc_html = $web357_item->description;
		$ext_desc_features_html = $web357_item->description_features;

		if (version_compare( $mini_version, "2.5", "<="))
		{
			$backend_settings_url = str_replace('filter[search]', 'filter_search', $backend_settings_url);
		}

		// output
		$html = '';

		// Header
		$html .= '<h1>'.$real_name.' - Joomla! '.$extension_type.'</h1>';

		// begin container
		$container_style = $jinput->get('option') == 'com_installer' ? ' style="margin: 30px !important;"' : '';

		// Header
		$html .= '<div class="web357framework-description-text w357-container '.$major_version.' w357 '.$jinput->get('option').'"'.$container_style.'>';
		$html .= '<div class="row row-fluid">';
		
		// Description
		$full_desc_style = $jinput->get('option') == 'com_installer' ? ' style="margin: 30px 0 0 10px !important;"' : '';
		$desc = <<<HTML

		<div class="w357_item_full_desc"{$full_desc_style}>
			
			<p class="uk-text-large">{$ext_desc_html}</p>

			{$ext_desc_features_html}

		</div><!-- end .w357_item_full_desc -->
HTML;
				
		if (!empty($desc))
		{
			$html .= '<div class="span9 col">';

			// description
			$html .= $desc;

			// jed review
			$html .= (!empty($jed_url)) ? '<div class="w357_item_full_desc"><h4>'.Text::_('W357FRM_HEADER_JED_REVIEW_AND_RATING').'</h4><p>'.sprintf(Text::_('W357FRM_LEAVE_REVIEW_ON_JED'), $jed_url, $real_name).'</p></div>' : '';

			$html .= '</div>'; // end .span9
		}
		else
		{
			$html .= Text::_('W357FRM_NO_DESCRIPTION_FOR_THIS_EXTENSION');
		}
		
		$html .= '</div>'; // end .row

		$html .= '</div>'; // end .container

		return $html;
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
		return $this->getHtmlDescription();
	}

	protected function getInput_J3()
	{
		return ' ';
	}

	protected function getLabel_J4()
	{
		return $this->getHtmlDescription_j4_label();
	}

	protected function getInput_J4()
	{
		return $this->getHtmlDescription_j4_input();
	}

	// check if url exists
	protected static function url_exists($url) 
	{
		if (self::_isCurl())
		{
			// cUrl method
			$ch = curl_init();

			$options = array(
				CURLOPT_URL            => $url,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_HEADER         => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_ENCODING       => "",
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_AUTOREFERER    => true,
				CURLOPT_CONNECTTIMEOUT => 120,
				CURLOPT_TIMEOUT        => 120,
				CURLOPT_MAXREDIRS      => 10,
			);
			curl_setopt_array( $ch, $options );
			$response = curl_exec($ch); 
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // $retcode >= 400 -> not found, $retcode = 200, found.

			if ($httpCode != 200)
			{
				// The URL does not exist
				return false;

			} else {
				return true;
			}

			curl_close($ch);
		}
		else
		{			
			// default method
			$file_headers = @get_headers($url);
			if($file_headers[0] == 'HTTP/1.1 404 Not Found')
			{
				return false;
			}
			else
			{
				return true;
			}
		}
    }
    
    /**
	 * Check if the PHP function curl is enabled
	 */
	protected static function _isCurl()
	{
		return function_exists('curl_version');
	}
	
	/**
	 * Check if the PHP function allow_url_fopen is enabled
	 */
	protected static function _allowUrlFopen()
	{
		return ini_get('allow_url_fopen');
	}

}