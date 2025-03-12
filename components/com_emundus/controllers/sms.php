<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;

class EmundusControllerSMS extends JControllerLegacy
{
	private $user = null;
	protected $app = null;
	private ?EmundusModelSMS $model = null;

	private int $sms_action_id = 0;

	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->app  = Factory::getApplication();
		$this->user = $this->app->getIdentity();
		$this->user->id = (int)$this->user->id;

		require_once(JPATH_ROOT . '/components/com_emundus/models/sms.php');
		$this->model = new EmundusModelSMS();

		$this->sms_action_id = $this->model->getSmsActionId();
	}

	private function sendJsonResponse($response): void
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

	public function getSmsTemplate()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response['code'] = 500;
			$response['message'] = Text::_('SMS_TEMPLATE_NOT_FOUND');
			$template_id = $this->app->input->getInt('id', 0);

			if (!empty($template_id)) {
				$template = $this->model->getSmsTemplate($template_id);

				$response = ['code' => 200, 'message' => Text::_('SMS_TEMPLATE'), 'status' => true, 'data' => $template];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getSMSTemplates()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$category = $this->app->input->getInt('category', 0);
			$search = $this->app->input->getString('recherche', '');
			$order_by = $this->app->input->getString('order_by', '');
			$order = $this->app->input->getString('sort', 'ASC');

			$response = ['code' => 200, 'message' => Text::_('SMS_TEMPLATES'), 'status' => true];
			$templates = $this->model->getSMSTemplates($search, $category, $order_by, $order);


			foreach ($templates as $key => $template) {
				if (!empty($template['category'])) {
					$template['additional_columns'] = [
						[
							'key'     => Text::_('COM_EMUNDUS_ONBOARD_CATEGORY'),
							'value'   => $template['category'],
							'classes' => 'em-p-5-12 em-font-weight-600 em-bg-neutral-200 em-text-neutral-900 em-font-size-14 em-border-radius',
							'display' => 'all'
						],
					];
				}
				else {
					$template['additional_columns'] = [['key' => Text::_('COM_EMUNDUS_ONBOARD_CATEGORY'), 'value' => '', 'classes' => '', 'display' => 'all']];
				}
				$templates[$key] = $template;
			}

			$response['data'] = [
				'count' => count($templates),
				'datas' => $templates
			];
		}

		$this->sendJsonResponse($response);
	}

	public function updateTemplate()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$id = $this->app->input->getInt('id', 0);
			$label = $this->app->input->getString('label', '');
			$label = strip_tags($label);

			$message = $this->app->input->getString('message', '');
			$message = strip_tags($message);
			$category_id = $this->app->input->getInt('category_id', 0);

			if (!empty($id) && !empty($label) && !empty($message)) {
				$tags = [
					'success_tag' => $this->app->input->getInt('success_tag', 0),
					'failure_tag' => $this->app->input->getInt('failure_tag', 0)
				];


				$updated = $this->model->updateTemplate($id, $label, $message, $this->user->id, $category_id, $tags);

				if ($updated) {
					$response = ['code' => 200, 'message' => Text::_('SMS_TEMPLATE_UPDATED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('SMS_TEMPLATE_NOT_UPDATED'), 'status' => false];
				}
			} else {
				$response = ['code' => 500, 'message' => Text::_('SMS_TEMPLATE_NOT_UPDATED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function deleteTemplate()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$id = $this->app->input->getInt('id', 0);
			$ids = $this->app->input->getString('ids', '');

			if (!empty($id)) {
				$deleted = $this->model->deleteTemplate($id, $this->user->id);

				if ($deleted) {
					$response = ['code' => 200, 'message' => Text::_('SMS_TEMPLATE_DELETED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('SMS_TEMPLATE_NOT_DELETED'), 'status' => false];
				}
			} else if (!empty($ids)) {
				$template_ids = explode(',', $ids);
				$deleted = $this->model->deleteTemplates($template_ids, $this->user->id);

				if ($deleted) {
					$response = ['code' => 200, 'message' => Text::_('SMS_TEMPLATES_DELETED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('SMS_TEMPLATES_NOT_DELETED'), 'status' => false];
				}
			} else {
				$response = ['code' => 500, 'message' => Text::_('SMS_TEMPLATE_NOT_DELETED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function sendSMS()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asAccessAction($this->sms_action_id, 'c', $this->user->id))
		{
			$message = $this->app->input->getString('message', '');
			$fnums = $this->app->input->getString('fnums', '');
			$template_id = $this->app->input->getInt('template_id', 0);
			$fnums = explode(',', $fnums);
			$valid_fnums = [];

			foreach ($fnums as $fnum) {
				if (EmundusHelperAccess::asAccessAction($this->sms_action_id, 'r', $this->user->id, $fnum)) {
					$valid_fnums[] = $fnum;
				}
			}

			if (!empty($valid_fnums)) {
				$receivers = $this->model->createReceiversFromFnums($valid_fnums);
				if (!empty($message) && !empty($receivers)) {
					$stored = $this->model->storeSmsToSend($message, $receivers, $template_id, $this->user->id);

					if ($stored) {
						$response = ['code' => 200, 'message' => Text::_('COM_EMUNDUS_SMS_STORED_IN_QUEUE'), 'status' => true];
					} else {
						$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_SMS_NOT_SENT'), 'status' => false];
					}
				} else {
					$response = ['code' => 500, 'message' => Text::_('COM_EMUNDUS_SMS_NOT_SENT'), 'status' => false];
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getGlobalSMSHistory()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$page = $this->app->input->getInt('page', 0);
			$limit = $this->app->input->getInt('limit', 10);
			$search = $this->app->input->getString('search', '');
			$status = $this->app->input->getString('status', '');
			$status = !empty($status) ? explode(',', $status) : [];

			$response = ['code' => 200, 'message' => Text::_('SENT_SMS'), 'status' => true];
			$response['data'] = $this->model->getGlobalSMSHistory($page, $limit, $search, $status);
		}

		$this->sendJsonResponse($response);
	}

	public function getSMSHistory()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];
		$fnum = $this->app->input->getString('fnum', '');

		if (EmundusHelperAccess::asAccessAction($this->sms_action_id, 'r', $this->user->id, $fnum))
		{
			$response = ['code' => 200, 'message' => Text::_('SENT_SMS'), 'status' => true];

			$sent_sms = $this->model->getSMSHistory($fnum);
			$response['data'] = [
				'count' => count($sent_sms),
				'datas' => $sent_sms
			];
		}

		$this->sendJsonResponse($response);
	}

	public function getSMSCategories()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$response = ['code' => 200, 'message' => Text::_('SENT_SMS'), 'status' => true];

			$categories = $this->model->getSMSCategories();

			$response['data'] = array_map(function ($category) {
				return [
					'id' => $category->id,
					'value' => $category->id,
					'label' => $category->label
				];
			}, $categories);
		}

		$this->sendJsonResponse($response);
	}

	public function updateSMSCategory()
	{
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id)) {
			$id = $this->app->input->getInt('category_id', 0);
			$label = $this->app->input->getString('label', '');
			$label = strip_tags($label);

			if (!empty($id) && !empty($label)) {
				$updated = $this->model->updateSMSCategory($id, $label, (int)$this->user->id);

				if ($updated) {
					$response = ['code' => 200, 'message' => Text::_('SMS_CATEGORY_UPDATED'), 'status' => true];
				} else {
					$response = ['code' => 500, 'message' => Text::_('SMS_CATEGORY_NOT_UPDATED'), 'status' => false];
				}
			} else {
				$response = ['code' => 500, 'message' => Text::_('SMS_CATEGORY_NOT_UPDATED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function createSMSCategory() {
		$response = ['code' => 403, 'message' => Text::_('ACCESS_DENIED'), 'status' => false];

		if (EmundusHelperAccess::asCoordinatorAccessLevel($this->user->id))
		{
			$label = $this->app->input->getString('label', '');
			$label = strip_tags($label);

			if (!empty($label)) {
				$category_id = $this->model->createSMSCategory($label, (int)$this->user->id);

				if ($category_id > 0) {
					$response = ['code' => 200, 'message' => Text::_('SMS_CATEGORY_CREATED'), 'status' => true, 'data' => $category_id];
				} else {
					$response = ['code' => 500, 'message' => Text::_('SMS_CATEGORY_NOT_CREATED'), 'status' => false];
				}
			} else {
				$response = ['code' => 500, 'message' => Text::_('SMS_CATEGORY_NOT_CREATED'), 'status' => false];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function isSMSActivated()
	{
		$response = ['code' => 403, 'message' => Text::_('SMS_NOT_ACTIVATED'), 'status' => false];

		if (EmundusHelperAccess::asPartnerAccessLevel($this->user->id) && EmundusHelperAccess::asAccessAction($this->sms_action_id, 'r', $this->user->id))
		{
			$response = ['code' => 200, 'message' => Text::_('SMS_ACTIVATED'), 'status' => true,  'data' => $this->model->activated];
		}

		$this->sendJsonResponse($response);
	}
}