<?php

namespace Tchooz\Factories\Task;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Task\TaskPriorityEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Factories\Automation\ActionExecutionMessageFactory;
use Tchooz\Repositories\Automation\ActionRepository;

class TaskFactory
{

	/**
	 * @param   array                $dbObjects
	 * @param   DatabaseDriver|null  $db
	 *
	 * @return array<TaskEntity>
	 * @throws \Exception
	 */
	public static function fromDbObjects(array $dbObjects, ?DatabaseDriver $db = null): array
	{
		$tasks = [];

		$db = $db ?? Factory::getContainer()->get('DatabaseDriver');

		if (!empty($dbObjects))
		{
			$actionRepository = new ActionRepository($db);

			foreach ($dbObjects as $obj) {
				$action = null;
				if(!empty($obj->action_id))
				{
					$action = $actionRepository->getActionById($obj->action_id);
				}

				$executionMessages = [];
				if (!empty($obj->messages))
				{
					$messages = json_decode($obj->messages, true);

					if (!empty($messages))
					{
						foreach ($messages as $messageData)
						{
							$executionMessages[] = ActionExecutionMessageFactory::fromArray($messageData);
						}
					}
				}

				$tasks[] = new TaskEntity(
					$obj->id,
					TaskStatusEnum::from($obj->status),
					$action,
					$obj->user_id ?? null,
					json_decode($obj->metadata, true) ?? [],
					isset($obj->created_at) ? new \DateTimeImmutable($obj->created_at) : null,
					isset($obj->updated_at) ? new \DateTimeImmutable($obj->updated_at) : null,
					isset($obj->started_at) ? new \DateTimeImmutable($obj->started_at) : null,
					isset($obj->finished_at) ? new \DateTimeImmutable($obj->finished_at) : null,
					$obj->attempts ?? 0,
					!empty($obj->priority) ? TaskPriorityEnum::from($obj->priority) : TaskPriorityEnum::MEDIUM,
					$executionMessages
				);
			}
		}

		return $tasks;
	}
}