<?php

/**
 * @package     Joomla
 * @subpackage  com_emundus
 *
 * @copyright   Copyright (C) 2005 - 2018 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access to this file
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted Access');

## GLOBAL ##
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_VISUALIZE');
Text::script('COM_EMUNDUS_ONBOARD_OK');
Text::script('COM_EMUNDUS_ONBOARD_CANCEL');
Text::script('COM_EMUNDUS_ONBOARD_ALL');
Text::script('COM_EMUNDUS_ONBOARD_SYSTEM');
Text::script('COM_EMUNDUS_ONBOARD_EMAILS');
Text::script('COM_EMUNDUS_ONBOARD_EMAILS_DESC');
Text::script('COM_EMUNDUS_ONBOARD_EMAIL_PREVIEWMODEL');
Text::script('COM_EMUNDUS_ONBOARD_CATEGORIES');
Text::script('COM_EMUNDUS_ONBOARD_CANT_REVERT');
Text::script('COM_EMUNDUS_ONBOARD_EMPTY_LIST');
Text::script('COM_EMUNDUS_ONBOARD_LABEL_EMAILS');
## END ##

## ACTIONS ##
Text::script('COM_EMUNDUS_ONBOARD_ACTION');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DELETE');
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

## EMAIL ##
Text::script('COM_EMUNDUS_ONBOARD_ADD_EMAIL');
Text::script('COM_EMUNDUS_ONBOARD_NOEMAIL');
Text::script('COM_EMUNDUS_ONBOARD_EMAILDELETE');
Text::script('COM_EMUNDUS_ONBOARD_EMAILDELETED');
Text::script('COM_EMUNDUS_ONBOARD_EMAILUNPUBLISHED');
Text::script('COM_EMUNDUS_ONBOARD_EMAILPUBLISHED');
## END ##

## TUTORIAL ##
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_CAMPAIGN');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORM');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORMBUILDER');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_DOCUMENTS');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_PROGRAM');
## END ##

Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TAGS');
Text::script('COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT');

Text::script('COM_EMUNDUS_ONBOARD_ADD_EMAIL');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_CHOOSETYPE');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_NAME');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_RECEIVER');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESS');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PARAMETER');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_INFORMATION');
Text::script('COM_EMUNDUS_ONBOARD_CHOOSECATEGORY');
Text::script('COM_EMUNDUS_ONBOARD_ADD_RETOUR');
Text::script('COM_EMUNDUS_ONBOARD_ADD_CONTINUER');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_RESUME');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_CATEGORY');
Text::script('COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE');
Text::script('COM_EMUNDUS_ONBOARD_EMAILTYPE');
Text::script('COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING');
Text::script('COM_EMUNDUS_ONBOARD_SUBJECT_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_BODY_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_ADDEMAIL_BODY');
Text::script('COM_EMUNDUS_ONBOARD_VARIABLESTIP');
Text::script('COM_EMUNDUS_ONBOARD_TIP');
Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TRIGGER');
Text::script('COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERMODEL_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERSTATUS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERSTATUS_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERTARGET');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERTARGET_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_CANDIDATES');
Text::script('COM_EMUNDUS_ONBOARD_PROGRAM_DEFINED_USERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_CHOOSE_USERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_USERS_REQUIRED');
Text::script('COM_EMUNDUS_ONBOARD_SEARCH_USERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERMODEL');
Text::script('COM_EMUNDUS_ONBOARD_THE_CANDIDATE');
Text::script('COM_EMUNDUS_ONBOARD_MANUAL');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_ACTIONS');

## TUTORIAL ##
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_CAMPAIGN');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORM');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_FORMBUILDER');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_DOCUMENTS');
Text::script('COM_EMUNDUS_ONBOARD_TUTORIAL_PROGRAM');
## END ##

Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TAGS');
Text::script('COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT');

# receiver
Text::script('COM_EMUNDUS_ONBOARD_RECEIVER_CC_TAGS');
Text::script('COM_EMUNDUS_ONBOARD_RECEIVER_BCC_TAGS');

Text::script('COM_EMUNDUS_ONBOARD_RECEIVER_CC_TAGS_PLACEHOLDER');
Text::script('COM_EMUNDUS_ONBOARD_RECEIVER_BCC_TAGS_PLACEHOLDER');

Text::script('COM_EMUNDUS_ONBOARD_PLACEHOLDER_EMAIL_DOCUMENT');

Text::script('COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT');

Text::script('COM_EMUNDUS_ONBOARD_CC_BCC_TOOLTIPS');

Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TAGS');
Text::script('COM_EMUNDUS_ONBOARD_PLACEHOLDER_EMAIL_TAGS');

Text::script('COM_EMUNDUS_ONBOARD_CANDIDAT_ATTACHMENTS');
Text::script('COM_EMUNDUS_ONBOARD_PLACEHOLDER_CANDIDAT_ATTACHMENTS');

Text::script('COM_EMUNDUS_ONBOARD_NAME');
Text::script('COM_EMUNDUS_ONBOARD_START_DATE');
Text::script('COM_EMUNDUS_ONBOARD_END_DATE');
Text::script('COM_EMUNDUS_ONBOARD_STATE');
Text::script('COM_EMUNDUS_ONBOARD_NB_FILES');
Text::script('COM_EMUNDUS_ONBOARD_SUBJECT');
Text::script('COM_EMUNDUS_ONBOARD_TYPE');
Text::script('COM_EMUNDUS_ONBOARD_CATEGORY');
Text::script('COM_EMUNDUS_ONBOARD_STATUS');
Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TYPE_SYSTEM');
Text::script('COM_EMUNDUS_ONBOARD_EMAIL_TYPE_MODEL');
Text::script('COM_EMUNDUS_ONBOARD_EMAILS_CONFIRM_DELETE');

Text::script('COM_EMUNDUS_ONBOARD_ALL_PROGRAM_CATEGORIES');
Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');
Text::script('COM_EMUNDUS_ONBOARD_EMAILS_FILTER_CATEGORY');

# SMS
Text::script('COM_EMUNDUS_ONBOARD_SMS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_SMS');
Text::script('COM_EMUNDUS_ONBOARD_NOSMS');
Text::script('COM_EMUNDUS_SMS_PLACEHOLDER');

require_once(JPATH_BASE . '/components/com_emundus/helpers/access.php');
require_once(JPATH_BASE . '/components/com_emundus/helpers/cache.php');

$app = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$languages    = LanguageHelper::getLanguages();
if (count($languages) > 1) {
	$many_languages = '1';
}
else {
	$many_languages = '0';
}

$user               = $app->getIdentity();
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($user->id);

$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div id="em-component-vue"
      component="Emails"
      coordinatorAccess="<?= $coordinator_access ?>"
      sysadminAccess="<?= $sysadmin_access ?>"
      shortLang="<?= $short_lang ?>"
      currentLanguage="<?= $current_lang ?>"
      manyLanguages="<?= $many_languages ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>
