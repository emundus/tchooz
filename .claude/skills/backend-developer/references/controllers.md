# Controller internals

Deep dive on `Tchooz\Controller\EmundusController` — what happens behind the four-step recipe, how `#[AccessAttribute]` resolves, and which HTTP codes / response shapes to return when.

## What the base controller does for you

Look at `components/com_emundus/classes/Controller/EmundusController.php`. When the router invokes a task method (e.g. `?task=workflow.getworkflows`):

1. `execute($task)` is called by the Joomla MVC layer.
2. It first calls `enforceAccess($this, $task)`:
   - Reads `#[AccessAttribute]` instances on the method via reflection (cached per `Class::method`).
   - Reads `#[AccessAttribute]` instances on the class via reflection.
   - Method-level rules take priority over class-level rules. **If the method has rules, only those count**; class rules are *only* consulted when the method has none.
   - For each rule, calls `passesAccessAttribute()`. The request passes if **any** rule passes (OR semantics across attributes on the same target).
   - If no rule passes, throws `AccessException` with code `403`.
   - If neither method nor class has any rule, the method is **open** (no auth required). Use this only for genuinely public endpoints.
3. `parent::execute($task)` runs the actual method body. Return value goes into `$response`.
4. Any exception thrown inside the method is caught and turned into `EmundusResponse::fail($e->getMessage(), $e->getCode())`.
5. `sendJsonResponse($response)` is called in `finally`. It sets the `Content-Type` header, sets the HTTP status from `$response->code`, JSON-encodes, and `exit`s.

So a task method should `return EmundusResponse::ok(...)` (or throw). It must never `echo`, `header()`, `exit`, or `return $this->somethingElse()`.

## `#[AccessAttribute]` — the full matrix

```php
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class AccessAttribute
{
    public function __construct(
        public ?AccessLevelEnum $accessLevel = null,
        public array $actions = []   // [['id' => string|ActionEnum, 'mode' => CrudEnum], …]
    ) {}
}
```

### How a single rule passes (`passesAccessAttribute`)

| Both `accessLevel` and `actions` empty? | Result |
|---|---|
| Yes | **Deny** (defensive — a rule that asserts nothing is a config bug) |

Then:

| User is guest? | Result |
|---|---|
| Yes | **Deny** |

Then:

| `accessLevel` set? | Result |
|---|---|
| Yes, and `EmundusHelperAccess::as<Level>AccessLevel($userId)` returns false | **Deny** |

Then:

| `actions` empty? | Result |
|---|---|
| Yes | **Allow** (level check alone was enough) |
| No | Loop: for each `['id' => …, 'mode' => …]`, call `EmundusHelperAccess::asAccessAction($id, $mode, $userId)`. If **any** returns true, **Allow**. Otherwise **Deny**. |

### Multiple rules on the same target

Multiple `#[AccessAttribute(...)]` attributes on the same method are **OR-combined**. The request passes if any rule passes.

There is **no AND combinator at the attribute level**. To express "must be a Partner AND have read on workflow", encode it inside a single rule — that's exactly what `accessLevel + actions` does together (level check AND at-least-one-action check).

### Class-level fallback

```php
#[AccessAttribute(accessLevel: AccessLevelEnum::ADMINISTRATOR)]
class EmundusControllerSettings extends EmundusController
{
    public function getsettings(): EmundusResponse { /* uses class-level rule */ }

    #[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
    public function getpublicsettings(): EmundusResponse { /* overrides class rule */ }
}
```

Class-level rules are convenient for controllers where every method has the same access requirements. The moment a method needs a different rule, attach one to the method — the class rule is then ignored for that method.

### Canonical access patterns to recognise / reuse

Coordinator OR domain-scoped Partner:
```php
#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
    ['id' => 'workflow', 'mode' => CrudEnum::READ]
])]
```

Action-only (any user with the action permission):
```php
#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
    ['id' => ActionEnum::CUSTOM_REFERENCE, 'mode' => CrudEnum::CREATE]
])]
```

Multiple acceptable actions:
```php
#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [
    ['id' => ActionEnum::EXPORT_EXCEL, 'mode' => CrudEnum::CREATE],
    ['id' => ActionEnum::EXPORT_PDF,   'mode' => CrudEnum::CREATE],
])]
```

Public (no rule at all on method or class) — rare; prefer requiring at least `REGISTERED`:
```php
// (no #[AccessAttribute] anywhere)
public function publicping(): EmundusResponse { … }
```

## HTTP codes

`EmundusResponse` exposes the full RFC list as `HTTP_*` constants. Use them — never magic numbers. Frequent ones:

| Constant | Code | When |
|---|---|---|
| `HTTP_OK` | 200 | Success. |
| `HTTP_CREATED` | 201 | A POST created a resource. |
| `HTTP_BAD_REQUEST` | 400 | Missing or malformed input. Default for `fail()`. |
| `HTTP_UNAUTHORIZED` | 401 | User is unauthenticated. Rare in this codebase — guests are denied earlier. |
| `HTTP_FORBIDDEN` | 403 | Authenticated user lacks permission. `AccessException` uses this. |
| `HTTP_NOT_FOUND` | 404 | Resource doesn't exist. |
| `HTTP_CONFLICT` | 409 | Domain rule violation (duplicate entry status on workflow steps, race, …). |
| `HTTP_UNPROCESSABLE_ENTITY` | 422 | Validation failed on otherwise-well-formed input. |
| `HTTP_INTERNAL_SERVER_ERROR` | 500 | Unexpected failure. |

