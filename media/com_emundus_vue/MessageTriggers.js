import list from "./List.js";
import { _ as _export_sfc, r as resolveComponent, c as createElementBlock, o as openBlock, g as createVNode, n as normalizeClass } from "./app_emundus.js";
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
  name: "MessageTriggers",
  components: {
    List: list
  },
  props: {
    context: {
      type: String,
      default: "default"
    },
    contextId: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      config: {
        triggers: {
          title: "COM_EMUNDUS_ONBOARD_TRIGGERS",
          tabs: [
            {
              title: "COM_EMUNDUS_ONBOARD_TRIGGERS",
              key: "triggers",
              controller: "email",
              getter: "getemailtriggers",
              noData: "COM_EMUNDUS_ONBOARD_NOTRIGGERS",
              actions: [
                {
                  action: this.context !== "default" && this.contextId > 0 ? "index.php?option=com_emundus&view=emails&layout=triggeredit&id=0&" + this.context + "=" + this.contextId : "index.php?option=com_emundus&view=emails&layout=triggeredit&id=0",
                  label: "COM_EMUNDUS_ONBOARD_ADD_TRIGGER",
                  controller: "email",
                  name: "add",
                  type: "redirect"
                },
                {
                  action: "index.php?option=com_emundus&view=emails&layout=triggeredit&id=%id%",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "email",
                  name: "edit",
                  type: "redirect"
                },
                {
                  action: "removetrigger",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_DELETE",
                  controller: "email",
                  name: "delete",
                  multiple: false,
                  method: "delete",
                  confirm: "COM_EMUNDUS_ONBOARD_TRIGGER_DELETE"
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
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_list = resolveComponent("list");
  return openBlock(), createElementBlock("div", {
    id: "message-triggers-list",
    class: normalizeClass({ context: $props.context !== "default" })
  }, [
    createVNode(_component_list, {
      "default-lists": $options.configString,
      "default-type": "triggers",
      "default-filter": $props.context !== "default" && $props.contextId > 0 ? `${$props.context}=${$props.contextId}` : ""
    }, null, 8, ["default-lists", "default-filter"])
  ], 2);
}
const MessageTriggers = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  MessageTriggers as default
};
