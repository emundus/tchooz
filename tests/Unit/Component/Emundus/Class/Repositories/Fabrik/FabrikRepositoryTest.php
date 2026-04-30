<?php

namespace Unit\Component\Emundus\Class\Repositories\Fabrik;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Factories\Fabrik\FabrikFactory;
use Tchooz\Repositories\Fabrik\FabrikRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Fabrik
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Fabrik\FabrikRepository
 */
class FabrikRepositoryTest extends UnitTestCase
{
	private ?FabrikRepository $repository;

	private ?FabrikFactory $factory;

	private User $coordinator;

	private array $createdFormIds    = [];
	private array $createdListIds    = [];
	private array $createdGroupIds   = [];
	private array $createdElementIds = [];

	public function setUp(): void
	{
		parent::setUp();

		$this->coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		$this->repository = new FabrikRepository(true, $this->coordinator);
		$this->factory = new FabrikFactory($this->repository);
		$this->repository->setFactory($this->factory);

	}

	protected function tearDown(): void
	{
		// Remove joins/jsactions for duplicated elements
		if (!empty($this->createdElementIds))
		{
			$ids   = implode(',', array_map('intval', $this->createdElementIds));
			$query = $this->db->getQuery(true)->delete('#__fabrik_joins')->where('element_id IN (' . $ids . ')');
			$this->db->setQuery($query);
			$this->db->execute();
			$query = $this->db->getQuery(true)->delete('#__fabrik_jsactions')->where('element_id IN (' . $ids . ')');
			$this->db->setQuery($query);
			$this->db->execute();
		}

		foreach ($this->createdElementIds as $id)
		{
			$query = $this->db->getQuery(true)->delete('#__fabrik_elements')->where('id = ' . (int) $id);
			$this->db->setQuery($query);
			$this->db->execute();
		}

		// Remove joins created for is_join groups
		if (!empty($this->createdGroupIds))
		{
			$ids   = implode(',', array_map('intval', $this->createdGroupIds));
			$query = $this->db->getQuery(true)->delete('#__fabrik_joins')->where('group_id IN (' . $ids . ')');
			$this->db->setQuery($query);
			$this->db->execute();
		}

		foreach ($this->createdGroupIds as $id)
		{
			$query = $this->db->getQuery(true)->delete('#__fabrik_groups')->where('id = ' . (int) $id);
			$this->db->setQuery($query);
			$this->db->execute();
			$query = $this->db->getQuery(true)->delete('#__fabrik_formgroup')->where('group_id = ' . (int) $id);
			$this->db->setQuery($query);
			$this->db->execute();
		}

		foreach ($this->createdListIds as $id)
		{
			$query = $this->db->getQuery(true)->delete('#__fabrik_lists')->where('id = ' . (int) $id);
			$this->db->setQuery($query);
			$this->db->execute();
		}

		foreach ($this->createdFormIds as $id)
		{
			$query = $this->db->getQuery(true)->delete('#__fabrik_forms')->where('id = ' . (int) $id);
			$this->db->setQuery($query);
			$this->db->execute();
			$query = $this->db->getQuery(true)->delete('#__emundus_setup_formlist')->where('form_id = ' . (int) $id);
			$this->db->setQuery($query);
			$this->db->execute();
			$query = $this->db->getQuery(true)->delete('#__emundus_setup_form_rules')->where('form_id = ' . (int) $id);
			$this->db->setQuery($query);
			$this->db->execute();
		}

		parent::tearDown();
	}

	// --- DB helpers (bypass repository to avoid filter side-effects) ---

	private function loadRawFormFromDb(int $formId): ?object
	{
		$query = $this->db->getQuery(true)
			->select(['ff.*', 'fl.id AS list_id', 'fl.db_table_name'])
			->from($this->db->quoteName('#__fabrik_forms', 'ff'))
			->leftJoin($this->db->quoteName('#__fabrik_lists', 'fl') . ' ON fl.form_id = ff.id')
			->where('ff.id = ' . $this->db->quote($formId));
		$this->db->setQuery($query);

		return $this->db->loadObject() ?: null;
	}

	private function loadRawListFromDb(int $formId): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__fabrik_lists'))
			->where('form_id = ' . $this->db->quote($formId));
		$this->db->setQuery($query);

