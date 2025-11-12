<?php

namespace Tchooz\Repositories\Payment;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\Payment\ProductCategoryEntity;
use Tchooz\Factories\Payment\ProductCategoryFactory;
use Tchooz\Traits\TraitTable;

#[TableAttribute(table: '#__emundus_product_category')]
class ProductCategoryRepository
{
	use TraitTable;

	private DatabaseDriver $db;

	public function __construct()
	{
		Log::addLogger(['text_file' => 'com_emundus.repository.product_category.php'], Log::ALL, ['com_emundus.repository.product_category']);
		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	/**
	 * @param   ProductCategoryEntity  $product_category
	 *
	 * @return bool
	 */
	public function flush(ProductCategoryEntity $product_category): bool
	{
		$saved = false;

		$query = $this->db->createQuery();
		if (!empty($product_category->getId())) {
			$query->update($this->db->quoteName($this->getTableName(self::class)))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($product_category->getLabel()))
				->set($this->db->quoteName('published') . ' = ' . $this->db->quote($product_category->getPublished()))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($product_category->getId()));
		} else {
			$query->insert($this->db->quoteName($this->getTableName(self::class)))
				->columns($this->db->quoteName(['label', 'published']))
				->values(implode(',', [$this->db->quote($product_category->getLabel()), $this->db->quote($product_category->getPublished())]));
		}

		try {
			$this->db->setQuery($query);
			$saved = $this->db->execute();

			if ($saved && empty($product_category->getId())) {
				$product_category->setId((int) $this->db->insertid());
			}
		} catch (\Exception $e) {
			Log::add('Error saving product category: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product_category');
		}

		return $saved;
	}

	/**
	 * @param   int  $id
	 *
	 * @return ProductCategoryEntity|null
	 */
	public function getProductCategoryById(int $id): ?ProductCategoryEntity
	{
		$category = null;

		$query = $this->db->createQuery();
		$query->select('*')
			->from($this->db->quoteName($this->getTableName(self::class)))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($id));

		try {
			$this->db->setQuery($query);
			$result = $this->db->loadObject();

			if ($result)
			{
				$category = ProductCategoryFactory::fromDbObjects([$result])[0];
			}
		} catch (\Exception $e) {
			Log::add('Error loading product category by ID: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product_category');
		}

		return $category;
	}

	/**
	 * @param  array  $filters
	 *
	 * @return array<ProductCategoryEntity>
	 */
	public function getProductCategories(array $filters = []): array
	{
		$categories = [];

		$query = $this->db->createQuery();
		$query->select('*')
			->from($this->db->quoteName($this->getTableName(self::class)))
			->order($this->db->quoteName('label') . ' ASC');

		if (!empty($filters))
		{
			foreach ($filters as $field => $value)
			{
				if (!in_array($field, ['id', 'label', 'published']))
				{
					continue;
				}

				if (is_array($value)) {
					$query->where($this->db->quoteName($field) . ' IN (' . implode(',', $value) . ')');
				} else {
					$query->where($this->db->quoteName($field) . ' = ' . $this->db->quote($value));
				}
			}
		}

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if ($results)
			{
				$categories = ProductCategoryFactory::fromDbObjects($results);
			}
		} catch (\Exception $e) {
			Log::add('Error loading product categories: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product_category');
		}

		return $categories;
	}
}