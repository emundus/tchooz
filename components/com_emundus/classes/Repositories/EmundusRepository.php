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
use Tchooz\Enums\Automation\ConditionOperatorEnum;
use Tchooz\Traits\TraitDispatcher;
use Tchooz\Traits\TraitTable;

class EmundusRepository
{
	use TraitTable;

	use TraitDispatcher;

	protected bool $withRelations;
	protected array $exceptRelations = [];

	// ...existing code...

	public function getWithRelations(): bool|array
	{
		return $this->withRelations;
	}

	public function getExceptRelations(): array
	{
		return $this->exceptRelations;
	}

	protected DatabaseInterface $db;

	protected string $tableName = '';
	protected string $primaryKey = 'id';
	protected string $alias = 't';
	protected array $columns = [];
	protected array $columnsNoAlias = [];

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
		$this->columnsNoAlias = $this->getTableColumnsNoPrefix($className);

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

			if ($item && $returnEntity && !empty($this->getFactory())) {
				$item = $this->callFactory('fromDbObject', [$item, $this->withRelations, $this->exceptRelations]);
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

			if ($items && $returnEntity && !empty($this->getFactory())) {
				$items = $this->callFactory('fromDbObjects', [$items, $this->withRelations, $this->exceptRelations]);
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
			->select($this->columns)
			->from($this->db->quoteName($this->tableName, $this->alias));
		$this->buildLeftJoin($query);

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

			if ($items && $returnEntity && !empty($this->getFactory())) {
				$items = $this->callFactory('fromDbObjects', [$items, $this->withRelations, $this->exceptRelations]);
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
	 * @param   array         $filters Filters to apply to the query, Two formats are allowed, key => value, or ColumnFilter object
	 * @param   int           $limit
	 * @param   int           $page
	 * @param   string|array  $select
	 * @param   string        $order
	 * @param   string        $search
	 * @param   bool          $buildEntity
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
			$searchConditions = $this->buildSearchConditions($this->searchableColumns, $search);

			if (!empty($searchConditions))
			{
				$query->where($searchConditions);
			}
		}

		if (!empty($limit))
		{
			$offset = ($page - 1) * $limit;
			$query->setLimit($limit, $offset);
		}

		$this->db->setQuery($query);
		$objects = $this->db->loadObjectList();

		if ($objects && $buildEntity && !empty($this->getFactory())) {
			$objects = $this->callFactory('fromDbObjects', [$objects, $this->withRelations, $this->exceptRelations]);
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

	/**
	 * @param   QueryInterface  $query
	 * @param   array           $filters  Two formats are allowed, key => value where the operator is
	 *                                    inferred from the value, or ColumnFilter objects
	 *                                    carrying an explicit operator. Right now filters are all on AND operation. We should handle a FilterGroup some day
	 * @return void
	 */
	public function applyFilters(QueryInterface $query, array $filters): void
	{
		if (!empty($filters))
		{
			$query->where('1 = 1');

			foreach ($filters as $field => $value)
			{
				if ($value instanceof ColumnFilter)
				{
					$field    = $value->getColumn();
					$operator = $value->getOperator();
					$value    = $value->getValue();
				}
				else
				{
					$operator = ConditionOperatorEnum::EQUALS;
				}

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

					$this->buildWhere($subQuery, ($joinObjectFilterAlias . '.' . $fieldWithoutAlias), $value, $operator);

					$query->where('EXISTS (' . $subQuery . ')');

					continue;
				}

				$this->buildWhere($query, $field, $value, $operator);
			}
		}
	}

	/**
	 * A filter without an explicit operator is an equality, so both filter shapes share this single path.
	 * Semantics mirror ScalarComparator::compare and ArrayComparator::compare, so that filtering rows in
	 * SQL and evaluating an automation condition in PHP on the same data agree.
	 *
	 * @throws \InvalidArgumentException when the operator cannot be applied to the given value
	 */
	private function buildWhere(QueryInterface $query, string $field, mixed $value, ?ConditionOperatorEnum $operator = null): void
	{
		$operator = $operator ?? ConditionOperatorEnum::EQUALS;
		$column   = $this->db->quoteName($field);

		if ($operator->noValueNeeded() || is_null($value))
		{
			// ScalarComparator compares loosely, so comparing to null tests emptiness
			$expectsEmpty = match ($operator)
			{
				ConditionOperatorEnum::IS_EMPTY, ConditionOperatorEnum::EQUALS          => true,
				ConditionOperatorEnum::IS_NOT_EMPTY, ConditionOperatorEnum::NOT_EQUALS  => false,
				default                                                                 => throw new \InvalidArgumentException("Operator {$operator->value} requires a value to filter on {$field}"),
			};

			$query->where($this->buildEmptyWhere($column, $expectsEmpty));

			return;
		}

		if (is_array($value))
		{
			// ArrayComparator with its default ANY match mode: the column has to be one of the values
			if (!in_array($operator, [ConditionOperatorEnum::EQUALS, ConditionOperatorEnum::NOT_EQUALS], true))
			{
				throw new \InvalidArgumentException("Operator {$operator->value} does not support a list of values on {$field}");
			}

			// No column value can belong to an empty list, so the query has to return no row at all.
			// Writing IN () would be a SQL error, hence the invalidating condition
			if (empty($value))
			{
				$query->where($operator === ConditionOperatorEnum::NOT_EQUALS ? '1 = 1' : '1 = 0');

				return;
			}

			$values = implode(',', array_map([$this->db, 'quote'], $value));
			$query->where($column . ($operator === ConditionOperatorEnum::NOT_EQUALS ? ' NOT IN ' : ' IN ') . '(' . $values . ')');

			return;
		}

		// Callers embed their own wildcards in a value to ask for a partial match
		if (is_string($value) && str_contains($value, '%') && in_array($operator, [ConditionOperatorEnum::EQUALS, ConditionOperatorEnum::NOT_EQUALS], true))
		{
			$query->where($column . ($operator === ConditionOperatorEnum::NOT_EQUALS ? ' NOT LIKE ' : ' LIKE ') . $this->db->quote($value));

			return;
		}

		if (in_array($operator, [ConditionOperatorEnum::CONTAINS, ConditionOperatorEnum::NOT_CONTAINS], true))
		{
			$query->where($column . ' ' . $operator->sqlOperator() . ' ' . $this->db->quote('%' . $this->db->escape($value, true) . '%', false));

			return;
		}

		if ($operator === ConditionOperatorEnum::NOT_EQUALS)
		{
			// PHP holds null != value as true where SQL would drop those rows
			$query->where('(' . $column . ' IS NULL OR ' . $column . ' != ' . $this->db->quote($value) . ')');

			return;
		}

		$query->where($column . ' ' . $operator->sqlOperator() . ' ' . $this->db->quote($value));
	}

	/**
	 * Empty is understood as PHP empty(): null, an empty string and zero all qualify. The zero is compared
	 * as a string so that MySQL does not coerce a text column to a number, where any text would equal 0.
	 */
	private function buildEmptyWhere(string $column, bool $expectsEmpty): string
	{
		$emptyString = $this->db->quote('');
		$zero        = $this->db->quote('0');

		if ($expectsEmpty)
		{
			return '(' . $column . ' IS NULL OR ' . $column . ' = ' . $emptyString . ' OR ' . $column . ' = ' . $zero . ')';
		}

		return '(' . $column . ' IS NOT NULL AND ' . $column . ' != ' . $emptyString . ' AND ' . $column . ' != ' . $zero . ')';
	}

	/**
	 * Builds the SQL condition of a search field. A comma separates independent terms: searching
	 * "data1,data2" returns the matches of both at once.
	 *
	 * @param   array   $columns  columns to search in, prefixed with the repository alias when unqualified
	 * @param   string  $search   raw search string
	 *
	 * @return string empty when there is nothing to search on
	 */
	public function buildSearchConditions(array $columns, string $search): string
	{
		$terms = array_filter(array_map('trim', explode(',', $search)), function ($term) {
			return $term !== '';
		});

		if (empty($terms) || empty($columns))
		{
			return '';
		}

		$conditions = [];
		foreach ($terms as $term)
		{
			$term = $this->db->quote('%' . $this->db->escape($term, true) . '%', false);

			foreach ($columns as $column)
			{
				if (!str_contains($column, '.'))
				{
					$column = $this->alias . '.' . $column;
				}

				$conditions[] = $this->db->quoteName($column) . ' LIKE ' . $term;
			}
		}

		return '(' . implode(' OR ', $conditions) . ')';
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

	/**
	 * Calls a method of the factory in a compatible manner,
	 * whether it is a static or instance method.
	 *
	 * @param string $method    Method name (ex: 'fromDbObject', 'fromDbObjects')
	 * @param array  $args      Arguments to pass to the method
	 *
	 * @return mixed
	 */
	protected function callFactory(string $method, array $args): mixed
	{
		$factory = $this->getFactory();

		if ($factory === null || !method_exists($factory, $method))
		{
			return $args[0] ?? null;
		}

		$ref = new \ReflectionMethod($factory, $method);

		if ($ref->isStatic())
		{
			return $factory::$method(...$args);
		}

		return $factory->$method(...$args);
	}
}