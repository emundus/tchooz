<script>
import ImportSummary from '@/components/Import/ImportSummary.vue';
import ImportFailedRows from '@/components/Import/ImportFailedRows.vue';

export default {
	name: 'ImportDone',
	components: { ImportSummary, ImportFailedRows },
	emits: ['close', 'import-another', 'reset'],
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
		failedRows() {
			return this.report?.rows?.filter((r) => r.status === 'failed') ?? [];
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-1 tw-flex-col tw-gap-4">
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
				{{ translate('COM_EMUNDUS_IMPORT_RESULT') }}
			</h2>
			<button class="tw-cursor-pointer tw-bg-transparent" @click="$emit('close')">
				<span class="material-symbols-outlined">close</span>
			</button>
		</div>
		<p class="tw-mt-2 tw-text-center">{{ translate('COM_EMUNDUS_IMPORT_IMPORT_RESULT') }}</p>

		<ImportSummary :summary="report.summary" :dry="false" :entity-term="entityTerm" />

		<ImportFailedRows :rows="failedRows" :entity-term="entityTerm" />

		<div class="tw-mt-auto tw-flex tw-items-center tw-justify-end tw-pt-4">
			<button type="button" class="tw-btn-primary tw-w-fit" @click="$emit('close')">
				{{ translate('COM_EMUNDUS_IMPORT_STATUS_FINISH') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
