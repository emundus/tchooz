<template>
	<div :id="'evaluation-step-' + step.id + '-list'">
		<div v-if="evaluations.length > 0" class="tw-ml-4 tw-mt-4 tw-h-full">
			<Tabs
				:tabs="evaluationsTabs"
				:classes="'tw-overflow-x-scroll tw-flex tw-items-center tw-justify-start tw-gap-2'"
				@changeTabActive="onChangeTab"
			></Tabs>

			<iframe
				:src="selectedEvaluation.url"
				:key="selectedEvaluation.id"
				@load="iframeLoaded($event)"
				class="iframe-selected-evaluation tw-w-full tw-rounded-coordinator-cards tw-shadow-card"
			>
			</iframe>
		</div>
		<p
			v-else
			class="tw-m-2 tw-rounded tw-border tw-border-blue-500 tw-bg-blue-50 tw-p-2 tw-text-center tw-text-neutral-900"
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
		evaluations: [],
	},
	data: () => {
		return {
			selectedEvaluation: 0,
		};
	},
	created() {
		if (this.evaluations.length > 0) {
			this.selectedEvaluation = this.evaluations[0];
		}
	},
	components: {
		Tabs,
	},
	methods: {
		onChangeTab(tabId) {
			this.selectedEvaluation = this.evaluations.find((evaluation) => {
				return evaluation.id === tabId;
			});
		},

		iframeLoaded(event) {
			this.loading = false;
			let iframeDoc = event.target.contentDocument || event.target.contentWindow.document;
			if (iframeDoc.querySelector('.emundus-form')) {
				iframeDoc.querySelector('.emundus-form').classList.add('eval-form-split-view', 'tw-m-4');
				iframeDoc.querySelector('body').classList.add('tw-bg-white');
				iframeDoc
					.querySelector('body .platform-content > div')
					.classList.add('eval-form-split-view-container', 'tw-bg-white');
			}
		},
	},
	computed: {
		evaluationsTabs() {
			return this.evaluations.map((evaluation, index) => {
				return {
					id: evaluation.id,
					name: evaluation.evaluator_name,
					displayed: true,
					active: index === 0,
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
	width: 95%;
}
</style>
