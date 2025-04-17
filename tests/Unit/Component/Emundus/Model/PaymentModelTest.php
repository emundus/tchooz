<?php

/**
 * @package         Joomla.UnitTest
 * @subpackage      Extension
 *
 * @copyright   (C) 2022 Open Source Matters, Inc. <https://www.joomla.org>
 * @license         GNU General Public License version 2 or later; see LICENSE.txt
 */

namespace Unit\Component\Emundus\Model;

use EmundusModelApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\User\UserFactoryInterface;
use Joomla\Tests\Unit\UnitTestCase;
use stdClass;

/**
 * @package     Unit\Component\Emundus\Model
 *
 * @since       version 1.0.0
 * @covers      EmundusModelPayment
 */
class PaymentModelTest extends UnitTestCase
{
	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct('payment', $data, $dataName, 'EmundusModelPayment');
	}
	/**
	 * @covers \EmundusModelPayment::createHikashopProduct
	 */
	public function testCreateHikashopProduct()
	{
		// Insert a new product
		$product = new stdClass();
		$product->label = 'Test Product';
		$product->price = 10.00;

		$product_id_1 = $this->model->createHikashopProduct($product->label, $product->price);
		$this->assertNotEmpty($product_id_1, 'Product ID should not be null');
		$this->assertIsInt($product_id_1, 'Product ID should be an integer');

		// Try with code
		$product->code = 'TEST123';
		$product_id_2 = $this->model->createHikashopProduct($product->label, $product->price, $product->code);
		$this->assertNotEmpty($product_id_2, 'Product ID should not be null');
		$this->assertIsInt($product_id_2, 'Product ID should be an integer');

		// Delete the product
		$this->deleteTestProduct($product_id_1);
		$this->deleteTestProduct($product_id_2);
	}

	/**
	 * @covers \EmundusModelPayment::createHikashopProduct
	 */
	public function testCreateHikashopProductHikamarket()
	{
		// test create hikashop product with hikamarket. it means that product_vendor_id and product_vendor_params exists in hikashop_product

		// add the columns if they do not exist
		$db = Factory::getContainer()->get('DatabaseDriver');
		$table_query = 'SHOW COLUMNS FROM #__hikashop_product';
		$db->setQuery($table_query);
		$columns_info = $db->loadColumn();

		if (!in_array('product_vendor_id', $columns_info)) {
			$add_query = 'ALTER TABLE #__hikashop_product ADD COLUMN product_vendor_id INT(11) DEFAULT NULL';
			$db->setQuery($add_query);
			$db->execute();
		}
		if (!in_array('product_vendor_params', $columns_info)) {
			$add_query = 'ALTER TABLE #__hikashop_product ADD COLUMN product_vendor_params TEXT DEFAULT NULL';
			$db->setQuery($add_query);
			$db->execute();
		}

		$product = new stdClass();
		$product->label = 'Test Product';
		$product->price = 10.00;
		$product_id = $this->model->createHikashopProduct($product->label, $product->price);
		$this->assertNotEmpty($product_id, 'Product creation works even with hikamarket columns');
		$this->assertIsInt($product_id, 'Product ID should be an integer');

		$this->deleteTestProduct($product_id);
	}


	/**
	 * @covers \EmundusModelPayment::getProduct
	 */
	public function testGetProduct() {
		$product = new stdClass();
		$product->label = 'Test Product';
		$product->price = 10.00;
		$product_id = $this->model->createHikashopProduct($product->label, $product->price);

		$this->assertNotEmpty($product_id, 'Product ID should not be null');
		$found_product = $this->model->getProduct($product_id);
		$this->assertNotEmpty($found_product->product_id, 'Product should be found');
		$this->assertEquals($product_id, $found_product->product_id, 'Product ids should match');
		$this->assertEquals($product->label, $found_product->product_name, 'Product names should match');
		$this->assertEquals($product->price, $found_product->product_sort_price, 'Product prices should match');

		// Clean up
		$this->deleteTestProduct($product_id);
	}

	/**
	 * @covers \EmundusModelPayment::updateHikashopProductPrice
	 */
	public function testUpdateHikashopProductPrice()
	{
		$product = new stdClass();
		$product->label = 'Test Product';
		$product->price = 10.00;
		$product_id = $this->model->createHikashopProduct($product->label, $product->price);
		$this->assertNotEmpty($product_id);

		$updated = $this->model->updateHikashopProductPrice($product_id, 20.00);
		$this->assertTrue($updated, 'Product price should be updated');
		$product = $this->model->getProduct($product_id);
		$this->assertEquals(20.00, $product->product_sort_price, 'Product price should be 20.00');

		$this->deleteTestProduct($product_id);
	}

	/**
	 * @param $product_id
	 *
	 * @return void
	 */
	private function deleteTestProduct(int $product_id): void
	{
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();
		$query->delete($db->quoteName('#__hikashop_product'))
			->where($db->quoteName('product_id') . ' = ' . (int) $product_id);
		$db->setQuery($query);
		$db->execute();
	}
}