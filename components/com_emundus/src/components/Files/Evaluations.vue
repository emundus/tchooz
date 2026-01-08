<template>
	<div id="evaluations-container">
		<div v-if="evaluationsSteps.length > 0" class="tw-mt-2 tw-flex tw-h-full tw-flex-col">
			<nav class="tw-overflow-x-auto tw-pt-1">
				<ul class="tw-flex tw-list-none tw-flex-row">
					<li
						v-for="step in evaluationsSteps"
						:key="step.id"
						class="tw-cursor-pointer tw-whitespace-nowrap tw-rounded-t-lg tw-px-2.5 tw-py-3 tw-shadow"
						:class="{
							'em-bg-main-500 em-text-neutral-300': selectedTab === step.id,
						}"
						@click="updateTab(step)"
					>
						{{ step.label }}
					</li>
				</ul>
			</nav>

			<div v-if="ccid > 0 && selectedEvaluationStep && selectedEvaluationStep.form_id" class="tw-h-full">
				<div v-if="selectedEvaluationStep.evaluations.length > 1">
					<!-- Make a tab for each evaluation -->
					<EvaluationList
						:step="selectedEvaluationStep"
						:ccid="ccid"
						:evaluations="selectedEvaluationStep.evaluations"
					/>
				</div>
				<iframe
					v-else-if="selectedEvaluationStep.evaluations.length > 0"
					v-show="!loading"
					:src="'/' + currentLang + selectedEvaluationStep.evaluations[0].url"
					class="iframe-evaluation-list tw-w-full tw-grow tw-bg-coordinator-bg tw-p-6"
					:key="selectedTab"
					@load="iframeLoaded($event)"
				>
				</iframe>

				<p
					v-else
					class="tw-m-2 tw-rounded tw-border tw-border-blue-500 tw-bg-blue-50 tw-p-2 tw-text-center tw-text-neutral-900"
				>
					{{ translate('COM_EMUNDUS_EVALUATIONS_LIST_NO_EVALUATIONS') }}
				</p>
			</div>
			<div>
				<div v-if="loading" class="em-page-loader" />
			</div>
		</div>
		<p
			v-else
			class="tw-m-2 tw-rounded tw-border tw-border-blue-500 tw-bg-blue-50 tw-p-2 tw-text-center tw-text-neutral-900"
		>
			{{ translate('COM_EMUNDUS_EVALUATIONS_LIST_NO_EDITABLE_EVALUATIONS') }}
		</p>
	</div>
</template>

<script>
import evaluationService from '@/services/evaluation.js';
import fileService from '@/services/file.js';
import { useGlobalStore } from '@/stores/global.js';
import EvaluationList from '@/components/Files/EvaluationList.vue';

export default {
	name: 'Evaluations',
	components: { EvaluationList },
	props: {
		fnum: {
			type: String,
			required: true,
		},
		defaultCcid: {
			type: Number,
			default: 0,
		},
		onlyEditionAccess: {
			type: Boolean,
			default: true,
		},
	},
	data() {
		return {
			evaluationsSteps: [],
			selectedTab: 0,
			ccid: 0,

			loading: false,
			currentLang: useGlobalStore().getShortLang,
		};
	},
	mounted() {
		this.getFileId();
		this.getEvaluationsForms();
	},
	methods: {
		getFileId() {
			if (this.defaultCcid > 0) {
				this.ccid = this.defaultCcid;
			} else {
				fileService.getFileIdFromFnum(this.fnum).then((response) => {
					if (response.status) {
						this.ccid = response.data;
					}
				});
			}
		},
		getEvaluationsForms() {
			this.loading = true;
			// there can be multiple evaluations forms, based on fnums and evaluator access
			evaluationService
				.getEvaluationsForms(this.fnum)
				.then((response) => {
					this.evaluationsSteps = response.data;

					// restore last selected tab from session storage
					let menu = window.location.pathname.replace(/^\//, '').replace(/\//g, '_');
					let lastTab = sessionStorage.getItem('com_emundus_last_tab_evaluation_' + menu);

					if (this.evaluationsSteps.length > 0) {
						// if last tab exists in current evaluations, select it
						if (lastTab) {
							let lastTabObj = JSON.parse(lastTab);
							let exists = this.evaluationsSteps.find((evaluation) => evaluation.id == lastTabObj.id);
							if (exists) {
								this.updateTab(exists);
							} else {
								this.updateTab(this.evaluationsSteps[0]);
							}
						} else {
							this.updateTab(this.evaluationsSteps[0]);
						}
						this.loading = false;
					} else {
						this.loading = false;
					}
				})
				.catch((error) => {
					this.loading = false;
					console.log(error);
				});
		},
		iframeLoaded(event) {
			this.loading = false;
			let iframeDoc = event.target.contentDocument || event.target.contentWindow.document;
			if (iframeDoc.querySelector('.emundus-form')) {
				iframeDoc.querySelector('.emundus-form').classList.add('eval-form-split-view');
				iframeDoc.querySelector('body .platform-content > div').classList.add('eval-form-split-view-container');
			}
		},
		updateTab(evaluation) {
			this.selectedTab = evaluation.id;

			let menu = window.location.pathname.replace(/^\//, '').replace(/\//g, '_');
			sessionStorage.setItem('com_emundus_last_tab_evaluation_' + menu, JSON.stringify(evaluation));
		},
	},
	computed: {
		selectedEvaluationStep() {
			return this.evaluationsSteps.length > 0
				? this.evaluationsSteps.find((evaluation) => evaluation.id === this.selectedTab)
				: {};
		},
	},
};
</script>

<style scoped>
.iframe-evaluation-list {
	width: 100%;
	min-height: 80%;
	border: unset;
	height: 100%;
}
</style>
