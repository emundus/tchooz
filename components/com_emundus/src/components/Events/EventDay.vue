<script>
import {useGlobalStore} from "@/stores/global.js";

export default {
  name: "EventDay",
  props: {
    calendarEvent: {
      type: Object,
      required: true,
    },
    view: {
      type: String,
      required: true,
    },
    editAction: {
      type: String,
    },
    preset: {
      type: String,
      default: 'basic',
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
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getCurrentLang;

    this.eventStartDate = new Date(this.calendarEvent.start);
    this.eventEndDate = new Date(this.calendarEvent.end);
  },
  methods: {},
  watch: {
    calendarEvent: {
      handler() {
        this.eventStartDate = new Date(this.calendarEvent.start);
        this.eventEndDate = new Date(this.calendarEvent.end);
      },
      deep: true,
    }
  },
  computed: {
    eventPeople() {
      let people = this.calendarEvent.people;
      if (Array.isArray(this.calendarEvent.people)) {
        people = this.calendarEvent.people.join(', ');
      }

      return people;
    },
    eventHours() {
      return this.eventStartDate.toLocaleTimeString(this.actualLanguage, {
        hour: '2-digit',
        minute: '2-digit'
      }) + ' - ' + this.eventEndDate.toLocaleTimeString(this.actualLanguage, {hour: '2-digit', minute: '2-digit'});
    },
    textColor() {
      // Choose white or black depending on the background color
      if(this.calendarEvent.color) {
        const color = this.calendarEvent.color;
        const r = parseInt(color.substr(1, 2), 16);
        const g = parseInt(color.substr(3, 2), 16);
        const b = parseInt(color.substr(5, 2), 16);
        const brightness = Math.round(((r * 299) + (g * 587) + (b * 114)) / 1000);
        return brightness > 125 ? '#000' : '#fff';
      } else {
        return '#000';
      }
    }
  }
}
</script>

<template>
  <div class="tw-flex tw-h-full tw-gap-2"
       :class="{
         'tw-flex-col tw-p-2': view === 'week' || preset === 'full',
         'tw-items-center tw-flex-row tw-px-2 tw-border-2 tw-border-neutral-300': view === 'day' && preset !== 'full',
         'tw-border-s-neutral-300 sx__stripped_event': calendarEvent.booked_count >= calendarEvent.availabilities_count,
       }"
       :style="{ backgroundColor: calendarEvent.color, color: textColor}"
  >
    <div v-if="calendarEvent.title">
      <span><strong>{{ calendarEvent.title }}</strong></span>
    </div>

    <div v-if="preset === 'full' && calendarEvent.people" class="tw-flex tw-items-center tw-gap-2">
      <span class="material-symbols-outlined tw-text-neutral-900"
            :style="{ color: textColor }">group</span>
      <p class="tw-text-sm"
         :style="{ color: textColor }"
      >
        {{ eventPeople }}
      </p>
    </div>

    <div class="tw-flex tw-items-center tw-gap-2">
      <span v-if="preset === 'full'" class="material-symbols-outlined tw-text-neutral-900"
            :style="{ color: textColor }">schedule</span>
      <p class="tw-text-sm"
         :style="{ color: textColor }"
      >
        {{ eventHours }}
      </p>
    </div>

    <div v-if="preset === 'full' && calendarEvent.room && calendarEvent.location" class="tw-flex tw-items-center tw-gap-2">
      <span class="material-symbols-outlined tw-text-neutral-900"
            :style="{ color: textColor }">room</span>
      <p class="tw-text-sm"
         :style="{ color: textColor }"
      >
        {{ calendarEvent.location }}
      </p>
    </div>

    <div v-if="preset === 'full' && calendarEvent.availabilities_count" class="tw-flex tw-items-center tw-gap-2">
      <span class="material-symbols-outlined tw-text-neutral-900"
            :style="{ color: textColor }">groups</span>
      <p class="tw-text-sm"
         :style="{ color: textColor }"
      >
        {{ calendarEvent.booked_count }} / {{ calendarEvent.availabilities_count }}
      </p>
    </div>
  </div>
</template>

<style>
</style>