<?php

defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Actions\ActionRepository;

Text::script('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICES');
Text::script('COM_EMUNDUS_ONBOARD_NOAPPLICATIONCHOICES');
Text::script('COM_EMUNDUS_ONBOARD_LABEL_APPLICATIONCHOICES');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_STATUS');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_TITLE');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_SELECT_STATUS');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_PLEASE_SELECT_A_STATUS');
Text::script('COM_EMUNDUS_ACTIONS_CANCEL');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO_LABEL');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO_1');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO_2');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO_3');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_FILE_STATUS_FILTER');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_STATUS_FILTER');
Text::script('COM_EMUNDUS_FILTER_PRESAVED_FILTERS');
Text::script('COM_EMUNDUS_FILTER_PRESAVED_FILTERS_PLEASE_SELECT');
Text::script('COM_EMUNDUS_FILTER_PRESAVED_FILTERS_SAVE_CURRENT_FILTER');
Text::script('COM_EMUNDUS_FILTER_PRESAVED_FILTERS_DELETE');
Text::script('COM_EMUNDUS_FILTER_PRESAVED_FILTERS_DELETE_CONFIRM');
Text::script('COM_EMUNDUS_FILTER_PRESAVED_FILTERS_DELETED_SUCCESSFULLY');
Text::script('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_YES');

Text::script('COM_EMUNDUS_APPLICATION_CHOICES_FILTER_CAMPAIGNS');
Text::script('COM_EMUNDUS_APPLICATION_CHOICES_ALL_CAMPAIGNS');

$data = LayoutFactory::prepareVueData();

$user = Factory::getApplication()->getIdentity();

$actionRepository = new ActionRepository();
$applicationChoicesAction   = $actionRepository->getByName('application_choices');
$data['crud'] = [
    'application_choices' => [
        'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($applicationChoicesAction->getId(), 'c', $user->id),
        'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($applicationChoicesAction->getId(), 'u', $user->id),
        'd' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($applicationChoicesAction->getId(), 'd', $user->id),
    ]
];
?>

<div id="em-component-vue"
     component="Application/ApplicationChoicesList"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $data['hash'] ?>"></script>
