<script>
import AutomationCondition from '@/components/Automation/AutomationCondition.vue';
import { newConditionGroup } from '@/components/Automation/conditionGroup.js';

export default {
	name: 'AutomationConditionGroup',
	components: { AutomationCondition },
	props: {
		conditionGroup: {
			type: Object,
			required: true,
		},
		conditionsList: {
			type: Array,
			required: true,
		},
		operators: {
			type: Array,
			required: true,
		},
		operatorsFieldMapping: {
			type: Object,
			required: true,
		},
	},
	methods: {
		addCondition() {
			const newCondition = {
				id: Date.now(),
				group_id: this.conditionGroup.id,
				target: null,
				type: null,
				operator: null,
				value: null,
			};
			this.conditionGroup.conditions.push(newCondition);
		},
		addConditionGroup(conditionGroupId = null) {
			this.conditionGroup.subGroups.push(newConditionGroup(conditionGroupId));
		},
		onRemoveCondition(condition) {
			this.conditionGroup.conditions = this.conditionGroup.conditions.filter((c) => c.id !== condition.id);
			this.$emit('remove-condition', this.conditionGroup.id, condition);
		},
		removeConditionGroup(group) {
			this.conditionGroup.subGroups = this.conditionGroup.subGroups.filter((g) => g.id !== group.id);
			this.$emit('remove-condition-group', group);
		},
		onOperatorChange() {
			this.$emit('operator-change', this.conditionGroup.id, this.conditionGroup.operator);
		},
		onRemoveSubGroupCondition(groupId, condition) {
			this.$emit('remove-condition', groupId, condition);
		},
		onChangeSubGroupOperator(groupId, operator) {
			this.$emit('operator-change', groupId, operator);
		},
	},
	computed: {
		canAddConditionGroup() {
			// there can be only 2 sub levels of condition groups
			// it means that it can only be added if parent_id is 0 or parent_id of parent_id is 0
			if (
				this.conditionGroup.parent_id === null ||
				this.conditionGroup.parent_id === undefined ||
				this.conditionGroup.parent_id === 0
			) {
				return true;
			} else if (this.$parent.$props.conditionGroup) {
				return (
					this.$parent.$props.conditionGroup.parent_id === null ||
					this.$parent.$props.conditionGroup.parent_id === undefined ||
					this.$parent.$props.conditionGroup.parent_id === 0
				);
			}

			return false;
		},
		multipleConditions() {
			return (
				this.conditionGroup.conditions.length > 1 ||
				this.conditionGroup.subGroups.length > 1 ||
				(this.conditionGroup.conditions.length > 0 && this.conditionGroup.subGroups.length > 0)
			);
		},
	},
};
</script>

