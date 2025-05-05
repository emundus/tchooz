import list from "./List.js";
import { u as useCampaignStore } from "./campaign.js";
import { _ as _export_sfc, X as campaignService, r as resolveComponent, o as openBlock, c as createElementBlock, a as createBlock } from "./app_emundus.js";
import "./ExportSlotsModal.js";
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
  name: "Campaigns",
  components: {
    list
  },
  data() {
    return {
      campaignActivated: null,
      renderingKey: 1,
      config: {
        campaigns: {
          title: "COM_EMUNDUS_ONBOARD_CAMPAIGNS",
          tabs: [
            {
              title: "COM_EMUNDUS_ONBOARD_CAMPAIGNS",
              key: "campaign",
              controller: "campaign",
              getter: "getallcampaign",
              noData: "COM_EMUNDUS_ONBOARD_NOCAMPAIGN",
              actions: [
                {
                  action: "index.php?option=com_emundus&view=campaigns&layout=add",
                  label: "COM_EMUNDUS_ONBOARD_ADD_CAMPAIGN",
                  controller: "campaign",
                  name: "add",
                  type: "redirect"
                },
                {
                  action: "duplicatecampaign",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE",
                  controller: "campaign",
                  name: "duplicate",
                  method: "post"
                },
                {
                  action: "index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=%id%",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "campaign",
                  type: "redirect",
                  name: "edit"
                },
                {
                  action: "deletecampaign",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_DELETE",
                  controller: "campaign",
                  name: "delete",
                  multiple: true,
                  method: "delete",
                  confirm: "COM_EMUNDUS_ONBOARD_CAMPDELETE",
                  showon: {
                    key: "nb_files",
                    operator: "<",
                    value: "1"
                  }
                },
                {
                  action: "unpublishcampaign",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH",
                  controller: "campaign",
                  name: "unpublish",
                  multiple: true,
                  method: "post",
                  showon: {
                    key: "published",
                    operator: "=",
                    value: "1"
                  }
                },
                {
                  action: "publishcampaign",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_PUBLISH",
                  controller: "campaign",
                  name: "publish",
                  multiple: true,
                  method: "post",
                  showon: {
                    key: "published",
                    operator: "=",
                    value: "0"
                  }
                }
              ],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_CAMPAIGNS_FILTER_PUBLISH",
                  allLabel: "COM_EMUNDUS_ONBOARD_FILTER_ALL",
                  alwaysDisplay: true,
                  getter: "",
                  controller: "campaigns",
                  key: "filter",
                  values: [
                    {
                      label: "COM_EMUNDUS_ONBOARD_FILTER_ALL",
                      value: "all"
                    },
                    {
                      label: "COM_EMUNDUS_CAMPAIGN_YET_TO_COME",
                      value: "yettocome"
                    },
                    {
                      label: "COM_EMUNDUS_ONBOARD_FILTER_OPEN",
                      value: "ongoing"
                    },
                    {
                      label: "COM_EMUNDUS_ONBOARD_FILTER_CLOSE",
                      value: "Terminated"
                    },
                    {
                      label: "COM_EMUNDUS_ONBOARD_FILTER_PUBLISH",
                      value: "Publish"
                    },
                    {
                      label: "COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH",
                      value: "Unpublish"
                    }
                  ],
                  default: "Publish"
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_CAMPAIGNS_FILTER_PROGRAMS",
                  allLabel: "COM_EMUNDUS_ONBOARD_ALL_PROGRAMS",
                  alwaysDisplay: true,
                  getter: "getallprogramforfilter",
                  controller: "programme",
                  key: "program",
                  values: null
                }
              ]
            },
            {
              title: "COM_EMUNDUS_ONBOARD_PROGRAMS",
              key: "programs",
              controller: "programme",
              getter: "getallprogram",
              noData: "COM_EMUNDUS_ONBOARD_NOPROGRAM",
              actions: [
                {
                  action: "index.php?option=com_fabrik&view=form&formid=108",
                  controller: "programme",
                  label: "COM_EMUNDUS_ONBOARD_ADD_PROGRAM",
                  name: "add",
                  type: "redirect"
                },
                {
                  action: "index.php?option=com_emundus&view=programme&layout=edit&id=%id%",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "programme",
                  type: "redirect",
                  name: "edit"
                }
              ],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_ALL_PROGRAM_CATEGORIES_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_ALL_PROGRAM_CATEGORIES",
                  getter: "getprogramcategories",
                  controller: "programme",
                  key: "category",
                  alwaysDisplay: true,
                  values: null
                }
              ]
            }
          ]
        }
      },
      importAction: {
        action: "importfiles",
        label: "COM_EMUNDUS_ONBOARD_ACTION_IMPORT_FILES",
        type: "modal",
        component: "Import",
        name: "import",
        multiple: false
      }
    };
  },
  created() {
    if (useCampaignStore().getActivated === null) {
      campaignService.isImportActivated().then((response) => {
        this.campaignActivated = response.data;
        if (this.campaignActivated) {
          useCampaignStore().updateActivated(true);
          this.config.campaigns.tabs[0].actions.push(this.importAction);
          this.renderingKey++;
        } else {
          useCampaignStore().updateActivated(false);
        }
      });
    } else {
      this.campaignActivated = useCampaignStore().getActivated;
    }
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
    (openBlock(), createBlock(_component_list, {
      "default-lists": $options.configString,
      "default-type": "campaigns",
      key: $data.renderingKey
    }, null, 8, ["default-lists"]))
  ]);
}
const Campaigns = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Campaigns as default
};
