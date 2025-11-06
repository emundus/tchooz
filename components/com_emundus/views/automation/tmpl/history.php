<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;

Text::script('COM_EMUNDUS_AUTOMATION_HISTORY');
Text::script('COM_EMUNDUS_AUTOMATION_HISTORY_INTRO');
Text::script('COM_EMUNDUS_AUTOMATION_BACK_TO_LIST');
Text::script('COM_EMUNDUS_AUTOMATION_DETAILS');
Text::script('COM_EMUNDUS_AUTOMATION_PROCESS_NB_FILES_CONTEXT');
Text::script('COM_EMUNDUS_AUTOMATION_PROCESS_NB_FAILED_ACTIONS');
Text::script('COM_EMUNDUS_AUTOMATION_PROCESS_NB_SUCCESSFULL_ACTIONS');
Text::script('COM_EMUNDUS_AUTOMATION_PROCESS_NB_FILES_PROCESSED');
Text::script('COM_EMUNDUS_AUTOMATION_SUCCESSFUL_ACTIONS');
Text::script('COM_EMUNDUS_AUTOMATION');
Text::script('COM_EMUNDUS_AUTOMATION_PROCESS_ID');
Text::script('COM_EMUNDUS_AUTOMATION_FILES');
Text::script('COM_EMUNDUS_AUTOMATION_FAILED_ACTIONS');
Text::script('COM_EMUNDUS_AUTOMATION_ACTION');
Text::script('COM_EMUNDUS_AUTOMATION_TARGET');
Text::script('COM_EMUNDUS_LIST_CLOSE_PREVIEW');
Text::script('COM_EMUNDUS_ONBOARD_VISUALIZE');


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

<div id="em-component-vue"
     component="Automation/AutomationHistoryList"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
     defaultLang="<?= $default_lang ?>"
     manyLanguages="<?= $many_languages ?>"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