		return $this->db->loadObject() ?: null;
	}

	private function loadRawGroupFromDb(int $formId): ?object
	{
		$query = $this->db->getQuery(true)
			->select('fg.*')
			->from($this->db->quoteName('#__fabrik_groups', 'fg'))
			->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ffg.group_id = fg.id')
			->where('ffg.form_id = ' . $this->db->quote($formId))
			->where('fg.is_join = 0')
			->setLimit(1);
		$this->db->setQuery($query);

		return $this->db->loadObject() ?: null;
	}

	private function loadRawElementFromDb(int $groupId): ?object
	{
		$query = $this->db->getQuery(true)
			->select('*')
			->from($this->db->quoteName('#__fabrik_elements'))
			->where('group_id = ' . $this->db->quote($groupId))
			->where('published <> -2')
			->setLimit(1);
		$this->db->setQuery($query);

		return $this->db->loadObject() ?: null;
	}

	private function getElementIdsForGroup(int $groupId): array
	{
		$query = $this->db->getQuery(true)
			->select('id')
			->from($this->db->quoteName('#__fabrik_elements'))
			->where('group_id = ' . $this->db->quote($groupId));
		$this->db->setQuery($query);

		return $this->db->loadColumn() ?: [];
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getFormById
	 */
	public function testGetFormById(): void
	{
		$formId = 102;
		$form = $this->repository->withRelations(false)->getFormById($formId);

		$this->assertNotNull($form);
		$this->assertEquals($formId, $form->getId());
		$this->assertNotEmpty($form->getLabel());
		$this->assertEmpty($form->getGroups(), 'Groups should not be loaded when withRelations is false');

		$form = $this->repository->withRelations()->getFormById($formId);
		$this->assertNotEmpty($form->getGroups());
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getGroupsByFormId
	 */
	public function testGetGroupsByFormId(): void
	{
		$formId = 102;
		$groups = $this->repository->getGroupsByFormId($formId);

		$this->assertNotEmpty($groups);

		$form = $this->repository->withRelations()->getFormById($formId);
		$this->assertEquals(count($form->getGroups()), count($groups));
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElementsByGroupId
	 */
	public function testGetElementsByGroupId(): void
	{
		$formId = 102;
		$form = $this->repository->withRelations()->getFormById($formId);
		$this->assertNotEmpty($form->getGroups());

		$group = $form->getGroups()[0];
		$elements = $this->repository->getElementsByGroupId($group->getId());

		$this->assertNotEmpty($elements);
		$this->assertEquals($group->getId(), $elements[0]->getGroupId());
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElements
	 * @return void
	 */
	public function testGetElements(): void
	{
		$elements = $this->repository->getElements([
			'plugin' => [
				ElementPluginEnum::DATABASEJOIN->value
			],
		], 10);

		$this->assertNotEmpty($elements, 'Elements should not be empty');
		foreach ($elements as $element) {
			$this->assertEquals(ElementPluginEnum::DATABASEJOIN, $element->getPlugin(), 'Element plugin should be DATABASEJOIN');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::withRelations
	 */
	public function testWithRelationsReturnsSelf(): void
	{
		$result = $this->repository->withRelations(false);
		$this->assertSame($this->repository, $result, 'withRelations should return $this for fluent interface');

		$result = $this->repository->withRelations();
		$this->assertSame($this->repository, $result, 'withRelations with default param should also return $this');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getFormFromElementId
	 */
	public function testGetFormFromElementId(): void
	{
		$formId = 102;
		$form   = $this->repository->withRelations()->getFormById($formId);
		$this->assertNotEmpty($form->getGroups(), 'Form 102 should have groups');

		$group    = $form->getGroups()[0];
		$elements = $this->repository->getElementsByGroupId($group->getId());
		$this->assertNotEmpty($elements, 'Group should have elements');

		$elementId = $elements[0]->getId();

		$foundForm = $this->repository->withRelations(false)->getFormFromElementId($elementId);

		$this->assertNotNull($foundForm, 'Should find a form from a valid element ID');
		$this->assertEquals($formId, $foundForm->getId(), 'Found form ID should match the original form');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getFormFromElementId
	 */
	public function testGetFormFromElementIdWithZeroReturnsNull(): void
	{
		$form = $this->repository->getFormFromElementId(0);
		$this->assertNull($form, 'Should return null for element ID 0');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getGroupsOrdering
	 */
	public function testGetGroupsOrdering(): void
	{
		$formId   = 102;
		$ordering = $this->repository->getGroupsOrdering($formId);

		$this->assertIsArray($ordering, 'getGroupsOrdering should return an array');
		$this->assertNotEmpty($ordering, 'Form 102 should have group ordering');
		foreach ($ordering as $groupId) {
			$this->assertIsNumeric($groupId, 'Each ordering entry should be a numeric group ID');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getGroupsOrdering
	 */
	public function testGetGroupsOrderingReturnsEmptyArrayForInvalidFormId(): void
	{
		$ordering = $this->repository->getGroupsOrdering(0);
		$this->assertIsArray($ordering, 'Should return an array even for invalid form ID');
		$this->assertEmpty($ordering, 'Should return empty array for form ID 0');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElementById
	 */
	public function testGetElementById(): void
	{
		$formId   = 102;
		$form     = $this->repository->withRelations()->getFormById($formId);
		$group    = $form->getGroups()[0];
		$elements = $this->repository->getElementsByGroupId($group->getId());
		$this->assertNotEmpty($elements, 'Group should have elements');

		$elementId = $elements[0]->getId();

		$this->repository->setElementFilters([]);
		$element = $this->repository->getElementById($elementId);

		$this->assertNotNull($element, 'getElementById should not return null for a valid ID');
		$this->assertInstanceOf(FabrikElementEntity::class, $element);
		$this->assertEquals($elementId, $element->getId(), 'Returned element ID should match the requested ID');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElementsByIds
	 */
	public function testGetElementsByIds(): void
	{
		$formId   = 102;
		$form     = $this->repository->withRelations()->getFormById($formId);
		$group    = $form->getGroups()[0];
		$elements = $this->repository->getElementsByGroupId($group->getId());
		$this->assertNotEmpty($elements, 'Group should have elements');

		$ids = array_map(fn($e) => $e->getId(), array_slice($elements, 0, 2));

		$this->repository->setElementFilters([]);
		$foundElements = $this->repository->getElementsByIds($ids);

		$this->assertNotEmpty($foundElements, 'getElementsByIds should find elements for valid IDs');
		$this->assertCount(count($ids), $foundElements, 'Should return the same number of elements as requested IDs');
		$foundIds = array_map(fn($e) => $e->getId(), $foundElements);
		foreach ($ids as $id) {
			$this->assertContains($id, $foundIds, 'Each requested ID should be present in the result');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElementsByIds
	 */
	public function testGetElementsByIdsReturnsEmptyArrayForEmptyInput(): void
	{
		$elements = $this->repository->getElementsByIds([]);
		$this->assertIsArray($elements, 'Should return an array');
		$this->assertEmpty($elements, 'Should return empty array for empty IDs input');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElements
	 */
	public function testGetElementsWithNameFilter(): void
	{
		$formId   = 102;
		$form     = $this->repository->withRelations()->getFormById($formId);
		$group    = $form->getGroups()[0];
		$elements = $this->repository->getElementsByGroupId($group->getId());
		$this->assertNotEmpty($elements, 'Group should have elements');

		$elementName = $elements[0]->getName();

		$this->repository->setElementFilters([]);
		$filteredElements = $this->repository->getElements(['name' => $elementName], 10);

		$this->assertNotEmpty($filteredElements, 'Should find elements when filtering by name');
		foreach ($filteredElements as $filteredElement) {
			$this->assertEquals($elementName, $filteredElement->getName(), 'Each returned element should match the name filter');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElements
	 */
	public function testGetElementsWithPagination(): void
	{
		$filters = ['plugin' => [ElementPluginEnum::DATABASEJOIN->value]];

		$page1 = $this->repository->getElements($filters, 1, 1);
		$this->assertCount(1, $page1, 'Page 1 with limit 1 should return exactly 1 element');

		$this->repository->setElementFilters([]);
		$page2 = $this->repository->getElements($filters, 1, 2);

		if (!empty($page2)) {
			$this->assertCount(1, $page2, 'Page 2 with limit 1 should return exactly 1 element');
			$this->assertNotEquals($page1[0]->getId(), $page2[0]->getId(), 'Pages should return different elements');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElementAlias
	 */
	public function testGetElementAlias(): void
	{
		$formId   = 102;
		$form     = $this->repository->withRelations()->getFormById($formId);
		$group    = $form->getGroups()[0];
		$elements = $this->repository->getElementsByGroupId($group->getId());
		$this->assertNotEmpty($elements, 'Group should have elements');

		$elementId = $elements[0]->getId();

		$alias = $this->repository->getElementAlias($elementId);

		$this->assertIsString($alias, 'getElementAlias should return a string');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getMenuItemIdByFormId
	 */
	public function testGetMenuItemIdByFormId(): void
	{
		$formId     = 102;
		$menuItemId = $this->repository->getMenuItemIdByFormId($formId);

		if ($menuItemId !== null) {
			$this->assertIsInt($menuItemId, 'Menu item ID should be an integer when found');
			$this->assertGreaterThan(0, $menuItemId, 'Menu item ID should be a positive integer');
		} else {
			$this->assertNull($menuItemId, 'getMenuItemIdByFormId may return null if no published menu item exists');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::setElementFilters
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getElementFilters
	 */
	public function testSetAndGetElementFilters(): void
	{
		$filters = ['plugin' => 'field', 'published' => 1];
		$this->repository->setElementFilters($filters);

		$this->assertEquals($filters, $this->repository->getElementFilters(), 'getElementFilters should return the filters set by setElementFilters');

		$this->repository->setElementFilters([]);
		$this->assertEmpty($this->repository->getElementFilters(), 'Filters should be empty after resetting with an empty array');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::updateFabrikLabel
	 */
	public function testUpdateFabrikLabelThrowsExceptionForInvalidTable(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->updateFabrikLabel(1, 'FORM_', '', '', 'invalid_table');
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::getFormsByProfileId
	 */
	public function testGetFormsByProfileId(): void
	{
		$profileId = 1000;
		$forms     = $this->repository->withRelations(false)->getFormsByProfileId($profileId);

		$this->assertIsArray($forms, 'getFormsByProfileId should return an array');
	}

	// -------------------------------------------------------------------------
	// flushForm
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushForm
	 */
	public function testFlushFormInsertsNewForm(): void
	{
		$rawForm = $this->loadRawFormFromDb(102);
		$this->assertNotNull($rawForm, 'Form 102 must exist in DB');

		unset($rawForm->list_id, $rawForm->db_table_name);
		$rawForm->id = 0;

		$result = $this->repository->flushForm($rawForm);

		$this->assertTrue($result, 'flushForm should return true on successful insert');
		$this->assertGreaterThan(0, $rawForm->id, 'flushForm should populate the ID on the passed object');
		$this->createdFormIds[] = $rawForm->id;
	}

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushForm
	 */
	public function testFlushFormUpdatesExistingForm(): void
	{
		// Create a form so we have something safe to update
		$rawForm = $this->loadRawFormFromDb(102);
		unset($rawForm->list_id, $rawForm->db_table_name);
		$rawForm->id = 0;
		$this->repository->flushForm($rawForm);
		$this->createdFormIds[] = $rawForm->id;

		$updatedLabel  = 'FORM_UPDATE_TEST_' . $rawForm->id;
		$rawForm->label = $updatedLabel;

		$result = $this->repository->flushForm($rawForm);

		$this->assertTrue($result, 'flushForm should return true on successful update');

		$query = $this->db->getQuery(true)
			->select('label')
			->from('#__fabrik_forms')
			->where('id = ' . $this->db->quote($rawForm->id));
		$this->db->setQuery($query);
		$this->assertEquals($updatedLabel, $this->db->loadResult(), 'Updated label should be persisted in DB');
	}

	// -------------------------------------------------------------------------
	// flushList
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushList
	 */
	public function testFlushListInsertsNewList(): void
	{
		$rawForm = $this->loadRawFormFromDb(102);
		unset($rawForm->list_id, $rawForm->db_table_name);
		$rawForm->id = 0;
		$this->repository->flushForm($rawForm);
		$this->createdFormIds[] = $rawForm->id;

		$originalList  = $this->loadRawListFromDb(102);
		$this->assertNotNull($originalList, 'Form 102 must have a list');

		$newList          = clone $originalList;
		$newList->id      = 0;
		$newList->form_id = $rawForm->id;

		$result = $this->repository->flushList($newList);

		$this->assertTrue($result, 'flushList should return true on successful insert');
		$this->assertGreaterThan(0, $newList->id, 'flushList should populate the ID on the passed object');
		$this->createdListIds[] = $newList->id;
	}

	// -------------------------------------------------------------------------
	// flushGroup
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushGroup
	 */
	public function testFlushGroupInsertsNewGroup(): void
	{
		$rawGroup = $this->loadRawGroupFromDb(102);
		$this->assertNotNull($rawGroup, 'Form 102 must have at least one non-join group');

		$newGroup     = clone $rawGroup;
		$newGroup->id = 0;

		$result = $this->repository->flushGroup($newGroup);

		$this->assertTrue($result, 'flushGroup should return true on successful insert');
		$this->assertGreaterThan(0, $newGroup->id, 'flushGroup should populate the ID on the passed object');
		$this->createdGroupIds[] = $newGroup->id;
	}

	// -------------------------------------------------------------------------
	// flushElement
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushElement
	 */
	public function testFlushElementInsertsNewElement(): void
	{
		$ordering = $this->repository->getGroupsOrdering(102);
		$this->assertNotEmpty($ordering, 'Form 102 must have groups');

		$rawElement = $this->loadRawElementFromDb((int) $ordering[0]);
		$this->assertNotNull($rawElement, 'First group of form 102 must have at least one element');

		$newElement     = clone $rawElement;
		$newElement->id = 0;

		$result = $this->repository->flushElement($newElement);

		$this->assertTrue($result, 'flushElement should return true on successful insert');
		$this->assertGreaterThan(0, $newElement->id, 'flushElement should populate the ID on the passed object');
		$this->createdElementIds[] = $newElement->id;
	}

	// -------------------------------------------------------------------------
	// duplicateList
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateList
	 */
	public function testDuplicateListCreatesNewList(): void
	{
		$originalList = $this->loadRawListFromDb(102);
		$this->assertNotNull($originalList, 'Form 102 must have a list');

		// Need a host form for the new list
		$rawForm = $this->loadRawFormFromDb(102);
		unset($rawForm->list_id, $rawForm->db_table_name);
		$rawForm->id = 0;
		$this->repository->flushForm($rawForm);
		$this->createdFormIds[] = $rawForm->id;

		$newList = $this->repository->duplicateList($originalList->id, $rawForm->id);

		$this->assertIsObject($newList, 'duplicateList should return an object');
		$this->assertGreaterThan(0, $newList->id, 'Duplicated list should have a new positive ID');
		$this->assertNotEquals($originalList->id, $newList->id, 'Duplicated list ID must differ from original');
		$this->assertEquals($rawForm->id, $newList->form_id, 'Duplicated list must reference the new form');
		$this->createdListIds[] = $newList->id;
	}

	// -------------------------------------------------------------------------
	// duplicateGroup
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateGroup
	 */
	public function testDuplicateGroupCreatesNewGroup(): void
	{
		$rawGroup = $this->loadRawGroupFromDb(102);
		$this->assertNotNull($rawGroup, 'Form 102 must have at least one non-join group');

		// Prepare host form and list
		$rawForm = $this->loadRawFormFromDb(102);
		unset($rawForm->list_id, $rawForm->db_table_name);
		$rawForm->id = 0;
		$this->repository->flushForm($rawForm);
		$this->createdFormIds[] = $rawForm->id;

		$originalList     = $this->loadRawListFromDb(102);
		$newList          = clone $originalList;
		$newList->id      = 0;
		$newList->form_id = $rawForm->id;
		$this->repository->flushList($newList);
		$this->createdListIds[] = $newList->id;

		$duplicatedGroup = $this->repository->duplicateGroup($rawGroup, $newList, $rawForm, 102, false, []);

		$this->assertIsObject($duplicatedGroup, 'duplicateGroup should return an object');
		$this->assertGreaterThan(0, $duplicatedGroup->id, 'Duplicated group should have a new positive ID');
		$this->assertNotEquals($rawGroup->id, $duplicatedGroup->id, 'Duplicated group ID must differ from original');
		$this->createdGroupIds[] = $duplicatedGroup->id;
	}

	// -------------------------------------------------------------------------
	// duplicateGroups
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateGroups
	 */
	public function testDuplicateGroupsCreatesAllGroups(): void
	{
		$groups = $this->repository->getGroupsByFormId(102);
		$this->assertNotEmpty($groups, 'Form 102 must have groups');

		$rawForm = $this->loadRawFormFromDb(102);
		unset($rawForm->list_id, $rawForm->db_table_name);
		$rawForm->id = 0;
		$this->repository->flushForm($rawForm);
		$this->createdFormIds[] = $rawForm->id;

		$originalList     = $this->loadRawListFromDb(102);
		$newList          = clone $originalList;
		$newList->id      = 0;
		$newList->form_id = $rawForm->id;
		$this->repository->flushList($newList);
		$this->createdListIds[] = $newList->id;

		$duplicatedGroups = $this->repository->duplicateGroups(102, $rawForm, $newList, []);

		$this->assertIsArray($duplicatedGroups, 'duplicateGroups should return an array');
		$this->assertCount(count($groups), $duplicatedGroups, 'Should duplicate exactly as many groups as the original form has');

		foreach ($duplicatedGroups as $group)
		{
			$this->assertGreaterThan(0, $group->id, 'Each duplicated group should have a valid ID');
			$this->createdGroupIds[] = $group->id;
			foreach ($this->getElementIdsForGroup($group->id) as $elementId)
			{
				$this->createdElementIds[] = $elementId;
			}
		}
	}

	// -------------------------------------------------------------------------
	// duplicateElement
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateElement
	 */
	public function testDuplicateElementCreatesNewElement(): void
	{
		$ordering = $this->repository->getGroupsOrdering(102);
		$this->assertNotEmpty($ordering, 'Form 102 must have groups');

		$rawElement = $this->loadRawElementFromDb((int) $ordering[0]);
		$this->assertNotNull($rawElement, 'First group of form 102 must have at least one element');

		// Create a target group for the duplicated element
		$rawGroup     = $this->loadRawGroupFromDb(102);
		$newGroup     = clone $rawGroup;
		$newGroup->id = 0;
		$this->repository->flushGroup($newGroup);
		$this->createdGroupIds[] = $newGroup->id;

		$duplicatedElement = $this->repository->duplicateElement($rawElement, $newGroup, []);

		$this->assertIsObject($duplicatedElement, 'duplicateElement should return an object');
		$this->assertGreaterThan(0, $duplicatedElement->id, 'Duplicated element should have a new positive ID');
		$this->assertNotEquals($rawElement->id, $duplicatedElement->id, 'Duplicated element ID must differ from original');
		$this->assertEquals($newGroup->id, $duplicatedElement->group_id, 'Duplicated element should belong to the new group');
		$this->createdElementIds[] = $duplicatedElement->id;
	}

	// -------------------------------------------------------------------------
	// duplicateConditions
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateConditions
	 */
	public function testDuplicateConditionsReturnsBool(): void
	{
		$rawForm = $this->loadRawFormFromDb(102);
		unset($rawForm->list_id, $rawForm->db_table_name);
		$rawForm->id = 0;
		$this->repository->flushForm($rawForm);
		$this->createdFormIds[] = $rawForm->id;

		$result = $this->repository->duplicateConditions((object) ['id' => 102], $rawForm);

		$this->assertIsBool($result, 'duplicateConditions should return a boolean');
	}

	// -------------------------------------------------------------------------
	// updateCalculationParametersAfterDuplicate
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::updateCalculationParametersAfterDuplicate
	 */
	public function testUpdateCalculationParametersAfterDuplicateReturnsFalseForFormWithNoCalcElements(): void
	{
		// A form with no calc elements in the new form returns false
		$result = $this->repository->updateCalculationParametersAfterDuplicate(102, (object) ['id' => 0]);

		$this->assertIsBool($result, 'updateCalculationParametersAfterDuplicate should always return a boolean');
		$this->assertFalse($result, 'Should return false when no calculation elements are found in the new form');
	}

	// -------------------------------------------------------------------------
	// duplicateForm
	// -------------------------------------------------------------------------

	/**
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateForm
	 */
	public function testDuplicateFormCreatesCompleteFormTree(): void
	{
		$rawForm = $this->loadRawFormFromDb(102);
		$this->assertNotNull($rawForm, 'Form 102 must exist in DB');

		$originalGroups = $this->repository->getGroupsByFormId(102);

		$result = $this->repository->duplicateForm($rawForm, null, []);

		$this->assertIsObject($result, 'duplicateForm should return an object');
		$this->assertTrue(property_exists($result, 'form'), 'Result must have a form property');
		$this->assertTrue(property_exists($result, 'list'), 'Result must have a list property');
		$this->assertTrue(property_exists($result, 'groups'), 'Result must have a groups property');
		$this->assertGreaterThan(0, $result->form->id, 'New form must have a valid positive ID');
		$this->assertNotEquals(102, $result->form->id, 'Duplicated form ID must differ from the source');
		$this->assertGreaterThan(0, $result->list->id, 'New list must have a valid positive ID');
		$this->assertCount(count($originalGroups), $result->groups, 'All groups from the source form must be duplicated');

		// Register for cleanup
		$this->createdFormIds[] = $result->form->id;
		$this->createdListIds[] = $result->list->id;
		foreach ($result->groups as $group)
		{
			$this->createdGroupIds[] = $group->id;
			foreach ($this->getElementIdsForGroup($group->id) as $elementId)
			{
				$this->createdElementIds[] = $elementId;
			}
		}
	}
}