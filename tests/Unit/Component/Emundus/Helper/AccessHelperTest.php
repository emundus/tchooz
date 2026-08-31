<?php
/**
 * @package     Unit\Component\Emundus\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Helper;

use EmundusHelperAccess;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Repositories\Groups\GroupRepository;

require_once JPATH_BASE . '/components/com_emundus/helpers/access.php';

/**
 * Program management scope: outside of the all rights group, a user only manages the programs of their
 * own groups, and belonging to no program means managing nothing.
 *
 * Each scenario gets its own user on purpose: EmundusHelperAccess memoizes the scope for the whole
 * request, so a single user cannot be moved from one group to another between two assertions.
 *
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      EmundusHelperAccess
 */
class AccessHelperTest extends UnitTestCase
{
	private GroupRepository $groupRepository;

	private int $allRightsGroupId;

	private int $scopedGroupId = 0;

	private string $managedProgramCode;

	private string $unmanagedProgramCode;

	private int $allRightsManager = 0;

	private int $scopedManager = 0;

	private int $groupLessManager = 0;

	private array $createdProgramIds = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->groupRepository  = new GroupRepository();
		$this->allRightsGroupId = (int) ComponentHelper::getParams('com_emundus')->get('all_rights_group', 1);

		$this->managedProgramCode = $this->dataset['program']['programme_code'];

		$unmanagedProgram = $this->h_dataset->createSampleProgram(
			'Programme Test Unitaire Hors Perimetre ' . rand(0, 999999),
			$this->dataset['coordinator']
		);
		$this->unmanagedProgramCode = $unmanagedProgram['programme_code'];
		$this->createdProgramIds[]  = $unmanagedProgram['programme_id'];

		$scopedGroup = new GroupEntity(0, 'Groupe Test Unitaire Perimetre ' . rand(0, 999999), '', true, [], false, false, []);
		$this->groupRepository->flush($scopedGroup);
		$this->scopedGroupId = $scopedGroup->getId();
		$this->groupRepository->addProgram($this->scopedGroupId, $this->managedProgramCode);

