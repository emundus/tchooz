<?php

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Log\Log;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Attributes\AccessAttribute;
use Tchooz\EmundusResponse;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Exception\EmundusSMSException;
use Tchooz\Controller\EmundusController;

class EmundusControllerSMS extends EmundusController
{
	private ?EmundusModelSMS $model;

	private int $sms_action_id;

	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->user->id = (int) $this->user->id;

		require_once(JPATH_ROOT . '/components/com_emundus/models/sms.php');
		$this->model         = new EmundusModelSMS();
		$this->sms_action_id = $this->model->getSmsActionId();

		if (!class_exists('Tchooz\Exception\EmundusSMSException'))
		{
			require_once(JPATH_ROOT . '/components/com_emundus/Exception/EmundusSMSException.php');
		}
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::READ]])]
	public function getSmsTemplate(): EmundusResponse
	{
		$template_id = $this->app->input->getInt('id', 0);
		if (empty($template_id))
		{
			throw new InvalidArgumentException('Template ID is required');
		}

		$template = $this->model->getSmsTemplate($template_id);
		if (empty($template))
		{
			throw new RuntimeException(Text::_('COM_EMUNDUS_ERROR_SMS_NOT_FOUND'));
		}

		return EmundusResponse::ok($template, Text::_('SMS_TEMPLATE'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::READ]])]
	public function getSMSTemplates(): EmundusResponse
	{
		$category = $this->app->input->getInt('category', 0);
		$search   = $this->app->input->getString('recherche', '');
		$order_by = $this->app->input->getString('order_by', '');
		$order    = $this->app->input->getString('sort', 'ASC');

		$templates = $this->model->getSMSTemplates($search, $category, $order_by, $order);

		foreach ($templates as $key => $template)
		{
			if (!empty($template['category']))
			{
				$template['additional_columns'] = [
					[
						'key'     => Text::_('COM_EMUNDUS_ONBOARD_CATEGORY'),
						'value'   => $template['category'],
						'classes' => 'em-p-5-12 em-font-weight-600 em-bg-neutral-200 em-text-neutral-900 em-font-size-14 em-border-radius',
						'display' => 'all'
					],
				];
			}
			else
			{
				$template['additional_columns'] = [['key' => Text::_('COM_EMUNDUS_ONBOARD_CATEGORY'), 'value' => '', 'classes' => '', 'display' => 'all']];
			}
			$templates[$key] = $template;
		}

		return EmundusResponse::ok([
			'count' => count($templates),
			'datas' => $templates
		], Text::_('SMS_TEMPLATE'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::UPDATE]])]
	public function updateTemplate(): EmundusResponse
	{
		$id    = $this->app->input->getInt('id', 0);
		$label = $this->app->input->getString('label', '');
		$label = strip_tags($label);

		$message       = $this->app->input->getString('message', '');
		$message       = strip_tags($message);
		$category_id   = $this->app->input->getInt('category_id', 0);
		$allow_unicode = $this->app->input->getInt('allow_unicode', 0);
		if (empty($id) || empty($label) || empty($message))
		{
			throw new InvalidArgumentException('ID, label and message are required');
		}

		$allow_unicode = $allow_unicode === 1;
		$tags          = [
			'success_tag' => $this->app->input->getInt('success_tag', 0),
			'failure_tag' => $this->app->input->getInt('failure_tag', 0)
		];

		try
		{
			$updated = $this->model->updateTemplate($id, $label, $message, $this->user->id, $category_id, $tags, $allow_unicode);
			if (!$updated)
			{
				throw new RuntimeException(Text::_('SMS_TEMPLATE_NOT_UPDATED'));
			}

			return EmundusResponse::ok([], Text::_('SMS_TEMPLATE_UPDATED'));
		}
		catch (EmundusSMSException $e)
		{
			Log::add('Error updating SMS template: ' . $e->getDetailedMessage(), Log::ERROR, 'emundus');

			return EmundusResponse::fail(Text::_('SMS_TEMPLATE_NOT_UPDATED'), 500, $e->getContext());
		}
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::DELETE]])]
	public function deleteTemplate(): EmundusResponse
	{
		$id  = $this->app->input->getInt('id', 0);
		$ids = $this->app->input->getString('ids', '');

		if (empty($ids) && empty($id))
		{
			throw new InvalidArgumentException('ID or IDs are required');
		}

		if (!empty($id))
		{
			$deleted = $this->model->deleteTemplate($id, $this->user->id);
			if (!$deleted)
			{
				throw new RuntimeException(Text::_('SMS_TEMPLATE_NOT_DELETED'));
			}
		}
		elseif (!empty($ids))
		{
			$template_ids = explode(',', $ids);
			$deleted      = $this->model->deleteTemplates($template_ids, $this->user->id);
			if (!$deleted)
			{
				throw new RuntimeException(Text::_('SMS_TEMPLATES_NOT_DELETED'));
			}
		}

		return EmundusResponse::ok([], Text::_('SMS_TEMPLATE_DELETED'));
	}

	#[AccessAttribute(actions: [['id' => 'sms', 'mode' => CrudEnum::CREATE]])]
	public function sendSMS(): EmundusResponse
	{

		$message = $this->app->input->getString('message', '');
		if (empty($message))
		{
			throw new InvalidArgumentException(Text::_('COM_EMUNDUS_SMS_NOT_SENT_EMPTY_MESSAGE'));
		}

		$fnums       = $this->app->input->getString('fnums', '');
		$template_id = $this->app->input->getInt('template_id', 0);
		$fnums       = explode(',', $fnums);
		$valid_fnums = [];

		foreach ($fnums as $fnum)
		{
			if (EmundusHelperAccess::asAccessAction($this->sms_action_id, CrudEnum::READ->value, $this->user->id, $fnum))
			{
				$valid_fnums[] = $fnum;
			}
		}

		if (empty($valid_fnums))
		{
			throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$receivers = $this->model->createReceiversFromFnums($valid_fnums);
		if (empty($receivers))
		{
			throw new RuntimeException(Text::_('COM_EMUNDUS_SMS_NOT_SENT_NO_VALID_RECEIVERS'));
		}

		$stored = $this->model->storeSmsToSend($message, $receivers, $template_id, $this->user->id);
		if (!$stored)
		{
			throw new RuntimeException(Text::_('COM_EMUNDUS_SMS_NOT_SENT_FAILED_TO_STORE_IT_IN_QUEUE'));
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_SMS_STORED_IN_QUEUE'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	public function getGlobalSMSHistory(): EmundusResponse
	{
		$page   = $this->app->input->getInt('page', 0);
		$limit  = $this->app->input->getInt('limit', 10);
		$search = $this->app->input->getString('search', '');
		$status = $this->app->input->getString('status', '');
		$status = !empty($status) ? explode(',', $status) : [];

		$response = $this->model->getGlobalSMSHistory($page, $limit, $search, $status);

		return EmundusResponse::ok($response);
	}

	public function getSMSHistory(): EmundusResponse
	{
		$fnum = $this->app->input->getString('fnum', '');

		if (empty($fnum))
		{
			throw new InvalidArgumentException('Fnum is required');
		}

		if (!EmundusHelperAccess::asAccessAction($this->sms_action_id, 'r', $this->user->id, $fnum))
		{
			throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}


		$sent_sms = $this->model->getSMSHistory($fnum);

		return EmundusResponse::ok([
			'count' => count($sent_sms),
			'datas' => $sent_sms
		]);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::READ]])]
	public function getSMSCategories(): EmundusResponse
	{
		$categories = $this->model->getSMSCategories();
		$data       = array_map(function ($category) {
			return [
				'id'    => $category->id,
				'value' => $category->id,
				'label' => $category->label
			];
		}, $categories);

		return EmundusResponse::ok($data);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::UPDATE]])]
	public function updateSMSCategory(): EmundusResponse
	{
		$id    = $this->app->input->getInt('category_id', 0);
		$label = $this->app->input->getString('label', '');
		$label = strip_tags($label);
		if (empty($id) || empty($label))
		{
			throw new InvalidArgumentException('Category ID and label are required');
		}

		$updated = $this->model->updateSMSCategory($id, $label, (int) $this->user->id);
		if (!$updated)
		{
			throw new RuntimeException(Text::_('SMS_CATEGORY_NOT_UPDATED'));
		}

		return EmundusResponse::ok([], Text::_('COM_EMUNDUS_SMS_UPDATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::CREATE]])]
	public function createSMSCategory(): EmundusResponse
	{
		$label = $this->app->input->getString('label', '');
		$label = strip_tags($label);
		if (empty($label))
		{
			throw new InvalidArgumentException('Label is required');
		}

		$category_id = $this->model->createSMSCategory($label, (int) $this->user->id);
		if (empty($category_id))
		{
			throw new RuntimeException(Text::_('SMS_CATEGORY_NOT_CREATED'));
		}

		return EmundusResponse::ok([], Text::_('SMS_CATEGORY_CREATED'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::READ]])]
	public function isSMSActivated(): EmundusResponse
	{
		return EmundusResponse::ok($this->model->activated, Text::_('SMS_ACTIVATION_STATUS'));
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::CREATE]])]
	public function getSMSConfiguration(): EmundusResponse
	{
		$addon = $this->model->getSMSAddon();
		if (empty($addon->configuration))
		{
			throw new RuntimeException(Text::_('SMS_CONFIGURATION_NOT_FOUND'));
		}

		$configuration = json_decode($addon->configuration, true);

		return EmundusResponse::ok($configuration);
	}

	#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
	#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'sms', 'mode' => CrudEnum::READ]])]
	public function getRecipientsData(): EmundusResponse
	{
		$fnums = $this->app->input->getString('fnums', '');
		if (empty($fnums))
		{
			throw new InvalidArgumentException('Fnums are required');
		}

		$valid_fnums = [];
		$fnums       = explode(',', $fnums);
		foreach ($fnums as $fnum)
		{
			if (EmundusHelperAccess::asAccessAction($this->sms_action_id, 'r', $this->user->id, $fnum))
			{
				$valid_fnums[] = $fnum;
			}
		}

		if (empty($valid_fnums))
		{
			throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
		}

		$recipients = $this->model->getRecipientsData($valid_fnums);
		return EmundusResponse::ok($recipients);
	}
}