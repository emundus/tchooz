<script>
import Parameter from '@/components/Utils/Parameter.vue';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';

export default {
	name: 'ParameterForm',
	props: {
		id: {
			type: Number,
			default: null,
		},
		title: {
			type: String,
			default: '',
		},
		description: {
			type: String,
			default: '',
		},
		groups: {
			type: Array,
			required: true,
		},
		fields: {
			type: Array,
			default: () => [],
		},
	},
	components: {
		Parameter,
	},
	mixins: [transformIntoParameterField],
	emits: ['parameterValueUpdated'],
	data() {
		return {
			initialized: false,
		};
	},
	mounted() {
		this.initialized = true;
	},
	methods: {
		onParameterValueUpdated(parameter, group, rowIndex = null, oldValue = null, newValue = null) {
			// Skip entirely when both sides were explicitly passed and are equivalent
			// (covers the [] vs null hydration loop without blocking real clears like "foo" → null).
			if (oldValue !== null && newValue !== null && this.areValuesEquivalent(oldValue, newValue)) {
				return;
			}
			if (parameter) {
				this.$emit('parameterValueUpdated', parameter, group, rowIndex);
			}

			// Reload the display rules whenever the value actually changed — including the first
			// change from null to a value, so conditional (hidden) fields appear immediately.
			// Internal calls (reloadParametersRules) pass no old/new value (both null) and are
			// filtered by areValuesEquivalent below, which preserves the recursion guard.
			// Empty-equivalent transitions ([] ↔ null) are also filtered to stop the hydration loop.
			if (this.initialized && !this.areValuesEquivalent(oldValue, newValue)) {
				this.reloadParametersRules(parameter);

				this.fields.forEach((field) => {
					if (field.watchers && field.watchers.length > 0) {
						field.watchers.forEach(async (watcher) => {
							if (watcher.field === parameter.param && watcher.events.includes('onChange')) {
								let values = {};
								let fieldParameter = this.findParameterByName(field.name);

								if (rowIndex === null) {
									group.parameters.forEach(function (param) {
										// key is the parameter name, value is the parameter value
										values[param.param] = param.value;
									});
								} else {
									// if the parameter is in a repeatable group, we need to get the values of the parameters in the same row
									group.rows[rowIndex].parameters.forEach(function (param) {
										// key is the parameter name, value is the parameter value
										values[param.param] = param.value;
									});
								}

								if (fieldParameter.type === 'select') {
									fieldParameter.options = await this.provideParameterOptions(field, values);

									// force reload of the multiselect, if NaN,
									if (isNaN(fieldParameter.reload)) {
										fieldParameter.reload = 1;
									} else {
										fieldParameter.reload += 1;
									}
								} else if (fieldParameter.type === 'multiselect' && field.optionsProvider) {
									fieldParameter.multiselectOptions.options = await this.provideParameterOptions(field, values);
									fieldParameter.multiselectOptions.optionsProvider = field.optionsProvider;
									fieldParameter.multiselectOptions.optionsProvider.dependenciesValues =
										this.getParamDependenciesValues(field, values);

									// force reload of the multiselect, if NaN,
									if (isNaN(fieldParameter.reload)) {
										fieldParameter.reload = 1;
									} else {
										fieldParameter.reload += 1;
									}
								}
							}
						});
					}
				});
			}
		},
		isEmptyValue(value) {
			return value === null || value === undefined || value === '' || (Array.isArray(value) && value.length === 0);
		},
		areValuesEquivalent(a, b) {
			if (this.isEmptyValue(a) && this.isEmptyValue(b)) {
				return true;
			}
			if (Array.isArray(a) && Array.isArray(b)) {
				return JSON.stringify(a) === JSON.stringify(b);
			}
			return a === b;
		},
		/**
		 * Validate every displayed Parameter via its ref and serialize the values.
		 *
		 * - Skips fields where `displayed === false`.
		 * - Calls `validate()` on each Parameter ref (`field_<param>`); aborts on the first failure
		 *   and scrolls the invalid field into view.
		 * - For `multiselect` fields, flattens via `multiselectOptions.trackBy`.
		 *
		 * @returns {{ isValid: boolean, form: Object }} `form` is empty when `isValid` is false.
		 */
		validate() {
			const form = {};

			for (const group of this.groups) {
				for (const field of group.parameters) {
					if (!field.displayed) continue;

					const fieldRef = this.$refs['field_' + field.param];
					if (fieldRef && fieldRef[0] && !fieldRef[0].validate()) {
						this.scrollToField(fieldRef[0]);
						return { isValid: false, form: {} };
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							form[field.param] = (field.value || []).map((element) => element[field.multiselectOptions.trackBy]);
						} else {
							form[field.param] = field.value ? field.value[field.multiselectOptions.trackBy] : null;
						}
					} else {
						form[field.param] = field.value;
					}
				}
			}

			return { isValid: true, form };
		},
		/**
		 * Smooth-scroll an invalid Parameter component into the viewport.
		 *
		 * Wait for nextTick so the Parameter has rendered its error state before measuring.
		 *
		 * @param {{ $el?: HTMLElement }} fieldComponent
		 */
		scrollToField(fieldComponent) {
			this.$nextTick(() => {
				const el = fieldComponent && fieldComponent.$el;
				if (el && typeof el.scrollIntoView === 'function') {
					el.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
			});
		},
		findParameterByName(name) {
			for (const group of this.groups) {
				for (const parameter of group.parameters) {
					if (parameter.param === name) {
						return parameter;
					}
				}
			}
			return null;
		},
		getParameterGroup(parameterName) {
			for (const group of this.groups) {
				for (const parameter of group.parameters) {
					if (parameter.param === parameterName) {
						return group;
					}
				}
			}
			return null;
		},
		reloadParametersRules(currentParameter = null) {
			this.groups.forEach((group) => {
				if (group.isRepeatable) {
					group.rows.forEach((row, rowIndex) => {
						row.parameters.forEach((parameter) => {
							if (parameter.displayRules && parameter.displayRules.length > 0) {
								let everyRulesSucceed = parameter.displayRules.every((rule) => {
									const fieldValue = this.getRowParameterValue(group, rowIndex, rule.field);

									if (fieldValue != null && fieldValue == rule.value) {
										return true;
									}

									return false;
								});

								if (everyRulesSucceed) {
									parameter.displayed = true;
									parameter.hidden = false;
									parameter.hideLabel = false;

									if (isNaN(parameter.reload)) {
										parameter.reload = 1;
									} else {
										parameter.reload += 1;
									}
								} else {
									parameter.displayed = false;
									parameter.hidden = true;
									parameter.hideLabel = true;
									if (isNaN(parameter.reload)) {
										parameter.reload = 1;
									} else {
										parameter.reload += 1;
									}

									if (parameter.value != null) {
										parameter.value = null;
										this.onParameterValueUpdated(parameter, group, rowIndex);
									}
								}
							}
						});
					});
				} else {
					group.parameters.forEach((parameter) => {
						if (
							(currentParameter === null || (currentParameter && currentParameter.param !== parameter.param)) &&
							parameter.displayRules &&
							parameter.displayRules.length > 0
						) {
							let everyRulesSucceed = parameter.displayRules.every((rule) => {
								const ruleParameter = this.findParameterByName(rule.field);

								if (ruleParameter) {
									if (ruleParameter.value == rule.value) {
										return true;
									}
								}

								return false;
							});

							if (everyRulesSucceed) {
								if (parameter.displayed !== true) {
									parameter.displayed = true;
									parameter.reload = parameter.reload ? parameter.reload + 1 : 1;
								}
							} else {
								if (parameter.displayed !== false || parameter.value !== null) {
									parameter.displayed = false;
									parameter.value = null;
									parameter.reload = parameter.reload ? parameter.reload + 1 : 1;
								}
							}
						}
					});
				}
			});
		},
		addRow(group) {
			const newRow = {
				id: Date.now() + Math.random(),
				parameters: group.parameters.map((param) => ({
					...param,
					value: param.defaultValue || null,
				})),
			};
			group.rows.push(newRow);

			this.reloadParametersRules();
		},
		removeRow(group, rowToRemove) {
			const rowIndex = group.rows.findIndex((row) => row.id === rowToRemove.id);
			if (rowIndex === -1) {
				return;
			}

			group.rows.splice(rowIndex, 1);

			// The parent stores parameter_values[group.id] as a positional array indexed by row.
			// A removed row must be spliced out there too, otherwise a stale/empty entry lingers
			// and gets persisted (then breaks backend actions iterating over the rows).
			this.$emit('rowRemoved', group, rowIndex);

			this.reloadParametersRules();
		},
		getRowParameterValue(group, rowIndex, parameterName) {
			const row = group.rows[rowIndex];
			if (row) {
				const parameter = row.parameters.find((param) => param.param === parameterName);
				return parameter ? parameter.value : null;
			}
			return null;
		},
	},
	watch: {
		groups: {
			handler() {
				this.reloadParametersRules();
			},
			immediate: true,
		},
	},
};
</script>
<!-- TODO: handle repeatable groups -->
<!-- TODO: handle display rules between fields -->

