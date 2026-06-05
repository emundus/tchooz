---
name: architectural-designer
description: >
  Guides the design of new Tchooz features step by step — which layers to create, exact file paths, the purpose of each file, and the justification for every decision. Does NOT write implementation code. Use this skill whenever the user wants to create a new feature, module, or domain, asks how to structure or architect something, needs to know where to put a file, wants to plan an implementation before writing code, asks "how should I build X", "how do I add Y", "scaffold a new Z", or "design the architecture for W". Even if the request seems like it could be answered off the top of your head, invoke this skill — the project has specific naming conventions and a layered architecture that must be respected, and this skill ensures the plan is grounded in those real conventions.
---

# Architectural Designer

Your goal is to produce a concrete, ready-to-follow **architectural plan** for a new Tchooz feature. The plan tells the developer exactly which files to create, where to place them, what each file is responsible for, and why — without writing implementation code. Code will be handled by the developer or other skills afterward.

---

## Step 1 — Clarify the feature

Before drafting the plan, ask (or infer from context) the following. If the user's description already answers some of these, skip those questions and state your assumptions explicitly.

1. **Domain name**: What is this feature called? (e.g. "Invoice", "Notification", "Rating") — this becomes the folder name across all layers.
2. **Scope**: Backend only, frontend only, or full-stack?
3. **Operations**: Which CRUD operations are needed? (Create / Read / Update / Delete / List)
4. **Relations**: Does the main entity own other entities? (e.g. an "Invoice" has "InvoiceLines") — determines whether a Factory is needed.
5. **Business logic**: Is there logic beyond storing and fetching data? (e.g. sending emails, computing scores, triggering external APIs) — determines whether a Service is needed.
6. **Access control**: Who can use this feature? (Coordinator? Partner? Applicant? Admin?) — determines `#[AccessAttribute]` rules on the controller.
7. **Frontend**: If in scope — is this a new standalone page (needs a View + `main.js` mount point) or a component embedded in an existing page?
8. **Shared state**: Does the frontend need to share state across multiple components? — determines whether a Pinia store is needed.

---

## Step 2 — Decide which layers to create or update

