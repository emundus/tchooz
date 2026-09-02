<?php

namespace Unit\Component\Emundus\Class\Entities\Payment;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Entities\Reference\ExternalReferenceEntity;

/**
 * @package     Unit\Component\Emundus\Class\Entities\Payment
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Entities\Payment\TransactionEntity
 */
class TransactionEntityTest extends UnitTestCase
{
	/**
	 * @covers \Tchooz\Entities\Payment\TransactionEntity::setExternalReferences
	 * @covers \Tchooz\Entities\Payment\TransactionEntity::getExternalReferences
	 * @return void
	 */
	public function testExternalReferencesAreKeptApartFromTheMainOne(): void
	{
		$transaction = new TransactionEntity(1);
		$this->assertSame([], $transaction->getExternalReferences(), 'A transaction starts without any reference');

		$transaction->setExternalReference('ABC123');

		$main       = new ExternalReferenceEntity(1, 'jos_emundus_payment_transaction.id', '1', 'ABC123');
		$hubspot    = new ExternalReferenceEntity(2, 'jos_emundus_payment_transaction.id', '1', 'hubspot-deal-1234', 42, 'deals', 'hs_object_id');
		$transaction->setExternalReferences([$main, $hubspot]);

		$this->assertEquals('ABC123', $transaction->getExternalReference(), 'The main reference stays a single reference');
		$this->assertCount(2, $transaction->getExternalReferences());
		$this->assertSame($main, $transaction->getExternalReferences()[0]);
		$this->assertSame($hubspot, $transaction->getExternalReferences()[1]);
	}

	/**
	 * @covers \Tchooz\Entities\Payment\TransactionEntity::setExternalReferences
	 * @return void
	 */
	public function testSetExternalReferencesReindexesTheGivenArray(): void
	{
		$transaction = new TransactionEntity(1);
		$reference   = new ExternalReferenceEntity(1, 'jos_emundus_payment_transaction.id', '1', 'ABC123');

		$transaction->setExternalReferences([7 => $reference]);

		$this->assertSame([0], array_keys($transaction->getExternalReferences()));
	}

	/**
	 * @covers \Tchooz\Entities\Payment\TransactionEntity::setExternalReferences
	 * @return void
	 */
	public function testSetExternalReferencesRejectsAnythingElseThanReferences(): void
	{
		$transaction = new TransactionEntity(1);

		$this->expectException(\InvalidArgumentException::class);
		$transaction->setExternalReferences(['ABC123']);
	}

	/**
	 * @covers \Tchooz\Entities\Payment\TransactionEntity::serialize
	 * @return void
	 */
	public function testSerializeExposesBothTheMainReferenceAndAllOfThem(): void
	{
		$transaction = new TransactionEntity(1);
		$transaction->setStatus(TransactionStatus::CONFIRMED);
		$transaction->setCreatedAt('2026-08-25 10:00:00');
		$transaction->setCurrency(new CurrencyEntity(1, 'Euro', '€', 'EUR'));
		$transaction->setPaymentMethod(new PaymentMethodEntity(1, 'card', 'Carte bancaire'));
		$transaction->setExternalReference('ABC123');
		$transaction->setExternalReferences([
			new ExternalReferenceEntity(1, 'jos_emundus_payment_transaction.id', '1', 'ABC123'),
			new ExternalReferenceEntity(2, 'jos_emundus_payment_transaction.id', '1', 'hubspot-deal-1234', 42, 'deals', 'hs_object_id'),
		]);

		$serialized = $transaction->serialize();

		$this->assertEquals('ABC123', $serialized['external_reference'], 'The scalar key keeps its former meaning');
		$this->assertIsArray($serialized['external_references']);
		$this->assertCount(2, $serialized['external_references']);
		$this->assertEquals('ABC123', $serialized['external_references'][0]['reference']);
		$this->assertNull($serialized['external_references'][0]['synchronizerId']);
		$this->assertEquals('hubspot-deal-1234', $serialized['external_references'][1]['reference']);
		$this->assertEquals('deals', $serialized['external_references'][1]['referenceObject']);
	}

	/**
	 * @covers \Tchooz\Entities\Payment\TransactionEntity::serialize
	 * @return void
	 */
	public function testSerializeReturnsAnEmptyArrayWhenNoReferenceIsLoaded(): void
	{
		$transaction = new TransactionEntity(1);
		$transaction->setStatus(TransactionStatus::INITIATED);
		$transaction->setCreatedAt('2026-08-25 10:00:00');
		$transaction->setCurrency(new CurrencyEntity(1, 'Euro', '€', 'EUR'));
		$transaction->setPaymentMethod(new PaymentMethodEntity(1, 'card', 'Carte bancaire'));

		$serialized = $transaction->serialize();

		$this->assertSame('', $serialized['external_reference']);
		$this->assertSame([], $serialized['external_references']);
	}
}
