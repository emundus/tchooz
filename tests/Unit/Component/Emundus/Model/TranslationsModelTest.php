<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use Joomla\CMS\Factory;
use Joomla\Tests\Unit\UnitTestCase;

class TranslationsModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '', $className = null)
	{
		parent::__construct('translations', $data, $dataName, 'EmundusModelTranslations');
	}

	public function testInsertTranslation()
	{
		// Test insert translation with empty key return false
		$inserted = $this->model->insertTranslation('', 'Test élément avec clé vide', 'fr-FR', '', 'override', 'fabrik_elements', 999999);
		$this->assertFalse($inserted);

		// Make sure $^*()=+\[<?; are not allowed in tag
		$inserted = $this->model->insertTranslation('E[L<$EN^()T_TE\T', 'Test élément avec clé vide', 'fr-FR', '', 'override', 'fabrik_elements', 999999);
		$this->assertFalse($inserted);

		if (empty($this->model->getTranslations('override', 'fr-FR', '', '', 'fabrik_elements', 0, '', 'ELEMENT_TEST'))) {
			// TEST 1 - Insert a basic translation of a fabrik_element
			$this->assertSame(true, $this->model->insertTranslation('ELEMENT_TEST', 'Mon élément de test', 'fr-FR', '', 'override', 'fabrik_elements', 9999, 'label'));
		}
		else {
			// TEST 2 - Failed waiting - Insert a basic translation of a fabrik_element
			$this->assertSame(false, $this->model->insertTranslation('ELEMENT_TEST', 'Mon élément de test', 'fr-FR', '', 'override', 'fabrik_elements', 9999, 'label'));
		}

		if (empty($this->model->getTranslations('override', 'en-GB', '', '', 'fabrik_elements', 0, '', 'ELEMENT_TEST'))) {
			// TEST 1 - Insert a basic translation of a fabrik_element in english file
			$this->assertSame(true, $this->model->insertTranslation('ELEMENT_TEST', 'My element', 'en-GB', '', 'override', 'fabrik_elements', 9999, 'label'));
		}
		else {
			// TEST 2 - Failed waiting - Insert a basic translation of a fabrik_element in english file
			$this->assertSame(false, $this->model->insertTranslation('ELEMENT_TEST', 'My element', 'en-GB', '', 'override', 'fabrik_elements', 9999, 'label'));
		}
	}

	public function testGetTranslations()
	{
		// TEST 1 - GET ALL FABRIK TRANSLATIONS BY DEFAULT
		$this->assertNotEmpty($this->model->getTranslations());

		// TEST 2 - GET TYPE NOT EXISTING, EMPTY ARRAY HAS TO BE RETURNED
		$this->assertEmpty($this->model->getTranslations('mon_type'));

		// TEST 3 - PASS TYPE NOT STRING, EMPTY ARRAY HAS TO BE RETURNED
		$this->assertEmpty($this->model->getTranslations(1));

		// TEST 4 - GET FABRIK TRANSLATIONS IN FRENCH
		$this->assertNotEmpty($this->model->getTranslations('override', 'fr-FR'));

		// TEST 5 - GET FABRIK TRANSLATIONS IN ENGLISH
		$this->assertNotEmpty($this->model->getTranslations('override', 'en-GB'));

		// TEST 6 - GET FABRIK TRANSLATIONS IN LANGUAGE NOT EXISTING
		$this->assertEmpty($this->model->getTranslations('override', 'pt-PT'));

		// TEST 7 - GET FABRIK OPTIONS of the element 7777
		$this->assertNotEmpty($this->model->getTranslations('override', '*', '', '', '', 9999));

		// TEST 8 - GET FABRIK ELEMENTS on lang fr-FR
		$this->assertNotEmpty($this->model->getTranslations('override', 'fr-FR', '', '', 'fabrik_elements'));

		// TEST 9 - GET FABRIK ELEMENTS on lang en-GB
		$this->assertNotEmpty($this->model->getTranslations('override', 'en-GB', '', '', 'fabrik_elements'));

		// TEST 10 - GET TRANSLATIONS WITH SEARCH
		$this->assertNotEmpty($this->model->getTranslations('override', '*', 'Mon élément'));
	}

	public function testUpdateTranslations()
	{
		$override_original_file_size = filesize(JPATH_SITE . '/language/overrides/fr-FR.override.ini');


		// TEST 1 - Update the translations created before in french
		$this->assertSame('ELEMENT_TEST', $this->model->updateTranslation('ELEMENT_TEST', 'Mon élement modifié', 'fr-FR'));

		// TEST 2 - Update the translations created before in english
		$this->assertSame('ELEMENT_TEST', $this->model->updateTranslation('ELEMENT_TEST', 'My updated element', 'en-GB'));

		// TEST 3 - Failed waiting - Update the translations created before in portuguesh
		$this->assertSame(false, $this->model->updateTranslation('ELEMENT_TEST', 'My updated element', 'pt-PT'));

		// TEST 4 - If no tag given, traduction should return false, request sould not work
		$this->assertSame(false, $this->model->updateTranslation('', 'My updated element', 'fr-FR'), 'Make sure that we can\'t add empty tag into override file');

		$override_new_file_size = filesize(JPATH_SITE . '/language/overrides/fr-FR.override.ini');
		$this->assertGreaterThanOrEqual($override_original_file_size, $override_new_file_size, 'New override file size is greater or equal than original override file (make sure override file is not destroyed)');

		// TEST 6 - Succes waiting - Update translations of com_emundus not possible so we insert it in override file
		//$this->assertSame(true,$this->model->updateTranslation('COM_EMUNDUS_EMAIL','Un nouvel email','fr-FR','component'));
	}

	public function testDeleteTranslations()
	{
		// TEST 1 - Delete translation that we manage in other tests
		$this->assertSame(true, $this->model->deleteTranslation('ELEMENT_TEST'));
	}

	public function testgetTranslationsObject()
	{
		$this->assertIsArray($this->model->getTranslationsObject());
	}

	public function testgetDefaultLanguage()
	{
		$this->assertIsObject($this->model->getDefaultLanguage());
	}

	public function testgetAllLanguages()
	{
		$this->assertIsArray($this->model->getAllLanguages());
	}

	public function testgetOrphelins()
	{
		$this->assertIsArray($this->model->getOrphelins('fr-FR', 'en-GB'));
	}

	public function testCheckSetup()
	{
		$this->assertNotNull($this->model->checkSetup());
	}

	public function testGetPlatformLanguages()
	{
		$this->assertNotEmpty($this->model->getPlatformLanguages());
	}

	public function testCheckTagIsCorrect()
	{
		$this->assertFalse($this->model->checkTagIsCorrect('', 'Ma traduction', 'insert', 'fr'));
		$this->assertFalse($this->model->checkTagIsCorrect('E[L<$EN^()T_TE\T', 'Ma traduction', 'insert', 'fr'));
		$this->assertTrue($this->model->checkTagIsCorrect('MON_ELEMENT', 'Ma traduction', 'insert', 'fr'));
	}

	public function testgetTranslationsFalang()
	{
		$reference_id = 9999;
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->getQuery(true);
		$query->insert('#__emundus_setup_status')
			->columns('`step`, `value`')
			->values($reference_id . ', "Translation Status"');

		$db->setQuery($query);
		$db->execute();

		$translations = $this->model->getTranslationsFalang('fr-FR', 'en-GB', $reference_id, 'value', 'emundus_setup_status');
		error_log(print_r($translations, true));
		$this->assertNotEmpty($translations, 'Falang translations should not be empty');

		// cleanup
		$query = $db->getQuery(true);
		$query->delete('#__emundus_setup_status')
			->where('`step` = ' . $reference_id);

		$db->setQuery($query);
		$db->execute();
	}
}