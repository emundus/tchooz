<script>
import Info from '@/components/Utils/Info.vue';

export default {
	name: 'ImportSummary',
	components: { Info },
	props: {
		summary: {
			type: Object,
			required: true,
		},
		dry: {
			type: Boolean,
			default: true,
		},
		entityTerm: {
			type: String,
			default: '',
		},
	},
	methods: {
		buildMessage(key, count) {
			return this.translate(key).replace('%s', count);
		},
	},
	computed: {
		validMessage() {
			return this.buildMessage('COM_EMUNDUS_IMPORT_SUMMARY_VALID', this.summary.valid ?? 0);
		},
		createdMessage() {
			return this.buildMessage(
				this.dry ? 'COM_EMUNDUS_IMPORT_SUMMARY_TO_BE_CREATED' : 'COM_EMUNDUS_IMPORT_SUMMARY_CREATED',
				this.summary.created,
			);
		},
		updatedMessage() {
			return this.buildMessage(
				this.dry ? 'COM_EMUNDUS_IMPORT_SUMMARY_TO_BE_UPDATED' : 'COM_EMUNDUS_IMPORT_SUMMARY_UPDATED',
				this.summary.updated ?? 0,
			);
		},
		skippedMessage() {
			return this.buildMessage('COM_EMUNDUS_IMPORT_SUMMARY_SKIPPED', this.summary.skipped);
		},
		ignoredMessage() {
			return this.buildMessage('COM_EMUNDUS_IMPORT_SUMMARY_IGNORED', this.summary.ignored ?? 0);
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-3">
		<Info
			v-if="dry && (summary.valid ?? 0) > 0"
			:text="validMessage"
			class="tw-w-full tw-text-left"
			:icon="'check_circle'"
			:icon-color="'tw-text-green-700'"
			:bg-color="'tw-bg-green-100'"
		/>
		<Info
			v-if="summary.created > 0"
			:text="createdMessage"
			class="tw-w-full tw-text-left"
			:icon="'check_circle'"
			:icon-color="'tw-text-green-700'"
			:bg-color="'tw-bg-green-100'"
		/>
		<Info v-if="(summary.updated ?? 0) > 0" :text="updatedMessage" class="tw-w-full tw-text-left" />
		<Info v-if="summary.skipped > 0" :text="skippedMessage" class="tw-w-full tw-text-left" />
		<Info
			v-if="(summary.ignored ?? 0) > 0"
			:text="ignoredMessage"
			class="tw-w-full tw-text-left"
			:icon="'warning'"
			:icon-color="'tw-text-orange-600'"
			:bg-color="'tw-bg-orange-100'"
		/>
	</div>
</template>

<style scoped></style>
