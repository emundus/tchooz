# Component patterns

## File structure

Order of tags: `<script>` first, then `<template>`, then `<style scoped>`.

This is the project convention. It keeps imports, props, and state at the top of the file where they're easier to scan.

Options order inside the component:

```
name, components, mixins, props, emits, data, computed, watch,
created, mounted, beforeUnmount, methods
```

Mandatory parts:

- `name` in PascalCase
- `components: {...}` declaring every child component used
- `emits: [...]` declaring every event emitted via `$emit`
- `<style scoped>` only for what Tailwind can't express

`translate` is registered globally in `main.js`. Don't import it. Other mixins (`alerts`, etc.) must be imported.

## Size

If a component goes past 200 lines, split it by responsibility (`ComponentCard`, `ComponentForm`, `ComponentFilters`, etc.). A monolithic component is a refactor smell.

## Tailwind

Every utility class needs the `tw-` prefix:

```html
<div class="tw-flex tw-items-center tw-gap-4">
<button class="tw-bg-blue-500 tw-text-white tw-rounded tw-px-4 tw-py-2 hover:tw-bg-blue-600">
```

Project-specific classes (`em-main-500-color`, `coordinator-form`) keep their original names without the prefix.

Keep class lists minimal. Don't stack utilities that don't change the visual output.

## Actions

Action buttons should reflect their state:

```html
<button :disabled="!input.trim() || loading" @click="save">
  {{ translate('COM_EMUNDUS_SAVE') }}
</button>
```

Destructive actions (delete, reset, anything irreversible) go through the `alerts` mixin:

```javascript
import alerts from '@/mixins/alerts.js';

export default {
  mixins: [alerts],
  methods: {
    async remove() {
      const confirmed = await this.alertConfirm('COM_EMUNDUS_WORKFLOW_DELETE_CONFIRM');
      if (!confirmed) return;
      // proceed
    },
  },
};
```

API failures should surface to the user via `this.alertError(...)`.

## Mockup fidelity

When a design mockup is provided, treat it as the source of truth.

- **Icons**: use the exact icon shown. Check Google Fonts Material Symbols before substituting.
- **Spacing**: translate gaps and padding to the Tailwind value that matches, not the nearest round number.
- **Radius**: sharp corners are `tw-rounded-none`, otherwise match (`tw-rounded`, `tw-rounded-lg`, `tw-rounded-full`).
- **Colors**: use the exact token. Don't substitute `tw-blue-500` for `tw-blue-600` because it "looks close".
- **Layout**: row vs column, alignment, full-width vs constrained. Respect what's shown.
- **Typography**: match size, weight, and color exactly.
- **States**: implement hover, disabled, active, empty states if the mockup shows them.

When something looks ambiguous or wrong, implement it as shown and flag the question.

## Existing components catalog

Before writing new markup, list the relevant folder. The names below are indicative, not exhaustive, and they will drift over time. Trust the directory listing over this list.

- **Atoms** (`src/components/Atoms/`): primitive building blocks. Examples: Button, Loader, Chip, Tag, Avatar, SplitButton, FormatSelector, CountryFlag.
- **Molecules** (`src/components/Molecules/`): composed atoms. Examples: Card, Stepper, GridDetails, Tabs.
- **Utils** (`src/components/Utils/`): cross-cutting utilities. Examples: NoResults, Pagination, Info, Back, ToggleInput, GridPreview, Parameter.
- **Root level**: Modal, AdvancedSelect, IncrementalSelect, Skeleton, Popover.

If a component is close to what you need but missing a prop or slot, extend it. Don't duplicate.