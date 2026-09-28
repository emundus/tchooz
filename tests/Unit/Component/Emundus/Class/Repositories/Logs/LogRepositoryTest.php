<?php

namespace Unit\Component\Emundus\Class\Repositories\Logs;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Logs\LogEntity;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\Logs\LogRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Logs
 *
 * @covers      \Tchooz\Repositories\Logs\LogRepository
 */
class LogRepositoryTest extends UnitTestCase
{
	private LogRepository $repository;

	private array $writtenIds = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->repository = new LogRepository();
	}

	protected function tearDown(): void
	{
		foreach ($this->writtenIds as $id)
		{
			$this->repository->delete($id);
		}
		$this->writtenIds = [];

		parent::tearDown();
	}

	/**
	 * @covers \Tchooz\Repositories\Logs\LogRepository::add
	 */
	public function testAddWritesTheEntryAsDeclared(): void
	{
		$log = new LogEntity(
			userFrom: $this->dataset['coordinator'],
			action: ActionEnum::FILE,
			crud: CrudEnum::UPDATE,
			message: 'COM_EMUNDUS_ACCESS_FILE_UPDATE',
			params: ['updated' => [['old' => 'before', 'new' => 'after']]],
			fnum: $this->dataset['fnum'],
			userTo: $this->dataset['applicant']
		);

		$this->assertTrue($this->repository->add($log));
		$this->assertNotEmpty($log->getId(), 'The written entry should carry its new id.');
		$this->writtenIds[] = $log->getId();

		$written = $this->repository->getById($log->getId());

		$this->assertEquals($this->dataset['fnum'], $written->getFnum());
		$this->assertEquals(CrudEnum::UPDATE, $written->getCrud());
		$this->assertEquals('COM_EMUNDUS_ACCESS_FILE_UPDATE', $written->getMessage());
		$this->assertEquals('after', $written->getParams()['updated'][0]['new']);
	}

	/**
	 * Action ids above the seeded range differ per platform, so a name must be resolved through
	 * the actions referential rather than assumed.
	 *
	 * @covers \Tchooz\Repositories\Logs\LogRepository::add
	 */
	public function testAddResolvesTheActionNameToItsId(): void
	{
		$expectedId = (new ActionRepository())->getByName(ActionEnum::EXPORT->value)?->getId();
		$this->assertNotEmpty($expectedId, 'The "export" action should exist in the actions referential.');

		$log = new LogEntity(
			userFrom: $this->dataset['coordinator'],
			action: ActionEnum::EXPORT,
			crud: CrudEnum::CREATE,
			message: 'COM_EMUNDUS_LOGS_AUTOMATION_EXPORT',
			fnum: $this->dataset['fnum']
		);

		$this->assertTrue($this->repository->add($log));
		$this->writtenIds[] = $log->getId();

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName('action_id'))
			->from($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('id') . ' = ' . (int) $log->getId());
		$db->setQuery($query);

		$this->assertEquals($expectedId, (int) $db->loadResult());
	}

	/**
	 * @covers \Tchooz\Repositories\Logs\LogRepository::add
	 */
	public function testAddRefusesAnEntryWithoutAnAuthor(): void
	{
		$log = new LogEntity(
			userFrom: 0,
			action: ActionEnum::FILE,
			crud: CrudEnum::UPDATE,
			message: 'COM_EMUNDUS_ACCESS_FILE_UPDATE',
			fnum: $this->dataset['fnum']
		);

		$this->assertFalse($this->repository->add($log));
		$this->assertEmpty($log->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Logs\LogRepository::addMany
	 */
	public function testAddManyWritesOneEntryPerLog(): void
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('fnum_to') . ' = ' . $db->quote($this->dataset['fnum']))
			->where($db->quoteName('message') . ' = ' . $db->quote('COM_EMUNDUS_LOGS_AUTOMATION_EXPORT'));
		$db->setQuery($query);
		$before = (int) $db->loadResult();

		$logs = [];
		for ($i = 0; $i < 2; $i++)
		{
			$logs[] = new LogEntity(
				userFrom: $this->dataset['coordinator'],
				action: ActionEnum::EXPORT,
				crud: CrudEnum::CREATE,
				message: 'COM_EMUNDUS_LOGS_AUTOMATION_EXPORT',
				fnum: $this->dataset['fnum']
			);
		}

		$this->assertTrue($this->repository->addMany($logs));

		$db->setQuery($query);
		$this->assertEquals($before + 2, (int) $db->loadResult());

		$this->deleteWrittenExports();
	}

	private function deleteWrittenExports(): void
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('fnum_to') . ' = ' . $db->quote($this->dataset['fnum']))
			->where($db->quoteName('message') . ' = ' . $db->quote('COM_EMUNDUS_LOGS_AUTOMATION_EXPORT'));
		$db->setQuery($query);
		$db->execute();
	}
}
