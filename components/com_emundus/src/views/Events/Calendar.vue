<script>
import {ref, shallowRef} from 'vue';
import Swal from 'sweetalert2';

/* COMPONENTS */
import EventModal from "@/components/Events/EventModal.vue";
import EventDay from "@/components/Events/EventDay.vue";

/* Schedule X */
import {ScheduleXCalendar} from '@schedule-x/vue'
import {
  createCalendar,
  createViewDay,
  createViewMonthAgenda,
  createViewMonthGrid,
  createViewWeek, viewWeek,
} from '@schedule-x/calendar'
import '@schedule-x/theme-default/dist/index.css'
import {createEventsServicePlugin} from '@schedule-x/events-service'
import {createCalendarControlsPlugin} from '@schedule-x/calendar-controls'
import {createEventModalPlugin} from "@schedule-x/event-modal";

const eventsServicePlugin = createEventsServicePlugin();
const calendarControls = createCalendarControlsPlugin();
const eventModal = createEventModalPlugin();

// Do not use a ref here, as the calendar instance is not reactive, and doing so might cause issues
// For updating events, use the events service plugin
const createCalendarConfig = (vm) => ({
  locale: 'fr-FR',
  dayBoundaries: {
    start: '08:00',
    end: '21:00',
  },
  weekOptions: {
    gridHeight: 900,
    eventWidth: 95,
  },
  views: [
    createViewDay(),
    createViewWeek()
  ],
  events: [],
  plugins: [
    eventModal,
    eventsServicePlugin,
    calendarControls
  ],
  callbacks: {
    onRender($app) {
      const range = $app.calendarState.range.value
      let start = new Date(range.start);
      let end = new Date(range.end);

      if (start.getDate() === end.getDate()) {
        vm.getEventsAvailabilities(range.start, range.end);
      } else {
        vm.getEventsSlots(range.start, range.end);
      }
    },
    onRangeUpdate(range) {
      let start = new Date(range.start);
      let end = new Date(range.end);

      if (start.getDate() === end.getDate()) {
        vm.getEventsAvailabilities(range.start, range.end);
      } else {
        vm.getEventsSlots(range.start, range.end);
      }
    },
  }
});

/* Services */
import eventsService from "@/services/events";

export default {
  name: "Calendar",
  components: {EventDay, EventModal, ScheduleXCalendar},
  props: {
    items: {
      type: Object,
      required: true
    },
    editAction: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      calendarApp: shallowRef(null),
      view: 'week'
    }
  },
  created() {
    this.initCalendar();
  },
  methods: {
    initCalendar() {
      // Keep a reference to the Vue instance's methods and data
      const vm = {
        getEventsSlots: this.getEventsSlots,
        getEventsAvailabilities: this.getEventsAvailabilities,
      };

      // Initialize calendarApp with shallowRef
      this.calendarApp = createCalendar(createCalendarConfig(vm));
    },
    getEventsSlots(start, end) {
      this.view = 'week';
      const calendars = {};

      if(this.items.events && this.items.events.length > 0) {
        for(const item of this.items.events) {
          calendars[item.id] = {
            colorName: item.id,
            lightColors: {
              main: item.color.main,
              container: item.color.container,
              onContainer: item.color.onContainer,
            }
          };
        }

        calendarControls.setCalendars(calendars);

        let eventsIds = this.items.events.map(event => event.id);
        eventsIds = eventsIds.join(',');

        eventsService.getEventsSlots(start, end, eventsIds).then(response => {
          if (response.status) {
            let events = [];
            response.data.forEach((event) => {
              event.title = event.name;
              if(event.people) {
                event.people = event.people.split(',');
              }
              event.calendarId = event.event_id;
              events.push(event);
            });

            calendarControls.setWeekOptions({
              gridHeight: 900,
              eventWidth: 95,
            });

            eventsServicePlugin.set(events);
          }
        });
      }
    },
    getEventsAvailabilities(start, end) {
      this.view = 'day';
      const calendars = {};

      if(this.items.events && this.items.events.length > 0) {
        for(const item of this.items.events) {
          calendars[item.id] = {
            colorName: item.id,
            lightColors: {
              main: item.color.main,
              container: item.color.container,
              onContainer: item.color.onContainer,
            }
          };
        }

        calendarControls.setCalendars(calendars);

        let min_duration = null;
        let eventsIds = this.items.events.map(event => event.id);
        eventsIds = eventsIds.join(',');

        eventsService.getEventsAvailabilities(start, end, eventsIds).then(response => {
          if (response.status) {
            let events = [];
            response.data.forEach((event) => {
              event.title = event.name;
              if(event.people) {
                event.people = event.people.split(',');
              }
              event.calendarId = event.event_id;
              events.push(event);

              // Store the minimal slot duration
              if(event.slot_duration_type == 'hours') {
                event.slot_duration = event.slot_duration * 60;
              }
              if(!min_duration) {
                min_duration = event.slot_duration;
              }
              min_duration = Math.min(min_duration, event.slot_duration);
            });

            calendarControls.setWeekOptions({
              gridHeight: this.updateGridHeight(min_duration),
              eventWidth: 95,
            });

            eventsServicePlugin.set(events);
          }
        });
      }
    },
    updateGridHeight(slot_duration) {
      const minSlotDuration = 5;
      const maxSlotDuration = 60;

      if(slot_duration > maxSlotDuration) {
        return 900;
      }

      // Ensure slot_duration is within the allowed range
      slot_duration = Math.max(minSlotDuration, Math.min(maxSlotDuration, slot_duration));

      // Calculate gridHeight proportionally
      const gridHeight = 3000 * (1 - (slot_duration - minSlotDuration) / (maxSlotDuration - minSlotDuration));

      return Math.round(gridHeight); // Return rounded value for simplicity
    }
  }
}
</script>

<template>
  <div v-if="calendarApp">
    <ScheduleXCalendar :calendar-app="calendarApp">
      <template #timeGridEvent="{ calendarEvent }">
        <EventDay :calendar-event="calendarEvent" :view="view"/>
      </template>

      <template #eventModal="{ calendarEvent }">
        <EventModal :calendar-event="calendarEvent" :editAction="editAction" @edit-event="$emit('on-click-action',editAction)"/>
      </template>
    </ScheduleXCalendar>
  </div>
</template>

<style scoped>

</style>