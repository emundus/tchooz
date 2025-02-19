<script>
import eventsService from "@/services/events";
import {useGlobalStore} from "@/stores/global.js";
import Info from "@/components/Utils/Info.vue";

export default {
  name: "EventBooking",
  components: {Info},
  data() {
    return {
      loading: false,

      myBookings: [],
      currentStartIndex: 0,

      availableDates: [],

      slots: [],
      slotSelected: null,

      name: null,
      currentTimezone: {
        offset: 0,
        name: ''
      },

      location: 0
    };
  },
  created() {
    this.name = useGlobalStore().getDatas.name_element.value;
    this.currentTimezone.offset = useGlobalStore().getDatas.offset.value;
    this.currentTimezone.name = useGlobalStore().getDatas.timezone.value;

    // If we want to filter the slots by location
    let location_filter_elt = useGlobalStore().getDatas.location_filter_elt.value;
    if(location_filter_elt && location_filter_elt !== '' && document.getElementById(location_filter_elt)) {
      location_filter_elt = document.getElementById(location_filter_elt);
    }

    // First check if the user has already booked a slot
    this.getMyBookings().then((bookings) => {
      this.myBookings = bookings;

      if (this.myBookings.length > 0) {
        this.slotSelected = this.myBookings[0].availability;
      }

      if (this.myBookings.length === 0 && location_filter_elt) {
        location_filter_elt.addEventListener('change', (event) => {
          this.location = event.target.value;

          if(this.location && this.location !== 0 && this.location !== '0' && this.location !== '') {
            this.getSlots();
          } else {
            this.slots = [];
            this.availableDates = [];
          }
        });

        this.location = location_filter_elt.value;
        if(this.location && this.location !== 0 && this.location !== '0' && this.location !== '') {
          this.getSlots();
        }
      } else {
        this.getSlots();
      }
    });
  },
  methods: {
    async getMyBookings() {
      return new Promise((resolve, reject) => {
        eventsService.getMyBookings().then((response) => {
          if (response.status) {
            resolve(response.data);
          } else {
            console.error("Error when try to retrieve my bookings", response.error);
            reject([]);
          }
        });
      });
    },
    async getSlots() {
      this.loading = true;
      try {
        const responseSlots = await eventsService.getAvailabilitiesByCampaignsAndPrograms(new Date().toISOString().split('T'), '', this.location, 1);
        let slots = responseSlots.data;

        const groupedSlots = slots.reduce((accumulator, slot) => {
          const key = `${slot.start}_${slot.end}_${slot.event_id}`;
          if (!accumulator[key]) {
            accumulator[key] = {
              slots: [],
              totalCapacity: 0,
              totalBookers: 0,
              start: slot.start,
              end: slot.end,
              event_id: slot.event_id
            };
          }
          accumulator[key].slots.push({...slot, bookers: 0});
          accumulator[key].totalCapacity += slot.capacity;
          return accumulator;
        }, {});

        const responseRegistrants = await eventsService.getAvailabilityRegistrants();
        const registrants = responseRegistrants.data;

        Object.values(groupedSlots).forEach(group => {
          group.slots.forEach(slot => {
            const slotRegistrants = registrants.filter(registrant => registrant.availability === slot.id);
            slot.bookers = slotRegistrants.length;
            group.totalBookers += slot.bookers;
          });
        });

        this.slots = Object.values(groupedSlots);

        this.slots.sort((a, b) => new Date(a.start) - new Date(b.start));

        this.availableDates = [...new Set(this.slots.map(slot => new Date(slot.start).toISOString().split('T')[0]))];
        this.availableDates.sort((a, b) => new Date(a) - new Date(b));

        this.loading = false;

      } catch (error) {
        console.error("Erreur lors de la récupération des créneaux ou des registrants :", error);
        this.loading = false;
      }
    },
    /*updateVisibleDates() {
      if (this.currentStartIndex >= this.availableDates.length) {
        this.currentStartIndex = Math.max(0, this.availableDates.length - 3);
      }
      if (this.currentStartIndex < 0) {
        this.currentStartIndex = 0;
      }

      this.visibleDates = this.availableDates
          .slice(this.currentStartIndex, this.currentStartIndex + 3)
          .map(dateString => new Date(dateString));
    },*/
    formatDay(date) {
      return date.toLocaleDateString('fr-FR', {weekday: 'long'}).charAt(0).toUpperCase() + date.toLocaleDateString('fr-FR', {weekday: 'long'}).slice(1);
    },
    formatShortDate(date) {
      return date.toLocaleDateString('fr-FR', {day: 'numeric', month: 'short'});
    },
    nextDates() {
      if (this.currentStartIndex + 3 < this.availableDates.length) {
        this.currentStartIndex += 3;
        //this.updateVisibleDates();
      }
    },
    previousDates() {
      if (this.currentStartIndex > 0) {
        this.currentStartIndex -= 3;
        //this.updateVisibleDates();
      }
    },
    getAvailableSlotsForDate(date) {
      const now = new Date();

      return this.slots
        .filter(slot => {
          const slotDate = new Date(slot.start);
          return (
            slotDate.toLocaleDateString() === date.toLocaleDateString() &&
                slotDate >= now
          );
        })
        .map(slot => {
          let id = 0;
          for (const innerSlot of slot.slots) {
            if (innerSlot.capacity > innerSlot.bookers) {
              id = innerSlot.id;
              break;
            }
          }

          return {
            ...slot,
            id,
            displayTime: new Date(slot.start).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})
          };
        });
    }
  },
  computed: {
    visibleDates: function () {
      // ! make sure to not create a recursive loop by changing the currentStartIndex, who is watched
      // TODO: computed properties should not update the data they are based on
      if (this.currentStartIndex >= this.availableDates.length) {
        this.currentStartIndex = Math.max(0, this.availableDates.length - 3);
      }
      if (this.currentStartIndex < 0) {
        this.currentStartIndex = 0;
      }

      return this.availableDates
        .slice(this.currentStartIndex, this.currentStartIndex + 3)
        .map(dateString => new Date(dateString));
    },

    selectedSlotInfo: function () {
      let text = null;

      if (this.slotSelected) {
        const selectedSlot = this.slots.flatMap(group => group.slots).find(slot => slot.id === this.slotSelected);
        if (selectedSlot) {
          const start = new Date(selectedSlot.start);
          const end = new Date(selectedSlot.end);
          const interval = end - start;
          let minutes = Math.floor((interval / 1000) / 60);

          if (minutes < 60) {
            minutes = minutes + ' ' + this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES');
          } else {
            const hours = Math.floor(minutes / 60);
            minutes = hours + ' ' + this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS');
          }

          text = this.translate('COM_EMUNDUS_EVENT_SLOT_RECAP');
          text = text.replace('{{date}}', start.toLocaleDateString('fr-FR', {
            weekday: 'long',
            day: 'numeric',
            month: 'long'
          }));
          text = text.replace('{{time}}', start.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}));
          text = text.replace('{{duration}}', minutes);
        }
      }
      return text;
    },
    displayedTimezone: function () {
      return this.currentTimezone.name.replace('_', ' ') + ' (UTC' + (this.currentTimezone.offset > 0 ? '+' : '') + this.currentTimezone.offset + ')';
    }
  },
};
</script>


