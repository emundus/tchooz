import { _ as _export_sfc, o as openBlock, e as createElementBlock, d as createBaseVNode, t as toDisplayString, j as createVNode, b as withCtx, w as withDirectives, n as normalizeStyle, L as renderSlot, v as vShow, x as Transition } from "./app_emundus.js";
const Popover_vue_vue_type_style_index_0_scoped_935d1a05_lang = "";
const _sfc_main = {
  name: "Popover",
  props: {
    icon: {
      type: String,
      default: "more_vert"
    },
    position: {
      type: String,
      default: "bottom"
      // top, bottom, left, right
    },
    popoverContentStyle: {
      type: Object,
      default: () => ({})
    }
  },
  data: () => ({
    id: "popover-" + Math.random().toString(36).substring(2, 9),
    isOpen: false
  }),
  created() {
    this.calculatePosition();
    document.addEventListener("click", this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener("click", this.handleClickOutside);
  },
  methods: {
    calculatePosition() {
      const popoverContentContainer = this.$refs.popoverContent;
      if (popoverContentContainer) {
        const popoverContentWidth = popoverContentContainer.children[0].offsetWidth;
        const popoverContentHeight = popoverContentContainer.children[0].offsetHeight;
        const popoverToggleBtnWidth = popoverContentContainer.previousElementSibling.offsetWidth;
        const popoverToggleBtnHeight = popoverContentContainer.previousElementSibling.offsetHeight;
        const margin = 4;
        switch (this.position) {
          case "top":
            popoverContentContainer.style.left = `calc(50% - ${popoverContentWidth / 2}px)`;
            popoverContentContainer.style.bottom = `${popoverToggleBtnHeight + margin}px`;
            break;
          case "left":
            popoverContentContainer.style.top = `calc(50% - ${popoverContentHeight / 2}px)`;
            popoverContentContainer.style.right = `${popoverToggleBtnWidth + margin}px`;
            break;
          case "right":
            popoverContentContainer.style.top = `calc(50% - ${popoverContentHeight / 2}px)`;
            popoverContentContainer.style.left = `${popoverToggleBtnWidth + margin}px`;
            break;
          case "bottom":
          default:
            popoverContentContainer.style.left = `calc(50% - ${popoverContentWidth / 2}px)`;
            popoverContentContainer.style.top = `${popoverToggleBtnHeight + margin}px`;
            break;
        }
      }
    },
    onClickToggle() {
      this.isOpen = !this.isOpen;
      if (this.isOpen) {
        this.calculatePosition();
      }
    },
    onFocusOut() {
      this.isOpen = false;
    },
    handleClickOutside(event) {
      const clickedElement = event.target;
      if (!clickedElement.closest("#" + this.id)) {
        this.isOpen = false;
      }
    }
  }
};
const _hoisted_1 = ["id"];
const _hoisted_2 = ["id"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    id: _ctx.id,
    class: "popover-container",
    onFocusout: _cache[1] || (_cache[1] = (...args) => $options.onFocusOut && $options.onFocusOut(...args))
  }, [
    createBaseVNode("span", {
      class: "material-symbols-outlined popover-toggle-btn tw-cursor-pointer",
      onClick: _cache[0] || (_cache[0] = (...args) => $options.onClickToggle && $options.onClickToggle(...args))
    }, toDisplayString($props.icon), 1),
    createVNode(Transition, { name: "fade" }, {
      default: withCtx(() => [
        withDirectives(createBaseVNode("div", {
          class: "popover-content tw-shadow tw-rounded",
          ref: "popoverContent",
          id: "popover-content-" + _ctx.id,
          style: normalizeStyle($props.popoverContentStyle)
        }, [
          renderSlot(_ctx.$slots, "default", {}, void 0, true)
        ], 12, _hoisted_2), [
          [vShow, _ctx.isOpen]
        ])
      ]),
      _: 3
    })
  ], 40, _hoisted_1);
}
const Popover = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-935d1a05"]]);
export {
  Popover as P
};
