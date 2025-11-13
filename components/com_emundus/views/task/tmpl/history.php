<?php
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Language\Text;
use Tchooz\Enums\Task\TaskStatusEnum;

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

Text::script('COM_EMUNDUS_TASKS');
Text::script('COM_EMUNDUS_TASKS_INTRO');
Text::script('COM_EMUNDUS_NO_TASKS');
Text::script('COM_EMUNDUS_TASK_STATUS');
Text::script('COM_EMUNDUS_ONBOARD_FILTER_ALL');

$datas = [
    'statuses' => array_map(function (TaskStatusEnum $status) {
        return [
            'value' => $status->value,
            'label' => Text::_($status->getLabel())
        ];
    }, TaskStatusEnum::cases())
];

?>

<div id="em-component-vue"
     component="Task/TaskHistoryList"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
     shortLang="<?= $short_lang ?>"
     currentLanguage="<?= $current_lang ?>"
     defaultLang="<?= $default_lang ?>"
     manyLanguages="<?= $many_languages ?>"
     coordinatorAccess="<?= $coordinator_access ?>"
     sysadminAccess="<?= $sysadmin_access ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
