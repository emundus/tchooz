import list from "./List.js";
import { _ as _export_sfc, e as resolveComponent, o as openBlock, c as createElementBlock, g as createVNode } from "./app_emundus.js";
import "./Skeleton.js";
const _sfc_main = {
  name: "Campaigns",
  components: {
    list
  },
  data() {
    return {
      config: {
        "campaigns": {
          "title": "COM_EMUNDUS_ONBOARD_CAMPAIGNS",
          "tabs": [
            {
              "title": "COM_EMUNDUS_ONBOARD_CAMPAIGNS",
              "key": "campaign",
              "controller": "campaign",
              "getter": "getallcampaign",
              "actions": [
                {
                  "action": "index.php?option=com_emundus&view=campaigns&layout=add",
                  "label": "COM_EMUNDUS_ONBOARD_ADD_CAMPAIGN",
                  "controller": "campaign",
                  "name": "add",
                  "type": "redirect"
                },
                {
                  "action": "duplicatecampaign",
                  "label": "COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE",
                  "controller": "campaign",
                  "name": "duplicate"
                },
                {
                  "action": "index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=%id%",
                  "label": "COM_EMUNDUS_ONBOARD_MODIFY",
                  "controller": "campaign",
                  "type": "redirect",
                  "name": "edit"
                },
                {
                  "action": "deletecampaign",
                  "label": "COM_EMUNDUS_ONBOARD_ACTION_DELETE",
                  "controller": "campaign",
                  "name": "delete",
                  "confirm": "COM_EMUNDUS_ONBOARD_CAMPDELETE",
                  "showon": {
                    "key": "nb_files",
                    "operator": "<",
                    "value": "1"
                  }
                },
                {
                  "action": "unpublishcampaign",
                  "label": "COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH",
                  "controller": "campaign",
                  "name": "unpublish",
                  "showon": {
                    "key": "published",
                    "operator": "=",
                    "value": "1"
                  }
                },
                {
                  "action": "publishcampaign",
                  "label": "COM_EMUNDUS_ONBOARD_ACTION_PUBLISH",
                  "controller": "campaign",
                  "name": "publish",
                  "showon": {
                    "key": "published",
                    "operator": "=",
                    "value": "0"
                  }
                }
              ],
              "filters": [
                {
                  "label": "COM_EMUNDUS_ONBOARD_FILTER_ALL",
                  "getter": "",
                  "controller": "campaigns",
                  "key": "filter",
                  "values": [
                    {
                      "label": "COM_EMUNDUS_ONBOARD_FILTER_ALL",
                      "value": "all"
                    },
                    {
                      "label": "COM_EMUNDUS_CAMPAIGN_YET_TO_COME",
                      "value": "yettocome"
                    },
                    {
                      "label": "COM_EMUNDUS_ONBOARD_FILTER_OPEN",
                      "value": "ongoing"
                    },
                    {
                      "label": "COM_EMUNDUS_ONBOARD_FILTER_CLOSE",
                      "value": "Terminated"
                    },
                    {
                      "label": "COM_EMUNDUS_ONBOARD_FILTER_PUBLISH",
                      "value": "Publish"
                    },
                    {
                      "label": "COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH",
                      "value": "Unpublish"
                    }
                  ],
                  "default": "Publish"
                },
                {
                  "label": "COM_EMUNDUS_ONBOARD_ALL_PROGRAMS",
                  "getter": "getallprogramforfilter",
                  "controller": "programme",
                  "key": "program",
                  "values": null
                }
              ]
            },
            {
              "title": "COM_EMUNDUS_ONBOARD_PROGRAMS",
              "key": "programs",
              "controller": "programme",
              "getter": "getallprogram",
              "actions": [
                {
                  "action": "index.php?option=com_fabrik&view=form&formid=108",
                  "controller": "programme",
                  "label": "COM_EMUNDUS_ONBOARD_ADD_PROGRAM",
                  "name": "add",
                  "type": "redirect"
                },
                {
                  "action": "/campaigns/edit-program?id=%id%",
                  "label": "COM_EMUNDUS_ONBOARD_MODIFY",
                  "controller": "programme",
                  "type": "redirect",
                  "name": "edit"
                }
              ],
              "filters": [
                {
                  "label": "COM_EMUNDUS_ONBOARD_ALL_PROGRAM_CATEGORIES",
                  "getter": "getprogramcategories",
                  "controller": "programme",
                  "key": "category",
                  "values": null
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
const _hoisted_1 = { id: "campaigns-list" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_list = resolveComponent("list");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_list, {
      "default-lists": $options.configString,
      "default-type": "campaigns"
    }, null, 8, ["default-lists"])
  ]);
}
const Campaigns = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Campaigns as default
};
