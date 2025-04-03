import { e as eventsService } from "./events2.js";
import { _ as _export_sfc, M as Modal, r as resolveComponent, o as openBlock, c as createElementBlock, d as createBaseVNode, t as toDisplayString, g as withModifiers, F as Fragment, e as renderList, w as withDirectives, v as vShow, n as normalizeClass, a as createBlock, b as createCommentVNode } from "./app_emundus.js";
import { I as Info } from "./Info.js";
import { P as Parameter } from "./Parameter.js";
import { C as ColorPicker } from "./ColorPicker.js";
import { L as LocationPopup } from "./LocationPopup.js";
import EventBooking from "./EventBooking.js";
import "./index.js";
import "./LocationForm.js";
const _sfc_main = {
  name: "EditSlot",
  components: {
    EventBooking,
    LocationPopup,
    ColorPicker,
    Parameter,
    Info,
    Modal
  },
  props: {
    slot: Object
  },
  emits: ["close", "valueUpdated"],
  data: () => ({
    actualLanguage: "fr-FR",
    cancelPopupOpenForBookingId: null,
    initialEvent: null,
    submitted: false,
    fields: [
      {
        param: "event_id",
        type: "multiselect",
        multiselectOptions: {
          noOptions: false,
          multiple: false,
          taggable: false,
          searchable: true,
          internalSearch: false,
          asyncRoute: "getevents",
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
        reload: 0,
        label: "COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_EVENT",
        placeholder: "COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_EVENT_PLACEHOLDER",
        displayed: true
      },
      {
        param: "booking",
        type: "component",
        component: "EventBooking",
        placeholder: "",
        value: 0,
        reload: 0,
        label: "COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_BOOKING",
        helptext: "",
        displayed: false
      },
      {
        param: "user",
        type: "multiselect",
        multiselectOptions: {
          noOptions: false,
          multiple: false,
          taggable: false,
          searchable: true,
          internalSearch: false,
          asyncRoute: "getapplicants",
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
        reload: 0,
        label: "COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_APPLICANT",
        placeholder: "COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_APPLICANT_PLACEHOLDER",
        displayed: false
      },
      {
        param: "juror",
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
        reload: 0,
        label: "COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS",
        placeholder: "COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_PLACEHOLDER",
        displayed: false,
        optional: true
      }
    ]
  }),
  created: function() {
    if (this.slot) {
      this.fields.forEach((field) => {
        var _a, _b, _c, _d, _e;
        if (this.slot[field.param]) {
          if (field.param === "user") {
            field.value = this.slot["ccid"];
          } else {
            field.value = this.slot[field.param];
          }
        } else if (field.param === "user") {
          let index = this.slot.registrantSelected ? (_b = (_a = this.slot.registrants) == null ? void 0 : _a.datas) == null ? void 0 : _b.findIndex((r) => r.id === this.slot.registrantSelected.id) : -1;
          field.value = index !== -1 ? ((_e = (_d = (_c = this.slot.registrants) == null ? void 0 : _c.datas) == null ? void 0 : _d[index]) == null ? void 0 : _e.ccid) ?? null : null;
        } else if (field.param === "booking") {
          field.value = this.slot["availability"] ?? this.slot["id"];
        } else if (field.param === "juror") {
          if (this.slot["additional_columns"]) {
            const jurors = this.slot["additional_columns"].find(
              (col) => col.key === Joomla.JText._("COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS")
            );
            field.value = jurors.id ? jurors.id.split(",").map((id) => Number(id.trim())) : [];
          } else if (this.slot["assoc_user_id"]) {
            field.value = this.slot["assoc_user_id"] ? this.slot["assoc_user_id"].split(",").map((id) => Number(id.trim())) : [];
          } else if (this.slot["registrantSelected"] && this.slot["registrantSelected"]["assoc_user_id"]) {
            field.value = this.slot["registrantSelected"]["assoc_user_id"].split(",").map((id) => Number(id.trim()));
          } else if (this.slot["users"]) {
            field.value = this.slot["users"].split(",").map((id) => Number(id.trim()));
          } else {
            field.value = [];
          }
        } else {
          field.value = null;
        }
      });
    } else {
      this.fields.forEach((field) => {
        field.value = null;
      });
    }
  },
  methods: {
    editSlot() {
      var _a, _b, _c, _d, _e;
      this.submitted = true;
      let slot_edited = {};
      const slotValidationFailed = this.fields.some((field) => {
        if (field.displayed) {
          let ref_name = "slot_" + field.param;
          if (!this.$refs[ref_name][0].validate()) {
            return true;
          }
          if (field.type === "multiselect") {
            if (field.multiselectOptions.multiple) {
              slot_edited[field.param] = [];
              field.value.forEach((element) => {
                slot_edited[field.param].push(element.value);
              });
            } else {
              slot_edited[field.param] = field.value[field.multiselectOptions.trackBy] ?? field.value;
            }
          } else {
            slot_edited[field.param] = field.value;
          }
          return false;
        }
      });
      if (slotValidationFailed) return;
      if (this.slot) {
        if (this.slot.calendarId && !this.slot.registrants) {
          slot_edited["id"] = 0;
        } else {
          if (this.slot.calendarId) {
            let index = this.slot.registrantSelected ? (_b = (_a = this.slot.registrants) == null ? void 0 : _a.datas) == null ? void 0 : _b.findIndex((r) => r.id === this.slot.registrantSelected.id) : -1;
            slot_edited["id"] = index !== -1 ? ((_e = (_d = (_c = this.slot.registrants) == null ? void 0 : _c.datas) == null ? void 0 : _d[index]) == null ? void 0 : _e.id) ?? null : null;
          } else {
            slot_edited["id"] = this.slot.id;
          }
        }
      } else {
        slot_edited["id"] = null;
      }
      eventsService.editSlot(slot_edited).then((response) => {
        if (response.status === true) {
          Swal.fire({
            position: "center",
            icon: "success",
            title: this.slot ? Joomla.JText._("COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_SAVED") : Joomla.JText._("COM_EMUNDUS_ONBOARD_REGISTRANT_ADD_SAVED"),
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
            this.onClosePopup();
            this.$emit("update-items");
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
    onClosePopup() {
      this.$emit("close");
    },
    updateBookingElement(value) {
      const field = this.fields.find((f) => f.param === "booking");
      field.value = value.value ?? value;
    },
    updateForm(parameter, old, newValue) {
      if (parameter.param === "event_id" && old !== newValue) {
        this.fields.forEach((field) => {
          var _a;
          if (field.param !== "event_id") {
            this.$nextTick(() => {
              if (field.displayed) {
                field.reload = (field.reload || 0) + 1;
              } else if (field.param === "booking" || field.param === "user" || field.param === "juror") {
                field.displayed = true;
              }
            });
            if (field.param === "booking") {
              if (this.slot && this.slot["event_id"] !== ((_a = this.fields.find((f) => f.param === "event_id")) == null ? void 0 : _a.value)) {
                field.value = null;
              }
            }
          } else if (field.displayed && field.param === "event_id") {
            if (newValue === null) {
              field.value = null;
            } else {
              field.value = field.value[field.multiselectOptions.trackBy];
            }
          }
        });
      }
    }
  },
  computed: {
    disabledSubmit: function() {
      return this.fields.some((field) => {
        if (!field.optional && field.displayed) {
          return field.value === "" || field.value === 0 || field.value === null || typeof field.value === "object" && Object.keys(field.value).length === 0;
        } else {
          return false;
        }
      });
    },
    bookingSlot() {
      if (this.slot) {
        return this.slot["availability"] ?? this.slot["id"];
      }
      return null;
    }
  }
};
const _hoisted_1 = { class: "tw-pt-4" };
const _hoisted_2 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_3 = { key: 0 };
const _hoisted_4 = { key: 1 };
const _hoisted_5 = { class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6" };
const _hoisted_6 = { class: "tw-mb-8 tw-mt-5 tw-flex tw-justify-between" };
const _hoisted_7 = ["disabled"];
const _hoisted_8 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Parameter = resolveComponent("Parameter");
  const _component_Info = resolveComponent("Info");
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("div", _hoisted_1, [
      createBaseVNode("div", _hoisted_2, [
        $props.slot ? (openBlock(), createElementBlock("h2", _hoisted_3, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT")), 1)) : (openBlock(), createElementBlock("h2", _hoisted_4, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_ADD")), 1)),
        createBaseVNode("button", {
          class: "tw-cursor-pointer tw-bg-transparent",
          onClick: _cache[0] || (_cache[0] = withModifiers((...args) => $options.onClosePopup && $options.onClosePopup(...args), ["prevent"]))
        }, _cache[3] || (_cache[3] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
        ]))
      ])
    ]),
    createBaseVNode("div", _hoisted_5, [
      (openBlock(true), createElementBlock(Fragment, null, renderList(_ctx.fields, (field) => {
        var _a, _b, _c, _d, _e;
        return withDirectives((openBlock(), createElementBlock("div", {
          key: field.param,
          class: normalizeClass("tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-2")
        }, [
          field.displayed && field.param === "booking" ? (openBlock(), createBlock(_component_Parameter, {
            ref_for: true,
            ref: "slot_" + field.param,
            key: field.reload ? field.reload + " booking" : field.param + " booking",
            "parameter-object": field,
            "help-text-type": "above",
            "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null,
            asyncAttributes: [
              (_a = _ctx.fields.find((f) => f.param === "event_id")) == null ? void 0 : _a.value,
              (_b = _ctx.fields.find((f) => f.param === "user")) == null ? void 0 : _b.value,
              field.param
            ],
            componentsProps: {
              event_id: (_c = _ctx.fields.find((f) => f.param === "event_id")) == null ? void 0 : _c.value,
              slot_id: $options.bookingSlot
            },
            onValueUpdated: $options.updateBookingElement
          }, null, 8, ["parameter-object", "multiselect-options", "asyncAttributes", "componentsProps", "onValueUpdated"])) : field.displayed ? (openBlock(), createBlock(_component_Parameter, {
            ref_for: true,
            ref: "slot_" + field.param,
            key: field.reload ? field.reload + field.param : field.param,
            "parameter-object": field,
            "help-text-type": "below",
            "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null,
            asyncAttributes: [
              (_d = _ctx.fields.find((f) => f.param === "event_id")) == null ? void 0 : _d.value,
              (_e = _ctx.fields.find((f) => f.param === "user")) == null ? void 0 : _e.value,
              field.param
            ],
            onValueUpdated: $options.updateForm
          }, null, 8, ["parameter-object", "multiselect-options", "asyncAttributes", "onValueUpdated"])) : createCommentVNode("", true),
          field.param === "juror" && (!field.value || field.value.length === 0) ? (openBlock(), createBlock(_component_Info, {
            key: field.value,
            text: _ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_NO_SELECTED"),
            class: "tw-mt-4"
          }, null, 8, ["text"])) : createCommentVNode("", true)
        ])), [
          [vShow, field.displayed]
        ]);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_6, [
      createBaseVNode("button", {
        class: "tw-btn-cancel",
        onClick: _cache[1] || (_cache[1] = (...args) => $options.onClosePopup && $options.onClosePopup(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_CANCEL")), 1),
      createBaseVNode("button", {
        class: "tw-btn-primary",
        disabled: $options.disabledSubmit || _ctx.submitted,
        onClick: _cache[2] || (_cache[2] = ($event) => $options.editSlot())
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_CONFIRM")), 9, _hoisted_7)
    ]),
    !_ctx.fields[1].displayed ? (openBlock(), createElementBlock("div", _hoisted_8)) : createCommentVNode("", true)
  ]);
}
const EditSlot = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  EditSlot as default
};
