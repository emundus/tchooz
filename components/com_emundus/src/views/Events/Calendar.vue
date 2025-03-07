<script>
import {shallowRef} from 'vue';
import {useGlobalStore} from "@/stores/global.js";
import colors from "@/mixins/colors";

/* COMPONENTS */
import EventModal from "@/components/Events/EventModal.vue";
import EventDay from "@/components/Events/EventDay.vue";

/* Schedule X */
import {ScheduleXCalendar} from '@schedule-x/vue'
import {
  createCalendar,
  createViewDay,
  createViewWeek
} from '@schedule-x/calendar'
import '@schedule-x/theme-default/dist/index.css'
import {createEventsServicePlugin} from '@schedule-x/events-service'
import {createCalendarControlsPlugin} from '@schedule-x/calendar-controls'
import {createEventModalPlugin} from "@schedule-x/event-modal";
import {translations, mergeLocales} from '@schedule-x/translations'

const eventsServicePlugin = createEventsServicePlugin();
const calendarControls = createCalendarControlsPlugin();
const eventModal = createEventModalPlugin();

// Do not use a ref here, as the calendar instance is not reactive, and doing so might cause issues
// For updating events, use the events service plugin
const createCalendarConfig = (vm) => ({
  locale: 'fr-FR',
  defaultView: vm.defaultView,
  dayBoundaries: {
    start: '08:00',
    end: '21:00',
  },
  weekOptions: {
    gridHeight: 2500,
    eventWidth: 95,
    eventOverlap: false,
  },
  views: [
    createViewWeek(),
    createViewDay()
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
      let start = new Date();
      let startString = start.toISOString().split('T')[0];

      if (vm.items.registrants && vm.items.registrants.length > 0) {
        // Set calendar date range to the nearest start_date of items.registrants
        const nearestEvent = vm.items.registrants.reduce((prev, curr) => {
          if (!curr.start_date) {
            return prev;
          }
          if (!prev.start_date) {
            return curr;
          }

          return new Date(curr.start_date) < new Date(prev.start_date) ? curr : prev;
        });

        if (nearestEvent && nearestEvent.start_date) {
          start = new Date(nearestEvent.start_date);
          startString = start.toISOString().split('T')[0];
        }
      }

      calendarControls.setDate(startString);

      // If startString is between range dispatch onRangeUpdate event
      if (new Date(startString) >= new Date(range.start) && new Date(startString) <= new Date(range.end)) {
        if (calendarControls.getView() === 'day') {
          vm.getEventsAvailabilities(range.start, range.end);
        } else {
          vm.getEventsSlots(range.start, range.end);
        }
      }
    },
    onRangeUpdate(range) {
      if (calendarControls.getView() === 'day') {
        vm.getEventsAvailabilities(range.start, range.end);
      } else {
        vm.getEventsSlots(range.start, range.end);
      }
    },
  },
  translations: mergeLocales(
      translations,
      {
        frFR: {
          'Week': 'Vue semaine',
          'Day': 'Vue jour',
          'Today': 'Revenir à aujourd\'hui',
        },
        enGB: {
          'Week': 'Week View',
          'Day': 'Day View',
          'Today': 'Back to today',
        }
      }
  ),
});

/* Services */
import eventsService from "@/services/events";
import EventInformations from "@/components/Events/EventInformations.vue";
import Modal from "@/components/Modal.vue";
import EditSlot from "@/views/Events/EditSlot.vue";

