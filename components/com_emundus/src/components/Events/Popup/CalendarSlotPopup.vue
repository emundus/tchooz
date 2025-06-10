<script>
/* Components */
import Modal from '@/components/Modal.vue';
import Parameter from '@/components/Utils/Parameter.vue';

/* Services */
import eventsService from '@/services/events.js';
import { DatePicker } from 'v-calendar';

/* Store */
import { useGlobalStore } from '@/stores/global.js';
import Popover from '@/components/Popover.vue';
import Info from '@/components/Utils/Info.vue';

export default {
	name: 'CalendarSlotPopup',
	emits: ['close', 'open', 'slot-saved', 'slot-deleted'],
	components: { Info, Popover, DatePicker, Parameter, Modal },
	props: {
		date: {
			type: String,
			default: '',
		},
		slot: {
			type: Object,
			default: null,
		},
		eventId: {
			type: Number,
			default: 0,
		},
		locationId: {
			type: Number,
			default: 0,
		},
		duration: {
			type: Number,
			default: 0,
		},
		duration_type: {
			type: String,
			default: 0,
		},
		break_every: {
			type: Number,
			default: 0,
		},
		break_time: {
			type: Number,
			default: 0,
		},
		break_time_type: {
			type: String,
			default: 0,
		},
	},
	data() {
		return {
			loading: true,
			showRepeat: false,
			displayPopover: false,
			durationSlotInfo: '',

			actualLanguage: 'fr-FR',

			rooms: [],

			fields: [
				{
					param: 'users',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getavailablemanagers',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [],
						optionsLimit: 30,
						label: 'name',
						trackBy: 'value',
					},
					value: 0,
					hideLabel: false,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_ASSOC_USER',
					placeholder: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'start_date',
					type: 'time',
					placeholder: '',
					value: 0,
					hideLabel: false,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_START_DATE',
					helptext: '',
					displayed: true,
				},
				{
					param: 'end_date',
					type: 'time',
					placeholder: '',
					value: 0,
					hideLabel: false,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_END_DATE',
					helptext: '',
					displayed: true,
				},
				{
					param: 'room',
					type: 'select',
					placeholder: '',
					value: 0,
					hideLabel: false,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_ROOM',
					helptext: '',
					displayed: true,
					optional: true,
					options: [],
				},
				{
					param: 'slot_capacity',
					type: 'text',
					value: 1,
					hideLabel: false,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAPACITY',
					placeholder: '',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAPACITY_PLACEHOLDER',
					displayed: true,
					optional: true,
					options: [],
				},
				{
					param: 'more_infos',
					type: 'textarea',
					value: '',
					hideLabel: false,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_MORE_INFOS',
					placeholder: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_MORE_INFOS_PLACEHOLDER',
					helptext: '',
					displayed: true,
					optional: true,
					options: [],
				},
			],

			repeat_dates: [],
			minDate: new Date(),
		};
	},
	created() {
		const globalStore = useGlobalStore();
		this.actualLanguage = globalStore.getShortLang;

		if (!this.$props.slot) {
			this.fields.find((field) => field.param === 'start_date').value = this.roundToQuarter(this.date);

			const date = new Date(this.fields.find((field) => field.param === 'start_date').value);
			if (this.$props.duration_type === 'minutes') {
				date.setMinutes(date.getMinutes() + this.$props.duration);
			} else {
				date.setHours(date.getHours() + this.$props.duration);
			}

			this.fields.find((field) => field.param === 'end_date').value = this.formatDate(date);

			// minDate is the end date + 1 day
			this.minDate = new Date(date);
			this.minDate.setDate(this.minDate.getDate() + 1);
		} else {
			this.fields.find((field) => field.param === 'start_date').value = this.$props.slot.start;
			this.fields.find((field) => field.param === 'end_date').value = this.$props.slot.end;
			this.minDate = new Date(this.$props.slot.end);

			this.fields.forEach((field) => {
				if (this.$props.slot[field.param] && field.param !== 'start_date' && field.param !== 'end_date') {
					field.value = this.$props.slot[field.param];
				}
			});

			if (this.$props.slot.repeat_dates && this.$props.slot.repeat_dates.length > 0) {
				this.displayPopover = true;
				this.repeat_dates = this.$props.slot.repeat_dates;
			}
		}

		this.durationSlotInfo = this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_INFO');
		this.durationSlotInfo = this.durationSlotInfo.replace('{{duration}}', this.duration);
		if (this.duration_type === 'minutes') {
			this.durationSlotInfo = this.durationSlotInfo.replace(
				'{{duration_type}}',
				this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES'),
			);
		} else if (this.duration_type === 'hours') {
			this.durationSlotInfo = this.durationSlotInfo.replace(
				'{{duration_type}}',
				this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS'),
			);
		}

		// fetch rooms
		this.getRooms();
	},
	methods: {
		beforeClose() {
			this.$emit('close');
		},
		beforeOpen() {
			this.$emit('open');
		},
		getRooms() {
			eventsService.getRooms(this.locationId).then((response) => {
				let options = [
					{
						value: 0,
						label: this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_ROOM_SELECT'),
					},
				];

				if (response.status) {
					Array.prototype.push.apply(options, response.data);
				}

				this.fields.find((field) => field.param === 'room').options = options;
				this.loading = false;
			});
		},
		saveSlot(mode = 1) {
			let slot = {};

			// Validate all fields
			const slotValidationFailed = this.fields.some((field) => {
				if (field.displayed) {
					let ref_name = 'slot_' + field.param;

					if (!this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'time') {
						slot[field.param] = this.formatDate(new Date(field.value));
					} else if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							slot[field.param] = [];
							field.value.forEach((element) => {
								slot[field.param].push(element.value);
							});
						} else {
							slot[field.param] = field.value.value;
						}
					} else {
						slot[field.param] = field.value;
					}

					return false;
				}
			});

			if (slotValidationFailed) return;
			// Check if the start date is before the end date
			if (new Date(slot.start_date) >= new Date(slot.end_date)) {
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DATE_ERROR'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						actions: 'em-swal-single-action',
					},
				});
				return;
			}

			// Check if the start date is before the current date
			if (new Date(slot.start_date) < new Date()) {
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DATE_ERROR_BEFORE_NOW'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						actions: 'em-swal-single-action',
					},
				});
				return;
			}

			// Check if interval during start_date and end_date is greater than duration
			if (new Date(slot.end_date) - new Date(slot.start_date) < this.duration * 60 * 1000) {
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_ERROR'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						actions: 'em-swal-single-action',
					},
				});
				return;
			}

			slot.event_id = this.eventId;
			slot.duration = this.duration;
			slot.duration_type = this.duration_type;
			slot.break_every = this.break_every;
			slot.break_time = this.break_time;
			slot.break_time_type = this.break_time_type;
			slot.mode = mode;
			slot.repeat_dates = this.repeat_dates.map((day) => day.id);

			if (this.$props.slot) {
				slot.id = this.$props.slot.id;
				slot.parent_slot_id = this.$props.slot.parent_slot_id;
			}

			eventsService.saveEventSlot(slot).then((response) => {
				if (response.status === true) {
					let slots = response.data;

					Swal.fire({
						position: 'center',
						icon: 'success',
						title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_SAVED'),
						showConfirmButton: false,
						allowOutsideClick: false,
						reverseButtons: true,
						timer: 1500,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
					}).then(() => {
						this.$emit('slot-saved', slots);
						this.$emit('close');
					});
				} else {
					// Handle error
					Swal.fire({
						icon: 'error',
						title: 'Oops...',
						text: response.message,
					});
				}
			});
		},

		deleteSlot() {
			Swal.fire({
				title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM'),
				text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_TEXT'),
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_YES'),
				cancelButtonText: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_NO'),
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					cancelButton: 'em-swal-cancel-button',
				},
			}).then((result) => {
				if (result.isConfirmed) {
					eventsService.deleteEventSlot(this.$props.slot.id).then((response) => {
						if (response.status === true) {
							Swal.fire({
								position: 'center',
								icon: 'success',
								title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETED'),
								showConfirmButton: false,
								allowOutsideClick: false,
								reverseButtons: true,
								timer: 1500,
								customClass: {
									title: 'em-swal-title',
									confirmButton: 'em-swal-confirm-button',
									actions: 'em-swal-single-action',
								},
							}).then(() => {
								this.$emit('slot-deleted', this.$props.slot.id);
								this.$emit('close');
							});
						} else {
							// Handle error
							Swal.fire({
								icon: 'error',
								title: 'Oops...',
								text: response.message,
							});
						}
					});
				}
			});
		},

		formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
			let year = date.getFullYear();
			let month = (1 + date.getMonth()).toString().padStart(2, '0');
			let day = date.getDate().toString().padStart(2, '0');
			let hours = date.getHours().toString().padStart(2, '0');
			let minutes = date.getMinutes().toString().padStart(2, '0');
			let seconds = date.getSeconds().toString().padStart(2, '0');

			return format
				.replace('YYYY', year)
				.replace('MM', month)
				.replace('DD', day)
				.replace('HH', hours)
				.replace('mm', minutes)
				.replace('ss', seconds);
		},

		roundToQuarter(stringDate = null, date = null) {
			if (stringDate) {
				date = new Date(stringDate);
			}

			let minutes = date.getMinutes();
			let roundedMinutes = Math.round(minutes / 15) * 15;
			date.setMinutes(roundedMinutes);
			date.setSeconds(0);
			return this.formatDate(date);
		},

		onDayClick(day) {
			if (!day.isDisabled) {
				const idx = this.repeat_dates.findIndex((d) => d.id === day.id);
				if (idx >= 0) {
					this.repeat_dates.splice(idx, 1);
				} else {
					this.repeat_dates.push({
						id: day.id,
						date: day.date,
					});
				}
			}
		},

		formatDuplicateDate(date) {
			const [year, month, day] = date.split('-');
			return `${day}-${month}-${year}`;
		},

		removeDate(date) {
			const idx = this.repeat_dates.findIndex((d) => d.id === date);
			if (idx >= 0) {
				this.repeat_dates.splice(idx, 1);
			}
		},
	},
	computed: {
		disabledSubmit: function () {
			return this.fields.some((field) => {
				if (!field.optional) {
					if (field.type === 'time') {
						let dateValue = new Date(field.value);
						let now = new Date();

						return isNaN(dateValue) || dateValue < now;
					}
					return field.value === '' || field.value === 0 || field.value === null;
				} else {
					return false;
				}
			});
		},
		dates() {
			return this.repeat_dates.map((day) => day.date);
		},
		attributes() {
			return this.dates.map((date) => ({
				highlight: true,
				dates: date,
			}));
		},
	},
};
</script>

