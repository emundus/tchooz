<?php

defined('_JEXEC') or die('Restricted access');

use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;

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

$data = LayoutFactory::prepareVueData();
?>

<div id="em-component-vue"
     component="MessageTriggers"
     coordinatorAccess="<?= $data['coordinator_access'] ?>"
     sysadminAccess="<?= $data['sysadmin_access'] ?>"
     shortLang="<?= $data['short_lang'] ?>"
     currentLanguage="<?= $data['current_lang'] ?>"
     manyLanguages="<?= $data['many_languages'] ?>">
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>