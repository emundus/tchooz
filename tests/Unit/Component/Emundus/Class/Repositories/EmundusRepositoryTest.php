<?php
/**
 * @package     Unit\Component\Emundus\Class\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories;

use Joomla\CMS\Factory;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Actions\ActionEntity;
use Tchooz\Entities\List\ListResult;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\EmundusRepository;

/**
 * Tests des méthodes héritées de EmundusRepository.
 * Utilise ActionRepository comme support concret.
 *
 * @package     Unit\Component\Emundus\Class\Repositories
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\EmundusRepository
 */
class EmundusRepositoryTest extends UnitTestCase
{
	private Registry $config;
	
	private ActionRepository $repository;

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('cache_handler', 'file');
		$this->config->set('caching', 1);

		$this->repository = new ActionRepository();
	}

	// =====================
	// getItemByField
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemByField
	 */
	public function testGetItemByFieldReturnsObject(): void
	{
		$item = $this->repository->getItemByField('name', 'file');

		$this->assertNotNull($item);
		$this->assertIsObject($item);
		$this->assertEquals('file', $item->name);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemByField
	 */
	public function testGetItemByFieldReturnsEntityWhenRequested(): void
	{
		$item = $this->repository->getItemByField('name', 'file', true);

		$this->assertNotNull($item);
		$this->assertInstanceOf(ActionEntity::class, $item);
		$this->assertEquals('file', $item->getName());
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemByField
	 */
	public function testGetItemByFieldReturnsNullForNonExistent(): void
	{
		$item = $this->repository->getItemByField('name', 'non_existent_action_xyz');

		$this->assertNull($item);
	}

	// =====================
	// getItemsByField
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByField
	 */
	public function testGetItemsByFieldReturnsArray(): void
	{
		$items = $this->repository->getItemsByField('status', 1);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByField
	 */
	public function testGetItemsByFieldWithArrayValue(): void
	{
		$action = $this->repository->getByName('file');
		$this->assertNotNull($action);

		$items = $this->repository->getItemsByField('id', [$action->getId()]);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByField
	 */
	public function testGetItemsByFieldReturnsEntitiesWhenRequested(): void
	{
		$items = $this->repository->getItemsByField('status', 1, true);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
		$this->assertInstanceOf(ActionEntity::class, $items[0]);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByField
	 */
	public function testGetItemsByFieldReturnsEmptyForNonExistent(): void
	{
		$items = $this->repository->getItemsByField('name', 'non_existent_action_xyz');

		$this->assertIsArray($items);
		$this->assertEmpty($items);
	}

	// =====================
	// getItemsByFields
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByFields
	 */
	public function testGetItemsByFieldsReturnsArray(): void
	{
		$items = $this->repository->getItemsByFields(['status' => 1]);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByFields
	 */
	public function testGetItemsByFieldsWithMultipleFilters(): void
	{
		$items = $this->repository->getItemsByFields(['status' => 1, 'name' => 'file']);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByFields
	 */
	public function testGetItemsByFieldsReturnsEntitiesWhenRequested(): void
	{
		$items = $this->repository->getItemsByFields(['name' => 'file'], true);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
		$this->assertInstanceOf(ActionEntity::class, $items[0]);
		$this->assertEquals('file', $items[0]->getName());
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByFields
	 */
	public function testGetItemsByFieldsThrowsOnInvalidField(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->getItemsByFields(['invalid_column' => 'value']);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getItemsByFields
	 */
	public function testGetItemsByFieldsWithArrayValues(): void
	{
		$items = $this->repository->getItemsByFields(['name' => ['file', 'evaluation']], true);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
		foreach ($items as $item) {
			$this->assertInstanceOf(ActionEntity::class, $item);
			$this->assertContains($item->getName(), ['file', 'evaluation']);
		}
	}

	// =====================
	// get
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetReturnsEntities(): void
	{
		$items = $this->repository->get();

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
		$this->assertInstanceOf(ActionEntity::class, $items[0]);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithLimit(): void
	{
		$items = $this->repository->get([], 2);

		$this->assertIsArray($items);
		$this->assertLessThanOrEqual(2, count($items));
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithFilters(): void
	{
		$items = $this->repository->get(['name' => 'file']);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
		$this->assertEquals('file', $items[0]->getName());
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithPagination(): void
	{
		$page1 = $this->repository->get([], 1, 1);
		$page2 = $this->repository->get([], 1, 2);

		$this->assertCount(1, $page1);
		$this->assertCount(1, $page2);
		$this->assertNotEquals($page1[0]->getId(), $page2[0]->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithOrder(): void
	{
		$items = $this->repository->get([], 0, 1, '*', 'esa.id DESC');

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);

		if (count($items) > 1) {
			$this->assertGreaterThan($items[1]->getId(), $items[0]->getId());
		}
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithSearch(): void
	{
		$items = $this->repository->get([], 0, 1, '*', '', 'file');

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithSelectColumns(): void
	{
		$items = $this->repository->get([], 0, 1, ['id', 'name'], '', '', false);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithEmptySelectUsesTableColumns(): void
	{
		$items = $this->repository->get([], 0, 1, []);

		$this->assertIsArray($items);
		$this->assertNotEmpty($items);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithInvalidSelectThrows(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->get([], 0, 1, ['invalid_column_xyz']);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::get
	 */
	public function testGetWithInvalidFilterThrows(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->get(['invalid_filter_xyz' => 'value']);
	}

	// =====================
	// getCount
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getCount
	 */
	public function testGetCountReturnsInteger(): void
	{
		$count = $this->repository->getCount();

		$this->assertIsInt($count);
		$this->assertGreaterThan(0, $count);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getCount
	 */
	public function testGetCountWithFilters(): void
	{
		$countAll = $this->repository->getCount();
		$countFiltered = $this->repository->getCount(['name' => 'file']);

		$this->assertGreaterThanOrEqual($countFiltered, $countAll);
		$this->assertGreaterThan(0, $countFiltered);
	}

	// =====================
	// getList
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getList
	 */
	public function testGetListReturnsListResult(): void
	{
		$list = $this->repository->getList();

		$this->assertInstanceOf(ListResult::class, $list);
		$this->assertIsArray($list->getItems());
		$this->assertIsInt($list->getTotalItems());
		$this->assertGreaterThan(0, $list->getTotalItems());
		$this->assertNotEmpty($list->getItems());
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getList
	 */
	public function testGetListWithLimitRespectsLimit(): void
	{
		$list = $this->repository->getList([], 2);

		$this->assertLessThanOrEqual(2, count($list->getItems()));
		$this->assertGreaterThanOrEqual(count($list->getItems()), $list->getTotalItems());
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getList
	 */
	public function testGetListWithFilters(): void
	{
		$list = $this->repository->getList(['name' => 'file']);

		$this->assertNotEmpty($list->getItems());
		$this->assertEquals('file', $list->getItems()[0]->getName());
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getList
	 */
	public function testGetListTotalIsConsistentWithGetCount(): void
	{
		$list = $this->repository->getList();
		$count = $this->repository->getCount();

		$this->assertEquals($count, $list->getTotalItems());
	}

	// =====================
	// buildOrderBy
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildOrderBy
	 */
	public function testBuildOrderByReturnsString(): void
	{
		$order = $this->repository->buildOrderBy('id');

		$this->assertIsString($order);
		$this->assertStringContainsString('ASC', $order);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildOrderBy
	 */
	public function testBuildOrderByWithDescDirection(): void
	{
		$order = $this->repository->buildOrderBy('id', 'DESC');

		$this->assertIsString($order);
		$this->assertStringContainsString('DESC', $order);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildOrderBy
	 */
	public function testBuildOrderByThrowsOnInvalidField(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->buildOrderBy('invalid_column_xyz');
	}
}