		$this->allRightsManager = $this->h_dataset->createSampleUser(
			2, 'all_rights_manager_unit_test' . rand(0, 999999) . '@emundus.fr', 'test1234', [2, 7], 'Test', 'ALLRIGHTS', [$this->allRightsGroupId]
		);
		$this->scopedManager    = $this->h_dataset->createSampleUser(
			2, 'scoped_manager_unit_test' . rand(0, 999999) . '@emundus.fr', 'test1234', [2, 7], 'Test', 'SCOPED', [$this->scopedGroupId]
		);
		$this->groupLessManager = $this->h_dataset->createSampleUser(
			2, 'group_less_manager_unit_test' . rand(0, 999999) . '@emundus.fr', 'test1234', [2, 7], 'Test', 'NOGROUP', []
		);
	}

	protected function tearDown(): void
	{
		foreach ([$this->allRightsManager, $this->scopedManager, $this->groupLessManager] as $userId)
		{
			if (!empty($userId))
			{
				$this->h_dataset->deleteSampleUser($userId);
			}
		}

		if (!empty($this->scopedGroupId))
		{
			$this->groupRepository->delete($this->scopedGroupId);
		}

		foreach ($this->createdProgramIds as $programId)
		{
			$this->h_dataset->deleteSampleProgram($programId);
		}

		parent::tearDown();
	}

	// -------------------------------------------------------------------------
	// canManageAllPrograms
	// -------------------------------------------------------------------------

	/**
	 * @covers EmundusHelperAccess::canManageAllPrograms
	 * @return void
	 */
	public function testCanManageAllProgramsReturnsTrueWhenUserBelongsToTheAllRightsGroup(): void
	{
		$this->assertTrue(
			EmundusHelperAccess::canManageAllPrograms($this->allRightsManager),
			'A user belonging to the all rights group should manage every program'
		);
	}

	/**
	 * @covers EmundusHelperAccess::canManageAllPrograms
	 * @return void
	 */
	public function testCanManageAllProgramsReturnsFalseWhenUserOnlyBelongsToAProgramGroup(): void
	{
		$this->assertFalse(
			EmundusHelperAccess::canManageAllPrograms($this->scopedManager),
			'A user belonging only to a program group should not manage every program'
		);
	}

	/**
	 * @covers EmundusHelperAccess::canManageAllPrograms
	 * @return void
	 */
	public function testCanManageAllProgramsReturnsFalseWhenUserHasNoGroup(): void
	{
		$this->assertFalse(
			EmundusHelperAccess::canManageAllPrograms($this->groupLessManager),
			'A user belonging to no group at all should not manage every program'
		);
	}

	// -------------------------------------------------------------------------
	// canManageProgram — all rights group
	// -------------------------------------------------------------------------

	/**
	 * @covers EmundusHelperAccess::canManageProgram
	 * @return void
	 */
	public function testCanManageProgramReturnsTrueForAnyProgramWhenUserBelongsToTheAllRightsGroup(): void
	{
		$this->assertTrue(
			EmundusHelperAccess::canManageProgram($this->allRightsManager, $this->managedProgramCode),
			'A user of the all rights group should manage a program associated to their groups'
		);
		$this->assertTrue(
			EmundusHelperAccess::canManageProgram($this->allRightsManager, $this->unmanagedProgramCode),
			'A user of the all rights group should manage a program even without a dedicated group for it'
		);
	}

	/**
	 * Pins the asymmetry with a scoped user: an entity carrying no program is manageable by the all
	 * rights group only.
	 *
	 * @covers EmundusHelperAccess::canManageProgram
	 * @return void
	 */
	public function testCanManageProgramReturnsTrueWithoutProgramCodeWhenUserBelongsToTheAllRightsGroup(): void
	{
		$this->assertTrue(
			EmundusHelperAccess::canManageProgram($this->allRightsManager, null),
			'A user of the all rights group should manage an entity attached to no program'
		);
	}

	// -------------------------------------------------------------------------
	// canManageProgram — program scoped user
	// -------------------------------------------------------------------------

	/**
	 * @covers EmundusHelperAccess::canManageProgram
	 * @return void
	 */
	public function testCanManageProgramReturnsTrueForTheProgramOfTheUserGroup(): void
	{
		$this->assertTrue(
			EmundusHelperAccess::canManageProgram($this->scopedManager, $this->managedProgramCode),
			'A program manager should manage the program associated to their group'
		);
	}

	/**
	 * @covers EmundusHelperAccess::canManageProgram
	 * @return void
	 */
	public function testCanManageProgramReturnsFalseForAProgramOutsideTheUserGroups(): void
	{
		$this->assertFalse(
			EmundusHelperAccess::canManageProgram($this->scopedManager, $this->unmanagedProgramCode),
			'A program manager should not manage a program no group of theirs is associated to'
		);
	}

	/**
	 * @covers EmundusHelperAccess::canManageProgram
	 * @return void
	 */
	public function testCanManageProgramReturnsFalseWithoutProgramCodeForAScopedUser(): void
	{
		$this->assertFalse(
			EmundusHelperAccess::canManageProgram($this->scopedManager, null),
			'A program manager should not manage an entity attached to no program'
		);
		$this->assertFalse(
			EmundusHelperAccess::canManageProgram($this->scopedManager, ''),
			'An empty program code should be refused the same way as a missing one'
		);
	}

	// -------------------------------------------------------------------------
	// canManageProgram — no program is not every program
	// -------------------------------------------------------------------------

	/**
	 * Guards the semantics: an empty scope must not be read as an unrestricted one.
	 *
	 * @covers EmundusHelperAccess::canManageProgram
	 * @return void
	 */
	public function testCanManageProgramReturnsFalseWhenUserHasNoGroup(): void
	{
		$this->assertFalse(
			EmundusHelperAccess::canManageProgram($this->groupLessManager, $this->managedProgramCode),
			'A user belonging to no group manages nothing, having no program is not having every program'
		);
		$this->assertFalse(
			EmundusHelperAccess::canManageProgram($this->groupLessManager, $this->unmanagedProgramCode),
			'A user belonging to no group manages no program at all'
		);
	}

	/**
	 * @covers EmundusHelperAccess::canManageProgram
	 * @return void
	 */
	public function testCanManageProgramReturnsFalseForAnUnknownProgramCode(): void
	{
		$this->assertFalse(
			EmundusHelperAccess::canManageProgram($this->scopedManager, 'CODE_THAT_DOES_NOT_EXIST'),
			'An unknown program code should never be manageable by a scoped user'
		);
	}
}
