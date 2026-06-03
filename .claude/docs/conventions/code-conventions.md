# Tchooz cross-cutting code conventions

> Read this before writing or modifying any code in `com_emundus`. These rules apply to every layer — backend PHP, frontend Vue, plugins, scripts. They are not preferences; they are lessons from changes that have actually broken or rotted Tchooz code.
>
> For deeper architectural decision rules, see [`design-decisions.md`](./design-decisions.md).

Most concrete how-to-write-it guidance lives in the layer-specific tooling:

| You're doing… | Reference |
|---|---|
| Writing or editing backend PHP (controllers, services, repositories, entities, factories, subscribers, transformers, enums) | `backend-developer` skill |
| Writing or editing Vue components / frontend services / stores | `frontend-conventions` skill |
| Planning the file layout of a new feature before coding | `architectural-designer` skill |
| Writing or updating PHPUnit tests | `test-writer` skill |
| Reviewing a PR / branch / staged diff | `code-review` skill |

This document stays small on purpose: cross-cutting rules only, and pointers to where the specifics live.

---

## The three universal rules

These are the rules that have caused the most rewrites in Tchooz history. They are not layer-specific — they apply equally to a controller, a Vue component, a Pinia store, a Fabrik plugin.

### Rule 1 — Names must match what the class actually does

A class named `Import` whose body deals only with one spreadsheet format should be `XlsxImporter`. A "service" that wraps one HTTP call is not a `Manager`. Generic names (`Manager`, `Handler`, `Service`, `Helper`) attached to narrow bodies invite dishonest growth — the next dev tries to extend the class, the body resists, and a hack gets bolted on.

**Test**: read the class name aloud. If you need to explain "but really it also does X", rename.

Canonical examples in this codebase:
- `Tchooz\Services\Export\Excel\ExcelService` — handles Excel only, narrow scope (sibling `Pdf/` handles PDF)
- `Tchooz\Services\Emails\EmailService` — sends emails, not a `MailManager`
- `Tchooz\Services\FileSecurityService` — checks file security, not a `FileHelper`
- `WorkflowRepository::getWorkflowByFnum()` — method name says exactly what it returns and from what key

### Rule 2 — One source of truth per fact

When two places declare the same fact, they will drift. Even if you write them on the same day.

Anti-patterns:
- An `instanceof X` check AND a `in_array(Y, $supported)` check gating the same behavior
- A frontend translation table AND a backend `value_label` for the same enum
- A list of "supported features" in the controller AND in the service that owns them
- A `#[AccessAttribute]` AND a runtime `if (!asXAccess) throw` doing the same gate

**Pattern that works**: `Tchooz\Services\Export\ExportRegistry::autoRegisterExports()` derives the list of available exporters from a directory scan + interface implementation — the file system + the `ExportInterface` are the truth, the list is derived, drift is impossible. Compare with hand-maintaining an array of supported formats in two places.

When you find yourself maintaining two parallel declarations, identify which one is the *truth* and *derive* the other from it.

### Rule 3 — Reuse before you reimplement

Before writing "a small utility while you're at it", grep:

```bash
grep -rn "<concept>" components/com_emundus/{helpers,classes,libraries/emundus} | head -20
```

Common reinventions to refuse:

| Concern | Canonical |
|---|---|
| Date formatting / parsing | `Tchooz\Transformers\DateTransformer`, `Tchooz\Providers\DateProvider` |
| Phone validation | libphonenumber (already vendored), `Tchooz\Transformers\PhoneTransformer` |
| Access control | `#[AccessAttribute]` |
| JSON response shaping | `EmundusResponse::ok/fail` + `TraitResponse` |
| fnum lookup | `EmundusHelperFiles::getIdFromFnum()` |
| Language resolution | `Tchooz\Factories\Language\LanguageFactory` |
| Cache key on code version | `EmundusHelperCache::getCurrentGitHash()` |
| Vue API consumption | `components/com_emundus/src/services/fetchClient.js` |
| Vue translation | `translate('COM_EMUNDUS_...')` via the `translate` mixin |
| Backend translation | `Text::_('COM_EMUNDUS_...')` |

