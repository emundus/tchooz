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
use stdClass;
use Tchooz\Entities\Contacts\AddressEntity;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Entities\Contacts\ContactAddressEntity;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\CurrencyRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductRepository;

require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');
/**
 * @package     Unit\Component\Emundus\Helper
 *
 * @since       version 1.0.0
 */
class CartRepositoryTest extends UnitTestCase
{

	private \EmundusModelWorkflow $workflow_model;

	private $payment_workflow = [];

	private $payment_step = null;

	public function __construct(?string $name = null, array $data = [], $dataName = '')
	{
		parent::__construct();
		$this->model = new CartRepository();
		$this->workflow_model = new \EmundusModelWorkflow();
		$this->initDataSet();
	}

	public function createWorkflowWithPayment()
	{
		$query = $this->db->createQuery();
		$query->clear()
			->update('#__emundus_setup_sync')
			->set('published = 1')
			->set('enabled = 1')
			->where('type = "sogecommerce"');

		$this->db->setQuery($query);
		$this->db->execute();

		if (empty($this->payment_workflow)) {
			$workflow_id = $this->workflow_model->add();
			$workflow = ['id' => $workflow_id, 'label' => 'Test Workflow', 'published' => 1];
			$steps = [
				[
					'id' => 0,
					'entry_status' => [['id' => 0]],
					'type' => 1,
					'profile_id' => '1000',
					'label' => 'Test Step',
					'output_status' => 1
				],
				[
					'id' => 0,
					'entry_status' => [['id' => 0]],
					'type' => $this->workflow_model->getPaymentStepType(),
					'label' => 'Test Payment Step',
					'output_status' => 1
				]
			];

			$updated = $this->workflow_model->updateWorkflow($workflow, $steps, [['id' => $this->dataset['program']['programme_id']]]);

			$this->payment_workflow = $this->workflow_model->getWorkflow($workflow_id);

			$currency_repository = new CurrencyRepository();
			$currency = $currency_repository->getCurrencyById(1);

			$product_repository = new ProductRepository();
			$mandatory_product = new ProductEntity();
			$mandatory_product->setLabel('Produit obligatoire');
			$mandatory_product->setDescription('Produit obligatoire avec pour montant 500€');
			$mandatory_product->setCurrency($currency);
			$mandatory_product->setPrice(500.0);
			$mandatory_product->setMandatory(1);
			$mandatory_product_id = $product_repository->flush($mandatory_product);
			$mandatory_product->setId($mandatory_product_id);

			$optional_product = new ProductEntity();
			$optional_product->setLabel('Produit obligatoire');
			$optional_product->setDescription('Produit obligatoire avec pour montant 500€');
			$optional_product->setMandatory(0);
			$optional_product->setPrice(20);
			$optional_product->setCurrency($currency);
			$optional_product_id = $product_repository->flush($optional_product);
			$optional_product->setId($optional_product_id);

			$payment_step = null;
			foreach ($this->payment_workflow['steps'] as $step)
			{
				if ($this->workflow_model->isPaymentStep($step->type)) {
					$payment_step = $step;
				}
			}

			if (!empty($payment_step->id)) {
				$payment_repository = new PaymentRepository();
				$payment_methods = $payment_repository->getPaymentMethods();
				$this->payment_step = $payment_repository->getPaymentStepById($payment_step->id);
				$this->payment_step->setPaymentMethods($payment_methods);
				$this->payment_step->setAdjustBalance(0);
				$this->payment_step->setProducts([$mandatory_product, $optional_product]);

				$rule = new stdClass();
				$rule->from_amount = 200;
				$rule->to_amount = 400;
				$rule->min_installments = 1;
				$rule->max_installments = 3;
				$this->payment_step->setInstallmentRules([$rule]);

				$services = $payment_repository->getPaymentServices();
				if (!empty($services)) {
					$this->payment_step->setSynchronizerId($services[0]->id);
				}

				$payment_repository->flushPaymentStep($this->payment_step);
			}
		}
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CartRepository::createCart
	 * @return void
	 */
	public function testCreateCart()
	{
		$cart_id = $this->model->createCart('', 0);
		$this->assertEquals(0, $cart_id);

		$this->createWorkflowWithPayment();

		$cart_id = $this->model->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$this->assertGreaterThan(0, $cart_id);
		$cart = $this->model->getCartById($cart_id, 0, $this->dataset['coordinator']);
		$this->assertNotEmpty($cart, 'Le panier existe');
		$this->assertNotEmpty($cart->getProducts(), 'Les produits sont chargés');
		$this->assertNotEmpty($cart->getPaymentMethods(), 'Les méthodes de paiement sont chargées');
		$cart->calculateTotal();
		$this->assertEquals(500, $cart->getTotal(), 'Le montant total du panier doit être égal au montant des produits obligatoires du panier par défaut.');
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CartRepository::getCartById
	 * @return void
	 */
	public function testGetCartById()
	{
		$cart = $this->model->getCartById(0, 0, $this->dataset['coordinator']);
		$this->assertEmpty($cart);

		$this->createWorkflowWithPayment();

		$cart_id = $this->model->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$cart = $this->model->getCartById($cart_id, 0, $this->dataset['coordinator']);
		$this->assertNotEmpty($cart);
		$this->assertEquals($cart_id, $cart->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CartRepository::getCartByFnum
	 * @return void
	 */
	public function testGetCartByFnum()
	{
		$cart = $this->model->getCartByFnum(0, 0, $this->dataset['coordinator']);
		$this->assertEmpty($cart);

		$this->createWorkflowWithPayment();

		$cart_id = $this->model->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$cart = $this->model->getCartByFnum($this->dataset['fnum'], 0, $this->dataset['coordinator']);
		$this->assertNotEmpty($cart);
		$this->assertEquals($cart_id, $cart->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CartRepository::createTransaction
	 * @return void
	 */
	public function testCreateTransactionFromCart()
	{
		$this->createWorkflowWithPayment();
		$cart_id = $this->model->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$cart = $this->model->getCartById($cart_id, $this->payment_step->getId(), $this->dataset['coordinator']);
		$transaction = $this->model->createTransaction($cart);
		$this->assertNotEmpty($transaction, 'La transaction a été créée à partir du panier.');
		$this->assertContainsOnlyInstancesOf(TransactionEntity::class, [$transaction], 'La transaction est une instance de TransactionEntity.');
		$this->assertEquals($transaction->getAmount(), $cart->getTotal(), 'Le montant du panier et le montant de la transaction sont équivalents.');
		$this->assertNotEmpty($transaction->getData(), 'Les données de la transaction sont présentes.');
		$this->assertEquals($transaction->getPaymentMethod()->getId(), $cart->getSelectedPaymentMethod()->getId(), 'La méthode de paiement de la transaction correspond à la méthode de paiement sélectionnée dans le panier.');
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CartRepository::verifyCart
	 * @return void
	 */
	public function testVerifyCartThrowsErrorIfNoPaymentMethodSelected()
	{
		$this->createWorkflowWithPayment();
		$cart_id = $this->model->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$cart = $this->model->getCartById($cart_id, $this->payment_step->getId(), $this->dataset['coordinator']);
		$fake_adress = new AddressEntity(0, 'La Rochelle', 'Nouvelle-Aquitaine', '10 passage du drakkar', '', 17000, 77);
		$cart->getCustomer()->setAddresses([$fake_adress]);

		$verified = $this->model->verifyCart($cart, $this->dataset['coordinator']);
		$this->assertTrue($verified, 'Cart is successfully verified when payment method is selected.');

		// Simulate no payment method selected
		$cart->setSelectedPaymentMethod(null);
		$this->expectException(\Exception::class);
		$this->model->verifyCart($cart, $this->dataset['coordinator']);
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CartRepository::verifyCart
	 * @return void
	 */
	public function testVerifyCartThrowsErrorIfNoProductsInCart()
	{
		$this->createWorkflowWithPayment();
		$cart_id = $this->model->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$cart = $this->model->getCartById($cart_id, $this->payment_step->getId(), $this->dataset['coordinator']);
		$fake_adress = new AddressEntity(0, 'La Rochelle', 'Nouvelle-Aquitaine', '10 passage du drakkar', '', 17000, 77);
		$cart->getCustomer()->setAddresses([$fake_adress]);

		$verified = $this->model->verifyCart($cart, $this->dataset['coordinator']);
		$this->assertTrue($verified, 'Cart is successfully verified with products in it.');

		// Simulate no products in cart
		$cart->setProducts([]);
		$this->expectException(\Exception::class);
		$this->model->verifyCart($cart, $this->dataset['coordinator']);
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\CartRepository::verifyCart
	 * @return void
	 */
	public function testVerifyCartThrowsErrorIfNoAddress()
	{
		$this->createWorkflowWithPayment();
		$cart_id = $this->model->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$cart = $this->model->getCartById($cart_id, $this->payment_step->getId(), $this->dataset['coordinator']);

		$this->expectException(\Exception::class);
		$this->model->verifyCart($cart, $this->dataset['coordinator']);
	}
}