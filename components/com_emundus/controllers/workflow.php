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

			$workflows = $this->model->getWorkflows($ids);

			foreach ($workflows as $key => $workflow) {
				$workflows[$key]->label = [
					'fr' => $workflow->label,
					'en' => $workflow->label
				];
			}

			$data = [
				'datas' => array_values($workflows)
			];

			$response['data'] = $data;
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
				$updated = $this->model->updateWorkflow($workflow, $steps, $programs);

				if ($updated) {
					$response['code'] = 200;
					$response['status'] = true;
				} else {
					$response['code'] = 500;
					$response['message'] = Text::_('ERROR_WHILE_UPDATING_WORKFLOW');
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
}
