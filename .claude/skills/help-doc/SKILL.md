---
name: help-doc
description: Produces client-facing help documentation (French, HTML guide with screenshot slots, packaged as a zip) for a Tchooz / eMundus feature, from the shared eMundus help-doc template. Use this skill whenever the user asks for a help article, user guide, "documentation d'aide", "article d'aide", "guide d'utilisation", "doc client" or end-user documentation for a feature — form-builder field, module, workflow, setting. Do NOT use it for developer/technical docs (ADR, README, code comments, docs/ in the repo).
---

# Help documentation (articles d'aide)

Client-facing guides share one look and one structure. Never design a page from scratch: start from `assets/feature-doc-template.html` (next to this file).

Audience = platform users (coordinators, applicants), **not developers**. Language = **French**. No code, no table names, no API keys, no internal class names.

---

## Workflow

### 1. Ground the content in the real feature
Before writing a line, explore the feature (Explore agent for anything spanning several files):
- **Exact UI labels** from the language files — `components/com_emundus/language/fr-FR/fr-FR.com_emundus.ini` and, for a Fabrik element, `plugins/fabrik_element/<plugin>/language/fr-FR/*.ini`. Quote labels and messages verbatim; never paraphrase a button or an error message.
- **Real behaviour**: parameter defaults, what each state looks like for the end user, what blocks a submission, edge cases (repeat groups, hidden groups, unavailable service…).
- **Filtering rules** that make the admin "not find" something — these become FAQ entries.

### 2. Clarify with the user (only what you cannot infer)
- Which **screenshots** they want (one slot per capture; name them in the answer).
- Specific **FAQ questions** to answer, and which cases to cover or leave out.
- Deliverable: default = zip folder (below). Offer a published Artifact only if they want a shareable link (then screenshots can't be embedded — see §4).

### 3. Build the page from the template
Copy the template, then:
- Replace `{{FEATURE_NAME}}`, `{{FEATURE_TAGLINE}}`, `{{FEATURE_INTRO}}` (also in `<title>` and `<footer>`).
- Rewrite the `<nav class="toc">` and the `<section>`s. Keep `<span class="num">` numbering in sync with the TOC order; use readable ids (`#parametrage`, `#faq`).
- Remove the commented example blocks you did not use.

Typical outline (adapt, don't force):
1. Présentation — what it does, in cards
2. Ajouter / activer la fonctionnalité — `.steps`
3. Paramétrer — options table (option / effet / défaut), procedure, capture
4. Côté déposant (or end user) — one capture per meaningful state, table of other cases with the exact messages
5. Règles / validation — `.table-scroll` with `.yes` / `.no`
6. FAQ — question as heading, answer with the cause + a "Solution" `.note`

Available components (all styled for light and dark): `.steps`, `.cards`/`.card`, `.table-scroll`+`<table>` (`.yes`/`.no`), `.perm` chips (`view`/`edit`/`manage` — add a variant if a state needs it, e.g. `closed` using `--danger`), `.note` / `.note.warn` / `.note.danger`, `.formats`/`.fmt` badges, `.ui` for interface labels, `<figure>` screenshot slots.

Left-aligned text columns in tables: the template centers every column but the first; add a `.left` class (`tbody td.left, thead th.left { text-align: left !important; }`) for long text columns.

### 4. Screenshot slots
Pasted screenshots arrive as rendered images, not files — they cannot be embedded by you. Always leave slots for the user:

- **Zip / file deliverable (default):**
  ```html
  <figure>
    <img src="images/capture-1.png" alt="…" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
    <div class="ph"><span class="tag">Capture 1</span><span class="glyph">🖼️</span><span class="desc">Déposez ici « capture-1.png » : <what the capture must show>.</span></div>
    <figcaption><b>Titre.</b> Explication.</figcaption>
  </figure>
  ```
  The hatched placeholder shows until the PNG is dropped in `images/`.
- **Artifact:** keep only the `<div class="ph">` (the CSP blocks external images).

### 5. Package
```
doc-<feature>/
├── <feature>-guide.html
├── LISEZ-MOI.txt      # how to open + one line per capture-N.png → what it must show
└── images/            # empty, the user drops the captures here
```
Zip it (`zip -r doc-<feature>.zip doc-<feature>`) and put the zip where the user asks (default `~/Downloads/`). Generate the working files in the scratchpad or an untracked folder — **never commit the generated doc** into the repo.

### 6. Check before handing over
- Open the HTML in the browser pane, check the layout at desktop and mobile width.
- Every label/message in the guide exists verbatim in the language files.
- TOC entries ↔ section numbers match; every capture slot is listed in `LISEZ-MOI.txt`.

---

## Example

The SIRET field guide (form-builder field, 3 captures: paramétrage / établissement actif / établissement fermé, FAQ « Je ne trouve pas mes champs dans le paramétrage » covering field types and repeat groups) was built with this exact workflow.
