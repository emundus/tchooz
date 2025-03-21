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
  name: "Forms",
  components: {
    list
  },
  data() {
    return {
      config: {
        forms: {
          title: "COM_EMUNDUS_ONBOARD_FORMS",
          tabs: [
            {
              title: "COM_EMUNDUS_FORM_MY_FORMS",
              key: "form",
              controller: "form",
              getter: "getallform",
              noData: "COM_EMUNDUS_ONBOARD_NOFORM",
              actions: [
                {
                  action: "duplicateform",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE",
                  controller: "form",
                  method: "post",
                  name: "duplicate"
                },
                {
                  action: "index.php?option=com_emundus&view=form&layout=formbuilder&prid=%id%",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "form",
                  type: "redirect",
                  name: "edit"
                },
                {
                  action: "createform",
                  controller: "form",
                  label: "COM_EMUNDUS_ONBOARD_ADD_FORM",
                  name: "add"
                }
              ],
              filters: []
            },
            {
              title: "COM_EMUNDUS_FORM_MY_EVAL_FORMS",
              key: "form_evaluations",
              controller: "form",
              getter: "getallgrilleEval",
              noData: "COM_EMUNDUS_ONBOARD_NOFORM",
              actions: [
                {
                  action: "createformeval",
                  label: "COM_EMUNDUS_ONBOARD_ADD_EVAL_FORM",
                  controller: "form",
                  name: "add"
                },
                {
                  action: "/index.php?option=com_emundus&view=form&layout=formbuilder&prid=%id%&mode=eval",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "form",
                  type: "redirect",
                  name: "edit"
                }
              ],
              filters: []
            },
            {
              title: "COM_EMUNDUS_FORM_PAGE_MODELS",
              key: "form_models",
              controller: "formbuilder",
              getter: "getallmodels",
              noData: "COM_EMUNDUS_ONBOARD_NOFORM",
              actions: [
                {
                  action: "deleteformmodelfromids",
                  label: "COM_EMUNDUS_ACTIONS_DELETE",
                  controller: "formbuilder",
                  parameters: "&model_ids=%id%",
                  name: "delete",
                  method: "delete"
                },
                {
                  action: "/index.php?option=com_emundus&view=form&layout=formbuilder&prid=%form_id%&mode=models",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "form",
                  type: "redirect",
                  name: "edit"
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
const _hoisted_1 = { id: "forms-list" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_list = resolveComponent("list");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_list, {
      "default-lists": $options.configString,
      "default-type": "forms"
    }, null, 8, ["default-lists"])
  ]);
}
const Forms = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Forms as default
};
