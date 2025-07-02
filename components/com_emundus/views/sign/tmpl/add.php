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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\LanguageHelper;

## GLOBAL ##
Text::script('COM_EMUNDUS_ERROR');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_VISUALIZE');
Text::script('COM_EMUNDUS_ONBOARD_OK');
Text::script('COM_EMUNDUS_ONBOARD_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_ALL');
Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');
Text::script('COM_EMUNDUS_REGISTRANTS_FILE_READY');
Text::script('COM_EMUNDUS_ONBOARD_EDITOR_UNDO');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_NO_CONNECTORS');
Text::script('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_SIGNER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNERS_TITLE');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NO');
## END ##

## ACTIONS ##
Text::script('COM_EMUNDUS_ONBOARD_ACTION');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
Text::script('COM_EMUNDUS_MULTISELECT_NORESULTS');
## END ##

## FILTERS ##
Text::script('COM_EMUNDUS_ONBOARD_FILTER');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_ALL');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_OPEN');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_CLOSE');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_SELECT');
Text::script('COM_EMUNDUS_ONBOARD_DESELECT');
Text::script('COM_EMUNDUS_ONBOARD_TOTAL');
Text::script('COM_EMUNDUS_ONBOARD_SORT');
Text::script('COM_EMUNDUS_ONBOARD_SORT_CREASING');
Text::script('COM_EMUNDUS_ONBOARD_SORT_DECREASING');
Text::script('COM_EMUNDUS_ONBOARD_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_ALL_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_SEARCH');
## END ##

## REQUESTS ##
Text::script('COM_EMUNDUS_ONBOARD_LABEL_REQUESTS');
Text::script('COM_EMUNDUS_ONBOARD_REQUESTS');
Text::script('COM_EMUNDUS_ONBOARD_REQUESTS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_NO_REQUESTS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_REQUEST');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONFIRM');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_APPLICANT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_APPLICANT_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_ATTACHMENT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_ATTACHMENT_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONNECTOR');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_CONNECTOR_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_SIGNERS');
Text::script('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_SIGNERS_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_ADD_SAVED');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_CANCEL_REQUEST');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_CANCEL_CONFIRM');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_UPLOAD');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_EDIT_UPLOAD_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_CANCEL_REASON');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_APPLICANTS_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_APPLICANTS_ALL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_ATTACHMENTS_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_ATTACHMENTS_ALL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_SIGNED_DATE_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_STATUS_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_FILTER_STATUS_ALL');
Text::script('COM_EMUNDUS_ONBOARD_SIGN_EXPORTS_EXCEL');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_SEND_REMINDER');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SEND_REMINDER_CONFIRM');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER');
Text::script('COM_EMUNDUS_ONBOARD_ADD_REQUEST_CREATE_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_REQUEST_EDIT_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_LASTNAME');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_FIRSTNAME');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_EMAIL');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_EDIT_CONTACT');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTACT_CREATE');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_TAG_FOUND');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_TAGS_FOUND');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_PAGE_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_PAGE');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_POSITION_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_POSITION_HELP');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_POSITION_ERROR_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_POSITION_ERROR_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_INCOMPLETE_ERROR_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_INCOMPLETE_ERROR_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNATURE_INCOMPLETE_ERROR_TEXT');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_ERROR_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_STANDARD');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_AES');
Text::script('COM_EMUNDUS_ONBOARD_REQUEST_SIGNER_AUTHENTICATION_LEVEL_QES');

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');

$app = Factory::getApplication();
$lang = $app->getLanguage();
$user = $app->getIdentity();

$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$languages    = LanguageHelper::getLanguages();
if (count($languages) > 1) {
	$many_languages = '1';
	require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
	$m_translations = new EmundusModelTranslations();
	$default_lang   = $m_translations->getDefaultLanguage()->lang_code;
}
else {
	$many_languages = '0';
	$default_lang   = $current_lang;
}

$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($user->id);

require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');
$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div id="em-component-vue"
     component="Sign/SaveRequest"
     shortLang="<?= $short_lang ?>" currentLanguage="<?= $current_lang ?>"
     defaultLang="<?= $default_lang ?>"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
     manyLanguages="<?= $many_languages ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
