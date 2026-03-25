<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories\Groups
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Groups;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\ApplicationFile\StatusEntity;
use Tchooz\Entities\Groups\GroupEntity;
use Tchooz\Entities\Programs\ProgramEntity;
use Tchooz\Factories\Groups\GroupFactory;
use Tchooz\Repositories\Groups\GroupRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Groups
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Groups\GroupRepository
 */
class GroupRepositoryTest extends UnitTestCase
{
	private Registry $config;
	private GroupRepository $repository;
	private array $createdGroupIds = [];

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('cache_handler', 'file');
		$this->config->set('caching', 1);

		$this->repository = new GroupRepository(false);
	}

	protected function tearDown(): void
	{
		foreach ($this->createdGroupIds as $id) {
			try {
				$this->repository->delete($id);
			} catch (\Exception $e) {
				// Silently ignore if already deleted by the test
			}
		}

		parent::tearDown();
	}

	private function createGroupEntity(string $label = '', array $programs = [], array $statuses = [], array $visibleGroups = [], array $visibleAttachments = []): GroupEntity
	{
		if (empty($label)) {
			$label = 'Test Group ' . uniqid();
		}

		return new GroupEntity(
			0,
			$label,
			'Description for ' . $label,
			true,
			$programs,
			false,
			false,
			$statuses,
			$visibleGroups,
			$visibleAttachments,
			'label-blue-2'
		);
	}

	private function getExistingProgramCode(): ?string
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('code')
			->from($db->quoteName('#__emundus_setup_programmes'))
			->where($db->quoteName('published') . ' = 1')
			->setLimit(1);
		$db->setQuery($query);

		return $db->loadResult() ?: null;
	}

	private function getExistingStatusStep(): ?int
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('step')
			->from($db->quoteName('#__emundus_setup_status'))
			->setLimit(1);
		$db->setQuery($query);
		$result = $db->loadResult();

		return $result !== null ? (int) $result : null;
	}

	/**
	 * @return int[] Existing fabrik group IDs (up to $count)
	 */
	private function getExistingFabrikGroupIds(int $count = 2): array
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__fabrik_groups'))
			->setLimit($count);
		$db->setQuery($query);

		return array_map('intval', $db->loadColumn() ?: []);
	}

	/**
	 * @return int[] Existing attachment IDs (up to $count)
	 */
	private function getExistingAttachmentIds(int $count = 2): array
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('id')
			->from($db->quoteName('#__emundus_setup_attachments'))
			->setLimit($count);
		$db->setQuery($query);

		return array_map('intval', $db->loadColumn() ?: []);
	}

	// =====================
	// flush — insert tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::flush
	 */
	public function testFlushInsertCreatesGroup(): void
	{
		$group = $this->createGroupEntity();

		$result = $this->repository->flush($group);

		$this->assertTrue($result);
		$this->assertGreaterThan(0, $group->getId());
		$this->createdGroupIds[] = $group->getId();
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::flush
	 */
	public function testFlushInsertPersistsAllProperties(): void
	{
		$group = new GroupEntity(
			0,
			'Persist Test ' . uniqid(),
			'A detailed description',
			true,
			[],
			true,
			true,
			[],
			[],
			[],
			'label-red-1'
		);

		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$fetched = $this->repository->getById($group->getId());

		$this->assertNotNull($fetched);
		$this->assertEquals($group->getLabel(), $fetched->getLabel());
		$this->assertEquals('A detailed description', $fetched->getDescription());
		$this->assertTrue($fetched->isPublished());
		$this->assertTrue($fetched->isAnonymize());
		$this->assertTrue($fetched->isFilterStatus());
		$this->assertEquals('label-red-1', $fetched->getClass());
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::flush
	 */
	public function testFlushInsertWithEmptyLabelThrowsException(): void
	{
		$group = $this->createGroupEntity('');
		$group->setLabel('');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Label is required');

		$this->repository->flush($group);
	}

	// =====================
	// flush — update tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::flush
	 */
	public function testFlushUpdateModifiesProperties(): void
	{
		$group = $this->createGroupEntity();
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$group->setLabel('Updated Label');
		$group->setDescription('Updated description');
		$group->setPublished(false);
		$group->setAnonymize(true);
		$group->setFilterStatus(true);
		$group->setClass('label-green-3');

		$result = $this->repository->flush($group);
		$this->assertTrue($result);

		$fetched = $this->repository->getById($group->getId());

		$this->assertEquals('Updated Label', $fetched->getLabel());
		$this->assertEquals('Updated description', $fetched->getDescription());
		$this->assertFalse($fetched->isPublished());
		$this->assertTrue($fetched->isAnonymize());
		$this->assertTrue($fetched->isFilterStatus());
		$this->assertEquals('label-green-3', $fetched->getClass());
	}

	// =====================
	// getById tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::getById
	 */
	public function testGetByIdReturnsGroupEntity(): void
	{
		$group = $this->createGroupEntity();
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$fetched = $this->repository->getById($group->getId());

		$this->assertNotNull($fetched);
		$this->assertInstanceOf(GroupEntity::class, $fetched);
		$this->assertEquals($group->getId(), $fetched->getId());
		$this->assertEquals($group->getLabel(), $fetched->getLabel());
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::getById
	 */
	public function testGetByIdReturnsNullForNonExistent(): void
	{
		$result = $this->repository->getById(999999);

		$this->assertNull($result);
	}

	// =====================
	// getFactory tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::getFactory
	 */
	public function testGetFactoryReturnsGroupFactory(): void
	{
		$factory = $this->repository->getFactory();

		$this->assertNotNull($factory);
		$this->assertInstanceOf(GroupFactory::class, $factory);
	}

	// =====================
	// delete tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::delete
	 */
	public function testDeleteRemovesGroup(): void
	{
		$group = $this->createGroupEntity();
		$this->repository->flush($group);
		$groupId = $group->getId();

		$deleted = $this->repository->delete($groupId);
		$this->assertTrue($deleted);

		$fetched = $this->repository->getById($groupId);
		$this->assertNull($fetched);
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::delete
	 */
	public function testDeleteCleansUpRelatedTables(): void
	{
		$programCode = $this->getExistingProgramCode();
		$fabrikGroupIds = $this->getExistingFabrikGroupIds(1);
		$attachmentIds = $this->getExistingAttachmentIds(1);

		$group = $this->createGroupEntity();
		$this->repository->flush($group);
		$groupId = $group->getId();

		$db = Factory::getContainer()->get('DatabaseDriver');

		// Insert some related data using real FK values
		if (!empty($programCode)) {
			$data = (object) ['parent_id' => $groupId, 'course' => $programCode];
			$db->insertObject('#__emundus_setup_groups_repeat_course', $data);
		}
		$data = (object) ['parent_id' => $groupId, 'status' => 1];
		$db->insertObject('#__emundus_setup_groups_repeat_status', $data);

		if (!empty($fabrikGroupIds)) {
			$data = (object) ['parent_id' => $groupId, 'fabrik_group_link' => $fabrikGroupIds[0]];
			$db->insertObject('#__emundus_setup_groups_repeat_fabrik_group_link', $data);
		}
		if (!empty($attachmentIds)) {
			$data = (object) ['parent_id' => $groupId, 'attachment_id_link' => $attachmentIds[0]];
			$db->insertObject('#__emundus_setup_groups_repeat_attachment_id_link', $data);
		}

		$this->repository->delete($groupId);

		$tables = [
			'#__emundus_setup_groups_repeat_course',
			'#__emundus_setup_groups_repeat_status',
			'#__emundus_setup_groups_repeat_fabrik_group_link',
			'#__emundus_setup_groups_repeat_attachment_id_link',
		];

		foreach ($tables as $table) {
			$query = $db->getQuery(true)
				->select('COUNT(*)')
				->from($db->quoteName($table))
				->where($db->quoteName('parent_id') . ' = ' . $groupId);
			$db->setQuery($query);
			$count = (int) $db->loadResult();

			$this->assertEquals(0, $count, "Table $table should have no rows left for group $groupId");
		}
	}

	// =====================
	// saveGroupPrograms tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::saveGroupPrograms
	 */
	public function testFlushSavesPrograms(): void
	{
		$programCode = $this->getExistingProgramCode();
		if ($programCode === null) {
			$this->markTestSkipped('No published program found in the database');
		}

		$program = new ProgramEntity($programCode, 'Test Program');
		$group = $this->createGroupEntity('', [$program]);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('course')
			->from($db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$courses = $db->loadColumn();

		$this->assertContains($programCode, $courses);
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::saveGroupPrograms
	 */
	public function testSaveGroupProgramsRemovesOldPrograms(): void
	{
		$programCode = $this->getExistingProgramCode();
		if ($programCode === null) {
			$this->markTestSkipped('No published program found in the database');
		}

		$program = new ProgramEntity($programCode, 'Test Program');
		$group = $this->createGroupEntity('', [$program]);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		// Remove the program
		$group->setPrograms([]);
		$this->repository->saveGroupPrograms($group);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$count = (int) $db->loadResult();

		$this->assertEquals(0, $count);
	}

	// =====================
	// saveGroupStatuses tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::saveGroupStatuses
	 */
	public function testFlushSavesStatuses(): void
	{
		$step = $this->getExistingStatusStep();
		if ($step === null) {
			$this->markTestSkipped('No status found in the database');
		}

		$status = new StatusEntity(0, $step, 'Test Status', 0, '#000000');
		$group = $this->createGroupEntity('', [], [$status]);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('status')
			->from($db->quoteName('#__emundus_setup_groups_repeat_status'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$statuses = $db->loadColumn();

		$this->assertContains($step, $statuses);
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::saveGroupStatuses
	 */
	public function testSaveGroupStatusesRemovesOldStatuses(): void
	{
		$step = $this->getExistingStatusStep();
		if ($step === null) {
			$this->markTestSkipped('No status found in the database');
		}

		$status = new StatusEntity(0, $step, 'Test Status', 0, '#000000');
		$group = $this->createGroupEntity('', [], [$status]);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$group->setStatuses([]);
		$this->repository->saveGroupStatuses($group);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__emundus_setup_groups_repeat_status'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$count = (int) $db->loadResult();

		$this->assertEquals(0, $count);
	}

	// =====================
	// saveGroupVisibleGroups tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::saveGroupVisibleGroups
	 */
	public function testFlushSavesVisibleGroups(): void
	{
		$fabrikGroupIds = $this->getExistingFabrikGroupIds(2);
		if (count($fabrikGroupIds) < 2) {
			$this->markTestSkipped('Not enough fabrik groups found in the database');
		}

		$group = $this->createGroupEntity('', [], [], $fabrikGroupIds);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('fabrik_group_link')
			->from($db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$visibleGroups = array_map('intval', $db->loadColumn());

		$this->assertContains($fabrikGroupIds[0], $visibleGroups);
		$this->assertContains($fabrikGroupIds[1], $visibleGroups);
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::saveGroupVisibleGroups
	 */
	public function testSaveGroupVisibleGroupsRemovesOld(): void
	{
		$fabrikGroupIds = $this->getExistingFabrikGroupIds(3);
		if (count($fabrikGroupIds) < 3) {
			$this->markTestSkipped('Not enough fabrik groups found in the database (need 3)');
		}

		$group = $this->createGroupEntity('', [], [], [$fabrikGroupIds[0], $fabrikGroupIds[1]]);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$group->setVisibleGroups([$fabrikGroupIds[2]]);
		$this->repository->saveGroupVisibleGroups($group);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('fabrik_group_link')
			->from($db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$visibleGroups = array_map('intval', $db->loadColumn());

		$this->assertNotContains($fabrikGroupIds[0], $visibleGroups);
		$this->assertNotContains($fabrikGroupIds[1], $visibleGroups);
		$this->assertContains($fabrikGroupIds[2], $visibleGroups);
	}

	// =====================
	// saveGroupVisibleAttachments tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::saveGroupVisibleAttachments
	 */
	public function testFlushSavesVisibleAttachments(): void
	{
		$attachmentIds = $this->getExistingAttachmentIds(2);
		if (count($attachmentIds) < 2) {
			$this->markTestSkipped('Not enough attachments found in the database');
		}

		$group = $this->createGroupEntity('', [], [], [], $attachmentIds);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('attachment_id_link')
			->from($db->quoteName('#__emundus_setup_groups_repeat_attachment_id_link'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$attachments = array_map('intval', $db->loadColumn());

		$this->assertContains($attachmentIds[0], $attachments);
		$this->assertContains($attachmentIds[1], $attachments);
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::saveGroupVisibleAttachments
	 */
	public function testSaveGroupVisibleAttachmentsRemovesOld(): void
	{
		$attachmentIds = $this->getExistingAttachmentIds(3);
		if (count($attachmentIds) < 3) {
			$this->markTestSkipped('Not enough attachments found in the database (need 3)');
		}

		$group = $this->createGroupEntity('', [], [], [], [$attachmentIds[0], $attachmentIds[1]]);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$group->setVisibleAttachments([$attachmentIds[2]]);
		$this->repository->saveGroupVisibleAttachments($group);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('attachment_id_link')
			->from($db->quoteName('#__emundus_setup_groups_repeat_attachment_id_link'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$attachments = array_map('intval', $db->loadColumn());

		$this->assertNotContains($attachmentIds[0], $attachments);
		$this->assertNotContains($attachmentIds[1], $attachments);
		$this->assertContains($attachmentIds[2], $attachments);
	}

	// =====================
	// addProgram tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::addProgram
	 */
	public function testAddProgramInsertsRow(): void
	{
		$programCode = $this->getExistingProgramCode();
		if ($programCode === null) {
			$this->markTestSkipped('No published program found in the database');
		}

		$group = $this->createGroupEntity();
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$this->repository->addProgram($group->getId(), $programCode);

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('course')
			->from($db->quoteName('#__emundus_setup_groups_repeat_course'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$courses = $db->loadColumn();

		$this->assertContains($programCode, $courses);
	}

	// =====================
	// checkGroupAssociated tests
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::checkGroupAssociated
	 */
	public function testCheckGroupAssociatedReturnsTrueWhenAssociated(): void
	{
		$programCode = $this->getExistingProgramCode();
		if ($programCode === null) {
			$this->markTestSkipped('No published program found in the database');
		}

		$group = $this->createGroupEntity();
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$this->repository->addProgram($group->getId(), $programCode);

		$result = $this->repository->checkGroupAssociated($group->getId(), $programCode);

		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::checkGroupAssociated
	 */
	public function testCheckGroupAssociatedReturnsFalseWhenNotAssociated(): void
	{
		$group = $this->createGroupEntity();
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$result = $this->repository->checkGroupAssociated($group->getId(), 'NON_EXISTENT_PROG');

		$this->assertFalse($result);
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::checkGroupAssociated
	 */
	public function testCheckGroupAssociatedReturnsFalseForNonExistentGroup(): void
	{
		$result = $this->repository->checkGroupAssociated(999999, 'ANY_PROG');

		$this->assertFalse($result);
	}

	// =====================
	// flush — full lifecycle test
	// =====================

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::flush
	 */
	public function testFlushFullLifecycleInsertUpdateDelete(): void
	{
		// 1. Insert
		$group = $this->createGroupEntity('Lifecycle Test ' . uniqid());
		$this->repository->flush($group);
		$this->assertGreaterThan(0, $group->getId());
		$this->createdGroupIds[] = $group->getId();

		// 2. Verify insert
		$fetched = $this->repository->getById($group->getId());
		$this->assertNotNull($fetched);
		$this->assertEquals($group->getLabel(), $fetched->getLabel());

		// 3. Update
		$fetched->setLabel('Updated Lifecycle');
		$fetched->setDescription('Updated description');
		$this->repository->flush($fetched);

		// 4. Verify update
		$updated = $this->repository->getById($fetched->getId());
		$this->assertEquals('Updated Lifecycle', $updated->getLabel());
		$this->assertEquals('Updated description', $updated->getDescription());

		// 5. Delete
		$deleted = $this->repository->delete($fetched->getId());
		$this->assertTrue($deleted);

		// 6. Verify delete
		$gone = $this->repository->getById($fetched->getId());
		$this->assertNull($gone);

		// Remove from cleanup list since already deleted
		$this->createdGroupIds = array_diff($this->createdGroupIds, [$fetched->getId()]);
	}

	/**
	 * @covers \Tchooz\Repositories\Groups\GroupRepository::flush
	 */
	public function testFlushUpdateWithRelationsSync(): void
	{
		$programCode = $this->getExistingProgramCode();
		if ($programCode === null) {
			$this->markTestSkipped('No published program found in the database');
		}

		$fabrikGroupIds = $this->getExistingFabrikGroupIds(1);
		if (empty($fabrikGroupIds)) {
			$this->markTestSkipped('No fabrik group found in the database');
		}

		$attachmentIds = $this->getExistingAttachmentIds(1);
		if (empty($attachmentIds)) {
			$this->markTestSkipped('No attachment found in the database');
		}

		// Insert with one program and visible groups/attachments
		$program = new ProgramEntity($programCode, 'Test Program');
		$group = $this->createGroupEntity('Relations Sync ' . uniqid(), [$program], [], $fabrikGroupIds, $attachmentIds);
		$this->repository->flush($group);
		$this->createdGroupIds[] = $group->getId();

		$db = Factory::getContainer()->get('DatabaseDriver');

		// Verify program is saved
		$this->assertTrue($this->repository->checkGroupAssociated($group->getId(), $programCode));

		// Verify visible groups
		$query = $db->getQuery(true)
			->select('fabrik_group_link')
			->from($db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$this->assertContains($fabrikGroupIds[0], $db->loadColumn());

		// Verify visible attachments
		$query = $db->getQuery(true)
			->select('attachment_id_link')
			->from($db->quoteName('#__emundus_setup_groups_repeat_attachment_id_link'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$this->assertContains($attachmentIds[0], $db->loadColumn());

		// Update: remove all relations
		$group->setPrograms([]);
		$group->setVisibleGroups([]);
		$group->setVisibleAttachments([]);
		$this->repository->flush($group);

		// Verify all cleaned
		$this->assertFalse($this->repository->checkGroupAssociated($group->getId(), $programCode));

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__emundus_setup_groups_repeat_fabrik_group_link'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$this->assertEquals(0, (int) $db->loadResult());

		$query = $db->getQuery(true)
			->select('COUNT(*)')
			->from($db->quoteName('#__emundus_setup_groups_repeat_attachment_id_link'))
			->where('parent_id = ' . $group->getId());
		$db->setQuery($query);
		$this->assertEquals(0, (int) $db->loadResult());
	}
}