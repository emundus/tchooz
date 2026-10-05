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
use Tchooz\Enums\Export\ExportModeEnum;
use Tchooz\Enums\ValueFormatEnum;
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

	/**
	 * @covers EmundusHelperFabrik::getFabrikValue
	 *
	 * @since version 2.0.0
	 */
	public function testGetFabrikValueOnCampaignKeyedTable()
	{
		$table_name = 'jos_emundus_setup_campaigns_more';

		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		// The additional informations of a campaign are stored once per campaign, so reuse the row
		// when the dataset campaign already has one instead of adding a second one.
		$query->select('date_time')
			->from($db->quoteName($table_name))
			->where($db->quoteName('campaign_id') . ' = ' . $db->quote($this->dataset['campaign']));
		$db->setQuery($query);
		$expected_value = $db->loadResult();

		if (empty($expected_value)) {
			$expected_value = '2026-01-15';

			$query->clear()
				->insert($db->quoteName($table_name))
				->columns($db->quoteName('campaign_id') . ', ' . $db->quoteName('date_time'))
				->values($db->quote($this->dataset['campaign']) . ', ' . $db->quote($expected_value));
			$db->setQuery($query);
			$inserted = $db->execute();
			$this->assertTrue($inserted, 'The campaign additional informations should be inserted in the database');

			// Track the row so tearDown can remove it and keep the test re-runnable.
			$this->insertedDataRows[] = ['table' => $table_name, 'id' => (int) $db->insertid()];
		}

		$values = $this->helper->getFabrikValue([$this->dataset['fnum']], $table_name, 'date_time');

		$this->assertArrayHasKey($this->dataset['fnum'], $values, 'A campaign keyed value should be returned indexed by the fnum of the files of the campaign');
		$this->assertEquals($expected_value, $values[$this->dataset['fnum']]['val'], 'The value obtained should be the one stored for the campaign of the file');

		$values = $this->helper->getFabrikValue(['fnum_that_does_not_exist'], $table_name, 'date_time');
		$this->assertEmpty($values, 'No value should be returned for a file belonging to no campaign');
	}

	/**
	 * jos_emundus_users carries a campaign_id and no fnum, yet its rows belong to one applicant:
	 * reaching them through the campaign returns the data of another applicant of that campaign.
	 *
	 * @covers EmundusHelperFabrik::getFabrikValue
	 *
	 * @since version 2.0.0
	 */
	public function testGetFabrikValueOnUserTableSharingACampaign()
	{
		$table_name = 'jos_emundus_users';

		$applicant_firstname   = 'ApplicantOfTheFile';
		$coordinator_firstname = 'OtherUserOfTheCampaign';

		$backup = $this->readUsersRows([$this->dataset['applicant'], $this->dataset['coordinator']]);

		// Both users are put on the campaign of the file so that a campaign wide lookup would have
		// two rows to choose from.
		$this->writeUsersRow($this->dataset['applicant'], $applicant_firstname, (int) $this->dataset['campaign']);
		$this->writeUsersRow($this->dataset['coordinator'], $coordinator_firstname, (int) $this->dataset['campaign']);

		try {
			$values = $this->helper->getFabrikValue([$this->dataset['fnum']], $table_name, 'firstname');

			$this->assertArrayHasKey($this->dataset['fnum'], $values, 'A value should be returned for the file');
			$this->assertEquals($applicant_firstname, $values[$this->dataset['fnum']]['val'], 'The value obtained should belong to the applicant of the file and not to another user of its campaign');
		}
		finally {
			foreach ($backup as $user_id => $row) {
				$this->writeUsersRow($user_id, $row['firstname'], $row['campaign_id']);
			}
		}
	}

	/**
	 * @param   int[]  $userIds
	 *
	 * @return  array<int, array{firstname: string, campaign_id: int|null}>
	 */
	private function readUsersRows(array $userIds): array
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->select('user_id, firstname, campaign_id')
			->from($db->quoteName('#__emundus_users'))
			->where($db->quoteName('user_id') . ' IN (' . implode(',', array_map('intval', $userIds)) . ')');
		$db->setQuery($query);

		$rows = [];
		foreach ($db->loadAssocList('user_id') as $user_id => $row) {
			$rows[(int) $user_id] = [
				'firstname'   => $row['firstname'],
				'campaign_id' => $row['campaign_id'] === null ? null : (int) $row['campaign_id'],
			];
		}

		return $rows;
	}

	private function writeUsersRow(int $userId, string $firstname, ?int $campaignId): void
	{
		$db    = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();

		$query->update($db->quoteName('#__emundus_users'))
			->set($db->quoteName('firstname') . ' = ' . $db->quote($firstname))
			->set($db->quoteName('campaign_id') . ' = ' . ($campaignId === null ? 'NULL' : (int) $campaignId))
			->where($db->quoteName('user_id') . ' = ' . (int) $userId);

		$db->setQuery($query);
		$db->execute();
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

		$this->clearRepeatFixtures();

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

	// -------------------------------------------------------------------------
	// Repeat groups (getFabrikElementValues)
	// -------------------------------------------------------------------------

	private const REPEAT_FNUM = '2099010100000000000000999999';

	private const REPEAT_PARENT_TABLE = 'jos_emundus_unit_repeat_parent';

	private const REPEAT_TABLE = 'jos_emundus_unit_repeat_parent_1_repeat';

	private const REPEAT_REF_TABLE = 'jos_emundus_unit_repeat_ref';

	private const REPEAT_MULTI_TABLE = 'jos_emundus_unit_repeat_parent_1_repeat_repeat_multi';

	private const REPEAT_MULTI_ELEMENT_ID = 999201;

	/**
	 * Parent row id of each evaluation inserted by createRepeatFixtures(), in insertion order.
	 */
	private array $repeatParentIds = [];

	/**
	 * Two rows of the same file in the parent table (like two evaluations), the first one holding
	 * one repetition, the second one three: a filled one, an unanswered one, a filled one.
	 */
	private function createRepeatFixtures(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$this->clearRepeatFixtures();

		$db->setQuery('CREATE TABLE ' . $db->quoteName(self::REPEAT_PARENT_TABLE) . ' (id INT AUTO_INCREMENT PRIMARY KEY, fnum VARCHAR(28), step_id INT)')->execute();
		$db->setQuery('CREATE TABLE ' . $db->quoteName(self::REPEAT_TABLE) . ' (id INT AUTO_INCREMENT PRIMARY KEY, parent_id INT, txt TEXT, yn TEXT, radio TEXT, cur TEXT, country TEXT, multi INT)')->execute();
		$db->setQuery('CREATE TABLE ' . $db->quoteName(self::REPEAT_REF_TABLE) . ' (id INT PRIMARY KEY, label VARCHAR(255))')->execute();
		$db->setQuery('CREATE TABLE ' . $db->quoteName(self::REPEAT_MULTI_TABLE) . ' (id INT AUTO_INCREMENT PRIMARY KEY, parent_id INT, multi INT)')->execute();

		$db->setQuery('INSERT INTO ' . $db->quoteName(self::REPEAT_REF_TABLE) . " VALUES (1, 'Belgique'), (2, 'France')")->execute();

		foreach ([1, 2] as $step) {
			$db->setQuery('INSERT INTO ' . $db->quoteName(self::REPEAT_PARENT_TABLE) . ' (fnum, step_id) VALUES (' . $db->quote(self::REPEAT_FNUM) . ', ' . $step . ')')->execute();
			$this->repeatParentIds[] = (int) $db->insertid();
		}

		$repetitions = [
			[$this->repeatParentIds[0], "'seul'", "'1'", "'1'", "'100,00 € (EUR)'", "'2'", [2]],
			[$this->repeatParentIds[1], "'a'", "'1'", "'1'", "'400,00 € (EUR)'", "'1'", [1, 2]],
			[$this->repeatParentIds[1], 'NULL', 'NULL', "''", "''", "'0'", []],
			[$this->repeatParentIds[1], "'c'", "'0'", "'2'", "'750,00 € (EUR)'", "'2'", [2]],
		];
		foreach ($repetitions as [$parentId, $txt, $yn, $radio, $cur, $country, $multi]) {
			$db->setQuery('INSERT INTO ' . $db->quoteName(self::REPEAT_TABLE) . ' (parent_id, txt, yn, radio, cur, country) VALUES (' . implode(', ', [$parentId, $txt, $yn, $radio, $cur, $country]) . ')')->execute();
			$repetitionId = (int) $db->insertid();

			foreach ($multi as $choice) {
				$db->setQuery('INSERT INTO ' . $db->quoteName(self::REPEAT_MULTI_TABLE) . ' (parent_id, multi) VALUES (' . $repetitionId . ', ' . $choice . ')')->execute();
			}
		}

		$query = $db->createQuery()
			->insert($db->quoteName('#__fabrik_joins'))
			->columns($db->quoteName(['list_id', 'element_id', 'join_from_table', 'table_join', 'table_key', 'table_join_key', 'join_type', 'group_id', 'params']))
			->values(implode(', ', [0, self::REPEAT_MULTI_ELEMENT_ID, $db->quote(self::REPEAT_TABLE), $db->quote(self::REPEAT_MULTI_TABLE), $db->quote('multi'), $db->quote('parent_id'), $db->quote('left'), 0, $db->quote('{"type":"repeatElement"}')]));
		$db->setQuery($query)->execute();
	}

	private function clearRepeatFixtures(): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');

		foreach ([self::REPEAT_PARENT_TABLE, self::REPEAT_TABLE, self::REPEAT_REF_TABLE, self::REPEAT_MULTI_TABLE] as $table) {
			$db->setQuery('DROP TABLE IF EXISTS ' . $db->quoteName($table))->execute();
		}

		$query = $db->createQuery()
			->delete($db->quoteName('#__fabrik_joins'))
			->where($db->quoteName('element_id') . ' = ' . self::REPEAT_MULTI_ELEMENT_ID);
		$db->setQuery($query)->execute();

		$this->repeatParentIds = [];
	}

	/**
	 * The element array getFabrikElementValues() expects, for a column of the repeat fixture table.
	 */
	private function buildRepeatElement(string $name, string $plugin, array $params = [], string $tableJoin = self::REPEAT_TABLE, int $id = 999200): array
	{
		return [
			'id'            => $id,
			'name'          => $name,
			'plugin'        => $plugin,
			'params'        => json_encode((object) $params),
			'group_params'  => json_encode(['repeat_group_button' => 1]),
			'db_table_name' => self::REPEAT_PARENT_TABLE,
			'table_join'    => $tableJoin,
		];
	}

	/**
	 * Value of the second parent row (three repetitions), formatted, joined with $separator.
	 */
	private function getRepeatValue(array $element, ?string $separator = null, array $translations = []): mixed
	{
		$values = $this->helper->getFabrikElementValue($element, self::REPEAT_FNUM, $this->repeatParentIds[1], ValueFormatEnum::FORMATTED, 0, ExportModeEnum::GROUP_CONCAT, $translations, $separator);

		return $values[$element['id']][self::REPEAT_FNUM]['val'] ?? null;
	}

	/**
	 * @covers EmundusHelperFabrik::getFabrikElementValues
	 * @return void
	 */
	public function testGetFabrikElementValuesOnRepeatGroupKeepsAnEmptySlotForAnUnansweredRepetition(): void
	{
		$this->createRepeatFixtures();

		$this->assertSame('a, , c', $this->getRepeatValue($this->buildRepeatElement('txt', 'field')), 'A NULL repetition should keep its slot instead of being dropped by GROUP_CONCAT');
	}

	/**
	 * @covers EmundusHelperFabrik::getFabrikElementValues
	 * @return void
	 */
	public function testGetFabrikElementValuesOnRepeatYesNoTransformsEachRepetitionWithTheMarkerSeparator(): void
	{
		$this->createRepeatFixtures();

		$value = $this->getRepeatValue($this->buildRepeatElement('yn', 'yesno'), EmundusHelperFabrik::VALUE_SEPARATOR_MARKER, ['JYES' => 'Oui', 'JNO' => 'Non']);

		$this->assertSame('Oui[SEPARATOR][SEPARATOR]Non', $value, 'Each repetition should be transformed on its own, and an unanswered one should not read as "Non"');
	}

	/**
	 * @covers EmundusHelperFabrik::getFabrikElementValues
	 * @return void
	 */
	public function testGetFabrikElementValuesOnRepeatRadioResolvesTheLabelOfEachRepetition(): void
	{
		$this->createRepeatFixtures();

		$element = $this->buildRepeatElement('radio', 'radiobutton', ['sub_options' => ['sub_values' => ['1', '2'], 'sub_labels' => ['Forfait', 'Réel']]]);
		$value   = $this->getRepeatValue($element, EmundusHelperFabrik::VALUE_SEPARATOR_MARKER);

		$this->assertSame('Forfait[SEPARATOR][SEPARATOR]Réel', $value, 'Each repetition should get its own label, not the first label of the list');
	}

	/**
	 * @covers EmundusHelperFabrik::getFabrikElementValues
	 * @return void
	 */
	public function testGetFabrikElementValuesOnRepeatCurrencyDoesNotSplitInsideTheDecimalComma(): void
	{
		$this->createRepeatFixtures();

		$value = $this->getRepeatValue($this->buildRepeatElement('cur', 'currency'));

		$this->assertSame('400, , 750', $value, 'Each formatted amount should stay whole, "400,00 € (EUR)" must not be cut on its decimal comma');
	}

	/**
	 * @covers EmundusHelperFabrik::getFabrikElementValues
	 * @return void
	 */
	public function testGetFabrikElementValuesOnRepeatCurrencyReturnsEveryAmountWithTheMarkerSeparator(): void
	{
		$this->createRepeatFixtures();

		$value = $this->getRepeatValue($this->buildRepeatElement('cur', 'currency'), EmundusHelperFabrik::VALUE_SEPARATOR_MARKER);

		$this->assertSame('400[SEPARATOR][SEPARATOR]750', $value, 'Every amount should be returned, not only the first one');
	}

	/**
	 * @covers EmundusHelperFabrik::getFabrikValueRepeat
	 * @return void
	 */
	public function testGetFabrikElementValuesOnRepeatDatabaseJoinKeepsARepetitionWithoutMatch(): void
	{
		$this->createRepeatFixtures();

		$element = $this->buildRepeatElement('country', 'databasejoin', [
			'join_db_name'               => self::REPEAT_REF_TABLE,
			'join_key_column'            => 'id',
			'join_val_column'            => 'label',
			'database_join_display_type' => 'dropdown',
		]);

		$this->assertSame('Belgique, , France', $this->getRepeatValue($element), 'A repetition whose value matches nothing in the joined table should keep its slot');
	}

	/**
	 * The element is loaded with the join of its own choices as table_join (FabrikRepository picks
	 * one of its two joins), the repeat table has to come from #__fabrik_joins.
	 *
	 * @covers EmundusHelperFabrik::getFabrikValueRepeat
	 * @return void
	 */
	public function testGetFabrikElementValuesOnRepeatMultiDatabaseJoinGroupsTheChoicesOfEachRepetition(): void
	{
		$this->createRepeatFixtures();

		$element = $this->buildRepeatElement('multi', 'databasejoin', [
			'join_db_name'               => self::REPEAT_REF_TABLE,
			'join_key_column'            => 'id',
			'join_val_column'            => 'label',
			'database_join_display_type' => 'multilist',
		], self::REPEAT_MULTI_TABLE, self::REPEAT_MULTI_ELEMENT_ID);

		$value = $this->getRepeatValue($element, EmundusHelperFabrik::VALUE_SEPARATOR_MARKER);

		$this->assertSame('Belgique, France[SEPARATOR][SEPARATOR]France', $value, 'The choices of a repetition should stay together, and a repetition without choice should keep its slot');
	}

	/**
	 * @covers EmundusHelperFabrik::getFabrikValueRepeat
	 * @return void
	 */
	public function testGetFabrikElementValuesOnRepeatGroupOnlyReturnsTheRepetitionsOfTheRequestedParentRow(): void
	{
		$this->createRepeatFixtures();

		$element = $this->buildRepeatElement('txt', 'field');
		$values  = $this->helper->getFabrikElementValue($element, self::REPEAT_FNUM, $this->repeatParentIds[0]);

		$this->assertSame('seul', $values[$element['id']][self::REPEAT_FNUM]['val'], 'Only the repetitions of the requested row (evaluation) should be returned, not those of the other rows of the file');
	}
}