export default {
  name: "Calendar",
  components: {Modal, EventInformations, EventDay, EventModal, ScheduleXCalendar, EditSlot},
  props: {
    items: {
      type: Object,
      required: true
    },
    editWeekAction: {
      type: String,
      required: true
    }
  },
  mixins: [colors],
  emits: ['valueUpdated', 'update-items'],
  data() {
    return {
      actualLanguage: 'fr',

      calendarApp: shallowRef(null),
      view: 'week',

      calendars: {},

      showModal: false,
      currentSlot: null,
    }
  },
  created() {
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getShortLang;

    this.initCalendar();
  },
  methods: {
    openModal(slot, registrant) {
      this.showModal = false;
      this.$nextTick(() => {
        slot.registrantSelected = registrant;
        this.currentSlot = slot;
        this.showModal = true;
      });

    },

    closePopup() {
      this.showModal = false;
      this.currentSlot = null;
    },
    initCalendar() {
      const view = sessionStorage.getItem('tchooz_calendar_view/' + document.location.hostname);

      // Keep a reference to the Vue instance's methods and data
      const vm = {
        getEventsSlots: this.getEventsSlots,
        getEventsAvailabilities: this.getEventsAvailabilities,
        items: this.items,
        defaultView: view ? view : 'week',
      };

      // Initialize calendarApp with shallowRef
      this.calendarApp = createCalendar(createCalendarConfig(vm));
    },

    getEventsSlots(start, end) {
      this.view = 'week';

      this.calendars = {};

      if (this.items.registrants && this.items.registrants.length > 0) {
        let eventsIds = this.items.registrants.map(event => event.id);
        eventsIds = eventsIds.join(',');

        eventsService.getEventsSlots(start, end, eventsIds).then(async (response) => {
          if (response.status && response.data.length > 0) {
            for (const item of this.items.registrants) {
              if (item.availabilities_count === 0) {
                continue;
              }

              this.calendars['calendar_' + item.id] = this.buildCalendar(item, true);
            }

            let events = await this.prepareEvents(response.data);

            if (events.length > 0) {
              // Only set calendars with show = true
              const calendarsToShow = Object.keys(this.calendars).filter(key => this.calendars[key].show);
              calendarControls.setCalendars(calendarsToShow);

              calendarControls.setWeekOptions({
                gridHeight: 1000,
                eventWidth: 95,
              });

              eventsServicePlugin.set(events);
            }
          }
        });
      }
    },

    getEventsAvailabilities(start, end) {
      this.view = 'day';

      this.calendars = {};

      if (this.items.registrants && this.items.registrants.length > 0) {
        let eventsIds = this.items.registrants.map(event => event.id);
        eventsIds = eventsIds.join(',');

        eventsService.getEventsAvailabilities(start, end, eventsIds).then(async (response) => {
          if (response.status && response.data.length > 0) {
            for (const item of this.items.registrants) {
              if (item.availabilities_count === 0) {
                continue;
              }

              this.calendars['calendar_' + item.id] = this.buildCalendar(item);
            }

            let events = await this.prepareEvents(response.data, false);

            if (events.length > 0) {
              for (const event of events) {
                let calendarId = event.calendarId;

                if (this.calendars[calendarId].events) {
                  if (this.calendars[calendarId].events.some(e => e.id === event.id)) {
                    continue;
                  }

                  this.calendars[calendarId].availabilities_count += event.availabilities_count;
                  this.calendars[calendarId].booked_count += event.booked_count;
                  this.calendars[calendarId].events.push(event);
                  this.calendars[calendarId].show = true;
                }
              }

              // Remove calendars with no events
              for (const key in this.calendars) {
                if (this.calendars[key].events.length === 0) {
                  delete this.calendars[key];
                }
              }

              const calendarsToShow = Object.keys(this.calendars).filter(key => this.calendars[key].show);
              calendarControls.setCalendars(calendarsToShow);

              calendarControls.setWeekOptions({
                gridHeight: 1800,
                eventWidth: 95,
              });

              eventsServicePlugin.set(events);
            } else {
              // Remove all calendars if no events
              this.calendars = {};
            }
          } else {
            this.calendars = {};
          }
        });
      }
    },

    buildCalendar(item, defaultShow = false)
    {
      return {
        colorName: 'calendar_' + item.id,
        lightColors: {
          main: item.color,
          container: item.color,
          onContainer: item.color,
        },
        color: item.color,
        name: item.label[this.actualLanguage],
        location: item.location,
        availabilities_count: 0,
        booked_count: 0,
        show: defaultShow,
        events: []
      };
    },

    prepareEvents(datas, check_show = true) {
      return new Promise((resolve) => {
        let events = [];
        let columns = [];

        // Check if calendar of event is shown
        if(check_show) {
          datas = datas.filter(event => this.calendars['calendar_' + event.event_id].show);
        }

        let groupedEvents = {};
        datas.forEach((event) => {
          if (!groupedEvents[event.event_id]) {
            groupedEvents[event.event_id] = [];
          }
          groupedEvents[event.event_id].push(event);
        });

        let groupedArray = Object.values(groupedEvents).sort(
            (a, b) => a[0].start - b[0].start
        );

        groupedArray.forEach((group) => {
          group.forEach((event) => {
            event.title = event.name;
            if (event.people && typeof event.people === "string") {
              event.people = event.people.split(",");
            }
            event.calendarId = "calendar_" + event.event_id;
          });

          // Placement du groupe entier dans les colonnes
          let placed = false;
          for (let column of columns) {
            if (!column.some((e) => e.end > group[0].start)) {
              column.push(...group);
              placed = true;
              break;
            }
          }
          if (!placed) {
            columns.push([...group]);
          }
        });

        let totalColumns = columns.length;
        columns.forEach((column, colIndex) => {
          column.forEach((event) => {
            event.width = `calc(100% / ${totalColumns})`;
            event.left = `calc(${(colIndex / totalColumns) * 100}%)`;
            events.push(event);
          });
        });

        resolve(events);
      });
    },

    editEvent(action, id) {
      this.$emit('on-click-action', action, id);
    },

    calendarStyle(calendar) {
      let style = {
        borderColor: calendar.color
      };

      if (calendar.show) {
        style.backgroundColor = this.lightenColor(calendar.color, 90);
        style.border = `2px solid ${calendar.color}`;
        style.borderLeft = `4px solid ${calendar.color}`;
      } else {
        style.borderLeft = `4px solid ${calendar.color}`;
      }

      return style;
    },

    checkboxCalendarStyle(calendar) {
      if (calendar.show) {
        return {
          backgroundColor: calendar.color,
          borderColor: calendar.color
        }
      } else {
        return {
          backgroundColor: this.lightenColor(calendar.color, 90),
          borderColor: calendar.color
        }
      }
    },
    updateItems() {
      this.$emit('update-items');
    },

    toggleCalendar(calendar) {
      calendar.show = !calendar.show;

      // get events of all calendars
      let datas = [];
      for (const key in this.calendars) {
        datas = datas.concat(this.calendars[key].events);
      }

      this.prepareEvents(datas).then((events) => {
        eventsServicePlugin.set(events);
      });
    },
  },
  watch: {
    view(value) {
      sessionStorage.setItem('tchooz_calendar_view/' + document.location.hostname, value);
    }
  }
}
</script>

