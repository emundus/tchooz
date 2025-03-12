<template>
	<div id="workflow-settings">
		<div id="step-types">
			<h2>{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPES') }}</h2>

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
