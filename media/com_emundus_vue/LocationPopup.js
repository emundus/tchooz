import { _ as _export_sfc, M as Modal, r as resolveComponent, o as openBlock, a as createBlock, f as withCtx, h as createVNode, n as normalizeClass } from "./app_emundus.js";
import LocationForm from "./LocationForm.js";
const _sfc_main = {
  name: "LocationPopup",
  components: { LocationForm, Modal },
  props: {
    location_id: {
      type: Number,
      default: 0
    }
  },
  emits: ["close", "open"],
  methods: {
    beforeClose() {
      this.$emit("close");
    },
    beforeOpen() {
      this.$emit("open");
    },
    closeModal(location_id) {
      this.$emit("close", location_id);
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_LocationForm = resolveComponent("LocationForm");
  const _component_modal = resolveComponent("modal");
  return openBlock(), createBlock(_component_modal, {
    name: "add-location-modal",
    class: normalizeClass("placement-center tw-rounded tw-shadow-modal tw-px-4 tw-max-h-[80vh] tw-overflow-y-auto"),
    transition: "nice-modal-fade",
    width: "600px",
    delay: 100,
    adaptive: true,
    clickToClose: false,
    onClosed: $options.beforeClose,
    onBeforeOpen: $options.beforeOpen
  }, {
    default: withCtx(() => [
      createVNode(_component_LocationForm, {
        "is-modal": true,
        id: $props.location_id,
        onClose: $options.closeModal
      }, null, 8, ["id", "onClose"])
    ]),
    _: 1
  }, 8, ["onClosed", "onBeforeOpen"]);
}
const LocationPopup = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-3ef48c2d"]]);
export {
  LocationPopup as L
};
