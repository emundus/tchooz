<?php
/**
 * @package     Tchooz\Factories\Export
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Factories\Export;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Export\ExportEntity;
use Tchooz\Enums\Export\ExportFormatEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Task\TaskRepository;

class ExportFactory implements DBFactory
{

	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): mixed
	{
		if (is_object($dbObject))
		{
			$dbObject = (array) $dbObject;
		}

		$task = null;
		if ($withRelations && !empty($dbObject['task_id']))
		{
			$taskRepository = new TaskRepository();
			$task           = $taskRepository->getTaskById($dbObject['task_id']);
		}

		$format = ExportFormatEnum::tryFrom($dbObject['format']);
		if ($format === null)
		{
			throw new \InvalidArgumentException('Invalid export format value: ' . $dbObject['format']);
		}

		return new ExportEntity(
			id: $dbObject['id'],
			createdAt: new \DateTime($dbObject['created_at']),
			createdBy: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject['created_by']),
			filename: $dbObject['filename'],
			format: $format,
			expiredAt: !empty($dbObject['expired_at']) ? new \DateTime($dbObject['expired_at']) : null,
			task: $task,
			hits: $dbObject['hits'],
			progress: $dbObject['progress'],
			cancelled: $dbObject['cancelled'] == 1,
			failed: $dbObject['failed'] == 1,
		);
	}

	public function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities = [];
		$taskRepository = new TaskRepository();

		foreach ($dbObjects as $dbObject)
		{
			if(is_object($dbObject))
			{
				$dbObject = (array) $dbObject;
			}

			$task = null;
			if ($withRelations && !empty($dbObject['task_id']))
			{

				$task = $taskRepository->getTaskById($dbObject['task_id']);
			}

			$format = ExportFormatEnum::tryFrom($dbObject['format']);
			if ($format === null)
			{
				throw new \InvalidArgumentException('Invalid export format value: ' . $dbObject['format']);
			}

			$entities[] = new ExportEntity(
				id: $dbObject['id'],
				createdAt: new \DateTime($dbObject['created_at']),
				createdBy: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject['created_by']),
				filename: $dbObject['filename'],
				format: $format,
				expiredAt: !empty($dbObject['expired_date']) ? new \DateTime($dbObject['expired_at']) : null,
				task: $task,
				hits: $dbObject['hits'],
				progress: $dbObject['progress'],
				cancelled: $dbObject['cancelled'] == 1,
				failed: $dbObject['failed'] == 1,
			);
		}

		return $entities;
	}
}