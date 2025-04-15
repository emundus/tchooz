import list from "./List.js";
import { _ as _export_sfc, r as resolveComponent, o as openBlock, c as createElementBlock, h as createVNode } from "./app_emundus.js";
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
              viewsOptions: [
                { value: "table", icon: "dehaze" },
                { value: "calendar", icon: "calendar_today" }
              ],
              noData: "COM_EMUNDUS_ONBOARD_NO_REGISTRANTS",
              displaySearch: false,
              actions: [
                {
                  action: "addregistrant",
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANT_ADD",
                  component: "EditSlot",
                  name: "add",
                  type: "modal",
                  acl: "booking|c"
                },
                {
                  action: "index.php?option=com_emundus&view=events&layout=add&event=%id%",
                  label: "COM_EMUNDUS_ONBOARD_MODIFY",
                  controller: "events",
                  type: "redirect",
                  name: "edit",
                  view: "calendar",
                  calendarView: "week"
                },
                {
                  action: "editslot",
                  type: "modal",
                  component: "EditSlot",
                  name: "editslot",
                  multiple: false,
                  icon: "edit",
                  acl: "booking|u"
                },
                {
                  action: "deletebooking",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_DELETE",
                  controller: "events",
                  name: "delete",
                  method: "delete",
                  multiple: true,
                  confirm: "COM_EMUNDUS_ONBOARD_REGISTRANT_DELETE_CONFIRM",
                  icon: "delete_outline",
                  iconOutlined: true,
                  buttonClasses: "tw-group tw-bg-red-500 tw-border-red-500 tw-text-white hover:tw-bg-white hover:tw-border-red-500",
                  spanClasses: "group-hover:tw-text-red-500",
                  acl: "booking|d"
                },
                {
                  action: "resend",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_RESEND",
                  controller: "events",
                  name: "resend",
                  method: "post",
                  multiple: true,
                  confirm: "COM_EMUNDUS_ONBOARD_REGISTRANT_RESEND_CONFIRM",
                  acl: "booking|u"
                },
                {
                  action: "associate",
                  label: "COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_ASSOCIATE",
                  type: "modal",
                  component: "AssociateUser",
                  name: "associateuser",
                  multiple: true,
                  acl: "booking|u"
                }
              ],
              filters: [
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_ALL",
                  getter: "getfilterevents",
                  controller: "events",
                  key: "event",
                  values: null,
                  multiselect: true
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_APPLICANT_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_APPLICANT_ALL",
                  getter: "getfilterapplicants",
                  controller: "events",
                  key: "applicant",
                  values: null,
                  multiselect: true
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_DAY_LABEL",
                  type: "date",
                  key: "day"
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_HOUR_LABEL",
                  type: "time",
                  key: "hour"
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_ALL",
                  getter: "getlocations",
                  controller: "events",
                  key: "location",
                  values: null,
                  multiselect: true
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ROOM_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ROOM_ALL",
                  getter: "getfilterrooms",
                  controller: "events",
                  key: "room",
                  values: null,
                  multiselect: true
                },
                {
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ASSOC_USER_LABEL",
                  allLabel: "COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ASSOC_USER_ALL",
                  getter: "getfilterassocusers",
                  controller: "events",
                  key: "assoc_user",
                  values: null,
                  multiselect: true
                }
              ],
              exports: [
                {
                  action: "exportpdf",
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_EXPORTS_EMARGEMENT",
                  controller: "events",
                  name: "exportpdf",
                  method: "get",
                  multiple: true,
                  exportModal: true
                },
                {
                  action: "exportexcel",
                  label: "COM_EMUNDUS_ONBOARD_REGISTRANTS_EXPORTS_EXCEL",
                  controller: "events",
                  name: "exportcsv",
                  method: "get",
                  multiple: true,
                  exportModal: true
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
