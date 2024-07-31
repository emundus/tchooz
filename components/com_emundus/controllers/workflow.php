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
}
