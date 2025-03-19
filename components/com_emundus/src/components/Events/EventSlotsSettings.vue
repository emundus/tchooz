<script>
/* Components */
import Parameter from '@/components/Utils/Parameter.vue';

/* Services */
import eventsService from '@/services/events.js';

/* Store */
import { useEventStore } from '@/stores/event.js';
import dayjs from 'dayjs';

import Swal from 'sweetalert2';

export default {
	name: 'EventSlotsSettings',
	components: { Parameter },
	props: {
		event: Object,
	},
	emits: ['reload-event'],
	data() {
		return {
			loading: true,

			formChanged: false,

			duration_fields: [
				{
					param: 'slot_duration',
					type: 'text',
					placeholder: '',
					value: '',
					concatValue: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HELP',
					displayed: true,
					splitField: true,
					secondParameterType: 'select',
					secondParameterDefault: 'minutes',
					secondParameterOptions: [
						{
							value: 'minutes',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES',
						},
						{
							value: 'hours',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS',
						},
					],
					splitChar: ' ',
				},
			],

			break_fields: [
				{
					param: 'slot_break_every',
					type: 'text',
					placeholder: '',
					value: '',
					concatValue: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_EVERY',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_EVERY_HELP',
					displayed: true,
					optional: true,
					endText: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_EVERY_END_TEXT',
				},
				{
					param: 'slot_break_time',
					type: 'text',
					placeholder: '',
					value: '',
					concatValue: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_TIME',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_TIME_HELP',
					displayed: true,
					optional: true,
					splitField: true,
					secondParameterType: 'select',
					secondParameterDefault: 'minutes',
					secondParameterOptions: [
						{
							value: 'minutes',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES',
						},
						{
							value: 'hours',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS',
						},
					],
					splitChar: ' ',
				},
			],

			more_fields: [
				{
					param: 'slots_availables_to_show',
					type: 'select',
					placeholder: '',
					value: 0,
					concatValue: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_AVAILABLE_TO_SHOW',
					helptext: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_AVAILABLE_TO_SHOW_HELP',
					helpTextType: 'icon',
					displayed: true,
					optional: true,
					options: [
						{ value: 0, label: 'COM_EMUNDUS_ONBOARD_EVENTS_SLOTS_ALL' },
						{ value: 1, label: '1' },
						{ value: 2, label: '2' },
						{ value: 3, label: '3' },
						{ value: 4, label: '4' },
						{ value: 5, label: '5' },
						{ value: 10, label: '10' },
						{ value: 20, label: '20' },
						{ value: 50, label: '50' },
						{ value: 100, label: '100' },
					],
				},
				{
					param: 'slot_can_book_until',
					type: 'text',
					placeholder: '',
					value: '',
					concatValue: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL',
					displayed: true,
					splitField: true,
					secondParameterType: 'select',
					secondParameterDefault: 'days',
					secondParameterOptions: [
						{
							value: 'days',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL_DAYS',
						},
						{
							value: 'date',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL_DATE',
						},
					],
					splitChar: ' ',
					optional: true,
				},
				{
					param: 'slot_can_cancel',
					type: 'toggle',
					value: 0,
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_CANCEL',
					hideLabel: true,
					displayed: true,
					optional: true,
				},
				{
					param: 'slot_can_cancel_until',
					type: 'text',
					placeholder: '',
					value: '',
					concatValue: '',
					label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_CANCEL_UNTIL',
					displayed: false,
					displayedOn: 'slot_can_cancel',
					displayedOnValue: 1,
					splitField: true,
					secondParameterType: 'select',
					secondParameterDefault: 'days',
					secondParameterOptions: [
						{
							value: 'days',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_CANCEL_UNTIL_DAYS',
						},
						{
							value: 'date',
							label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL_DATE',
						},
					],
					splitChar: ' ',
				},
			],
		};
	},
	mounted() {
		//this.handleBeforeUnload();
	},
	created() {
		for (let field of this.duration_fields) {
			if (this.event[field.param]) {
				field.value = this.event[field.param] + field.splitChar + this.event['slot_duration_type'];
				field.concatValue = this.event['slot_duration_type'];
			}
		}

		for (let field of this.break_fields) {
			if (this.event[field.param]) {
				if (field.param == 'slot_break_time') {
					field.value = this.event[field.param] + field.splitChar + this.event['slot_break_time_type'];
					field.concatValue = this.event['slot_break_time_type'];
				} else {
					field.value = this.event[field.param];
				}
			}
		}

		for (let field of this.more_fields) {
			if (field.param === 'slot_can_book_until') {
				if (this.event['slot_can_book_until_days']) {
					field.value = this.event['slot_can_book_until_days'] + field.splitChar + 'days';
					field.concatValue = 'days';
				} else if (this.event['slot_can_book_until_date']) {
					field.value = dayjs(this.event['slot_can_book_until_date']).format('YYYY-MM-DD') + field.splitChar + 'date';
					field.concatValue = 'date';
				}
			} else if (field.param === 'slot_can_cancel_until') {
				if (this.event['slot_can_cancel_until_days']) {
					field.value = this.event['slot_can_cancel_until_days'] + field.splitChar + 'days';
					field.concatValue = 'days';
				} else if (this.event['slot_can_cancel_until_date']) {
					field.value = dayjs(this.event['slot_can_cancel_until_date']).format('YYYY-MM-DD') + field.splitChar + 'date';
					field.concatValue = 'date';
				}
			} else {
				field.value = this.event[field.param];
			}
		}
		this.loading = false;
	},
	methods: {
		checkConditional(parameter, oldValue, value) {
			// Find all fields that are displayed based on the current field
			let fields = this.more_fields.filter((field) => field.displayedOn === parameter.param);

			// Check if the current field is displayed based on the value
			for (let field of fields) {
				field.displayed = field.displayedOnValue == value;
				if (!field.displayed) {
					if (field.default) {
						field.value = field.default;
					} else {
						field.value = '';
					}
				}
			}
		},

		setupSlots() {
			let slot = {};

			let fields = this.duration_fields.concat(this.break_fields).concat(this.more_fields);

			// Validate all fields
			const slotValidationFailed = fields.some((field) => {
				if (field.displayed) {
					let ref_name = 'event_slot_settings_' + field.param;

					if (!this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							slot[field.param] = [];
							field.value.forEach((element) => {
								slot[field.param].push(element.value);
							});
						} else {
							slot[field.param] = field.value.value;
						}
					} else if (
						(field.param === 'slot_can_book_until' || field.param === 'slot_can_cancel_until') &&
						field.concatValue.split(' ').slice(-1)[0] === 'date'
					) {
						slot[field.param] = dayjs(field.value).format('YYYY-MM-DD') + field.splitChar + 'date';
					} else {
						if (field.concatValue) {
							slot[field.param] = field.concatValue;
						} else {
							slot[field.param] = field.value;
						}
					}

					return false;
				}
			});

			if (slotValidationFailed) return;

			slot.event_id = this.event.id;

			eventsService.setupSlot(slot).then((response) => {
				if (response.status === true) {
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_SETUP_SUCCESS'),
						showConfirmButton: false,
						allowOutsideClick: false,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
						timer: 1500,
					}).then(() => {
						this.$emit('reload-event', this.event.id, 3);
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

		onFormChange(parameter, oldValue, value) {
			if (oldValue !== null && oldValue !== value && !this.formChanged) {
				this.formChanged = true;
			}
		},

		handleMoreFieldsValueUpdated(parameter, oldValue, value) {
			this.checkConditional(parameter, oldValue, value);
			this.onFormChange(parameter, oldValue, value);
		},

		handleBeforeUnload() {
			var links = [];
			var logo = document.querySelectorAll('#header-a a');
			var menu_items = document.querySelectorAll('#header-b a');
			var user_items = document.querySelectorAll('#userDropdown a');
			var footer_items = document.querySelectorAll('#g-footer a');
			var back_button_form = document.querySelectorAll('.goback-btn');

			links = [...menu_items, ...user_items, ...logo, ...footer_items, ...back_button_form];

			for (var i = 0, len = links.length; i < len; i++) {
				links[i].onclick = (e) => {
					if (this.formChanged) {
						e.preventDefault();

						Swal.fire({
							title: this.translate('COM_EMUNDUS_WANT_EXIT_FORM_TITLE'),
							html: this.translate('COM_EMUNDUS_WANT_EXIT_FORM_TEXT'),
							showCloseButton: false,
							showCancelButton: true,
							confirmButtonText: this.translate('COM_EMUNDUS_WANT_EXIT_FORM_YES'),
							cancelButtonText: this.translate('COM_EMUNDUS_WANT_EXIT_FORM_NO'),
							reverseButtons: true,
							customClass: {
								title: 'em-swal-title',
								cancelButton: 'em-swal-cancel-button',
								confirmButton: 'em-swal-confirm-button',
							},
						}).then((result) => {
							if (result.value) {
								if (e.srcElement.classList.contains('goback-btn')) {
									window.history.back();
								}

								let href = window.location.origin + '/index.php';
								// If click event target is a direct link
								if (typeof e.target.href !== 'undefined') {
									href = e.target.href;
								}
								// If click event target is a child of a link
								else {
									e = e.target;
									let attempt = 0;
									do {
										e = e.parentNode;
									} while (typeof e.href === 'undefined' && attempt++ < 5);

									if (typeof e.href !== 'undefined') {
										href = e.href;
									}
								}

								window.location.href = href;
							}
						});
					}
				};
			}
		},
	},
	computed: {
		disabledSubmit: function () {
			let fields = this.duration_fields.concat(this.break_fields).concat(this.more_fields);

			return fields.some((field) => {
				if (field.displayed && !field.optional) {
					return field.value === '' || field.value === 0;
				} else {
					return false;
				}
			});
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6" v-if="!this.loading">
			<div v-for="field in duration_fields" :key="field.param" class="tw-w-[51%]" v-show="field.displayed">
				<Parameter
					v-if="field.displayed"
					:ref="'event_slot_settings_' + field.param"
					:parameter-object="field"
					:help-text-type="'above'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					@valueUpdated="onFormChange"
				/>
			</div>

			<div class="tw-grid tw-justify-between" style="grid-template-columns: repeat(2, 47%)">
				<div v-for="field in break_fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
					<Parameter
						v-if="field.displayed"
						:ref="'event_slot_settings_' + field.param"
						:parameter-object="field"
						:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
						@valueUpdated="onFormChange"
					/>
				</div>
			</div>

			<div class="tw-flex tw-flex-col tw-gap-6">
				<div v-for="field in more_fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
					<Parameter
						v-if="field.displayed"
						:class="[
							field.param === 'slot_can_book_until' || field.param === 'slot_can_cancel_until'
								? 'tw-w-1/2'
								: 'tw-w-full',
						]"
						:ref="'event_slot_settings_' + field.param"
						:parameter-object="field"
						:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
						@valueUpdated="handleMoreFieldsValueUpdated"
					/>
				</div>
			</div>

			<div class="tw-mt-7 tw-flex tw-justify-end">
				<button type="button" class="tw-btn-primary tw-cursor-pointer" :disabled="disabledSubmit" @click="setupSlots">
					{{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CREATE') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
