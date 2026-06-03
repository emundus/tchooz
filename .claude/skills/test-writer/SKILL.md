---
name: test-writer
description: Generates PHPUnit test files for Tchooz classes (Entity, Factory, Repository, Service, Controller) following project conventions. Use when asked to write tests, generate a test file, cover a class with tests, or add a test case. Trigger it on phrases like "write tests for X", "test this class", "add a test", "cover this method", "PHPUnit", or whenever the user wants test coverage for any class under `components/com_emundus/classes/`.
---

# Tchooz test writer

Writes PHPUnit tests that fit Tchooz's two-layer test architecture. The philosophy half explains *what kind of test* to write; the recipe half explains *how to write the file*.

---

## Part A — Test philosophy

### The two test layers

| Layer | Base class | Bootstrap cost | Use when |
|---|---|---|---|
| **Pure unit** | `\PHPUnit\Framework\TestCase` | None | Isolated logic — value objects, services with mockable deps, enums, entities |
| **Integration** | `Joomla\Tests\Unit\UnitTestCase` | Creates sample user/program/campaign | Repositories, controllers, anything that needs DB schema |

**Choosing rule**: if the code under test queries the database, you need `UnitTestCase`. Otherwise prefer pure `TestCase` — it's faster, less brittle, and forces you to keep mockable seams.

Examples in the codebase:
- `tests/Unit/Component/Emundus/Class/Services/FileSecurityServiceTest.php` → `TestCase` (pure logic, no DB)
- `tests/Unit/Component/Emundus/Class/Repositories/EmundusRepositoryTest.php` → `UnitTestCase` (real DB)

### Test-writing rules

**One test = one behavior.** The test name reads as a spec sentence: `testWhenXThenY`. A 200-line test method with 8 assertions on different concerns is multiple tests fused — split.

**Don't mock the system under test.** If you find yourself creating a partial mock of the class you're testing and overriding 3 methods to focus on the 4th, you're testing the mock, not the class. Either:
- Refactor the class so the 4th method can be tested in isolation (extract it), or
- Write an integration test.

**Don't pay integration cost for unit work.** A test that extends `UnitTestCase` for logic that doesn't need the DB pays the dataset bootstrap cost for nothing. It's slower and more brittle (one schema change breaks unrelated tests).

**Test names read as specifications:**
```
✅ testGetByIdentifierCode_WhenCodeExists_ReturnsOrganization
✅ testFlush_WhenEntityIsInvalid_ThrowsInvalidArgumentException
✅ testRun_WhenMoreThanHalfHeadersUnknown_ReportsGlobalErrorAndPersistsZeroRows

❌ testFlush
❌ testHappyPath
❌ testEdgeCase1
```

A glance at the name tells the reader what behavior is being verified.

### Coverage targets (contextual, not mechanical)

- **Repositories**: every public method's happy path + at least one failure path (invalid entity, DB constraint violated).
- **Services / pipelines**: each branch of business logic + the error propagation path.
- **Value objects / enums**: every public method + `toArray()` / `toSchema()` shape stability.
- **Controllers**: the 4-step recipe per public action (access works, bad input fails, good input calls service, response shape matches) — *not* every branch (branches belong to the services).

A repository suite with 100% line coverage of happy paths is worth less than a 60% suite that covers actual failure modes (constraint violations, race conditions, partial state).

### What NOT to test

- **Joomla framework internals** — if a test asserts `Factory::getDbo()` returns a `DatabaseDriver`, you're testing Joomla.
- **`Text::_()` results** — assert on the translation *key* passed in, not the translated string.
- **A mock you just configured** — `$mock->method('foo')->willReturn('bar'); $this->assertSame('bar', $mock->foo());` proves nothing.

### When testing surfaces a design problem

Hard-to-test code is a design signal, not a testing problem:

| Symptom | Likely design issue |
|---|---|
| Hard to instantiate | Too many dependencies, too few injection points |
| Hard to mock its deps | Deps are concrete classes that should be interfaces |
| Hard to assert on output | Output is a side effect (file write, API call) with no observable hook |
| Hard to isolate a single behavior | Class does too many things |

Don't paper over the design problem with elaborate test setup. Refactor the class first, then test.

---

## Part B — Recipe for writing the file

### Step 1 — Locate and read the class

The user will provide a class path or name. Resolve it under `components/com_emundus/classes/`.
Read the full class to understand:
- Namespace and class name
- Constructor parameters and their types
- All public methods (signature + return type)
- Dependencies (injected repositories, services)

### Step 2 — Check for existing tests

Look under `tests/Unit/Component/Emundus/Class/{Category}/{Subcategory}/` for an existing test file. If one exists, read it and **extend** it rather than overwriting.

### Step 3 — Determine the test category and base class