<template>
	<modal
		:name="'calendar-slot-modal'"
		:class="'placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-overflow-x-hidden tw-rounded tw-px-4 tw-shadow-modal'"
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
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
				<h2 v-if="slot">
					{{ translate('COM_EMUNDUS_ONBOARD_EDIT_SLOT') }}
				</h2>
				<h2 v-else>
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT') }}
				</h2>
				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
		</div>

		<Info class="tw-w-full" :text="this.durationSlotInfo" />

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
			<div
				v-for="field in fields"
				v-show="field.displayed"
				:key="field.param"
				:class="{
					'tw-w-fit': field.param === 'start_date' || field.param === 'end_date',
				}"
			>
				<Parameter
					:ref="'slot_' + field.param"
					:parameter-object="field"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					:help-text-type="'above'"
				/>
			</div>

			<div>
				<div class="tw-flex tw-flex-col tw-gap-3">
					<div class="tw-flex tw-items-center tw-gap-2">
						<span class="material-symbols-outlined">repeat</span>
						<button type="button" class="tw-flex tw-items-center tw-gap-1" @click="showRepeat = !showRepeat">
							<span>{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_REPEAT') }}</span>
							<span class="material-symbols-outlined tw-text-neutral-900" :class="{ 'tw-rotate-90': showRepeat }"
								>chevron_right</span
							>
							<span
								v-if="repeat_dates.length > 0"
								class="tw-rounded-full tw-bg-profile-full tw-px-2 tw-py-1 tw-text-white"
							>
								{{ repeat_dates.length }}
								{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_REPEAT_SELECTED') }}
							</span>
						</button>
					</div>

					<div v-show="showRepeat" class="tw-flex tw-flex-col tw-gap-2">
						<DatePicker
							:id="'slot_repeat'"
							mode="date"
							title-position="left"
							:locale="actualLanguage"
							:attributes="attributes"
							:columns="2"
							:min-date="minDate"
							expanded
							@dayclick="onDayClick"
						>
						</DatePicker>

						<div class="tw-flex tw-flex-wrap tw-items-center tw-gap-2 tw-overflow-y-auto" style="max-height: 135px">
							<div
								v-for="date in repeat_dates"
								class="tw-flex tw-items-center tw-gap-1 tw-rounded-full tw-bg-profile-full tw-px-2 tw-py-1 tw-text-white"
							>
								<span @click="togglePopover">{{ formatDuplicateDate(date.id) }}</span>
								<span class="material-symbols-outlined tw-text-white" @click="removeDate(date.id)">close</span>
							</div>
						</div>
					</div>
				</div>

				<div></div>
			</div>
		</div>

		<div class="tw-mb-2 tw-mt-7 tw-flex" :class="{ 'tw-justify-end': !slot, 'tw-justify-between': slot }">
			<div class="tw-flex tw-items-center tw-gap-4">
				<button v-if="slot" type="button" class="!tw-w-auto tw-text-red-500" @click.prevent="deleteSlot()">
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_DELETE') }}
				</button>
			</div>

			<popover
				v-if="slot && displayPopover"
				:position="'top-left'"
				:icon="'keyboard_arrow_down'"
				:button="translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT')"
				class="custom-popover-arrow"
			>
				<ul
					style="list-style-type: none; margin: 0; padding-left: 0px; white-space: nowrap"
					class="tw-flex tw-h-full tw-flex-col tw-justify-center"
				>
					<li class="tw-cursor-pointer tw-p-2 hover:tw-bg-neutral-300" @click="saveSlot(1)">
						{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ONLY_ONE') }}
					</li>
					<li class="tw-cursor-pointer tw-p-2 hover:tw-bg-neutral-300" @click="saveSlot(2)">
						{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ALL_FUTURES') }}
					</li>
					<li class="tw-cursor-pointer tw-p-2 hover:tw-bg-neutral-300" @click="saveSlot(3)">
						{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ALL') }}
					</li>
				</ul>
			</popover>

			<button
				v-else
				type="button"
				class="tw-btn-primary !tw-w-auto"
				:disabled="disabledSubmit"
				@click.prevent="saveSlot(0)"
			>
				<span v-if="slot">{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT') }}</span>
				<span v-else>{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_CREATE') }}</span>
			</button>
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
