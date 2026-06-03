---
name: frontend-conventions
description: Generates and edits Vue.js frontend code following Tchooz project conventions. Use this skill whenever the user asks to create or edit a Vue component, write or extend a service file, make API calls from the frontend, add translations, work with Pinia stores, or do any frontend Vue work in this project.
---

# Tchooz frontend conventions

Vue 3 (Options API), Tailwind with `tw-` prefix, Joomla backend reached through a custom `FetchClient`.

## Workflow

### Step 1 — Match the mockup

If a mockup is provided, treat it as source of truth. Match icons, spacing, radius, colors, alignment, typography, and states exactly. Mockup details in `references/components.md`.

---

### Step 2 — Reuse before building

Before writing markup for a button, input, modal, popover, loader, card, tag, etc., check what already exists:

```
ls components/com_emundus/src/components/Atoms/
ls components/com_emundus/src/components/Molecules/
ls components/com_emundus/src/components/Utils/
```

Plus root-level reusables (`Modal.vue`, `AdvancedSelect.vue`, `Popover.vue`, `Skeleton.vue`, `IncrementalSelect.vue`). If a matching component exists, reuse it. If it lacks a prop or slot, extend it rather than duplicate.

---

### Step 3 — Write or edit the component

Write or edit the component. Structure, Tailwind, actions, mockup fidelity: see `references/components.md`.

---

### Step 4 — Wire up the data layer

If the task involves API calls, Pinia stores, or cross-module events: see `references/data-layer.md`.

---

### Step 5 — Translate user-visible strings

For any user-visible string, use the translate mixin. See `references/translations.md`.

---

### Step 6 — Verify the build passes

```
cd components/com_emundus && npm run build
```

## Non-negotiable rules

These are the rules where deviation silently breaks things or breaks team consistency.

**Tailwind prefix `tw-` on every utility.** Tailwind is configured with a `tw-` prefix to avoid collisions with Joomla's Bootstrap classes. Without the prefix, styles look like they apply but don't. Project-specific CSS classes (`em-main-500-color`, etc.) do not take the prefix.

**Options API only.** The codebase uses global mixins (`translate`, `alerts`) registered in `main.js`. These don't compose with `<script setup>` or `setup()`. Composition API breaks the mixin pattern.

**Never hardcode user-facing strings.** Always go through `translate('KEY')` in components or `Joomla.Text._('KEY')` in plain JS.

**Use existing service files.** Don't import `FetchClient` directly into components. Don't use raw `axios` or `fetch`.

## Reading strategy

Read the target file directly. Avoid broad directory exploration except for the component-existence check above. For files over 200 lines, use a view range. If a file is already in context, reference it without re-reading.

## Output checklist

Before finishing, verify:

- Tailwind classes prefixed with `tw-`
- Existing components reused where possible
- Strings go through `translate()`
- Service files used, no direct `FetchClient` in components
- Listeners added in `mounted` are removed in `beforeUnmount`
- Action buttons `:disabled` on empty input or while loading
- Destructive actions confirmed via `alertConfirm`
- New translation keys listed at the end of the response
- Component under 200 lines, otherwise split by responsibility
- Build passes