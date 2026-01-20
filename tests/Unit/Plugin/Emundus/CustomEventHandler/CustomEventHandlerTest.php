<?php

/**
 * @package         Joomla.UnitTest
 * @subpackage      Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Unit\Plugin\Emundus\CustomEventHandler;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Event\DispatcherInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionUpdateFileTags;
use Tchooz\Entities\Automation\Actions\ActionUpdateStatus;
use Tchooz\Entities\Automation\AutomationEntity;
use Tchooz\Entities\Automation\ConditionEntity;
use Tchooz\Entities\Automation\ConditionGroupEntity;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Automation\EventsDefinitions\onAfterStatusChangeDefinition;
use Tchooz\Entities\Automation\TargetEntity;
use Tchooz\Entities\Automation\TargetPredefinitions\ApplicantCurrentFilePredefinition;
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Enums\Automation\ConditionsAndorEnum;
use Tchooz\Enums\Automation\ConditionTargetTypeEnum;
use Tchooz\Enums\Automation\TargetTypeEnum;
use Tchooz\Repositories\Automation\AutomationRepository;
use Tchooz\Repositories\Automation\EventsRepository;
use Tchooz\Services\Automation\ActionRegistry;


/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      plgEmundusCustom_event_handler
 */
class CustomEventHandlerTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		$directory = JPATH_BASE . '/plugins/emundus/custom_event_handler/';
		$container = Factory::getContainer();
		$dispatcher = $container->get(DispatcherInterface::class);
		$construct_args = [$dispatcher, []];


		parent::__construct('custom_event_handler', $data, $dataName, 'plgEmundusCustom_event_handler', $directory, $construct_args);
	}


	/**
	 * @covers plgEmundusCustom_event_handler::getEventCategory
	 * @return void
	 */
	public function testgetEventCategory()
	{
		$category = $this->model->getEventCategory('');
		$this->assertEmpty($category);

		$category = $this->model->getEventCategory('onAfterProcess');
		$this->assertEquals('Form', $category);
	}

	/**
	 * @covers plgEmundusCustom_event_handler::checkEventConditions
	 * @return void
	 */
	public function testcheckEventConditions()
	{
		$fnum = $this->dataset['fnum'];

		$conditions = new \stdClass();
		$conditions->condition_1 = new \stdClass();

		$passed = self::callPrivateMethod($this->model, 'checkEventConditions', [$conditions, $fnum]);
		$this->assertFalse($passed, 'If no conditions are set, the event should not pass.');

		$condition = new \stdClass();
		$condition->targeted_column = 'jos_emundus_campaign_candidature.fnum';
		$condition->targeted_value = $fnum;

		$conditions->condition_1 = $condition;
		$passed = self::callPrivateMethod($this->model, 'checkEventConditions', [$conditions, $fnum]);
		$this->assertTrue($passed, 'If the condition is met, the event should pass.');

		$condition->targeted_value = $fnum + 1;
		$passed = self::callPrivateMethod($this->model, 'checkEventConditions', [$conditions, $fnum]);
		$this->assertFalse($passed, 'If the condition is not met, the event should not pass.');

		$condition->targeted_value = $fnum;
		$condition2 = new \stdClass();
		$condition2->targeted_column = 'jos_emundus_setup_programmes.id';
		$condition2->targeted_value = $this->dataset['program']['programme_id'];

		$multiple_conditions = new \stdClass();
		$multiple_conditions->condition_1 = $condition;
		$multiple_conditions->condition_2 = $condition2;

		$passed = self::callPrivateMethod($this->model, 'checkEventConditions', [$multiple_conditions, $fnum]);
		$this->assertTrue($passed, 'If more than one condition, and all of them are met, the event should pass.');

		$condition2->targeted_value = $this->dataset['program']['programme_id'] + 1;
		$passed = self::callPrivateMethod($this->model, 'checkEventConditions', [$multiple_conditions, $fnum]);
		$this->assertFalse($passed, 'If more than one condition, and one of them is not met, the event should not pass.');

		$condition->operator = 'IN';
		$passed = self::callPrivateMethod($this->model, 'checkEventConditions', [$conditions, $fnum]);
		$this->assertTrue($passed, 'Operator IN should work as well.');

		$condition->operator = '!=';
		$passed = self::callPrivateMethod($this->model, 'checkEventConditions', [$conditions, $fnum]);
		$this->assertFalse($passed, 'Operator != should work as well.');

		$condition->operator = '=';

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->select('fe.id, fe.params')
			->from($db->quoteName('#__fabrik_elements', 'fe'))
			->leftJoin($db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON fe.group_id = ffg.group_id')
			->where('ffg.form_id = 102')
			->andWhere('fe.name = ' . $db->quote('fnum'));

		$element = $db->setQuery($query)->loadAssoc();

		$alias = 'test_alias_fnum';

		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__fabrik_elements'))
			->set('alias = ' . $db->quote($alias))
			->where('id = ' . $element['id']);

		$executed = $db->setQuery($query)->execute();

		if ($executed) {
			$condition->targeted_column = 'test_alias_fnum';
			$condition->targeted_value = $fnum;
			$passed = self::callPrivateMethod($this->model, 'checkEventConditions', [$conditions, $fnum]);
			$this->assertTrue($passed, 'Passing the alias of the column should work as well.');
		}
	}

	/**
	 * @covers plgEmundusCustom_event_handler::launchEventAction
	 * @return void
	 */
	public function testlaunchEventAction() {
		$fnum = $this->dataset['fnum'];
		if (empty($fnum))
		{
			$fnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		}

		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [null, '']);
		$this->assertFalse($landed, 'If no action and no fnum is set, the action should not be launched');

		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [null, $fnum]);
		$this->assertFalse($landed, 'If no action is set, the action should not be launched');

		$action = new \stdClass();
		$action->action_type = 'test';

		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [$action, $fnum]);
		$this->assertFalse($landed, 'If action does not exist, the action should not be launched');

		$action->action_type = 'update_file_status';
		$action->new_file_status = '0';
		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [$action, $fnum]);
		$this->assertTrue($landed, 'If action exists and is well configured, the action should be launched');

		$action->new_file_status = '1';
		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [$action, $fnum]);
		$this->assertTrue($landed, 'If action exists and is well configured, the action should be launched');

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();
		$query->select('status')
			->from('#__emundus_campaign_candidature')
			->where('fnum = ' . $db->quote($fnum));

		$status = $db->setQuery($query)->loadResult();
		$this->assertEquals('1', $status, 'The status of the file should have been updated');
	}

	/**
	 * @covers plgEmundusCustom_event_handler::runEventSimpleAction
	 * @return void
	 */
	public function testrunEventSimpleAction()
	{
		$event = new \stdClass();
		$event->type = 'custom';
		$event->custom_actions = [];
		$data = [];

		$status = self::callPrivateMethod($this->model, 'runEventSimpleAction', [$event, $data]);
		$this->assertFalse($status, 'If event is not options or no action is set or no fnum is passed, the action should not be launched');


		$fnum = $this->dataset['fnum'];
		if (empty($fnum))
		{
			$fnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		}

		$data['fnum'] = $fnum;
		$event->type = 'options';
		$custom_action = new \stdClass();
		$event->event = 'onAfterProcess';
		$event->custom_actions = [$custom_action];
		$status = self::callPrivateMethod($this->model, 'runEventSimpleAction', [$event, $data]);
		$this->assertFalse($status, 'If no form ids are set on a Form category event, then the action should not be launched');

		$event->form_ids = [380];
		$data['formid'] = 270;
		$event->custom_actions = [$custom_action];
		$status = self::callPrivateMethod($this->model, 'runEventSimpleAction', [$event, $data]);
		$this->assertFalse($status, 'If the form id is not the same as the one in the data, the action should not be launched');

		$data['formid'] = 380;
		$conditions = new \stdClass();
		$condition = new \stdClass();
		$condition->targeted_column = 'jos_emundus_campaign_candidature.fnum';
		$condition->targeted_value = $fnum;
		$conditions->condition_1 = $condition;
		$custom_action->conditions = $conditions;

		$action = new \stdClass();
		$action->action_type = 'update_file_status';
		$action->new_file_status = '0';

		$custom_action->actions = [$action];
		$event->custom_actions = [$custom_action];

		$status = self::callPrivateMethod($this->model, 'runEventSimpleAction', [$event, $data]);
		$this->assertTrue($status, 'If the conditions are met, the action should be launched');
	}

	/**
	 * @covers plgEmundusCustom_event_handler::operateCondition
	 * @return void
	 */
	public function testOperateCondition()
	{
		$condition = new \stdClass();
		$condition->targeted_column = 'jos_emundus_campaign_candidature.fnum';
		$condition->targeted_value = $this->dataset['fnum'];
		$condition->operator = '=';

		$value = $this->dataset['fnum'];

		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertTrue($result, 'The condition should be met');

		$condition->operator = '!=';
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertFalse($result, 'The condition should not be met');

		$value = 1;
		$condition->operator = 'IN';
		$condition->targeted_value = [1, 2];
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertTrue($result, 'The condition should be met');

		$condition->targeted_value = [2, 3];
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertFalse($result, 'The condition should not be met');

		$condition->operator = 'NOT IN';
		$condition->targeted_value = [2, 3];
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertTrue($result, 'The condition should be met');

		$condition->targeted_value = [1, 2];
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertFalse($result, 'The condition should not be met');

	}

	/**
	 * @covers plgEmundusCustom_event_handler::operateCondition
	 * @return void
	 */
	public function testOperateConditionMultipleTargetValues()
	{
		$condition = new \stdClass();
		$condition->targeted_column = 'jos_emundus_campaign_candidature.fnum';
		$condition->targeted_value = [1, 2];
		$condition->operator = 'IN';

		$value = 1;
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertTrue($result, 'The condition should be met');

		$value = 3;
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertFalse($result, 'The condition should not be met');

		$condition->operator = 'NOT IN';
		$value = 3;
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertTrue($result, 'The condition should be met');

		$value = 1;
		$result = self::callPrivateMethod($this->model, 'operateCondition', [$condition, $value]);
		$this->assertFalse($result, 'The condition should not be met');
	}

	/**
	 * This test is to make sure that automations are not re-run if they have already been executed in the same context.
	 *
	 * @covers plgEmundusCustom_event_handler::runAutomations
	 * @return void
	 */
	public function testRunAutomations(): void
	{
		$this->h_dataset->resetAutomations();
		$fnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		$ran = $this->model->runAutomations('', []);
		$this->assertFalse($ran, 'If no event and no data are passed, the automations should not run.');

		$event = 'onAfterStatusChange';
		$ran = $this->model->runAutomations($event, []);
		$this->assertFalse($ran, 'If no data are passed, the automations should not run.');

		$data = ['context' => new EventContextEntity($user, [$fnum], [], [onAfterStatusChangeDefinition::OLD_STATUS_PARAMETER => 0, onAfterStatusChangeDefinition::STATUS_PARAMETER => 1])];
		$ran = $this->model->runAutomations($event, $data);
		$this->assertTrue($ran, 'If event and data are passed, the automations should run. Even if no automation is set for that event, the method should return true.');

		$automationRepository = new AutomationRepository();
		$eventRepository = new EventsRepository();
		$eventStatus = $eventRepository->getEventByName('onAfterStatusChange');

		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1);
		$conditionGroup = new ConditionGroupEntity(1, [$condition], ConditionsAndorEnum::AND);
		$updateStateAction = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 2]);
		$updateStateAction->addTarget(new TargetEntity(1, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition()));
		$automationUpdateState = new AutomationEntity(0, 'Mis à jour du statut vers 2 si statut 1', '', $eventStatus, [$conditionGroup], [$updateStateAction], true);
		$automationRepository->flush($automationUpdateState);

		$conditionGroup2 = new ConditionGroupEntity(2, [new ConditionEntity(2, 2, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 2)], ConditionsAndorEnum::AND);
		$updateStateAction2 = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 3]);
		$updateStateAction2->addTarget(new TargetEntity(2, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition()));
		$automationUpdateState2 = new AutomationEntity(0, 'Mis à jour du statut vers 3 si statut 2', '', $eventStatus, [$conditionGroup2], [$updateStateAction2], true);
		$automationRepository->flush($automationUpdateState2);

		$ran = $this->model->runAutomations($event, $data);
		$this->assertTrue($ran, 'If event and data are passed, the automations should run.');

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('status'))
			->from($db->quoteName('#__emundus_campaign_candidature'))
			->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

		$db->setQuery($query);
		$status = $db->loadResult();

		$this->assertEquals(3, $status, 'The status should have been updated to 2 by the first automation.');

		$m_logs = new \EmundusModelLogs();
		$statusLogs = $m_logs->getActionsOnFnum($fnum, null, 13);
		$this->assertNotEmpty($statusLogs);
		$this->assertCount(2, $statusLogs, 'There should be two log entries for the two status updates. If there was more, it would mean that the automations ran more than once.');
	}

	/**
	 * If this test does not work, it will do a timeout
	 * @covers plgEmundusCustom_event_handler::runAutomations
	 * @return void
	 */
	public function testRunAutomationsNoLoopPossible(): void
	{
		$this->h_dataset->resetAutomations();
		$fnum = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		$user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		// make 2 automations, 1 change status to 1 if 0, the other change status to 0 if 1
		$automationRepository = new AutomationRepository();
		$eventRepository = new EventsRepository();
		$eventStatus = $eventRepository->getEventByName('onAfterStatusChange');

		$condition = new ConditionEntity(1, 1, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 0);
		$conditionGroup = new ConditionGroupEntity(1, [$condition], ConditionsAndorEnum::AND);
		$updateStateAction = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 1]);
		$updateStateAction->addTarget(new TargetEntity(1, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition()));
		$automationUpdateState = new AutomationEntity(0, 'Mis à jour du statut vers 1 si statut 0', '', $eventStatus, [$conditionGroup], [$updateStateAction], true);
		$automationRepository->flush($automationUpdateState);

		$conditionGroup2 = new ConditionGroupEntity(2, [new ConditionEntity(2, 2, ConditionTargetTypeEnum::CONTEXTDATA, 'status', ConditionOperatorEnum::EQUALS, 1)], ConditionsAndorEnum::AND);
		$updateStateAction2 = new ActionUpdateStatus([ActionUpdateStatus::STATUS_PARAMETER => 0]);
		$updateStateAction2->addTarget(new TargetEntity(2, TargetTypeEnum::FILE, new ApplicantCurrentFilePredefinition()));
		$automationUpdateState2 = new AutomationEntity(0, 'Mis à jour du statut vers 0 si statut 1', '', $eventStatus, [$conditionGroup2], [$updateStateAction2], true);
		$automationRepository->flush($automationUpdateState2);

		$event = 'onAfterStatusChange';
		$data = ['context' => new EventContextEntity($user, [$fnum], [], [onAfterStatusChangeDefinition::OLD_STATUS_PARAMETER => 0, onAfterStatusChangeDefinition::STATUS_PARAMETER => 1])];
		$ran = $this->model->runAutomations($event, $data);
		$this->assertTrue($ran, 'If event and data are passed, the automations should run.');

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->select($db->quoteName('status'))
			->from($db->quoteName('#__emundus_campaign_candidature'))
			->where($db->quoteName('fnum') . ' = ' . $db->quote($fnum));

		$db->setQuery($query);
		$status = $db->loadResult();

		$this->assertEquals(1, $status, 'The status should have been updated to 1 by the two automations, and not looped endlessly.');
		$this->h_dataset->resetAutomations();
	}
}