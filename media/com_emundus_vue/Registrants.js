import list from "./List.js";
import { _ as _export_sfc, r as resolveComponent, o as openBlock, c as createElementBlock, g as createVNode } from "./app_emundus.js";
import "./Skeleton.js";
import "./Calendar.js";
import "./core.js";
import "./events2.js";
const _sfc_main = {
  // eslint-disable-next-line vue/multi-word-component-names
  name: "Registrants",
  components: {
    list
  },
  data() {
    return {
      config: {
        registrants: {
          title: "COM_EMUNDUS_ONBOARD_REGISTRANTS",
          intro: "COM_EMUNDUS_ONBOARD_REGISTRANTS_INTRO",
          tabs: [
            {
              title: "COM_EMUNDUS_ONBOARD_REGISTRANTS",
              key: "registrants",
              controller: "events",
              getter: "getregistrants",
              viewsOptions: [{ value: "table", icon: "dehaze" }],
              noData: "COM_EMUNDUS_ONBOARD_NO_REGISTRANTS",
              displaySearch: false,
              actions: [],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_ALL",
                  getter: "getfilterevents",
                  controller: "events",
                  key: "event",
                  values: null
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_ALL",
                  getter: "getlocations",
                  controller: "events",
                  key: "location",
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
const _hoisted_1 = { id: "registrants-list" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_list = resolveComponent("list");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_list, {
      "default-lists": $options.configString,
      "default-type": "registrants"
    }, null, 8, ["default-lists"])
  ]);
}
const Registrants = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Registrants as default
};
