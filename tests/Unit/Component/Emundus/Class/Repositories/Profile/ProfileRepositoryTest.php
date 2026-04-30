<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories\Profile
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Profile;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\List\ListResult;
use Tchooz\Entities\Profile\ProfileEntity;
use Tchooz\Factories\Profile\ProfileFactory;
use Tchooz\Repositories\Profile\ProfileRepository;

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Profile
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Profile\ProfileRepository
 */
class ProfileRepositoryTest extends UnitTestCase
{
	/** Standard fixture profile ID — always present in the test DB */
	private const FIXTURE_PROFILE_ID = 1000;

	private ProfileRepository $repository;

	/** IDs of profiles created during tests — removed in tearDown */
	private array $createdProfileIds = [];

	protected function setUp(): void
	{
		parent::setUp();
		$this->repository = new ProfileRepository();
	}

	protected function tearDown(): void
	{
		foreach ($this->createdProfileIds as $id)
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__emundus_setup_profiles'))
				->where('id = ' . (int) $id);
			$this->db->setQuery($query);
			$this->db->execute();
		}

		parent::tearDown();
	}

	// ---- helper -----------------------------------------------------------

	private function makeProfileEntity(
		int    $id           = 0,
		string $label        = 'Test Profile',
		string $description  = 'Unit test profile',
		bool   $published    = true,
		string $menutype     = 'testmenu',
		int    $aclAroGroups = 1,
		string $class        = 'applicant'
	): ProfileEntity
	{
		return new ProfileEntity($id, $label, $description, $published, $menutype, $aclAroGroups, $class);
	}

	// =========================================================================
	// getFactory
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\Profile\ProfileRepository::getFactory
	 */
	public function testGetFactoryReturnsProfileFactoryInstance(): void
	{
		$factory = $this->repository->getFactory();

		$this->assertInstanceOf(ProfileFactory::class, $factory, 'getFactory should return a ProfileFactory instance');
	}

	// =========================================================================
	// flush — insert
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\Profile\ProfileRepository::flush
	 */
	public function testFlushInsertsNewProfileAndSetsId(): void
	{
		$profile = $this->makeProfileEntity(id: 0, label: 'New profile ' . uniqid());

		$result = $this->repository->flush($profile);

		$this->assertTrue($result, 'flush should return true on successful insert');
		$this->assertGreaterThan(0, $profile->getId(), 'flush should populate the ID on the entity after insert');
		$this->createdProfileIds[] = $profile->getId();
	}

	/**
	 * @covers \Tchooz\Repositories\Profile\ProfileRepository::flush
	 */
	public function testFlushInsertsProfileWithCorrectValues(): void
	{
		$label       = 'Flush insert test ' . uniqid();
		$description = 'Test description';
		$menutype    = 'testmenu_' . rand(1, 9999);
		$profile     = $this->makeProfileEntity(label: $label, description: $description, menutype: $menutype);

		$this->repository->flush($profile);
		$this->createdProfileIds[] = $profile->getId();

		$persisted = $this->repository->getById($profile->getId());
		$this->assertNotNull($persisted);
		$this->assertSame($label, $persisted->getLabel());
		$this->assertSame($description, $persisted->getDescription());
		$this->assertSame($menutype, $persisted->getMenutype());
	}

	/**
	 * @covers \Tchooz\Repositories\Profile\ProfileRepository::flush
	 */
	public function testFlushThrowsExceptionForEmptyLabel(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->flush($this->makeProfileEntity(label: ''));
	}

	// =========================================================================
	// flush — update
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\Profile\ProfileRepository::flush
	 */
	public function testFlushUpdatesExistingProfile(): void
	{
		$profile = $this->makeProfileEntity(label: 'Before update ' . uniqid());
		$this->repository->flush($profile);
		$this->createdProfileIds[] = $profile->getId();

		$newLabel = 'After update ' . uniqid();
		$profile->setLabel($newLabel);
		$result = $this->repository->flush($profile);

		$this->assertTrue($result, 'flush should return true on successful update');

		$persisted = $this->repository->getById($profile->getId());
		$this->assertSame($newLabel, $persisted->getLabel(), 'Updated label should be persisted in DB');
	}

	// =========================================================================
	// getById
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\Profile\ProfileRepository::getById
	 */
	public function testGetByIdReturnsProfileEntityForValidId(): void
	{
		$profile = $this->repository->getById(self::FIXTURE_PROFILE_ID);

		$this->assertNotNull($profile, 'getById should return an entity for a valid ID');
		$this->assertInstanceOf(ProfileEntity::class, $profile);
		$this->assertSame(self::FIXTURE_PROFILE_ID, $profile->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Profile\ProfileRepository::getById
	 */
	public function testGetByIdReturnsNullForNonExistentId(): void
	{
		$profile = $this->repository->getById(PHP_INT_MAX);

		$this->assertNull($profile, 'getById should return null for a non-existent ID');
	}

	/**
	 * @covers \Tchooz\Repositories\Profile\ProfileRepository::getById
	 */
	public function testGetByIdReturnsFreshInsertedProfile(): void
	{
		$label   = 'getById test ' . uniqid();
		$profile = $this->makeProfileEntity(label: $label);
		$this->repository->flush($profile);
		$this->createdProfileIds[] = $profile->getId();

		$found = $this->repository->getById($profile->getId());

		$this->assertNotNull($found);
		$this->assertSame($profile->getId(), $found->getId());
		$this->assertSame($label, $found->getLabel());
	}

	// =========================================================================
	// getItemByField (inherited)
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemByField
	 */
	public function testGetItemByFieldReturnsRawObjectByDefault(): void
	{
		$item = $this->repository->getItemByField('id', self::FIXTURE_PROFILE_ID);

		$this->assertNotNull($item);
		$this->assertIsObject($item);
		$this->assertEquals(self::FIXTURE_PROFILE_ID, $item->id);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemByField
	 */
	public function testGetItemByFieldWithReturnEntityBuildsProfileEntity(): void
	{
		$item = $this->repository->getItemByField('id', self::FIXTURE_PROFILE_ID, true, []);

		$this->assertInstanceOf(ProfileEntity::class, $item, 'Should return a ProfileEntity when returnEntity is true');
		$this->assertSame(self::FIXTURE_PROFILE_ID, $item->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemByField
	 */
	public function testGetItemByFieldReturnsNullForNonExistentValue(): void
	{
		$item = $this->repository->getItemByField('id', PHP_INT_MAX);

		$this->assertNull($item);
	}

	// =========================================================================
	// getItemsByField (inherited)
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByField
	 */
	public function testGetItemsByFieldReturnsArrayForPublishedFilter(): void
	{
		$items = $this->repository->getItemsByField('published', 1);

		$this->assertIsArray($items, 'getItemsByField should return an array');
		$this->assertNotEmpty($items, 'There should be at least one published profile');
		foreach ($items as $item)
		{
			$this->assertEquals(1, $item->published, 'Each item should match the filter');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByField
	 */
	public function testGetItemsByFieldWithArrayValueFiltersCorrectly(): void
	{
		$profile1 = $this->makeProfileEntity(label: 'Array filter test 1 ' . uniqid());
		$profile2 = $this->makeProfileEntity(label: 'Array filter test 2 ' . uniqid());
		$this->repository->flush($profile1);
		$this->repository->flush($profile2);
		$this->createdProfileIds[] = $profile1->getId();
		$this->createdProfileIds[] = $profile2->getId();

		$items = $this->repository->getItemsByField('id', [$profile1->getId(), $profile2->getId()]);

		$this->assertCount(2, $items, 'Should return exactly the two inserted profiles');
		$ids = array_column($items, 'id');
		$this->assertContains($profile1->getId(), $ids);
		$this->assertContains($profile2->getId(), $ids);
	}

	// =========================================================================
	// getItemsByFields (inherited)
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByFields
	 */
	public function testGetItemsByFieldsFiltersWithMultipleFields(): void
	{
		$label   = 'Multi-field filter ' . uniqid();
		$profile = $this->makeProfileEntity(label: $label, published: true);
		$this->repository->flush($profile);
		$this->createdProfileIds[] = $profile->getId();

		$items = $this->repository->getItemsByFields(['published' => 1, 'id' => $profile->getId()]);

		$this->assertNotEmpty($items, 'Should find the profile matching both filters');
		$this->assertEquals($profile->getId(), $items[0]->id);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByFields
	 */
	public function testGetItemsByFieldsThrowsExceptionForInvalidField(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->getItemsByFields(['non_existent_column' => 'value']);
	}

	// =========================================================================
	// getCount (inherited)
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getCount
	 */
	public function testGetCountReturnsPositiveIntegerForAllProfiles(): void
	{
		$count = $this->repository->getCount();

		$this->assertIsInt($count);
		$this->assertGreaterThan(0, $count, 'There should be at least one profile in the DB');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getCount
	 */
	public function testGetCountWithFilterReturnsCorrectCount(): void
	{
		$countBefore = $this->repository->getCount(['published' => 1]);

		$profile = $this->makeProfileEntity(label: 'Count test ' . uniqid(), published: true);
		$this->repository->flush($profile);
		$this->createdProfileIds[] = $profile->getId();

		$countAfter = $this->repository->getCount(['published' => 1]);

		$this->assertSame($countBefore + 1, $countAfter, 'Count should increase by 1 after inserting a published profile');
	}

	// =========================================================================
	// get (inherited)
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetReturnsArrayOfProfileEntities(): void
	{
		$items = $this->repository->get();

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
		foreach ($items as $item)
		{
			$this->assertInstanceOf(ProfileEntity::class, $item, 'Each item should be a ProfileEntity');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithLimitReturnsCorrectNumberOfItems(): void
	{
		$items = $this->repository->get([], 2, 1);

		$this->assertIsArray($items);
		$this->assertCount(2, $items, 'get() with limit 2 should return exactly 2 items');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithPaginationReturnsDifferentPages(): void
	{
		$page1 = $this->repository->get([], 1, 1);
		$page2 = $this->repository->get([], 1, 2);

		$this->assertCount(1, $page1, 'Page 1 should return exactly 1 item');
		if (!empty($page2))
		{
			$this->assertNotEquals($page1[0]->getId(), $page2[0]->getId(), 'Page 2 should return a different profile than page 1');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithFilterReturnsMatchingProfiles(): void
	{
		$label   = 'Filter test ' . uniqid();
		$profile = $this->makeProfileEntity(label: $label, published: false);
		$this->repository->flush($profile);
		$this->createdProfileIds[] = $profile->getId();

		$results = $this->repository->get(['published' => 0]);

		$this->assertIsArray($results);
		$ids = array_map(fn(ProfileEntity $p) => $p->getId(), $results);
		$this->assertContains($profile->getId(), $ids, 'get() with published=0 should include the unpublished profile');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetThrowsExceptionForInvalidSelectField(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->get([], 0, 1, 'non_existent_column');
	}

	// =========================================================================
	// getList (inherited)
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getList
	 */
	public function testGetListReturnsListResult(): void
	{
		$result = $this->repository->getList();

		$this->assertInstanceOf(ListResult::class, $result, 'getList should return a ListResult');
		$this->assertIsArray($result->getItems(), 'ListResult items should be an array');
		$this->assertGreaterThan(0, $result->getTotalItems(), 'Total items count should be positive');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getList
	 */
	public function testGetListWithLimitReturnsPaginatedResult(): void
	{
		$result    = $this->repository->getList([], 1, 1);
		$fullCount = $this->repository->getCount();

		$this->assertInstanceOf(ListResult::class, $result);
		$this->assertCount(1, $result->getItems(), 'Paginated list should contain only 1 item when limit is 1');
		$this->assertSame($fullCount, $result->getTotalItems(), 'Total count should reflect all profiles, not just the current page');
	}

	// =========================================================================
	// buildOrderBy (inherited)
	// =========================================================================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildOrderBy
	 */
	public function testBuildOrderByReturnsValidOrderString(): void
	{
		$order = $this->repository->buildOrderBy('label', 'ASC');

		$this->assertIsString($order);
		$this->assertStringContainsString('label', $order);
		$this->assertStringContainsString('ASC', $order);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildOrderBy
	 */
	public function testBuildOrderByWithDescDirectionContainsDESC(): void
	{
		$order = $this->repository->buildOrderBy('id', 'DESC');

		$this->assertStringContainsString('DESC', $order);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildOrderBy
	 */
	public function testBuildOrderByThrowsExceptionForInvalidField(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->repository->buildOrderBy('non_existent_column');
	}
}
