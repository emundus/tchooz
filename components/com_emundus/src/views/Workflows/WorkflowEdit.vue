<template>
  <p> {{ workflowId }} </p>
</template>

<script>
export default {
  name: 'WorkflowEdit',
  props: {
    workflowId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      workflow: {
        id: 0,
        label: ''
      },
      steps: [],
      programs: []
    }
  },

}
</script>

<style scoped>

</style>