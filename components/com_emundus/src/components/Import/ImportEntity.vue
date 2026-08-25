<script>
import importService from '@/services/import.js';
import settingsService from '@/services/settings';
import { useGlobalStore } from '@/stores/global.js';
import alerts from '@/mixins/alerts.js';
import ImportFieldsHelp from '@/components/Import/ImportFieldsHelp.vue';
import ImportDropzone from '@/components/Import/ImportDropzone.vue';
import ImportDryRunResult from '@/components/Import/ImportDryRunResult.vue';
import ImportDone from '@/components/Import/ImportDone.vue';
import ImportSummary from '@/components/Import/ImportSummary.vue';

// Live progress polling is built but voluntarily disabled for now: queued
// imports show a "go to My imports" screen instead. Set to true to restore
// the in-modal progress bar.
const LIVE_POLLING_ENABLED = false;

export default {
	name: 'ImportEntity',
	components: { ImportFieldsHelp, ImportDropzone, ImportDryRunResult, ImportDone, ImportSummary },
	mixins: [alerts],
	emits: ['close', 'update-items'],
	props: {
		tab: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			fields: [],
			step: 'selectingFile',
			showHelp: false,
			uploading: false,
			uploadError: null,
			selectedFile: null,
			dryRunReport: null,
			importReport: null,
			supportedFormats: ['csv', 'xlsx', 'xls', 'json'],
			// Async follow-up state (filled when the server queues the import).
			importId: null,
			pollTimer: null,
			// Guards against overlapping polls: a request can outlast the
			// polling interval (loaded server, big import), and the interval
			// must never stack a second one while the first is in flight.
			pollInFlight: false,
			progress: 0,
			counts: null,
			// Stop polling after this many consecutive failures (import deleted,
			// 404, server down…) instead of hammering the endpoint forever.
			pollFailures: 0,
			maxPollFailures: 5,
			pollError: false,
			// Conflict-resolution policy chosen by the user before upload.
			// Defaults to 'skip' (current safe behaviour) — must match
			// Tchooz\Enums\Import\ImportConflictModeEnum values.
			conflictMode: 'skip',
			conflictModes: [
				{ value: 'skip', icon: 'block', labelKey: 'COM_EMUNDUS_IMPORT_CONFLICT_MODE_SKIP', displayed: true },
				{ value: 'update', icon: 'sync', labelKey: 'COM_EMUNDUS_IMPORT_CONFLICT_MODE_UPDATE', displayed: true },
				{
					value: 'create_new',
					icon: 'content_copy',
					labelKey: 'COM_EMUNDUS_IMPORT_CONFLICT_MODE_CREATE_NEW',
					displayed: false,
				},
			],
		};
	},
	created() {
		this.getEntityImportInformation();
	},
	beforeUnmount() {
		// Never leave a polling timer running after the component is gone.
		this.stopPolling();
	},
	methods: {
		getEntityImportInformation() {
			this.loading = true;
			importService.getEntityImportInformation(this.tab).then((response) => {
				this.fields = response.data.fields || [];

				if (Array.isArray(response.data.formatsSupported) && response.data.formatsSupported.length) {
					this.supportedFormats = response.data.formatsSupported;
				}
				if (Array.isArray(response.data.conflictModesSupported)) {
					this.conflictModes.forEach((mode) => {
						mode.displayed = response.data.conflictModesSupported.includes(mode.value);
					});

					// If the default conflict mode is not supported, switch to the first supported one.
					if (!this.conflictModes.find((mode) => mode.value === this.conflictMode && mode.displayed)) {
						const firstSupportedMode = this.conflictModes.find((mode) => mode.displayed);
						this.conflictMode = firstSupportedMode ? firstSupportedMode.value : null;
					}
				}

				this.loading = false;
			});
		},
		onUpdateConflictMode(mode) {
			this.conflictMode = mode;
		},
		handleFile(file) {
			const ext = file.name.split('.').pop().toLowerCase();
			if (!this.supportedFormats.includes(ext)) {
				this.uploadError = this.translate('COM_EMUNDUS_IMPORT_UNSUPPORTED_FORMAT').replace(
					'%s',
					this.supportedFormats.join(', '),
				);
				return;
			}

			this.uploadError = null;
			this.selectedFile = file;
		},
		async launchDryRun() {
			if (this.selectedFile === null) {
				this.uploadError = this.translate('COM_EMUNDUS_IMPORT_NO_FILE_SELECTED');
				return;
			}

			this.uploading = true;
			const response = await importService.dryRun(this.tab, this.selectedFile, this.conflictMode);

			this.uploading = false;

			if (response?.status === false) {
				this.uploadError = response.msg || this.translate('COM_EMUNDUS_IMPORT_UPLOAD_ERROR');
				this.selectedFile = null;
				return;
			}

			this.dryRunReport = response.data ?? response;
			this.step = 'dryrun';
		},
		resetToFields() {
			this.stopPolling();
			this.step = 'selectingFile';
			this.dryRunReport = null;
			this.importReport = null;
			this.selectedFile = null;
			this.uploadError = null;
			this.importId = null;
			this.progress = 0;
			this.counts = null;
		},
		async confirmImport() {
			this.step = 'importing';

			const response = await importService.importFile(this.tab, this.selectedFile, this.conflictMode);

			if (response?.status === false) {
				this.uploadError = response.msg || this.translate('COM_EMUNDUS_IMPORT_UPLOAD_ERROR');
				this.step = 'dryrun';
				return;
			}

			const data = response.data ?? response;

			if (data && data.import_id) {
				this.importId = data.import_id;

				if (LIVE_POLLING_ENABLED) {
					// Async: follow progress by polling.
					this.progress = 0;
					this.counts = null;
					this.step = 'processing';
					this.startPolling();
				} else {
					// Async without live follow-up: point the user to the "My imports" page.
					this.closeAndRefresh();
					this.alertConfirm(
						'COM_EMUNDUS_IMPORT_QUEUED_TITLE',
						response.msg,
						false,
						'COM_EMUNDUS_GO_TO_IMPORTS_PAGE',
						'COM_EMUNDUS_STAY_ON_PAGE',
						null,
						false,
					).then((result) => {
						if (result.isConfirmed) {
							this.goToImportsPage();
						}
					});
				}
			} else {
				// Synchronous fallback: the report is already here.
				this.importReport = data;
				this.step = 'done';
			}
		},
		startPolling() {
			this.stopPolling();
			this.pollFailures = 0;
			this.pollError = false;
			// ~3s cadence: matches the cron run rhythm without hammering the server.
			this.pollTimer = setInterval(() => this.pollProgress(), 3000);
			this.pollProgress();
		},
		stopPolling() {
			if (this.pollTimer) {
				clearInterval(this.pollTimer);
				this.pollTimer = null;
			}
		},
		async pollProgress() {
			if (this.pollInFlight) {
				return;
			}
			this.pollInFlight = true;

			try {
				const importId = this.importId;
				const response = await importService.getImportProgress(importId);

				// Stale response: polling was stopped (cancel, reset, unmount) or
				// restarted for another import while this request was in flight.
				if (!this.pollTimer || importId !== this.importId) {
					return;
				}

				// Failure (404 if the import was deleted, transient 5xx, network…): tolerate
				// a few in a row, but give up instead of looping forever on a dead job.
				if (response?.status === false) {
					this.pollFailures++;
					if (this.pollFailures >= this.maxPollFailures) {
						this.stopPolling();
						this.pollError = true;
					}
					return;
				}

				// Recovered: reset the failure streak.
				this.pollFailures = 0;

				const data = response.data ?? response;
				this.progress = data.progress ?? 0;
				this.counts = data.counts ?? null;

				if (['completed', 'failed', 'cancelled'].includes(data.status)) {
					this.stopPolling();
					await this.loadFinalReport();
				}
			} finally {
				this.pollInFlight = false;
			}
		},
		async loadFinalReport() {
			const response = await importService.getImportReport(this.importId);

			if (response?.status === false) {
				this.pollError = true;
				return;
			}

			const data = response.data ?? response;
			this.importReport = data.report ?? data;
			this.step = 'done';
		},
		async cancelImport() {
			await importService.cancelImport(this.importId);
			this.stopPolling();
			// Show the partial result gathered so far.
			await this.loadFinalReport();
		},
		closeAndRefresh() {
			this.$emit('update-items');
			this.$emit('close');
		},
		goToImportsPage() {
			settingsService.redirectJRoute(
				'index.php?option=com_emundus&view=imports&layout=imports',
				useGlobalStore().getCurrentLang,
			);
		},
		importAnother() {
			this.$emit('update-items');
			this.resetToFields();
		},
	},
	computed: {
		displayedConflictModes() {
			return this.conflictModes.filter((mode) => mode.displayed);
		},
		entityTerm() {
			return this.translate('COM_EMUNDUS_IMPORT_ENTITY_' + this.tab.toUpperCase());
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-h-full tw-flex-col tw-gap-4">
		<ImportFieldsHelp
			v-if="showHelp"
			:fields="fields"
			:type="this.tab"
			@close="showHelp = false"
			@close-modal="$emit('close')"
		/>

		<template v-else>
			<div
				v-if="step !== 'dryrun' && step !== 'done'"
				class="tw-relative tw-mb-2 tw-flex tw-items-center tw-justify-center"
			>
				<h2 class="tw-m-0 tw-text-center">{{ translate('COM_EMUNDUS_IMPORT_TITLE').replace('%s', entityTerm) }}</h2>
				<button class="tw-absolute tw-right-0 tw-cursor-pointer tw-bg-transparent" @click="$emit('close')">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>

			<div v-if="loading" class="tw-flex tw-flex-1 tw-items-center tw-justify-center">
				<span class="material-symbols-outlined tw-animate-spin tw-text-4xl">progress_activity</span>
			</div>

			<div v-else-if="step === 'selectingFile'" class="tw-flex tw-flex-col tw-gap-4">
				<ImportDropzone
					:uploading="uploading"
					:upload-error="uploadError"
					:entity-term="entityTerm"
					:supported-formats="supportedFormats"
					:selected-file="selectedFile"
					@update-conflict-mode="onUpdateConflictMode"
					@file-selected="handleFile"
					@show-help="showHelp = true"
					@remove-file="selectedFile = null"
				/>

				<!-- Conflict mode selector: drives how existing rows are handled by the pipeline. -->
				<div class="tw-flex tw-flex-col tw-gap-2">
					<p class="tw-m-0 tw-font-medium">
						{{ translate('COM_EMUNDUS_IMPORT_CONFLICT_MODE_LABEL') }}
					</p>
					<div class="tw-flex tw-flex-col tw-gap-2">
						<label
							v-for="mode in displayedConflictModes"
							:key="mode.value"
							:for="'import-conflict-mode-' + mode.value"
							class="tw-mb-0 tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-gap-2 tw-font-normal"
						>
							<input
								:id="'import-conflict-mode-' + mode.value"
								v-model="conflictMode"
								type="radio"
								name="import-conflict-mode"
								:value="mode.value"
								class="!tw-mr-0 !tw-h-fit tw-cursor-pointer"
							/>
							{{ translate(mode.labelKey) }}
						</label>
					</div>
				</div>
				<div class="tw-flex tw-w-full tw-justify-center tw-gap-2">
					<button
						class="tw-btn-primary tw-w-fit tw-gap-1"
						@click="launchDryRun"
						:disabled="uploading || selectedFile === null || uploadError !== null"
					>
						<span class="material-symbols-outlined">manage_search</span>
						{{ translate('COM_EMUNDUS_IMPORT_LAUNCH_DRY_RUN') }}
					</button>
				</div>
			</div>

			<ImportDryRunResult
				v-else-if="step === 'dryrun' && dryRunReport"
				:report="dryRunReport"
				:entity-term="entityTerm"
				@reset="resetToFields"
				@confirm="confirmImport"
				@close="$emit('close')"
			/>

			<div
				v-else-if="step === 'importing'"
				class="tw-flex tw-flex-1 tw-flex-col tw-items-center tw-justify-center tw-gap-3"
			>
				<span class="material-symbols-outlined tw-animate-spin tw-text-4xl">progress_activity</span>
				<p class="tw-m-0">{{ translate('COM_EMUNDUS_IMPORT_RUNNING') }}</p>
			</div>

			<!-- Async: the job runs in the background, we poll its progress. -->
			<div v-else-if="step === 'processing'" class="tw-flex tw-flex-1 tw-flex-col tw-gap-4">
				<template v-if="pollError">
					<div class="tw-rounded tw-bg-red-50 tw-p-3">
						<p class="tw-m-0 tw-text-sm tw-text-red-700">{{ translate('COM_EMUNDUS_IMPORT_PROGRESS_LOST') }}</p>
					</div>
					<div class="tw-mt-auto tw-flex tw-justify-end tw-pt-4">
						<button type="button" class="tw-btn-primary tw-w-fit" @click="closeAndRefresh">
							{{ translate('COM_EMUNDUS_IMPORT_STATUS_FINISH') }}
						</button>
					</div>
				</template>

				<template v-else>
					<div class="tw-flex tw-items-center tw-gap-3">
						<span class="material-symbols-outlined tw-animate-spin tw-text-2xl">progress_activity</span>
						<p class="tw-m-0 tw-font-medium">{{ translate('COM_EMUNDUS_IMPORT_PROCESSING') }}</p>
					</div>

					<div class="tw-flex tw-flex-col tw-gap-1">
						<div class="tw-h-3 tw-w-full tw-overflow-hidden tw-rounded-full tw-bg-neutral-200">
							<div
								class="tw-h-full tw-bg-main-500 tw-transition-all tw-duration-500"
								:style="{ width: progress + '%' }"
							></div>
						</div>
						<p class="tw-m-0 tw-text-right tw-text-sm tw-text-profile-full">{{ Math.round(progress) }}%</p>
					</div>

					<ImportSummary v-if="counts" :summary="counts" :dry="false" />

					<div class="tw-mt-auto tw-flex tw-justify-end tw-pt-4">
						<button type="button" class="tw-btn-secondary tw-w-fit" @click="cancelImport">
							{{ translate('COM_EMUNDUS_IMPORT_CANCEL') }}
						</button>
					</div>
				</template>
			</div>

			<ImportDone
				v-else-if="step === 'done' && importReport"
				:report="importReport"
				:entity-term="entityTerm"
				@close="closeAndRefresh"
				@import-another="importAnother"
				@reset="resetToFields"
			/>
		</template>
	</div>
</template>

<style scoped></style>
