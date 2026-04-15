# AGENTS.md — Tchooz (eMundus)

## Project Overview
Tchooz is an **online application management platform** built on **Joomla 5** (PHP 8.2+) with a **Vue 3** SPA frontend. It manages application campaigns, evaluations, workflows, and user file tracking. The codebase has two repos: `tchooz` (active, Joomla 5) and `core` (legacy, Joomla 3.10 — read-only reference).

## Architecture

### Backend (PHP)
The core component lives in `components/com_emundus/`. It uses a **layered architecture** under the `Tchooz\` namespace in `components/com_emundus/classes/`:

| Layer | Path | Role |
|---|---|---|
| **Controller** | `classes/Controller/EmundusController.php` | Base controller with PHP 8 `#[AccessAttribute]` for access control |
| **Repository** | `classes/Repositories/` | Data access layer extending `EmundusRepository`, uses `#[TableAttribute]` ORM |
| **Entity** | `classes/Entities/` | Domain objects (e.g. `WorkflowEntity`, `StepEntity`) |
| **Factory** | `classes/Factories/EmundusFactory.php` | Loads relations on entities |
| **Service** | `classes/Services/` | Business logic (emails, exports, automation…) |
| **Transformer** | `classes/Transformers/` | Data format conversion (dates, IBAN, currency…) |
| **Subscriber** | `classes/Subscribers/` | Joomla event subscribers extending `EmundusSubscriber` |
| **Enum** | `classes/Enums/` | PHP enums for statuses, CRUD, access levels, etc. |

Legacy Joomla MVC coexists alongside: `controllers/`, `models/`, `helpers/`, `views/` (PHP templates). New code should use the `Tchooz\` namespaced classes.

### Frontend (Vue 3)
Source: `components/com_emundus/src/`. Built with **Vite** (IIFE output to `media/com_emundus_vue/`).

- **Entry**: `src/main.js` — mounts Vue components on DOM element IDs (e.g. `#em-component-vue`, `#em-files`)
- **Store**: Pinia stores in `src/stores/` (e.g. `global.js`, `formbuilder.js`)
- **Services**: `src/services/` — `FetchClient` (fetch-based) and `axiosClient.js`; API calls go to `/index.php?option=com_emundus&controller=<name>&task=<method>`
- **Components**: `src/components/` organized by domain (Workflow/, FormBuilder/, Campaigns/, etc.)
- **Views**: `src/views/` — top-level page components
- **Translations**: use `Joomla.Text._('KEY')` via the `translate` mixin (`src/mixins/translate.js`)
- **Styling**: Tailwind CSS with **`tw-` prefix** (configured in root `tailwind.config.js`)

### Key Integration Points
- **Fabrik**: form builder engine — plugins in `plugins/fabrik_element/`, `plugins/fabrik_form/`, `plugins/fabrik_list/`
- **Joomla plugins**: `plugins/emundus/` for custom event handlers (yousign, teams, parcoursup…)
- **REST API**: `api/components/com_emundus/` (Joomla Web Services)
- **External APIs**: `classes/api/` — FileMaker, Yousign, Zoom, GLPI, PostgREST, etc.

## Developer Workflows

### Local Setup (Docker)
```bash
# Start containers (PHP/Apache, MySQL, Redis)
docker compose -f docker-compose-<username>.yml up --build -d
# Update database/project
docker exec -it <web_service> php cli/joomla.php tchooz:update
```

### Frontend Development
```bash
# Install Tailwind deps (project root)
npm install
# Install Vue deps
cd components/com_emundus && npm install
# Dev watch
npm run watch
# Production build (MUST run before committing)
npm run build
```

### Backend Tests (PHPUnit, inside Docker)
```bash
# Install test deps
docker exec -it <web_service> libraries/emundus/composer.phar install --working-dir=tests/
# Run tests
docker exec -it <web_service> tests/vendor/phpunit/phpunit/phpunit -c tests/phpunit.xml --no-coverage
```
Test files: `tests/Unit/Component/Emundus/` — extend `UnitTestCase` in `tests/Unit/UnitTestCase.php`.

### Frontend Linting
ESLint + Prettier configured in `components/com_emundus/eslint.config.js` — 2-space indent, no console restriction.

## Conventions

### Commit Prefixes (mandatory for release automation)
- `feat:` / `feature:` / `minor:` → minor version bump
- `fix:` / `patch:` / `hotfix:` / `refactor:` / `style:` / `perf:` / `security:` → patch bump
- `BREAKING:` / `BREAKING CHANGE:` → major version bump

### PHP Patterns
- **Access control**: use `#[AccessAttribute(accessLevel: AccessLevelEnum::COORDINATOR)]` on controller methods
- **ORM mapping**: use `#[TableAttribute('table_name', 'alias')]` on repository classes; `#[Table]`, `#[Column]` on entities
- **Response**: return `EmundusResponse::ok($data)` or `EmundusResponse::fail($msg, $code)` from controllers; use `TraitResponse::sendJsonResponse()`
- **Logging**: `Log::addLogger(['text_file' => 'com_emundus.<context>.php'], Log::ALL, ['com_emundus.<context>'])`
- **Namespace root**: `Tchooz\` maps to `components/com_emundus/classes/`

### Vue Patterns
- API calls: instantiate `new FetchClient('controllername')` then call `.get('taskname', params)` or `.post('taskname', data)`
- CSRF: handled automatically by `FetchClient` via `Joomla.getOptions('csrf.token')`
- Tailwind classes use `tw-` prefix (e.g. `tw-flex`, `tw-mt-4`)
- Global state via `useGlobalStore()` (Pinia)

### Branching
- `master` → stable, protected
- `dev` → integration, create `feature/xxx` from here
- `hotfix` → patches from master, create `patch/xxx` from here
- `release` → merge dev → master

## Key Files Reference
| What | Path |
|---|---|
| Component manifest & version | `administrator/components/com_emundus/emundus.xml` |
| Vue entry point | `components/com_emundus/src/main.js` |
| Vite config | `components/com_emundus/vite.config.js` |
| Tailwind config | `tailwind.config.js` |
| Base controller | `components/com_emundus/classes/Controller/EmundusController.php` |
| Base repository | `components/com_emundus/classes/Repositories/EmundusRepository.php` |
| Response class | `components/com_emundus/classes/EmundusResponse.php` |
| PHPUnit config | `tests/phpunit.xml` |
| Docker dev guide | `docker-dev-installation.md` |
| Contribution guide | `contribute.md` |

