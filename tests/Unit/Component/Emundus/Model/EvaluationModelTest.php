<?php
/**
 * @package     Unit\Component\Emundus\Model
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Model;

use Joomla\Tests\Unit\UnitTestCase;

class EvaluationModelTest extends UnitTestCase
{

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('evaluation', $data, $dataName, 'EmundusModelEvaluation');
	}

	public function testgetLettersByProgrammesStatusCampaigns()
	{
		$letters = $this->model->getLettersByProgrammesStatusCampaigns();
		$this->assertIsArray($letters, 'getLettersByProgrammesStatusCampaigns should return an array');
		$this->assertEmpty($letters, 'Without parameters, getLettersByProgrammesStatusCampaigns should return an empty array');

		$letter_attachement_id = $this->h_dataset->createSampleAttachment();
		$letter_id = $this->h_dataset->createSampleLetter($letter_attachement_id, 2, [$this->dataset['program']['programme_code']], [0], [$this->dataset['campaign']]);

		$letters = $this->model->getLettersByProgrammesStatusCampaigns([$this->dataset['program']['programme_code']], [0], [$this->dataset['campaign']]);
		$this->assertNotEmpty($letters, 'I should retrieve letters by programme status and campaign');

		$letter_ids       = array_column($letters, 'id');
		$this->assertContains($letter_id, $letter_ids, 'I should retrieve the created letter id in the list of letters');

		$this->h_dataset->deleteSampleAttachment($letter_attachement_id);
		$this->h_dataset->deleteSampleLetter($letter_id);
	}

	public function testgetLetterTemplateForFnum()
	{
		$letters = $this->model->getLetterTemplateForFnum('');
		$this->assertIsArray($letters, 'getLetterTemplateForFnum should return an array');
		$this->assertEmpty($letters, 'Without parameters, getLetterTemplateForFnum should return an empty array');

		$letter_attachement_id = $this->h_dataset->createSampleAttachment();
		$letter_id = $this->h_dataset->createSampleLetter($letter_attachement_id, 2, [$this->dataset['program']['programme_code']], [0], [$this->dataset['campaign']]);

		$letters = $this->model->getLetterTemplateForFnum($this->dataset['fnum'], [$letter_attachement_id]);
		$this->assertNotEmpty($letters, 'I should retrieve letters by fnum and letter attachement id');

		// Clear datasets
		$this->h_dataset->deleteSampleAttachment($letter_attachement_id);
		$this->h_dataset->deleteSampleLetter($letter_id);
	}
}