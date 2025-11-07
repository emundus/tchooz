<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
jimport('joomla.user.helper');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Workflow\StepEntity;
use Tchooz\Entities\Workflow\StepTypeEntity;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Traits\TraitResponse;
use Tchooz\Entities\Workflow\WorkflowEntity;

require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';

class EmundusControllerWorkflow extends JControllerLegacy
{
	use TraitResponse;

	private $user = null;
	protected $app = null;

	private $model = null;

	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->app  = Factory::getApplication();
		$this->user = $this->app->getIdentity();

		require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
		$this->model = new EmundusModelWorkflow();
	}

	public function getworkflows()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$ids         = $this->app->input->getString('ids', '[]');
			$ids         = json_decode($ids, true);
			$lim         = $this->app->input->getInt('lim', 0);
			$page        = $this->app->input->getInt('page', 0);
			$search        = $this->app->input->getString('recherche', '');
			$program_ids = $this->app->input->getString('program', '');
			$program_ids = !empty($program_ids) ? explode(',', $program_ids) : [];
			$sort 	     = $this->app->input->getString('sort', 'DESC');
			$order_by    = $this->app->input->getString('order_by', 'esw.id');
			$order_by    = $order_by == 'label' ? 'esw.label' : $order_by;

			$workflows = $this->model->getWorkflows($ids, $lim, $page, $program_ids, $order_by, $sort, $search);

			if (!empty($workflows))
			{
				$db    = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->createQuery();

				foreach ($workflows as $key => $workflow)
				{
					$workflow->label = ['fr' => $workflow->label, 'en' => $workflow->label];

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
							$associated_programmes_html = '';
							$associated_programmes_html_long = '';
							if (count($associated_programmes) < 2)
							{
								$associated_programmes_html .= '<a class="tw-flex tw-flex-row tw-underline em-main-500-color tw-transition-all" href="'.EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=programme&layout=edit&id='.$associated_programmes[0]->id).'" target="_blank">' . $associated_programmes[0]->label . '</a>';
							}
							else
							{
								$associated_programmes_html_long = '<div>';
								$associated_programmes_html_long       .= '<h2 class="tw-mb-2">' . Text::_('COM_EMUNDUS_WORKFLOW_PROGRAMS_ASSOCIATED_TITLE') . '</h2>';
								$associated_programmes_html_long       .= '<div class="tw-flex tw-flex-col tw-flex-wrap">';
								foreach ($associated_programmes as $program)
								{
									$associated_programmes_html_long .= '<a class="tw-flex tw-flex-row tw-underline em-main-500-color tw-transition-all" href="'.EmundusHelperMenu::routeViaLink('index.php?option=com_emundus&view=programme&layout=edit&id='.$program->id).'" target="_blank">' . $program->label . '</a>';
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

					if (!empty($associated_programmes_html_long)) {
						$additional_col['long_value'] = $associated_programmes_html_long;
					}

					$workflow->additional_columns = [$additional_col];
					$workflows[$key]              = $workflow;
				}
			}

			$response['data']   = ['datas' => array_values($workflows), 'count' => $this->model->countWorkflows($ids)];
			$response['code']   = 200;
			$response['status'] = true;
		}

		$this->sendJsonResponse($response);
	}

	public function getworkflow()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$id = $this->app->input->getInt('id', 0);

			$workflow = $this->model->getWorkflow($id, [], true);

			if ($workflow)
			{
				$response['data']   = $workflow;
				$response['code']   = 200;
				$response['status'] = true;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function updateworkflow()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$workflow = $this->app->input->getString('workflow');
			$workflow = json_decode($workflow, true);

			$steps = $this->app->input->getString('steps');
			$steps = json_decode($steps, true);

			$programs = $this->app->input->getString('programs');
			$programs = json_decode($programs, true);

			if (!empty($workflow['id']))
			{
				try
				{
					$updated = $this->model->updateWorkflow($workflow, $steps, $programs);

					if ($updated)
					{
						$response['code']   = 200;
						$response['status'] = true;
					}
					else
					{
						$response['code']    = 500;
						$response['message'] = Text::_('ERROR_WHILE_UPDATING_WORKFLOW');
					}
				}
				catch (Exception $e)
				{
					$response['code']    = 500;
					$response['message'] = $e->getMessage();
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function deleteworkflowstep()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$step_id             = $this->app->input->getInt('step_id', 0);
			$response['code']    = 500;
			$response['message'] = Text::_('ERROR_WHILE_DELETING_WORKFLOW_STEP');

			if (!empty($step_id))
			{
				$can_delete = $this->model->canDeleteWorkflowStep($step_id);

				if($can_delete)
				{
					$deleted = $this->model->deleteWorkflowStep($step_id);

					if ($deleted)
					{
						$response['code']   = 200;
						$response['status'] = true;
					}
				}
				else
				{
					$response['code']    = 500;
					$response['message'] = Text::_('COM_EMUNDUS_WORKFLOW_STEP_CANNOT_BE_DELETED');
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function delete()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$id                  = $this->app->input->getInt('id', 0);
			if(empty($id)) {
				$ids = $this->app->input->getString('ids');
				$id = explode(',', $ids);
			}

			$response['code']    = 500;
			$response['message'] = Text::_('ERROR_WHILE_DELETING_WORKFLOW_STEP');

			if (!empty($id))
			{
				$deleted = $this->model->delete($id, $this->user->id);

				if ($deleted)
				{
					$response['code']   = 200;
					$response['status'] = true;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getsteptypes()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$types = $this->model->getStepTypes();

			if (!empty($types))
			{
				$response['data']   = $types;
				$response['code']   = 200;
				$response['status'] = true;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function saveStepTypes()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$types = $this->app->input->getString('types');
			$types = json_decode($types, true);

			// verify if the types are valid
			if (empty($types))
			{
				$response['code']    = 500;
				$response['message'] = Text::_('INVALID_STEP_TYPES');
			}
			else
			{
				$saved = $this->model->saveStepTypes($types);

				if ($saved)
				{
					$response['code']   = 200;
					$response['status'] = true;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getcampaignsteps()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code']    = 500;
			$response['message'] = Text::_('MISSING_PARAMS');
			$campaign_id         = $this->app->input->getInt('campaign_id', 0);

			if (!empty($campaign_id))
			{
				$steps = $this->model->getCampaignSteps($campaign_id);

				$response['data']   = $steps;
				$response['code']   = 200;
				$response['status'] = true;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getstepsfromfnum()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];
		$fnum = $this->app->input->getString('fnum', '');

		if (!empty($fnum) && EmundusHelperAccess::asPartnerAccessLevel($this->user->id) && EmundusHelperAccess::asAccessAction(1, 'r', $this->user->id, $fnum) || EmundusHelperAccess::isFnumMine($this->user->id, $fnum))
		{
			$steps = [];
			$files_infos = $this->model->getCampaignInfosFromFileIdentifier($fnum);
			$isApplicant = EmundusHelperAccess::isFnumMine($this->user->id, $fnum) && !EmundusHelperAccess::asPartnerAccessLevel($this->user->id);

			try {
				$workflow_id = $this->model->getWorkflowIdByFnum($fnum);
				if (!empty($workflow_id)) {
					$serialized_steps = [];
					$workflow = new WorkflowEntity($workflow_id);
					$steps = $workflow->getApplicantSteps();
				}

				$initialStep = array_filter($steps, function($step) {
					assert($step instanceof StepEntity);
					return in_array(0, $step->getEntryStatus()) && $step->getOutputStatus() === 1;
				});

				if (empty($initialStep))
				{
					$campaignRepository = new CampaignRepository();
					$campaignStep = $campaignRepository->getCampaignDefaultStep($files_infos['campaign_id']);
					$campaignStep->setWorkflowId($workflow_id);

					// insert the step at the beginning of the steps array
					array_unshift($steps, $campaignStep);
				}

				foreach($steps as $step) {
					assert($step instanceof StepEntity);
					if ($isApplicant && $step->getType()->getId() != 1) {
						// display only form steps for applicants
						continue;
					}

					$dates = $this->model->calculateStartAndEndDates($step, $fnum, $files_infos['campaign_id']);
					$serialized_step = $step->serialize();
					$serialized_step['dates'] = $dates;

					// format dates
					if (!empty($serialized_step['dates']['start_date'])) {
						$serialized_step['dates']['start_date_raw'] = $serialized_step['dates']['start_date'];
						$serialized_step['dates']['start_date'] = EmundusHelperDate::displayDate($serialized_step['dates']['start_date'], 'j F Y');
					}
					if (!empty($serialized_step['dates']['end_date'])) {
						$serialized_step['dates']['end_date_raw'] = $serialized_step['dates']['end_date'];
						$serialized_step['dates']['end_date'] = EmundusHelperDate::displayDate($serialized_step['dates']['end_date'], 'j F Y');
					}

					if (!class_exists('EmundusModelApplication'))
					{
						require_once(JPATH_ROOT . '/components/com_emundus/models/application.php');
					}
					$m_application = new EmundusModelApplication();
					$serialized_step['forms_progress'] = $m_application->getFormsProgressWithProfile($fnum, $step->profile_id);
					$serialized_step['attachments_progress'] = $m_application->getAttachmentsProgressWithProfile($fnum, $step->profile_id);
					$serialized_step['completed'] = $serialized_step['forms_progress'] >= 100 && $serialized_step['attachments_progress'] >= 100;

					if ($isApplicant && $serialized_step['completed'] === false && (!in_array($files_infos['status'], $step->getEntryStatus()) || (in_array($files_infos['status'], $step->getEntryStatus()) && $serialized_step['dates']['start_date_raw'] > date('Y-m-d H:i:s')))) {
						// if applicant, skip non completed steps that are not yet accessible
						continue;
					}

					$serialized_steps[] = $serialized_step;
				}

				usort($serialized_steps, function($a, $b) {
					return $a['ordering'] <=> $b['ordering'];
				});
				$response['data']   = $serialized_steps;
				$response['code']   = 200;
			} catch (Exception $e) {
				$response['code']    = 500;
				$response['message'] = $e->getMessage();
			}
		}

		$this->sendJsonResponse($response);
	}

	public function savecampaignstepsdates()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$campaign_id = $this->app->input->getInt('campaign_id', 0);
			$steps       = $this->app->input->getString('steps', '[]');
			$steps       = json_decode($steps, true);

			if (!empty($campaign_id) && !empty($steps))
			{
				$saved = $this->model->saveCampaignStepsDates($campaign_id, $steps);

				if ($saved)
				{
					$response['code']   = 200;
					$response['status'] = true;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getworkflowsbyprogramid()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$program_id = $this->app->input->getInt('program_id', 0);

			if (!empty($program_id))
			{
				$workflows = $this->model->getWorkflows([], 0, 0, [$program_id]);

				$response = [
					'data'   => $workflows,
					'code'   => 200,
					'status' => true
				];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function updateprogramworkflows()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$program_id = $this->app->input->getInt('program_id', 0);
			$workflows  = $this->app->input->getString('workflows', '');
			$workflows  = json_decode($workflows, true);

			if (!empty($program_id))
			{
				$updated = $this->model->updateProgramWorkflows($program_id, $workflows);

				if ($updated)
				{
					$response['code']   = 200;
					$response['status'] = true;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function updatestepstate()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$step_id             = $this->app->input->getInt('step_id', 0);
			$state               = $this->app->input->getInt('state', 0);
			$response['code']    = 500;
			$response['message'] = Text::_('ERROR_WHILE_UPDATING_WORKFLOW_STEP_STATE');

			if (!empty($step_id))
			{
				try
				{
					$updated = $this->model->updateStepState($step_id, $state);

					if ($updated)
					{
						$response['code']   = 200;
						$response['status'] = true;
					}
				}
				catch (Exception $e)
				{
					$response['message'] = $e->getMessage();
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getprogramsworkflows()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{

			$workflows = $this->model->getProgramsWorkflows();

			$response = [
				'data'   => $workflows,
				'code'   => 200,
				'status' => true
			];
		}

		$this->sendJsonResponse($response);
	}

	public function duplicate()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$workflow_id = $this->app->input->getInt('id', 0);

			if (!empty($workflow_id))
			{
				$new_workflow_id = $this->model->duplicateWorkflow($workflow_id);

				if (!empty($new_workflow_id))
				{
					$response['code']   = 200;
					$response['status'] = true;
				}
			}
		}

		$this->sendJsonResponse($response);
	}
}
