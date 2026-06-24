# Errors, transactions, I/O, caching

Deep dive on the patterns that cover the most expensive shipped-to-prod mistakes in Tchooz: silent corruption, half-writes, broken caches, file lifecycle leaks. Read this when handling errors, persistence, logging, file I/O, or cache in any backend layer.

## 1. Failure is explicit, never silent

The forbidden pattern:

```php
// NEVER
try { $repo->flush($entity); }
catch (\Exception $e) {
    Log::add($e->getMessage());
    return;
}
```

The caller has no way to know it failed. Counters get incremented, success is reported, data is half-persisted. Three months later, 30% of records have a broken state. **Log + return = recovery without a recovery plan.**

The rule: a method either does its job or throws. The layer that catches must:
- Recover meaningfully (have an actual recovery strategy), **or**
- Re-throw with context.

**Canonical patterns**:
- `OrganizationRepository::flush()`, `ContactRepository::flush()`, `FilterRepository::flush()` — throw `\InvalidArgumentException` / `\RuntimeException` with translation-keyed messages.
- `WorkflowRepository::save()` — multi-step persistence (workflow row + program associations + step rows). On step-conflict validation failure, throws `\InvalidArgumentException` *before* touching the database; on DB exception, logs with context and returns `false` so the caller can react. (A stricter version of this pattern would wrap the multi-step write in an explicit transaction — see §3 below.)
- `ExcelService::export()` — propagates exceptions to the action / task system, which marks the task `FAILED` instead of swallowing.

## 2. Catch without context = invisible bug

Anytime an exception is caught, log it with what makes diagnosis possible — file, line, exception type, relevant input identifiers:

```php
catch (\Throwable $e) {
    Log::add(
        sprintf('[Domain] Operation failed for "%s": %s in %s:%d',
            $contextId, $e->getMessage(), $e->getFile(), $e->getLine()),
        Log::ERROR,
        'com_emundus.<domain>'
    );
    return EmundusResponse::fail(/* user-friendly */);
}
```

Channels follow the convention `com_emundus.<domain>` — `com_emundus.import`, `com_emundus.workflow`, `com_emundus.export`, etc. The grep-ability is non-negotiable.

**Watch for**: `catch (\Throwable)` without a `$e` variable. You just threw away every diagnostic.

The user gets a friendly message; the developer gets the stack trace. These are two different audiences — the catch block must serve both.

## 3. Transactions wrap the unit of work, not the leaf operation

In a pipeline that processes 100 rows, the transaction wraps **one row** (the unit you want to commit or rollback together), **not** the whole pipeline (you'd lose all 99 valid rows if row 100 fails) and **not** just the SQL INSERT (you'd commit the parent record but not its children).

A "unit of work" for a workflow save is `{workflow row + its program associations + its steps}`: rolling back partially leaves orphan program links pointing at no workflow. A unit of work for a row-by-row import is one row: rolling back everything when row 100 fails would discard 99 valid imports.

```php
$this->db->transactionStart();
try {
    $repo->flush($entity);
    foreach ($children as $child) {
        $childRepo->flush($child);
    }
    $this->db->transactionCommit();
} catch (\Throwable $e) {
    $this->db->transactionRollback();
    throw $e;   // or wrap and rethrow with context
}
```

If you can't decide where the boundary is, the test is: "if step N fails, what state do I want the data in?" The transaction wraps whatever produces that state.

## 4. Cache keys: derived from code, not from the clock

A file named `export_2026-04-29_141502.xlsx` is regenerated every call. A file named `export_a1b2c3d4.xlsx` is regenerated only when the underlying data or code changes.

**Canonical patterns**:
- `EmundusHelperCache::getCurrentGitHash()` returns the git HEAD in dev, the component manifest version in prod. Use this as a cache suffix for anything whose content depends on code.
- For data-dependent caches, derive the key from a hash of the input shape: `md5(json_encode($descriptors))`.

**Watch for**:
- `date('Y-m-d_His')` in a cached filename — cache never reused.
- Per-request cache regeneration without invalidation tied to the actual change.
- A user-facing "force refresh" button that exists because the cache key is wrong.

Purge the cache **from the write side**, not the read side:
```php
public function save(WorkflowEntity $workflow): bool
{
    /* … insert/update … */
    (new \EmundusHelperCache())->set('workflow_programs', null);
    return $saved;
}
```

## 5. File lifecycle lives in services, never controllers

`mkdir`, `unlink`, `glob`, `copy`, `rename` inside a controller method is **always** wrong. The controller doesn't own the file lifecycle — it asks a service for "the export URL" or "the import model file", and the service owns where the file lives, when it's cleaned up, and how it's named.

Anti-pattern: a controller that creates a temp dir, writes a file, returns the URL, and relies on someone else to clean up. The cleanup never happens.

**Canonical patterns**:
- `ExcelService::export()` owns the file path, the temp dir, and the cleanup hook.
- `UploadService` owns where uploaded files land and when they're cleaned up — controllers ask it for the storage location, they don't choose one.

## 6. Stable JSON output shape

`toArray()` / `jsonSerialize()` / `EmundusResponse::ok($data)` returns the same keys every time. Empty values stay present as `[]`, `null`, or `0` — never omitted.

```php
// Frontend can do this:
response.data.summary.failed         // always a number
response.data.summary.unknown_headers // always an array

// Instead of this:
response.data?.summary?.failed ?? 0
response.data?.summary?.unknown_headers ?? []
```

Multiply the second form by every consumer × every field. Fragility compounds.

**Watch for**:
- `if ($condition) $out['key'] = $value;` inside a `toArray()` method.
- A frontend with chains of `?.` and `??` to guess shape.
- Different keys returned by different endpoints of the same service.

**Exception**: it's OK to omit a key when its absence carries meaning (e.g. a field descriptor omits `format` when no format is declared — distinguishing "no format hint" from "empty format"). When you do this, document it in the docblock so the absence is a deliberate signal, not an accident.

## 7. Translation conventions for error and response messages

Even error messages go through `Text::_` / `Text::sprintf`:

```php
// ❌
return EmundusResponse::fail('Organization not found');

// ✅
return EmundusResponse::fail(Text::_('COM_EMUNDUS_ORGANIZATION_NOT_FOUND'));
```

And the key must exist in **both** `fr-FR.com_emundus.ini` and `en-GB.com_emundus.ini`. Otherwise English users see the raw key in production.

## 8. Quick reference — when to throw vs return false/null

| Situation | Action |
|---|---|
| Operation that should always succeed (flush valid entity) | **Throw** on failure |
| Operation that has a legitimate "not found" outcome | **Return null** |
| List that may have zero results | **Return `[]`**, never `null` |
| Validation that is part of the API contract | **Throw** `\InvalidArgumentException` |
| Validation that is "data shape inspection" | Return `false`, log if needed |
| Unexpected runtime error (DB down, file system full) | **Let it propagate** (or rethrow with context) |
| Business rule violation (transition not allowed) | **Throw** a domain exception (`\DomainException`) |

Prefer throwing inside the service / repository. The controller catches at the boundary — but in practice the base `EmundusController::execute()` does that for you, so even controllers usually just let exceptions bubble.
