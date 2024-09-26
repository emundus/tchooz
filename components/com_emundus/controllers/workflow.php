<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');
jimport('joomla.user.helper');

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

require_once JPATH_ROOT . '/components/com_emundus/helpers/files.php';

class EmundusControllerWorkflow extends JControllerLegacy
{
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

	private function sendJsonResponse($response)
	{
		if ($response['code'] === 403)
		{
			header('HTTP/1.1 403 Forbidden');
			echo $response['message'];
			exit;
		}
		else
		{
			if ($response['code'] === 500)
			{
				header('HTTP/1.1 500 Internal Server Error');
				echo $response['message'];
				exit;
			}
		}

		echo json_encode($response);
		exit;
	}

	public function getworkflows()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$ids = $this->app->input->getString('ids', '[]');
			$ids = json_decode($ids, true);
			$lim = $this->app->input->getInt('lim', 0);
			$page = $this->app->input->getInt('page', 0);
			$program_ids = $this->app->input->getString('program', '');
			$program_ids = !empty($program_ids) ? explode(',', $program_ids) : [];

			$workflows = $this->model->getWorkflows($ids, $lim, $page, $program_ids);

			if (!empty($workflows)) {
				$db = Factory::getContainer()->get('DatabaseDriver');
				$query = $db->createQuery();

				foreach ($workflows as $key => $workflow) {
					$workflow->label = ['fr' => $workflow->label, 'en' => $workflow->label];

					$associated_programmes_html = '<span class="label label-red-2">' . Text::_('COM_EMUNDUS_WORKFLOW_ZERO_ASSOCIATED_PROGRAMS') . '</span>';
					if (!empty($workflow->programme_ids)) {
						$query->clear()
							->select('id, label')
							->from('#__emundus_setup_programmes')
							->where('id IN (' . implode(',', $workflow->programme_ids) . ')');

						$db->setQuery($query);
						$associated_programmes = $db->loadObjectList();

						if (!empty($associated_programmes)) {
							$associated_programmes_html = '';
							foreach ($associated_programmes as $program) {
								$associated_programmes_html .= '<a class="tw-flex tw-flex-row tw-underline em-main-500-color tw-transition-all" href="/campaigns/edit-program?id=' . $program->id . '" target="_blank">' . $program->label . '</a>';
							}
						}
					}

					$workflow->additional_columns = [
						[
							'key'     => Text::_('COM_EMUNDUS_WORKFLOW_ASSOCIATED_PROGRAMS'),
							'value'   => $associated_programmes_html,
							'classes' => '',
							'display' => 'all'
						]
					];
					$workflows[$key] = $workflow;
				}
			}

			$response['data'] = ['datas' => array_values($workflows), 'count' => $this->model->countWorkflows($ids)];
			$response['code'] = 200;
			$response['status'] = true;
		}

		$this->sendJsonResponse($response);
	}

	public function getworkflow()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$id = $this->app->input->getInt('id', 0);

			$workflow = $this->model->getWorkflow($id);

			if ($workflow) {
				$response['data'] = $workflow;
				$response['code'] = 200;
				$response['status'] = true;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function updateworkflow()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$workflow = $this->app->input->getString('workflow');
			$workflow = json_decode($workflow, true);

			$steps = $this->app->input->getString('steps');
			$steps = json_decode($steps, true);

			$programs = $this->app->input->getString('programs');
			$programs = json_decode($programs, true);

			if (!empty($workflow['id'])) {
				try {
					$updated = $this->model->updateWorkflow($workflow, $steps, $programs);

					if ($updated) {
						$response['code'] = 200;
						$response['status'] = true;
					} else {
						$response['code'] = 500;
						$response['message'] = Text::_('ERROR_WHILE_UPDATING_WORKFLOW');
					}
				} catch (Exception $e) {
					$response['code'] = 500;
					$response['message'] = $e->getMessage();
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function deleteworkflowstep()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$step_id = $this->app->input->getInt('step_id', 0);
			$response['code'] = 500;
			$response['message'] = Text::_('ERROR_WHILE_DELETING_WORKFLOW_STEP');

			if (!empty($step_id)) {
				$deleted = $this->model->deleteWorkflowStep($step_id);

				if ($deleted) {
					$response['code'] = 200;
					$response['status'] = true;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function delete()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$id = $this->app->input->getInt('id', 0);
			$response['code'] = 500;
			$response['message'] = Text::_('ERROR_WHILE_DELETING_WORKFLOW_STEP');

			if (!empty($id)) {
				$deleted = $this->model->delete($id, $this->user->id);

				if ($deleted) {
					$response['code'] = 200;
					$response['status'] = true;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getsteptypes()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$types = $this->model->getStepTypes();

			if (!empty($types)) {
				$response['data'] = $types;
				$response['code'] = 200;
				$response['status'] = true;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function saveStepTypes()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$types = $this->app->input->getString('types');
			$types = json_decode($types, true);

			// verify if the types are valid
			if (empty($types))
			{
				$response['code']    = 500;
				$response['message'] = Text::_('INVALID_STEP_TYPES');
			} else {
				$saved = $this->model->saveStepTypes($types);

				if ($saved) {
					$response['code'] = 200;
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
			$response['code'] = 500;
			$response['message'] = Text::_('MISSING_PARAMS');
			$campaign_id = $this->app->input->getInt('campaign_id', 0);

			if (!empty($campaign_id)) {
				$steps = $this->model->getCampaignSteps($campaign_id);

				$response['data'] = $steps;
				$response['code'] = 200;
				$response['status'] = true;
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
			$steps = $this->app->input->getString('steps', '[]');
			$steps = json_decode($steps, true);

			if (!empty($campaign_id) && !empty($steps)) {
				$saved = $this->model->saveCampaignStepsDates($campaign_id, $steps);

				if ($saved) {
					$response['code'] = 200;
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

			if (!empty($program_id)) {
				$workflows = $this->model->getWorkflows([], 0, 0, [$program_id]);

				$response = [
					'data' => $workflows,
					'code' => 200,
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
			$workflows = $this->app->input->getString('workflows', '');
			$workflows = json_decode($workflows, true);

			if (!empty($program_id)) {
				$updated = $this->model->updateProgramWorkflows($program_id, $workflows);

				if ($updated) {
					$response['code'] = 200;
					$response['status'] = true;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function  updatestepstate()
	{
		$response = ['status' => false, 'code' => 403, 'message' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$step_id = $this->app->input->getInt('step_id', 0);
			$state = $this->app->input->getInt('state', 0);
			$response['code'] = 500;
			$response['message'] = Text::_('ERROR_WHILE_UPDATING_WORKFLOW_STEP_STATE');

			if (!empty($step_id)) {
				try {
					$updated = $this->model->updateStepState($step_id, $state);

					if ($updated) {
						$response['code'] = 200;
						$response['status'] = true;
					}
				} catch (Exception $e) {
					$response['message'] = $e->getMessage();
				}
			}
		}

		$this->sendJsonResponse($response);
	}
}
