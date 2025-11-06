<?php
use Joomla\CMS\Language\Text;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\EventEntity;

Text::script('COM_EMUNDUS_ONBOARD_AUTOMATIONS');
Text::script('COM_EMUNDUS_ONBOARD_NO_AUTOMATIONS');
Text::script('COM_EMUNDUS_AUTOMATION_ADD');
Text::script('COM_EMUNDUS_AUTOMATIONS');
Text::script('COM_EMUNDUS_AUTOMATION');
Text::script('COM_EMUNDUS_ONBOARD_AUTOMATIONS_INTRO');
Text::script('COM_EMUNDUS_APPLICATION_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_ALL');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_PUBLISH');
Text::script('COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH');
Text::script('COM_EMUNDUS_AUTOMATION_HISTORY');
Text::script('COM_EMUNDUS_AUTOMATION_LIST_FILTER_EVENT');
Text::script('COM_EMUNDUS_AUTOMATION_LIST_FILTER_ACTION');
Text::script('COM_EMUNDUS_AUTOMATION_DELETE_CONFIRM');

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

$datas = [
	'events' => array_map(function (EventEntity $event) {
		return $event->serialize();
	}, $this->events),
    'actions' => array_map(function (ActionEntity $action) {
        return $action->serialize();
    }, array_values($this->actions)),
    'shortLang' => $short_lang,
    'currentLanguage' => $current_lang,
    'defaultLang' => $default_lang,
    'manyLanguages' => $many_languages,
    'coordinatorAccess' => $coordinator_access,
    'sysadminAccess' => $sysadmin_access,
];
?>

<div id="em-component-vue" component="Automation/AutomationList"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
