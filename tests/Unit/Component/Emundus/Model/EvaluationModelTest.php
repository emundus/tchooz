<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use EmundusHelperFiles;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_SITE . '/components/com_emundus/helpers/files.php';

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelEvaluation
 */
class EvaluationModelTest extends UnitTestCase
{
	private ?User $previousIdentity = null;

	private bool $identityOverridden = false;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('evaluation', $data, $dataName, 'EmundusModelEvaluation');
	}

	protected function tearDown(): void
	{
		if ($this->identityOverridden)
		{
			// loadIdentity(null) loads a guest user instead of clearing the identity
			$identity = new \ReflectionProperty(Factory::getApplication(), 'identity');
			$identity->setValue(Factory::getApplication(), $this->previousIdentity);
			$this->identityOverridden = false;
		}

		$session = Factory::getApplication()->getSession();
		foreach (['em-applied-filters', 'limit', 'limitstart', 'filter_order', 'filter_order_Dir'] as $key)
		{
			$session->clear($key);
		}

		parent::tearDown();
	}

	/**
	 * @covers EmundusModelEvaluation::getUsers
	 * @return void
	 */
	public function testGetUsersListsTheStepsOfSeveralEvaluationTablesPageByPage(): void
	{
		$fixtures = $this->createTwoTablesFixtures();
		$session  = Factory::getApplication()->getSession();
		$session->set('em-applied-filters', [$this->stepsFilter($fixtures['step_ids'])]);

		$full  = $this->model->getUsers(null, null, true);
		$all   = $this->listedRows($full, $fixtures['files']);
		$total = $this->model->getTotal();

		$session->set('limit', 2);
		$session->set('limitstart', 2);
		$page = $this->model->getUsers();

		$this->clearFixtures($fixtures);

		[$step_a, $step_b] = $fixtures['step_ids'];
		[$file_1, $file_2, $file_3] = $fixtures['files'];
		$this->assertEqualsCanonicalizing(
			[[$step_a, $file_1], [$step_a, $file_1], [$step_a, $file_2], [$step_a, $file_3], [$step_b, $file_1], [$step_b, $file_2], [$step_b, $file_3]],
			$all,
			'Each step of both tables lists its evaluations, or the file when it has none in the step'
		);
		$this->assertSame(array_slice($full, 2, 2), $page, 'The page is the slice of the full list at limitstart, in the same order');
		$this->assertGreaterThanOrEqual(7, $total, 'The total counts every listed row, not the page');
		$this->assertSame($total, $this->model->getTotal(), 'The total stays the same when the list is paginated');
	}

	/**
	 * @covers EmundusModelEvaluation::getUsers
	 * @return void
	 */
	public function testGetUsersEvaluatedFilterAppliesToEachRowItsOwnStep(): void
	{
		$fixtures = $this->createTwoTablesFixtures();
		Factory::getApplication()->getSession()->set('em-applied-filters', [
			$this->stepsFilter($fixtures['step_ids']),
			['uid' => 'evaluated', 'id' => 'evaluated', 'type' => 'select', 'value' => [1], 'operator' => 'IN', 'andorOperator' => 'OR'],
		]);

		$evaluated = $this->listedRows($this->model->getUsers(null, null, true), $fixtures['files']);

		$this->clearFixtures($fixtures);

		[$step_a] = $fixtures['step_ids'];
		[$file_1, $file_2] = $fixtures['files'];
		$this->assertEqualsCanonicalizing(
			[[$step_a, $file_1], [$step_a, $file_1], [$step_a, $file_2]],
			$evaluated,
			'Only the rows of the step where the file is evaluated are listed'
		);
	}

	/**
	 * @covers EmundusModelEvaluation::getUsers
	 * @covers EmundusHelperFiles::setFiltersValuesAvailability
	 * @return void
	 */
	public function testGetUsersAndFilterCountApplyACriterionOnlyToTheStepsOfItsForm(): void
	{
		$fixtures = $this->createTwoTablesFixtures();
		$query    = $this->db->getQuery(true)
			->select('fe.id')
			->from($this->db->quoteName('#__fabrik_elements', 'fe'))
			->join('INNER', $this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ffg.group_id = fe.group_id')
			->where('ffg.form_id = ' . (int) $fixtures['form_ids'][0])
			->where('fe.name = ' . $this->db->quote('updated_by'));
		$element_id = (int) $this->db->setQuery($query)->loadResult();
		$criterion  = ['uid' => 'filter-' . $element_id, 'id' => $element_id, 'type' => 'select', 'plugin' => 'user', 'value' => [$this->dataset['coordinator']], 'operator' => 'IN', 'andorOperator' => 'OR'];
		Factory::getApplication()->getSession()->set('em-applied-filters', [$this->stepsFilter($fixtures['step_ids']), $criterion]);

		$listed = $this->listedRows($this->model->getUsers(null, null, true), $fixtures['files']);

		$menu_item = new MenuItem(['id' => 0, 'query' => ['view' => 'evaluation'], 'params' => '{}']);
		$counted   = $criterion + ['values' => [['value' => $this->dataset['coordinator'], 'label' => 'Coordinator']]];
		$result    = (new EmundusHelperFiles())->setFiltersValuesAvailability([$counted], $this->dataset['coordinator'], $menu_item);

		$this->clearFixtures($fixtures);

		[$step_a] = $fixtures['step_ids'];
		[$file_1] = $fixtures['files'];
		$this->assertSame([[$step_a, $file_1]], $listed, 'The criterion keeps the evaluation having the value, not the rows of the other form\'s steps');
		$this->assertSame(1, $result[0]['values'][0]['count'], 'The count agrees with the list');
	}

	/**
	 * Workflow with two evaluation steps, each with its own form and table, on the dataset program.
	 * Three submitted files: in step A, file 1 evaluated twice and file 2 once; nothing in step B.
	 *
	 * @return array{workflow_id: int, step_ids: int[], form_ids: int[], files: string[]}
	 */
	private function createTwoTablesFixtures(): array
	{
		require_once JPATH_ROOT . '/components/com_emundus/models/workflow.php';
		require_once JPATH_ROOT . '/components/com_emundus/models/form.php';

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$m_workflow  = new \EmundusModelWorkflow();
		$m_form      = new \EmundusModelForm();
		$workflow_id = $m_workflow->add();

		$m_workflow->updateWorkflow(
			['id' => $workflow_id, 'label' => 'Test list workflow', 'published' => 1],
			[
				['id' => 0, 'entry_status' => [['id' => 1]], 'type' => 2, 'form_id' => $m_form->createFormEval($coordinator), 'label' => 'Test list step A', 'output_status' => 0],
				['id' => 0, 'entry_status' => [['id' => 2]], 'type' => 2, 'form_id' => $m_form->createFormEval($coordinator), 'label' => 'Test list step B', 'output_status' => 0],
			],
			[['id' => $this->dataset['program']['programme_id']]]
		);
		$steps = array_map(fn($step) => $m_workflow->getStepData($step->id), $m_workflow->getWorkflow($workflow_id)['steps']);
		$this->assertNotSame($steps[0]->table, $steps[1]->table, 'Each step has its own evaluation table');

		$files = [];
		for ($i = 0; $i < 3; $i++)
		{
			$files[] = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		}
		$this->db->setQuery('UPDATE #__emundus_campaign_candidature SET status = 1, published = 1 WHERE fnum IN (' . implode(',', $this->db->quote($files)) . ')')->execute();

		foreach ([[$files[0], $this->dataset['coordinator']], [$files[0], $this->dataset['applicant']], [$files[1], $this->dataset['applicant']]] as [$fnum, $evaluator])
		{
			$row = (object) ['ccid' => EmundusHelperFiles::getIdFromFnum($fnum), 'fnum' => $fnum, 'step_id' => $steps[0]->id, 'evaluator' => $evaluator, 'updated_by' => $evaluator];
			$this->db->insertObject($steps[0]->table, $row);
		}

		$this->previousIdentity   = Factory::getApplication()->getIdentity();
		$this->identityOverridden = true;
		Factory::getApplication()->loadIdentity($coordinator);

		return [
			'workflow_id' => $workflow_id,
			'step_ids'    => array_map(fn($step) => (int) $step->id, $steps),
			'form_ids'    => array_map(fn($step) => (int) $step->form_id, $steps),
			'files'       => $files,
		];
	}

	private function clearFixtures(array $fixtures): void
	{
		foreach ($fixtures['files'] as $fnum)
		{
			$this->h_dataset->deleteSampleFile($fnum);
		}
	}

	private function stepsFilter(array $step_ids): array
	{
		return ['uid' => 'workflow_steps', 'id' => 'workflow_steps', 'type' => 'select', 'value' => $step_ids, 'operator' => 'IN', 'andorOperator' => 'OR'];
	}

	/**
	 * @return array<array{0: int, 1: string}>  [step, fnum] of the rows of the given files.
	 */
	private function listedRows(array $list, array $fnums): array
	{
		$rows = array_filter($list, fn($row) => in_array($row['fnum'], $fnums, true));

		return array_values(array_map(fn($row) => [(int) $row['evaluations_step_id'], $row['fnum']], $rows));
	}

	/**
	 * @covers EmundusModelEvaluation::getLettersByProgrammesStatusCampaigns
	 *
	 * @since version 1.0.0
	 */
	public function testgetLettersByProgrammesStatusCampaigns()
	{
		$letters = $this->model->getLettersByProgrammesStatusCampaigns();
		$this->assertIsArray($letters, 'getLettersByProgrammesStatusCampaigns should return an array');
		$this->assertEmpty($letters, 'Without parameters, getLettersByProgrammesStatusCampaigns should return an empty array');

		$letter_attachement_id = $this->h_dataset->createSampleAttachment();
		$letter_id = $this->h_dataset->createSampleLetter($letter_attachement_id, 2, [$this->dataset['program']['programme_code']], [0], [$this->dataset['campaign']]);

		$letters = $this->model->getLettersByProgrammesStatusCampaigns([$this->dataset['program']['programme_code']], [0], [$this->dataset['campaign']]);
		$this->assertNotEmpty($letters, 'I should retrieve letters by programme status and campaign');

		$letter_ids       = array_column($letters, 'id');
		$this->assertContains($letter_id, $letter_ids, 'I should retrieve the created letter id in the list of letters');

		$this->h_dataset->deleteSampleAttachment($letter_attachement_id);
		$this->h_dataset->deleteSampleLetter($letter_id);
	}

	/**
	 * @covers EmundusModelEvaluation::getLetterTemplateForFnum
	 *
	 * @since version 1.0.0
	 */
	public function testgetLetterTemplateForFnum()
	{
		$this->refreshDataset();
		$letters = $this->model->getLetterTemplateForFnum('');
		$this->assertIsArray($letters, 'getLetterTemplateForFnum should return an array');
		$this->assertEmpty($letters, 'Without parameters, getLetterTemplateForFnum should return an empty array');

		$letter_attachement_id = $this->h_dataset->createSampleAttachment();
		$letter_id = $this->h_dataset->createSampleLetter($letter_attachement_id, 2, [$this->dataset['program']['programme_code']], [0], [$this->dataset['campaign']]);
		$this->assertNotEmpty($letter_id, 'testgetLetterTemplateForFnum, sample letter creation failed');

		$letters = $this->model->getLetterTemplateForFnum($this->dataset['fnum'], [$letter_attachement_id]);
		$this->assertNotEmpty($letters, 'I should retrieve letters by fnum and letter attachement id');

		// Clear datasets
		$this->h_dataset->deleteSampleAttachment($letter_attachement_id);
		$this->h_dataset->deleteSampleLetter($letter_id);
	}

	/**
	 * @covers EmundusModelEvaluation::getEvaluationAverageBySteps
	 *
	 * @return void
	 */
	public function  testgetEvaluationAverageBySteps()
	{
		$evaluations = $this->model->getEvaluationAverageBySteps([$this->dataset['fnum']], 1);
		$this->assertIsArray($evaluations, 'getEvaluationAverageBySteps should return an array');

		// TODO: create eval step with an eval form that contains an average field and test the function
	}
}