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
use Tchooz\Entities\Payment\CurrencyEntity;
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
	private ProductRepository $productRepository;

	private CurrencyEntity $defaultCurrency;


	public function __construct()
	{
		parent::__construct();
		$this->productRepository = new ProductRepository();
		$currency_repository = new CurrencyRepository();
		$this->defaultCurrency = $currency_repository->getCurrencyById(1);
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\ProductRepository::flush
	 * @return void
	 */
	public function testFlush()
	{
		$product = new ProductEntity(0, 'Test', 'Test', 1.0, $this->defaultCurrency);
		$flushed = $this->productRepository->flush($product);
		$this->assertTrue($flushed, 'La sauvegarde d\'un produit fonctionne.');
		$this->assertGreaterThan(0, $product->getId(), 'La sauvegarde d\'un produit fonctionne.');
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\ProductRepository::getProductById
	 * @return void
	 */
	public function testGetProductById()
	{
		$product = new ProductEntity(0, 'Test', 'Test', 1.0, $this->defaultCurrency);
		$this->productRepository->flush($product);

		$retrievedProduct = $this->productRepository->getProductById($product->getId());
		$this->assertInstanceOf(ProductEntity::class, $retrievedProduct);
		$this->assertEquals($product->getId(), $retrievedProduct->getId());
		$this->assertEquals('Test', $retrievedProduct->getLabel());
		$this->assertEquals(1.0, $retrievedProduct->getPrice());
		$this->assertEquals($this->defaultCurrency->getId(), $retrievedProduct->getCurrency()->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\ProductRepository::getProducts
	 * @return void
	 */
	public function testGetProducts()
	{
		$product = new ProductEntity(0, 'Test', 'Test', 1.0, $this->defaultCurrency);
		$this->productRepository->flush($product);

		$product2 = new ProductEntity(0, 'Test 2', 'Test 2', 60.0, $this->defaultCurrency);
		$this->productRepository->flush($product2);

		$products = $this->productRepository->getProducts($lim = 10, $offset = 0, ['p.id' => [$product->getId(), $product2->getId()]]);
		$this->assertIsArray($products);
		$this->assertNotEmpty($products);
		$this->assertCount(2, $products);
		$product_ids = array_map(fn($p) => $p->getId(), $products);
		$this->assertContains($product->getId(), $product_ids);
		$this->assertContains($product2->getId(), $product_ids);
	}
}