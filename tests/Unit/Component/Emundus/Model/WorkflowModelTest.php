<?php

/**
 * @package         Joomla.UnitTest
 * @subpackage      Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Unit\Component\Emundus\Model;

use EmundusModelApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use stdClass;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelApplication
 */
class WorkflowModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('workflow', $data, $dataName, 'EmundusModelWorkflow');
	}

	/**
	 * @covers EmundusModelWorkflow::add
	 * @return void
	 */
	public function testAdd()
	{
		$workflow_id = $this->model->add();
		$this->assertIsInt($workflow_id);
		$this->assertGreaterThan(0, $workflow_id);
	}

	/**
	 * @covers EmundusModelWorkflow::getWorkflow
	 */
	public function testGetWorkflow()
	{
		$workflow_id = $this->model->add();
		$this->assertNotEmpty($workflow_id);

		$workflow_data = $this->model->getWorkflow($workflow_id);
		$this->assertNotEmpty($workflow_data);
		$this->assertNotEmpty($workflow_data['workflow']);
		$this->assertEquals($workflow_id, $workflow_data['workflow']->id);

		// by default steps and programs should be empty, but keys should exist
		$this->assertArrayHasKey('steps', $workflow_data);
		$this->assertEmpty($workflow_data['steps']);

		$this->assertArrayHasKey('programs', $workflow_data);
		$this->assertEmpty($workflow_data['programs']);
	}

	/**
	 * @covers EmundusModelWorkflow::getWorkflows
	 */
	public function testGetWorkflows()
	{
		$workflows = $this->model->getWorkflows();
		$this->assertNotEmpty($workflows);
		$this->assertIsArray($workflows);

		$workflow_id = $this->model->add('Workflow A');
		$this->assertNotEmpty($workflow_id);

		$workflows = $this->model->getWorkflows([$workflow_id]);
		$this->assertNotEmpty($workflows);
		$this->assertIsArray($workflows);
		$this->assertCount(1, $workflows);
		$this->assertEquals($workflow_id, $workflows[0]->id);

		$this->model->add('Workflow B');

		$workflows_order_by_label = $this->model->getWorkflows([], 0, 0, [], 'esw.label', 'ASC');
		$this->assertNotEmpty($workflows_order_by_label);

		$workflows_order_by_label_desc = $this->model->getWorkflows([], 0, 0, [], 'esw.label', 'DESC');
		$this->assertNotEmpty($workflows_order_by_label_desc);
		$this->assertNotEquals($workflows_order_by_label, $workflows_order_by_label_desc);

		$workflows_default = $this->model->getWorkflows();
		$workflows_order_by_wrong_column = $this->model->getWorkflows([], 0, 0, [], 'esw.not_allowed', 'something_else');
		$this->assertNotEmpty($workflows_order_by_wrong_column);
		$this->assertEquals($workflows_default, $workflows_order_by_wrong_column, 'If the order column values are not allowed, the result should be the same as the default one');
	}

	/**
	 * @covers EmundusModelWorkflow::delete
	 */
	public function testDelete()
	{
		$workflow_id = $this->model->add();
		$this->assertNotEmpty($workflow_id);

		$deleted = $this->model->delete($workflow_id, 1, true);
		$this->assertTrue($deleted);

		$workflow = $this->model->getWorkflow($workflow_id);
		$this->assertEmpty($workflow);
	}

	/**
	 * @covers EmundusModelWorkflow::updateWorkflow
	 */
	public function testUpdateWorkflow()
	{
		$workflow_id = $this->model->add();
		$this->assertNotEmpty($workflow_id);

		$workflow = [
			'id' => $workflow_id,
			'label' => 'Test Workflow',
			'published' => 1
		];

		$updated = $this->model->updateWorkflow($workflow, [], []);
		$this->assertTrue($updated);

		$workflow_data = $this->model->getWorkflow($workflow_id);
		$this->assertNotEmpty($workflow_data['workflow']);
		$this->assertEquals('Test Workflow', $workflow_data['workflow']->label);
		$this->assertEquals(1, $workflow_data['workflow']->published);

		$step = [
			'id' => 0,
			'entry_status' => [['id' => 0]],
			'type' => 1,
			'profile_id' => '1000',
			'label' => 'Test Step',
			'output_status' => 1
		];
		$updated = $this->model->updateWorkflow($workflow, [$step], []);
		$this->assertTrue($updated, 'Adding a step to the workflow worked');

		$program = $this->h_dataset->createSampleProgram();
		$updated = $this->model->updateWorkflow($workflow, [], [['id' => $program['programme_id']]]);
		$this->assertTrue($updated, 'Adding a program to the workflow worked');
	}

	/**
	 * @covers EmundusModelWorkflow::updateWorkflow only exception thrown case when adding two steps with same entry status
	 *
	 * @return void
	 */
	public function testExceptionUpdateWorkflow()
	{
		$workflow_id = $this->model->add();
		$this->assertNotEmpty($workflow_id);

		$workflow = [
			'id' => $workflow_id,
			'label' => 'Test Workflow',
			'published' => 1
		];

		$step = [
			'id' => 0,
			'entry_status' => [['id' => 0, 'label' => 'Test Status']],
			'type' => 1,
			'profile_id' => '1000',
			'label' => 'Test Step',
			'output_status' => 1
		];

		// adding two steps with same entry status should throw an exception
		$this->expectException(\Exception::class);
		$this->model->updateWorkflow($workflow, [$step, $step], []);
	}

	/**
	 * @covers EmundusModelWorkflow::getCurrentWorkflowStepFromFile
	 * @return void
	 */
	public function testGetCurrentWorkflowStepFromFile()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign_id = $this->h_dataset->createSampleCampaign($program);
		$this->assertNotEmpty($campaign_id);

		$user_id = $this->h_dataset->createSampleUser(1000, 'testuser' . time() . '@emundus.fr');
		$this->assertNotEmpty($user_id);

		$fnum = $this->h_dataset->createSampleFile($campaign_id, $user_id);
		$this->assertNotEmpty($fnum);

		$step = $this->model->getCurrentWorkflowStepFromFile($fnum, 1, 'fnum');
		$this->assertEmpty($step, 'No step should be returned for a file that is not associated to a workflow');

		$workflow_id = $this->model->add();
		$workflow = [
			'id' => $workflow_id,
			'label' => 'Test Workflow',
			'published' => 1
		];
		$steps = [[
			'id' => 0,
			'entry_status' => [['id' => 0]],
			'type' => 1,
			'profile_id' => '1000',
			'label' => 'Test Step',
			'output_status' => 1
		]];
		$programs = [[
			'id' => $program['programme_id']
		]];
		$updated = $this->model->updateWorkflow($workflow, $steps, $programs);
		$this->assertTrue($updated, 'Adding a step to the workflow worked');

		$step = $this->model->getCurrentWorkflowStepFromFile($fnum);
		$this->assertNotEmpty($step, 'A step should be returned for a file that is associated to a workflow');

		$this->assertEquals('Test Step', $step->label);
		$this->assertEquals('1000', $step->profile_id, 'The profile_id should be set to the one from the step');

		// adding another step on another status should not change the result of the getCurrentWorkflowStepFromFile
		$workflow_data = $this->model->getWorkflow($workflow_id);
		$steps[0]['id'] = $workflow_data['steps'][0]->id;

		$steps[] = [
			'id' => 0,
			'entry_status' => [['id' => 1]],
			'type' => 1,
			'profile_id' => '1000',
			'label' => 'Test Step 2',
			'output_status' => 1
		];

		$updated = $this->model->updateWorkflow($workflow, $steps, $programs);
		$this->assertTrue($updated, 'Adding a step to the workflow worked');

		$step = $this->model->getCurrentWorkflowStepFromFile($fnum);
		$this->assertNotEmpty($step, 'A step should be returned for a file that is associated to a workflow');
		$this->assertEquals('1000', $step->profile_id, 'The profile_id should be set to the one from the first step');
		$this->assertEquals($step->id, $steps[0]['id'], 'The profile_id should be set to the one from the first step');
	}

	/**
	 * @covers EmundusModelWorkflow::duplicateWorkflow
	 */
	public function testDuplicateWorkflow()
	{
		$new_workflow_id = $this->model->duplicateWorkflow(999999999);
		$this->assertEmpty($new_workflow_id, 'Duplicating a non-existing workflow should return an empty value');

		$workflow_id = $this->model->add('Workflow qui doit être dupliqué');
		$this->assertNotEmpty($workflow_id);

		$new_workflow_id = $this->model->duplicateWorkflow($workflow_id);
		$this->assertNotEmpty($new_workflow_id, 'Duplicating an existing workflow should return a new workflow id');

		$workflow_data = $this->model->getWorkflow($new_workflow_id);
		$this->assertSame('Workflow qui doit être dupliqué - Copie', $workflow_data['workflow']->label, 'The label of the duplicated workflow should be the same as the original one, with a suffix');

	}

	/**
	 * @covers EmundusModelWorkflow::getStepTypes
	 */
	public function testGetStepTypes()
	{
		$step_types = $this->model->getStepTypes();
		$this->assertNotEmpty($step_types);
		$this->assertIsArray($step_types);
		$this->assertGreaterThanOrEqual(2, count($step_types), 'There should be at least 2 step types, one for the applicant, and one for evaluators');
	}

	/**
	 * @covers EmundusModelWorkflow::getCampaignSteps
	 */
	public function testGetCampaignSteps()
	{
		$program = $this->h_dataset->createSampleProgram();
		$campaign_id = $this->h_dataset->createSampleCampaign($program);
		$this->assertNotEmpty($campaign_id);

		$steps = $this->model->getCampaignSteps($campaign_id);
		$this->assertEmpty($steps, 'No steps should be returned for a campaign that is not associated to a workflow');

		$workflow_id = $this->model->add();
		$workflow = [
			'id' => $workflow_id,
			'label' => 'Test Workflow',
			'published' => 1
		];

		$steps = [[
			'id' => 0,
			'entry_status' => [['id' => 0]],
			'type' => 1,
			'profile_id' => '1000',
			'label' => 'Test Step',
			'output_status' => 1
		]];

		$programs = [[
			'id' => $program['programme_id']
		]];

		$updated = $this->model->updateWorkflow($workflow, $steps, $programs);
		$this->assertTrue($updated, 'Adding a step to the workflow worked');

		$steps = $this->model->getCampaignSteps($campaign_id);
		$this->assertNotEmpty($steps, 'Steps should be returned for a campaign that is associated to a workflow');
		$this->assertCount(1, $steps);
		$this->assertEquals('Test Step', $steps[0]->label);
	}

	public function testGetEvaluationStepDataForFnum()
	{
		$data = $this->model->getEvaluationStepDataForFnum($this->dataset['fnum'], 0, []);
		$this->assertIsArray($data);

		if (!class_exists('EmundusModelForm'))
		{
			require_once JPATH_ROOT . '/components/com_emundus/models/form.php';
		}
		$m_form = new \EmundusModelForm();
		$eval_form_id = $m_form->createFormEval(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$this->assertNotEmpty($eval_form_id, 'Evaluation form creation succeeds');

		$workflow_id = $this->model->add();
		$workflow = [
			'id' => $workflow_id,
			'label' => 'Test Workflow',
			'published' => 1
		];
		$steps = [[
			'id'           => 0,
			'entry_status' => [['id' => 1]],
			'type'         => 2,
			'form_id'      => $eval_form_id,
			'label'        => 'Test Evaluation Step',
			'output_status' => 0
		]];
		$updated = $this->model->updateWorkflow($workflow, $steps, [[
			'id' =>	$this->dataset['program']['programme_id']]
		]);
		$this->assertTrue($updated, 'Adding an evaluation step to the workflow worked');

		$workflow = $this->model->getWorkflow($workflow_id);
		$step = $workflow['steps'][0];
		$this->assertNotEmpty($step);

		$query = $this->db->getQuery(true)
			->select('jfe.id')
			->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
			->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON ' . $this->db->quoteName('jffg.group_id') . ' = ' . $this->db->quoteName('jfe.group_id'))
			->where($this->db->quoteName('jffg.form_id') . ' = ' . $this->db->quote($step->form_id));
		$element_ids = $this->db->setQuery($query)->loadColumn();
		$this->assertNotEmpty($element_ids, 'Element ids should be returned for the evaluation form');

		$data = $this->model->getEvaluationStepDataForFnum($this->dataset['fnum'], $step->id, $element_ids);
		$this->assertNotEmpty($data, 'Data should be returned for an evaluation step, even if there is no data yet');
	}
}