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
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-3">
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
	</div>
</template>

<style scoped></style>
