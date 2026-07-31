# Design decisions

Read this when **designing** a new class, service, controller, component, or refactoring something that "feels off". Each section is a decision rule, not a discussion.

See also [`code-conventions.md`](./code-conventions.md) for the cross-cutting rules that apply while writing code.

## Table of contents
1. One responsibility per class
2. Declarative > imperative when the need is "configuration"
3. Don't anticipate, but prepare extension points
4. Marker interface + declarative method (optional capabilities)
5. Defaults preserve old behavior
6. Declarative validation > duplicated imperative validation
7. Input tolerance, output predictability
8. When patching costs more than rewriting
9. Explicit TODOs > silent omissions
10. Guardrails at the entry point, not in the middle of the loop

---

## 1. One responsibility per class

"Responsibility" = "a single reason to change". A `WorkflowSubscriber` that listens to 4 events and contains the business logic of each will change every time one of those workflows evolves. Each event handler should delegate to a service.

A method longer than your screen is almost always doing too many things.

**Recognize the anti-pattern**:
- A controller method longer than the service methods it calls
- A `foreach` loop with > 30 lines inside
- A class that imports `Spreadsheet`, `Log`, `Factory`, `DateTimeImmutable`, `regex` all at once
- A method that mixes I/O, business logic, and formatting

**Pattern that works**:
- `Tchooz\Subscribers\GenerateReferenceSubscriber` listens to two events with one handler method each, every method delegating to `InternalReferenceService`
- `Tchooz\Services\Reference\InternalReferenceService::generateShortReference()` / `::generateReference()` — each public method is one named business step; the handler is glue
- Controllers extend `Tchooz\Controller\EmundusController` and rely on `#[AccessAttribute]` for access control instead of inlining checks

---

## 2. Declarative > imperative when the need is "configuration"

When information *describes* something (a column map, a validation rule, an enum's labels, a workflow's stages), prefer a **data object** over abstract methods that return slices of the description.

Why: a data object is introspectable. It serializes to JSON. It can be reused by a documentation generator, a model builder, a validator, a frontend descriptor — without any of those consumers calling back into class methods.

**Recognize the anti-pattern**:
- A subclass overriding 4+ abstract methods that each return a piece of "what this class is about"
- A repeated `case` switch on the same enum in multiple files
- A "configuration" that requires `instanceof` checks to read

**Pattern that works**:
- `Tchooz\Entities\Fields\Field::toSchema()` + variants (`StringField`, `NumericField`, `ChoiceField`, `PasswordField`, `DateField`) — each field type self-describes via `toSchema()`; consumers (JSON API for the frontend, validators, form generators) read the schema instead of hardcoding per-type logic
- Enums with `getLabel()` + `Text::_('KEY')` for human display (see `GenderEnum`, `VerifiedStatusEnum`, `ActionEnum`, `FilterModeEnum`, `ApiStatusEnum`) — the enum holds the data, consumers ask it
- Registries that auto-discover descriptors at runtime (see `ExportRegistry`, `ActionRegistry`, `TransformationsRegistry`) — adding a new descriptor file is enough; no central list to update

---

## 3. Don't anticipate fictional needs. But prepare extension points.

These are two different things.

**Don't build**:
- A 10-option configuration class when 1 variant exists today
- A plugin system before there are two plugins
- A multi-tenant abstraction "in case" we ever need it
- A generic interface for one consumer

**Do build**:
- Dormant hooks on options / constructor parameters with sensible defaults — e.g. the `loadChilds` flag on `WorkflowRepository::getWorkflowByFnum()` defaults to `false`, so existing callers stay fast and the rare caller that needs child workflows opts in
- Optional `?DatabaseDriver` injection on repositories (see `WorkflowRepository::__construct(?DatabaseDriver $db = null)`) — production code passes nothing; tests inject a real driver. The hook activates only on demand.
- Extension points named after the future use case, with a comment explaining when/how they activate

**Decision rule**: if the future feature would require modifying *existing* public signatures, plan the extension point now. If it would only add new code on top, defer.

---

## 4. Marker interface for type safety + declarative method for documentation/UX

When a capability is **optional**, two complementary mechanisms work together:

```php
// Marker interface — type guarantee (PHPStan, IDE, instanceof)
interface SupportsBatchExport extends ExportInterface {
    public function exportBatch(array $items): ExportResult;
}

// Declarative method — UI/UX (what's available, in what order, with what label)
public function getSupportedCapabilities(): array { /* derived from instanceof */ }
```

The marker is for the **compiler/runtime**. The declarative list is for the **frontend** and any orchestrator that branches on capability.

The two MUST be derived from each other to avoid drift. The canonical pattern: a registry (or a base class) that implements the declarative method by inspecting `instanceof` on each concrete implementation, rather than maintaining a parallel array of "what's supported".

---

## 5. Defaults preserve old behavior. New params are opt-in.

