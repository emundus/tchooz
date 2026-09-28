<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionApiMap;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Mapping\MappingEntity;
use Tchooz\Repositories\Synchronizer\SynchronizerRepository;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Automation\Actions
 *
 * @covers      \Tchooz\Entities\Automation\Actions\ActionApiMap
 */
class ActionApiMapTest extends UnitTestCase
{
	private const MESSAGE = 'COM_EMUNDUS_LOGS_AUTOMATION_API_MAP';

	private const SYNCHRONIZER_ID = 1;

	protected function tearDown(): void
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('user_id_to') . ' = ' . (int) $this->dataset['applicant'])
			->where($db->quoteName('message') . ' = ' . $db->quote(self::MESSAGE));
		$db->setQuery($query);
		$db->execute();

		parent::tearDown();
	}

	/**
	 * A target without a file (user event) is logged too, addressed to its user.
	 *
	 * @covers \Tchooz\Entities\Automation\Actions\ActionApiMap::logSent
	 */
	public function testEverySentTargetIsLoggedWithItsSynchronizer(): void
	{
		$synchronizer = (new SynchronizerRepository())->getById(self::SYNCHRONIZER_ID);
		$this->assertNotEmpty($synchronizer, 'A synchronizer should be seeded with id ' . self::SYNCHRONIZER_ID . '.');

		$coord   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$targets = [
			new ActionTargetEntity($coord, $this->dataset['fnum'], $this->dataset['applicant']),
			new ActionTargetEntity($coord, null, $this->dataset['applicant']),
		];

		$method = new \ReflectionMethod(ActionApiMap::class, 'logSent');
		$method->setAccessible(true);
		$method->invoke(new ActionApiMap(), new MappingEntity(0, 'Unit mapping', self::SYNCHRONIZER_ID, 'contact'), $targets);

		$logs = $this->loadWrittenLogs();

		$this->assertCount(2, $logs);
		$this->assertEquals([$this->dataset['fnum'], ''], array_column($logs, 'fnum_to'));

		foreach ($logs as $log)
		{
			$created = json_decode($log['params'], true)['created'][0];
			$this->assertEquals($synchronizer->getName(), $created['element']);
			$this->assertEquals('Unit mapping', $created['details']);
		}
	}

	private function loadWrittenLogs(): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName(['fnum_to', 'params']))
			->from($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('user_id_to') . ' = ' . (int) $this->dataset['applicant'])
			->where($db->quoteName('message') . ' = ' . $db->quote(self::MESSAGE))
			->order($db->quoteName('id') . ' ASC');
		$db->setQuery($query);

		return $db->loadAssocList();
	}
}
