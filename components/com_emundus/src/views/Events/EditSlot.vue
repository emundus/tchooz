<script>
import eventsService from '@/services/events.js';
import Modal from '@/components/Modal.vue';
import Info from '@/components/Utils/Info.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import ColorPicker from '@/components/ColorPicker.vue';
import LocationPopup from '@/components/Events/Popup/LocationPopup.vue';
import EventBooking from '@/views/Events/EventBooking.vue';

export default {
	name: 'EditSlot',
	components: {
		EventBooking,
		LocationPopup,
		ColorPicker,
		Parameter,
		Info,
		Modal,
	},
	props: {
		item: Object,
	},
	emits: ['close', 'valueUpdated'],
	data: () => ({
		actualLanguage: 'fr-FR',
		cancelPopupOpenForBookingId: null,
		initialEvent: null,

		submitted: false,

		fields: [
			{
				param: 'event_id',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: false,
					taggable: false,
					searchable: true,
					internalSearch: false,
					asyncRoute: 'getevents',
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
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_EVENT',
				placeholder: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_EVENT_PLACEHOLDER',
				displayed: true,
			},
			{
				param: 'booking',
				type: 'component',
				component: 'EventBooking',
				placeholder: '',
				value: 0,
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_BOOKING',
				helptext: '',
				displayed: false,
			},
			{
				param: 'user',
				type: 'multiselect',
				multiselectOptions: {
					noOptions: false,
					multiple: false,
					taggable: false,
					searchable: true,
					internalSearch: false,
					asyncRoute: 'getapplicants',
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
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_APPLICANT',
				placeholder: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_APPLICANT_PLACEHOLDER',
				displayed: false,
			},
			{
				param: 'juror',
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
				reload: 0,
				label: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS',
				placeholder: 'COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_PLACEHOLDER',
				displayed: false,
				optional: true,
			},
		],
	}),
	created: function () {
		if (this.item) {
			this.fields.forEach((field) => {
				if (this.item[field.param]) {
					if (field.param === 'user') {
						field.value = this.item['ccid'];
					} else {
						field.value = this.item[field.param];
					}
				} else if (field.param === 'user') {
					let index = this.item.registrantSelected
						? this.item.registrants?.datas?.findIndex((r) => r.id === this.item.registrantSelected.id)
						: -1;

					field.value = index !== -1 ? (this.item.registrants?.datas?.[index]?.ccid ?? null) : null;
				} else if (field.param === 'booking') {
					field.value = this.item['availability'] ?? this.item['id'];
				} else if (field.param === 'juror') {
					if (this.item['additional_columns']) {
						const jurors = this.item['additional_columns'].find(
							(col) => col.key === Joomla.JText._('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS'),
						);
						field.value = jurors.id ? jurors.id.split(',').map((id) => Number(id.trim())) : [];
					} else if (this.item['assoc_user_id']) {
						field.value = this.item['assoc_user_id']
							? this.item['assoc_user_id'].split(',').map((id) => Number(id.trim()))
							: [];
					} else if (this.item['registrantSelected'] && this.item['registrantSelected']['assoc_user_id']) {
						field.value = this.item['registrantSelected']['assoc_user_id'].split(',').map((id) => Number(id.trim()));
					} else if (this.item['users']) {
						field.value = this.item['users'].split(',').map((id) => Number(id.trim()));
					} else {
						field.value = [];
					}
				} else {
					field.value = null;
				}
			});
		} else {
			this.fields.forEach((field) => {
				field.value = null;
			});
		}
	},
	methods: {
		editSlot() {
			this.submitted = true;
			let slot_edited = {};

			// Validate all fields
			const slotValidationFailed = this.fields.some((field) => {
				if (field.displayed) {
					let ref_name = 'slot_' + field.param;

					if (!this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							slot_edited[field.param] = [];
							field.value.forEach((element) => {
								slot_edited[field.param].push(element.value);
							});
						} else {
							slot_edited[field.param] = field.value[field.multiselectOptions.trackBy] ?? field.value;
						}
					} else {
						slot_edited[field.param] = field.value;
					}

					return false;
				}
			});

			if (slotValidationFailed) return;

			if (this.item) {
				if (this.item.calendarId && !this.item.registrants) {
					slot_edited['id'] = 0;
				} else {
					if (this.item.calendarId) {
						let index = this.item.registrantSelected
							? this.item.registrants?.datas?.findIndex((r) => r.id === this.item.registrantSelected.id)
							: -1;

						slot_edited['id'] = index !== -1 ? (this.item.registrants?.datas?.[index]?.id ?? null) : null;
					} else {
						slot_edited['id'] = this.item.id;
					}
				}
			} else {
				slot_edited['id'] = null;
			}

			eventsService.editSlot(slot_edited).then((response) => {
				if (response.status === true) {
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: this.item
							? Joomla.JText._('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_SAVED')
							: Joomla.JText._('COM_EMUNDUS_ONBOARD_REGISTRANT_ADD_SAVED'),
						showConfirmButton: true,
						allowOutsideClick: false,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
						timer: 1500,
					}).then(() => {
						this.onClosePopup();
						this.$emit('update-items', 1, null, true);
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
		onClosePopup() {
			this.$emit('close');
		},
		updateBookingElement(value) {
			const field = this.fields.find((f) => f.param === 'booking');
			field.value = value.value ?? value;
		},
		updateForm(parameter, old, newValue) {
			if (parameter.param === 'event_id' && old !== newValue) {
				this.fields.forEach((field) => {
					if (field.param !== 'event_id') {
						this.$nextTick(() => {
							if (field.displayed) {
								field.reload = (field.reload || 0) + 1;
							} else if (field.param === 'booking' || field.param === 'user' || field.param === 'juror') {
								field.displayed = true;
							}
						});

						if (field.param === 'booking') {
							if (this.item && this.item['event_id'] !== this.fields.find((f) => f.param === 'event_id')?.value) {
								field.value = null;
							}
						}
					} else if (field.displayed && field.param === 'event_id') {
						if (newValue === null) {
							field.value = null;
						} else {
							field.value = field.value[field.multiselectOptions.trackBy];
						}
					}
				});
			}
		},
	},
	computed: {
		disabledSubmit: function () {
			return this.fields.some((field) => {
				if (!field.optional && field.displayed) {
					return (
						field.value === '' ||
						field.value === 0 ||
						field.value === null ||
						(typeof field.value === 'object' && Object.keys(field.value).length === 0)
					);
				} else {
					return false;
				}
			});
		},
		bookingSlot() {
			if (this.item) {
				return this.item['availability'] ?? this.item['id'];
			}
			return null;
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-pt-4">
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
				<h2 v-if="item">
					{{ translate('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT') }}
				</h2>
				<h2 v-else>
					{{ translate('COM_EMUNDUS_ONBOARD_REGISTRANT_ADD') }}
				</h2>

				<button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="onClosePopup">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
		</div>

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
			<div
				v-for="field in fields"
				v-show="field.displayed"
				:key="field.param"
				:class="'tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-2'"
			>
				<Parameter
					v-if="field.displayed && field.param === 'booking'"
					:ref="'slot_' + field.param"
					:key="field.reload ? field.reload + ' booking' : field.param + ' booking'"
					:parameter-object="field"
					:help-text-type="'above'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					:asyncAttributes="[
						fields.find((f) => f.param === 'event_id')?.value,
						fields.find((f) => f.param === 'user')?.value,
						field.param,
					]"
					:componentsProps="{
						event_id: fields.find((f) => f.param === 'event_id')?.value,
						slot_id: bookingSlot,
					}"
					@valueUpdated="updateBookingElement"
				/>

				<Parameter
					v-else-if="field.displayed"
					:ref="'slot_' + field.param"
					:key="field.reload ? field.reload + field.param : field.param"
					:parameter-object="field"
					:help-text-type="'below'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					:asyncAttributes="[
						fields.find((f) => f.param === 'event_id')?.value,
						fields.find((f) => f.param === 'user')?.value,
						field.param,
					]"
					@valueUpdated="updateForm"
				/>

				<Info
					v-if="field.param === 'juror' && (!field.value || field.value.length === 0)"
					:key="field.value"
					:text="translate('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_NO_SELECTED')"
					class="tw-mt-4"
				/>
			</div>
		</div>

		<div class="tw-mb-8 tw-mt-5 tw-flex tw-justify-between">
			<button class="tw-btn-cancel" @click="onClosePopup">
				{{ translate('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_CANCEL') }}
			</button>
			<button class="tw-btn-primary" :disabled="disabledSubmit || submitted" @click="editSlot()">
				{{ translate('COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_CONFIRM') }}
			</button>
		</div>

		<div class="em-page-loader" v-if="!fields[1].displayed"></div>
	</div>
</template>

<style scoped></style>