Pass the code as the exception's second argument; the base controller propagates it into the response:
```php
throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_WORKFLOW_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
throw new \DomainException(Text::_('COM_EMUNDUS_WORKFLOW_DUPLICATE_STATUS'), EmundusResponse::HTTP_CONFLICT);
```

## Input parsing — full table

`$this->app->input` is a `Joomla\Input\Input`. Always go through its typed getters; never `$_POST`, `$_GET`, raw `$_REQUEST`.

| Type | Getter | Notes |
|---|---|---|
| `int` | `getInt($name, $default)` | Cast safely. |
| `float` | `getFloat($name, $default)` | |
| `bool` | `getBool($name, $default)` | Truthy strings (`"1"`, `"true"`) accepted. |
| `string` (filtered) | `getString($name, $default)` | Default filter strips HTML. Use for labels, slugs. |
| `string` (CMD) | `getCmd($name, $default)` | Letters/digits/`-_` only — perfect for task names and identifiers. |
| `string` (raw) | `get($name, $default, 'raw')` | **Only** if you genuinely need HTML (e.g. an email body authored in the admin UI). Validate downstream. |
| Array | `get($name, [], 'array')` then iterate with typed casts | Or `json_decode($input->getString('json', '[]'), true)` when the client sends JSON. |
| File upload | `files->get($name)` | Treat with `FileSecurityService`. |

### Pattern: JSON payload from the frontend

The Vue frontend sends complex payloads as JSON strings (because Joomla's input bag is form-encoded by default):

```php
$workflow = json_decode($this->app->input->getString('workflow', '{}'), true);
$steps    = json_decode($this->app->input->getString('steps',    '[]'), true);
```

Always cast IDs to `int` and validate required keys *before* delegating:
```php
if (empty($workflow['id'])) {
    throw new \InvalidArgumentException(Text::_('COM_EMUNDUS_WORKFLOW_ID_REQUIRED'), EmundusResponse::HTTP_BAD_REQUEST);
}
$workflowId = (int) $workflow['id'];
```

## Response shape — stable JSON

`EmundusResponse::jsonSerialize()` always emits:
```json
{ "status": true|false, "msg": "...", "code": 200, "data": ..., "description": "" }
```

`data` can be a scalar, an array, an entity (entities should implement `JsonSerializable` if you return them directly), or any nested structure.

Rules:
- **Never** make a `data` key conditional on a flag inside the controller. Anti-pattern: `if ($cond) $payload['extra'] = …;`. If the key can be absent, document it; ideally emit `null` or `[]`.
- For lists, always include the count in the same envelope:
  ```php
  return EmundusResponse::ok([
      'datas' => $workflows,    // array of entities
      'count' => $totalCount,   // int — total ignoring pagination
  ]);
  ```
- The `msg` is user-facing — translate it: `EmundusResponse::ok($data, Text::_('COM_EMUNDUS_WORKFLOW_SAVED'))`.

## When to throw vs return `EmundusResponse::fail`

Prefer throwing. Reasons:
- The base controller's `try/catch` already converts exceptions into the right response shape.
- Throwing produces a stack trace in error logs; returning a `fail` does not.
- The exception class signals *what kind* of failure to anyone reading the trace.

Return `EmundusResponse::fail(...)` directly only when:
- You're handling a *non-error* business outcome that still has `status: false` (rare — usually you'd `ok` with a flag in `data`).
- You're inside a `catch` that already inspected the exception and wants to attach extra `data` to the response:
  ```php
  catch (BusinessException $e) {
      return EmundusResponse::fail($e->getMessage(), EmundusResponse::HTTP_CONFLICT, ['conflicts' => $e->getConflicts()]);
  }
  ```

## Common mistakes to refuse

These have shipped to production:

1. **Controller doing the service's work.** A 400-line `getworkflows` that builds HTML, runs SQL, and reads files. Extract into a service. (Rule 4 + Anti-pattern #6 in `.claude/docs/conventions/code-conventions.md`.)
2. **Mixing `#[AccessAttribute]` with runtime `if (!asXAccess) throw`.** Two sources of truth → drift. Pick one — the attribute is the new convention.
3. **`require_once` inside a task method.** The autoloader handles `Tchooz\` namespaces; if you can't autoload it, the file probably belongs in `classes/` instead of where it currently lives.
4. **Catching `Throwable` to return a generic error.** You lose diagnostics. Let it bubble; the base controller logs it.
5. **`echo`, `header()`, `die`, or `exit` inside a method.** The base controller owns response delivery.
6. **Returning an array of `['status' => true, 'msg' => …]` instead of `EmundusResponse::ok(...)`.** Legacy controllers still do this; new code must not.
