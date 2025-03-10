<template>
	<div id="evaluations-container">
		<div v-if="evaluations.length > 0" class="tw-h-full">
			<nav class="tw-mt-1">
				<ul class="tw-list-none tw-flex tw-flex-row">
					<li
						v-for="evaluation in evaluations"
						:key="evaluation.id"
						class="tw-cursor-pointer tw-shadow tw-rounded-t-lg tw-px-2.5 tw-py-3"
						:class="{ 'em-bg-main-500 em-text-neutral-300': selectedTab === evaluation.id }"
						@click="selectedTab = evaluation.id"
					>
						{{ evaluation.label }}
					</li>
				</ul>
			</nav>
			<iframe
				v-if="ccid > 0 && selectedEvaluation && selectedEvaluation.form_id"
				v-show="!loading"
				:src="selectedEvaluation.url"
				class="tw-w-full iframe-evaluation-list"
				:key="selectedTab"
				@load="iframeLoaded($event)"
			>
			</iframe>
			<div>
				<div v-if="loading" class="em-page-loader" />
			</div>
		</div>
		<p
			v-else
			class="tw-text-center tw-p-2 tw-m-2 tw-bg-blue-50 tw-border tw-border-blue-500 tw-rounded tw-text-neutral-900"
		>
			{{ translate('COM_EMUNDUS_EVALUATIONS_LIST_NO_EDITABLE_EVALUATIONS') }}
		</p>
	</div>
</template>

<script>
import evaluationService from '@/services/evaluation.js';
import fileService from '@/services/file.js';

export default {
	name: 'Evaluations',
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
			evaluations: [],
			selectedTab: 0,
			ccid: 0,

			loading: false,
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
					if (this.onlyEditionAccess) {
						this.evaluations = response.data.filter((evaluation) => evaluation.user_access.can_edit);
					} else {
						this.evaluations = response.data;
					}

					if (this.evaluations.length > 0) {
						this.selectedTab = this.evaluations[0].id;
					} else {
						this.loading = false;
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
		iframeLoaded(event) {
			this.loading = false;
		},
	},
	computed: {
		selectedEvaluation() {
			return this.evaluations.length > 0
				? this.evaluations.find((evaluation) => evaluation.id === this.selectedTab)
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
}
</style>
