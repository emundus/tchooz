<?php
/**
 * @package     Unit\Component\Emundus\Class\Services\Fabrik
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Services\Fabrik;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Services\Fabrik\ConditionService;

class ConditionServiceTest extends UnitTestCase
{
	private ConditionService $service;

	private array $insertedGroupIds = [];

	public function setUp(): void
	{
		parent::setUp();
		$this->service = new ConditionService();
	}

	public function tearDown(): void
	{
		if (!empty($this->insertedGroupIds)) {
			$db = Factory::getContainer()->get('DatabaseDriver');
			$ids = implode(',', array_map([$db, 'quote'], $this->insertedGroupIds));
			$db->setQuery(
				$db->getQuery(true)
					->delete($db->quoteName('#__emundus_setup_form_rules_js_conditions_group'))
					->where($db->quoteName('id') . ' IN (' . $ids . ')')
			);
			$db->execute();
			$this->insertedGroupIds = [];
		}

		parent::tearDown();
	}

	// =========================================================================
	// groupConditions — private method, tested via reflection
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::groupConditions
	 */
	public function testGroupConditionsWithEmptyArrayReturnsNull(): void
	{
		$result = self::callPrivateMethod($this->service, 'groupConditions', [[]]);

		// array_reduce with no initial value and empty array returns null
		$this->assertNull($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::groupConditions
	 */
	public function testGroupConditionsWithUngroupedConditionsReturnsFlatArray(): void
	{
		$cond1 = (object)['field' => 'field_a', 'state' => '=', 'values' => 'foo', 'group' => null];
		$cond2 = (object)['field' => 'field_b', 'state' => '!=', 'values' => 'bar', 'group' => null];

		$result = self::callPrivateMethod($this->service, 'groupConditions', [[$cond1, $cond2]]);

		$this->assertIsArray($result);
		$this->assertCount(2, $result);
		$this->assertSame($cond1, $result[0]);
		$this->assertSame($cond2, $result[1]);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::groupConditions
	 */
	public function testGroupConditionsWithGroupedConditionsReturnsAssociativeArray(): void
	{
		$cond1 = (object)['field' => 'field_a', 'state' => '=', 'values' => 'foo', 'group' => 5];
		$cond2 = (object)['field' => 'field_b', 'state' => '!=', 'values' => 'bar', 'group' => 5];
		$cond3 = (object)['field' => 'field_c', 'state' => '=', 'values' => 'baz', 'group' => 7];

		$result = self::callPrivateMethod($this->service, 'groupConditions', [[$cond1, $cond2, $cond3]]);

		$this->assertIsArray($result);
		$this->assertArrayHasKey(5, $result);
		$this->assertArrayHasKey(7, $result);
		$this->assertCount(2, $result[5]);
		$this->assertCount(1, $result[7]);
		$this->assertSame($cond1, $result[5][0]);
		$this->assertSame($cond2, $result[5][1]);
		$this->assertSame($cond3, $result[7][0]);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::groupConditions
	 */
	public function testGroupConditionsWithMixedConditionsSeparatesCorrectly(): void
	{
		$ungrouped = (object)['field' => 'field_a', 'state' => '=', 'values' => 'foo', 'group' => null];
		$grouped   = (object)['field' => 'field_b', 'state' => '=', 'values' => 'bar', 'group' => 3];

		$result = self::callPrivateMethod($this->service, 'groupConditions', [[$ungrouped, $grouped]]);

		$this->assertIsArray($result);
		// ungrouped appended at numeric key 0
		$this->assertArrayHasKey(0, $result);
		$this->assertSame($ungrouped, $result[0]);
		// grouped under its group id
		$this->assertArrayHasKey(3, $result);
		$this->assertIsArray($result[3]);
		$this->assertSame($grouped, $result[3][0]);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::groupConditions
	 */
	public function testGroupConditionsWithSingleUngroupedCondition(): void
	{
		$cond = (object)['field' => 'field_a', 'state' => '=', 'values' => 'val', 'group' => null];

		$result = self::callPrivateMethod($this->service, 'groupConditions', [[$cond]]);

		$this->assertIsArray($result);
		$this->assertCount(1, $result);
		$this->assertSame($cond, $result[0]);
	}

	// =========================================================================
	// checkCondition — private method, tested via reflection
	// Conditions passed here must already be in the grouped format produced by
	// groupConditions(), i.e.  [int_key => object]  for ungrouped items.
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Single ungrouped condition, equal operator, value matches, OR rule group → true.
	 */
	public function testCheckConditionEqualOperatorWithMatchingValueOrGroup(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '=', 'values' => 'expected', 'group' => null];
		$formData = ['table___myfield_raw' => 'expected'];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
		]);

		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Single ungrouped condition, equal operator, value does NOT match, OR rule group → false.
	 */
	public function testCheckConditionEqualOperatorWithNonMatchingValueOrGroup(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '=', 'values' => 'expected', 'group' => null];
		$formData = ['table___myfield_raw' => 'other'];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
		]);

		$this->assertFalse($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Single ungrouped condition, not-equal operator, value differs → condition passes, OR group → true.
	 */
	public function testCheckConditionNotEqualOperatorWithDifferentValueOrGroup(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '!=', 'values' => 'forbidden', 'group' => null];
		$formData = ['table___myfield_raw' => 'other'];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
		]);

		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Single ungrouped condition, not-equal operator, value is the same → condition fails, OR group → false.
	 */
	public function testCheckConditionNotEqualOperatorWithSameValueOrGroup(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '!=', 'values' => 'forbidden', 'group' => null];
		$formData = ['table___myfield_raw' => 'forbidden'];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
		]);

		$this->assertFalse($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Equal operator against an array value — uses in_array.
	 */
	public function testCheckConditionEqualOperatorWithArrayValueContainingMatch(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '=', 'values' => 'b', 'group' => null];
		$formData = ['table___myfield_raw' => ['a', 'b', 'c']];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
		]);

		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Equal operator against an array value where the value is absent → false.
	 */
	public function testCheckConditionEqualOperatorWithArrayValueMissingMatch(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '=', 'values' => 'z', 'group' => null];
		$formData = ['table___myfield_raw' => ['a', 'b', 'c']];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
		]);

		$this->assertFalse($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Not-equal operator against an array that contains the value → false.
	 */
	public function testCheckConditionNotEqualOperatorWithArrayValueContainingForbiddenValue(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '!=', 'values' => 'b', 'group' => null];
		$formData = ['table___myfield_raw' => ['a', 'b', 'c']];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
		]);

		$this->assertFalse($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Not-equal operator against an array that does not contain the value → true.
	 */
	public function testCheckConditionNotEqualOperatorWithArrayValueMissingForbiddenValue(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '!=', 'values' => 'z', 'group' => null];
		$formData = ['table___myfield_raw' => ['a', 'b', 'c']];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
		]);

		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Repeat field: key contains both the field name and "repeat" → uses repeatCounter to select value.
	 */
	public function testCheckConditionWithRepeatFieldUsesRepeatCounter(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '=', 'values' => 'second', 'group' => null];
		$formData = ['table___myfield_raw_repeat' => ['first', 'second', 'third']];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
			1, // repeatCounter = 1 → index 1 → 'second'
		]);

		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Repeat field: wrong repeatCounter selects a different value that does not match.
	 */
	public function testCheckConditionWithRepeatFieldWrongCounterDoesNotMatch(): void
	{
		$cond = (object)['field' => 'myfield', 'state' => '=', 'values' => 'second', 'group' => null];
		$formData = ['table___myfield_raw_repeat' => ['first', 'second', 'third']];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond],
			$formData,
			'OR',
			0, // repeatCounter = 0 → 'first' ≠ 'second'
		]);

		$this->assertFalse($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * OR rule group: any passing ungrouped condition produces true.
	 */
	public function testCheckConditionOrRuleGroupReturnsTrueWhenAnyConditionPasses(): void
	{
		$passing = (object)['field' => 'field_a', 'state' => '=', 'values' => 'yes', 'group' => null];
		$failing  = (object)['field' => 'field_b', 'state' => '=', 'values' => 'no', 'group' => null];
		$formData = [
			'table___field_a_raw' => 'yes',
			'table___field_b_raw' => 'other',
		];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $passing, 1 => $failing],
			$formData,
			'OR',
		]);

		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * OR rule group: all conditions fail → false.
	 */
	public function testCheckConditionOrRuleGroupReturnsFalseWhenAllConditionsFail(): void
	{
		$cond1 = (object)['field' => 'field_a', 'state' => '=', 'values' => 'yes', 'group' => null];
		$cond2 = (object)['field' => 'field_b', 'state' => '=', 'values' => 'yes', 'group' => null];
		$formData = [
			'table___field_a_raw' => 'no',
			'table___field_b_raw' => 'no',
		];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond1, 1 => $cond2],
			$formData,
			'OR',
		]);

		$this->assertFalse($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * AND rule group: no condition fails → returns false (all pass, in_array(false, [true, true]) = false).
	 * Note: this is the current implementation behaviour where AND returns true only when at least one
	 * condition group fails, which mirrors the inverse semantic used in checkNotEmptyRules (!$result).
	 */
	public function testCheckConditionAndRuleGroupReturnsFalseWhenAllConditionsPass(): void
	{
		$cond1 = (object)['field' => 'field_a', 'state' => '=', 'values' => 'yes', 'group' => null];
		$cond2 = (object)['field' => 'field_b', 'state' => '=', 'values' => 'ok', 'group' => null];
		$formData = [
			'table___field_a_raw' => 'yes',
			'table___field_b_raw' => 'ok',
		];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond1, 1 => $cond2],
			$formData,
			'AND',
		]);

		// in_array(false, [true, true]) = false
		$this->assertFalse($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * AND rule group: at least one condition fails → returns true (in_array(false, [..., false, ...]) = true).
	 */
	public function testCheckConditionAndRuleGroupReturnsTrueWhenAnyConditionFails(): void
	{
		$cond1 = (object)['field' => 'field_a', 'state' => '=', 'values' => 'yes', 'group' => null];
		$cond2 = (object)['field' => 'field_b', 'state' => '=', 'values' => 'ok', 'group' => null];
		$formData = [
			'table___field_a_raw' => 'yes',
			'table___field_b_raw' => 'wrong',
		];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[0 => $cond1, 1 => $cond2],
			$formData,
			'AND',
		]);

		// in_array(false, [true, false]) = true
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Condition with grouped items (array value under a group key) — requires getGroupType DB call.
	 * We insert a real group row, run the test, then clean up in tearDown.
	 */
	public function testCheckConditionWithGroupedConditionsAndOrGroupType(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery(
			$db->getQuery(true)
				->insert($db->quoteName('#__emundus_setup_form_rules_js_conditions_group'))
				->columns([$db->quoteName('group_type')])
				->values($db->quote('OR'))
		);
		$db->execute();
		$groupId = (int) $db->insertid();
		$this->insertedGroupIds[] = $groupId;

		$cond1 = (object)['field' => 'field_a', 'state' => '=', 'values' => 'yes', 'group' => $groupId];
		$cond2 = (object)['field' => 'field_b', 'state' => '=', 'values' => 'ok', 'group' => $groupId];
		$formData = [
			'table___field_a_raw' => 'yes',  // passes
			'table___field_b_raw' => 'wrong', // fails
		];

		// grouped structure: [$groupId => [$cond1, $cond2]]
		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[$groupId => [$cond1, $cond2]],
			$formData,
			'OR', // ruleGroup
		]);

		// group_type=OR → at least one subcondition passes → condition_state=[true]
		// ruleGroup=OR → in_array(true, [true]) = true
		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkCondition
	 * Grouped conditions with AND group type — all subconditions must pass.
	 */
	public function testCheckConditionWithGroupedConditionsAndAndGroupType(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery(
			$db->getQuery(true)
				->insert($db->quoteName('#__emundus_setup_form_rules_js_conditions_group'))
				->columns([$db->quoteName('group_type')])
				->values($db->quote('AND'))
		);
		$db->execute();
		$groupId = (int) $db->insertid();
		$this->insertedGroupIds[] = $groupId;

		$cond1 = (object)['field' => 'field_a', 'state' => '=', 'values' => 'yes', 'group' => $groupId];
		$cond2 = (object)['field' => 'field_b', 'state' => '=', 'values' => 'ok', 'group' => $groupId];
		$formData = [
			'table___field_a_raw' => 'yes',
			'table___field_b_raw' => 'ok',
		];

		$result = self::callPrivateMethod($this->service, 'checkCondition', [
			[$groupId => [$cond1, $cond2]],
			$formData,
			'OR', // ruleGroup
		]);

		// group_type=AND → all subconditions pass → condition_state=[true]
		// ruleGroup=OR → in_array(true, [true]) = true
		$this->assertTrue($result);
	}

	// =========================================================================
	// getRules — public method, DB required
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getRules
	 */
	public function testGetRulesWithoutFiltersReturnsArray(): void
	{
		$rules = $this->service->getRules();

		$this->assertIsArray($rules);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getRules
	 */
	public function testGetRulesWithStringFieldFilterReturnsArray(): void
	{
		$rules = $this->service->getRules(['fields' => 'non_existent_field_xyz']);

		$this->assertIsArray($rules);
		$this->assertEmpty($rules);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getRules
	 */
	public function testGetRulesWithArrayActionFilterReturnsArray(): void
	{
		$rules = $this->service->getRules([
			'action' => ['set_optional', 'set_mandatory'],
		]);

		$this->assertIsArray($rules);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getRules
	 */
	public function testGetRulesWithSingleActionFilterReturnsArray(): void
	{
		$rules = $this->service->getRules(['action' => 'set_optional']);

		$this->assertIsArray($rules);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getRules
	 */
	public function testGetRulesWithCombinedFiltersReturnsArray(): void
	{
		$rules = $this->service->getRules([
			'fields' => 'non_existent_field_xyz',
			'action' => ['set_optional', 'set_mandatory'],
		]);

		$this->assertIsArray($rules);
		$this->assertEmpty($rules);
	}

	// =========================================================================
	// getConditions — public method, DB required
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getConditions
	 */
	public function testGetConditionsWithNonExistentRuleIdReturnsEmptyArray(): void
	{
		$conditions = $this->service->getConditions(0);

		$this->assertIsArray($conditions);
		$this->assertEmpty($conditions);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getConditions
	 */
	public function testGetConditionsWithFormIdFilterReturnsArray(): void
	{
		$conditions = $this->service->getConditions(0, 999);

		$this->assertIsArray($conditions);
	}

	// =========================================================================
	// getGroupType — public method, DB required
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getGroupType
	 */
	public function testGetGroupTypeReturnsNullForNonExistentId(): void
	{
		$type = $this->service->getGroupType(0);

		$this->assertNull($type);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getGroupType
	 */
	public function testGetGroupTypeReturnsExpectedTypeForInsertedRow(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery(
			$db->getQuery(true)
				->insert($db->quoteName('#__emundus_setup_form_rules_js_conditions_group'))
				->columns([$db->quoteName('group_type')])
				->values($db->quote('AND'))
		);
		$db->execute();
		$groupId = (int) $db->insertid();
		$this->insertedGroupIds[] = $groupId;

		$type = $this->service->getGroupType($groupId);

		$this->assertSame('AND', $type);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::getGroupType
	 */
	public function testGetGroupTypeReturnsOrForOrGroup(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery(
			$db->getQuery(true)
				->insert($db->quoteName('#__emundus_setup_form_rules_js_conditions_group'))
				->columns([$db->quoteName('group_type')])
				->values($db->quote('OR'))
		);
		$db->execute();
		$groupId = (int) $db->insertid();
		$this->insertedGroupIds[] = $groupId;

		$type = $this->service->getGroupType($groupId);

		$this->assertSame('OR', $type);
	}

	// =========================================================================
	// checkNotEmptyRules — public method
	// =========================================================================

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkNotEmptyRules
	 * When no rule targets the given field, the method always returns true (field is required by default).
	 */
	public function testCheckNotEmptyRulesReturnsTrueWhenNoRulesMatchField(): void
	{
		$result = $this->service->checkNotEmptyRules(
			'non_existent_field_xyz',
			['table___non_existent_field_xyz_raw' => 'some_value'],
			0
		);

		$this->assertTrue($result);
	}

	/**
	 * @covers \Tchooz\Services\Fabrik\ConditionService::checkNotEmptyRules
	 * Empty form data — no matching key in formData — still returns true (no rule applies).
	 */
	public function testCheckNotEmptyRulesReturnsTrueWithEmptyFormData(): void
	{
		$result = $this->service->checkNotEmptyRules(
			'non_existent_field_xyz',
			[],
			0
		);

		$this->assertTrue($result);
	}
}
