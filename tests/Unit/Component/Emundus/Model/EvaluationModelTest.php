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
		$program               = $this->h_dataset->createSampleProgram();

		$campaign  = $this->h_dataset->createSampleCampaign($program);
		$letter_id = $this->h_dataset->createSampleLetter($letter_attachement_id, 2, [$program['programme_code']], [0], [$campaign]);

		$user = $this->h_dataset->createSampleUser(9, 'user.test' . rand(0, 1000) . '@emundus.fr');
		$fnum = $this->h_dataset->createSampleFile($campaign, $user);

		$letters = $this->model->getLettersByProgrammesStatusCampaigns([$program['programme_code']], [0], [$campaign]);
		$this->assertNotEmpty($letters, 'I should retrieve letters by programme status and campaign');

		$letter_ids       = array_column($letters, 'id');
		$letter_id_string = (string) $letter_id;
		$this->assertContains($letter_id_string, $letter_ids, 'I should retrieve the created letter id in the list of letters');

	}

	public function testgetLetterTemplateForFnum()
	{
		$letters = $this->model->getLetterTemplateForFnum('');
		$this->assertIsArray($letters, 'getLetterTemplateForFnum should return an array');
		$this->assertEmpty($letters, 'Without parameters, getLetterTemplateForFnum should return an empty array');

		$letter_attachement_id = $this->h_dataset->createSampleAttachment();
		$program               = $this->h_dataset->createSampleProgram();

		$campaign  = $this->h_dataset->createSampleCampaign($program);
		$letter_id = $this->h_dataset->createSampleLetter($letter_attachement_id, 2, [$program['programme_code']], [0], [$campaign]);

		$user = $this->h_dataset->createSampleUser(9, 'user.test' . rand(0, 1000) . '@emundus.fr');
		$fnum = $this->h_dataset->createSampleFile($campaign, $user);

		$letters = $this->model->getLetterTemplateForFnum($fnum, [$letter_attachement_id]);
		$this->assertNotEmpty($letters, 'I should retrieve letters by fnum and letter attachement id');
	}
}