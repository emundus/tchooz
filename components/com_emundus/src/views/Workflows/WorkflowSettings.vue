<template>
	<div
		id="workflow-settings"
		class="tw-mb-6 tw-w-full tw-rounded-coordinator-cards tw-border tw-border-gray-200 tw-bg-neutral-0 tw-p-5 tw-shadow"
	>
		<div id="step-types">
			<h2 class="tw-pb-5">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPES') }}</h2>

			<StepTypesByLevel
				@updateTypes="onUpdateTypes"
				v-if="stepTypes.length > 0"
				:defaultTypes="stepTypes"
				:parentId="0"
			></StepTypesByLevel>
		</div>
	</div>
</template>

<script>
import workflowService from '@/services/workflow.js';
import StepTypesByLevel from '@/components/Workflow/StepTypesByLevel.vue';

export default {
	name: 'WorkflowSettings',
	components: {
		StepTypesByLevel,
	},
	data() {
		return {
			stepTypes: [],
		};
	},
	created() {
		this.getStepTypes();
	},
	methods: {
		getStepTypes() {
			workflowService
				.getStepTypes()
				.then((response) => {
					this.stepTypes = response.data.map((type) => {
						type.label = this.translate(type.label);
						return type;
					});

					// Filter to not include payment and choices step types
					this.stepTypes = this.stepTypes.filter((type) => !['choices'].includes(type.code));
				})
				.catch((error) => {
					console.log(error);
				});
		},
		onUpdateTypes(types) {
			this.stepTypes = types;
		},
	},
};
</script>

<style scoped></style>