<template>
  <div v-if="calendarApp"
       :class="{
          'tw-grid tw-gap-4 day-grid': view === 'day'
       }"
  >
    <template v-if="showModal">
      <Teleport to=".com_emundus_vue">
        <modal
            :name="'modal-component'"
            transition="nice-modal-fade"
            :class="'placement-center tw-rounded tw-shadow-modal tw-px-4 tw-max-h-[80vh] tw-overflow-y-auto'"
            :width="'600px'"
            :delay="100"
            :adaptive="true"
            :clickToClose="false"
            @click.stop
        >
          <component :is="'EditSlot'" :slot="this.currentSlot" @close="closePopup()" @update-items="updateItems()"/>
        </modal>
      </Teleport>
    </template>

    <div v-if="view === 'day'" class="tw-flex tw-flex-col tw-gap-4">
      <div v-for="calendar in calendars"
           class="tw-flex tw-gap-2 tw-cursor-pointer"
           @click="toggleCalendar(calendar)"
      >
        <input :checked="calendar.show"
               type="checkbox"
               :style="checkboxCalendarStyle(calendar)"
               class="tw-cursor-pointer event-checkbox tw-appearance-none tw-w-[20px] !tw-h-[20px] tw-rounded-md tw-relative"
        />
        <p>{{ calendar.title ? calendar.title : calendar.name }}</p>
      </div>
    </div>

    <div class="tw-flex tw-flex-col tw-gap-4">
      <div v-if="view === 'day'" class="tw-flex tw-gap-4"
           style="padding-left: var(--sx-calendar-week-grid-padding-left);">
        <div v-for="calendar in calendars"
             class="tw-bg-white tw-w-full tw-rounded-lg tw-px-6 tw-py-4 tw-shadow tw-border-neutral-400 tw-flex tw-flex-col tw-gap-2 tw-cursor-pointer"
             :style="calendarStyle(calendar)"
             @click="toggleCalendar(calendar)"
             v-show="calendar.show"
        >
          <EventInformations :calendar-event="calendar" :can-be-selected="true"/>
        </div>
      </div>

      <ScheduleXCalendar :calendar-app="calendarApp">
        <template #timeGridEvent="{ calendarEvent }">
          <EventDay v-if="calendars && Object.keys(this.calendars).length > 0"
                    :calendar-event="calendarEvent"
                    :view="view"
                    @update-items="updateItems" @edit-modal="openModal"/>
        </template>

        <template #eventModal="{ calendarEvent }">
          <EventModal :calendar-event="calendarEvent" :editAction="editWeekAction" @edit-event="editEvent"
                      :view="view"/>
        </template>
      </ScheduleXCalendar>
    </div>

  </div>
</template>

<style scoped>
.placement-center {
  position: fixed;
  left: 50%;
  transform: translate(-50%, -50%);
  top: 50%;
}

.day-grid {
  grid-template-columns: 25% 75%;
}

input[type="checkbox"].event-checkbox {
  margin-right: 0 !important;
}

.event-checkbox:checked:before {
  content: "✓";
  color: white;
  font-size: 16px;
  font-weight: bold;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
</style>