<script>
import fromAutomationFieldToParameter from '@/mixins/transformIntoParameterField.js';
import Parameter from '@/components/Utils/Parameter.vue';
import AutomationActionTargets from '@/components/Automation/AutomationActionTargets.vue';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';

export default {
	name: 'AutomationAction',
	props: {
		event: {
			type: Object,
			required: true,
		},
		action: {
			type: Object,
			required: true,
		},
		targetPredefinitions: {
			type: Array,
			required: true,
		},
	},
	mixins: [fromAutomationFieldToParameter],
	components: { AutomationActionTargets, Parameter, ParameterForm },
	data() {
		return {
			formGroups: [],
		};
	},
	created() {
		this.mountForm();
	},
	methods: {
		async mountForm() {
			this.formGroups = await this.fieldsToParameterFormGroups(this.action.parameters, this.action.parameter_values);
		},
		removeAction(action) {
			this.$emit('remove-action', action);
		},
		onParameterValueUpdated(parameter, group = null, rowIndex = null) {
			if (group.isRepeatable) {
				if (!this.action.parameter_values[group.id]) {
					this.action.parameter_values[group.id] = [];
				}
				if (!this.action.parameter_values[group.id][rowIndex]) {
					this.action.parameter_values[group.id][rowIndex] = {};
				}

				switch (parameter.type) {
					case 'multiselect':
						if (parameter.multiple === false) {
							this.action.parameter_values[group.id][rowIndex][parameter.param] = parameter.value
								? parameter.value.value
								: null;
						} else {
							this.action.parameter_values[group.id][rowIndex][parameter.param] = parameter.value.map(
								(item) => item.value,
							);
						}
						break;
					default:
						this.action.parameter_values[group.id][rowIndex][parameter.param] = parameter.value;
						break;
				}
			} else {
				switch (parameter.type) {
					case 'multiselect':
						if (parameter.multiple === false) {
							this.action.parameter_values[parameter.param] = parameter.value ? parameter.value.value : null;
						} else {
							this.action.parameter_values[parameter.param] = parameter.value.map((item) => item.value);
						}
						break;
					default:
						this.action.parameter_values[parameter.param] = parameter.value;
						break;
				}
			}
		},
	},
};
</script>

<template>
	<div
		:id="'action-' + action.id"
		class="tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<div class="tw-mb-4 tw-flex tw-flex-row tw-items-start tw-justify-between">
			<div class="tw-mb-4 tw-flex tw-flex-row tw-items-center tw-justify-start tw-gap-2">
				<div class="tw-h-[32px] tw-w-[32px] tw-rounded-coordinator tw-bg-blue-100 tw-pl-1">
					<span class="material-symbols-outlined tw-h-[20px] tw-w-[20px] !tw-text-2xl tw-font-bold tw-text-blue-600">{{
						action.icon
					}}</span>
				</div>
				<h3 class="tw-text-lg tw-font-medium">{{ translate(action.label) }}</h3>
			</div>
			<span class="material-symbols-outlined tw-cursor-pointer tw-text-red-500" @click="removeAction(action)">
				close
			</span>
		</div>
		<p class="tw-mb-2">{{ translate(action.description) }}</p>

		<ParameterForm
			:id="'action-form-' + action.id"
			:title="null"
			:description="null"
			:groups="formGroups"
			@parameterValueUpdated="onParameterValueUpdated"
		>
		</ParameterForm>

		<AutomationActionTargets :event="event" :action="action" :target-predefinitions="targetPredefinitions" />
	</div>
</template>

<style scoped></style>
