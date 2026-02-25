<?php

namespace Unit\Component\Emundus\Class\Repositories\Payment;

use Joomla\Tests\Unit\UnitTestCase;
use Joomla\CMS\Factory;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Repositories\Payment\CurrencyRepository;

class CurrencyRepositoryTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->model = new CurrencyRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CurrencyRepository::getCurrencyById
	 * @return void
	 */
	public function testGetCurrencyById()
	{
		$currency = $this->model->getCurrencyById(0);
		$this->assertEmpty($currency);

		$currency = $this->model->getCurrencyById(1);
		$this->assertNotEmpty($currency);
		$this->assertEquals($currency->getSymbol(), '€');
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CurrencyRepository::getCurrencies
	 * @return void
	 */
	public function testGetCurrencies()
	{
		$currencies = $this->model->getCurrencies();
		$this->assertNotEmpty($currencies);

		foreach ($currencies as $currency) {
			$this->assertInstanceOf(CurrencyEntity::class, $currency);
		}
	}
}