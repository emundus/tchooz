import list from "./List.js";
import { _ as _export_sfc, r as resolveComponent, c as createElementBlock, o as openBlock, g as createVNode } from "./app_emundus.js";
import "./Skeleton.js";
import "./Calendar.js";
import "./core.js";
import "./events2.js";
const _sfc_main = {
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
      "default-type": "emails"
    }, null, 8, ["default-lists"])
  ]);
}
const Emails = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Emails as default
};
