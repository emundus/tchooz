<script>
import { defineComponent } from 'vue';
import exportService from '@/services/export.js';
import alerts from '@/mixins/alerts.js';
import Stepper from '@/components/Molecules/Stepper.vue';
import FormatSelector from '@/components/Atoms/FormatSelector.vue';
import Tabs from '@/components/Utils/Tabs.vue';
import Dropdown from '@/components/Molecules/Dropdown.vue';
import { Alert, Button, Icon, MenuItem } from '@emundus/ui';
import ExportContent from '@/components/Exports/ExportContent.vue';
import ExportOptions from '@/components/Exports/ExportOptions.vue';
import ExportResume from '@/components/Exports/ExportResume.vue';
import { VueDraggableNext as draggable } from 'vue-draggable-next';

export default defineComponent({
	name: 'Exports',
	components: {
		Icon,
		ExportContent,
		ExportOptions,
		ExportResume,
		Alert,
		Dropdown,
		MenuItem,
		Tabs,
		FormatSelector,
		Stepper,
		Button,
	},
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
			subElementsLoading: 0,
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

			// Pending template deletion (kept until the undo window closes, then committed to the backend)
			pendingDelete: null,
			deleteTimer: null,

			formats: [],
			selectedFormat: 'xlsx',
			formatView: 'format',

			selectedContentMenu: 'applicant',

			reloadWidth: 0,
			currentStep: null,

			elements: [],
			openedSections: [],

			selectedElements: [],
			selectedHeaders: [],
			selectedSynthesis: [],
			selectedAttachments: [],

			// Content types whose default selection has already been applied (so it isn't reapplied on remount)
			initializedContent: [],

			exportSettings: {},
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
	beforeUnmount() {
		// Don't lose a pending deletion if the component is torn down before the undo window elapses
		this.commitDeleteExport();
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

		isActiveStep(code) {
			const step = this.steps.find((step) => {
				return step.code === code;
			});

			if (!step) {
				return false;
			}

			return step.active;
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
			// A saved export already carries its format and content: skip the intermediate steps straight to the resume
			if (this.currentStep && this.currentStep.code === 'formats' && this.exportId > 0) {
				this.loadSavedExport().then(() => {
					this.goToStep('resume');
				});
				return;
			}

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

			return new Promise((resolve) => {
				exportService
					.getElements(this.selectedContentMenu, this.selectedFormat, this.queryController)
					.then((response) => {
						this.elements = response.data;
						this.subLoading = false;
						this.elementsLoading = false;
						this.queryController = null;

						resolve(this.elements);
					});
			});
		},

		runExport(async = false) {
			this.loading = true;
			this.exportLoading = true;

			if (!this.canRunExport) {
				this.alertError('COM_EMUNDUS_EXPORT_ERROR_TITLE', this.translate('COM_EMUNDUS_EXPORT_NO_CONTENT_SELECTED'));
				this.loading = false;
				this.exportLoading = false;
				return;
			}

			// Prepare selected elements ids
			const selectedElementIds = this.selectedElements.map((el) => el.id);
			const selectedHeaderIds = this.selectedHeaders.map((el) => el.id);
			const selectedSynthesisIds = this.selectedSynthesis.map((el) => el.id);
			const selectedAttachmentIds = this.selectedAttachments.map((el) => el.id);

			const allowAsync = Joomla.getOptions('plg_system_emundus.async_export', 0);

			exportService
				.export(
					this.selectedFormat,
					selectedElementIds,
					selectedHeaderIds,
					selectedSynthesisIds,
					selectedAttachmentIds,
					this.exportSettings,
					async,
					allowAsync == 1,
				)
				.then((response) => {
					this.loading = false;
					this.exportLoading = false;

					let confirmMessage = 'LINK_TO_DOWNLOAD';
					let closeMessage = 'COM_EMUNDUS_CLOSE';
					if (async && !response.data.progress >= 100) {
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
								if (async && !response.data.progress >= 100) {
									window.open(this.exportLink, '_self');
								} else {
									// Files under images/emundus/exports must be served through the getfile
									// PHP gateway: some web servers (IIS) 301-redirect static .zip requests to
									// the home page, turning the download into an HTML file. Going through
									// index.php streams the bytes and is immune to those rewrite rules.
									const filename = response.data.filename || '';
									const downloadUrl = filename.startsWith('images/emundus/exports')
										? '/index.php?option=com_emundus&task=getfile&u=' + filename
										: '/' + filename;
									window.open(downloadUrl, '_blank');
								}
							}
						});
					} else {
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

			if (!this.canRunExport) {
				this.alertError('COM_EMUNDUS_EXPORT_ERROR_TITLE', this.translate('COM_EMUNDUS_EXPORT_NO_CONTENT_SELECTED'));
				this.loading = false;
				return;
			}

			const selectedElementIds = this.selectedElements.map((el) => el.id);
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
					this.exportSettings,
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
			const id = this.exportId;
			const index = this.exportTemplates.findIndex((template) => parseInt(template.id) === id);
			if (index === -1) {
				return;
			}

			// Commit any previous pending deletion before starting a new one
			this.commitDeleteExport();

			// Optimistically remove the template and open an undo window before hitting the backend
			const template = this.exportTemplates[index];
			this.pendingDelete = { template, index };
			this.exportTemplates = this.exportTemplates.filter((t) => parseInt(t.id) !== id);

			this.displayExportSavedContent = false;
			this.exportId = 0;
			this.exportName = '';

			this.message = this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_DELETED_UNDO_MESSAGE').replace('%s', template.name);

			clearTimeout(this.deleteTimer);
			this.deleteTimer = setTimeout(() => {
				this.commitDeleteExport();
				this.message = '';
			}, 5000);
		},
		commitDeleteExport() {
			if (!this.pendingDelete) {
				return;
			}

			clearTimeout(this.deleteTimer);
			this.deleteTimer = null;

			const { template, index } = this.pendingDelete;
			this.pendingDelete = null;

			exportService.deleteExport(template.id).then((response) => {
				if (!response.status) {
					// Roll back the optimistic removal if the backend refused
					this.exportTemplates.splice(index, 0, template);
					this.alertError('COM_EMUNDUS_EXPORT_ERROR_TITLE', response.msg);
				}
			});
		},
		undoDelete() {
			clearTimeout(this.deleteTimer);
			this.deleteTimer = null;

			if (this.pendingDelete) {
				const { template, index } = this.pendingDelete;
				// Restore the template at its original position and reselect it
				this.exportTemplates.splice(index, 0, template);
				this.exportId = parseInt(template.id);
				this.exportName = template.name;
				this.pendingDelete = null;
			}

			this.message = this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_DELETE_UNDONE_MESSAGE');
			setTimeout(() => {
				this.message = '';
			}, 3000);
		},

		updateTemplate(value) {
			// The placeholder option emits an empty string; normalise to 0 so "no template" stays numeric
			this.exportId = parseInt(value) || 0;
		},

		loadSavedExport() {
			this.selectedElements = [];
			this.selectedHeaders = [];
			this.selectedSynthesis = [];
			this.selectedAttachments = [];

			return exportService.getElementsFromSaveExport(this.exportId).then((response) => {
				if (response.status) {
					this.selectedFormat = response.data.format;
					this.exportName = response.data.name;

					this.selectedElements = response.data.elements;
					this.selectedHeaders = response.data.headers;
					this.selectedSynthesis = response.data.synthesis;
					this.selectedAttachments = response.data.attachments;
					this.exportSettings = response.data.settings ?? {};
				}

				return this.getElements().then(() => {
					this.reloadForm++;
				});
			});
		},

		goToStep(code) {
			const index = this.steps.findIndex((step) => step.code === code);
			if (index === -1) {
				return;
			}

			this.steps.forEach((step, i) => {
				step.active = i === index;
				step.completed = i < index;
			});

			this.currentStep = this.steps[index];
			this.reloadWidth++;
		},

		updateSelectedContentMenu(value) {
			this.selectedContentMenu = value;
		},

		onContentInitialized(contentType) {
			if (!this.initializedContent.includes(contentType)) {
				this.initializedContent.push(contentType);
			}
		},
	},
	computed: {
		steps() {
			let steps = [];
			steps.push({ label: 'COM_EMUNDUS_EXPORTS_FORMAT', code: 'formats', active: true, completed: false });
			switch (this.selectedFormat) {
				case 'xlsx':
					steps.push({
						label: 'COM_EMUNDUS_EXPORT_CONTENT_MAIN',
						code: 'content_main',
						active: false,
						completed: false,
					});
					break;
				case 'pdf':
				case 'zip':
					steps.push(
						{
							label: 'COM_EMUNDUS_EXPORT_CONTENT_MAIN',
							code: 'content_main',
							active: false,
							completed: false,
						},
						{
							label: 'COM_EMUNDUS_EXPORT_CONTENT_HEADER',
							code: 'content_header',
							active: false,
							completed: false,
						},
						{
							label: 'COM_EMUNDUS_EXPORT_CONTENT_SYNTHESIS',
							code: 'content_synthesis',
							active: false,
							completed: false,
						},
						{
							label: 'COM_EMUNDUS_EXPORT_CONTENT_ATTACHMENT',
							code: 'content_attachment',
							active: false,
							completed: false,
						},
					);
					break;
			}

			steps.push(
				{ label: 'COM_EMUNDUS_EXPORT_OPTIONS', code: 'options', active: false, completed: false },
				{ label: 'COM_EMUNDUS_EXPORT_RESUME', code: 'resume', active: false, completed: false },
			);

			return steps;
		},
		finalStep() {
			return this.steps[this.steps.length - 1];
		},
		nextStepDisabled() {
			// On the format step, the saved-template view requires a template to be selected before going further
			return this.currentStep?.code === 'formats' && this.formatView === 'template' && this.exportId === 0;
		},
		canRunExport() {
			// A ZIP bundles documents, so it can be launched with attachments (or any other content)
			// alone — no form content required. Other formats still need form content selected.
			if (this.selectedFormat === 'zip') {
				return (
					this.selectedElements.length > 0 ||
					this.selectedHeaders.length > 0 ||
					this.selectedSynthesis.length > 0 ||
					this.selectedAttachments.length > 0
				);
			}

			return this.selectedElements.length > 0;
		},
		runExportLabel() {
			return (
				this.translate('COM_EMUNDUS_EXPORT_RUN') +
				' (' +
				this.fnumsCount +
				' ' +
				this.translate(this.fnumsCount > 1 ? 'COM_EMUNDUS_FILES_FILES' : 'COM_EMUNDUS_FILES_FILE') +
				')'
			);
		},
	},
	watch: {
		currentStep: {
			handler(newStep) {
				if (newStep === null) return;

				// A saved export is loaded once when proceeding from the format step (see nextStep).
				// Navigation must only refresh the available elements pool, never reload the saved data,
				// otherwise edits made to a template's selection get overwritten on every step change.
				if (['content_header', 'content_synthesis', 'content_main'].includes(newStep.code)) {
					if (this.selectedContentMenu === 'attachments') {
						// Reset to a content-bearing menu when coming back from the attachments step
						this.selectedContentMenu = 'applicant';
					} else {
						this.getElements();
					}
				}

				if (newStep.code === 'content_attachment') {
					if (this.selectedContentMenu !== 'attachments') {
						// Triggers getElements() via the selectedContentMenu watcher
						this.selectedContentMenu = 'attachments';
					} else {
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
		selectedContentMenu: {
			handler() {
				this.getElements();
			},
		},
		formatView: {
			handler(newView) {
				// Switching to the format view means starting from a format, not a saved template
				if (newView === 'format') {
					this.exportId = 0;
					this.exportName = '';
				}
			},
		},
	},
});
</script>

<template>
	<div class="tw-max-h-[80vh] tw-w-[70vw] tw-min-w-[20vw]">
		<div class="tw-flex tw-flex-col tw-gap-6" :key="reloadForm" v-show="!loading">
			<Alert v-if="message !== ''" state="success" class="tw-mb-4">
				<span v-html="message"></span>
				<template #actions v-if="pendingDelete">
					<Button variant="primary" emphasis="main" size="sm" @click="undoDelete">
						<template #leading>
							<Icon name="delete_forever" />
						</template>
						{{ translate('COM_EMUNDUS_EXPORT_TEMPLATE_DELETE_UNDO') }}
					</Button>
				</template>
			</Alert>

			<div v-if="!displayExportSavedContent">
				<Stepper :steps="steps" @step-clicked="changeStep" :reload-width="reloadWidth" class="tw-mb-6" />

				<FormatSelector
					v-if="isActiveStep('formats')"
					v-model="selectedFormat"
					:formats="formats"
					:exportTemplates="exportTemplates"
					:selected-template="exportId"
					@select-template="updateTemplate"
					@update:view="formatView = $event"
				/>

				<ExportContent
					v-if="isActiveStep('content_header')"
					v-model="selectedHeaders"
					content-type="content_header"
					:elements="elements"
					:selected-format="selectedFormat"
					:loading="subLoading"
					:elements-loading="elementsLoading"
					:apply-defaults="exportId === 0 && !initializedContent.includes('content_header')"
					@toggle-content-menu="updateSelectedContentMenu"
					@initialized="onContentInitialized"
				/>

				<ExportContent
					v-if="isActiveStep('content_synthesis')"
					v-model="selectedSynthesis"
					content-type="content_synthesis"
					:elements="elements"
					:selected-format="selectedFormat"
					:loading="subLoading"
					:elements-loading="elementsLoading"
					:apply-defaults="exportId === 0 && !initializedContent.includes('content_synthesis')"
					@toggle-content-menu="updateSelectedContentMenu"
					@initialized="onContentInitialized"
				/>

				<ExportContent
					v-if="isActiveStep('content_main')"
					v-model="selectedElements"
					:elements="elements"
					:selected-format="selectedFormat"
					:loading="subLoading"
					:elements-loading="elementsLoading"
					:apply-defaults="exportId === 0 && !initializedContent.includes('content_main')"
					@toggle-content-menu="updateSelectedContentMenu"
					@initialized="onContentInitialized"
				/>

				<ExportContent
					v-if="isActiveStep('content_attachment')"
					v-model="selectedAttachments"
					content-type="content_attachment"
					:elements="elements"
					:selected-format="selectedFormat"
					:loading="subLoading"
					:elements-loading="elementsLoading"
					@toggle-content-menu="updateSelectedContentMenu"
				/>

				<div v-if="isActiveStep('options')">
					<ExportOptions
						v-model="exportSettings"
						:format="selectedFormat"
						:elements="selectedElements"
						:all-elements="elements"
					/>
				</div>

				<div v-if="isActiveStep('resume')" class="tw-max-h-[55vh] tw-overflow-y-auto">
					<ExportResume
						:selected-format="selectedFormat"
						:export-settings="exportSettings"
						:selected-headers="selectedHeaders"
						:selected-synthesis="selectedSynthesis"
						:selected-elements="selectedElements"
						:selected-attachments="selectedAttachments"
						:formats="formats"
					/>
				</div>
			</div>

			<div v-else>
				<div class="tw-flex tw-flex-col tw-gap-2">
					<label>{{ this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_NAME') }}</label>
					<span>{{ this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_NAME_HELP') }}</span>
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
				<Button v-if="!steps[0].active" emphasis="lite" @click="previousStep">
					<template #leading>
						<Icon name="chevron_left" />
					</template>
					{{ translate('COM_EMUNDUS_EXPORT_PREVIOUS_STEP') }}
				</Button>
				<Dropdown
					v-if="finalStep.active"
					:disabled="!canRunExport"
					:label="runExportLabel"
					variant="primary"
					align="right"
					direction="up"
				>
					<MenuItem icon="download" :label="runExportLabel" :aria-label="runExportLabel" @click="runExport" />
					<MenuItem
						v-if="exportId !== 0"
						icon="edit"
						:label="translate('COM_EMUNDUS_EXPORT_TEMPLATE_UPDATE')"
						:aria-label="translate('COM_EMUNDUS_EXPORT_TEMPLATE_UPDATE')"
						@click="
							newTemplate = false;
							displayExportSavedContent = true;
						"
					/>
					<MenuItem
						icon="check_circle"
						:label="translate('COM_EMUNDUS_EXPORT_TEMPLATE_CREATE')"
						:aria-label="translate('COM_EMUNDUS_EXPORT_TEMPLATE_CREATE')"
						@click="
							newTemplate = true;
							exportName = '';
							displayExportSavedContent = true;
						"
					/>
					<MenuItem
						v-if="exportId !== 0"
						danger
						icon="delete"
						:label="translate('COM_EMUNDUS_EXPORT_TEMPLATE_DELETE')"
						:aria-label="translate('COM_EMUNDUS_EXPORT_TEMPLATE_DELETE')"
						@click="deleteExport"
					/>
				</Dropdown>

				<Button v-else variant="primary" :disabled="nextStepDisabled" @click="nextStep">
					{{ translate('COM_EMUNDUS_EXPORT_NEXT_STEP') }}
					<template #trailing>
						<Icon name="chevron_right" />
					</template>
				</Button>
			</div>

			<div v-else class="tw-flex tw-justify-between">
				<Button emphasis="lite" @click="displayExportSavedContent = false">
					<template #leading>
						<Icon name="chevron_left" />
					</template>
					{{ this.translate('COM_EMUNDUS_EXPORT_TEMPLATE_BACK_TO_RESUME') }}
				</Button>
				<Button variant="primary" @click="saveExport">
					{{ this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE') }}
				</Button>
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
