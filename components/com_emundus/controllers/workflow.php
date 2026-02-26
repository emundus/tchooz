<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
jimport('joomla.user.helper');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\User\EmundusUserRepository;
use Tchooz\Repositories\Workflow\WorkflowRepository;
use Tchooz\Traits\TraitResponse;
use Tchooz\Entities\Workflow\WorkflowEntity;
use Tchooz\Controller\EmundusController;

require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';

class EmundusControllerWorkflow extends EmundusController
{
	private $model = null;

	public function __construct($config = array())
	{
		parent::__construct($config);

		require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
		$this->model = new EmundusModelWorkflow();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::READ]])]
	public function getworkflows(): EmundusResponse
	{
		$ids         = $this->app->input->getString('ids', '[]');
		$ids         = json_decode($ids, true);
		$lim         = $this->app->input->getInt('lim', 0);
		$page        = $this->app->input->getInt('page', 0);
		$search      = $this->app->input->getString('recherche', '');
		$program_ids = $this->app->input->getString('program', '');
		$program_ids = !empty($program_ids) ? explode(',', $program_ids) : [];
		$sort        = $this->app->input->getString('sort', 'DESC');
		$order_by    = $this->app->input->getString('order_by', 'esw.id');
		$order_by    = $order_by == 'label' ? 'esw.label' : $order_by;

		$emundusUserRepository = new EmundusUserRepository();

		$userPrograms  = $emundusUserRepository->getUserProgramsIds($this->user->id);
		if(!empty($program_ids))
		{
			$userPrograms = array_intersect($userPrograms, $program_ids);
		}
		$userPrograms = array_values($userPrograms);

		$workflows = $this->model->getWorkflows($ids, $lim, $page, $userPrograms, $order_by, $sort, $search, empty($program_ids));

		if (!empty($workflows))
		{
			$actionRepository = new ActionRepository();
			$programAction = $actionRepository->getByName('program');
			$programReadAccess = EmundusHelperAccess::asAccessAction($programAction->getId(), CrudEnum::READ->value, $this->user->id);
			$programEditAccess = EmundusHelperAccess::asAccessAction($programAction->getId(), CrudEnum::UPDATE->value, $this->user->id);

			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();

			foreach ($workflows as $key => $workflow)
			{
				$workflow->label = ['fr' => $workflow->label, 'en' => $workflow->label];

				if($programReadAccess)
				{
					$associated_programmes_html = '<span class="label label-red-2">' . Text::_('COM_EMUNDUS_WORKFLOW_ZERO_ASSOCIATED_PROGRAMS') . '</span>';
					if (!empty($workflow->programme_ids))
					{
						$query->clear()
							->select('id, label')
							->from('#__emundus_setup_programmes')
							->where('id IN (' . implode(',', $workflow->programme_ids) . ')');

						$db->setQuery($query);
						$associated_programmes = $db->loadObjectList();

						if (!empty($associated_programmes))
						{
							$associated_programmes_html      = '';
							$associated_programmes_html_long = '';
							if (count($associated_programmes) < 2)
							{
								$associated_programmes_html .= $programEditAccess ? '<a class="tw-flex tw-flex-row tw-underline em-main-500-color tw-transition-all" href="' . EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=programme&layout=edit&id=' . $associated_programmes[0]->id) . '" target="_blank">' . $associated_programmes[0]->label . '</a>' : $associated_programmes[0]->label;
							}
							else
							{
								$associated_programmes_html_long = '<div>';
								$associated_programmes_html_long .= '<h2 class="tw-mb-2">' . Text::_('COM_EMUNDUS_WORKFLOW_PROGRAMS_ASSOCIATED_TITLE') . '</h2>';
								$associated_programmes_html_long .= '<div class="tw-flex tw-flex-col tw-flex-wrap">';
								foreach ($associated_programmes as $program)
								{
									$associated_programmes_html_long .= $programEditAccess ? '<a class="tw-flex tw-flex-row tw-underline em-main-500-color tw-transition-all" href="' . EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=programme&layout=edit&id=' . $program->id) . '" target="_blank">' . $program->label . '</a>' : $program->label;
								}
								$associated_programmes_html_long .= '</div></div>';

								$associated_programmes_html = '<div>';
								$associated_programmes_html .= '<span class="tw-cursor-pointer tw-font-semibold tw-text-profile-full tw-flex tw-items-center tw-text-sm hover:!tw-underline">' . count($associated_programmes) . ' ' . Text::_('COM_EMUNDUS_WORKFLOW_ASSOCIATED_PROGRAMS') . '</span>';
								$associated_programmes_html .= '</div>';

							}
						}
					}

					$additional_col = [
						'key'     => Text::_('COM_EMUNDUS_WORKFLOW_ASSOCIATED_PROGRAMS'),
						'value'   => $associated_programmes_html,
						'classes' => '',
						'display' => 'all'
					];

					if (!empty($associated_programmes_html_long))
					{
						$additional_col['long_value'] = $associated_programmes_html_long;
					}

					$workflow->additional_columns = [$additional_col];
				}

				$workflows[$key]              = $workflow;
			}
		}

		return EmundusResponse::ok(['datas' => array_values($workflows), 'count' => $this->model->countWorkflows($ids)]);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::READ]])]
	public function getworkflow(): EmundusResponse
	{
		$id = $this->app->input->getInt('id', 0);
		if (empty($id))
		{
			throw new \InvalidArgumentException('Workflow ID is required');
		}

		$workflow = $this->model->getWorkflow($id, [], true);

		return EmundusResponse::ok($workflow);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::UPDATE]])]
	public function updateworkflow(): EmundusResponse
	{
		$workflow = $this->app->input->getString('workflow');
		$workflow = json_decode($workflow, true);

		$steps = $this->app->input->getString('steps');
		$steps = json_decode($steps, true);

		$programs = $this->app->input->getString('programs');
		$programs = json_decode($programs, true);

		if (empty($workflow['id']))
		{
			throw new \InvalidArgumentException('Workflow ID is required');
		}

		if (!EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			if (!empty($programs)) {
				$programsIds = array_map(function ($program) {
					return $program['id'];
				}, $programs);

				if (!class_exists('EmundusModelProgramme')) {
					require_once(JPATH_ROOT . '/components/com_emundus/models/programme.php');
				}
				$m_programs = new EmundusModelProgramme();
				$userPrograms = $m_programs->getUserProgramIds($this->user->id);
				$programsIds = array_intersect($programsIds, $userPrograms);

				foreach ($programsIds as $program)
				{
					if (!in_array($program, $userPrograms)) {
						throw new AccessException(Text::_('ACCESS_DENIED'));
					}
				}

				// get already associated programs
				$existingPrograms = $this->model->getWorkflowPrograms($workflow['id']);
				foreach ($existingPrograms as $existingProgram)
				{
					if (!in_array($existingProgram, $userPrograms)) {
						throw new AccessException(Text::_('ACCESS_DENIED'));
					}
				}
			}
		}


		if (!$this->model->updateWorkflow($workflow, $steps, $programs))
		{
			throw new RuntimeException(Text::_('ERROR_WHILE_UPDATING_WORKFLOW'));
		}

		return EmundusResponse::ok();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::UPDATE]])]
	public function deleteworkflowstep(): EmundusResponse
	{
		$step_id = $this->app->input->getInt('step_id', 0);
		if (empty($step_id))
		{
			throw new \InvalidArgumentException('Step ID is required');
		}

		$can_delete = $this->model->canDeleteWorkflowStep($step_id);
		if (!$can_delete)
		{
			throw new RuntimeException(Text::_('COM_EMUNDUS_WORKFLOW_STEP_CANNOT_BE_DELETED'));
		}

		if (!$this->model->deleteWorkflowStep($step_id))
		{
			throw new RuntimeException(Text::_('ERROR_WHILE_DELETING_WORKFLOW_STEP'));
		}

		return EmundusResponse::ok();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::DELETE]])]
	public function delete(): EmundusResponse
	{

		$id = $this->app->input->getInt('id', 0);
		if (empty($id))
		{
			$ids = $this->app->input->getString('ids');
			$id  = explode(',', $ids);
		}

		if (empty($id))
		{
			throw new \InvalidArgumentException('Workflow ID(s) is required');
		}


		if (!$this->model->delete($id, $this->user->id))
		{
			throw new RuntimeException(Text::_('ERROR_WHILE_DELETING_WORKFLOW_STEP'));
		}

		return EmundusResponse::ok();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::READ]])]
	public function getsteptypes(): EmundusResponse
	{
		$types = $this->model->getStepTypes();

		return EmundusResponse::ok($types);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::UPDATE]])]
	public function saveStepTypes(): EmundusResponse
	{
		$types = $this->app->input->getString('types');
		$types = json_decode($types, true);
		if (empty($types))
		{
			throw new \InvalidArgumentException(Text::_('INVALID_STEP_TYPES'));
		}

		if (!$this->model->saveStepTypes($types))
		{
			throw new RuntimeException(Text::_('ERROR_WHILE_SAVING_STEP_TYPES'));
		}

		return EmundusResponse::ok();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::READ]])]
	public function getcampaignsteps(): EmundusResponse
	{
		$campaign_id = $this->app->input->getInt('campaign_id', 0);
		if (empty($campaign_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$steps = $this->model->getCampaignSteps($campaign_id);

		return EmundusResponse::ok($steps);
	}

	public function getstepsfromfnum(): void
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
		$fnum     = $this->app->input->getString('fnum', '');

		if (!empty($fnum) && EmundusHelperAccess::asPartnerAccessLevel($this->user->id) && EmundusHelperAccess::asAccessAction(1, 'r', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum))
		{
			$steps       = [];
			$files_infos = $this->model->getCampaignInfosFromFileIdentifier($fnum);
			$isApplicant = EmundusHelperAccess::isFnumMine($this->user->id, $fnum) && !EmundusHelperAccess::asPartnerAccessLevel($this->user->id);

			try
			{
				$workflowRepository = new WorkflowRepository();
				$workflow           = $workflowRepository->getWorkflowByFnum($fnum);

				if (!empty($workflow))
				{
					$steps = $workflow->getApplicantSteps();
					if(!empty($workflow->getParentWorkflow()))
					{
						$parentSteps = $workflow->getParentWorkflow()->getApplicantSteps();
						$steps = array_merge($steps, $parentSteps);
					}

					$initialStep = array_filter($steps, function ($step) {
						assert($step instanceof StepEntity);

						return in_array(0, $step->getEntryStatus()) && $step->getOutputStatus() === 1;
					});
				}

				if (empty($initialStep))
				{
					$campaignRepository = new CampaignRepository();
					$campaignStep       = $campaignRepository->getCampaignDefaultStep($files_infos['campaign_id']);

					if (!empty($campaignStep))
					{
						if (!empty($workflow))
						{
							$campaignStep->setWorkflowId($workflow->getId());
						}

						// insert the step at the beginning of the steps array
						array_unshift($steps, $campaignStep);
					}
					else
					{
						$initialStep = $steps[0];
					}
				}

				foreach ($steps as $step)
				{
					assert($step instanceof StepEntity);
					if ($isApplicant && $step->getType()->getId() != 1)
					{
						// display only form steps for applicants
						continue;
					}

					$dates                    = $this->model->calculateStartAndEndDates($step, $fnum, $files_infos['campaign_id']);
					$serialized_step          = $step->serialize();
					$serialized_step['dates'] = $dates;

					// format dates
					if (!empty($serialized_step['dates']['start_date']))
					{
						$serialized_step['dates']['start_date_raw'] = $serialized_step['dates']['start_date'];
						$serialized_step['dates']['start_date']     = EmundusHelperDate::displayDate($serialized_step['dates']['start_date'], 'j F Y');
					}
					if (!empty($serialized_step['dates']['end_date']))
					{
						$serialized_step['dates']['end_date_raw'] = $serialized_step['dates']['end_date'];
						$serialized_step['dates']['end_date']     = EmundusHelperDate::displayDate($serialized_step['dates']['end_date'], 'j F Y');
					}

					if (!class_exists('EmundusModelApplication'))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
					}
					$m_application                           = new EmundusModelApplication();
					$serialized_step['forms_progress']       = $m_application->getFormsProgressWithProfile($fnum, $step->getProfileId());
					$serialized_step['attachments_progress'] = $m_application->getAttachmentsProgressWithProfile($fnum, $step->getProfileId());
					$serialized_step['completed']            = $serialized_step['forms_progress'] >= 100 && $serialized_step['attachments_progress'] >= 100;

					if ($isApplicant && $serialized_step['completed'] === false && (!in_array($files_infos['status'], $step->getEntryStatus()) || (in_array($files_infos['status'], $step->getEntryStatus()) && $serialized_step['dates']['start_date_raw'] > date('Y-m-d H:i:s'))))
					{
						// if applicant, skip non completed steps that are not yet accessible
						continue;
					}

					$serialized_steps[] = $serialized_step;
				}

				usort($serialized_steps, function ($a, $b) {
					return $a['ordering'] <=> $b['ordering'];
				});
				$response['data'] = $serialized_steps;
				$response['code'] = 200;
			}
			catch (Exception $e)
			{
				$response['code']    = 500;
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
		['id' => 'campaign', 'mode' => CrudEnum::CREATE],
		['id' => 'campaign', 'mode' => CrudEnum::UPDATE]
	])]
	public function savecampaignstepsdates(): EmundusResponse
	{
		$campaign_id = $this->app->input->getInt('campaign_id', 0);
		$steps       = $this->app->input->getString('steps', '[]');
		$steps       = json_decode($steps, true);
		if (empty($campaign_id) || empty($steps))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->model->saveCampaignStepsDates($campaign_id, $steps))
		{
			throw new RuntimeException(Text::_('ERROR_WHILE_SAVING_CAMPAIGN_STEPS_DATES'));
		}

		return EmundusResponse::ok();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::READ]])]
	public function getworkflowsbyprogramid(): EmundusResponse
	{
		$program_id = $this->app->input->getInt('program_id', 0);
		if (empty($program_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$emundusUserRepository = new EmundusUserRepository();

		$userPrograms  = $emundusUserRepository->getUserProgramsIds($this->user->id);
		if(empty($userPrograms) || !in_array($program_id, $userPrograms))
		{
			throw new AccessException(Text::_('ACCESS_DENIED'));
		}

		$workflows = $this->model->getWorkflows([], 0, 0, [$program_id]);

		return EmundusResponse::ok($workflows);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::UPDATE]])]
	public function updateprogramworkflows(): EmundusResponse
	{
		$program_id = $this->app->input->getInt('program_id', 0);
		$workflows  = $this->app->input->getString('workflows', '');
		$workflows  = json_decode($workflows, true);
		if (empty($program_id) || empty($workflows))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		if (!$this->model->updateProgramWorkflows($program_id, $workflows))
		{
			throw new RuntimeException(Text::_('ERROR_WHILE_UPDATING_PROGRAM_WORKFLOWS'));
		}

		return EmundusResponse::ok();
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::UPDATE]])]
	public function updatestepstate(): EmundusResponse
	{
		$step_id = $this->app->input->getInt('step_id', 0);
		$state   = $this->app->input->getInt('state', 0);
		if (empty($step_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		try
		{
			if (!$this->model->updateStepState($step_id, $state))
			{
				throw new RuntimeException(Text::_('ERROR_WHILE_UPDATING_WORKFLOW_STEP_STATE'));
			}

			return EmundusResponse::ok();
		}
		catch (Exception $e)
		{
			throw new RuntimeException($e->getMessage());
		}
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::READ]])]
	public function getprogramsworkflows(): EmundusResponse
	{
		$workflows = $this->model->getProgramsWorkflows();

		return EmundusResponse::ok($workflows);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::CREATE]])]
	public function duplicate(): EmundusResponse
	{
		$workflow_id = $this->app->input->getInt('id', 0);
		if (empty($workflow_id))
		{
			throw new \InvalidArgumentException(Text::_('MISSING_PARAMS'));
		}

		$workflowRepository  = new WorkflowRepository();
		$workflowToDuplicate = $workflowRepository->getWorkflowById($workflow_id);
		if(empty($workflowToDuplicate))
		{
			throw new \InvalidArgumentException(Text::_('WORKFLOW_NOT_FOUND'));
		}

		$new_workflow_id = $workflowRepository->duplicate($workflowToDuplicate);
		if(empty($new_workflow_id))
		{
			throw new RuntimeException(Text::_('ERROR_WHILE_DUPLICATING_WORKFLOW'));
		}

		return EmundusResponse::ok($new_workflow_id);
	}
}
