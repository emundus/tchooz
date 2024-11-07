<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Language\LanguageHelper;

Text::script('SAVE');
Text::script('BACK');
Text::script('CANCEL');
Text::script('COM_EMUNDUS_ACTIONS_DELETE');
Text::script('COM_EMUNDUS_ACTIONS_ARCHIVE');
Text::script('COM_EMUNDUS_ACTIONS_UNARCHIVE');
Text::script('COM_EMUNDUS_ACTIONS_DUPLICATE');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_MENUS_WORKFLOWS');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_WORKFLOWS');
Text::script('COM_EMUNDUS_WORKFLOW_NEW');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_LABEL');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_TYPE');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_START_DATE');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_END_DATE');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_ROLES');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_GROUPS');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_PROFILE');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS_SELECT');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS_SELECT');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_IS_MULTIPLE');
Text::script('COM_EMUNDUS_WORKFLOW_ADD_STEP');
Text::script('COM_EMUNDUS_WORKFLOW_NEW_STEP_LABEL');
Text::script('COM_EMUNDUS_WORKFLOW_ASSOCIATED_PROGRAMS');
Text::script('COM_EMUNDUS_WORKFLOW_PROGRAM_ASSOCIATED_TO_ANOTHER_WORKFLOW');
Text::script('COM_EMUNDUS_WORKFLOW_PROGRAM_ASSOCIATED_TO_ANOTHER_WORKFLOW_TEXT');
Text::script('COM_EMUNDUS_WORKFLOW_CONFIRM_CHANGE_PROGRAM_ASSOCIATION');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_TYPE_APPLICANT');
Text::script('COM_EMUNDUS_WORKFLOW_STEP_TYPE_EVALUATOR');
Text::script('COM_EMUNDUS_WORKFLOW_NO_STEPS');
Text::script('COM_EMUNDUS_WORKFLOW_SAVE_FAILED');
Text::script('COM_EMUNDUS_WORKFLOW_SAVE_SUCCESS');
Text::script('COM_EMUNDUS_WORKFLOW_DELETE_STEP_CONFIRMATION');
Text::script('COM_EMUNDUS_WORKFLOW_PROGRAMS');
Text::script('COM_EMUNDUS_WORKFLOW_PROGRAMS_DESC');
Text::script('COM_EMUNDUS_WORKFLOW_SEARCH_PROGRAMS_PLACEHOLDER');
Text::script('COM_EMUNDUS_WORKFLOW_STEPS');
Text::script('COM_EMUNDUS_WORKFLOW_STEPS_DESC');
Text::script('COM_EMUNDUS_WORKFLOW_EDIT_RIGHTS');

$app          = Factory::getApplication();
$lang         = $app->getLanguage();
$short_lang   = substr($lang->getTag(), 0, 2);
$current_lang = $lang->getTag();
$languages    = LanguageHelper::getLanguages();
if (count($languages) > 1)
{
	$many_languages = '1';
	require_once JPATH_SITE . '/components/com_emundus/models/translations.php';
	$m_translations = new EmundusModelTranslations();
	$default_lang   = $m_translations->getDefaultLanguage()->lang_code;
}
else
{
	$many_languages = '0';
	$default_lang   = $current_lang;
}
$coordinator_access = EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id);
$sysadmin_access    = EmundusHelperAccess::isAdministrator($this->user->id);

?>

<div id="em-component-vue" component="WorkflowEdit"
     workflowid="<?= $this->current_workflow_id; ?>"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
     defaultLang="<?= $default_lang ?>"
     manyLanguages="<?= $many_languages ?>"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
