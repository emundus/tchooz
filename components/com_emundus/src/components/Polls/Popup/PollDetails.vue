<script>
import Modal from '@/components/Modal.vue';
import Chip from '@/components/Atoms/Chip.vue';
import GridDetails from '@/components/Molecules/GridDetails.vue';
import CountryFlag from '@/components/Atoms/CountryFlag.vue';
import Avatar from '@/components/Atoms/Avatar.vue';
import { useGlobalStore } from '@/stores/global.js';
import { StatusIcon, Tag, Icon } from '@emundus/ui';
import PollSlotDetails from '@/components/Polls/Popup/PollSlotDetails.vue';
import ModalHeader from '@/components/Utils/Modal/Header.vue';
import date from '@/mixins/date.js';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'PollDetails',
	components: { Icon, ModalHeader, Tag, Avatar, CountryFlag, GridDetails, Chip, Modal, StatusIcon, PollSlotDetails },
	mixins: [date, alerts],
	props: {
		item: Object,
	},
	emits: ['close', 'open', 'edit-slot'],
	data() {
		return {
			activeSlotId: null,
			transitionName: 'slide-next',
		};
	},
	created() {
		if (!this.$props.item) {
			this.closeModal();
		}

		this.shortLang = useGlobalStore().getShortLang;
	},
	computed: {
		activeSlot() {
			if (!this.activeSlotId || !this.$props.item?.slots) return null;
			return this.$props.item.slots.find((slot) => slot.id === this.activeSlotId) || null;
		},
		pollName() {
			if (!this.$props.item) return '';
			if (this.$props.item.name) return this.$props.item.name;
			if (this.$props.item.label && this.shortLang) return this.$props.item.label[this.shortLang];
			return '';
		},
		showNeededParticipants() {
			if (!this.$props.item?.slots) return false;
			return this.$props.item.slots.some((slot) => {
				return slot.capacity > 0;
			});
		},
	},
	methods: {
		beforeClose() {
			this.$emit('close');
		},
		beforeOpen() {
			this.$emit('open');
		},
		closeModal() {
			this.$emit('close');
		},

		openSlotDetails(slotId) {
			this.transitionName = 'slide-next';
			this.activeSlotId = slotId;
		},
		closeSlotDetails() {
			this.transitionName = 'slide-prev';
			this.activeSlotId = null;
		},
		onSlotEdit(slotId) {
			this.$emit('edit-slot', slotId);
		},

		tagText(slot) {
			return `${this.availableCount(slot)}/${slot.capacity} ${this.translate('COM_EMUNDUS_POLL_SLOT_CAPACITY_NEEDED')}`;
		},
		tagVariant(slot) {
			return this.isCapacityReached(slot) ? 'primary' : 'danger';
		},

		isCapacityReached(slot) {
			return slot.capacity > 0 && this.availableCount(slot) >= slot.capacity;
		},
		availableCount(slot) {
			return slot.answers.filter((answer) => answer.answer === 'available').length;
		},

		filterAnswers(state, slot) {
			var length = slot.answers.filter((answer) => answer.answer === state).length;
			if (length > 0) {
				return length;
			}
			return '-';
		},
	},
};
</script>

<template>
	<div class="tw-relative tw-overflow-hidden">
		<Transition :name="transitionName" mode="out-in">
			<PollSlotDetails
				v-if="activeSlot"
				key="slot-details"
				:slot="activeSlot"
				:poll-name="pollName"
				:poll-id="item.id"
				:participants="item.participants || []"
				@back="closeSlotDetails"
				@close="closeModal"
				@edit="onSlotEdit"
			/>

			<div v-else key="slot-list">
				<ModalHeader
					:title="translate('COM_EMUNDUS_POLL_REPLY_MODAL_TITLE')"
					:subtitle="this.$props.item.name"
					@close="closeModal"
				/>

				<div>
					<table class="tw-border-separate" id="poll-slots-table">
						<thead>
							<tr>
								<td>{{ translate('COM_EMUNDUS_POLLS_SLOT_DATES') }}</td>
								<td v-if="showNeededParticipants">{{ translate('COM_EMUNDUS_POLL_FIELD_NEEDED_PARTICIPANTS') }}</td>
								<td>{{ translate('COM_EMUNDUS_POLL_RESULTS') }}</td>
								<td></td>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="slot in item.slots"
								:key="slot.id"
								class="tw-group/item-row table-row tw-cursor-pointer tw-rounded-coordinator-cards tw-border"
								@click="openSlotDetails(slot.id)"
							>
								<td class="tw-rounded-s-coordinator-cards tw-bg-white group-hover/item-row:tw-bg-neutral-300">
									<div class="tw-flex tw-flex-col tw-gap-1">
										<div class="tw-flex tw-items-center tw-gap-1">
											<Icon name="calendar_month" />
											<span>{{ formatSlotDay(slot.start) }}</span>
										</div>
										<div class="tw-flex tw-items-center tw-gap-1">
											<Icon name="schedule" />
											<span>{{ formatSlotTimeRange(slot.start, slot.end) }}</span>
										</div>
									</div>
								</td>
								<td v-if="showNeededParticipants" class="tw-bg-white group-hover/item-row:tw-bg-neutral-300">
									<div class="tw-flex tw-items-center tw-gap-2" v-if="slot.capacity">
										<Tag :label="tagText(slot)" :variant="tagVariant(slot)" />
									</div>
									<span v-else> - </span>
								</td>

								<td class="tw-rounded-e-coordinator-cards tw-bg-white group-hover/item-row:tw-bg-neutral-300">
									<div class="tw-flex tw-items-center tw-gap-2" v-if="slot.answers">
										<div class="tw-flex tw-items-center tw-gap-1">
											<StatusIcon state="correct" />
											<span class="tw-text-success-300">
												{{ filterAnswers('available', slot) }}
											</span>
										</div>
										<div class="tw-flex tw-items-center tw-gap-1">
											<StatusIcon state="neutral" />
											<span class="tw-text-info-300">
												{{ filterAnswers('if_needed', slot) }}
											</span>
										</div>
										<div class="tw-flex tw-items-center tw-gap-1">
											<StatusIcon state="wrong" />
											<span class="tw-text-error-300">
												{{ filterAnswers('not_available', slot) }}
											</span>
										</div>
										<div class="tw-flex tw-items-center tw-gap-1">
											<StatusIcon state="empty" />
											<span class="tw-text-neutral-500">
												{{ filterAnswers('not_answered', slot) }}
											</span>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</Transition>
	</div>
</template>

<style scoped>
#poll-slots-table {
	transition: all 0.3s;
	border: 0;
	border-spacing: 0 3px;

	input[type='checkbox'] {
		appearance: none;
		width: 20px;
		height: 20px;
		border: 2px solid #333;
		border-radius: 4px;
		background-color: white;
		cursor: pointer;
		position: relative;
		display: block;
		padding: 0;
		margin-right: 0;
	}

	input[type='checkbox']:checked {
		background-color: var(--em-profile-color);
		border-color: var(--em-profile-color);
	}

	input[type='checkbox']:checked::before {
		content: '✓';
		color: white;
		font-size: 16px;
		font-weight: bold;
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	thead th {
		background-color: transparent !important;
		border: unset !important;
		box-shadow: unset;
		font-weight: bold;
	}
}
</style>

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
