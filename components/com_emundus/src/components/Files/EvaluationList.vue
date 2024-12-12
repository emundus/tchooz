<template>
  <div :id="'evaluation-step-' + step.id + '-list'" class="tw-p-4">
    <h2>List of evaluations</h2>
    <Tabs v-if="evaluations.length > 0"
          :tabs="evaluationsTabs"
          :classes="'tw-overflow-x-scroll tw-flex tw-items-center tw-justify-start tw-gap-2'"
          @changeTabActive="onChangeTab"
    ></Tabs>

    <iframe :src="selectedEvaluation.url" :key="selectedEvaluation.id"
      width="100%" height="100%"
    >
    </iframe>
  </div>
</template>

<script>
import evaluationService from '@/services/evaluation.js';
import Tabs from "@/components/Utils/Tabs.vue";
import evaluation from "@/services/evaluation.js";

export default {
  name: "EvaluationList",
  props: {
    ccid: {
      type: Number,
      required: true
    },
    step: {
      type: Object,
      required: true
    }
  },
  data: () => {
    return {
      evaluations: [],
      selectedEvaluation: 0
    }
  },
  components: {
    Tabs
  },
  created() {
    this.getEvaluations();
  },
  methods: {
    getEvaluations() {
      evaluationService.getEvaluations(this.step.id, this.ccid).then(response => {
        this.evaluations = response.data;
        this.selectedEvaluation = this.evaluations[0];
      }).catch(error => {
        console.log(error);
      });
    },
    onChangeTab(tabId) {
      this.selectedEvaluation = this.evaluations.find((evaluation) => {
        return evaluation.id == tabId;
      });
    }
  },
  computed: {
    evaluationsTabs() {
      return this.evaluations.map((evaluation, index) => {
        return {
          id: evaluation.id,
          name: evaluation.evaluator_name,
          displayed: true,
          active: index == 0,
          icon: null
        }
      })
    }
  }
}
</script>


<style scoped>

</style>