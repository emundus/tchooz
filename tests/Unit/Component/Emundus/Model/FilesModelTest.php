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
use EmundusHelperUsers;
use EmundusModelFiles;
use EmundusModelProfile;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Joomla\CMS\Language\Text;

require_once JPATH_SITE . '/components/com_emundus/models/files.php';
require_once JPATH_SITE . '/components/com_emundus/helpers/files.php';
require_once JPATH_SITE . '/components/com_emundus/helpers/users.php';
require_once JPATH_SITE . '/components/com_emundus/models/profile.php';

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelFiles
 */
class FilesModelTest extends UnitTestCase
{
	/**
	 * @var    EmundusHelperFiles
	 * @since  4.2.0
	 */
	private $h_files;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('files', $data, $dataName, 'EmundusModelFiles');

		$this->h_files  = new EmundusHelperFiles;
	}

	/**
	 * @covers EmundusModelFiles::__construct
	 *
	 * @since version 1.0.0
	 */
	public function testConstruct()
	{
		$this->assertSame(false, $this->model->use_module_filters, 'By default, we do not use new module filters');
	}

	/**
	 * @covers EmundusModelFiles::shareUsers
	 *
	 * @since version 1.0.0
	 */
	public function testshareUsers()
	{
		$shared = $this->model->shareUsers([2], EVALUATOR_RIGHTS, [$this->dataset['fnum']], Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$this->assertTrue($shared, 'shareUsers returns true if the sharing is successful');
	}

	/**
	 * @covers EmundusModelFiles::unshareUsers
	 *
	 * @since version 1.0.0
	 */
	public function testunshareUsers()
	{
		$shared = $this->model->shareUsers([2], EVALUATOR_RIGHTS, [$this->dataset['fnum']], Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));

		if($shared) {
			$unshared = $this->model->unshareUsers([2],[$this->dataset['fnum']],Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));

			$this->assertTrue($unshared, 'unshareUsers returns true if the unsharing is successful');
		}

		$unshared = $this->model->unshareUsers([2], [], Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$this->assertFalse($unshared, 'unshareUsers returns false if no fnum is given');

		$unshared = $this->model->unshareUsers([], [$this->dataset['fnum']], Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$this->assertFalse($unshared, 'unshareUsers returns false if no user is given');

		$unshared = $this->model->unshareUsers([2], [$this->dataset['fnum']], Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$this->assertTrue($unshared, 'unshareUsers returns true even if the user is not shared');
	}

	/**
	 * @covers EmundusModelFiles::getAllFnums
	 *
	 * @since version 1.0.0
	 */
	public function testgetAllFnums()
	{
		$fnums = $this->model->getAllFnums(false, 1);
		$this->assertIsArray($fnums, 'getusers returns an array');

		$filter = [
			'id' => 'programs',
			'uid' => 'programs',
			'label' => 'Formations',
			'type' => 'select',
			'value' => [$this->dataset['program']['programme_id']],
			'values' => [],
			'operator' => 'IN',
			'andorOperator' => 'OR',
			'default' => true,
			'available' => true,
			'order' => 0,
		];

		$session = Factory::getApplication()->getSession();
		$session->set('em-applied-filters', [$filter]);

		$this->model->shareUsers([2], EVALUATOR_RIGHTS, [$this->dataset['fnum']], Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$fnums = $this->model->getAllFnums(false, 2);

		$this->assertNotEmpty($fnums, 'if a fnum exists, by default get users should return a value');
		$this->assertTrue(in_array($this->dataset['fnum'], $fnums), 'If a fnum is associated to me. I should see it.');
	}

	/**
	 * @covers EmundusModelFiles::getAllTags
	 *
	 * @since version 1.0.0
	 */
	public function testgetAllTags()
	{
		$tags = $this->model->getAllTags();
		$this->assertIsArray($tags, 'getAllTags returns an array');
		$this->assertNotEmpty($tags, 'getAllTags returns a non-empty array');
	}

	/**
	 * @covers EmundusModelFiles::tagFile
	 *
	 * @since version 1.0.0
	 */
	public function testTagFile()
	{
		$tagged = $this->model->tagFile([], []);
		$this->assertFalse($tagged, 'tagFile returns false if no file is given');

		$tagged = $this->model->tagFile([$this->dataset['fnum']], []);
		$this->assertFalse($tagged, 'tagFile returns false if no tag is given');

		$tags   = $this->model->getAllTags();
		$tagged = $this->model->tagFile([$this->dataset['fnum']], [$tags[0]['id']], $this->dataset['coordinator']);
		$this->assertTrue($tagged, 'tagFile returns true if a file and a tag are given');
	}

	/**
	 * @covers EmundusModelFiles::updateState
	 *
	 * @since version 1.0.0
	 */
	public function testUpdateState()
	{
		$updated = $this->model->updateState([], null);
		$this->assertFalse($updated, 'updateState returns false if no file and no new state is given');
	}

	/**
	 * @covers EmundusModelFiles::getFnumArray2
	 *
	 * @since version 1.0.0
	 */
	public function testgetFnumArray2()
	{
		$fnums    = [];
		$elements = [];
		$data     = $this->model->getFnumArray2($fnums, $elements);
		$this->assertEmpty($data, 'getFnumArray returns an empty array if no fnum is given');

		$element_ids = [];
		$form_id     = $this->h_dataset->getUnitTestFabrikForm();
		
		$query = $this->db->getQuery(true);
		$query->select('jfe.id')
			->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
			->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
			->where('jffg.form_id = ' . $form_id)
			->andWhere('jfe.hidden = 0');

		$this->db->setQuery($query);
		$element_ids = $this->db->loadColumn();
		$element_ids = implode(',', $element_ids);
		$elements    = $this->h_files->getElementsName($element_ids);
		$this->assertNotEmpty($elements, 'getElementsName returns an array of elements');

		$columns = ['user', 'fnum', 'e_797_7973', 'e_797_7974', 'e_797_7975', 'e_797_7976', 'e_797_7977', 'e_797_7978', 'e_797_7979', 'e_797_7980', 'e_797_7981', 'e_797_7982', 'e_797_7983', 'dropdown_multi', 'dbjoin_multi', 'cascadingdropdown'];
		$values  = array($this->dataset['applicant'], $this->dataset['fnum'], 'TEST FIELD', 'TEST TEXTAREA', '["1"]', '2', '3', '65', 'Ajoutez du texte personnalisé pour vos candidats', "<p>S'il vous plait taisez vous</p>", '1', '2023-01-01', '2023-07-13 00:00:00', '["0","1"]', 0, '');
		$query->clear()
			->insert('jos_emundus_unit_test_form')
			->columns($columns)
			->values(implode(',', $this->db->quote($values)));

		$this->db->setQuery($query);
		$this->db->execute();
		$insert_id = $this->db->insertid();

		if (!empty($insert_id)) {
			$query->clear()
				->insert('jos_emundus_unit_test_form_repeat_dbjoin_multi')
				->columns(['parent_id', 'dbjoin_multi'])
				->values($insert_id . ', "17"');

			$this->db->setQuery($query);
			$this->db->execute();
		}

		$field_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'field') {
				$field_element = $element;
				break;
			}
		}
		if ($field_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$field_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with field element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($field_element->tab_name . '___' . $field_element->element_name, $data[$this->dataset['fnum']], 'the data contains the field element');
			// after we mock the data, we should test that the data is correct
			$this->assertEquals('TEST FIELD', $data[$this->dataset['fnum']][$field_element->tab_name . '___' . $field_element->element_name], 'the fnum contains the field element');
		}

		$texarea_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'textarea') {
				$texarea_element = $element;
				break;
			}
		}
		if ($texarea_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$texarea_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with texarea element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($texarea_element->tab_name . '___' . $texarea_element->element_name, $data[$this->dataset['fnum']], 'the data contains the textarea element');
			$this->assertEquals('TEST TEXTAREA', $data[$this->dataset['fnum']][$texarea_element->tab_name . '___' . $texarea_element->element_name], 'the fnum contains the field element');

		}

		$display_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'display') {
				$display_element = $element;
				break;
			}
		}
		if ($display_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$display_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with display element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($display_element->tab_name . '___' . $display_element->element_name, $data[$this->dataset['fnum']], 'the data contains the display element');
		}

		$yesno_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'yesno') {
				$yesno_element = $element;
				break;
			}
		}
		if ($yesno_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$yesno_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with yesno element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($yesno_element->tab_name . '___' . $yesno_element->element_name, $data[$this->dataset['fnum']], 'the data contains the yesno element');
			$this->assertContains($data[$this->dataset['fnum']][$yesno_element->tab_name . '___' . $yesno_element->element_name], [Text::_('JNO'), Text::_('JYES')], 'the yesno element contains translation for 0 and 1, such as jyes and jno');
		}

		$date_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'date') {
				$date_element = $element;
				break;
			}
		}
		if ($date_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$date_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with date element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($date_element->tab_name . '___' . $date_element->element_name, $data[$this->dataset['fnum']], 'the data contains the date element');
			$this->assertStringMatchesFormat('%d-%d-%d %d:%d:%d', $data[$this->dataset['fnum']][$date_element->tab_name . '___' . $date_element->element_name], 'the date element contains a date in the correct format');
		}

		$birthday_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'birthday') {
				$birthday_element = $element;
				break;
			}
		}
		if ($birthday_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$birthday_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with birthday element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($birthday_element->tab_name . '___' . $birthday_element->element_name, $data[$this->dataset['fnum']], 'the data contains the birthday element');
			$this->assertStringMatchesFormat('%d-%d-%d', $data[$this->dataset['fnum']][$birthday_element->tab_name . '___' . $birthday_element->element_name], 'the date element contains a birthday in the correct format');
		}

		$databasejoin_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'databasejoin') {
				$element_attribs = json_decode($element->element_attribs);
				if ($element_attribs->database_join_display_type === 'dropdown' || $element_attribs->database_join_display_type === 'radio') {
					$databasejoin_element = $element;
					break;
				}
			}
		}
		if ($databasejoin_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$databasejoin_element], 0, 0, 0, 1);
			$this->assertNotFalse($data, 'getFnumArray does not encounter an error');
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with databasejoin element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($databasejoin_element->tab_name . '___' . $databasejoin_element->element_name, $data[$this->dataset['fnum']], 'the data contains the databasejoin element');
		}

		$databasejoin_multi_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'databasejoin') {
				$element_attribs = json_decode($element->element_attribs);
				if ($element_attribs->database_join_display_type === 'checkbox' || $element_attribs->database_join_display_type === 'multilist') {
					$databasejoin_multi_element = $element;
					break;
				}
			}
		}
		if ($databasejoin_multi_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$databasejoin_multi_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with databasejoin multi element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($databasejoin_multi_element->table_join . '___' . $databasejoin_multi_element->element_name, $data[$this->dataset['fnum']], 'the data contains the databasejoin multi element');
			$this->assertStringContainsString('Charente-Maritime', $data[$this->dataset['fnum']][$databasejoin_multi_element->table_join . '___' . $databasejoin_multi_element->element_name], 'the databasejoin multi element contains the correct value');
		}

		$radio_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'radiobutton') {
				$radio_element = $element;
				break;
			}
		}
		if ($radio_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$radio_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with radiobutton element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($radio_element->tab_name . '___' . $radio_element->element_name, $data[$this->dataset['fnum']], 'the data contains the radiobutton element');
		}

		$dropdown_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'dropdown') {
				$element_attribs = json_decode($element->element_attribs);

				if ($element_attribs->multiple == 0) {
					$dropdown_element = $element;
					break;
				}
			}
		}
		if ($dropdown_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$dropdown_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with dropdown element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($dropdown_element->tab_name . '___' . $dropdown_element->element_name, $data[$this->dataset['fnum']], 'the data contains the dropdown element');
		}

		$dropdown_multi_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'dropdown') {
				$element_attribs = json_decode($element->element_attribs);
				if ($element_attribs->multiple == 1) {
					$dropdown_multi_element = $element;
					break;
				}
			}
		}
		if ($dropdown_multi_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$dropdown_multi_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with dropdown multiselect element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name, $data[$this->dataset['fnum']], 'the data contains the dropdown multiselect element');
			$this->assertNotEmpty($data[$this->dataset['fnum']][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'the data contains the dropdown multiselect element');
			$this->assertStringNotContainsString('[', $data[$this->dataset['fnum']][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'dropdown multiselect element, open bracket has been removed');
			$this->assertStringNotContainsString(']', $data[$this->dataset['fnum']][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'dropdown multiselect element, close bracket has been removed');
		}

		$cascadingdropdown_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'cascadingdropdown') {
				$cascadingdropdown_element = $element;
				break;
			}
		}
		if ($cascadingdropdown_element) {
			$data = $this->model->getFnumArray2([$this->dataset['fnum']], [$cascadingdropdown_element], 0, 0, 0, 1);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with cascadingdropdown element');
			$this->assertNotEmpty($data[$this->dataset['fnum']], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($cascadingdropdown_element->tab_name . '___' . $cascadingdropdown_element->element_name, $data[$this->dataset['fnum']], 'the data contains the cascadingdropdown element');
		}

		$first_form_elements = [$birthday_element, $date_element, $yesno_element, $display_element, $texarea_element, $field_element, $databasejoin_element, $radio_element, $dropdown_element, $dropdown_multi_element, $databasejoin_multi_element, $cascadingdropdown_element];
		$data                = $this->model->getFnumArray2([$this->dataset['fnum']], $first_form_elements, 0, 0, 0, 1);
		$this->assertNotEmpty($data, 'getFnumArray returns an not empty array of data with all elements');

		// calculate time of execution
		//$elements_from_different_forms = array_merge($first_form_elements, $repeat_form_elements);
		/*
		$elements_from_different_forms = $first_form_elements;
		$start                         = microtime(true);
		$data                          = $this->model->getFnumArray2([$this->dataset['fnum']], $elements_from_different_forms, 0, 0, 0, 1);
		$end                           = microtime(true);
		$this->assertNotEmpty($data, 'getFnumArray returns a not empty array of data with all elements from different forms');
		$elapsed_new_function_time = $end - $start;

		$start                     = microtime(true);
		$data                      = $this->model->getFnumArray([$this->dataset['fnum']], $elements_from_different_forms, 0, 0, 0, 0, '',Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$end                       = microtime(true);
		$elapsed_old_function_time = $end - $start;

		$this->assertGreaterThanOrEqual($elapsed_new_function_time, $elapsed_old_function_time, 'getFnumArray2 is faster than getFnumArray ' . $elapsed_new_function_time . ' vs ' . $elapsed_old_function_time);
		*/
	}
}