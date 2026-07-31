---
name: backend-developer
description: Generates and edits Tchooz / eMundus PHP backend code following the project's layered architecture (Controller, Service, Repository, Entity, Factory, Subscriber, Transformer, Enum). Use this skill whenever the user asks to implement, write, or modify backend code in `components/com_emundus/classes/` or `components/com_emundus/controllers/` — adding an endpoint, a service method, a repository query, an entity, a factory, a subscriber, an enum, or an access-control rule. Trigger it on phrases like "add a backend endpoint", "implement the controller", "write the service", "create the repository", "add the entity", "wire up the subscriber", "add an AccessAttribute", or anything involving `Tchooz\`, `EmundusResponse`, `#[AccessAttribute]`, or `#[TableAttribute]`. Do not wait for the user to ask for it explicitly — the layer boundaries and access-control conventions here are non-obvious, and bypassing them creates regressions that have shipped before.
---

# Tchooz backend developer

This skill writes PHP code that fits inside Tchooz's layered architecture. It answers the actual *how do I write it* question.

---

## Before writing any code

1. **Re-read `CLAUDE.md`** (already in context). It defines the namespace, folder layout, command shortcuts, and base classes.
2. **`grep` for existing code that does the same thing.** Reuse beats reimplementation every time. The canonical reuse map is in `.claude/docs/conventions/code-conventions.md` Rule 3.
3. **Decide which layers you actually need.** Not every feature needs all eight. The `architectural-designer` skill is the source of truth for that decision; if you skip it, at minimum work through the "Layer decision table" in §1 below.
4. **Match the workflow in §2** to whether you are *creating* a layer (new file) or *updating* one (adding to an existing file).

---

## Two non-negotiable backend rules

These two rules are responsible for most of the regressions the backend has shipped. Apply them every time without asking. The full pattern catalogue lives in `references/errors-and-io.md`; the rules are stated here because they are non-negotiable.

### Rule A — Failure is explicit, never silent

A persistence or business method either succeeds or throws. **Never** `catch { Log::add(); return; }` — the caller has no way to know it failed, counters get incremented, success is reported, and three months later 30% of records have a broken state.

```php
// FORBIDDEN
try { $repo->flush($entity); }
catch (\Exception $e) { Log::add($e->getMessage()); return; }

// CANONICAL — see FilterRepository::flush, OrganizationRepository::flush
if (!$this->db->insertObject($this->tableName, $data)) {
    throw new \RuntimeException(Text::_('COM_EMUNDUS_FILTER_INSERT_FAILED'));
}
```

The layer that catches must either recover meaningfully (with a real plan, not just a log line) or rethrow with context. See `references/errors-and-io.md` §1–§3 for the full pattern, including transactions and the channel convention.

### Rule B — The controller orchestrates, it does not do

A controller task method does exactly four things, in this order:

```
1. Check access (#[AccessAttribute] — declarative, not runtime if/else)
2. Parse and validate input ($this->app->input typed getters)
3. Call the service / repository / pipeline that does the work
4. Return EmundusResponse::ok($data) or throw (the base controller wraps)
```

If it contains `Spreadsheet`, `DataValidation`, `unlink()`, `glob()`, `preg_match` on business data, raw SQL (`$db->setQuery(...)`), or filesystem iteration — the controller is doing the service's job. Extract.

