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
use Tchooz\Repositories\Payment\ProductRepository;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Repositories\Payment\CurrencyRepository;


/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 */
class ProductRepositoryTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->model = new ProductRepository();
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\ProductRepository::createCart
	 * @return void
	 */
	public function testGetProducts()
	{
		$products = $this->model->getProducts();
		$this->assertIsArray($products);
	}

	/**
	 * @covers ProductRepository::flush
	 * @return void
	 */
	public function testFlush()
	{
		$currency_repository = new CurrencyRepository();
		$currency = $currency_repository->getCurrencyById(1);

		$product = new ProductEntity(0);
		$product->setLabel('Test');
		$product->setDescription('Test');
		$product->setPrice(1);
		$product->setCurrency($currency);

		$product_id = $this->model->flush($product);
		$this->assertGreaterThan(0, $product_id, 'La sauvegarde d\'un produit fonctionne.');

		$products_after = $this->model->getProducts(1000);
	}
}