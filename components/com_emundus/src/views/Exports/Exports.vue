<script>
import { defineComponent } from 'vue';
import exportService from '@/services/export.js';
import alerts from '@/mixins/alerts.js';
import Button from '@/components/Atoms/Button.vue';
import Stepper from '@/components/Molecules/Stepper.vue';
import FormatSelector from '@/components/Atoms/FormatSelector.vue';
import Tabs from '@/components/Utils/Tabs.vue';

export default defineComponent({
	name: 'Exports',
	components: { Tabs, FormatSelector, Stepper, Button },
	mixins: [alerts],
	props: {},
	data() {
		return {
			loading: false,
			reloadForm: 0,

			formats: [],
			selectedFormat: 'xlsx',

			steps: [
				{ label: 'COM_EMUNDUS_EXPORTS_FORMAT', code: 'formats', active: true, completed: false },
				{ label: 'COM_EMUNDUS_EXPORT_CONTENT', code: 'content', active: false, completed: false },
				{ label: 'COM_EMUNDUS_EXPORT_OPTIONS', code: 'options', active: false, completed: false },
				{ label: 'COM_EMUNDUS_EXPORT_RESUME', code: 'resume', active: false, completed: false },
			],
			reloadWidth: 0,
			currentStep: null,

			tabs: [
				{
					id: 1,
					code: 'applicant',
					name: 'COM_EMUNDUS_EXPORTS_APPLICANTS_TAB',
					active: true,
					displayed: true,
				},
				{
					id: 2,
					code: 'management',
					name: 'COM_EMUNDUS_EXPORTS_MANAGEMENTS_TAB',
					active: false,
					displayed: true,
				},
				{
					id: 3,
					code: 'other',
					name: 'COM_EMUNDUS_EXPORTS_OTHER_TAB',
					active: false,
					displayed: true,
				},
			],

			elements: [],
		};
	},
	created() {
		this.loading = true;

		this.currentStep = this.steps.find((step) => step.active);

		this.getAvailableFormats().finally(() => {
			this.loading = false;
		});
	},
	methods: {
		getAvailableFormats() {
			return new Promise((resolve) => {
				exportService.getAvailableFormats().then((response) => {
					this.formats = response.data;
					resolve(this.formats);
				});
			});
		},

		previousStep() {
			const currentIndex = this.steps.findIndex((step) => step.active);
			if (currentIndex > 0) {
				this.steps[currentIndex].active = false;
				this.steps[currentIndex - 1].active = true;
				this.steps[currentIndex - 1].completed = false;

				this.currentStep = this.steps[currentIndex - 1];
			}

			this.reloadWidth++;
		},

		nextStep() {
			const currentIndex = this.steps.findIndex((step) => step.active);
			if (currentIndex !== -1 && currentIndex < this.steps.length - 1) {
				this.steps[currentIndex].active = false;
				this.steps[currentIndex].completed = true;
				this.steps[currentIndex + 1].active = true;

				this.currentStep = this.steps[currentIndex + 1];
			}

			this.reloadWidth++;
		},

		changeStep(index) {
			const currentIndex = this.steps.findIndex((step) => step.active);
			if (index < currentIndex) {
				this.steps[currentIndex].active = false;
				this.steps[index].active = true;
				this.steps[index].completed = false;

				this.currentStep = this.steps[index];
			}

			this.reloadWidth++;
		},

		getElements() {
			return new Promise((resolve) => {
				exportService.getElements(this.selectedMenuItem.code).then((response) => {
					this.elements = response.data;
					resolve(this.elements);
				});
			});
		},

		runExport(async = false) {
			this.loading = true;

			exportService.export(this.selectedFormat, async).then((response) => {
				this.loading = false;

				if (response.status) {
					this.alertConfirm(
						'COM_EMUNDUS_EXPORT_SUCCESS_TITLE',
						response.msg,
						false,
						'LINK_TO_DOWNLOAD',
						'COM_EMUNDUS_ACTIONS_CANCEL',
						null,
						false,
					).then((result) => {
						if (result.isConfirmed) {
							window.open(response.data.filename, '_blank');
						}
					});
				} else {
					if (response.msg.includes('The operation was aborted.')) {
						// Rerun export with async flag
						this.runExport(true);
					} else {
						this.alertError('COM_EMUNDUS_EXPORT_ERROR_TITLE', response.msg);
					}
				}
			});
		},
	},
	computed: {
		finalStep() {
			return this.steps[this.steps.length - 1];
		},
		selectedMenuItem() {
			return this.tabs.find((tab) => tab.active);
		},
	},
	watch: {
		currentStep: {
			handler(newStep) {
				console.log(newStep);
				if (newStep.code === 'content') {
					this.getElements();
				}
			},
			immediate: true,
		},
	},
});
</script>

<template>
	<div class="tw-min-w-[15vw]">
		<div class="tw-flex tw-flex-col tw-gap-6" :key="reloadForm" v-show="!loading">
			<Stepper :steps="steps" @step-clicked="changeStep" :reload-width="reloadWidth" />

			<FormatSelector v-if="steps[0].active" v-model="selectedFormat" :formats="formats" />

			<div v-if="steps[1].active" class="tw-grid tw-w-[100rem] tw-grid-cols-2 tw-gap-4">
				<div>
					<p>
						<strong>{{ this.translate('COM_EMUNDUS_EXPORT_AVAILABLE_CONTENT') }}</strong>
					</p>
					<div class="tw-mt-3">
						<Tabs
							:tabs="tabs"
							@change-tab-active="getElements"
							:classes="'tw-overflow-auto tw-flex tw-items-center tw-gap-2 tw-ml-4'"
						></Tabs>
						<div
							class="tw-relative tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
						>
							Test
						</div>
					</div>
				</div>
				<div>
					<p>
						<strong>{{ this.translate('COM_EMUNDUS_EXPORT_EXPORTING_CONTENT') }}</strong>
					</p>
				</div>
			</div>

			<div class="tw-flex" :class="{ 'tw-justify-end': steps[0].active, 'tw-justify-between': !steps[0].active }">
				<Button v-if="!steps[0].active" variant="secondary" @click="previousStep">
					{{ translate('COM_EMUNDUS_EXPORT_PREVIOUS_STEP') }}
				</Button>
				<Button v-if="finalStep.active" variant="primary" @click="runExport">
					{{ translate('COM_EMUNDUS_EXPORT_RUN') }}
				</Button>
				<Button v-else variant="primary" @click="nextStep">
					{{ translate('COM_EMUNDUS_EXPORT_NEXT_STEP') }}
				</Button>
			</div>
		</div>

		<div v-if="loading" class="tw-flex tw-justify-center">
			<div class="em-loader"></div>
		</div>
	</div>
</template>

<style scoped></style>
