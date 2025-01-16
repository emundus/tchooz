<script>
/* Components */
import Tabs from "@/components/Utils/Tabs.vue";
import EventGlobalSettings from "@/components/Events/EventGlobalSettings.vue";
import EventSlotsSettings from "@/components/Events/EventSlotsSettings.vue";

/* Services */
import settingsService from "@/services/settings.js";
import eventsService from "@/services/events.js";

/* Store */
import {useGlobalStore} from "@/stores/global.js";
import EventCalendarSettings from "@/components/Events/EventCalendarSettings.vue";
import EventEmailSettings from "@/components/Events/EventEmailSettings.vue";

export default {
  name: "EventForm",
  components: {EventEmailSettings, EventCalendarSettings, EventSlotsSettings, EventGlobalSettings, Tabs},
  props: {},
  data: () => ({
    loading: true,
    event_id: 0,
    event: {},

    tabs: [
      {
        id: 1,
        name: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL',
        description: "",
        icon: 'info',
        active: true,
        displayed: true
      },
      {
        id: 2,
        name: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SCHEDULE',
        description: "",
        icon: 'schedule',
        active: false,
        displayed: true,
        disabled: true
      },
      {
        id: 3,
        name: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_CALENDAR',
        description: "",
        icon: 'calendar_today',
        active: false,
        displayed: true,
        disabled: true
      },
      {
        id: 4,
        name: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_EMAILS',
        description: "",
        icon: 'schedule_send',
        active: false,
        displayed: true,
        disabled: true
      }
    ]
  }),
  created() {
    this.event_id = parseInt(useGlobalStore().datas.eventid.value);

    if(this.event_id) {
      this.getEvent(this.event_id);
    } else {
      this.loading = false;
    }
  },
  methods: {
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },
    handleChangeTab(tab_id) {
      this.$refs.tabsComponent.changeTab(tab_id);
    },

    // Display a message when the user clicks on a disabled tab
    displayDisabledMessage(tab) {
      Swal.fire({
        position: 'center',
        icon: 'warning',
        title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_PLEASE_CREATE_FIRST'),
        showConfirmButton: true,
        allowOutsideClick: false,
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          actions: 'em-swal-single-action'
        }
      });
    },

    getEvent(event_id, change_tab = 0) {
      eventsService.getEvent(event_id).then((response) => {
        if(response.status) {
          this.event = response.data;
          if(this.event.slots) {
            this.event.slots.forEach((slot) => {
              if(slot.people) {
                slot.people = slot.people.split(',');
              }
            });
          }
          this.tabs[1].disabled = false;
          this.tabs[3].disabled = false;
          if(this.event.slot_duration) {
            this.tabs[2].disabled = false;
          }
          this.loading = false;
        }

        if(change_tab) {
          this.handleChangeTab(change_tab);
        }
      });
    },
  }
}
</script>

<template>
  <div class="events__add-event">
    <div>
      <div class="tw-flex tw-items-center tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300 goback-btn"
           @click="redirectJRoute('index.php?option=com_emundus&view=events')">
        <span class="material-symbols-outlined tw-text-neutral-600">navigate_before</span>
        <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
      </div>

      <h1 class="tw-mt-4">
        {{ this.event && Object.keys(this.event).length > 0
          ? translate('COM_EMUNDUS_ONBOARD_EDIT_EVENT_GLOBAL_CREATE') + " " + this.event["name"]
          : translate('COM_EMUNDUS_ONBOARD_ADD_EVENT') }}
      </h1>

      <hr class="tw-mt-1.5 tw-mb-8">

      <template v-if="!loading">
        <Tabs
            ref="tabsComponent"
            :classes="'tw-flex tw-items-center tw-gap-2 tw-ml-7'"
            :tabs="tabs"
            :context="event_id ? 'event_form_'+event_id : ''"
            @click-disabled-tab="displayDisabledMessage"
        />

        <div class="tw-w-full tw-rounded-coordinator tw-p-6 tw-bg-white tw-border tw-border-neutral-300 tw-relative">
          <EventGlobalSettings
              v-if="tabs[0].active"
              :event="event"
              @reload-event="getEvent"
          />
          <EventSlotsSettings
              v-if="tabs[1].active"
              :event="event"
              @reload-event="getEvent"
          />
          <EventCalendarSettings
              v-if="tabs[2].active"
              :event="event"
              @go-back="redirectJRoute('index.php?option=com_emundus&view=events')"
          />
          <EventEmailSettings
              v-if="tabs[3].active"
              :event="event"
              @reload-event="getEvent"
              @go-back="redirectJRoute('index.php?option=com_emundus&view=events')"
          />
        </div>
      </template>

    </div>

    <div v-if="loading" class="em-page-loader"></div>
  </div>
</template>

<style scoped>

</style>