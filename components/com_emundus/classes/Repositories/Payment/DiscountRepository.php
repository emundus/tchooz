<?php

namespace Tchooz\Repositories\Payment;

use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\DiscountEntity;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;

class DiscountRepository
{
	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.discount.php'], Log::ALL, ['com_emundus.repository.discount']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function countDiscounts(): int
	{
		$query = $this->db->createQuery();

		$query->select('COUNT(*)')
			->from($this->db->quoteName('jos_emundus_discount'));

		try {
			$this->db->setQuery($query);
			$count = $this->db->loadResult();
		} catch (\Exception $e) {
			Log::add('Error counting products: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.discount');
			return 0;
		}

		return (int)$count;
	}

	public function getDiscounts(int $lim = 10, int $page = 1, array $filters = []): array
	{
		$discount_list = [];

		$query = $this->db->createQuery();

		$offset = ($page - 1) * $lim;

		$query->select('*')
			->from($this->db->quoteName('jos_emundus_discount'));

		if (!empty($filters)) {
			foreach ($filters as $key => $value) {
				if (is_array($value)) {
					$query->where($this->db->quoteName($key) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
				} else {
					$query->where($this->db->quoteName($key) . ' = ' . $this->db->quote($value));
				}
			}
		}

		$query->setLimit($lim, $offset);

		try {
			$this->db->setQuery($query);
			$discounts = $this->db->loadObjectList();

			if ($discounts)
			{
				foreach ($discounts as $discount) {
					$discount_entity = new DiscountEntity(0);
					$discount_entity->setId($discount->id);
					$discount_entity->setLabel($discount->label);
					$discount_entity->setDescription($discount->description);
					$discount_entity->setValue($discount->value);
					$discount_entity->setType($discount->type);
					$discount_entity->setPublished($discount->published);

					$discount_list[] = $discount_entity;
				};
			}
		} catch (\Exception $e) {
			Log::add('Error loading products: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.discount');
		}

		return $discount_list;
	}

	public function getDiscountById(int $id): ?DiscountEntity
	{
		$discount_entity = null;

		if (!empty($id)) {
			$query = $this->db->createQuery();

			$query->select('*')
				->from($this->db->quoteName('jos_emundus_discount'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

			try {
				$this->db->setQuery($query);
				$discount = $this->db->loadObject();
				if ($discount)
				{
					$discount_entity = new DiscountEntity($discount->id);
					$discount_entity->setLabel($discount->label);
					$discount_entity->setDescription($discount->description);
					$discount_entity->setValue((float)$discount->value);
					$discount_entity->setType($discount->type);
					$discount_entity->setPublished($discount->published);
					$discount_entity->setQuantity($discount->quantity);
				}
			} catch (\Exception $e) {
				Log::add('Error loading discount: ' . $e->getMessage(), Log::ERROR, 'com_emundus.entity.discount');
			}
		}

		return $discount_entity;
	}

	public function flush(DiscountEntity $discount_entity): bool
	{
		$flushed = false;

		$query = $this->db->createQuery();

		if (empty($discount_entity->getLabel())) {
			throw new \InvalidArgumentException('Discount label is required');
		}

		if (empty($discount_entity->getValue())) {
			throw new \InvalidArgumentException('Discount value is required');
		}

		if ($discount_entity->getType() == 'fixed' && (empty($discount_entity->getCurrency()) || empty($discount_entity->getCurrency()->getId()))) {
			throw new \InvalidArgumentException('Fixed discount requires a currency');
		}

		if (!empty($discount_entity->getId())) {
			$query->update($this->db->quoteName('jos_emundus_discount'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($discount_entity->getLabel()))
				->set($this->db->quoteName('description') . ' = ' . $this->db->quote($discount_entity->getDescription()))
				->set($this->db->quoteName('value') . ' = ' . $this->db->quote($discount_entity->getValue()))
				->set($this->db->quoteName('type') . ' = ' . $this->db->quote($discount_entity->getType()->value))
				->set($this->db->quoteName('published') . ' = ' . $this->db->quote($discount_entity->getPublished()))
				->set($this->db->quoteName('quantity') . ' = ' . $this->db->quote($discount_entity->getQuantity()));

			if (!empty($discount_entity->getAvailableFrom())) {
				$query->set($this->db->quoteName('available_from') . ' = ' . $this->db->quote($discount_entity->getAvailableFrom()->format('Y-m-d H:i:s')));
			} else {
				$query->set($this->db->quoteName('available_from') . ' = NULL');
			}

			if (!empty($discount_entity->getAvailableTo())) {
				$query->set($this->db->quoteName('available_to') . ' = ' . $this->db->quote($discount_entity->getAvailableTo()->format('Y-m-d H:i:s')));
			} else {
				$query->set($this->db->quoteName('available_to') . ' = NULL');
			}

			if (!empty($discount_entity->getCurrency()) && !empty($discount_entity->getCurrency()->getId())) {
				$query->set($this->db->quoteName('currency_id') . ' = ' . $this->db->quote($discount_entity->getCurrency()->getId()));
			} else {
				$query->set($this->db->quoteName('currency_id') . ' = NULL');
			}

			$query->where($this->db->quoteName('id') . ' = ' . $this->db->quote($discount_entity->getId()));
		} else {
			$values =  [
				$this->db->quote($discount_entity->getLabel()),
				$this->db->quote($discount_entity->getDescription()),
				$this->db->quote($discount_entity->getValue()),
				$this->db->quote($discount_entity->getType()->value),
				$this->db->quote($discount_entity->getPublished()),
				$this->db->quote($discount_entity->getQuantity()),
				!empty($discount_entity->getCurrency()) && !empty($discount_entity->getCurrency()->getId()) ? $this->db->quote($discount_entity->getCurrency()->getId()) : 'NULL',
			];

			if (!empty($discount_entity->getAvailableFrom())) {
				$values[] = $this->db->quote($discount_entity->getAvailableFrom()->format('Y-m-d H:i:s'));
			} else {
				$values[] = 'NULL';
			}

			if (!empty($discount_entity->getAvailableTo())) {
				$values[] = $this->db->quote($discount_entity->getAvailableTo()->format('Y-m-d H:i:s'));
			} else {
				$values[] = 'NULL';
			}

			$query->insert($this->db->quoteName('jos_emundus_discount'))
				->columns($this->db->quoteName(['label', 'description', 'value', 'type', 'published', 'quantity', 'currency_id', 'available_from', 'available_to']))
				->values(implode(',', $values));
		}

		try {
			$this->db->setQuery($query);
			$flushed = $this->db->execute();
		} catch (\Exception $e) {
			Log::add('Error saving discount: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.discount');
		}

		return $flushed;
	}
	
	public function delete(int $discount_id): bool 
	{
		$deleted = false;
		
		if (!empty($discount_id)) 
		{
			$query = $this->db->createQuery();

			$query->delete($this->db->quoteName('jos_emundus_discount'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($discount_id));

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (\Exception $e) {
				Log::add('Error deleting discount: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.discount');
			}
		}
		
		return $deleted;
	}
}