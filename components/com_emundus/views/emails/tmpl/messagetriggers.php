<?php

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;

require_once(JPATH_ROOT . '/components/com_emundus/helpers/access.php');
require_once(JPATH_ROOT . '/components/com_emundus/helpers/cache.php');

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

Text::script('COM_EMUNDUS_ONBOARD_TRIGGERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGERS_INTRO');
Text::script('COM_EMUNDUS_ONBOARD_ADD_TRIGGER');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_STEP');

Text::script('COM_EMUNDUS_TRIGGER_EDIT');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_WHEN_TO_SEND');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_STATUS');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_PROGRAMMES');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_MODELS_SELECTION');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_MODELS_EMAIL_SELECTION');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_MODELS_SMS_SELECTION');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_APPLICANT');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_ON_APPLICANT_ACTION');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_ON_MANAGER_ACTION');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_MANAGERS');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_MANAGERS_INTRO');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_ROLE');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_GROUPS');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_EDIT_SEND_TO_USERS');
Text::script('COM_EMUNDUS_ONBOARD_TRIGGER_EDIT_SEND_TO_USERS_PLACEHOLDER');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_ALL_PROGRAM');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_SAVE_SUCCESS');
Text::script('COM_EMUNDUS_TRIGGER_EDIT_SAVE');
Text::script('COM_EMUNDUS_TRIGGER_ADD');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');

Text::script('COM_EMUNDUS_ONBOARD_NOTRIGGERS');

$user               = $app->getIdentity();
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($user->id);

$hash = EmundusHelperCache::getCurrentGitHash();
?>

<div id="em-component-vue"
     component="MessageTriggers"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
     manyLanguages="<?= $many_languages ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $hash ?>"></script>