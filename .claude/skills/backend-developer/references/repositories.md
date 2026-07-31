# Repository internals

Deep dive on `Tchooz\Repositories\EmundusRepository`, the `#[TableAttribute]` ORM hint, query building, and the `flush()` discipline.

## Two repository shapes

The codebase has two acceptable shapes, picked by how much of `EmundusRepository` you actually need:

### Shape A — Extend `EmundusRepository` (recommended for CRUD-like domains)

```php
#[TableAttribute(table: 'jos_emundus_filters', alias: 'ef', columns: [
    'id', 'time_date', 'user', 'name', 'constraints', 'item_id', 'mode',
])]
class FilterRepository extends EmundusRepository implements RepositoryInterface
{
    private FilterFactory $factory;

    public function __construct($withRelations = true, $exceptRelations = [])
    {
        parent::__construct($withRelations, $exceptRelations, 'filters', self::class);
        $this->factory = new FilterFactory();
    }

    public function getFactory(): ?object { return $this->factory; }
    public function getById(int $id): FilterEntity { /* … */ }
    public function delete(int $id): bool         { /* … */ }
    public function flush(FilterEntity $entity): void { /* … */ }
}
```

What you get for free from the parent:
- `getList($filters, $limit, $page, $select, $order, $search)` → `ListResult` (rows + count).
- `getCount($filters)` → `int`.
- `get($filters, $limit, $page, $select, $order, $search, $buildEntity)` → array of entities (or raw rows).
- `getItemByField($field, $value, $returnEntity)` → one row, optionally hydrated through the factory.
- `getItemsByField($field, $value, $returnEntity)` → many rows.
- `getItemsByFields($fields, $returnEntity, $operator)` → AND/OR composed `WHERE`.
- `applyFilters(QueryInterface $query, array $filters)` — validates field names against `$this->columns`, builds `WHERE`, supports joined-alias subqueries automatically.
- `buildOrderBy($order, $direction)` — validates field name, returns a quoted `ORDER BY` fragment.
- `buildSelect(...)`, `buildLeftJoin(...)`.
- Cache controller and a logger (`com_emundus.repository.<name>`) registered in the constructor.

Use this when the entity is a typical table with CRUD + list/search and the queries are mostly field-based.

### Shape B — Slim, use `TraitTable` only (when CRUD-base would be overkill)

```php
#[TableAttribute(table: 'jos_emundus_setup_workflows')]
class WorkflowRepository
{
    use TraitTable;

    private DatabaseDriver $db;

    public function __construct(?DatabaseDriver $db = null)
    {
        $this->db = $db ?? Factory::getContainer()->get('DatabaseDriver');
        Log::addLogger(['text_file' => 'com_emundus.repository.workflow.php'], Log::ALL, ['com_emundus.repository.workflow']);
    }
    // hand-written queries
}
```

Pick this when:
- The repository is mostly hand-written joins that don't fit the generic `applyFilters` model.
- You want to inject a `DatabaseDriver` for testing (the `EmundusRepository` constructor pulls from `Factory::getContainer()`).
- You expose specialized methods (e.g. `getWorkflowByFnum`, `getWorkflowByProgramId`) more than list/get-by-field.

Both shapes use the same `#[TableAttribute]` so `getTableName(self::class)` works.

## `#[TableAttribute]` — what the columns array does

```php
#[TableAttribute(
    table:   'jos_emundus_filters',
    alias:   'ef',
    columns: ['id', 'time_date', 'user', 'name', 'constraints', 'item_id', 'mode']
)]
```

