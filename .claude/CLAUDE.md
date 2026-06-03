# CLAUDE.md — Tchooz (eMundus)

Tchooz is an online application management platform built on Joomla 5 (PHP 8.2+) with a Vue 3 SPA frontend. Two repos: `tchooz` (active) and `core` (legacy Joomla 3.10, read-only).

## Code conventions (read before writing code)

Before writing or modifying any code in `com_emundus`, read:
- `.claude/docs/conventions/code-conventions.md` — cross-cutting rules: naming honesty, one source of truth per fact, reuse-before-reimplement, translation policy, and the universal anti-patterns.
- `.claude/docs/conventions/design-decisions.md` — architectural decision rules: single responsibility, declarative > imperative, defaults preserve old behavior, validation derivation, when to rewrite vs patch, guards at the entry point.

## Architecture

### Backend (PHP)

New code uses the `Tchooz\` namespace mapped to `components/com_emundus/classes/`. Layered architecture:

- **Controller** (`classes/Controller/EmundusController.php`) — base controller; use `#[AccessAttribute]` for access control
- **Repository** (`classes/Repositories/`) — data access extending `EmundusRepository`; use `#[TableAttribute('table', 'alias')]`
- **Entity** (`classes/Entities/`) — domain objects (e.g. `WorkflowEntity`, `StepEntity`)
- **Factory** (`classes/Factories/`) — loads relations on entities via `EmundusFactory`
- **Service** (`classes/Services/`) — business logic (emails, exports, automation)
- **Transformer** (`classes/Transformers/`) — data format conversion (dates, IBAN, currency)
- **Subscriber** (`classes/Subscribers/`) — Joomla event subscribers extending `EmundusSubscriber`
- **Enum** (`classes/Enums/`) — PHP enums for statuses, CRUD, access levels

Legacy Joomla MVC coexists: `controllers/`, `models/`, `helpers/`, `views/`. Always prefer `Tchooz\` namespaced classes for new code.

### Frontend (Vue 3)

Source in `components/com_emundus/src/`, built with Vite (IIFE output to `media/com_emundus_vue/`).

- Entry: `src/main.js` — mounts components on DOM IDs (`#em-component-vue`, `#em-files`, etc.)
- Store: Pinia in `src/stores/` (e.g. `global.js`, `formbuilder.js`)
- Services: `src/services/` — prefer `FetchClient` over `axiosClient.js`
- Components: `src/components/` by domain (Workflow/, FormBuilder/, Campaigns/)
- Translations: `Joomla.Text._('KEY')` via the `translate` mixin (`src/mixins/translate.js`)
- Styling: Tailwind CSS with **`tw-` prefix** (`tw-flex`, `tw-mt-4`)

### Integration Points

- **Fabrik**: form builder — plugins in `plugins/fabrik_element/`, `plugins/fabrik_form/`, `plugins/fabrik_list/`
- **Joomla plugins**: `plugins/emundus/` for custom event handlers
- **REST API**: `api/components/com_emundus/`
- **External APIs**: `classes/api/` (FileMaker, Yousign, Zoom, GLPI, PostgREST)

## Common Commands

```bash
# Docker: start containers
docker compose -f docker-compose-<username>.yml up --build -d

# Docker: update database/project
docker exec -it <web_service> php cli/joomla.php tchooz:update

# Frontend: install deps (run both)
npm install                              # root: Tailwind deps
cd components/com_emundus && npm install # Vue deps

# Frontend: dev watch
cd components/com_emundus && npm run watch

# Frontend: production build (MUST run before committing)
cd components/com_emundus && npm run build

# Backend tests (inside Docker)
docker exec -it <web_service> libraries/emundus/composer.phar install --working-dir=tests/
docker exec -it <web_service> tests/vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --no-coverage
```

## Code Patterns

### PHP: Controller method
```php
#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]
#[AccessAttribute(accessLevel: AccessLevelEnum::PARTNER, actions: [['id' => 'workflow', 'mode' => CrudEnum::READ]])]
public function getworkflows(): EmundusResponse
{
    // ... business logic ...
    return EmundusResponse::ok($data);
}
```
Always return `EmundusResponse::ok($data)` or `EmundusResponse::fail($msg, $code)`. Use `TraitResponse::sendJsonResponse()` to send.

### PHP: Logging
```php
Log::addLogger(['text_file' => 'com_emundus.workflow.php'], Log::ALL, ['com_emundus.workflow']);
```

### PHP: Repository ORM
```php
#[TableAttribute('jos_emundus_setup_workflows', 'esw')]
class WorkflowRepository extends EmundusRepository implements RepositoryInterface
```
Entities use `#[Table]` and `#[Column]` attributes from `Tchooz\Attributes\ORM\`.

### Vue: API calls
```javascript
import { FetchClient } from '@/services/fetchClient.js';
const client = new FetchClient('workflow');
const response = await client.get('getworkflows', { lim: 10 });
const result = await client.post('save', { label: 'My workflow' });
```
CSRF is handled automatically via `Joomla.getOptions('csrf.token')`.

### Vue: Translations
```javascript
// In component using translate mixin
this.translate('COM_EMUNDUS_SOME_KEY')
```

## Conventions

### Commits (mandatory prefixes for semantic release)
- `feat:` / `feature:` / `minor:` → minor bump (1.0.0 → 1.1.0)
- `fix:` / `patch:` / `hotfix:` / `refactor:` / `style:` / `perf:` / `security:` → patch bump
- `BREAKING:` / `BREAKING CHANGE:` → major bump

### Branching
- `master` → stable, protected
- `dev` → integration; create `feature/xxx` from here
- `hotfix` → patches from master; create `patch/xxx` from here
- `release` → merge dev → master

### Linting
- ESLint + Prettier: 2-space indent, `no-console: off`, `vue/multi-word-component-names: off`
- Config: `components/com_emundus/eslint.config.js`

## Key Files

- Component manifest & version: `administrator/components/com_emundus/emundus.xml`
- Vue entry point: `components/com_emundus/src/main.js`
- Vite config: `components/com_emundus/vite.config.js`
- Tailwind config: `tailwind.config.js`
- Base controller: `components/com_emundus/classes/Controller/EmundusController.php`
- Base repository: `components/com_emundus/classes/Repositories/EmundusRepository.php`
- Response class: `components/com_emundus/classes/EmundusResponse.php`
- PHPUnit config: `tests/phpunit.xml`
- Test base class: `tests/Unit/UnitTestCase.php`
- Docker dev guide: `docker-dev-installation.md`
- Contribution guide: `contribute.md`

