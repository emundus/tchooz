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

 
namespace Web357Framework;

defined('_JEXEC') or die;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Factory;
use Joomla\CMS\Version;

class VersionChecker
{
    public static function outputMessage($element)
    {
        // Retrieving request data using JInput
		$jinput = Factory::getApplication()->input;
		
		// get extension's details
		$position = $element['position']; // version's position (top + bottom)
		$extension_type_single = $element['extension_type']; // component, module, plugin 
		$extension_type = $element['extension_type'].'s'; // components, modules, plugins 
		$extension_name = $element['extension_name']; // mod_name, com_name, plg_system_name
		$plugin_type = $element['plugin_type']; // system, authentication, content etc.
		$plugin_folder = (!empty($plugin_type) && $plugin_type != '') ? $plugin_type.'/' : '';
		$real_name = $element['real_name'];
		$url_slug = $element['url_slug'];

		if (empty($extension_type) || empty($extension_name)):
			Factory::getApplication()->enqueueMessage("Error in XML. Please, contact us at support@web357.com!", "error");
			return false;
		endif;
		
		// Get Joomla's version
		$jversion = new Version();
		$short_version = explode('.', $jversion->getShortVersion()); // 3.8.10
		$mini_version = $short_version[0].'.'.$short_version[1]; // 3.8
		
		$j25 = false;
		$j3x = false;
		if (version_compare( $mini_version, "2.5", "<=")):
			// j25
			$w357_ext_uptodate_class = 'w357_ext_uptodate_j25';
			$j25 = true;
		else:
			// j3x
			$w357_ext_uptodate_class = 'w357_ext_uptodate_j3x';
			$j3x = true;
		endif;

		// get current extension's version & creationDate from database
		$db = Factory::getDBO();
		$query = "SELECT manifest_cache "
		."FROM #__extensions "
		."WHERE element LIKE '%".$extension_name."%' and type = '".$extension_type_single."' "
		;
		$db->setQuery($query);
		$db->execute();
		$manifest = json_decode( $db->loadResult(), true );
		$current_version = (!empty($manifest['version'])) ? $manifest['version'] : '1.0.0';
		$current_creationDate = (!empty($manifest['creationDate'])) ? $manifest['creationDate'] : '10 Oct 1985';

		// Get extension name
		if (strpos($extension_name, '_') !== false) 
		{
			$extension_name_clean = explode('_', $extension_name);
			$extension_name = $extension_name_clean[1]; // e.g. get only supporthours, instead of mod_supporhours
		}

		// Get web357 releases json content
		$web357_releases_json_url = 'https://cdn.web357.com/extension-info/'.urlencode($extension_name).'-info.json';

		$web357_releases_json = '';
		if (self::url_exists($web357_releases_json_url))
		{
			if (self::_allowUrlFopen())
			{
				$web357_releases_json = file_get_contents($web357_releases_json_url);
				$web357_releases_json = json_decode($web357_releases_json);
			}
			elseif (self::_isCurl()) // check if extension=php_curl.dll is enabled from php.ini
			{
				// cUrl method
				$ch = curl_init();

				$options = array(
					CURLOPT_SSL_VERIFYPEER => false, // Disable SSL verification
					CURLOPT_RETURNTRANSFER => true, // // Will return the response, if false it print the response
					CURLOPT_URL            => $web357_releases_json_url, // Set the url
					CURLOPT_CONNECTTIMEOUT => 120,
					CURLOPT_TIMEOUT        => 120,
					CURLOPT_MAXREDIRS      => 10,
				);

				curl_setopt_array( $ch, $options ); // Add options to array
				
				$web357_releases_json = curl_exec($ch); // Execute

				curl_close($ch); // Closing

				// get data in a json
				$web357_releases_json = json_decode($web357_releases_json);
			}
		}

		// Get the latest version of extension, from Web357.com
		$latest_version = $current_version;
		$latest_creationDate = $current_creationDate;

		if (!empty($web357_releases_json))
		{
			if ($web357_releases_json->$extension_name->extension == $extension_name)
			{
				$latest_version = $web357_releases_json->$extension_name->version;
				$latest_creationDate = date("d-M-Y", strtotime($web357_releases_json->$extension_name->date));
			}
		}

		// get changelog's url
		$real_ext_name_with_dashes = Text::_($real_name);
		$real_ext_name_with_dashes = str_replace(" (Pro version)", "", $real_ext_name_with_dashes);
		$real_ext_name_with_dashes = str_replace(" (Pro version)", "", $real_ext_name_with_dashes);
		$real_ext_name_with_dashes = str_replace(" (Free version)", "", $real_ext_name_with_dashes);
		$real_ext_name_with_dashes = str_replace(" PRO", "", $real_ext_name_with_dashes);
		$real_ext_name_with_dashes = str_replace(" FREE", "", $real_ext_name_with_dashes);
		$real_ext_name_with_dashes = str_replace("System - ", "", $real_ext_name_with_dashes);
		$real_ext_name_with_dashes = str_replace("Authentication - ", "", $real_ext_name_with_dashes);
		$real_ext_name_with_dashes = str_replace("User - ", "", $real_ext_name_with_dashes);
		if ($real_ext_name_with_dashes != 'Web357 Framework')
		{
			$real_ext_name_with_dashes = str_replace("Web357 ", "", $real_ext_name_with_dashes);
		}
		$real_ext_name_with_dashes = strtolower(str_replace(" ", "-", $real_ext_name_with_dashes));
		$url_slug = (!empty($url_slug)) ? $url_slug : $real_ext_name_with_dashes;
		$changelog_url = '//www.web357.com/'.$url_slug.'/changelog';  // e.g. support-hours

		// output
		$html  = '';
		$html .= '<div>';
		if (!empty($latest_version) && !empty($latest_creationDate))
		{
			if ($current_version == $latest_version/* || strpos($current_version, 'beta' ) !== false*/)
			{
				$html .= '<div class="w357_ext_uptodate '.$jinput->get('option').' '.$w357_ext_uptodate_class.'">';
				$html .= '<div>'.Text::_('W357FRM_YOUR_CURRENT_VERSION_IS').': <a title="'.Text::_('W357FRM_VIEW_THE_CHANGELOG').'" href="'.$changelog_url.'" class="btn_view_changelog" target="_blank">'.$current_version.' ('.$current_creationDate.')</a><br />'.Text::_('W357FRM_UP_TO_DATE').'</div>';
				$html .= '</div>';
			}
			else
			{
				$html .= '<div class="w357_ext_notuptodate '.$jinput->get('option').'">'.Text::_('W357FRM_YOUR_CURRENT_VERSION_IS').': '.$current_version.' ('.$current_creationDate.').<br /><span>'.Text::_('W357FRM_UPDATE_TO_THE_LATEST_VERSION').' '.$latest_version.' ('.$latest_creationDate.')'.' {<a href="'.$changelog_url.'" target="_blank">'.Text::_('W357FRM_VIEW_THE_CHANGELOG').'</a>}.</span>
				<br />
				<a href="index.php?option=com_installer&view=update">'.Text::_('W357FRM_UPDATE_VIA_THE_JOOMLA_UPDATE_MANAGER').'</a>
				&nbsp;&nbsp;&nbsp;'.Text::_('W357FRM_OR').'&nbsp;&nbsp;&nbsp;
				<a href="//www.web357.com/my-account/downloads/" target="_blank">'.Text::_('W357FRM_GO_TO_DOWNLOAD_AREA').'</a>
				</div>';
			}
		}
		else
		{
			$html .= '<div class="w357_ext_uptodate '.$jinput->get('option').'">'.Text::_('W357FRM_UP_TO_DATE').'</div>';
		}

		$html .= '</div>';

		// get joomla version for javascript file
		if (version_compare( $mini_version, "4.0", ">=")):
			// j4
			$js_jversion = 'j4x';
		elseif (version_compare( $mini_version, "3.0", ">=")):
			// j3
			$js_jversion = 'j3x';
		elseif (version_compare( $mini_version, "2.5", ">=")):
			// j25
			$js_jversion = 'j25x';
		else:
			// j
			$js_jversion = 'jx';
		endif;

		// get base url (for jquery)
		$base_url = str_replace('/administrator', '', Uri::base());
		$html .= '<div id="baseurl" data-baseurl="'.$base_url.'"></div>';
		$html .= '<div id="jversion" data-jversion="'.$js_jversion.'"></div>';

		return $html;
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