Reuse beats reimplementation every time. The cost of "small utility while you're at it" is fragmentation — three slightly-different date parsers in three corners of the repo, and every bug fix has to happen three times.

---

## Translation policy (always applied)

Cross-cutting because it applies equally to PHP and Vue.

- Prefix every key with `COM_EMUNDUS_` — grep-ability is non-negotiable.
- Use positional placeholders `%1$s, %2$s` — never bare `%s` when there are 2+ values.
- Update **both** `language/fr-FR/fr-FR.com_emundus.ini` **and** `language/en-GB/en-GB.com_emundus.ini` in the *same commit*.
- No hardcoded user-facing strings:
  - Backend: `Text::_('COM_EMUNDUS_...')` or `Text::sprintf(...)`
  - Frontend: `translate('COM_EMUNDUS_...')` via the `translate` mixin (or `Joomla.Text._('COM_EMUNDUS_...')` in plain JS)
- Even error messages and response messages go through translation — they're user-facing too.

A key added only to FR will ship the raw key to production UI in English. This has happened.

---

## Cross-cutting anti-patterns

The few anti-patterns that aren't tied to a specific layer. Layer-specific ones live where they apply: backend anti-patterns in `backend-developer`, frontend anti-patterns in `frontend-conventions`, the full review-time catalogue in `code-review`.

1. **Generic class name (`Manager` / `Handler` / `Service` / `Helper`) on a narrow body** — Rule 1 violation. The next maintainer assumes the class can grow and bolts on something that doesn't belong.
2. **Two declarations of the same fact** — Rule 2 violation. Drift is inevitable; bug fixes only get applied to one side.
3. **Translation key in FR but not EN** — raw key ships to English users.
4. **Hardcoded user-facing string** (PHP or Vue) — leaks the language assumption.
5. **Same regex / parsing chain in 2+ files** — extract a transformer or validator.
6. **Subclass overriding 4+ methods to declare configuration** — should be a value object (see [`design-decisions.md`](./design-decisions.md) §2 "Declarative > imperative").
7. **`instanceof` chain doing dispatch** — should be a registry or polymorphism.

---

## Design decisions (cross-cutting deep dive)

For deeper architectural rules — single responsibility, declarative > imperative, defaults preserve old behavior, validation derivation, input tolerance / output predictability, when to rewrite vs patch, TODOs vs silent omissions, guards at the entry point — see [`design-decisions.md`](./design-decisions.md).

These rules shape decisions you make *while* writing code, not as a checklist *after*. They apply across layers: a service can violate "single responsibility" the same way a Vue component can.

---

## Quick decision shortcuts

**Where does this logic go?**
→ Touches HTTP/input/output? Controller. Touches data persistence? Repository. Multi-step business workflow? Service. Entity construction from raw data? Factory. Event reaction? Subscriber. Data format conversion? Transformer. Vue component? See `frontend-conventions`. None of these match cleanly? You probably haven't split the responsibility — re-read [`design-decisions.md`](./design-decisions.md) §1.

**Should I patch this messy code or rewrite it?**
→ 3+ methods need to change for any feature add, AND class < 500 lines, AND tests would be more invasive than the rewrite → rewrite. Otherwise patch. Full rubric in [`design-decisions.md`](./design-decisions.md) §8.

**Should this be a value object or a subclass with abstract methods?**
→ Information that *describes* something (column map, validation rule, enum labels) → value object. Behavior that varies → subclass. [`design-decisions.md`](./design-decisions.md) §2.

**Should I build an extension point for the future?**
→ Would the future feature require modifying *existing* public signatures? Yes → build the hook now (dormant, default no-op, documented). Would it only add new code on top? No → defer. [`design-decisions.md`](./design-decisions.md) §3.
