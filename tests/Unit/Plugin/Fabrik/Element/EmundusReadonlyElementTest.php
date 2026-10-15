<?php

namespace Unit\Plugin\Fabrik\Element;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Enums\Fabrik\ReadonlyStorageTypeEnum;

require_once JPATH_LIBRARIES . '/fabrik/fabrik/include.php';
require_once JPATH_SITE . '/components/com_fabrik/models/plugin.php';
require_once JPATH_SITE . '/components/com_fabrik/models/element.php';
require_once JPATH_SITE . '/plugins/fabrik_element/emundusreadonly/emundusreadonly.php';

/**
 * @covers \PlgFabrik_ElementEmundusreadonly
 */
class EmundusReadonlyElementTest extends UnitTestCase
{
	private array $originalProfile = [];

	protected function setUp(): void
	{
		parent::setUp();

		$coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		Factory::getApplication()->loadIdentity($coordinator);
	}

	protected function tearDown(): void
	{
		if (!empty($this->originalProfile))
		{
			$query = $this->db->getQuery(true)
				->update('#__emundus_users')
				->set($this->db->quoteName('firstname') . ' = ' . $this->db->quote($this->originalProfile['firstname']))
				->where($this->db->quoteName('user_id') . ' = ' . (int) $this->dataset['applicant']);
			$this->db->setQuery($query);
			$this->db->execute();
		}

		parent::tearDown();
	}

	public function testGetFormattedValue_WhenSourceIsProfileField_ReturnsTheFileApplicantValue(): void
	{
		$sourceId = $this->findElementId('jos_emundus_users', 'firstname');
		$this->assertNotEmpty($sourceId, 'A published firstname element on jos_emundus_users is required');

		$this->setApplicantFirstname('ReadonlyProfileTest');

		$value = $this->buildElement($sourceId)->getFormattedValue(['jos_emundus_1001_00___fnum' => $this->dataset['fnum']]);

		$this->assertSame('ReadonlyProfileTest', $value);
	}

	public function testGetFormattedValue_WhenSourceTableHasNoFnumAndIsNotProfile_ReturnsEmptyString(): void
	{
		$sourceId = $this->findElementId('jos_emundus_setup_programmes', 'label');
		if (empty($sourceId))
		{
			$this->markTestSkipped('No published label element on jos_emundus_setup_programmes');
		}

		$value = $this->buildElement($sourceId)->getFormattedValue(['jos_emundus_1001_00___fnum' => $this->dataset['fnum']]);

		$this->assertSame('', $value);
	}

	public static function datetimeStorageProvider(): array
	{
		return [
			'datetime value'    => ['2026-10-13 10:30:00', '2026-10-13 10:30:00'],
			'date only value'   => ['2026-10-13', '2026-10-13 00:00:00'],
			'empty value'       => ['', null],
			'formatted label'   => ['13/10/2026', null],
		];
	}

	/**
	 * @dataProvider datetimeStorageProvider
	 */
	public function testStoreDatabaseFormat_WhenStoredInDatetime_KeepsOnlyValidDates(string $posted, ?string $expected): void
	{
		$element = $this->buildElement(0, ReadonlyStorageTypeEnum::DATETIME);

		$this->assertSame($expected, $element->storeDatabaseFormat($posted, []));
	}

	public function testStoreDatabaseFormat_WhenStoredInDate_DropsTheTime(): void
	{
		$element = $this->buildElement(0, ReadonlyStorageTypeEnum::DATE);

		$this->assertSame('2026-10-13', $element->storeDatabaseFormat('2026-10-13 10:30:00', []));
	}

	public function testStoreDatabaseFormat_WhenStoredInText_KeepsThePostedValue(): void
	{
		$element = $this->buildElement(0, ReadonlyStorageTypeEnum::TEXT);

		$this->assertSame('13/10/2026', $element->storeDatabaseFormat('13/10/2026', []));
	}

	private function buildElement(int $sourceId, ?ReadonlyStorageTypeEnum $storageType = null): \PlgFabrik_ElementEmundusreadonly
	{
		// The Fabrik constructor boots the element validator model, unavailable outside a Fabrik request and unused here
		$element = (new \ReflectionClass(\PlgFabrik_ElementEmundusreadonly::class))->newInstanceWithoutConstructor();

		$params = new \ReflectionProperty($element, 'params');
		$params->setValue($element, new Registry(['source_element_id' => $sourceId]));

		if ($storageType !== null)
		{
			(new \ReflectionProperty($element, 'storageType'))->setValue($element, $storageType);
		}

		return $element;
	}

	private function findElementId(string $tableName, string $elementName): int
	{
		$query = $this->db->getQuery(true)
			->select('fe.id')
			->from($this->db->quoteName('#__fabrik_elements', 'fe'))
			->join('INNER', $this->db->quoteName('#__fabrik_formgroup', 'ffg') . ' ON ffg.group_id = fe.group_id')
			->join('INNER', $this->db->quoteName('#__fabrik_lists', 'fl') . ' ON fl.form_id = ffg.form_id')
			->where('fl.db_table_name = ' . $this->db->quote($tableName))
			->where('fe.name = ' . $this->db->quote($elementName))
			->where('fe.published = 1')
			->order('fe.id ASC');
		$this->db->setQuery($query, 0, 1);

		return (int) $this->db->loadResult();
	}

	private function setApplicantFirstname(string $firstname): void
	{
		$query = $this->db->getQuery(true)
			->select('firstname')
			->from('#__emundus_users')
			->where($this->db->quoteName('user_id') . ' = ' . (int) $this->dataset['applicant']);
		$this->db->setQuery($query);
		$this->originalProfile = ['firstname' => (string) $this->db->loadResult()];

		$query = $this->db->getQuery(true)
			->update('#__emundus_users')
			->set($this->db->quoteName('firstname') . ' = ' . $this->db->quote($firstname))
			->where($this->db->quoteName('user_id') . ' = ' . (int) $this->dataset['applicant']);
		$this->db->setQuery($query);
		$this->db->execute();
	}
}
