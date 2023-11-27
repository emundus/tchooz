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
use Joomla\Tests\Unit\UnitTestCase;

require_once JPATH_SITE . '/components/com_emundus/models/files.php';
require_once JPATH_SITE . '/components/com_emundus/helpers/files.php';
require_once JPATH_SITE . '/components/com_emundus/helpers/users.php';
require_once JPATH_SITE . '/components/com_emundus/models/profile.php';

class FilesModelTest extends UnitTestCase
{
	/**
	 * @var    EmundusModelFiles
	 * @since  4.2.0
	 */
	protected $model;

	/**
	 * @var    EmundusHelperUsers
	 * @since  4.2.0
	 */
	private $h_users;

	/**
	 * @var    EmundusHelperFiles
	 * @since  4.2.0
	 */
	private $h_files;

	/**
	 * @var    int
	 * @since  4.2.0
	 */
	private $unit_test_coord_id;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct($name, $data, $dataName);

		$this->h_users  = new EmundusHelperUsers;
		$this->h_files  = new EmundusHelperFiles;

		$app                      = Factory::getApplication();
		$username                 = 'test-gestionnaire-' . rand(0, 1000) . '@emundus.fr';
		$password                 = $this->h_users->generateStrongPassword();
		$this->unit_test_coord_id = $this->h_dataset->createSampleUser(2, $username, $password);

		if (!empty($this->unit_test_coord_id)) {
			$logged_in = $app->login([
				'username' => $username,
				'password' => $password
			]);

			if ($logged_in) {
				$m_profile = new EmundusModelProfile;
				$m_profile->initEmundusSession();
			}
		}