**Hard ceiling**: 250 lines per controller method. Soft target: 150. `require_once` inside a controller method is always wrong (autoloader bypass — Tchooz's `Tchooz\` namespace is autoloaded).

Canonical: `EmundusControllerReference::generate()` (4 useful lines after auth, delegates to `InternalReferenceService`), `EmundusControllerWorkflow::getworkflow()` (2 lines of input parsing, one delegation, one return).

### Backend anti-patterns (refuse while writing, refuse in review)

Each of these has shipped to production and had to be reverted or rewritten:

1. `try { } catch { Log; return; }` in persistence — silent half-write (violates Rule A)
2. `catch (\Throwable)` without a `$e` logged with file/line/context — lost diagnostics
3. Controller method > 200 lines — service work in the wrong layer (violates Rule B)
4. `require_once` inside a controller method — autoloader bypass
5. `mkdir`, `unlink`, `glob`, `copy` in a controller — file lifecycle in wrong layer
6. `$db->setQuery(...)` in a controller — data access in wrong layer
7. `if ($cond) $out['key'] = ...;` inside `toArray()` / `jsonSerialize()` — unstable JSON
8. `date('Y-m-d_His')` as a cache key — cache never reused
9. New constructor parameter without a default on a shared signature — fan-out break
10. Mixing `#[AccessAttribute]` with a runtime `if (!asXAccess) throw AccessException` doing the same thing — two sources of truth, drift inevitable

---

## 1. The eight layers — what each one is, when to create or update

Tchooz organises backend code under `components/com_emundus/classes/` in eight layers, each a folder. Every layer has a single responsibility. Mixing responsibilities — a controller that runs SQL, a repository that sends emails — is the most common cause of regressions in this codebase.

| Layer | Folder | Responsibility (in one sentence) | Create a new file when… | Update an existing file when… |
|---|---|---|---|---|
| **Entity** | `Entities/<Domain>/<Name>Entity.php` | Holds one record as a typed in-memory object — no DB, no I/O, no business logic. | The domain doesn't have an entity yet, or you need a *new* kind of record (e.g. `StepEntity` alongside `WorkflowEntity`). | You add a field/property to the underlying table, change a getter/setter, or add a small in-memory invariant (e.g. `addStep()` rejecting duplicates). |
| **Repository** | `Repositories/<Domain>/<Name>Repository.php` | Reads and writes the entity to the database — the *only* layer that issues SQL or `quoteName()`. | A new entity exists with no repository yet. One repository per main table (joined tables can be reached from the same repository). | You need a new query (`getByCampaignId`, `getActive…`), a new flush/save behaviour, or a new filter in `applyFilters()`. |
| **Factory** | `Factories/<Domain>/<Name>Factory.php` | Converts raw DB rows (or arrays) into populated entities, loading relations when asked. | The entity has relations to load (other entities, enums, JSON columns) or the DB→entity mapping is non-trivial. Skip if the mapping is a flat 1-1. | The entity gains a new relation, a new optional load path (`loadChilds`), or a new alternate constructor (`fromArray`, `fromDbObject`). |
| **Service** | `Services/<Domain>/<Name>Service.php` | Multi-step business logic that orchestrates repositories, external APIs, emails, files, calculations. | The feature has logic beyond CRUD — sending emails, calling Yousign/Zoom/FileMaker, computing derived values, running a pipeline. | You add a new business operation in the same domain (`generateShortReference()` alongside `generateReference()`). |
| **Controller** | `controllers/<domain>.php` (legacy folder, but new classes extend `Tchooz\Controller\EmundusController`) | HTTP entry point — checks access, parses input, calls the service/repository, returns `EmundusResponse`. **Nothing else.** | You need a new HTTP endpoint and there is no controller for the domain. One controller per domain. | You add a new task method (`getworkflow`, `updateworkflow`, `duplicateworkflow`). Always under 250 lines, target 150. |
| **Subscriber** | `Subscribers/<Name>Subscriber.php` | Reacts to Joomla / Tchooz events (`onAfterStatusChange`, `onAfterCampaignCandidature`, …). | The feature must run *as a side effect* of an event somewhere else in the system. | A new event needs to trigger an already-existing reaction in the same subscriber. |
| **Transformer** | `Transformers/<Name>Transformer.php` | Format conversion: date strings, IBAN, currency, phone, choice labels. Pure and stateless. | You hit the same format-conversion in two places. | The same format gains a new variant (e.g. a new currency code). |
| **Enum** | `Enums/<Domain>/<Name>Enum.php` (or top-level `<Name>Enum.php` for cross-domain) | A closed set of values that appears in the entity or database. | The feature has fixed statuses/types/modes (`StateEnum`, `FilterModeEnum`, `CrudEnum`). | A new case must be added to an existing enum. |

If a piece of logic doesn't cleanly fit any of these — that's the warning. You probably haven't split the responsibility yet. Re-read the user request and look for the two concerns that got merged.

> **Where does logic go?** Touches HTTP/input/output? → controller. Touches data persistence? → repository. Touches multi-step business workflow? → service. Touches construction of an entity from raw data? → factory. Touches an external event reaction? → subscriber. Touches data format conversion? → transformer. (Same rule lives in `.claude/docs/conventions/code-conventions.md`; the duplication is intentional because it is *the* core decision.)

---

## 2. Workflow for adding a backend feature

The natural creation order (each layer depends on the previous) is:

```
Enum → Entity → Repository → Factory → Service → Controller → Subscriber
```

For each layer you decide to touch, work through the matching section below. If a layer already exists, jump straight to "Updating" inside that section.

### 2.1 Enum

**Create when**: the new feature introduces a fixed set of values (statuses, modes, types, levels).

**Template**:
```php
<?php
namespace Tchooz\Enums\<Domain>;

enum <Name>Enum: string  // use `: int` only when the DB stores integers (rare; `AccessLevelEnum` is one)
{
    case ACTIVE   = 'active';
    case ARCHIVED = 'archived';
}
```

Add label/translation logic with a `getLabel()` or `getMethodName()` method only if you have a concrete consumer. See `Tchooz\Enums\AccessLevelEnum::getMethodName()` for the canonical pattern.

**Update when**: a new case must be added. Treat enum cases as an API — adding is safe, renaming or removing breaks consumers. If a renaming is required, grep for the old case name across the repo first.

### 2.2 Entity

**Create when**: the domain represents a record (one row, one object).

**Template** (modern style with ORM attributes, see `Tchooz\Entities\Reference\InternalReferenceEntity`):
```php
<?php
namespace Tchooz\Entities\<Domain>;

use Tchooz\Attributes\ORM\Column;
use Tchooz\Attributes\ORM\Table;
use Tchooz\Attributes\ORM\Types;

#[Table(name: '#__emundus_<table>')]
class <Name>Entity
{
    private int $id;

    #[Column(length: 255)]
    private string $label;

    #[Column(type: Types::INTEGER)]
    private int $published;

    public function __construct(int $id = 0, string $label = '', int $published = 1)
    {
        $this->id        = $id;
        $this->label     = $label;
        $this->published = $published;
    }

    public function getId(): int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getLabel(): string { return $this->label; }
    public function setLabel(string $label): void { $this->label = $label; }

    public function isPublished(): bool { return $this->published === 1; }
    public function setPublished(bool $published): void { $this->published = $published ? 1 : 0; }
}
```

Rules:
- No DB calls, no `Factory::getContainer()`, no `Log::add()` except in the constructor to register the logger.
- Getters return typed values; setters accept typed values; boolean fields expose `isFoo()`.
- Add small *in-memory* invariants here (e.g. `WorkflowEntity::addStep()` rejecting steps with conflicting entry statuses). Anything that needs a DB lookup belongs in a service or repository.

**Update when**: a property is added/changed. Update the constructor signature (with a default that preserves prior behaviour — Anti-pattern #9 above), the getter/setter, and any consumer that destructures from the entity.

### 2.3 Repository

**Create when**: a new entity has no repository. One repository per main table.

**Template** (with `TableAttribute` ORM hint):
```php
<?php
namespace Tchooz\Repositories\<Domain>;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Attributes\TableAttribute;
use Tchooz\Entities\<Domain>\<Name>Entity;
use Tchooz\Factories\<Domain>\<Name>Factory;
use Tchooz\Repositories\EmundusRepository;
use Tchooz\Repositories\RepositoryInterface;

#[TableAttribute(table: 'jos_emundus_<table>', alias: 'e<short>', columns: [
    'id', 'label', 'published'
])]
class <Name>Repository extends EmundusRepository implements RepositoryInterface
{
    private <Name>Factory $factory;

    public function __construct($withRelations = true, $exceptRelations = [])
    {
        parent::__construct($withRelations, $exceptRelations, '<domain>', self::class);
        $this->factory = new <Name>Factory();
    }

    public function getFactory(): ?object { return $this->factory; }

    public function getById(int $id): ?<Name>Entity { /* … */ }
    public function delete(int $id): bool          { /* … */ }
    public function flush(<Name>Entity $entity): void { /* insert or update; throw on failure */ }
}
```

Read `references/repositories.md` for the full pattern: when to extend `EmundusRepository` (you get `getList()`, `getCount()`, `applyFilters()`, joins, caching for free) versus when to stay slim (`WorkflowRepository` uses `TraitTable` directly).

**Rules** (these have shipped bugs when ignored — they are concrete forms of Rule A above):
- `flush()` either persists or throws. **Never** `catch { Log::add(); return; }` — that hides corruption.
- Use `Tchooz\Attributes\TableAttribute` so `getTableName(self::class)` works through `TraitTable`; never hardcode `'jos_emundus_...'` inside queries.
- Validate field names against `$this->columns` before injecting into a `where()` clause — `EmundusRepository::applyFilters()` already does this; if you write raw queries, do it manually.
- Quote everything (`$this->db->quote(...)`, `$this->db->quoteName(...)`). No string concatenation of unvalidated input.
- Logger group: `com_emundus.repository.<domain>` — registered in the constructor by `EmundusRepository`.

**Update when**: a new query is needed. Add a public method named after what it returns (`getActiveStepsByWorkflowId`, not `findAll2`). If the filter is general, extend `applyFilters()` rather than writing a one-off query.

### 2.4 Factory

**Create when**: the entity has relations to hydrate (other entities, enums from raw values, JSON columns expanded into typed objects) or when the same construction logic recurs in 2+ places.

**Template** (see `Tchooz\Factories\Workflow\WorkflowFactory`):
```php
<?php
namespace Tchooz\Factories\<Domain>;

use Tchooz\Entities\<Domain>\<Name>Entity;

class <Name>Factory
{
    /**
     * @param array $dbObjects raw rows from the database
     * @return array<<Name>Entity>
     */
    public static function fromDbObjects(array $dbObjects, bool $withRelations = true): array
    {
        $entities = [];
        foreach ($dbObjects as $row)
        {
            $entities[] = new <Name>Entity(
                id:       (int) $row->id,
                label:    $row->label,
                published: (int) $row->published,
                // …load relations if $withRelations
            );
        }
        return $entities;
    }
}
```

A static factory is the project convention. The repository calls `Factory::fromDbObjects(...)` after fetching. The factory may inject other repositories to load relations (see `WorkflowFactory::fromDbObjects` loading steps + campaigns).

**Update when**: a new relation must be loaded, or a new alternate constructor (`fromArray`, `fromHttpPayload`) is needed.

### 2.5 Service

**Create when**: the feature has multi-step logic beyond CRUD — sending emails, calling external APIs, generating files, computing derived values, dispatching events, transactional sequences.

**Template** (see `Tchooz\Services\Emails\EmailService`, `Tchooz\Services\Reference\InternalReferenceService`):
```php
<?php
namespace Tchooz\Services\<Domain>;

use Joomla\CMS\Factory;
use Joomla\CMS\Log\Log;
use Tchooz\Repositories\<Domain>\<Name>Repository;

class <Name>Service
{
    public function __construct(
        private <Name>Repository $repository = new <Name>Repository()
    ) {
        Log::addLogger(['text_file' => 'com_emundus.service.<domain>.php'], Log::ALL, ['com_emundus.service.<domain>']);
    }

    public function generateSomething(int $id): string
    {
        $entity = $this->repository->getById($id);
        if ($entity === null) {
            throw new \DomainException("Entity {$id} not found");
        }
        // multi-step logic …
        return $result;
    }
}
```

Rules:
- Inject dependencies through the constructor — `EmailService` accepts an optional `MailerInterface`, `InternalReferenceService` accepts a `DateProvider` and an `ApplicationFileRepository`. This makes the service testable without monkey-patching globals.
- The class name must be honest about what it does. `EmailService` sends emails; it is not a `Manager`. (Rule 1 in `.claude/docs/conventions/code-conventions.md`.)
- Throw with context on failure. Never log-and-return when the caller depends on the outcome.

**Update when**: a new business operation belongs to the same domain. If you find yourself adding operations from a different domain, that's a sign you need a new service.

### 2.6 Controller — the four-step recipe

This is the layer the user asks about most, and the layer where the most regressions have shipped. Controllers do **only** these four things:

```
1. Check access (#[AccessAttribute] on the method — declarative, not runtime if/else)
2. Parse and validate input ($this->app->input->getInt / getString / …)
3. Call the service / repository / factory — never run SQL, never touch files
4. Return EmundusResponse::ok($data) or EmundusResponse::fail($msg, $code)
```

Anything else — SQL, file I/O, `Spreadsheet`, `preg_match` on business data, `require_once`, `mkdir`, `glob` — belongs to the service or repository. Hard ceiling: 250 lines per controller method; target 150. (Rule B above.)

**Controller class skeleton**:
```php
<?php
defined('_JEXEC') or die('Restricted access');

use Tchooz\Attributes\AccessAttribute;
use Tchooz\Controller\EmundusController;
use Tchooz\EmundusResponse;
use Tchooz\Enums\AccessLevelEnum;
use Tchooz\Enums\CrudEnum;
use Tchooz\Enums\Actions\ActionEnum;

class EmundusController<Domain> extends EmundusController
{
    private <Name>Service $service;

    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->service = new <Name>Service();
    }
}
```

Three things happen automatically because you extend `EmundusController`:
- `execute($task)` (see `classes/Controller/EmundusController.php`) wraps every action in a `try/catch`, enforces `#[AccessAttribute]`, and calls `sendJsonResponse()`. **You return the response; the base class sends it.**
- Throw any exception inside a task method and it becomes a `EmundusResponse::fail($e->getMessage(), $e->getCode())` automatically. Use `\InvalidArgumentException` for bad input, `\DomainException` for business rule violations, `Symfony\Component\OptionsResolver\Exception\AccessException` for fine-grained access denials.
- `$this->user` is the current `Joomla\CMS\User\User` (or guest); `$this->app->input` is the input bag.

**AccessAttribute — declarative access control**

`#[AccessAttribute]` is repeatable. Each instance is *one* rule, and **the request passes if any of the attached rules pass** (OR semantics). Method-level rules take priority over class-level rules. A method with no attribute and no class-level attribute is open.

Two parameters:

| Parameter | Type | Meaning |
|---|---|---|
| `accessLevel` | `AccessLevelEnum\|null` | Required access level (`COORDINATOR`, `PARTNER`, `APPLICANT`, …). The base controller calls `EmundusHelperAccess::as<Level>AccessLevel($userId)`. |
| `actions` | `array<{id: ActionEnum\|string, mode: CrudEnum}>` | Fine-grained per-action permission. *Any* matching action grants access. |

Patterns you will use 95% of the time:

```php
// Coordinator OR Partner-with-read on "workflow"
#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
    ['id' => 'workflow', 'mode' => CrudEnum::READ]
])]
public function getworkflows(): EmundusResponse { /* … */ }

// Coordinator-only update
#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
    ['id' => 'workflow', 'mode' => CrudEnum::UPDATE]
])]
public function updateworkflow(): EmundusResponse { /* … */ }

// Any partner with permission to create a custom reference
#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
    ['id' => ActionEnum::CUSTOM_REFERENCE, 'mode' => CrudEnum::CREATE]
])]
public function generate(): EmundusResponse { /* … */ }
```

Guidance:
- Prefer `ActionEnum::*` over the raw string for `id` whenever the action exists in the enum (`Tchooz\Enums\Actions\ActionEnum`). The string form is supported for legacy actions only.
- Multiple `#[AccessAttribute(...)]` lines = OR. There is no AND combinator at the attribute level — encode the AND inside the rule's `actions` array (the access logic requires both the level AND one of the actions).
- Guests are always denied when any rule is declared.
- **Do not** add a runtime `if (!EmundusHelperAccess::asCoordinatorAccessLevel(...)) throw new AccessException()` *and* an `#[AccessAttribute]` doing the same thing — that's "Rule 3: one source of truth" violated. Pick the attribute.

**Task method recipe**

```php
#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
public function updatesomething(): EmundusResponse
{
    // STEP 1 — Access. Already enforced by the attribute above. Nothing to do here.

    // STEP 2 — Parse input. Use $this->app->input typed getters; never trust raw $_POST.
    $id    = $this->app->input->getInt('id', 0);
    $label = $this->app->input->getString('label', '');

    if (empty($id)) {
        // Throw — the base controller turns this into EmundusResponse::fail with code from $e->getCode()
        throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_SOMETHING_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
    }

    // STEP 3 — Delegate the work. The service knows how; the controller does not.
    $entity = $this->service->updateSomething($id, $label);

    // STEP 4 — Return. EmundusResponse::ok wraps data + status + code into the JSON envelope.
    return EmundusResponse::ok($entity, Text::_('COM_EMUNDUS_SOMETHING_UPDATED'));
}
```

If you find yourself doing any of these inside the controller method, stop and extract into a service:
- A `foreach` over rows building HTML
- A `Spreadsheet`, `unlink`, `mkdir`, `glob`, `file_get_contents`
- A `preg_match` on business data
- A `$db->setQuery(...)` (controller never touches the DB directly)
- A `require_once` (autoloader bypass — Anti-pattern #4 above)

**Response shape**

`EmundusResponse::ok($data, $msg = '', $code = 200)` and `EmundusResponse::fail($msg, $code = 400, $data = [])`. Both serialize through `jsonSerialize()` to:
```json
{ "status": true|false, "msg": "...", "code": 200, "data": ..., "description": "" }
```

Keys are stable — never make a key optional. If a value is empty, return `[]` / `null` / `0`, not nothing (Anti-pattern #5: unstable JSON).

**Input parsing cheat-sheet**

| Need | Use |
|---|---|
| Integer | `$this->app->input->getInt('id', 0)` |
| String | `$this->app->input->getString('label', '')` |
| Raw / HTML | `$this->app->input->get('html', '', 'raw')` (rarely; almost never trust user HTML) |
| JSON array | `json_decode($this->app->input->getString('items', '[]'), true)` then validate types |
| Boolean | `$this->app->input->getBool('flag', false)` |
| Float | `$this->app->input->getFloat('score', 0.0)` |

Never use `$_POST`, `$_GET`, `JFactory::getApplication()->input` directly when `$this->app->input` is available (it is, via the parent constructor).

### 2.7 Subscriber

**Create when**: the feature reacts to an event somewhere else in the system. Subscribers replace the older "plugin that listens to onAfterX" pattern for behaviour owned by `com_emundus`.

**Template** (see `Tchooz\Subscribers\GenerateReferenceSubscriber`):
```php
<?php
namespace Tchooz\Subscribers;

use Joomla\CMS\Event\GenericEvent;
use Joomla\CMS\Log\Log;

class <Name>Subscriber extends EmundusSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [
            'onAfterStatusChange' => 'doSomething',
        ];
    }

    public function doSomething(GenericEvent $event): void
    {
        try {
            $data = $event->getArguments();
            if (empty($data['fnum'])) {
                return;
            }
            // delegate to a service — the subscriber is just a glue layer
            (new <Name>Service())->doSomething($data['fnum']);
        } catch (\Exception $e) {
            Log::add('Error in <Name>Subscriber: ' . $e->getMessage(), Log::ERROR);
        }
    }
}
```

Register the new subscriber in `Tchooz\Providers\EmundusSubscriberProvider::register()`:
```php
$subject->addSubscriber(new <Name>Subscriber('<name>'));
```

**Update when**: a new event must trigger an existing reaction. Add to `getSubscribedEvents()` and route to the right handler. Don't bloat a single handler — split into named methods per event.

### 2.8 Transformer

**Create when**: a value needs format conversion, and the same conversion is needed in 2+ places.

**Template** (see `Tchooz\Transformers\IbanTransformer`):
```php
<?php
namespace Tchooz\Transformers;

use Tchooz\Interfaces\FabrikTransformerInterface;

class <Name>Transformer implements FabrikTransformerInterface
{
    public function transform(mixed $value, array $options = []): string
    {
        // pure, stateless
        return $convertedValue;
    }
}
```

Keep transformers pure and stateless. They are commonly invoked from Fabrik plugins via the `FabrikTransformerInterface`.

**Update when**: the transformer must support a new variant of the same format (a new locale, a new option flag).

---

## 3. Cross-cutting patterns (used in every layer)

### Logging

```php
Log::addLogger(['text_file' => 'com_emundus.<layer>.<domain>.php'], Log::ALL, ['com_emundus.<layer>.<domain>']);
Log::add('Descriptive message with context (id, fnum, ...)', Log::ERROR, 'com_emundus.<layer>.<domain>');
```

`Log::ERROR` for failures; `Log::WARNING` for unexpected-but-recoverable; `Log::INFO` for milestones. Always include the identifier (`fnum`, `id`) so the line is grep-able. Logs without context are useless three months later.

### Exceptions

Throw with a meaningful exception class and a translated message:
```php
throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
throw new \DomainException(Text::_('COM_EMUNDUS_WORKFLOW_CONFLICT_STATUS'), EmundusResponse::HTTP_CONFLICT);
throw new AccessException(Text::_('ACCESS_DENIED'), EmundusResponse::HTTP_FORBIDDEN);
```

The base controller turns the exception into an `EmundusResponse::fail` automatically. The HTTP code comes from `$e->getCode()`. Use the `EmundusResponse::HTTP_*` constants — never magic numbers.

### Translations

Every user-facing string goes through `Text::_('COM_EMUNDUS_…')` (backend) or `translate('COM_EMUNDUS_…')` (frontend). Prefix is `COM_EMUNDUS_`. Add the key to **both** `language/fr-FR/fr-FR.com_emundus.ini` and `language/en-GB/en-GB.com_emundus.ini` **in the same commit**. Anti-pattern #10 (a key added only to FR → raw key in production UI) has shipped before.

### Reuse before reimplementing

Before writing a helper, grep:
```bash
grep -rn "<concept>" components/com_emundus/{helpers,classes,libraries/emundus} | head -20
```

Canonical helpers that already exist (don't reimplement):
- Date / IBAN / phone / currency normalization → `Tchooz\Transformers\*Transformer`
- Fnum lookup → `EmundusHelperFiles::getIdFromFnum()`
- Language resolution → `Tchooz\Factories\Language\LanguageFactory`
- Cache key on code version → `EmundusHelperCache::getCurrentGitHash()`
- Response shaping → `EmundusResponse::ok/fail`
- Access control → `#[AccessAttribute]`

The full map lives in `.claude/docs/conventions/code-conventions.md` Rule 3.

---

## 4. Mental checklist before pushing

Quick pre-push pass. For a thorough review (PR audit, blocker catalogue, pass order), invoke `code-review` instead.

- [ ] Does the controller method only do {access, parse, delegate, return}? (Rule B)
- [ ] Does every `flush`/`save`/`delete` either succeed or throw — never log-and-return? (Rule A)
- [ ] Are all `#[AccessAttribute]` rules necessary, OR-combinable, and consistent with siblings?
- [ ] Are all user-facing strings going through `Text::_('COM_EMUNDUS_…')` with the key added to both FR and EN INI files?
- [ ] Is every class name honest about the class body when read aloud? (`.claude/docs/conventions/code-conventions.md` Rule 1)
- [ ] Is there a duplicate declaration of the same fact (controller AND service AND enum)? (`.claude/docs/conventions/code-conventions.md` Rule 2)
- [ ] Is the JSON output stable (same keys every time)?
- [ ] Are constructor params backwards-compatible (defaults on new params)?
- [ ] Are tests in scope? → run `test-writer` skill.

---

## 5. Where to look next

| You need to … | Read |
|---|---|
| Cross-cutting rules — naming honesty, source-of-truth, reuse, translation policy | `.claude/docs/conventions/code-conventions.md` |
| Plan a new feature's file layout before coding | `architectural-designer` |
| Write tests for what you implemented | `test-writer` |
| Run a full review pass on a branch / PR | `code-review` |
| Repository internals — ORM attributes, joins, caching, list/count, `flush()` discipline | `references/repositories.md` |
| Controller internals — `#[AccessAttribute]` matrix, response codes, edge cases | `references/controllers.md` |
| Event subscriber registration and dispatch | `references/subscribers.md` |
| Error handling, transactions, file lifecycle, cache keys, stable JSON | `references/errors-and-io.md` |

