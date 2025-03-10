import { _ as _export_sfc, o as openBlock, c as createElementBlock, n as normalizeClass, j as normalizeStyle } from "./app_emundus.js";
const _sfc_main = {
  name: "Skeleton.vue",
  props: {
    height: {
      type: String,
      default: "auto"
    },
    width: {
      type: String,
      default: "auto"
    },
    classes: {
      type: String,
      default: ""
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", {
    class: normalizeClass(["em-skeleton", $props.classes]),
    style: normalizeStyle({
      height: $props.height,
      width: $props.width
    })
  }, null, 6);
}
const Skeleton = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-a19fd706"]]);
export {
  Skeleton as S
};
