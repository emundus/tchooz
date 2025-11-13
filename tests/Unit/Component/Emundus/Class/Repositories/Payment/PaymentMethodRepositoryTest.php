<?php

namespace Unit\Component\Emundus\Class\Repositories\Payment;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Repositories\Payment\PaymentMethodRepository;

class PaymentMethodRepositoryTest extends UnitTestCase
{
	private PaymentMethodRepository $repository;

	public function setUp(): void
	{
		parent::setUp();
		$this->repository = new PaymentMethodRepository();
	}

	/**
	 * @covers PaymentMethodRepository::getAll
	 * @return void
	 */
	public function testGetAll()
	{
		$methods = $this->repository->getAll(10, 1);
		$this->assertIsArray($methods, 'Expected an array of payment methods');
		$this->assertNotEmpty($methods, 'Expected at least one payment method');
		$this->assertInstanceOf(PaymentMethodEntity::class, $methods[0], 'Expected instance of PaymentMethodEntity');
	}

	/**
	 * @covers PaymentMethodRepository::getById
	 * @return void
	 */
	public function testGetById(): void
	{
		$methods = $this->repository->getAll(1);
		$this->assertNotEmpty($methods, 'Expected at least one payment method to test getById');

		$methodId = $methods[0]->getId();
		$method = $this->repository->getById($methodId);

		$this->assertInstanceOf(PaymentMethodEntity::class, $method, 'Expected instance of PaymentMethodEntity');
		$this->assertEquals($methodId, $method->getId(), 'Expected the retrieved method ID to match the requested ID');
	}
}