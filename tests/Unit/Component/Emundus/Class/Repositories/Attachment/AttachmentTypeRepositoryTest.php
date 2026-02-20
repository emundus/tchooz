<?php

namespace Unit\Component\Emundus\Class\Repositories\Attachment;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\Attachments\AttachmentTypeRepository;

class AttachmentTypeRepositoryTest extends UnitTestCase
{
	private AttachmentTypeRepository $repository;

	public function setUp(): void
	{
		parent::setUp();

		$this->repository = new AttachmentTypeRepository();
	}

	/**
	 * @covers AttachmentTypeRepository::get
	 * @return void
	 */
	public function testGet()
	{
		$attachmentTypes = $this->repository->get();
		$this->assertIsArray($attachmentTypes, 'Expected get() to return an array');
		// by default, limit is 10
		$this->assertLessThanOrEqual(10, count($attachmentTypes), 'Expected get() to return at most 10 items');
		// assert that each item is an object AttachmentType
		foreach ($attachmentTypes as $attachmentType) {
			$this->assertInstanceOf(\Tchooz\Entities\Attachments\AttachmentType::class, $attachmentType, 'Expected each item to be an instance of AttachmentType');
		}
	}
}