<template>
	<div :id="'form-' + id" class="form-container">
		<h2 v-if="title">{{ title }}</h2>
		<p v-if="description">{{ description }}</p>
		<div v-for="group in groups" :key="group.id" class="form-group tw-mt-4">
			<div v-if="group.isRepeatable">
				<h3>{{ group.title }}</h3>
				<p v-if="group.description">{{ group.description }}</p>

				<div class="tw-mt-4 tw-flex tw-w-full tw-flex-row tw-justify-end">
					<button class="tw-btn-primary" @click="addRow(group)">
						{{ translate('COM_EMUNDUS_ADD_ROW') }}
					</button>
				</div>

				<div v-if="group.display === 'table'" class="group-table tw-mt-4">
					<table class="tw-w-full tw-border-collapse">
						<thead>
							<tr>
								<th
									v-for="(field, index) in group.parameters"
									:key="field.param + '-header-' + index"
									class="tw-border tw-border-neutral-300 tw-bg-neutral-100 tw-px-4 tw-py-2 tw-text-left"
								>
									{{ field.label }}
								</th>
								<th class="tw-border tw-border-neutral-300 tw-bg-neutral-100 tw-px-4 tw-py-2"></th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="(row, rowIndex) in group.rows" :key="row.id" class="tw-border-b tw-border-neutral-300">
								<td
									v-for="(field, index) in group.parameters"
									:key="field.param + '-' + rowIndex + '-' + field.reload"
									class="tw-border tw-border-neutral-300 tw-px-4 tw-py-2"
								>
									<Parameter
										:multiselect-options="field.type === 'multiselect' ? field.multiselectOptions : null"
										:parameter-object="row.parameters[index]"
										:key="row.parameters[index].param + '-' + rowIndex + '-' + index"
										:asyncAttributes="field.type === 'multiselect' ? field.multiselectOptions.asyncAttributes : null"
										@valueUpdated="
											(parameter, oldVal, newVal) =>
												onParameterValueUpdated(row.parameters[index], group, rowIndex, oldVal, newVal)
										"
									/>
								</td>
								<td class="tw-border tw-border-neutral-300 tw-px-4 tw-py-2 tw-text-center">
									<span
										class="material-symbols-outlined not-to-close-modal tw-cursor-pointer tw-text-red-500"
										@click="removeRow(group, row)"
										>close</span
									>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
				<div v-else>
					<div
						v-for="(row, rowIndex) in group.rows"
						:key="row.id"
						class="repeatable-row tw-mb-4 tw-mt-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
					>
						<div class="tw-flex tw-w-full tw-flex-row tw-items-center tw-justify-between">
							<h3>{{ group.title }} - {{ rowIndex + 1 }}</h3>
							<span
								class="material-symbols-outlined not-to-close-modal tw-cursor-pointer tw-text-red-500"
								@click="removeRow(group, row)"
								>close</span
							>
						</div>
						<div class="tw-flex tw-w-full tw-flex-col tw-gap-4">
							<Parameter
								v-for="(field, index) in group.parameters"
								:key="field.param + '-' + rowIndex + '-' + field.reload"
								:multiselect-options="field.type === 'multiselect' ? field.multiselectOptions : null"
								:parameter-object="row.parameters[index]"
								:asyncAttributes="field.type === 'multiselect' ? field.multiselectOptions.asyncAttributes : null"
								@valueUpdated="
									(parameter, oldVal, newVal) =>
										onParameterValueUpdated(row.parameters[index], group, rowIndex, oldVal, newVal)
								"
							/>
						</div>
					</div>
				</div>
				<div class="tw-mt-4 tw-flex tw-w-full tw-flex-row tw-justify-end">
					<button v-if="group.rows.length > 0" class="tw-btn-primary" @click="addRow(group)">
						{{ translate('COM_EMUNDUS_ADD_ROW') }}
					</button>
				</div>
			</div>
			<div v-else>
				<h3>{{ group.title }}</h3>
				<p v-if="group.description">{{ group.description }}</p>
				<div class="tw-flex tw-w-full tw-flex-col tw-gap-4">
					<Parameter
						v-for="(field, index) in group.parameters"
						v-show="field.displayed !== false"
						:help-text-type="group.helpTextType ? group.helpTextType : 'icon'"
						:ref="'field_' + field.param"
						:key="field.param + '-' + field.reload"
						:multiselect-options="field.type === 'multiselect' ? field.multiselectOptions : null"
						:parameter-object="field"
						:asyncAttributes="field.type === 'multiselect' ? field.multiselectOptions.asyncAttributes : null"
						@valueUpdated="(parameter, oldVal, newVal) => onParameterValueUpdated(field, group, null, oldVal, newVal)"
					/>
				</div>
			</div>
		</div>
	</div>
</template>

<style>
.group-table label {
	display: none !important;
}

td .parameter-label {
	display: none !important;
}
</style>
