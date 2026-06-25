# Factory pattern - Resume

## 🏗️ Folder structure

```
Factories/
├── Cache/
│   └── RelationCache.php          ← Static identity map (in-memory cache for relations)
├── AbstractFactory.php            ← Abstract class to extends for each factory
├── BatchDBFactory.php             ← New interface for batch loading (fromDbObjects)
├── DBFactory.php                  ← Only for backward compatibility (fromDbObject)
├── EmundusFactory.php             ← Deprecated, to migrate to AbstractFactory (compat layer)
```

## 📐 Classes diagram

```
                    ┌──────────────┐
                    │  DBFactory   │ (interface)
                    │ fromDbObject │
                    └──────┬───────┘
                           │ extends
                    ┌──────┴────────────┐
                    │  BatchDBFactory   │ (interface)
                    │  fromDbObjects    │
                    └──────┬────────────┘
                           │ implements
                    ┌──────┴────────────┐
                    │  AbstractFactory  │ (abstract)
                    │                   │
                    │ • RelationCache   │←── Static cache (Identity Map)
                    │ • resolveRelations│
                    │ • preloadRelations│←── Override for batch loading
                    │ • fromDbObject    │
                    │ • fromDbObjects   │
                    └──────┬────────────┘
                           │
          ┌────────────────┼────────────────┐
          │                                 │
        ┌─┴──────────┐                   ┌──┴───────────────┐
        │ Campaign   │                   │ApplicationFile   │
        │ Factory    │                   │   Factory        │
        └────────────┘                   └──────────────────┘
```

## 🔑 Key features

### 1. **RelationCache** — static Identity Map
```php
// In memory cache during php execution
// Avoid to re-query the same relation multiple times for different entities sharing the same foreign key

// Exemple : 100 application files, 3 unique campaigns → 3 DB queries instead of 100
RelationCache::remember('campaign', $campaignId, fn() => $repo->getById($id));

// Pattern "remember" : get or create
RelationCache::remember($namespace, $key, $callback);

// Preload en batch
RelationCache::preload('campaign', [1 => $campaign1, 2 => $campaign2]);

// Clear (only for tests)
RelationCache::flush();
```

### 2. **Declarative relationship management**

Each factory declares its relationships in a constant:
```php
class ApplicationFileFactory extends AbstractFactory
{
    public const RELATION_CAMPAIGN = 'campaign';
    public const RELATION_STATUS   = 'status';
    public const RELATION_USER     = 'user';

    // Default relations
    protected const RELATIONS = [
        self::RELATION_CAMPAIGN,
        self::RELATION_STATUS,
        self::RELATION_USER,
    ];
}
```

### 3. **Batch loading avec preloading**

`fromDbObjects()` working in 2 steps :
```
Step 1 : preloadRelations()
   → Collect the unique IDs (campaign_id, status, ...)
   → Loads in a single pass and populates the RelationCache

Step 2 : Build of entities
   → For each object, `loadRelationsForObject()` checks the cache
   → 0 additional queries (the cache is "hot")
```

### 4. **4 abstract methods to be implemented**

To create a new factory:

```php
class MonEntityFactory extends AbstractFactory
{
    protected const RELATIONS = ['relation_a', 'relation_b'];
    protected string $cacheNamespace = 'mon_entity';

    // 1. Build the entity
    protected function buildEntity(object $dbObject, array $relations): MonEntity
    {
        return new MonEntity(
            name: $dbObject->name,
            relationA: $relations['relation_a'] ?? null,
        );
    }

    // 2. Load a single relation
    protected function loadRelation(string $relation, object $dbObject): mixed
    {
        return match ($relation) {
            'relation_a' => $this->getRepoA()->getById($dbObject->a_id),
            default => null,
        };
    }

    // 3. Cache key for each relation
    protected function getRelationCacheKey(string $relation, object $dbObject): string|int
    {
        return match ($relation) {
            'relation_a' => (int) $dbObject->a_id,
            default => spl_object_id($dbObject),
        };
    }

    // 4. (Optionnal) Preload batch
    protected function preloadRelations(array $dbObjects, array $relationsToLoad): void
    {
        if (in_array('relation_a', $relationsToLoad)) {
            $ids = array_unique(array_map(fn($o) => $o->a_id, $dbObjects));
            $cacheNs = $this->cacheNamespace . '.relation_a';
            foreach ($ids as $id) {
                if (!RelationCache::has($cacheNs, $id)) {
                    RelationCache::set($cacheNs, $id, $this->getRepoA()->getById($id));
                }
            }
        }
    }
}
```

### 6. **Cache debug**

```php
// View cache statistics (number of entries per namespace)
$stats = RelationCache::stats();
// ['campaign' => 3, 'status' => 5, ...]

// Clear a specific namespace
RelationCache::forget('campaign');

// Clear everything (between tests)
RelationCache::flush();
```

## 💡 More details

1. **Lazy-loading of repositories** — Instantiated only when necessary
2. **Injection setters** — To inject mocks into a test (`setCampaignRepository()`)
3. **`RelationCache::remember()`** — The ‘get or create’ pattern that simplifies the code
4. **`RelationCache::preload()`** — To pre-populate the cache in batch mode
