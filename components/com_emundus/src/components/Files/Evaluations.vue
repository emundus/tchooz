<template>
  <div id="evaluations-container">
    <nav class="tw-mt-1">
      <ul class="tw-list-none tw-flex tw-flex-row">
        <li v-for="evaluation in evaluations" :key="evaluation.id"
            class="tw-cursor-pointer tw-shadow tw-rounded-t-lg tw-px-2.5 tw-py-3"
            :class="{'em-bg-main-500 em-text-neutral-300': selectedTab === evaluation.id}"
            @click="selectedTab = evaluation.id"
        >
          {{ evaluation.label }}
        </li>
      </ul>
    </nav>
    <iframe
        v-if="ccid > 0"
        :src="'/evaluator-step-form?formid=' + selectedEvaluation.form_id + '&' + selectedEvaluation.table + '___ccid=' + this.ccid + '&' + selectedEvaluation.table + '___step_id=' + selectedEvaluation.id + '&tmpl=component&iframe=1'"
        class="tw-w-full tw-h-screen"
        :key="selectedTab"
      >
    </iframe>
  </div>
</template>

<script>
import evaluationService from '@/services/evaluation.js';
import fileService from "@/services/file.js";

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
      evaluations: [],
      selectedTab: 0,
      ccid: 0
    }
  },
  mounted() {
    this.getFileId();
    this.getEvaluationsForms();
  },
  methods: {
    getFileId() {
      fileService.getFileIdFromFnum(this.fnum).then((response) =>  {
        if (response.status) {
          this.ccid = response.data;
        }
      });
    },
    getEvaluationsForms() {
      // there can be multiple evaluations forms, based on fnums and evaluator access
      evaluationService.getEvaluationsForms(this.fnum).then(response => {
        this.evaluations = response.data;
        this.selectedTab = this.evaluations[0].id;
      }).catch(error => {
        console.log(error);
      });
    }
  },
  computed: {
    selectedEvaluation() {
      return this.evaluations.find(evaluation => evaluation.id === this.selectedTab);
    }
  }
}
</script>


<style scoped>

</style>