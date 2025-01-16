<script>
import {useGlobalStore} from "@/stores/global.js";

export default {
  name: "EventModal",
  props: {
    calendarEvent: {
      type: Object,
      required: true,
    },
    editAction: {
      type: String,
    }
  },
  data() {
    return {
      actualLanguage: 'fr-FR',

      eventStartDate: null,
      eventEndDate: null,
      eventDay: '',
    }
  },
  created() {
    console.log(this.calendarEvent);
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getCurrentLang;

    this.eventStartDate = new Date(this.calendarEvent.start);
    this.eventEndDate = new Date(this.calendarEvent.end);
  },
  methods: {
    editEvent() {
      this.$emit('edit-event', this.editAction, this.calendarEvent.event_id);
    }
  },
  computed: {
    eventDay() {
      return this.eventStartDate.toLocaleDateString(this.actualLanguage, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    },
    eventHours() {
      return this.eventStartDate.toLocaleTimeString(this.actualLanguage, { hour: '2-digit', minute: '2-digit' }) + ' - ' + this.eventEndDate.toLocaleTimeString(this.actualLanguage, { hour: '2-digit', minute: '2-digit' });
    },
  }
}
</script>

<template>
  <div class="tw-rounded-lg tw-px-6 tw-py-4 tw-shadow-sm tw-border tw-border-neutral-400 tw-flex tw-flex-col tw-gap-2">
    <p><strong>{{ calendarEvent.title }}</strong></p>
    <div class="tw-flex tw-items-center tw-gap-2">
      <span class="material-symbols-outlined">calendar_today</span>
      <p>{{ eventDay }}</p>
    </div>
    <div class="tw-flex tw-items-center tw-gap-2">
      <span class="material-symbols-outlined">schedule</span>
      <p>{{ eventHours }}</p>
    </div>
    <div v-if="calendarEvent.location" class="tw-flex tw-items-center tw-gap-2">
      <span class="material-symbols-outlined">location_on</span>
      <p>{{ calendarEvent.location }}</p>
    </div>
    <div class="tw-flex tw-items-center tw-gap-2">
      <span class="material-symbols-outlined">groups</span>
      <p>{{ calendarEvent.booked_count }} / {{ calendarEvent.availabilities_count }}</p>
    </div>

    <div class="tw-flex tw-justify-end">
      <button type="button" @click="editEvent">{{ translate('COM_EMUNDUS_EDIT_ITEM') }}</button>
    </div>
  </div>
</template>

<style>
</style>