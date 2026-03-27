<?php

namespace Unit\Component\Emundus\Class\Services\ApplicationFile;

use Joomla\CMS\Language\Text;
use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\ApplicationFile\ApplicationFileRepository;
use Tchooz\Repositories\Upload\UploadRepository;
use Tchooz\Services\ApplicationFile\ApplicationFileService;

class ApplicationFileServiceTest extends UnitTestCase
{
	private ApplicationFileService $service;

	public function setUp(): void
	{
		parent::setUp();
		$this->service = new ApplicationFileService();
	}

	/**
	 * @covers \Tchooz\Services\ApplicationFile\ApplicationFileService::updateOwner
	 * @return void
	 */
	public function testUpdateOwner(): void
	{
		$uploadRepository          = new UploadRepository();
		$applicationFileRepository = new ApplicationFileRepository();
		$applicationFile           = $applicationFileRepository->getByFnum($this->dataset['fnum']);

		// 1. Create application file with owner A
		$attachment_id = $this->h_dataset->createSampleAttachment();
		$this->h_dataset->createSampleUpload($this->dataset['fnum'], $this->dataset['campaign'], $this->dataset['applicant'], $attachment_id);
		$applicantDir = JPATH_ROOT . "/images/emundus/files/{$this->dataset['applicant']}";
		if (!is_dir($applicantDir))
		{
			mkdir($applicantDir, 0755, true);
		}
		file_put_contents("$applicantDir/sample_file.txt", "This is a sample file for applicant A.");
		$this->assertFileExists("$applicantDir/sample_file.txt");
		// Fill data
		$this->h_dataset->createSampleData($this->dataset['fnum'], $this->dataset['applicant']);

		// 2. Create user B
		$newOwnerEmail = 'applicant' . rand(0, 99999) . '@emundus.test.fr';
		$newOwnerId    = $this->h_dataset->createSampleUser(1000, $newOwnerEmail);
		$newOwnerDir   = JPATH_ROOT . "/images/emundus/files/$newOwnerId";

		// 3. Call updateOwner to change owner from A to B
		$updated = $this->service->updateOwner($applicationFile, $newOwnerId, $this->dataset['coordinator']);

		$this->assertTrue($updated);
		$this->assertEquals($applicationFile->getUser()->id, $newOwnerId, "Application file owner should be updated to new owner ID $newOwnerId.");
		$this->assertFileExists("$newOwnerDir/sample_file.txt", "This is a sample file for applicant A should be copied to new owner directory.");

		$newOwnerUploads = $uploadRepository->getByFnum($applicationFile->getFnum());
		foreach ($newOwnerUploads as $upload)
		{
			$this->assertEquals($upload->getUserId(), $newOwnerId, "Upload ID {$upload->getId()} should have its user_id updated to new owner ID $newOwnerId.");
		}

		$this->h_dataset->deleteSampleUser($this->dataset['applicant']);
		$fnumData = $this->h_dataset->getUnitTestData($this->dataset['fnum']);
		$this->assertNotEmpty($fnumData, "Application file data should still exist after original owner is deleted.");
		$this->assertSame($this->dataset['fnum'], $fnumData['fnum'], "Fnum should remain the same after owner update.");
		$this->assertSame($this->dataset['applicant'], $fnumData['user'], "Original applicant should still be associated with the application file data after owner update even if deleted.");

		// Tests exceptions
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_OWNER_DOES_NOT_EXIST'));
		$this->service->updateOwner($applicationFile, 0, $this->dataset['coordinator']);

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage(Text::_('COM_EMUNDUS_UPDATE_OWNER_ERROR_OWNER_SAME_AS_CURRENT'));
		$this->service->updateOwner($applicationFile, $applicationFile->getUser()->id, $this->dataset['coordinator']);
	}
}