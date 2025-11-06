<?php

namespace Unit\Component\Emundus\Entities\Automation;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitions\ApplicantCurrentFilePredefinition;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionsAndorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

class AutomationEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Automation\AutomationEntity::process
	 * @return void
	 */
	public function testProcess(): void
	{
		$this->h_dataset->resetAutomations();

		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$newStatus = 2;

		$context = new EventContextEntity($coord, [$fnum], [], ['status' => 1, 'old_status' => 0]);
		$condition = new ConditionEntity(1, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$actionTarget = new TargetEntity(1, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => $newStatus]);
		$action->addTarget($actionTarget);

		$automation = new AutomationEntity();
		$automation->addConditionGroup($conditionGroup);
		$automation->addAction($action);

		try {
			$processed = $automation->process($context);
			$this->assertTrue($processed, 'Automation processed successfully.');

			// the goal was to update the status to 2
			$db = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->getQuery(true);
			$query->select($db->quoteName('status'))
				->from($db->quoteName('#__emundus_campaign_candidature'))
				->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));
			$db->setQuery($query);
			$status = $db->loadResult();
			$this->assertEquals(2, $status, 'Candidature status updated to ' . $newStatus);
		} catch (\Exception $e) {
			$this->fail('Automation processing failed with exception: ' . $e->getMessage());
		}
	}

	/**
	 * @covers \Tchooz\Entities\Automation\AutomationEntity::process
	 * @return void
	 */
	public function testProcessWithFormDataCondition(): void
	{
		$this->h_dataset->resetAutomations();

		$fnum = $this->dataset['fnum'];
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		$statusElementId = $this->h_dataset->getFormElementForTest(102, 'status');
		$context = new EventContextEntity($coord, [$fnum], [], ['status' => 0, 'old_status' => 0]);
		$condition = new ConditionEntity(1, 0, ConditionTargetTypeEnum::FORMDATA, '102.' . $statusElementId, ConditionOperatorEnum::EQUALS, 0);
		$conditionGroup = new ConditionGroupEntity(0, [$condition]);
		$actionTarget = new TargetEntity(1, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition());
		$action = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$action->addTarget($actionTarget);

		$automation = new AutomationEntity();
		$automation->addConditionGroup($conditionGroup);
		$automation->addAction($action);

		try {
			$processed = $automation->process($context);
			$this->assertTrue($processed, 'Automation processed successfully.');
		} catch (\Exception $e) {
			$this->fail('Automation processing failed with exception: ' . $e->getMessage());
		}
	}
}