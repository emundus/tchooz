<script>
import automationService from '@/services/automation.js';
import { useFormBuilderStore } from '@/stores/formbuilder.js';

export default {
	name: 'BuildCalculationOperation',
	props: {
		operation: {
			type: String,
			required: true,
			default: '',
			// example: "a + b"
		},
		fields: {
			type: Object,
			required: true,
			default: () => ({}),
			// example: {a: {type: 'form_data', field: '398.8205'}, b: {type: 'form_data', field: '398.8206'}}
		},
	},
	data() {
		return {
			operators: [
				{ label: '+', value: '+', desc: '' },
				{ label: '-', value: '-', desc: '' },
				{ label: 'COM_EMUNDUS_FORMBUILDER_CALC_OPERATOR_MULTIPLICATION', value: '*', desc: '' },
				{ label: 'COM_EMUNDUS_FORMBUILDER_CALC_OPERATOR_DIVISION', value: '/', desc: '' },
				{ label: 'COM_EMUNDUS_FORMBUILDER_CALC_OPERATOR_POW', value: '**', desc: '' },
				{ label: '()', value: '()', desc: '' },
				{ label: 'COM_EMUNDUS_FORMBUILDER_CALC_OPERATOR_MODULO', value: '%', desc: '' },
				{ label: 'MIN()', value: 'MIN()', desc: 'COM_EMUNDUS_FORMBUILDER_CALC_OPERATOR_MIN_HELPTEXT' },
				{ label: 'MAX()', value: 'MAX()', desc: 'COM_EMUNDUS_FORMBUILDER_CALC_OPERATOR_MAX_HELPTEXT' },
			],

			availableFields: [],

			showDropdown: false,
			search: '',
			initialized: false,
		};
	},
	created() {
		const fieldNames = Object.values(this.fields).map((field) => field.field);

		this.searchFields('', 'form_data', {
			storedValues: fieldNames,
		});
	},
	mounted() {
		// wait loading to finish before rendering expression
		this.renderFromExpression();
	},
	methods: {
		renderFromExpression() {
			const editor = this.$refs.editor;

			if (!this.operation) {
				editor.innerHTML = '';
				return;
			}

			let expression = this.operation;

			Object.entries(this.fields).forEach(([key, field]) => {
				const token = this.createToken(field);

				const regex = new RegExp(`\\b${key}\\b`, 'g');

				expression = expression.replace(regex, token.outerHTML);
			});

			// make sure that end of expression is not a token, otherwise the cursor will be placed before it and user won't be able to add operators after it.
			if (expression.endsWith('>')) {
				expression += '&nbsp;';
			}

			editor.innerHTML = expression;
		},

		createToken(field) {
			const span = document.createElement('span');

			span.className = 'tw-bg-gray-200 tw-rounded tw-px-1 tw-py-0.5 tw-mx-1 field-token';
			span.contentEditable = false;

			span.dataset.id = field.type + '|' + field.field;
			span.dataset.field = field.field;
			span.dataset.type = field.type;

			span.innerText = field.label || this.getFieldLabelFromFieldName(field);

			return span;
		},

		getFieldLabelFromFieldName(field) {
			let label = this.translate('COM_EMUNDUS_FIELD') + ' ' + field.field;

			// find field label in availableFields
			const availableField = this.availableFields.find((f) => f.field === field.field && f.type === field.type);
			if (availableField) {
				label = availableField.label;
			}

			return label;
		},

		addField(field) {
			const token = this.createToken(field);

			// replace this.search inside editor with token
			this.$refs.editor.focus();

			// replace the last occurrence of this.search in editor with token
			const editor = this.$refs.editor;
			const html = editor.innerHTML;
			const lastIndex = html.lastIndexOf(this.search);

			if (lastIndex !== -1) {
				const before = html.slice(0, lastIndex);
				const after = html.slice(lastIndex + this.search.length);
				editor.innerHTML = before;
				editor.appendChild(token);
				editor.innerHTML += after;
			} else {
				editor.appendChild(token);
			}

			this.search = '';
			this.showDropdown = false;

			// add a non-breaking space after token to make sure that user can add operators after it without them being inside the token
			editor.innerHTML += '&nbsp;';

			this.updateExpressionFromEditor();
		},

		onInput(e) {
			const selection = window.getSelection();
			const textBefore = selection.anchorNode?.textContent?.slice(0, selection.anchorOffset) || '';
			const regex = /([\p{L}\p{N}]*)$/u;

			const match = textBefore.match(regex);
			if (match && match[1] !== '') {
				this.search = match[1];
				this.showDropdown = true;

				this.searchFields(this.search);
			} else {
				this.showDropdown = false;
			}

			this.updateExpressionFromEditor();
		},
		onBackspace() {
			const sel = window.getSelection();
			const node = sel.anchorNode;

			if (!node) return;

			if (node.parentElement.classList.contains('field-token')) {
				node.parentElement.remove();
			} else if (node.classList && node.classList.contains('field-token')) {
				node.remove();
			} else {
				// default behavior
			}

			this.updateExpressionFromEditor();
		},

		updateExpressionFromEditor() {
			const editor = this.$refs.editor;
			if (editor.innerHTML === '<br>' || editor.innerHTML === '<div><br></div>') {
				editor.innerHTML = '';
			}
			let operation = '';
			let fields = {};

			editor.childNodes.forEach((node) => {
				if (node.nodeType === Node.TEXT_NODE) {
					operation += node.textContent;
				} else if (node.nodeType === Node.ELEMENT_NODE) {
					if (node.classList.contains('field-token')) {
						const id = node.dataset.id;
						const type = node.dataset.type;
						// key is a char like 'a', 'b', etc. We can generate it based on current fields count
						const key = String.fromCharCode(97 + Object.keys(fields).length); // 'a' = 97 in ASCII
						fields[key] = { type, field: node.dataset.field };
						operation += key;
					}
				}
			});

			this.$emit('updateOperation', operation, fields);
		},

		addOperator(operator) {
			const editor = this.$refs.editor;

			editor.innerHTML += operator;

			this.updateExpressionFromEditor();
		},

		searchFields(search, type = 'form_data', parameters = {}) {
			parameters.formId = useFormBuilderStore().formId;

			automationService.getConditionsFields(type, parameters, search).then((response) => {
				if (response.status) {
					// add found fields to availableFields, but only if they are not already in it
					response.data.forEach((field) => {
						if (!this.availableFields.some((f) => f.field === field.name)) {
							this.availableFields.push({
								type: type,
								field: field.name,
								label: field.label,
							});
						}
					});
				}

				if (!this.initialized) {
					this.renderFromExpression();
					this.initialized = true;
				}
			});
		},
	},
	computed: {
		filteredFields() {
			if (!this.search) return this.availableFields;

			// max 50 results
			return this.availableFields
				.filter((field) => field.label.toLowerCase().includes(this.search.toLowerCase()))
				.slice(0, 50);
		},
	},
};
</script>

