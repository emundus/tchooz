<template>
	<div :id="'evaluation-step-' + step.id + '-list'">
		<h2 class="tw-mb-4">{{ translate('COM_EMUNDUS_EVALUATIONS_LIST') }}</h2>
		<div v-if="evaluations.length > 0" class="tw-p-4 tw-h-full">
			<Tabs
				:tabs="evaluationsTabs"
				:classes="'tw-overflow-x-scroll tw-flex tw-items-center tw-justify-start tw-gap-2'"
				@changeTabActive="onChangeTab"
			></Tabs>

			<iframe :src="selectedEvaluation.url" :key="selectedEvaluation.id" class="tw-w-full iframe-selected-evaluation">
			</iframe>
		</div>
		<p
			v-else
			class="tw-text-center tw-p-2 tw-m-2 tw-bg-blue-50 tw-border tw-border-blue-500 tw-rounded tw-text-neutral-900"
		>
			{{ translate('COM_EMUNDUS_EVALUATIONS_LIST_NO_EVALUATIONS') }}
		</p>
	</div>
</template>

<script>
import evaluationService from '@/services/evaluation.js';
import Tabs from '@/components/Utils/Tabs.vue';

export default {
	name: 'EvaluationList',
	props: {
		ccid: {
			type: Number,
			required: true,
		},
		step: {
			type: Object,
			required: true,
		},
	},
	data: () => {
		return {
			evaluations: [],
			selectedEvaluation: 0,
		};
	},
	components: {
		Tabs,
	},
	created() {
		this.getEvaluations();
	},
	methods: {
		getEvaluations() {
			evaluationService
				.getEvaluations(this.step.id, this.ccid)
				.then((response) => {
					this.evaluations = response.data;

					if (this.evaluations.length > 0) {
						this.selectedEvaluation = this.evaluations[0];
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
		onChangeTab(tabId) {
			this.selectedEvaluation = this.evaluations.find((evaluation) => {
				return evaluation.id == tabId;
			});
		},
	},
	computed: {
		evaluationsTabs() {
			return this.evaluations.map((evaluation, index) => {
				return {
					id: evaluation.id,
					name: evaluation.evaluator_name,
					displayed: true,
					active: index == 0,
					icon: null,
				};
			});
		},
	},
};
</script>

<style scoped>
.iframe-selected-evaluation {
	height: calc(100vh - 258px);
}
</style>
