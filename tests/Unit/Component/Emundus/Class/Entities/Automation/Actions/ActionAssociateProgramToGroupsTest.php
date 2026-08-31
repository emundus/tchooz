<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\EventsDefinitions\onAfterProgramCreateDefinition;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Repositories\Groups\GroupRepository;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Automation\Actions
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups
 */
class ActionAssociateProgramToGroupsTest extends UnitTestCase
{
	private GroupRepository $groupRepository;
	private array $createdGroupIds = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->groupRepository = new GroupRepository(false);
	}

	protected function tearDown(): void
	{
		foreach ($this->createdGroupIds as $id)
		{
			try
			{
				$this->groupRepository->delete($id);
			}
			catch (\Exception $e)
			{
				// Silently ignore if already deleted by the test
			}
		}

		parent::tearDown();
	}

	/**
	 * Creates a published group and returns its id, tracked for cleanup.
	 */
	private function createGroup(): int
	{
		$group = new GroupEntity(
			0,
			'Automation Group ' . uniqid(),
			'Group created for automation test',
			true,
			[],
			false,
			false,
			[],
			[],
			[],
			'label-blue-2'
		);

		$this->groupRepository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		return $group->getId();
	}

	/**
	 * Builds an ActionTargetEntity carrying the program code, as onAfterProgramCreate would.
	 */
	private function createProgramContext(?string $programCode): ActionTargetEntity
	{
		$coord      = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$parameters = $programCode !== null ? [onAfterProgramCreateDefinition::PROGRAM_CODE_KEY => $programCode] : [];

		return new ActionTargetEntity($coord, null, 0, $parameters);
	}

	// -------------------------------------------------------------------------
	// execute — happy path
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups::execute
	 * @return void
	 */
	public function testExecuteAssociatesProgramToGroup(): void
	{
		$programCode = $this->dataset['program']['programme_code'];
		$groupId     = $this->createGroup();

		$context = $this->createProgramContext($programCode);
		$action  = new ActionAssociateProgramToGroups([ActionAssociateProgramToGroups::PARAMETER_GROUPS => [$groupId]]);

		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result, 'Execution should complete when a valid group and program are provided');
		$this->assertTrue($this->groupRepository->checkGroupAssociated($groupId, $programCode), 'The program should be linked to the group after execution');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups::execute
	 * @return void
	 */
	public function testExecuteDoesNotDuplicateWhenAlreadyAssociated(): void
	{
		$programCode = $this->dataset['program']['programme_code'];
		$groupId     = $this->createGroup();

		// Pre-associate the program so the re-run must not create a duplicate row.
		$this->groupRepository->addProgram($groupId, $programCode);

		$context = $this->createProgramContext($programCode);
		$action  = new ActionAssociateProgramToGroups([ActionAssociateProgramToGroups::PARAMETER_GROUPS => [$groupId]]);

		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result, 'Execution should complete even when the program is already associated');

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where('parent_id = ' . $groupId)
			->where($db->quoteName('course') . ' = ' . $db->quote($programCode));
		$db->setQuery($query);

		$this->assertEquals(1, (int) $db->loadResult(), 'The link row must not be duplicated on a re-run');
	}

	// -------------------------------------------------------------------------
	// execute — failure paths
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups::execute
	 * @return void
	 */
	public function testExecuteReturnsFailedWhenProgramCodeMissing(): void
	{
		$groupId = $this->createGroup();

		$context = $this->createProgramContext(null);
		$action  = new ActionAssociateProgramToGroups([ActionAssociateProgramToGroups::PARAMETER_GROUPS => [$groupId]]);

		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::FAILED, $result, 'Execution should fail when the context carries no program code');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups::execute
	 * @return void
	 */
	public function testExecuteReturnsFailedWhenNoGroupSelected(): void
	{
		$programCode = $this->dataset['program']['programme_code'];

		$context = $this->createProgramContext($programCode);
		$action  = new ActionAssociateProgramToGroups([ActionAssociateProgramToGroups::PARAMETER_GROUPS => []]);

		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::FAILED, $result, 'Execution should fail when no group is selected');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups::execute
	 * @return void
	 */
	public function testExecuteThrowsWhenGroupValueInvalid(): void
	{
		$programCode = $this->dataset['program']['programme_code'];

		$context = $this->createProgramContext($programCode);
		$action  = new ActionAssociateProgramToGroups([ActionAssociateProgramToGroups::PARAMETER_GROUPS => [999999]]);

		$this->expectException(\InvalidArgumentException::class);

		$action->execute($context);
	}

	// -------------------------------------------------------------------------
	// getGroupsOptions
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups::getGroupsOptions
	 * @return void
	 */
	public function testGetGroupsOptionsExcludesAllRightsGroup(): void
	{
		$groupId      = $this->createGroup();
		$allRightsGrp = ComponentHelper::getParams('com_emundus')->get('all_rights_group', 1);

		$action  = new ActionAssociateProgramToGroups();
		$options = $action->getGroupsOptions();

		$values = array_map(fn($option) => (int)$option->getValue(), $options);

		$this->assertNotContains($allRightsGrp, $values, 'The all-rights group must be excluded from the options');
		$this->assertContains($groupId, $values, 'A published group must be present in the options');
	}

	// -------------------------------------------------------------------------
	// getLabelForLog
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionAssociateProgramToGroups::getLabelForLog
	 * @return void
	 */
	public function testGetLabelForLogAppendsSelectedGroupLabels(): void
	{
		$groupId    = $this->createGroup();
		$groupLabel = $this->groupRepository->getById($groupId)->getLabel();

		$action = new ActionAssociateProgramToGroups([ActionAssociateProgramToGroups::PARAMETER_GROUPS => [$groupId]]);

		$labelForLog = $action->getLabelForLog();

		$this->assertStringContainsString($groupLabel, $labelForLog, 'The log label should include the selected group label');
	}
}
