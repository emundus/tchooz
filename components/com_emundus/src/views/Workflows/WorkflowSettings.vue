<template>
  <div id="workflow-settings">
    <div id="step-types">
      <h2> {{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPES') }}</h2>

      <StepTypesByLevel v-if="stepTypes.length > 0" :defaultTypes="stepTypes" :parentId="0"></StepTypesByLevel>
    </div>
  </div>
</template>

<script>
import workflowService from '@/services/workflow.js';
import StepTypesByLevel from '@/components/Workflow/StepTypesByLevel.vue';

export default {
  name: 'WorkflowSettings',
  components: {
    StepTypesByLevel
  },
  data() {
    return {
      stepTypes: [],
    }
  },
  created() {
    this.getStepTypes();
  },
  methods: {
    getStepTypes() {
      workflowService.getStepTypes()
        .then(response => {
          this.stepTypes = response.data
        })
        .catch(error => {
          console.log(error)
        });
    }
  },
}
</script>

<style scoped>

</style>