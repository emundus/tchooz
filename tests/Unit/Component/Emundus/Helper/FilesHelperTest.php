<?php
/**
 * @package     Unit\Component\Emundus\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Helper;

use EmundusHelperFiles;
use Joomla\CMS\Factory;
use Joomla\CMS\Menu\MenuItem;
use Joomla\CMS\Menu\SiteMenu;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Enums\Fabrik\ElementPluginEnum;

require_once JPATH_SITE . '/components/com_emundus/helpers/files.php';

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      EmundusHelperFiles
 */
class FilesHelperTest extends UnitTestCase
{
	/**
	 * @var    EmundusHelperFiles
	 * @since  4.2.0
	 */
	private $helper;

	private ?User $previousIdentity = null;

	private bool $identityOverridden = false;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->helper = new EmundusHelperFiles();
	}

	protected function tearDown(): void
	{
		if ($this->identityOverridden)
		{
			// loadIdentity(null) loads a guest user instead of clearing the identity, which would leak a timezone to the next tests
			$identity = new \ReflectionProperty(Factory::getApplication(), 'identity');
			$identity->setValue(Factory::getApplication(), $this->previousIdentity);
			$this->identityOverridden = false;
		}

		parent::tearDown();
	}

	/**
	 * @test
	 * @covers EmundusHelperFiles::createFnum
	 */
	public function testCreateFnum()
	{
		$this->assertSame('', EmundusHelperFiles::createFnum(0, 0, false), 'Create fnum with wrong campaign_id and user_id returns empty');
		$this->assertSame('', EmundusHelperFiles::createFnum(0, $this->dataset['coordinator'], false), 'Create fnum with wrong campaign_id returns empty');

		$this->assertSame('', EmundusHelperFiles::createFnum(1, 0, false), 'Create fnum with nio user id connected or given returns empty');

		$this->assertNotEmpty(EmundusHelperFiles::createFnum(1, $this->dataset['coordinator'], false), 'Create fnum with correct campaign_id and user_id returns not empty');
		$this->assertNotEmpty(EmundusHelperFiles::createFnum(1, $this->dataset['coordinator']), 'Create fnum with correct campaign_id and user_id and redirect to true returns not empty');
	}

	/**
	 * @test
	 * @covers EmundusHelperFiles::getExportExcelFilter
	 */
	public function testGetExportExcelFilter()
	{
		$this->assertFalse($this->helper->getExportExcelFilter(0), 'Get export excel filter with wrong user id returns false');

		$coord_filters = $this->helper->getExportExcelFilter($this->dataset['coordinator']);
		$this->assertNotFalse($coord_filters, 'Get export excel filter with correct user id returns not false');
		$this->assertSame('array', gettype($coord_filters), 'Get export excel filter with correct user id returns an array even if empty');
	}

	/**
	 * @test
	 * @covers EmundusHelperFiles::findJoinsBetweenTablesRecursively
	 */
	public function testfindJoinsBetweenTablesRecursively()
	{
		$joins = $this->helper->findJoinsBetweenTablesRecursively('', '');
		$this->assertEmpty($joins, 'Find joins between tables recursively with empty tables returns an empty array');

		$joins = $this->helper->findJoinsBetweenTablesRecursively('jos_emundus_campaign_candidature', '');
		$this->assertEmpty($joins, 'Find joins between tables recursively with empty table 2 returns an empty array');

		$joins = $this->helper->findJoinsBetweenTablesRecursively('', 'jos_emundus_setup_campaigns');
		$this->assertEmpty($joins, 'Find joins between tables recursively with empty table 1 returns an empty array');

		$joins = $this->helper->findJoinsBetweenTablesRecursively('jos_emundus_campaign_candidature', 'jos_emundus_campaign_candidature');
		$this->assertEmpty($joins, 'Find joins between tables recursively with same tables returns an empty array');

		$joins = $this->helper->findJoinsBetweenTablesRecursively('jos_emundus_campaign_candidature', 'jos_emundus_setup_campaigns');
		$this->assertNotEmpty($joins, 'Find joins between tables recursively with different tables returns not empty array');
		$this->assertSame(1, sizeof($joins), 'Join between jos_emundus_campaign_candidature and jos_emundus_setup_campaigns returns 1 join');
		$this->assertSame('jos_emundus_campaign_candidature.campaign_id = jos_emundus_setup_campaigns.id', $joins[0]['join_from_table'] . '.' . $joins[0]['table_key'] . ' = ' . $joins[0]['table_join'] . '.' . $joins[0]['table_join_key'], 'Join between jos_emundus_campaign_candidature and jos_emundus_setup_campaigns returns correct join');

		$joins = $this->helper->findJoinsBetweenTablesRecursively('jos_emundus_campaign_candidature', 'jos_emundus_users');
		$this->assertNotEmpty($joins, 'Find joins between tables recursively with different tables returns not empty array');
		// TODO: add foreign keys to vanilla xml tables, change joomla exporter behavior to export foreign keys
		$this->assertSame(1, sizeof($joins), 'Join between jos_emundus_campaign_candidature and jos_emundus_users returns 1 join');
		$this->assertSame('jos_emundus_campaign_candidature.applicant_id = jos_emundus_users.user_id', $joins[0]['join_from_table'] . '.' . $joins[0]['table_key'] . ' = ' . $joins[0]['table_join'] . '.' . $joins[0]['table_join_key'], 'Join between jos_emundus_campaign_candidature and jos_emundus_users returns correct join');

		$joins          = $this->helper->findJoinsBetweenTablesRecursively('jos_emundus_evaluations', 'jos_emundus_campaign_candidature');
		$joins_reversed = $this->helper->findJoinsBetweenTablesRecursively('jos_emundus_campaign_candidature', 'jos_emundus_evaluations');

		// 'Join between jos_emundus_evaluations and jos_emundus_campaign_candidature returns same join as join between jos_emundus_campaign_candidature and jos_emundus_evaluations' with only join direction changed
		$this->assertEquals($joins[0]['join_from_table'], $joins_reversed[0]['table_join'], 'Join between jos_emundus_evaluations and jos_emundus_campaign_candidature returns same join as join between jos_emundus_campaign_candidature and jos_emundus_evaluations with only join direction changed');
		$this->assertEquals($joins[0]['table_join'], $joins_reversed[0]['join_from_table'], 'Join between jos_emundus_evaluations and jos_emundus_campaign_candidature returns same join as join between jos_emundus_campaign_candidature and jos_emundus_evaluations with only join direction changed');
		$this->assertEquals($joins[0]['table_key'], $joins_reversed[0]['table_join_key'], 'Join between jos_emundus_evaluations and jos_emundus_campaign_candidature returns same join as join between jos_emundus_campaign_candidature and jos_emundus_evaluations with only join direction changed');
		$this->assertEquals($joins[0]['table_join_key'], $joins_reversed[0]['table_key'], 'Join between jos_emundus_evaluations and jos_emundus_campaign_candidature returns same join as join between jos_emundus_campaign_candidature and jos_emundus_evaluations with only join direction changed');
	}

	/**
	 * @test
	 * @covers EmundusHelperFiles::writeJoins
	 */
	public function testwriteJoins()
	{
		$joins          = $this->helper->findJoinsBetweenTablesRecursively('jos_emundus_campaign_candidature', 'jos_emundus_setup_campaigns');
		$already_joined = array();

		$joins_as_string = $this->helper->writeJoins([], $already_joined);
		$this->assertEmpty($joins_as_string, 'Write joins with empty joins returns empty string');

		$joins_as_string = $this->helper->writeJoins($joins, $already_joined);
		$this->assertNotEmpty($joins_as_string, 'Write joins with correct joins returns not empty string');
		$this->assertSame(' LEFT JOIN `jos_emundus_setup_campaigns` ON `jos_emundus_setup_campaigns`.`id` = `jos_emundus_campaign_candidature`.`campaign_id`', $joins_as_string, 'Write joins with correct joins returns correct string');
	}

	/**
	 * @test
	 * @covers EmundusHelperFiles::writeQueryWithOperator
	 */
	public function testwriteQueryWithOperator()
	{
		$query_condition = $this->helper->writeQueryWithOperator(null, null, null);
		$this->assertSame('1=1', $query_condition, 'Write query with null operator returns 1=1 string');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', '24343432323', '!=');
		$this->assertSame('(ecc.fnum != \'24343432323\' OR ecc.fnum IS NULL ) ', $query_condition, 'Write query with != operator returns correct string');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', '24343432323', '=');
		$this->assertSame('ecc.fnum = \'24343432323\'', $query_condition, 'Write query with = operator returns correct string');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', ['24343432323', '24334234234234'], '=');
		$this->assertSame('ecc.fnum IN (\'24343432323\',\'24334234234234\')', $query_condition, 'Write query with = operator and array of values returns correct string with IN');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', ['24343432323', '24334234234234'], 'superior');
		$this->assertSame('(ecc.fnum > \'24343432323\' AND ecc.fnum > \'24334234234234\')', $query_condition, 'Write query with superior operator and multiple values for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', '24343432323', 'superior');
		$this->assertSame('ecc.fnum > \'24343432323\'', $query_condition, 'Write query with superior operator and single for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', ['24343432323', '24334234234234'], 'inferior');
		$this->assertSame('(ecc.fnum < \'24343432323\' AND ecc.fnum < \'24334234234234\')', $query_condition, 'Write query with inferior operator and multiple values for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', '24343432323', 'inferior');
		$this->assertSame('ecc.fnum < \'24343432323\'', $query_condition, 'Write query with inferior operator and single for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', ['24343432323', '24334234234234'], 'superior_or_equal');
		$this->assertSame('(ecc.fnum >= \'24343432323\' AND ecc.fnum >= \'24334234234234\')', $query_condition, 'Write query with superior_or_equal operator and multiple values for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', '24343432323', 'superior_or_equal');
		$this->assertSame('ecc.fnum >= \'24343432323\'', $query_condition, 'Write query with superior_or_equal operator and single for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', ['24343432323', '24334234234234'], 'inferior_or_equal');
		$this->assertSame('(ecc.fnum <= \'24343432323\' AND ecc.fnum <= \'24334234234234\')', $query_condition, 'Write query with inferior_or_equal operator and multiple values for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.fnum', '24343432323', 'inferior_or_equal');
		$this->assertSame('ecc.fnum <= \'24343432323\'', $query_condition, 'Write query with inferior_or_equal operator and single for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.id', '1', 'superior');
		$this->assertSame('ecc.id > \'1\'', $query_condition, 'Write query with superior operator and single for default filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.created', ['2023-02-01', ''], 'superior', 'date');
		$this->assertSame('ecc.created > \'2023-02-01\'', $query_condition, 'Write query with superior operator for date filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.created', ['2023-02-01', '2023-02-09'], 'between', 'date');
		$this->assertSame('ecc.created BETWEEN \'2023-02-01\' AND \'2023-02-09\'', $query_condition, 'Write query with between operator for date filter type works');

		$query_condition = $this->helper->writeQueryWithOperator('ecc.created', ['2023-02-01', ''], 'between', 'date');
		$this->assertSame('ecc.created >= \'2023-02-01\'', $query_condition, 'Write query with between operator for date filter type works even if only "from" value is passed');
	}

	/**
	 * @test
	 * @covers EmundusHelperFiles::getFabrikElementData
	 */
	public function testgetFabrikElementData()
	{
		$data = $this->helper->getFabrikElementData(0);
		$this->assertEmpty($data, 'Get fabrik element data with 0 id returns empty array');
		
		$query = $this->db->getQuery(true);
		$query->select('*')
			->from('#__fabrik_elements')
			->setLimit(1);
		$this->db->setQuery($query);
		$element = $this->db->loadAssoc();

		$data = $this->helper->getFabrikElementData($element['id']);
		$this->assertNotEmpty($data, 'Get fabrik element data with correct id returns not empty array');
		$this->assertSame($element['id'], $data['element_id'], 'Get fabrik element data with correct id returns correct id');

		// make sure we knwo the name, the plugin, the group_id, the list_id
		$this->assertNotEmpty($data['name'], 'Get fabrik element data with correct id returns not empty name');
		$this->assertNotEmpty($data['plugin'], 'Get fabrik element data with correct id returns not empty plugin');
		$this->assertNotEmpty($data['group_id'], 'Get fabrik element data with correct id returns not empty group_id');
		$this->assertNotEmpty($data['list_id'], 'Get fabrik element data with correct id returns not empty list_id');
	}

	/**
	 * @test
	 * @covers EmundusHelperFiles::_moduleBuildWhere
	 */
	public function test_moduleBuildWhere()
	{
		$menu = new SiteMenu();
		$menu_item = $menu->getItems('link', 'index.php?option=com_emundus&view=files', true);

		$where = $this->helper->_moduleBuildWhere([], 'files', [], [], $menu_item);
		$this->assertNotEmpty($where, 'Build where with empty filters returns not empty string');

		// $where must contain q and join entries
		$this->assertArrayHasKey('q', $where, 'Build where with empty filters returns q entry');
		$this->assertArrayHasKey('join', $where, 'Build where with empty filters returns join entry');

		$session = Factory::getApplication()->getSession();
		$session->set('em-quick-search-filters', [
			[
				'scope' => 'everywhere',
				'value' => 'test',
			]
		]);
		$session->set('em-applied-filters', []);

		$where = $this->helper->_moduleBuildWhere([], 'files', [], [], $menu_item);
		$this->assertNotEmpty($where['q'], 'Build where with filters returns not empty string');
		
		$this->assertSame(' AND esc.published > 0 AND (jecc.applicant_id LIKE \'%test%\' OR jecc.fnum LIKE \'%test%\' OR jecc.short_reference LIKE \'%test%\' OR eir.reference LIKE \'%test%\' OR ((u.username LIKE \'%test%\' OR eu.firstname LIKE \'%test%\' OR eu.lastname LIKE \'%test%\' OR u.email LIKE \'%test%\') AND eu.is_anonym <> 1 AND jecc.anonymous <> 1)) AND jecc.published = \'1\'', $where['q'], 'Build where with filters returns correct string');

		$session->set('em-quick-search-filters', [
			[
				'scope' => '',
				'value' => 'test',
			]
		]);
		$where = $this->helper->_moduleBuildWhere([], 'files', [], [], $menu_item);
		$this->assertSame(' AND esc.published > 0 AND jecc.published = \'1\'', $where['q'], 'Build where with quick search filters with no scope returns only default filter on published');

		$session->set('em-quick-search-filters', [
			[
				'scope' => 'unhandled_scope',
				'value' => 'test',
			]
		]);
		$where = $this->helper->_moduleBuildWhere([], 'files', [], [], $menu_item);
		$this->assertSame(' AND esc.published > 0 AND jecc.published = \'1\'', $where['q'], 'Build where with quick search filters with unhandled scope returns only default filter on published');

		$session->clear('em-quick-search-filters');
		$session->clear('em-applied-filters');
	}

	public function testCheckLimitationFilesRules()
	{
		$applicant_id = $this->dataset['applicant'];

		$can_create_new = $this->helper::checkLimitationFilesRules($applicant_id, $this->dataset['campaign']);
		$this->assertTrue($can_create_new, 'Check limitation files rules with correct applicant id and campaign id returns true');
	}

	// -------------------------------------------------------------------------
	// Filter values count
	// -------------------------------------------------------------------------

	/**
	 * Evaluation step on the dataset program, three submitted files and their evaluations:
	 * file 1 by the coordinator (updated_by A) and by the applicant (updated_by B), file 2 by the
	 * applicant (updated_by B), file 3 not evaluated. updated_by stands for a criterion of the form.
	 *
	 * @return array{step_id: int, element_id: int, files: string[], updated_by_a: int, updated_by_b: int}
	 */
	private function createEvaluationFixtures(): array
	{
		require_once JPATH_ROOT . '/components/com_emundus/models/workflow.php';
		require_once JPATH_ROOT . '/components/com_emundus/models/form.php';

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$m_workflow  = new \EmundusModelWorkflow();
		$form_id     = (new \EmundusModelForm())->createFormEval($coordinator);
		$workflow_id = $m_workflow->add();

		$m_workflow->updateWorkflow(
			['id' => $workflow_id, 'label' => 'Test count workflow', 'published' => 1],
			[['id' => 0, 'entry_status' => [['id' => 1]], 'type' => 2, 'form_id' => $form_id, 'label' => 'Test count step', 'output_status' => 0]],
			[['id' => $this->dataset['program']['programme_id']]]
		);
		$step = $m_workflow->getWorkflow($workflow_id)['steps'][0];
		$step = $m_workflow->getStepData($step->id);

		$files = [];
		for ($i = 0; $i < 3; $i++)
		{
			$files[] = $this->h_dataset->createSampleFile($this->dataset['campaign'], $this->dataset['applicant']);
		}
		$this->db->setQuery('UPDATE #__emundus_campaign_candidature SET status = 1, published = 1 WHERE fnum IN (' . implode(',', $this->db->quote($files)) . ')')->execute();

		$updated_by_a = 900001;
		$updated_by_b = 900002;
		$evaluations  = [
			[$files[0], $this->dataset['coordinator'], $updated_by_a],
			[$files[0], $this->dataset['applicant'], $updated_by_b],
			[$files[1], $this->dataset['applicant'], $updated_by_b],
		];
		foreach ($evaluations as [$fnum, $evaluator, $updated_by])
		{
			$row = (object) ['ccid' => EmundusHelperFiles::getIdFromFnum($fnum), 'fnum' => $fnum, 'step_id' => $step->id, 'evaluator' => $evaluator, 'updated_by' => $updated_by];
			$this->db->insertObject($step->table, $row);
		}

		$query = $this->db->getQuery(true)
			->select('fe.id')
			->from($this->db->quoteName('#__fabrik_elements', 'fe'))
			->join('INNER', $this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ffg.group_id = fe.group_id')
			->where('ffg.form_id = ' . (int) $form_id)
			->where('fe.name = ' . $this->db->quote('updated_by'));
		$element_id = (int) $this->db->setQuery($query)->loadResult();

		$this->previousIdentity = Factory::getApplication()->getIdentity();
		$this->identityOverridden = true;
		Factory::getApplication()->loadIdentity($coordinator);

		return ['step_id' => (int) $step->id, 'element_id' => $element_id, 'files' => $files, 'updated_by_a' => $updated_by_a, 'updated_by_b' => $updated_by_b];
	}

	private function clearEvaluationFixtures(array $fixtures): void
	{
		foreach ($fixtures['files'] as $fnum)
		{
			$this->h_dataset->deleteSampleFile($fnum);
		}

		$session = Factory::getApplication()->getSession();
		$session->clear('em-applied-filters');
	}

	/**
	 * @param   string  $view       files or evaluation
	 * @param   array   $filters    Session filters (em-applied-filters).
	 * @param   array   $counted    Filter whose values are counted.
	 *
	 * @return array<int|string, int|null>  value => count
	 */
	private function countValues(string $view, array $filters, array $counted): array
	{
		Factory::getApplication()->getSession()->set('em-applied-filters', $filters);

		$menu_item = new MenuItem(['id' => 0, 'query' => ['view' => $view], 'params' => '{}']);
		$result    = $this->helper->setFiltersValuesAvailability([$counted], $this->dataset['coordinator'], $menu_item);

		return array_column(array_map(fn($value) => ['value' => $value['value'], 'count' => $value['count'] ?? null], $result[0]['values']), 'count', 'value');
	}

	private function evaluatorsFilter(int $evaluator_id, string $operator): array
	{
		return ['uid' => 'evaluators', 'id' => 'evaluators', 'type' => 'select', 'value' => [$evaluator_id], 'operator' => $operator, 'andorOperator' => 'OR'];
	}

	/**
	 * @covers EmundusHelperFiles::setFiltersValuesAvailability
	 * @return void
	 */
	public function testSetFiltersValuesAvailabilityWhenEvaluatorsNotInThenCountsAreTheComplementOfIn(): void
	{
		$fixtures = $this->createEvaluationFixtures();
		$status   = ['uid' => 'status', 'id' => 'status', 'type' => 'select', 'plugin' => 'dropdown', 'values' => [['value' => 1, 'label' => 'Sent']]];

		$all    = $this->countValues('files', [], $status)[1];
		$in     = $this->countValues('files', [$this->evaluatorsFilter($this->dataset['coordinator'], 'IN')], $status)[1];
		$not_in = $this->countValues('files', [$this->evaluatorsFilter($this->dataset['coordinator'], 'NOT IN')], $status)[1];

		$this->clearEvaluationFixtures($fixtures);

		$this->assertGreaterThan(0, $in, 'Files evaluated by the evaluator are counted');
		$this->assertSame($all, $in + $not_in, 'Files evaluated by the evaluator and files not evaluated by them add up to all files');
	}

	/**
	 * @covers EmundusHelperFiles::setFiltersValuesAvailability
	 * @return void
	 */
	public function testSetFiltersValuesAvailabilityInEvaluationViewCountsCriterionOnTheSelectedEvaluatorsEvaluations(): void
	{
		$fixtures  = $this->createEvaluationFixtures();
		$filters   = [
			$this->evaluatorsFilter($this->dataset['coordinator'], 'IN'),
			['uid' => 'workflow_steps', 'id' => 'workflow_steps', 'type' => 'select', 'value' => [$fixtures['step_id']], 'operator' => 'IN', 'andorOperator' => 'OR'],
		];
		$criterion = [
			'uid' => 'filter-' . $fixtures['element_id'], 'id' => $fixtures['element_id'], 'type' => 'select', 'plugin' => 'user',
			'values' => [['value' => $fixtures['updated_by_a'], 'label' => 'A'], ['value' => $fixtures['updated_by_b'], 'label' => 'B']],
		];

		$evaluation_view = $this->countValues('evaluation', $filters, $criterion);
		$files_view      = $this->countValues('files', $filters, $criterion);

		$this->clearEvaluationFixtures($fixtures);

		$this->assertSame(1, $evaluation_view[$fixtures['updated_by_a']], 'Evaluation view counts the selected evaluator\'s evaluation');
		$this->assertSame(0, $evaluation_view[$fixtures['updated_by_b']], 'Evaluation view does not count another evaluator\'s evaluation of the same file');
		$this->assertSame(1, $files_view[$fixtures['updated_by_b']], 'Files view counts a file evaluated by the evaluator having the value in any of its evaluations');
	}

	/**
	 * @covers EmundusHelperFiles::_moduleBuildWhere
	 * @return void
	 */
	public function testEvaluatedFilterEvaluatedAndToEvaluateSplitTheFiles(): void
	{
		$fixtures = $this->createEvaluationFixtures();
		$steps    = ['uid' => 'workflow_steps', 'id' => 'workflow_steps', 'type' => 'select', 'value' => [$fixtures['step_id']], 'operator' => 'IN', 'andorOperator' => 'OR'];
		$status   = ['uid' => 'status', 'id' => 'status', 'type' => 'select', 'plugin' => 'dropdown', 'values' => [['value' => 1, 'label' => 'Sent']]];

		$all         = $this->countValues('evaluation', [$steps], $status)[1];
		$evaluated   = $this->countValues('evaluation', [$steps, $this->evaluatedFilter([1])], $status)[1];
		$to_evaluate = $this->countValues('evaluation', [$steps, $this->evaluatedFilter([0])], $status)[1];

		$this->clearEvaluationFixtures($fixtures);

		$this->assertGreaterThan(0, $evaluated, 'Evaluated files are counted');
		$this->assertSame($all, $evaluated + $to_evaluate, 'Evaluated and to evaluate split the files');
	}

	private function evaluatedFilter(array $value): array
	{
		return ['uid' => 'evaluated', 'id' => 'evaluated', 'type' => 'select', 'value' => $value, 'operator' => 'IN', 'andorOperator' => 'OR'];
	}

	/**
	 * @covers EmundusHelperFiles::countFilesByValue
	 * @return void
	 */
	public function testCountFilesByValueWhenQueryFailsReturnsNull(): void
	{
		$user      = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$menu_item = new MenuItem(['id' => 0, 'query' => ['view' => 'files'], 'params' => '{}']);
		$filter    = ['uid' => 'status', 'id' => 'status', 'type' => 'select', 'plugin' => 'dropdown'];

		$counts = self::callPrivateMethod($this->helper, 'countFilesByValue', [$filter, 'unknown_table.unknown_column', [], '', ['eval' => false], $menu_item, $user]);

		$this->assertNull($counts, 'A failed count is told apart from a count with no result, so values are not hidden at 0');
	}

	/**
	 * @covers EmundusHelperFiles::sumCountsByValue
	 * @return void
	 */
	public function testSumCountsByValueAddsCountsOfTheSameValue(): void
	{
		$counts = self::callPrivateMethod($this->helper, 'sumCountsByValue', [[['count_value' => '1', 'count' => '2'], ['count_value' => null, 'count' => '4']], ['1' => ['count' => 3]]]);

		$this->assertSame([1 => ['count' => 5]], $counts, 'Counts of a value add up, and rows without value are ignored');
	}

	/**
	 * @covers EmundusHelperFiles::addCheckboxItems
	 * @covers EmundusHelperFiles::countItemsByOption
	 * @return void
	 */
	public function testAddCheckboxItemsCountsAnItemOnceWhenSeveralOfItsRowsHoldTheOption(): void
	{
		$rows = [
			['item' => '1', 'count_value' => '["a","b"]'],
			['item' => '1', 'count_value' => '["b"]'],
			['item' => '2', 'count_value' => '["b"]'],
			['item' => '3', 'count_value' => null],
		];

		$counts = self::callPrivateMethod($this->helper, 'countItemsByOption', [self::callPrivateMethod($this->helper, 'addCheckboxItems', [$rows])]);

		$this->assertSame(['a' => ['count' => 1], 'b' => ['count' => 2]], $counts, 'Each option counts the distinct items holding it');
	}

	/**
	 * @covers EmundusHelperFiles::addCheckboxItems
	 * @return void
	 */
	public function testAddCheckboxItemsKeepsTheSameItemOfTwoStepsApart(): void
	{
		$rows  = [['item' => 'fnum-1', 'count_value' => '["a"]']];
		$items = self::callPrivateMethod($this->helper, 'addCheckboxItems', [$rows, [], '1:']);
		$items = self::callPrivateMethod($this->helper, 'addCheckboxItems', [$rows, $items, '2:']);

		$this->assertSame(['a' => ['1:fnum-1' => true, '2:fnum-1' => true]], $items, 'A file listed in two steps counts once per step');
	}

	/**
	 * @covers EmundusHelperFiles::isFilterActive
	 * @return void
	 */
	public function testIsFilterActiveOnlyForAValueOtherThanAll(): void
	{
		$this->assertTrue(self::callPrivateMethod($this->helper, 'isFilterActive', [['value' => ['0']]]), 'A 0 value is active');
		$this->assertTrue(self::callPrivateMethod($this->helper, 'isFilterActive', [['value' => [5]]]), 'A value is active');
		$this->assertFalse(self::callPrivateMethod($this->helper, 'isFilterActive', [['value' => ['all']]]), 'All is not active');
		$this->assertFalse(self::callPrivateMethod($this->helper, 'isFilterActive', [['value' => []]]), 'No value is not active');
		$this->assertFalse(self::callPrivateMethod($this->helper, 'isFilterActive', [[]]), 'A filter without value is not active');
	}

	/**
	 * @covers EmundusHelperFiles::evaluatedByCondition
	 * @return void
	 */
	public function testEvaluatedByConditionWithoutEvaluationStepMatchesNothing(): void
	{
		$this->assertSame('FALSE', self::callPrivateMethod($this->helper, 'evaluatedByCondition', [[], [1]]), 'Without step, no file is evaluated by anyone');
	}

	/**
	 * @covers EmundusHelperFiles::writeQueryWithOperator
	 */
	public function testWriteQueryWithOperatorOnOrderlistMatchesChoicesAtTheirRank(): void
	{
		$stored = '"A","C","B"';

		$this->assertTrue($this->orderlistConditionMatches($stored, [['values' => ['A'], 'rank' => 1]]), 'A is ranked first');
		$this->assertFalse($this->orderlistConditionMatches($stored, [['values' => ['A'], 'rank' => 2]]), 'A is not ranked second');
		$this->assertTrue($this->orderlistConditionMatches($stored, [['values' => ['B', 'A'], 'rank' => 1]]), 'One of the choices of a criterion is enough');
		$this->assertTrue($this->orderlistConditionMatches('A,C,B', [['values' => ['B'], 'rank' => 3]]), 'Values stored without quotes are handled');
		$this->assertFalse($this->orderlistConditionMatches(null, [['values' => ['A'], 'rank' => 1]]), 'A file without value does not match');
	}

	/**
	 * @covers EmundusHelperFiles::writeQueryWithOperator
	 */
	public function testWriteQueryWithOperatorOnOrderlistCombinesCriteria(): void
	{
		$stored   = '"A","C","B"';
		$criteria = [['values' => ['C'], 'rank' => 2], ['values' => ['C'], 'rank' => 1]];

		$this->assertFalse($this->orderlistConditionMatches($stored, $criteria, '=', 'AND'), 'With AND, every criterion must match');
		$this->assertTrue($this->orderlistConditionMatches($stored, $criteria, '=', 'OR'), 'With OR, one criterion is enough');
		$this->assertFalse($this->orderlistConditionMatches($stored, $criteria, '=', 'unknown'), 'An unknown combination falls back to AND');
	}

	/**
	 * @covers EmundusHelperFiles::writeQueryWithOperator
	 */
	public function testWriteQueryWithOperatorOnOrderlistNegatesTheCondition(): void
	{
		$this->assertFalse($this->orderlistConditionMatches('"A","C","B"', [['values' => ['A'], 'rank' => 1]], '!='), 'A is ranked first');
		$this->assertTrue($this->orderlistConditionMatches('"A","C","B"', [['values' => ['B'], 'rank' => 1]], '!='), 'B is not ranked first');
		$this->assertTrue($this->orderlistConditionMatches(null, [['values' => ['A'], 'rank' => 1]], '!='), 'A file without value has no choice ranked first');
	}

	/**
	 * @covers EmundusHelperFiles::writeQueryWithOperator
	 */
	public function testWriteQueryWithOperatorOnOrderlistIgnoresIncompleteCriteria(): void
	{
		$orderlist = ['plugin' => ElementPluginEnum::ORDERLIST->value];

		$this->assertSame('1=1', $this->helper->writeQueryWithOperator('t.v', [], '=', ElementPluginEnum::ORDERLIST->value, $orderlist));
		$this->assertSame('1=1', $this->helper->writeQueryWithOperator('t.v', [['values' => ['A'], 'rank' => ''], ['values' => [], 'rank' => 1], ['values' => 'A', 'rank' => 1], ['values' => [''], 'rank' => 1], 'A'], '=', ElementPluginEnum::ORDERLIST->value, $orderlist));
		$this->assertTrue($this->orderlistConditionMatches('"A","B"', [['values' => ['B'], 'rank' => 2], ['values' => ['A'], 'rank' => 0]]), 'Only complete criteria are applied');
		$this->assertFalse($this->orderlistConditionMatches('"A","B"', [['values' => ["A' OR '1'='1"], 'rank' => 1]]), 'Choice values are escaped');
	}

	/**
	 * @covers EmundusHelperFiles::orderlistLabelsSelect
	 */
	public function testOrderlistLabelsSelectDisplaysOrderedLabels(): void
	{
		$attribs = (object) ['sub_options' => (object) ['sub_values' => ['1', '2', '3'], 'sub_labels' => ['Master A', "Master l'B", 'Master C']]];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$db->setQuery('SELECT ' . EmundusHelperFiles::orderlistLabelsSelect('t.v', $attribs) . ' FROM (SELECT ' . $db->quote('"3","1","2"') . ' AS v) AS t');

		$this->assertSame("Master C, Master A, Master l'B", $db->loadResult());
	}

	private function orderlistConditionMatches(?string $stored, array $criteria, string $operator = '=', string $andor = 'AND'): bool
	{
		$db        = Factory::getContainer()->get('DatabaseDriver');
		$condition = $this->helper->writeQueryWithOperator('t.v', $criteria, $operator, ElementPluginEnum::ORDERLIST->value, ['plugin' => ElementPluginEnum::ORDERLIST->value], $andor);
		$db->setQuery('SELECT COUNT(*) FROM (SELECT ' . ($stored === null ? 'NULL' : $db->quote($stored)) . ' AS v) AS t WHERE ' . $condition);

		return (int) $db->loadResult() === 1;
	}

	/**
	 * A first lookup without the group must not hide the join found through the group afterwards.
	 *
	 * @covers EmundusHelperFiles::getJoinInformations
	 * @return void
	 */
	public function testGetJoinInformationsFindsTheGroupJoinAfterALookupWithoutTheGroup(): void
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true)
			->select('fj.group_id, fj.list_id, fe.id AS element_id')
			->from($db->quoteName('#__fabrik_joins', 'fj'))
			->join('INNER', $db->quoteName('#__fabrik_elements', 'fe') . ' ON fe.group_id = fj.group_id')
			->join('LEFT', $db->quoteName('#__fabrik_joins', 'own') . ' ON own.element_id = fe.id')
			->where('fj.element_id = 0')
			->where('fj.group_id > 0')
			->where($db->quoteName('fj.join_from_table') . ' <> ' . $db->quote(''))
			->where('own.id IS NULL');
		$db->setQuery($query, 0, 1);
		$repeat = $db->loadAssoc();

		if (empty($repeat))
		{
			$this->markTestSkipped('No repeat group in the test database');
		}

		$withoutGroup = $this->helper->getJoinInformations((int) $repeat['element_id']);
		$withGroup    = $this->helper->getJoinInformations((int) $repeat['element_id'], (int) $repeat['group_id'], (int) $repeat['list_id']);

		$this->assertSame([], $withoutGroup, 'The element has no join of its own');
		$this->assertNotEmpty($withGroup['join_from_table'] ?? null, 'The join of the group is found despite the previous lookup');
	}
}
