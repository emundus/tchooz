<template>
  <div id="registrants-list">
    <list
        :default-lists="configString"
        :default-type="'registrants'"
    ></list>
  </div>
</template>

<script>
import list from "@/views/list.vue";

export default {
  // eslint-disable-next-line vue/multi-word-component-names
  name: "Registrants",
  components: {
    list
  },
  data() {
    return {
      config: {
        registrants: {
          title: "COM_EMUNDUS_ONBOARD_REGISTRANTS",
          intro: "COM_EMUNDUS_ONBOARD_REGISTRANTS_INTRO",
          tabs: [
            {
              title: "COM_EMUNDUS_ONBOARD_REGISTRANTS",
              key: "registrants",
              controller: "events",
              getter: "getregistrants",
              viewsOptions: [{value: 'table', icon: 'dehaze'}],
              noData: "COM_EMUNDUS_ONBOARD_NO_REGISTRANTS",
              displaySearch: false,
              actions: [],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_ALL",
                  getter: "getfilterevents",
                  controller: "events",
                  key: "event",
                  values: null
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_ALL",
                  getter: "getlocations",
                  controller: "events",
                  key: "location",
                  values: null
                }
              ]
            }
          ]
        },
      }
    };
  },
  computed: {
    configString() {
      return btoa(JSON.stringify(this.config));
    }
  }
}
</script>

<style scoped>

</style>