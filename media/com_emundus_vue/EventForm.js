import { _ as _export_sfc, s as settingsService, u as useGlobalStore, r as resolveComponent, c as createElementBlock, o as openBlock, a as createBlock, b as createCommentVNode, d as createBaseVNode, F as Fragment, e as renderList, w as withDirectives, v as vShow, n as normalizeClass, t as toDisplayString, S as Swal$1, M as Modal, P as Popover, f as withCtx, g as createVNode, h as withModifiers, i as shallowRef, j as normalizeStyle, k as nextTick, l as emailService, T as Tabs } from "./app_emundus.js";
import { P as Parameter, d as dayjs } from "./Parameter.js";
import { L as LocationPopup } from "./LocationPopup.js";
import { C as ColorPicker } from "./ColorPicker.js";
import { I as Info } from "./Info.js";
import { e as eventsService } from "./events2.js";
import { D as DatePicker } from "./index.js";
import { _ as _o, E as EventDay, c as createEventsServicePlugin, a as createCalendar, m as mergeLocales, b as createCalendarControlsPlugin, d as createViewWeek, v as viewWeek, t as translations } from "./core.js";
import "./EventBooking.js";
import "./LocationForm.js";
const _sfc_main$5 = {
  name: "EventGlobalSettings",
  components: { ColorPicker, Info, LocationPopup, Parameter },
  emits: ["reload-event"],
  props: {
    event: Object
  },
  data() {
    return {
      loading: true,
      openedLocationPopup: false,
      teamsEnabled: false,
      teamsPublished: false,
      settingsLink: "",
      eventsNames: [],
      eventColor: "",
      fields: [
        {
          param: "name",
          type: "text",
          maxlength: 150,
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_NAME",
          helptext: "",
          displayed: true
        },
        {
          param: "location",
          type: "select",
          placeholder: "",
          value: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_LOCATION",
          helptext: "",
          displayed: true,
          options: [],
          reload: 0
        },
        {
          param: "is_conference_link",
          type: "toggle",
          value: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_IS_CONFERENCE_LINK",
          iconLabel: "videocam",
          displayed: true,
          hideLabel: true,
          optional: true
        },
        {
          param: "conference_engine",
          type: "radiobutton",
          value: null,
          default: "link",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE",
          displayed: false,
          displayedOn: "is_conference_link",
          displayedOnValue: 1,
          hideRadio: true,
          optional: true,
          options: [
            {
              value: "link",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_LINK"
            },
            {
              value: "teams",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_TEAMS",
              img: "teams.svg",
              altImg: "Microsoft Teams"
            }
          ]
        },
        {
          param: "link",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_LINK",
          helptext: "",
          displayed: false,
          displayedOn: "conference_engine",
          displayedOnValue: "link"
        },
        {
          param: "generate_link_by",
          type: "select",
          placeholder: "",
          default: 1,
          value: 1,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_GENERATE_LINK_BY",
          helptext: "",
          displayed: false,
          displayedOn: "conference_engine",
          displayedOnValue: "teams",
          options: [
            {
              value: 1,
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_GENERATE_LINK_BY_RESERVATION"
            },
            {
              value: 2,
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_GENERATE_LINK_BY_SLOT"
            }
          ]
        },
        {
          param: "teams_subject",
          type: "text",
          placeholder: "",
          default: "Entretien de [APPLICANT_NAME]",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_TEAMS_SUBJECT",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_TEAMS_SUBJECT_HELPTEXT",
          helpTextType: "icon",
          displayed: false,
          displayedOn: "conference_engine",
          displayedOnValue: "teams"
        },
        {
          param: "manager",
          type: "multiselect",
          multiselectOptions: {
            noOptions: false,
            multiple: false,
            taggable: false,
            searchable: true,
            internalSearch: false,
            asyncRoute: "getavailablemanagers",
            optionsPlaceholder: "",
            selectLabel: "",
            selectGroupLabel: "",
            selectedLabel: "",
            deselectedLabel: "",
            deselectGroupLabel: "",
            noOptionsText: "",
            noResultsText: "COM_EMUNDUS_MULTISELECT_NORESULTS",
            tagValidations: [],
            options: [],
            optionsLimit: 30,
            label: "name",
            trackBy: "value"
          },
          value: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_MANAGER",
          placeholder: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_MANAGER_PLACEHOLDER",
          displayed: true,
          optional: true
        },
        {
          param: "available_for",
          type: "radiobutton",
          value: 1,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_AVAILABLE_FOR",
          displayed: true,
          options: [
            {
              value: 1,
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_AVAILABLE_FOR_CAMPAIGNS"
            },
            {
              value: 2,
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_AVAILABLE_FOR_PROGRAMS"
            }
          ]
        },
        {
          param: "campaigns",
          type: "multiselect",
          multiselectOptions: {
            noOptions: false,
            multiple: true,
            taggable: false,
            searchable: true,
            internalSearch: false,
            asyncRoute: "getavailablecampaigns",
            optionsPlaceholder: "",
            selectLabel: "",
            selectGroupLabel: "",
            selectedLabel: "",
            deselectedLabel: "",
            deselectGroupLabel: "",
            noOptionsText: "",
            noResultsText: "COM_EMUNDUS_MULTISELECT_NORESULTS",
            tagValidations: [],
            options: [],
            optionsLimit: 30,
            label: "name",
            trackBy: "value"
          },
          value: [],
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CAMPAIGNS",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CAMPAIGNS_HELPTEXT",
          displayed: false,
          displayedOn: "available_for",
          displayedOnValue: 1,
          optional: true
        },
        {
          param: "programs",
          type: "multiselect",
          multiselectOptions: {
            noOptions: false,
            multiple: true,
            taggable: false,
            searchable: true,
            internalSearch: false,
            asyncRoute: "getavailableprograms",
            optionsPlaceholder: "",
            selectLabel: "",
            selectGroupLabel: "",
            selectedLabel: "",
            deselectedLabel: "",
            deselectGroupLabel: "",
            noOptionsText: "",
            noResultsText: "COM_EMUNDUS_MULTISELECT_NORESULTS",
            tagValidations: [],
            options: [],
            optionsLimit: 30,
            label: "name",
            trackBy: "value"
          },
          value: [],
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_PROGRAMS",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_PROGRAMS_HELPTEXT",
          displayed: false,
          displayedOn: "available_for",
          displayedOnValue: 2,
          optional: true
        }
      ]
    };
  },
  created: function() {
    this.getLocations();
    eventsService.getEventsNames().then((response) => {
      if (response.status) {
        this.eventsNames = response.data;
      }
    });
    this.checkTeamsIntegration();
    setInterval(() => {
      this.checkTeamsIntegration();
    }, 8e3);
    this.getSettingsLink();
    if (this.event) {
      this.fields.forEach((field) => {
        if (this.event[field.param]) {
          field.value = this.event[field.param];
        }
      });
      if (this.event.color) {
        this.eventColor = this.event.color;
      }
    }
  },
  methods: {
    // Services
    getLocations(location_id = 0) {
      eventsService.getLocations().then((response) => {
        let options = [
          {
            value: 0,
            label: this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_LOCATION_SELECT")
          }
        ];
        if (response.status) {
          Array.prototype.push.apply(options, response.data);
        }
        this.fields.find((field) => field.param === "location").options = options;
        if (location_id) {
          this.fields.find((field) => field.param === "location").value = location_id;
          this.fields.find((field) => field.param === "location").reload += 1;
        }
        this.loading = false;
      });
    },
    checkTeamsIntegration() {
      settingsService.getApp(0, "teams").then((response) => {
        if (response.status) {
          this.teamsEnabled = response.data.enabled && response.data.config !== "{}";
          this.teamsPublished = response.data.published;
          this.updateConferenceEngineOptions();
        }
      });
    },
    updateConferenceEngineOptions() {
      const conferenceEngineField = this.fields.find((field) => field.param === "conference_engine");
      if (this.teamsPublished) {
        conferenceEngineField.options = [
          {
            value: "link",
            label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_LINK"
          },
          {
            value: "teams",
            label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_TEAMS",
            img: "teams.svg",
            altImg: "Microsoft Teams"
          }
        ];
      } else {
        conferenceEngineField.options = [
          {
            value: "link",
            label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CONFERENCE_ENGINE_LINK"
          }
        ];
      }
    },
    getSettingsLink() {
      settingsService.getSEFLink("index.php?option=com_emundus&view=settings", useGlobalStore().getCurrentLang).then((response) => {
        if (response.status) {
          this.settingsLink = "/" + response.data;
        }
      });
    },
    // Create
    createEvent() {
      let event = {};
      const eventValidationFailed = this.fields.some((field) => {
        if (field.displayed) {
          let ref_name = "event_" + field.param;
          if (field.param === "name") {
            if (this.eventsNames.includes(field.value)) {
              Swal.fire({
                icon: "error",
                title: "Oops...",
                text: Joomla.Text._("COM_EMUNDUS_ONBOARD_ADD_EVENT_NAME_EXISTS"),
                customClass: {
                  title: "em-swal-title",
                  confirmButton: "em-swal-confirm-button",
                  actions: "em-swal-single-action"
                }
              });
              return true;
            }
          }
          if (!this.$refs[ref_name][0].validate()) {
            return true;
          }
          if (field.type === "multiselect") {
            if (field.multiselectOptions.multiple) {
              event[field.param] = [];
              field.value.forEach((element) => {
                event[field.param].push(element.value);
              });
            } else {
              event[field.param] = field.value.value;
            }
          } else {
            event[field.param] = field.value;
          }
          return false;
        }
      });
      if (eventValidationFailed) return;
      event["color"] = this.eventColor;
      eventsService.createEvent(event).then((response) => {
        if (response.status === true) {
          Swal.fire({
            position: "center",
            icon: "success",
            title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_CREATED"),
            showConfirmButton: false,
            allowOutsideClick: false,
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            },
            timer: 1500
          }).then(() => {
            this.$emit("reload-event", response.data, 2);
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set("event", response.data);
            window.history.replaceState({}, "", `${window.location.pathname}?${urlParams}`);
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: response.message
          });
        }
      });
    },
    // Edit
    editEvent(event_id) {
      let event_edited = {};
      const eventValidationFailed = this.fields.some((field) => {
        if (field.displayed) {
          let ref_name = "event_" + field.param;
          if (this.$refs[ref_name] && !this.$refs[ref_name][0].validate()) {
            return true;
          }
          if (field.type === "multiselect") {
            if (field.multiselectOptions.multiple) {
              event_edited[field.param] = [];
              field.value.forEach((element) => {
                event_edited[field.param].push(element.value);
              });
            } else {
              event_edited[field.param] = field.value ? field.value.value : null;
            }
          } else {
            event_edited[field.param] = field.value;
          }
          return false;
        }
      });
      if (eventValidationFailed) return;
      event_edited["id"] = event_id;
      event_edited["color"] = this.eventColor;
      eventsService.editEvent(event_edited).then((response) => {
        if (response.status === true) {
          Swal.fire({
            position: "center",
            icon: "success",
            title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SAVED"),
            showConfirmButton: true,
            allowOutsideClick: false,
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            },
            timer: 1500
          }).then(() => {
            this.$emit("reload-event", response.data, 2);
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: response.message
          });
        }
      });
    },
    // Hooks
    checkConditional(parameter, oldValue, value) {
      let fields = this.fields.filter((field) => field.displayedOn === parameter.param);
      for (let field of fields) {
        field.displayed = field.displayedOnValue == value;
        if (!field.displayed) {
          if (field.default) {
            field.value = field.default;
          } else {
            field.value = "";
          }
          this.checkConditional(field, field.value, "");
        }
      }
    },
    locationPopupClosed(location_id) {
      this.openedLocationPopup = false;
      this.getLocations(location_id);
    }
  },
  computed: {
    disabledSubmit: function() {
      return this.fields.some((field) => {
        if (!field.optional && field.displayed) {
          return field.value === "" || field.value === 0;
        } else {
          return false;
        }
      });
    },
    teamsDisabledText: function() {
      let text = this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_TEAMS_DISABLED");
      text = text.replace("{{settingsLink}}", this.settingsLink + "#integration");
      return text;
    }
  },
  watch: {}
};
const _hoisted_1$5 = { class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6" };
const _hoisted_2$5 = { class: "tw-underline" };
const _hoisted_3$5 = { class: "tw-underline" };
const _hoisted_4$5 = { class: "tw-mt-7 tw-flex tw-justify-end" };
const _hoisted_5$3 = ["disabled"];
const _hoisted_6$1 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_LocationPopup = resolveComponent("LocationPopup");
  const _component_Parameter = resolveComponent("Parameter");
  const _component_color_picker = resolveComponent("color-picker");
  const _component_Info = resolveComponent("Info");
  return openBlock(), createElementBlock("div", null, [
    $data.openedLocationPopup ? (openBlock(), createBlock(_component_LocationPopup, {
      key: 0,
      location_id: $data.fields[1].value,
      onClose: $options.locationPopupClosed
    }, null, 8, ["location_id", "onClose"])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_1$5, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.fields, (field) => {
        return withDirectives((openBlock(), createElementBlock("div", {
          key: field.param,
          class: normalizeClass({
            "tw-flex tw-w-1/2 tw-items-end tw-justify-between tw-gap-2": field.param === "name",
            "tw-w-full": field.param !== "name"
          })
        }, [
          field.displayed ? (openBlock(), createBlock(_component_Parameter, {
            class: normalizeClass({ "tw-w-full": field.param === "name" }),
            ref_for: true,
            ref: "event_" + field.param,
            key: field.reload ? field.reload : field.param,
            "parameter-object": field,
            "help-text-type": field.helpTextType ? field.helpTextType : "above",
            "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null,
            onValueUpdated: $options.checkConditional
          }, null, 8, ["class", "parameter-object", "help-text-type", "multiselect-options", "onValueUpdated"])) : createCommentVNode("", true),
          field.param === "name" ? (openBlock(), createBlock(_component_color_picker, {
            key: 1,
            modelValue: $data.eventColor,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.eventColor = $event),
            swatches: "dark",
            "row-length": 8,
            id: "status_swatches",
            random: true,
            style: { "top": "-8px" }
          }, null, 8, ["modelValue"])) : createCommentVNode("", true),
          field.param === "conference_engine" && field.value === "teams" && !$data.teamsEnabled ? (openBlock(), createBlock(_component_Info, {
            key: 2,
            "parameter-object": field,
            text: $options.teamsDisabledText,
            icon: "warning",
            "bg-color": "tw-bg-orange-100",
            "icon-type": "material-symbols-outlined",
            "icon-color": "tw-text-orange-600",
            class: normalizeClass("tw-mt-2")
          }, null, 8, ["parameter-object", "text"])) : createCommentVNode("", true),
          field.param === "location" && field.value === 0 ? (openBlock(), createElementBlock("button", {
            key: 3,
            type: "button",
            class: "tw-mt-2 tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-text-blue-500",
            onClick: _cache[1] || (_cache[1] = ($event) => $data.openedLocationPopup = true)
          }, [
            _cache[4] || (_cache[4] = createBaseVNode("span", { class: "material-symbols-outlined !tw-text-blue-500" }, "add", -1)),
            createBaseVNode("span", _hoisted_2$5, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_ADD_LOCATION")), 1)
          ])) : field.param === "location" ? (openBlock(), createElementBlock("button", {
            key: 4,
            type: "button",
            class: "tw-mt-2 tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-text-blue-500",
            onClick: _cache[2] || (_cache[2] = ($event) => $data.openedLocationPopup = true)
          }, [
            createBaseVNode("span", _hoisted_3$5, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EDIT_LOCATION")), 1)
          ])) : createCommentVNode("", true)
        ], 2)), [
          [vShow, field.displayed]
        ]);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_4$5, [
      createBaseVNode("button", {
        type: "button",
        disabled: $options.disabledSubmit,
        class: "tw-btn-primary tw-cursor-pointer",
        onClick: _cache[3] || (_cache[3] = ($event) => this.$props.event && Object.keys(this.$props.event).length > 0 ? $options.editEvent(this.$props.event["id"]) : $options.createEvent())
      }, toDisplayString(this.$props.event && Object.keys(this.$props.event).length > 0 ? _ctx.translate("COM_EMUNDUS_ONBOARD_EDIT_EVENT_GLOBAL_CREATE") : _ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_CREATE")), 9, _hoisted_5$3)
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_6$1)) : createCommentVNode("", true)
  ]);
}
const EventGlobalSettings = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5]]);
const _sfc_main$4 = {
  name: "EventSlotsSettings",
  components: { Parameter },
  props: {
    event: Object
  },
  emits: ["reload-event"],
  data() {
    return {
      loading: true,
      formChanged: false,
      duration_fields: [
        {
          param: "slot_duration",
          type: "text",
          placeholder: "",
          value: "",
          concatValue: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HELP",
          displayed: true,
          splitField: true,
          secondParameterType: "select",
          secondParameterDefault: "minutes",
          secondParameterOptions: [
            {
              value: "minutes",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES"
            },
            {
              value: "hours",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS"
            }
          ],
          splitChar: " "
        }
      ],
      break_fields: [
        {
          param: "slot_break_every",
          type: "text",
          placeholder: "",
          value: "",
          concatValue: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_EVERY",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_EVERY_HELP",
          displayed: true,
          optional: true,
          endText: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_EVERY_END_TEXT"
        },
        {
          param: "slot_break_time",
          type: "text",
          placeholder: "",
          value: "",
          concatValue: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_TIME",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_BREAK_TIME_HELP",
          displayed: true,
          optional: true,
          splitField: true,
          secondParameterType: "select",
          secondParameterDefault: "minutes",
          secondParameterOptions: [
            {
              value: "minutes",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES"
            },
            {
              value: "hours",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS"
            }
          ],
          splitChar: " "
        }
      ],
      more_fields: [
        {
          param: "slots_availables_to_show",
          type: "select",
          placeholder: "",
          value: 0,
          concatValue: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_AVAILABLE_TO_SHOW",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_AVAILABLE_TO_SHOW_HELP",
          helpTextType: "icon",
          displayed: true,
          optional: true,
          options: [
            { value: 0, label: "COM_EMUNDUS_ONBOARD_EVENTS_SLOTS_ALL" },
            { value: 1, label: "1" },
            { value: 2, label: "2" },
            { value: 3, label: "3" },
            { value: 4, label: "4" },
            { value: 5, label: "5" },
            { value: 10, label: "10" },
            { value: 20, label: "20" },
            { value: 50, label: "50" },
            { value: 100, label: "100" }
          ]
        },
        {
          param: "slot_can_book_until",
          type: "text",
          placeholder: "",
          value: "",
          concatValue: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL",
          displayed: true,
          splitField: true,
          secondParameterType: "select",
          secondParameterDefault: "days",
          secondParameterOptions: [
            {
              value: "days",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL_DAYS"
            },
            {
              value: "date",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL_DATE"
            }
          ],
          splitChar: " ",
          optional: true
        },
        {
          param: "slot_can_cancel",
          type: "toggle",
          value: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_CANCEL",
          hideLabel: true,
          displayed: true,
          optional: true
        },
        {
          param: "slot_can_cancel_until",
          type: "text",
          placeholder: "",
          value: "",
          concatValue: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_CANCEL_UNTIL",
          displayed: false,
          displayedOn: "slot_can_cancel",
          displayedOnValue: 1,
          splitField: true,
          secondParameterType: "select",
          secondParameterDefault: "days",
          secondParameterOptions: [
            {
              value: "days",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_CANCEL_UNTIL_DAYS"
            },
            {
              value: "date",
              label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAN_BOOK_UNTIL_DATE"
            }
          ],
          splitChar: " "
        }
      ]
    };
  },
  mounted() {
  },
  created() {
    for (let field of this.duration_fields) {
      if (this.event[field.param]) {
        field.value = this.event[field.param] + field.splitChar + this.event["slot_duration_type"];
        field.concatValue = this.event["slot_duration_type"];
      }
    }
    for (let field of this.break_fields) {
      if (this.event[field.param]) {
        if (field.param == "slot_break_time") {
          field.value = this.event[field.param] + field.splitChar + this.event["slot_break_time_type"];
          field.concatValue = this.event["slot_break_time_type"];
        } else {
          field.value = this.event[field.param];
        }
      }
    }
    for (let field of this.more_fields) {
      if (field.param === "slot_can_book_until") {
        if (this.event["slot_can_book_until_days"]) {
          field.value = this.event["slot_can_book_until_days"] + field.splitChar + "days";
          field.concatValue = "days";
        } else if (this.event["slot_can_book_until_date"]) {
          field.value = dayjs(this.event["slot_can_book_until_date"]).format("YYYY-MM-DD") + field.splitChar + "date";
          field.concatValue = "date";
        }
      } else if (field.param === "slot_can_cancel_until") {
        if (this.event["slot_can_cancel_until_days"]) {
          field.value = this.event["slot_can_cancel_until_days"] + field.splitChar + "days";
          field.concatValue = "days";
        } else if (this.event["slot_can_cancel_until_date"]) {
          field.value = dayjs(this.event["slot_can_cancel_until_date"]).format("YYYY-MM-DD") + field.splitChar + "date";
          field.concatValue = "date";
        }
      } else {
        field.value = this.event[field.param];
      }
    }
    this.loading = false;
  },
  methods: {
    checkConditional(parameter, oldValue, value) {
      let fields = this.more_fields.filter((field) => field.displayedOn === parameter.param);
      for (let field of fields) {
        field.displayed = field.displayedOnValue == value;
        if (!field.displayed) {
          if (field.default) {
            field.value = field.default;
          } else {
            field.value = "";
          }
        }
      }
    },
    setupSlots() {
      let slot = {};
      let fields = this.duration_fields.concat(this.break_fields).concat(this.more_fields);
      const slotValidationFailed = fields.some((field) => {
        if (field.displayed) {
          let ref_name = "event_slot_settings_" + field.param;
          if (!this.$refs[ref_name][0].validate()) {
            return true;
          }
          if (field.type === "multiselect") {
            if (field.multiselectOptions.multiple) {
              slot[field.param] = [];
              field.value.forEach((element) => {
                slot[field.param].push(element.value);
              });
            } else {
              slot[field.param] = field.value.value;
            }
          } else if ((field.param === "slot_can_book_until" || field.param === "slot_can_cancel_until") && field.concatValue.split(" ").slice(-1)[0] === "date") {
            slot[field.param] = dayjs(field.value).format("YYYY-MM-DD") + field.splitChar + "date";
          } else {
            if (field.concatValue) {
              slot[field.param] = field.concatValue;
            } else {
              slot[field.param] = field.value;
            }
          }
          return false;
        }
      });
      if (slotValidationFailed) return;
      slot.event_id = this.event.id;
      eventsService.setupSlot(slot).then((response) => {
        if (response.status === true) {
          Swal$1.fire({
            position: "center",
            icon: "success",
            title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_SETUP_SUCCESS"),
            showConfirmButton: false,
            allowOutsideClick: false,
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            },
            timer: 1500
          }).then(() => {
            this.$emit("reload-event", this.event.id, 3);
          });
        } else {
          Swal$1.fire({
            icon: "error",
            title: "Oops...",
            text: response.message
          });
        }
      });
    },
    onFormChange(parameter, oldValue, value) {
      if (oldValue !== null && oldValue !== value && !this.formChanged) {
        this.formChanged = true;
      }
    },
    handleMoreFieldsValueUpdated(parameter, oldValue, value) {
      this.checkConditional(parameter, oldValue, value);
      this.onFormChange(parameter, oldValue, value);
    },
    handleBeforeUnload() {
      var links = [];
      var logo = document.querySelectorAll("#header-a a");
      var menu_items = document.querySelectorAll("#header-b a");
      var user_items = document.querySelectorAll("#userDropdown a");
      var footer_items = document.querySelectorAll("#g-footer a");
      var back_button_form = document.querySelectorAll(".goback-btn");
      links = [...menu_items, ...user_items, ...logo, ...footer_items, ...back_button_form];
      for (var i = 0, len = links.length; i < len; i++) {
        links[i].onclick = (e) => {
          if (this.formChanged) {
            e.preventDefault();
            Swal$1.fire({
              title: this.translate("COM_EMUNDUS_WANT_EXIT_FORM_TITLE"),
              html: this.translate("COM_EMUNDUS_WANT_EXIT_FORM_TEXT"),
              showCloseButton: false,
              showCancelButton: true,
              confirmButtonText: this.translate("COM_EMUNDUS_WANT_EXIT_FORM_YES"),
              cancelButtonText: this.translate("COM_EMUNDUS_WANT_EXIT_FORM_NO"),
              reverseButtons: true,
              customClass: {
                title: "em-swal-title",
                cancelButton: "em-swal-cancel-button",
                confirmButton: "em-swal-confirm-button"
              }
            }).then((result) => {
              if (result.value) {
                if (e.srcElement.classList.contains("goback-btn")) {
                  window.history.back();
                }
                let href = window.location.origin + "/index.php";
                if (typeof e.target.href !== "undefined") {
                  href = e.target.href;
                } else {
                  e = e.target;
                  let attempt = 0;
                  do {
                    e = e.parentNode;
                  } while (typeof e.href === "undefined" && attempt++ < 5);
                  if (typeof e.href !== "undefined") {
                    href = e.href;
                  }
                }
                window.location.href = href;
              }
            });
          }
        };
      }
    }
  },
  computed: {
    disabledSubmit: function() {
      let fields = this.duration_fields.concat(this.break_fields).concat(this.more_fields);
      return fields.some((field) => {
        if (field.displayed && !field.optional) {
          return field.value === "" || field.value === 0;
        } else {
          return false;
        }
      });
    }
  }
};
const _hoisted_1$4 = {
  key: 0,
  class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6"
};
const _hoisted_2$4 = {
  class: "tw-grid tw-justify-between",
  style: { "grid-template-columns": "repeat(2, 47%)" }
};
const _hoisted_3$4 = { class: "tw-flex tw-flex-col tw-gap-6" };
const _hoisted_4$4 = { class: "tw-mt-7 tw-flex tw-justify-end" };
const _hoisted_5$2 = ["disabled"];
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Parameter = resolveComponent("Parameter");
  return openBlock(), createElementBlock("div", null, [
    !this.loading ? (openBlock(), createElementBlock("div", _hoisted_1$4, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.duration_fields, (field) => {
        return withDirectives((openBlock(), createElementBlock("div", {
          key: field.param,
          class: "tw-w-[51%]"
        }, [
          field.displayed ? (openBlock(), createBlock(_component_Parameter, {
            key: 0,
            ref_for: true,
            ref: "event_slot_settings_" + field.param,
            "parameter-object": field,
            "help-text-type": "above",
            "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null,
            onValueUpdated: $options.onFormChange
          }, null, 8, ["parameter-object", "multiselect-options", "onValueUpdated"])) : createCommentVNode("", true)
        ])), [
          [vShow, field.displayed]
        ]);
      }), 128)),
      createBaseVNode("div", _hoisted_2$4, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.break_fields, (field) => {
          return withDirectives((openBlock(), createElementBlock("div", {
            key: field.param,
            class: "tw-w-full"
          }, [
            field.displayed ? (openBlock(), createBlock(_component_Parameter, {
              key: 0,
              ref_for: true,
              ref: "event_slot_settings_" + field.param,
              "parameter-object": field,
              "help-text-type": field.helpTextType ? field.helpTextType : "above",
              "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null,
              onValueUpdated: $options.onFormChange
            }, null, 8, ["parameter-object", "help-text-type", "multiselect-options", "onValueUpdated"])) : createCommentVNode("", true)
          ])), [
            [vShow, field.displayed]
          ]);
        }), 128))
      ]),
      createBaseVNode("div", _hoisted_3$4, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.more_fields, (field) => {
          return withDirectives((openBlock(), createElementBlock("div", {
            key: field.param,
            class: "tw-w-full"
          }, [
            field.displayed ? (openBlock(), createBlock(_component_Parameter, {
              key: 0,
              class: normalizeClass([
                field.param === "slot_can_book_until" || field.param === "slot_can_cancel_until" ? "tw-w-1/2" : "tw-w-full"
              ]),
              ref_for: true,
              ref: "event_slot_settings_" + field.param,
              "parameter-object": field,
              "help-text-type": field.helpTextType ? field.helpTextType : "above",
              "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null,
              onValueUpdated: $options.handleMoreFieldsValueUpdated
            }, null, 8, ["class", "parameter-object", "help-text-type", "multiselect-options", "onValueUpdated"])) : createCommentVNode("", true)
          ])), [
            [vShow, field.displayed]
          ]);
        }), 128))
      ]),
      createBaseVNode("div", _hoisted_4$4, [
        createBaseVNode("button", {
          type: "button",
          class: "tw-btn-primary tw-cursor-pointer",
          disabled: $options.disabledSubmit,
          onClick: _cache[0] || (_cache[0] = (...args) => $options.setupSlots && $options.setupSlots(...args))
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CREATE")), 9, _hoisted_5$2)
      ])
    ])) : createCommentVNode("", true)
  ]);
}
const EventSlotsSettings = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4]]);
const _sfc_main$3 = {
  name: "CalendarSlotPopup",
  emits: ["close", "open", "slot-saved", "slot-deleted"],
  components: { Info, Popover, DatePicker, Parameter, Modal },
  props: {
    date: {
      type: String,
      default: ""
    },
    slot: {
      type: Object,
      default: null
    },
    eventId: {
      type: Number,
      default: 0
    },
    locationId: {
      type: Number,
      default: 0
    },
    duration: {
      type: Number,
      default: 0
    },
    duration_type: {
      type: String,
      default: 0
    },
    break_every: {
      type: Number,
      default: 0
    },
    break_time: {
      type: Number,
      default: 0
    },
    break_time_type: {
      type: String,
      default: 0
    }
  },
  data() {
    return {
      loading: true,
      showRepeat: false,
      displayPopover: false,
      durationSlotInfo: "",
      actualLanguage: "fr-FR",
      rooms: [],
      fields: [
        {
          param: "users",
          type: "multiselect",
          multiselectOptions: {
            noOptions: false,
            multiple: true,
            taggable: false,
            searchable: true,
            internalSearch: false,
            asyncRoute: "getavailablemanagers",
            optionsPlaceholder: "",
            selectLabel: "",
            selectGroupLabel: "",
            selectedLabel: "",
            deselectedLabel: "",
            deselectGroupLabel: "",
            noOptionsText: "",
            noResultsText: "COM_EMUNDUS_MULTISELECT_NORESULTS",
            tagValidations: [],
            options: [],
            optionsLimit: 30,
            label: "name",
            trackBy: "value"
          },
          value: 0,
          hideLabel: false,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_ASSOC_USER",
          placeholder: "",
          displayed: true,
          optional: true
        },
        {
          param: "start_date",
          type: "time",
          placeholder: "",
          value: 0,
          hideLabel: false,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_START_DATE",
          helptext: "",
          displayed: true
        },
        {
          param: "end_date",
          type: "time",
          placeholder: "",
          value: 0,
          hideLabel: false,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_END_DATE",
          helptext: "",
          displayed: true
        },
        {
          param: "room",
          type: "select",
          placeholder: "",
          value: 0,
          hideLabel: false,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_ROOM",
          helptext: "",
          displayed: true,
          optional: true,
          options: []
        },
        {
          param: "slot_capacity",
          type: "text",
          value: 1,
          hideLabel: false,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAPACITY",
          placeholder: "",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAPACITY_PLACEHOLDER",
          displayed: true,
          optional: true,
          options: []
        },
        {
          param: "more_infos",
          type: "textarea",
          value: "",
          hideLabel: false,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_MORE_INFOS",
          placeholder: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_MORE_INFOS_PLACEHOLDER",
          helptext: "",
          displayed: true,
          optional: true,
          options: []
        }
      ],
      repeat_dates: [],
      minDate: /* @__PURE__ */ new Date()
    };
  },
  created() {
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getShortLang;
    if (!this.$props.slot) {
      this.fields.find((field) => field.param === "start_date").value = this.roundToQuarter(this.date);
      const date = new Date(this.date);
      if (this.$props.duration_type === "minutes") {
        date.setMinutes(date.getMinutes() + this.$props.duration);
      } else {
        date.setHours(date.getHours() + this.$props.duration);
      }
      this.fields.find((field) => field.param === "end_date").value = this.roundToQuarter(null, date);
      this.minDate = new Date(date);
      this.minDate.setDate(this.minDate.getDate() + 1);
    } else {
      this.fields.find((field) => field.param === "start_date").value = this.$props.slot.start;
      this.fields.find((field) => field.param === "end_date").value = this.$props.slot.end;
      this.minDate = new Date(this.$props.slot.end);
      this.fields.forEach((field) => {
        if (this.$props.slot[field.param] && field.param !== "start_date" && field.param !== "end_date") {
          field.value = this.$props.slot[field.param];
        }
      });
      if (this.$props.slot.repeat_dates && this.$props.slot.repeat_dates.length > 0) {
        this.displayPopover = true;
        this.repeat_dates = this.$props.slot.repeat_dates;
      }
    }
    this.durationSlotInfo = this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_INFO");
    this.durationSlotInfo = this.durationSlotInfo.replace("{{duration}}", this.duration);
    if (this.duration_type === "minutes") {
      this.durationSlotInfo = this.durationSlotInfo.replace(
        "{{duration_type}}",
        this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES")
      );
    } else if (this.duration_type === "hours") {
      this.durationSlotInfo = this.durationSlotInfo.replace(
        "{{duration_type}}",
        this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS")
      );
    }
    this.getRooms();
  },
  methods: {
    beforeClose() {
      this.$emit("close");
    },
    beforeOpen() {
      this.$emit("open");
    },
    getRooms() {
      eventsService.getRooms(this.locationId).then((response) => {
        let options = [
          {
            value: 0,
            label: this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_ROOM_SELECT")
          }
        ];
        if (response.status) {
          Array.prototype.push.apply(options, response.data);
        }
        this.fields.find((field) => field.param === "room").options = options;
        this.loading = false;
      });
    },
    saveSlot(mode = 1) {
      let slot = {};
      const slotValidationFailed = this.fields.some((field) => {
        if (field.displayed) {
          let ref_name = "slot_" + field.param;
          if (!this.$refs[ref_name][0].validate()) {
            return true;
          }
          if (field.type === "time") {
            slot[field.param] = this.formatDate(new Date(field.value));
          } else if (field.type === "multiselect") {
            if (field.multiselectOptions.multiple) {
              slot[field.param] = [];
              field.value.forEach((element) => {
                slot[field.param].push(element.value);
              });
            } else {
              slot[field.param] = field.value.value;
            }
          } else {
            slot[field.param] = field.value;
          }
          return false;
        }
      });
      if (slotValidationFailed) return;
      if (new Date(slot.start_date) >= new Date(slot.end_date)) {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DATE_ERROR"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
        return;
      }
      if (new Date(slot.start_date) < /* @__PURE__ */ new Date()) {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DATE_ERROR_BEFORE_NOW"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
        return;
      }
      if (new Date(slot.end_date) - new Date(slot.start_date) < this.duration * 60 * 1e3) {
        Swal.fire({
          icon: "error",
          title: "Oops...",
          text: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_ERROR"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
        return;
      }
      slot.event_id = this.eventId;
      slot.duration = this.duration;
      slot.duration_type = this.duration_type;
      slot.break_every = this.break_every;
      slot.break_time = this.break_time;
      slot.break_time_type = this.break_time_type;
      slot.mode = mode;
      slot.repeat_dates = this.repeat_dates.map((day) => day.id);
      if (this.$props.slot) {
        slot.id = this.$props.slot.id;
        slot.parent_slot_id = this.$props.slot.parent_slot_id;
      }
      eventsService.saveEventSlot(slot).then((response) => {
        if (response.status === true) {
          let slots = response.data;
          Swal.fire({
            position: "center",
            icon: "success",
            title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_SAVED"),
            showConfirmButton: false,
            allowOutsideClick: false,
            reverseButtons: true,
            timer: 1500,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            }
          }).then(() => {
            this.$emit("slot-saved", slots);
            this.$emit("close");
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: response.message
          });
        }
      });
    },
    deleteSlot() {
      Swal.fire({
        title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM"),
        text: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_TEXT"),
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_YES"),
        cancelButtonText: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_NO"),
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          cancelButton: "em-swal-cancel-button"
        }
      }).then((result) => {
        if (result.isConfirmed) {
          eventsService.deleteEventSlot(this.$props.slot.id).then((response) => {
            if (response.status === true) {
              Swal.fire({
                position: "center",
                icon: "success",
                title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETED"),
                showConfirmButton: false,
                allowOutsideClick: false,
                reverseButtons: true,
                timer: 1500,
                customClass: {
                  title: "em-swal-title",
                  confirmButton: "em-swal-confirm-button",
                  actions: "em-swal-single-action"
                }
              }).then(() => {
                this.$emit("slot-deleted", this.$props.slot.id);
                this.$emit("close");
              });
            } else {
              Swal.fire({
                icon: "error",
                title: "Oops...",
                text: response.message
              });
            }
          });
        }
      });
    },
    formatDate(date, format = "YYYY-MM-DD HH:mm:ss") {
      let year = date.getFullYear();
      let month = (1 + date.getMonth()).toString().padStart(2, "0");
      let day = date.getDate().toString().padStart(2, "0");
      let hours = date.getHours().toString().padStart(2, "0");
      let minutes = date.getMinutes().toString().padStart(2, "0");
      let seconds = date.getSeconds().toString().padStart(2, "0");
      return format.replace("YYYY", year).replace("MM", month).replace("DD", day).replace("HH", hours).replace("mm", minutes).replace("ss", seconds);
    },
    roundToQuarter(stringDate = null, date = null) {
      if (stringDate) {
        date = new Date(stringDate);
      }
      let minutes = date.getMinutes();
      let roundedMinutes = Math.round(minutes / 15) * 15;
      date.setMinutes(roundedMinutes);
      date.setSeconds(0);
      return this.formatDate(date);
    },
    onDayClick(day) {
      if (!day.isDisabled) {
        const idx = this.repeat_dates.findIndex((d) => d.id === day.id);
        if (idx >= 0) {
          this.repeat_dates.splice(idx, 1);
        } else {
          this.repeat_dates.push({
            id: day.id,
            date: day.date
          });
        }
      }
    },
    formatDuplicateDate(date) {
      const [year, month, day] = date.split("-");
      return `${day}-${month}-${year}`;
    },
    removeDate(date) {
      const idx = this.repeat_dates.findIndex((d) => d.id === date);
      if (idx >= 0) {
        this.repeat_dates.splice(idx, 1);
      }
    }
  },
  computed: {
    disabledSubmit: function() {
      return this.fields.some((field) => {
        if (!field.optional) {
          return field.value === "" || field.value === 0;
        } else {
          return false;
        }
      });
    },
    dates() {
      return this.repeat_dates.map((day) => day.date);
    },
    attributes() {
      return this.dates.map((date) => ({
        highlight: true,
        dates: date
      }));
    }
  }
};
const _hoisted_1$3 = { class: "tw-sticky tw-top-0 tw-z-10 tw-border-b tw-border-neutral-300 tw-bg-white tw-pt-4" };
const _hoisted_2$3 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_3$3 = { key: 0 };
const _hoisted_4$3 = { key: 1 };
const _hoisted_5$1 = { class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6" };
const _hoisted_6 = { class: "tw-flex tw-flex-col tw-gap-3" };
const _hoisted_7 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_8 = {
  key: 0,
  class: "tw-rounded-full tw-bg-profile-full tw-px-2 tw-py-1 tw-text-white"
};
const _hoisted_9 = { class: "tw-flex tw-flex-col tw-gap-2" };
const _hoisted_10 = {
  class: "tw-flex tw-flex-wrap tw-items-center tw-gap-2 tw-overflow-y-auto",
  style: { "max-height": "135px" }
};
const _hoisted_11 = { class: "tw-flex tw-items-center tw-gap-1 tw-rounded-full tw-bg-profile-full tw-px-2 tw-py-1 tw-text-white" };
const _hoisted_12 = ["onClick"];
const _hoisted_13 = { class: "tw-flex tw-items-center tw-gap-4" };
const _hoisted_14 = {
  style: { "list-style-type": "none", "margin": "0", "padding-left": "0px", "white-space": "nowrap" },
  class: "tw-flex tw-h-full tw-flex-col tw-justify-center"
};
const _hoisted_15 = ["disabled"];
const _hoisted_16 = { key: 0 };
const _hoisted_17 = { key: 1 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Info = resolveComponent("Info");
  const _component_Parameter = resolveComponent("Parameter");
  const _component_DatePicker = resolveComponent("DatePicker");
  const _component_popover = resolveComponent("popover");
  const _component_modal = resolveComponent("modal");
  return openBlock(), createBlock(_component_modal, {
    name: "calendar-slot-modal",
    class: normalizeClass("placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-overflow-x-hidden tw-rounded tw-px-4 tw-shadow-modal"),
    transition: "nice-modal-fade",
    width: "60%",
    delay: 100,
    adaptive: true,
    clickToClose: false,
    onClosed: $options.beforeClose,
    onBeforeOpen: $options.beforeOpen
  }, {
    default: withCtx(() => [
      createBaseVNode("div", _hoisted_1$3, [
        createBaseVNode("div", _hoisted_2$3, [
          $props.slot ? (openBlock(), createElementBlock("h2", _hoisted_3$3, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EDIT_SLOT")), 1)) : (openBlock(), createElementBlock("h2", _hoisted_4$3, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT")), 1)),
          createBaseVNode("button", {
            class: "tw-cursor-pointer tw-bg-transparent",
            onClick: _cache[0] || (_cache[0] = withModifiers(($event) => _ctx.$emit("close"), ["prevent"]))
          }, _cache[8] || (_cache[8] = [
            createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
          ]))
        ])
      ]),
      createVNode(_component_Info, {
        class: "tw-w-full",
        text: this.durationSlotInfo
      }, null, 8, ["text"]),
      createBaseVNode("div", _hoisted_5$1, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.fields, (field) => {
          return withDirectives((openBlock(), createElementBlock("div", {
            key: field.param,
            class: normalizeClass({
              "tw-w-fit": field.param === "start_date" || field.param === "end_date"
            })
          }, [
            createVNode(_component_Parameter, {
              ref_for: true,
              ref: "slot_" + field.param,
              "parameter-object": field,
              "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null,
              "help-text-type": "above"
            }, null, 8, ["parameter-object", "multiselect-options"])
          ], 2)), [
            [vShow, field.displayed]
          ]);
        }), 128)),
        createBaseVNode("div", null, [
          createBaseVNode("div", _hoisted_6, [
            createBaseVNode("div", _hoisted_7, [
              _cache[9] || (_cache[9] = createBaseVNode("span", { class: "material-symbols-outlined" }, "repeat", -1)),
              createBaseVNode("button", {
                type: "button",
                class: "tw-flex tw-items-center tw-gap-1",
                onClick: _cache[1] || (_cache[1] = ($event) => $data.showRepeat = !$data.showRepeat)
              }, [
                createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_REPEAT")), 1),
                createBaseVNode("span", {
                  class: normalizeClass(["material-symbols-outlined tw-text-neutral-900", { "tw-rotate-90": $data.showRepeat }])
                }, "chevron_right", 2),
                $data.repeat_dates.length > 0 ? (openBlock(), createElementBlock("span", _hoisted_8, toDisplayString($data.repeat_dates.length) + " " + toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_REPEAT_SELECTED")), 1)) : createCommentVNode("", true)
              ])
            ]),
            withDirectives(createBaseVNode("div", _hoisted_9, [
              createVNode(_component_DatePicker, {
                id: "slot_repeat",
                mode: "date",
                "title-position": "left",
                locale: $data.actualLanguage,
                attributes: $options.attributes,
                columns: 2,
                "min-date": $data.minDate,
                expanded: "",
                onDayclick: $options.onDayClick
              }, null, 8, ["locale", "attributes", "min-date", "onDayclick"]),
              createBaseVNode("div", _hoisted_10, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.repeat_dates, (date) => {
                  return openBlock(), createElementBlock("div", _hoisted_11, [
                    createBaseVNode("span", {
                      onClick: _cache[2] || (_cache[2] = (...args) => _ctx.togglePopover && _ctx.togglePopover(...args))
                    }, toDisplayString($options.formatDuplicateDate(date.id)), 1),
                    createBaseVNode("span", {
                      class: "material-symbols-outlined tw-text-white",
                      onClick: ($event) => $options.removeDate(date.id)
                    }, "close", 8, _hoisted_12)
                  ]);
                }), 256))
              ])
            ], 512), [
              [vShow, $data.showRepeat]
            ])
          ]),
          _cache[10] || (_cache[10] = createBaseVNode("div", null, null, -1))
        ])
      ]),
      createBaseVNode("div", {
        class: normalizeClass(["tw-mb-2 tw-mt-7 tw-flex", { "tw-justify-end": !$props.slot, "tw-justify-between": $props.slot }])
      }, [
        createBaseVNode("div", _hoisted_13, [
          $props.slot ? (openBlock(), createElementBlock("button", {
            key: 0,
            type: "button",
            class: "!tw-w-auto tw-text-red-500",
            onClick: _cache[3] || (_cache[3] = withModifiers(($event) => $options.deleteSlot(), ["prevent"]))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_DELETE")), 1)) : createCommentVNode("", true)
        ]),
        $props.slot && $data.displayPopover ? (openBlock(), createBlock(_component_popover, {
          key: 0,
          position: "top-left",
          icon: "keyboard_arrow_down",
          button: _ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT"),
          class: "custom-popover-arrow"
        }, {
          default: withCtx(() => [
            createBaseVNode("ul", _hoisted_14, [
              createBaseVNode("li", {
                class: "tw-cursor-pointer tw-p-2 hover:tw-bg-neutral-300",
                onClick: _cache[4] || (_cache[4] = ($event) => $options.saveSlot(1))
              }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ONLY_ONE")), 1),
              createBaseVNode("li", {
                class: "tw-cursor-pointer tw-p-2 hover:tw-bg-neutral-300",
                onClick: _cache[5] || (_cache[5] = ($event) => $options.saveSlot(2))
              }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ALL_FUTURES")), 1),
              createBaseVNode("li", {
                class: "tw-cursor-pointer tw-p-2 hover:tw-bg-neutral-300",
                onClick: _cache[6] || (_cache[6] = ($event) => $options.saveSlot(3))
              }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ALL")), 1)
            ])
          ]),
          _: 1
        }, 8, ["button"])) : (openBlock(), createElementBlock("button", {
          key: 1,
          type: "button",
          class: "tw-btn-primary !tw-w-auto",
          disabled: $options.disabledSubmit,
          onClick: _cache[7] || (_cache[7] = withModifiers(($event) => $options.saveSlot(0), ["prevent"]))
        }, [
          $props.slot ? (openBlock(), createElementBlock("span", _hoisted_16, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT")), 1)) : (openBlock(), createElementBlock("span", _hoisted_17, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_CREATE")), 1))
        ], 8, _hoisted_15))
      ], 2)
    ]),
    _: 1
  }, 8, ["onClosed", "onBeforeOpen"]);
}
const CalendarSlotPopup = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-0ba8752e"]]);
const eventsServicePlugin = createEventsServicePlugin();
const calendarControls = createCalendarControlsPlugin();
const createCalendarConfig = (vm) => ({
  locale: "fr-FR",
  defaultView: viewWeek.name,
  dayBoundaries: {
    start: "08:00",
    end: "21:00"
  },
  weekOptions: {
    gridHeight: 900,
    eventWidth: 100
  },
  views: [createViewWeek()],
  events: [],
  plugins: [eventsServicePlugin, calendarControls],
  callbacks: {
    onClickDateTime: (dateTime) => {
      vm.openSlotPopup(dateTime);
    },
    onEventClick: (event) => {
      vm.openSlotPopup(null, event);
    },
    onRender($app) {
      nextTick(() => {
        vm.addEventListeners();
        vm.updateCellSize();
      });
    },
    onRangeUpdate(range) {
      nextTick(() => {
        vm.addEventListeners();
      });
    }
  },
  translations: mergeLocales(translations, {
    frFR: {
      Week: "Vue semaine",
      Day: "Vue jour",
      Today: "Revenir à aujourd'hui"
    },
    enGB: {
      Week: "Week View",
      Day: "Day View",
      Today: "Back to today"
    }
  })
});
const _sfc_main$2 = {
  name: "EventCalendarSettings",
  components: { EventDay, CalendarSlotPopup, ScheduleXCalendar: _o },
  props: {
    event: Object
  },
  emits: ["go-back"],
  data() {
    return {
      calendarApp: shallowRef(null),
      loading: true,
      openedSlotPopup: false,
      dateClicked: null,
      currentSlot: null,
      view: "week",
      selection: {
        visible: false,
        top: 0,
        left: 0,
        width: 0,
        height: 0
      },
      cellHeight: 50,
      cellWidth: 100,
      lastMouseX: 0,
      lastMouseY: 0,
      lastMouseTarget: null,
      animationFrame: null
    };
  },
  mounted() {
    const vm = {
      openSlotPopup: this.openSlotPopup,
      dateClicked: this.dateClicked,
      addEventListeners: this.addEventListeners,
      updateCellSize: this.updateCellSize
    };
    this.calendarApp = createCalendar(createCalendarConfig(vm));
    for (const slot of this.$props.event.slots) {
      slot.color = this.event.color;
    }
    if (this.$props.event.slots.length > 0) {
      let key = 0;
      while (this.$props.event.slots[key] && this.$props.event.slots[key].start < (/* @__PURE__ */ new Date()).toISOString()) {
        key++;
      }
      let selectedDate = new Date(this.$props.event.slots[key].start);
      selectedDate = selectedDate.toISOString().split("T")[0];
      calendarControls.setDate(selectedDate);
    }
    eventsServicePlugin.set(this.$props.event.slots);
  },
  beforeUnmount() {
    this.removeEventListeners();
  },
  created() {
    this.loading = false;
  },
  methods: {
    addEventListeners() {
      document.querySelectorAll(".sx__time-grid-day").forEach((el) => {
        el.addEventListener("mousemove", this.handleMouseMove);
        el.addEventListener("mouseleave", this.hideSelection);
      });
      window.addEventListener("scroll", this.updateSelectionOnScroll);
      window.addEventListener("resize", this.updateCellSize);
    },
    removeEventListeners() {
      document.querySelectorAll(".sx__time-grid-day").forEach((el) => {
        el.removeEventListener("mousemove", this.handleMouseMove);
        el.removeEventListener("mouseleave", this.hideSelection);
      });
      window.removeEventListener("scroll", this.updateSelectionOnScroll);
      window.removeEventListener("resize", this.updateCellSize);
    },
    openSlotPopup(date, slot = null) {
      if (!this.event.slot_duration) {
        Swal$1.fire({
          icon: "error",
          title: "Oops...",
          text: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_REQUIRED"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
        return;
      }
      if (slot) {
        slot.repeat_dates = [];
        let parent_slot_id = slot.id;
        let parent_slot = this.$props.event.slots.find((s) => s.id === slot.parent_slot_id);
        if (parent_slot) {
          parent_slot_id = parent_slot.id;
        }
        let child_slots = this.$props.event.slots.filter(
          (s) => s.parent_slot_id !== 0 && s.parent_slot_id === parent_slot_id
        );
        if (child_slots.length > 0) {
          for (const child_slot of child_slots) {
            let repeat_date = {};
            repeat_date.id = child_slot.start.split(" ")[0];
            repeat_date.date = child_slot.start;
            slot.repeat_dates.push(repeat_date);
          }
        }
      }
      this.dateClicked = date;
      this.currentSlot = slot;
      this.openedSlotPopup = true;
    },
    updateSlots(slots) {
      for (const slot of slots) {
        let existingSlot = eventsServicePlugin.get(slot.id);
        if (existingSlot) {
          eventsServicePlugin.update(slot);
        } else {
          eventsServicePlugin.add(slot);
        }
      }
    },
    deleteSlot(slot_id) {
      eventsServicePlugin.remove(slot_id);
    },
    updateCellSize() {
      this.cellWidth = document.querySelector(".sx__time-grid-day").offsetWidth;
      this.cellHeight = document.querySelector(".sx__week-grid__hour").offsetHeight;
      if (this.$props.event.slot_duration_type === "minutes") {
        this.cellHeight = this.cellHeight / 60 * this.$props.event.slot_duration;
      } else {
        this.cellHeight = this.cellHeight * this.$props.event.slot_duration;
      }
    },
    handleMouseMove(event) {
      if (!this.$refs.calendar) return;
      this.lastMouseX = event.clientX;
      this.lastMouseY = event.clientY;
      this.lastMouseTarget = event.target;
      if (!event.target.classList.contains("sx__time-grid-day")) {
        this.hideSelection();
        return;
      }
      if (this.animationFrame) {
        cancelAnimationFrame(this.animationFrame);
      }
      this.animationFrame = requestAnimationFrame(() => {
        const rect = event.target;
        let calendarGrid = document.querySelector(".sx__view-container ");
        const rectCalendar = calendarGrid.getBoundingClientRect();
        const header = document.querySelector(".sx__calendar-header").offsetHeight + document.querySelector(".sx__week-header").offsetHeight;
        const relativeX = rect.offsetLeft + 23;
        const relativeY = event.clientY - rectCalendar.top + header + 25;
        this.selection = {
          visible: true,
          top: relativeY,
          left: relativeX,
          width: this.cellWidth,
          height: this.cellHeight
        };
        this.animationFrame = null;
      });
    },
    updateSelectionOnScroll() {
      if (this.selection.visible) {
        this.handleMouseMove({
          clientX: this.lastMouseX,
          clientY: this.lastMouseY,
          target: this.lastMouseTarget
        });
      }
    },
    hideSelection() {
      this.selection.visible = false;
    }
  },
  computed: {}
};
const _hoisted_1$2 = {
  key: 1,
  class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6"
};
const _hoisted_2$2 = { class: "tw-mb-0 tw-flex tw-items-end tw-font-semibold" };
const _hoisted_3$2 = { class: "tw-text-base tw-text-neutral-600" };
const _hoisted_4$2 = {
  key: 0,
  class: "calendar-container tw-mt-4",
  ref: "calendar"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_CalendarSlotPopup = resolveComponent("CalendarSlotPopup");
  const _component_EventDay = resolveComponent("EventDay");
  const _component_ScheduleXCalendar = resolveComponent("ScheduleXCalendar");
  return openBlock(), createElementBlock("div", null, [
    $data.openedSlotPopup ? (openBlock(), createBlock(_component_CalendarSlotPopup, {
      key: 0,
      date: $data.dateClicked,
      slot: $data.currentSlot,
      "event-id": $props.event.id,
      "location-id": $props.event.location,
      duration: $props.event.slot_duration,
      duration_type: $props.event.slot_duration_type,
      break_every: $props.event.slot_break_every,
      break_time: $props.event.slot_break_time,
      break_time_type: $props.event.slot_break_time_type,
      onClose: _cache[0] || (_cache[0] = ($event) => $data.openedSlotPopup = false),
      onSlotSaved: $options.updateSlots,
      onSlotDeleted: $options.deleteSlot
    }, null, 8, ["date", "slot", "event-id", "location-id", "duration", "duration_type", "break_every", "break_time", "break_time_type", "onSlotSaved", "onSlotDeleted"])) : createCommentVNode("", true),
    !this.loading ? (openBlock(), createElementBlock("div", _hoisted_1$2, [
      createBaseVNode("div", null, [
        createBaseVNode("div", null, [
          createBaseVNode("label", _hoisted_2$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CALENDAR")), 1),
          createBaseVNode("span", _hoisted_3$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CALENDAR_HELP")), 1)
        ]),
        $data.calendarApp ? (openBlock(), createElementBlock("div", _hoisted_4$2, [
          createVNode(_component_ScheduleXCalendar, {
            "calendar-app": $data.calendarApp,
            class: "tw-relative"
          }, {
            timeGridEvent: withCtx(({ calendarEvent }) => [
              createVNode(_component_EventDay, {
                "calendar-event": calendarEvent,
                view: $data.view,
                preset: "full"
              }, null, 8, ["calendar-event", "view"])
            ]),
            _: 1
          }, 8, ["calendar-app"]),
          $data.selection.visible ? (openBlock(), createElementBlock("div", {
            key: 0,
            class: "selection-box",
            style: normalizeStyle({
              top: $data.selection.top + "px",
              left: $data.selection.left + "px",
              width: $data.selection.width + "px",
              height: $data.selection.height + "px"
            })
          }, null, 4)) : createCommentVNode("", true)
        ], 512)) : createCommentVNode("", true)
      ])
    ])) : createCommentVNode("", true)
  ]);
}
const EventCalendarSettings = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2]]);
const _sfc_main$1 = {
  name: "EventEmailSettings",
  components: { Info, Parameter },
  emits: ["reload-event", "go-back"],
  props: {
    event: Object
  },
  data() {
    return {
      loading: true,
      emails: [],
      fields: [
        {
          param: "applicant_notify",
          type: "toggle",
          value: 1,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_NOTIFY",
          displayed: true,
          hideLabel: true,
          optional: true
        },
        {
          param: "applicant_notify_email",
          type: "select",
          placeholder: "",
          value: 0,
          default: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_NOTIFY_EMAIL",
          helptext: "",
          displayed: false,
          displayedOn: "applicant_notify",
          displayedOnValue: 1,
          options: [],
          reload: 0,
          optional: true
        },
        {
          param: "ics_event_name",
          type: "text",
          placeholder: "",
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_NAME_ICS",
          helptext: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_NAME_ICS_HELP_TEXT",
          displayed: false,
          displayedOn: "applicant_notify",
          displayedOnValue: 1,
          optional: false
        },
        {
          param: "applicant_recall",
          type: "toggle",
          value: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL",
          displayed: true,
          hideLabel: true,
          optional: true
        },
        {
          param: "applicant_recall_frequency",
          type: "text",
          value: 1,
          default: 7,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY",
          displayed: false,
          displayedOn: "applicant_recall",
          displayedOnValue: 1,
          endText: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY_END_TEXT",
          optional: true
        },
        {
          param: "applicant_recall_email",
          type: "select",
          value: 0,
          default: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_EMAIL",
          displayed: false,
          displayedOn: "applicant_recall",
          displayedOnValue: 1,
          options: [],
          reload: 0
        },
        {
          param: "manager_recall",
          type: "toggle",
          value: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_MANAGER_RECALL",
          displayed: true,
          hideLabel: true,
          optional: true
        },
        {
          param: "manager_recall_frequency",
          type: "text",
          value: 1,
          default: 7,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY",
          displayed: false,
          displayedOn: "manager_recall",
          displayedOnValue: 1,
          endText: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_MANAGER_RECALL_FREQUENCY_END_TEXT"
        },
        {
          param: "manager_recall_email",
          type: "select",
          value: 0,
          default: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_MANAGER_RECALL_EMAIL",
          displayed: false,
          displayedOn: "manager_recall",
          displayedOnValue: 1,
          options: [],
          reload: 0
        },
        {
          param: "users_recall",
          type: "toggle",
          value: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_USERS_RECALL",
          displayed: true,
          hideLabel: true,
          optional: true
        },
        {
          param: "users_recall_frequency",
          type: "text",
          value: 1,
          default: 7,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY",
          displayed: false,
          displayedOn: "users_recall",
          displayedOnValue: 1,
          endText: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY_END_TEXT"
        },
        {
          param: "users_recall_email",
          type: "select",
          value: 0,
          default: 0,
          label: "COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_MANAGER_RECALL_EMAIL",
          displayed: false,
          displayedOn: "users_recall",
          displayedOnValue: 1,
          options: [],
          reload: 0
        }
      ]
    };
  },
  created: function() {
    this.getEmails().then((response) => {
      if (response.status === true && this.event && this.event["notifications"]) {
        for (const field of this.fields) {
          field.value = this.event["notifications"][field.param];
          if (field.param === "applicant_notify_email" && field.value == 0) {
            let email = this.emails.find((email2) => email2.lbl === "booking_confirmation");
            if (email) {
              field.value = email.value;
            }
          }
        }
      } else {
        for (const field of this.fields) {
          if (field.param === "applicant_notify_email" && field.value == 0) {
            let email = this.emails.find((email2) => email2.lbl === "booking_confirmation");
            if (email) {
              field.value = email.value;
            }
          }
        }
      }
      this.loading = false;
    });
  },
  methods: {
    getEmails(email_id = 0) {
      return new Promise((resolve, reject) => {
        this.loading = true;
        emailService.getEmails().then((response) => {
          if (response.status) {
            for (const email of response.data.datas) {
              this.emails.push({
                value: email.id,
                label: email.subject,
                lbl: email.lbl
              });
            }
            let options = [
              {
                value: 0,
                label: this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_EMAIL_SELECT")
              }
            ];
            if (response.status) {
              Array.prototype.push.apply(options, this.emails);
            }
            this.fields.find((field) => field.param === "applicant_notify_email").options = options;
            this.fields.find((field) => field.param === "applicant_recall_email").options = options;
            this.fields.find((field) => field.param === "manager_recall_email").options = options;
            this.fields.find((field) => field.param === "users_recall_email").options = options;
            resolve({ status: true, options });
          } else {
            reject({ status: false });
          }
        });
      });
    },
    // Hooks
    checkConditional(parameter, oldValue, value) {
      let fields = this.fields.filter((field) => field.displayedOn === parameter.param);
      for (let field of fields) {
        field.displayed = field.displayedOnValue == value;
        if (!field.displayed) {
          if (field.default) {
            field.value = field.default;
            if (field.reload) {
              field.reload = field.reload + 1;
            }
          } else {
            field.value = "";
          }
          this.checkConditional(field, field.value, "");
        }
      }
    },
    saveBookingNotifications() {
      let notifications = {};
      let fields = this.fields;
      const notificationsValidationFailed = fields.some((field) => {
        if (field.displayed) {
          let ref_name = "event_emails_" + field.param;
          if (!this.$refs[ref_name][0].validate()) {
            return true;
          }
          notifications[field.param] = field.value;
          return false;
        }
      });
      if (notificationsValidationFailed) return;
      notifications.event_id = this.event.id;
      eventsService.saveBookingNotifications(notifications).then((response) => {
        if (response.status === true) {
          Swal$1.fire({
            position: "center",
            icon: "success",
            title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_SETUP_SUCCESS"),
            showConfirmButton: false,
            allowOutsideClick: false,
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            },
            timer: 1500
          }).then(() => {
            this.$emit("go-back");
          });
        } else {
          Swal$1.fire({
            icon: "error",
            title: "Oops...",
            text: response.message
          });
        }
      });
    }
  },
  computed: {
    disabledSubmit: function() {
      return this.fields.some((field) => {
        if (!field.optional && field.displayed) {
          return field.value === "" || field.value === 0;
        } else {
          return false;
        }
      });
    }
  }
};
const _hoisted_1$1 = {
  key: 0,
  class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6"
};
const _hoisted_2$1 = { class: "tw-mt-7 tw-flex tw-justify-end" };
const _hoisted_3$1 = ["disabled"];
const _hoisted_4$1 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Parameter = resolveComponent("Parameter");
  const _component_Info = resolveComponent("Info");
  return openBlock(), createElementBlock("div", null, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_1$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.fields, (field) => {
        return withDirectives((openBlock(), createElementBlock("div", {
          key: field.param,
          class: normalizeClass([
            field.param === "applicant_recall_frequency" || field.param === "manager_recall_frequency" || field.param === "users_recall_frequency" ? "tw-w-1/2" : "tw-w-full"
          ])
        }, [
          field.displayed ? (openBlock(), createBlock(_component_Parameter, {
            ref_for: true,
            ref: "event_emails_" + field.param,
            key: field.reload ? field.reload : field.param,
            "parameter-object": field,
            "help-text-type": "above",
            "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null,
            onValueUpdated: $options.checkConditional
          }, null, 8, ["parameter-object", "multiselect-options", "onValueUpdated"])) : createCommentVNode("", true),
          !$props.event.manager && field.param === "manager_recall" && field.value === "1" ? (openBlock(), createBlock(_component_Info, {
            key: field.value,
            text: _ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_INFO_BEFORE_NOTIFICATIONS_MANAGER_RECALL"),
            icon: "warning",
            "bg-color": "tw-bg-orange-100",
            "icon-type": "material-icons",
            "icon-color": "tw-text-orange-600",
            class: normalizeClass("tw-mt-4")
          }, null, 8, ["text"])) : createCommentVNode("", true)
        ], 2)), [
          [vShow, field.displayed]
        ]);
      }), 128))
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_2$1, [
      createBaseVNode("button", {
        type: "button",
        disabled: $options.disabledSubmit,
        onClick: _cache[0] || (_cache[0] = (...args) => $options.saveBookingNotifications && $options.saveBookingNotifications(...args)),
        class: "tw-btn-primary tw-cursor-pointer"
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SAVE_AND_EXIT")), 9, _hoisted_3$1)
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_4$1)) : createCommentVNode("", true)
  ]);
}
const EventEmailSettings = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "EventForm",
  components: {
    EventEmailSettings,
    EventCalendarSettings,
    EventSlotsSettings,
    EventGlobalSettings,
    Tabs
  },
  props: {},
  data: () => ({
    loading: true,
    event_id: 0,
    event: null,
    tabs: [
      {
        id: 1,
        name: "COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL",
        description: "",
        icon: "info",
        active: true,
        displayed: true
      },
      {
        id: 2,
        name: "COM_EMUNDUS_ONBOARD_ADD_EVENT_SCHEDULE",
        description: "",
        icon: "schedule",
        active: false,
        displayed: true,
        disabled: true
      },
      {
        id: 3,
        name: "COM_EMUNDUS_ONBOARD_ADD_EVENT_CALENDAR",
        description: "",
        icon: "calendar_today",
        active: false,
        displayed: true,
        disabled: true
      },
      {
        id: 4,
        name: "COM_EMUNDUS_ONBOARD_ADD_EVENT_EMAILS",
        description: "",
        icon: "schedule_send",
        active: false,
        displayed: true,
        disabled: true
      }
    ]
  }),
  created() {
    this.event_id = parseInt(useGlobalStore().datas.eventid.value);
    if (this.event_id) {
      this.getEvent(this.event_id);
    } else {
      this.loading = false;
    }
  },
  methods: {
    goBack() {
      if (typeof window.history !== "undefined") {
        window.history.back();
      } else {
        this.redirectJRoute("index.php?option=com_emundus&view=events");
      }
    },
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },
    handleChangeTab(tab_id) {
      this.$refs.tabsComponent.changeTab(tab_id);
    },
    reloadEvent(tab_id) {
      if (tab_id === 3) {
        if (isNaN(this.event_id)) {
          this.event_id = this.event.id;
        }
        this.event = null;
        this.getEvent(this.event_id);
      }
    },
    // Display a message when the user clicks on a disabled tab
    displayDisabledMessage(tab) {
      Swal.fire({
        position: "center",
        icon: "warning",
        title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ADD_EVENT_PLEASE_CREATE_FIRST"),
        showConfirmButton: true,
        allowOutsideClick: false,
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action"
        }
      });
    },
    getEvent(event_id, change_tab = 0) {
      eventsService.getEvent(event_id).then((response) => {
        if (response.status) {
          this.event = response.data;
          if (this.event.slots) {
            this.event.slots.forEach((slot) => {
              if (slot.people) {
                slot.people = slot.people.split(",");
              }
            });
          }
          this.tabs[1].disabled = false;
          this.tabs[3].disabled = false;
          if (this.event.slot_duration) {
            this.tabs[2].disabled = false;
          }
          this.loading = false;
        }
        if (change_tab) {
          this.handleChangeTab(change_tab);
        }
      });
    }
  }
};
const _hoisted_1 = { class: "events__add-event" };
const _hoisted_2 = { class: "group-hover:tw-underline" };
const _hoisted_3 = { class: "tw-mt-4" };
const _hoisted_4 = { class: "tw-relative tw-w-full tw-rounded-2xl tw-border tw-border-neutral-300 tw-bg-white tw-p-6" };
const _hoisted_5 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Tabs = resolveComponent("Tabs");
  const _component_EventGlobalSettings = resolveComponent("EventGlobalSettings");
  const _component_EventSlotsSettings = resolveComponent("EventSlotsSettings");
  const _component_EventCalendarSettings = resolveComponent("EventCalendarSettings");
  const _component_EventEmailSettings = resolveComponent("EventEmailSettings");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", null, [
      createBaseVNode("div", {
        class: "tw-group tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.goBack && $options.goBack(...args))
      }, [
        _cache[3] || (_cache[3] = createBaseVNode("span", { class: "material-symbols-outlined tw-mr-1 tw-text-link-regular" }, "navigate_before", -1)),
        createBaseVNode("span", _hoisted_2, toDisplayString(_ctx.translate("BACK")), 1)
      ]),
      createBaseVNode("h1", _hoisted_3, toDisplayString(this.event && Object.keys(this.event).length > 0 ? _ctx.translate("COM_EMUNDUS_ONBOARD_EDIT_EVENT_GLOBAL_CREATE") + " " + this.event["name"] : _ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT")), 1),
      _cache[4] || (_cache[4] = createBaseVNode("hr", { class: "tw-mb-8 tw-mt-1.5" }, null, -1)),
      !_ctx.loading ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
        createVNode(_component_Tabs, {
          ref: "tabsComponent",
          classes: "tw-flex tw-items-center tw-gap-2 tw-ml-7",
          tabs: _ctx.tabs,
          context: _ctx.event_id ? "event_form_" + _ctx.event_id : "",
          onClickDisabledTab: $options.displayDisabledMessage,
          onChangeTabActive: $options.reloadEvent
        }, null, 8, ["tabs", "context", "onClickDisabledTab", "onChangeTabActive"]),
        createBaseVNode("div", _hoisted_4, [
          _ctx.tabs[0].active ? (openBlock(), createBlock(_component_EventGlobalSettings, {
            key: 0,
            event: _ctx.event,
            onReloadEvent: $options.getEvent
          }, null, 8, ["event", "onReloadEvent"])) : createCommentVNode("", true),
          _ctx.tabs[1].active ? (openBlock(), createBlock(_component_EventSlotsSettings, {
            key: 1,
            event: _ctx.event,
            onReloadEvent: $options.getEvent
          }, null, 8, ["event", "onReloadEvent"])) : createCommentVNode("", true),
          _ctx.tabs[2].active && _ctx.event ? (openBlock(), createBlock(_component_EventCalendarSettings, {
            key: 2,
            event: _ctx.event,
            onGoBack: _cache[1] || (_cache[1] = ($event) => $options.redirectJRoute("index.php?option=com_emundus&view=events"))
          }, null, 8, ["event"])) : createCommentVNode("", true),
          _ctx.tabs[3].active ? (openBlock(), createBlock(_component_EventEmailSettings, {
            key: 3,
            event: _ctx.event,
            onReloadEvent: $options.getEvent,
            onGoBack: _cache[2] || (_cache[2] = ($event) => $options.redirectJRoute("index.php?option=com_emundus&view=events"))
          }, null, 8, ["event", "onReloadEvent"])) : createCommentVNode("", true)
        ])
      ], 64)) : createCommentVNode("", true)
    ]),
    _ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_5)) : createCommentVNode("", true)
  ]);
}
const EventForm = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  EventForm as default
};