| Layer | Create or update when…                                                                        |
|---|-----------------------------------------------------------------------------------------------|
| **Entity** | Always (it's the domain object)                                                               |
| **Repository** | Always (data access)                                                                          |
| **Factory** | Entity has relations OR the DB→Entity mapping is non-trivial (enum hydration, nested objects) |
| **Service** | Business logic beyond CRUD (emails, external APIs, calculations, event dispatch)              |
| **Enum** | Fixed status/type values that appear in the entity or database                                |
| **Controller** | Backend API endpoints are needed                                                              |
| **Subscriber** | Feature must react to Joomla events (file upload, status change, etc.)                        |
| **Transformer** | Data format conversion is needed (dates, IBAN, currency)                                      |
| **Vue service** | Any frontend ↔ backend communication                                                          |
| **Pinia store** | State shared across multiple components OR complex async lifecycle                            |
| **Vue component** | Reusable UI element                                                                           |
| **Vue view** | Page-level container mounted from `main.js`                                                   |

For every layer you decide **not** to create or update, briefly say why (e.g. "no Factory needed — flat entity with trivial hydration").

---

## Step 3 — Output the plan

### 3a. Assumptions

List all assumptions you made in Step 1, so the developer can correct them if needed.

### 3b. File map

List every file to create. Use exact paths.

```
components/com_emundus/classes/Entities/<Domain>/<Name>Entity.php
components/com_emundus/classes/Repositories/<Domain>/<Name>Repository.php
components/com_emundus/classes/Factories/<Domain>/<Name>Factory.php      ← if needed
components/com_emundus/classes/Services/<Domain>/<Name>Service.php       ← if needed
components/com_emundus/classes/Enums/<Domain>/<Name>Enum.php             ← if needed
components/com_emundus/controllers/<domain>.php
components/com_emundus/src/services/<domain>.js                          ← if frontend
components/com_emundus/src/stores/<domain>.js                            ← if shared state
components/com_emundus/src/components/<Domain>/<Name>.vue                ← if needed
components/com_emundus/src/views/<Domain>/<Name>View.vue                 ← if new page
```

### 3c. File-by-file descriptions

For each file, provide a short block with:

- **Path** — exact location
- **Class / export name** — what it's called
- **Responsibility** — one sentence: what this file owns and what it does not own
- **Key contents** — the main properties, methods, or exports to define (names and types, not code)
- **Why it's here** — the architectural reason this layer exists for this feature

Example format:

---

**`components/com_emundus/classes/Entities/Rating/RatingEntity.php`**
Class: `RatingEntity` · Namespace: `Tchooz\Entities\Rating`
Responsibility: holds one rating record as a typed in-memory object; no DB logic.
Key contents: properties `id`, `fnum`, `coordinatorId`, `score` (int 1–5), `comment`, `createdAt`; getters and setters for each.
Why: the entity layer ensures the rest of the codebase works with typed objects, not raw DB rows.

---

Do this for every file in the map. Be as specific as possible about method names and property names — this is what the developer needs to start writing.

### 3d. Creation order

List the files in the order they should be created and explain the dependency reason for each step. The natural order is: Enum → Entity → Repository → Factory → Service → Controller → Vue service → Vue store → Vue component → Vue view.

### 3e. DB table(s)

If backend is in scope, describe each new table: name, columns with types, and any constraints (unique keys, foreign keys, indexes). No SQL — just a description.

---

## Naming conventions

| Item | Convention | Example |
|---|---|---|
| Domain folder | `PascalCase` (singular) | `Invoice/` |
| Entity class | `<Name>Entity` | `InvoiceEntity` |
| Repository class | `<Name>Repository` | `InvoiceRepository` |
| Factory class | `<Name>Factory` | `InvoiceFactory` |
| Service class | `<Name>Service` | `InvoiceService` |
| Controller class | `EmundusController<Name>` | `EmundusControllerInvoice` |
| Controller file | `<domain>.php` (lowercase) | `invoice.php` |
| DB table | `jos_emundus_<domain>` (literal prefix, never `#__`) | `jos_emundus_invoices` |
| Vue service file | `<domain>.js` (lowercase) | `invoice.js` |
| Pinia store id | `<domain>` (lowercase) | `'invoice'` |
| Vue view file | `<Name>View.vue` | `InvoiceView.vue` |
| Vue component file | `<Name>.vue` | `InvoiceCard.vue` |
| DOM mount point | `em-<domain>-vue` | `em-invoice-vue` |

---

## Key architectural rules (mention if relevant to the feature)

**Controller access control**: new controllers use `#[AccessAttribute]` PHP attributes above each method. The old `EmundusHelperAccess::asCoordinatorAccessLevel()` runtime-check pattern exists in the codebase but must not be used in new code.

**Controller responses**: methods return `EmundusResponse::ok($data)` or `EmundusResponse::fail($msg, $code)` directly. The base class handles serialization. Old controllers that build manual `['status' => true, ...]` arrays exist but are legacy.

**Vue**: Options API only (no Composition API, no `<script setup>`). Global mixins (`translate`, `alerts`) are incompatible with the Composition API.

**FetchClient**: Vue services use `FetchClient`, not raw `fetch` or `axios`.

---

## Step 4 — After presenting the plan

- Flag any existing domain in the codebase that overlaps with this feature. Check `classes/Entities/`, `classes/Repositories/`, and `src/components/` before finalizing. If an overlap exists, recommend whether to extend the existing code or create a new domain, and why.
- Ask the user to confirm or adjust the plan.
- Once confirmed, the `frontend-conventions` skill can implement the Vue files, and the `test-writer` skill can generate tests for the PHP classes.

---

## Step 5 — Produce the feature specification file

After the plan is presented, **always** write a feature specification file to disk that follows the structure of `.claude/skills/architectural-designer/feature-template.md`. This is the deliverable the developer hands off as an issue/ticket.

- **Read the template** at `.claude/skills/architectural-designer/feature-template.md` first to get the exact current section structure (it may have changed since this skill was written).
- **Fill every section** with content derived from the feature and the architectural plan above — do not leave placeholder text like "Criterion 1" or "Item 1". Replace it with real, feature-specific content:
  - **Summary** — one-paragraph description of the feature.
  - **Context** — the problem it solves, grounded in the user's request.
  - **Proposal** — the technical implementation, summarizing the layers and files from Step 3 (the file map and creation order).
  - **Acceptance Criteria checklist** — concrete, checkable conditions derived from the CRUD operations, access rules, and business logic identified in Step 1.
  - **Dependencies** — prerequisites, integration points (Fabrik, external APIs), and any overlapping domains flagged in Step 4.
  - **Cybersecurity Analysis** — fill each sub-section (Access, Data Privacy, Guaranteed data integrity and consistency, Data update traceability) with items specific to this feature, e.g. the `#[AccessAttribute]` rules for Access, what personal data is stored for Data Privacy, etc.
  - **Design Elements** — note mockups if known, otherwise state none provided.
  - **Test Scenarios** — key test cases aligned with the acceptance criteria.
  - **Success-story** — what success looks like and how to measure it.
- **Where to write it**: save as `<domain>-feature.md` (lowercase domain) in the repository root, unless the user specifies another path.
- After writing the file, tell the user its path and confirm it was generated from the agreed plan.
