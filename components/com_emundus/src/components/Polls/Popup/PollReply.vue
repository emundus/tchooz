<script>
import { Alert, Button, Icon, Tag } from '@emundus/ui';
import Parameter from '@/components/Utils/Parameter.vue';
import Modal from '@/components/Modal.vue';
import date from '@/mixins/date.js';
import alerts from '@/mixins/alerts.js';
import { useGlobalStore } from '@/stores/global.js';
import pollService from '@/services/poll.js';
import ModalHeader from '@/components/Utils/Modal/Header.vue';
import PollSlotReply from '@/components/Polls/Popup/PollSlotReply.vue';

const STATE_OPTIONS = [
	{
		value: 'available',
		label: 'COM_EMUNDUS_POLL_REPLY_AVAILABLE',
		icon: 'check_circle',
		variant: 'success',
		buttonVariant: 'success',
	},
	{
		value: 'if_needed',
		label: 'COM_EMUNDUS_POLL_REPLY_IF_NEEDED',
		icon: 'do_disturb_on',
		variant: 'info',
		buttonVariant: 'info',
	},
	{
		value: 'not_available',
		label: 'COM_EMUNDUS_POLL_REPLY_UNAVAILABLE',
		icon: 'cancel',
		variant: 'danger',
		buttonVariant: 'danger',
	},
];

