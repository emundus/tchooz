<?php
/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

require_once (JPATH_COMPONENT.DS.'helpers'.DS.'access.php');

// GLOBAL
JText::script('COM_EMUNDUS_ONBOARD_ADD_RETOUR');
JText::script('COM_EMUNDUS_ONBOARD_ADD_CONTINUER');
JText::script('COM_EMUNDUS_ONBOARD_OK');
JText::script('COM_EMUNDUS_ONBOARD_CANCEL');
JText::script('COM_EMUNDUS_ONBOARD_NEXT');
JText::script('COM_EMUNDUS_ONBOARD_LOAD_FILE');
JText::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM');
JText::script('COM_EMUNDUS_ONBOARD_SELECT_ALL');
JText::script('COM_EMUNDUS_ONBOARD_MODIFY');
JText::script('COM_EMUNDUS_ONBOARD_UPDATE_ICON');
JText::script('COM_EMUNDUS_SWAL_OK_BUTTON');

// MENUS
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_STYLE');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_STYLE_DESC');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_HOMEPAGE');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_HOMEPAGE_DESC');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_TERMS');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_TERMS_DESC');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_FOOTER');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_FOOTER_DESC');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_LEGAL_MENTION');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_DATAS');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_RIGHTS');
JText::script('COM_EMUNDUS_ONBOARD_CONTENT_TOOL_COOKIES');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_STATUS');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_STATUS_DESC');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_TAGS');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_TAGS_DESC');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_APPLICANTS');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_APPLICANTS_DESC');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_DATAS');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_DATAS_DESC');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_TRANSLATIONS');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_TRANSLATIONS_DESC');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_FILES_TOOL');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_FILES_TOOL_DESC');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_ATTACHMENT_STORAGE');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_ATTACHMENT_STORAGE_DESC');
JText::script('COM_EMUNDUS_ONBOARD_STATUSDESCRIPTION');
JText::script('COM_EMUNDUS_ONBOARD_STYLINGDESCRIPTION');
JText::script('COM_EMUNDUS_ONBOARD_TAGSDESCRIPTION');
JText::script('COM_EMUNDUS_ONBOARD_HOMEDESCRIPTION');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_ADDTAG');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATE_ENGLISH');
JText::script('COM_EMUNDUS_ONBOARD_COLORS');
JText::script('COM_EMUNDUS_ONBOARD_UPDATE_LOGO');
JText::script('COM_EMUNDUS_ONBOARD_DROP_HERE');
JText::script('COM_EMUNDUS_ONBOARD_REMOVE_FILE');
JText::script('COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD');
JText::script('COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD_CONFIRMATION');
JText::script('COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE');
JText::script('COM_EMUNDUS_ONBOARD_FILE_TOO_BIG');
JText::script('COM_EMUNDUS_ONBOARD_MAX_FILES_EXCEEDED');
JText::script('COM_EMUNDUS_ONBOARD_ERROR');
JText::script('COM_EMUNDUS_ONBOARD_PRIMARY_COLOR');
JText::script('COM_EMUNDUS_ONBOARD_SECONDARY_COLOR');
JText::script('COM_EMUNDUS_ONBOARD_BUILDER_UPDATE');
JText::script('COM_EMUNDUS_ONBOARD_COLOR_SUCCESS');
JText::script('COM_EMUNDUS_ONBOARD_CREATE_DATAS');
JText::script('COM_EMUNDUS_ONBOARD_IMPORT_DATAS');
JText::script('COM_EMUNDUS_ONBOARD_FIRSTNAME');
JText::script('COM_EMUNDUS_ONBOARD_FIRSTNAME_REQUIRED');
JText::script('COM_EMUNDUS_ONBOARD_LASTNAME');
JText::script('COM_EMUNDUS_ONBOARD_LASTNAME_REQUIRED');
JText::script('COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION');
JText::script('COM_EMUNDUS_ONBOARD_VALUES');
JText::script('COM_EMUNDUS_ONBOARD_USERSDESCRIPTIONSETTINGS');
JText::script('COM_EMUNDUS_ONBOARD_LAST_CONNECTED');
JText::script('COM_EMUNDUS_ONBOARD_EMAIL');
JText::script('COM_EMUNDUS_ONBOARD_EMAIL_REQUIRED');
JText::script('COM_EMUNDUS_ONBOARD_DOSSIERS_STATUS');
JText::script('COM_EMUNDUS_ONBOARD_SEARCH');
JText::script('COM_EMUNDUS_ONBOARD_ACTIVATED');
JText::script('COM_EMUNDUS_ONBOARD_BLOCKED');
JText::script('COM_EMUNDUS_ONBOARD_NO_RESULTS_FOUND');
JText::script('COM_EMUNDUS_ONBOARD_ACTIONS');
JText::script('COM_EMUNDUS_ONBOARD_PROGRAM_ADDUSER');
JText::script('COM_EMUNDUS_ONBOARD_RESET_PASSWORD');
JText::script('COM_EMUNDUS_ONBOARD_RESET_PASSWORD_MESSAGE');
JText::script('COM_EMUNDUS_ONBOARD_LOCK_USER');
JText::script('COM_EMUNDUS_ONBOARD_UNLOCK_USER');
JText::script('COM_EMUNDUS_ONBOARD_ROLE');
JText::script('COM_EMUNDUS_ONBOARD_ROLE_REQUIRED');
JText::script('COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATOR');
JText::script('COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATOR');
JText::script('COM_EMUNDUS_ONBOARD_UPDATE_DATAS');
JText::script('COM_EMUNDUS_ONBOARD_BLOCKED_USERS');
JText::script('COM_EMUNDUS_ONBOARD_COLUMNS');
JText::script('COM_EMUNDUS_ONBOARD_MY_COLUMNS');
JText::script('COM_EMUNDUS_ONBOARD_CSV_COLUMNS');
JText::script('COM_EMUNDUS_ONBOARD_CSV_ASSOCIATION');
JText::script('COM_EMUNDUS_ONBOARD_LEAST_ONE_COLUMN_REQUIRED');
JText::script('COM_EMUNDUS_ONBOARD_ADD_STATUS');
JText::script('COM_EMUNDUS_ONBOARD_SAVE');
JText::script('COM_EMUNDUS_ONBOARD_SAVED');
JText::script('COM_EMUNDUS_ONBOARD_COLUMN');
JText::script('COM_EMUNDUS_ONBOARD_PREVIEW');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATE_IN');
JText::script('COM_EMUNDUS_ONBOARD_ICON');
JText::script('COM_EMUNDUS_ONBOARD_UPDATE_BACKGROUND');
JText::script('COM_EMUNDUS_ONBOARD_BACKGROUND');
JText::script('COM_EMUNDUS_ONBOARD_DISPLAY_BACKGROUND');
JText::script('COM_EMUNDUS_ONBOARD_HOME_TITLE');
JText::script('COM_EMUNDUS_ONBOARD_HOME_CONTENT');
JText::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PARAMETER');
JText::script('COM_EMUNDUS_ONBOARD_CANNOT_DELETE_STATUS');
JText::script('COM_EMUNDUS_ONBOARD_STYLE_TOOL_GENERAL');
JText::script('COM_EMUNDUS_FORM_BUILDER_ALLOWED_FORMATS');