<template>
  <div
      class="tw-w-full tw-rounded-coordinator tw-border tw-border-neutral-300 tw-p-4 tw-flex tw-flex-col tw-gap-4 tw-items-center tw-relative">
    <div v-if="visibleDates.length > 0 && myBookings.length === 0 && !loading" class="tw-w-full">
      <div class="tw-flex tw-items-center tw-gap-1 tw-mb-3">
        <span class="material-symbols-outlined !tw-text-base">language</span>
        <span class="tw-text-base">{{ displayedTimezone }}</span>
      </div>

      <div class="tw-flex tw-items-start tw-gap-1 tw-w-full">
        <button
            class="tw-p-2 tw-border-0 tw-bg-transparent tw-rounded-coordinator hover:tw-bg-neutral-100"
            type="button"
            :disabled="currentStartIndex === 0"
            :style="{
          cursor: currentStartIndex === 0 ? 'not-allowed' : 'pointer',
          opacity: currentStartIndex === 0 ? 0.2 : 1}"
            @click="previousDates">
          <span class="material-symbols-outlined">chevron_left</span>
        </button>

        <div v-if="slots" class="tw-flex tw-flex-row tw-items-stretch tw-gap-4 tw-flex-1 tw-w-auto tw-justify-center">
          <div
              v-for="(date, index) in visibleDates"
              :key="index"
              :style="{
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            gap: '8px',
            flexGrow: '1'
          }">
            <p class="tw-text-lg tw-text-center">{{ formatDay(date) }}</p>
            <p class="tw-text-sm tw-text-neutral-500 tw-text-center">{{ formatShortDate(date) }}</p>

            <div class="tw-mt-4 tw-grid tw-grid-cols-2 tw-gap-2 tw-w-full">
              <button
                  v-for="slot in getAvailableSlotsForDate(date)"
                  type="button"
                  class="tw-flex tw-items-center tw-justify-center tw-px-4 tw-w-full tw-py-2 tw-bg-neutral-300 tw-rounded-coordinator tw-border"
                  :class="{
                    'tw-border-profile-full tw-bg-profile-light': slotSelected === slot.id,
                    'hover:tw-bg-neutral-400': slotSelected !== slot.id,
                    'tw-opacity-50 tw-line-through tw-cursor-not-allowed': slot.totalBookers >= slot.totalCapacity
                  }"
                  :key="slot.id"
                  :disabled="slot.totalBookers >= slot.totalCapacity"
                  @click="this.slotSelected = slot.id">
                {{ slot.displayTime }}
              </button>

            </div>
          </div>
        </div>

        <button
            class="tw-p-2 tw-border-0 tw-bg-transparent tw-rounded-coordinator hover:tw-bg-neutral-100"
            type="button"
            :disabled="currentStartIndex + 3 >= availableDates.length"
            :style="{
          cursor: currentStartIndex + 3 >= availableDates.length ? 'not-allowed' : 'pointer',
          opacity: currentStartIndex + 3 >= availableDates.length ? 0.2 : 1}"
            @click="nextDates">
          <span class="material-symbols-outlined">chevron_right</span>
        </button>
      </div>
    </div>

    <div v-else-if="visibleDates.length === 0 && !loading">
      <span>{{ translate('COM_EMUNDUS_EVENT_NO_SLOT_AVAILABLE') }}</span>
    </div>

    <div v-else-if="loading" class="em-loader" />

    <Info
        v-if="slotSelected && this.slots.length > 0"
        class="tw-w-full"
        :text="selectedSlotInfo"
    />

    <input
        type="text"
        class="hidden fabrikinput"
        :id="name"
        :name="name"
        :value="slotSelected"
    />
  </div>
</template>

<style scoped>
</style>
