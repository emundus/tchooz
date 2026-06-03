# Translations

Every user-visible string goes through the translate mixin. The mixin is registered globally in `main.js`, so `translate()` and `this.translate()` are available everywhere without import.

## In templates

```html
<button>{{ translate('COM_EMUNDUS_SAVE') }}</button>
<label>{{ translate('COM_EMUNDUS_FIELD_NAME') }}</label>
<p v-if="error">{{ translate('COM_EMUNDUS_ERROR_GENERIC') }}</p>
```

Pass translation keys as props to child components that translate internally:

```html
<MyComponent :title="'COM_EMUNDUS_WORKFLOW_TITLE'" />
```

## In service files or standalone JS

The mixin isn't available outside Vue components. Use Joomla's text helper:

```javascript
const message = Joomla.Text._('COM_EMUNDUS_OPERATION_FAILED');
```

## Key naming

Format: `COM_EMUNDUS_` + `DOMAIN_` + `DESCRIPTION`, all uppercase.

Examples:

- `COM_EMUNDUS_WORKFLOW_STEP_DELETE_CONFIRM`
- `COM_EMUNDUS_USER_PROFILE_SAVE_SUCCESS`
- `COM_EMUNDUS_FORM_VALIDATION_REQUIRED`

## End of task

At the end of any task that introduces new translation keys, list every new key in the response so it can be added to the language files. Format:

```
New translation keys to add:
- COM_EMUNDUS_NEW_KEY_ONE
- COM_EMUNDUS_NEW_KEY_TWO
```