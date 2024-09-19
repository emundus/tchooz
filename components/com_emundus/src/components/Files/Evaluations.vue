<template>
  <div id="evaluations-container">

  </div>
</template>

<script>
export default {
  name: "Evaluations",
  props :{
    fnum: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      evaluations: []
    }
  },
  methods: {
    getEvaluationsForms() {
      // there can be multiple evaluations forms, based on fnums and evaluator access

    }
  },
}
</script>


<style scoped>

</style>