<template>
	<div class="tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card">
		<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
			<h3 class="tw-m-0">{{ translate('COM_EMUNDUS_AUTOMATION_CONDITIONS_GROUP') }}</h3>
			<span
				v-if="
					conditionGroup.parent_id !== null && conditionGroup.parent_id !== undefined && conditionGroup.parent_id > 0
				"
				class="material-symbols-outlined tw-cursor-pointer tw-text-red-500"
				@click="removeConditionGroup(conditionGroup)"
			>
				close
			</span>
		</div>

		<div class="tw-flex tw-flex-col tw-items-start tw-gap-4">
			<div
				class="condition-operators tw-flex tw-flex-row tw-rounded-full tw-bg-orange-500 tw-p-1"
				v-show="multipleConditions"
			>
				<div class="tw-rounded-full tw-px-2" :class="{ 'tw-bg-white': conditionGroup.operator === 'AND' }">
					<input
						type="radio"
						class="and-operator"
						:id="'and-operator-' + conditionGroup.id"
						:name="'condition-group-operator-' + conditionGroup.id"
						value="AND"
						v-model="conditionGroup.operator"
						@change="onOperatorChange"
					/>
					<label
						:for="'and-operator-' + conditionGroup.id"
						class="tw-cursor-pointer"
						:class="{
							'tw-text-orange-500': conditionGroup.operator === 'AND',
							'tw-text-white': conditionGroup.operator !== 'AND',
						}"
					>
						{{ translate('COM_EMUNDUS_CONDITIONS_GROUP_OPERATOR_AND_RELATION') }}
					</label>
				</div>

				<div class="tw-rounded-full tw-px-2" :class="{ 'tw-bg-white': conditionGroup.operator === 'OR' }">
					<input
						type="radio"
						class="or-operator"
						:id="'or-operator-' + conditionGroup.id"
						:name="'condition-group-operator-' + conditionGroup.id"
						value="OR"
						v-model="conditionGroup.operator"
						@change="onOperatorChange"
					/>
					<label
						:for="'or-operator-' + conditionGroup.id"
						class="tw-cursor-pointer"
						:class="{
							'tw-text-orange-500': conditionGroup.operator === 'OR',
							'tw-text-white': conditionGroup.operator !== 'OR',
						}"
					>
						{{ translate('COM_EMUNDUS_CONDITIONS_GROUP_OPERATOR_OR_RELATION') }}
					</label>
				</div>
			</div>
			<div class="tw-flex tw-w-full tw-flex-col tw-gap-4">
				<div v-for="(condition, index) in conditionGroup.conditions" :key="condition.id">
					<div v-if="index > 0 && multipleConditions" class="tw-mb-2 tw-text-sm tw-font-medium tw-text-gray-500">
						{{ translate('COM_EMUNDUS_CONDITIONS_GROUP_OPERATOR_' + conditionGroup.operator) }}
					</div>
					<AutomationCondition
						:key="condition.id"
						:condition="condition"
						:conditions-list="conditionsList"
						:operators="operators"
						:operatorsFieldMapping="operatorsFieldMapping"
						@remove-condition="onRemoveCondition"
					/>
				</div>

				<div v-for="(group, index) in conditionGroup.subGroups" :key="group.id" class="tw-w-full">
					<div
						v-if="(index > 0 || conditionGroup.conditions.length > 0) && multipleConditions"
						class="tw-mb-2 tw-text-sm tw-font-medium tw-text-gray-500"
					>
						{{ translate('COM_EMUNDUS_CONDITIONS_GROUP_OPERATOR_' + conditionGroup.operator) }}
					</div>
					<AutomationConditionGroup
						:condition-group="group"
						:conditions-list="conditionsList"
						:operators="operators"
						:operators-field-mapping="operatorsFieldMapping"
						:sub-groups="group.subGroups || []"
						@remove-condition-group="removeConditionGroup"
						@add-condition-group="addConditionGroup"
						@remove-condition="onRemoveSubGroupCondition"
						@operator-change="onChangeSubGroupOperator"
					/>
				</div>

				<p v-if="conditionGroup.conditions.length < 1">
					{{ translate('COM_EMUNDUS_AUTOMATION_NO_CONDITIONS_IN_GROUP') }}
				</p>
			</div>

			<div class="tw-flex tw-w-full tw-flex-row tw-justify-end tw-gap-2">
				<button
					v-if="canAddConditionGroup"
					id="add-condition-group"
					@click="addConditionGroup(conditionGroup.id)"
					class="tw-btn-secondary-orange tw-btn-secondary tw-mt-4"
				>
					{{ translate('COM_EMUNDUS_AUTOMATION_ADD_CONDITION_GROUP') }}
				</button>
				<button class="tw-btn-primary-orange tw-btn-primary tw-mt-4" @click="addCondition">
					{{ translate('COM_EMUNDUS_AUTOMATION_ADD_CONDITION') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped>
.and-operator,
.or-operator {
	visibility: hidden;
	width: 0;
	margin: 0 !important;
	padding: 0 !important;
}

.condition-operators {
	transition: all 0.3s ease;

	& > div {
		transition: all 0.3s ease;
	}
}
</style>
