---
name: code-review
description: Reviews Tchooz / eMundus PHP and Vue code against the project's accumulated rules — pass-by-pass audit with blocker catalogue and final mental checklist. Use this skill when reviewing a pull request, auditing a branch, or running a self-review before pushing. Trigger it on phrases like "review this PR", "audit this branch", "is this ready to merge", "what's wrong with this code", "self-review my changes", or whenever the user explicitly asks for a review. Do NOT trigger when writing new code — the writing skills (`backend-developer`, `frontend-conventions`) already embed the rules; this skill is the *checking* pass at the end of a change.
---

# Tchooz code review

A reviewer's pass order, grounded in patterns that have actually shipped to Tchooz production and caused regressions. Use this skill when *reading* a diff, not when writing one.

---

## How to use this skill

Get the diff first:
```bash
git diff <base-branch>...HEAD          # for a PR / branch review
git diff --cached                      # for staged changes before commit
git diff HEAD                          # for everything not yet committed
```

Then walk the diff in the pass order below. Don't shuffle — the passes build on each other (e.g. you can't judge error handling until you know the layers).

When you spot a problem, classify it: **blocker** (must fix before merge) vs **flag** (request a clarification or suggest a follow-up). The blocker catalogue at the end of this skill is the authoritative list.

End with the mental checklist.

---

## Pass 1 — Names and shapes

For each **new or renamed class**:
- Read the class name aloud. Does the body match? If you need to say "but it also does X", that's a rename request.
- Are dependencies suspiciously narrow (every method takes `Worksheet $sheet`) under a broad name (`Import`)? Flag.
- Generic name (`Manager`, `Handler`, `Service`, `Helper`) on a narrow body? Flag.

For each **new method**:
- Length > 50 lines? Ask for a split or a justification comment.
- Mixes I/O, business logic, and formatting? Split.

---

## Pass 2 — Layering (backend)

For each touched **controller**:
- Method > 200 lines → **blocker**
- Contains `Spreadsheet`, `DataValidation`, `unlink()`, `glob()`, `preg_match` on business data, raw SQL (`$db->setQuery`), or filesystem iteration → **blocker** (extract to a service)
- `require_once` inside the method → **blocker** (autoloader bypass)
- Logic structure deviates from the 4-step recipe (access / parse / call / format) → flag

The 4-step recipe (see `backend-developer` §2.6):
```
1. Check access (#[AccessAttribute])
2. Parse and validate input
3. Call the service / repository / pipeline that does the work
4. Format and return (EmundusResponse::ok / ::fail)
```

For each touched **service**:
- Does it own a single concern? If it imports `Spreadsheet` AND `DateTimeImmutable` AND `regex` AND `Log`, suspect.

For each touched **repository**:
- Does it own only data access? If it sends emails or calls external APIs, that belongs in a service.

For each touched **Vue component**:
- See `frontend-conventions` output checklist. Tailwind `tw-` prefix, Options API only, no direct `FetchClient` in components, listeners removed in `beforeUnmount`.

---

## Pass 3 — Error handling

(For the full pattern, see `backend-developer/references/errors-and-io.md`.)

Quick checks:
- Any `catch (...) { Log::add(...); return; }` in persistence code → **blocker** (silent half-write)
- Any `catch (Throwable)` without a `$e` variable → **blocker** (lost diagnostics)
- Any `catch` block that doesn't log file/line/exception type/context → flag
- Any `flush()` / persist that doesn't throw on failure → flag
- Any `try { ... } catch (\Exception $e) { return; }` swallowing the error silently → **blocker**

---

## Pass 4 — Output contracts

