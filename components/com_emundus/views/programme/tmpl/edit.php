<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted Access');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\LayoutFactory;
use Tchooz\Repositories\Actions\ActionRepository;

if (empty($this->programEntity) || !($this->programEntity instanceof ProgramEntity)) {
    return;
}

Text::script('SAVE');
Text::script('BACK');
Text::script('CANCEL');
Text::script('COM_EMUNDUS_PROGRAM_FORM_CREATE_TITLE');
Text::script('COM_EMUNDUS_PROGRAM_FORM_EDIT_TITLE');
Text::script('COM_EMUNDUS_PROGRAM_LABEL_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_CODE_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_PROGRAMMES_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_PUBLISHED_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_DESCRIPTION_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_SYNTHESIS_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_SYNTHESIS_HELP');
Text::script('COM_EMUNDUS_PROGRAM_LOGO_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_FORM_CREATE');
Text::script('COM_EMUNDUS_PROGRAM_FORM_EDIT');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_TITLE');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_SUBTITLE');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_INTRO');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_TAB_GENERAL');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_TAB_CAMPAIGNS');
Text::script('COM_EMUNDUS_PROGRAMS_EDITION_TAB_WORKFLOWS');
Text::script('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE');
Text::script('COM_EMUNDUS_ONBOARD_WORKFLOWS_ASSOCIATED_TITLE');
Text::script('COM_EMUNDUS_PROGRAMS_ACCESS_TO_WORKFLOWS');
Text::script('COM_EMUNDUS_PROGRAMS_ACCESS_TO_CAMPAIGNS');
Text::script('COM_EMUNDUS_PROGRAM_UPDATE_ASSOCIATED_WORKFLOW_SUCCESS');
Text::script('COM_EMUNDUS_PROGRAM_APPLY_ONLINE_LABEL');
Text::script('COM_EMUNDUS_PROGRAM_APPLY_ONLINE_HELP');

$data = LayoutFactory::prepareVueData();
$user = Factory::getApplication()->getIdentity();

$actionRepository = new ActionRepository();
$campaignAction   = $actionRepository->getByName('campaign');
$workflowAction    = $actionRepository->getByName('workflow');

$data['crud'] = [
    'campaign' => [
        'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($campaignAction->getId(), 'c', $user->id),
        'r' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($campaignAction->getId(), 'r', $user->id),
        'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($campaignAction->getId(), 'u', $user->id),
    ],
    'workflow'  => [
        'c' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($workflowAction->getId(), 'c', $user->id),
        'r' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($workflowAction->getId(), 'r', $user->id),
        'u' => $data['coordinator_access'] || $data['sysadmin_access'] || EmundusHelperAccess::asAccessAction($workflowAction->getId(), 'u', $user->id),
    ]
];

$data['program'] = $this->programEntity->__serialize();
$data['useOldProgramForm'] = $this->useOldProgramForm;
?>

<div id="em-component-vue" component="Program/ProgramEdit"
     data="<?= htmlspecialchars(json_encode($data), ENT_QUOTES, 'UTF-8'); ?>"
>
</div>

<script type="module" src="media/com_emundus_vue/app_emundus.js?<?php echo $this->hash . uniqid() ?>"></script>
