<script>
import importService from '@/services/import.js';
import ImportSummary from '@/components/Import/ImportSummary.vue';
import ImportFailedRows from '@/components/Import/ImportFailedRows.vue';
import Info from '@/components/Utils/Info.vue';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'ImportReport',
	components: { Info, ImportSummary, ImportFailedRows },
	emits: ['close'],
	props: {
		item: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			report: null,
			shortLang: 'fr',
		};
	},
	created() {
		this.shortLang = useGlobalStore().getShortLang;
		importService.getImportReport(this.item.id).then((response) => {
			if (response.status && response.data.report && Object.keys(response.data.report).length > 0) {
				this.report = response.data.report;
			}
			this.loading = false;
		});
	},
	computed: {
		failedRows() {
			return this.report?.failed_rows ?? [];
		},
		failedTruncated() {
			return this.report?.failed_truncated ?? false;
		},
		failedTotal() {
			return this.report?.failed_total ?? 0;
		},
		unknownHeaders() {
			return this.report?.summary?.unknown_headers ?? [];
		},
		hasUnknownHeaders() {
			return this.unknownHeaders.length > 0;
		},
		unknownHeadersMessage() {
			return this.translate('COM_EMUNDUS_IMPORT_UNKNOWN_HEADERS_WARNING').replace('%s', this.unknownHeaders.join(', '));
		},
		hasFailedRows() {
			return this.failedTotal > 0;
		},
	},
	methods: {
		downloadReport() {
			window.open(
				window.location.origin +
					'/index.php?option=com_emundus&controller=import&task=downloadreport&id=' +
					this.item.id,
				'_blank',
			);
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-4">
		<div class="tw-flex tw-items-start tw-justify-between tw-gap-4">
			<div class="tw-flex tw-flex-col">
				<h2 class="tw-mb-0">{{ translate('COM_EMUNDUS_IMPORT_REPORT_TITLE_FOR') }} {{ item.label[shortLang] }}</h2>
			</div>
			<button type="button" class="tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>

		<div v-if="loading" class="tw-flex tw-min-h-[10vh] tw-items-center tw-justify-center">
			<div class="em-loader"></div>
		</div>

		<template v-else-if="report">
			<ImportSummary :summary="report.summary" :dry="false" />

			<Info
				v-if="hasUnknownHeaders"
				:text="unknownHeadersMessage"
				class="tw-w-full tw-text-left"
				:icon="'cancel'"
				:bg-color="'tw-bg-red-100'"
				:icon-color="'tw-text-red-600'"
			/>

			<ImportFailedRows :rows="failedRows" :truncated="failedTruncated" :failed-total="failedTotal" />

			<div v-if="hasFailedRows" class="tw-flex tw-justify-end">
				<button
					type="button"
					class="tw-btn-primary tw-inline-flex tw-w-fit tw-items-center tw-gap-1"
					@click="downloadReport"
				>
					<span class="material-symbols-outlined">download</span>
					{{ translate('COM_EMUNDUS_IMPORT_DOWNLOAD_REPORT') }}
				</button>
			</div>
		</template>

		<p v-else>{{ translate('COM_EMUNDUS_IMPORTS_NO_REPORT') }}</p>
	</div>
</template>

<style scoped></style>
