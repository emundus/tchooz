<?php
/**
 * @package     Unit\Component\Emundus\Class
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Unit\Component\Emundus\Class\Repositories\Payment;

use Joomla\Tests\Unit\UnitTestCase;
use Joomla\CMS\Factory;
use Tchooz\Repositories\Payment\PaymentRepository;


/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 */
class PaymentRepositoryTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->model = new PaymentRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\PaymentRepository::getCountries
	 * @return void
	 */
	public function testGetCountries()
	{
		$countries = $this->model->getCountries();
		$this->assertNotEmpty($countries);
	}

	/**
	 * @covers PaymentRepository::getPaymentMethods
	 * @return void
	 */
	public function testGetPaymentMethods()
	{
		$payment_methods = $this->model->getPaymentMethods();
		$this->assertNotEmpty($payment_methods, 'Les méthodes de paiments existent.');
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\PaymentRepository::testGenerateAmountsByIterations
	 * @return void
	 */
	public function testGenerateAmountsByIterations()
	{
		$amounts = $this->model->generateAmountsByIterations(0, 0);
		$this->assertEmpty($amounts);

		$amounts_by_iterations = $this->model->generateAmountsByIterations(500, 1);
		$this->assertNotEmpty($amounts_by_iterations);
		$this->assertEquals([500], $amounts_by_iterations, '1 iteration works correctly');

		$amounts_by_iterations = $this->model->generateAmountsByIterations(500, 2);
		$this->assertNotEmpty($amounts_by_iterations);
		$this->assertEquals([250, 250], $amounts_by_iterations, 'Two iteration works correctly');

		$amounts_by_iterations = $this->model->generateAmountsByIterations(501, 2);
		$this->assertNotEmpty($amounts_by_iterations);
		$this->assertEquals([251, 250], $amounts_by_iterations);

		$amounts_by_iterations = $this->model->generateAmountsByIterations(15.5, 1);
		$this->assertNotEmpty($amounts_by_iterations);
		$this->assertEquals([15.5], $amounts_by_iterations);

		$amounts_by_iterations = $this->model->generateAmountsByIterations(15.5, 2);
		$this->assertNotEmpty($amounts_by_iterations);
		$this->assertEquals([8.5, 7], $amounts_by_iterations);

		$amounts_by_iterations = $this->model->generateAmountsByIterations(1012, 6);
		$this->assertNotEmpty($amounts_by_iterations);
		$this->assertEquals(sizeof($amounts_by_iterations), 6, 'IL y a bien le bon nombre d\'itérations.');
		$this->assertEquals(array_sum($amounts_by_iterations), 1012, 'La somme des montants par itération est égale au montant initial.');

		foreach ($amounts_by_iterations as $iteration => $amount) {
			// amount has max 2 decimals
			$this->assertMatchesRegularExpression('/^\d+(\.\d{1,2})?$/', (string) $amount, "L'itération $iteration a maximum 2 décimales.");
		}

		$amounts_by_iterations = $this->model->generateAmountsByIterations(7267.68, 8);
		$this->assertNotEmpty($amounts_by_iterations);
		$this->assertEquals(sizeof($amounts_by_iterations), 8, 'IL y a bien le bon nombre d\'itérations.');
		$this->assertEquals(array_sum($amounts_by_iterations), 7267.68, 'La somme des montants par itération est égale au montant initial.');

		foreach ($amounts_by_iterations as $iteration => $amount) {
			// amount has max 2 decimals
			$this->assertMatchesRegularExpression('/^\d+(\.\d{1,2})?$/', (string) $amount, "L'itération $iteration a maximum 2 décimales.");
		}
	}
}