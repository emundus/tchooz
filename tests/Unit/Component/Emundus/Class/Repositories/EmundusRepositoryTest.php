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
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Repositories\Actions\ActionRepository;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\ColumnFilter;

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

	// =====================
	// applyFilters with ColumnFilter
	// Semantics mirror ScalarComparator and ArrayComparator
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectWithEqualsOnScalarMatchesThatValue(): void
	{
		$items = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::EQUALS, 'file')]);

		$this->assertCount(1, $items, 'A single action is named file');
		$this->assertEquals('file', $items[0]->getName(), 'The returned action should be the filtered one');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectWithEqualsOnArrayBuildsAnInClause(): void
	{
		$items = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::EQUALS, ['file', 'evaluation'])]);

		$this->assertCount(2, $items, 'A list of values with EQUALS should behave as an IN clause');
		foreach ($items as $item)
		{
			$this->assertContains($item->getName(), ['file', 'evaluation'], 'Only the listed values should be returned');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectWithNotEqualsOnArrayBuildsANotInClause(): void
	{
		$total = $this->repository->getCount();
		$items = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::NOT_EQUALS, ['file'])]);

		$this->assertCount($total - 1, $items, 'Every action but the excluded one should be returned');
		foreach ($items as $item)
		{
			$this->assertNotEquals('file', $item->getName(), 'The excluded value should never be returned');
		}
	}

	/**
	 * No value can belong to an empty list, so the query has to return nothing rather than everything.
	 * A user holding no program must not end up seeing every program.
	 *
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterOnAnEmptyArrayMatchesNothingOnBothShapes(): void
	{
		$explicit = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::EQUALS, [])]);
		$implicit = $this->repository->get(['name' => []]);

		$this->assertCount(0, $explicit, 'An empty list of expected values should return no row');
		$this->assertCount(0, $implicit, 'Both filter shapes should treat an empty list the same way');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::getCount
	 */
	public function testFilterOnAnEmptyArrayCountsNothing(): void
	{
		$count = $this->repository->getCount([new ColumnFilter('name', ConditionOperatorEnum::EQUALS, [])]);

		$this->assertSame(0, $count, 'The count has to agree with the rows returned for an empty list');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectWithNotEqualsOnEmptyArrayMatchesEverything(): void
	{
		$total = $this->repository->getCount();
		$items = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::NOT_EQUALS, [])]);

		$this->assertCount($total, $items, 'Excluding nothing should return every row');
	}

	/**
	 * Empty and not empty must partition the table, which proves null, empty string and zero are all
	 * handled on the same side.
	 *
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectEmptyAndNotEmptyPartitionTheRows(): void
	{
		$total    = $this->repository->getCount();
		$empty    = $this->repository->get([new ColumnFilter('description', ConditionOperatorEnum::IS_EMPTY)]);
		$notEmpty = $this->repository->get([new ColumnFilter('description', ConditionOperatorEnum::IS_NOT_EMPTY)]);

		$this->assertSame($total, count($empty) + count($notEmpty), 'Empty and not empty should cover every row exactly once');

		foreach ($empty as $item)
		{
			$this->assertEmpty($item->getDescription(), 'IS_EMPTY should only return rows PHP considers empty');
		}

		foreach ($notEmpty as $item)
		{
			$this->assertNotEmpty($item->getDescription(), 'IS_NOT_EMPTY should only return rows PHP considers filled');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectWithEqualsAndNoValueTestsEmptiness(): void
	{
		$nullValue = $this->repository->get([new ColumnFilter('description', ConditionOperatorEnum::EQUALS, null)]);
		$isEmpty   = $this->repository->get([new ColumnFilter('description', ConditionOperatorEnum::IS_EMPTY)]);

		$this->assertSame(count($isEmpty), count($nullValue), 'Comparing to null should behave as IS_EMPTY, as ScalarComparator does');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectWithContainsBuildsALikeClause(): void
	{
		$items = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::CONTAINS, 'export')]);

		$this->assertNotEmpty($items, 'Several actions are named after an export');
		foreach ($items as $item)
		{
			$this->assertStringContainsString('export', $item->getName(), 'CONTAINS should only return rows holding the value');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectWithNotContainsExcludesMatchingRows(): void
	{
		$items = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::NOT_CONTAINS, 'export')]);

		$this->assertNotEmpty($items, 'Actions unrelated to exports should remain');
		foreach ($items as $item)
		{
			$this->assertStringNotContainsString('export', $item->getName(), 'NOT_CONTAINS should exclude every row holding the value');
		}
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectWithGreaterThanComparesValues(): void
	{
		$items = $this->repository->get([new ColumnFilter('id', ConditionOperatorEnum::GREATER_THAN, 4)]);

		$this->assertNotEmpty($items, 'Actions with a higher id should be returned');
		foreach ($items as $item)
		{
			$this->assertGreaterThan(4, $item->getId(), 'GREATER_THAN should only return higher values');
		}
	}

	/**
	 * The count query has to understand the filter as well, otherwise getList pages on a wrong total.
	 *
	 * @covers \Tchooz\Repositories\EmundusRepository::getList
	 */
	public function testFilterObjectIsAppliedToTheCountOfAList(): void
	{
		$filters = [new ColumnFilter('name', ConditionOperatorEnum::EQUALS, ['file', 'evaluation'])];

		$list = $this->repository->getList($filters);

		$this->assertSame(2, $list->getTotalItems(), 'The total of a filtered list should count the filtered rows only');
		$this->assertCount(2, $list->getItems(), 'Items and total should agree');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectStillValidatesTheColumn(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->get([new ColumnFilter('invalid_column_xyz', ConditionOperatorEnum::EQUALS, 1)]);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectThrowsWhenAnOperatorNeedsAValue(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->get([new ColumnFilter('id', ConditionOperatorEnum::GREATER_THAN, null)]);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectThrowsWhenAListIsGivenToAComparisonOperator(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		$this->repository->get([new ColumnFilter('id', ConditionOperatorEnum::GREATER_THAN, [1, 2])]);
	}

	/**
	 * Both shapes have to keep working side by side.
	 *
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testFilterObjectAndKeyValueShapesCombine(): void
	{
		$items = $this->repository->get([
			'status' => 1,
			new ColumnFilter('name', ConditionOperatorEnum::EQUALS, 'file'),
		]);

		$this->assertCount(1, $items, 'Mixing both filter shapes should apply both conditions');
		$this->assertEquals('file', $items[0]->getName(), 'The object filter should have been applied');
	}

	/**
	 * A key => value filter is an equality, so both shapes have to produce the same rows.
	 *
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testKeyValueShapeBehavesAsAnExplicitEquals(): void
	{
		$implicitScalar = $this->repository->get(['name' => 'file']);
		$explicitScalar = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::EQUALS, 'file')]);

		$this->assertSame(count($explicitScalar), count($implicitScalar), 'A scalar key => value filter should equal an explicit EQUALS');

		$implicitList = $this->repository->get(['name' => ['file', 'evaluation']]);
		$explicitList = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::EQUALS, ['file', 'evaluation'])]);

		$this->assertSame(count($explicitList), count($implicitList), 'A list key => value filter should equal an explicit EQUALS on a list');
	}

	/**
	 * Wildcards embedded in a value keep asking for a partial match, on both shapes.
	 *
	 * @covers \Tchooz\Repositories\EmundusRepository::applyFilters
	 */
	public function testValueHoldingWildcardsStillBuildsALikeClause(): void
	{
		$items = $this->repository->get(['name' => '%export%']);

		$this->assertNotEmpty($items, 'A value holding wildcards should still match partially');
		foreach ($items as $item)
		{
			$this->assertStringContainsString('export', $item->getName(), 'Only rows matching the pattern should be returned');
		}

		$explicit = $this->repository->get([new ColumnFilter('name', ConditionOperatorEnum::EQUALS, '%export%')]);
		$this->assertSame(count($items), count($explicit), 'Both filter shapes should read wildcards the same way');
	}

	// =====================
	// buildSearchConditions
	// =====================

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildSearchConditions
	 */
	public function testBuildSearchConditionsSearchesEveryGivenColumn(): void
	{
		$conditions = $this->repository->buildSearchConditions(['label', 'name'], 'art1243');

		$this->assertStringContainsString('esa`.`label` LIKE ', $conditions);
		$this->assertStringContainsString('esa`.`name` LIKE ', $conditions);
		$this->assertStringContainsString('%art1243%', $conditions);
		$this->assertSame(1, substr_count($conditions, 'OR'), 'Two columns on one term should be joined by a single OR');
	}

	/**
	 * A comma separates independent terms so an applicant can look up several choices at once.
	 *
	 * @covers \Tchooz\Repositories\EmundusRepository::buildSearchConditions
	 */
	public function testBuildSearchConditionsSplitsOnCommas(): void
	{
		$conditions = $this->repository->buildSearchConditions(['label'], 'art1243,art1674');

		$this->assertStringContainsString('%art1243%', $conditions);
		$this->assertStringContainsString('%art1674%', $conditions);
		$this->assertSame(1, substr_count($conditions, 'OR'), 'Both terms should be matched at the same time');
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildSearchConditions
	 */
	public function testBuildSearchConditionsIgnoresSpacingAndEmptyTerms(): void
	{
		$conditions = $this->repository->buildSearchConditions(['label'], ' art1243 , , art1674 ,');

		$this->assertSame(2, substr_count($conditions, 'LIKE'), 'Only the two real terms should build a condition');
		$this->assertStringContainsString('%art1243%', $conditions);
		$this->assertStringContainsString('%art1674%', $conditions);
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildSearchConditions
	 */
	public function testBuildSearchConditionsReturnsEmptyWhenThereIsNothingToSearch(): void
	{
		$this->assertSame('', $this->repository->buildSearchConditions(['label'], ' , '));
		$this->assertSame('', $this->repository->buildSearchConditions([], 'art1243'));
	}

	/**
	 * @covers \Tchooz\Repositories\EmundusRepository::buildSearchConditions
	 */
	public function testBuildSearchConditionsKeepsLikeWildcardsLiteral(): void
	{
		$conditions = $this->repository->buildSearchConditions(['label'], '50%');

		$this->assertStringContainsString('50\\%', $conditions, 'A wildcard typed by the user should be escaped');
	}

	/**
	 * The search of a campaign list reaches the applicant through getAllCampaigns, comma included.
	 *
	 * @covers \Tchooz\Repositories\EmundusRepository::buildSearchConditions
	 */
	public function testBuildSearchConditionsAcceptsAlreadyQualifiedColumns(): void
	{
		$conditions = $this->repository->buildSearchConditions(['u.name'], 'art1243');

		$this->assertStringContainsString('`u`.`name` LIKE ', $conditions);
		$this->assertStringNotContainsString('esa`.`u', $conditions);
	}
}
