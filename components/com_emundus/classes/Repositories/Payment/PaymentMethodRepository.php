<?php

namespace Tchooz\Repositories\Payment;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Payment\PaymentMethodEntity;
use Tchooz\Factories\Payment\PaymentMethodFactory;
use Joomla\Database\DatabaseInterface;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_setup_payment_method')]
class PaymentMethodRepository
{
	use TraitTable;

	private PaymentMethodFactory $factory;

	private DatabaseInterface $db;


	public function __construct()
	{
		$this->factory = new PaymentMethodFactory();
		$this->db = Factory::getContainer()->get(DatabaseInterface::class);
		Log::addLogger(['text_file' => 'com_emundus.repository.paymentmethod.php'], Log::ALL, ['com_emundus.repository.paymentmethod']);
	}


	/**
	 * Retrieve all payment methods
	 * @return array<PaymentMethodEntity>
	 */
	public function getAll(int $limit = 25, int $page = 1, array $filters = []): array
	{
		$methods = [];

		try {
			$query = $this->db->createQuery();
			$query->select('pmethod.*, GROUP_CONCAT(pmsync.service_id SEPARATOR ",") as services')
				->from($this->db->quoteName($this->getTableName(self::class), 'pmethod'))
				->leftJoin($this->db->quoteName('#__emundus_setup_payment_method_sync', 'pmsync') . ' ON pmsync.payment_method_id = pmethod.id')
				->where($this->db->quoteName('pmethod.published') . ' = 1');

			if (!empty($limit))
			{
				$offset = ($page - 1) * $limit;
				$query->setLimit($limit, $offset);
			}

			$query->group('pmethod.id');
			$this->db->setQuery($query);
			$methods = $this->db->loadObjectList();

			if (!empty($methods)) {
				$methods = $this->factory->fromDbObjects($methods);
			}
		} catch (\Exception $e) {
			Log::add('Failed to load payment methods: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.paymentmethod');
		}

		return $methods;
	}

	/**
	 * @param   int  $id
	 *
	 * @return ?PaymentMethodEntity
	 */
	public function getById(int $id): ?PaymentMethodEntity
	{
		$method = null;

		if (!empty($id))
		{
			$query = $this->db->createQuery();
			$query->select('pmethod.*, GROUP_CONCAT(pmsync.service_id SEPARATOR ",") as services')
				->from($this->db->quoteName($this->getTableName(self::class), 'pmethod'))
				->leftJoin($this->db->quoteName('#__emundus_setup_payment_method_sync', 'pmsync') . ' ON pmsync.payment_method_id = pmethod.id')
				->where($this->db->quoteName('pmethod.id') . ' = ' . $this->db->quote($id));

			try {
				$this->db->setQuery($query);
				$object = $this->db->loadObject();

				if (!empty($object)) {
					$methods = $this->factory->fromDbObjects([$object]);
					$method = $methods[0];
				}
			} catch (\Exception $e) {
				Log::add('Failed to load payment method by ID: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.paymentmethod');
			}
		}

		return $method;
	}
}