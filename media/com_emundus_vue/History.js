import { _ as _export_sfc, o as openBlock, c as createElementBlock, a as createBaseVNode, d as withDirectives, x as vModelSelect, F as Fragment, b as renderList, t as toDisplayString, n as normalizeClass, m as normalizeStyle, G as mixin, z as useGlobalStore, q as settingsService, r as resolveComponent, h as createCommentVNode, k as createBlock, y as createTextVNode } from "./app_emundus.js";
const Pagination_vue_vue_type_style_index_0_scoped_ba33d3d6_lang = "";
const _sfc_main$1 = {
  name: "Pagination",
  props: {
    limits: {
      type: Array,
      default: () => [5, 10, 25, 50, 100]
    },
    dataLength: {
      type: Number,
      default: 0
    },
    page: {
      type: Number,
      default: 1
    },
    limit: {
      type: Number,
      default: 5
    },
    sticky: {
      type: Boolean,
      default: false
    }
  },
  emits: ["update:page", "update:limit"],
  data: () => ({
    currentPage: 1,
    currentLimit: 5
  }),
  created() {
    this.currentPage = this.page;
    this.currentLimit = this.limit;
  },
  watch: {
    currentPage() {
      this.$emit("update:page", this.currentPage);
    },
    currentLimit() {
      this.$emit("update:limit", this.currentLimit);
    }
  },
  computed: {
    stickyClass() {
      return this.sticky ? "tw-sticky tw-border-b tw-border-neutral-400 tw-top-0" : "";
    },
    stickyStyle() {
      let banner = document.querySelector(".alerte-message-container");
      if (banner) {
        let top = banner.offsetHeight;
        return { top: `${top}px` };
      }
    }
  }
};
const _hoisted_1$1 = { class: "tw-ml-2" };
const _hoisted_2$1 = { class: "em-ml-16 em-flex-row" };
const _hoisted_3$1 = ["value"];
const _hoisted_4$1 = { class: "em-container-pagination-selectPage" };
const _hoisted_5$1 = { class: "tw-flex tw-items-center tw-gap-1 pagination pagination-sm" };
const _hoisted_6$1 = { class: "tw-flex" };
const _hoisted_7$1 = ["onClick"];
const _hoisted_8$1 = { class: "tw-flex" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(["tw-flex tw-items-center tw-justify-between tw-py-2 tw-px-3 tw-bg-white", $options.stickyClass]),
    style: normalizeStyle($options.stickyStyle)
  }, [
    createBaseVNode("div", _hoisted_1$1, [
      createBaseVNode("div", _hoisted_2$1, [
        _cache[3] || (_cache[3] = createBaseVNode("label", {
          for: "pager-select",
          class: "em-mb-0-important em-mr-4"
        }, "Afficher", -1)),
        withDirectives(createBaseVNode("select", {
          name: "pager-select",
          id: "pager-select",
          class: "em-select-no-border",
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => _ctx.currentLimit = $event)
        }, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($props.limits, (availableLimit) => {
            return openBlock(), createElementBlock("option", { value: availableLimit }, toDisplayString(availableLimit), 9, _hoisted_3$1);
          }), 256))
        ], 512), [
          [vModelSelect, _ctx.currentLimit]
        ])
      ])
    ]),
    createBaseVNode("div", _hoisted_4$1, [
      createBaseVNode("ul", _hoisted_5$1, [
        createBaseVNode("li", _hoisted_6$1, [
          createBaseVNode("a", {
            class: normalizeClass(["tw-cursor-pointer", { "disabled": this.currentPage === 1 }]),
            onClick: _cache[1] || (_cache[1] = ($event) => this.currentPage !== 1 ? this.currentPage -= 1 : null)
          }, _cache[4] || (_cache[4] = [
            createBaseVNode("span", { class: "material-symbols-outlined" }, "navigate_before", -1)
          ]), 2)
        ]),
        (openBlock(true), createElementBlock(Fragment, null, renderList(Math.ceil($props.dataLength / $props.limit), (pageAvailable) => {
          return openBlock(), createElementBlock("li", {
            key: pageAvailable,
            class: normalizeClass([{ "active": pageAvailable === this.currentPage }, "tw-cursor-pointer tw-flex"]),
            onClick: ($event) => this.currentPage = pageAvailable
          }, [
            createBaseVNode("a", null, toDisplayString(pageAvailable), 1)
          ], 10, _hoisted_7$1);
        }), 128)),
        createBaseVNode("li", _hoisted_8$1, [
          createBaseVNode("a", {
            class: normalizeClass(["tw-cursor-pointer", { "disabled": this.currentPage === Math.ceil($props.dataLength / $props.limit) }]),
            onClick: _cache[2] || (_cache[2] = ($event) => this.currentPage !== Math.ceil($props.dataLength / $props.limit) ? this.currentPage += 1 : null)
          }, _cache[5] || (_cache[5] = [
            createBaseVNode("span", { class: "material-symbols-outlined" }, "navigate_next", -1)
          ]), 2)
        ])
      ])
    ])
  ], 6);
}
const Pagination = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-ba33d3d6"]]);
const History_vue_vue_type_style_index_0_scoped_94ef35dd_lang = "";
const _sfc_main = {
  name: "History",
  components: { Pagination },
  props: {
    extension: {
      type: String,
      required: true
    },
    itemId: {
      type: Number,
      default: 0
    },
    columns: {
      type: Array,
      default: () => [
        // Modification(s)
        "title",
        // Type
        "message_language_key",
        // Date
        "log_date",
        // By
        "user_id",
        // Status
        //'status',
        "diff"
      ]
    },
    displayTitle: {
      type: Boolean,
      default: false
    }
  },
  mixins: [mixin],
  data() {
    return {
      loading: true,
      colorClasses: {
        done: "tw-text-main-500",
        pending: "tw-text-orange-500",
        cancelled: "tw-text-red-500"
      },
      icon: {
        done: "check_circle",
        pending: "rule_settings",
        cancelled: "cancel"
      },
      text: {
        done: this.translate("COM_EMUNDUS_GLOBAL_HISTORY_STATUS_DONE"),
        pending: this.translate("COM_EMUNDUS_GLOBAL_HISTORY_STATUS_PENDING"),
        cancelled: this.translate("COM_EMUNDUS_GLOBAL_HISTORY_STATUS_CANCELLED")
      },
      history: [],
      historyLength: 0,
      page: 1,
      limit: 10
    };
  },
  setup() {
    return {
      globalStore: useGlobalStore()
    };
  },
  created() {
    this.fetchHistory();
  },
  methods: {
    fetchHistory() {
      this.loading = true;
      settingsService.getHistory(this.extension, false, this.page, this.limit, this.itemId).then((response) => {
        this.historyLength = parseInt(response.length);
        response.data.forEach((data) => {
          data.message = JSON.parse(data.message);
          if (data.message.old_data) {
            data.message.old_data = JSON.parse(data.message.old_data);
          }
          if (data.message.new_data) {
            data.message.new_data = JSON.parse(data.message.new_data);
            data.message.new_data_json = JSON.parse(JSON.stringify(data.message.new_data));
          }
          if (data.message.new_data) {
            data.message.new_data = Object.values(data.message.new_data);
          }
        });
        this.history = response.data;
        this.loading = false;
      });
    },
    updateHistoryStatus(id, status) {
      if (this.sysadmin) {
        Swal.fire({
          title: this.translate("COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_TITLE"),
          text: this.translate("COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_TEXT"),
          showCancelButton: true,
          reverseButtons: true,
          confirmButtonText: this.translate("COM_EMUNDUS_GLOBAL_HISTORY_STATUS_UPDATE_YES"),
          cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
          customClass: {
            title: "em-swal-title",
            cancelButton: "em-swal-cancel-button",
            confirmButton: "em-swal-confirm-button"
          }
        }).then((result) => {
          if (result.isConfirmed) {
            settingsService.updateHistoryStatus(id, status).then(() => {
              this.fetchHistory();
            });
          }
        });
      }
    }
  },
  computed: {
    sysadmin: function() {
      return parseInt(this.globalStore.hasSysadminAccess);
    }
  },
  watch: {
    page: function() {
      this.fetchHistory();
    },
    limit: function() {
      this.fetchHistory();
    }
  }
};
const _hoisted_1 = { class: "tw-relative" };
const _hoisted_2 = {
  key: 0,
  class: "tw-mb-6"
};
const _hoisted_3 = { key: 0 };
const _hoisted_4 = { key: 0 };
const _hoisted_5 = { key: 1 };
const _hoisted_6 = { key: 2 };
const _hoisted_7 = { key: 3 };
const _hoisted_8 = { key: 4 };
const _hoisted_9 = { key: 5 };
const _hoisted_10 = { key: 0 };
const _hoisted_11 = { key: 0 };
const _hoisted_12 = { key: 0 };
const _hoisted_13 = { class: "tw-text-green-700" };
const _hoisted_14 = { key: 1 };
const _hoisted_15 = { key: 2 };
const _hoisted_16 = { key: 3 };
const _hoisted_17 = { key: 4 };
const _hoisted_18 = { class: "tw-flex tw-items-center" };
const _hoisted_19 = { key: 0 };
const _hoisted_20 = ["onClick"];
const _hoisted_21 = ["onClick"];
const _hoisted_22 = {
  key: 0,
  class: "!tw-border !tw-border-slate-100 !tw-border-solid tw-rounded tw-text-sm"
};
const _hoisted_23 = { key: 1 };
const _hoisted_24 = {
  key: 3,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Pagination = resolveComponent("Pagination");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    $props.displayTitle ? (openBlock(), createElementBlock("h2", _hoisted_2, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY")), 1)) : createCommentVNode("", true),
    $data.history.length > 0 ? (openBlock(), createBlock(_component_Pagination, {
      key: 1,
      dataLength: $data.historyLength,
      sticky: true,
      page: $data.page,
      "onUpdate:page": _cache[0] || (_cache[0] = ($event) => $data.page = $event),
      limit: $data.limit,
      "onUpdate:limit": _cache[1] || (_cache[1] = ($event) => $data.limit = $event)
    }, null, 8, ["dataLength", "page", "limit"])) : createCommentVNode("", true),
    !$data.loading ? (openBlock(), createElementBlock(Fragment, { key: 2 }, [
      $data.history.length > 0 ? (openBlock(), createElementBlock("table", _hoisted_3, [
        createBaseVNode("thead", null, [
          createBaseVNode("tr", null, [
            $props.columns.includes("title") ? (openBlock(), createElementBlock("th", _hoisted_4, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_UPDATES")), 1)) : createCommentVNode("", true),
            $props.columns.includes("message_language_key") ? (openBlock(), createElementBlock("th", _hoisted_5, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_TYPE")), 1)) : createCommentVNode("", true),
            $props.columns.includes("log_date") ? (openBlock(), createElementBlock("th", _hoisted_6, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_LOG_DATE")), 1)) : createCommentVNode("", true),
            $props.columns.includes("user_id") ? (openBlock(), createElementBlock("th", _hoisted_7, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_BY")), 1)) : createCommentVNode("", true),
            $props.columns.includes("status") ? (openBlock(), createElementBlock("th", _hoisted_8, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_STATUS")), 1)) : createCommentVNode("", true),
            $props.columns.includes("diff") ? (openBlock(), createElementBlock("th", _hoisted_9, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_DIFF")), 1)) : createCommentVNode("", true)
          ])
        ]),
        createBaseVNode("tbody", null, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.history, (data) => {
            return openBlock(), createElementBlock("tr", {
              key: data.id
            }, [
              $props.columns.includes("title") ? (openBlock(), createElementBlock("td", _hoisted_10, [
                createBaseVNode("p", null, toDisplayString(_ctx.translate(data.message.title)), 1),
                data.message.new_data.length > 0 && $props.extension == "com_emundus.settings.web_security" ? (openBlock(), createElementBlock("p", _hoisted_11, [
                  (openBlock(true), createElementBlock(Fragment, null, renderList(data.message.new_data, (newData, index) => {
                    return openBlock(), createElementBlock("span", { key: index }, [
                      index > 0 ? (openBlock(), createElementBlock("span", _hoisted_12, ", ")) : createCommentVNode("", true),
                      createBaseVNode("span", _hoisted_13, toDisplayString(newData), 1)
                    ]);
                  }), 128))
                ])) : createCommentVNode("", true)
              ])) : createCommentVNode("", true),
              $props.columns.includes("message_language_key") ? (openBlock(), createElementBlock("td", _hoisted_14, toDisplayString(_ctx.translate(data.message_language_key + "_TITLE")), 1)) : createCommentVNode("", true),
              $props.columns.includes("log_date") ? (openBlock(), createElementBlock("td", _hoisted_15, toDisplayString(_ctx.formattedDate(data.log_date, "L") + " " + _ctx.formattedDate(data.log_date, "LT")), 1)) : createCommentVNode("", true),
              $props.columns.includes("user_id") ? (openBlock(), createElementBlock("td", _hoisted_16, toDisplayString(data.logged_by), 1)) : createCommentVNode("", true),
              $props.columns.includes("status") ? (openBlock(), createElementBlock("td", _hoisted_17, [
                createBaseVNode("div", _hoisted_18, [
                  createBaseVNode("span", {
                    class: normalizeClass(["material-symbols-outlined tw-mr-2", $data.colorClasses[data.message.status]])
                  }, toDisplayString($data.icon[data.message.status]), 3),
                  createBaseVNode("p", {
                    class: normalizeClass($data.colorClasses[data.message.status])
                  }, [
                    createTextVNode(toDisplayString(_ctx.translate($data.text[data.message.status])) + " ", 1),
                    (data.message.status == "done" || data.message.status == "cancelled") && data.message.status_updated ? (openBlock(), createElementBlock("span", _hoisted_19, toDisplayString(_ctx.formattedDate(data.message.status_updated, "L")), 1)) : createCommentVNode("", true)
                  ], 2),
                  this.sysadmin && data.message.status === "pending" ? (openBlock(), createElementBlock("span", {
                    key: 0,
                    onClick: ($event) => $options.updateHistoryStatus(data.id, "done"),
                    class: "material-symbols-outlined tw-cursor-pointer"
                  }, " edit ", 8, _hoisted_20)) : createCommentVNode("", true),
                  this.sysadmin && data.message.status === "pending" ? (openBlock(), createElementBlock("span", {
                    key: 1,
                    onClick: ($event) => $options.updateHistoryStatus(data.id, "cancelled"),
                    class: "material-symbols-outlined tw-cursor-pointer"
                  }, " backspace ", 8, _hoisted_21)) : createCommentVNode("", true)
                ])
              ])) : createCommentVNode("", true),
              createBaseVNode("td", null, [
                $props.columns.includes("diff") && (!Array.isArray(data.message.old_data) || data.message.old_data.length > 0) && (!Array.isArray(data.message.new_data) || data.message.new_data.length > 0) ? (openBlock(), createElementBlock("table", _hoisted_22, [
                  createBaseVNode("thead", null, [
                    createBaseVNode("tr", null, [
                      createBaseVNode("th", null, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_DIFF_COLUMN")), 1),
                      createBaseVNode("th", null, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_DIFF_OLD_DATA")), 1),
                      createBaseVNode("th", null, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_DIFF_NEW_DATA")), 1)
                    ])
                  ]),
                  createBaseVNode("tbody", null, [
                    (openBlock(true), createElementBlock(Fragment, null, renderList(data.message.old_data, (value, key) => {
                      return openBlock(), createElementBlock("tr", { key }, [
                        createBaseVNode("td", null, toDisplayString(key), 1),
                        createBaseVNode("td", null, toDisplayString(value), 1),
                        createBaseVNode("td", null, toDisplayString(data.message.new_data_json[key]), 1)
                      ]);
                    }), 128))
                  ])
                ])) : createCommentVNode("", true)
              ])
            ]);
          }), 128))
        ])
      ])) : (openBlock(), createElementBlock("div", _hoisted_23, [
        createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_HISTORY_NO_HISTORY")), 1)
      ]))
    ], 64)) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_24)) : createCommentVNode("", true)
  ]);
}
const History = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-94ef35dd"]]);
export {
  History as default
};
