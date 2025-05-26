<?php

namespace Tchooz\Repositories\Payment;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Tchooz\Entities\Payment\ProductCategoryEntity;

class ProductCategoryRepository
{
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
			$query->update($this->db->quoteName('jos_emundus_product_category'))
				->set($this->db->quoteName('label') . ' = ' . $this->db->quote($product_category->getLabel()))
				->set($this->db->quoteName('published') . ' = ' . $this->db->quote($product_category->getPublished()))
				->where($this->db->quoteName('id') . ' = ' . $this->db->quote($product_category->getId()));
		} else {
			$query->insert($this->db->quoteName('jos_emundus_product_category'))
				->columns($this->db->quoteName(['label', 'published']))
				->values(implode(',', [$this->db->quote($product_category->getLabel()), $this->db->quote($product_category->getPublished())]));
		}

		try {
			$this->db->setQuery($query);
			$saved = $this->db->execute();
		} catch (\Exception $e) {
			Log::add('Error saving product category: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product_category');
		}

		return $saved;
	}

	/**
	 * @return void
	 */
	public function getProductCategories(): array
	{
		$categories = [];

		$query = $this->db->createQuery();
		$query->select('*')
			->from($this->db->quoteName('jos_emundus_product_category'))
			->order($this->db->quoteName('label') . ' ASC');

		try {
			$this->db->setQuery($query);
			$results = $this->db->loadObjectList();

			if ($results)
			{
				foreach ($results as $result)
				{
					$category = new ProductCategoryEntity(0);
					$category->setId($result->id);
					$category->setLabel($result->label);
					$category->setPublished($result->published);

					$categories[] = $category;
				}
			}
		} catch (\Exception $e) {
			Log::add('Error loading product categories: ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.product_category');
		}

		return $categories;
	}
}