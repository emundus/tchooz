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

	public function testAdd()
	{
		$workflow_id = $this->model->add();
		$this->assertIsInt($workflow_id);
		$this->assertGreaterThan(0, $workflow_id);
	}

	public function testDelete()
	{
		$workflow_id = $this->model->add();
		$this->assertNotEmpty($workflow_id);

		$deleted = $this->model->delete($workflow_id, 1, true);
		$this->assertTrue($deleted);

		$workflow = $this->model->getWorkflow($workflow_id);
		$this->assertEmpty($workflow);
	}

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
	}

	public function testDuplicateWorkflow()
	{
		$new_workflow_id = $this->model->duplicateWorkflow(1);
		$this->assertNotEmpty($new_workflow_id, 'Duplicating a complete workflow should return a value');

		$new_workflow_id = $this->model->duplicateWorkflow(999999999);
		$this->assertEmpty($new_workflow_id, 'Duplicating a non-existing workflow should return an empty value');

		$workflow_id = $this->model->add('Workflow qui doit être dupliqué');
		$this->assertNotEmpty($workflow_id);

		$new_workflow_id = $this->model->duplicateWorkflow($workflow_id);
		$this->assertNotEmpty($new_workflow_id, 'Duplicating an existing workflow should return a new workflow id');

		$workflow_data = $this->model->getWorkflow($new_workflow_id);
		$this->assertSame('Workflow qui doit être dupliqué - Copie', $workflow_data['workflow']->label, 'The label of the duplicated workflow should be the same as the original one, with a suffix');

	}

	public function testMigrateDeprecatedCampaignWorkflows() {
		// old Workflows were kind of equals to current Step Object, not Workflow Object
		// before a program could be linked to muliple workflows, now it can only be linked to one
		// before campaigns could be linked to multiple workflows, now they can not be linked to any, it must be througth campaign's program

	}
}