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
        workflow: {
          title: "COM_EMUNDUS_ONBOARD_WORKFLOWS",
          tabs: [
            {
              title: "COM_EMUNDUS_ONBOARD_WORKFLOWS",
              key: "workflow",
              controller: "workflow",
              getter: "getworkflows",
              noData: "COM_EMUNDUS_ONBOARD_NOWORKFLOW",
              actions: [
                {
                  action: "index.php?option=com_emundus&view=workflows&layout=add",
                  label: "COM_EMUNDUS_ONBOARD_ADD_WORKFLOW",
                  controller: "workflow",
                  name: "add",
                  type: "redirect"
                },
                {
                  action: "index.php?option=com_emundus&view=workflows&layout=edit&wid=%id%",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "workflow",
                  type: "redirect",
                  name: "edit"
                },
                {
                  action: "delete",
                  label: "COM_EMUNDUS_ACTIONS_DELETE",
                  controller: "workflow",
                  method: "delete",
                  multiple: true,
                  name: "delete",
                  confirm: "COM_EMUNDUS_WORKFLOW_DELETE_WORKFLOW_CONFIRMATION"
                },
                {
                  action: "duplicate",
                  label: "COM_EMUNDUS_ACTIONS_DUPLICATE",
                  controller: "workflow",
                  name: "duplicate",
                  method: "post"
                }
              ],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_WORKFLOWS_FILTER_PROGRAM",
                  allLabel: "COM_EMUNDUS_ONBOARD_ALL_PROGRAMS",
                  getter: "getallprogramforfilter&type=id",
                  controller: "programme",
                  key: "program",
                  alwaysDisplay: true,
                  values: null
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