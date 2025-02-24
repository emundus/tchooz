import list from "./List.js";
import { _ as _export_sfc, r as resolveComponent, c as createElementBlock, o as openBlock, g as createVNode } from "./app_emundus.js";
import "./Skeleton.js";
import "./Calendar.js";
import "./core.js";
import "./events2.js";
const _sfc_main = {
  name: "Workflows",
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
};
const _hoisted_1 = { id: "workflow_id" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_list = resolveComponent("list");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_list, {
      "default-lists": $options.configString,
      "default-type": "workflow"
    }, null, 8, ["default-lists"])
  ]);
}
const Workflows = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Workflows as default
};
