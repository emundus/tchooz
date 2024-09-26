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
              "getter": "getworkflows",
              "actions": [
                {
                  "action": "workflows/add",
                  "label": "COM_EMUNDUS_ONBOARD_ADD_WORKFLOW",
                  "controller": "workflow",
                  "name": "add",
                  "type": "redirect"
                },
                {
                  "action": "workflows/edit?wid=%id%",
                  "label": "COM_EMUNDUS_ONBOARD_MODIFY",
                  "controller": "workflow",
                  "type": "redirect",
                  "name": "edit"
                },
                {
                  "action": "delete",
                  "label": "COM_EMUNDUS_ACTIONS_DELETE",
                  "controller": "workflow",
                  "parameters": "&id=%id%",
                  "name": "delete",
                  "confirm": "COM_EMUNDUS_WORKFLOW_DELETE_WORKFLOW_CONFIRMATION"
                },
              ],
              "filters": [
                {
                  "label": "COM_EMUNDUS_ONBOARD_ALL_PROGRAMS",
                  "getter": "getallprogramforfilter&type=id",
                  "controller": "programme",
                  "key": "program",
                  "values": null
                }
              ]
            }
          ]
        }
      }
    };
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