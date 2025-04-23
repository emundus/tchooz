import { _ as _export_sfc, ab as userService, o as openBlock, c as createElementBlock, d as createBaseVNode, t as toDisplayString, g as withModifiers, w as withDirectives, Y as vModelCheckbox, F as Fragment, e as renderList } from "./app_emundus.js";
const _sfc_main = {
  name: "ExportsSlotsModal",
  emits: ["close", "selectionConfirm"],
  data() {
    return {
      selectedItemsFromView: [],
      selectedItemsFromProfile: [],
      checkboxItemsFromProfile: [],
      checkboxItemsFromBookingsView: [
        { label: "COM_EMUNDUS_ONBOARD_LABEL_REGISTRANTS" },
        { label: "COM_EMUNDUS_REGISTRANTS_USER" },
        { label: "COM_EMUNDUS_REGISTRANTS_DAY" },
        { label: "COM_EMUNDUS_REGISTRANTS_HOUR" },
        { label: "COM_EMUNDUS_REGISTRANTS_LOCATION" },
        { label: "COM_EMUNDUS_REGISTRANTS_ROOM" },
        { label: "COM_EMUNDUS_REGISTRANTS_ASSOC_USER" }
      ],
      selectAll: false,
      loading: false
    };
  },
  created() {
    this.loading = true;
    this.getColumnsFromProfileForm().then((checkboxItems) => {
      this.checkboxItemsFromProfile = checkboxItems;
      this.loading = false;
      this.selectedItemsFromView = this.checkboxItemsFromBookingsView.map((item) => item.label);
    });
  },
  methods: {
    async getColumnsFromProfileForm() {
      return new Promise((resolve, reject) => {
        userService.getColumnsFromProfileForm().then((response) => {
          if (response.status) {
            resolve(response.data);
          } else {
            console.error("Error when trying to retrieve columns from profile form", response.error);
            reject([]);
          }
        });
      });
    },
    toggleAll() {
      if (this.selectAll) {
        this.selectedItemsFromView = this.checkboxItemsFromBookingsView.map((item) => item.label);
        this.selectedItemsFromProfile = this.checkboxItemsFromProfile.map((item) => item.id);
      } else {
        this.selectedItemsFromView = [];
        this.selectedItemsFromProfile = [];
      }
    },
    onClosePopup() {
      this.$emit("close");
    },
    onConfirmSelection() {
      const sortedViewSelection = this.checkboxItemsFromBookingsView.filter((item) => this.selectedItemsFromView.includes(item.label)).map((item) => item.label);
      const sortedProfileSelection = this.checkboxItemsFromProfile.filter((item) => this.selectedItemsFromProfile.includes(item.id)).map((item) => item.id);
      this.$emit("selectionConfirm", {
        viewSelection: sortedViewSelection,
        profileSelection: sortedProfileSelection
      });
      this.onClosePopup();
    }
  },
  watch: {
    selectedItemsFromView() {
      this.selectAll = this.selectedItemsFromView.length === this.checkboxItemsFromBookingsView.length && this.selectedItemsFromProfile.length === this.checkboxItemsFromProfile.length;
    },
    selectedItemsFromProfile() {
      this.selectAll = this.selectedItemsFromView.length === this.checkboxItemsFromBookingsView.length && this.selectedItemsFromProfile.length === this.checkboxItemsFromProfile.length;
    }
  }
};
const _hoisted_1 = { class: "tw-w-full tw-p-6" };
const _hoisted_2 = {
  key: 0,
  class: "em-page-loader"
};
const _hoisted_3 = { key: 1 };
const _hoisted_4 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_5 = { class: "tw-mb-6 tw-text-center" };
const _hoisted_6 = { class: "tw-mb-6 tw-block" };
const _hoisted_7 = { class: "tw-flex tw-gap-6" };
const _hoisted_8 = { class: "tw-flex tw-flex-1 tw-items-center tw-gap-2" };
const _hoisted_9 = {
  for: "all",
  class: "checkbox-label tw-mt-1.5 tw-cursor-pointer tw-align-middle"
};
const _hoisted_10 = { class: "tw-flex tw-gap-6" };
const _hoisted_11 = { class: "tw-flex-1" };
const _hoisted_12 = ["value", "id"];
const _hoisted_13 = ["for"];
const _hoisted_14 = { class: "tw-flex-1" };
const _hoisted_15 = ["value", "id"];
const _hoisted_16 = ["for"];
const _hoisted_17 = { class: "tw-mt-6 tw-flex tw-justify-between" };
const _hoisted_18 = ["disabled"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1, [
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_2)) : (openBlock(), createElementBlock("div", _hoisted_3, [
      createBaseVNode("div", _hoisted_4, [
        createBaseVNode("h2", _hoisted_5, toDisplayString(_ctx.translate("COM_EMUNDUS_EVENTS_EMARGEMENT")), 1),
        createBaseVNode("button", {
          class: "tw-cursor-pointer tw-bg-transparent",
          onClick: _cache[0] || (_cache[0] = withModifiers((...args) => $options.onClosePopup && $options.onClosePopup(...args), ["prevent"]))
        }, _cache[7] || (_cache[7] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
        ]))
      ]),
      createBaseVNode("span", _hoisted_6, toDisplayString(_ctx.translate("COM_EMUNDUS_EXPORTS_SELECT_INFORMATIONS")), 1),
      createBaseVNode("div", _hoisted_7, [
        createBaseVNode("div", _hoisted_8, [
          withDirectives(createBaseVNode("input", {
            type: "checkbox",
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.selectAll = $event),
            id: "all",
            onChange: _cache[2] || (_cache[2] = (...args) => $options.toggleAll && $options.toggleAll(...args)),
            class: "tw-h-4 tw-w-4 tw-cursor-pointer"
          }, null, 544), [
            [vModelCheckbox, $data.selectAll]
          ]),
          createBaseVNode("label", _hoisted_9, toDisplayString(_ctx.translate("ALL_FEMININE")), 1)
        ])
      ]),
      _cache[8] || (_cache[8] = createBaseVNode("hr", { class: "tw-my-2 tw-w-full tw-border-t tw-border-gray-300" }, null, -1)),
      createBaseVNode("div", _hoisted_10, [
        createBaseVNode("div", _hoisted_11, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.checkboxItemsFromBookingsView, (item, index) => {
            return openBlock(), createElementBlock("div", {
              key: "booking-" + index,
              class: "tw-mb-2 tw-flex tw-items-center tw-gap-2"
            }, [
              withDirectives(createBaseVNode("input", {
                type: "checkbox",
                "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.selectedItemsFromView = $event),
                value: item.label,
                id: "checkbox-booking-" + index + this.checkboxItemsFromProfile.length,
                class: "tw-h-4 tw-w-4 tw-cursor-pointer"
              }, null, 8, _hoisted_12), [
                [vModelCheckbox, $data.selectedItemsFromView]
              ]),
              createBaseVNode("label", {
                for: "checkbox-booking-" + index + this.checkboxItemsFromProfile.length,
                class: "checkbox-label tw-mt-1.5 tw-cursor-pointer tw-align-middle"
              }, toDisplayString(_ctx.translate(item.label)), 9, _hoisted_13)
            ]);
          }), 128))
        ]),
        createBaseVNode("div", _hoisted_14, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.checkboxItemsFromProfile, (item, index) => {
            return openBlock(), createElementBlock("div", {
              key: "profile-" + index,
              class: "tw-mb-2 tw-flex tw-items-center tw-gap-2"
            }, [
              withDirectives(createBaseVNode("input", {
                type: "checkbox",
                "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.selectedItemsFromProfile = $event),
                value: item.id,
                id: "checkbox-booking-" + index,
                class: "tw-h-4 tw-w-4 tw-cursor-pointer"
              }, null, 8, _hoisted_15), [
                [vModelCheckbox, $data.selectedItemsFromProfile]
              ]),
              createBaseVNode("label", {
                for: "checkbox-booking-" + index,
                class: "checkbox-label tw-mt-1.5 tw-cursor-pointer tw-align-middle"
              }, toDisplayString(_ctx.translate(item.label)), 9, _hoisted_16)
            ]);
          }), 128))
        ])
      ]),
      createBaseVNode("div", _hoisted_17, [
        createBaseVNode("button", {
          onClick: _cache[5] || (_cache[5] = (...args) => $options.onClosePopup && $options.onClosePopup(...args)),
          class: "tw-btn-secondary"
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_CANCEL_EXPORT")), 1),
        createBaseVNode("button", {
          onClick: _cache[6] || (_cache[6] = (...args) => $options.onConfirmSelection && $options.onConfirmSelection(...args)),
          disabled: $data.selectedItemsFromView.length === 0 && $data.selectedItemsFromProfile.length === 0,
          class: "tw-btn-primary"
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_CONFIRM_EXPORT")), 9, _hoisted_18)
      ])
    ]))
  ]);
}
const ExportsSlotsModal = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  ExportsSlotsModal as default
};