```php
public function getWorkflowByFnum(string $fnum, bool $loadChilds = false): ?WorkflowEntity
//                                                              ^^^^^
//                            Existing callers don't know this param exists — they keep
//                            the previous (cheap) behavior automatically.
```

Non-negotiable for any shared class / service / repository. Tchooz has many callers (controllers in legacy MVC, plugins, scheduled tasks, Fabrik element plugins, CLI commands) — a default change is a fan-out break.

**Watch for**:
- A "fix" that flips the meaning of an existing default
- A constructor argument with no default added to a class instantiated in 12 places
- Breaking changes hidden inside a "refactor" commit

---

## 6. Declarative validation > duplicated imperative validation

When you write the same check (`filter_var(..., FILTER_VALIDATE_EMAIL)`, `strlen($iso) === 2`, `strtotime($date)`) in two different services, the validation should be **derived from a field type declaration**, not implemented per consumer.

**Pattern that works**:
- `Tchooz\Transformers\` namespace handles "value normalization by type" — `DateTransformer`, `IbanTransformer`, `PhoneTransformer`, `CurrencyTransformer` each own one format. Any consumer that needs an IBAN parsed/validated calls `IbanTransformer::transform()` instead of writing the regex again.
- `Tchooz\Entities\Fields\Field` subclasses (`StringField`, `NumericField`, `DateField`, `ChoiceField`) own their own validation through `toSchema()` — the schema drives validation everywhere it's consumed.
- Fabrik element plugins respect element type and provide consistent validation.

**Approach**: when you write the same check twice, identify the **trigger** (the underlying type or property) and push the validation onto that.

---

## 7. Input tolerance, output predictability

User-facing inputs should be permissive. Internal canonical forms should be predictable.

```
"Email Address" / "email address" / "EMAIL-ADDRESS" / " email address "
                                  ↓
                       canonical: "email_address"
```

Same canonical form coming out of any normalizer call, regardless of how the user typed it.

**Pattern that works**:
- `Tchooz\Transformers\IbanTransformer` accepts spaces/separators, outputs canonical IBAN
- `Tchooz\Transformers\DateTransformer` accepts multiple input formats, outputs a single canonical one
- `Tchooz\Transformers\PhoneTransformer` accepts various phone formats, normalizes through libphonenumber

**Watch for**: two different `strtolower`/`trim`/`str_replace` chains on the same value at different points (= subtle inconsistency). The fix is to push that normalization into a transformer one of them calls.

---

## 8. When patching costs more than rewriting, rewrite it

When an existing implementation has 5+ structural problems (coupling, mixed responsibilities, missing tests, silent errors, dead-end abstractions), **stop patching**. Delete it. Write the contract you want. Reimplement.

**Cost of patching**: every fix risks regressing another. The reviewer fatigues. The class accretes patch-on-patch.

**Cost of rewriting**: one focused PR, clean tests, the whole change is reviewable in a session.

**Rewrite when**:
- 3+ methods need to change for any feature add
- Tests would be more invasive than the rewrite
- The naming would have to change to honest names anyway
- The class is < 500 lines (above that, factor down before rewriting)

**Patch when**:
- Single localized issue
- The architecture is sound but the implementation is messy
- There are many callers you can't migrate atomically

The decision is reversible neither way — rewriting forces the architecture conversation explicitly, where patching defers it indefinitely.

---

## 9. Explicit TODOs > silent omissions

If you defer a behavior — "for now we don't touch X" — write it in the code, not only in the PR description. Future-you will not remember.

```php
// TODO(scope-tag): decide how Y should affect Z. Currently NOT handled.
// Open questions:
//   - merge or replace?
//   - what about cascade implications on W?
// Until that's decided, this only handles the scalar fields.
```

The TODO documents *the decision* (the trade-off you made) and *the unknowns* (so the next person knows what to investigate). It's not a "I'll come back to this later" — it's a deliberate boundary with a rationale.

**Watch for**: behavior gaps documented only in the PR description, in Slack, or in someone's head.

---

## 10. Guardrails at the entry point, not in the middle of the loop

If the request is wrong, fail at the first inspection — not after silently dropping foreign data row by row, transaction by transaction.

**Recognize the anti-pattern**:
- A row-by-row validation that could have been a single check on metadata
- An import that creates 12 broken records before failing on the 13th because a missing column is only detected lazily
- A controller that validates input piecemeal across 4 method calls instead of upfront

**Pattern that works**:
- `#[AccessAttribute]` runs before the controller method body, not somewhere inside — the entire request is rejected at the door, not after partial processing
- `FilterRepository::flush()` validates the entity's name is non-empty *before* any DB write, throwing `InvalidArgumentException` upfront rather than letting `insertObject` fail with a less informative error
- `WorkflowRepository::save()` checks step entry-status conflicts before opening a transaction — if two steps would collide, no rows are touched at all
