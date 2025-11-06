<script>
import AutomationActionTarget from './AutomationActionTarget.vue';
import targetInstance from '@/components/Automation/targetInstance.js';

export default {
	name: 'AutomationActionTargets',
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
	components: { AutomationActionTarget },
	data() {
		return {};
	},
	created() {
		if (this.needTargets) {
			// Ensure at least one target exists
			if (this.action.targets.length === 0) {
				this.action.targets.push(targetInstance());
			}
		}
	},
	methods: {
		addTarget() {
			// todo: opens a modal selection of target type ?

			this.action.targets.push(targetInstance());
		},
		onRemoveTarget(targetToRemove) {
			this.action.targets = this.action.targets.filter((target) => {
				return target.id !== targetToRemove.id;
			});
		},
	},
	computed: {
		needTargets() {
			return this.action.supported_target_types && this.action.supported_target_types.length > 0;
		},
	},
};
</script>

<template>
	<div id="action-targets-wrapper">
		<div v-if="needTargets">
			<hr class="tw-mt-4" />
			<h4 class="tw-mt-4 tw-text-blue-500">{{ translate('COM_EMUNDUS_AUTOMATION_ACTION_TARGETS') }}</h4>
			<p class="tw-mt-2">{{ translate('COM_EMUNDUS_AUTOMATION_ACTION_TARGETS_HELPTEXT') }}</p>

			<AutomationActionTarget
				v-for="target in action.targets"
				:event="event"
				:action="action"
				:target-predefinitions="targetPredefinitions"
				:target="target"
				:key="target.id"
				@remove-target="onRemoveTarget"
			>
			</AutomationActionTarget>

			<div class="tw-mt-4 tw-flex tw-w-full tw-flex-row tw-justify-end">
				<button class="tw-btn-primary-blue tw-btn-primary tw-text-white" @click="addTarget">
					{{ translate('COM_EMUNDUS_AUTOMATION_ACTION_ADD_TARGET') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
