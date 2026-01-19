<?php

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

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

$data = LayoutFactory::prepareVueData();

?>

<div id="em-component-vue"
     component="Automation/AutomationHistoryList"
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
     defaultLang="<?= $data['default_lang'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
