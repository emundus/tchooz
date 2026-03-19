<?php

namespace Unit\Component\Emundus\Class\Entities\Automation\Actions;

use Joomla\CMS\Factory;
use Joomla\CMS\User\User;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Automation\Actions\ActionPrintApplication;
use Tchooz\Entities\Automation\ActionTargetEntity;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Joomla\Registry\Registry;

class ActionPrintApplicationTest extends UnitTestCase
{
	private User $coordinator;

	private Registry $config;

	public function setUp(): void
	{
		parent::setUp();
		$this->coordinator = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$campaignRepository = new CampaignRepository();
		$campaign     = $campaignRepository->getById($this->dataset['campaign']);
		$campaign->setProfileId(1001);
		$campaignRepository->flush($campaign);

		$this->config = Factory::getApplication()->getConfig();
		$this->config->set('site_uri', 'https://example.com');
		$this->config->set('live_site', 'https://example.com');
	}

	/**
	 * @covers \Tchooz\Entities\Automation\Actions\ActionPrintApplication::execute
	 * @covers \EmundusModelFiles::generatePDF
	 * @covers \EmundusModelApplication::getFormsPDF
	 * @covers \EmundusHelperExport::buildFormPDF
	 *
	 * @return void
	 */
	public function testExecute(): void
	{
		$targetEntity = new ActionTargetEntity($this->coordinator, $this->dataset['fnum'], (int) $this->dataset['applicant']);
		$action       = new ActionPrintApplication();

		$result = $action->execute($targetEntity);
		$this->assertEquals(\Tchooz\Enums\Automation\ActionExecutionStatusEnum::COMPLETED, $result, 'The print application action should complete successfully');

		// Verify that the PDF file was generated
		$uploadRepository = new UploadRepository();
		$uploads = $uploadRepository->get(['fnum' => $this->dataset['fnum'], 'attachment_id' => ActionPrintApplication::ATTACHMENT_ID]);
		$this->assertNotEmpty($uploads);
		$pdfUpload = $uploads[0];
		assert($pdfUpload instanceof \Tchooz\Entities\Upload\UploadEntity);
		$this->assertFileExists($pdfUpload->getFileInternalPath(), 'The generated PDF file should exist');
	}
}