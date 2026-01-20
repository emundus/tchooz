<?php

use Joomla\CMS\Language\Text;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Factories\LayoutFactory;

$data = LayoutFactory::prepareVueData();

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
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
     defaultLang="<?= $data['default_lang'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
