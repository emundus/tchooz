<?php

namespace Tchooz\Entities\Payment;

use Joomla\Database\DatabaseDriver;
use Joomla\CMS\Factory;

class ProductCategoryEntity
{
	private int $id = 0;
	private string $label;
	private int $published;
	private DatabaseDriver $db;


	public function __construct(int $id)
	{
		$this->db = Factory::getContainer()->get('DatabaseDriver');
		$this->id = $id;

		if (!empty($this->id))
		{
			$this->load();
		}
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function setId(int $id): void
	{
		$this->id = $id;
	}

	private function load(): void
	{
		$query = $this->db->createQuery();

		$query->select($this->db->quoteName(['id', 'label', 'published']))
			->from($this->db->quoteName('jos_emundus_product_category'))
			->where($this->db->quoteName('id') . ' = ' . $this->db->quote($this->id));

		try {
			$this->db->setQuery($query);
			$category = $this->db->loadObject();

			if ($category)
			{
				$this->label = $category->label;
				$this->published = $category->published;
			}
			else
			{
				throw new \Exception('Category not found');
			}
		} catch (\Exception $e) {
			throw new \Exception('Error loading category: ' . $e->getMessage());
		}
	}

	public function setLabel(string $label): void
	{
		$this->label = $label;
	}

	public function getLabel(): string
	{
		return $this->label;
	}

	public function setPublished(int $published): void
	{
		$this->published = $published;
	}

	public function getPublished(): int
	{
		return $this->published;
	}

	public function serialize(): array
	{
		return [
			'id' => $this->getId(),
			'label' => $this->getLabel(),
			'published' => $this->getPublished(),
		];
	}
}