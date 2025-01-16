<script>
import {shallowRef} from "vue";

/* Components */
import CalendarSlotPopup from "@/components/Events/Popup/CalendarSlotPopup.vue";

/* Schedule X */
import {ScheduleXCalendar} from '@schedule-x/vue'
import {
  createCalendar,
  createViewDay,
  createViewMonthAgenda,
  createViewMonthGrid,
  createViewWeek,
    viewWeek
} from '@schedule-x/calendar'
import '@schedule-x/theme-default/dist/index.css'
import {createEventsServicePlugin} from '@schedule-x/events-service'
import { createCalendarControlsPlugin } from '@schedule-x/calendar-controls'
import Swal from "sweetalert2";
import EventDay from "@/components/Events/EventDay.vue";

const eventsServicePlugin = createEventsServicePlugin();
const calendarControls = createCalendarControlsPlugin()

// Do not use a ref here, as the calendar instance is not reactive, and doing so might cause issues
// For updating events, use the events service plugin
const createCalendarConfig = (vm) => ({
  locale: 'fr-FR',
  defaultView: viewWeek.name,
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
    eventsServicePlugin,
    calendarControls
  ],
  callbacks: {
    /**
     * Runs before the calendar is rendered
     * */
    beforeRender: ($app) => {
      const range = $app.calendarState.range.value
    },
    onClickDateTime: (dateTime) => {
      vm.openSlotPopup(dateTime);
    },
    onEventClick: (event) => {
      vm.openSlotPopup(null, event);
    },
    onRangeUpdate(range) {
      let start = new Date(range.start);
      let end = new Date(range.end);

      if (start.getDate() === end.getDate()) {
        vm.setView('day');
      } else {
        vm.setView('week');
      }
    },
  }
});

export default {
  name: "EventCalendarSettings",
  components: {EventDay, CalendarSlotPopup, ScheduleXCalendar},
  props: {
    event: Object,
  },
  emits: ['go-back'],
  data() {
    return {
      calendarApp: shallowRef(null),
      loading: true,
      openedSlotPopup: false,

      dateClicked: null,
      currentSlot: null,
      view: 'week',
    }
  },
  mounted() {
    // Keep a reference to the Vue instance's methods and data
    const vm = {
      openSlotPopup: this.openSlotPopup,
      dateClicked: this.dateClicked,
      setView: this.setView,
    };

    // Initialize calendarApp with shallowRef
    this.calendarApp = createCalendar(createCalendarConfig(vm));

    for(const slot of this.$props.event.slots) {
      slot.color = this.event.color;
    }

    // Set selected date corresponding to last slot
    if (this.$props.event.slots.length > 0) {
      let selectedDate = new Date(this.$props.event.slots[this.$props.event.slots.length - 1].start);
      // Convert selectedDate to a string in the format 'YYYY-MM-DD'
      selectedDate = selectedDate.toISOString().split('T')[0];
      calendarControls.setDate(selectedDate);
    }

    eventsServicePlugin.set(this.$props.event.slots);
  },
  created() {
    this.loading = false;
  },
  methods: {
    openSlotPopup(date, slot = null) {
      // Do not open if no duration
      if (!this.event.slot_duration) {
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_REQUIRED'),
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            confirmButton: 'em-swal-confirm-button',
            actions: 'em-swal-single-action'
          }
        });
        return;
      }

      if(slot) {
        slot.repeat_dates = [];

        // Search if other slots are linked to this slot via parent_slot_id
        let parent_slot_id = slot.id;
        let parent_slot = this.$props.event.slots.find(s => s.id === slot.parent_slot_id);
        if(parent_slot) {
          parent_slot_id = parent_slot.id;
        }
        let child_slots = this.$props.event.slots.filter(s => s.parent_slot_id !== 0 && s.parent_slot_id === parent_slot_id);

        if (child_slots.length > 0) {
          for (const child_slot of child_slots) {
            let repeat_date = {};
            repeat_date.id = child_slot.start.split(' ')[0];
            repeat_date.date = child_slot.start;
            slot.repeat_dates.push(repeat_date);
          }
        }
        //
      }

      this.dateClicked = date;
      this.currentSlot = slot;
      this.openedSlotPopup = true;
    },
    updateSlots(slots) {
      for (const slot of slots) {
        let existingSlot = eventsServicePlugin.get(slot.id);

        if (existingSlot) {
          eventsServicePlugin.update(slot);
        } else {
          eventsServicePlugin.add(slot);
        }
      }
    },
    deleteSlot(slot_id) {
      eventsServicePlugin.remove(slot_id);
    },

    setView(view) {
      this.view = view;
    }
  },
  computed: {}
}
</script>

<template>
  <div>
    <CalendarSlotPopup
        v-if="openedSlotPopup"
        :date="dateClicked"
        :slot="currentSlot"
        :event-id="event.id"
        :location-id="event.location"
        :duration="event.slot_duration"
        :duration_type="event.slot_duration_type"
        :break_every="event.slot_break_every"
        :break_time="event.slot_break_time"
        :break_time_type="event.slot_break_time_type"
        @close="openedSlotPopup = false;"
        @slot-saved="updateSlots"
        @slot-deleted="deleteSlot"
    />

    <div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6" v-if="!this.loading">
      <div>
        <div>
          <label class="tw-flex tw-font-semibold tw-items-end tw-mb-0">
            {{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CALENDAR') }}
          </label>
          <span class="tw-text-base tw-text-neutral-600">
            {{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CALENDAR_HELP') }}
          </span>
        </div>

        <div class="tw-mt-4" v-if="calendarApp">
          <ScheduleXCalendar :calendar-app="calendarApp">
            <template #timeGridEvent="{ calendarEvent }">
              <EventDay :calendar-event="calendarEvent" :view="view" :preset="'full'" />
            </template>
          </ScheduleXCalendar>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>