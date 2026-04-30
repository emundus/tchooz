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
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Registry\Registry;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\Profile\ProfileRepository;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelForm
 */
class FormModelTest extends UnitTestCase
{
	private Registry $config;

	const PROFILE_ID = 1001;

	/**
	 * @var    \EmundusModelForm
	 * @since  4.2.0
	 */
	protected $model;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('form', $data, $dataName, 'EmundusModelForm');
	}

	protected function setUp(): void
	{
		parent::setUp();

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('live_site', 'https://example.com');
	}


	/**
	 * @test
	 * @covers EmundusModelForm::copyAttachmentsToNewProfile()
	 */
	public function testCopyAttachmentsToNewProfile()
	{
		$base_profile     = self::PROFILE_ID;
		$fake_new_profile = 64567657;

		$copy = $this->model->copyAttachmentsToNewProfile(0, $fake_new_profile);
		$this->assertFalse($copy, 'Copy attachments requires a valid old profile id');

		$copy = $this->model->copyAttachmentsToNewProfile($base_profile, 0);
		$this->assertFalse($copy, 'Copy attachments requires a valid new profile id');

		$copy = $this->model->copyAttachmentsToNewProfile($base_profile, $fake_new_profile);
		$this->assertFalse($copy, 'Copy attachments fails because new profile does not exist');

		$fake_new_profile = $this->h_dataset->duplicateSampleProfile($base_profile);
		$this->assertNotEmpty($fake_new_profile, 'Fake new profile created');
		$this->assertNotSame($fake_new_profile, 64567657, 'Fake new profile is not the same as the fake id');
		$copy = $this->model->copyAttachmentsToNewProfile($base_profile, $fake_new_profile);
		$this->assertTrue($copy, 'Copy attachments succeeds');
	}

	/**
	 * @test
	 * @covers EmundusModelForm::duplicateForm
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateForms
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateForm
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushForm
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateList
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushList
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateGroups
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateGroup
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushGroup
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateElement
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::flushElement
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::updateFabrikLabel
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::updateCalculationParametersAfterDuplicate
	 * @covers \Tchooz\Repositories\Fabrik\FabrikRepository::duplicateConditions
	 */
	public function testDuplicateForm()
	{
		$coord = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);

		$pids      = [0];
		$duplicate = $this->model->duplicateForm($pids, $coord);
		$this->assertEmpty($duplicate, 'Duplicate form requires a valid profile id');

		// Get number of forms for profile 1001, we need to have the same count after duplication
		$profileRepository = new ProfileRepository();
		$oldProfile = $profileRepository->getById(self::PROFILE_ID);
		$formsid_arr = [];
		$query = $this->db->getQuery(true);
		$query->select('link')
			->from('#__menu')
			->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($oldProfile->getMenutype()))
			->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
			->andWhere('published = 1')
			->order('lft');
		$this->db->setQuery($query);
		$links = $this->db->loadObjectList();
		foreach ($links as $link) {
			if (str_contains($link->link, 'formid')) {
				$formsid_arr[] = explode('=', $link->link)[3];
			}
		}

		$duplicatedProfile = $this->model->duplicateForm(self::PROFILE_ID, $coord);
		$this->assertIsInt($duplicate, 'The method will return a valid profile id');
		$this->assertGreaterThan(0, $duplicatedProfile, 'The method will return a positive profile id');
		$this->assertNotEquals(self::PROFILE_ID, $duplicatedProfile);

		$profileRepository = new ProfileRepository();
		$newformsid_arr = [];
		$newProfile = $profileRepository->getById($duplicatedProfile);
		$query->clear()
			->select('link')
			->from('#__menu')
			->where($this->db->quoteName('menutype') . ' = ' . $this->db->quote($newProfile->getMenutype()))
			->andWhere($this->db->quoteName('type') . ' = ' . $this->db->quote('component'))
			->andWhere('published = 1')
			->order('lft');
		$this->db->setQuery($query);
		$links = $this->db->loadObjectList();
		foreach ($links as $link) {
			if (str_contains($link->link, 'formid')) {
				$newformsid_arr[] = explode('=', $link->link)[3];
			}
		}

		$this->assertEquals(sizeof($formsid_arr), sizeof($newformsid_arr), 'The number of forms is the same after duplication');
		$this->assertNotEquals($formsid_arr, $newformsid_arr, 'The forms are different after duplication');
	}

	/**
	 * @test
	 * @covers EmundusModelForm::createFormEval()
	 */
	public function testcreateFormEval()
	{
		$form_id = $this->model->createFormEval(Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']));
		$this->assertNotEmpty($form_id, 'Evaluation form creation succeeds');

		$query = $this->db->getQuery(true);

		$query->select('jfe.name, jfe.published')
			->from($this->db->quoteName('#__fabrik_elements', 'jfe'))
			->leftJoin($this->db->quoteName('#__fabrik_formgroup', 'jffg') . ' ON (' . $this->db->quoteName('jffg.group_id') . ' = ' . $this->db->quoteName('jfe.group_id') . ')')
			->where($this->db->quoteName('jffg.form_id') . ' = ' . $this->db->quote($form_id));

		$this->db->setQuery($query);
		$elements = $this->db->loadAssocList();

		$this->assertNotEmpty($elements, 'Evaluation form elements are not empty');

		$element_names = array_column($elements, 'name');
		$this->assertContains('id', $element_names, 'Evaluation form elements contains id');
		$this->assertContains('ccid', $element_names, 'Evaluation form elements contains ccid');
		$this->assertContains('step_id', $element_names, 'Evaluation form elements contains step');
		$this->assertContains('evaluator', $element_names, 'Evaluation form elements contains evaluator');

		foreach ($elements as $element) {
			if (in_array($element['name'], ['ccid', 'step_id', 'evaluator'])) {
				$this->assertSame(1, intval($element['published']), 'Evaluation default form elements are published');
			}
		}
	}
}