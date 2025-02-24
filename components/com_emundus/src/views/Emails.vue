<template>
  <div id="emails-list">
    <list
        :default-lists="configString"
        :default-type="'emails'"
    ></list>
  </div>
</template>

<script>
import list from "@/views/list.vue";

export default {
  // eslint-disable-next-line vue/multi-word-component-names
  name: "Emails",
  components: {
    list
  },
  data() {
    return {
      config: {
        emails: {
          title: "COM_EMUNDUS_ONBOARD_EMAILS",
          tabs: [
            {
              controller: "email",
              getter: "getallemail",
              title: "COM_EMUNDUS_ONBOARD_EMAILS",
              key: "emails",
              noData: "COM_EMUNDUS_ONBOARD_NOEMAIL",
              actions: [
                {
                  action: "index.php?option=com_emundus&view=emails&layout=add",
                  controller: "email",
                  label: "COM_EMUNDUS_ONBOARD_ADD_EMAIL",
                  name: "add",
                  type: "redirect"
                },
                {
                  action: "index.php?option=com_emundus&view=emails&layout=add&eid=%id%",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "email",
                  type: "redirect",
                  name: "edit"
                },
                {
                  action: "deleteemail",
                  label: "COM_EMUNDUS_ACTIONS_DELETE",
                  controller: "email",
                  name: "delete",
                  method: "delete",
                  multiple: true,
                  confirm: "COM_EMUNDUS_ONBOARD_EMAILS_CONFIRM_DELETE",
                  showon: {
                    key: "type",
                    operator: "!=",
                    value: "1"
                  }
                },
                {
                  action: "preview",
                  label: "COM_EMUNDUS_ONBOARD_VISUALIZE",
                  controller: "email",
                  name: "preview",
                  title: "subject",
                  content: "message"
                }
              ],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_EMAILS_FILTER_CATEGORY",
                  allLabel: "COM_EMUNDUS_ONBOARD_ALL_PROGRAM_CATEGORIES",
                  getter: "getemailcategories",
                  controller: "email",
                  key: "recherche",
                  alwaysDisplay: true,
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