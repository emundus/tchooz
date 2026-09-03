<?php

namespace Unit\Component\Emundus\Class\Repositories\Payment;

use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Payment\TransactionStatus;
use Tchooz\Repositories\Payment\CartRepository;
use Tchooz\Repositories\Payment\CurrencyRepository;
use Tchooz\Repositories\Payment\PaymentRepository;
use Tchooz\Repositories\Payment\ProductRepository;
use Tchooz\Repositories\Payment\TransactionRepository;
use Tchooz\Entities\Reference\ExternalReferenceEntity;
use Tchooz\Repositories\Reference\ExternalReferenceRepository;
use Joomla\Tests\Unit\UnitTestCase;
use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;
use stdClass;
use Tchooz\Entities\Payment\TransactionEntity;

require_once(JPATH_ROOT . '/components/com_emundus/models/workflow.php');

/**
 * @package     Unit\Component\Emundus\Class\Repositories\Payment
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\Repositories\Payment\TransactionRepository
 */
class TransactionRepositoryTest extends UnitTestCase
{
	private $payment_workflow = [];

	private \EmundusModelWorkflow $workflow_model;

	private $payment_step = null;

	public function setUp(): void
	{
		parent::setUp();
		$this->model          = new TransactionRepository();
		$this->workflow_model = new \EmundusModelWorkflow();
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::getTransactions
	 * @return void
	 */
	public function testGetTransactions()
	{
		$this->createWorkflowWithPayment();
		$cart_repository = new CartRepository();
		$cart_id         = $cart_repository->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$transactions    = [
			[$this->db->quote($cart_id), $this->db->quote($this->dataset['fnum']), $this->db->quote($this->payment_step->getId()), $this->db->quote(TransactionStatus::CONFIRMED->value), $this->db->quote(800.00), 1, $this->db->quote($this->payment_step->getSynchronizerId()), 1, $this->db->quote('{}')],
			[$this->db->quote($cart_id), $this->db->quote($this->dataset['fnum']), $this->db->quote($this->payment_step->getId()), $this->db->quote(TransactionStatus::INITIATED->value), $this->db->quote(800.00), 1, $this->db->quote($this->payment_step->getSynchronizerId()), 1, $this->db->quote('{}')],
			[$this->db->quote($cart_id), $this->db->quote($this->dataset['fnum']), $this->db->quote($this->payment_step->getId()), $this->db->quote(TransactionStatus::CANCELLED->value), $this->db->quote(800.00), 1, $this->db->quote($this->payment_step->getSynchronizerId()), 1, $this->db->quote('{}')]
		];
		$this->fakeTransactions($transactions);

		$transactions = $this->model->getTransactions(10, 1, ['fnum' => $this->dataset['fnum']]);
		$this->assertNotEmpty($transactions);
		$this->assertCount(3, $transactions);
		foreach ($transactions as $transaction)
		{
			$this->assertInstanceOf(TransactionEntity::class, $transaction);
			$this->assertEquals($this->dataset['fnum'], $transaction->getFnum());
			$this->assertEquals($cart_id, $transaction->getCartId());
			$this->assertEquals($this->payment_step->getId(), $transaction->getStepId());
			$this->assertEquals(800.00, $transaction->getAmount());
			$this->assertEquals(1, $transaction->getCurrency()->getId());
			$this->assertEquals(1, $transaction->getNumberInstallmentDebit());
		}

		$transactions = $this->model->getTransactions(10, 1, ['fnum' => $this->dataset['fnum'], 'status' => TransactionStatus::CONFIRMED->value]);
		$this->assertNotEmpty($transactions);
		$this->assertCount(1, $transactions);
		$this->assertEquals(TransactionStatus::CONFIRMED, $transactions[0]->getStatus());

		$transactions = $this->model->getTransactions(10, 1, ['fnum' => $this->dataset['fnum'], 'status' => TransactionStatus::INITIATED->value]);
		$this->assertNotEmpty($transactions);
		$this->assertCount(1, $transactions);
		$this->assertEquals(TransactionStatus::INITIATED, $transactions[0]->getStatus());

		$transactions = $this->model->getTransactions(10, 1, ['fnum' => $this->dataset['fnum'], 'status' => TransactionStatus::CANCELLED->value]);
		$this->assertNotEmpty($transactions);
		$this->assertCount(1, $transactions);
		$this->assertEquals(TransactionStatus::CANCELLED, $transactions[0]->getStatus());
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

		if (empty($this->payment_workflow))
		{
			$workflow_id = $this->workflow_model->add();
			$workflow    = ['id' => $workflow_id, 'label' => 'Test Workflow', 'published' => 1];
			$steps       = [
				[
					'id'            => 0,
					'entry_status'  => [['id' => 0]],
					'type'          => 1,
					'profile_id'    => '1000',
					'label'         => 'Test Step',
					'output_status' => 1
				],
				[
					'id'            => 0,
					'entry_status'  => [['id' => 0]],
					'type'          => $this->workflow_model->getPaymentStepType(),
					'label'         => 'Test Payment Step',
					'output_status' => 1
				]
			];

			$updated = $this->workflow_model->updateWorkflow($workflow, $steps, [['id' => $this->dataset['program']['programme_id']]]);
			$this->payment_workflow = $this->workflow_model->getWorkflow($workflow_id);

			$currency_repository = new CurrencyRepository();
			$currency            = $currency_repository->getCurrencyById(1);

			$product_repository = new ProductRepository();
			$mandatory_product  = new ProductEntity();
			$mandatory_product->setLabel('Produit obligatoire');
			$mandatory_product->setDescription('Produit obligatoire avec pour montant 500€');
			$mandatory_product->setCurrency($currency);
			$mandatory_product->setPrice(500.0);
			$mandatory_product->setMandatory(1);
			$product_repository->flush($mandatory_product);

			$optional_product = new ProductEntity();
			$optional_product->setLabel('Produit obligatoire');
			$optional_product->setDescription('Produit obligatoire avec pour montant 500€');
			$optional_product->setMandatory(0);
			$optional_product->setPrice(20);
			$optional_product->setCurrency($currency);
			$product_repository->flush($optional_product);

			$payment_step = null;
			foreach ($this->payment_workflow['steps'] as $step)
			{
				if ($this->workflow_model->isPaymentStep($step->type))
				{
					$payment_step = $step;
				}
			}

			if (!empty($payment_step->id))
			{
				$payment_repository = new PaymentRepository();
				$payment_methods    = $payment_repository->getPaymentMethods();
				$this->payment_step = $payment_repository->getPaymentStepById($payment_step->id);
				$this->payment_step->setPaymentMethods($payment_methods);
				$this->payment_step->setAdjustBalance(0);
				$this->payment_step->setProducts([$mandatory_product, $optional_product]);

				$rule                   = new stdClass();
				$rule->from_amount      = 200;
				$rule->to_amount        = 400;
				$rule->min_installments = 1;
				$rule->max_installments = 3;
				$this->payment_step->setInstallmentRules([$rule]);

				$services = $payment_repository->getPaymentServices();
				if (!empty($services))
				{
					$this->payment_step->setSynchronizerId($services[0]->id);
				}

				$payment_repository->flushPaymentStep($this->payment_step);
			}
		}
	}

	public function fakeTransactions($transactions = [])
	{
		$entities = [];

		$query = $this->db->createQuery();

		foreach ($transactions as $transaction)
		{
			$query->clear()
				->insert('#__emundus_payment_transaction')
				->columns('cart_id, fnum, step_id, status, amount, currency_id, synchronizer_id, payment_method_id, data')
				->values(implode(',', $transaction));

			try
			{
				$this->db->setQuery($query);
				$inserted = $this->db->execute();

				if ($inserted)
				{
					$transaction_id = $this->db->insertid();
					$entity         = $this->model->getById($transaction_id);
					$entity->generateExternalReference();
					$this->model->saveTransaction($entity, 1);
					$entities[] = $entity;
				}
			}
			catch (\Exception $e)
			{
				$this->fail('Failed to insert transaction before running test: ' . $e->getMessage());
			}
		}

		return $entities;
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::saveTransaction
	 * @return void
	 * @throws \Exception
	 */
	public function testSaveTransaction(): void
	{
		$this->createWorkflowWithPayment();
		$cart_repository = new CartRepository();
		$cart_id         = $cart_repository->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$transaction = new TransactionEntity(0);
		$transaction->setCartId($cart_id);
		$transaction->setAmount(50.00);
		$transaction->setFnum($this->dataset['fnum']);
		$transaction->setStatus(TransactionStatus::INITIATED);
		$currency = new CurrencyEntity(1, 'Euro', 'EUR', '€');
		$transaction->setCurrency($currency);
		$transaction->setStepId($this->payment_step->getId());
		$transaction->setSynchronizerId($this->payment_step->getSynchronizerId());

		$payment_repository = new PaymentRepository();
		$payment_methods    = $payment_repository->getPaymentMethods();

		$transaction->setPaymentMethod($payment_methods[0]);

		$saved = $this->model->saveTransaction($transaction, 1);
		$this->assertTrue($saved, 'Transaction should be saved successfully');
		$this->assertGreaterThan(0, $transaction->getId(), 'Transaction ID should be set after saving');
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::getById
	 * @return void
	 */
	public function testGetById()
	{
		$query = $this->db->createQuery();

		$this->createWorkflowWithPayment();
		$cart_repository = new CartRepository();
		$cart_id         = $cart_repository->createCart($this->dataset['fnum'], $this->payment_step->getId());

		$transaction = [$this->db->quote($cart_id), $this->db->quote($this->dataset['fnum']), $this->db->quote($this->payment_step->getId()), $this->db->quote(TransactionStatus::CONFIRMED->value), $this->db->quote(800.00), 1, $this->db->quote($this->payment_step->getSynchronizerId()), 1, $this->db->quote('{}')];
		$query->clear()
			->insert('#__emundus_payment_transaction')
			->columns('cart_id, fnum, step_id, status, amount, currency_id, synchronizer_id, payment_method_id, data')
			->values(implode(',', $transaction));

		try
		{
			$this->db->setQuery($query);
			$this->db->execute();
		}
		catch (\Exception $e)
		{
			$this->fail('Failed to insert transaction before running test: ' . $e->getMessage());
		}

		$transaction_id = $this->db->insertid();

		$transaction = $this->model->getById($transaction_id);
		$this->assertNotEmpty($transaction);
		$this->assertInstanceOf(TransactionEntity::class, $transaction);
		$this->assertEquals($this->dataset['fnum'], $transaction->getFnum());
		$this->assertEquals($cart_id, $transaction->getCartId());
		$this->assertEquals($this->payment_step->getId(), $transaction->getStepId());
		$this->assertEquals(800.00, $transaction->getAmount());
		$this->assertEquals(1, $transaction->getCurrency()->getId());
		$this->assertEquals(1, $transaction->getNumberInstallmentDebit());
		$this->assertEquals(TransactionStatus::CONFIRMED, $transaction->getStatus());
		$this->assertEquals(1, $transaction->getPaymentMethod()->getId());
		$this->assertEquals($this->payment_step->getSynchronizerId(), $transaction->getSynchronizerId());
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::prepareExport
	 * @return void
	 */
	public function testPrepareExport()
	{
		$this->createWorkflowWithPayment();
		$cart_repository = new CartRepository();
		$cart_id         = $cart_repository->createCart($this->dataset['fnum'], $this->payment_step->getId());
		$cart            = $cart_repository->getCartById($cart_id, $this->payment_step->getId(), $this->dataset['applicant']);
		$transaction     = $cart_repository->createTransaction($cart);
		$transactions    = [$transaction];

		$lines = $this->model->prepareExport($transactions);
		$this->assertNotEmpty($lines);
		$this->assertGreaterThan(1, count($lines));

		$line        = $lines[1];
		$transaction = $transactions[0];
		$customer    = $this->model->getTransactionCustomer($transaction);
		$this->assertEquals($line[0], $transaction->getId(), 'Transaction ID should match');
		$this->assertEquals($line[1], $transaction->getExternalReference(), 'External should match');
		$this->assertEquals($line[2], $transaction->getFnum(), 'Fnum should match');
		$this->assertEquals($line[3], $customer->getFullName(), 'Full name should match');
		$this->assertEquals($line[4], $customer->getUserId(), 'Buyer id should match');
		$this->assertEquals($line[6], $transaction->getAmount() . ' ' . $transaction->getCurrency()->getSymbol(), 'Amount should match');
	}

	/**
	 * A transaction can be pointed at by several references, only the one without a synchronizer is shared with the
	 * payment service provider. getExternalReference must keep returning that one and nothing else.
	 *
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::getTransactions
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::getExternalReferences
	 * @return void
	 */
	public function testGetTransactionsSeparatesMainReferenceFromSynchronizerOnes(): void
	{
		$this->refreshDataset();
		$transaction    = $this->fakeTransactionWithReferences(['hubspot-deal-1234']);
		$main_reference = $transaction->getExternalReference();

		$transactions = $this->model->getTransactions(10, 1, ['fnum' => $this->dataset['fnum']]);
		$this->assertCount(1, $transactions, 'A transaction with several references must still be listed once');

		$listed = $transactions[0];
		$this->assertEquals($main_reference, $listed->getExternalReference(), 'The main reference is the one shared with the payment service provider');
		$this->assertStringNotContainsString('|', $listed->getExternalReference(), 'The main reference must never hold several references at once');

		$references = $listed->getExternalReferences();
		$this->assertCount(2, $references, 'Every reference pointing at the transaction must be loaded');
		$this->assertEquals($main_reference, $references[0]->getReference(), 'The main reference comes first');
		$this->assertNull($references[0]->getSynchronizerId(), 'The main reference is not attached to a synchronizer');
		$this->assertEquals('hubspot-deal-1234', $references[1]->getReference());
		$this->assertEquals($this->payment_step->getSynchronizerId(), $references[1]->getSynchronizerId());
		$this->assertEquals('deals', $references[1]->getReferenceObject(), 'The reference object must survive the hydration');

		$this->deleteExternalReferences($transaction->getId());
	}

	/**
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::getById
	 * @return void
	 */
	public function testGetByIdLoadsEveryExternalReference(): void
	{
		$this->refreshDataset();
		$transaction    = $this->fakeTransactionWithReferences(['hubspot-deal-5678']);
		$main_reference = $transaction->getExternalReference();

		$loaded = $this->model->getById($transaction->getId());
		$this->assertEquals($main_reference, $loaded->getExternalReference());
		$this->assertCount(2, $loaded->getExternalReferences());

		$this->deleteExternalReferences($transaction->getId());
	}

	/**
	 * Saving a transaction must never overwrite a reference owned by a third party synchronizer.
	 *
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::saveTransaction
	 * @return void
	 * @throws \Exception
	 */
	public function testSaveTransactionOnlyWritesTheMainReference(): void
	{
		$this->refreshDataset();
		$transaction = $this->fakeTransactionWithReferences(['hubspot-deal-9012']);

		$transaction->setExternalReference('NEWREF');
		$this->assertTrue($this->model->saveTransaction($transaction, 1));

		$reloaded = $this->model->getById($transaction->getId());
		$this->assertEquals('NEWREF', $reloaded->getExternalReference(), 'The main reference must be updated');

		$references = $reloaded->getExternalReferences();
		$this->assertCount(2, $references, 'Saving must not create a duplicate reference');

		$synchronizer_references = array_values(array_filter($references, static function (ExternalReferenceEntity $reference) {
			return !empty($reference->getSynchronizerId());
		}));
		$this->assertCount(1, $synchronizer_references);
		$this->assertEquals('hubspot-deal-9012', $synchronizer_references[0]->getReference(), 'The synchronizer reference must be left untouched');

		$this->deleteExternalReferences($transaction->getId());
	}

	/**
	 * Searching matches every reference of a transaction, but the transaction is still listed once and counted once.
	 *
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::getTransactions
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::countTransactions
	 * @return void
	 */
	public function testSearchOnSeveralReferencesDoesNotDuplicateTheTransaction(): void
	{
		$this->refreshDataset();
		$transaction = $this->fakeTransactionWithReferences(['SHARED-hubspot', 'SHARED-docusign']);

		$filters = ['fnum' => $this->dataset['fnum']];

		$transactions = $this->model->getTransactions(10, 1, $filters, '', 'SHARED-');
		$this->assertCount(1, $transactions, 'Two matching references must not yield two rows');
		$this->assertEquals($transaction->getId(), $transactions[0]->getId());

		$this->assertEquals(1, $this->model->countTransactions($filters, '', 'SHARED-'), 'The count must match the number of listed transactions');

		$transactions = $this->model->getTransactions(10, 1, $filters, '', 'SHARED-hubspot');
		$this->assertCount(1, $transactions, 'A synchronizer reference is searchable');

		$transactions = $this->model->getTransactions(10, 1, $filters, '', $transaction->getExternalReference());
		$this->assertCount(1, $transactions, 'The main reference is searchable');

		$this->deleteExternalReferences($transaction->getId());
	}

	/**
	 * Payment webhooks resolve a transaction from the reference they were given by the payment service provider,
	 * a reference owned by another system must never resolve.
	 *
	 * @covers \Tchooz\Repositories\Payment\TransactionRepository::getTransactionIdByExternalReference
	 * @return void
	 */
	public function testGetTransactionIdByExternalReferenceIgnoresSynchronizerReferences(): void
	{
		$this->refreshDataset();
		$transaction = $this->fakeTransactionWithReferences(['hubspot-deal-3456']);

		$this->assertEquals($transaction->getId(), $this->model->getTransactionIdByExternalReference($transaction->getExternalReference()));
		$this->assertSame(0, $this->model->getTransactionIdByExternalReference('hubspot-deal-3456'));
		$this->assertSame(0, $this->model->getTransactionIdByExternalReference('unknown-reference'));

		$this->deleteExternalReferences($transaction->getId());
	}

	/**
	 * Creates one transaction with its main reference, plus one synchronizer owned reference per given reference.
	 */
	private function fakeTransactionWithReferences(array $synchronizer_references = []): TransactionEntity
	{
		$this->createWorkflowWithPayment();
		$this->assertGreaterThan(0, $this->payment_step->getSynchronizerId(), 'A payment service is needed to fake synchronizer references');

		$cart_repository = new CartRepository();
		$cart_id         = $cart_repository->createCart($this->dataset['fnum'], $this->payment_step->getId());

		$entities = $this->fakeTransactions([
			[$this->db->quote($cart_id), $this->db->quote($this->dataset['fnum']), $this->db->quote($this->payment_step->getId()), $this->db->quote(TransactionStatus::CONFIRMED->value), $this->db->quote(800.00), 1, $this->db->quote($this->payment_step->getSynchronizerId()), 1, $this->db->quote('{}')]
		]);
		$transaction = $entities[0];

		$external_reference_repository = new ExternalReferenceRepository();
		foreach ($synchronizer_references as $index => $reference)
		{
			$external_reference_repository->flush(new ExternalReferenceEntity(
				0,
				'jos_emundus_payment_transaction.id',
				(string) $transaction->getId(),
				$reference,
				$this->payment_step->getSynchronizerId() + $index,
				'deals',
				'hs_object_id'
			));
		}

		return $transaction;
	}

	private function deleteExternalReferences(int $transaction_id): void
	{
		$query = $this->db->createQuery();
		$query->delete('#__emundus_external_reference')
			->where($this->db->quoteName('intern_id') . ' = ' . $this->db->quote($transaction_id))
			->where($this->db->quoteName('column') . ' = ' . $this->db->quote('jos_emundus_payment_transaction.id'));

		$this->db->setQuery($query);
		$this->db->execute();
	}

	public function tearDown(): void
	{
		$this->h_dataset->deleteSampleCart($this->dataset['fnum']);
		parent::tearDown();
	}
}