## TUTORIAL ##
JText::script('COM_EMUNDUS_ONBOARD_TUTORIAL_CAMPAIGN');
JText::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORM');
JText::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORMBUILDER');
JText::script('COM_EMUNDUS_ONBOARD_TUTORIAL_DOCUMENTS');
JText::script('COM_EMUNDUS_ONBOARD_TUTORIAL_PROGRAM');
JText::script('COM_EMUNDUS_ONBOARD_TUTORIAL_REWIND');
JText::script('COM_EMUNDUS_ONBOARD_TUTORIAL_REWIND_SUCCESS');
JText::script('COM_EMUNDUS_ONBOARD_REMOVE_ICON');
JText::script('COM_EMUNDUS_ONBOARD_REMOVE_ICON_TEXT');
JText::script('COM_EMUNDUS_ONBOARD_UPDATE_COLORS');
JText::script('COM_EMUNDUS_ONBOARD_DELETE_STATUS');
JText::script('COM_EMUNDUS_ONBOARD_INSERT_HEADER_IMAGE');
JText::script('COM_EMUNDUS_ONBOARD_INSERT_LOGO');
JText::script('COM_EMUNDUS_ONBOARD_INSERT_ICON');
JText::script('COM_EMUNDUS_ONBOARD_ICON_TIP_TEXT');
JText::script('COM_EMUNDUS_FORM_BUILDER_ICON_RECOMMENDED');
JText::script('COM_EMUNDUS_FORM_BUILDER_LOGO_RECOMMENDED');
JText::script('COM_EMUNDUS_ONBOARD_LOGO_TIP_TEXT');
JText::script('COM_EMUNDUS_FORM_BUILDER_COLORS_RECOMMENDED');
JText::script('COM_EMUNDUS_ONBOARD_BANNER_TIP_TEXT');
JText::script('COM_EMUNDUS_ONBOARD_UPDATE_BANNER');
## END ##

