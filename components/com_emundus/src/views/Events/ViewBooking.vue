<script>
import eventsService from "@/services/events.js";
import Modal from "@/components/Modal.vue";
import Info from "@/components/Utils/Info.vue";
import {useGlobalStore} from "@/stores/global.js";

export default {
  name: "ViewBooking",
  components: {Info, Modal},
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
            console.error("Error when trying to retrieve applicant bookings", response.error);
            reject([]);
          }
        });
      });
    },
    deleteBooking(booking_id) {
      eventsService.deleteBooking(booking_id).then((response) => {
        if(response.status === true) {
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
              actions: 'em-swal-single-action'
            }
          }).then(() => {
            this.myBookings = this.myBookings.filter((booking) => booking.id !== booking_id);
            this.$emit('close');
          })
        } else {
          // Handle error
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: response.message,
          });
        }
        this.changePopUpCancelState();
      });
    },
    canShowCancelButton(booking) {
      if (!booking.can_cancel) {
        return false;
      }

      const today = new Date();
      const startDate = new Date(booking.start);

      if (booking.can_cancel_until_date) {
        const cancelUntilDate = new Date(booking.can_cancel_until_date);
        return today <= cancelUntilDate;
      }

      else if (booking.can_cancel_until_days !== null) {
        const cancelUntilCalculatedDate = new Date();
        cancelUntilCalculatedDate.setDate(today.getDate() + booking.can_cancel_until_days);
        return cancelUntilCalculatedDate <= startDate;
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
      }
      else if(this.actualLanguage === 'en-GB')
      {
        formattedStartTime = start.toLocaleTimeString('en-EN', timeOptions);
        formattedEndTime = end.toLocaleTimeString('en-EN', timeOptions);
      }

      return `${formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1)} (${formattedStartTime} - ${formattedEndTime})`;
    },
    applicantTextBeforeCancel(booking) {
      const formatDate = (date) => {
        const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };

        if (this.actualLanguage === 'fr-FR') {
          return date.toLocaleDateString('fr-FR', options);
        } else if (this.actualLanguage === 'en-GB') {
          return date.toLocaleDateString('en-GB', options);
        }

        return date.toLocaleDateString(options);
      };

      let text = "";

      if (booking.can_book_until_days !== null) {
        const currentDate = new Date();
        const futureDate = new Date(currentDate);
        futureDate.setDate(currentDate.getDate() + booking.can_book_until_days);

        text = this.translate('COM_EMUNDUS_EVENT_CANT_BOOK_UNTIL_DATE');
        text = text.replace('{{date}}', formatDate(futureDate));
      }

      if (booking.can_book_until_date !== null) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        // Add a day to be consistent with the date defined in event configuration
        const canBookUntilDate = new Date(booking.can_book_until_date)
        canBookUntilDate.setDate(canBookUntilDate.getDate() + 1);

        if (canBookUntilDate < today) {
          return this.translate('COM_EMUNDUS_EVENT_CANT_BOOK_NOW');
        }

        text = this.translate('COM_EMUNDUS_EVENT_CANT_BOOK_FROM_DATE');
        text = text.replace('{{date}}', formatDate(canBookUntilDate));
      }

      return text;
    },
  },
};
</script>

<template>
  <div>
    <h1 class="tw-mt-4 tw-mb-8">{{ translate('COM_EMUNDUS_EVENTS_MY_RESERVATIONS') }}</h1>

    <div v-if="myBookings.length > 0">
      <div
          v-for="booking in myBookings"
          :key="booking.id"
          class="tw-flex tw-items-center tw-p-6 tw-border tw-border-neutral-300 tw-rounded-coordinator-cards tw-bg-white tw-shadow-sm tw-mb-4 tw-mr-36"
      >
        <div v-if="cancelPopupOpenForBookingId === booking.id">
          <modal
              :name="'add-location-modal'"
              :class="'placement-center tw-rounded tw-shadow-modal tw-px-6'"
              transition="nice-modal-fade"
              :width="'600px'"
              :delay="100"
              :adaptive="true"
              :clickToClose="false"
          >
            <h1 class="tw-text-center tw-mb-4 tw-mt-8">{{ translate('COM_EMUNDUS_EVENTS_CANCEL_RESERVATION')}}</h1>

            <div class="tw-flex tw-flex-col tw-text-center tw-mb-5">
              <p>{{ translate('COM_EMUNDUS_EVENTS_ARE_YOU_SURE_CANCEL_RESERVATION') }}</p>
              <p class="tw-mb-1 tw-font-bold tw-leading-6">{{ booking.event_name }}</p>
              <p class="tw-mb-1 tw-font-bold tw-leading-6">{{ booking.booking_date }}</p>
            </div>

            <Info v-if="applicantTextBeforeCancel(booking)"
                  :text="applicantTextBeforeCancel(booking)"
                  class="tw-text-left tw-w-full tw-mt-4"
                  :icon="'warning'" :bg-color="'tw-bg-orange-100'"
                  :icon-type="'material-icons'"
                  :icon-color="'tw-text-orange-600'" />

            <div class="tw-flex tw-justify-between tw-mt-5 tw-mb-8">
              <button class="tw-btn-primary"
                      @click="changePopUpCancelState(booking.id)">{{ translate('BACK') }}</button>
              <button class="tw-btn-secondary"
                      @click="deleteBooking(booking.id)"
                      >
                      {{ translate('COM_EMUNDUS_EVENTS_CANCEL_RESERVATION') }}</button>
            </div>
          </modal>
        </div>

        <div class="tw-flex-1">
          <p class="tw-text-green-700">{{ booking.event_name }}</p>
          <p class="tw-font-bold">{{ booking.booking_date }}</p>
        </div>
        <div class="tw-flex-1 tw-ml-12 tw-text-left">
            <p class="tw-text-base tw-text-neutral-600">
              {{ booking.name_location }}
              <span v-if="booking.room_name">- {{ booking.room_name }}</span>
            </p>
            <div v-if="booking.link_registrant || booking.link_event">
              <a :href="booking.link_registrant ? booking.link_registrant : booking.link_event"
                 target="_blank"
                 class="tw-text-green-700">
                <span class="tw-underline">{{ translate('COM_EMUNDUS_EVENTS_JOIN_VIDEOCONFERENCE') }}</span>
              </a>
            </div>
        </div>
        <div class="tw-flex-1 tw-flex tw-gap-2 tw-justify-end">
          <!-- <button class="tw-btn-primary">
            <span class="material-symbols-outlined">edit</span>
          </button> -->
          <button
              v-if="canShowCancelButton(booking)"
              class="tw-btn-secondary"
              @click="changePopUpCancelState(booking.id)"
          >
             <span class="material-symbols-outlined ">delete</span>
          </button>

        </div>
      </div>
    </div>

    <div v-else>
      <p class="tw-text-center tw-text-neutral-500">{{ translate('COM_EMUNDUS_EVENTS_NO_RESERVATION_FOUND') }}</p>
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
