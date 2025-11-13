<?php

namespace Tchooz\Repositories\Payment;

use Joomla\CMS\Factory;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Payment\CurrencyEntity;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Factories\Payment\CurrencyFactory;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: 'data_currency')]
class CurrencyRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.currency.php'], Log::ALL, ['com_emundus.repository.currency']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * @param   int  $id
	 *
	 * @return CurrencyEntity|null
	 */
	public function getCurrencyById(int $id): ?CurrencyEntity
	{
		$currency = null;

		if (!empty($id)) {
			$query = $this->db->createQuery();
			$query->select('*')
				->from($this->db->quoteName($this->getTableName(self::class)))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

			try {
				$this->db->setQuery($query);
				$object = $this->db->loadObject();

				if ($object) {
					$currency = CurrencyFactory::fromDbObjects([$object])[0];
				}
			} catch (\Exception $e) {
				Log::add('Failed to load currency entity: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.currency');
			}
		}

		return $currency;
	}

	public function getCurrencies(int $lim = 10, int $page = 1, array $filters = []): array
	{
		$currency_list = [];

		$query = $this->db->createQuery();

		$offset = ($page - 1) * $lim;

		$query->select('*')
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where('1=1');

		if (!empty($filters)) {
			foreach ($filters as $key => $value) {
				if (is_array($value)) {
					$query->andWhere($this->db->quoteName($key) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
				} else {
					$query->andWhere($this->db->quoteName($key) . ' = ' . $this->db->quote($value));
				}
			}
		}

		$query->andWhere($this->db->quoteName('published') . ' = 1');
		$query->setLimit($lim, $offset);

		try {
			$this->db->setQuery($query);
			$currencies = $this->db->loadObjectList();

			if ($currencies) {
				$currency_list = CurrencyFactory::fromDbObjects($currencies);
			}
		} catch (\Exception $e) {
			Log::add('Error loading currencies: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.currency');
		}

		return $currency_list;
	}

}