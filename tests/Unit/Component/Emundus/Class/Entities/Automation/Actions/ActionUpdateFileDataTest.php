<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateFileData;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Enums\Automation\ActionExecutionStatusEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Services\Automation\Condition\FormDataConditionResolver;

class ActionUpdateFileDataTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateFileData::execute
	 * @covers \Tchooz\Entities\Automation\ActionEntity::getParameterValue
	 * @covers \Tchooz\Entities\Automation\ActionEntity::getParameter
	 */
	public function testActionUpdateFileDataExecute(): void
	{
		$formId = $this->h_dataset->getUnitTestFabrikForm();
		$this->h_dataset->insertUnitTestFormData($this->dataset['applicant'], $this->dataset['fnum']);
		$elementId = $this->h_dataset->getFormElementForTest($formId, 'e_797_7973');

		$fnum  = $this->dataset['fnum'];
		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		$newValue = rand(1000, 9999);

		$context = new ActionTargetEntity($coordinator, $fnum, 0, []);

		$action = new ActionUpdateFileData([
			'type'  => ConditionTargetTypeEnum::FORMDATA->value,
			'field' => $formId . '.' . $elementId,
			'value' => $newValue,
		]);

		$result = $action->execute($context);
		$this->assertEquals(ActionExecutionStatusEnum::COMPLETED, $result);

		try {
			$formDataResolver = new FormDataConditionResolver();
			$retrievedValue = $formDataResolver->resolveValue($context, $formId. '.' . $elementId);
			$this->assertEquals($newValue, $retrievedValue);
		}catch (\Exception $e)
		{
			$this->fail('Exception thrown while retrieving form data: ' . $e->getMessage());
		}
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionUpdateFileData::execute
	 */
	public function testExecuteWithoutFileReturnsFailed(): void
	{
		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		$context = new ActionTargetEntity($coordinator, '', 0, []);

		$action = new ActionUpdateFileData([
			'type'  => ConditionTargetTypeEnum::FORMDATA->value,
			'field' => '1.999',
			'value' => 'X',
		]);

		$result = $action->execute($context);

		$this->assertEquals(ActionExecutionStatusEnum::FAILED, $result);
	}
}