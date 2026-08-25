<script>
import Info from '@/components/Utils/Info.vue';

export default {
	name: 'ImportFailedRows',
	components: { Info },
	props: {
		rows: {
			type: Array,
			default: () => [],
		},
		failedTotal: {
			type: Number,
			default: 0,
		},
		truncated: {
			type: Boolean,
			default: false,
		},
		entityTerm: {
			type: String,
			default: '',
		},
	},
	computed: {
		total() {
			return this.failedTotal > 0 ? this.failedTotal : this.rows.length;
		},
		hasFailures() {
			return this.total > 0;
		},
		failedTitle() {
			return this.total > 1
				? this.translate('COM_EMUNDUS_IMPORT_SUMMARY_FAILED_SEVERAL_ELEMENTS').replace('%s', this.total)
				: this.translate('COM_EMUNDUS_IMPORT_SUMMARY_FAILED_ONE_ELEMENT');
		},
		groupedErrors() {
			const SAMPLE_LIMIT = 5;
			const groups = new Map();

			this.rows.forEach((row) => {
				(row.errors || []).forEach((error) => {
					const key = error.code + '|' + (error.field ?? '');
					if (!groups.has(key)) {
						groups.set(key, { error, count: 0, sampleRows: [] });
					}
					const group = groups.get(key);
					group.count += 1;
					if (group.sampleRows.length < SAMPLE_LIMIT) {
						group.sampleRows.push(row.row);
					}
				});
			});

			return Array.from(groups.values());
		},
		failedRowsList() {
			const items = this.groupedErrors.map((group) => {
				const message = this.formatError(group.error);
				const hasMore = group.count > group.sampleRows.length;
				const sample = group.sampleRows.join(', ') + (hasMore ? ', …' : '');
				const sampleKey =
					group.count > 1 ? 'COM_EMUNDUS_IMPORT_ERRORS_SAMPLE_ROWS' : 'COM_EMUNDUS_IMPORT_ERRORS_SAMPLE_ROW';
				const sampleText = this.translate(sampleKey).replace('%s', sample);

				return (
					'<li><strong>' +
					group.count +
					'× </strong>' +
					message +
					' <span class="tw-text-sm tw-text-neutral-600">' +
					sampleText +
					'</span></li>'
				);
			});

			let html = '<ul class="tw-mb-0 tw-list-disc tw-pl-5">' + items.join('') + '</ul>';

			if (this.truncated && this.total > this.rows.length) {
				const hidden = this.total - this.rows.length;
				html +=
					'<p class="tw-mb-0 tw-mt-2 tw-text-sm">' +
					this.translate('COM_EMUNDUS_IMPORT_FAILED_TRUNCATED').replace('%s', hidden) +
					'</p>';
			}

			return html;
		},
	},
	methods: {
		// Values come from the imported file and are rendered through v-html, so
		// they must be HTML-escaped before being spliced into the message.
		escapeHtml(value) {
			return String(value)
				.replaceAll('&', '&amp;')
				.replaceAll('<', '&lt;')
				.replaceAll('>', '&gt;')
				.replaceAll('"', '&quot;')
				.replaceAll("'", '&#39;');
		},
		formatError(error) {
			const params = (error.params || []).map((param) => {
				const value = typeof param === 'string' && param.startsWith('COM_EMUNDUS_') ? this.translate(param) : param;
				return this.escapeHtml(value);
			});
			let text = this.translate(error.code);

			params.forEach((param, index) => {
				text = text.replaceAll('%' + (index + 1) + '$s', param);
			});

			params.forEach((param) => {
				text = text.replace('%s', param);
			});

			if (params.length > 1 && params[1] !== '') {
				const example = this.translate('COM_EMUNDUS_IMPORT_ERROR_EXAMPLE_VALUE').replace('%s', params[1]);
				text += ' (' + example + ')';
			}

			return text;
		},
	},
};
</script>

<template>
	<Info
		v-if="hasFailures"
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