- `table` — the literal table name (always `jos_emundus_*` — never `#__emundus_*`, see CLAUDE.md note on Tchooz's table prefix).
- `alias` — short query alias (`ef`, `esw`, `eu`). Defaults to the table name if omitted.
- `columns` — the whitelist `EmundusRepository::applyFilters()` checks against. Without it, any filter key throws `InvalidArgumentException`. Column names get prefixed with the alias automatically by `TraitTable::getTableColumns()`.

Add new columns to the `columns` array **the same time** you add them to the database — otherwise `applyFilters()` will reject filter keys that match real columns. This has caused production bugs.

## Building queries — non-negotiable rules

All queries go through `$this->db` (a `Joomla\Database\DatabaseDriver`). The patterns are:

```php
$query = $this->db->createQuery()      // or getQuery(true) — same thing in this codebase
    ->select($this->alias . '.*')
    ->from($this->db->quoteName($this->getTableName(self::class), $this->alias))
    ->where($this->db->quoteName($this->alias . '.id') . ' = ' . (int) $id);

$this->db->setQuery($query);
$row = $this->db->loadObject();
```

Rules:
- **Every column name** goes through `quoteName(...)`. Backtick injection is real.
- **Every value** goes through `quote(...)` or is cast to `int`/`float` first. SQL injection is real.
- **Never** build a SQL string with `.= "WHERE foo = '$value'"`. Use the query builder.
- **Never** select `*` from a joined table if the column set is wide — pick the columns. `getList` with `select = '*'` already does this safely by prefixing with the alias.
- For `IN (...)`, use:
  ```php
  $query->where(
      $this->db->quoteName($this->alias . '.id') . ' IN (' .
      implode(',', array_map([$this->db, 'quote'], $ids)) .
      ')'
  );
  ```

### Joins

When extending `EmundusRepository`, register joins so `buildLeftJoin()` and `applyFilters()` know about them:

```php
$this->joins[] = new Join(
    fromAlias: 'ef', fromKey: 'user',
    toTable: 'jos_users', toAlias: 'u', toKey: 'id',
    type: JoinTypeEnum::LEFT,
);
```

After that, `applyFilters(['u.name' => $search])` produces an `EXISTS (...)` subquery to keep grouping correct and avoid duplicate rows.

When the join is one-off and unusual, write it directly in the bespoke method (`WorkflowRepository::getWorkflowByFnum` does this).

## The `flush()` discipline

Repositories own persistence. The single most important rule:

> A persistence method either succeeds or throws. **Never** `catch { Log::add(); return; }`.

Anti-pattern (Anti-pattern #2 in `.claude/docs/conventions/code-conventions.md`):
```php
public function flush(Entity $e): void
{
    try {
        $this->db->insertObject(...);
    } catch (\Exception $ex) {
        Log::add($ex->getMessage());   // ← caller has no idea this failed
        return;                        // ← silent corruption guaranteed
    }
}
```

Canonical (`FilterRepository::flush`):
```php
public function flush(FilterEntity $entity): void
{
    if (empty($entity->getName())) {
        throw new \InvalidArgumentException('Filter name cannot be empty');
    }

    $data = (object) [/* … */];

    if (empty($entity->getId())) {
        if (!$this->db->insertObject($this->tableName, $data)) {
            throw new \RuntimeException('Failed to insert filter into database');
        }
        $entity->setId($this->db->insertid());
    } else {
        $data->id = $entity->getId();
        if (!$this->db->updateObject($this->tableName, $data, 'id')) {
            throw new \RuntimeException('Failed to update filter in database');
        }
    }
}
```

Three things to notice:
- Validation happens up front and throws `InvalidArgumentException`.
- The DB driver returns `false` on failure; we turn that into a `RuntimeException`.
- The entity's ID is set after a successful insert — callers get a fully-hydrated entity back.

If the operation spans multiple statements (e.g. `WorkflowRepository::save()` inserting the workflow row, its program associations, and its child step rows), wrap in `$this->db->transactionStart()` / `transactionCommit()` / `transactionRollback()` and re-throw on rollback so the caller knows the unit of work failed atomically. `OrganizationRepository::flush()` and `FilterRepository::flush()` are good references for single-statement persistence with strict pre-validation.

## When repository methods should return `null` vs throw

- **Lookup by ID/key returns `null`** when not found — the caller already expected "maybe missing":
  ```php
  public function getById(int $id): ?WorkflowEntity { /* return null if no row */ }
  ```
- **A required operation throws** when the precondition is violated:
  ```php
  public function flush(Entity $e): void   // throws on empty name, DB failure, etc.
  ```
- **A list returns `[]`** when empty — never `null`. Stable shape.

## Logging

`EmundusRepository::__construct` registers `com_emundus.repository.<name>` automatically when you pass a name. For Shape B (slim) repositories, do it yourself:

```php
Log::addLogger(['text_file' => "com_emundus.repository.{$domain}.php"], Log::ALL, ["com_emundus.repository.{$domain}"]);
```

Every `Log::add` line must include the identifier in scope:
```php
Log::add('Error fetching workflow by fnum ' . $fnum . ': ' . $e->getMessage(), Log::ERROR, 'com_emundus.repository.workflow');
```

A message without the `id`/`fnum` is useless three months later when grepping logs.

## Caching

`EmundusRepository::__construct` builds a `CacheController` under `com_emundus.<name>` when you pass a name. Use it for expensive aggregates that are stable across a short TTL:

```php
$key = 'workflow_programs';
$cached = $this->cache?->get($key);
if ($cached !== false) {
    return $cached;
}
$computed = /* … */;
$this->cache?->store($computed, $key);
return $computed;
```

**Never** use a timestamp as a cache key (`date('Y-m-d_His')`) — the cache will never be hit. Use a stable key + an explicit version (e.g. `EmundusHelperCache::getCurrentGitHash()` for code-versioned caches).

Purge the cache from the write side, not the read side:
```php
public function save(WorkflowEntity $workflow): bool
{
    /* … insert/update … */
    (new \EmundusHelperCache())->set('workflow_programs', null);
    return $saved;
}
```
