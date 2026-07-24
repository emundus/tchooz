<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories\Poll
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Poll;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Poll\PollEntity;
use Tchooz\Enums\Campaigns\StatusEnum;
use Tchooz\Enums\ColorEnum;
use Tchooz\Repositories\Poll\PollRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Poll
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Poll\PollRepository
 */
class PollRepositoryTest extends UnitTestCase
{
	private PollRepository $repository;

	/** @var int[] */
	private array $createdPollIds = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->repository = new PollRepository();
	}

	protected function tearDown(): void
	{
		foreach ($this->createdPollIds as $id)
		{
			try
			{
				$this->repository->delete($id);
			}
			catch (\Exception)
			{
				// already removed by the test
			}
		}
		$this->createdPollIds = [];

		parent::tearDown();
	}

	private function pollTableExists(): bool
	{
		try
		{
			$db = Factory::getContainer()->get('DatabaseDriver');

			return !empty($db->setQuery('SHOW TABLES LIKE ' . $db->quote('jos_emundus_setup_polls'))->loadResult());
		}
		catch (\Throwable)
		{
			// No reachable database in this environment.
			return false;
		}
	}

	// -------------------------------------------------------------------------
	// Validation — throws before touching the database
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollRepository::flush
	 * @return void
	 */
	public function testFlushThrowsWhenNameIsEmpty(): void
	{
		$poll = new PollEntity(0, '', '', ColorEnum::BLUE);

		$this->expectException(\InvalidArgumentException::class);

		$this->repository->flush($poll);
	}

	/**
	 * @covers \Tchooz\Repositories\Poll\PollRepository::delete
	 * @return void
	 */
	public function testDeleteThrowsWhenPollIdIsEmpty(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->delete(0);
	}

	/**
	 * @covers \Tchooz\Repositories\Poll\PollRepository::deleteAllParticipants
	 * @return void
	 */
	public function testDeleteAllParticipantsThrowsWhenPollIdIsEmpty(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->deleteAllParticipants(0);
	}

	/**
	 * @covers \Tchooz\Repositories\Poll\PollRepository::saveSlot
	 * @return void
	 */
	public function testSaveSlotThrowsWhenParametersAreMissing(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->saveSlot(0, null, '', '');
	}

	/**
	 * @covers \Tchooz\Repositories\Poll\PollRepository::deleteSlot
	 * @return void
	 */
	public function testDeleteSlotThrowsWhenSlotIdIsEmpty(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->deleteSlot(0);
	}

	// -------------------------------------------------------------------------
	// Round-trip — insert, read back, delete (needs the poll schema)
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Poll\PollRepository::flush
	 * @covers \Tchooz\Repositories\Poll\PollRepository::delete
	 * @return void
	 */
	public function testFlushThenDeletePersistsAndRemovesPoll(): void
	{
		if (!$this->pollTableExists())
		{
			$this->markTestSkipped('Poll schema is not present in the test database.');
		}

		$poll = new PollEntity(
			0,
			'PHPUnit poll',
			'Created by PollRepositoryTest',
			ColorEnum::BLUE,
			StatusEnum::UPCCOMING,
			null,
			null,
			[],
			[],
			true
		);

		$this->repository->flush($poll);
		$id = $poll->getId();
		$this->createdPollIds[] = $id;

		$this->assertGreaterThan(0, $id, 'flush should assign a generated id to a new poll');

		$columns = $this->repository->getTableColumns(PollRepository::class);
		$loaded  = $this->repository->getItemByField('id', $id, true, $columns);

		$this->assertInstanceOf(PollEntity::class, $loaded, 'The persisted poll should be reloadable');
		$this->assertSame('PHPUnit poll', $loaded->getName(), 'The persisted name should match');
		$this->assertTrue($loaded->canEditAnswers(), 'The persisted can_edit_answers flag should be true');

		$this->assertTrue($this->repository->delete($id), 'delete should return true');

		$afterDelete = $this->repository->getItemByField('id', $id, true, $columns);
		$this->assertEmpty($afterDelete, 'The poll should no longer be found after deletion');

		// Already deleted: avoid a duplicate delete in tearDown.
		$this->createdPollIds = [];
	}
}
