<script>
import Modal from '@/components/Modal.vue';
import Info from '@/components/Utils/Info.vue';
import { useGlobalStore } from '@/stores/global.js';
import eventsService from '@/services/events.js';

export default {
	name: 'CancelReservation',
	components: { Info, Modal },
	props: {
		slotSelected: {
			type: Object,
			required: false,
		},
	},
	emits: ['close'],
	data() {
		return {
			actualLanguage: 'fr-FR',
		};
	},
	created() {
		this.actualLanguage = useGlobalStore().getCurrentLang;
	},
	methods: {
		toFormattedDate() {
			const start = new Date(this.slotSelected.start);
			const end = new Date(this.slotSelected.end);

			const dateOptions = {
				weekday: 'long',
				day: 'numeric',
				month: 'long',
			};
			const timeOptions = {
				hour: '2-digit',
				minute: '2-digit',
			};

			const formattedDate = start.toLocaleDateString(this.actualLanguage, dateOptions);
			let formattedStartTime = start.toLocaleTimeString(this.actualLanguage, timeOptions);
			let formattedEndTime = end.toLocaleTimeString(this.actualLanguage, timeOptions);

			if (this.actualLanguage === 'fr-FR') {
				formattedStartTime = formattedStartTime.replace(':', 'h');
				formattedEndTime = formattedEndTime.replace(':', 'h');
			} else if (this.actualLanguage === 'en-GB') {
				formattedStartTime = start.toLocaleTimeString('en-EN', timeOptions);
				formattedEndTime = end.toLocaleTimeString('en-EN', timeOptions);
			}

			return `${formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1)} (${formattedStartTime} - ${formattedEndTime})`;
		},
		changePopUpCancelState() {
			this.$emit('close');
		},
		applicantTextBeforeCancel() {
			const formatDate = (date) => {
				const options = {
					weekday: 'long',
					day: 'numeric',
					month: 'long',
					year: 'numeric',
				};

				if (this.actualLanguage === 'fr-FR') {
					return date.toLocaleDateString('fr-FR', options);
				} else if (this.actualLanguage === 'en-GB') {
					return date.toLocaleDateString('en-GB', options);
				}

				return date.toLocaleDateString(options);
			};

			let text = '';

			if (this.slotSelected.can_book_until_days !== null) {
				const currentDate = new Date();
				const futureDate = new Date(currentDate);
				futureDate.setDate(currentDate.getDate() + this.slotSelected.can_book_until_days);

				text = this.translate('COM_EMUNDUS_EVENT_CANT_BOOK_UNTIL_DATE');
				text = text.replace('{{date}}', formatDate(futureDate));
			}

			if (this.slotSelected.can_book_until_date !== null) {
				const today = new Date();
				today.setHours(0, 0, 0, 0);

				// Add a day to be consistent with the date defined in event configuration
				const canBookUntilDate = new Date(this.slotSelected.can_book_until_date);
				canBookUntilDate.setDate(canBookUntilDate.getDate() + 1);

				if (canBookUntilDate < today) {
					return this.translate('COM_EMUNDUS_EVENT_CANT_BOOK_NOW');
				}

				text = this.translate('COM_EMUNDUS_EVENT_CANT_BOOK_FROM_DATE');
				text = text.replace('{{date}}', formatDate(canBookUntilDate));
			}

			return text;
		},
		deleteBooking() {
			eventsService.deleteBooking(this.slotSelected.id).then((response) => {
				if (response.status === true) {
					Swal.fire({
						position: 'center',
						icon: 'success',
						title: Joomla.JText._('COM_EMUNDUS_EVENTS_RESERVATION_DELETED'),
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
						window.location.reload();
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
				this.$emit('close');
			});
		},
	},
};
</script>

<template>
	<modal
		:name="'add-location-modal'"
		:classes="' tw-rounded tw-px-6 tw-shadow-modal'"
		transition="nice-modal-fade"
		:width="'600px'"
		:delay="100"
		:adaptive="true"
		:clickToClose="false"
	>
		<h1 class="tw-mb-4 tw-mt-8 tw-text-center">
			{{ translate('COM_EMUNDUS_EVENTS_CANCEL_RESERVATION') }}
		</h1>

		<div class="tw-mb-5 tw-flex tw-flex-col tw-text-center">
			<p class="!tw-text-center">
				{{ translate('COM_EMUNDUS_EVENTS_ARE_YOU_SURE_CANCEL_RESERVATION') }}
			</p>
			<p class="tw-mb-1 tw-font-bold tw-leading-6">
				{{ this.slotSelected.event_name }}
			</p>
			<p class="tw-mb-1 tw-font-bold tw-leading-6">
				{{ this.toFormattedDate() }}
			</p>
		</div>

		<Info
			v-if="applicantTextBeforeCancel()"
			:text="applicantTextBeforeCancel()"
			class="tw-mt-4 tw-w-full tw-text-left"
			:icon="'warning'"
			:bg-color="'tw-bg-orange-100'"
			:icon-type="'material-icons'"
			:icon-color="'tw-text-orange-600'"
		/>

		<div class="tw-mb-8 tw-mt-5 tw-flex tw-justify-between">
			<button class="tw-btn-primary" @click="changePopUpCancelState()">
				{{ translate('BACK') }}
			</button>
			<button class="tw-btn-secondary" @click="deleteBooking()">
				{{ translate('COM_EMUNDUS_EVENTS_CANCEL_RESERVATION') }}
			</button>
		</div>
	</modal>
</template>

<style scoped>
@import '../../assets/css/modal.scss';

.placement-center {
	position: fixed;
	left: 50%;
	transform: translate(-50%, -50%);
	top: 50%;
}
</style>
