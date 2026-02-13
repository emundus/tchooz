<?php

use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Input\Input;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\List\AdditionalColumn;
use Tchooz\Entities\List\AdditionalColumnTag;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\List\ListColumnTypesEnum;
use Tchooz\Enums\List\ListDisplayEnum;
use Tchooz\Enums\Task\TaskPriorityEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Factories\Automation\ActionTargetFactory;
use Tchooz\Repositories\Task\TaskRepository;
use Tchooz\Traits\TraitResponse;
use Joomla\CMS\MVC\Controller\BaseController;

class EmundusControllerTask extends BaseController
{
	use TraitResponse;

	private const MAX_LIMIT = 100;

	private TaskRepository $taskRepository;

	public function __construct($config = [], ?MVCFactoryInterface $factory = null, ?CMSApplicationInterface $app = null, ?Input $input = null)
	{
		parent::__construct($config, $factory, $app, $input);
		$this->taskRepository = new TaskRepository();
	}

	public function executeTask(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAdministratorAccessLevel($this->app->getIdentity()->id))
		{
			$taskIds = $this->input->getString('ids', '');
			if (empty($taskIds))
			{
				$taskIds = $this->input->getInt('id', 0);
			}

			if (!empty($taskIds))
			{
				$tasks = $this->taskRepository->getTasks(['id' => $taskIds]);
				if (!empty($tasks))
				{
					$allExecuted = true;

					foreach ($tasks as $task)
					{
						try
						{
							$this->taskRepository->executeTask($task);
							if ($task->getStatus() === TaskStatusEnum::FAILED)
							{
								$allExecuted = false;
							}
						}
						catch (\Exception $e)
						{
							$allExecuted = false;
						}
					}

					if ($allExecuted)
					{
						$response = ['status' => true, 'code' => 200, 'msg' => Text::_('COM_EMUNDUS_TASK_EXECUTED_SUCCESSFULLY')];
					}
					else
					{
						$response = ['status' => false, 'code' => 500, 'data' => [], 'msg' => Text::_('COM_EMUNDUS_TASK_EXECUTION_FAILED_FOR_SOME_TASKS')];
					}
				}
				else
				{
					$response = ['status' => false, 'code' => 404, 'data' => [], 'msg' => Text::_('COM_EMUNDUS_TASKS_NOT_FOUND')];
				}
			}
			else
			{
				$response = ['status' => false, 'code' => 400, 'data' => [], 'msg' => Text::_('COM_EMUNDUS_INVALID_TASK_ID')];
			}
		}

		$this->sendJsonResponse($response);
	}

	public function gettasks(): void
	{
		$response = ['code' => 403, 'status' => false, 'msg' => Text::_('ACCESS_DENIED'), 'data' => []];

		if (EmundusHelperAccess::asAdministratorAccessLevel($this->app->getIdentity()->id))
		{
			$limit = $this->input->getInt('lim', 10);
			$page  = $this->input->getInt('page', 1);

			if ($limit < 1)
			{
				$limit = 10;
			}
			elseif ($limit > self::MAX_LIMIT)
			{
				$limit = self::MAX_LIMIT;
			}
			$filters = $this->input->get('filter', [], 'array');

			$count = $this->taskRepository->getTasksCount($filters);
			$tasks = $this->taskRepository->getTasks($filters, $limit, $page);

			$tasks = array_map(function ($task) {
				assert($task instanceof TaskEntity);
				$to = '';
				if (!empty($task->getMetadata()) && isset($task->getMetadata()['actionTargetEntity'])) {
					$actionTargetEntityData = $task->getMetadata()['actionTargetEntity'];
					$actionTargetEntity = ActionTargetFactory::fromSerialized($actionTargetEntityData);

					if ($actionTargetEntity instanceof ActionTargetEntity)
					{
						if (!empty($actionTargetEntity->getFile()))
						{
							$to = $actionTargetEntity->getFile();
						} else if (!empty($actionTargetEntity->getUserId()))
						{
							$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($actionTargetEntity->getUserId());
							$to = $user->username;
						}
					}
				}

				$serializedTask          = $task->serialize();
				$serializedTask['label'] = [
					'fr' => '[#' . $task->getId() . '] ' . (!empty($task->getAction()) ? $task->getAction()->getLabelForLog() : '') . (!empty($to) ? Text::_('COM_EMUNDUS_TASK_TARGET') .' : ' . $to : ''),
				];

				$createdAt = EmundusHelperDate::displayDate($task->getCreatedAt()->format('Y-m-d H:i:s'), 'd/m/Y H:i', 0);
				$startedAt = !empty($task->getStartedAt()) ? EmundusHelperDate::displayDate($task->getStartedAt()->format('Y-m-d H:i:s'), 'd/m/Y H:i', 0) : Text::_('COM_EMUNDUS_TASK_NOT_STARTED');
				$finishedAt = !empty($task->getFinishedAt()) ? EmundusHelperDate::displayDate($task->getFinishedAt()->format('Y-m-d H:i:s'), 'd/m/Y H:i', 0) : Text::_('COM_EMUNDUS_TASK_NOT_FINISHED');

				$serializedTask['additional_columns'] = [
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_TASK_ATTEMPTS'),
						'',
						ListDisplayEnum::ALL,
						'',
						$task->getAttempts(),
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_TASK_CREATED_AT'),
						'',
						ListDisplayEnum::ALL,
						'',
						$createdAt,
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_TASK_STARTED_AT'),
						'',
						ListDisplayEnum::ALL,
						'',
						$startedAt,
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_TASK_FINISHED'),
						'',
						ListDisplayEnum::ALL,
						'',
						$finishedAt,
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_TASK_STATUS'),
						'',
						ListDisplayEnum::ALL,
						'',
						'',
						[
							new AdditionalColumnTag(
								$task->getStatus()->getLabel(),
								$task->getStatus()->getIcon(),
								$task->getStatus()->getLabel(),
								$task->getStatus()->getClasses()
							)
						],
						ListColumnTypesEnum::TAGS
					),
					new AdditionalColumn(
						Text::_('COM_EMUNDUS_TASK_PRIORITY'),
						'',
						ListDisplayEnum::ALL,
						'',
						$task->getPriority()->getHtmlBadge(),
					)
				];

				return $serializedTask;
			}, $tasks);


			$data     = [
				'datas' => $tasks,
				'count' => $count,
			];
			$response = ['status' => true, 'code' => 200, 'data' => $data, 'msg' => Text::_('COM_EMUNDUS_TASKS_RETRIEVED_SUCCESSFULLY')];
		}

		$this->sendJsonResponse($response);
	}
}