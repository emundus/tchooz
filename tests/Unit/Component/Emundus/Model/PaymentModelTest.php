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
		$db = Factory::getContainer()->get('DatabaseDriver');
		$query = $db->createQuery();
		$query->delete($db->quoteName('#__hikashop_product'))
			->where($db->quoteName('product_id') . ' IN (' . implode(',', [$product_id_1, $product_id_2]) . ')');
		$db->setQuery($query);
		$db->execute();
	}
}