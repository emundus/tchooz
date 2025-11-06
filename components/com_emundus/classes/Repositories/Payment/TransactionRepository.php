<?php

namespace Tchooz\Repositories\Payment;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\User\UserFactoryInterface;
use Tchooz\Entities\Automation\EventContextEntity;
use Tchooz\Entities\Automation\EventsDefinitions\onAfterEmundusTransactionUpdateDefinition;
use Tchooz\Entities\Contacts\ContactEntity;
use Tchooz\Entities\Payment\CartEntity;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Entities\Payment\PaymentStepEntity;
use Tchooz\Entities\Payment\TransactionStatus;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Tchooz\Entities\Payment\TransactionEntity;
use Tchooz\Repositories\Contacts\ContactRepository;
use Tchooz\Synchronizers\Payment\Sogecommerce;
use Tchooz\Synchronizers\Payment\Stripe;

class TransactionRepository
{
	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.transaction.php'], Log::ALL, ['com_emundus.repository.transaction']);

		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function getById(int $id, ?CartEntity $cart = null): ?TransactionEntity
	{
		$transaction_entity = null;

		if (!empty($id)) {
			$query = $this->db->getQuery(true);
			$query->select('transaction.*, reference.reference')
				->from($this->db->quoteName('#__emundus_payment_transaction', 'transaction'))
				->leftJoin($this->db->quoteName('#__emundus_external_reference', 'reference') . ' ON transaction.id = reference.intern_id AND reference.column = ' .  $this->db->quote('jos_emundus_payment_transaction.id'))
				->where('transaction.id = ' . $id);
			$this->db->setQuery($query);
			$transaction = $this->db->loadObject();

			if ($transaction) {
				$transaction_entity = $this->mountEntityFromObject($transaction, $cart);
			} else {
				Log::add(Text::_('COM_EMUNDUS_TRANSACTION_NOT_FOUND'), Log::ERROR, 'com_emundus.repository.transaction');
			}
		} else {
			Log::add(Text::_('COM_EMUNDUS_TRANSACTION_ID_EMPTY'), Log::ERROR, 'com_emundus.repository.transaction');
		}

		return $transaction_entity;
	}

	/**
	 * @param   array   $filters
	 * @param   string  $until_date
	 * @param   string  $search
	 *
	 * @return int
	 */
	public function countTransactions(array $filters = [], string $until_date = '', string $search = ''): int
	{
		$query = $this->db->createQuery();

		$query->select('COUNT(transaction.id)')
			->from($this->db->quoteName('jos_emundus_payment_transaction', 'transaction'))
			->where('1=1');

		if (!empty($filters)) {
			foreach ($filters as $key => $value) {
				if ($key === 'applicant_id') {
					$query->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'candidature') .  ' ON candidature.fnum = transaction.fnum');
					$query->andWhere('candidature.applicant_id = ' . $this->db->quote($value));
				} else {
					if (is_array($value)) {
						$query->andWhere($this->db->quoteName('transaction.' . $key) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
					} else {
						$query->andWhere($this->db->quoteName('transaction.' . $key) . ' = ' . $this->db->quote($value));
					}
				}
			}
		}

		if (!empty($until_date)) {
			$query->andWhere($this->db->quoteName('transaction.created_at') . ' <= ' . $this->db->quote($until_date));
		}

		if (!empty($search)) {
			$query->andWhere($this->db->quoteName('external_reference.reference') . ' LIKE ' . $this->db->quote('%'.$search.'%'));
		}

		try {
			$this->db->setQuery($query);
			$count = $this->db->loadResult();
		} catch (\Exception $e) {
			Log::add('Error counting transactions: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.transaction');
			return 0;
		}

