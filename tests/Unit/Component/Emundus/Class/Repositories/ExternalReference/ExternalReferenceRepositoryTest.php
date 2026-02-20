<?php

namespace Unit\Component\Emundus\Class\Repositories\ExternalReference;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Repositories\ExternalReference\ExternalReferenceRepository;
use Tchooz\Entities\ExternalReference\ExternalReferenceEntity;

class ExternalReferenceRepositoryTest extends UnitTestCase
{
	private ExternalReferenceRepository $repository;

	public function setUp(): void
	{
		parent::setUp();


		$this->repository = new ExternalReferenceRepository();
	}

	/**
	 * @covers Tchooz\Repositories\ExternalReference\ExternalReferenceRepository::flush
	 * @return void
	 */
	public function testFlush(): void
	{
		$reference = new ExternalReferenceEntity(0, 'jos_emundus_payment_transaction.id', 1, 'external-uuid-1234');

		$flushed = $this->repository->flush($reference);
		$this->assertTrue($flushed, 'The flush method should return true on success.');
		$this->assertGreaterThan(0, $reference->getId(), 'The reference ID should be greater than 0 after flush.');

		$this->repository->delete($reference->getId());
	}

	/**
	 * @covers Tchooz\Repositories\ExternalReference\ExternalReferenceRepository::getById
	 * @return void
	 */
	public function testGetById(): void
	{
		$reference = new ExternalReferenceEntity(0, 'jos_emundus_payment_transaction.id', 99999, 'external-uuid-9999');

		$flushed = $this->repository->flush($reference);
		$this->assertGreaterThan(0, $reference->getId());

		$fetchedReference = $this->repository->getById($reference->getId());
		$this->assertInstanceOf(ExternalReferenceEntity::class, $fetchedReference);
		$this->assertEquals($reference->getId(), $fetchedReference->getId());
		$this->assertEquals($reference->getColumn(), $fetchedReference->getColumn());
		$this->assertEquals($reference->getInternId(), $fetchedReference->getInternId());
		$this->assertEquals($reference->getReference(), $fetchedReference->getReference());

		$this->repository->delete($reference->getId());
	}

	/**
	 * @covers Tchooz\Repositories\ExternalReference\ExternalReferenceRepository::get
	 * @return void
	 */
	public function testGet(): void
	{
		$reference = new ExternalReferenceEntity(0, 'jos_emundus_payment_transaction.id', 99998, 'external-uuid-99998');

		$flushed = $this->repository->flush($reference);
		$this->assertTrue($flushed);

		$references = $this->repository->get();
		$this->assertIsArray($references);
		$this->assertNotEmpty($references);
		$this->assertInstanceOf(ExternalReferenceEntity::class, $references[0]);

		$this->repository->delete($reference->getId());
	}
}