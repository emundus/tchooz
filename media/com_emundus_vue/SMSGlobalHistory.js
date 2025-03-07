import { _ as _export_sfc, x as Pagination, y as smsService, r as resolveComponent, o as openBlock, c as createElementBlock, d as createBaseVNode, t as toDisplayString, w as withDirectives, z as vModelSelect, F as Fragment, e as renderList, A as vModelText, h as createVNode, m as createTextVNode, b as createCommentVNode, B as _imports_0 } from "./app_emundus.js";
const _sfc_main = {
  name: "SMSGlobalHistory",
  components: {
    Pagination
  },
  data() {
    return {
      page: 1,
      limit: 10,
      total: 0,
      smsHistory: [],
      search: "",
      status: [
        {
          "value": "sent",
          "label": this.translate("COM_EMUNDUS_SMS_SENT")
        },
        {
          "value": "pending",
          "label": this.translate("COM_EMUNDUS_SMS_PENDING")
        },
        {
          "value": "failed",
          "label": this.translate("COM_EMUNDUS_SMS_FAILED")
        }
      ],
      selectedStatus: ""
    };
  },
  created() {
    this.getGlobalHistory();
  },
  methods: {
    getGlobalHistory() {
      let pageoffset = this.page - 1;
      smsService.getGlobalHistory(pageoffset, this.limit, this.search, this.selectedStatus).then((response) => {
        this.total = response.data.count;
        this.smsHistory = response.data.datas.map((message) => {
          return {
            id: message.id,
            fnum: message.fnum,
            message: message.message,
            user_id_from: message.user_id_from,
            user_name_from: message.lastname + " " + message.firstname,
            params: message.params,
            status: message.status
          };
        });
      });
    },
    onUpdateLimit(limit) {
      this.limit = limit;
      this.page = 1;
      this.getGlobalHistory();
    },
    onUpdatePage(page) {
      this.page = page;
      this.getGlobalHistory();
    },
    onSearch() {
      this.page = 1;
      this.getGlobalHistory();
    },
    onSelectStatus() {
      this.page = 1;
      this.getGlobalHistory();
    },
    replaceWithBr(text) {
      return text.replace(/\n/g, "<br>").replace(/\r/g, "<br>");
    }
  }
};
const _hoisted_1 = { id: "sms-history" };
const _hoisted_2 = { class: "tw-mb-4" };
const _hoisted_3 = { class: "tw-flex tw-flex-row tw-justify-between" };
const _hoisted_4 = {
  value: "",
  selected: ""
};
const _hoisted_5 = ["value"];
const _hoisted_6 = { class: "tw-flex tw-items-center tw-min-w-[15rem]" };
const _hoisted_7 = ["placeholder"];
const _hoisted_8 = { key: 0 };
const _hoisted_9 = { class: "from tw-mb-2 tw-flex tw-justify-between" };
const _hoisted_10 = { class: "tw-flex tw-flex-col" };
const _hoisted_11 = { class: "tw-text-neutral-500 tw-text-xs" };
const _hoisted_12 = { class: "tw-text-xs" };
const _hoisted_13 = ["title"];
const _hoisted_14 = ["title"];
const _hoisted_15 = ["title"];
const _hoisted_16 = ["innerHTML"];
const _hoisted_17 = {
  key: 1,
  id: "empty-list",
  class: "tw-text-center"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Pagination = resolveComponent("Pagination");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("h1", _hoisted_2, toDisplayString(_ctx.translate("COM_EMUNDUS_SMS_HISTORY")), 1),
    createBaseVNode("div", _hoisted_3, [
      createBaseVNode("div", null, [
        withDirectives(createBaseVNode("select", {
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.selectedStatus = $event),
          onChange: _cache[1] || (_cache[1] = (...args) => $options.onSelectStatus && $options.onSelectStatus(...args))
        }, [
          createBaseVNode("option", _hoisted_4, toDisplayString(_ctx.translate("COM_EMUNDUS_SMS_ALL_STATUS")), 1),
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.status, (status) => {
            return openBlock(), createElementBlock("option", {
              key: status.value,
              value: status.value
            }, toDisplayString(status.label), 9, _hoisted_5);
          }), 128))
        ], 544), [
          [vModelSelect, $data.selectedStatus]
        ])
      ]),
      createBaseVNode("div", _hoisted_6, [
        withDirectives(createBaseVNode("input", {
          type: "text",
          class: "!tw-rounded-coordinator !tw-h-[38px] tw-m-0",
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.search = $event),
          placeholder: _ctx.translate("COM_EMUNDUS_ACTIONS_SEARCH"),
          onKeyup: _cache[3] || (_cache[3] = (...args) => $options.onSearch && $options.onSearch(...args))
        }, null, 40, _hoisted_7), [
          [vModelText, $data.search]
        ]),
        _cache[4] || (_cache[4] = createBaseVNode("span", { class: "material-symbols-outlined tw-mr-2 tw-cursor-pointer tw-ml-[-32px]" }, " search ", -1))
      ])
    ]),
    createVNode(_component_Pagination, {
      limit: $data.limit,
      page: $data.page,
      dataLength: $data.total,
      "onUpdate:limit": $options.onUpdateLimit,
      "onUpdate:page": $options.onUpdatePage
    }, null, 8, ["limit", "page", "dataLength", "onUpdate:limit", "onUpdate:page"]),
    $data.smsHistory.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_8, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.smsHistory, (sms) => {
        return openBlock(), createElementBlock("div", {
          key: sms.id,
          class: "tw-border tw-border-neutral-300 em-card-shadow tw-rounded-lg tw-bg-white tw-p-6 tw-mb-4"
        }, [
          createBaseVNode("div", _hoisted_9, [
            createBaseVNode("div", _hoisted_10, [
              createBaseVNode("span", _hoisted_11, toDisplayString(sms.params.date), 1),
              createBaseVNode("span", _hoisted_12, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_EMAILS_MESSAGE_FROM")) + " " + toDisplayString(sms.user_name_from) + " " + toDisplayString(_ctx.translate("COM_EMUNDUS_EMAILS_MESSAGE_TO") + " ") + " ", 1),
                createBaseVNode("strong", null, toDisplayString(sms.fnum), 1)
              ])
            ]),
            createBaseVNode("div", null, [
              sms.status === "sent" ? (openBlock(), createElementBlock("span", {
                key: 0,
                class: "material-symbols-outlined tw-text-main-400",
                title: _ctx.translate("COM_EMUNDUS_SMS_SENT")
              }, "done_all", 8, _hoisted_13)) : sms.status === "pending" ? (openBlock(), createElementBlock("span", {
                key: 1,
                class: "material-symbols-outlined tw-text-yellow-600",
                title: _ctx.translate("COM_EMUNDUS_SMS_PENDING")
              }, "schedule_send", 8, _hoisted_14)) : sms.status === "failed" ? (openBlock(), createElementBlock("span", {
                key: 2,
                class: "material-symbols-outlined tw-text-red-400",
                title: _ctx.translate("COM_EMUNDUS_SMS_FAILED")
              }, "cancel_schedule_send", 8, _hoisted_15)) : createCommentVNode("", true)
            ])
          ]),
          createBaseVNode("p", {
            innerHTML: $options.replaceWithBr(sms.params.message),
            class: "tw-whitespace-pre-line"
          }, null, 8, _hoisted_16)
        ]);
      }), 128))
    ])) : (openBlock(), createElementBlock("div", _hoisted_17, [
      _cache[5] || (_cache[5] = createBaseVNode("img", {
        src: _imports_0,
        alt: "empty-list",
        class: "tw-mx-auto tw-mt-8 tw-w-1/2",
        style: { "width": "10vw", "height": "10vw", "margin": "0 auto" }
      }, null, -1)),
      createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_SMS_EMPTY_HISTORY")), 1)
    ]))
  ]);
}
const SMSGlobalHistory = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  SMSGlobalHistory as default
};
