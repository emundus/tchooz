<script>
import Info from '@/components/Utils/Info.vue';

export default {
	name: 'ImportFailedRows',
	components: { Info },
	props: {
		rows: {
			type: Array,
			required: true,
		},
		entityTerm: {
			type: String,
			default: '',
		},
	},
	computed: {
		failedTitle() {
			return this.rows.length > 1
				? this.translate('COM_EMUNDUS_IMPORT_SUMMARY_FAILED_SEVERAL_ELEMENTS').replace('%s', this.rows.length)
				: this.translate('COM_EMUNDUS_IMPORT_SUMMARY_FAILED_ONE_ELEMENT');
		},
		failedRowsList() {
			const rowLabel = this.translate('COM_EMUNDUS_IMPORT_ROW_NUMBER');
			const items = this.rows.map(
				(row) => '<li><strong>' + rowLabel + ' ' + row.row + '</strong> : ' + row.reasons.join(' · ') + '</li>',
			);

			return '<ul class="tw-mb-0 tw-list-disc tw-pl-5">' + items.join('') + '</ul>';
		},
	},
};
</script>

<template>
	<Info
		v-if="rows.length > 0"
		:title="failedTitle"
		:accordion="true"
		:default-open="true"
		:text="failedRowsList"
		class="tw-w-full tw-text-left"
		:icon="'cancel'"
		:bg-color="'tw-bg-red-100'"
		:icon-color="'tw-text-red-600'"
	/>
</template>

<style scoped></style>
