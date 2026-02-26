<?php

namespace Unit\Component\Emundus\Class\Factories\Payment;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Factories\Payment\TransactionFactory;

class TransactionFactoryTest extends UnitTestCase
{
	private TransactionFactory $factory;

	public function setUp(): void
	{
		parent::setUp();

		$this->factory = new TransactionFactory();
	}

	/**
	 * @covers \Tchooz\Factories\Payment\TransactionFactory::getTransactionTitle
	 */
	public function testGetTransactionTitle()
	{
		$transaction = new TransactionEntity(1);
		$title = $this->factory->getTransactionTitle($transaction);
		$this->assertNotEmpty($title, 'Transaction title should not be empty');
		$this->assertStringContainsString(1, $title, 'Transaction title should contain the transaction ID');

		$productLabel = 'Product 1';
		$transaction->setData(json_encode([
			'products' => [
				['label' =>  $productLabel],
			]
		]));

		$titleWithProduct = $this->factory->getTransactionTitle($transaction);
		$this->assertStringContainsString($productLabel, $titleWithProduct, 'Transaction title should contain the product label');
	}
}