<?php

namespace Unit\Component\Emundus\Class\Repositories\Upload;


use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Upload\UploadEntity;
use Tchooz\Repositories\Upload\UploadRepository;

class UploadRepositoryTest extends UnitTestCase
{
	private UploadRepository $repository;

	private int $sampleAttachmentId;

	public function setUp(): void
	{
		parent::setUp();
		$this->repository = new UploadRepository();
		$this->sampleAttachmentId = $this->h_dataset->createSampleAttachment();
	}

	/**
	 * @covers UploadRepository::flush
	 */
	public function testFlush(): void
	{
		$upload = new UploadEntity(
			0,
			$this->dataset['applicant'],
			$this->dataset['fnum'],
			$this->sampleAttachmentId,
			'pdf_test_file.pdf',
			'Test file description',
			'local_testfile.pdf'
		);

		$result = $this->repository->flush($upload);
		$this->assertTrue($result);
		$this->assertGreaterThan(0, $upload->getId(), 'Upload ID should be set after flush');
	}

	/**
	 * @covers UploadRepository::getByFnum
	 */
	public function testGetByFnum(): void
	{
		$upload = new UploadEntity(
			0,
			$this->dataset['applicant'],
			$this->dataset['fnum'],
			$this->sampleAttachmentId,
			'pdf_test_file.pdf',
			'Test file description',
			'local_testfile.pdf'
		);

		$this->repository->flush($upload);

		$uploads = $this->repository->getByFnum($this->dataset['fnum']);
		$this->assertNotEmpty($uploads, 'Uploads should be retrieved by fnum');
		$this->assertInstanceOf(UploadEntity::class, $uploads[0], 'Retrieved item should be an instance of UploadEntity');
		$this->assertEquals(1, count($uploads), 'There should be exactly one upload for the fnum');
		$this->assertEquals('pdf_test_file.pdf', $uploads[0]->getFilename(), 'Original filename should match');
	}

	/**
	 * @covers UploadRepository::getById
	 */
	public function testGetById(): void
	{
		$upload = new UploadEntity(
			0,
			$this->dataset['applicant'],
			$this->dataset['fnum'],
			$this->sampleAttachmentId,
			'pdf_test_file.pdf',
			'Test file description',
			'local_testfile.pdf'
		);

		$this->repository->flush($upload);
		$this->assertGreaterThanOrEqual(0, $upload->getId(), 'Upload ID should be set after flush');
		$retrievedUpload = $this->repository->getById($upload->getId());

		$this->assertNotNull($retrievedUpload, 'Upload should be retrieved by ID');
		$this->assertInstanceOf(UploadEntity::class, $retrievedUpload, 'Retrieved item should be an instance of UploadEntity');
		$this->assertEquals($upload->getId(), $retrievedUpload->getId(), 'Upload ID should match');
		$this->assertEquals('pdf_test_file.pdf', $retrievedUpload->getFilename(), 'Original filename should match');
		$this->assertEquals($upload->getDescription(), $retrievedUpload->getDescription(), 'Description should match');
	}
}
