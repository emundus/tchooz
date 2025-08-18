<?php

namespace Emundus\Indexer\Entities;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\Database\DatabaseDriver;

class IndexEntity {
	private int $id;

	public string $label;
	public string|int $old_index;
	public string|int $new_index;
	public string|array $params = [];
	private DatabaseDriver $db;
	public function __construct(int $id, string $label, string|int $old_index, string|int $new_index, array $params = [])
	{
		if (!empty($id)) {
			$this->setId($id);
		} else {
			$this->label = $label;
			$this->old_index = $old_index;
			$this->new_index = $new_index;
			$this->params = $params;
		}

		if (empty($this->label)) {
			throw new \Exception('Label is required');
		}

		if (empty($this->old_index)) {
			throw new \Exception('Old index is required');
		}

		if (empty($this->new_index)) {
			throw new \Exception('New index is required');
		}

		$this->db = Factory::getContainer()->get('DatabaseDriver');
	}

	public function setId(int $id): void
	{
		$this->id = $id;
		$this->load();
	}

	public function getId(): int
	{
		return $this->id;
	}

	private function load(): void
	{
		$query = $this->db->createQuery();

		$query->select('ei.*')
			->from($this->db->quoteName('#__emundus_indexes', 'ei'))
			->where('ei.id = ' . $this->id);

		$this->db->setQuery($query);
		$index = $this->db->loadObject();

		if (!empty($index)) {
			$this->label = $index->label;
			$this->old_index = $index->old_index;
			$this->new_index = $index->new_index;
			$this->params = json_decode($index->params);
		}
	}

	public function databaseFormat(): void
	{
		$this->params = is_array($this->params) ? json_encode($this->params) : $this->params;
	}

	public function save(): bool
	{
		$saved = false;

		$this->databaseFormat();

		if (empty($this->id)) {
			$saved = $this->db->insertObject('#__emundus_indexes', $this);
			$this->id = $this->db->insertid();
		} else {
			$saved = $this->db->updateObject('#__emundus_indexes', $this, 'id');
		}

		return $saved;
	}
}