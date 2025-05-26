<?php

namespace Tchooz\Repositories\Payment;

use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\ProductCategoryEntity;
use Joomla\CMS\Log\Log;

class CurrencyRepository
{

	/**
	 * @var \Joomla\CMS\Factory
	 */
	private $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.currency.php'], Log::ALL, ['com_emundus.repository.currency']);
		$this->db = \Joomla\CMS\Factory::getContainer()->get('DatabaseDriver');
	}

	public function getCurrencyById(int $id): ?CurrencyEntity
	{
		$currency = null;

		if (!empty($id)) {
			$currency = new CurrencyEntity($id);
		}

		return $currency;
	}

	public function getCurrencies(int $lim = 10, int $page = 1, array $filters = []): array
	{
		$currency_list = [];

		$query = $this->db->createQuery();

		$offset = ($page - 1) * $lim;

		$query->select('*')
			->from($this->db->quoteName('data_currency'))
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
				foreach ($currencies as $currency) {
					$currency_entity = new CurrencyEntity(0, $currency->name, $currency->symbol, $currency->iso3, 1);
					$currency_entity->setId($currency->id);
					$currency_list[] = $currency_entity;
				}
			}
		} catch (\Exception $e) {
			Log::add('Error loading currencies: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.currency');
		}

		return $currency_list;
	}

}