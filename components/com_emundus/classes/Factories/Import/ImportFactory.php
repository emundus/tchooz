<?php
/**
 * @package     Tchooz\Factories\Import
 * @subpackage
 *
 * @copyright   Copyright (C) eMundus
 * @license     GNU General Public License version 2 or later
 */

namespace Tchooz\Factories\Import;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Import\ImportEntity;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Factories\DBFactory;
use Tchooz\Repositories\Task\TaskRepository;

class ImportFactory implements DBFactory
{
	public function fromDbObject(object|array $dbObject, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): ImportEntity
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

		return new ImportEntity(
			id: (int) $dbObject['id'],
			createdAt: new \DateTime($dbObject['created_at']),
			createdBy: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject['created_by']),
			type: $dbObject['type'],
			filename: $dbObject['filename'] ?? '',
			originalFilename: $dbObject['original_filename'] ?? '',
			format: $dbObject['format'],
			conflictMode: ImportConflictModeEnum::tryFrom($dbObject['conflict_mode']) ?? ImportConflictModeEnum::SKIP,
			task: $task,
			progress: (float) $dbObject['progress'],
			totalRows: (int) $dbObject['total_rows'],
			lastProcessedRow: (int) $dbObject['last_processed_row'],
			report: !empty($dbObject['report']) ? (json_decode($dbObject['report'], true) ?? []) : [],
			cancelled: $dbObject['cancelled'] == 1,
			failed: $dbObject['failed'] == 1,
		);
	}

	public function fromDbObjects(array $dbObjects, bool|array $withRelations = true, array $exceptRelations = [], ?DatabaseDriver $db = null): array
	{
		$entities       = [];
		$taskRepository = new TaskRepository();

		foreach ($dbObjects as $dbObject)
		{
			if (is_object($dbObject))
			{
				$dbObject = (array) $dbObject;
			}

			$task = null;
			if ($withRelations && !empty($dbObject['task_id']))
			{
				$task = $taskRepository->getTaskById($dbObject['task_id']);
			}

			$entities[] = new ImportEntity(
				id: (int) $dbObject['id'],
				createdAt: new \DateTime($dbObject['created_at']),
				createdBy: Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById((int) $dbObject['created_by']),
				type: $dbObject['type'],
				filename: $dbObject['filename'] ?? '',
				originalFilename: $dbObject['original_filename'] ?? '',
				format: $dbObject['format'],
				conflictMode: ImportConflictModeEnum::tryFrom($dbObject['conflict_mode']) ?? ImportConflictModeEnum::SKIP,
				task: $task,
				progress: (float) $dbObject['progress'],
				totalRows: (int) $dbObject['total_rows'],
				lastProcessedRow: (int) $dbObject['last_processed_row'],
				report: !empty($dbObject['report']) ? (json_decode($dbObject['report'], true) ?? []) : [],
				cancelled: $dbObject['cancelled'] == 1,
				failed: $dbObject['failed'] == 1,
			);
		}

		return $entities;
	}
}