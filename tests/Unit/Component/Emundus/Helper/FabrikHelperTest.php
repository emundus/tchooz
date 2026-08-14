<?php
/**
 * @package     Unit\Component\Emundus\Helper
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Helper;

use EmundusHelperFabrik;
use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_SITE . '/components/com_emundus/helpers/fabrik.php';

/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 * @covers      EmundusHelperFabrik
 */
class FabrikHelperTest extends UnitTestCase
{
	/**
	 * @var    EmundusHelperFabrik
	 * @since  4.2.0
	 */
	private $helper;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->helper = new EmundusHelperFabrik();
	}

	/**
	 * @covers EmundusHelperFabrik::getFormattedPhoneNumberValue
	 *
	 * @since version 1.0.0
	 */
	public function testgetFormattedPhoneNumberValue()
	{
		$unformatted_phone_number = '';
		$formatted_phone_number   = $this->helper::getFormattedPhoneNumberValue($unformatted_phone_number);
		$this->assertSame('', $formatted_phone_number, 'Empty phone number returns empty string');

		$unformatted_phone_number = 'zkljhdqopsjdpzhfklqsjnd';
		$formatted_phone_number   = $this->helper::getFormattedPhoneNumberValue($unformatted_phone_number);
		$this->assertSame('', $formatted_phone_number, 'Random string with incorrect characters returns empty string');

		$unformatted_phone_number = '+33 6 12 34 56 78';
		$formatted_phone_number   = $this->helper::getFormattedPhoneNumberValue($unformatted_phone_number);
		$this->assertNotEmpty($formatted_phone_number, 'Correct phone number returns not empty string and by default format is E164');
		$this->assertSame('FR+33612345678', $formatted_phone_number, 'Correct phone number returns correct formatted string');

		$unformatted_phone_number = 'FR+33 612 3456 7 8';
		$formatted_phone_number   = $this->helper::getFormattedPhoneNumberValue($unformatted_phone_number);
		$this->assertNotEmpty($formatted_phone_number, 'Correct phone number returns not empty string');
		$this->assertSame('FR+33612345678', $formatted_phone_number, 'Correct phone number with weird spacing returns correct formatted string');

		$unformatted_phone_number = 'FR+33 612 3456 7 8';
		$formatted_phone_number   = $this->helper::getFormattedPhoneNumberValue($unformatted_phone_number, 2);
		$this->assertNotEmpty($formatted_phone_number, 'Correct phone number returns not empty string');
		$this->assertSame('FR06 12 34 56 78', $formatted_phone_number, 'Setting format 2 (national) returns formatted number correctly');


		$unformatted_phone_number = 'FR+33 612 34za 7 8';
		$formatted_phone_number   = $this->helper::getFormattedPhoneNumberValue($unformatted_phone_number, 2);
		$this->assertEmpty($formatted_phone_number, 'Incorrect phone number returns empty string');
	}

	/**
	 * @return void
	 * @description Test the getElementByAlias() method
	 * @covers EmundusHelperFabrik::getElementsByAlias
	 * It should return the name and database table name storage of the element with the alias passed as parameter
	 */
	public function testGetElementByAlias()
	{
		$this->assertSame([], $this->helper::getElementsByAlias(""), 'Empty alias should return empty array');

		$form_id = $this->h_dataset->getUnitTestFabrikForm();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('fe.id, fe.name, fe.params, fl.db_table_name')
			->from($db->quoteName('#__fabrik_elements', 'fe'))
			->leftJoin($db->quoteName('#__fabrik_formgroup','ffg').' ON '.$db->quoteName('ffg.group_id').' = '.$db->quoteName('fe.group_id'))
			->leftJoin($db->quoteName('#__fabrik_lists','fl').' ON '.$db->quoteName('fl.form_id').' = '.$db->quoteName('ffg.form_id'))
			->where($db->quoteName('ffg.form_id') . ' = ' . $form_id);

		$db->setQuery($query);
		$elements = $db->loadObjectList();

		foreach ($elements as $element) {
			$alias = 'alias_' . $element->id;

			$query->clear()
				->update($db->quoteName('#__fabrik_elements'))
				->set($db->quoteName('alias') . ' = ' . $db->quote($alias))
				->where($db->quoteName('id') . ' = ' . $element->id)
				->setLimit(1);
			$db->setQuery($query);
			$db->execute();

			$elements_by_alias = $this->helper::getElementsByAlias($alias, $form_id);
			$this->assertEquals($element->name, $elements_by_alias[0]->name, 'The element name obtained should be the same as the element name in the database.');
			$this->assertEquals($element->db_table_name, $elements_by_alias[0]->db_table_name, 'The database table name storage obtained should be the same as the database table name storage in the database.');
		}
	}

	public function testGetValueByAlias()
	{
		$this->assertEmpty($this->helper->getValueByAlias('', 1), 'Empty alias should return empty raw value');
		$this->assertEmpty($this->helper->getValueByAlias('test', ''), 'Empty fnum should return empty raw value');

		$form_id = $this->h_dataset->getUnitTestFabrikForm();
		$applicant_id = $this->dataset['applicant'];

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->select('jfl.db_table_name')
			->from($db->quoteName('#__fabrik_lists', 'jfl'))
			->where($db->quoteName('jfl.form_id') . ' = ' . $form_id);

		$db->setQuery($query);
		$db_table_name = $db->loadResult();

		$query->clear()
			->select('jfe.*')
			->from($db->quoteName('#__fabrik_elements', 'jfe'))
			->leftJoin($db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON ' . $db->quoteName('jffg.group_id') . ' = ' . $db->quoteName('jfe.group_id'))
			->where($db->quoteName('jffg.form_id') . ' = ' . $form_id)
			->andWhere($db->quoteName('jfe.name') . ' = ' . $db->quote('e_797_7973'));

		$db->setQuery($query);
		$element = $db->loadAssoc();

		if (!empty($element)) {
			$params = json_decode($element['params'], true);

			if (empty($params['alias'])) {
				$params['alias'] = 'alias_' . $element['id'];

				$query->clear()
					->update($db->quoteName('#__fabrik_elements'))
					->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
					->where($db->quoteName('id') . ' = ' . $element['id']);
				$db->setQuery($query);
				$updated = $db->execute();
				$this->assertTrue($updated, 'The params should be updated in the database');
			}

			$targeted_value = 'test';

			$value = $this->helper->getValueByAlias($params['alias'], null, $applicant_id);
			$this->assertEmpty($value['raw'], 'The value obtained should be empty');

			// insert a value in the database
			$query->clear()
				->insert($db->quoteName($db_table_name))
				->columns($db->quoteName('fnum') . ', ' . $db->quoteName('e_797_7973') . ', ' . $db->quoteName('user'))
				->values($db->quote($this->dataset['fnum']) . ', ' . $db->quote($targeted_value) . ', ' . $db->quote($applicant_id));

			$db->setQuery($query);
			$inserted = $db->execute();
			$this->assertTrue($inserted, 'The value should be inserted in the database');

			// Track the row so tearDown can remove it and keep the test re-runnable.
			$this->insertedDataRows[] = ['table' => $db_table_name, 'id' => (int) $db->insertid()];

			$value = $this->helper->getValueByAlias($params['alias'],null, $applicant_id);
			$this->assertEquals($targeted_value, $value['raw'], 'The value obtained should be the same as the value in the database');

			$value = $this->helper->getValueByAlias($params['alias'],$this->dataset['fnum']);
			$this->assertEmpty($value['raw'], 'The value obtained should be empty because the element is not in an applicant form');

			$value = $this->helper->getValueByAlias($params['alias'],null, $applicant_id, 'column');
			$this->assertIsArray($value, 'The value obtained should be an array using the column format');
			$this->assertEquals($targeted_value, $value[0]['raw'], 'The value obtained should be the same as the value in the database using the column format');
		}
	}

	public function testencryptDatas()
	{
		$encrypted_data = $this->helper::encryptDatas('test', 'unittest_encryption_key');
		$this->assertNotEmpty($encrypted_data, 'The encrypted data should not be empty');
		$this->assertNotEquals('test', $encrypted_data, 'The encrypted data should not be equal to the original data');

		$decrypted_data = $this->helper::decryptDatas($encrypted_data, 'unittest_encryption_key');
		$this->assertNotEmpty($decrypted_data, 'The decrypted data should not be empty');
		$this->assertEquals('test', $decrypted_data, 'The decrypted data should be equal to the original data');
	}

	public function testgetAllFabrikAliases()
	{
		$fabrik_aliases = $this->helper::getAllFabrikAliases();
		//

		$this->assertIsArray($fabrik_aliases, 'The aliases should be returned as an array');
	}

	public function testGetAllFabrikAliasesGrouped()
	{
		$fabrik_aliases_grouped = $this->helper::getAllFabrikAliasesGrouped(25,1, '', '', '', 'ASC', $this->dataset['coordinator']);

		$this->assertIsArray($fabrik_aliases_grouped, 'The aliases should be returned as an array');
		$this->assertLessThanOrEqual(25, count($fabrik_aliases_grouped['datas']), 'There should be 25 aliases in the datas group');

		$fabrik_aliases_grouped = $this->helper::getAllFabrikAliasesGrouped(5,1, '', '', '', 'ASC', $this->dataset['coordinator']);
		$this->assertLessThanOrEqual(5, count($fabrik_aliases_grouped['datas']), 'There should be 5 aliases in the datas group when limit is set to 5');

		$fabrik_aliases_grouped = $this->helper::getAllFabrikAliasesGrouped(25, 1, 'alias_that_does_not_exist', '', '', 'ASC', $this->dataset['coordinator']);
		$this->assertEmpty($fabrik_aliases_grouped['datas'], 'There should be no aliases in the datas group when a non-existing alias is used as filter');
	}

	// -------------------------------------------------------------------------
	// sortElementIdsByDataFreshness
	// -------------------------------------------------------------------------

	/**
	 * A fnum that no real data belongs to, so only the logs we insert drive the freshness.
	 */
	private const FRESHNESS_FNUM = '0000000000unittestfresh';

	/**
	 * Ids that do not match any fabrik element, so the data-table fallback stays out of the way.
	 */
	private const FRESHNESS_ID_A = 999001;

	private const FRESHNESS_ID_B = 999002;

	private const FRESHNESS_ID_C = 999003;

	/**
	 * @var int[] Ids of the log rows created for the freshness tests, cleaned up in tearDown.
	 */
	private array $createdLogIds = [];

	/**
	 * @var array<int, array{table: string, id: int}> Fabrik data rows inserted by tests, cleaned up in tearDown.
	 */
	private array $insertedDataRows = [];

	protected function tearDown(): void
	{
		if (!empty($this->insertedDataRows)) {
			$db = Factory::getContainer()->get('DatabaseDriver');

			foreach ($this->insertedDataRows as $row) {
				$query = $db->createQuery();
				$query->delete($db->quoteName($row['table']))
					->where($db->quoteName('id') . ' = ' . (int) $row['id']);

				try {
					$db->setQuery($query);
					$db->execute();
				}
				catch (\Exception) {
				}
			}

			$this->insertedDataRows = [];
		}

		if (!empty($this->createdLogIds)) {
			$db    = Factory::getContainer()->get('DatabaseDriver');
			$query = $db->createQuery();
			$query->delete($db->quoteName('#__emundus_logs'))
				->where($db->quoteName('id') . ' IN (' . implode(',', array_map('intval', $this->createdLogIds)) . ')');

			try {
				$db->setQuery($query);
				$db->execute();
			}
			catch (\Exception) {
			}

			$this->createdLogIds = [];
		}

		parent::tearDown();
	}

	/**
	 * Inserts a "file update" log carrying the given element id, and returns nothing but records
	 * the row id for cleanup.
	 */
	private function insertUpdateLog(int $elementId, string $timestamp): void
	{
		$db     = Factory::getContainer()->get('DatabaseDriver');
		$params = json_encode(['updated' => [['id' => $elementId, 'element' => 'unit test', 'old' => '', 'new' => 'x']]]);

		$columns = ['timestamp', 'user_id_from', 'user_id_to', 'fnum_to', 'action_id', 'verb', 'message', 'params', 'ip_from'];
		$values  = [
			$db->quote($timestamp),
			$db->quote($this->dataset['coordinator']),
			$db->quote($this->dataset['applicant']),
			$db->quote(self::FRESHNESS_FNUM),
			1,
			$db->quote('u'),
			$db->quote('COM_EMUNDUS_ACCESS_FILE_UPDATE'),
			$db->quote($params),
			$db->quote(''),
		];

		$query = $db->createQuery();
		$query->insert($db->quoteName('#__emundus_logs'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

		$db->setQuery($query);
		$db->execute();

		$this->createdLogIds[] = (int) $db->insertid();
	}

	/**
	 * @covers EmundusHelperFabrik::sortElementIdsByDataFreshness
	 * @return void
	 */
	public function testSortElementIdsByDataFreshnessWhenFewerThanTwoIdsReturnsInputAsList()
	{
		$this->assertSame([], EmundusHelperFabrik::sortElementIdsByDataFreshness([], self::FRESHNESS_FNUM), 'An empty id list is returned untouched');
		$this->assertSame([42], EmundusHelperFabrik::sortElementIdsByDataFreshness([42], self::FRESHNESS_FNUM), 'A single id needs no sorting and is returned as-is');
	}

	/**
	 * @covers EmundusHelperFabrik::sortElementIdsByDataFreshness
	 * @return void
	 */
	public function testSortElementIdsByDataFreshnessWhenFnumEmptyReturnsReindexedInput()
	{
		$sorted = EmundusHelperFabrik::sortElementIdsByDataFreshness([5 => 101, 9 => 202], '');
		$this->assertSame([101, 202], $sorted, 'With an empty fnum the input order is preserved but keys are reindexed to a plain list');
	}

	/**
	 * @covers EmundusHelperFabrik::sortElementIdsByDataFreshness
	 * @return void
	 */
	public function testSortElementIdsByDataFreshnessOrdersFreshestLoggedElementFirst()
	{
		$this->insertUpdateLog(self::FRESHNESS_ID_A, '2024-01-01 10:00:00');
		$this->insertUpdateLog(self::FRESHNESS_ID_B, '2025-01-01 10:00:00');

		$expected = [self::FRESHNESS_ID_B, self::FRESHNESS_ID_A];

		$this->assertSame(
			$expected,
			EmundusHelperFabrik::sortElementIdsByDataFreshness([self::FRESHNESS_ID_A, self::FRESHNESS_ID_B], self::FRESHNESS_FNUM),
			'The element with the most recent update log comes first'
		);

		$this->assertSame(
			$expected,
			EmundusHelperFabrik::sortElementIdsByDataFreshness([self::FRESHNESS_ID_B, self::FRESHNESS_ID_A], self::FRESHNESS_FNUM),
			'The freshest element wins regardless of the input order'
		);
	}

	/**
	 * @covers EmundusHelperFabrik::sortElementIdsByDataFreshness
	 * @return void
	 */
	public function testSortElementIdsByDataFreshnessPlacesElementsWithoutFreshnessLast()
	{
		$this->insertUpdateLog(self::FRESHNESS_ID_A, '2024-01-01 10:00:00');

		// Id C has neither a log nor a matching data table, so it has no known freshness.
		$sorted = EmundusHelperFabrik::sortElementIdsByDataFreshness([self::FRESHNESS_ID_C, self::FRESHNESS_ID_A], self::FRESHNESS_FNUM);

		$this->assertSame(
			[self::FRESHNESS_ID_A, self::FRESHNESS_ID_C],
			$sorted,
			'An element with a known update timestamp is ordered before one with no known freshness'
		);
	}

	/**
	 * Covers the data-table fallback: a real element with no log entry must still get a freshness
	 * timestamp resolved from its data table (element -> db_table_name -> MAX(time_date)).
	 *
	 * @covers EmundusHelperFabrik::sortElementIdsByDataFreshness
	 * @return void
	 */
	public function testSortElementIdsByDataFreshnessFallsBackToDataTableWhenNoLog()
	{
		$form_id = $this->h_dataset->getUnitTestFabrikForm();

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->select('fl.db_table_name, fe.id AS element_id')
			->from($db->quoteName('#__fabrik_elements', 'fe'))
			->leftJoin($db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ' . $db->quoteName('ffg.group_id') . ' = ' . $db->quoteName('fe.group_id'))
			->leftJoin($db->quoteName('#__fabrik_lists', 'fl') . ' ON ' . $db->quoteName('fl.form_id') . ' = ' . $db->quoteName('ffg.form_id'))
			->where($db->quoteName('ffg.form_id') . ' = ' . (int) $form_id)
			->setLimit(1);
		$db->setQuery($query);
		$row = $db->loadObject();

		if (empty($row) || empty($row->db_table_name)) {
			$this->markTestSkipped('No fabrik data table available for the unit test form');
		}

		$columns = array_keys($db->getTableColumns($row->db_table_name));
		if (!in_array('time_date', $columns) || !in_array('fnum', $columns)) {
			$this->markTestSkipped('The unit test data table has no time_date/fnum column');
		}

		$realElementId = (int) $row->element_id;

		// A row in the element's own data table, dated, but with no matching update log.
		$query->clear()
			->insert($db->quoteName($row->db_table_name))
			->columns($db->quoteName(['fnum', 'time_date']))
			->values($db->quote(self::FRESHNESS_FNUM) . ', ' . $db->quote('2025-03-15 09:30:00'));
		$db->setQuery($query);
		$db->execute();
		$this->insertedDataRows[] = ['table' => $row->db_table_name, 'id' => (int) $db->insertid()];

		// Id C is unknown everywhere, so only the real element can resolve a timestamp.
		$sorted = EmundusHelperFabrik::sortElementIdsByDataFreshness([self::FRESHNESS_ID_C, $realElementId], self::FRESHNESS_FNUM);

		$this->assertSame(
			[$realElementId, self::FRESHNESS_ID_C],
			$sorted,
			'A real element resolves its freshness from the data table and outranks an unknown id'
		);
	}
}