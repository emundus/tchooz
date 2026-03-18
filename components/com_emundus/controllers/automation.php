<?php

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\LanguageHelper;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Router\Route;
use Joomla\Input\Input;
use Joomla\CMS\MVC\Controller\BaseController;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Entities\List\AdditionalColumnPublished;
use Tchooz\Entities\List\AdditionalColumnTag;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionsAndorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Enums\List\ListColumnTypesEnum;
use Tchooz\Enums\List\ListDisplayEnum;
use Tchooz\Factories\Automation\AutomationFactory;
use Tchooz\Repositories\Automation\AutomationRepository;
use Tchooz\Repositories\Automation\EventsRepository;
use Tchooz\EmundusResponse;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;
use Tchooz\Services\Automation\ConditionRegistry;
use Tchooz\Services\Automation\TargetPredefinitionRegistry;
use Tchooz\Traits\TraitResponse;
use Tchooz\Services\Automation\ActionRegistry;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;


// todo: move non-related directly to automation methods to other controllers (action, condition methods)

class EmundusControllerAutomation extends BaseController
{
	use TraitResponse;

	private int $automationActionId = 0;

	private AutomationRepository $automationRepository;

	private const MAX_LIMIT = 100;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplicationInterface $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);

		$this->automationActionId = EmundusHelperAccess::getActionIdFromActionName('automation');
		$this->automationRepository = new AutomationRepository();
	}

	public function getActionsParameters(): void
	{
		$response = ['data' => [], 'status' => false, 'code' => 403, 'msg' => Text::_('ACCESS_DENIED')];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'r', $this->app->getIdentity()->id))
		{
			try {
				$actionRegistry = new ActionRegistry();
				$response['data'] = $actionRegistry->getAvailableActionsSchema();
				$response['status'] = true;
				$response['code'] = 200;
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_ACTIONS_PARAMETERS_RETRIEVED_SUCCESS');
			} catch (\Exception $e) {
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_ACTIONS_PARAMETERS_RETRIEVED_ERROR') . ' : ' . $e->getMessage();
				$response['code'] = 500;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getAutomations(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'r', $this->app->getIdentity()->id))
		{
			$limit = $this->input->getInt('lim', 10);
			$page = $this->input->getInt('page', 1);

			if ($limit < 1) {
				$limit = 10;
			} elseif ($limit > self::MAX_LIMIT) {
				$limit = self::MAX_LIMIT;
			}

			$filters = $this->input->get('filter', [], 'array');
			$filters['search'] = $this->input->getString('recherche', '');

			$repository = new AutomationRepository();
			$automations = $repository->getAutomations($filters, $limit, $page);
			$automations = array_map(function ($automationEntity) {
				assert($automationEntity instanceof AutomationEntity);
				$automation = (object)$automationEntity->serialize();
				$automation->label = ['fr' => $automation->name, 'en' => $automation->name];
				$automation->additional_columns = [
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_AUTOMATION_EVENT'),
						'',
						ListDisplayEnum::ALL,
						'',
						$automationEntity->getEvent()->getLabel()
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_AUTOMATION_DESCRIPTION'),
						'',
						ListDisplayEnum::ALL,
						'',
						$automationEntity->getDescription()
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_AUTOMATION_ACTIONS'),
						'',
						ListDisplayEnum::ALL,
						'',
						'',
						array_map(function($action) {
							return new AdditionalColumnTag(
								Text::_('COM_EMUNDUS_AUTOMATION_ACTION'),
								$action->getIcon(),
								$action->getLabel(),
								'material-symbols-outlined tw-h-[20px] tw-w-[20px] !tw-text-2xl tw-font-bold tw-text-blue-600 tw-bg-blue-200 tw-rounded-coordinator tw-pl-1 tw-pr-7'
							);
						}, $automationEntity->getActions()),
						ListColumnTypesEnum::TAGS
					),
					new AdditionalColumnPublished($automationEntity->isPublished(), 'published')
				];

				return $automation;
			}, $automations);

			$response['data'] = [
				'datas' => $automations,
				'count' => $repository->getAutomationsCount($filters)
			];
			$response['status'] = true;
			$response['code'] = 200;
			$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATIONS_RETRIEVED_SUCCESS');
		}

		$this->sendJsonResponse($response);
	}

	public function getAutomationsHistory(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'r', $this->app->getIdentity()->id))
		{
			if (!class_exists('EmundusModelSettings'))
			{
				require_once(JPATH_ROOT . '/components/com_emundus/models/settings.php');
			}
			$m_settings = new EmundusModelSettings();

			$limit = $this->input->getInt('lim', 10);
			$page = $this->input->getInt('page', 1);


			$history = $m_settings->getHistory('com_emundus.automation', false, $page, $limit);
			$history = array_map(function ($row) {
				$message = json_decode($row->message);

				return [
					'id' => $row->id,
					'label' => ['fr' => $message->automation_entity->name, 'en' => $message->automation_entity->name],
					'message' => $row->message,
					'additional_columns' => [
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_AUTOMATION_PROCESS_DATE'),
							'',
							ListDisplayEnum::ALL,
							'',
							EmundusHelperDate::displayDate($row->log_date, 'd/m/Y H:i', 0)
						),
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_AUTOMATION_PROCESS_TRIGGERED_BY'),
							'',
							ListDisplayEnum::ALL,
							'',
							$message->username ?? Text::_('COM_EMUNDUS_SYSTEM')
						),
						new AdditionalColumn(
							Text::_('COM_EMUNDUS_AUTOMATION_PROCESS_STATE'),
							'',
							ListDisplayEnum::ALL,
							'',
							'',
							[
								new AdditionalColumnTag(
									Text::_('COM_EMUNDUS_AUTOMATION_STATE'),
									$message->nb_failed_actions > 0 ? Text::_('COM_EMUNDUS_AUTOMATION_PROCESS_FAILED') : Text::_('COM_EMUNDUS_AUTOMATION_PROCESS_SUCCESS'),
									'',
									$message->nb_failed_actions < 1 ? 'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm em-bg-main-500 tw-text-white' : 'tw-mr-2 tw-h-max tw-flex tw-flex-row tw-items-center tw-gap-2 tw-text-base tw-rounded-coordinator tw-px-2 tw-py-1 tw-font-medium tw-text-sm tw-bg-red-300 tw-text-red-700'
								)
							],
							ListColumnTypesEnum::TAGS
						)
					]
				];
			}, $history);


			$response['data'] = [
				'datas' => $history,
				'count' => $m_settings->getHistoryLength('com_emundus.automation')
			];
			$response['status'] = true;
			$response['code'] = 200;
			$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_CONDITION_FIELDS_RETRIEVED_SUCCESS');
		}

		$this->sendJsonResponse($response);
	}

	public function saveAutomation(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'c', $this->app->getIdentity()->id))
		{
			$data = $this->input->getString('automation', '');
			$automation = json_decode($data);

			// todo: automationFactory->fromJson($automationData)

			if (!empty($automation) && !empty($automation->event) && !empty($automation->name) && !empty($automation->actions)) {

				$factory = new AutomationFactory();
				$automationEntity = $factory->fromJson($automation);

				try {
					$saved = $this->automationRepository->flush($automationEntity);
					if ($saved) {
						$response['status'] = true;
						$response['code'] = 200;
						$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_SAVED_SUCCESS');
						$response['data'] = (object)$automationEntity->serialize();
						$response['redirect'] = Route::_('/index.php?option=com_emundus&view=automation&layout=edit&id=' . $automationEntity->getId());
						$menuLink = EmundusHelperMenu::routeViaLink('/index.php?option=com_emundus&view=automation&layout=edit&id=' . $automationEntity->getId());
						if (!empty($menuLink)) {
							$response['redirect'] = $menuLink;
						}
					} else {
						$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_SAVED_ERROR');
						$response['code'] = 500;
					}
				} catch (\InvalidArgumentException $e) {
					$response['msg'] = Text::_($e->getMessage());
					$response['code'] = 400;
				}
			} else {
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_INVALID_AUTOMATION_DATA');
				$response['code'] = 400;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function delete(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'd', $this->app->getIdentity()->id))
		{
			$automationId = $this->input->getInt('id', 0);

			if ($automationId > 0)
			{
				$deleted = $this->automationRepository->delete($automationId);

				if ($deleted) {
					$response['status'] = true;
					$response['code'] = 200;
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_DELETED_SUCCESS');
				} else {
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_DELETED_ERROR');
					$response['code'] = 500;
				}
			} else {
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_INVALID_AUTOMATION_ID');
				$response['code'] = 400;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function duplicateAutomation(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'c', $this->app->getIdentity()->id))
		{
			$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_INVALID_AUTOMATION_ID');
			$response['code'] = 400;
			$id = $this->input->getInt('id', 0);

			if ($id > 0)
			{
				$automation = $this->automationRepository->getById($id);

				if (!empty($automation)) {
					$newAutomation = $this->automationRepository->duplicateAutomation($automation);

					if ($newAutomation->getId() > 0) {
						$response['status'] = true;
						$response['code'] = 200;
						$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_DUPLICATED_SUCCESS');
						$response['data'] = $newAutomation->serialize();
						$response['redirect'] = Route::_('/index.php?option=com_emundus&view=automation&layout=edit&id=' . $newAutomation->getId());
					} else {
						$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_DUPLICATED_ERROR');
						$response['code'] = 500;
					}
				} else {
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_NOT_FOUND');
					$response['code'] = 404;
				}
			}
		}

		$this->sendJsonResponse($response);
	}

	public function publishAutomation(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'u', $this->app->getIdentity()->id))
		{
			$automationIds = $this->input->getString('ids', '');

			if (empty($automationIds))
			{
				$automationIds = $this->input->getString('id', '');
			}


			if (!empty($automationIds))
			{
				$ids = array_map('intval', explode(',', $automationIds));
				$published = $this->automationRepository->togglePublishedAutomations($ids, true);

				if ($published) {
					$response['status'] = true;
					$response['code'] = 200;
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_PUBLISHED_SUCCESS');
				} else {
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_PUBLISHED_ERROR');
					$response['code'] = 500;
				}
			} else {
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_INVALID_AUTOMATION_ID');
				$response['code'] = 400;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function unpublishAutomation(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'u', $this->app->getIdentity()->id))
		{
			$automationIds = $this->input->getString('ids', '');

			if (empty($automationIds))
			{
				$automationIds = $this->input->getString('id', '');
			}

			if (!empty($automationIds))
			{
				$ids = array_map('intval', explode(',', $automationIds));
				$unpublished = $this->automationRepository->togglePublishedAutomations($ids, false);

				if ($unpublished) {
					$response['status'] = true;
					$response['code'] = 200;
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_UNPUBLISHED_SUCCESS');
				} else {
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_UNPUBLISHED_ERROR');
					$response['code'] = 500;
				}
			} else {
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_INVALID_AUTOMATION_ID');
				$response['code'] = 400;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getEventsList(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'r', $this->app->getIdentity()->id))
		{
			$eventsRepository = new EventsRepository();
			$events = $eventsRepository->getEventsList();

			if (!empty($events)) {
				$response['data'] = array_map(function ($event) {
					return $event->serialize();
				}, $events);
				$response['status'] = true;
				$response['code'] = 200;
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_EVENTS_RETRIEVED_SUCCESS');
			} else {
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_NO_EVENTS_FOUND');
				$response['code'] = 204;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getActionsList(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'c', $this->app->getIdentity()->id))
		{
			try {
				$actionRegistry = new ActionRegistry();
				$response['data'] = $actionRegistry->getAvailableActionsSchema();
				$response['status'] = true;
				$response['code'] = 200;
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_AVAILABLE_ACTIONS_RETRIEVED_SUCCESS');
			} catch (\Exception $e) {
				Log::add('Failed to retrieve available actions: ' . $e->getMessage(), Log::ERROR, 'com_emundus.automation');
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_AVAILABLE_ACTIONS_RETRIEVED_ERROR') . ' : ' . $e->getMessage();
				$response['code'] = 500;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getConditionsList(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'c', $this->app->getIdentity()->id))
		{
			$contextFilters = [];
			$event_id = $this->input->getInt('event_id', 0);
			$automation_id = $this->input->getInt('automation_id', 0);

			if (!empty($event_id))
			{
				$eventsRepository = new EventsRepository();
				$event            = $eventsRepository->getEventById($event_id);
				$contextFilters['eventName'] = $event->getName();
			}

			if (!empty($automation_id))
			{
				$contextFilters['automationId'] = $automation_id;
			}

			try {
				$conditionsRegistry = new ConditionRegistry();
				$response['data'] = $conditionsRegistry->getAvailableConditionSchemas($contextFilters);
				$response['status'] = true;
				$response['code'] = 200;
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_AVAILABLE_ACTIONS_RETRIEVED_SUCCESS');
			} catch (\Exception $e) {
				Log::add('Failed to retrieve available conditions resolvers: ' . $e->getMessage(), Log::ERROR, 'com_emundus.automation');
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_AVAILABLE_CONDITIONS_RETRIEVED_ERROR') . ' : ' . $e->getMessage();
				$response['code'] = 500;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getTargetConditionsList(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'c', $this->app->getIdentity()->id))
		{
			$contextPossessFile = $this->input->getInt('context_possess_file', 0);
			$contextPossessFile = $contextPossessFile === 1;
			$targetType = $this->input->getString('type', '');

			try {
				$conditionsRegistry = new ConditionRegistry();
				$conditions = $conditionsRegistry->getAvailableConditionSchemas();
				$conditions = array_filter($conditions, function ($condition) use ($targetType) {
					return in_array($targetType, $condition['allowedActionTargetTypes'], true);
				});
				$conditions = array_values($conditions);

				if ($contextPossessFile)
				{
					// if from type is file, we must add an available value for conditions to allow comparison to be on the current file value
					foreach ($conditions as $c_key => $condition)
					{
						if (in_array(TargetTypeEnum::FILE->value, $condition['allowedActionTargetTypes']))
						{
							// todo: add for each fields the possible value ConditionEntity::SAME_AS_CURRENT_FILE as first value if field type is compatible

							foreach ($condition['fields'] as $f_key =>  $field)
							{
								if ($field['type'] === 'choice')
								{
									array_unshift($field['choices'], [
										'value' => ConditionEntity::SAME_AS_CURRENT_FILE,
										'label' => Text::_('COM_EMUNDUS_AUTOMATION_CONDITION_FIELD_VALUE_SAME_AS_CURRENT_FILE')
									]);

									$condition['fields'][$f_key] = $field;
								}
							}

							$conditions[$c_key] = $condition;
						}
					}
				}

				$response['data'] = $conditions;
				$response['status'] = true;
				$response['code'] = 200;
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_TARGET_CONDITIONS_RETRIEVED_SUCCESS');
			} catch (\Exception $e) {
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_INVALID_CONDITION_TARGET_TYPE');
				$response['code'] = 400;
			}
		}

		$this->sendJsonResponse($response);
	}

	public function getConditionsFields(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAccessAction($this->automationActionId, 'c', $this->app->getIdentity()->id))
		{
			$eventType = $this->input->getString('type', '');

			try {
				$targetType = ConditionTargetTypeEnum::from($eventType);
				$parameters = $this->input->getString('parameters', '');
				$parameters = json_decode($parameters, true) ?? [];
				$parameters['search'] = $this->input->getString('search_query', '');

				$conditionsRegistry = new ConditionRegistry();
				$resolver = $conditionsRegistry->getResolver($targetType->value);
				if ($resolver) {
					$fields = $resolver->getAvailableFields($parameters);
					$response['data'] = array_map(function ($field) {
						return $field->toSchema();
					}, $fields);
					$response['status'] = true;
					$response['code'] = 200;
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_CONDITION_FIELDS_RETRIEVED_SUCCESS');
				} else {
					$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_INVALID_CONDITION_TARGET_TYPE');
					$response['code'] = 400;
				}
			} catch (\Exception $e) {
				$response['msg'] = Text::_('COM_EMUNDUS_AUTOMATION_INVALID_CONDITION_TARGET_TYPE');
				$response['code'] = 400;
			}
		}

		$this->sendJsonResponse($response);
	}
	
	public function runaction(): void
	{
		try
		{
			$typesAllowed = ['generate_letter'];

			$user = $this->app->getIdentity();
			
			$type = $this->input->getString('type', '');
			$options = $this->input->getString('options');
			$options = json_decode($options, true) ?? [];

			$eSession = $this->app->getSession()->get('emundusUser');

			if($user->guest || empty($eSession) || empty($eSession->fnum) || !in_array($type, $typesAllowed, true))
			{
				throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
			}

			$fnum = $eSession->fnum;

			$actionRegistry = new ActionRegistry();
			$actionInstance = $actionRegistry->getActionInstance($type, $options);

			if(!$actionInstance)
			{
				throw new Exception(Text::_('COM_EMUNDUS_AUTOMATION_ACTION_NOT_FOUND'), EmundusResponse::HTTP_NOT_FOUND);
			}

			$actionTarget = new ActionTargetEntity($user, $fnum);
			$executed = $actionInstance->execute($actionTarget);

			if($executed !== ActionExecutionStatusEnum::COMPLETED)
			{
				throw new Exception(Text::_('COM_EMUNDUS_AUTOMATION_ACTION_EXECUTION_FAILED'), EmundusResponse::HTTP_INTERNAL_SERVER_ERROR);
			}

			$result = $actionInstance->getResult();

			$response = EmundusResponse::ok($result, Text::_('COM_EMUNDUS_AUTOMATION_ACTION_EXECUTED_SUCCESS'));
		}
		catch (Exception $e)
		{
			$response = EmundusResponse::fail($e->getMessage(), $e->getCode());
		}

		$this->sendJsonResponse($response);
	}
}