<?php

namespace Unit\Component\Emundus\Class\Services\NumericSign;

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\NumericSign\Request;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;
use Tchooz\Repositories\Campaigns\CampaignRepository;
use Tchooz\Repositories\NumericSign\RequestRepository;
use Tchooz\Repositories\NumericSign\RequestSignersRepository;
use Tchooz\Repositories\NumericSign\YousignRequestsRepository;
use Tchooz\Services\NumericSign\YousignService;
use Tchooz\Synchronizers\NumericSign\YousignSynchronizer;

class YousignServiceTest extends UnitTestCase
{
	private \EmundusModelFiles $m_files;

	private YousignService $service;

	private Request $request;

	private array $application_file;

	public function setUp(): void
	{
		parent::setUp();

		$request_repository         = new RequestRepository($this->db);
		$request_signers_repository = new RequestSignersRepository($this->db);
		$yousign_repository         = new YousignRequestsRepository($this->db);
		$yousign_synchronizer       = new YousignSynchronizer();
		if (!class_exists('EmundusModelFiles'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/files.php';
		}
		$this->m_files = new \EmundusModelFiles();
		if (!class_exists('EmundusModelApplication'))
		{
			require_once JPATH_SITE . '/components/com_emundus/models/application.php';
		}
		$m_application = new \EmundusModelApplication();

		$this->service = new YousignService(
			$yousign_synchronizer,
			$yousign_repository,
			$request_repository,
			$request_signers_repository,
			$this->m_files,
			$m_application,
			Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']),
		);

		$this->application_file = $this->m_files->getFnumInfos($this->dataset['fnum']);

		$attachmentRepository  = new AttachmentTypeRepository();
		$attachment_id         = $this->h_dataset->createSampleAttachment();
		$attachment_type = $attachmentRepository->loadAttachmentTypeById($attachment_id);

		$upload_id = $this->h_dataset->createSampleUpload($this->dataset['fnum'], $this->dataset['campaign'], $this->dataset['applicant'], $attachment_id);

		$requestRepository = new RequestRepository($this->db);
		$this->request     = new Request($this->dataset['coordinator']);
		$this->request->setFnum($this->dataset['fnum']);
		$this->request->setAttachment($attachment_type);
		$this->request->setCcid($this->application_file['ccid']);
		$this->request->setUserId($this->dataset['coordinator']);
		$this->request->setUploadId($upload_id);
		$requestRepository->flush($this->request);
	}

	/**
	 * @covers \Tchooz\Services\NumericSign\YousignService::flushYousignRequest
	 * @return void
	 */
	public function testFlushYousignRequest()
	{
		$user            = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->dataset['coordinator']);
		$applicant_user = Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($this->application_file['applicant_id']);

		$expiration_date = (new \DateTime())->modify('+7 days')->format('Y-m-d H:i:s');
		$request_name    = 'Test Yousign Request';

		// Test a simple yousign request
		$yousign_request = $this->service->flushYousignRequest($this->application_file, $this->request, $user);
		$this->assertNotEmpty($yousign_request->getId());
		//

		// Test with expiration date
		$yousign_request = $this->service->flushYousignRequest($this->application_file, $this->request, $user, $expiration_date);
		$this->assertNotEmpty($yousign_request->getId());
		//

		/*// Test with a simple request name
		$yousign_request = $this->service->flushYousignRequest($this->application_file, $this->request, $user, '', $request_name);
		$this->assertNotEmpty($yousign_request->getId());
		$this->assertEquals($request_name, $yousign_request->getName());
		//

		// Test with request name with tags
		$request_name = '[CAMPAIGN_LABEL] - [APPLICANT_NAME]';
		$yousign_request = $this->service->flushYousignRequest($this->application_file, $this->request, $user, '', $request_name);
		$this->assertNotEmpty($yousign_request->getId());

		$campaignRepository = new CampaignRepository();
		$campaign = $campaignRepository->getById($this->dataset['campaign']);

		$expected_name = str_replace(
			['[CAMPAIGN_LABEL]', '[APPLICANT_NAME]'],
			[$campaign->getLabel(), $applicant_user->name],
			$request_name
		);
		$this->assertEquals($expected_name, $yousign_request->getName());
		//*/
	}
}