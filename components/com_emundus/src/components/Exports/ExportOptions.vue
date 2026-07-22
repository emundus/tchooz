<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';
import exportService from '@/services/export.js';

const PIVOT_SCOPE = 'pivot_scope';
const PIVOT_TARGET = 'pivot_target';

const SCOPE_GROUP = 'group';
const SCOPE_ELEMENT = 'element';
const SCOPE_EVALUATION = 'evaluation';

// Keeps the target-field label in sync with the picked scope. Backend label uses
// a `%s` placeholder that we substitute with the scope's own translation.
const SCOPE_LABEL_KEYS = {
	[SCOPE_GROUP]: 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_GROUP',
	[SCOPE_ELEMENT]: 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_ELEMENT',
	[SCOPE_EVALUATION]: 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_EVALUATION',
};

export default {
	name: 'ExportOptions',
	components: { ParameterForm },
	mixins: [transformIntoParameterField],

	props: {
		modelValue: {
			type: Object,
			default: () => ({}),
		},
		format: {
			type: String,
			required: true,
		},
		elements: {
			type: Array,
			required: true,
		},
		allElements: {
			type: Array,
			required: true,
		},
	},

	emits: ['update:modelValue'],

	data() {
		return {
			loading: false,
			fields: [],
			groups: [],
		};
	},

	created() {
		this.loadSchema();
	},

	watch: {
		format() {
			this.loadSchema();
		},

		// When the user changes the scope, refresh the target dropdown's options
		// and drop the previously-picked target if it no longer belongs to the new scope.
		'modelValue.pivot_scope'(newScope, oldScope) {
			if (newScope === oldScope) return;
			this.refreshPivotTarget(newScope);
		},
	},

	methods: {
		async loadSchema() {
			this.loading = true;

			try {
				const response = await exportService.getOptionsSchema(this.format);

				if (!response.status || !Array.isArray(response.data)) {
					this.fields = [];
					this.groups = [];
					return;
				}

				this.fields = response.data;

				const initialValues = {};
				this.fields.forEach((field) => {
					initialValues[field.name] =
						this.modelValue && Object.prototype.hasOwnProperty.call(this.modelValue, field.name)
							? this.modelValue[field.name]
							: field.default;
				});

				this.groups = await this.fieldsToParameterFormGroups(this.fields, initialValues);
				this.applyFilenameSanitizer();

				// Render helptexts inline under each field label rather than behind an info
				// icon. The mixin doesn't set this — ParameterForm defaults to 'icon' otherwise.
				this.groups.forEach((group) => {
					group.helpTextType = 'above';
				});

				const scopeField = this.findParameter(PIVOT_SCOPE);
				if (scopeField) {
					scopeField.helptext = 'COM_EMUNDUS_EXPORT_PIVOT_SCOPE_HELPTEXT';
				}

				this.enhancePivotTargetField(initialValues[PIVOT_SCOPE], initialValues[PIVOT_TARGET]);

				this.$emit('update:modelValue', { ...initialValues });
			} finally {
				this.loading = false;
			}
		},

		// Scope-dependent target: rebuild the grouped multiselect options from the
		// user's selected content, then reset the current target if the new scope
		// no longer includes it. Called on scope change AND on template reload.
		refreshPivotTarget(scope) {
			const targetField = this.findParameter(PIVOT_TARGET);
			if (!targetField) return;

			const options = this.buildPivotTargetOptions(scope);
			targetField.multiselectOptions = {
				...(targetField.multiselectOptions || {}),
				options,
			};
			targetField.displayed = options.length > 0;
			targetField.label = this.buildTargetLabel(scope);

			// Drop any stale selection that doesn't survive the scope change
			const currentTarget = this.modelValue?.[PIVOT_TARGET];
			if (currentTarget && !this.optionsIncludeValue(options, currentTarget)) {
				targetField.value = null;
				const next = { ...(this.modelValue || {}) };
				next[PIVOT_TARGET] = null;
				this.$emit('update:modelValue', next);
			}

			// Parameter.vue caches `multiselectOptions.options` in local `multiOptions`
			// at mount and never re-reads it — bump `reload` so ParameterForm remounts
			// the component with the fresh option list.
			targetField.reload = (targetField.reload || 0) + 1;
		},

		enhancePivotTargetField(initialScope, initialTarget) {
			const targetField = this.findParameter(PIVOT_TARGET);
			if (!targetField) return;

			const options = this.buildPivotTargetOptions(initialScope);

			Object.assign(targetField, {
				type: 'multiselect',
				multiple: false,
				helptext: 'COM_EMUNDUS_EXPORT_PIVOT_DATA_HELPTEXT',
				label: this.buildTargetLabel(initialScope),
				displayed: options.length > 0,
				multiselectOptions: {
					options,
					noOptions: false,
					multiple: false,
					taggable: false,
					searchable: true,
					internalSearch: true,
					optionsPlaceholder: 'COM_EMUNDUS_MULTISELECT_ADDKEYWORDS',
					placeholder: 'PLEASE_SELECT',
					selectLabel: 'PRESS_ENTER_TO_SELECT',
					selectGroupLabel: 'PRESS_ENTER_TO_SELECT_GROUP',
					selectedLabel: 'SELECTED',
					deselectedLabel: 'PRESS_ENTER_TO_REMOVE',
					deselectGroupLabel: 'PRESS_ENTER_TO_DESELECT_GROUP',
					noOptionsText: 'COM_EMUNDUS_MULTISELECT_NOKEYWORDS',
					noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
					tagValidations: [],
					tagRegex: '',
					trackBy: 'value',
					label: 'label',
					groupValues: 'options',
					groupLabel: 'groupLabel',
					groupSelect: false,
				},
			});

			if (initialTarget !== null && initialTarget !== undefined && initialTarget !== '') {
				targetField.value = initialTarget;
			}
		},

		// Route to the right option-builder based on scope. All builders derive
		// from `this.elements` — the user's selected export content — so pivot
		// options stay restricted to what the export will actually contain.
		buildPivotTargetOptions(scope) {
			switch (scope) {
				case SCOPE_GROUP:
					return this.buildGroupOptions();
				case SCOPE_ELEMENT:
					return this.buildElementOptions();
				case SCOPE_EVALUATION:
					return this.buildEvaluationOptions();
				default:
					return [];
			}
		},

		// Element scope: the original behavior — expose every element that carries
		// repeatable data (repeat group / checkbox / databasejoin multilist),
		// grouped by their section for readability.
		buildElementOptions() {
			const groups = {};
			this.elements.forEach((element) => {
				if (!this.isElementPivotable(element)) return;
				this.pushOptionInGroup(groups, element, {
					value: element.id,
					label: element.label,
				});
			});
			return Object.values(groups);
		},

		// Group scope: only repeatable groups matter; each becomes one target.
		// Deduped by group_id so a group with N selected elements appears once.
		buildGroupOptions() {
			const seen = new Set();
			const groups = {};

			this.elements.forEach((element) => {
				const groupParams = this.safeParse(element.group_params);
				if (parseInt(groupParams.repeat_group_button, 10) !== 1) return;
				if (seen.has(element.group_id)) return;
				seen.add(element.group_id);

				this.pushOptionInGroup(groups, element, {
					value: element.group_id,
					label: element.group_label || this.translate('COM_EMUNDUS_FORM_BUILDER_UNNAMED_SECTION'),
				});
			});

			return Object.values(groups);
		},

		// "Formulaire" scope: evaluation forms whose workflow step is flagged multiple
		// (several submissions per file). The backend tags every management element with
		// `is_multiple_evaluation`; we keep only those and dedupe by form_id.
		buildEvaluationOptions() {
			const seen = new Set();
			const forms = [];

			this.elements.forEach((element) => {
				if (element.is_multiple_evaluation !== true) return;
				if (!element.form_id || seen.has(element.form_id)) return;
				seen.add(element.form_id);

				forms.push({
					value: element.form_id,
					label: element.form_label || this.translate('COM_EMUNDUS_FORM_BUILDER_UNNAMED_SECTION'),
				});
			});

			if (forms.length === 0) return [];

			return [
				{
					groupLabel: this.translate('COM_EMUNDUS_EXPORT_PIVOT_SCOPE_EVALUATION'),
					options: forms,
				},
			];
		},

		isElementPivotable(element) {
			const groupParams = this.safeParse(element.group_params);
			const elementParams = this.safeParse(element.params);
			return (
				parseInt(groupParams.repeat_group_button, 10) === 1 ||
				element.plugin === 'checkbox' ||
				(element.plugin === 'databasejoin' && elementParams.database_join_display_type === 'multilist')
			);
		},

		pushOptionInGroup(groups, element, option) {
			if (!groups[element.group_id]) {
				let groupLabel = element.form_label ? element.form_label + ' - ' : '';
				groupLabel +=
					element.group_label !== '' ? element.group_label : this.translate('COM_EMUNDUS_FORM_BUILDER_UNNAMED_SECTION');
				groups[element.group_id] = { groupLabel, options: [] };
			}
			groups[element.group_id].options.push(option);
		},

		optionsIncludeValue(groupedOptions, value) {
			return groupedOptions.some((group) => (group.options || []).some((opt) => String(opt.value) === String(value)));
		},

		// `group_params` / `params` arrive as raw strings from FabrikElementEntity::toArray;
		// they default to `''` when the group/element has no JSON params row, so JSON.parse
		// would throw and silently drop every following element.
		safeParse(raw) {
			if (typeof raw !== 'string' || raw === '') return {};
			try {
				return JSON.parse(raw) || {};
			} catch {
				return {};
			}
		},

		findParameter(name) {
			for (const group of this.groups) {
				const param = group.parameters?.find((p) => p.param === name);
				if (param) return param;
			}
			return null;
		},

		// Pre-substitute `%s` in the target-field label with the current scope's
		// own translation. Parameter.vue calls `translate()` on the label; passing
		// an unknown-key string returns it verbatim, which is what we want here.
		buildTargetLabel(scope) {
			const template = this.translate('COM_EMUNDUS_EXPORT_PIVOT_DATA_LABEL');
			const scopeKey = SCOPE_LABEL_KEYS[scope];
			const scopeLabel = scopeKey ? this.translate(scopeKey) : '';
			return template.replace('%s', scopeLabel);
		},

		onParameterValueUpdated(parameter) {
			let value = parameter.value;

			// The pivot target multiselect emits the full `{value, label}` option; persist the id only.
			if (parameter.param === PIVOT_TARGET && value && typeof value === 'object' && 'value' in value) {
				value = value.value;
			}

			const next = { ...(this.modelValue || {}) };
			next[parameter.param] = value;
			this.$emit('update:modelValue', next);
		},

		// The "filename" option feeds FilenameRenderer, which keeps only [A-Za-z0-9_.-] (plus the
		// %TAG% / [fabrik] template delimiters) and drops everything else — spaces, accents,
		// punctuation. Mirror that on the input so the user sees exactly what will be kept.
		applyFilenameSanitizer() {
			this.groups.forEach((group) => {
				const filenameParam = (group.parameters || []).find((param) => param.param === 'filename');
				if (filenameParam) {
					filenameParam.sanitizePattern = '[^A-Za-z0-9_.%\\[\\]-]';
				}
			});
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-4">
		<div v-if="loading" class="tw-flex tw-min-h-[10vh] tw-flex-col tw-items-center tw-justify-center">
			<div class="em-loader"></div>
		</div>

		<ParameterForm
			v-else-if="groups.length > 0"
			:groups="groups"
			:fields="fields"
			@parameter-value-updated="onParameterValueUpdated"
		/>

		<div v-else class="tw-text-neutral-600">
			{{ this.translate('COM_EMUNDUS_EXPORT_NO_OPTIONS_AVAILABLE') }}
		</div>
	</div>
</template>

<style scoped></style>
