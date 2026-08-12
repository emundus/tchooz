<script>
import PollSlotContact from '@/components/Polls/Popup/PollSlotContact.vue';
import ModalHeader from '@/components/Utils/Modal/Header.vue';
import date from '@/mixins/date.js';
import alerts from '@/mixins/alerts.js';
import { ExpansionPanel, StatusIcon, Chip, Button, Tag, Alert, Icon } from '@emundus/ui';
import pollService from '@/services/poll.js';

const GROUP_DEFINITIONS = [
	{
		key: 'available',
		label: 'COM_EMUNDUS_POLL_REPLY_AVAILABLE',
		variant: 'correct',
		labelClass: 'tw-text-success-200',
		dividerClass: 'tw-bg-success-100',
	},
	{
		key: 'if_needed',
		label: 'COM_EMUNDUS_POLL_REPLY_IF_NEEDED',
		variant: 'neutral',
		labelClass: 'tw-text-info-300',
		dividerClass: 'tw-bg-info-100',
	},
	{
		key: 'not_available',
		label: 'COM_EMUNDUS_POLL_REPLY_UNAVAILABLE',
		variant: 'wrong',
		labelClass: 'tw-text-error-300',
		dividerClass: 'tw-bg-error-100',
	},
	{
		key: 'not_answered',
		label: 'COM_EMUNDUS_POLL_REPLY_STATE_NO_ANSWER',
		labelClass: 'tw-text-neutral-500',
		dividerClass: 'tw-bg-neutral-300',
	},
];

export default {
	name: 'PollSlotDetails',
	components: { Icon, Alert, ExpansionPanel, Button, ModalHeader, Tag, Chip, StatusIcon, PollSlotContact },
	mixins: [date, alerts],
	emits: ['back', 'edit', 'resend', 'send'],
	props: {
		slot: {
			type: Object,
			required: true,
		},
		pollName: {
			type: String,
			default: '',
		},
		pollId: {
			type: Number,
			default: 0,
		},
		participants: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			participantsOpen: false,
			groupDefinitions: GROUP_DEFINITIONS,
			contactOpen: false,
			contactRecipients: [],
			openComments: [],
			transitionName: 'slide-next',

			message: '',
			displayedToast: false,
			toastTimer: null,
		};
	},
	beforeUnmount() {
		if (this.toastTimer) {
			clearTimeout(this.toastTimer);
			this.toastTimer = null;
		}
	},
	computed: {
		answers() {
			return this.slot.answers || [];
		},
		availableCount() {
			return this.answers.filter((answer) => answer.answer === 'available').length;
		},
		capacity() {
			return this.slot.capacity ?? this.slot.slot_capacity ?? 0;
		},
		isCapacityReached() {
			return this.capacity > 0 && this.availableCount >= this.capacity;
		},
		tagText() {
			return `${this.availableCount}/${this.capacity} ${this.translate('COM_EMUNDUS_POLL_SLOT_CAPACITY_NEEDED')}`;
		},
		tagVariant() {
			return this.isCapacityReached ? 'primary' : 'danger';
		},
		slotDateLabel() {
			if (!this.slot || !this.slot.start) return '';
			const day = this.formatSlotDay(this.slot.start);
			const range = this.formatSlotTimeRange(this.slot.start, this.slot.end);
			return `${day} ${range}`;
		},
		groupedParticipants() {
			const groups = {
				available: [],
				if_needed: [],
				not_available: [],
				not_answered: [],
			};

			this.participants.forEach((participant) => {
				const answer = this.answers.find((a) => a.participant === participant.id);
				const state = answer && answer.answer ? answer.answer : 'not_answered';
				const bucket = groups[state] !== undefined ? state : 'not_answered';
				groups[bucket].push({
					...participant,
					comment: answer ? answer.comment : '',
				});
			});

			return groups;
		},
	},
	methods: {
		toggleParticipants() {
			this.participantsOpen = !this.participantsOpen;
		},
		fullName(participant) {
			return `${participant.firstname || ''} ${participant.lastname || ''}`.trim() || participant.email;
		},
		commentKey(groupKey, participant) {
			return `${groupKey}-${participant.id}`;
		},
		hasComment(participant) {
			return !!(participant.comment && participant.comment.trim());
		},
		isCommentOpen(key) {
			return this.openComments.includes(key);
		},
		toggleComment(key) {
			this.openComments = this.isCommentOpen(key)
				? this.openComments.filter((openKey) => openKey !== key)
				: [...this.openComments, key];
		},
		groupForKey(key) {
			return this.groupedParticipants[key] || [];
		},
		openContact(recipients) {
			this.contactRecipients = recipients;
			this.transitionName = 'slide-next';
			this.contactOpen = true;
		},
		closeContact() {
			this.transitionName = 'slide-prev';
			this.contactOpen = false;
		},
		resendOne(participantId) {
			const participant = this.groupForKey('not_answered').find((p) => p.id === participantId);
			this.$emit('resend', participantId);
			this.openContact(participant ? [participant] : []);
		},
		resendAll() {
			this.openContact(this.groupForKey('not_answered'));
		},
		async handleSend(payload) {
			try {
				payload.id = this.pollId;
				const response = await pollService.contactParticipants(payload);
				if (response && response.status === true) {
					this.displayToast('COM_EMUNDUS_POLL_RESEND_SUCCESS');
				} else {
					this.displayToast(response?.message || response?.msg || 'COM_EMUNDUS_POLL_RUN_NO_IDS');
				}

				this.closeContact();
			} finally {
				this.isRunning = false;
			}
		},

		displayToast(message) {
			this.message = this.translate(message);

			this.displayedToast = true;

			if (this.toastTimer) {
				clearTimeout(this.toastTimer);
			}
			this.toastTimer = setTimeout(() => {
				this.displayedToast = false;
				this.toastTimer = null;
			}, 3000);
		},
	},
};
</script>