		return (int)$count;
	}

	/**
	 * @param   int     $limit
	 * @param   int     $page
	 * @param   array   $filters
	 * @param   string  $until_date
	 *
	 * @return array
	 */
	public function getTransactions(int $limit = 10, int $page = 1, array $filters = [], string $until_date = '', string $search = ''): array
	{
		$transaction_entities = [];

		$query = $this->db->getQuery(true);
		$query->select('transaction.*, external_reference.reference')
			->from($this->db->quoteName('#__emundus_payment_transaction', 'transaction'))
			->leftJoin($this->db->quoteName('#__emundus_external_reference', 'external_reference') . ' ON external_reference.intern_id = transaction.id AND external_reference.column = ' . $this->db->quote('jos_emundus_payment_transaction.id'))
			->where('1=1');

		if (!empty($filters)) {
			foreach ($filters as $key => $value) {
				if ($key === 'applicant_id') {
					$query->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'candidature') .  ' ON candidature.fnum = transaction.fnum');
					$query->andWhere('candidature.applicant_id = ' . $this->db->quote($value));
				} else {
					if (is_array($value)) {
						$query->andWhere($this->db->quoteName('transaction.' . $key) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
					} else {
						$query->andWhere($this->db->quoteName('transaction.' . $key) . ' = ' . $this->db->quote($value));
					}
				}
			}
		}

		if (!empty($until_date)) {
			$query->andWhere($this->db->quoteName('transaction.created_at') . ' <= ' . $this->db->quote($until_date));
		}

		if (!empty($search)) {
			$query->andWhere($this->db->quoteName('external_reference.reference') . ' LIKE ' . $this->db->quote('%'.$search.'%'));
		}

		$query->order('transaction.created_at DESC, transaction.updated_at DESC');
		$query->setLimit($limit, ($page - 1) * $limit);

		try {
			$this->db->setQuery($query);
			$transactions = $this->db->loadObjectList();

			foreach ($transactions as $transaction) {
				$transaction_entity = $this->mountEntityFromObject($transaction);
				$transaction_entities[] = $transaction_entity;
			}
		} catch (\Exception $e) {
			Log::add(Text::_('COM_EMUNDUS_ERROR_LOADING_TRANSACTIONS') . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.transaction');
		}


		return $transaction_entities;
	}

	private function mountEntityFromObject(object $transaction, ?CartEntity $cart = null): TransactionEntity
	{
		$transaction_entity = new TransactionEntity($transaction->id);
		$transaction_entity->setStatus(TransactionStatus::from($transaction->status));
		$transaction_entity->setAmount($transaction->amount);
		$transaction_entity->setCreatedAt($transaction->created_at);
		$transaction_entity->setCreatedBy($transaction->created_by);
		$transaction_entity->setFnum($transaction->fnum);

		if (!empty($transaction->updated_at)) {
			$transaction_entity->setUpdatedAt($transaction->updated_at);
			$transaction_entity->setUpdatedBy($transaction->updated_by);
		}

		if (!empty($cart)) {
			$transaction_entity->setCartId($cart->getId());
		} else {
			$transaction_entity->setCartId($transaction->cart_id);
		}

		$currency_repository = new CurrencyRepository();
		$currency = $currency_repository->getCurrencyById($transaction->currency_id);
		$transaction_entity->setCurrency($currency);
		$transaction_entity->setPaymentMethod(new PaymentMethodEntity($transaction->payment_method_id));
		$transaction_entity->setSynchronizerId($transaction->synchronizer_id);
		$transaction_entity->setStepId($transaction->step_id);

		if (!empty($transaction->reference)) {
			$transaction_entity->setExternalReference($transaction->reference);
		} else {
			$transaction_entity->setExternalReference('');
		}

		if (!empty($transaction->data)) {
			$transaction_entity->setData($transaction->data);

			$data = json_decode($transaction->data);
			if (!empty($data->installment) && !empty($data->installment->number_installment_debit))
			{
				$transaction_entity->setNumberInstallmentDebit($data->installment->number_installment_debit);
			}
		}

		return $transaction_entity;
	}

	/**
	 * @param   TransactionEntity  $transaction
	 * @param   int                $user_id
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function saveTransaction(TransactionEntity $transaction, int $user_id): bool
	{
		$saved = false;

		$query = $this->db->createQuery();

		$old_data = [];

		if (empty($transaction->getId())) {
			$query->clear()
				->insert($this->db->quoteName('jos_emundus_payment_transaction'))
				->columns($this->db->quoteName(['cart_id', 'amount', 'currency_id', 'payment_method_id', 'status', 'synchronizer_id', 'created_at', 'created_by', 'updated_at', 'updated_by', 'step_id', 'data', 'fnum']))
				->values($this->db->quote($transaction->getCartId()) . ', ' . $this->db->quote($transaction->getAmount()) . ', ' . $this->db->quote($transaction->getCurrency()->getId()) . ', ' . $this->db->quote($transaction->getPaymentMethod()->getId()) . ', ' . $this->db->quote($transaction->getStatus()->value) . ', ' . $this->db->quote($transaction->getSynchronizerId()) . ', ' . $this->db->quote(date('Y-m-d H:i:s')) . ', ' . $this->db->quote($user_id) . ', ' . $this->db->quote(date('Y-m-d H:i:s')) . ', ' . $this->db->quote($user_id) . ', ' . $this->db->quote($transaction->getStepId()) . ', '. $this->db->quote($transaction->getData()) . ', ' . $this->db->quote($transaction->getFnum()));

			$old_data['status'] = null;
		}
		else
		{
			$query->clear()
				->select('transaction.*, reference.reference AS external_reference')
				->from($this->db->quoteName('jos_emundus_payment_transaction', 'transaction'))
				->leftJoin($this->db->quoteName('#__emundus_external_reference', 'reference') . ' ON transaction.id = reference.intern_id AND reference.column = ' .  $this->db->quote('jos_emundus_payment_transaction.id'))
				->where($this->db->quoteName('transaction.id') . ' = ' . $this->db->quote($transaction->getId()));

			$this->db->setQuery($query);
			$old_data = $this->db->loadAssoc();

			$query->clear()
				->update($this->db->quoteName('jos_emundus_payment_transaction'))
				->set($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($transaction->getCartId()))
				->set($this->db->quoteName('amount') . ' = ' . $this->db->quote($transaction->getAmount()))
				->set($this->db->quoteName('currency_id') . ' = ' . $this->db->quote($transaction->getCurrency()->getId()))
				->set($this->db->quoteName('payment_method_id') . ' = ' . $this->db->quote($transaction->getPaymentMethod()->getId()))
				->set($this->db->quoteName('status') . ' = ' . $this->db->quote($transaction->getStatus()->value))
				->set($this->db->quoteName('synchronizer_id') . ' = ' . $this->db->quote($transaction->getSynchronizerId()))
				->set($this->db->quoteName('updated_at') . ' = ' . $this->db->quote(date('Y-m-d H:i:s')))
				->set($this->db->quoteName('updated_by') . ' = ' . $this->db->quote($user_id))
				->set($this->db->quoteName('step_id') . ' = ' . $this->db->quote($transaction->getStepId()))
				->set($this->db->quoteName('fnum') . ' = ' . $this->db->quote($transaction->getFnum()))
				->set($this->db->quoteName('data') . ' = ' . $this->db->quote($transaction->getData()))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($transaction->getId()));
		}

		try {
			$this->db->setQuery($query);
			$saved = $this->db->execute();

			if ($saved) {
				if (empty($transaction->getId())) {
					$transaction->setId($this->db->insertid());
				}

				$saved = $this->saveTransactionReference($transaction);
			}
		} catch (\Exception $e) {
			Log::add('Error saving transaction: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.transaction');
			throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SAVING_TRANSACTION'));
		}

		if ($saved) {
			$payment_repository = new PaymentRepository();

			if (!empty($old_data)) {
				if ($old_data['status'] !== $transaction->getStatus()->value) {
					switch ($transaction->getStatus()) {
						case TransactionStatus::CONFIRMED:
							try {
								$payment_step = $payment_repository->getPaymentStepById($transaction->getStepId());

								if (!is_null($payment_step->getOutputStatus()))
								{
									if (!class_exists('EmundusModelFiles')) {
										require_once (JPATH_ROOT . '/components/com_emundus/models/files.php');
									}
									$m_files = new \EmundusModelFiles();
									$m_files->updateState([$transaction->getFnum()], $payment_step->getOutputStatus(), $user_id);
								}

								$cart_repository = new CartRepository();
								$cart = $cart_repository->getCartById($transaction->getCartId());
								$reset = $cart_repository->resetCart($cart, $user_id);

								if (!$reset)
								{
									Log::add('Failed to reset cart ' . $transaction->getCartId(), Log::ERROR, 'com_emundus.repository.transaction');
								}
							} catch (\Exception $e) {
								Log::add('Error updating transaction data: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.transaction');
							}

							break;
					}
				}

				$details = ['updated' => []];

				if ($old_data['status'] != $transaction->getStatus()->value) {
					$details['updated'][] = ['old' => $old_data['status'], 'new' => $transaction->getStatus()->value];
				}

				if ($old_data['external_reference'] != $transaction->getExternalReference()) {
					$details['updated'][] = ['old' => !empty($old_data['external_reference']) ? $old_data['external_reference'] : '' , 'new' => $transaction->getExternalReference()];
				}

				if ($old_data['amount'] != $transaction->getAmount()) {
					$details['updated'][] = ['old' => $old_data['amount'], 'new' => $transaction->getAmount()];
				}
			} else {
				$details = [];
			}

			if (!class_exists('EmundusModelLogs')) {
				require_once (JPATH_ROOT . '/components/com_emundus/models/logs.php');
			}
			if (!class_exists('EmundusModelFiles')) {
				require_once (JPATH_ROOT . '/components/com_emundus/helpers/files.php');
			}
			$applicant_id = \EmundusHelperFiles::getApplicantIdFromFnum($transaction->getFnum());
			\EmundusModelLogs::log($user_id, $applicant_id, $transaction->getFnum(), $payment_repository->getActionId(), 'u', 'COM_EMUNDUS_TRANSACTION_SAVED', json_encode($details));

			PluginHelper::importPlugin('emundus');
			$dispatcher = Factory::getApplication()->getDispatcher();
			$onAfterEmundusCartUpdate = new GenericEvent('onCallEventHandler', [
				'onAfterEmundusTransactionUpdate',
				[
					'fnum' => $transaction->getFnum(),
					'transaction' => $transaction,
					'context' => new EventContextEntity(
						Factory::getContainer()->get(UserFactoryInterface::class)->loadUserById($user_id),
						[$transaction->getFnum()],
						[],
						[
							onAfterEmundusTransactionUpdateDefinition::TRANSACTION_STATUS_PARAMETER => $transaction->getStatus()->value,
							onAfterEmundusTransactionUpdateDefinition::OLD_TRANSACTION_STATUS_PARAMETER => !empty($old_data['status']) ? $old_data['status'] : null,
							onAfterEmundusTransactionUpdateDefinition::TRANSACTION_STEP_ID_PARAMETER => $transaction->getStepId(),
						]
					)
				]
			]);
			$dispatcher->dispatch('onCallEventHandler', $onAfterEmundusCartUpdate);
		}

		return $saved;
	}

	private function saveTransactionReference(TransactionEntity $transaction): bool
	{
		$saved = false;

		if (!empty($transaction->getId())) {
			if (empty($transaction->getExternalReference())) {
				$transaction->generateExternalReference();
			}

			$query = $this->db->createQuery();
			$query->clear()
				->select('id')
				->from($this->db->quoteName('#__emundus_external_reference'))
				->where($this->db->quoteName('intern_id') . ' = ' . $this->db->quote($transaction->getId()))
				->where($this->db->quoteName('column') . ' = ' . $this->db->quote('jos_emundus_payment_transaction.id'));

			$this->db->setQuery($query);
			$external_reference = $this->db->loadResult();

			if (empty($external_reference)) {
				$query->clear()
					->insert($this->db->quoteName('#__emundus_external_reference'))
					->columns($this->db->quoteName(['intern_id', 'column', 'reference']))
					->values($this->db->quote($transaction->getId()) . ', ' . $this->db->quote('jos_emundus_payment_transaction.id') . ', ' . $this->db->quote($transaction->getExternalReference()));
			} else {
				$query->clear()
					->update($this->db->quoteName('#__emundus_external_reference'))
					->set($this->db->quoteName('reference') . ' = ' . $this->db->quote($transaction->getExternalReference()))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($external_reference));
			}

			try {
				$this->db->setQuery($query);
				$saved = $this->db->execute();
			} catch (\Exception $e) {
				Log::add('Error saving transaction reference: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.transaction');
				throw new \Exception(Text::_('COM_EMUNDUS_ERROR_SAVING_TRANSACTION'));
			}
		}

		return $saved;
	}

	public function getTransactionByCart(CartEntity $cart): ?TransactionEntity
	{
		$transaction = null;

		if (!empty($cart->getId()))
		{
			$query = $this->db->createQuery();

			$query->select('id')
				->from($this->db->quoteName('#__emundus_payment_transaction'))
				->where($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($cart->getId()))
				->andWhere($this->db->quoteName('step_id') . ' = ' . $this->db->quote($cart->getPaymentStep()->getId()))
				->order($this->db->quoteName('created_at') . ' DESC')
				->setLimit(1);

			$this->db->setQuery($query);
			$transaction_id = $this->db->loadResult();

			if (!empty($transaction_id)) {
				$transaction = $this->getById($transaction_id, $cart);
			}
		}

		return $transaction;
	}

	public function getTransactionByCartAndStep(CartEntity $cart, int $step_id, TransactionStatus $transaction_status = TransactionStatus::CONFIRMED): ?TransactionEntity
	{
		$transaction = null;

		if (!empty($cart->getId()) && !empty($step_id))
		{
			$query = $this->db->createQuery();

			$query->select('id')
				->from($this->db->quoteName('#__emundus_payment_transaction'))
				->where($this->db->quoteName('cart_id') . ' = ' . $this->db->quote($cart->getId()))
				->andWhere($this->db->quoteName('step_id') . ' = ' . $this->db->quote($step_id))
				->andWhere($this->db->quoteName('status') . ' = ' . $this->db->quote($transaction_status->value))
				->order($this->db->quoteName('created_at') . ' DESC')
				->setLimit(1);

			$this->db->setQuery($query);
			$transaction_id = $this->db->loadResult();

			if (!empty($transaction_id)) {
				$transaction = $this->getById($transaction_id, $cart);
			}
		}

		return $transaction;
	}

	public function getServiceLabel(int $transaction_sync_id): string
	{
		$name = '';

		if (!empty($transaction_sync_id))
		{
			$query = $this->db->createQuery();
			$query->select($this->db->quoteName('name'))
				->from($this->db->quoteName('#__emundus_setup_sync'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($transaction_sync_id));

			$this->db->setQuery($query);
			$name = $this->db->loadResult();
		}

		return $name;
	}

	/**
	 * @param   string  $fnum
	 *
	 * @return string
	 */
	public function getCampaignLabel(string $fnum): string
	{
		$label = '';

		if (!empty($fnum))
		{
			$query = $this->db->createQuery();

			$query->select($this->db->quoteName('label'))
				->from($this->db->quoteName('#__emundus_setup_campaigns', 'esc'))
				->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'ecc') . ' ON ' . $this->db->quoteName('ecc.campaign_id') . ' = ' . $this->db->quoteName('esc.id'))
				->where($this->db->quoteName('fnum') . ' = ' . $this->db->quote($fnum));

			$this->db->setQuery($query);
			$label = $this->db->loadResult();
		}

		return $label;
	}

	public function getTransactionIdByExternalReference(string $external_reference): int
	{
		$transaction_id = 0;

		if (!empty($external_reference))
		{
			$query = $this->db->createQuery();
			$query->select('intern_id')
				->from($this->db->quoteName('#__emundus_external_reference'))
				->where($this->db->quoteName('reference') . ' = ' . $this->db->quote($external_reference))
				->andWhere($this->db->quoteName('column') . ' = ' . $this->db->quote('jos_emundus_payment_transaction.id'));

			$this->db->setQuery($query);
			$transaction_id = $this->db->loadResult();
		}

		return $transaction_id;
	}

	/**
	 * @param   array  $data (payload)
	 * @param   string  $external_reference
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function addTransactionToQueue(array $data, string $external_reference, int $synchronizer_id): bool
	{
		$added = false;

		if (!empty($data))
		{
			$query = $this->db->createQuery();

			$transaction_id = $this->getTransactionIdByExternalReference($external_reference);

			if (!empty($transaction_id)) {
				$query->clear()
					->insert($this->db->quoteName('#__emundus_payment_queue'))
					->columns($this->db->quoteName(['created_at', 'transaction_id', 'data', 'sync_id', 'status']))
					->values($this->db->quote(date('Y-m-d H:i:s')) . ',' .
						$this->db->quote($transaction_id) . ',' .
						$this->db->quote(json_encode($data)) . ',' .
						$this->db->quote($synchronizer_id) . ',' .
						$this->db->quote('pending'));

				$this->db->setQuery($query);
				$added = $this->db->execute();

				$query->clear()
					->update($this->db->quoteName('#__emundus_payment_transaction'))
					->set($this->db->quoteName('status') . ' = ' . $this->db->quote(TransactionStatus::WAITING->value))
					->where($this->db->quoteName('id') . ' = ' . $this->db->quote($transaction_id));

				$this->db->setQuery($query);
				$this->db->execute();
			} else {
				Log::add('No transaction found for this external reference', Log::ERROR, 'com_emundus.repository.transaction');
				Throw new \Exception('No transaction found for this external reference ' . $external_reference);
			}
		} else {
			Log::add('No data to add to queue', Log::ERROR, 'com_emundus.repository.transaction');
		}

		return $added;
	}

	/**
	 * @param   array  $transaction_ids
	 *
	 * @return array
	 */
	public function getTransactionsInQueue(array $status = ['pending'], array $transaction_ids = [], array $synchronizer_ids = [], int $limit = 0, int $page = 1): array
	{
		$rows = [];

		try {
			$query = $this->db->createQuery();
			$query->select('queue.*, ess.type as sync_type')
				->from($this->db->quoteName('#__emundus_payment_queue', 'queue'))
				->leftJoin($this->db->quoteName('#__emundus_setup_sync', 'ess') . ' ON ' . $this->db->quoteName('queue.sync_id') . ' = ' . $this->db->quoteName('ess.id'))
				->where($this->db->quoteName('queue.status') . ' IN (' . implode(',', $this->db->quote($status)) . ')');

			if (!empty($transaction_ids)) {
				$query->andWhere($this->db->quoteName('queue.transaction_id') . ' IN (' . implode(',', $this->db->quote($transaction_ids)) . ')');
			}

			if (!empty($synchronizer_ids)) {
				$query->andWhere($this->db->quoteName('queue.sync_id') . ' IN (' . $this->db->quote(implode(',', $synchronizer_ids)) . ')');
			}

			$query->order('queue.created_at ASC');
			$this->db->setQuery($query);

			if (!empty($limit)) {
				$query->setLimit($limit, ($page - 1) * $limit);
			}

			$rows = $this->db->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Failed to get pending transactions queue ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.transaction');
		}

		return $rows;
	}

	/**
	 * @param $rows
	 *
	 * @return bool
	 * @throws \Exception
	 */
	public function manageQueueTransactions(array $rows): bool
	{
		$managed = false;

		if (!empty($rows)) {
			$config = ComponentHelper::getParams('com_emundus');
			$automated_task_user = (int)$config->get('automated_task_user', 0);
			$query = $this->db->createQuery();
			$updates = [];
			foreach ($rows as $row) {
				if (!empty($row->sync_type)) {
					$data = json_decode($row->data, true);

					switch($row->sync_type) {
						case 'sogecommerce':
							$sogecommerce = new Sogecommerce();
							$updated = $sogecommerce->updateTransactionFromCallback($data, $row->transaction_id, $automated_task_user);
							break;
						case 'stripe':
							$stripe = new Stripe();
							$updated = $stripe->updateTransactionFromCallback($data, $row->transaction_id, $automated_task_user);
							break;
						default:
							Log::add('Unknown sync type: ' . $row->sync_type, Log::ERROR, 'com_emundus.repository.transaction');
							continue 2;
					}

					if ($updated) {
						$query->update($this->db->quoteName('#__emundus_payment_queue', 'queue'))
							->set('status = ' . $this->db->quote('updated'))
							->where($this->db->quoteName('queue.id') . ' = ' . $this->db->quote($row->id));

						$this->db->setQuery($query);
						$updated = $this->db->execute();
					} else {
						Log::add('Failed to update transaction from queue id ' . $row->id, Log::ERROR, 'com_emundus.repository.transaction');
					}

					$updates[] = $updated;
				}
			}

			$managed = !in_array(false, $updates);
		}

		return $managed;
	}

	public function getTransactionCustomer(TransactionEntity $transaction): ?ContactEntity
	{
		$customer = null;

		if (!empty($transaction->getFnum()))
		{
			$query = $this->db->createQuery();
			$query->select('applicant_id')
				->from('#__emundus_campaign_candidature')
				->where('fnum = ' . $this->db->quote($transaction->getFnum()));

			$this->db->setQuery($query);
			$applicant_id = $this->db->loadResult();

			$contact_repository = new ContactRepository();
			$customer = $contact_repository->getByUserId($applicant_id);
		}

		return $customer;
	}

	public function getTransactionsApplicants(): array
	{
		$applicants = [];

		$query = $this->db->createQuery();

		$query->select('DISTINCT(candidature.applicant_id) as value, CONCAT(contact.firstname, " ", contact.lastname) as label')
			->from($this->db->quoteName('#__emundus_contacts', 'contact'))
			->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'candidature') . ' ON candidature.applicant_id = contact.user_id')
			->leftJoin($this->db->quoteName('#__emundus_payment_transaction', 'transaction') . ' ON transaction.fnum = candidature.fnum')
			->where($this->db->quoteName('transaction.id') . ' IS NOT NULL')
			->andWhere('candidature.published = 1');

		try {
			$this->db->setQuery($query);
			$applicants = $this->db->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Failed to get applicants list: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.transaction');
		}

		return $applicants;
	}

	public function getTransactionsFileNumbers(): array {
		$fnums = [];

		$query = $this->db->createQuery();

		$query->select('DISTINCT(transaction.fnum) as value, transaction.fnum as label')
			->from($this->db->quoteName('#__emundus_payment_transaction', 'transaction'))
			->leftJoin($this->db->quoteName('#__emundus_campaign_candidature', 'candidature') . ' ON candidature.fnum = transaction.fnum')
			->where($this->db->quoteName('transaction.fnum') . ' IS NOT NULL')
			->andWhere('candidature.published = 1');

		try {
			$this->db->setQuery($query);
			$fnums = $this->db->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Failed to get file numbers list: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.transaction');
		}

		return $fnums;
	}

	/**
	 * @param   array<TransactionEntity>  $transactions
	 *
	 * @return array
	 */
	public function prepareExport(array $transactions): array
	{
		$lines = [];

		if (!empty($transactions)) {
			$lines[] = [
				Text::_('COM_EMUNDUS_TRANSACTION_ID'),
				Text::_('COM_EMUNDUS_TRANSACTION_EXTERNAL_REFERENCE'),
				Text::_('COM_EMUNDUS_TRANSACTION_FNUM'),
				Text::_('COM_EMUNDUS_TRANSACTION_APPLICANT'),
				Text::_('COM_EMUNDUS_TRANSACTION_APPLICANT_ID'),
				Text::_('COM_EMUNDUS_CAMPAIGN'),
				Text::_('COM_EMUNDUS_TRANSACTION_AMOUNT'),
				Text::_('COM_EMUNDUS_TRANSACTION_DATE'),
				Text::_('COM_EMUNDUS_TRANSACTION_SYNCHRONIZER'),
				Text::_('COM_EMUNDUS_TRANSACTION_PAYMENT_METHOD'),
				Text::_('COM_EMUNDUS_TRANSACTION_INSTALLMENT_NUMBER_DEBIT'),
				Text::_('COM_EMUNDUS_TRANSACTION_STATUS'),
				Text::_('COM_EMUNDUS_TRANSACTION_ITEM_ID'),
				Text::_('COM_EMUNDUS_TRANSACTION_PRODUCT_LABEL'),
				Text::_('COM_EMUNDUS_TRANSACTION_PRODUCT_PRICE'),
				Text::_('COM_EMUNDUS_TRANSACTION_PRODUCT_DESCRIPTION'),
			];

			foreach ($transactions as $transaction) {
				assert($transaction instanceof TransactionEntity);

				// a line by product
				$data = $transaction->getData();
				$data = json_decode($data);
				$customer = $this->getTransactionCustomer($transaction);
				$campaign_label = $this->getCampaignLabel($transaction->getFnum());
				$default_line_content = [
					$transaction->getId(),
					$transaction->getExternalReference(),
					$transaction->getFnum(),
					$customer ? $customer->getFullName() : '',
					$customer ? $customer->getUserId() : '',
					$campaign_label,
					$transaction->getAmount() . ' ' . $transaction->getCurrency()->getSymbol(),
					$transaction->getCreatedAt(true),
					$this->getServiceLabel($transaction->getSynchronizerId()),
					$transaction->getPaymentMethod()->getLabel(),
					$transaction->getNumberInstallmentDebit(),
					$transaction->getStatus()->getLabel(),
				];

				if (!empty($data->products)) {
					foreach ($data->products as $product) {
						$lines[] = [
							...$default_line_content,
							$product->id,
							$product->label,
							$product->displayed_price,
							$product->description
						];
					}
				}

				if (!empty($data->alterations)) {
					foreach ($data->alterations as $alteration) {
						$lines[] = [
							...$default_line_content,
							$alteration->id ?? '',
							Text::_('COM_EMUNDUS_DISCOUNT'),
							$alteration->displayed_amount . ' ' . $transaction->getCurrency()->getSymbol(),
							$alteration->description
						];
					}
				}
			}
		}

		return $lines;
	}

	public function apiRender(TransactionEntity $transaction_entity): array
	{
		$transaction = [];
		$payment_repository = new PaymentRepository();
		$contact_repository = new ContactRepository();

		if(!class_exists('EmundusHelperFiles')) {
			require_once(JPATH_ROOT . '/components/com_emundus/helpers/files.php');
		}

		$transaction = [
			'id' => $transaction_entity->getId(),
			'fnum' => $transaction_entity->getFnum(),
			'contact' => $contact_repository->getByUserId(\EmundusHelperFiles::getApplicantIdFromFnum($transaction_entity->getFnum()))?->__serialize(),
			'external_reference' => $transaction_entity->getExternalReference(),
			'status' => $transaction_entity->getStatus()->value,
			'amount' => $transaction_entity->getAmount(),
			'currency' => $transaction_entity->getCurrency()->serialize(),
			'payment_method' => $transaction_entity->getPaymentMethod()->serialize(),
			'created_at' => $transaction_entity->getCreatedAt(),
			'created_by' => $transaction_entity->getCreatedBy(),
			'updated_at' => $transaction_entity->getUpdatedAt(),
			'updated_by' => $transaction_entity->getUpdatedBy(),
			'synchronizer' => $payment_repository->getPaymentServiceById($transaction_entity->getSynchronizerId()),
			'step_id' => $transaction_entity->getStepId(),
		];

		$data = json_decode($transaction_entity->getData(), true);
		if (!empty($data)) {
			if (!empty($data['products'])) {
				$transaction['products'] = array_map(function($product) {
					unset($product['campaigns']);
					unset($product['displayed_price']);
					unset($product['available_to']);
					unset($product['available_from']);
					unset($product['illimited']);
					unset($product['mandatory']);
					unset($product['quantity']);
					unset($product['published']);
					return $product;
				}, $data['products']);
			} else {
				$transaction['products'] = [];
			}

			if (!empty($data['alterations'])) {
				$transaction['alterations'] = array_map(function($alteration) {
					unset($alteration['id']);
					unset($alteration['cart_id']);
					unset($alteration['displayed_amount']);
					return $alteration;
				}, $data['alterations']);
			} else {
				$transaction['alterations'] = [];
			}

			if (!empty($data['installment'])) {
				$transaction['installment'] = $data['installment'];
			} else {
				$transaction['installment'] = null;
			}
		}

		return $transaction;
	}
}