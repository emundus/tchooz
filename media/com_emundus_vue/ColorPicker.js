import { _ as _export_sfc, o as openBlock, c as createElementBlock, d as createBaseVNode, j as normalizeStyle, w as withDirectives, v as vShow, F as Fragment, e as renderList, n as normalizeClass } from "./app_emundus.js";
const basicPreset = [
  "red-1",
  "red-2",
  "pink-1",
  "pink-2",
  "purple-1",
  "purple-2",
  "light-blue-1",
  "light-blue-2",
  "blue-1",
  "blue-2",
  "blue-3",
  "green-1",
  "green-2",
  "yellow-1",
  "yellow-2",
  "orange-1",
  "orange-2",
  "beige",
  "brown",
  "grey-1",
  "grey-2",
  "black"
];
const darkPreset = ["red-2", "pink-2", "purple-2", "blue-3", "green-2", "orange-2", "brown", "black"];
const extractPropertyFromPreset = (presetName) => {
  if (typeof presetName !== "string") {
    return null;
  } else if (presetName === "basic" && typeof basicPreset === "object") {
    let root = document.querySelector(":root");
    let variables = getComputedStyle(root);
    let swatches = [];
    for (const swatch of basicPreset) {
      let color = variables.getPropertyValue("--em-" + swatch);
      swatches.push(color);
    }
    return swatches;
  } else if (presetName === "dark" && typeof darkPreset === "object") {
    let root = document.querySelector(":root");
    let variables = getComputedStyle(root);
    let swatches = [];
    for (const swatch of darkPreset) {
      let color = variables.getPropertyValue("--em-" + swatch);
      swatches.push(color);
    }
    return swatches;
  } else {
    return null;
  }
};
const _sfc_main = {
  name: "ColorPicker",
  props: {
    swatches: {
      type: [Array, String],
      default: () => "basic"
    },
    position: {
      type: String,
      default: "top"
      // top, bottom, left, right
    },
    rowLength: {
      type: Number,
      default: 6
    },
    modelValue: {
      type: String,
      default: ""
    }
  },
  emits: ["input", "update:modelValue"],
  data: () => ({
    isOpen: false
  }),
  mounted() {
    document.addEventListener("click", this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener("click", this.handleClickOutside);
  },
  methods: {
    swatchStyle(swatch) {
      const baseStyles = {
        backgroundColor: swatch !== "" ? swatch : "#FFFFFF"
      };
      return {
        ...baseStyles
      };
    },
    updateSwatch(swatch) {
      this.$emit("update:modelValue", swatch);
      this.$emit("input", swatch);
      this.isOpen = false;
    },
    togglePopover() {
      const otherColorPickers = document.querySelectorAll(".color-picker-container");
      otherColorPickers.forEach((colorPicker) => {
        colorPicker.querySelector(".vue-swatches__wrapper").style.display = "none";
      });
      this.isOpen = !this.isOpen;
    },
    handleClickOutside(event) {
      const clickedElement = event.target;
      if (!clickedElement.closest("#" + this.$attrs.id)) {
        this.isOpen = false;
      }
    }
  },
  computed: {
    computedSwatches() {
      if (this.swatches instanceof Array) return this.swatches;
      if (typeof this.swatches === "string") {
        return extractPropertyFromPreset(this.swatches);
      } else {
        return [];
      }
    },
    selectedSwatchStyle() {
      return {
        backgroundColor: this.modelValue !== "" ? this.modelValue : "#FFFFFF"
      };
    },
    wrapperStyle() {
      switch (this.position) {
        case "top":
          return { bottom: "35px" };
        case "bottom":
          return { top: "35px" };
        default:
          return { bottom: "35px" };
      }
    }
  }
};
const _hoisted_1 = ["id"];
const _hoisted_2 = ["onClick"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    id: _ctx.id,
    class: "color-picker-container tw-relative"
  }, [
    createBaseVNode("div", {
      class: "tw-rounded-full tw-h-[24px] tw-w-[24px] tw-cursor-pointer",
      style: normalizeStyle($options.selectedSwatchStyle),
      onClick: _cache[0] || (_cache[0] = (...args) => $options.togglePopover && $options.togglePopover(...args))
    }, null, 4),
    withDirectives(createBaseVNode("div", {
      class: normalizeClass(["vue-swatches__wrapper", `tw-grid-cols-${this.rowLength}`]),
      style: normalizeStyle($options.wrapperStyle)
    }, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.computedSwatches, (swatchRow, index) => {
        return openBlock(), createElementBlock("div", {
          key: index,
          class: "vue-swatches__row tw-rounded-full tw-h-[24px] tw-w-[24px] tw-cursor-pointer hover:tw-scale-110",
          style: normalizeStyle($options.swatchStyle(swatchRow)),
          onClick: ($event) => $options.updateSwatch(swatchRow)
        }, null, 12, _hoisted_2);
      }), 128))
    ], 6), [
      [vShow, _ctx.isOpen]
    ])
  ], 8, _hoisted_1);
}
const ColorPicker = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  ColorPicker as C,
  basicPreset as b
};
