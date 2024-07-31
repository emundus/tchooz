<template>
  <list
    :default-lists="workflowConfig"
    :default-type="'workflow'"
  ></list>
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

}
</script>

<style scoped>

</style>