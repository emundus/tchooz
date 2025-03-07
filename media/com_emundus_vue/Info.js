import { _ as _export_sfc, o as openBlock, c as createElementBlock, n as normalizeClass, t as toDisplayString, b as createCommentVNode, d as createBaseVNode } from "./app_emundus.js";
const _sfc_main = {
  name: "Info",
  components: {},
  props: {
    text: {
      type: String,
      required: true
    },
    bgColor: {
      type: String,
      default: "tw-bg-blue-50"
    },
    icon: {
      type: String,
      default: "info"
    },
    iconColor: {
      type: String,
      default: "tw-text-blue-500"
    },
    iconType: {
      type: String,
      default: "material-symbols-outlined"
    },
    displayIcon: {
      type: Boolean,
      default: true
    },
    textColor: {
      type: String,
      default: "tw-text-neutral-900"
    }
  },
  mixins: [],
  data() {
    return {
      textValueExtracted: "",
      loading: false
    };
  },
  created() {
    this.loading = true;
    this.textValueExtracted = this.translate(this.text);
    this.loading = false;
  },
  mounted() {
  },
  methods: {},
  computed: {
    borderColor() {
      return this.iconColor.replace("text", "border");
    }
  },
  watch: {
    text: function(val) {
      this.textValueExtracted = this.translate(val);
    }
  }
};
const _hoisted_1 = ["innerHTML"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    class: normalizeClass([[$props.bgColor, $options.borderColor], "tw-rounded tw-flex tw-items-start tw-gap-2 tw-px-5 tw-py-6 tw-border"])
  }, [
    $props.displayIcon ? (openBlock(), createElementBlock("span", {
      key: 0,
      class: normalizeClass([$props.iconType, $props.iconColor])
    }, toDisplayString($props.icon), 3)) : createCommentVNode("", true),
    createBaseVNode("div", {
      innerHTML: $data.textValueExtracted,
      class: normalizeClass([$props.textColor])
    }, null, 10, _hoisted_1)
  ], 2);
}
const Info = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Info as I
};
