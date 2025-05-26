<?php

namespace Tchooz\Repositories\Payment;

use Tchooz\Entities\Payment\ProductEntity;
use Tchooz\Entities\Payment\CurrencyEntity;
use Tchooz\Entities\Payment\ProductCategoryEntity;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;

class ProductRepository
{
	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.product.php'], Log::ALL, ['com_emundus.repository.product']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}


	public function getProductById(int $id): ?ProductEntity
	{
		$product = null;

		if (!empty($id)) {
			$product = new ProductEntity($id);
		}

		return $product;
	}

	public function countProducts(): int
	{
		$query = $this->db->createQuery();

		$query->select('COUNT(*)')
			->from($this->db->quoteName('jos_emundus_product'));

		try {
			$this->db->setQuery($query);
			$count = $this->db->loadResult();
		} catch (\Exception $e) {
			Log::add('Error counting products: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product');
			return 0;
		}

		return (int)$count;
	}

	public function getProducts(int $lim = 10, int $page = 1, array $filters = [], $search = ''): array
	{
		$product_list = [];

		$query = $this->db->createQuery();

		$offset = ($page - 1) * $lim;

		$query->select('p.*')
			->from($this->db->quoteName('jos_emundus_product', 'p'))
			->leftJoin($this->db->quoteName('jos_emundus_product_category', 'c') . ' ON c.id = p.category_id')
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

		if (!empty($search)) {
			$query->andWhere($this->db->quoteName('p.description') . ' LIKE ' . $this->db->quote('%' . $search . '%') . ' OR ' . $this->db->quoteName('p.label') . ' LIKE ' . $this->db->quote('%' . $search . '%') . ' OR ' . $this->db->quoteName('c.label') . ' LIKE ' . $this->db->quote('%' . $search . '%'));
		}

		$query->setLimit($lim, $offset);

		try {
			$this->db->setQuery($query);
			$products = $this->db->loadObjectList();

			if ($products)
			{
				$currencies = [];
				$categories = [];

				foreach ($products as $product) {
					$productEntity = new ProductEntity(0);
					$productEntity->setId($product->id);
					$productEntity->label = $product->label;
					$productEntity->description = $product->description;
					$productEntity->price = $product->price;
					$productEntity->quantity = $product->quantity ?? -1;
					$productEntity->illimited = $product->illimited == 1;
					$productEntity->available_from = new \DateTime($product->available_from);
					$productEntity->available_to = new \DateTime($product->available_to);
					$productEntity->published = $product->published == 1;

					if (!isset($currencies[$product->currency_id])) {
						$currency = new CurrencyEntity($product->currency_id);
						$currencies[$product->currency_id] = $currency;
					} else {
						$currency = $currencies[$product->currency_id];
					}
					$productEntity->setCurrency($currency);

					if (!empty($product->category_id)) {
						if (!isset($categories[$product->category_id])) {
							$category = new ProductCategoryEntity($product->category_id);
							$categories[$product->category_id] = $category;
						} else {
							$category = $categories[$product->category_id];
						}
						$productEntity->setCategory($category);
					}

					$product_list[] = $productEntity;
				};
			}
		} catch (\Exception $e) {
			Log::add('Error loading products: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product');
		}

		return $product_list;
	}


	/**
	 * @param   ProductEntity  $product
	 *
	 * @return int
	 */
	public function flush(ProductEntity $product): int
	{
		$product_id = 0;

		$query = $this->db->createQuery();

		if (!empty($product->getId()))
		{
			$query->update($this->db->quoteName('jos_emundus_product'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($product->label))
				->set($this->db->quoteName('description') . ' = ' . $this->db->quote($product->description))
				->set($this->db->quoteName('price') . ' = ' . $this->db->quote($product->price))
				->set($this->db->quoteName('currency_id') . ' = ' . $this->db->quote($product->currency->getId()))
				->set($this->db->quoteName('quantity') . ' = ' . $this->db->quote($product->quantity))
				->set($this->db->quoteName('illimited') . ' = ' . $this->db->quote($product->illimited ? 1 : 0))
				->set($this->db->quoteName('published') . ' = ' . $this->db->quote($product->published ? 1 : 0))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($product->getId()));

			if (!empty($product->available_from)) {
				$query->set($this->db->quoteName('available_from') . ' = ' . $this->db->quote($product->available_from->format('Y-m-d H:i:s')));
			} else {
				$query->set($this->db->quoteName('available_from') . ' = NULL');
			}

			if (!empty($product->available_to)) {
				$query->set($this->db->quoteName('available_to') . ' = ' . $this->db->quote($product->available_to->format('Y-m-d H:i:s')));
			} else {
				$query->set($this->db->quoteName('available_to') . ' = NULL');
			}

			if ($product->category) {
				$query->set($this->db->quoteName('category_id') . ' = ' . $this->db->quote($product->category->getId()));
			}

			try {
				$this->db->setQuery($query);
				$saved = $this->db->execute();

				if ($saved) {
					// clean campaigns
					$query->clear()
						->delete('#__emundus_product_campaigns')
						->where('product_id = ' . $product->getId());
					$this->db->setQuery($query);
					$this->db->execute();

					if (!empty($product->getCampaigns())) {
						foreach ($product->getCampaigns() as $campaign_id) {
							$query->clear()
								->insert('#__emundus_product_campaigns')
								->columns('product_id, campaign_id')
								->values($product->getId() . ', ' . $campaign_id);

							$this->db->setQuery($query);
							$this->db->execute();
						}
					}
				}
			} catch (\Exception $e) {
				Log::add('Error saving product: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product');
			}

		}
		else
		{
			$columns = ['label', 'price', 'description', 'currency_id'];
			$values = [$this->db->quote($product->label), $this->db->quote($product->price), $this->db->quote($product->description), $this->db->quote($product->getCurrency()->getId())];

			if ($product->category) {
				$columns[] = 'category_id';
				$values[] = $this->db->quote($product->category->getId());
			}

			$query->insert($this->db->quoteName('jos_emundus_product'))
				->columns($this->db->quoteName($columns))
				->values(implode(',', $values));

			try
			{
				$this->db->setQuery($query);
				$inserted = $this->db->execute();

				if ($inserted) {
					$product->setId($this->db->insertid());

					if (!empty($product->getCampaigns())) {
						foreach ($product->getCampaigns() as $campaign_id) {
							$query->clear()
								->insert('#__emundus_product_campaigns')
								->columns('product_id, campaign_id')
								->values($product->getId() . ', ' . $campaign_id);

							$this->db->setQuery($query);
							$this->db->execute();
						}
					}

					$saved = true;
				}
			}
			catch (\Exception $e)
			{
				Log::add('Error saving product: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product');
			}
		}

		if ($saved) {
			$product_id = $product->getId();
		}

		return $product_id;
	}

	public function delete(int $product_id): bool
	{
		$deleted = false;

		if (!empty($product_id)) {
			$query = $this->db->createQuery();

			$query->delete($this->db->quoteName('jos_emundus_product'))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($product_id));

			try {
				$this->db->setQuery($query);
				$deleted = $this->db->execute();
			} catch (\Exception $e) {
				Log::add('Error deleting product: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product');
			}
		}

		return $deleted;
	}
}