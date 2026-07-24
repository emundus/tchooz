<script>
import Modal from '@/components/Modal.vue';
import Parameter from '@/components/Utils/Parameter.vue';

import date from '@/mixins/date.js';
import alerts from '@/mixins/alerts.js';
import ModalHeader from '@/components/Utils/Modal/Header.vue';
import { Icon, Button } from '@emundus/ui';

export default {
	name: 'PollSlotPopup',
	emits: ['close', 'open', 'slot-saved', 'slot-deleted'],
	components: { Icon, ModalHeader, Button, Parameter, Modal },
	mixins: [date, alerts],
	props: {
		date: {
			type: String,
			default: '',
		},
		slot: {
			type: Object,
			default: null,
		},
		pollId: {
			type: Number,
			default: 0,
		},
		duration: {
			type: Number,
			default: 60,
		},
	},
	data() {
		return {
			fields: [
				{
					param: 'slot_date',
					type: 'date',
					placeholder: '',
					value: null,
					hideLabel: false,
					label: 'COM_EMUNDUS_POLL_DATES_SLOT_DAY',
					helptext: '',
					displayed: false,
					classes: 'tw-w-fit',
					placement: 'right',
				},
				{
					param: 'start_date',
					type: 'time',
					placeholder: '',
					value: 0,
					hideLabel: false,
					label: 'COM_EMUNDUS_POLL_SLOT_FIELD_START_HOUR_LABEL',
					helptext: '',
					displayed: true,
				},
				{
					param: 'end_date',
					type: 'time',
					placeholder: '',
					value: 0,
					hideLabel: false,
					label: 'COM_EMUNDUS_POLL_SLOT_FIELD_END_HOUR_LABEL',
					helptext: '',
					displayed: true,
				},
				{
					param: 'slot_capacity',
					type: 'number',
					placeholder: '',
					value: 1,
					hideLabel: false,
					label: 'COM_EMUNDUS_POLL_FIELD_SLOT_REQUIRED_PARTICIPANTS',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'location_text',
					type: 'text',
					placeholder: '',
					value: '',
					hideLabel: false,
					label: 'COM_EMUNDUS_REGISTRANTS_LOCATION',
					helptext: '',
					displayed: true,
					optional: true,
				},
			],
		};
	},
	created() {
		if (!this.$props.slot) {
			const dateClicked = this.date ? this.date.toString().split('+')[0] : null;
			if (dateClicked) {
				const startDate = this.roundToQuarter(dateClicked);
				this.fields.find((field) => field.param === 'start_date').value = startDate;

				const endDate = new Date(startDate);
				endDate.setMinutes(endDate.getMinutes() + this.$props.duration);
				this.fields.find((field) => field.param === 'end_date').value = this.formatDate(endDate);
			} else {
				const slotDateField = this.fields.find((field) => field.param === 'slot_date');
				slotDateField.displayed = true;
				slotDateField.value = new Date().toISOString().split('T')[0];
			}
		} else {
			const startDate = this.$props.slot.start.toString().split('+')[0];
			const endDate = this.$props.slot.end.toString().split('+')[0];
			this.fields.find((field) => field.param === 'start_date').value = startDate;
			this.fields.find((field) => field.param === 'end_date').value = endDate;

			if (this.$props.slot.slot_capacity != null) {
				this.fields.find((field) => field.param === 'slot_capacity').value = this.$props.slot.slot_capacity;
			}

			if (this.$props.slot.location_text != null) {
				this.fields.find((field) => field.param === 'location_text').value = this.$props.slot.location_text;
			}
		}
	},
	methods: {
		beforeClose() {
			this.$emit('close');
		},
		beforeOpen() {
			this.$emit('open');
		},
		saveSlot() {
			const slot = {};

			const validationFailed = this.fields.some((field) => {
				if (!field.displayed) return false;
				const refName = 'slot_' + field.param;
				if (!this.$refs[refName][0].validate()) {
					return true;
				}
				if (field.type === 'time') {
					slot[field.param] = this.formatDate(new Date(field.value));
				} else {
					slot[field.param] = field.value;
				}
				return false;
			});

			if (validationFailed) return;

			if (slot.slot_date) {
				const startTime = slot.start_date ? slot.start_date.split(' ')[1] : '00:00:00';
				const endTime = slot.end_date ? slot.end_date.split(' ')[1] : '00:00:00';
				slot.start_date = `${slot.slot_date} ${startTime}`;
				slot.end_date = `${slot.slot_date} ${endTime}`;
				delete slot.slot_date;
			}

			if (new Date(slot.start_date) >= new Date(slot.end_date)) {
				this.alertError('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DATE_ERROR');
				return;
			}

			if (new Date(slot.start_date) < new Date()) {
				this.alertError('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DATE_ERROR_BEFORE_NOW');
				return;
			}

			slot.poll_id = this.pollId;

			if (this.$props.slot) {
				slot.id = this.$props.slot.id;
			}

			this.$emit('slot-saved', slot);
			this.$emit('close');
		},
		deleteSlot() {
			Swal.fire({
				title: Joomla.JText._('COM_EMUNDUS_POLL_DELETE_SLOT_CONFIRM_TITLE'),
				text: Joomla.JText._('COM_EMUNDUS_POLL_DELETE_SLOT_CONFIRM_TEXT'),
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: Joomla.JText._('COM_EMUNDUS_POLL_DELETE_SLOT_CONFIRM_YES'),
				cancelButtonText: Joomla.JText._('COM_EMUNDUS_POLL_DELETE_SLOT_CONFIRM_NO'),
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					cancelButton: 'em-swal-cancel-button',
				},
			}).then((result) => {
				if (result.isConfirmed) {
					this.$emit('slot-deleted', this.$props.slot.id);
					this.$emit('close');
				}
			});
		},
		formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
			const year = date.getFullYear();
			const month = (1 + date.getMonth()).toString().padStart(2, '0');
			const day = date.getDate().toString().padStart(2, '0');
			const hours = date.getHours().toString().padStart(2, '0');
			const minutes = date.getMinutes().toString().padStart(2, '0');
			const seconds = date.getSeconds().toString().padStart(2, '0');

			return format
				.replace('YYYY', year)
				.replace('MM', month)
				.replace('DD', day)
				.replace('HH', hours)
				.replace('mm', minutes)
				.replace('ss', seconds);
		},
		roundToQuarter(stringDate = null, dateObj = null) {
			if (stringDate) {
				dateObj = new Date(stringDate);
			}
			const minutes = dateObj.getMinutes();
			const roundedMinutes = Math.round(minutes / 15) * 15;
			dateObj.setMinutes(roundedMinutes);
			dateObj.setSeconds(0);
			return this.formatDate(dateObj);
		},
	},
	computed: {
		selectedDate() {
			let raw = null;
			if (this.slot && this.slot.start) {
				raw = this.slot.start.toString().split('+')[0];
			} else if (this.date) {
				raw = this.date.toString().split('+')[0];
			}
			if (!raw) return null;
			const dateObj = new Date(raw);
			if (isNaN(dateObj.getTime())) return null;
			return dateObj.toLocaleDateString(undefined, {
				weekday: 'long',
				year: 'numeric',
				month: 'long',
				day: 'numeric',
			});
		},
		disabledSubmit() {
			const slotDateField = this.fields.find((field) => field.param === 'slot_date');
			const slotDateActive = slotDateField && slotDateField.displayed;
			return this.fields.some((field) => {
				if (!field.displayed) return false;
				if (field.optional) return false;
				if (field.type === 'time') {
					const dateValue = new Date(field.value);
					if (isNaN(dateValue)) return true;
					if (slotDateActive) return false;
					return dateValue < new Date();
				}
				return field.value === '' || field.value === null;
			});
		},
		submitText() {
			if (this.$props.slot) {
				return 'COM_EMUNDUS_POLL_FIELD_SLOT_SAVE_EXISTING';
			}

			return 'COM_EMUNDUS_POLL_FIELD_SLOT_SAVE';
		},
	},
};
</script>

