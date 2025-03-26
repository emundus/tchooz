import list from "./List.js";
import { _ as _export_sfc, r as resolveComponent, c as createElementBlock, o as openBlock, g as createVNode } from "./app_emundus.js";
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
const _sfc_main = {
  name: "SMS",
  components: {
    list
  },
  data() {
    return {
      config: {
        sms: {
          title: "COM_EMUNDUS_ONBOARD_SMS",
          tabs: [
            {
              controller: "sms",
              getter: "getSMSTemplates",
              title: "COM_EMUNDUS_ONBOARD_SMS",
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
                  icon: "preview",
                  iconOutlined: true,
                  title: "label",
                  content: "message"
                },
                {
                  action: "deleteTemplate",
                  label: "COM_EMUNDUS_ACTIONS_DELETE",
                  controller: "sms",
                  name: "delete"
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
};
const _hoisted_1 = { id: "emails-list" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_list = resolveComponent("list");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_list, {
      "default-lists": $options.configString,
      "default-type": "sms"
    }, null, 8, ["default-lists"])
  ]);
}
const SMS = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  SMS as default
};
