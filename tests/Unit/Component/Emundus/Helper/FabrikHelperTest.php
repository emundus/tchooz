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
	 * @covers EmundusHelperFabrik::getElementByAlias
	 * It should return the name and database table name storage of the element with the alias passed as parameter
	 */
	public function testGetElementByAlias()
	{
		$this->assertNull($this->helper::getElementByAlias(""), 'Empty alias should return null');

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
			$params = json_decode($element->params, true);

			if(empty($params["alias"]))
			{
				$params['alias'] = 'alias' . rand(0, 1000);

				$query->clear()
					->update($db->quoteName('#__fabrik_elements'))
					->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
					->where($db->quoteName('id') . ' = ' . $element->id)
					->setLimit(1);
				$db->setQuery($query);
				$db->execute();
			}

			$element_by_alias = $this->helper::getElementByAlias($params["alias"], $form_id);
			$this->assertEquals($element->name, $element_by_alias->name, 'The element name obtained should be the same as the element name in the database.');
			$this->assertEquals($element->db_table_name, $element_by_alias->db_table_name, 'The database table name storage obtained should be the same as the database table name storage in the database.');
		}
	}

	/**
	 * @return void
	 * @description Test the getValueByAlias() method
	 * @covers EmundusHelperFabrik::getValueByAlias
	 * It should return the value of the element with the alias and form number passed as parameters
	 */
	public function testGetValueByAlias()
	{
		$this->assertEmpty($this->helper::getValueByAlias("", 1)['raw'], 'Empty alias should return empty raw value');
		$this->assertEmpty($this->helper::getValueByAlias("test", "")['raw'], 'Empty fnum should return empty raw value');

		/*$form_id = $this->h_dataset->getUnitTestFabrikForm();

		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);

		$query->select('fe.id, fe.name, fe.params, fl.db_table_name ')
			->from($db->quoteName('#__fabrik_elements', 'fe'))
			->leftJoin($db->quoteName('#__fabrik_formgroup','ffg').' ON '.$db->quoteName('ffg.group_id').' = '.$db->quoteName('fe.group_id'))
			->leftJoin($db->quoteName('#__fabrik_lists','fl').' ON '.$db->quoteName('fl.form_id').' = '.$db->quoteName('ffg.form_id'))
			->where($db->quoteName('ffg.form_id') . ' = ' . $form_id);

		$db->setQuery($query);
		$elements = $db->loadObjectList();

		foreach ($elements as $element) {

			$query->clear()
				->select('tb.fnum, tb.' . $element->name)
				->from($db->quoteName($element->db_table_name, 'tb'))
				->where('tb.id = (SELECT MIN(id) FROM ' . $db->quoteName($element->db_table_name) . ')');

			$db->setQuery($query);
			$results = $db->loadObject();

			$fnum = $results->fnum;
			$expected = $results->{$element->name};

			if(isset($fnum)) {

				$params = json_decode($element->params, true);

				if(empty($params["alias"])) {

					$params['alias'] = 'alias' . rand(0, 1000);

					$query->clear()
						->update($db->quoteName('#__fabrik_elements'))
						->set($db->quoteName('params') . ' = ' . $db->quote(json_encode($params)))
						->where($db->quoteName('id') . ' = ' . $db->quote($element->id))
						->limit(1);
					$db->setQuery($query);
					$db->execute();
				}

				$alias_value = $this->helper::getValueByAlias($params["alias"], $fnum);
				$value = $alias_value['value'];
				$value_raw = $alias_value['raw'];

				if(!empty($expected)) {
					$expected_formatted = $this->helper::formatElementValue($element->name, $expected);
					$this->assertEquals($expected_formatted, $value, 'The value formatted obtained should be the same as the value formatted in the database.');
					$this->assertEquals($expected, $value_raw, 'The value obtained should be the same as the value in the database.');
				}
			}
		}*/
	}
}