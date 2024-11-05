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
use Joomla\Tests\Unit\UnitTestCase;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelForm
 */
class FormModelTest extends UnitTestCase
{
	/**
	 * @var    EmundusModelForm
	 * @since  4.2.0
	 */
	protected $model;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('form', $data, $dataName, 'EmundusModelForm');
	}

	/**
	 * @test
	 * @covers EmundusModelForm::copyAttachmentsToNewProfile()
	 */
	public function testCopyAttachmentsToNewProfile()
	{
		$base_profile     = 9;
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
	 * @covers EmundusModelForm::duplicateForm()
	 */
	public function testDuplicateForm()
	{
		$pids      = [0];
		$duplicate = $this->model->duplicateForm($pids);
		$this->assertFalse($duplicate, 'Duplicate form requires a valid profile id');

		// TODO: test duplicate form, error coming from cms language
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
		$this->assertContains('time_date', $element_names, 'Evaluation form elements contains time_date');
		$this->assertContains('fnum', $element_names, 'Evaluation form elements contains fnum');
		$this->assertContains('user', $element_names, 'Evaluation form elements contains user');
		$this->assertContains('student_id', $element_names, 'Evaluation form elements contains student_id');

		foreach ($elements as $element) {
			if (in_array($element['name'], ['id', 'time_date', 'fnum', 'user', 'student_id'])) {
				$this->assertSame(1, intval($element['published']), 'Evaluation default form elements are published');
			}
		}
	}
}