import { _ as _export_sfc, o as openBlock, c as createElementBlock, d as createBaseVNode, t as toDisplayString, b as createCommentVNode, D as script, r as resolveComponent, w as withDirectives, z as vModelSelect, F as Fragment, e as renderList, A as vModelText, a as createBlock, f as withCtx, m as createTextVNode, n as normalizeClass, h as createVNode, P as Popover, x as Pagination, B as _imports_0, g as withModifiers, v as vShow, M as Modal, u as useGlobalStore, q as ref, ab as userService, s as settingsService, S as Swal$1, aa as resolveDynamicComponent, p as Teleport, J as FetchClient } from "./app_emundus.js";
import ExportsSlotsModal from "./ExportSlotsModal.js";
import { S as Skeleton } from "./Skeleton.js";
import Calendar from "./Calendar.js";
import EditSlot from "./EditSlot.js";
import { P as Parameter } from "./Parameter.js";
import { e as eventsService } from "./events2.js";
import "./core.js";
import "./index.js";
import "./LocationPopup.js";
import "./LocationForm.js";
import "./EventBooking.js";
import "./Info.js";
import "./ColorPicker.js";
const _sfc_main$9 = {
  name: "Head",
  props: {
    title: {
      type: String,
      default: null
    },
    introduction: {
      type: String,
      default: null
    },
    addAction: {
      type: Object,
      default: null
    }
  },
  methods: {
    onClickAction(action) {
      this.$emit("action", action);
    }
  }
};
const _hoisted_1$9 = { class: "head tw-py-6" };
const _hoisted_2$9 = { class: "tw-mb-6 tw-flex tw-items-center tw-justify-between" };
const _hoisted_3$7 = {
  key: 0,
  class: "tw-text-neutral-700"
};
function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$9, [
    createBaseVNode("div", _hoisted_2$9, [
      createBaseVNode("h1", null, toDisplayString(_ctx.translate($props.title)), 1),
      $props.addAction ? (openBlock(), createElementBlock("a", {
        key: 0,
        id: "add-action-btn",
        class: "tw-btn-primary tw-w-auto tw-cursor-pointer tw-rounded-coordinator",
        onClick: _cache[0] || (_cache[0] = ($event) => $options.onClickAction($props.addAction))
      }, toDisplayString(_ctx.translate($props.addAction.label)), 1)) : createCommentVNode("", true)
    ]),
    $props.introduction ? (openBlock(), createElementBlock("p", _hoisted_3$7, toDisplayString(_ctx.translate($props.introduction)), 1)) : createCommentVNode("", true)
  ]);
}
const Head = /* @__PURE__ */ _export_sfc(_sfc_main$9, [["render", _sfc_render$9], ["__scopeId", "data-v-041fbbe6"]]);
const _sfc_main$8 = {
  name: "Filter",
  components: {
    Multiselect: script
  },
  props: {
    filter: {
      type: Object,
      required: true
    }
  },
  created() {
    if (this.filter.type === "multiselect" && this.filter.value && typeof this.filter.value !== "object") {
      const matched = this.filter.options.find((opt) => opt.value == this.filter.value);
      if (matched) {
        this.filter.value = matched;
      }
    }
  },
  methods: {
    onChangeFilter(filter) {
      this.$emit("change-filter", filter);
    },
    removeFilter(filter) {
      this.$emit("remove-filter", filter);
    }
  }
};
const _hoisted_1$8 = { class: "tw-flex tw-items-center tw-justify-between" };
const _hoisted_2$8 = { class: "!tw-mb-0 tw-font-medium" };
const _hoisted_3$6 = ["value"];
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("div", _hoisted_1$8, [
      createBaseVNode("label", _hoisted_2$8, toDisplayString(_ctx.translate($props.filter.label)), 1),
      !$props.filter.alwaysDisplay ? (openBlock(), createElementBlock("span", {
        key: 0,
        class: "material-icons-outlined tw-cursor-pointer tw-text-red-500",
        onClick: _cache[0] || (_cache[0] = ($event) => $options.removeFilter($props.filter))
      }, " close ")) : createCommentVNode("", true)
    ]),
    $props.filter.type === "select" ? withDirectives((openBlock(), createElementBlock("select", {
      key: 0,
      "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $props.filter.value = $event),
      onChange: _cache[2] || (_cache[2] = ($event) => $options.onChangeFilter($props.filter)),
      class: "tw-w-full"
    }, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($props.filter.options, (option) => {
        return openBlock(), createElementBlock("option", {
          key: option.value,
          value: option.value
        }, toDisplayString(_ctx.translate(option.label)), 9, _hoisted_3$6);
      }), 128))
    ], 544)), [
      [vModelSelect, $props.filter.value]
    ]) : $props.filter.type === "date" ? withDirectives((openBlock(), createElementBlock("input", {
      key: 1,
      type: "date",
      "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $props.filter.value = $event),
      onChange: _cache[4] || (_cache[4] = ($event) => $options.onChangeFilter($props.filter))
    }, null, 544)), [
      [vModelText, $props.filter.value]
    ]) : $props.filter.type === "time" ? withDirectives((openBlock(), createElementBlock("input", {
      key: 2,
      type: "time",
      "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $props.filter.value = $event),
      onChange: _cache[6] || (_cache[6] = ($event) => $options.onChangeFilter($props.filter))
    }, null, 544)), [
      [vModelText, $props.filter.value]
    ]) : $props.filter.type === "multiselect" ? (openBlock(), createBlock(_component_multiselect, {
      key: 3,
      modelValue: $props.filter.value,
      "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $props.filter.value = $event),
      options: $props.filter.options,
      multiple: false,
      searchable: true,
      "close-on-select": true,
      "clear-on-select": false,
      "preserve-search": true,
      "select-label": "",
      "deselect-label": "",
      selectedLabel: "",
      placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_FILTER_SEARCH_PLACEHOLDER"),
      label: "label",
      "track-by": "value",
      onSelect: _cache[8] || (_cache[8] = ($event) => $options.onChangeFilter($props.filter))
    }, {
      noResult: withCtx(() => [
        createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_MULTISELECT_NORESULTS")), 1)
      ]),
      _: 1
    }, 8, ["modelValue", "options", "placeholder"])) : createCommentVNode("", true)
  ]);
}
const Filter = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$8]]);
const _sfc_main$7 = {
  name: "Filters",
  components: {
    Filter,
    Multiselect: script
  },
  props: {
    filters: {
      type: Object,
      default: () => {
      }
    },
    currentTabKey: {
      type: Number,
      default: 0
    }
  },
  emits: ["update-filter"],
  data() {
    return {
      currentFilter: null,
      displayedFilters: []
    };
  },
  created() {
    const storedFilters = Object.keys(sessionStorage).filter(
      (key) => key.includes("tchooz_filter_" + this.currentTabKey)
    );
    if (storedFilters.length > 0) {
      this.displayedFilters = storedFilters.map((key) => {
        let filter = key.split("_").pop();
        filter = filter.split("/").shift();
        if (filter && this.filters[this.currentTabKey]) {
          let filterData = this.filters[this.currentTabKey].find((f) => f.key === filter);
          if (typeof filterData !== "undefined" && (typeof filterData.alwaysDisplay === "undefined" || filterData.alwaysDisplay !== true)) {
            return filterData;
          }
        }
        return null;
      });
      this.displayedFilters = this.displayedFilters.filter((f) => f !== null);
    }
    if (this.filters[this.currentTabKey] && this.filters[this.currentTabKey].length > 0) {
      const urlParams = new URLSearchParams(window.location.search);
      this.filters[this.currentTabKey].forEach((filter) => {
        if (filter.alwaysDisplay) {
          return;
        }
        if (urlParams.has(filter.key)) {
          const value = urlParams.get(filter.key);
          if (filter.options.find((option) => option.value == value)) {
            filter.value = value;
            if (!this.displayedFilters.find((f) => f.key === filter.key)) {
              this.displayedFilters.push(filter);
            } else {
              this.displayedFilters = this.displayedFilters.map((f) => {
                if (f.key === filter.key) {
                  f.value = value;
                }
                return f;
              });
            }
            sessionStorage.setItem(
              "tchooz_filter_" + this.currentTabKey + "_" + filter.key + "/" + document.location.hostname,
              filter.value
            );
          }
        }
      });
    }
    if (this.displayedFilters.length > 0) {
      this.displayedFilters.sort((a, b) => a.key.localeCompare(b.key));
    }
  },
  methods: {
    onChangeFilter(filter) {
      const fieldValue = typeof filter.value === "object" && filter.value !== null && "value" in filter.value ? filter.value.value : filter.value;
      sessionStorage.setItem(
        "tchooz_filter_" + this.currentTabKey + "_" + filter.key + "/" + document.location.hostname,
        fieldValue
      );
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has(filter.key)) {
        urlParams.set(filter.key, fieldValue);
      } else {
        urlParams.append(filter.key, fieldValue);
      }
      const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?" + urlParams.toString();
      window.history.replaceState({ path: newUrl }, "", newUrl);
      this.$emit("update-filter");
    },
    removeFilter(filter) {
      this.displayedFilters = this.displayedFilters.filter((f) => f.key !== filter.key);
      filter.value = filter.default ? filter.default : "all";
      sessionStorage.removeItem(
        "tchooz_filter_" + this.currentTabKey + "_" + filter.key + "/" + document.location.hostname
      );
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has(filter.key)) {
        urlParams.delete(filter.key);
        const newUrl = window.location.protocol + "//" + window.location.host + window.location.pathname + "?" + urlParams.toString();
        window.history.replaceState({ path: newUrl }, "", newUrl);
      }
      this.$emit("update-filter");
    },
    labelTranslate({ label }) {
      return this.translate(label);
    }
  },
  computed: {
    availableFilters() {
      return this.filters && this.filters[this.currentTabKey] ? this.filters[this.currentTabKey].filter((filter) => {
        return ((filter.type === "select" || filter.type === "multiselect") && filter.options.length > 0 || filter.type === "date" || filter.type === "time") && !filter.alwaysDisplay;
      }) : [];
    },
    defaultFilters() {
      return this.filters && this.filters[this.currentTabKey] ? this.filters[this.currentTabKey].filter((filter) => {
        return ((filter.type === "select" || filter.type === "multiselect") && filter.options.length > 0 || filter.type === "date" || filter.type === "time") && filter.alwaysDisplay;
      }) : [];
    }
  },
  watch: {
    currentFilter(value) {
      if (value && !this.displayedFilters.find((filter) => filter.key === value.key)) {
        this.displayedFilters.push(value);
        this.displayedFilters.sort((a, b) => a.key.localeCompare(b.key));
        sessionStorage.setItem(
          "tchooz_filter_" + this.currentTabKey + "_" + value.key + "/" + document.location.hostname,
          value.value
        );
        this.currentFilter = null;
      }
    }
  }
};
const _hoisted_1$7 = {
  id: "tab-filters",
  class: "tw-w-full"
};
const _hoisted_2$7 = { class: "tw-mb-2 tw-font-medium" };
const _hoisted_3$5 = { class: "tw-grid tw-grid-cols-3 tw-gap-4" };
const _hoisted_4$5 = { class: "tw-grid tw-grid-cols-3 tw-gap-4" };
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  const _component_Filter = resolveComponent("Filter");
  return openBlock(), createElementBlock("section", _hoisted_1$7, [
    $options.availableFilters.length > 0 ? (openBlock(), createElementBlock("div", {
      key: 0,
      class: normalizeClass({ "tw-mb-4": $data.displayedFilters.length > 0 })
    }, [
      createBaseVNode("label", _hoisted_2$7, toDisplayString(_ctx.translate("COM_EMUNDUS_ADD_FILTER")), 1),
      createBaseVNode("div", _hoisted_3$5, [
        createVNode(_component_multiselect, {
          id: "select-filter-" + $props.currentTabKey,
          modelValue: $data.currentFilter,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.currentFilter = $event),
          label: "label",
          "custom-label": $options.labelTranslate,
          "track-by": "key",
          options: $options.availableFilters,
          "options-limit": 100,
          multiple: false,
          taggable: false,
          placeholder: _ctx.translate("COM_EMUNDUS_FILTERS_CHOOSE_FILTER"),
          "select-label": _ctx.translate("PRESS_ENTER_TO_SELECT"),
          searchable: true,
          "preserve-search": true
        }, {
          noResult: withCtx(() => [
            createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_MULTISELECT_NORESULTS")), 1)
          ]),
          _: 1
        }, 8, ["id", "modelValue", "custom-label", "options", "placeholder", "select-label"])
      ])
    ], 2)) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_4$5, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.defaultFilters, (filter) => {
        return openBlock(), createElementBlock("div", {
          key: $props.currentTabKey + "-" + filter.key,
          class: "tw-flex tw-flex-col tw-gap-1"
        }, [
          createVNode(_component_Filter, {
            filter,
            onChangeFilter: $options.onChangeFilter
          }, null, 8, ["filter", "onChangeFilter"])
        ]);
      }), 128)),
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.displayedFilters, (filter) => {
        return openBlock(), createElementBlock("div", {
          key: $props.currentTabKey + "-" + filter.key,
          class: "tw-flex tw-flex-col tw-gap-1"
        }, [
          createVNode(_component_Filter, {
            filter,
            onChangeFilter: $options.onChangeFilter,
            onRemoveFilter: $options.removeFilter
          }, null, 8, ["filter", "onChangeFilter", "onRemoveFilter"])
        ]);
      }), 128))
    ])
  ]);
}
const Filters = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7]]);
const _sfc_main$6 = {
  name: "Exports",
  components: {
    Popover
  },
  props: {
    items: {
      type: Object,
      default: () => {
      }
    },
    checkedItems: {
      type: Array,
      default: () => []
    },
    views: {
      type: Object,
      default: () => {
      }
    },
    tab: {
      type: Object,
      default: () => {
      }
    },
    tabKey: {
      type: Number,
      default: ""
    },
    // V-Model
    view: {
      type: String,
      default: "table"
    },
    searches: {
      type: Object,
      default: () => {
      }
    }
  },
  emits: ["update:view", "update:searches"],
  data() {
    return {
      currentView: this.view,
      currentSearches: this.searches
    };
  },
  methods: {
    evaluateShowOn(showon = null) {
      if (showon === null) {
        return false;
      }
      let items = this.checkedItems;
      let show = [];
      items.forEach((item) => {
        if (typeof item === "number") {
          item = this.items[this.tabKey].find((i) => i.id === item);
        }
        switch (showon.operator) {
          case "==":
          case "=":
            show.push(item[showon.key] == showon.value);
            break;
          case "!=":
            show.push(item[showon.key] != showon.value);
            break;
          case ">":
            show.push(item[showon.key] > showon.value);
            break;
          case "<":
            show.push(item[showon.key] < showon.value);
            break;
          case ">=":
            show.push(item[showon.key] >= showon.value);
            break;
          case "<=":
            show.push(item[showon.key] <= showon.value);
            break;
          default:
            show.push(true);
        }
      });
      return show.every((s) => s === true);
    },
    onClickExport(exp) {
      this.$emit("exp", exp);
    }
  },
  computed: {
    multipleExportsPopover() {
      let exports = [];
      if (this.tab.exports) {
        exports = this.tab.exports.filter((exp) => {
          return exp.multiple;
        });
      }
      return exports;
    }
  },
  watch: {
    currentView() {
      this.$emit("update:view", this.currentView);
    },
    currentSearches() {
      this.$emit("update:searches", this.currentSearches);
    }
  }
};
const _hoisted_1$6 = {
  id: "default-exports",
  class: "tw-flex tw-gap-4"
};
const _hoisted_2$6 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_3$4 = { class: "tw-m-0 tw-list-none tw-items-center tw-p-4" };
const _hoisted_4$4 = ["onClick"];
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_popover = resolveComponent("popover");
  return openBlock(), createElementBlock("section", _hoisted_1$6, [
    createBaseVNode("div", _hoisted_2$6, [
      $options.multipleExportsPopover.length > 0 ? (openBlock(), createBlock(_component_popover, {
        key: 0,
        button: _ctx.translate("EXPORT"),
        "button-class": "tw-bg-white tw-border tw-h-[38px] hover:tw-border-form-border-hover tw-rounded-form",
        icon: "keyboard_arrow_down",
        position: "bottom-left",
        popoverContentStyle: { width: "max-content" },
        class: "custom-popover-arrow"
      }, {
        default: withCtx(() => [
          createBaseVNode("ul", _hoisted_3$4, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.multipleExportsPopover, (exp) => {
              return openBlock(), createElementBlock("li", {
                key: exp.name,
                onClick: ($event) => $options.onClickExport(exp),
                class: normalizeClass(["tw-px-2 tw-py-1.5", {
                  "tw-cursor-not-allowed tw-text-neutral-500": !(typeof exp.showon === "undefined" || $options.evaluateShowOn(exp.showon)),
                  "tw-cursor-pointer tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300": typeof exp.showon !== "undefined" && $options.evaluateShowOn(exp.showon) || typeof exp.showon === "undefined"
                }])
              }, toDisplayString(_ctx.translate(exp.label)), 11, _hoisted_4$4);
            }), 128))
          ])
        ]),
        _: 1
      }, 8, ["button"])) : createCommentVNode("", true)
    ])
  ]);
}
const Exports = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6]]);
const _sfc_main$5 = {
  name: "Actions",
  components: {
    Exports,
    Popover
  },
  props: {
    items: {
      type: Object,
      default: () => {
      }
    },
    checkedItems: {
      type: Array,
      default: () => []
    },
    views: {
      type: Object,
      default: () => {
      }
    },
    tab: {
      type: Object,
      default: () => {
      }
    },
    tabKey: {
      type: Number,
      default: 0
    },
    // V-Model
    view: {
      type: String,
      default: "table"
    },
    searches: {
      type: Object,
      default: () => {
      }
    }
  },
  emits: ["update:view", "update:searches"],
  data() {
    return {
      currentView: this.view,
      currentSearches: this.searches
    };
  },
  methods: {
    evaluateShowOn(showon = null) {
      if (showon === null) {
        return false;
      }
      let items = this.checkedItems;
      let show = [];
      items.forEach((item) => {
        if (typeof item === "number") {
          item = this.items[this.tabKey].find((i) => i.id === item);
        }
        switch (showon.operator) {
          case "==":
          case "=":
            show.push(item[showon.key] == showon.value);
            break;
          case "!=":
            show.push(item[showon.key] != showon.value);
            break;
          case ">":
            show.push(item[showon.key] > showon.value);
            break;
          case "<":
            show.push(item[showon.key] < showon.value);
            break;
          case ">=":
            show.push(item[showon.key] >= showon.value);
            break;
          case "<=":
            show.push(item[showon.key] <= showon.value);
            break;
          default:
            show.push(true);
        }
      });
      return show.every((s) => s === true);
    },
    searchItems() {
      if (this.currentSearches[this.tabKey].searchDebounce !== null) {
        clearTimeout(this.currentSearches[this.tabKey].searchDebounce);
      }
      if (this.currentSearches[this.tabKey].search === "") {
        sessionStorage.removeItem("tchooz_filter_" + this.tabKey + "_search/" + document.location.hostname);
      } else {
        sessionStorage.setItem(
          "tchooz_filter_" + this.tabKey + "_search/" + document.location.hostname,
          this.currentSearches[this.tabKey].search
        );
      }
      this.currentSearches[this.tabKey].searchDebounce = setTimeout(() => {
        if (this.currentSearches[this.tabKey].search !== this.currentSearches[this.tabKey].lastSearch) {
          this.currentSearches[this.tabKey].lastSearch = this.currentSearches[this.tabKey].search;
          this.$emit("updateItems", 1, this.tabKey);
        }
      }, 500);
    },
    changeViewType(currentView) {
      this.currentView = currentView.value;
      localStorage.setItem("tchooz_view_type/" + document.location.hostname, currentView.value);
    },
    onClickAction(action) {
      this.$emit("action", action);
    },
    onClickExport(exp) {
      this.$emit("exp", exp);
    },
    updateItems(page, tabKey) {
      this.$emit("updateItems", page, tabKey);
    }
  },
  computed: {
    multipleActionsPopover() {
      let actions = [];
      if (this.checkedItems.length > 0) {
        actions = this.tab.actions.filter((action) => {
          return action.multiple && action.display;
        });
      }
      return actions;
    }
  },
  watch: {
    currentView() {
      this.$emit("update:view", this.currentView);
    },
    currentSearches() {
      this.$emit("update:searches", this.currentSearches);
    }
  }
};
const _hoisted_1$5 = {
  id: "default-actions",
  class: "tw-flex tw-gap-4",
  style: { "margin-top": "1.75rem" }
};
const _hoisted_2$5 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_3$3 = { class: "tw-m-0 tw-list-none tw-items-center tw-p-4" };
const _hoisted_4$3 = ["onClick"];
const _hoisted_5$2 = {
  key: 1,
  class: "tw-flex tw-min-w-[15rem] tw-items-center"
};
const _hoisted_6$1 = ["placeholder", "disabled"];
const _hoisted_7$1 = { class: "view-type tw-flex tw-items-center tw-gap-2" };
const _hoisted_8$1 = ["onClick"];
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_popover = resolveComponent("popover");
  const _component_Exports = resolveComponent("Exports");
  return openBlock(), createElementBlock("section", _hoisted_1$5, [
    createBaseVNode("div", _hoisted_2$5, [
      $props.checkedItems.length > 0 && $options.multipleActionsPopover.length > 0 ? (openBlock(), createBlock(_component_popover, {
        key: 0,
        button: _ctx.translate("COM_EMUNDUS_ONBOARD_ACTIONS"),
        "button-class": "tw-bg-white tw-border tw-h-[38px] hover:tw-border-form-border-hover tw-rounded-form",
        icon: "keyboard_arrow_down",
        position: "bottom-left",
        class: "custom-popover-arrow"
      }, {
        default: withCtx(() => [
          createBaseVNode("ul", _hoisted_3$3, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.multipleActionsPopover, (action) => {
              return openBlock(), createElementBlock("li", {
                key: action.name,
                onClick: ($event) => $options.onClickAction(action),
                class: normalizeClass(["tw-px-2 tw-py-1.5", {
                  "tw-cursor-not-allowed tw-text-neutral-500": !(typeof action.showon === "undefined" || $options.evaluateShowOn(action.showon)),
                  "tw-cursor-pointer tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300": typeof action.showon !== "undefined" && $options.evaluateShowOn(action.showon) || typeof action.showon === "undefined"
                }])
              }, toDisplayString(_ctx.translate(action.label)), 11, _hoisted_4$3);
            }), 128))
          ])
        ]),
        _: 1
      }, 8, ["button"])) : createCommentVNode("", true),
      createVNode(_component_Exports, {
        items: $props.items,
        checkedItems: $props.checkedItems,
        views: $props.views,
        tab: $props.tab,
        "tab-key": $props.tabKey,
        view: $data.currentView,
        "onUpdate:view": _cache[0] || (_cache[0] = ($event) => $data.currentView = $event),
        searches: $data.currentSearches,
        "onUpdate:searches": _cache[1] || (_cache[1] = ($event) => $data.currentSearches = $event),
        onExp: $options.onClickExport,
        onUpdateItems: $options.updateItems
      }, null, 8, ["items", "checkedItems", "views", "tab", "tab-key", "view", "searches", "onExp", "onUpdateItems"]),
      $props.tab.displaySearch === true || typeof $props.tab.displaySearch === "undefined" ? (openBlock(), createElementBlock("div", _hoisted_5$2, [
        withDirectives(createBaseVNode("input", {
          name: "search",
          type: "text",
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $props.searches[$props.tabKey].search = $event),
          placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_SEARCH"),
          class: normalizeClass(["tw-m-0 !tw-h-[38px] !tw-rounded-coordinator", {
            "em-disabled-events": $props.items[$props.tabKey].length < 1 && $props.searches[$props.tabKey].search === ""
          }]),
          disabled: $props.items[$props.tabKey].length < 1 && $props.searches[$props.tabKey].search === "",
          onChange: _cache[3] || (_cache[3] = (...args) => $options.searchItems && $options.searchItems(...args)),
          onKeyup: _cache[4] || (_cache[4] = (...args) => $options.searchItems && $options.searchItems(...args))
        }, null, 42, _hoisted_6$1), [
          [vModelText, $props.searches[$props.tabKey].search]
        ]),
        createBaseVNode("span", {
          class: "material-symbols-outlined tw-ml-[-32px] tw-mr-2 tw-cursor-pointer",
          onClick: _cache[5] || (_cache[5] = (...args) => $options.searchItems && $options.searchItems(...args))
        }, " search ")
      ])) : createCommentVNode("", true)
    ]),
    createBaseVNode("div", _hoisted_7$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($props.views, (viewTypeOption) => {
        return openBlock(), createElementBlock("span", {
          key: viewTypeOption.value,
          class: normalizeClass(["material-symbols-outlined !tw-flex tw-h-[38px] tw-w-[38px] tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-coordinator tw-border tw-bg-neutral-0 tw-p-4", {
            "active tw-border-main-500 tw-text-main-500": viewTypeOption.value === $data.currentView,
            "tw-border-neutral-600 tw-text-neutral-600": viewTypeOption.value !== $data.currentView
          }]),
          onClick: ($event) => $options.changeViewType(viewTypeOption)
        }, toDisplayString(viewTypeOption.icon), 11, _hoisted_8$1);
      }), 128))
    ])
  ]);
}
const Actions = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5]]);
const _sfc_main$4 = {
  name: "Navigation",
  components: {
    Exports,
    Actions,
    Filters,
    Pagination
  },
  props: {
    tabs: {
      type: Array,
      default: () => []
    },
    filters: {
      type: Object,
      default: () => []
    },
    items: {
      type: Object,
      default: () => {
      }
    },
    checkedItems: {
      type: Array,
      default: () => []
    },
    views: {
      type: Object,
      default: () => {
      }
    },
    // V-Model
    view: {
      type: String,
      default: "table"
    },
    searches: {
      type: Object,
      default: () => {
      }
    },
    tab: {
      type: Object,
      default: () => {
      }
    },
    tabKey: {
      type: Number,
      default: ""
    },
    numberOfItemsToDisplay: {
      type: [Number, String],
      default: 5
    }
  },
  emits: ["update:views", "update:searches", "update:tab", "update:tabKey", "update:numberOfItemsToDisplay"],
  data() {
    return {
      currentView: this.view,
      currentSearches: this.searches,
      currentTab: this.tab,
      currentTabKey: this.tabKey,
      currentNumberOfItemsToDisplay: this.numberOfItemsToDisplay
    };
  },
  methods: {
    onSelectTab(tabKey) {
      let selected = false;
      if (this.currentTabKey !== tabKey) {
        if (this.tabs.find((tab) => tab.key === tabKey) !== "undefined") {
          this.orderBy = null;
          this.currentTabKey = tabKey;
          this.currentTab = this.tabs.find((tab) => tab.key === tabKey);
          sessionStorage.setItem("tchooz_selected_tab/" + document.location.hostname, tabKey);
          selected = true;
        }
        this.$emit("selectTab");
      }
      return selected;
    },
    onClickAction(action) {
      this.$emit("action", action, null, true);
    },
    onClickExport(exp) {
      this.$emit("exp", exp);
    },
    updateItems(page, tabKey) {
      this.$emit("updateItems", page, tabKey);
    },
    onChangeFilter() {
      this.$emit("updateItems", 1, this.currentTabKey);
    }
  },
  watch: {
    currentView() {
      this.$emit("update:view", this.currentView);
    },
    currentSearches() {
      this.$emit("update:searches", this.currentSearches);
    },
    currentTab() {
      this.$emit("update:tab", this.currentTab);
    },
    currentTabKey() {
      this.$emit("update:tabKey", this.currentTabKey);
    },
    currentNumberOfItemsToDisplay() {
      this.$emit("update:numberOfItemsToDisplay", this.currentNumberOfItemsToDisplay);
    }
  }
};
const _hoisted_1$4 = {
  key: 0,
  id: "list-nav"
};
const _hoisted_2$4 = { class: "tw-ml-0 tw-flex tw-list-none tw-pl-0" };
const _hoisted_3$2 = ["onClick"];
const _hoisted_4$2 = {
  id: "actions",
  class: "tw-mb-4 tw-mt-4 tw-flex tw-items-start tw-justify-between"
};
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Filters = resolveComponent("Filters");
  const _component_Actions = resolveComponent("Actions");
  const _component_Pagination = resolveComponent("Pagination");
  return openBlock(), createElementBlock("div", null, [
    $props.tabs.length > 1 ? (openBlock(), createElementBlock("nav", _hoisted_1$4, [
      createBaseVNode("ul", _hoisted_2$4, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($props.tabs, (tab) => {
          return openBlock(), createElementBlock("li", {
            key: tab.key,
            class: normalizeClass(["tw-cursor-pointer tw-font-normal", {
              "em-light-tabs em-light-selected-tab": $data.currentTabKey === tab.key,
              "em-light-tabs": $data.currentTabKey !== tab.key
            }]),
            onClick: ($event) => $options.onSelectTab(tab.key)
          }, toDisplayString(_ctx.translate(tab.title)), 11, _hoisted_3$2);
        }), 128))
      ])
    ])) : createCommentVNode("", true),
    createBaseVNode("section", _hoisted_4$2, [
      createVNode(_component_Filters, {
        filters: $props.filters,
        currentTabKey: $data.currentTabKey,
        onUpdateFilter: $options.onChangeFilter
      }, null, 8, ["filters", "currentTabKey", "onUpdateFilter"]),
      createVNode(_component_Actions, {
        items: $props.items,
        checkedItems: $props.checkedItems,
        views: $props.views,
        tab: $data.currentTab,
        "tab-key": $data.currentTabKey,
        view: $data.currentView,
        "onUpdate:view": _cache[0] || (_cache[0] = ($event) => $data.currentView = $event),
        searches: $data.currentSearches,
        "onUpdate:searches": _cache[1] || (_cache[1] = ($event) => $data.currentSearches = $event),
        onAction: $options.onClickAction,
        onExp: $options.onClickExport,
        onUpdateItems: $options.updateItems
      }, null, 8, ["items", "checkedItems", "views", "tab", "tab-key", "view", "searches", "onAction", "onExp", "onUpdateItems"])
    ]),
    this.items[this.currentTabKey].length > 0 && $props.view !== "calendar" ? (openBlock(), createBlock(_component_Pagination, {
      key: 1,
      limits: [5, 10, 25, 50, 100, "all"],
      dataLength: $data.currentTab.pagination.count,
      page: $data.currentTab.pagination.current,
      "onUpdate:page": _cache[2] || (_cache[2] = ($event) => $data.currentTab.pagination.current = $event),
      limit: $data.currentNumberOfItemsToDisplay,
      "onUpdate:limit": _cache[3] || (_cache[3] = ($event) => $data.currentNumberOfItemsToDisplay = $event)
    }, null, 8, ["dataLength", "page", "limit"])) : createCommentVNode("", true)
  ]);
}
const Navigation = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4]]);
const _sfc_main$3 = {
  name: "NoResults",
  props: {
    message: {
      type: String,
      default: "COM_EMUNDUS_NO_RESULT"
    }
  }
};
const _hoisted_1$3 = {
  id: "empty-list",
  class: "tw-text-center"
};
const _hoisted_2$3 = ["innerHTML"];
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$3, [
    _cache[0] || (_cache[0] = createBaseVNode("img", {
      src: _imports_0,
      class: "no-result-image",
      alt: "empty-list"
    }, null, -1)),
    createBaseVNode("p", {
      innerHTML: _ctx.translate($props.message)
    }, null, 8, _hoisted_2$3)
  ]);
}
const NoResults = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-cd8390c8"]]);
const _sfc_main$2 = {
  name: "Gantt",
  props: {
    defaultDisplay: {
      type: String,
      default: "year"
    },
    defaultStartDate: {
      type: String,
      default: /* @__PURE__ */ new Date()
    },
    defaultEndDate: {
      type: String,
      default: (/* @__PURE__ */ new Date()).setDate((/* @__PURE__ */ new Date()).getDate() + 365)
    },
    language: {
      type: String,
      default: "fr-FR"
    },
    periods: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      display: this.defaultDisplay,
      displayValues: ["year", "month", "week", "day"],
      dateRange: []
    };
  },
  mounted() {
    this.setDateRange(this.defaultStartDate, this.defaultEndDate);
  },
  methods: {
    changeDisplay(value) {
      if (this.displayValues.includes(value)) {
        this.display = value;
      }
    },
    setDateRange(startDate, endDate) {
      if (startDate && endDate) {
        switch (this.display) {
          case "year":
            const start = new Date(startDate);
            const end = new Date(endDate);
            let date = new Date(start);
            this.dateRange = [];
            while (date <= end) {
              const formattedString = new Intl.DateTimeFormat(this.language, {
                month: "short",
                year: "numeric"
              }).format(date);
              this.dateRange.push(formattedString);
              date.setMonth(date.getMonth() + 1);
            }
            break;
        }
      }
    }
  }
};
const _hoisted_1$2 = { id: "gantt-view" };
const _hoisted_2$2 = {
  id: "gantt-head",
  class: "tw-flex tw-flex-row"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$2, [
    _cache[0] || (_cache[0] = createBaseVNode("div", { id: "gantt-options" }, null, -1)),
    createBaseVNode("div", _hoisted_2$2, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.dateRange, (value) => {
        return openBlock(), createElementBlock("span", { key: value }, toDisplayString(value), 1);
      }), 128))
    ]),
    _cache[1] || (_cache[1] = createBaseVNode("div", { id: "gantt-rows" }, null, -1))
  ]);
}
const Gantt = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2]]);
const _sfc_main$1 = {
  name: "AssociateUser",
  components: { Parameter },
  props: {
    items: Array
  },
  emits: ["close", "valueUpdated"],
  data: () => ({
    actualLanguage: "fr-FR",
    cancelPopupOpenForBookingId: null,
    initialEvent: null,
    fields: [
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
        displayed: true
      },
      {
        param: "replace_jurors",
        type: "toggle",
        value: 1,
        label: "COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_USERS_REPLACE",
        displayed: true,
        hideLabel: true,
        optional: true
      }
    ]
  }),
  methods: {
    onClosePopup() {
      this.$emit("close");
    },
    assocUsers() {
      let jurors = [];
      this.fields[0].value.forEach((juror) => {
        jurors.push(juror.value);
      });
      eventsService.assocUsers(this.$props.items, jurors, this.fields[1].value).then((response) => {
        if (response.status === true) {
          Swal.fire({
            position: "center",
            icon: "success",
            title: Joomla.Text._("COM_EMUNDUS_ONBOARD_REGISTRANT_ASSOC_SAVED"),
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
    confirmButton: function() {
      return this.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_CONFIRM_ASSOCIATE").replace(
        "{{registrantCount}}",
        this.$props.items.length
      );
    }
  }
};
const _hoisted_1$1 = { class: "tw-pt-4" };
const _hoisted_2$1 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_3$1 = { class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6" };
const _hoisted_4$1 = { class: "tw-mb-8 tw-mt-5 tw-flex tw-justify-between" };
const _hoisted_5$1 = ["disabled"];
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Parameter = resolveComponent("Parameter");
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("div", _hoisted_1$1, [
      createBaseVNode("div", _hoisted_2$1, [
        createBaseVNode("h2", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_ASSOCIATE")), 1),
        createBaseVNode("button", {
          class: "tw-cursor-pointer tw-bg-transparent",
          onClick: _cache[0] || (_cache[0] = withModifiers((...args) => $options.onClosePopup && $options.onClosePopup(...args), ["prevent"]))
        }, _cache[3] || (_cache[3] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
        ]))
      ])
    ]),
    createBaseVNode("div", _hoisted_3$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList(_ctx.fields, (field) => {
        return withDirectives((openBlock(), createElementBlock("div", {
          key: field.param,
          class: normalizeClass("tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-2")
        }, [
          field.displayed ? (openBlock(), createBlock(_component_Parameter, {
            ref_for: true,
            ref: "assoc_user_" + field.param,
            key: field.reload ? field.reload + field.param : field.param,
            "parameter-object": field,
            "help-text-type": "below",
            "multiselect-options": field.multiselectOptions ? field.multiselectOptions : null
          }, null, 8, ["parameter-object", "multiselect-options"])) : createCommentVNode("", true)
        ])), [
          [vShow, field.displayed]
        ]);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_4$1, [
      createBaseVNode("button", {
        class: "tw-btn-cancel",
        onClick: _cache[1] || (_cache[1] = (...args) => $options.onClosePopup && $options.onClosePopup(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REGISTRANT_EDIT_CANCEL")), 1),
      createBaseVNode("button", {
        class: "tw-btn-primary",
        disabled: $options.disabledSubmit,
        onClick: _cache[2] || (_cache[2] = ($event) => $options.assocUsers())
      }, toDisplayString($options.confirmButton), 9, _hoisted_5$1)
    ])
  ]);
}
const AssociateUser = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "List",
  components: {
    ExportsSlotsModal,
    Modal,
    NoResults,
    Navigation,
    Head,
    Calendar,
    Skeleton,
    Popover,
    Gantt,
    EditSlot,
    AssociateUser
  },
  props: {
    defaultLists: {
      type: String,
      default: null
    },
    defaultType: {
      type: String,
      default: null
    },
    defaultFilter: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      loading: {
        lists: false,
        tabs: false,
        items: false,
        filters: true
      },
      components: {
        EditSlot,
        AssociateUser
      },
      lists: {},
      type: "forms",
      params: {},
      currentList: { title: "", tabs: [] },
      selectedListTab: 0,
      items: {},
      title: "",
      viewType: null,
      defaultViewsOptions: [
        { value: "table", icon: "dehaze" },
        { value: "blocs", icon: "grid_view" }
      ],
      searches: {},
      filters: {},
      alertBannerDisplayed: false,
      orderBy: null,
      order: "DESC",
      checkedItems: [],
      numberOfItemsToDisplay: 25,
      currentComponent: null,
      currentComponentElementId: null,
      showModal: false,
      showExportModal: false,
      exportClicked: null,
      eventExportClicked: null
    };
  },
  created() {
    const alertMessageContainer = document.querySelector(".alerte-message-container");
    if (alertMessageContainer) {
      this.alertBannerDisplayed = true;
      alertMessageContainer.querySelector("#close-preprod-alerte-container").addEventListener("click", () => {
        this.alertBannerDisplayed = false;
      });
    }
    this.loading.lists = true;
    this.loading.tabs = true;
    const globalStore = useGlobalStore();
    if (this.defaultType !== null) {
      this.params = {
        type: this.defaultType,
        shortlang: globalStore.getShortLang
      };
    } else {
      const data = globalStore.getDatas;
      this.params = Object.assign({}, ...Array.from(data).map(({ name, value }) => ({ [name]: value })));
    }
    this.type = this.params.type;
    const storageNbItemsDisplay = localStorage.getItem(
      "tchooz_number_of_items_to_display/" + document.location.hostname
    );
    if (storageNbItemsDisplay !== null) {
      this.numberOfItemsToDisplay = storageNbItemsDisplay !== "all" ? parseInt(storageNbItemsDisplay) : storageNbItemsDisplay;
    }
    this.initList();
  },
  methods: {
    initList() {
      this.lists = JSON.parse(atob(this.defaultLists));
      if (typeof this.lists[this.type] === "undefined") {
        console.error("List type " + this.type + " does not exist");
        window.location.href = "/";
      }
      this.currentList = this.lists[this.type];
      if (Object.prototype.hasOwnProperty.call(this.params, "tab")) {
        this.onSelectTab(this.params.tab);
      } else {
        const sessionTab = sessionStorage.getItem("tchooz_selected_tab/" + document.location.hostname);
        if (sessionTab !== null && this.currentList.tabs.some((tab) => tab.key === sessionTab)) {
          this.onSelectTab(sessionTab);
        } else {
          this.onSelectTab(this.currentList.tabs[0].key);
        }
      }
      let availableViews = this.currentTab.viewsOptions ? this.currentTab.viewsOptions : this.defaultViewsOptions;
      this.viewType = localStorage.getItem("tchooz_view_type/" + document.location.hostname);
      let isViewTypeAvailable = availableViews.some((view) => view.value === this.viewType);
      if (this.viewType === null || typeof this.viewType === "undefined" || !isViewTypeAvailable) {
        this.viewType = availableViews[0].value;
        if (this.viewType === null || typeof this.viewType === "undefined") {
          localStorage.setItem("tchooz_view_type/" + document.location.hostname, this.viewType);
        }
      }
      this.loading.lists = false;
      this.getListItems();
    },
    orderByColumn(column) {
      this.orderBy = column;
      this.order = this.order === "ASC" ? "DESC" : "ASC";
      this.getListItems(1, this.selectedListTab);
    },
    async getListItems(page = 1, tab = null, refreshFilters = false) {
      this.checkedItems = [];
      if (tab === null) {
        this.loading.tabs = true;
        this.items = ref(Object.assign({}, ...this.currentList.tabs.map((tab2) => ({ [tab2.key]: [] }))));
      } else {
        this.loading.items = true;
      }
      const tabs = tab === null ? this.currentList.tabs : [this.currentTab];
      if (tabs.length > 0) {
        tabs.forEach((tab2) => {
          if (typeof this.searches[tab2.key] === "undefined") {
            this.searches[tab2.key] = {
              search: "",
              lastSearch: "",
              debounce: null
            };
          }
          for (const action of tab2.actions) {
            action.display = true;
            if (action.acl) {
              const acl_options = action.acl.split("|");
              if (acl_options.length === 2) {
                userService.getAcl(acl_options[0], acl_options[1]).then((response) => {
                  if (response.status) {
                    action.display = response.right;
                  } else {
                    action.display = false;
                  }
                });
              } else {
                action.display = false;
              }
            }
          }
          const searchValue = sessionStorage.getItem(
            "tchooz_filter_" + this.selectedListTab + "_search/" + document.location.hostname
          );
          if (searchValue !== null && this.searches[this.selectedListTab]) {
            this.searches[this.selectedListTab].search = searchValue;
            this.searches[this.selectedListTab].lastSearch = searchValue;
          }
          this.setTabFilters(tab2, refreshFilters).then(() => {
            if (typeof tab2.getter !== "undefined") {
              let url = "/index.php?option=com_emundus&controller=" + tab2.controller + "&task=" + tab2.getter + "&lim=" + this.numberOfItemsToDisplay + "&page=" + page;
              if (this.searches[tab2.key].search !== "") {
                url += "&recherche=" + this.searches[tab2.key].search;
              }
              if (this.orderBy !== null && this.orderBy !== "") {
                url += "&order_by=" + this.orderBy;
                url += "&sort=" + this.order;
              }
              if (typeof this.filters[tab2.key] !== "undefined") {
                this.filters[tab2.key].forEach((filter) => {
                  const filterValue = typeof filter.value === "object" && filter.value !== null && "value" in filter.value ? filter.value.value : filter.value;
                  if (filterValue !== "" && filterValue !== "all") {
                    url += "&" + filter.key + "=" + filterValue;
                  }
                });
              }
              url += "&view=" + this.viewType;
              if (this.defaultFilter && this.defaultFilter.length > 0) {
                url += "&" + this.defaultFilter;
              }
              try {
                fetch(url).then((response) => response.json()).then((response) => {
                  if (response.status === true) {
                    if (typeof response.data.datas !== "undefined") {
                      this.items[tab2.key] = response.data.datas;
                      tab2.pagination = {
                        current: page,
                        count: response.data.count,
                        total: Math.ceil(response.data.count / this.numberOfItemsToDisplay)
                      };
                    }
                  } else {
                    console.error("Failed to get data : " + response.msg);
                  }
                  this.loading.tabs = false;
                  this.loading.items = false;
                }).catch((error) => {
                  console.error(error);
                  this.loading.tabs = false;
                  this.loading.items = false;
                });
              } catch (e) {
                console.error(e);
                this.loading.tabs = false;
                this.loading.items = false;
              }
            } else {
              this.loading.tabs = false;
              this.loading.items = false;
            }
          });
        });
      } else {
        this.loading.tabs = false;
        this.loading.items = false;
      }
    },
    async setTabFilters(tab, refreshFilters = false) {
      return new Promise(async (resolve) => {
        const urlParams = new URLSearchParams(window.location.search);
        if (typeof tab.filters !== "undefined" && tab.filters.length > 0) {
          if (typeof this.filters[tab.key] === "undefined" || refreshFilters) {
            this.loading.filters = true;
            this.filters[tab.key] = [];
            for (const filter of tab.filters) {
              let filterValue = filter.default ? filter.default : "all";
              let filterValueSession = sessionStorage.getItem(
                "tchooz_filter_" + this.selectedListTab + "_" + filter.key + "/" + document.location.hostname
              );
              if (urlParams.has(filter.key)) {
                filterValue = urlParams.get(filter.key);
              } else if (filterValueSession) {
                filterValue = filterValueSession;
              }
              if (!filter.values || refreshFilters) {
                if (filter.getter) {
                  this.filters[tab.key].push({
                    key: filter.key,
                    label: filter.label,
                    value: filterValue,
                    alwaysDisplay: filter.alwaysDisplay || false,
                    options: [],
                    type: filter.multiselect ? "multiselect" : filter.type || "select"
                  });
                  await this.setFilterOptions(
                    typeof filter.controller !== "undefined" ? filter.controller : tab.controller,
                    filter,
                    tab.key
                  );
                } else {
                  this.filters[tab.key].push({
                    key: filter.key,
                    label: filter.label,
                    value: filterValue,
                    alwaysDisplay: filter.alwaysDisplay || false,
                    options: filter.values || [],
                    type: filter.multiselect ? "multiselect" : filter.type || "select"
                  });
                }
              } else {
                this.filters[tab.key].push({
                  key: filter.key,
                  label: filter.label,
                  value: filterValue,
                  alwaysDisplay: filter.alwaysDisplay || false,
                  options: filter.values || [],
                  type: filter.multiselect ? "multiselect" : filter.type || "select"
                });
              }
            }
            this.loading.filters = false;
          }
        } else {
          this.loading.filters = false;
        }
        resolve();
      });
    },
    async setFilterOptions(controller, filter, tab) {
      return await fetch("index.php?option=com_emundus&controller=" + controller + "&task=" + filter.getter).then((response) => response.json()).then((response) => {
        if (response.status === true) {
          let options = response.data;
          if (typeof options[0] === "string") {
            options = options.map((option) => ({
              value: option,
              label: option
            }));
          }
          options.unshift({
            value: "all",
            label: this.translate(filter.allLabel)
          });
          this.filters[tab].find((f) => f.key === filter.key).options = options;
        } else {
          return [];
        }
      });
    },
    onClickAction(action, itemId = null, multiple = false, event = null) {
      if (action === null || typeof action !== "object" || typeof action.showon !== "undefined" && !this.evaluateShowOn(null, action.showon)) {
        return false;
      }
      if (event !== null) {
        event.stopPropagation();
      }
      let item = null;
      if (itemId !== null) {
        item = this.items[this.selectedListTab].find((item2) => item2.id === itemId);
      }
      if (action.name === "preview") {
        this.onClickPreview(item);
        return;
      }
      if (action.type === "modal") {
        this.currentComponent = action.component;
        this.showModal = true;
        this.currentComponentElementId = itemId;
        return;
      }
      if (action.type === "redirect") {
        let url = action.action;
        if (item !== null) {
          Object.keys(item).forEach((key) => {
            url = url.replace("%" + key + "%", item[key]);
          });
        }
        settingsService.redirectJRoute(url, useGlobalStore().getCurrentLang);
      } else {
        if (multiple) {
          if (this.checkedItems.length === 0) {
            return;
          }
        }
        let url = "index.php?option=com_emundus&controller=" + action.controller + "&task=" + action.action;
        let parameters = [];
        if (itemId !== null) {
          if (action.parameters) {
            let url_parameters = action.parameters;
            if (item !== null) {
              Object.keys(item).forEach((key) => {
                url_parameters = url_parameters.replace("%" + key + "%", item[key]);
              });
            }
            url += url_parameters;
          } else {
            parameters = { id: itemId };
          }
        } else if (multiple && this.checkedItems.length > 0) {
          parameters = { ids: this.checkedItems };
        }
        if (Object.prototype.hasOwnProperty.call(action, "confirm")) {
          Swal$1.fire({
            icon: "warning",
            title: this.translate(action.label),
            text: this.translate(action.confirm),
            showCancelButton: true,
            confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_OK"),
            cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              cancelButton: "em-swal-cancel-button",
              actions: "em-swal-double-action"
            }
          }).then((result) => {
            if (result.value) {
              this.executeAction(url, parameters, action.method);
            }
          });
        } else {
          this.executeAction(url, parameters, action.method);
        }
      }
    },
    closePopup() {
      this.currentComponent = null;
      this.showModal = false;
      this.showExportModal = false;
      this.currentComponentElementId = null;
    },
    onClickExport(exp, event = null) {
      if (event !== null) {
        event.stopPropagation();
      }
      if (exp === null || typeof exp !== "object" || typeof exp.showon !== "undefined" && !this.evaluateShowOn(null, exp.showon)) {
        return false;
      }
      if (exp.exportModal) {
        this.showExportModal = true;
        this.exportClicked = exp;
        this.eventExportClicked = null;
        return;
      }
      let url = "index.php?option=com_emundus&controller=" + exp.controller + "&task=" + exp.action;
      let parameters = {
        ids: this.checkedItems.length > 0 ? this.checkedItems : this.displayedItems.map((item) => item.id)
      };
      if (Object.prototype.hasOwnProperty.call(exp, "confirm")) {
        Swal$1.fire({
          icon: "warning",
          title: this.translate(exp.label),
          text: this.translate(exp.confirm),
          showCancelButton: true,
          confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_OK"),
          cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            cancelButton: "em-swal-cancel-button",
            actions: "em-swal-double-action"
          }
        }).then((result) => {
          if (result.value) {
            this.executeAction(url, parameters, exp.method);
          }
        });
      } else {
        this.executeAction(url, parameters, exp.method);
      }
    },
    onClickExportWithCheckboxes(checkboxesValues) {
      if (this.eventExportClicked !== null) {
        this.eventExportClicked.stopPropagation();
      }
      if (this.exportClicked === null || typeof this.exportClicked !== "object" || typeof this.exportClicked.showon !== "undefined" && !this.evaluateShowOn(null, this.exportClicked.showon)) {
        return false;
      }
      let url = "index.php?option=com_emundus&controller=" + this.exportClicked.controller + "&task=" + this.exportClicked.action;
      let parameters = {
        ids: this.checkedItems.length > 0 ? this.checkedItems : this.displayedItems.map((item) => item.id),
        checkboxesValuesFromView: JSON.stringify(checkboxesValues.viewSelection),
        checkboxesValuesFromProfile: JSON.stringify(checkboxesValues.profileSelection)
      };
      if (Object.prototype.hasOwnProperty.call(this.exportClicked, "confirm")) {
        Swal$1.fire({
          icon: "warning",
          title: this.translate(this.exportClicked.label),
          text: this.translate(this.exportClicked.confirm),
          showCancelButton: true,
          confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_OK"),
          cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            cancelButton: "em-swal-cancel-button",
            actions: "em-swal-double-action"
          }
        }).then((result) => {
          if (result.value) {
            this.executeAction(url, parameters, this.exportClicked.method);
          }
        });
      } else {
        this.executeAction(url, parameters, this.exportClicked.method);
      }
    },
    async executeAction(url, data = null, method = "get") {
      this.loading.items = true;
      let controller = url.split("controller=")[1].split("&")[0];
      let task = url.split("task=")[1].split("&")[0];
      let fetchClient = new FetchClient(controller);
      if (controller && task) {
        if (typeof method === "undefined") {
          method = "get";
        }
        let response = null;
        addLoader();
        if (method === "get") {
          response = await fetchClient.get(task, data);
        } else if (method === "post") {
          response = await fetchClient.post(task, data);
        } else if (method === "delete") {
          response = await fetchClient.delete(task, data);
        }
        removeLoader();
        if (response) {
          if (response.status === true || response.status === 1) {
            if (response.download_file) {
              Swal$1.fire({
                position: "center",
                icon: "success",
                title: this.translate("COM_EMUNDUS_REGISTRANTS_FILE_READY"),
                showCancelButton: true,
                showConfirmButton: true,
                confirmButtonText: this.translate("LINK_TO_DOWNLOAD"),
                cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_EDITOR_UNDO"),
                reverseButtons: true,
                allowOutsideClick: false,
                customClass: {
                  cancelButton: "em-swal-cancel-button",
                  confirmButton: "em-swal-confirm-button btn btn-success",
                  title: "w-full justify-center"
                },
                preConfirm: () => {
                  var link = document.createElement("a");
                  link.href = response.download_file;
                  link.download = "";
                  link.click();
                }
              });
            }
            if (response.redirect) {
              window.location.href = response.redirect;
            }
            this.getListItems();
          } else {
            if (response.msg) {
              Swal$1.fire({
                icon: "error",
                title: this.translate(response.msg),
                reverseButtons: true,
                customClass: {
                  title: "em-swal-title",
                  confirmButton: "em-swal-confirm-button",
                  actions: "em-swal-single-action"
                }
              });
            }
          }
        }
      }
      this.loading.items = false;
    },
    onClickPreview(item) {
      if (this.previewAction && (this.previewAction.title || this.previewAction.content)) {
        Swal$1.fire({
          title: this.previewAction.title === "label" ? item[this.previewAction.title][this.params.shortlang] : item[this.previewAction.title],
          html: '<div style="text-align: left;">' + item[this.previewAction.content] + "</div>",
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
      }
    },
    onSelectTab(tabKey) {
      let selected = false;
      if (this.selectedListTab !== tabKey) {
        this.onCheckAllitems();
        if (this.currentList.tabs.find((tab) => tab.key === tabKey) !== "undefined") {
          this.orderBy = null;
          this.selectedListTab = tabKey;
          sessionStorage.setItem("tchooz_selected_tab/" + document.location.hostname, tabKey);
          selected = true;
        }
      }
      return selected;
    },
    filterShowOnActions(actions, item) {
      return actions.filter((action) => {
        if (Object.prototype.hasOwnProperty.call(action, "showon")) {
          return this.evaluateShowOn(item, action.showon);
        }
        return true;
      });
    },
    evaluateShowOn(item = null, showon = null) {
      if (item === null && showon === null) {
        return false;
      }
      let items = [];
      if (item === null) {
        items = this.checkedItems;
      } else {
        items = [item];
      }
      let show = [];
      items.forEach((item2) => {
        if (typeof item2 === "number") {
          item2 = this.items[this.selectedListTab].find((i) => i.id === item2);
        }
        switch (showon.operator) {
          case "==":
          case "=":
            show.push(item2[showon.key] == showon.value);
            break;
          case "!=":
            show.push(item2[showon.key] != showon.value);
            break;
          case ">":
            show.push(item2[showon.key] > showon.value);
            break;
          case "<":
            show.push(item2[showon.key] < showon.value);
            break;
          case ">=":
            show.push(item2[showon.key] >= showon.value);
            break;
          case "<=":
            show.push(item2[showon.key] <= showon.value);
            break;
          default:
            show.push(true);
        }
      });
      return show.every((s) => s === true);
    },
    onCheckAllitems(e) {
      if (typeof e !== "undefined" && e.target.checked) {
        this.displayedItems.map(
          (item) => document.querySelector("#item-" + this.currentTab.key + "-" + item.id + " .item-check").checked = true
        );
        this.checkedItems = this.displayedItems.map((item) => item.id);
      } else {
        this.displayedItems.map(
          (item) => document.querySelector("#item-" + this.currentTab.key + "-" + item.id + " .item-check").checked = false
        );
        this.checkedItems = [];
        if (document.querySelector("#check-th input")) {
          document.querySelector("#check-th input").checked = false;
        }
      }
    },
    onCheckItem(id, e) {
      if (e.target.tagName === "A" || e.target.classList.contains("popover-toggle-btn")) {
        return;
      }
      let checkbox = document.querySelector("#item-" + this.currentTab.key + "-" + id + " .item-check");
      if (this.checkedItems.includes(id)) {
        this.checkedItems.splice(this.checkedItems.indexOf(id), 1);
        if (checkbox.checked) {
          checkbox.checked = false;
        }
      } else {
        this.checkedItems.push(id);
        if (!checkbox.checked) {
          checkbox.checked = true;
        }
      }
      document.querySelector("#check-th input").checked = this.checkedItems.length === this.displayedItems.length;
    },
    displayedColumns(item, viewType) {
      let columns = [];
      if (item && item.additional_columns) {
        columns = item.additional_columns.filter((column) => {
          return column.display === viewType || column.display === "all";
        });
      }
      return columns;
    },
    displayLongValue(e, html) {
      if (e) {
        e.stopPropagation();
      }
      Swal$1.fire({
        html: '<div style="text-align: left;">' + html + "</div>",
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action"
        }
      });
    }
  },
  computed: {
    resolvedComponent() {
      return this.components[this.currentComponent] || null;
    },
    currentTab() {
      return this.currentList.tabs.find((tab) => {
        return tab.key === this.selectedListTab;
      });
    },
    tabActionsPopover() {
      return typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.filter((action) => {
        return !["add", "edit"].includes(action.name) && !Object.prototype.hasOwnProperty.call(action, "icon") && action.display;
      }) : [];
    },
    editAction() {
      return typeof this.currentTab !== "undefined" && typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.find((action) => {
        return action.name === "edit" && (action.view === this.viewType || typeof action.view === "undefined");
      }) : false;
    },
    editWeekAction() {
      return typeof this.currentTab !== "undefined" && typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.find((action) => {
        return action.name === "edit" && action.view === "calendar" && action.calendarView === "week";
      }) : false;
    },
    addAction() {
      return typeof this.currentTab !== "undefined" && typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.find((action) => {
        return action.name === "add" && action.display;
      }) : false;
    },
    previewAction() {
      return typeof this.currentTab !== "undefined" && typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.find((action) => {
        return action.name === "preview";
      }) : false;
    },
    iconActions() {
      return typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.filter((action) => {
        return !["add", "edit", "preview"].includes(action.name) && Object.prototype.hasOwnProperty.call(action, "icon");
      }) : [];
    },
    displayedItems() {
      return typeof this.items[this.selectedListTab] !== "undefined" ? this.items[this.selectedListTab] : [];
    },
    additionalColumns() {
      let columns = [];
      let items = typeof this.items[this.selectedListTab] !== "undefined" ? this.items[this.selectedListTab] : [];
      if (items.length > 0 && items[0].additional_columns && items[0].additional_columns.length > 0) {
        items[0].additional_columns.forEach((column) => {
          if (column.display === "all" || column.display === this.viewType) {
            columns.push(column);
          }
        });
      }
      return columns;
    },
    viewTypeOptions() {
      if (typeof this.currentTab !== "undefined" && this.currentTab.viewsOptions) {
        return this.currentTab.viewsOptions;
      } else {
        return this.defaultViewsOptions;
      }
    }
  },
  watch: {
    "currentTab.pagination.current": function(newPage, oldPage) {
      if (newPage !== oldPage && typeof oldPage !== "undefined") {
        this.getListItems(newPage, this.selectedListTab);
      }
    },
    numberOfItemsToDisplay() {
      this.getListItems();
      localStorage.setItem(
        "tchooz_number_of_items_to_display/" + document.location.hostname,
        this.numberOfItemsToDisplay
      );
    },
    viewType(value, oldValue) {
      if (oldValue != null && oldValue !== value && (value === "calendar" || oldValue === "calendar")) {
        this.getListItems(1, this.selectedListTab);
      }
    }
  }
};
const _hoisted_1 = {
  key: 2,
  id: "tabs-loading"
};
const _hoisted_2 = { class: "tw-flex tw-justify-between" };
const _hoisted_3 = {
  key: 3,
  class: "list tw-mt-4"
};
const _hoisted_4 = { key: 2 };
const _hoisted_5 = {
  key: 0,
  id: "list-items"
};
const _hoisted_6 = {
  id: "check-th",
  class: "tw-p-4"
};
const _hoisted_7 = {
  key: 0,
  class: "material-symbols-outlined"
};
const _hoisted_8 = {
  key: 1,
  class: "material-symbols-outlined"
};
const _hoisted_9 = { class: "tw-cursor-pointer tw-font-medium" };
const _hoisted_10 = {
  key: 0,
  class: "material-symbols-outlined"
};
const _hoisted_11 = {
  key: 1,
  class: "material-symbols-outlined"
};
const _hoisted_12 = ["onClick"];
const _hoisted_13 = {
  key: 1,
  class: "tw-font-medium"
};
const _hoisted_14 = {
  key: 0,
  class: "tw-p-4"
};
const _hoisted_15 = ["id", "onClick"];
const _hoisted_16 = ["id"];
const _hoisted_17 = ["onClick", "title"];
const _hoisted_18 = ["innerHTML"];
const _hoisted_19 = { key: 1 };
const _hoisted_20 = ["onClick", "innerHTML"];
const _hoisted_21 = ["innerHTML"];
const _hoisted_22 = {
  key: 0,
  class: "tw-mb-3 tw-mt-1.5 tw-w-full"
};
const _hoisted_23 = ["onClick"];
const _hoisted_24 = { class: "tw-flex tw-items-center tw-justify-end tw-gap-2" };
const _hoisted_25 = ["onClick", "title"];
const _hoisted_26 = ["onClick"];
const _hoisted_27 = { key: 1 };
const _hoisted_28 = {
  style: { "list-style-type": "none", "margin": "0" },
  class: "em-flex-col-center tw-p-4"
};
const _hoisted_29 = ["onClick"];
const _hoisted_30 = { key: 1 };
const _hoisted_31 = { key: 2 };
const _hoisted_32 = { key: 2 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_skeleton = resolveComponent("skeleton");
  const _component_Head = resolveComponent("Head");
  const _component_Navigation = resolveComponent("Navigation");
  const _component_modal = resolveComponent("modal");
  const _component_popover = resolveComponent("popover");
  const _component_Calendar = resolveComponent("Calendar");
  const _component_ExportsSlotsModal = resolveComponent("ExportsSlotsModal");
  const _component_Gantt = resolveComponent("Gantt");
  const _component_NoResults = resolveComponent("NoResults");
  return openBlock(), createElementBlock("div", {
    id: "onboarding_list",
    class: normalizeClass(["tw-w-full", { "alert-banner-displayed": $data.alertBannerDisplayed }])
  }, [
    $data.loading.lists ? (openBlock(), createBlock(_component_skeleton, {
      key: 0,
      height: "40px",
      width: "100%",
      class: "tw-mb-4 tw-mt-4 tw-rounded-lg"
    })) : (openBlock(), createBlock(_component_Head, {
      key: 1,
      title: $data.currentList.title,
      introduction: $data.currentList.intro,
      "add-action": $options.addAction,
      onAction: $options.onClickAction
    }, null, 8, ["title", "introduction", "add-action", "onAction"])),
    $data.loading.tabs ? (openBlock(), createElementBlock("div", _hoisted_1, [
      createBaseVNode("div", _hoisted_2, [
        createVNode(_component_skeleton, {
          height: "40px",
          width: "20%",
          class: "tw-mb-4 tw-rounded-lg"
        }),
        createVNode(_component_skeleton, {
          height: "40px",
          width: "5%",
          class: "tw-mb-4 tw-rounded-lg"
        })
      ]),
      createBaseVNode("div", {
        class: normalizeClass(["tw-flex-wrap", {
          "skeleton-grid": $data.viewType === "blocs",
          "tw-flex tw-flex-col": $data.viewType === "table"
        }])
      }, [
        (openBlock(), createElementBlock(Fragment, null, renderList(9, (i) => {
          return createVNode(_component_skeleton, {
            key: i,
            class: "skeleton-item tw-rounded-lg"
          });
        }), 64))
      ], 2)
    ])) : (openBlock(), createElementBlock("div", _hoisted_3, [
      !$data.loading.filters ? (openBlock(), createBlock(_component_Navigation, {
        key: 0,
        tabs: $data.currentList.tabs,
        filters: $data.filters,
        items: $data.items,
        "checked-items": $data.checkedItems,
        views: $options.viewTypeOptions,
        view: $data.viewType,
        "onUpdate:view": _cache[0] || (_cache[0] = ($event) => $data.viewType = $event),
        searches: $data.searches,
        "onUpdate:searches": _cache[1] || (_cache[1] = ($event) => $data.searches = $event),
        tab: $options.currentTab,
        "onUpdate:tab": _cache[2] || (_cache[2] = ($event) => $options.currentTab = $event),
        "tab-key": $data.selectedListTab,
        "onUpdate:tabKey": _cache[3] || (_cache[3] = ($event) => $data.selectedListTab = $event),
        "number-of-items-to-display": $data.numberOfItemsToDisplay,
        "onUpdate:numberOfItemsToDisplay": _cache[4] || (_cache[4] = ($event) => $data.numberOfItemsToDisplay = $event),
        onSelectTab: $options.onCheckAllitems,
        onAction: $options.onClickAction,
        onExp: $options.onClickExport,
        onUpdateItems: $options.getListItems
      }, null, 8, ["tabs", "filters", "items", "checked-items", "views", "view", "searches", "tab", "tab-key", "number-of-items-to-display", "onSelectTab", "onAction", "onExp", "onUpdateItems"])) : createCommentVNode("", true),
      $data.loading.items ? (openBlock(), createElementBlock("div", {
        key: 1,
        id: "items-loading",
        class: normalizeClass({
          "skeleton-grid": $data.viewType === "blocs",
          "tw-mb-4 tw-flex tw-flex-col": $data.viewType === "table"
        }),
        style: { "flex-wrap": "wrap" }
      }, [
        (openBlock(), createElementBlock(Fragment, null, renderList(9, (i) => {
          return createVNode(_component_skeleton, {
            key: i,
            class: "skeleton-item tw-rounded-lg"
          });
        }), 64))
      ], 2)) : (openBlock(), createElementBlock("div", _hoisted_4, [
        $options.displayedItems.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_5, [
          $data.viewType !== "calendar" && $data.viewType !== "gantt" ? (openBlock(), createElementBlock("table", {
            key: 0,
            id: "list-table",
            class: normalizeClass(["tw-border-separate", { blocs: $data.viewType === "blocs" }])
          }, [
            createBaseVNode("thead", null, [
              createBaseVNode("tr", null, [
                createBaseVNode("th", _hoisted_6, [
                  createBaseVNode("input", {
                    class: "items-check-all",
                    type: "checkbox",
                    onChange: _cache[5] || (_cache[5] = (...args) => $options.onCheckAllitems && $options.onCheckAllitems(...args))
                  }, null, 32)
                ]),
                createBaseVNode("th", {
                  class: "tw-cursor-pointer tw-p-4",
                  onClick: _cache[6] || (_cache[6] = ($event) => $options.orderByColumn("label"))
                }, [
                  createBaseVNode("div", {
                    class: normalizeClass({ "tw-flex tw-flex-row": "label" === $data.orderBy })
                  }, [
                    "label" === $data.orderBy && $data.order === "ASC" ? (openBlock(), createElementBlock("span", _hoisted_7, "arrow_upward")) : "label" === $data.orderBy && $data.order === "DESC" ? (openBlock(), createElementBlock("span", _hoisted_8, "arrow_downward")) : createCommentVNode("", true),
                    createBaseVNode("label", _hoisted_9, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_LABEL_" + $options.currentTab.key.toUpperCase()) == "COM_EMUNDUS_ONBOARD_LABEL_" + $options.currentTab.key.toUpperCase() ? _ctx.translate("COM_EMUNDUS_ONBOARD_LABEL") : _ctx.translate("COM_EMUNDUS_ONBOARD_LABEL_" + $options.currentTab.key.toUpperCase())), 1)
                  ], 2)
                ]),
                (openBlock(true), createElementBlock(Fragment, null, renderList($options.additionalColumns, (column) => {
                  return openBlock(), createElementBlock("th", {
                    key: column.key,
                    class: "tw-p-4"
                  }, [
                    column.order_by ? (openBlock(), createElementBlock("div", {
                      key: 0,
                      class: normalizeClass({
                        "tw-flex tw-flex-row": column.order_by === $data.orderBy
                      })
                    }, [
                      column.order_by === $data.orderBy && $data.order === "ASC" ? (openBlock(), createElementBlock("span", _hoisted_10, "arrow_upward")) : column.order_by === $data.orderBy && $data.order === "DESC" ? (openBlock(), createElementBlock("span", _hoisted_11, "arrow_downward")) : createCommentVNode("", true),
                      createBaseVNode("label", {
                        class: "tw-cursor-pointer tw-font-medium",
                        onClick: ($event) => $options.orderByColumn(column.order_by)
                      }, toDisplayString(column.key), 9, _hoisted_12)
                    ], 2)) : (openBlock(), createElementBlock("label", _hoisted_13, toDisplayString(column.key), 1))
                  ]);
                }), 128)),
                $options.tabActionsPopover && $options.tabActionsPopover.length > 0 ? (openBlock(), createElementBlock("th", _hoisted_14)) : createCommentVNode("", true)
              ])
            ]),
            createBaseVNode("tbody", null, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedItems, (item) => {
                return openBlock(), createElementBlock("tr", {
                  key: item.id,
                  id: "item-" + $options.currentTab.key + "-" + item.id,
                  class: normalizeClass(["tw-group/item-row table-row tw-cursor-pointer tw-rounded-coordinator tw-border", {
                    "tw-flex tw-min-h-[200px] tw-flex-col tw-justify-between tw-rounded-coordinator-cards tw-p-8 tw-shadow-card": $data.viewType === "blocs",
                    "tw-shadow-table-border-profile": $data.checkedItems.includes(item.id) && $data.viewType === "table",
                    "tw-shadow-table-border-neutral": !$data.checkedItems.includes(item.id) && $data.viewType === "table",
                    "tw-border-profile-full tw-bg-main-50": $data.checkedItems.includes(item.id) && $data.viewType === "blocs",
                    "tw-bg-white hover:tw-bg-neutral-100": !$data.checkedItems.includes(item.id) && $data.viewType === "blocs"
                  }]),
                  onClick: ($event) => $options.onCheckItem(item.id, $event)
                }, [
                  createBaseVNode("td", {
                    class: normalizeClass(["tw-rounded-s-coordinator tw-p-4", {
                      "tw-bg-main-50": $data.checkedItems.includes(item.id) && $data.viewType === "table",
                      "tw-bg-white group-hover/item-row:tw-bg-neutral-100": !$data.checkedItems.includes(item.id) && $data.viewType === "table"
                    }])
                  }, [
                    withDirectives(createBaseVNode("input", {
                      id: "item-" + $options.currentTab.key + "-" + item.id,
                      class: "item-check",
                      type: "checkbox"
                    }, null, 8, _hoisted_16), [
                      [vShow, $data.viewType === "table"]
                    ])
                  ], 2),
                  createBaseVNode("td", {
                    class: normalizeClass(["tw-cursor-pointer tw-p-4", {
                      "tw-bg-main-50": $data.checkedItems.includes(item.id) && $data.viewType === "table",
                      "tw-bg-white group-hover/item-row:tw-bg-neutral-100": !$data.checkedItems.includes(item.id) && $data.viewType === "table"
                    }])
                  }, [
                    createBaseVNode("span", {
                      onClick: ($event) => $options.onClickAction($options.editAction, item.id, false, $event),
                      class: normalizeClass({
                        "tw-line-clamp-2 tw-min-h-[48px] tw-font-semibold": $data.viewType === "blocs",
                        "hover:tw-underline": $options.editAction
                      }),
                      title: item.label[$data.params.shortlang]
                    }, toDisplayString(item.label[$data.params.shortlang]), 11, _hoisted_17)
                  ], 2),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedColumns(item, $data.viewType), (column) => {
                    return openBlock(), createElementBlock("td", {
                      class: normalizeClass(["columns tw-p-4", {
                        "tw-bg-main-50": $data.checkedItems.includes(item.id) && $data.viewType === "table",
                        "tw-bg-white group-hover/item-row:tw-bg-neutral-100": !$data.checkedItems.includes(item.id) && $data.viewType === "table"
                      }]),
                      key: column.key
                    }, [
                      column.type === "tags" ? (openBlock(), createElementBlock("div", {
                        key: 0,
                        class: normalizeClass(["tw-flex tw-flex-wrap tw-items-center tw-gap-2", column.classes])
                      }, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList(column.values, (tag) => {
                          return openBlock(), createElementBlock("span", {
                            key: tag.key,
                            class: normalizeClass(["tw-mr-2 tw-h-max", tag.classes]),
                            innerHTML: tag.value
                          }, null, 10, _hoisted_18);
                        }), 128))
                      ], 2)) : column.hasOwnProperty("long_value") ? (openBlock(), createElementBlock("div", _hoisted_19, [
                        createBaseVNode("span", {
                          onClick: ($event) => $options.displayLongValue($event, column.long_value),
                          class: normalizeClass(["tw-mb-2 tw-mt-2", column.classes]),
                          innerHTML: column.value
                        }, null, 10, _hoisted_20)
                      ])) : (openBlock(), createElementBlock("span", {
                        key: 2,
                        class: normalizeClass(["tw-mb-2 tw-mt-2", column.classes]),
                        innerHTML: column.value
                      }, null, 10, _hoisted_21))
                    ], 2);
                  }), 128)),
                  createBaseVNode("td", {
                    class: normalizeClass(["actions tw-rounded-e-coordinator tw-p-4", {
                      "tw-bg-main-50": $data.checkedItems.includes(item.id) && $data.viewType === "table",
                      "tw-bg-white group-hover/item-row:tw-bg-neutral-100": !$data.checkedItems.includes(item.id) && $data.viewType === "table"
                    }])
                  }, [
                    $data.viewType === "blocs" ? (openBlock(), createElementBlock("hr", _hoisted_22)) : createCommentVNode("", true),
                    createBaseVNode("div", {
                      class: normalizeClass({
                        "tw-flex tw-w-full tw-justify-between": $data.viewType === "blocs"
                      })
                    }, [
                      $data.viewType === "blocs" && $options.editAction ? (openBlock(), createElementBlock("a", {
                        key: 0,
                        onClick: ($event) => $options.onClickAction($options.editAction, item.id, false, $event),
                        class: "tw-btn-primary tw-w-auto tw-cursor-pointer tw-rounded-coordinator tw-text-sm"
                      }, toDisplayString(_ctx.translate($options.editAction.label)), 9, _hoisted_23)) : createCommentVNode("", true),
                      createBaseVNode("div", _hoisted_24, [
                        $options.editAction && $data.viewType === "table" ? (openBlock(), createElementBlock("button", {
                          key: 0,
                          onClick: ($event) => $options.onClickAction($options.editAction, item.id),
                          class: "tw-btn-primary tw-flex !tw-w-auto tw-items-center tw-gap-1",
                          style: { "padding": "0.5rem" },
                          title: _ctx.translate($options.editAction.label)
                        }, _cache[15] || (_cache[15] = [
                          createBaseVNode("span", { class: "material-symbols-outlined popover-toggle-btn tw-cursor-pointer" }, "edit", -1)
                        ]), 8, _hoisted_25)) : createCommentVNode("", true),
                        (openBlock(true), createElementBlock(Fragment, null, renderList($options.iconActions, (action) => {
                          return withDirectives((openBlock(), createElementBlock("button", {
                            key: action.name,
                            class: normalizeClass(["tw-btn-primary tw-flex !tw-w-auto tw-items-center tw-gap-1", [
                              action.buttonClasses,
                              {
                                "tw-hidden": !(typeof action.showon === "undefined" || $options.evaluateShowOn(item, action.showon))
                              }
                            ]]),
                            onClick: ($event) => $options.onClickAction(action, item.id, false, $event)
                          }, [
                            createBaseVNode("span", {
                              class: normalizeClass(["popover-toggle-btn tw-cursor-pointer", [
                                action.spanClasses,
                                {
                                  "material-symbols-outlined": action.iconOutlined,
                                  "material-icons": !action.iconOutlined
                                }
                              ]])
                            }, toDisplayString(action.icon), 3)
                          ], 10, _hoisted_26)), [
                            [vShow, action.display]
                          ]);
                        }), 128)),
                        $data.showModal && $data.currentComponentElementId === item.id ? (openBlock(), createElementBlock("div", _hoisted_27, [
                          (openBlock(), createBlock(Teleport, { to: ".com_emundus_vue" }, [
                            createVNode(_component_modal, {
                              name: "modal-component",
                              transition: "nice-modal-fade",
                              class: normalizeClass("placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-rounded tw-px-4 tw-shadow-modal"),
                              width: "600px",
                              delay: 100,
                              adaptive: true,
                              clickToClose: false,
                              onClick: _cache[9] || (_cache[9] = withModifiers(() => {
                              }, ["stop"]))
                            }, {
                              default: withCtx(() => [
                                (openBlock(), createBlock(resolveDynamicComponent($options.resolvedComponent), {
                                  slot: item,
                                  onClose: _cache[7] || (_cache[7] = ($event) => $options.closePopup()),
                                  onUpdateItems: _cache[8] || (_cache[8] = ($event) => $options.getListItems())
                                }, null, 40, ["slot"]))
                              ]),
                              _: 2
                            }, 1024)
                          ]))
                        ])) : createCommentVNode("", true),
                        $options.tabActionsPopover && $options.tabActionsPopover.length > 0 && $options.filterShowOnActions($options.tabActionsPopover, item).length ? (openBlock(), createBlock(_component_popover, {
                          key: 2,
                          position: "left",
                          button: _ctx.translate("COM_EMUNDUS_ONBOARD_ACTIONS"),
                          "hide-button-label": true,
                          class: "custom-popover-arrow"
                        }, {
                          default: withCtx(() => [
                            createBaseVNode("ul", _hoisted_28, [
                              (openBlock(true), createElementBlock(Fragment, null, renderList($options.tabActionsPopover, (action) => {
                                return openBlock(), createElementBlock("li", {
                                  key: action.name,
                                  class: normalizeClass([{
                                    "tw-hidden": !(typeof action.showon === "undefined" || $options.evaluateShowOn(item, action.showon))
                                  }, "tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"]),
                                  onClick: ($event) => $options.onClickAction(action, item.id, false, $event)
                                }, toDisplayString(_ctx.translate(action.label)), 11, _hoisted_29);
                              }), 128))
                            ])
                          ]),
                          _: 2
                        }, 1032, ["button"])) : createCommentVNode("", true)
                      ])
                    ], 2)
                  ], 2)
                ], 10, _hoisted_15);
              }), 128))
            ])
          ], 2)) : $data.viewType === "calendar" ? (openBlock(), createElementBlock("div", _hoisted_30, [
            createVNode(_component_Calendar, {
              items: $data.items,
              "edit-week-action": $options.editWeekAction,
              onOnClickAction: $options.onClickAction,
              onUpdateItems: _cache[10] || (_cache[10] = ($event) => $options.getListItems())
            }, null, 8, ["items", "edit-week-action", "onOnClickAction"])
          ])) : createCommentVNode("", true),
          $data.showExportModal ? (openBlock(), createElementBlock("div", _hoisted_31, [
            createVNode(_component_modal, {
              name: "modal-component-export",
              transition: "nice-modal-fade",
              class: normalizeClass("export-modal placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-rounded tw-px-4 tw-shadow-modal"),
              width: "600px",
              delay: 100,
              adaptive: true,
              clickToClose: false,
              onClick: _cache[12] || (_cache[12] = withModifiers(() => {
              }, ["stop"]))
            }, {
              default: withCtx(() => [
                createVNode(_component_ExportsSlotsModal, {
                  onSelectionConfirm: $options.onClickExportWithCheckboxes,
                  onClose: _cache[11] || (_cache[11] = ($event) => $options.closePopup())
                }, null, 8, ["onSelectionConfirm"])
              ]),
              _: 1
            })
          ])) : $data.viewType === "gantt" ? (openBlock(), createBlock(_component_Gantt, {
            key: 3,
            language: $data.params.shortlang,
            periods: $options.displayedItems
          }, null, 8, ["language", "periods"])) : createCommentVNode("", true)
        ])) : (openBlock(), createBlock(_component_NoResults, {
          key: 1,
          message: $options.currentTab.noData
        }, null, 8, ["message"])),
        $data.showModal && $data.currentComponentElementId === null ? (openBlock(), createElementBlock("div", _hoisted_32, [
          createVNode(_component_modal, {
            name: "modal-component",
            transition: "nice-modal-fade",
            class: normalizeClass("placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-rounded tw-px-4 tw-shadow-modal"),
            width: "600px",
            delay: 100,
            adaptive: true,
            clickToClose: false,
            onClick: _cache[14] || (_cache[14] = withModifiers(() => {
            }, ["stop"]))
          }, {
            default: withCtx(() => [
              (openBlock(), createBlock(resolveDynamicComponent($options.resolvedComponent), {
                items: $data.checkedItems,
                onClose: _cache[13] || (_cache[13] = ($event) => $options.closePopup()),
                onUpdateItems: $options.getListItems
              }, null, 40, ["items", "onUpdateItems"]))
            ]),
            _: 1
          })
        ])) : createCommentVNode("", true)
      ]))
    ]))
  ], 2);
}
const list = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-a0c2a100"]]);
export {
  list as default
};
