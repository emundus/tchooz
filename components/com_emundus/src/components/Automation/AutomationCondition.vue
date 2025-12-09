<script>
import Parameter from '@/components/Utils/Parameter.vue';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';
import { useAutomationStore } from '@/stores/automation.js';

export default {
	name: 'AutomationCondition',
	components: { Parameter },
	props: {
		condition: {
			type: Object,
			required: true,
		},
		conditionsList: {
			type: Array,
			required: true,
		},
		title: {
			type: String,
			required: false,
			default: 'COM_EMUNDUS_AUTOMATION_CONDITION',
		},
	},
	mixins: [transformIntoParameterField],
	data() {
		return {
			fields: [
				{
					param: 'type',
					type: 'select',
					placeholder: 'COM_EMUNDUS_AUTOMATION_CONDITION_TYPE_PLACEHOLDER',
					value: null,
					label: 'COM_EMUNDUS_AUTOMATION_CONDITION_TYPE',
					helptext: '',
					displayed: true,
					options: [],
					reload: 0,
				},
				{
					param: 'target', // to be populated dynamically based on the selected condition type
					type: 'select',
					placeholder: 'COM_EMUNDUS_AUTOMATION_CONDITION_TARGET_PLACEHOLDER',
					value: null,
					label: 'COM_EMUNDUS_AUTOMATION_CONDITION_TARGET',
					helptext: '',
					displayed: false,
					options: [],
					reload: 0,
				},
				{
					param: 'operator', // to be populated dynamically based on the selected condition type
					type: 'select',
					placeholder: 'COM_EMUNDUS_AUTOMATION_CONDITION_OPERATOR_PLACEHOLDER',
					value: null,
					label: 'COM_EMUNDUS_AUTOMATION_CONDITION_OPERATOR',
					helptext: '',
					displayed: false,
					options: this.operators,
					reload: 0,
				},
				{
					param: 'value', // to be populated dynamically based on the selected condition type
					type: 'text',
					placeholder: 'COM_EMUNDUS_AUTOMATION_CONDITION_VALUE_PLACEHOLDER',
					value: null,
					label: 'COM_EMUNDUS_AUTOMATION_CONDITION_VALUE',
					helptext: '',
					displayed: false,
					options: [],
					multiselectOptions: {},
					reload: 0,
				},
			],
			operators: [],
			operatorsFieldMapping: [],
			initialized: false,
		};
	},
	created() {
		this.operators = useAutomationStore().operators;
		this.operatorsFieldMapping = useAutomationStore().operatorsFieldMapping;

		// set values if they exist in condition prop
		let typeField = this.fields.find((f) => f.param === 'type');
		typeField.options = this.conditionsList.map((condition) => {
			return {
				value: condition.targetType,
				label: this.translate(condition.label),
			};
		});
		let targetField = this.fields.find((f) => f.param === 'target');
		let operatorField = this.fields.find((f) => f.param === 'operator');
		let valueField = this.fields.find((f) => f.param === 'value');

		if (this.condition['type']) {
			typeField.value = this.condition['type'];
			typeField.displayed = true;
			this.onParameterValueUpdated(typeField);

			if (this.condition['target']) {
				targetField.value = this.condition['target'];
				targetField.displayed = true;
				this.onParameterValueUpdated(targetField);

				if (this.condition['operator']) {
					operatorField.value = this.condition['operator'];
					operatorField.displayed = true;
				}

				if (this.condition['value']) {
					if (['select', 'multiselect'].includes(valueField.type) && Array.isArray(valueField.options)) {
						if (valueField.type === 'multiselect' && Array.isArray(this.condition['value'])) {
							valueField.value = valueField.options.filter((opt) => this.condition['value'].includes(opt.value));
						} else {
							valueField.value = this.condition['value'];
						}
					} else {
						valueField.value = this.condition['value'];
					}

					valueField.displayed = true;
					valueField.reload += 1; // to force reload of the select component
				}
			}
		}
	},
	mounted() {
		this.initialized = true;
	},
	methods: {
		onParameterValueUpdated(parameter) {
			if (parameter.param === 'type') {
				// Find the selected condition type
				if (parameter.value) {
					this.resetFields(['operator', 'value']);

					// get fields from conditionsList with targetType = parameter.value
					const selectedCondition = this.conditionsList.find((condition) => condition.targetType === parameter.value);

					if (selectedCondition) {
						let fieldsOptions = selectedCondition.fields
							? selectedCondition.fields.map((field) => {
									return {
										value: field.name,
										label: this.translate(field.label),
									};
								})
							: [];

						const targetField = this.fields.find((field) => field.param === 'target');
						// Update the target field
						if (targetField) {
							if (selectedCondition.searchable) {
								// use a multiselect single value with search, and load options dynamically
								targetField.type = 'multiselect';

								// group options
								let groups = {};
								let groupedOptions = [];

								selectedCondition.fields.forEach((field) => {
									// On suppose que chaque field a une propriété group: { name, label }
									const groupName = field.group?.name || 'default';
									const groupLabel = field.group?.label || 'Autres';

									if (!groups[groupName]) {
										groups[groupName] = {
											groupLabel: groupLabel + '(' + groupName + ')',
											options: [],
										};
									}

									groups[groupName].options.push({
										value: field.name,
										label: field.label,
									});
								});

								// Transformer l'objet groups en tableau
								groupedOptions = Object.values(groups);

								targetField.options = fieldsOptions;
								targetField.multiselectOptions = {
									options: groupedOptions,
									noOptions: false,
									multiple: false,
									taggable: false,
									searchable: true,
									internalSearch: true,
									asyncRoute: 'getConditionsFields&type=' + selectedCondition.targetType,
									asyncController: 'automation',
									asyncCallback: async (response, parameter) => {
										return await this.searchableCallback(response, parameter, selectedCondition);
									},
									optionsLimit: 100,
									optionsPlaceholder: 'COM_EMUNDUS_MULTISELECT_ADDKEYWORDS',
									selectLabel: 'PRESS_ENTER_TO_SELECT',
									selectGroupLabel: 'PRESS_ENTER_TO_SELECT_GROUP',
									selectedLabel: 'SELECTED',
									deselectedLabel: 'PRESS_ENTER_TO_REMOVE',
									deselectGroupLabel: 'PRESS_ENTER_TO_DESELECT_GROUP',
									noOptionsText: 'COM_EMUNDUS_MULTISELECT_NOKEYWORDS',
									noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
									// Can add tag validations (ex. email, phone, regex)
									tagValidations: [],
									tagRegex: '',
									trackBy: 'value',
									label: 'label',
									groupValues: 'options',
									groupLabel: 'groupLabel',
									groupSelect: false,
								};
								targetField.displayed = true;
							} else {
								targetField.type = 'select';
								targetField.options = fieldsOptions;
								targetField.displayed = fieldsOptions.length > 0;

								if (this.initialized) {
									targetField.value = null; // reset value
								}
							}
							targetField.reload += 1;
						}
					}
				}
			}

			if (parameter.param === 'target') {
				if (parameter.value) {
					const selectedValue =
						typeof parameter.value === 'object' && 'value' in parameter.value ? parameter.value.value : parameter.value;

					const selectedCondition = this.conditionsList.find(
						(condition) => condition.targetType === this.condition.type,
					);

					if (selectedCondition) {
						let field = selectedCondition.fields.find((f) => f.name === selectedValue);

						// display the operator and value fields
						let operatorField = this.fields.find((f) => f.param === 'operator');
						if (operatorField) {
							operatorField.options = this.getOperatorsOptionsFromSelectedField(selectedCondition, selectedValue);
							operatorField.displayed = true;

							if (this.initialized) {
								operatorField.reload += 1; // to force reload of the select component
							}
						}

						// Update the value field
						let valueField = this.fields.find((f) => f.param === 'value');
						if (valueField) {
							const parameterFieldFromAutomationField = this.fromFieldEntityToParameter(field, null);
							Object.assign(valueField, parameterFieldFromAutomationField);
							valueField.param = 'value';
							valueField.displayed = true;
							if (this.initialized) {
								valueField.reload += 1; // to force reload of the select component
							}
						}
					}
				}
			}

			this.condition[parameter.param] = parameter.value;
		},

		getOperatorsOptionsFromSelectedField(selectedCondition, selectedValue) {
			const fieldMapping = this.operatorsFieldMapping;
			let operatorsOptions = [];

			if (selectedCondition && selectedCondition.fields) {
				const field = selectedCondition.fields.find((f) => f.name === selectedValue);
				if (field && field.type && fieldMapping[field.type]) {
					const operatorKeys = fieldMapping[field.type] || fieldMapping['default'];
					operatorsOptions = this.operators.filter((op) => operatorKeys.includes(op.value));
				} else {
					operatorsOptions = this.operators.filter((op) => fieldMapping['default'].includes(op.value));
				}
			}

			return operatorsOptions;
		},

		resetFields(fields = ['target', 'operator', 'value']) {
			if (!this.initialized) {
				return;
			}

			fields.forEach((param) => {
				const field = this.fields.find((f) => f.param === param);
				if (field) {
					field.value = null;
					field.displayed = false;
					if (param !== 'operator') {
						field.options = [];
					}
					field.reload += 1; // to force reload of the select component
				}
				this.condition[param] = null;
			});
		},

		async searchableCallback(response, parameter, selectedCondition) {
			return new Promise((resolve, reject) => {
				if (response && response.status && response.data) {
					// Fusionner les nouveaux champs avec les existants
					selectedCondition.fields = [...selectedCondition.fields, ...response.data].filter(
						(field, index, self) => index === self.findIndex((f) => f.name === field.name),
					);

					// Regrouper les options par groupe
					let groups = {};
					selectedCondition.fields.forEach((field) => {
						const groupName = field.group?.name || 'default';
						const groupLabel = field.group?.label || 'Autres';
						if (!groups[groupName]) {
							groups[groupName] = {
								groupLabel: groupLabel + (groupName !== 'default' ? ' (' + groupName + ')' : ''),
								options: [],
							};
						}
						groups[groupName].options.push({
							value: field.name,
							label: field.label,
						});
					});

					// Mettre à jour les options groupées dans le paramètre
					parameter.multiselectOptions.options = Object.values(groups);

					resolve(parameter.multiselectOptions.options);
				} else {
					this.alertError('COM_EMUNDUS_AUTOMATION_CONDITIONS_FIELDS_ERROR', response?.msg);
					reject([]);
				}
			});
		},
	},
	computed: {
		displayedFields() {
			return this.fields.filter((field) => field.displayed);
		},
	},
};
</script>

<template>
	<div
		:id="'condition-' + condition.id"
		class="tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
			<h4 class="tw-m-0">{{ translate(this.title) }}</h4>
			<span
				class="material-symbols-outlined tw-cursor-pointer tw-text-red-500"
				@click="$emit('remove-condition', condition)"
			>
				close
			</span>
		</div>

		<div class="tw-grid tw-grid-cols-6 tw-items-end tw-gap-2">
			<Parameter
				:parameter-object="field"
				v-for="field in displayedFields"
				:key="field.reload + field.param"
				class="tw-mr-4"
				:class="{
					'tw-col-span-2': field.param === 'target' || field.param === 'value',
					'tw-col-span-1': field.param === 'type' || field.param === 'operator',
				}"
				@valueUpdated="onParameterValueUpdated"
				:asyncAttributes="field.multiselectOptions ? field.multiselectOptions.asyncAttributes : []"
				:multiselectOptions="field.multiselectOptions ? field.multiselectOptions : null"
			/>
		</div>
	</div>
</template>

<style scoped></style>
