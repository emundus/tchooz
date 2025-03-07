<script>
import {useGlobalStore} from "@/stores/global.js";

import colors from "@/mixins/colors";
import Modal from "@/components/Modal.vue";
import EditSlot from "@/views/Events/EditSlot.vue";

export default {
  name: "EventDay",
  components: {Modal},
  emits: ['valueUpdated', 'update-items', 'edit-modal'],
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
    }
  },
  mixins: [colors],
  data() {
    return {
      actualLanguage: 'fr-FR',

      eventStartDate: null,
      eventEndDate: null,
      eventDay: '',

      availableSlotHovered: -1,

      showModal: false,
      currentSlotId: null,
    }
  },
  mounted() {
    this.$nextTick(() => {
      this.applyEventStyles();
    });
  },
  created() {
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getCurrentLang;

    this.eventStartDate = new Date(this.calendarEvent.start);
    this.eventEndDate = new Date(this.calendarEvent.end);
  },
  methods: {
    openModal(slot, registrant = null) {
      this.$emit('edit-modal', slot, registrant);
    },
    updateItems()
    {
      this.$emit('update-items');
    },
    applyEventStyles() {
      let eventElement = document.querySelector(`[data-event-id="${this.calendarEvent.id}"]`);
      if (eventElement) {
        eventElement.style.width = this.calendarEvent.width;
        eventElement.style.left = this.calendarEvent.left;
      }
    }
  },
  watch: {
    calendarEvent: {
      handler() {
        this.eventStartDate = new Date(this.calendarEvent.start);
        this.eventEndDate = new Date(this.calendarEvent.end);

        this.$nextTick(() => {
          this.applyEventStyles();
        });
      },
      deep: true,
    },
  },
  computed: {
    eventHours() {
      return this.eventStartDate.toLocaleTimeString(this.actualLanguage, {
        hour: '2-digit',
        minute: '2-digit'
      }) + ' - ' + this.eventEndDate.toLocaleTimeString(this.actualLanguage, {hour: '2-digit', minute: '2-digit'});
    },
    brightnessColor() {
      return this.lightenColor(this.calendarEvent.color, 90)
    },
    availableSlots() {
      return this.calendarEvent.availabilities_count - this.calendarEvent.booked_count;
    },
    generateNumbers() {
      let numbers = [];
      let i = 0;
      while (i < (this.calendarEvent.availabilities_count - this.calendarEvent.booked_count)) {
        numbers.push(i);
        i++;
      }
      return numbers;
    },
  },
}
</script>

<template>
  <div class="tw-flex tw-h-full tw-gap-2 tw-p-1 tw-flex-col tw-pl-2 tw-border tw-border-s-4 tw-overflow-auto"
       :style="{
          backgroundColor: brightnessColor,
          color: calendarEvent.color,
          borderColor: calendarEvent.color
        }"
  >

    <div v-if="view === 'week'">
      <div v-if="calendarEvent.title">
        <span class="tw-text-xs tw-flex tw-text-ellipsis tw-overflow-hidden tw-font-semibold">
          {{ calendarEvent.title }}
        </span>
      </div>

      <div class="tw-flex tw-items-center tw-gap-2">
        <span class="material-symbols-outlined tw-text-neutral-900 !tw-text-sm"
              :style="{ color: calendarEvent.color }">schedule</span>
        <p class="tw-text-xs"
           :style="{ color: calendarEvent.color }"
        >
          {{ eventHours }}
        </p>
      </div>

      <div v-if="calendarEvent.availabilities_count" class="tw-flex tw-items-center tw-gap-2">
        <span class="material-symbols-outlined tw-text-neutral-900 !tw-text-sm"
              :style="{ color: calendarEvent.color }">groups</span>
        <p class="tw-text-xs tw-whitespace-nowrap"
           :style="{ color: calendarEvent.color }"
        >
          {{ calendarEvent.booked_count }} / {{ calendarEvent.availabilities_count }}
        </p>
        <p class="tw-text-xs tw-overflow-hidden tw-text-ellipsis tw-whitespace-nowrap"
           :style="{ color: calendarEvent.color }"
        >
          {{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_BOOKED_SLOT_NUMBER') }}
        </p>
      </div>
    </div>

    <template v-else>
      <div v-if="calendarEvent.registrants" class="tw-mb-1 tw-h-full">
        <div v-for="registrant in calendarEvent.registrants.datas"
             class="tw-flex tw-items-center tw-gap-2 tw-py-1 tw-px-3 tw-rounded-md tw-border-2 tw-min-h-[30px]"
             @click="openModal(this.calendarEvent, registrant)"
             :style="{
              backgroundColor: lightenColor(calendarEvent.color, 90),
              borderColor: calendarEvent.color
             }"
        >
          <span class="material-symbols-outlined" :style="{ color: calendarEvent.color }">group</span>
          <p :style="{ color: calendarEvent.color }"><strong>{{ translate('COM_EMUNDUS_REGISTRANTS_BOOKED')}}</strong> - {{ registrant.user_fullname }}</p>
        </div>
      </div>

      <div v-if="availableSlots > 0" class="tw-flex tw-flex-col tw-gap-1 tw-h-full">
        <div v-for="n in generateNumbers" :key="n"
             class="tw-flex tw-items-center tw-justify-center tw-gap-2 tw-py-1 tw-px-3 tw-rounded-md tw-border-2 tw-border-dashed tw-bg-white tw-min-h-[30px]"
             @click="openModal(this.calendarEvent)"
             @mouseover="availableSlotHovered = n"
             @mouseleave="availableSlotHovered = -1"
             :style="{
              borderColor: calendarEvent.color,
              color: calendarEvent.color
             }"
        >
          <span v-show="availableSlotHovered === n"
                class="material-symbols-outlined"
                :style="{
                  color: calendarEvent.color
                }"
          >
            add_circle
          </span>

        </div>
      </div>

    </template>

  </div>
</template>

<style scoped>

</style>