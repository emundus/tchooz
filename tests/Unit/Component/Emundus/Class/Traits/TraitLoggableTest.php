<?php

namespace Unit\Component\Emundus\Class\Traits;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionAnonymize;
use Tchooz\Enums\Actions\ActionEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Traits\TraitLoggable;

/**
 * @package     Unit\Component\Emundus\Class\Traits
 *
 * @covers      \Tchooz\Traits\TraitLoggable
 */
class TraitLoggableTest extends UnitTestCase
{
	private const MESSAGE = 'COM_EMUNDUS_LOGS_AUTOMATION_ANONYMIZE';

	protected function tearDown(): void
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->delete($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('fnum_to') . ' = ' . $db->quote($this->dataset['fnum']))
			->where($db->quoteName('message') . ' = ' . $db->quote(self::MESSAGE));
		$db->setQuery($query);
		$db->execute();

		parent::tearDown();
	}

	/**
	 * @covers \Tchooz\Traits\TraitLoggable::log
	 */
	public function testLogMergesTheClassContextIntoTheParams(): void
	{
		$loggable = new class {
			use TraitLoggable;

			public function write(string $fnum, int $userFrom): bool
			{
				return $this->log(
					ActionEnum::FILE,
					CrudEnum::UPDATE,
					'COM_EMUNDUS_LOGS_AUTOMATION_ANONYMIZE',
					['updated' => [['new' => 'declared by the caller']]],
					$fnum,
					$userFrom
				);
			}

			protected function getLogContext(): array
			{
				return ['origin' => 'unit-test'];
			}
		};

		$this->assertTrue($loggable->write($this->dataset['fnum'], $this->dataset['coordinator']));

		$params = $this->loadLastParams();

		$this->assertEquals('unit-test', $params['origin'], 'The class context should be merged into the params.');
		$this->assertEquals('declared by the caller', $params['updated'][0]['new'], 'The caller params should survive the merge.');
	}

	/**
	 * The context must never silently replace what the caller declared.
	 *
	 * @covers \Tchooz\Traits\TraitLoggable::log
	 */
	public function testCallerParamsWinOverTheClassContext(): void
	{
		$loggable = new class {
			use TraitLoggable;

			public function write(string $fnum, int $userFrom): bool
			{
				return $this->log(
					ActionEnum::FILE,
					CrudEnum::UPDATE,
					'COM_EMUNDUS_LOGS_AUTOMATION_ANONYMIZE',
					['origin' => 'caller'],
					$fnum,
					$userFrom
				);
			}

			protected function getLogContext(): array
			{
				return ['origin' => 'context'];
			}
		};

		$this->assertTrue($loggable->write($this->dataset['fnum'], $this->dataset['coordinator']));
		$this->assertEquals('caller', $this->loadLastParams()['origin']);
	}

	/**
	 * Every automation action inherits the context, so no individual action has to remember it.
	 *
	 * @covers \Tchooz\Entities\Automation\ActionEntity::getLogContext
	 */
	public function testAnActionStampsItsTypeOnEveryEntry(): void
	{
		$action = new ActionAnonymize();

		$method = new \ReflectionMethod($action, 'getLogContext');
		$method->setAccessible(true);

		$this->assertEquals(ActionAnonymize::getType(), $method->invoke($action)['action_type']);
	}

	private function loadLastParams(): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select($db->quoteName('params'))
			->from($db->quoteName('#__emundus_logs'))
			->where($db->quoteName('fnum_to') . ' = ' . $db->quote($this->dataset['fnum']))
			->where($db->quoteName('message') . ' = ' . $db->quote(self::MESSAGE))
			->order($db->quoteName('id') . ' DESC');
		$db->setQuery($query, 0, 1);

		return json_decode($db->loadResult() ?? '', true) ?? [];
	}
}
