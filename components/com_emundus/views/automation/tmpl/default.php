<?php

use Joomla\CMS\Language\Text;
use Tchooz\Entities\Automation\ActionEntity;
use Tchooz\Entities\Automation\EventEntity;
use Tchooz\Factories\LayoutFactory;

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

$data = LayoutFactory::prepareVueData();

$datas = [
	'events' => array_map(function (EventEntity $event) {
		return $event->serialize();
	}, $this->events),
    'actions' => array_map(function (ActionEntity $action) {
        return $action->serialize();
    }, array_values($this->actions)),
    'shortLang' => $data['short_lang'],
    'currentLanguage' => $data['current_lang'],
    'defaultLang' => $data['default_lang'],
    'manyLanguages' => $data['many_languages'],
    'coordinatorAccess' => $data['coordinator_access'],
    'sysadminAccess' => $data['sysadmin_access'],
];
?>

<div id="em-component-vue" component="Automation/AutomationList"
     data="<?= htmlspecialchars(json_encode($datas), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
