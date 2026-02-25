<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Actions\ActionRepository;

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

$app               = Factory::getApplication();
$program_id_filter = $app->input->getInt('program_id', 0);
if (!empty($program_id_filter))
{
    ?>
    <script>
        sessionStorage.setItem('tchooz_filter_workflow_program/' + document.location.hostname, <?= $program_id_filter ?>)
    </script>
    <?php
}

$data = LayoutFactory::prepareVueData();

$user = Factory::getApplication()->getIdentity();

$actionRepository = new ActionRepository();
$workflowAction   = $actionRepository->getByName('workflow');
$data['crud'] = [
    'workflow' => [
        'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($workflowAction->getId(), 'c', $user->id),
        'r' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($workflowAction->getId(), 'r', $user->id),
        'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($workflowAction->getId(), 'u', $user->id),
        'd' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($workflowAction->getId(), 'd', $user->id),
    ]
];

?>

<div id="em-component-vue" component="Workflows"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash ?>"></script>
