<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionImport;
use Tchooz\Entities\Import\ImportEntity;
use Tchooz\Entities\Task\TaskEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Import\ImportConflictModeEnum;
use Tchooz\Enums\Import\RowStatusEnum;
use Tchooz\Enums\Task\TaskStatusEnum;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Repositories\Import\ImportRepository;
use Tchooz\Repositories\Task\TaskRepository;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Automation\Actions
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Automation\Actions\ActionImport
 */
class ActionImportTest extends UnitTestCase
{
	private ImportRepository  $importRepository;
	private TaskRepository    $taskRepository;
	private ContactRepository $contactRepository;

	private array $createdImportIds = [];

	private array $createdTaskIds = [];

	private array $createdContactEmails = [];

	private array $createdFiles = [];

	public function setUp(): void
	{
		parent::setUp();

		$this->importRepository  = new ImportRepository();
		$this->taskRepository    = new TaskRepository();
		$this->contactRepository = new ContactRepository();
	}

	protected function tearDown(): void
	{
		foreach ($this->createdContactEmails as $email)
		{
			try
			{
				$contact = $this->contactRepository->getByEmail($email);
				if ($contact !== null)
				{
					$this->contactRepository->delete($contact->getId());
				}
			}
			catch (\Throwable) {}
		}

		foreach ($this->createdImportIds as $id)
		{
			try { $this->importRepository->delete($id); } catch (\Throwable) {}
		}

		foreach ($this->createdTaskIds as $id)
		{
			try { $this->taskRepository->deleteTaskById($id); } catch (\Throwable) {}
		}

		foreach ($this->createdFiles as $path)
		{
			if (is_file($path))
			{
				@unlink($path);
			}
		}

		parent::tearDown();
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionImport::execute
	 * @return void
	 */
	public function testExecuteCompletesImportAndPersistsContacts(): void
	{
		$emailA = 'action.import.test+a' . rand(0, 999999) . '@example.com';
		$emailB = 'action.import.test+b' . rand(0, 999999) . '@example.com';
		$this->createdContactEmails[] = $emailA;
		$this->createdContactEmails[] = $emailB;

		$relativePath = $this->writeCsvFixture(
			"Nom,Prénom,email\n"
			. "Doe,John,$emailA\n"
			. "Smith,Jane,$emailB\n"
		);

		[$import, $task] = $this->createImportJob($relativePath, ActionEnum::CONTACT->value, 2);

		$result = (new ActionImport())->with($task)->execute([]);

		$this->assertSame(
			ActionExecutionStatusEnum::COMPLETED,
			$result,
			'A fully-consumed file must complete in one slice.'
		);

		$reloaded = $this->importRepository->getById($import->getId());
		$this->assertSame(100.0, $reloaded->getProgress(), 'A completed import must report 100% progress.');
		$this->assertFalse($reloaded->isFailed(), 'A successful import must not be flagged as failed.');
		$this->assertSame(
			2,
			$reloaded->getReport()['summary'][RowStatusEnum::CREATED->value] ?? 0,
			'Both rows should be reported as created.'
		);

		$this->assertNotNull($this->contactRepository->getByEmail($emailA), 'First contact should have been persisted.');
		$this->assertNotNull($this->contactRepository->getByEmail($emailB), 'Second contact should have been persisted.');
	}

	/**
	 * The job is identified by the running task; without one there is nothing to
	 * resume, so the action fails fast without touching any data.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionImport::execute
	 * @return void
	 */
	public function testExecuteWithoutTaskContextReturnsFailed(): void
	{
		$result = (new ActionImport())->execute([]);

		$this->assertSame(
			ActionExecutionStatusEnum::FAILED,
			$result,
			'Running the import action with no task in context must fail.'
		);
	}

	/**
	 * A missing source file rejects the whole job upfront (guardrail at the
	 * entry point), records a global error and flags the import as failed.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionImport::execute
	 * @return void
	 */
	public function testExecuteWithMissingSourceFileMarksImportFailed(): void
	{
		[$import, $task] = $this->createImportJob('tmp/imports/does-not-exist-' . rand(0, 999999) . '.csv', ActionEnum::CONTACT->value, 1);

		$result = (new ActionImport())->with($task)->execute([]);

		$this->assertSame(ActionExecutionStatusEnum::FAILED, $result, 'A missing source file must fail the action.');

		$reloaded = $this->importRepository->getById($import->getId());
		$this->assertTrue($reloaded->isFailed(), 'The import must be flagged as failed.');
		$this->assertNotEmpty(
			$reloaded->getReport()['summary']['global_errors'] ?? [],
			'A global error explaining the missing file must be recorded.'
		);
	}

	/**
	 * An import whose entity type has no registered importer is rejected before
	 * any row is read.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionImport::execute
	 * @return void
	 */
	public function testExecuteWithUnknownImporterTypeMarksImportFailed(): void
	{
		$relativePath = $this->writeCsvFixture("Nom,Prénom,email\nDoe,John,unused@example.com\n");

		[$import, $task] = $this->createImportJob($relativePath, 'definitely-not-a-real-entity', 1);

		$result = (new ActionImport())->with($task)->execute([]);

		$this->assertSame(ActionExecutionStatusEnum::FAILED, $result, 'An unknown importer type must fail the action.');

		$reloaded = $this->importRepository->getById($import->getId());
		$this->assertTrue($reloaded->isFailed(), 'The import must be flagged as failed.');
		$this->assertNotEmpty(
			$reloaded->getReport()['summary']['global_errors'] ?? [],
			'A global error naming the unknown importer must be recorded.'
		);
	}

	/**
	 * A cancelled import is acknowledged as COMPLETED so the scheduler stops
	 * re-enqueuing it, but no row is processed.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionImport::execute
	 * @return void
	 */
	public function testCancelledImportReturnsCompletedWithoutProcessing(): void
	{
		$email = 'action.import.cancelled+' . rand(0, 999999) . '@example.com';
		$this->createdContactEmails[] = $email;

		$relativePath = $this->writeCsvFixture("Nom,Prénom,email\nDoe,John,$email\n");

		[$import, $task] = $this->createImportJob($relativePath, ActionEnum::CONTACT->value, 1, cancelled: true);

		$result = (new ActionImport())->with($task)->execute([]);

		$this->assertSame(
			ActionExecutionStatusEnum::COMPLETED,
			$result,
			'A cancelled import must short-circuit to COMPLETED.'
		);
		$this->assertNull(
			$this->contactRepository->getByEmail($email),
			'No contact should be created when the import is cancelled.'
		);
	}

	/**
	 * When the source file describes another entity entirely (foreign headers),
	 * the pipeline returns a global error and the action terminates as a failure
	 * with nothing persisted.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionImport::execute
	 * @return void
	 */
	public function testForeignHeadersAbortAndMarkImportFailed(): void
	{
		$relativePath = $this->writeCsvFixture("foreign_column,another_unknown\nx,y\n");

		[$import, $task] = $this->createImportJob($relativePath, ActionEnum::CONTACT->value, 1);

		$result = (new ActionImport())->with($task)->execute([]);

		$this->assertSame(
			ActionExecutionStatusEnum::FAILED,
			$result,
			'A file with foreign headers must fail the whole job.'
		);

		$reloaded = $this->importRepository->getById($import->getId());
		$this->assertTrue($reloaded->isFailed(), 'The import must be flagged as failed.');
		$this->assertSame(
			0,
			$reloaded->getReport()['summary'][RowStatusEnum::CREATED->value] ?? 0,
			'No row should be created when the file is rejected upfront.'
		);
	}

	/**
	 * Persists a TaskEntity + a linked ImportEntity, mirroring what the import
	 * controller does when queuing an asynchronous import.
	 *
	 * @return array{0: ImportEntity, 1: TaskEntity}
	 */
	private function createImportJob(
		string $relativeFilename,
		string $type,
		int    $totalRows,
		bool   $cancelled = false
	): array
	{
		$coordinator = Factory::getContainer()
			->get(UserFactoryInterface::class)
			->loadUserById($this->dataset['coordinator']);

		$task = new TaskEntity(0, TaskStatusEnum::IN_PROGRESS, null, $coordinator->id);
		$this->taskRepository->saveTask($task);
		$this->createdTaskIds[] = $task->getId();

		$import = new ImportEntity(
			id: 0,
			createdAt: new \DateTime(),
			createdBy: $coordinator,
			type: $type,
			filename: $relativeFilename,
			originalFilename: 'contacts.csv',
			format: 'csv',
			conflictMode: ImportConflictModeEnum::SKIP,
			task: $task,
			totalRows: $totalRows,
			cancelled: $cancelled,
		);
		$this->importRepository->flush($import);
		$this->createdImportIds[] = $import->getId();

		return [$import, $task];
	}

	/**
	 * Writes a CSV fixture under JPATH_SITE/tmp/imports (the same durable
	 * location the controller uses) and returns its path relative to JPATH_SITE,
	 * which is what ActionImport resolves against.
	 */
	private function writeCsvFixture(string $contents): string
	{
		$directory = JPATH_SITE . '/tmp/imports';
		if (!is_dir($directory))
		{
			mkdir($directory, 0755, true);
		}

		$relativePath = 'tmp/imports/unit-test-' . rand(0, 999999) . '.csv';
		$absolutePath = JPATH_SITE . '/' . $relativePath;

		file_put_contents($absolutePath, $contents);
		$this->createdFiles[] = $absolutePath;

		return $relativePath;
	}
}