Map the class namespace to the correct test path and PHPUnit namespace:

| Source namespace          | Test path                                                       | Test namespace                                      |
|---------------------------|-----------------------------------------------------------------|-----------------------------------------------------|
| `Tchooz\Entities\Foo`     | `tests/Unit/Component/Emundus/Class/Entities/Foo/`             | `Unit\Component\Emundus\Class\Entities\Foo`         |
| `Tchooz\Factories\Foo`    | `tests/Unit/Component/Emundus/Class/Factories/Foo/`            | `Unit\Component\Emundus\Class\Factories\Foo`        |
| `Tchooz\Repositories\Foo` | `tests/Unit/Component/Emundus/Class/Repositories/Foo/`         | `Unit\Component\Emundus\Class\Repositories\Foo`     |
| `Tchooz\Services\Foo`     | `tests/Unit/Component/Emundus/Class/Services/Foo/`             | `Unit\Component\Emundus\Class\Services\Foo`         |

### Step 4 — Write the test file

#### File header
```php
<?php

namespace Unit\Component\Emundus\Class\{Category}\{Subcategory};

use Joomla\Tests\Unit\UnitTestCase;
use Tchooz\{Category}\{Subcategory}\{ClassName};
// Add other use statements as needed

/**
 * @package     Unit\Component\Emundus\Class\{Category}\{Subcategory}
 *
 * @since       version 1.0.0
 * @covers      \Tchooz\{Category}\{Subcategory}\{ClassName}
 */
class {ClassName}Test extends UnitTestCase
{
```

(Swap `UnitTestCase` for `\PHPUnit\Framework\TestCase` when no DB is needed — see Part A.)

#### setUp vs constructor

**Default to `setUp()`**:
```php
protected function setUp(): void
{
    parent::setUp();
    $this->service = new FooService($this->createMock(Foo::class));
}
```

#### tearDown

When tests create DB rows, track IDs and clean up:
```php
private array $createdFooIds = [];

protected function tearDown(): void
{
    foreach ($this->createdFooIds as $id) {
        try { $this->repository->delete($id); } catch (\Exception) {}
    }
    parent::tearDown();
}
```

For complex fixtures, use dedicated methods:
```php
public function createFixtures(): void { /* insert test data */ }
public function clearFixtures(): void  { /* delete test data */ }
```

#### Test method rules

- Prefix: `test`
- Style: `camelCase` — `testMethodNameScenarioExpectedResult` (e.g. `testIsPublishedReturnsTrueWhenPublished`)
- Return type: always `: void`
- PHPDoc on every test method:
  ```php
  /**
   * @covers \Tchooz\...\ClassName::methodName
   * @return void
   */
  ```

#### Assertion style

- Every assertion **must** include a descriptive third-parameter message.
- Use `assertSame` for strict equality, `assertEquals` for loose.
- Prefer multiple focused test methods over one test with many branches.
- For exception testing, use `expectException` before the call:
  ```php
  $this->expectException(\InvalidArgumentException::class);
  $this->expectExceptionMessage('some message');
  $subject->methodThatThrows();
  ```

#### Section separators

Group related tests with a comment separator. Style varies across files — use dashes:
```php
// -------------------------------------------------------------------------
// Constructor / getters
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Setters — value updated
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Fluent interface — every setter must return $this
// -------------------------------------------------------------------------

// -------------------------------------------------------------------------
// Edge cases
// -------------------------------------------------------------------------
```

#### Testing private methods

Use the helper inherited from `UnitTestCase`:
```php
$result = self::callPrivateMethod($this->service, 'privateMethodName', [$arg1, $arg2]);
```

#### Mocking (DB-backed tests)

Use `$this->createMock(ClassName::class)` with `expects($this->once()/never()/any())` and `willReturn(...)`. **Only mock at system boundaries** — never mock the class under test itself.

---

## What tests to generate by class type

**Entity:**
constructor initialises all properties · each getter · each setter updates value · each setter returns `$this` (fluent) · full fluent chain · edge cases (null, zero, empty string)

**Factory:**
`buildEntity` / `fromDbObjects` maps all fields correctly · handles missing/null/empty fields · injects optional repository · returns correct entity type

**Repository:**
each public query method (happy path) · filters/search params · not-found returns null/empty · `flush()` throws on invalid entity · `flush()` throws on DB failure · created rows tracked and cleaned up in tearDown

**Service:**
each public method (happy path) · dependencies injected via mocks · exception/error paths (`expectException`) · boundary and edge inputs

**Controller** (the 4-step recipe — `backend-developer` §2.6):
access check (403 for unauthorized user) · bad input returns fail response · good input calls the service · response shape matches the contract

---

## Step 5 — Output

- Write the file to the correct path under `tests/`.
- State the full file path.
- List the test methods generated and what each covers.
