<script>
import importService from '@/services/import.js';
import ImportFieldsHelp from '@/components/Import/ImportFieldsHelp.vue';
import ImportDropzone from '@/components/Import/ImportDropzone.vue';
import ImportDryRunResult from '@/components/Import/ImportDryRunResult.vue';
import ImportDone from '@/components/Import/ImportDone.vue';

export default {
	name: 'ImportEntity',
	components: { ImportFieldsHelp, ImportDropzone, ImportDryRunResult, ImportDone },
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
	methods: {
		getEntityImportInformation() {
			this.loading = true;
			importService.getEntityImportInformation(this.tab).then((response) => {
				this.fields = response.data.fields || [];

				if (Array.isArray(response.data.formatsSupported) && response.data.formatsSupported.length) {
					this.supportedFormats = response.data.formatsSupported;
				}

				if (response.data.rules && response.data.rules.conflictModesSupported) {
					this.conflictModes.forEach((mode) => {
						mode.displayed = response.data.rules.conflictModesSupported.includes(mode.value);
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
			this.step = 'selectingFile';
			this.dryRunReport = null;
			this.importReport = null;
			this.selectedFile = null;
			this.uploadError = null;
		},
		async confirmImport() {
			this.step = 'importing';

			const response = await importService.importFile(this.tab, this.selectedFile, this.conflictMode);

			if (response?.status === false) {
				this.uploadError = response.msg || this.translate('COM_EMUNDUS_IMPORT_UPLOAD_ERROR');
				this.step = 'dryrun';
				return;
			}

			this.importReport = response.data ?? response;
			this.step = 'done';
		},
		closeAndRefresh() {
			this.$emit('update-items');
			this.$emit('close');
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
