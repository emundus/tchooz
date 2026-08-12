<script>
import Button from '@/components/Atoms/Button.vue';
import date from '@/mixins/date.js';
import Back from '@/components/Utils/Back.vue';
import ModalHeader from '@/components/Utils/Modal/Header.vue';

const STATE_OPTIONS = [
	{ value: 'available', label: 'Disponible', icon: 'check_circle', variant: 'success' },
	{ value: 'if_needed', label: 'Si nécessaire', icon: 'do_disturb_on', variant: 'info' },
	{ value: 'not_available', label: 'Non disponible', icon: 'cancel', variant: 'danger' },
];

export default {
	name: 'PollSlotReply',
	components: { ModalHeader, Back, Button },
	mixins: [date],
	emits: ['back', 'save'],
	props: {
		slot: {
			type: Object,
			required: true,
		},
		pollName: {
			type: String,
			default: '',
		},
		state: {
			type: String,
			default: null,
		},
		comment: {
			type: String,
			default: '',
		},
		readonly: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			localState: this.state,
			localComment: this.comment,
			stateOptions: STATE_OPTIONS,
		};
	},
	computed: {
		slotDateLabel() {
			if (!this.slot || !this.slot.start) return '';
			const day = this.formatSlotDay(this.slot.start);
			const range = this.formatSlotTimeRange(this.slot.start, this.slot.end);
			return `${day} ${range}`;
		},
		canValidate() {
			return !this.readonly && !!this.localState;
		},
	},
	methods: {
		toggleState(value) {
			if (this.readonly) return;
			this.localState = this.localState === value ? null : value;
		},
		isActive(value) {
			return this.localState === value;
		},
		validate() {
			if (!this.canValidate) return;
			this.$emit('save', {
				slotId: this.slot.id,
				state: this.localState,
				comment: this.localComment,
			});
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-6">
		<ModalHeader
			:title="translate('COM_EMUNDUS_POLL_REPLY_MODAL_TITLE')"
			:subtitle="this.pollName"
			:show-back="true"
			:show-close="true"
			@back="$emit('back')"
			@close="$emit('close')"
		/>

		<div class="tw-flex tw-flex-col tw-gap-2">
			<div class="tw-flex tw-items-center tw-gap-2">
				<span class="material-symbols-outlined">calendar_month</span>
				<span>{{ slotDateLabel }}</span>
			</div>
		</div>

		<div
			v-if="readonly"
			class="tw-flex tw-items-center tw-gap-2 tw-rounded-lg tw-bg-neutral-100 tw-px-3 tw-py-2 tw-text-neutral-700"
		>
			<span class="material-symbols-outlined">lock</span>
			<span>{{ translate('COM_EMUNDUS_POLL_REPLY_LOCKED_INFO') }}</span>
		</div>

		<div class="tw-flex tw-gap-3">
			<Button
				v-for="option in stateOptions"
				:key="option.value"
				:is-activation-button="true"
				:variant="option.variant"
				:icon="option.icon"
				:active="isActive(option.value)"
				:disabled="readonly"
				class="tw-flex-1"
				@click="toggleState(option.value)"
			>
				{{ option.label }}
			</Button>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-2">
			<label class="tw-font-medium">{{ translate('COM_EMUNDUS_POLL_REPLY_COMMENT') }}</label>
			<textarea
				v-model="localComment"
				rows="5"
				:disabled="readonly"
				class="tw-w-full tw-rounded-lg tw-border tw-border-neutral-300 tw-px-3 tw-py-2 tw-text-sm focus:tw-border-profile-full focus:tw-outline-none disabled:tw-cursor-not-allowed disabled:tw-bg-neutral-100"
				:placeholder="translate('COM_EMUNDUS_POLL_REPLY_COMMENT_PLACEHOLDER')"
			/>
		</div>

		<div v-if="!readonly" class="tw-flex tw-justify-end">
			<Button :disabled="!canValidate" :icon="'check_circle'" @click="validate">
				{{ translate('COM_EMUNDUS_POLL_REPLY_VALIDATE') }}
			</Button>
		</div>
	</div>
</template>
