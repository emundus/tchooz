<?php

namespace Unit\Component\Emundus\Class\Services\Fabrik;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Fabrik\FabrikElementEntity;
use Tchooz\Enums\Fabrik\ElementPluginEnum;
use Tchooz\Enums\Fabrik\ReadonlyStorageTypeEnum;
use Tchooz\Services\Fabrik\ReadonlyElementStorage;

/**
 * @package     Unit\Component\Emundus\Class\Services\Fabrik
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Services\Fabrik\ReadonlyElementStorage
 */
class ReadonlyElementStorageTest extends UnitTestCase
{
	private const TABLE = 'jos_emundus_test_readonly_storage';

	private ReadonlyElementStorage $storage;

	protected function setUp(): void
	{
		parent::setUp();

		$this->storage = new ReadonlyElementStorage();

		$this->db->setQuery('DROP TABLE IF EXISTS ' . self::TABLE);
		$this->db->execute();
		$this->db->setQuery('CREATE TABLE ' . self::TABLE . ' (id INT AUTO_INCREMENT PRIMARY KEY, ro_text TEXT NULL, ro_datetime DATETIME NULL)');
		$this->db->execute();
	}

	protected function tearDown(): void
	{
		$this->db->setQuery('DROP TABLE IF EXISTS ' . self::TABLE);
		$this->db->execute();

		parent::tearDown();
	}

	public function testSyncColumn_WhenTextColumnIsEmpty_ChangesItToDatetime(): void
	{
		$this->assertTrue($this->storage->syncColumn(self::TABLE, 'ro_text', ReadonlyStorageTypeEnum::DATETIME));

		$this->assertSame('datetime', $this->getColumnType('ro_text'));
	}

	public function testSyncColumn_WhenTextColumnHoldsData_KeepsItsType(): void
	{
		$this->insertRow(['ro_text' => '13/10/2026']);

		$this->assertFalse($this->storage->syncColumn(self::TABLE, 'ro_text', ReadonlyStorageTypeEnum::DATETIME));

		$this->assertSame('text', $this->getColumnType('ro_text'));
		$this->assertSame('13/10/2026', $this->loadColumn('ro_text')[0]);
	}

	public function testSyncColumn_WhenTextColumnOnlyHoldsEmptyValues_ChangesItAndNullsThem(): void
	{
		$this->insertRow(['ro_text' => '']);
		$this->insertRow(['ro_text' => null]);

		$this->assertTrue($this->storage->syncColumn(self::TABLE, 'ro_text', ReadonlyStorageTypeEnum::DATETIME));

		$this->assertSame('datetime', $this->getColumnType('ro_text'));
		$this->assertSame([null, null], $this->loadColumn('ro_text'));
	}

	public function testSyncColumn_WhenDatetimeColumnIsEmpty_ChangesItBackToText(): void
	{
		$this->insertRow(['ro_datetime' => null]);

		$this->assertTrue($this->storage->syncColumn(self::TABLE, 'ro_datetime', ReadonlyStorageTypeEnum::TEXT));

		$this->assertSame('text', $this->getColumnType('ro_datetime'));
	}

	public function testSyncColumn_WhenDatetimeColumnHoldsADate_KeepsItsType(): void
	{
		$this->insertRow(['ro_datetime' => '2026-10-13 10:00:00']);

		$this->assertFalse($this->storage->syncColumn(self::TABLE, 'ro_datetime', ReadonlyStorageTypeEnum::TEXT));

		$this->assertSame('datetime', $this->getColumnType('ro_datetime'));
	}

	public function testGetColumnTypeById_WhenElementDoesNotExist_ReturnsText(): void
	{
		$this->assertSame(ReadonlyStorageTypeEnum::TEXT, $this->storage->getColumnTypeById(0));
	}

	public function testGetColumnTypeById_WhenElementIsStoredInADateColumn_ReturnsItsType(): void
	{
		$query = $this->db->getQuery(true)
			->select('fe.id, fe.name, fl.db_table_name')
			->from($this->db->quoteName('#__fabrik_elements', 'fe'))
			->join('INNER', $this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ffg.group_id = fe.group_id')
			->join('INNER', $this->db->quoteName('#__fabrik_groups', 'fg') . ' ON fg.id = fe.group_id')
			->join('INNER', $this->db->quoteName('#__fabrik_lists', 'fl') . ' ON fl.form_id = ffg.form_id')
			->where('fe.plugin = ' . $this->db->quote('birthday'))
			->where('fe.published = 1')
			->where('JSON_EXTRACT(fg.params, "$.repeat_group_button") <> 1');
		$this->db->setQuery($query);
		$candidates = $this->db->loadObjectList();

		foreach ($candidates as $candidate)
		{
			$columns = $this->db->getTableColumns($candidate->db_table_name);
			if (($columns[$candidate->name] ?? '') === 'date')
			{
				$this->assertSame(ReadonlyStorageTypeEnum::DATE, $this->storage->getColumnTypeById((int) $candidate->id));

				return;
			}
		}

		$this->markTestSkipped('No published birthday element stored in a DATE column');
	}

	public function testDisplayAsDateSource_WhenReadonlyIsStoredInADateColumn_TakesTheSourcePluginAndParams(): void
	{
		$source = $this->createMock(FabrikElementEntity::class);
		$source->method('getPlugin')->willReturn(ElementPluginEnum::JDATE);
		$source->method('getParamsRaw')->willReturn('{"jdate_form_format":"d\\/m\\/Y"}');

		$storage = $this->createPartialMock(ReadonlyElementStorage::class, ['getDateSource']);
		$storage->method('getDateSource')->with(12)->willReturn($source);

		$elements = $storage->displayAsDateSource([(object) ['id' => 12, 'plugin' => 'emundusreadonly', 'params' => '{}']]);

		$this->assertSame('jdate', $elements[0]->plugin);
		$this->assertSame('{"jdate_form_format":"d\\/m\\/Y"}', $elements[0]->params);
	}

	public function testDisplayAsDateSource_WhenReadonlyIsStoredAsText_KeepsItAsIs(): void
	{
		$storage = $this->createPartialMock(ReadonlyElementStorage::class, ['getDateSource']);
		$storage->method('getDateSource')->willReturn(null);

		$elements = $storage->displayAsDateSource([(object) ['id' => 12, 'plugin' => 'emundusreadonly', 'params' => '{}']]);

		$this->assertSame('emundusreadonly', $elements[0]->plugin);
		$this->assertSame('{}', $elements[0]->params);
	}

	public function testDisplayAsDateSource_WhenElementIsNotReadonly_NeverLooksForASource(): void
	{
		$storage = $this->createPartialMock(ReadonlyElementStorage::class, ['getDateSource']);
		$storage->expects($this->never())->method('getDateSource');

		$elements = $storage->displayAsDateSource([(object) ['id' => 3, 'plugin' => 'field', 'params' => '{}']]);

		$this->assertSame('field', $elements[0]->plugin);
	}

	private function insertRow(array $values): void
	{
		$row = (object) $values;
		$this->db->insertObject(self::TABLE, $row);
	}

	private function loadColumn(string $column): array
	{
		$this->db->setQuery('SELECT ' . $this->db->quoteName($column) . ' FROM ' . self::TABLE . ' ORDER BY id');

		return $this->db->loadColumn();
	}

	private function getColumnType(string $column): string
	{
		return $this->db->getTableColumns(self::TABLE)[$column] ?? '';
	}
}