<template>
	<modal
		:name="'poll-slot-modal'"
		:classes="' tw-max-h-[80vh] tw-overflow-y-auto tw-overflow-x-hidden tw-rounded tw-px-4 tw-shadow-modal'"
		transition="nice-modal-fade"
		:width="'60%'"
		:delay="100"
		:adaptive="true"
		:clickToClose="false"
		:blockScrolling="true"
		@closed="beforeClose"
		@before-open="beforeOpen"
	>
		<div class="tw-top-0 tw-z-10 tw-border-b tw-border-neutral-300 tw-bg-white tw-pt-4">
			<ModalHeader
				:title="slot ? translate('COM_EMUNDUS_POLL_FIELD_SLOT_EDIT') : translate('COM_EMUNDUS_POLL_FIELD_SLOT_ADD')"
				@close="$emit('close')"
			/>
		</div>

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
			<div v-if="selectedDate" class="tw-flex tw-w-fit tw-flex-col tw-gap-1">
				<label class="tw-flex tw-items-end tw-font-medium">{{ translate('COM_EMUNDUS_POLL_DATES_SLOT_DAY') }}</label>
				<div
					class="tw-flex tw-items-center tw-gap-2 tw-rounded-md tw-bg-neutral-100 tw-px-3 tw-py-2 tw-text-sm tw-text-neutral-700"
				>
					<Icon name="calendar_today" class="!tw-text-[20px]" />
					<span class="tw-font-medium tw-capitalize">{{ selectedDate }}</span>
				</div>
			</div>
			<div v-for="field in fields" v-show="field.displayed" :key="field.param" class="tw-w-fit">
				<Parameter :ref="'slot_' + field.param" :parameter-object="field" :help-text-type="'above'" />
			</div>
		</div>

		<div class="tw-mb-4 tw-mt-7 tw-flex" :class="{ 'tw-justify-center': !slot, 'tw-justify-between': slot }">
			<div class="tw-flex tw-items-center tw-gap-4">
				<Button v-if="slot" variant="danger" @click="deleteSlot()">
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_DELETE') }}
				</Button>
			</div>

			<Button :disabled="disabledSubmit" @click="saveSlot()">
				{{ translate(submitText) }}
			</Button>
		</div>
	</modal>
</template>

<style scoped>
@import '../../../assets/css/modal.scss';

.placement-center {
	position: fixed;
	left: 50%;
	transform: translate(-50%, -50%);
	top: 50%;
}
</style>
