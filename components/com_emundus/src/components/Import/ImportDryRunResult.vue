<script>
import ImportSummary from '@/components/Import/ImportSummary.vue';
import ImportFailedRows from '@/components/Import/ImportFailedRows.vue';
import Info from '@/components/Utils/Info.vue';

export default {
	name: 'ImportDryRunResult',
	components: { Info, ImportSummary, ImportFailedRows },
	emits: ['reset', 'confirm', 'close'],
	props: {
		report: {
			type: Object,
			required: true,
		},
		entityTerm: {
			type: String,
			default: '',
		},
	},
	computed: {
		globalErrors() {
			return this.report?.summary?.global_errors ?? [];
		},
		unknownHeaders() {
			return this.report?.summary?.unknown_headers ?? [];
		},
		hasGlobalErrors() {
			return this.globalErrors.length > 0;
		},
		hasUnknownHeaders() {
			return this.unknownHeaders.length > 0;
		},
		unknownHeadersMessage() {
			return this.translate('COM_EMUNDUS_IMPORT_UNKNOWN_HEADERS_WARNING').replace('%s', this.unknownHeaders.join(', '));
		},
		canConfirm() {
			// A global error means the whole file is rejected — never allow the import.
			if (this.hasGlobalErrors) {
				return false;
			}
			return (this.report.summary?.created ?? 0) > 0 || (this.report.summary?.updated ?? 0) > 0;
		},
		failedRows() {
			return this.report?.rows?.filter((r) => r.status === 'failed') ?? [];
		},
	},
};
</script>

<template>
	<div class="tw-relative tw-flex tw-items-center tw-justify-between">
		<button
			type="button"
			class="tw-inline-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-bg-transparent tw-text-sm"
			@click="$emit('reset')"
		>
			<span class="material-symbols-outlined">arrow_back</span>
			{{ translate('COM_EMUNDUS_ACTIONS_BACK') }}
		</button>
		<h2 class="tw-text-center">
			{{ translate('COM_EMUNDUS_IMPORT_DRY_RESULT_TITLE') }}
		</h2>
		<button class="tw-cursor-pointer tw-bg-transparent" @click="$emit('close')">
			<span class="material-symbols-outlined">close</span>
		</button>
	</div>
	<div class="tw-mt-2 tw-flex tw-flex-1 tw-flex-col tw-gap-4 tw-text-center">
		{{ translate('COM_EMUNDUS_IMPORT_DRY_RESULT') }}

		<!-- Fatal pre-flight errors: red banner, blocks the import -->
		<Info
			v-for="(error, index) in globalErrors"
			:key="'global-error-' + index"
			:text="error"
			class="tw-w-full tw-text-left"
			:icon="'cancel'"
			:bg-color="'tw-bg-red-100'"
			:icon-color="'tw-text-red-600'"
		/>

		<ImportSummary v-if="!hasGlobalErrors" :summary="report.summary" :dry="true" :entity-term="entityTerm" />

		<!-- Hidden when a global error already explains why nothing was created -->
		<Info
			v-if="!canConfirm && !hasGlobalErrors"
			:text="translate('COM_EMUNDUS_IMPORT_NOTHING_TO_CREATE')"
			class="tw-w-full tw-text-left"
			:icon="'cancel'"
			:bg-color="'tw-bg-red-100'"
			:icon-color="'tw-text-red-600'"
		/>

		<!-- Soft warning: some columns were ignored but processing went ahead -->
		<Info
			v-if="hasUnknownHeaders && !hasGlobalErrors"
			:text="unknownHeadersMessage"
			class="tw-w-full tw-text-left"
			:icon="'cancel'"
			:bg-color="'tw-bg-red-100'"
			:icon-color="'tw-text-red-600'"
		/>

		<ImportFailedRows :rows="failedRows" :entity-term="entityTerm" />

		<div class="tw-mt-auto tw-flex tw-items-center tw-justify-center tw-pt-4">
			<button type="button" class="tw-btn-primary tw-w-fit" :disabled="!canConfirm" @click="$emit('confirm')">
				<span class="material-symbols-outlined tw-mr-1">file_download</span>
				{{ translate('COM_EMUNDUS_IMPORT_LAUNCH') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