<template>
	<div class="tw-relative tw-overflow-hidden">
		<Transition :name="transitionName" mode="out-in">
			<PollSlotContact
				v-if="contactOpen"
				key="slot-contact"
				:recipients="contactRecipients"
				:display-reply-to="true"
				:default-body="translate('COM_EMUNDUS_POLL_NOTIFICATION_BODY', { poll: pollName })"
				:default-subject="translate('COM_EMUNDUS_POLL_NOTIFICATION_SUBJECT', { poll: pollName })"
				@back="closeContact"
				@close="$emit('close')"
				@send="handleSend"
			/>

			<div v-else key="slot-details" class="tw-flex tw-flex-col tw-gap-6 tw-p-2">
				<ModalHeader
					:title="translate('COM_EMUNDUS_POLL_SLOT_DETAILS_TITLE')"
					:show-back="true"
					:show-close="true"
					@back="$emit('back')"
					@close="$emit('close')"
				/>

				<div class="tw-flex tw-flex-col tw-gap-1">
					<div class="tw-flex tw-items-center tw-gap-2">
						<Icon name="calendar_month" />
						<span class="tw-font-medium">{{ slotDateLabel }}</span>
					</div>
					<div class="tw-flex tw-items-center tw-gap-2" v-if="slot.location_text">
						<Icon name="location_on" />
						<span class="tw-font-medium">{{ slot.location_text }}</span>
					</div>
					<div class="tw-flex tw-items-center tw-gap-2" v-if="pollName">
						<Icon name="ballot" />
						<span class="tw-font-medium"> {{ translate('COM_EMUNDUS_POLL_REPLY_POLL') }} : {{ pollName }} </span>
					</div>
				</div>

				<Alert v-if="displayedToast" :closable="false" :expandable="false" role="status" state="info" :text="message" />

				<ExpansionPanel>
					<template #title>
						<div class="tw-flex tw-items-center tw-justify-between">
							<span>
								{{ translate('COM_EMUNDUS_POLL_SLOT_DETAILS_PARTICIPANTS') }}
							</span>
							<Tag v-if="this.capacity > 0" :label="tagText" :variant="tagVariant" />
						</div>
					</template>

					<div class="tw-flex tw-flex-col tw-gap-3">
						<template v-for="group in groupDefinitions" :key="group.key">
							<template v-if="groupForKey(group.key).length > 0">
								<div class="tw-flex tw-items-center tw-gap-2">
									<StatusIcon v-if="group.variant" :state="group.variant" />
									<span class="tw-whitespace-nowrap tw-text-base tw-font-medium" :class="group.labelClass">
										{{ translate(group.label) }}
									</span>
									<div class="tw-h-px tw-flex-1" :class="group.dividerClass" />
									<Button v-if="group.key === 'not_answered'" emphasis="ghost" @click.prevent="resendAll">
										<template #leading>
											<span class="material-symbols-outlined tw-text-primary-300">forward_to_inbox</span>
										</template>
										{{ translate('COM_EMUNDUS_POLL_SLOT_DETAILS_RESEND_ALL') }}
									</Button>
								</div>
								<div
									v-for="participant in groupForKey(group.key)"
									:key="`${group.key}-${participant.id}`"
									class="tw-flex tw-flex-col tw-gap-2"
								>
									<div class="tw-flex tw-items-center tw-justify-between">
										<Chip :label="fullName(participant)" variant="neutral" />
										<Button
											v-if="group.key === 'not_answered'"
											emphasis="ghost"
											@click.prevent="resendOne(participant.id)"
										>
											<template #leading>
												<span class="material-symbols-outlined tw-text-primary-300">forward_to_inbox</span>
											</template>
											{{ translate('COM_EMUNDUS_POLL_SLOT_DETAILS_RESEND') }}
										</Button>
										<Button
											v-else-if="hasComment(participant)"
											emphasis="activation"
											:aria-expanded="isCommentOpen(commentKey(group.key, participant))"
											@click.prevent="toggleComment(commentKey(group.key, participant))"
										>
											<template #leading>
												<span class="material-symbols-outlined tw-text-primary-300">mark_unread_chat_alt</span>
											</template>
											{{ translate('COM_EMUNDUS_POLL_SLOT_DETAILS_COMMENT') }}
										</Button>
									</div>
									<div
										v-if="hasComment(participant) && isCommentOpen(commentKey(group.key, participant))"
										class="tw-rounded-lg tw-bg-neutral-100 tw-px-3 tw-py-2 tw-text-base tw-text-neutral-700"
									>
										{{ participant.comment }}
									</div>
								</div>
							</template>
						</template>
					</div>
				</ExpansionPanel>
			</div>
		</Transition>
	</div>
</template>

<style>
.slide-next-enter-active,
.slide-next-leave-active,
.slide-prev-enter-active,
.slide-prev-leave-active {
	transition:
		transform 0.2s ease-in-out,
		opacity 0.2s ease-in-out;
}

.slide-next-enter-from {
	transform: translateX(100%);
	opacity: 0;
}
.slide-next-leave-to {
	transform: translateX(-100%);
	opacity: 0;
}

.slide-prev-enter-from {
	transform: translateX(-100%);
	opacity: 0;
}
.slide-prev-leave-to {
	transform: translateX(100%);
	opacity: 0;
}
</style>