<template>
	<div id="calc-builder">
		<label class="parameter-label tw-mb-0 tw-flex tw-items-end tw-font-medium">{{
			translate('COM_EMUNDUS_FORMBUILDER_CALC_OPERATION_LABEL')
		}}</label>
		<div
			ref="editor"
			class="tw-w-full tw-rounded tw-border tw-p-2"
			contenteditable
			@input="onInput"
			@keydown.delete="onBackspace"
			@keydown.backspace="onBackspace"
			:data-placeholder="translate('COM_EMUNDUS_FORMBUILDER_CALC_OPERATION_PLACEHOLDER')"
		></div>
		<div
			id="dropdown-search"
			v-if="showDropdown"
			class="tw-absolute tw-z-10 tw-mt-1 tw-w-full tw-overflow-auto tw-rounded tw-border tw-bg-white tw-p-2 tw-shadow"
		>
			<div
				v-for="field in filteredFields"
				:key="field.field"
				class="tw-cursor-pointer tw-p-1 hover:tw-bg-gray-200"
				@click="addField(field)"
			>
				{{ field.label }}
			</div>
			<p v-if="filteredFields.length < 1">
				<i>{{ translate('COM_EMUNDUS_FORMBUILDER_CALC_OPERATION_NO_SEARCH_RESULT') }}</i>
			</p>
		</div>
		<p class="tw-mt-1 tw-text-xs">{{ translate('COM_EMUNDUS_FORMBUILDER_CALC_OPERATION_HELPTEXT') }}</p>

		<label class="parameter-label tw-mb-0 tw-mt-4 tw-flex tw-items-end tw-font-medium">{{
			translate('COM_EMUNDUS_FORMBUILDER_CALC_SUPPORTED_OPERATORS')
		}}</label>
		<div class="tw-mt-2 tw-grid tw-w-full tw-grid-cols-3 tw-gap-2">
			<div
				v-for="operator in operators"
				:key="operator.value"
				class="tw-cursor-pointer tw-rounded tw-border tw-p-2"
				:title="translate(operator.desc)"
				@click="addOperator(operator.value)"
			>
				{{ translate(operator.label) }}
			</div>
		</div>
	</div>
</template>

<style scoped>
[contenteditable]:empty::before {
	content: attr(data-placeholder);
	color: #9ca3af; /* tw-text-gray-400 */
	pointer-events: none;
}
</style>
