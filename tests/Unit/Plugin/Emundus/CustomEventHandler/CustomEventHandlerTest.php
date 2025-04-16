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
use Joomla\Event\DispatcherInterface;
use Joomla\Tests\Unit\UnitTestCase;


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

		$params = json_decode($element['params']);
		$params->alias = 'test_alias_fnum';

		$query = $db->getQuery(true);
		$query->update($db->quoteName('#__fabrik_elements'))
			->set('params = ' . $db->quote(json_encode($params)))
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
		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [null, '']);
		$this->assertFalse($landed, 'If no action and no fnum is set, the action should not be launched');

		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [null, $this->dataset['fnum']]);
		$this->assertFalse($landed, 'If no action is set, the action should not be launched');

		$action = new \stdClass();
		$action->action_type = 'test';

		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [$action, $this->dataset['fnum']]);
		$this->assertFalse($landed, 'If action does not exist, the action should not be launched');

		$action->action_type = 'update_file_status';
		$action->new_file_status = '0';
		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [$action, $this->dataset['fnum']]);
		$this->assertTrue($landed, 'If action exists and is well configured, the action should be launched');

		$action->new_file_status = '1';
		$landed = self::callPrivateMethod($this->model, 'launchEventAction', [$action, $this->dataset['fnum']]);
		$this->assertTrue($landed, 'If action exists and is well configured, the action should be launched');

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();
		$query->select('status')
			->from('#__emundus_campaign_candidature')
			->where('fnum = ' . $db->quote($this->dataset['fnum']));

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

		$data['fnum'] = $this->dataset['fnum'];
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
		$condition->targeted_value = $this->dataset['fnum'];
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
}