import { _ as _export_sfc, M as Modal, r as resolveComponent, o as openBlock, a as createBlock, h as createVNode, f as withCtx, n as normalizeClass, p as Teleport, c as createElementBlock, d as createBaseVNode, t as toDisplayString, g as withModifiers, F as Fragment, e as renderList, b as createCommentVNode, q as ref } from "./app_emundus.js";
import { M as Messages, m as messengerServices } from "./Messages.js";
import "./Parameter.js";
import "./index.js";
import "./EventBooking.js";
import "./events2.js";
import "./Info.js";
import "./AttachDocument.js";
import "./vue-dropzone.js";
import "./index2.js";
import "./Skeleton.js";
const _sfc_main$2 = {
  name: "MessengerPopup",
  components: { Messages, Modal },
  emits: ["close", "open"],
  props: {
    fnum: {
      type: String,
      required: true
    },
    fullname: {
      type: String,
      required: true
    },
    unread_messages: {
      type: Array
    }
  },
  methods: {
    beforeClose() {
      this.$emit("close");
    },
    beforeOpen() {
      this.$emit("open");
    },
    closeModal() {
      this.$emit("close");
    },
    closedChatroom(fnum) {
      this.$emit("closedChatroom", fnum);
    }
  }
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Messages = resolveComponent("Messages");
  const _component_modal = resolveComponent("modal");
  return openBlock(), createBlock(Teleport, { to: "body" }, [
    createVNode(_component_modal, {
      name: "messenger-modal",
      class: normalizeClass("placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-rounded tw-bg-white tw-shadow-modal"),
      transition: "nice-modal-fade",
      width: "95%",
      height: "95%",
      delay: 100,
      adaptive: true,
      clickToClose: false,
      onClosed: $options.beforeClose,
      onBeforeOpen: $options.beforeOpen
    }, {
      default: withCtx(() => [
        createVNode(_component_Messages, {
          "is-modal": true,
          fnum: $props.fnum,
          fullname: $props.fullname,
          unread_messages: $props.unread_messages,
          onClose: $options.closeModal,
          onClosedChatroom: $options.closedChatroom
        }, null, 8, ["fnum", "fullname", "unread_messages", "onClose", "onClosedChatroom"])
      ]),
      _: 1
    }, 8, ["onClosed", "onBeforeOpen"])
  ]);
}
const MessengerPopup = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-265839f0"]]);
const _sfc_main$1 = {
  name: "NotificationsPopup",
  props: {
    unreadMessages: {
      type: Array,
      required: true
    }
  },
  data: () => ({
    conversionOpened: 0
  }),
  created() {
    document.addEventListener("click", this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener("click", this.handleClickOutside);
  },
  methods: {
    toggleConversation(id) {
      if (this.conversionOpened === id) {
        this.conversionOpened = 0;
      } else {
        this.conversionOpened = id;
      }
    },
    closeChatroom(fnum) {
      messengerServices.closeChatroom(fnum).then((response) => {
        if (response.status) {
          this.$emit("closedChatroom", fnum);
        } else {
          Swal.fire({
            title: Joomla.Text._("COM_EMUNDUS_ONBOARD_ERROR"),
            text: response.msg,
            type: "error",
            showCancelButton: false,
            showConfirmButton: false,
            timer: 3e3
          });
        }
      });
    },
    goToFile(fnum) {
      messengerServices.goToFile(fnum).then((response) => {
        if (response.status) {
          window.location.href = response.route;
          let event = new CustomEvent("messengerOpenFile", { detail: { fnum } });
          document.dispatchEvent(event);
        }
      });
    },
    sentTranslation(unread) {
      if (unread.messages.length > 1) {
        return this.translate("COM_EMUNDUS_MESSENGER_NOTIFICATIONS_HAS_SENT_MESSAGES").replace(
          "%count",
          unread.messages.length
        );
      } else {
        return this.translate("COM_EMUNDUS_MESSENGER_NOTIFICATIONS_HAS_SENT_ONE_MESSAGE");
      }
    },
    handleClickOutside(event) {
      const clickedElement = event.target;
      if (!clickedElement.closest("#messenger_notifications_popup")) {
        this.$emit("close");
      }
    }
  }
};
const _hoisted_1$1 = { class: "tw-relative" };
const _hoisted_2$1 = { class: "tw-absolute tw-right-0 tw-top-6 tw-w-[25em] tw-rounded-coordinator tw-border tw-border-neutral-300 tw-bg-white tw-p-3 tw-shadow-standard" };
const _hoisted_3$1 = { class: "tw-flex tw-items-center tw-justify-between" };
const _hoisted_4 = {
  key: 0,
  class: "tw-flex tw-max-h-[20em] tw-flex-col tw-gap-3 tw-overflow-auto"
};
const _hoisted_5 = { class: "tw-flex tw-flex-col" };
const _hoisted_6 = { class: "tw-flex tw-flex-wrap tw-items-center tw-gap-1 tw-overflow-x-hidden" };
const _hoisted_7 = { class: "tw-mb-0 tw-flex tw-cursor-pointer tw-items-center tw-gap-1 tw-text-base" };
const _hoisted_8 = ["onClick"];
const _hoisted_9 = ["onClick"];
const _hoisted_10 = ["onClick"];
const _hoisted_11 = {
  key: 0,
  class: "tw-mt-1 tw-border-s-2 tw-border-main-500 tw-pl-1"
};
const _hoisted_12 = { class: "tw-mb-2 tw-flex tw-flex-col tw-gap-1" };
const _hoisted_13 = { class: "tw-text-sm" };
const _hoisted_14 = { class: "tw-rounded-coordinator tw-border tw-border-neutral-300 tw-p-2" };
const _hoisted_15 = { class: "tw-mx-1 tw-mt-1 tw-flex tw-items-center tw-justify-between" };
const _hoisted_16 = ["onClick"];
const _hoisted_17 = ["onClick"];
const _hoisted_18 = { key: 1 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", _hoisted_2$1, [
      createBaseVNode("div", _hoisted_3$1, [
        createBaseVNode("h4", null, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_NOTIFICATIONS")), 1),
        createBaseVNode("button", {
          class: "tw-cursor-pointer tw-bg-transparent",
          onClick: _cache[0] || (_cache[0] = withModifiers(($event) => _ctx.$emit("close"), ["prevent"]))
        }, _cache[1] || (_cache[1] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
        ]))
      ]),
      _cache[2] || (_cache[2] = createBaseVNode("hr", null, null, -1)),
      $props.unreadMessages.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_4, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($props.unreadMessages, (unread) => {
          return openBlock(), createElementBlock("div", _hoisted_5, [
            createBaseVNode("div", _hoisted_6, [
              createBaseVNode("label", _hoisted_7, [
                createBaseVNode("a", {
                  type: "button",
                  class: "tw-cursor-pointer tw-text-blue-500",
                  onClick: ($event) => $options.goToFile(unread.fnum)
                }, toDisplayString(unread.fullname), 9, _hoisted_8),
                createBaseVNode("span", {
                  onClick: ($event) => $options.toggleConversation(unread.page)
                }, toDisplayString($options.sentTranslation(unread)), 9, _hoisted_9)
              ]),
              createBaseVNode("span", {
                class: normalizeClass(["material-symbols-outlined tw-cursor-pointer", { "tw-rotate-90": _ctx.conversionOpened === unread.page }]),
                onClick: ($event) => $options.toggleConversation(unread.page)
              }, "chevron_right", 10, _hoisted_10)
            ]),
            _ctx.conversionOpened === unread.page ? (openBlock(), createElementBlock("div", _hoisted_11, [
              (openBlock(true), createElementBlock(Fragment, null, renderList(unread.messages, (message) => {
                return openBlock(), createElementBlock("div", _hoisted_12, [
                  createBaseVNode("span", _hoisted_13, toDisplayString(message.date_time), 1),
                  createBaseVNode("div", _hoisted_14, toDisplayString(message.message), 1)
                ]);
              }), 256)),
              createBaseVNode("div", _hoisted_15, [
                createBaseVNode("button", {
                  type: "button",
                  class: "tw-cursor-pointer tw-text-blue-500",
                  onClick: ($event) => $options.closeChatroom(unread.fnum)
                }, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CLOSE_CHATROOM")), 9, _hoisted_16),
                createBaseVNode("span", {
                  class: "material-symbols-outlined tw-cursor-pointer",
                  onClick: ($event) => $options.goToFile(unread.fnum)
                }, "reply", 8, _hoisted_17)
              ])
            ])) : createCommentVNode("", true)
          ]);
        }), 256))
      ])) : (openBlock(), createElementBlock("div", _hoisted_18, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_NO_NOTIFICATIONS")), 1))
    ])
  ]);
}
const NotificationsPopup = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "Messenger",
  props: {
    fnum: {
      type: String,
      required: true
    },
    fullname: {
      type: String,
      required: true
    },
    unread_messages: {
      type: Array
    },
    applicant: {
      type: Boolean,
      default: true
    }
  },
  components: {
    NotificationsPopup,
    MessengerPopup
  },
  data() {
    return {
      counter: 0,
      notifications: [],
      modalOpened: false
    };
  },
  created() {
    if (!this.applicant) {
      document.addEventListener("removeMessengerNotifications", this.removeNotifications);
    }
    this.notifications = ref(this.unread_messages);
  },
  beforeUnmount() {
    document.removeEventListener("removeMessengerNotifications", this.removeNotifications);
  },
  methods: {
    removeNotifications(event) {
      if (event.detail) {
        this.closedChatroom(event.detail.fnum);
      }
    },
    closedChatroom(fnum) {
      this.notifications = this.notifications.filter((notification) => notification.fnum !== fnum);
    }
  }
};
const _hoisted_1 = { id: "messenger_notifications_popup" };
const _hoisted_2 = {
  class: "tw-relative",
  style: { "height": "20px" },
  id: "messenger_notifications_icon"
};
const _hoisted_3 = {
  key: 0,
  class: "tw-absolute tw-rounded-full tw-bg-red-500",
  style: { "top": "-2px", "right": "4px", "width": "8px", "height": "8px" }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_MessengerPopup = resolveComponent("MessengerPopup");
  const _component_NotificationsPopup = resolveComponent("NotificationsPopup");
  return openBlock(), createElementBlock("div", null, [
    $data.modalOpened && $props.applicant ? (openBlock(), createBlock(_component_MessengerPopup, {
      key: 0,
      onClose: _cache[0] || (_cache[0] = ($event) => $data.modalOpened = false),
      fnum: $props.fnum,
      fullname: $props.fullname,
      unread_messages: $data.notifications,
      onClosedChatroom: $options.closedChatroom
    }, null, 8, ["fnum", "fullname", "unread_messages", "onClosedChatroom"])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_1, [
      $data.modalOpened && !$props.applicant ? (openBlock(), createBlock(_component_NotificationsPopup, {
        key: 0,
        onClose: _cache[1] || (_cache[1] = ($event) => $data.modalOpened = false),
        "unread-messages": $data.notifications,
        onClosedChatroom: $options.closedChatroom
      }, null, 8, ["unread-messages", "onClosedChatroom"])) : createCommentVNode("", true),
      createBaseVNode("div", _hoisted_2, [
        createBaseVNode("span", {
          class: "material-symbols-outlined tw-cursor-pointer tw-text-neutral-900",
          onClick: _cache[2] || (_cache[2] = ($event) => $data.modalOpened = !$data.modalOpened)
        }, "question_answer"),
        $data.notifications.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_3)) : createCommentVNode("", true)
      ])
    ])
  ]);
}
const Messenger = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Messenger as default
};
