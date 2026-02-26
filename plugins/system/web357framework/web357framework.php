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
defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Version;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\HTML\HTMLHelper;

$jVersion       = new Version();
$currentVersion = $jVersion->getShortVersion();

if (version_compare($currentVersion, '3.0', 'ge'))
{
	// Initialize Web357 Framework
	require_once(__DIR__.'/autoload.php');
}

if (!class_exists('plgSystemWeb357framework')):
	class plgSystemWeb357framework extends CMSPlugin
	{
		public function __construct(&$subject, $config)
		{
			parent::__construct($subject, $config);
		}
		
		public function onAfterInitialise()
		{
			$app 		= Factory::getApplication();
			$option 	= $app->input->get('option');
			$view 		= $app->input->get('view');
			$component 	= $app->input->get('component');
			
			// Backend rules
			if ($app->isClient('administrator'))
			{
				// API Key Checker
				if ($option == 'com_config' && $view == 'component' && 
					(
						$component == 'com_vmsales' || 
						$component == 'com_allcomments' || 
						$component == 'com_monthlyarchive' || 
						$component == 'com_cookiespolicynotificationbar' || 
						$component == 'com_limitactivelogins' || 
						$component == 'com_manageunusedimages' || 
						$component == 'com_loginasuser' || 
						$component == 'com_fix404errorlinks' || 
						$component == 'com_failedloginattempts' || 
						$component == 'com_jlogs' || 
						$component == 'com_jmaillog'
					)
				)
				{
					// Load the Web357 Framework language file
					Factory::getLanguage()->load('plg_system_web357framework', JPATH_PLUGINS . '/system/web357framework');

					// Call the Web357 Framework Helper Class
					require_once(JPATH_PLUGINS.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'web357framework'.DIRECTORY_SEPARATOR.'web357framework.class.php');
					$w357frmwrk = new Web357FrameworkHelperClass;

					// API Key Checker
					if (Factory::getUser()->id)
					{
						$w357frmwrk->apikeyChecker();
					}
				}

				// Get Joomla's version
				$jVersion       = new Version();
				$currentVersion = $jVersion->getShortVersion();

				// Load loginasuser language file in com_users
				if ($option == 'com_users')
				{
					Factory::getLanguage()->load('com_loginasuser', JPATH_ADMINISTRATOR.'/components/com_loginasuser');
				}
	
				// load jQuery only when needed
				if (
					($view == 'plugin' && $option == 'com_plugins') || 
					($view == 'module' && ($option == 'com_modules' || $option == 'com_advancedmodules')) || 
					($option == 'com_config' && $view == 'component' && 
					(
						$component == 'com_vmsales' || 
						$component == 'com_allcomments' || 
						$component == 'com_monthlyarchive' || 
						$component == 'com_cookiespolicynotificationbar' || 
						$component == 'com_limitactivelogins' || 
						$component == 'com_manageunusedimages' || 
						$component == 'com_loginasuser' || 
						$component == 'com_fix404errorlinks' || 
						$component == 'com_failedloginattempts' || 
						$component == 'com_jlogs' ||
						$component == 'com_jmaillog' ||
						$component == 'com_users'
					) || 
					(
						$option == 'com_vmsales' || 
						$option == 'com_allcomments' || 
						$option == 'com_monthlyarchive' || 
						$option == 'com_cookiespolicynotificationbar' || 
						$option == 'com_loginasuser' || 
						$option == 'com_limitactivelogins' || 
						$option == 'com_manageunusedimages' || 
						$option == 'com_fix404errorlinks' || 
						$option == 'com_failedloginattempts' || 
						$option == 'com_jlogs' || 
						$option == 'com_jmaillog' || 
						$option == 'com_users'
					)
				))
				{
					if (version_compare($currentVersion, '3.0', 'lt'))
					{
						Factory::getDocument()->addScript(Uri::root(true).'/media/plg_system_web357framework/js/jquery-1.10.2.min.js');
					}
					elseif (version_compare($currentVersion, '4.0', 'lt'))
					{
						HTMLHelper::_('jquery.framework');
					}

					// js
					Factory::getDocument()->addScript(Uri::root(true).'/media/plg_system_web357framework/js/script.min.js?v=20251027150438');

					// css
					Factory::getDocument()->addStyleSheet(Uri::root(true).'/media/plg_system_web357framework/css/style.min.css?v=20251027150438');
				}
			}
		}

		function onContentPrepareForm(?Form $form = null, $data = null)
		{
			$app    = Factory::getApplication();
			$option = $app->input->get('option');
			$view 	= $app->input->get('view');
			$layout = $app->input->get('layout');

			if ($app->isClient('administrator') && $option == 'com_plugins' && $view = 'plugin' && $layout == 'edit')
			{
				if (!($form instanceof Form))
				{
					throw new \Exception(Text::_('Not a form'), 403);
				}
				
				// Get plugin's element
				$db = Factory::getDbo();
				$query = $db->getQuery(true);
				$query->select($db->quoteName('element'));
				$query->from($db->quoteName('#__extensions'));
				$query->where($db->quoteName('type'). ' = '. $db->quote('plugin'));
				$query->where($db->quoteName('folder'). ' = '. $db->quote('system'));
				$query->where($db->quoteName('extension_id'). ' = '. $app->input->get('extension_id'));
				$db->setQuery($query);
				$element = $db->loadResult();

				// get the frontend language tag
				$frontend_language_tag = ComponentHelper::getComponent('com_languages')->getParams()->get('site');
				$frontend_language_default_tag = $frontend_language_tag;
				$frontend_language_tag = str_replace("-", "_", $frontend_language_tag);
				$frontend_language_tag = !empty($frontend_language_tag) ? $frontend_language_tag : "en_GB";
				
				// Load the Web357Framework plugin language file to get the translations of each language
				$extension = 'plg_system_web357framework';
				$base_dir = JPATH_SITE.'/plugins/system/web357framework/';
				$language_tag = str_replace('_', '-', $frontend_language_tag);
				$reload = true;
				Factory::getLanguage()->load($extension, $base_dir, $language_tag, $reload);
				
				// BEGIN: Cookies Policy Notification Bar - Joomla! Plugin
				if ($element == 'cookiespolicynotificationbar')
				{
					// Get language tag
					$language = Factory::getLanguage();
					$language_tag = str_replace("-", "_", $language->get('tag'));
					$language_tag = !empty($language_tag) ? $language_tag : "en_GB";

					// Get languages and load form
					$lang_codes_arr = array();
					$languages = LanguageHelper::getLanguages();
					
					if (!empty($languages) && count($languages) > 1):
						// Get language details
						foreach ($languages as $tag => $language):
							
							// get language name
							$language_name = $language->title_native;
							$language->lang_code = str_replace('-', '_', $language->lang_code);
							$lang_codes_arr[] = $language->lang_code;

							// Load the plugin language file to get the translations of each language
							$extension = 'plg_system_cookiespolicynotificationbar';
							$base_dir = JPATH_SITE.'/plugins/system/cookiespolicynotificationbar/';
							$language_tag = str_replace('_', '-', $language->lang_code);
							$reload = true;
							Factory::getLanguage()->load($extension, $base_dir, $language_tag, $reload);
							
							$this->getLangForm($form, $language_name, $language->lang_code);
							
						endforeach;
					else:
						// Get language details
						$language = Factory::getLanguage();
						$lang = new stdClass();
						$lang->known_languages = LanguageHelper::getKnownLanguages(JPATH_SITE);
						$known_lang_name = $lang->known_languages[$frontend_language_default_tag]['name'];
						$known_lang_tag = $lang->known_languages[$frontend_language_default_tag]['tag'];
						$known_lang_name = !empty($known_lang_name) ? $known_lang_name : 'English';
						$known_lang_tag = !empty($known_lang_tag) ? $known_lang_tag : 'en-GB';
						$frontend_language_tag = !empty($frontend_language_tag) ? $frontend_language_tag : $known_lang_tag;
						$language_name = $this->getLanguageNameByTag($frontend_language_default_tag); 
						$language_name = !empty($language_name) ? str_replace(' ('.str_replace('_', '-',$language_tag).')', '', $language_name) : $known_lang_name;
						$lang_codes_arr[] = $frontend_language_tag;

						// Load the plugin language file to get the translations of each language
						$extension = 'plg_system_cookiespolicynotificationbar';
						$base_dir = JPATH_SITE.'/plugins/system/cookiespolicynotificationbar/';
						$language_tag = str_replace('_', '-', $frontend_language_tag);
						$reload = true;
						Factory::getLanguage()->load($extension, $base_dir, $language_tag, $reload);

						// load form
						$this->getLangForm($form, $language_name, $frontend_language_tag);
						
					endif; 

					// Load the plugin language file to get the translations of the base language
					$lang = Factory::getLanguage();
					$current_lang_tag = $lang->getTag();
					$extension = 'plg_system_cookiespolicynotificationbar';
					$base_dir = JPATH_SITE.'/plugins/system/cookiespolicynotificationbar/';
					$reload = true;
					$lang->load($extension, $base_dir, $current_lang_tag, $reload);
				}
				// END: Cookies Policy Notification Bar - Joomla! Plugin

				// BEGIN: custom404errorpage - Joomla! Plugin
				if ($element == 'custom404errorpage')
				{
					// Get language tag
					$language = Factory::getLanguage();
					$language_tag = str_replace("-", "_", $language->get('tag'));
					$language_tag = !empty($language_tag) ? $language_tag : "en_GB";

					// Get languages and load form
					$lang_codes_arr = array();
					$languages = LanguageHelper::getLanguages();
					
					if (!empty($languages) && count($languages) > 1):
						// Get language details
						foreach ($languages as $tag => $language):
							
							// get language name
							$language_name = $language->title_native;
							$language->lang_code = str_replace('-', '_', $language->lang_code);
							$lang_codes_arr[] = $language->lang_code;

							// Load the plugin language file to get the translations of each language
							$extension = 'plg_system_custom404errorpage';
							$base_dir = JPATH_SITE.'/plugins/system/custom404errorpage/';
							$language_tag = str_replace('_', '-', $language->lang_code);
							$reload = true;
							Factory::getLanguage()->load($extension, $base_dir, $language_tag, $reload);
							
							$this->getLangFormCustom404ErrorPage($form, $language_name, $language->lang_code);
							
						endforeach;
					else:
						// Get language details
						$language = Factory::getLanguage();
						$lang = new stdClass();
						$lang->known_languages = LanguageHelper::getKnownLanguages(JPATH_SITE);
						$known_lang_name = $lang->known_languages[$frontend_language_default_tag]['name'];
						$known_lang_tag = $lang->known_languages[$frontend_language_default_tag]['tag'];
						$known_lang_name = !empty($known_lang_name) ? $known_lang_name : 'English';
						$known_lang_tag = !empty($known_lang_tag) ? $known_lang_tag : 'en-GB';
						$frontend_language_tag = !empty($frontend_language_tag) ? $frontend_language_tag : $known_lang_tag;
						$language_name = $this->getLanguageNameByTag($frontend_language_default_tag); 
						$language_name = !empty($language_name) ? str_replace(' ('.str_replace('_', '-',$language_tag).')', '', $language_name) : $known_lang_name;
						$lang_codes_arr[] = $frontend_language_tag;

						// Load the plugin language file to get the translations of each language
						$extension = 'plg_system_custom404errorpage';
						$base_dir = JPATH_SITE.'/plugins/system/custom404errorpage/';
						$language_tag = str_replace('_', '-', $frontend_language_tag);
						$reload = true;
						Factory::getLanguage()->load($extension, $base_dir, $language_tag, $reload);

						// load form
						$this->getLangFormCustom404ErrorPage($form, $language_name, $frontend_language_tag);
						
					endif; 

					// Load the plugin language file to get the translations of the base language
					$lang = Factory::getLanguage();
					$current_lang_tag = $lang->getTag();
					$extension = 'plg_system_custom404errorpage';
					$base_dir = JPATH_SITE.'/plugins/system/custom404errorpage/';
					$reload = true;
					$lang->load($extension, $base_dir, $current_lang_tag, $reload);
				}
				// END: custom404errorpage - Joomla! Plugin

				// BEGIN: Login as User - Joomla! Plugin (add extra fields for user groups and admins)
				if ($element == 'loginasuser')
				{
					// Get User Groups
					$db = Factory::getDbo();
					$query = $db->getQuery(true);
					$query->select('id, title');
					$query->from('#__usergroups');
					$query->where('parent_id > 0');
					$query->order('lft ASC');
					$db->setQuery($query);
					$usergroups = $db->loadObjectList();

					if (!empty($usergroups))
					{
						foreach ($usergroups as $usergroup)
						{
							$this->getUsersFormFieldLoginAsUser($form, $usergroup->id, htmlspecialchars($usergroup->title));
						}
					}
				}
				// END: Login as User - Joomla! Plugin (add extra fields for user groups and admins)
			}

			return true;
		}

		public function getLangForm($form, $language_name = "English", $lang_code = "en_GB")
		{
			$jVersion       = new Version();
			$currentVersion = $jVersion->getShortVersion();

			if (isset($form))
			{
				// start building xml file
				$xmlText = '<?xml version="1.0" encoding="utf-8"?>
				<form>
					<fields>
						<fieldset name="texts_for_languages" addfieldprefix="Joomla\Component\Menus\Administrator\Field">';

				// HEADER
				$xmlText .= '<field type="langheader" name="header_'.$lang_code.'" class="w357_large_header" addfieldpath="/plugins/system/cookiespolicynotificationbar/elements" lang_code="'.$lang_code.'" language_name="'.$language_name.'" />';
				
				// SMALL HEADER: Texts for the Cookies Policy Notification Bar
				$xmlText .= '<field 
				type="header"
				class="w357_small_header" 
				label="PLG_SYSTEM_CPNB_TEXTS_FOR_THE_BAR_HEADER" 
				/>';

				// MESSAGE
				$xmlText .= '<field 
				name="header_message_'.$lang_code.'" 
				type="textarea" 
				default="'.Text::_('J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_HEADER_MESSAGE_DEFAULT').'" label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_HEADER_MESSAGE_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_HEADER_MESSAGE_DESC" 
				rows="6" 
				cols="50" 
				filter="raw" 
				class="cpnb-notification-bar-message w357-display-inline-block"
				/>';

				// OK BUTTON
				$xmlText .= '<field 
				name="ok_btn_'.$lang_code.'" 
				type="radio" 
				class="btn-group btn-group-yesno" 
				default="1" 
				label="PLG_SYSTEM_CPNB_OK_BTN_LBL" 
				description="PLG_SYSTEM_CPNB_OK_BTN_DESC">
				<option value="1">JSHOW</option>
				<option value="0">JHIDE</option>
				</field>';
				
				// OK BUTTON TEXT
				$xmlText .= '<field 
				name="button_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_BUTTON_DEFAULT_TEXT_VALUE').'" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_BUTTON_TEXT_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_BUTTON_TEXT_DESC" 
				filter="STRING" 
				showon="ok_btn_'.$lang_code.':1" 
				/>';

				// DECLINE BUTTON
				$xmlText .= '<field 
				name="decline_btn_'.$lang_code.'" 
				type="radio" 
				class="btn-group btn-group-yesno" 
				default="1" 
				label="PLG_SYSTEM_CPNB_DECLINE_BTN_LBL" 
				description="PLG_SYSTEM_CPNB_DECLINE_BTN_DESC">
				<option value="1">JSHOW</option>
				<option value="0">JHIDE</option>
				</field>';
				
				// DECLINE BUTTON TEXT
				$xmlText .= '<field 
				name="decline_btn_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_DECLINE_BTN_DEFAULT_TEXT_VALUE').'" 
				label="PLG_SYSTEM_CPNB_DECLINE_BTN_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_DECLINE_BTN_TEXT_DESC" 
				filter="STRING" 
				showon="decline_btn_'.$lang_code.':1" 
				/>';

				// CANCEL BUTTON
				$xmlText .= '<field 
				name="cancel_btn_'.$lang_code.'" 
				type="radio" 
				class="btn-group btn-group-yesno" 
				default="0" 
				label="PLG_SYSTEM_CPNB_CANCEL_BTN_LBL" 
				description="PLG_SYSTEM_CPNB_CANCEL_BTN_DESC">
				<option value="1">JSHOW</option>
				<option value="0">JHIDE</option>
				</field>';
				
				// CANCEL BUTTON TEXT
				$xmlText .= '<field 
				name="cancel_btn_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_CANCEL_BTN_DEFAULT_TEXT_VALUE').'" 
				label="PLG_SYSTEM_CPNB_CANCEL_BTN_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_CANCEL_BTN_TEXT_DESC" 
				filter="STRING" 
				showon="cancel_btn_'.$lang_code.':1" 
				/>';

				// SETTINGS BUTTON
				$xmlText .= '<field 
				name="settings_btn_'.$lang_code.'" 
				type="radio" 
				class="btn-group btn-group-yesno" 
				default="1" 
				showon="modalState:1" 
				label="PLG_SYSTEM_CPNB_SETTINGS_BTN_LBL" 
				description="PLG_SYSTEM_CPNB_SETTINGS_BTN_DESC">
				<option value="1">JSHOW</option>
				<option value="0">JHIDE</option>
				</field>';
				
				// SETTINGS BUTTON TEXT
				$xmlText .= '<field 
				name="settings_btn_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_SETTINGS_BTN_DEFAULT_TEXT_VALUE').'" 
				label="PLG_SYSTEM_CPNB_SETTINGS_BTN_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_SETTINGS_BTN_TEXT_DESC" 
				filter="STRING" 
				showon="settings_btn_'.$lang_code.':1" 
				/>';
				
				// MORE INFO BUTTON
				$xmlText .= '<field 
				name="more_info_btn_'.$lang_code.'" 
				type="radio" 
				class="btn-group btn-group-yesno cpnb-modal-info-window" 
				default="1" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_MOR_INFO_BTN_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_MOR_INFO_BTN_DESC">
				<option value="1">JSHOW</option>
				<option value="0">JHIDE</option>
				</field>';

				// BUTTON MORE TEXT
				$xmlText .= '<field 
				name="button_more_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_BUTTON_MORETEXT_DEFAULT_VALUE').'" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_BUTTON_MORETEXT_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_BUTTON_MORETEXT_DESC" 
				filter="STRING" 
				showon="more_info_btn_'.$lang_code.':1" 
				/>';

				// LINK OR Menu Item
				$xmlText .= '<field 
				name="more_info_btn_type_'.$lang_code.'" 
				type="list" 
				default="custom_text" 
				showon="more_info_btn_'.$lang_code.':1" 
				class="cpnb-modal-info-window" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_MORE_INFO_BTN_TYPE_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_MORE_INFO_BTN_TYPE_DESC">
				<option value="link">J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_MORE_INFO_BTN_TYPE_OPTION_LINK</option>
				<option value="menu_item">J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_MORE_INFO_BTN_TYPE_OPTION_MENU_ITEM</option>
				<option value="custom_text">J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_MORE_INFO_BTN_TYPE_OPTION_CUSTOM_TEXT</option>
				</field>';

				// CUSTOM TEXT
				$xmlText .= '<field 
				name="custom_text_'.$lang_code.'" 
				type="editor" 
				default="'.Text::_('J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_CUSTOM_TEXT_DEFAULT').'" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_CUSTOM_TEXT_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_CUSTOM_TEXT_DESC" 
				width="300" 
				filter="safehtml"
				showon="more_info_btn_'.$lang_code.':1[AND]more_info_btn_type_'.$lang_code.':custom_text" 
				class="cpnb-modal-info-window"
				/>';

				// CUSTOM LINK FOR THE MORE INFO BUTTON
				$xmlText .= '<field 
				name="button_more_link_'.$lang_code.'" 
				type="url" 
				default="cookies-policy" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_BUTTON_MORELINK_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_BUTTON_MORELINK_DESC" 
				showon="more_info_btn_'.$lang_code.':1[AND]more_info_btn_type_'.$lang_code.':link" 
				/>';
				
				// MODAL MENU ITEM
				$xmlText .= '<field 
				name="cpnb_modal_menu_item_'.$lang_code.'" 
				type="modal_menu"
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_SELECT_MENU_ITEM_LBL"
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_SELECT_MENU_ITEM_DESC"
				required="false"
				select="true"
				new="true"
				edit="true"
				clear="true"
				addfieldpath="/administrator/components/com_menus/models/fields" 
				showon="more_info_btn_'.$lang_code.':1[AND]more_info_btn_type_'.$lang_code.':menu_item" 
				/>';

				// LINK TARGET
				$xmlText .= '<field 
				name="link_target_'.$lang_code.'" 
				type="list" 
				default="_self" 
				showon="more_info_btn_'.$lang_code.':1[AND]more_info_btn_type_'.$lang_code.'!:custom_text" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_LINK_TARGET_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_LINK_TARGET_DESC">
				<option value="_self">J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_LINK_TARGET_SAME_LBL</option>
				<option value="_blank">J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_LINK_TARGET_NEW_LBL</option>
				<option value="popup">J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_LINK_TARGET_POPUP_WINDOW_LBL</option>
				</field>';

				// POPUP WINDOW WIDTH
				$xmlText .= '<field 
				name="popup_width_'.$lang_code.'" 
				type="text" 
				default="800" 
				showon="more_info_btn_'.$lang_code.':1[AND]link_target_'.$lang_code.':popup[AND]more_info_btn_type_'.$lang_code.'!:custom_text" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_POPUP_WINDOW_WIDTH_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_POPUP_WINDOW_WIDTH_DESC" 
				/>';

				// POPUP WINDOW HEIGHT
				$xmlText .= '<field 
				name="popup_height_'.$lang_code.'" 
				type="text" 
				default="600" 
				showon="more_info_btn_'.$lang_code.':1[AND]link_target_'.$lang_code.':popup[AND]more_info_btn_type_'.$lang_code.'!:custom_text" 
				label="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_POPUP_WINDOW_HEIGHT_LBL" 
				description="J357_PLG_SYSTEM_COOKIESPOLICYNOTIFICATIONBAR_POPUP_WINDOW_HEIGHT_DESC" 
				/>';

				// SMALL HEADER: Texts for the Shortcode Functionality
				$xmlText .= '<field  type="header"  class="w357_small_header" label="PLG_SYSTEM_CPNB_TEXTS_FOR_THE_SHORTCODE_HEADER" showon="enable_shortcode_functionality:1" />';
				$xmlText .= '<field name="note_texts_for_the_shortcode_'.$lang_code.'" type="note" label="" description="'.Text::_('PLG_SYSTEM_CPNB_TEXTS_FOR_THE_SHORTCODE_NOTE').'" showon="enable_shortcode_functionality:1" />';

				// TEXT BEFORE ACCEPT/DECLINE
				$xmlText .= '<field  type="header"  class="w357_small_header"  label="PLG_SYSTEM_CPNB_TEXT_BEFORE_ACCEPT_DECLINE_LBL" showon="enable_shortcode_functionality:1" />';

				$text_before_accept_decline_default = '&lt;p&gt;The cookies on this website are disabled.&lt;br&gt;This decision can be reversed anytime by clicking the below button &quot;Allow Cookies&quot;.&lt;/p&gt;&lt;div class=&quot;cpnb-margin&quot;&gt;{cpnb_buttons}&lt;/div&gt;';

				$xmlText .= '<field 
				name="shortcode_text_before_accept_or_decline_'.$lang_code.'" 
				type="editor" 
				default="'.$text_before_accept_decline_default.'" 
				label="" 
				description="PLG_SYSTEM_CPNB_TEXT_BEFORE_ACCEPT_DECLINE_DESC" 
				width="300" 
				filter="safehtml"
				showon="enable_shortcode_functionality:1" 
				/>';

				// TEXT AFTER ACCEPT
				$xmlText .= '<field  type="header"  class="w357_small_header"  label="PLG_SYSTEM_CPNB_TEXT_AFTER_ACCEPT_LBL" showon="enable_shortcode_functionality:1" />';

				$text_after_accept_default = '&lt;h3&gt;Cookies served through our website&lt;/h3&gt;&lt;div class=&quot;cpnb-margin&quot;&gt;{cpnb_cookies_info_table}&lt;/div&gt;&lt;p&gt;You have allowed website&apos;s cookies to be placed on your browser.&lt;/p&gt;&lt;p&gt;This decision can be reversed anytime by clicking the below button &quot;Delete Cookies&quot;.&lt;/p&gt;&lt;div class=&quot;cpnb-margin&quot;&gt;{cpnb_buttons}&lt;/div&gt;';
				
				$xmlText .= '<field 
				name="shortcode_text_after_accept_'.$lang_code.'" 
				type="editor" 
				default="'.$text_after_accept_default.'" 
				label="" 
				description="PLG_SYSTEM_CPNB_TEXT_AFTER_ACCEPT_DESC" 
				width="300" 
				filter="safehtml"
				showon="enable_shortcode_functionality:1" 
				/>';

				// TEXT AFTER DECLINE
				$xmlText .= '<field  type="header"  class="w357_small_header"  label="PLG_SYSTEM_CPNB_TEXT_AFTER_DECLINE_LBL" showon="enable_shortcode_functionality:1" />';

				$text_after_decline_default = '&lt;p&gt;The cookies on this website are declined by you earlier.&lt;/p&gt;&lt;p&gt;This decision can be reversed anytime by clicking the below button &quot;Allow Cookies&quot;.&lt;/p&gt;&lt;h3&gt;Cookies served through our website&lt;/h3&gt;&lt;div class=&quot;cpnb-margin&quot;&gt;{cpnb_cookies_info_table}&lt;/div&gt;&lt;div class=&quot;cpnb-margin&quot;&gt;{cpnb_buttons}&lt;/div&gt;';

				$xmlText .= '<field 
				name="shortcode_text_after_decline_'.$lang_code.'" 
				type="editor" 
				default="'.$text_after_decline_default.'" 
				label="" 
				description="PLG_SYSTEM_CPNB_TEXT_AFTER_DECLINE_DESC" 
				width="300" 
				filter="safehtml" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// SMALL HEADER: Other texts for translations
				$xmlText .= '<field  type="header"  class="w357_small_header"  label="PLG_SYSTEM_CPNB_OTHER_TEXTS_FOR_TRANSLATIONS_HEADER" showon="enable_shortcode_functionality:1[OR]modalState:1" />';

				// BUTTONS (Note)
				$xmlText .= '<field name="note_buttons_'.$lang_code.'" type="note" label="PLG_SYSTEM_CPNB_BUTTONS_NOTE_LBL" description="" showon="enable_shortcode_functionality:1[OR]modalState:1" />';

				// ALLOW COOKIES BUTTON TEXT
				$xmlText .= '<field 
				name="allow_cookies_btn_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_ALLOW_COOKIES').'" 
				label="PLG_SYSTEM_CPNB_ALLOW_COOKIES_BTN_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_ALLOW_COOKIES_BTN_TEXT_DESC" 
				filter="STRING" 
				showon="enable_shortcode_functionality:1[OR]modalState:1"
				/>';

				// DECLINE COOKIES BUTTON TEXT
				$xmlText .= '<field 
				name="decline_cookies_btn_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_DECLINE_COOKIES').'" 
				label="PLG_SYSTEM_CPNB_DECLINE_COOKIES_BTN_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_DECLINE_COOKIES_BTN_TEXT_DESC" 
				filter="STRING" 
				showon="enable_shortcode_functionality:1[OR]modalState:1"
				/>';

				// DELETE COOKIES BUTTON TEXT
				$xmlText .= '<field 
				name="delete_cookies_btn_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_DELETE_COOKIES').'" 
				label="PLG_SYSTEM_CPNB_DELETE_COOKIES_BTN_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_DELETE_COOKIES_BTN_TEXT_DESC" 
				filter="STRING" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// RELOAD COOKIES BUTTON TEXT
				$xmlText .= '<field 
				name="reload_cookies_btn_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_RELOAD').'" 
				label="PLG_SYSTEM_CPNB_RELOAD_COOKIES_BTN_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_RELOAD_COOKIES_BTN_TEXT_DESC" 
				filter="STRING" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// SAVE SETTINGS BUTTON TEXT
				$xmlText .= '<field 
				name="save_settings_btn_text_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_SAVE_SETTINGS').'" 
				label="PLG_SYSTEM_CPNB_SAVE_SETTINGS_BTN_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_SAVE_SETTINGS_BTN_TEXT_DESC" 
				filter="STRING" 
				showon="modalState:1"
				/>';

				// ALERT MESSAGES (Note)
				$xmlText .= '<field name="note_alert_messages_'.$lang_code.'" type="note" label="PLG_SYSTEM_ALERT_MESSAGES_NOTE_LBL" description="" />';

				// ALLOW COOKIES CONFIRMATION ALERT
				$xmlText .= '<field 
				name="allow_cookies_confirmation_alert_txt_'.$lang_code.'" 
				type="textarea" 
				default="'.Text::_('PLG_SYSTEM_CPNB_ALLOW_COOKIES_CONFIRMATION').'" 
				label="PLG_SYSTEM_CPNB_ALLOW_COOKIES_CONFIRMATION_LBL" 
				description="PLG_SYSTEM_CPNB_ALLOW_COOKIES_CONFIRMATION_DESC" 
				rows="6" 
				cols="50" 
				filter="raw" 
				/>';

				// DELETE COOKIES CONFIRMATION ALERT
				$xmlText .= '<field 
				name="delete_cookies_confirmation_alert_txt_'.$lang_code.'" 
				type="textarea" 
				default="'.Text::_('PLG_SYSTEM_CPNB_DELETE_COOKIES_CONFIRMATION').'" 
				label="PLG_SYSTEM_CPNB_DELETE_COOKIES_CONFIRMATION_LBL" 
				description="PLG_SYSTEM_CPNB_DELETE_COOKIES_CONFIRMATION_DESC" 
				rows="6" 
				cols="50" 
				filter="raw" 
				/>';

				// LOCKED COOKIES CATEGORY CONFIRMATION ALERT
				$xmlText .= '<field 
				name="locked_cookies_category_confirmation_alert_txt_'.$lang_code.'" 
				type="textarea" 
				default="'.Text::_('PLG_SYSTEM_CPNB_LOCKED_COOKIES_CATEGORY_CONFIRMATION').'" 
				label="PLG_SYSTEM_CPNB_LOCKED_COOKIES_CATEGORY_CONFIRMATION_LBL" 
				description="PLG_SYSTEM_CPNB_LOCKED_COOKIES_CATEGORY_CONFIRMATION_DESC" 
				rows="6" 
				cols="50" 
				filter="raw" 
				showon="modalState:1"
				/>';

				// OTHER TEXTS (Note)
				$xmlText .= '<field name="note_other_texts_'.$lang_code.'" type="note" label="PLG_SYSTEM_OTHER_TEXTS_LBL" description="" showon="enable_shortcode_functionality:1" />';

				// ACCEPT COOKIE DESCRIPTION
				$xmlText .= '<field 
				name="accept_cookies_descrpiption_txt_'.$lang_code.'" 
				type="textarea" 
				default="'.Text::_('PLG_SYSTEM_CPNB_ACCEPT_COOKIES_DESCRIPTION_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_ACCEPT_COOKIES_DESCRIPTION_LBL" 
				description="PLG_SYSTEM_CPNB_ACCEPT_COOKIES_DESCRIPTION_DESC" 
				rows="6" 
				cols="50" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// DECLINE COOKIE DESCRIPTION
				$xmlText .= '<field 
				name="decline_cookies_descrpiption_txt_'.$lang_code.'" 
				type="textarea" 
				default="'.Text::_('PLG_SYSTEM_CPNB_DECLINE_COOKIES_DESCRIPTION_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_DECLINE_COOKIES_DESCRIPTION_LBL" 
				description="PLG_SYSTEM_CPNB_DECLINE_COOKIES_DESCRIPTION_DESC" 
				rows="6" 
				cols="50" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// SETTINGS COOKIE DESCRIPTION
				$xmlText .= '<field 
				name="settings_cookies_descrpiption_txt_'.$lang_code.'" 
				type="textarea" 
				default="'.Text::_('PLG_SYSTEM_CPNB_SETTINGS_COOKIES_DESCRIPTION_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_SETTINGS_COOKIES_DESCRIPTION_LBL" 
				description="PLG_SYSTEM_CPNB_SETTINGS_COOKIES_DESCRIPTION_DESC" 
				rows="6" 
				cols="50" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// PLEASE WAIT
				$xmlText .= '<field 
				name="please_wait_txt_'.$lang_code.'" 
				type="textarea" 
				default="'.Text::_('PLG_SYSTEM_CPNB_PLEASE_WAIT').'" 
				label="PLG_SYSTEM_CPNB_PLEASE_WAIT_LBL" 
				description="PLG_SYSTEM_CPNB_PLEASE_WAIT_DESC" 
				rows="6" 
				cols="50" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// MINUTE
				$xmlText .= '<field 
				name="minute_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_MINUTE_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_MINUTE_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// MINUTES
				$xmlText .= '<field 
				name="minutes_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_MINUTES_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_MINUTES_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// HOUR
				$xmlText .= '<field 
				name="hour_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_HOUR_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_HOUR_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// HOURS
				$xmlText .= '<field 
				name="hours_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_HOURS_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_HOURS_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// DAY
				$xmlText .= '<field 
				name="day_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_DAY_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_DAY_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// DAYS
				$xmlText .= '<field 
				name="days_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_DAYS_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_DAYS_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// MONTH
				$xmlText .= '<field 
				name="month_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_MONTH_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_MONTH_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// MONTHS
				$xmlText .= '<field 
				name="months_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_MONTHS_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_MONTHS_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// YEAR
				$xmlText .= '<field 
				name="year_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_YEAR_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_YEAR_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// YEARS
				$xmlText .= '<field 
				name="years_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_YEARS_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_YEARS_LBL" 
				description="PLG_SYSTEM_CPNB_TIME_DESC" 
				filter="raw" 
				showon="enable_shortcode_functionality:1" 
				/>';

				// FLOAT ICON BUTTON TEXT
				$xmlText .= '<field 
				name="float_icon_button_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_FLOAT_ICON_BUTTON_TEXT_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_FLOAT_ICON_BUTTON_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_FLOAT_ICON_BUTTON_TEXT_DESC" 
				filter="raw" 
				showon="modalState:1[AND]modalFloatButtonState:1" 
				/>';

				// COOKIES MANAGER HEADING TEXT
				$xmlText .= '<field 
				name="cookies_manager_heading_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_COOKIES_MANAGER_HEADING_TEXT_DEFAULT_TEXT').'" 
				label="PLG_SYSTEM_CPNB_COOKIES_MANAGER_HEADING_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_COOKIES_MANAGER_HEADING_TEXT_DESC" 
				filter="raw" 
				showon="modalState:1" 
				/>';

				// COOKIES CATEGORY CHECKBOX LABEL TEXT
				$xmlText .= '<field 
				name="cookies_category_checkbox_label_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('JENABLED').'" 
				label="PLG_SYSTEM_CPNB_COOKIES_CATEGORY_CHECKBOX_LABEL_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_COOKIES_CATEGORY_CHECKBOX_LABEL_TEXT_DESC" 
				filter="raw" 
				showon="modalState:1" 
				/>';

				// COOKIES CATEGORY LOCKED TEXT
				$xmlText .= '<field 
				name="cookies_category_locked_txt_'.$lang_code.'" 
				type="text" 
				default="'.Text::_('PLG_SYSTEM_CPNB_COOKIES_CATEGORY_LOCKED_TEXT_DEFAULT').'" 
				label="PLG_SYSTEM_CPNB_COOKIES_CATEGORY_LOCKED_TEXT_LBL" 
				description="PLG_SYSTEM_CPNB_COOKIES_CATEGORY_LOCKED_TEXT_DESC" 
				filter="raw" 
				showon="modalState:1" 
				/>';

				// OLD TEXTS FOR LANGUAGES
				if (version_compare($currentVersion, '4.0', '<'))
				{
					$xmlText .= '<field 
					name="textsforlanguagesold" 
					id="textsforlanguagesold"
					type="textsforlanguagesold" 
					default="600" 
					addfieldpath="/plugins/system/cookiespolicynotificationbar/elements"
					/>';
				}

				$xmlText .= '<field type="spacer" name="myspacer" hr="true" />';

				// closing xml file
				$xmlText .= '
						</fieldset>
					</fields>
				</form>';
				$xmlObj = new SimpleXMLElement($xmlText);
				$form->setField($xmlObj, 'params', true, 'texts_for_languages');
			}
		}

		public function getLangFormCustom404ErrorPage($form, $language_name = "English", $lang_code = "en_GB")
		{
			if (isset($form))
			{
				// start building xml file
				$xmlText = '<?xml version="1.0" encoding="utf-8"?>
				<form>
					<fields>
						<fieldset name="texts_for_languages_custom404errorpage" addfieldprefix="Joomla\Component\Menus\Administrator\Field">';

				// HEADER
				$xmlText .= '<field type="langheader" name="header_'.$lang_code.'" class="w357_large_header" addfieldpath="/plugins/system/custom404errorpage/elements" lang_code="'.$lang_code.'" language_name="'.$language_name.'" />';
				
				// LINK OR Menu Item
				$xmlText .= '<field 
				name="custom404erropage_link_type_'.$lang_code.'" 
				type="list" 
				default="core" 
				label="PLG_SYSTEM_CUSTOM404ERRORPAGE_SELECT_ERROR_404_REDIRECT_TYPE_LBL" 
				description="PLG_SYSTEM_CUSTOM404ERRORPAGE_SELECT_ERROR_404_REDIRECT_TYPE_DESC">
				<option value="core">PLG_SYSTEM_CUSTOM404ERRORPAGE_SELECT_ERROR_404_REDIRECT_TYPE_OPTION_CORE</option>
				<option value="menu_item">PLG_SYSTEM_CUSTOM404ERRORPAGE_SELECT_ERROR_404_REDIRECT_TYPE_OPTION_MENU_ITEM</option>
				</field>';
				
				// MENU ITEM
				$xmlText .= '<field 
				name="custom404erropage_menu_item_'.$lang_code.'" 
				type="modal_menu"
				label="PLG_SYSTEM_CUSTOM404ERRORPAGE_SELECT_ERROR_404_REDIRECT_TYPE_OPTION_MENU_ITEM"
				description=""
				required="false"
				select="true"
				new="true"
				edit="true"
				clear="true"
				addfieldpath="/administrator/components/com_menus/models/fields" 
				showon="custom404erropage_link_type_'.$lang_code.':menu_item" 
				/>';

				// closing xml file
				$xmlText .= '
						</fieldset>
					</fields>
				</form>';
				$xmlObj = new SimpleXMLElement($xmlText);
				$form->setField($xmlObj, 'params', true, 'texts_for_languages');
			}
		}

		public function getUsersFormFieldLoginAsUser($form, $usergroup_id, $usergroup_name)
		{
			if (isset($form))
			{
				// start building xml file
				$xmlText = '<?xml version="1.0" encoding="utf-8"?>
				<form>
					<fields>
						<fieldset name="loginasuser" addfieldprefix="Joomla\Component\Menus\Administrator\Field">';

				// HEADER
				$xmlText .= '<field type="header" name="header_'.$usergroup_id.'" class="w357_small_header" label="'.$usergroup_name.' ('.Text::_('PLG_LOGINASUSER_USER_GROUP').')" />';
				
				// ENABLE/DISABLED FOR THIS USER GROUP
				$xmlText .= '<field 
				name="enable_'.$usergroup_id.'_loginasuser" 
				type="radio" 
				class="btn-group btn-group-yesno" 
				default="1" 
				label="PLG_LOGINASUSER_ENABLE_FOR_THIS_USERGROUP_LBL" 
				description="PLG_LOGINASUSER_ENABLE_FOR_THIS_USERGROUP_DESC">
				<option value="1">JENABLED</option>
				<option value="0">JDISABLED</option>
				</field>';

				// NOTE
				$xmlText .= '<field name="note_'.$usergroup_id.'" type="note" label="" description="'.Text::_('PLG_LOGINASUSER_USER_GROUP_NOTE').'" showon="enable_'.$usergroup_id.'_loginasuser:1" />';

				// USERS
				$xmlText .= '<field 
				name="users_'.$usergroup_id.'" 
				type="sql" 
				label="PLG_LOGINASUSER_SELECT_ADMINS_LBL" 
				description="PLG_LOGINASUSER_SELECT_ADMINS_DESC" 
				query="SELECT u.id AS value, CONCAT(u.name, \' (\', GROUP_CONCAT(ug.title), \')\') AS users_'.$usergroup_id.' FROM #__users AS u LEFT JOIN #__user_usergroup_map AS ugm ON u.id = ugm.user_id LEFT JOIN #__usergroups AS ug ON ugm.group_id = ug.id WHERE (ug.id=8 OR ug.id=7 OR ug.id=6) GROUP BY u.id ORDER BY u.name ASC" 
				multiple="true" 
				showon="enable_'.$usergroup_id.'_loginasuser:1"
				/>';

				$xmlText .= '<field type="spacer" name="myspacer_'.$usergroup_id.'" hr="true" />';

				// closing xml file
				$xmlText .= '
						</fieldset>
					</fields>
				</form>';
				$xmlObj = new SimpleXMLElement($xmlText);
				$form->setField($xmlObj, 'params', true, 'permissions_for_loginasuser');
			}
		}

		public function getDefaultLanguageName()
		{
			$db = Factory::getDBO();
			$query = "SELECT title_native "
			."FROM #__languages "
			."WHERE published = 1"
			;
			$db->setQuery($query);
			$db->execute();
	
			return $db->loadResult();
		}
		
		public function getLanguageNameByTag($tag)
		{
			$db = Factory::getDBO();
			$query = "SELECT title_native "
			."FROM #__languages "
			."WHERE lang_code = '".$tag."' AND published = 1"
			;
			$db->setQuery($query);
			$db->execute();
			$result = $db->loadResult();
			
			// If there are more than one language
			if ($result !== null):
				return $result;
			// If there is only one language
			else:
				return $this->getDefaultLanguageName();
			endif;
	
		}
	
		public function getLanguageImage($lang_code)
		{
			$db = Factory::getDBO();
			$query = "SELECT image "
			."FROM #__languages "
			."WHERE lang_code = '".$lang_code."' AND published = 1"
			;
			$db->setQuery($query);
			$db->execute();
			$result = $db->loadResult();
			
			// If there are more than one language
			if ($result !== null):
				return $result;
			// If there is only one language
			else:
				return '';
			endif;
	
		}

	}
endif;