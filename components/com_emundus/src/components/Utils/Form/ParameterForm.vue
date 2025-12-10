<script>
import Parameter from '@/components/Utils/Parameter.vue';

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
	},
	components: {
		Parameter,
	},
	data() {
		return {
			initialized: false,
		};
	},
	mounted() {
		this.reloadParametersRules();
		this.initialized = true;
	},
	methods: {
		onParameterValueUpdated(parameter, group, rowIndex = null) {
			if (parameter) {
				this.$emit('parameterValueUpdated', parameter, group, rowIndex);
			}

			if (this.initialized) {
				this.reloadParametersRules();
			}
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
		reloadParametersRules() {
			this.groups.forEach((group) => {
				if (group.isRepeatable) {
					group.rows.forEach((row, rowIndex) => {
						row.parameters.forEach((parameter) => {
							if (parameter.displayRules.length > 0) {
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
									parameter.reload += 1;
								} else {
									parameter.displayed = false;
									parameter.hidden = true;
									parameter.hideLabel = true;
									parameter.reload += 1;

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
						if (parameter.displayRules.length > 0) {
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
								parameter.displayed = true;
							} else {
								parameter.displayed = false;
								parameter.value = null;
							}
						}
					});
				}
			});
		},
		addRow(group) {
			const newRow = {
				parameters: group.parameters.map((param) => ({
					...param,
					value: param.defaultValue || null,
				})),
			};
			group.rows.push(newRow);

			this.reloadParametersRules();
		},
		removeRow(group, rowIndex) {
			group.rows.splice(rowIndex, 1);
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
};
</script>
<!-- TODO: handle repeatable groups -->
<!-- TODO: handle display rules between fields -->

<template>
	<div :id="'form-' + id" class="form-container">
		<h2 v-if="title">{{ title }}</h2>
		<p v-if="description">{{ description }}</p>
		<div v-for="group in groups" :key="group.id" class="form-group">
			<div v-if="group.isRepeatable">
				<h3>{{ group.title }}</h3>
				<p v-if="group.description">{{ group.description }}</p>

				<div class="tw-mt-4 tw-flex tw-w-full tw-flex-row tw-justify-end">
					<button class="tw-btn-primary" @click="addRow(group)">
						{{ translate('COM_EMUNDUS_ADD_ROW') }}
					</button>
				</div>

				<div
					v-for="(row, rowIndex) in group.rows"
					:key="rowIndex"
					class="repeatable-row tw-mb-4 tw-mt-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
				>
					<div class="tw-flex tw-w-full tw-flex-row tw-items-center tw-justify-between">
						<h3>{{ group.title }} - {{ rowIndex + 1 }}</h3>
						<span
							class="material-symbols-outlined tw-cursor-pointer tw-text-red-500"
							@click="removeRow(group, rowIndex)"
							>close</span
						>
					</div>
					<div class="tw-flex tw-w-full tw-flex-col tw-gap-4">
						<Parameter
							v-for="(field, index) in group.parameters"
							:key="field.param + '-' + rowIndex + '-' + field.reload"
							:multiselect-options="field.type === 'multiselect' ? field.multiselectOptions : null"
							:parameter-object="row.parameters[index]"
							@valueUpdated="onParameterValueUpdated(row.parameters[index], group, rowIndex)"
						/>
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
						:key="field.param + '-' + field.reload"
						:multiselect-options="field.type === 'multiselect' ? field.multiselectOptions : null"
						:parameter-object="field"
						@valueUpdated="onParameterValueUpdated(field, group)"
					/>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