For each `toArray()` / JSON response / `EmundusResponse::ok(...)`:
- `if ($cond) $out['key'] = ...;` patterns → flag (unstable JSON shape — Anti-pattern #5)
- Same endpoint returning different keys depending on the code path → flag
- Empty values omitted instead of `[]` / `null` / `0` → flag (unless the absence carries documented meaning)

The frontend should be able to write `response.data.summary.failed` without `?.` chains and `??` fallbacks. If consumers must guard every access, the shape is wrong.

---

## Pass 5 — Drift sources (one source of truth)

- Same fact declared in two places? (e.g., a list of supported modes in controller AND in importer) → flag
- A list maintained alongside a marker interface? → derive one from the other
- Frontend translating values FR/EN while the backend ships `value_label`? → pick one
- Same regex / parsing logic in 2+ files? → extract a transformer
- `instanceof` chain doing dispatch → should be a registry or polymorphism

See `.claude/docs/conventions/code-conventions.md` Rule 2 (one source of truth).

---

## Pass 6 — Translations

- Every new user-facing string going through `Text::_('COM_EMUNDUS_...')` (backend) / `translate('COM_EMUNDUS_...')` (frontend)? If not → **blocker**
- New keys added to **both** `fr-FR.com_emundus.ini` AND `en-GB.com_emundus.ini` in the same commit? If FR only → **blocker** (raw key ships to English UI)
- Keys prefixed with `COM_EMUNDUS_`? If not → flag (grep-ability)
- Placeholders positional `%1$s, %2$s` (not bare `%s`) when there are 2+ values? If bare → flag

---

## Pass 7 — Reuse

`grep -rn` for the new utility / helper / parser against:
```
components/com_emundus/helpers/
components/com_emundus/classes/
libraries/emundus/vendor/
```

Specifically check for these canonical helpers that get reinvented often:
| Concern | Canonical |
|---|---|
| Date / IBAN / phone / currency normalization | `Tchooz\Transformers\*Transformer` (one transformer per format) |
| Phone validation | libphonenumber (already vendored) |
| Access control | `#[AccessAttribute]` |
| JSON response | `EmundusResponse::ok/fail` + `TraitResponse` |
| fnum lookup | `EmundusHelperFiles::getIdFromFnum()` |
| Language resolution | `Tchooz\Factories\Language\LanguageFactory` |
| Cache key suffix | `EmundusHelperCache::getCurrentGitHash()` |

If something matches, the PR should use the existing implementation (or improve it where it lives).

---

## Pass 8 — Compatibility

- New constructor parameter without a default? → **blocker** for shared classes (fan-out break). Defaults preserve old behavior; new params are opt-in.
- New public method on an interface? → all implementers must be updated (including test fakes).
- Public signature change? → all callers checked, migration plan documented.

---

## Pass 9 — Tests

(See `test-writer` for the full conventions.)

Quick checks:
- New service / repository code has tests? Pure unit if no DB needed, integration if DB needed.
- Test names read as spec sentences (`testWhenXThenY`)?
- One behavior per test method?
- No test mocking the system under test itself?

---

## Pass 10 — TODOs and extension points

- Any deferred behavior documented as a TODO **in the code** (not just the PR description)?
- TODOs name the open questions, not just the missing work?
- Extension points named after the future use case, with a comment about when/how they activate?

---

## Blocker catalogue

These should fail review automatically. Each has shipped to Tchooz production and caused regressions:

1. **Generic class name on narrow body** (`Manager`, `Handler`, `Service` over a class that does one thing) — `.claude/docs/conventions/code-conventions.md` Rule 1
2. **`try { } catch { Log; return; }` in persistence** — silent half-write
3. **`catch (Throwable)` without `$e` logged** — lost diagnostics
4. **Controller method > 200 lines** — service work in the wrong layer
5. **`require_once` inside a controller method** — autoloader bypass
6. **Translation key in FR but not EN** — raw key in production UI
7. **Hardcoded user-facing string in PHP / Vue** — leaks language assumption
8. **New shared-class parameter without a default** — fan-out break
9. **`if ($cond) $out['key'] = ...` inside `toArray()`** — unstable JSON shape
10. **`mkdir` / `unlink` / `glob` in a controller** — file lifecycle in wrong layer
11. **`instanceof` chain to do dispatch** — should be a registry or polymorphism
12. **Same regex in 2+ files** — extract a transformer or validator
13. **Subclass overriding 4+ methods to declare configuration** — should be a value object
14. **Two declarations of the same fact** (controller AND service AND enum) — drift incoming
15. **`date('Y-m-d_His')` as cache key** — cache never reused

---

## Mental checklist (run last)

- [ ] Does each class name match its body when read aloud?
- [ ] Is any method > 50 lines? Justified or split?
- [ ] Are there `catch` blocks without `$e` logged with file/line/context?
- [ ] Are there `if ($cond) $out['key'] = ...` in any `toArray()`?
- [ ] Did the PR add a translation key to FR but not EN?
- [ ] Did it reimplement something that exists in `helpers/` or `Tchooz\Services\`?
- [ ] If a constructor parameter was added, does it have a default that preserves old behavior?
- [ ] If a public method was added to an interface, are all implementers (including test fakes) updated?
- [ ] Are deferred behaviors documented as TODOs in the code (not just the PR description)?
- [ ] Are user-facing strings going through `Text::_` / `Joomla.Text._` / `translate(...)`?
- [ ] Did the author run `npm run build` for frontend changes? (cf. `CLAUDE.md`)

---

## Output format

When reviewing, structure feedback as:

1. **Blockers** — the items above that must be fixed before merge, with file:line references.
2. **Flags** — items worth a conversation but not blocking, with file:line references and a suggested direction.
3. **Nits** — style/preference items the author can take or leave.

Be concrete: quote the offending line, name the rule it violates (e.g. "Rule 4 in `backend-developer` — controller method runs SQL"), and propose a specific fix.
