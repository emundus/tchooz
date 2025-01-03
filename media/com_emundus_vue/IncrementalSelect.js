import { _ as _export_sfc, o as openBlock, c as createElementBlock, a as createBaseVNode, w as withDirectives, O as vModelText, t as toDisplayString, d as createCommentVNode, b as Fragment, r as renderList } from "./app_emundus.js";
const IncrementalSelect_vue_vue_type_style_index_0_lang = "";
const _sfc_main = {
  props: {
    options: {
      type: Array,
      required: true
    },
    defaultValue: {
      type: Number,
      required: false
    },
    locked: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      originalOptions: [],
      newValue: {
        id: 0,
        label: ""
      },
      existingValues: [],
      newExistingLabel: "",
      selectedExistingValue: -1,
      isNewVal: true,
      showOptions: false,
      hoverOptions: false,
      hoverUnselect: false
    };
  },
  beforeMount() {
    this.originalOptions = JSON.parse(JSON.stringify(this.options));
    this.existingValues = this.options;
  },
  mounted() {
    if (this.defaultValue != null) {
      this.onSelectValue(this.defaultValue);
    }
  },
  methods: {
    updateDefaultValue() {
      this.onSelectValue(this.defaultValue);
    },
    onSelectValue(value) {
      this.selectedExistingValue = value;
      if (this.selectedExistingValue === -1) {
        this.unselectExistingValue();
      } else {
        this.isNewVal = false;
        let detachedValue = this.existingValues.find((value2) => {
          return value2.id === this.selectedExistingValue;
        });
        if (detachedValue) {
          detachedValue = JSON.parse(JSON.stringify(detachedValue));
          this.newExistingLabel = detachedValue.label;
          this.newValue = detachedValue;
          this.emitValueChanges();
        } else {
          this.unselectExistingValue();
        }
      }
      this.showOptions = false;
    },
    unselectExistingValue() {
      this.isNewVal = true;
      let foundValue = this.existingValues.find((existingValue) => {
        return existingValue.id == this.selectedExistingValue;
      });
      this.newExistingLabel = foundValue ? foundValue.label : "";
      this.selectedExistingValue = -1;
      this.newValue.label = "";
      this.newValue.id = 0;
      this.existingValues = JSON.parse(JSON.stringify(this.originalOptions));
      this.showOptions = false;
      this.hoverOptions = false;
      this.hoverUnselect = false;
      this.emitValueChanges();
    },
    emitValueChanges(event = null) {
      if (this.hoverUnselect) {
        return;
      }
      if (this.showOptions && !this.hoverOptions) {
        this.showOptions = false;
      }
      if (this.isNewVal) {
        this.$emit("update-value", this.newValue);
      } else {
        this.existingValues.forEach((value) => {
          if (value.id == this.selectedExistingValue) {
            if (this.newExistingLabel !== value.label) {
              value.label = this.newExistingLabel;
              this.originalOptions = JSON.parse(JSON.stringify(this.existingValues));
              this.$emit("update-existing-values", this.existingValues);
              this.$emit("update-value", value);
            } else {
              this.$emit("update-existing-values", this.existingValues);
              this.$emit("update-value", value);
            }
          }
        });
      }
    }
  },
  computed: {
    displayedOptions() {
      return !this.isNewVal ? this.existingValues : this.existingValues.filter((existingDoc) => {
        return existingDoc.id !== -1 ? existingDoc.label.toLowerCase().includes(this.newValue.label.toLowerCase()) : true;
      });
    }
  },
  watch: {
    defaultValue: {
      handler(newValue) {
        this.updateDefaultValue();
      }
    }
  }
};
const _hoisted_1 = {
  id: "incremental-selector",
  class: "tw-mt-2"
};
const _hoisted_2 = { class: "tw-w-full tw-flex tw-items-center tw-mb-4" };
const _hoisted_3 = {
  key: 0,
  id: "new-value",
  class: "tw-w-full"
};
const _hoisted_4 = { class: "em-main-500-color" };
const _hoisted_5 = {
  key: 1,
  id: "existing-value",
  class: "tw-w-full"
};
const _hoisted_6 = { class: "tw-w-full tw-flex tw-items-center tw-justify-between" };
const _hoisted_7 = { class: "em-main-500-color" };
const _hoisted_8 = ["value", "selected", "onClick"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      $data.isNewVal ? (openBlock(), createElementBlock("div", _hoisted_3, [
        withDirectives(createBaseVNode("input", {
          type: "text",
          class: "tw-w-full !tw-mb-0",
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.newValue.label = $event),
          onFocusin: _cache[1] || (_cache[1] = ($event) => $data.showOptions = true),
          onFocusout: _cache[2] || (_cache[2] = (...args) => $options.emitValueChanges && $options.emitValueChanges(...args))
        }, null, 544), [
          [vModelText, $data.newValue.label]
        ]),
        createBaseVNode("i", _hoisted_4, "(" + toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_NEW_VALUE")) + ")", 1)
      ])) : createCommentVNode("", true),
      !$data.isNewVal ? (openBlock(), createElementBlock("div", _hoisted_5, [
        createBaseVNode("div", _hoisted_6, [
          withDirectives(createBaseVNode("input", {
            type: "text",
            class: "tw-w-full !tw-mb-0 em-border-main-500 important",
            "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.newExistingLabel = $event),
            onFocusout: _cache[4] || (_cache[4] = (...args) => $options.emitValueChanges && $options.emitValueChanges(...args))
          }, null, 544), [
            [vModelText, $data.newExistingLabel]
          ]),
          !$props.locked ? (openBlock(), createElementBlock("span", {
            key: 0,
            onClick: _cache[5] || (_cache[5] = (...args) => $options.unselectExistingValue && $options.unselectExistingValue(...args)),
            class: "material-symbols-outlined tw-cursor-pointer",
            onMouseenter: _cache[6] || (_cache[6] = ($event) => $data.hoverUnselect = true),
            onMouseleave: _cache[7] || (_cache[7] = ($event) => $data.hoverUnselect = false)
          }, "close", 32)) : createCommentVNode("", true)
        ]),
        createBaseVNode("i", _hoisted_7, "(" + toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_EXISTING_VALUE")) + ")", 1)
      ])) : createCommentVNode("", true)
    ]),
    $data.existingValues && $data.showOptions ? (openBlock(), createElementBlock("ul", {
      key: 0,
      class: "em-custom-selector em-border-neutral-300 tw-w-full",
      onMouseenter: _cache[8] || (_cache[8] = ($event) => $data.hoverOptions = true),
      onMouseleave: _cache[9] || (_cache[9] = ($event) => $data.hoverOptions = false)
    }, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedOptions, (option) => {
        return openBlock(), createElementBlock("li", {
          key: option.id,
          value: option.id,
          selected: $data.selectedExistingValue == option.id,
          class: "em-custom-selector-option tw-cursor-pointer em-p-8",
          onClick: ($event) => $options.onSelectValue(option.id)
        }, toDisplayString(option.label), 9, _hoisted_8);
      }), 128))
    ], 32)) : createCommentVNode("", true)
  ]);
}
const IncrementalSelect = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  IncrementalSelect as I
};
