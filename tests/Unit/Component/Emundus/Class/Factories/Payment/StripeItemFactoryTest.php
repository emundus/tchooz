<?php

namespace Unit\Component\Emundus\Class\Factories\Payment;

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\Entities\Payment\AlterationEntity;
use Tchooz\Entities\Payment\AlterationType;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\DiscountType;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Factories\Payment\StripeItemFactory;

/**
 * @package     Unit\Component\Emundus\Class\Factories\Payment
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Factories\Payment\StripeItemFactory
 */
class StripeItemFactoryTest extends UnitTestCase
{
	private StripeItemFactory $factory;

	private CurrencyEntity $currency;

	public function setUp(): void
	{
		parent::setUp();

		$this->factory  = new StripeItemFactory();
		$this->currency = new CurrencyEntity(1, 'Euro', '€', 'EUR');
	}

	private function buildProduct(int $id, string $label, float $price, string $description = ''): ProductEntity
	{
		$product = new ProductEntity();
		$product->setId($id);
		$product->setLabel($label);
		$product->setDescription($description);
		$product->setPrice($price);
		$product->setCurrency($this->currency);

		return $product;
	}

	private function buildCart(array $products = [], array $alterations = []): CartEntity
	{
		$cart = new CartEntity(0, 'test_fnum');
		$cart->setCurrency($this->currency);

		if (!empty($products))
		{
			$cart->setProducts($products);
		}

		if (!empty($alterations))
		{
			$cart->setPriceAlterations($alterations);
		}

		return $cart;
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataWithSingleProductNoAlterations(): void
	{
		$product = $this->buildProduct(1, 'Tuition', 100.00, 'Yearly tuition fee');
		$cart    = $this->buildCart([$product]);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		$this->assertArrayHasKey('line_items', $result, 'Result should contain line_items key');
		$this->assertArrayHasKey('discounts', $result, 'Result should contain discounts key');
		$this->assertCount(1, $result['line_items'], 'A single product should produce a single line item');
		$this->assertEmpty($result['discounts'], 'No alteration should produce no discount');

		$item = $result['line_items'][0];
		$this->assertEquals('Tuition', $item['price_data']['product_data']['name']);
		$this->assertEquals('Yearly tuition fee', $item['price_data']['product_data']['description']);
		$this->assertEquals('EUR', $item['price_data']['currency'], 'Currency iso3 should be used by Stripe');
		$this->assertEquals(10000, $item['price_data']['unit_amount'], 'Amount should be in cents');
		$this->assertEquals(1, $item['quantity']);
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataWithMultipleProducts(): void
	{
		$product1 = $this->buildProduct(1, 'Tuition', 100.00);
		$product2 = $this->buildProduct(2, 'Registration', 50.00);
		$cart     = $this->buildCart([$product1, $product2]);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		$this->assertCount(2, $result['line_items'], 'Each product should produce one line item');
		$this->assertEmpty($result['discounts']);
		$this->assertEquals(10000, $result['line_items'][0]['price_data']['unit_amount']);
		$this->assertEquals(5000, $result['line_items'][1]['price_data']['unit_amount']);
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataAppliesProductSpecificFixedAlteration(): void
	{
		$product    = $this->buildProduct(1, 'Tuition', 100.00);
		$alteration = new AlterationEntity(1, 0, $product, null, 'Extra', 10.00, AlterationType::FIXED);
		$cart       = $this->buildCart([$product], [$alteration]);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		$this->assertCount(1, $result['line_items'], 'Product-specific alteration should be merged into the product line item');
		$this->assertEmpty($result['discounts']);
		$this->assertEquals(11000, $result['line_items'][0]['price_data']['unit_amount'], '100 + 10 fixed alteration = 110');
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataAppliesProductSpecificPercentageAlteration(): void
	{
		$product    = $this->buildProduct(1, 'Tuition', 100.00);
		$alteration = new AlterationEntity(1, 0, $product, null, 'VAT', 20.00, AlterationType::PERCENTAGE);
		$cart       = $this->buildCart([$product], [$alteration]);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		$this->assertCount(1, $result['line_items']);
		$this->assertEmpty($result['discounts']);
		$this->assertEquals(12000, $result['line_items'][0]['price_data']['unit_amount'], '100 + 20% = 120');
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataEmitsPositiveGlobalAlterationAsFeeLineItem(): void
	{
		$product    = $this->buildProduct(1, 'Tuition', 100.00);
		$alteration = new AlterationEntity(1, 0, null, null, 'Handling fee', 10.00, AlterationType::FIXED);
		$cart       = $this->buildCart([$product], [$alteration]);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		$this->assertCount(2, $result['line_items'], 'Positive global alteration should be a separate line item');
		$this->assertEmpty($result['discounts']);
		$this->assertEquals(10000, $result['line_items'][0]['price_data']['unit_amount']);
		$this->assertEquals(1000, $result['line_items'][1]['price_data']['unit_amount']);
		$this->assertEquals('Handling fee', $result['line_items'][1]['price_data']['product_data']['name']);
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataEmitsNegativeGlobalAlterationAsCoupon(): void
	{
		$product    = $this->buildProduct(1, 'Tuition', 100.00);
		$alteration = new AlterationEntity(1, 0, null, null, 'Early bird', -15.00, AlterationType::FIXED);
		$cart       = $this->buildCart([$product], [$alteration]);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		$this->assertCount(1, $result['line_items'], 'Negative alteration should not create a line item');
		$this->assertCount(1, $result['discounts'], 'Negative alteration should produce a coupon');

		$this->assertEquals(10000, $result['line_items'][0]['price_data']['unit_amount']);

		$discount = $result['discounts'][0];
		$this->assertEquals(1500, $discount['amount_off'], 'Coupon must use absolute value in cents');
		$this->assertEquals('EUR', $discount['currency']);
		$this->assertEquals('once', $discount['duration']);
		$this->assertEquals('Early bird', $discount['name']);
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataGlobalPercentageAlterationUsesRunningTotal(): void
	{
		$product  = $this->buildProduct(1, 'Tuition', 200.00);
		$fee      = new AlterationEntity(1, 0, null, null, 'Service fee', 50.00, AlterationType::FIXED);
		$discount = new AlterationEntity(2, 0, null, null, 'Loyalty 10%', -10.00, AlterationType::PERCENTAGE);
		$cart     = $this->buildCart([$product], [$fee, $discount]);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		// running total after product + fee = 250, then -10% = -25
		$this->assertCount(2, $result['line_items']);
		$this->assertCount(1, $result['discounts']);
		$this->assertEquals(20000, $result['line_items'][0]['price_data']['unit_amount']);
		$this->assertEquals(5000, $result['line_items'][1]['price_data']['unit_amount']);
		$this->assertEquals(2500, $result['discounts'][0]['amount_off']);
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataSkipsAlterAdvanceAmountAlteration(): void
	{
		$product         = $this->buildProduct(1, 'Tuition', 100.00);
		$advanceOverride = new AlterationEntity(1, 0, null, null, 'Advance override', 30.00, AlterationType::ALTER_ADVANCE_AMOUNT);
		$cart            = $this->buildCart([$product], [$advanceOverride]);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		$this->assertCount(1, $result['line_items'], 'alter_advance_amount alterations must be ignored outside advance flow');
		$this->assertEmpty($result['discounts']);
		$this->assertEquals(10000, $result['line_items'][0]['price_data']['unit_amount']);
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataWithAdvancePaymentReturnsSingleLineItem(): void
	{
		$product = $this->buildProduct(1, 'Tuition', 1000.00);
		$cart    = $this->buildCart([$product]);
		$cart->setAllowedToPayAdvance(true);
		$cart->setAdvanceAmount(300);
		$cart->setAdvanceAmountType(DiscountType::FIXED);
		$cart->setPayAdvance(1);

		$result = $this->factory->buildCheckoutData($cart, $this->currency);

		$this->assertCount(1, $result['line_items'], 'Advance flow should produce a single advance line item');
		$this->assertEmpty($result['discounts']);
		$this->assertEquals(30000, $result['line_items'][0]['price_data']['unit_amount'], 'Advance line item should carry the advance total in cents');
		$this->assertEquals('EUR', $result['line_items'][0]['price_data']['currency']);
	}

	/**
	 * @covers \Tchooz\Factories\Payment\StripeItemFactory::buildCheckoutData
	 * @return void
	 */
	public function testBuildCheckoutDataThrowsWhenTotalMismatches(): void
	{
		// CartEntity::calculateTotal clamps the total to 0 when it would be negative,
		// but the factory does not clamp items − discounts, so a discount bigger than
		// the product price must trigger the mismatch guard.
		$product     = $this->buildProduct(1, 'Tuition', 10.00);
		$bigDiscount = new AlterationEntity(1, 0, null, null, 'Over-discount', -50.00, AlterationType::FIXED);
		$cart        = $this->buildCart([$product], [$bigDiscount]);

		$this->expectException(\Exception::class);
		$this->expectExceptionMessageMatches('/Total amount mismatch/');

		$this->factory->buildCheckoutData($cart, $this->currency);
	}
}