		$this->model = new EmundusModelFiles();
	}

	public function testConstruct()
	{
		$this->assertSame(false, $this->model->use_module_filters, 'By default, we do not use new module filters');
	}

	public function testshareUsers()
	{
		$user_id     = $this->h_dataset->createSampleUser(9, 'unit-test-candidat-' . rand(0, 1000) . '@emundus.test.fr');
		$program     = $this->h_dataset->createSampleProgram('Test partage d\'utilisateurs');
		$campaign_id = $this->h_dataset->createSampleCampaign($program);
		$fnum        = $this->h_dataset->createSampleFile($campaign_id, $user_id);

		$shared = $this->model->shareUsers([$this->unit_test_coord_id], EVALUATOR_RIGHTS, [$fnum]);
		$this->assertTrue($shared, 'shareUsers returns true if the sharing is successful');
	}

	public function testgetAllFnums()
	{
		$fnums = $this->model->getAllFnums();
		$this->assertIsArray($fnums, 'getusers returns an array');

		$user_id     = $this->h_dataset->createSampleUser(9, 'unit-test-candidat-' . rand(0, 1000) . '@emundus.test.fr');
		$program     = $this->h_dataset->createSampleProgram();
		$campaign_id = $this->h_dataset->createSampleCampaign($program);
		$fnum        = $this->h_dataset->createSampleFile($campaign_id, $user_id);

		$session = Factory::getApplication()->getSession();
		$session->set('filt_params', ['programme' => [$program['programme_code']]]);

		$fnums = $this->model->getAllFnums();
		$this->assertNotEmpty($fnums, 'if a fnum exists, by default get users should return a value');
		$this->assertTrue(in_array($fnum, $fnums), 'If a fnum is associated to me. I should see it.');
	}

	public function testgetAllTags()
	{
		$tags = $this->model->getAllTags();
		$this->assertIsArray($tags, 'getAllTags returns an array');
		$this->assertNotEmpty($tags, 'getAllTags returns a non-empty array');
	}

	public function testTagFile()
	{
		$tagged = $this->model->tagFile([], []);
		$this->assertFalse($tagged, 'tagFile returns false if no file is given');

		$user_id     = $this->h_dataset->createSampleUser(9, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$program     = $this->h_dataset->createSampleProgram();
		$campaign_id = $this->h_dataset->createSampleCampaign($program);
		$fnum        = $this->h_dataset->createSampleFile($campaign_id, $user_id);

		$tagged = $this->model->tagFile([$fnum], []);
		$this->assertFalse($tagged, 'tagFile returns false if no tag is given');

		$tags   = $this->model->getAllTags();
		$tagged = $this->model->tagFile([$fnum], [$tags[0]['id']], 62);
		$this->assertTrue($tagged, 'tagFile returns true if a file and a tag are given');
	}

	public function testUpdateState()
	{
		$updated = $this->model->updateState([], null);
		$this->assertFalse($updated, 'updateState returns false if no file and no new state is given');
	}

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

		$user_id     = $this->h_dataset->createSampleUser(9, 'userunittest' . rand(0, 1000) . '@emundus.test.fr');
		$program     = $this->h_dataset->createSampleProgram();
		$campaign_id = $this->h_dataset->createSampleCampaign($program);
		$fnum        = $this->h_dataset->createSampleFile($campaign_id, $user_id);

		$columns = ['user', 'fnum', 'e_797_7973', 'e_797_7974', 'e_797_7975', 'e_797_7976', 'e_797_7977', 'e_797_7978', 'e_797_7979', 'e_797_7980', 'e_797_7981', 'e_797_7982', 'e_797_7983', 'dropdown_multi', 'dbjoin_multi', 'cascadingdropdown'];
		$values  = array($user_id, $fnum, 'TEST FIELD', 'TEST TEXTAREA', '["1"]', '2', '3', '65', 'Ajoutez du texte personnalisé pour vos candidats', "<p>S'il vous plait taisez vous</p>", '1', '2023-01-01', '2023-07-13 00:00:00', '["0","1"]', null, '');
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
			$data = $this->model->getFnumArray2([$fnum], [$field_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with field element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($field_element->tab_name . '___' . $field_element->element_name, $data[$fnum], 'the data contains the field element');
			// after we mock the data, we should test that the data is correct
			$this->assertEquals('TEST FIELD', $data[$fnum][$field_element->tab_name . '___' . $field_element->element_name], 'the fnum contains the field element');
		}

		$texarea_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'textarea') {
				$texarea_element = $element;
				break;
			}
		}
		if ($texarea_element) {
			$data = $this->model->getFnumArray2([$fnum], [$texarea_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with texarea element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($texarea_element->tab_name . '___' . $texarea_element->element_name, $data[$fnum], 'the data contains the textarea element');
			$this->assertEquals('TEST TEXTAREA', $data[$fnum][$texarea_element->tab_name . '___' . $texarea_element->element_name], 'the fnum contains the field element');

		}

		$display_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'display') {
				$display_element = $element;
				break;
			}
		}
		if ($display_element) {
			$data = $this->model->getFnumArray2([$fnum], [$display_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with display element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($display_element->tab_name . '___' . $display_element->element_name, $data[$fnum], 'the data contains the display element');
		}

		$yesno_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'yesno') {
				$yesno_element = $element;
				break;
			}
		}
		if ($yesno_element) {
			$data = $this->model->getFnumArray2([$fnum], [$yesno_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with yesno element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($yesno_element->tab_name . '___' . $yesno_element->element_name, $data[$fnum], 'the data contains the yesno element');
			$this->assertContains($data[$fnum][$yesno_element->tab_name . '___' . $yesno_element->element_name], [JText::_('JNO'), JText::_('JYES')], 'the yesno element contains translation for 0 and 1, such as jyes and jno');
		}

		$date_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'date') {
				$date_element = $element;
				break;
			}
		}
		if ($date_element) {
			$data = $this->model->getFnumArray2([$fnum], [$date_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with date element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($date_element->tab_name . '___' . $date_element->element_name, $data[$fnum], 'the data contains the date element');
			$this->assertStringMatchesFormat('%d/%d/%d %d:%d:%d', $data[$fnum][$date_element->tab_name . '___' . $date_element->element_name], 'the date element contains a date in the correct format');
		}

		$birthday_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'birthday') {
				$birthday_element = $element;
				break;
			}
		}
		if ($birthday_element) {
			$data = $this->model->getFnumArray2([$fnum], [$birthday_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with birthday element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($birthday_element->tab_name . '___' . $birthday_element->element_name, $data[$fnum], 'the data contains the birthday element');
			$this->assertStringMatchesFormat('%d/%d/%d', $data[$fnum][$birthday_element->tab_name . '___' . $birthday_element->element_name], 'the date element contains a birthday in the correct format');
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
			$data = $this->model->getFnumArray2([$fnum], [$databasejoin_element]);
			$this->assertNotFalse($data, 'getFnumArray does not encounter an error');
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with databasejoin element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($databasejoin_element->tab_name . '___' . $databasejoin_element->element_name, $data[$fnum], 'the data contains the databasejoin element');
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
			$data = $this->model->getFnumArray2([$fnum], [$databasejoin_multi_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with databasejoin multi element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($databasejoin_multi_element->table_join . '___' . $databasejoin_multi_element->element_name, $data[$fnum], 'the data contains the databasejoin multi element');
			$this->assertStringContainsString('Charente-Maritime', $data[$fnum][$databasejoin_multi_element->table_join . '___' . $databasejoin_multi_element->element_name], 'the databasejoin multi element contains the correct value');
		}

		$radio_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'radiobutton') {
				$radio_element = $element;
				break;
			}
		}
		if ($radio_element) {
			$data = $this->model->getFnumArray2([$fnum], [$radio_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with radiobutton element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($radio_element->tab_name . '___' . $radio_element->element_name, $data[$fnum], 'the data contains the radiobutton element');
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
			$data = $this->model->getFnumArray2([$fnum], [$dropdown_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with dropdown element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($dropdown_element->tab_name . '___' . $dropdown_element->element_name, $data[$fnum], 'the data contains the dropdown element');
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
			$data = $this->model->getFnumArray2([$fnum], [$dropdown_multi_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with dropdown multiselect element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name, $data[$fnum], 'the data contains the dropdown multiselect element');
			$this->assertNotEmpty($data[$fnum][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'the data contains the dropdown multiselect element');
			$this->assertStringNotContainsString('[', $data[$fnum][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'dropdown multiselect element, open bracket has been removed');
			$this->assertStringNotContainsString(']', $data[$fnum][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'dropdown multiselect element, close bracket has been removed');
		}

		$cascadingdropdown_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'cascadingdropdown') {
				$cascadingdropdown_element = $element;
				break;
			}
		}
		if ($cascadingdropdown_element) {
			$data = $this->model->getFnumArray2([$fnum], [$cascadingdropdown_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with cascadingdropdown element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($cascadingdropdown_element->tab_name . '___' . $cascadingdropdown_element->element_name, $data[$fnum], 'the data contains the cascadingdropdown element');
		}

		$first_form_elements = [$birthday_element, $date_element, $yesno_element, $display_element, $texarea_element, $field_element, $databasejoin_element, $radio_element, $dropdown_element, $dropdown_multi_element, $databasejoin_multi_element, $cascadingdropdown_element];
		$data                = $this->model->getFnumArray2([$fnum], $first_form_elements);
		$this->assertNotEmpty($data, 'getFnumArray returns an not empty array of data with all elements');

		// TODO: create a form with all type of elements and where the group is repeatable
		/*$repeat_form_id = 381;
		$query->clear()
			->select('jfe.id')
			->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
			->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON jffg.group_id = jfe.group_id')
			->where('jffg.form_id = ' . $repeat_form_id)
			->andWhere('jfe.hidden = 0');

		$this->db->setQuery($query);
		$element_ids = $this->db->loadColumn();
		$element_ids = implode(',', $element_ids);
		$elements = $this->h_files->getElementsName($element_ids);
		$this->assertNotEmpty($elements, 'getElementsName returns an array of elements for a form with repeatable group');

		// TODO: create a fnum and writes data in it
		$fnum = '2023070411433500000020000095';


		$field_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'field') {
				$field_element = $element;
				break;
			}
		}
		if ($field_element) {
			$data = $this->model->getFnumArray2([$fnum], [$field_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with field element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($field_element->tab_name . '___' . $field_element->element_name, $data[$fnum], 'the data contains the field element');
			$this->assertNotEmpty($data[$fnum][$field_element->tab_name . '___' . $field_element->element_name], 'the data contains the field element and is not empty');
		}

		$texarea_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'textarea') {
				$texarea_element = $element;
				break;
			}
		}
		if ($texarea_element) {
			$data = $this->model->getFnumArray2([$fnum], [$texarea_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with texarea element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($texarea_element->tab_name . '___' . $texarea_element->element_name, $data[$fnum], 'the data contains the textarea element');
			$this->assertNotEmpty($data[$fnum][$texarea_element->tab_name . '___' . $texarea_element->element_name], 'the data contains the textarea element and is not empty');
		}

		$display_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'display') {
				$display_element = $element;
				break;
			}
		}
		if ($display_element) {
			$data = $this->model->getFnumArray2([$fnum], [$display_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with display element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($display_element->tab_name . '___' . $display_element->element_name, $data[$fnum], 'the data contains the display element');
		}

		$yesno_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'yesno') {
				$yesno_element = $element;
				break;
			}
		}
		if ($yesno_element) {
			$data = $this->model->getFnumArray2([$fnum], [$yesno_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with yesno element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($yesno_element->tab_name . '___' . $yesno_element->element_name, $data[$fnum], 'the data contains the yesno element');

			$values = explode(',', $data[$fnum][$yesno_element->tab_name . '___' . $yesno_element->element_name]);
			foreach ($values as $value) {
				$this->assertContains(trim($value), [JText::_('JNO'), JText::_('JYES')], 'the value is Yes or No translatation');
			}
		}

		$date_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin == 'date') {
				$date_element = $element;
				break;
			}
		}
		if ($date_element) {
			$data = $this->model->getFnumArray2([$fnum], [$date_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with date element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($date_element->tab_name . '___' . $date_element->element_name, $data[$fnum], 'the data contains the date element');

			$dates = explode(',', $data[$fnum][$date_element->tab_name . '___' . $date_element->element_name]);
			foreach ($dates as $date) {
				$this->assertStringMatchesFormat('%d/%d/%d %d:%d:%d', $date, 'the date element contains a date in the correct format');
			}
		}

		$birthday_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'birthday') {
				$birthday_element = $element;
				break;
			}
		}
		if ($birthday_element) {
			$data = $this->model->getFnumArray2([$fnum], [$birthday_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with birthday element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($birthday_element->tab_name . '___' . $birthday_element->element_name, $data[$fnum], 'the data contains the birthday element');

			$dates = explode(',', $data[$fnum][$birthday_element->tab_name . '___' . $birthday_element->element_name]);
			foreach ($dates as $date) {
				$this->assertStringMatchesFormat('%d/%d/%d', trim($date), 'the date element contains a birthday in the correct format');
			}
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
			$data = $this->model->getFnumArray2([$fnum], [$databasejoin_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with databasejoin element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($databasejoin_element->tab_name . '___' . $databasejoin_element->element_name, $data[$fnum], 'the data contains the databasejoin element');
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
			$data = $this->model->getFnumArray2([$fnum], [$databasejoin_multi_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with databasejoin multi element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($databasejoin_multi_element->tab_name . '___' . $databasejoin_multi_element->element_name, $data[$fnum], 'the data contains the databasejoin multi element');
		}

		$radio_element = null;
		foreach ($elements as $element) {
			if ($element->element_plugin === 'radiobutton') {
				$radio_element = $element;
				break;
			}
		}
		if ($radio_element) {
			$data = $this->model->getFnumArray2([$fnum], [$radio_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with radiobutton element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($radio_element->tab_name . '___' . $radio_element->element_name, $data[$fnum], 'the data contains the radiobutton element');
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
			$data = $this->model->getFnumArray2([$fnum], [$dropdown_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with dropdown element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($dropdown_element->tab_name . '___' . $dropdown_element->element_name, $data[$fnum], 'the data contains the dropdown element');
			$this->assertNotEmpty($data[$fnum][$dropdown_element->tab_name . '___' . $dropdown_element->element_name], 'dropdown in repeat context values returned');
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
			$data = $this->model->getFnumArray2([$fnum], [$dropdown_multi_element]);
			$this->assertNotEmpty($data, 'getFnumArray returns an array of data with dropdown multiselect element');
			$this->assertNotEmpty($data[$fnum], 'getFnumArray returns an array of data containing the fnum passed as parameter');
			$this->assertArrayHasKey($dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name, $data[$fnum], 'the data contains the dropdown multiselect element');
			$this->assertNotEmpty($data[$fnum][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'the data contains the dropdown multiselect element');
			$this->assertStringNotContainsString('[', $data[$fnum][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'dropdown multiselect element, open bracket has been removed');
			$this->assertStringNotContainsString(']', $data[$fnum][$dropdown_multi_element->tab_name . '___' . $dropdown_multi_element->element_name], 'dropdown multiselect element, close bracket has been removed');
		}

		$repeat_form_elements = [$field_element, $texarea_element, $display_element, $yesno_element, $date_element, $birthday_element, $databasejoin_element, $databasejoin_multi_element, $radio_element, $dropdown_element, $dropdown_multi_element];
		$data = $this->model->getFnumArray2([$fnum], $repeat_form_elements);
		$this->assertNotEmpty($data, 'getFnumArray returns a not empty array of data with all elements and repeatable group');*/

		// calculate time of execution
		//$elements_from_different_forms = array_merge($first_form_elements, $repeat_form_elements);
		$elements_from_different_forms = $first_form_elements;
		$start                         = microtime(true);
		$data                          = $this->model->getFnumArray2([$fnum], $elements_from_different_forms, true);
		$end                           = microtime(true);
		$this->assertNotEmpty($data, 'getFnumArray returns a not empty array of data with all elements from different forms');
		$elapsed_new_function_time = $end - $start;

		$start                     = microtime(true);
		$data                      = $this->model->getFnumArray([$fnum], $elements_from_different_forms);
		$end                       = microtime(true);
		$elapsed_old_function_time = $end - $start;

		$this->assertGreaterThanOrEqual($elapsed_new_function_time, $elapsed_old_function_time, 'getFnumArray2 is faster than getFnumArray ' . $elapsed_new_function_time . ' vs ' . $elapsed_old_function_time);
	}
}