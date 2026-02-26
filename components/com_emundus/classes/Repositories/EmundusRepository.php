<?php
/**
 * @package     Tchooz\Repositories
 * @subpackage
 *
 * @copyright   A copyright
 * @license     A "Slug" license name e.g. GPL2
 */

namespace Tchooz\Repositories;

use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseInterface;
use Joomla\Database\QueryInterface;
use Tchooz\Factories\Workflow\StepTypeFactory;
use Tchooz\Traits\TraitTable;

class EmundusRepository
{
	use TraitTable;

	protected bool $withRelations;
	protected array $exceptRelations = [];

	protected DatabaseInterface $db;

	protected string $tableName = '';
	protected string $primaryKey = 'id';
	protected string $alias = 't';
	protected array $columns = [];

	protected string $name = '';
	protected ?CacheController $cache = null;

	public function __construct(
		$withRelations = true,
		$exceptRelations = [],
		$name = '',
		$className = self::class
	)
	{
		$this->db              = Factory::getContainer()->get('DatabaseDriver');

		$this->tableName = $this->getTableName($className);
		$this->alias 	 = $this->getTableAlias($className);
		$this->columns   = $this->getTableColumns($className);

		$this->withRelations   = $withRelations;
		$this->exceptRelations = $exceptRelations;

		if (!empty($name))
		{
			$this->name = $name;

			$this->cache = Factory::getContainer()->get(CacheControllerFactoryInterface::class)
				->createCacheController('output', ['defaultgroup' => 'com_emundus.'.$name]);

			Log::addLogger(['text_file' => "com_emundus.repository.{$name}.php"], Log::ALL, ["com_emundus.repository.{$name}"]);
		}
	}

	/**
	 * @param   string  $field
	 * @param   mixed   $value
	 *
	 * @return object|null
	 */
	public function getItemByField(string $field, mixed $value): ?object
	{
		$item = null;

		$query = $this->db->getQuery(true)
			->select($this->alias . '.*')
			->from($this->db->quoteName($this->tableName, $this->alias))
			->where($this->db->quoteName($this->alias . '.' . $field) . ' = ' . $this->db->quote($value));

		try {
			$this->db->setQuery($query);
			$item = $this->db->loadObject();
		} catch (\Exception $e) {
			Log::add('Error on fetching item by field ' . $field . ' for table ' . $this->tableName . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $item;
	}

	/**
	 * @param   string  $field
	 * @param   mixed   $value
	 *
	 * @return array
	 */
	public function getItemsByField(string $field, mixed $value): array
	{
		$items = [];

		$query = $this->db->getQuery(true)
			->select($this->alias . '.*')
			->from($this->db->quoteName($this->tableName, $this->alias));

		if (is_array($value)) {
			$query->where($this->db->quoteName($this->alias . '.' . $field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
		} else {
			$query->where($this->db->quoteName($this->alias . '.' . $field) . ' = ' . $this->db->quote($value));
		}

		try {
			$this->db->setQuery($query);
			$items = $this->db->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Error on fetching items by field ' . $field . ' for table ' . $this->tableName . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $items;
	}

	/**
	 * @param   array  $fields
	 *
	 * @return array
	 */
	public function getItemsByFields(array $fields): array
	{
		$items = [];

		$query = $this->db->getQuery(true)
			->select($this->alias . '.*')
			->from($this->db->quoteName($this->tableName, $this->alias));

		foreach ($fields as $field => $value) {
			if (!in_array($field, $this->columns) && !in_array($this->alias . '.' . $field, $this->columns))
			{
				throw new \InvalidArgumentException("Field '{$field}' not allowed.");
			}

			if (is_array($value)) {
				$query->where($this->db->quoteName($this->alias . '.' . $field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
			} else {
				$query->where($this->db->quoteName($this->alias . '.' . $field) . ' = ' . $this->db->quote($value));
			}
		}

		try {
			$this->db->setQuery($query);
			$items = $this->db->loadObjectList();
		} catch (\Exception $e) {
			Log::add('Error on fetching items by fields for table ' . $this->tableName . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $items;
	}

	/**
	 * @param   array   $filters
	 * @param   int     $limit
	 * @param   int     $page
	 * @param   string  $select
	 * @param   string  $order
	 *
	 * @return array
	 */
	public function get(array $filters = [], int $limit = 0, int $page = 1, string $select = '*', string $order = ''): array
	{
		$objects = [];

		if ($select === '*')
		{
			$select = $this->alias . '.*';
		}
		else
		{
			$selectFields = [];
			$fields = is_array($select) ? $select : explode(',', $select);

			foreach ($fields as $field)
			{
				$field = trim($field);

				if (!str_starts_with($field, $this->alias))
				{
					$field = $this->alias . '.' . $field;
				}

				if (!in_array($field, $this->columns))
				{
					throw new \InvalidArgumentException("Field '{$field}' not allowed.");
				}

				$selectFields = $field;
			}

			$select = implode(', ', $selectFields);
		}

		$query = $this->db->createQuery()
			->select($select)
			->from($this->db->quoteName($this->tableName, $this->alias));

		if (!empty($order))
		{
			$query->order($order);
		} else {
			$query->order($this->alias . '.id ASC');
		}

		if (!empty($filters))
		{
			$this->applyFilters($query, $filters);
		}

		if (!empty($limit))
		{
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();

		if ($results && !empty($this->getFactory()) && method_exists($this->getFactory(), 'fromDbObjects')) {
			$objects = $this->getFactory()::fromDbObjects($results);
		}

		return $objects;
	}

	public function applyFilters(QueryInterface $query, array $filters): void
	{
		if (!empty($filters))
		{
			$query->where('1 = 1');

			foreach ($filters as $field => $value)
			{
				if (!str_starts_with($field, $this->alias . '.') && !str_contains($field, '.')) {
					$field = $this->alias . '.' . $field;
				}

				if (!in_array($field, $this->columns))
				{
					throw new \InvalidArgumentException("Invalid filter field: {$field}");
				}

				if (is_array($value))
				{
					$query->andWhere($this->db->quoteName($field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
				}
				else
				{
					$query->andWhere($this->db->quoteName($field) . ' = ' . $this->db->quote($value));
				}
			}
		}
	}

	public function getFactory(): ?object
	{
		return null;
	}
}