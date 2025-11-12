<?php

namespace Tchooz\Repositories\Payment;

use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Payment\ProductEntity;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Factories\Payment\ProductFactory;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_product')]
class ProductRepository
{
	use TraitTable;

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
			$query = $this->db->createQuery();

			$query->select('product.*, GROUP_CONCAT(product_campaigns.campaign_id SEPARATOR ",") as campaigns')
				->from($this->db->quoteName($this->getTableName(self::class), 'product'))
				->leftJoin($this->db->quoteName('jos_emundus_product_campaigns', 'product_campaigns') . ' ON product_campaigns.product_id = product.id')
				->where($this->db->quoteName('product.id') . ' = ' . $this->db->quote($id));

			try {
				$this->db->setQuery($query);
				$object = $this->db->loadObject();

				if (!empty($object))
				{
					$product = ProductFactory::fromDbObjects([$object])[0];
				}
			} catch (\Exception $e) {
				Log::add('Failed to load entity ' . $e->getMessage(), Log::ERROR, 'com_emundus.entity.product');
			}
		}

		return $product;
	}

	/**
	 * @param   array   $filters
	 * @param   object  $query
	 *
	 * @return void
	 */
	private function applyFilters(array $filters, object $query): void
	{
		if (!empty($filters))
		{
			foreach ($filters as $key => $value)
			{
				if (!empty($value))
				{
					if ($key === 'search') {
						$query->andWhere($this->db->quoteName('p.description') . ' LIKE ' . $this->db->quote('%' . $value . '%') . ' OR ' . $this->db->quoteName('p.label') . ' LIKE ' . $this->db->quote('%' . $value . '%') . ' OR ' . $this->db->quoteName('c.label') . ' LIKE ' . $this->db->quote('%' . $value . '%'));
					} else {
						if (is_array($value)) {
							$query->andWhere($this->db->quoteName($key) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
						} else {
							$query->andWhere($this->db->quoteName($key) . ' = ' . $this->db->quote($value));
						}
					}
				}
			}
		}
	}

	/**
	 * @param   array  $filters
	 *
	 * @return int
	 */
	public function countProducts(array $filters = []): int
	{
		$query = $this->db->createQuery();

		$query->select('COUNT(*)')
			->from($this->db->quoteName($this->getTableName(self::class), 'p'))
			->leftJoin($this->db->quoteName('jos_emundus_product_category', 'c') . ' ON c.id = p.category_id')
			->where('1=1');

		$this->applyFilters($filters, $query);

		try {
			$this->db->setQuery($query);
			$count = $this->db->loadResult();
		} catch (\Exception $e) {
			Log::add('Error counting products: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product');
			return 0;
		}

		return (int)$count;
	}

	/**
	 * @param   int    $lim
	 * @param   int    $page
	 * @param   array  $filters
	 *
	 * @return array<ProductEntity>
	 */
	public function getProducts(int $lim = 10, int $page = 1, array $filters = []): array
	{
		$product_list = [];

		$query = $this->db->createQuery();

		$offset = ($page - 1) * $lim;

		$query->select('p.*, GROUP_CONCAT(pc.campaign_id SEPARATOR ",") as campaigns')
			->from($this->db->quoteName($this->getTableName(self::class), 'p'))
			->leftJoin($this->db->quoteName('jos_emundus_product_category', 'c') . ' ON c.id = p.category_id')
			->leftJoin($this->db->quoteName('jos_emundus_product_campaigns', 'pc') . ' ON pc.product_id = p.id')
			->where('1=1');

		$this->applyFilters($filters, $query);

		if (!empty($search)) {
			$query->andWhere($this->db->quoteName('p.description') . ' LIKE ' . $this->db->quote('%' . $search . '%') . ' OR ' . $this->db->quoteName('p.label') . ' LIKE ' . $this->db->quote('%' . $search . '%') . ' OR ' . $this->db->quoteName('c.label') . ' LIKE ' . $this->db->quote('%' . $search . '%'));
		}

		if ($lim > 0) {
			$offset = ($page - 1) * $lim;
			$query->setLimit($lim, $offset);
		}

		try {
			$query->group('p.id');
			$this->db->setQuery($query);
			$products = $this->db->loadObjectList();

			if ($products)
			{
				$product_list = ProductFactory::fromDbObjects($products);
			}
		} catch (\Exception $e) {
			Log::add('Error loading products: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product');
		}

		return $product_list;
	}


	/**
	 * @param   ProductEntity  $product
	 *
	 * @return bool
	 */
	public function flush(ProductEntity $product): bool
	{
		$saved = false;

		$query = $this->db->createQuery();

		if (!empty($product->getId()))
		{
			$query->update($this->db->quoteName($this->getTableName(self::class)))
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

		return $saved;
	}

	public function delete(int $product_id): bool
	{
		$deleted = false;

		if (!empty($product_id)) {
			$query = $this->db->createQuery();

			$query->delete($this->db->quoteName($this->getTableName(self::class)))
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