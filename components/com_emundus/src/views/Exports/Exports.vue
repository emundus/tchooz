<script>
import { defineComponent } from 'vue';
import exportService from '@/services/export.js';
import alerts from '@/mixins/alerts.js';
import Button from '@/components/Atoms/Button.vue';
import Stepper from '@/components/Molecules/Stepper.vue';
import FormatSelector from '@/components/Atoms/FormatSelector.vue';
import Tabs from '@/components/Utils/Tabs.vue';
import SplitButton from '@/components/Atoms/SplitButton.vue';
import Info from '@/components/Utils/Info.vue';
import { VueDraggableNext as draggable } from 'vue-draggable-next';

export default defineComponent({
	name: 'Exports',
	components: { draggable, Info, SplitButton, Tabs, FormatSelector, Stepper, Button },
	mixins: [alerts],
	props: {
		fnumsCount: {
			type: Number,
			default: 0,
		},
		exportLink: {
			type: String,
			default: '',
		},
	},
	data() {
		return {
			loading: false,
			subLoading: false,
			formatLoading: false,
			elementsLoading: false,
			exportLoading: false,
			reloadForm: 0,
			message: '',

			queryController: null,

			exportTemplates: [],
			displayExportSavedContent: false,
			exportName: '',
			exportId: 0,
			newTemplate: false,

			formats: [],
			selectedFormat: 'xlsx',

			steps: [
				{ label: 'COM_EMUNDUS_EXPORTS_FORMAT', code: 'formats', active: true, completed: false },
				{ label: 'COM_EMUNDUS_EXPORT_CONTENT', code: 'content', active: false, completed: false },
				//{ label: 'COM_EMUNDUS_EXPORT_OPTIONS', code: 'options', active: false, completed: false },
				//{ label: 'COM_EMUNDUS_EXPORT_RESUME', code: 'resume', active: false, completed: false },
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
				{
					id: 4,
					code: 'attachments',
					name: 'COM_EMUNDUS_EXPORTS_ATTACHMENT_TAB',
					active: false,
					displayed: true,
				},
			],
			tabsPdf: [
				{
					id: 1,
					code: 'content',
					name: 'COM_EMUNDUS_EXPORTS_CONTENT_TAB',
					active: true,
					displayed: true,
					suffix: '(0)',
				},
				{
					id: 2,
					code: 'header',
					name: 'COM_EMUNDUS_EXPORTS_HEADER_TAB',
					active: false,
					displayed: true,
					suffix: '(0)',
				},
				{
					id: 3,
					code: 'synthesis',
					name: 'COM_EMUNDUS_EXPORTS_SYNTHESIS_TAB',
					active: false,
					displayed: true,
					suffix: '(0)',
				},
				{
					id: 4,
					code: 'attachments',
					name: 'COM_EMUNDUS_EXPORTS_ATTACHMENT_TAB',
					active: false,
					displayed: true,
					suffix: '(0)',
				},
			],
			tabsXlsx: [
				{
					id: 1,
					code: 'content',
					name: 'COM_EMUNDUS_EXPORTS_CONTENT_TAB',
					active: true,
					displayed: true,
					suffix: '(0)',
				},
				{
					id: 2,
					code: 'synthesis',
					name: 'COM_EMUNDUS_EXPORTS_SYNTHESIS_TAB',
					active: false,
					displayed: true,
					suffix: '(0)',
				},
			],

			elements: [],
			openedSections: [],

			selectedElements: [],
			selectedHeaders: [],
			selectedSynthesis: [],
			selectedAttachments: [],
		};
	},
	created() {
		this.loading = true;
		this.formatLoading = true;

		this.currentStep = this.steps.find((step) => step.active);

		this.getAvailableFormats().finally(() => {
			this.getExportTemplates().finally(() => {
				this.loading = false;
				this.formatLoading = false;
			});
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

		getExportTemplates() {
			return new Promise((resolve) => {
				exportService.getExportTemplates().then((response) => {
					this.exportTemplates = response.data;
					resolve(this.exportTemplates);
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
			this.subLoading = true;
			this.elementsLoading = true;

			if (this.queryController) {
				this.queryController.abort();
			}
			this.queryController = new AbortController();

			if (this.selectedMenuItem.code === 'attachments') {
				this.tabsPdf.find((tab) => tab.code === 'attachments').active = true;
			}

			return new Promise((resolve) => {
				exportService
					.getElements(this.selectedMenuItem.code, this.selectedFormat, this.queryController)
					.then((response) => {
						this.elements = response.data;
						this.subLoading = false;
						this.elementsLoading = false;
						this.queryController = null;

						resolve(this.elements);
					});
			});
		},

		getDefaultSynthesis() {
			exportService.getDefaultSynthesisElements(this.selectedFormat).then((response) => {
				if (response.status) {
					this.selectedSynthesis = [];

					if (response.data && response.data.length > 0) {
						response.data.forEach((element) => {
							this.addSynthesis(element);
						});
					}
				}
			});
		},

		getDefaultHeader() {
			exportService.getDefaultHeaderElements().then((response) => {
				if (response.status) {
					this.selectedHeaders = [];

					if (response.data && response.data.length > 0) {
						response.data.forEach((element) => {
							this.addHeader(element);
						});
					}
				}
			});
		},

		runExport(async = false) {
			this.loading = true;
			this.exportLoading = true;

			// Prepare selected elements ids
			const selectedElementIds = this.selectedElements.map((el) => el.id);
			if (selectedElementIds.length === 0) {
				this.alertError('COM_EMUNDUS_EXPORT_ERROR_TITLE', this.translate('COM_EMUNDUS_EXPORT_NO_CONTENT_SELECTED'));
				this.loading = false;
				this.exportLoading = false;
				return;
			}

			const selectedHeaderIds = this.selectedHeaders.map((el) => el.id);
			const selectedSynthesisIds = this.selectedSynthesis.map((el) => el.id);
			const selectedAttachmentIds = this.selectedAttachments.map((el) => el.id);

			exportService
				.export(
					this.selectedFormat,
					selectedElementIds,
					selectedHeaderIds,
					selectedSynthesisIds,
					selectedAttachmentIds,
					async,
				)
				.then((response) => {
					this.loading = false;
					this.exportLoading = false;

					let confirmMessage = 'LINK_TO_DOWNLOAD';
					let closeMessage = 'COM_EMUNDUS_CLOSE';
					if (async) {
						confirmMessage = 'COM_EMUNDUS_GO_TO_EXPORTS_PAGE';
						closeMessage = 'COM_EMUNDUS_STAY_ON_PAGE';
					}

					if (response.status) {
						this.alertConfirm(
							'COM_EMUNDUS_EXPORT_SUCCESS_TITLE',
							response.msg,
							false,
							confirmMessage,
							closeMessage,
							null,
							false,
						).then((result) => {
							if (result.isConfirmed) {
								if (async) {
									window.open(this.exportLink, '_self');
								} else {
									window.open(response.data.filename, '_blank');
								}
							}
						});
					} else {
						const allowAsync = Joomla.getOptions('plg_system_emundus.async_export', 0);

						if (response.title === 'AbortError' && allowAsync == 1) {
							// Rerun export with async flag
							this.runExport(true);
						} else {
							this.alertError('COM_EMUNDUS_EXPORT_ERROR_TITLE', response.msg);
						}
					}
				});
		},
		saveExport() {
			this.loading = true;

			if (this.exportName === '') {
				this.alertError('COM_EMUNDUS_EXPORT_ERROR_TITLE', 'Veuillez saisir un nom pour votre export enregistré.');
				this.loading = false;
				return;
			}

			const selectedElementIds = this.selectedElements.map((el) => el.id);
			if (selectedElementIds.length === 0) {
				this.alertError('COM_EMUNDUS_EXPORT_ERROR_TITLE', this.translate('COM_EMUNDUS_EXPORT_NO_CONTENT_SELECTED'));
				this.loading = false;
				return;
			}

			const selectedHeaderIds = this.selectedHeaders.map((el) => el.id);
			const selectedSynthesisIds = this.selectedSynthesis.map((el) => el.id);
			const selectedAttachmentIds = this.selectedAttachments.map((el) => el.id);

			if (this.newTemplate) {
				this.exportId = 0;
			}

			exportService
				.saveExport(
					this.exportName,
					this.selectedFormat,
					selectedElementIds,
					selectedHeaderIds,
					selectedSynthesisIds,
					selectedAttachmentIds,
					this.exportId,
				)
				.then((response) => {
					this.loading = false;

					if (response.status) {
						this.displayExportSavedContent = false;
						this.exportId = response.data;

						this.message = response.msg;
						setTimeout(() => {
							this.message = '';
						}, 3000);
					}
				});
		},
		deleteExport() {
			exportService.deleteExport(this.exportId).then((response) => {
				this.loading = false;

				if (response.status) {
					// Remove from export templates list
					this.exportTemplates = this.exportTemplates.filter((template) => template.id != this.exportId);

					this.displayExportSavedContent = false;
					this.exportId = 0;
					this.exportName = '';

					this.message = response.msg;
					setTimeout(() => {
						this.message = '';
					}, 3000);
				}
			});
		},
		updateOpenedSections(sectionId, type) {
			const idToUpdate = type + '-' + sectionId;
			if (this.openedSections.includes(idToUpdate)) {
				this.openedSections = this.openedSections.filter((id) => id !== idToUpdate);
			} else {
				this.openedSections.push(idToUpdate);
			}
		},
		checkOpenedSections(sectionId, type) {
			const idToCheck = type + '-' + sectionId;
			return this.openedSections.includes(idToCheck);
		},

		addSelectedStep(step) {
			Object.values(step.forms).forEach((form) => {
				this.addSelectedForm(form);
			});
		},

		addSelectedForm(form) {
			if (form.groups) {
				Object.values(form.groups).forEach((group) => {
					this.addSelectedGroup(group);
				});
			}
		},

		addSelectedGroup(group) {
			if (group.elements) {
				Object.values(group.elements).forEach((element) => {
					this.addSelectedElement(element);
				});
			}
		},

		addSelectedElement(element) {
			let section = '';
			if (
				(this.selectedFormat === 'pdf' && this.tabsPdf[0].active) ||
				(this.selectedFormat === 'xlsx' && this.tabsXlsx[0].active)
			) {
				section = 'content';
			} else if (this.selectedFormat === 'pdf' && this.tabsPdf[1].active) {
				section = 'header';
			} else if (this.tabsPdf[2].active || (this.selectedFormat === 'xlsx' && this.tabsXlsx[1].active)) {
				section = 'synthesis';
			} else if (this.selectedFormat === 'pdf' && this.tabsPdf[3].active) {
				section = 'attachments';
			}

			switch (section) {
				case 'content':
					this.addElement(element);
					break;
				case 'header':
					this.addHeader(element);
					break;
				case 'synthesis':
					this.addSynthesis(element);
					break;
				case 'attachments':
					this.addAttachment(element);
					break;
			}
		},

		addElement(element) {
			if (!this.selectedElements.some((el) => el.id === element.id)) {
				this.selectedElements.push(element);

				this.updatePrefixTab('content', this.selectedElements.length);
			}
		},

		addHeader(element) {
			if (!this.selectedHeaders.some((el) => el.id === element.id) && this.selectedHeaders.length < 5) {
				this.selectedHeaders.push(element);

				this.updatePrefixTab('header', this.selectedHeaders.length);
			}
		},

		addSynthesis(element) {
			if (!this.selectedSynthesis.some((el) => el.id === element.id) && this.selectedSynthesis.length < 10) {
				this.selectedSynthesis.push(element);

				this.updatePrefixTab('synthesis', this.selectedSynthesis.length);
			}
		},

		addAttachment(element) {
			if (!this.selectedAttachments.some((el) => el.id === element.id)) {
				this.selectedAttachments.push(element);

				this.updatePrefixTab('attachments', this.selectedAttachments.length);
			}
		},

		removeSelectedElement(element) {
			if (
				(this.selectedFormat === 'xlsx' && this.tabsXlsx[0].active) ||
				(this.selectedFormat === 'pdf' && this.tabsPdf[0].active)
			) {
				this.selectedElements = this.selectedElements.filter((el) => el.id !== element.id);

				this.updatePrefixTab('content', this.selectedElements.length);
			} else if (this.selectedFormat === 'pdf' && this.tabsPdf[1].active) {
				this.selectedHeaders = this.selectedHeaders.filter((el) => el.id !== element.id);

				this.updatePrefixTab('header', this.selectedHeaders.length);
			} else if (
				(this.selectedFormat === 'xlsx' && this.tabsXlsx[1].active) ||
				(this.selectedFormat === 'pdf' && this.tabsPdf[2].active)
			) {
				this.selectedSynthesis = this.selectedSynthesis.filter((el) => el.id !== element.id);

				this.updatePrefixTab('synthesis', this.selectedSynthesis.length);
			} else if (this.selectedFormat === 'pdf' && this.tabsPdf[3].active) {
				this.selectedAttachments = this.selectedAttachments.filter((el) => el.id !== element.id);

				this.updatePrefixTab('attachments', this.selectedAttachments.length);
			}
		},
		disabled(element) {
			if (
				(this.selectedFormat === 'xlsx' && this.tabsXlsx[0].active) ||
				(this.selectedFormat === 'pdf' && this.tabsPdf[0].active)
			) {
				// Search if element is already selected via id
				return this.selectedElements.some((el) => el.id === element.id);
			} else if (this.selectedFormat === 'pdf' && this.tabsPdf[1].active) {
				return this.selectedHeaders.some((el) => el.id === element.id);
			} else if (
				(this.selectedFormat === 'pdf' && this.tabsPdf[2].active) ||
				(this.selectedFormat === 'xlsx' && this.tabsXlsx[1].active)
			) {
				return this.selectedSynthesis.some((el) => el.id === element.id);
			} else if (this.selectedFormat === 'pdf' && this.tabsPdf[3].active) {
				return this.selectedAttachments.some((el) => el.id === element.id);
			}
		},

		updateTemplate(value) {
			this.exportId = value;
		},

		updatePrefixTab(type, length) {
			if (this.selectedFormat === 'pdf') {
				this.tabsPdf.find((tab) => tab.code === type).suffix = `(${length})`;
			} else if (this.selectedFormat === 'xlsx') {
				this.tabsXlsx.find((tab) => tab.code === type).suffix = `(${length})`;
			}
		},
	},
	computed: {
		finalStep() {
			return this.steps[this.steps.length - 1];
		},
		selectedMenuItem() {
			return this.tabs.find((tab) => tab.active);
		},
		allDisabled() {
			if (this.selectedFormat === 'pdf') {
				if (this.tabsPdf[1].active) {
					return this.selectedHeaders.length >= 5; // Max 2 headers
				} else if (this.tabsPdf[2].active) {
					return this.selectedSynthesis.length >= 10; // Max 10 synthesis elements
				}
			} else if (this.selectedFormat === 'xlsx') {
				if (this.tabsXlsx[1].active) {
					return this.selectedSynthesis.length >= 10; // Max 10 synthesis elements
				}
			}
			return false;
		},
	},
	watch: {
		currentStep: {
			handler(newStep) {
				if (newStep === null) return;

				if (newStep.code === 'formats') {
					this.exportId = 0;
					this.exportName = '';
				}

				if (newStep.code === 'content') {
					this.tabs.find((tab) => tab.code === 'other').disabled = !(
						this.selectedFormat === 'xlsx' ||
						(this.selectedFormat === 'pdf' && this.tabsPdf[1].active) ||
						(this.selectedFormat === 'pdf' && this.tabsPdf[2].active)
					);

					this.tabs.find((tab) => tab.code === 'attachments').displayed = this.selectedFormat === 'pdf';

					if (this.exportId > 0) {
						this.selectedElements = [];
						this.selectedHeaders = [];
						this.selectedSynthesis = [];
						this.selectedAttachments = [];

						// Get elements from saved export
						exportService.getElementsFromSaveExport(this.exportId).then((response) => {
							if (response.status) {
								this.selectedFormat = response.data.format;
								this.exportName = response.data.name;

								response.data.elements.forEach((element) => {
									this.addElement(element);
								});

								response.data.headers.forEach((element) => {
									this.addHeader(element);
								});

								response.data.synthesis.forEach((element) => {
									this.addSynthesis(element);
								});

								response.data.attachments.forEach((element) => {
									this.addAttachment(element);
								});
							}

							this.getElements();
						});
					} else {
						this.getDefaultSynthesis();
						if (this.selectedFormat === 'pdf') {
							this.getDefaultHeader();
						}

						this.getElements();
					}
				}
			},
			immediate: true,
		},
		displayExportSavedContent: {
			handler(newValue) {
				if (newValue) {
					// Focus on the input
					this.$nextTick(() => {
						const input = this.$el.querySelector('input[type="text"]');
						if (input) {
							input.focus();
						}
					});
				}
			},
		},
		tabsPdf: {
			handler(newValue) {
				if (newValue[0].active && this.tabs[2].active) {
					this.tabs.find((tab) => tab.code === 'other').active = false;
					this.tabs.find((tab) => tab.code === 'applicant').active = true;

					this.getElements();
				}

				this.tabs.find((tab) => tab.code === 'applicant').disabled = newValue[3].active;
				this.tabs.find((tab) => tab.code === 'other').disabled = newValue[3].active;
				this.tabs.find((tab) => tab.code === 'other').disabled = newValue[3].active;
				this.tabs.find((tab) => tab.code === 'management').disabled = newValue[3].active;
				this.tabs.find((tab) => tab.code === 'management').disabled = newValue[3].active;

				if (newValue[3].active && !this.tabs.find((tab) => tab.code === 'attachments').active) {
					this.tabs.find((tab) => tab.code === 'attachments').active = true;

					this.tabs.find((tab) => tab.code === 'applicant').active = false;
					this.tabs.find((tab) => tab.code === 'other').active = false;
					this.tabs.find((tab) => tab.code === 'management').active = false;

					this.getElements();
				} else if (!newValue[3].active && this.tabs.find((tab) => tab.code === 'attachments').active) {
					this.tabs.find((tab) => tab.code === 'attachments').active = false;
					this.tabs.find((tab) => tab.code === 'applicant').active = true;

					this.getElements();
				}

				this.tabs.find((tab) => tab.code === 'other').disabled = !(newValue[1].active || newValue[2].active);
				this.tabs.find((tab) => tab.code === 'attachments').disabled = !newValue[3].active;
			},
			deep: true,
		},
	},
});
</script>

<template>
	<div class="tw-min-w-[20vw]">
		<div class="tw-flex tw-flex-col tw-gap-6" :key="reloadForm" v-show="!loading">
			<Info v-if="message !== ''" :text="message" class="tw-mb-4" />

			<div v-if="!displayExportSavedContent">
				<Stepper :steps="steps" @step-clicked="changeStep" :reload-width="reloadWidth" class="tw-mb-4" />

				<FormatSelector
					v-if="steps[0].active"
					v-model="selectedFormat"
					:formats="formats"
					:exportTemplates="exportTemplates"
					@select-template="updateTemplate"
				/>

				<div v-if="steps[1].active" class="tw-grid tw-w-[90vw] tw-grid-cols-2">
					<div class="tw-z-10">
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
								class="tw-relative tw-flex tw-max-h-[70vh] tw-min-h-[70vh] tw-flex-col tw-gap-2 tw-overflow-scroll tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
							>
								<div v-if="subLoading" class="tw-flex tw-flex-col tw-items-center tw-justify-center">
									<div class="em-loader"></div>

									<span class="tw-mt-1" v-if="elementsLoading">{{
										this.translate('COM_EMUNDUS_EXPORT_ELEMENTS_LOADER')
									}}</span>
								</div>

								<!-- List of elements that can be exported, need to move to a separate component after -->
								<div
									v-if="!subLoading"
									v-for="step in elements"
									:key="step.profile_id"
									:id="'step-' + step.profile_id"
									class="tw-relative tw-rounded-coordinator-cards tw-border tw-border-main-50 tw-bg-white tw-p-0"
								>
									<div
										class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-bg-main-50 tw-p-2 tw-px-4 tw-py-2 tw-text-main-500"
										:class="{
											'tw-rounded-t-coordinator-cards': checkOpenedSections(step.profile_id, 'step'),
											'tw-rounded-coordinator-cards': !checkOpenedSections(step.profile_id, 'step'),
										}"
									>
										<div
											class="tw-flex tw-w-full tw-flex-col tw-gap-1"
											@click="updateOpenedSections(step.profile_id, 'step')"
										>
											<div class="tw-flex tw-w-full tw-items-center tw-gap-2">
												<span>{{ step.label }}</span>
												<span
													class="material-symbols-outlined tw-text-main-500"
													v-if="!checkOpenedSections(step.profile_id, 'step')"
													>chevron_right</span
												>
												<span class="material-symbols-outlined tw-text-main-500" v-else>keyboard_arrow_down</span>
											</div>
											<span
												class="tw-w-fit tw-rounded-coordinator tw-bg-neutral-400 tw-px-2 tw-py-1 tw-text-xs tw-text-neutral-900"
												v-if="step.campaign_label && step.campaign_label !== ''"
												>{{ step.campaign_label }}</span
											>
										</div>
										<div
											class="tw-flex tw-rounded tw-bg-main-500 tw-p-1"
											:class="{ 'tw-cursor-not-allowed tw-bg-neutral-300': allDisabled }"
											@click="addSelectedStep(step)"
										>
											<span class="material-symbols-outlined tw-text-white">arrow_forward</span>
										</div>
									</div>

									<div class="tw-flex tw-flex-col tw-gap-2 tw-p-3" v-if="checkOpenedSections(step.profile_id, 'step')">
										<div
											v-for="form in step.forms"
											:key="form.id"
											class="tw-relative tw-rounded-coordinator-cards tw-border tw-border-main-50 tw-bg-white tw-p-0"
										>
											<div
												class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-bg-main-50 tw-px-4 tw-py-2 tw-text-main-500"
												:class="{
													'tw-rounded-t-coordinator-cards': checkOpenedSections(form.id, 'form'),
													'tw-rounded-coordinator-cards': !checkOpenedSections(form.id, 'form'),
												}"
											>
												<div
													class="tw-flex tw-w-full tw-items-center tw-gap-2"
													@click="updateOpenedSections(form.id, 'form')"
												>
													<span>{{ form.label }}</span>
													<span
														class="material-symbols-outlined tw-text-main-500"
														v-if="!checkOpenedSections(form.id, 'form')"
														>chevron_right</span
													>
													<span class="material-symbols-outlined tw-text-main-500" v-else>keyboard_arrow_down</span>
												</div>
												<div
													class="tw-flex tw-rounded tw-bg-main-500 tw-p-1"
													:class="{ 'tw-cursor-not-allowed tw-bg-neutral-300': allDisabled }"
													@click="addSelectedForm(form)"
												>
													<span class="material-symbols-outlined tw-text-white">arrow_forward</span>
												</div>
											</div>

											<div class="tw-flex tw-flex-col tw-gap-2 tw-p-3" v-if="checkOpenedSections(form.id, 'form')">
												<div
													v-for="group in form.groups"
													:key="group.id"
													class="tw-relative tw-rounded-coordinator-cards tw-border tw-border-main-50 tw-bg-white tw-p-0"
												>
													<div
														class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-rounded-t-coordinator-cards tw-bg-main-50 tw-px-4 tw-py-2 tw-text-main-500"
														v-if="group.label !== ''"
													>
														<span class="tw-w-full">{{ group.label }}</span>
														<div
															class="tw-flex tw-rounded tw-bg-main-500 tw-p-1"
															:class="{ 'tw-cursor-not-allowed tw-bg-neutral-300': allDisabled }"
															@click="addSelectedGroup(group)"
														>
															<span class="material-symbols-outlined tw-text-white">arrow_forward</span>
														</div>
													</div>

													<div class="tw-flex tw-flex-col tw-gap-2 tw-p-3">
														<div
															v-for="field in group.elements"
															:key="field.id"
															class="tw-flex tw-cursor-pointer tw-items-center tw-gap-3 tw-px-4 tw-py-2"
															v-show="!['panel', 'display', 'emundus_fileupload'].includes(field.plugin)"
														>
															<span class="tw-w-full" v-if="field.label !== ''">{{ field.label }}</span>
															<span class="tw-w-full" v-else>{{ field.plugin_name }}</span>
															<div
																class="tw-flex tw-cursor-pointer tw-rounded tw-bg-main-500 tw-p-1"
																:class="{ 'tw-cursor-not-allowed tw-bg-neutral-300': disabled(field) || allDisabled }"
																@click="addSelectedElement(field)"
															>
																<span class="material-symbols-outlined tw-text-white">arrow_forward</span>
															</div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div>
						<p>
							<strong>{{ this.translate('COM_EMUNDUS_EXPORT_EXPORTING_CONTENT') }}</strong>
						</p>
						<div class="tw-mt-3">
							<Tabs
								v-if="selectedFormat === 'pdf'"
								:tabs="tabsPdf"
								:classes="'tw-overflow-auto tw-flex tw-items-center tw-gap-2 tw-ml-4'"
							></Tabs>
							<Tabs
								v-else-if="selectedFormat === 'xlsx'"
								:tabs="tabsXlsx"
								:classes="'tw-overflow-auto tw-flex tw-items-center tw-gap-2 tw-ml-4'"
							></Tabs>
							<div
								class="tw-relative -tw-ml-8 tw-max-h-[70vh] tw-min-h-[70vh] tw-overflow-scroll tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-neutral-100 tw-py-6 tw-pl-12 tw-pr-6 tw-shadow-card"
							>
								<div
									v-if="
										(selectedFormat === 'xlsx' && tabsXlsx[0].active) || (selectedFormat === 'pdf' && tabsPdf[0].active)
									"
								>
									<div
										class="tw-mb-2 tw-flex tw-w-full tw-items-center tw-justify-between"
										v-if="selectedElements.length > 0"
									>
										<span>
											{{ selectedElements.length }} {{ this.translate('COM_EMUNDUS_EXPORT_ITEMS_SELECTED') }}
										</span>
										<Button
											variant="link"
											width="fit"
											class="tw-text-red-400"
											@click="
												selectedElements = [];
												updatePrefixTab('content', 0);
											"
										>
											{{ this.translate('COM_EMUNDUS_EXPORT_CLEAR_ALL') }}
										</Button>
									</div>

									<div v-if="selectedElements.length > 0" class="tw-flex tw-flex-col tw-gap-2">
										<draggable
											v-model="selectedElements"
											group="exports-elements"
											handle=".handle"
											:sort="selectedFormat === 'xlsx'"
										>
											<transition-group>
												<div
													v-for="element in selectedElements"
													:key="element.id"
													class="tw-flex tw-items-center tw-gap-3 tw-rounded-coordinator-cards tw-border tw-border-main-50 tw-bg-white tw-p-3"
												>
													<div
														class="tw-flex tw-cursor-pointer tw-rounded tw-border tw-border-main-500 tw-bg-white tw-p-1"
														@click="removeSelectedElement(element)"
													>
														<span class="material-symbols-outlined tw-text-main-500">arrow_back</span>
													</div>
													<span v-if="selectedFormat === 'xlsx'" class="material-symbols-outlined handle tw-cursor-grab"
														>drag_indicator</span
													>
													<div class="tw-flex tw-flex-col">
														<span>{{ element.label }}</span>
														<span class="tw-text-xs tw-text-neutral-500">{{ element.plugin_name }}</span>
													</div>
												</div>
											</transition-group>
										</draggable>
									</div>
									<div v-else v-html="this.translate('COM_EMUNDUS_EXPORT_NO_CONTENT_SELECTED')"></div>
								</div>

								<div v-else-if="selectedFormat === 'pdf' && tabsPdf[1].active">
									<div
										class="tw-mb-2 tw-flex tw-w-full tw-items-center tw-justify-between"
										v-if="selectedHeaders.length > 0"
									>
										<span>
											{{ selectedHeaders.length }} {{ this.translate('COM_EMUNDUS_EXPORT_ITEMS_SELECTED') }}
										</span>
										<Button
											variant="link"
											width="fit"
											class="tw-text-red-400"
											@click="
												selectedHeaders = [];
												updatePrefixTab('header', 0);
											"
										>
											{{ this.translate('COM_EMUNDUS_EXPORT_CLEAR_ALL') }}
										</Button>
									</div>
									<div v-if="selectedHeaders.length >= 5" class="tw-mb-2 tw-text-sm tw-text-red-500">
										{{ this.translate('COM_EMUNDUS_EXPORT_MAX_HEADER_REACHED') }}
									</div>
									<div v-if="selectedHeaders.length > 0" class="tw-flex tw-flex-col tw-gap-2">
										<draggable v-model="selectedHeaders" group="exports-headers" handle=".handle">
											<transition-group>
												<div
													v-for="element in selectedHeaders"
													:key="element.id"
													class="tw-flex tw-items-center tw-gap-3 tw-rounded-coordinator-cards tw-border tw-border-main-50 tw-bg-white tw-p-3"
												>
													<div
														class="tw-flex tw-cursor-pointer tw-rounded tw-border tw-border-main-500 tw-bg-white tw-p-1"
														@click="removeSelectedElement(element)"
													>
														<span class="material-symbols-outlined tw-text-main-500">arrow_back</span>
													</div>
													<span class="material-symbols-outlined handle tw-cursor-grab">drag_indicator</span>
													<div class="tw-flex tw-flex-col">
														<span>{{ element.label }}</span>
														<span class="tw-text-xs tw-text-neutral-500">{{ element.plugin_name }}</span>
													</div>
												</div>
											</transition-group>
										</draggable>
									</div>
									<div v-else v-html="this.translate('COM_EMUNDUS_EXPORT_NO_HEADER_SELECTED')"></div>
								</div>

								<div
									v-else-if="
										(selectedFormat === 'pdf' && tabsPdf[2].active) || (selectedFormat === 'xlsx' && tabsXlsx[1].active)
									"
								>
									<div
										class="tw-mb-2 tw-flex tw-w-full tw-items-center tw-justify-between"
										v-if="selectedSynthesis.length > 0"
									>
										<span>
											{{ selectedSynthesis.length }} {{ this.translate('COM_EMUNDUS_EXPORT_ITEMS_SELECTED') }}
										</span>
										<Button
											variant="link"
											width="fit"
											class="tw-text-red-400"
											@click="
												selectedSynthesis = [];
												updatePrefixTab('synthesis', 0);
											"
										>
											{{ this.translate('COM_EMUNDUS_EXPORT_CLEAR_ALL') }}
										</Button>
									</div>
									<div v-if="selectedSynthesis.length >= 10" class="tw-mb-2 tw-text-sm tw-text-red-500">
										{{ this.translate('COM_EMUNDUS_EXPORT_MAX_SYNTHESIS_REACHED') }}
									</div>
									<div v-if="selectedSynthesis.length > 0" class="tw-flex tw-flex-col tw-gap-2">
										<draggable v-model="selectedSynthesis" group="exports-synthesis" handle=".handle">
											<transition-group>
												<div
													v-for="element in selectedSynthesis"
													:key="element.id"
													class="tw-flex tw-items-center tw-gap-3 tw-rounded-coordinator-cards tw-border tw-border-main-50 tw-bg-white tw-p-3"
												>
													<div
														class="tw-flex tw-cursor-pointer tw-rounded tw-border tw-border-main-500 tw-bg-white tw-p-1"
														@click="removeSelectedElement(element)"
													>
														<span class="material-symbols-outlined tw-text-main-500">arrow_back</span>
													</div>
													<span class="material-symbols-outlined handle tw-cursor-grab">drag_indicator</span>
													<div class="tw-flex tw-flex-col">
														<span>{{ element.label }}</span>
														<span class="tw-text-xs tw-text-neutral-500">{{ element.plugin_name }}</span>
													</div>
												</div>
											</transition-group>
										</draggable>
									</div>
									<div
										v-else-if="selectedFormat === 'pdf'"
										v-html="this.translate('COM_EMUNDUS_EXPORT_NO_SYNTHESIS_SELECTED')"
									></div>
									<div
										v-else-if="selectedFormat === 'xlsx'"
										v-html="this.translate('COM_EMUNDUS_EXPORT_NO_SYNTHESIS_SELECTED_XLSX')"
									></div>
								</div>

								<div v-else-if="selectedFormat === 'pdf' && tabsPdf[3].active">
									<div
										class="tw-mb-2 tw-flex tw-w-full tw-items-center tw-justify-between"
										v-if="selectedAttachments.length > 0"
									>
										<span>
											{{ selectedAttachments.length }} {{ this.translate('COM_EMUNDUS_EXPORT_ITEMS_SELECTED') }}
										</span>
										<Button
											variant="link"
											width="fit"
											class="tw-text-red-400"
											@click="
												selectedAttachments = [];
												updatePrefixTab('attachments', 0);
											"
										>
											{{ this.translate('COM_EMUNDUS_EXPORT_CLEAR_ALL') }}
										</Button>
									</div>
									<div v-if="selectedAttachments.length > 0" class="tw-flex tw-flex-col tw-gap-2">
										<draggable v-model="selectedAttachments" group="exports-headers" sort="false">
											<transition-group>
												<div
													v-for="element in selectedAttachments"
													:key="element.id"
													class="tw-flex tw-items-center tw-gap-3 tw-rounded-coordinator-cards tw-border tw-border-main-50 tw-bg-white tw-p-3"
												>
													<div
														class="tw-flex tw-cursor-pointer tw-rounded tw-border tw-border-main-500 tw-bg-white tw-p-1"
														@click="removeSelectedElement(element)"
													>
														<span class="material-symbols-outlined tw-text-main-500">arrow_back</span>
													</div>
													<div class="tw-flex tw-flex-col">
														<span>{{ element.label }}</span>
														<span class="tw-text-xs tw-text-neutral-500">{{ element.plugin_name }}</span>
													</div>
												</div>
											</transition-group>
										</draggable>
									</div>
									<div v-else v-html="this.translate('COM_EMUNDUS_EXPORT_NO_ATTACHMENTS_SELECTED')"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div v-else>
				<div class="form-group tw-flex tw-flex-col tw-gap-2">
					<label>{{ this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_NAME') }}</label>
					<input
						v-model="exportName"
						type="text"
						maxlength="40"
						class="form__input field-general w-input"
						style="margin: 0"
					/>
				</div>
			</div>

			<div
				v-if="!displayExportSavedContent"
				class="tw-flex"
				:class="{ 'tw-justify-end': steps[0].active, 'tw-justify-between': !steps[0].active }"
			>
				<Button v-if="!steps[0].active" variant="secondary" @click="previousStep">
					{{ translate('COM_EMUNDUS_EXPORT_PREVIOUS_STEP') }}
				</Button>
				<SplitButton
					v-if="finalStep.active"
					:disabled="selectedElements.length === 0"
					:label="
						translate('COM_EMUNDUS_EXPORT_RUN') + ' (' + fnumsCount + ' ' + translate('COM_EMUNDUS_FILES_FILES') + ')'
					"
					variant="primary"
					position="top-left"
					@click="runExport"
				>
					<ul class="tw-m-0 tw-list-none tw-p-2">
						<li
							class="tw-cursor-pointer tw-rounded-applicant tw-p-2 tw-text-red-600 hover:tw-bg-neutral-300"
							v-if="exportId !== 0"
							@click="deleteExport"
						>
							{{ this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_DELETE') }}
						</li>
						<li
							class="tw-cursor-pointer tw-rounded-applicant tw-p-2 tw-text-neutral-900 hover:tw-bg-neutral-300"
							v-if="exportId !== 0"
							@click="
								newTemplate = false;
								displayExportSavedContent = true;
							"
						>
							{{ this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_UPDATE') }}
						</li>
						<li
							class="tw-cursor-pointer tw-rounded-applicant tw-p-2 tw-text-neutral-900 hover:tw-bg-neutral-300"
							@click="
								newTemplate = true;
								exportName = '';
								displayExportSavedContent = true;
							"
						>
							{{ this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_CREATE') }}
						</li>
					</ul>
				</SplitButton>

				<Button v-else variant="primary" @click="nextStep">
					{{ translate('COM_EMUNDUS_EXPORT_NEXT_STEP') }}
				</Button>
			</div>

			<div v-else class="tw-flex tw-justify-between">
				<Button variant="secondary" @click="displayExportSavedContent = false">{{
					this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_BACK')
				}}</Button>
				<Button variant="primary" @click="saveExport">{{
					this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE')
				}}</Button>
			</div>
		</div>

		<div v-if="loading" class="tw-flex tw-min-h-[20vh] tw-flex-col tw-items-center tw-justify-center">
			<div class="em-loader"></div>

			<span class="tw-mt-1" v-if="this.formatLoading">{{ this.translate('COM_EMUNDUS_EXPORT_FORMATS_LOADER') }}</span>
			<span class="tw-mt-1" v-if="this.exportLoading">{{ this.translate('COM_EMUNDUS_EXPORT_RUN_LOADER') }}</span>
		</div>
	</div>
</template>

<style scoped></style>
