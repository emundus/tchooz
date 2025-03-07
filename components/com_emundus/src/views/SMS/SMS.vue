<template>
  <div id="emails-list">
    <list
        :default-lists="configString"
        :default-type="'sms'"
    ></list>
  </div>
</template>

<script>
import list from "@/views/list.vue";

export default {
  name: 'SMS',
  components: {
    list
  },
  data() {
    return {
      config: {
        "sms": {
          "title": "COM_EMUNDUS_ONBOARD_SMS",
          "tabs": [
            {
              "controller": "sms",
              "getter": "getSMSTemplates",
              "title": "COM_EMUNDUS_ONBOARD_SMS",
              "key": "sms",
              "actions": [
                {
                  "action": "index.php?option=com_emundus&view=sms&layout=add",
                  "controller": "sms",
                  "label": "COM_EMUNDUS_ONBOARD_ADD_SMS",
                  "name": "add",
                  "type": "redirect"
                },
                {
                  "action": "index.php?option=com_emundus&view=sms&layout=edit&sms_id=%id%",
                  "label": "COM_EMUNDUS_ONBOARD_MODIFY",
                  "controller": "sms",
                  "type": "redirect",
                  "name": "edit"
                },
                {
                  "action": "preview",
                  "label": "COM_EMUNDUS_ONBOARD_VISUALIZE",
                  "controller": "sms",
                  "name": "preview",
                  "icon": "preview",
                  "iconOutlined": true,
                  "title": "label",
                  "content": "message"
                },
                {
                  "action": "deleteTemplate",
                  "label": "COM_EMUNDUS_ACTIONS_DELETE",
                  "controller": "sms",
                  "name": "delete",
                }
              ],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_EMAILS_FILTER_CATEGORY",
                  allLabel: "COM_EMUNDUS_ONBOARD_ALL_PROGRAM_CATEGORIES",
                  getter: "getSMSCategories",
                  controller: "sms",
                  key: "category",
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
      return btoa(JSON.stringify(this.config));
    }
  }
}
</script>

<style scoped>

</style>