export default {
	name: 'PollReply',
	emits: ['close', 'open'],
	components: { ModalHeader, Alert, Icon, Tag, Button, Parameter, Modal, PollSlotReply },
	mixins: [date, alerts],
	props: {
		item: Object,
	},
	data() {
		return {
			checkedSlots: [],

			activeSlotId: null,
			replySlots: [],
			slotStates: {},
			slotComments: {},
			answeredSlotIds: [],
			transitionName: 'slide-next',
			bulkState: null,
			bulkComment: '',
			selectionMenuOpen: false,
			stateOptions: STATE_OPTIONS,
		};
	},
	computed: {
		activeSlot() {
			if (!this.activeSlotId || !this.$props.item?.slots) return null;
			return this.$props.item.slots.find((slot) => slot.id === this.activeSlotId) || null;
		},
		canEditAnswers() {
			const value = this.$props.item?.can_edit_answers;
			return value === 1 || value === true || value === '1';
		},
		selectableSlotIds() {
			const slots = this.$props.item?.slots || [];
			return slots.filter((slot) => !this.isSlotLocked(slot.id)).map((slot) => slot.id);
		},
		allSlotsChecked() {
			return this.selectableSlotIds.length > 0 && this.checkedSlots.length === this.selectableSlotIds.length;
		},
		someSlotsChecked() {
			return this.checkedSlots.length > 0 && !this.allSlotsChecked;
		},
		allSlotsAnswered() {
			return this.answeredSlotIds.length === this.item.slots.length;
		},
		alertText() {
			return this.allSlotsAnswered
				? this.translate('COM_EMUNDUS_POLL_ANSWERS_SENDED_LOCKED')
				: this.translate('COM_EMUNDUS_POLL_ANSWERS_HELP');
		},
	},
	created() {
		if (!this.$props.item) {
			this.closeModal();
			return;
		}
		this.initExistingAnswers();
	},
	methods: {
		initExistingAnswers() {
			const myAnswers = this.$props.item?.my_answers || {};
			const states = {};
			const comments = {};
			const answered = [];

			Object.entries(myAnswers).forEach(([slotId, data]) => {
				const id = Number(slotId);
				if (!data || !data.answer) return;

				states[id] = data.answer;
				comments[id] = data.comment || '';
				answered.push(id);
			});

			this.slotStates = states;
			this.slotComments = comments;
			this.answeredSlotIds = answered;
		},
		isSlotLocked(slotId) {
			return !this.canEditAnswers && this.answeredSlotIds.includes(slotId);
		},
		beforeClose() {
			this.$emit('close');
		},
		beforeOpen() {
			this.$emit('open');
		},
		closeModal() {
			this.$emit('update-items');
			this.$emit('close');
		},

		openSlotReply(slotId) {
			this.transitionName = 'slide-next';
			this.activeSlotId = slotId;
		},
		closeSlotReply() {
			this.transitionName = 'slide-prev';
			this.activeSlotId = null;
		},
		handleSlotReplySave({ slotId, state, comment }) {
			this.slotStates = {
				...this.slotStates,
				[slotId]: state,
			};
			this.slotComments = {
				...this.slotComments,
				[slotId]: comment,
			};
			this.closeSlotReply();
		},

		toggleSelectAll(event) {
			this.checkedSlots = event.target.checked ? [...this.selectableSlotIds] : [];
		},
		toggleSlotSelection(slotId) {
			if (this.isSlotLocked(slotId)) return;

			const index = this.checkedSlots.indexOf(slotId);
			if (index === -1) {
				this.checkedSlots = [...this.checkedSlots, slotId];
			} else {
				this.checkedSlots = this.checkedSlots.filter((id) => id !== slotId);
			}
		},
		applyBulk() {
			if (!this.bulkState || this.checkedSlots.length === 0) return;

			const nextStates = { ...this.slotStates };
			const nextComments = { ...this.slotComments };
			const comment = this.bulkComment;

			this.checkedSlots
				.filter((slotId) => !this.isSlotLocked(slotId))
				.forEach((slotId) => {
					nextStates[slotId] = this.bulkState;
					if (comment) {
						nextComments[slotId] = comment;
					}
				});

			this.slotStates = nextStates;
			this.slotComments = nextComments;
			this.bulkState = null;
			this.bulkComment = '';
			this.checkedSlots = [];
		},
		toggleSelectionMenu() {
			this.selectionMenuOpen = !this.selectionMenuOpen;
		},
		selectAllSlots() {
			this.checkedSlots = [...this.selectableSlotIds];
			this.selectionMenuOpen = false;
		},
		clearSelection() {
			this.checkedSlots = [];
			this.selectionMenuOpen = false;
		},

		async sendSlotsState() {
			const answers = Object.entries(this.slotStates)
				.filter(([slot, answer]) => !!answer && !this.isSlotLocked(Number(slot)))
				.map(([slot, answer]) => ({
					slot: Number(slot),
					answer,
					comment: this.slotComments[slot] || '',
				}));

			if (answers.length === 0) {
				await this.alertInfo(this.translate('COM_EMUNDUS_POLL_REPLY_NO_ANSWER'));
				return;
			}

			const response = await pollService.savePollAnswers({
				poll_id: this.$props.item.id,
				answers,
			});

			if (response?.status) {
				this.closeModal();
			} else {
				await this.alertError(response?.msg || this.translate('COM_EMUNDUS_POLL_REPLY_SAVE_ERROR'));
			}
		},

		tagVariant(state) {
			if (!state) {
				return 'neutral';
			} else {
				// Search state in STATE_OPTIONS
				const stateOption = STATE_OPTIONS.find((option) => option.value === state);
				if (stateOption) {
					return stateOption.variant;
				}
			}
		},
		tagText(state) {
			if (!state) {
				return this.translate('COM_EMUNDUS_POLL_REPLY_STATE_NO_ANSWER');
			} else {
				// Search state in STATE_OPTIONS
				const stateOption = STATE_OPTIONS.find((option) => option.value === state);
				if (stateOption) {
					return this.translate(stateOption.label);
				}
			}
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-relative tw-overflow-hidden">
			<Transition :name="transitionName" mode="out-in">
				<PollSlotReply
					v-if="activeSlot"
					key="slot-reply"
					:slot="activeSlot"
					:poll-name="$props.item.name"
					:state="slotStates[activeSlot.id] || null"
					:comment="slotComments[activeSlot.id] || ''"
					:readonly="isSlotLocked(activeSlot.id)"
					@back="closeSlotReply"
					@close="closeModal"
					@save="handleSlotReplySave"
				/>

				<div key="slot-list">
					<ModalHeader
						:title="translate('COM_EMUNDUS_POLL_REPLY_MODAL_TITLE')"
						:subtitle="this.$props.item.name"
						@close="closeModal"
					/>

					<div class="tw-flex tw-flex-col tw-gap-2">
						<div v-if="item.description" v-html="item.description" />
					</div>

					<hr />

					<Alert state="info" v-if="checkedSlots.length <= 0">
						{{ alertText }}
					</Alert>

					<!-- Bulk action bar -->
					<div class="tw-mb-3 tw-flex tw-items-end tw-gap-2" v-if="checkedSlots.length > 0">
						<div class="tw-flex tw-shrink-0 tw-flex-col tw-gap-2">
							<label for="bulk-state-select" class="tw-text-base tw-font-medium">
								{{ translate('COM_EMUNDUS_POLL_REPLY_BULK_STATE_LABEL') }}
							</label>
							<select
								id="bulk-state-select"
								v-model="bulkState"
								class="tw-h-10 tw-w-full tw-rounded-lg tw-border tw-bg-white tw-px-3 tw-py-2 tw-text-base tw-font-medium focus:tw-border-profile-full focus:tw-outline-none"
								:class="bulkState ? 'tw-text-neutral-900' : 'tw-text-neutral-500'"
							>
								<option :value="null" disabled>
									{{ translate('COM_EMUNDUS_POLL_REPLY_BULK_STATE_PLACEHOLDER') }}
								</option>
								<option v-for="option in stateOptions" :key="option.value" :value="option.value">
									{{ translate(option.label) }}
								</option>
							</select>
						</div>

						<div class="tw-flex tw-min-w-0 tw-flex-1 tw-flex-col tw-gap-2">
							<label for="bulk-comment-input" class="tw-text-base tw-font-medium">
								{{ translate('COM_EMUNDUS_POLL_REPLY_BULK_COMMENT_LABEL') }}
							</label>
							<input
								id="bulk-comment-input"
								v-model="bulkComment"
								type="text"
								class="tw-h-10 tw-w-full tw-rounded-lg tw-border tw-border-neutral-100 tw-bg-white tw-px-3 tw-py-2 tw-text-base tw-font-medium placeholder:tw-text-neutral-500 focus:tw-border-profile-full focus:tw-outline-none"
								:placeholder="translate('COM_EMUNDUS_POLL_REPLY_BULK_COMMENT_PLACEHOLDER')"
							/>
						</div>

						<div class="tw-relative">
							<Button emphasis="lite" @click.prevent="toggleSelectionMenu">
								<template #leading>
									<Icon name="library_add_check" />
								</template>
								<span class="tw-whitespace-nowrap">
									{{ translate('COM_EMUNDUS_POLL_REPLY_SELECTION') }} ({{ checkedSlots.length }})
								</span>
								<template #trailing>
									<Icon name="expand_more" />
								</template>
							</Button>

							<div
								v-if="selectionMenuOpen"
								class="tw-absolute tw-right-0 tw-top-full tw-z-10 tw-mt-1 tw-flex tw-min-w-[200px] tw-flex-col tw-rounded-lg tw-border tw-border-neutral-100 tw-bg-white tw-py-1 tw-shadow-md"
							>
								<button
									type="button"
									class="tw-cursor-pointer tw-bg-transparent tw-px-3 tw-py-2 tw-text-left hover:tw-bg-neutral-100"
									@click.prevent="selectAllSlots"
								>
									{{ translate('COM_EMUNDUS_POLL_REPLY_SELECT_ALL') }}
								</button>
								<button
									type="button"
									class="tw-cursor-pointer tw-bg-transparent tw-px-3 tw-py-2 tw-text-left hover:tw-bg-neutral-100"
									@click.prevent="clearSelection"
								>
									{{ translate('COM_EMUNDUS_POLL_REPLY_DESELECT_ALL') }}
								</button>
							</div>
						</div>

						<Button :disabled="!bulkState || checkedSlots.length === 0" @click="applyBulk">
							<template #leading>
								<Icon name="check_circle" />
							</template>
							{{ translate('COM_EMUNDUS_POLL_REPLY_SAVE') }}
						</Button>
					</div>

					<!-- Slots list -->
					<div>
						<table class="tw-border-separate" id="poll-slots-table">
							<thead>
								<tr>
									<td class="tw-p-4">
										<input
											v-show="!allSlotsAnswered"
											id="select-all-slots"
											class="item-check"
											type="checkbox"
											:checked="allSlotsChecked"
											:indeterminate.prop="someSlotsChecked"
											@change="toggleSelectAll"
										/>
									</td>
									<td>{{ translate('COM_EMUNDUS_POLLS_SLOT_DATES') }}</td>
									<td>{{ translate('COM_EMUNDUS_POLL_FIELD_LOCATION_LABEL') }}</td>
									<td>{{ translate('COM_EMUNDUS_POLL_REPLY_STATE') }}</td>
								</tr>
							</thead>
							<tbody>
								<tr
									v-for="slot in item.slots"
									:key="slot.id"
									class="tw-group/item-row table-row tw-rounded-coordinator-cards tw-border"
									:class="{ 'tw-cursor-pointer': !isSlotLocked(slot.id) }"
									@click="toggleSlotSelection(slot.id)"
								>
									<td
										class="tw-rounded-s-coordinator-cards tw-p-4"
										:class="{
											'tw-bg-main-50': checkedSlots.includes(slot.id),
											'tw-bg-white group-hover/item-row:tw-bg-neutral-100': !checkedSlots.includes(slot.id),
										}"
									>
										<input
											v-if="!isSlotLocked(slot.id)"
											:id="'slot-' + slot.id"
											v-model="checkedSlots"
											:value="slot.id"
											class="item-check"
											type="checkbox"
											@click.stop
										/>
										<Icon v-else name="lock" />
									</td>
									<td
										:class="{
											'tw-bg-main-50': checkedSlots.includes(slot.id),
											'tw-bg-white group-hover/item-row:tw-bg-neutral-100': !checkedSlots.includes(slot.id),
										}"
									>
										<div class="tw-flex tw-flex-col tw-gap-1">
											<div class="tw-flex tw-items-center tw-gap-1">
												<span class="material-symbols-outlined">calendar_month</span>
												<span>{{ formatSlotDay(slot.start) }}</span>
											</div>
											<div class="tw-flex tw-items-center tw-gap-1">
												<span class="material-symbols-outlined">schedule</span>
												<span>{{ formatSlotTimeRange(slot.start, slot.end) }}</span>
											</div>
										</div>
									</td>
									<td
										class="tw-p-4"
										:class="{
											'tw-bg-main-50': checkedSlots.includes(slot.id),
											'tw-bg-white group-hover/item-row:tw-bg-neutral-100': !checkedSlots.includes(slot.id),
										}"
									>
										<div class="tw-flex tw-items-center tw-gap-2" v-if="slot.location_text">
											<span class="material-symbols-outlined">location_on</span>
											<span>{{ slot.location_text }}</span>
										</div>
										<span v-else> - </span>
									</td>
									<td
										:class="{
											'tw-bg-main-50': checkedSlots.includes(slot.id),
											'tw-bg-white group-hover/item-row:tw-bg-neutral-100': !checkedSlots.includes(slot.id),
										}"
									>
										<Tag :variant="tagVariant(this.slotStates[slot.id])">
											{{ tagText(this.slotStates[slot.id]) }}
										</Tag>
									</td>
									<td
										class="tw-rounded-e-coordinator-cards"
										:class="{
											'tw-bg-main-50': checkedSlots.includes(slot.id),
											'tw-bg-white group-hover/item-row:tw-bg-neutral-100': !checkedSlots.includes(slot.id),
										}"
									>
										<div class="tw-flex tw-items-center tw-gap-2">
											<span
												v-if="isSlotLocked(slot.id)"
												class="tw-flex tw-items-center tw-gap-1 tw-text-neutral-500"
												:title="translate('COM_EMUNDUS_POLL_REPLY_LOCKED_INFO')"
											>
												<span class="material-symbols-outlined">lock</span>
												{{ translate('COM_EMUNDUS_POLL_REPLY_LOCKED') }}
											</span>
											<Button v-else :icon="'reply'" @click.stop="openSlotReply(slot.id)">
												{{ translate('COM_EMUNDUS_POLL_REPLY_ANSWER') }}
											</Button>
										</div>
									</td>
								</tr>
							</tbody>
						</table>

						<hr />

						<div class="tw-mt-4 tw-flex tw-items-center tw-justify-center">
							<Button @click="sendSlotsState">
								<template #leading>
									<Icon name="send" />
								</template>
								{{ translate('COM_EMUNDUS_POLL_REPLY_SEND_ANSWERS') }}
							</Button>
						</div>
					</div>
				</div>
			</Transition>
		</div>
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
