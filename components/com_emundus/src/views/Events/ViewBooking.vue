<script>
import eventsService from '@/services/events.js';
import Modal from '@/components/Modal.vue';
import Info from '@/components/Utils/Info.vue';
import { useGlobalStore } from '@/stores/global.js';
import CancelReservation from '@/views/Events/CancelReservation.vue';

export default {
	name: 'ViewBooking',
	components: { CancelReservation, Info, Modal },
	data: () => ({
		actualLanguage: 'fr-FR',

		myBookings: [],
		cancelPopupOpenForBookingId: null,

		loading: false,
	}),
	created() {
		this.loading = true;
		const globalStore = useGlobalStore();
		this.actualLanguage = globalStore.getCurrentLang;
		this.getApplicantBookings().then((bookings) => {
			this.myBookings = bookings
				.map((booking) => ({
					...booking,
					booking_date: this.toFormattedDate(booking.start, booking.end),
				}))
				.sort((a, b) => new Date(a.start) - new Date(b.start));

			this.loading = false;
		});
	},
	methods: {
		async getApplicantBookings() {
			return new Promise((resolve, reject) => {
				eventsService.getApplicantBookings().then((response) => {
					if (response.status) {
						resolve(response.data);
					} else {
						console.error('Error when trying to retrieve applicant bookings', response.error);
						reject([]);
					}
				});
			});
		},
		canShowCancelButton(booking) {
			if (!booking.can_cancel) {
				return false;
			}

			const today = new Date();
			today.setHours(0, 0, 0, 0);

			const startDate = new Date(booking.start);
			startDate.setHours(0, 0, 0, 0);

			if (booking.can_cancel_until_date) {
				const cancelUntilDate = new Date(booking.can_cancel_until_date);
				cancelUntilDate.setHours(0, 0, 0, 0);
				return today <= cancelUntilDate;
			} else if (booking.can_cancel_until_days !== null) {
				const cancelUntilCalculatedDate = new Date();
				cancelUntilCalculatedDate.setDate(startDate.getDate() - booking.can_cancel_until_days);
				cancelUntilCalculatedDate.setHours(0, 0, 0, 0);
				return today <= cancelUntilCalculatedDate;
			}

			return true;
		},
		changePopUpCancelState(booking_id) {
			this.cancelPopupOpenForBookingId = this.cancelPopupOpenForBookingId === booking_id ? null : booking_id;
		},
		toFormattedDate(startDate, endDate) {
			const start = new Date(startDate);
			const end = new Date(endDate);

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
	},
};
</script>

<template>
	<div>
		<h1 class="tw-mb-8 tw-mt-4">
			{{ translate('COM_EMUNDUS_EVENTS_MY_RESERVATIONS') }}
		</h1>

		<div v-if="myBookings.length > 0">
			<div
				v-for="booking in myBookings"
				:key="booking.id"
				class="tw-mb-4 tw-mr-36 tw-flex tw-items-center tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-sm"
			>
				<div v-if="cancelPopupOpenForBookingId === booking.id">
					<CancelReservation :slot-selected="booking" @close="changePopUpCancelState(booking.id)" />
				</div>

				<div class="tw-flex-1">
					<p class="tw-text-green-700">{{ booking.event_name }}</p>
					<p class="tw-font-bold">{{ booking.booking_date }}</p>
				</div>
				<div class="tw-ml-12 tw-flex-1 tw-text-left">
					<p class="tw-text-base tw-text-neutral-600">
						{{ booking.name_location }}
						<span v-if="booking.room_name">- {{ booking.room_name }}</span>
					</p>
					<div v-if="booking.link_registrant || booking.link_event">
						<a
							:href="booking.link_registrant ? booking.link_registrant : booking.link_event"
							target="_blank"
							class="tw-text-green-700"
						>
							<span class="tw-underline">{{ translate('COM_EMUNDUS_EVENTS_JOIN_VIDEOCONFERENCE') }}</span>
						</a>
					</div>
				</div>
				<div class="tw-flex tw-flex-1 tw-justify-end tw-gap-2">
					<!-- <button class="tw-btn-primary">
            <span class="material-symbols-outlined">edit</span>
          </button> -->
					<button
						v-if="canShowCancelButton(booking)"
						class="tw-btn-secondary"
						@click="changePopUpCancelState(booking.id)"
					>
						<span class="material-symbols-outlined">delete</span>
					</button>
				</div>
			</div>
		</div>

		<div v-else>
			<p class="tw-text-center tw-text-neutral-500">
				{{ translate('COM_EMUNDUS_EVENTS_NO_RESERVATION_FOUND') }}
			</p>
		</div>

		<div v-if="loading" class="em-page-loader"></div>
	</div>
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