## eMUNDUS CONFIG ##
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANT_CAN_RENEW');
JText::script('JNO');
JText::script('JYES');
JText::script('COM_EMUNDUS_APPLICANT_CAN_RENEW_CAMPAIGN');
JText::script('COM_EMUNDUS_APPLICANT_CAN_RENEW_YEAR');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANT_CAN_EDIT_UNTIL_DEADLINE');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANTS_DESC');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANTS');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANT_COPY_APPLICATION_FORM');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANT_CAN_SUBMIT_ENCRYPTED');
## END ##

## TRANSLATIONS ##
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_GLOBAL');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_NO_LANGUAGES_AVAILABLE');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHELINS');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_DEFAULT');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_DEFAULT_DESC');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SECONDARY');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SECONDARY_DESC');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_LANGUAGE');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_TRANSLATION_TITLE');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_TRANSLATION_TEXT');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_OBJECT');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_PROGRESS');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_LAST');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_ORPHELINS_TITLE');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_ORPHELINS_TEXT');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHELIN_CONFIRM_TRANSLATION');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_OTHER_LANGUAGE');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SETUP_PROGRESSING');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SETUP_SUCCESS');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_FIELD');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SEND');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SENDED');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SENDED_TEXT');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_CONTENT');
JText::script('COM_EMUNDUS_ONBOARD_SETTINGS_MENU_CONTENT_DESC');
JText::script('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHANS_CONGRATULATIONS');
JText::script('COM_EMUNDUS_ONBOARD_BANNER');
JText::script('COM_EMUNDUS_FORM_BUILDER_RECOMMENDED_SIZE');
## END ##

## CONTENTELEMENT ##
JText::script('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES');
JText::script('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_DESC');
JText::script('COM_EMUNDUS_CONTENTELEMENTS_SETUP_PROFILES_FABRIK_FORMS');
JText::script('COM_EMUNDUS_CONTENTELEMENTS_SETUP_CAMPAIGNS');
JText::script('COM_EMUNDUS_CONTENTELEMENTS_SETUP_CAMPAIGNS_DESC');
## END ##

## SU GED ##
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_CONFIGURATION');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_STORAGE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_ADD_MENU');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_DELETE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_CONF_WRITING');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_SELECT_TYPE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_CAMPAIGN_TYPE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_FILE_TYPE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_USER_TYPE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_YEAR_TYPE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_OTHER');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_NAME_WRITING');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_SEPARATOR');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_NAME_RESET');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_NAME_TAGS_LIST');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_NAME_SELECT_A_TAG');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_DOCTYPE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_STATUS');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_STORAGE_TYPE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_SYNCHRO');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_SYNC_TYPE_LOCAL');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GED_ALFRESCO_SYNC_TYPE_GED');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_SYNC_READ');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_SYNC_WRITE');
JText::script('COM_EMUNDUS_ONBOARD_ATTACHMENT_STORAGE_GO_BACK_TO_SELECT');
## END ##

JText::script('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS');
JText::script('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_UPLOAD');
JText::script('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_MAPPING');
JText::script('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_UPLOAD_UPDATE');
JText::script('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_UPLOAD_ADD');
JText::script('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_UPLOAD_ADD_FROM_FILE');
JText::script('COM_EMUNDUS_ATTACHMENT_STORAGE_DEFAULT_ASPECTS_MAPPING');
Jtext::script('COM_EMUNDUS_ATTACHMENT_STORAGE_GED_ALFRESCO_ASPECTS_MISSING_ASPECT_FILE');

$lang = JFactory::getLanguage();
$short_lang = substr($lang->getTag(), 0 , 2);
$current_lang = $lang->getTag();
$languages = JLanguageHelper::getLanguages();
if (count($languages) > 1) {
    $many_languages = '1';
    require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
    $m_translations = new EmundusModelTranslations();
    $default_lang = $m_translations->getDefaultLanguage()->lang_code;
} else {
    $many_languages = '0';
    $default_lang = $current_lang;
}

$user = JFactory::getUser();
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access = EmundusHelperAccess::isAdministrator($user->id);

$xmlDoc = new DOMDocument();
if ($xmlDoc->load(JPATH_SITE.'/administrator/components/com_emundus/emundus.xml')) {
    $release_version = $xmlDoc->getElementsByTagName('version')->item(0)->textContent;
}
?>

<div id="em-component-vue"
     component="settings"
     shortLang="<?= $short_lang ?>" currentLanguage="<?= $current_lang ?>"
     defaultLang="<?= $default_lang ?>"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
     manyLanguages="<?= $many_languages ?>"
></div>

<script src="media/com_emundus_vue/app_emundus.js?<?php echo $release_version ?>"></script>