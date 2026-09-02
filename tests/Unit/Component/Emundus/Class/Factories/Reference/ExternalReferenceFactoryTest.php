<?php

namespace Unit\Component\Emundus\Class\Factories\Reference;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Factories\Reference\ExternalReferenceFactory;

/**
 * @package     Unit\Component\Emundus\Class\Factories\Reference
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Reference\ExternalReferenceFactory
 */
class ExternalReferenceFactoryTest extends UnitTestCase
{
	/**
	 * The synchronizer columns tell a third party reference apart from the one eMundus owns, they must survive
	 * the hydration.
	 *
	 * @covers \Tchooz\Factories\Reference\ExternalReferenceFactory::fromDbObjects
	 * @return void
	 */
	public function testFromDbObjectsKeepsTheSynchronizerColumns(): void
	{
		$references = ExternalReferenceFactory::fromDbObjects([
			(object) [
				'id'                  => 1,
				'column'              => 'jos_emundus_payment_transaction.id',
				'intern_id'           => 12,
				'reference'           => 'ABC123',
				'sync_id'             => null,
				'reference_object'    => null,
				'reference_attribute' => null,
			],
			(object) [
				'id'                  => 2,
				'column'              => 'jos_emundus_payment_transaction.id',
				'intern_id'           => 12,
				'reference'           => 'hubspot-deal-1234',
				'sync_id'             => 42,
				'reference_object'    => 'deals',
				'reference_attribute' => 'hs_object_id',
			],
		]);

		$this->assertCount(2, $references);
		$this->assertInstanceOf(ExternalReferenceEntity::class, $references[0]);

		$this->assertNull($references[0]->getSynchronizerId(), 'The eMundus owned reference has no synchronizer');
		$this->assertNull($references[0]->getReferenceObject());
		$this->assertNull($references[0]->getReferenceAttribute());

		$this->assertEquals(42, $references[1]->getSynchronizerId());
		$this->assertEquals('deals', $references[1]->getReferenceObject());
		$this->assertEquals('hs_object_id', $references[1]->getReferenceAttribute());
	}

	/**
	 * @covers \Tchooz\Factories\Reference\ExternalReferenceFactory::fromDbObjects
	 * @return void
	 */
	public function testFromDbObjectsToleratesRowsWithoutTheSynchronizerColumns(): void
	{
		$references = ExternalReferenceFactory::fromDbObjects([
			(object) [
				'id'        => 1,
				'column'    => 'jos_emundus_payment_transaction.id',
				'intern_id' => 12,
				'reference' => 'ABC123',
			],
		]);

		$this->assertCount(1, $references);
		$this->assertEquals('ABC123', $references[0]->getReference());
		$this->assertNull($references[0]->getSynchronizerId());
	}
}
