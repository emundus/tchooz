<script>
import {useGlobalStore} from "@/stores/global.js";

import colors from "@/mixins/colors";
import EventInformations from "@/components/Events/EventInformations.vue";

export default {
  name: "EventModal",
  components: {EventInformations},
  props: {
    calendarEvent: {
      type: Object,
      required: true,
    },
    editAction: {
      type: String,
    },
    view: {
      type: String,
      required: true,
    }
  },
  mixins: [colors],
  data() {
    return {
      popupPosition: '',
    }
  },
  created() {
    setTimeout(() => {
      this.setPopupPosition();
    }, 150);
  },
  methods: {
    editEvent() {
      this.$emit('edit-event', this.editAction, this.calendarEvent.event_id);
    },
    setPopupPosition() {
      const modal = document.getElementsByClassName('card-event');
      const event = document.querySelector('div[data-event-id="'+this.calendarEvent.id+'"]');
      if(modal[0] && event) {
        const modalPosition = modal[0].getBoundingClientRect().left;
        const eventPosition = event.getBoundingClientRect().left;

        if(modalPosition > eventPosition) {
          this.popupPosition = 'left';
        } else {
          this.popupPosition = 'right';
        }
      }
    }
  },
}
</script>

<template>
  <div class="card-event tw-rounded-lg tw-px-6 tw-py-4 tw-shadow tw-border-neutral-400 tw-flex tw-flex-col tw-gap-2"
       :class="{ 'card-event-left': popupPosition === 'left', 'card-event-right': popupPosition === 'right' }"
       v-if="view == 'week'"
       :style="{ borderColor: calendarEvent.color, '--event-arrow-color': calendarEvent.color }"
  >
    <EventInformations :calendar-event="calendarEvent" />

    <div class="tw-flex tw-justify-end">
      <button type="button" @click="editEvent">{{ translate('COM_EMUNDUS_EDIT_ITEM') }}</button>
    </div>
  </div>
</template>

<style scoped>
.card-event-left {
  border-inline-start-width: 4px;
}
.card-event-right {
  border-inline-end-width: 4px;
}
.card-event-right:before {
  content: "";
  position: absolute;
  right: -8px;
  top: 10%;
  transform: translateY(-50%);
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  border-left: 8px solid var(--event-arrow-color);
}
.card-event-left:before {
  content: "";
  position: absolute;
  left: -8px;
  top: 10%;
  transform: translateY(-50%);
  border-top: 8px solid transparent;
  border-bottom: 8px solid transparent;
  border-right: 8px solid var(--event-arrow-color);
}
</style>