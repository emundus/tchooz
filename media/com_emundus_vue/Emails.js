import list from "./List.js";
import { a0 as defineStore, _ as _export_sfc, r as resolveComponent, c as createElementBlock, o as openBlock, a as createBlock, b as createCommentVNode, B as smsService } from "./app_emundus.js";
import "./ExportSlotsModal.js";
import "./Skeleton.js";
import "./Calendar.js";
import "./core.js";
import "./index.js";
import "./Parameter.js";
import "./EventBooking.js";
import "./events2.js";
import "./Info.js";
import "./EditSlot.js";
import "./ColorPicker.js";
import "./LocationPopup.js";
import "./LocationForm.js";
const useSmsStore = defineStore("sms", {
  state: () => ({
    activated: null
  }),
  getters: {
    getActivated: (state) => state.activated
  },
  actions: {
    updateActivated(payload) {
      this.activated = payload;
    }
  }
});
const _sfc_main = {
  name: "Emails",
  components: {
    list
  },
  data() {
    return {
      smsActivated: null,
      renderingKey: 1,
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
        }
      },
      smsTabConfig: {
        controller: "sms",
        getter: "getSMSTemplates",
        title: "COM_EMUNDUS_ONBOARD_SMS",
        noData: "COM_EMUNDUS_ONBOARD_NOSMS",
        key: "sms",
        actions: [
          {
            action: "index.php?option=com_emundus&view=sms&layout=add",
            controller: "sms",
            label: "COM_EMUNDUS_ONBOARD_ADD_SMS",
            name: "add",
            type: "redirect"
          },
          {
            action: "index.php?option=com_emundus&view=sms&layout=edit&sms_id=%id%",
            label: "COM_EMUNDUS_ONBOARD_MODIFY",
            controller: "sms",
            type: "redirect",
            name: "edit"
          },
          {
            action: "preview",
            label: "COM_EMUNDUS_ONBOARD_VISUALIZE",
            controller: "sms",
            name: "preview",
            title: "label",
            content: "message"
          },
          {
            action: "deleteTemplate",
            label: "COM_EMUNDUS_ACTIONS_DELETE",
            controller: "sms",
            name: "delete",
            multiple: true
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
    };
  },
  created() {
    if (useSmsStore().getActivated === null) {
      smsService.isSMSActivated().then((response) => {
        this.smsActivated = response.data;
        if (this.smsActivated) {
          useSmsStore().updateActivated(true);
          this.config.emails.tabs.push(this.smsTabConfig);
          this.renderingKey++;
        } else {
          useSmsStore().updateActivated(false);
        }
      });
    } else {
      this.smsActivated = useSmsStore().getActivated;
    }
  },
  computed: {
    configString() {
      return btoa(JSON.stringify(this.config));
    }
  }
};
const _hoisted_1 = { id: "emails-list" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_list = resolveComponent("list");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    $data.smsActivated !== null ? (openBlock(), createBlock(_component_list, {
      "default-lists": $options.configString,
      "default-type": "emails",
      key: $data.renderingKey
    }, null, 8, ["default-lists"])) : createCommentVNode("", true)
  ]);
}
const Emails = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Emails as default
};
