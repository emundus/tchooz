<?php

namespace Unit\Component\Emundus\Class\Entities\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionsAndorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;

class ConditionGroupEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Automation\ConditionGroupEntity::isSatisfied
	 * @return void
	 */
	public function testIsSatisfied(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$fnum = $this->dataset['fnum'];
		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, '1');
		$conditionGroup = new ConditionGroupEntity(1, [$condition], ConditionsAndorEnum::OR);

		$contextData = new ActionTargetEntity($user, $fnum, 0, ['status' => '1']);
		$this->assertTrue($conditionGroup->isSatisfied($contextData));
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ConditionGroupEntity::isSatisfied
	 * @return void
	 */
	public function testIsSatisfiedWithMultipleConditions(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$fnum = $this->dataset['fnum'];
		$condition1 = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, '1');
		$condition2 = new ConditionEntity(2, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'priority', ConditionOperatorEnum::EQUALS, 'high');
		$conditionGroup = new ConditionGroupEntity(1, [$condition1, $condition2], ConditionsAndorEnum::AND);

		$contextData = new ActionTargetEntity($user, $fnum, 0, ['status' => '1', 'priority' => 'high']);
		$this->assertTrue($conditionGroup->isSatisfied($contextData));

		$contextDataFail = new ActionTargetEntity($user, $fnum, 0, ['status' => '1', 'priority' => 'low']);
		$this->assertFalse($conditionGroup->isSatisfied($contextDataFail));

		$conditionGroupOr = new ConditionGroupEntity(1, [$condition1, $condition2], ConditionsAndorEnum::OR);
		$this->assertTrue($conditionGroupOr->isSatisfied($contextDataFail));
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ConditionGroupEntity::isSatisfied
	 * @return void
	 */
	public function testIsSatisfiedWithSubGroupsOfConditions(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$fnum = $this->dataset['fnum'];
		$condition1 = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, '1');
		$condition2 = new ConditionEntity(2, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'priority', ConditionOperatorEnum::EQUALS, 'high');
		$subGroup = new ConditionGroupEntity(2, [$condition2], ConditionsAndorEnum::AND);
		$conditionGroup = new ConditionGroupEntity(1, [$condition1], ConditionsAndorEnum::AND);

		$contextData = new ActionTargetEntity($user, $fnum, 0, ['status' => '1', 'priority' => 'high']);
		$this->assertTrue($conditionGroup->isSatisfied($contextData, [$subGroup]));

		$contextDataFail = new ActionTargetEntity($user, $fnum, 0, ['status' => '1', 'priority' => 'low']);
		$this->assertFalse($conditionGroup->isSatisfied($contextDataFail, [$subGroup]));

		$conditionGroupOr = new ConditionGroupEntity(1, [$condition1], ConditionsAndorEnum::OR);
		$this->assertTrue($conditionGroupOr->isSatisfied($contextDataFail, [$subGroup]));

		$contextDataFail = new ActionTargetEntity($user, $fnum, 0, ['status' => '0', 'priority' => 'low']);
		$this->assertFalse($conditionGroupOr->isSatisfied($contextDataFail, [$subGroup]));
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ConditionGroupEntity::isSatisfied
	 * @return void
	 */
	public function testIsSatisfiedWithMultipleSubGroups(): void
	{
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$fnum = $this->dataset['fnum'];
		$condition1 = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, '1');
		$condition2 = new ConditionEntity(2, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'priority', ConditionOperatorEnum::EQUALS, 'high');
		$condition3 = new ConditionEntity(3, 2, ConditionTargetTypeEnum::CONTEXTDATA, 'category', ConditionOperatorEnum::EQUALS, 'A');
		$subGroup1 = new ConditionGroupEntity(2, [$condition2], ConditionsAndorEnum::AND);
		$subGroup2 = new ConditionGroupEntity(3, [$condition3], ConditionsAndorEnum::AND);
		$conditionGroup = new ConditionGroupEntity(1, [$condition1], ConditionsAndorEnum::AND);

		$contextData = new ActionTargetEntity($user, $fnum, 0, ['status' => '1', 'priority' => 'high', 'category' => 'A']);
		$this->assertTrue($conditionGroup->isSatisfied($contextData, [$subGroup1, $subGroup2]));

		$contextDataFail = new ActionTargetEntity($user, $fnum, 0, ['status' => '1', 'priority' => 'low', 'category' => 'A']);
		$this->assertFalse($conditionGroup->isSatisfied($contextDataFail, [$subGroup1, $subGroup2]));

		$contextDataFail2 = new ActionTargetEntity($user, $fnum, 0, ['status' => '1', 'priority' => 'high', 'category' => 'B']);
		$this->assertFalse($conditionGroup->isSatisfied($contextDataFail2, [$subGroup1, $subGroup2]));

		$conditionGroupOr = new ConditionGroupEntity(1, [$condition1], ConditionsAndorEnum::OR);
		$this->assertTrue($conditionGroupOr->isSatisfied($contextDataFail2, [$subGroup1, $subGroup2]));

		$contextDataFailAll = new ActionTargetEntity($user, $fnum, 0, ['status' => '0', 'priority' => 'low', 'category' => 'B']);
		$this->assertFalse($conditionGroupOr->isSatisfied($contextDataFailAll, [$subGroup1, $subGroup2]));
	}
}