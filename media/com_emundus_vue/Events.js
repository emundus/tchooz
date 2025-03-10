import list from "./List.js";
import { _ as _export_sfc, r as resolveComponent, o as openBlock, c as createElementBlock, h as createVNode } from "./app_emundus.js";
import "./Skeleton.js";
import "./Calendar.js";
import "./core.js";
import "./index.js";
import "./Parameter.js";
import "./EventBooking.js";
import "./events2.js";
import "./Info.js";
import "./LocationPopup.js";
import "./LocationForm.js";
import "./EditSlot.js";
import "./ColorPicker.js";
const _sfc_main = {
  name: "Events",
  components: {
    list
  },
  data() {
    return {
      config: {
        events: {
          title: "COM_EMUNDUS_ONBOARD_EVENTS",
          intro: "COM_EMUNDUS_ONBOARD_EVENTS_INTRO",
          tabs: [
            {
              title: "COM_EMUNDUS_ONBOARD_EVENTS",
              key: "events",
              controller: "events",
              getter: "getevents",
              noData: "COM_EMUNDUS_ONBOARD_NO_EVENTS",
              actions: [
                {
                  action: "index.php?option=com_emundus&view=events&layout=add",
                  label: "COM_EMUNDUS_ONBOARD_ADD_EVENT",
                  controller: "events",
                  name: "add",
                  type: "redirect"
                },
                {
                  action: "duplicateevent",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE",
                  controller: "events",
                  name: "duplicate",
                  method: "post"
                },
                {
                  action: "index.php?option=com_emundus&view=events&layout=add&event=%id%",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "events",
                  type: "redirect",
                  name: "edit"
                },
                {
                  action: "deleteevent",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_DELETE",
                  controller: "events",
                  name: "delete",
                  method: "delete",
                  multiple: true,
                  confirm: "COM_EMUNDUS_ONBOARD_EVENT_DELETE_CONFIRM",
                  showon: {
                    key: "registrant_count",
                    operator: "<",
                    value: "1"
                  }
                }
              ],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS",
                  allLabel: "COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS_ALL",
                  getter: "getlocations",
                  controller: "events",
                  key: "location",
                  alwaysDisplay: true,
                  values: null
                }
              ]
            },
            {
              title: "COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS",
              key: "locations",
              controller: "events",
              getter: "getalllocations",
              actions: [
                {
                  action: "index.php?option=com_emundus&view=events&layout=addlocation",
                  controller: "events",
                  label: "COM_EMUNDUS_ONBOARD_ADD_LOCATION",
                  name: "add",
                  type: "redirect"
                },
                {
                  action: "index.php?option=com_emundus&view=events&layout=addlocation&location=%id%",
                  controller: "events",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  name: "edit",
                  type: "redirect"
                },
                {
                  action: "deletelocation",
                  controller: "events",
                  label: "COM_EMUNDUS_ACTIONS_DELETE",
                  name: "delete",
                  method: "delete",
                  multiple: true,
                  confirm: "COM_EMUNDUS_ONBOARD_LOCATION_DELETE_CONFIRM",
                  showon: {
                    key: "nb_events",
                    operator: "<",
                    value: "1"
                  }
                }
              ],
              filters: []
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
const _hoisted_1 = { id: "events-list" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_list = resolveComponent("list");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_list, {
      "default-lists": $options.configString,
      "default-type": "events"
    }, null, 8, ["default-lists"])
  ]);
}
const Events = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Events as default
};
