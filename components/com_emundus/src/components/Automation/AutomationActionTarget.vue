<script>
import AutomationCondition from '@/components/Automation/AutomationCondition.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import conditionInstance from '@/components/Automation/conditionInstance.js';
import automationService from '@/services/automation.js';
import { useAutomationStore } from '@/stores/automation.js';

export default {
	name: 'AutomationActionTarget',
	components: { Parameter, AutomationCondition },
	props: {
		event: {
			type: Object,
			required: true,
		},
		action: {
			type: Object,
			required: true,
		},
		target: {
			type: Object,
			required: true,
		},
		targetPredefinitions: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			fields: [
				{
					type: 'select',
					multiple: false,
					options: [],
					param: 'type',
					label: 'COM_EMUNDUS_AUTOMATION_ACTION_TARGET_TYPE',
					optional: false,
					value: this.action.supported_target_types[0],
					displayed: true,
					reload: 0,
				},
				{
					type: 'select',
					multiple: false,
					options: [],
					param: 'predefinition',
					label: 'COM_EMUNDUS_AUTOMATION_ACTION_TARGET_PREDEFINITION',
					helpText: 'COM_EMUNDUS_AUTOMATION_ACTION_TARGET_PREDEFINITION_HELPER',
					optional: true,
					value: null,
					displayed: true,
					reload: 0,
				},
			],
			conditionsList: [],
			operatorsFieldMapping: [],
		};
	},
	created() {
		// set options for predefinition select
		this.fields[0].options = this.action.supported_target_types.map((type) => {
			return {
				value: type,
				label: this.translate('COM_EMUNDUS_AUTOMATION_ACTION_TARGET_TYPE_' + type.toUpperCase()),
			};
		});
		this.fields[0].value = this.target.type;
		this.fields[1].options = this.targetPredefinitionsOptions;
		this.fields[1].value = this.target.predefinition ? this.target.predefinition.name : null;
		this.fields[1].displayed = this.targetPredefinitionsOptions.length > 0;

		this.getTargetConditionsList();
	},
	methods: {
		getTargetConditionsList() {
			automationService
				.getTargetConditionsList(this.target.type, useAutomationStore().doesEventContainsFile(this.event.name))
				.then((response) => {
					if (response.status) {
						this.conditionsList = response.data;
					}
				});
		},
		onParameterValueUpdated(parameter) {
			if (parameter.param === 'predefinition') {
				this.target.predefinition = this.targetPredefinitions.find((predefinition) => {
					return predefinition.name === parameter.value;
				});
			} else {
				this.target[parameter.param] = parameter.value;

				if (parameter.param === 'type') {
					if (parameter.value === 'custom') {
						this.fields[1].displayed = false;
						this.target.predefinition = null;
					} else {
						this.fields[1].displayed = this.targetPredefinitionsOptions.length > 0;
						this.fields[1].options = this.targetPredefinitionsOptions;
					}
					this.fields[1].reload++;

					this.getTargetConditionsList();
				}
			}
		},
		addCondition() {
			this.target.conditions.push(conditionInstance(Date.now()));
		},
		onRemoveCondition(conditionToRemove) {
			this.target.conditions = this.target.conditions.filter((condition) => {
				return condition.id !== conditionToRemove.id;
			});
		},
	},
	computed: {
		targetPredefinitionsOptions() {
			let options = this.targetPredefinitions
				.filter((predefinition) => {
					return (
						this.target.type === predefinition.category &&
						useAutomationStore().doesEventContainsFromCategories(this.event.name, predefinition.fromCategories)
					);
				})
				.map((predef) => ({
					label: predef.label,
					value: predef.name,
				}));

			if (options.length > 0) {
				options.unshift({
					label: this.translate('COM_EMUNDUS_AUTOMATION_ACTION_TARGET_PREDEFINITION_OPTION_NONE'),
					value: null,
				});
			}

			return options;
		},
		displayedFields() {
			return this.fields.filter((field) => field.displayed);
		},
	},
};
</script>

<template>
	<div class="tw-mb-4 tw-mt-4 tw-rounded-coordinator-cards tw-bg-blue-100 tw-p-6">
		<div class="tw-flex tw-flex-row tw-items-start tw-justify-between">
			<h4 class="tw-mb-4">{{ translate('COM_EMUNDUS_AUTOMATION_ACTION_TARGET') }}</h4>
			<span class="material-symbols-outlined tw-cursor-pointer tw-text-red-500" @click="$emit('remove-target', target)"
				>close</span
			>
		</div>
		<div class="tw-flex tw-flex-col tw-gap-4">
			<Parameter
				v-for="field in displayedFields"
				:key="field.reload + field.param"
				:class="{ 'tw-w-full': field.param === 'name' }"
				:ref="field.param"
				:parameter-object="field"
				:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
				:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
				@valueUpdated="onParameterValueUpdated"
			/>

			<div
				v-if="conditionsList && conditionsList.length > 0 && target.type !== 'custom'"
				class="tw-mt-4 tw-flex tw-flex-col tw-gap-4"
			>
				<AutomationCondition
					v-for="condition in target.conditions"
					:key="condition.id"
					:conditions-list="conditionsList"
					:condition="condition"
					:title="'COM_EMUNDUS_AUTOMATION_ACTION_TARGET_SELECTION_RULE'"
					@remove-condition="onRemoveCondition"
				/>
				<div class="tw-flex tw-w-full tw-flex-row tw-justify-end">
					<button class="tw-btn-secondary-blue tw-btn-secondary !tw-text-blue-500" @click="addCondition">
						{{ translate('COM_EMUNDUS_AUTOMATION_ACTION_TARGET_FILTER_SELECTION') }}
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
