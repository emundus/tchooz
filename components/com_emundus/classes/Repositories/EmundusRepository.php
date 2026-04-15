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
use Tchooz\Entities\List\ListResult;
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

	protected array $searchableColumns = [];

	/**
	 * @var array<Join>
	 */
	protected array $joins = [];

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
	public function getItemByField(string $field, mixed $value, bool $returnEntity = false, string|array $select = '*'): ?object
	{
		$item = null;

		$query = $this->db->getQuery(true);

		$this->buildSelect($query, $select);
		$this->buildLeftJoin($query);

		$query->where($this->db->quoteName($this->alias . '.' . $field) . ' = ' . $this->db->quote($value));


		try {
			$this->db->setQuery($query);
			$item = $this->db->loadObject();

			if ($item && $returnEntity && !empty($this->getFactory()) && method_exists($this->getFactory(), 'fromDbObject')) {
				$item = $this->getFactory()->fromDbObject($item, $this->withRelations);
			}
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
	public function getItemsByField(string $field, mixed $value, bool $returnEntity = false): array
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

			if ($items && $returnEntity && !empty($this->getFactory()) && method_exists($this->getFactory(), 'fromDbObjects')) {
				$items = $this->getFactory()::fromDbObjects($items, $this->withRelations);
			}
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
	public function getItemsByFields(array $fields, bool $returnEntity = false, string $operator = 'AND'): array
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
				$query->where($this->db->quoteName($this->alias . '.' . $field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')', $operator);
			} else {
				$query->where($this->db->quoteName($this->alias . '.' . $field) . ' = ' . $this->db->quote($value), $operator);
			}
		}

		try {
			$this->db->setQuery($query);
			$items = $this->db->loadObjectList();

			if ($items && $returnEntity && !empty($this->getFactory()) && method_exists($this->getFactory(), 'fromDbObjects')) {
				$items = $this->getFactory()::fromDbObjects($items, $this->withRelations);
			}
		} catch (\Exception $e) {
			Log::add('Error on fetching items by fields for table ' . $this->tableName . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $items;
	}

	public function getList(array $filters = [], int $limit = 0, int $page = 1, string|array $select = '*', string $order = '', string $search = ''): ListResult
	{
		return new ListResult(
			$this->get($filters, $limit, $page, $select, $order, $search),
			$this->getCount($filters)
		);
	}

	public function getCount(array $filters = []): int
	{
		$count = 0;

		$query = $this->db->createQuery()
			->select('COUNT('.$this->primaryKey.')')
			->from($this->db->quoteName($this->tableName, $this->alias));

		if (!empty($filters))
		{
			$this->applyFilters($query, $filters);
		}

		try {
			$this->db->setQuery($query);
			$count = (int) $this->db->loadResult();
		} catch (\Exception $e) {
			Log::add('Error on fetching count for table ' . $this->tableName . ' : ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository');
		}

		return $count;
	}

	/**
	 * @param   array   $filters
	 * @param   int     $limit
	 * @param   int     $page
	 * @param   string|array  $select
	 * @param   string  $order
	 *
	 * @return array
	 */
	public function get(array $filters = [], int $limit = 0, int $page = 1, string|array $select = '*', string $order = '', string $search = '', bool $buildEntity = true): array
	{
		if ($select === '*')
		{
			$select = $this->alias . '.*';
		}
		else
		{
			if(empty($select))
			{
				$select = $this->columns;
			}

			$selectFields = [];
			$fields = is_array($select) ? $select : explode(',', $select);

			foreach ($fields as $field)
			{
				$field = trim($field);

				if (!str_contains($field, '.'))
				{
					$field = $this->alias . '.' . $field;
				}

				if (!in_array($field, $this->columns))
				{
					throw new \InvalidArgumentException("Field '{$field}' not allowed.");
				}

				$selectFields[] = $field;
			}

			$select = implode(', ', $selectFields);
		}

		$query = $this->db->createQuery()
			->select($select)
			->from($this->db->quoteName($this->tableName, $this->alias));
		$this->buildLeftJoin($query);

		if (!empty($order))
		{
			$query->order($order);
		} else {
			$query->order($this->alias . '.' . $this->primaryKey . ' ASC');
		}

		if (!empty($filters))
		{
			$this->applyFilters($query, $filters);
		}

		if(!empty($search))
		{
			$searchConditions = [];
			foreach ($this->searchableColumns as $column)
			{
				if(!str_contains($column, '.'))
				{
					$column = $this->alias . '.' . $column;
				}
				$searchConditions[] = $this->db->quoteName($column) . ' LIKE ' . $this->db->quote('%' . $search . '%');
			}

			if (!empty($searchConditions))
			{
				$query->where('(' . implode(' OR ', $searchConditions) . ')');
			}
		}

		if (!empty($limit))
		{
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

		$this->db->setQuery($query);
		$objects = $this->db->loadObjectList();

		if ($objects && $buildEntity && !empty($this->getFactory()) && method_exists($this->getFactory(), 'fromDbObjects')) {
			$objects = $this->getFactory()::fromDbObjects($objects, $this->withRelations);
		}

		return $objects;
	}

	public function buildSelect(QueryInterface $query, string|array $select = '*'): void
	{
		if ($select === '*')
		{
			$select = $this->alias . '.*';
		}
		else
		{
			if(empty($select))
			{
				$select = $this->columns;
			}

			$selectFields = [];
			$fields = is_array($select) ? $select : explode(',', $select);

			foreach ($fields as $field)
			{
				$field = trim($field);

				if (!str_contains($field, '.'))
				{
					$field = $this->alias . '.' . $field;
				}

				if (!in_array($field, $this->columns))
				{
					throw new \InvalidArgumentException("Field '{$field}' not allowed.");
				}

				$selectFields[] = $field;
			}

			$select = implode(', ', $selectFields);
		}

		$query->select($select)
			->from($this->db->quoteName($this->tableName, $this->alias));
	}

	public function buildLeftJoin(QueryInterface $query): void
	{
		if(!empty($this->joins))
		{
			$query->group($this->alias . '.id');
		}

		foreach ($this->joins as $join)
		{
			$query->{$join->getType()->getMethod()}(
				$this->db->quoteName($join->getToTable(), $join->getToAlias()),
				$this->db->quoteName($join->getFromAlias() . '.' . $join->getFromKey()) . ' = ' . $this->db->quoteName($join->getToAlias() . '.' . $join->getToKey())
			);
		}
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

				$fieldAlias = explode('.', $field)[0];
				$fieldWithoutAlias = explode('.', $field)[1] ?? $field;

				// If column have an alias of joined table, we allow it without prefix but we need to find the real column name for validation
				$joinedAlias = [];
				foreach ($this->joins as $join)
				{
					$joinedAlias[] = $join->getToAlias();
				}

				if (!in_array($field, $this->columns))
				{
					if (str_contains($field, '.') && !in_array($fieldAlias, $joinedAlias))
					{
						throw new \InvalidArgumentException("Invalid filter field: {$field}");
					}
				}

				// If the field is an alias of a joined table, we have to build a subquery to apply the filter to keep grouping and avoid duplicates
				if(in_array($fieldAlias, $joinedAlias))
				{
					$joinObject = $this->joins[$fieldAlias];
					$joinObjectFilterAlias = $joinObject->getToAlias() . '_filter';
					$subQuery = $this->db->getQuery(true)
						->select(1)
						->from($this->db->quoteName($joinObject->getToTable(), $joinObjectFilterAlias))
						->where(
							$this->db->quoteName($joinObjectFilterAlias . '.' . $joinObject->getToKey()) .
							' = ' .
							$this->db->quoteName($this->alias . '.' . $joinObject->getFromKey())
						);

					$this->buildWhere($subQuery, ($joinObjectFilterAlias . '.' . $fieldWithoutAlias), $value);

					$query->where('EXISTS (' . $subQuery . ')');

					continue;
				}

				$this->buildWhere($query, $field, $value);
			}
		}
	}

	private function buildWhere(QueryInterface $query, string $field, mixed $value): void
	{
		if (is_array($value))
		{
			if (!empty($value))
			{
				$query->where($this->db->quoteName($field) . ' IN (' . implode(',', array_map([$this->db, 'quote'], $value)) . ')');
			}
		}
		elseif (is_null($value))
		{
			$query->where($this->db->quoteName($field) . ' IS NULL');
		}
		elseif (str_contains($value, '%'))
		{
			$query->where($this->db->quoteName($field) . ' LIKE ' . $this->db->quote($value));
		}
		else
		{
			$query->where($this->db->quoteName($field) . ' = ' . $this->db->quote($value));
		}
	}

	public function buildOrderBy(string $order, string $direction = 'ASC'): string
	{
		if (!str_starts_with($order, $this->alias . '.') && !str_contains($order, '.')) {
			$order = $this->alias . '.' . $order;
		}

		if (!in_array($order, $this->columns))
		{
			throw new \InvalidArgumentException("Invalid order field: {$order}");
		}

		return $this->db->quoteName($order) . ' ' . $direction;
	}

	public function getFactory(): ?object
	{
		return null;
	}
}