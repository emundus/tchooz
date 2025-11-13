<?php

namespace Unit\Component\Emundus\Class\Entities\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Services\Automation\ConditionRegistry;

class ConditionEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Automation\ConditionEntity::isSatisfied
	 * @return void
	 */
	public function testIsSatisfied(): void
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$formId = 102;
		$elementId = $this->h_dataset->getFormElementForTest($formId, 'status');
		$fieldName = $formId . '.' . $elementId;
		$condition = new ConditionEntity(1, 0, ConditionTargetTypeEnum::FORMDATA, $fieldName, ConditionOperatorEnum::EQUALS, 0);
		$context = new ActionTargetEntity($coord, $fnum, 0, ['status' => 0, 'old_status' => 0]);

		// file is at status 0, condition expects status 0
		$this->assertTrue($condition->isSatisfied($context), 'Condition should be satisfied when status is 0.');

		$condition->setValue(1);
		// file is at status 0, condition expects status 1
		$this->assertFalse($condition->isSatisfied($context), 'Condition should not be satisfied when status is 1.');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ConditionEntity::isSatisfied
	 * @return void
	 */
	public function testIsSatisfiedOperators(): void
	{
		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$formId = 102;
		$elementId = $this->h_dataset->getFormElementForTest($formId, 'status');
		$fieldName = $formId . '.' . $elementId;
		$condition = new ConditionEntity(1, 0, ConditionTargetTypeEnum::FORMDATA, $fieldName, ConditionOperatorEnum::EQUALS, 0);
		$context = new ActionTargetEntity($coord, $fnum,  (int)$this->dataset['applicant'], ['status' => 0, 'old_status' => 0]);

		// file is at status 0
		$this->assertTrue($condition->isSatisfied($context), 'Condition should be satisfied when status is 0 and operator is "=".');

		$condition->setOperator(ConditionOperatorEnum::NOT_EQUALS);
		$this->assertFalse($condition->isSatisfied($context), 'Condition should not be satisfied when status is 0 and operator is "!=".');

		$condition->setOperator(ConditionOperatorEnum::GREATER_THAN);
		$this->assertFalse($condition->isSatisfied($context), 'Condition should not be satisfied when status is 0 and operator is ">".');

		$condition->setOperator(ConditionOperatorEnum::LESS_THAN);
		$this->assertFalse($condition->isSatisfied($context), 'Condition should not be satisfied when status is 0 and operator is "<".');

		$condition->setOperator(ConditionOperatorEnum::GREATER_THAN_OR_EQUAL);
		$this->assertTrue($condition->isSatisfied($context), 'Condition should be satisfied when status is 0 and operator is ">=".');

		$condition->setOperator(ConditionOperatorEnum::LESS_THAN_OR_EQUAL);
		$this->assertTrue($condition->isSatisfied($context), 'Condition should be satisfied when status is 0 and operator is "<=".');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\ConditionEntity::transformValue
	 * @return void
	 */
	public function testTransformValue(): void
	{
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$fnum = $this->dataset['fnum'];
		$campaignId = $this->dataset['campaign'];
		$applicantId = (int)$this->dataset['applicant'];
		$fnum2 = $this->h_dataset->createSampleFile($this->dataset['campaign'], $applicantId);

		$originalContext = new ActionTargetEntity($coord, $fnum, null, []);
		$actionContext = new ActionTargetEntity($coord, $fnum2, null, [], null, $originalContext);
		$condition = new ConditionEntity(1, 0, ConditionTargetTypeEnum::CAMPAIGNDATA, 'id', ConditionOperatorEnum::EQUALS, ConditionEntity::SAME_AS_CURRENT_FILE);
		$resolver = (new ConditionRegistry())->getResolver(ConditionTargetTypeEnum::CAMPAIGNDATA->value);

		$this->assertEquals($campaignId, $condition->getTransformedValue($actionContext, $resolver), 'Condition value should be transformed to the campaign id of the original context.');
		$this->assertTrue($condition->isSatisfied($actionContext), 'Condition should be satisfied when campaign id matches SAME_AS_CURRENT_FILE.');

		$newCampaign = $this->h_dataset->createSampleCampaign($this->dataset['program'], $this->dataset['coordinator']);
		$fnumWithAnotherCampaign = $this->h_dataset->createSampleFile($newCampaign, $applicantId);
		$actionContextDifferentCampaign = new ActionTargetEntity($coord, $fnumWithAnotherCampaign, null, [], null, $originalContext);

		$actionContext->setOriginalContext($actionContextDifferentCampaign);

		$this->assertFalse($condition->isSatisfied($actionContext), 'Condition should not be satisfied when campaign id does not match SAME_AS_CURRENT_FILE.');
		$this->assertEquals($newCampaign, $condition->getTransformedValue($actionContext, $resolver), 'Condition value should be transformed to the new campaign id of the original context.');


		$condition->setValue([ConditionEntity::SAME_AS_CURRENT_FILE, 9999]);
		$actionContext->setOriginalContext($originalContext);
		$this->assertTrue($condition->isSatisfied($actionContext), 'Condition should be satisfied when campaign id is in the array including SAME_AS_CURRENT_FILE.');
		$this->assertContains($campaignId, $condition->getTransformedValue($actionContext, $resolver), 'Condition value should contain the campaign id of the original context.');
	}
}