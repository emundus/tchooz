<?php

namespace Unit\Component\Emundus\Class\Entities\Automation;

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
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;

// Test classes are not autoloaded, PHPUnit only loads the *Test.php files themselves.
require_once __DIR__ . '/MessageEmittingAction.php';

/**
 * @package     Unit\Component\Emundus\Class\Entities\Automation
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Automation\AutomationEntity
 */
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

	/**
	 * @covers \Tchooz\Entities\Automation\AutomationEntity::process
	 * @return void
	 */
	public function testProcessReportsExecutionMessagesOfSynchronousActions(): void
	{
		$this->h_dataset->resetAutomations();

		$fnum    = $this->dataset['fnum'];
		$coord   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new EventContextEntity($coord, [$fnum], [], ['status' => 1, 'old_status' => 0]);

		$report = $this->processAndCaptureReport($context, new MessageEmittingAction());

		$this->assertCount(1, $report['successful_actions'], 'The action should be reported once');
		$messages = $report['successful_actions'][0]['messages'];
		$this->assertCount(1, $messages, 'The message emitted during the run should be reported');
		$this->assertEquals('warning', $messages[0]['type']);
		$this->assertEquals('Executed for ' . $fnum, $messages[0]['message']);
	}

	/**
	 * The `messages` key must always be there, empty included, so the history view never guards it.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationEntity::process
	 * @return void
	 */
	public function testProcessAlwaysReportsAMessagesKey(): void
	{
		$this->h_dataset->resetAutomations();

		$coord   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new EventContextEntity($coord, [$this->dataset['fnum']], [], ['status' => 1, 'old_status' => 0]);

		$report = $this->processAndCaptureReport($context, new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 2]));

		$this->assertArrayHasKey('messages', $report['successful_actions'][0], 'An action emitting nothing still carries the key');
		$this->assertSame([], $report['successful_actions'][0]['messages']);
	}

	/**
	 * The action instance is reused for every target of the automation, and messages are kept on it:
	 * without a reset between runs, the second target would inherit the messages of the first.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationEntity::process
	 * @covers \Tchooz\Entities\Automation\ActionEntity::resetExecutionMessages
	 * @return void
	 */
	public function testProcessDoesNotLeakExecutionMessagesBetweenTargets(): void
	{
		$this->h_dataset->resetAutomations();

		$firstFnum  = $this->dataset['fnum'];
		$secondFnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$this->assertNotEmpty($secondFnum, 'A second file is needed to observe the leak');

		$coord   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new EventContextEntity($coord, [$firstFnum, $secondFnum], [], ['status' => 1, 'old_status' => 0]);

		$report = $this->processAndCaptureReport($context, new MessageEmittingAction());

		$this->assertCount(2, $report['successful_actions'], 'Both targets should be reported');
		foreach ($report['successful_actions'] as $reportedAction) {
			$this->assertCount(1, $reportedAction['messages'], 'Each target carries only the message of its own run');
			$this->assertEquals(
				'Executed for ' . $reportedAction['context']['file'],
				$reportedAction['messages'][0]['message'],
				'The reported message should be the one emitted for that very target'
			);
		}
	}

	/**
	 * The report is persisted as one JSON blob in a TEXT column: INFO messages are per-run tracing and
	 * must stay in the log files rather than inflate every history row.
	 *
	 * @covers \Tchooz\Entities\Automation\AutomationEntity::process
	 * @return void
	 */
	public function testProcessDoesNotReportInfoMessages(): void
	{
		$this->h_dataset->resetAutomations();

		$coord   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new EventContextEntity($coord, [$this->dataset['fnum']], [], ['status' => 1, 'old_status' => 0]);

		$action           = new MessageEmittingAction();
		$action->warnings = 1;
		$action->infos    = 3;

		$report   = $this->processAndCaptureReport($context, $action);
		$messages = $report['successful_actions'][0]['messages'];

		$this->assertCount(1, $messages, 'Only the actionable message should be reported');
		$this->assertEquals('warning', $messages[0]['type']);
	}

	/**
	 * @covers \Tchooz\Entities\Automation\AutomationEntity::process
	 * @return void
	 */
	public function testProcessTruncatesTooManyMessagesAndSaysSo(): void
	{
		$this->h_dataset->resetAutomations();

		$coord   = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$context = new EventContextEntity($coord, [$this->dataset['fnum']], [], ['status' => 1, 'old_status' => 0]);

		$action           = new MessageEmittingAction();
		$action->warnings = 8;

		$report   = $this->processAndCaptureReport($context, $action);
		$messages = $report['successful_actions'][0]['messages'];

		$this->assertCount(6, $messages, 'Five messages are kept, plus the notice saying what was dropped');
		$this->assertStringContainsString('3', end($messages)['message'], 'The notice should name the 3 dropped messages');
	}

	/**
	 * Run an automation made of the single given action and return the payload the history is built
	 * from, captured on the onAfterAutomationProcessed event.
	 */
	private function processAndCaptureReport(EventContextEntity $context, $action): array
	{
		$action->addTarget(new TargetEntity(1, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition()));

		$automation = new AutomationEntity();
		$automation->addConditionGroup(new ConditionGroupEntity(0, [
			new ConditionEntity(1, 0, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1),
		]));
		$automation->addAction($action);

		$report     = [];
		$dispatcher = Factory::getApplication()->getDispatcher();
		$listener   = function ($event) use (&$report) {
			$report = $event->getArgument('context')->getParameters();
		};
		$dispatcher->addListener('onAfterAutomationProcessed', $listener);

		try {
			$automation->process($context);
		} finally {
			$dispatcher->removeListener('onAfterAutomationProcessed', $listener);
		}

		$this->assertNotEmpty($report, 'onAfterAutomationProcessed should have been dispatched');

		return $report;
	}
}