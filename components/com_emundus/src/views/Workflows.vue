<template>
  <div id="workflow_id">
    <list
        :default-lists="configString"
        :default-type="'workflow'"
    ></list>
  </div>
</template>

<script>
import list from "@/views/list.vue";

export default {
  name: 'Workflows',
  components: {
    list
  },
  data() {
    return {
      workflowConfig: {
        "workflow": {
          "title": "COM_EMUNDUS_ONBOARD_WORKFLOWS",
          "tabs": [
            {
              "title": "COM_EMUNDUS_ONBOARD_WORKFLOWS",
              "key": "workflow",
              "controller": "workflow",
              "getter": "getworkflows"
            }
          ]
        }
      }
    };
  },
  mounted() {
    console.log('here');
  },
  computed: {
    configString() {
      return btoa(JSON.stringify(this.workflowConfig));
    }
  }
}
</script>

<style scoped>

</style>