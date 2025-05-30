<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;

Text::script('COM_EMUNDUS_GLOBAL_PARAMS_MENUS_WORKFLOWS');
Text::script('COM_EMUNDUS_GLOBAL_PARAMS_WORKFLOWS');
Text::script('COM_EMUNDUS_ONBOARD_ADD_WORKFLOW');
Text::script('COM_EMUNDUS_ONBOARD_RESULTS');
Text::script('COM_EMUNDUS_ONBOARD_SEARCH');
Text::script('COM_EMUNDUS_ONBOARD_LABEL');
Text::script('COM_EMUNDUS_ONBOARD_WORKFLOWS');
Text::script('COM_EMUNDUS_ONBOARD_MODIFY');
Text::script('COM_EMUNDUS_ONBOARD_ACTIONS');
Text::script('COM_EMUNDUS_ACTIONS_DELETE');
Text::script('COM_EMUNDUS_ACTIONS_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ALL_PROGRAMS');
Text::script('COM_EMUNDUS_WORKFLOW_DELETE_WORKFLOW_CONFIRMATION');
Text::script('COM_EMUNDUS_WORKFLOW_PROGRAMS_ASSOCIATED_TITLE');
Text::script('COM_EMUNDUS_WORKFLOW_PROGRAMS_ASSOCIATED');
Text::script('COM_EMUNDUS_ONBOARD_NOWORKFLOW');
Text::script('COM_EMUNDUS_PAGINATION_DISPLAY');
Text::script('COM_EMUNDUS_ONBOARD_WORKFLOWS_FILTER_PROGRAM');

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

$program_id_filter = $app->input->getInt('program_id', 0);

if (!empty($program_id_filter))
{
    ?>
    <script>
        sessionStorage.setItem('tchooz_filter_workflow_program/' + document.location.hostname, <?= $program_id_filter ?>);
    </script>
    <?php
}

?>

<div id="em-component-vue" component="Workflows"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
     defaultLang="<?= $default_lang ?>"
     manyLanguages="<?= $many_languages ?>"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
