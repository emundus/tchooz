import { _ as _export_sfc, r as resolveComponent, c as createElementBlock, o as openBlock, d as createBaseVNode, b as createCommentVNode, g as createVNode, t as toDisplayString, F as Fragment, e as renderList, n as normalizeClass, f as withCtx, a as createBlock, Y as Transition, w as withDirectives, z as vModelText, K as withKeys, h as withModifiers, I as axios, a1 as hooks, u as useGlobalStore } from "./app_emundus.js";
import { A as AttachDocument } from "./AttachDocument.js";
import { q as qs } from "./index2.js";
import "./vue-dropzone.js";
const _sfc_main = {
  name: "MessagesCoordinator",
  props: {},
  components: {
    AttachDocument
  },
  data() {
    return {
      fnum: String,
      user: Number,
      dates: [],
      messages: [],
      fileSelected: 0,
      message: "",
      loading: false,
      showDate: 0,
      counter: 0,
      attachOpen: false,
      currentUserName: "",
      translations: {
        messages: this.translate("COM_EMUNDUS_MESSENGER_TITLE"),
        send: this.translate("COM_EMUNDUS_MESSENGER_SEND"),
        writeMessage: this.translate("COM_EMUNDUS_MESSENGER_WRITE_MESSAGE")
      }
    };
  },
  created() {
    this.fnum = useGlobalStore().datas.fnum.value;
    this.user = useGlobalStore().datas.user.value;
    if (typeof this.fnum != "undefined") {
      this.fileSelected = this.fnum;
      this.getMessagesByFnum();
      setInterval(() => {
        this.getMessagesByFnum(false, false);
      }, 2e4);
    }
    this.getUsername();
  },
  methods: {
    moment(date) {
      return hooks(date);
    },
    getMessagesByFnum(loader = true, scroll = true) {
      this.loading = loader;
      axios({
        method: "get",
        url: "index.php?option=com_emundus&controller=messenger&task=getmessagesbyfnum",
        params: {
          fnum: this.fileSelected
        },
        paramsSerializer: (params) => {
          return qs.stringify(params);
        }
      }).then((response) => {
        this.messages = response.data.data.messages;
        this.dates = response.data.data.dates;
        this.markAsRead();
        if (document.getElementsByClassName("notifications-counter") && typeof document.getElementsByClassName("notifications-counter")[0] != "undefined") {
          document.getElementsByClassName("notifications-counter")[0].remove();
        }
        if (scroll) {
          this.scrollToBottom();
        }
        this.loading = false;
      });
    },
    markAsRead() {
      axios({
        method: "get",
        url: "index.php?option=com_emundus&controller=messenger&task=markasread",
        params: {
          fnum: this.fileSelected
        },
        paramsSerializer: (params) => {
          return qs.stringify(params);
        }
      }).then((response) => {
        this.$emit("removeNotifications", response.data.data);
      });
    },
    getUsername() {
      fetch("index.php?option=com_emundus&controller=users&task=getuserbyid").then((res) => {
        if (res.ok) {
          return res.json();
        }
      }).then((response) => {
        if (response.status) {
          this.currentUserName = response.user[0].firstname + " " + response.user[0].lastname;
        }
      });
    },
    sendMessage(e) {
      if (typeof e != "undefined") {
        e.stopImmediatePropagation();
      }
      if (this.attachOpen) {
        this.$refs.attachment.sendMessage(this.message);
        this.message = "";
      } else {
        if (this.message.trim() !== "") {
          const formData = new FormData();
          formData.append("message", this.message);
          formData.append("fnum", this.fileSelected);
          fetch("index.php?option=com_emundus&controller=messenger&task=sendmessage", {
            method: "POST",
            body: formData
          }).then((res) => {
            if (res.ok) {
              return res.json();
            }
          }).then((response) => {
            this.send_progress = false;
            if (response.status) {
              this.getMessagesByFnum(true, true);
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
          this.pushToDatesArray({
            message_id: Math.floor(Math.random() * 1e3) + 9999,
            user_id_from: this.user,
            user_id_to: null,
            folder_id: 2,
            date_time: this.formatedTimestamp(),
            state: 0,
            priority: 0,
            subject: 0,
            message: this.message,
            email_from: null,
            email_cc: null,
            email_to: null,
            name: this.currentUserName
          });
          this.message = "";
        }
      }
    },
    pushToDatesArray(message) {
      let pushToDate = false;
      let message_date = this.moment().format("YYYY-MM-DD");
      if (message.date_time) {
        message_date = message.date_time.split(" ")[0];
      }
      this.dates.forEach((elt, index) => {
        if (elt.dates == message_date) {
          this.dates[index].messages.push(message.message_id);
          pushToDate = true;
        }
      });
      if (!pushToDate) {
        var new_date = {
          dates: this.moment().format("YYYY-MM-DD"),
          messages: []
        };
        new_date.messages.push(message.message_id);
        this.dates.push(new_date);
      }
      this.messages.push(message);
    },
    scrollToBottom() {
      setTimeout(() => {
        const container = document.getElementsByClassName("messages__list-block")[0];
        container.scrollTop = container.scrollHeight;
      }, 500);
    },
    attachDocument() {
      this.attachOpen = !this.attachOpen;
      setTimeout(() => {
        if (this.attachOpen) {
          this.$refs.attachment.getTypesByCampaign();
        }
      }, 500);
    },
    pushAttachmentMessage(message) {
      this.pushToDatesArray(message);
      this.scrollToBottom();
      this.attachDocument();
    },
    formatedTimestamp() {
      const d = /* @__PURE__ */ new Date();
      const date = d.toISOString().split("T")[0];
      const time = d.toTimeString().split(" ")[0];
      return `${date} ${time}`;
    }
  },
  computed: {
    messageByDates() {
      let messages = [];
      this.dates.forEach((elt) => {
        let date = elt.dates;
        let messages_array = [];
        elt.messages.forEach((message_id) => {
          this.messages.forEach((message) => {
            if (message.message_id == message_id) {
              messages_array.push(message);
            }
          });
        });
        messages.push({ date, messages: messages_array });
      });
      return messages;
    }
  },
  watch: {
    fileSelected: function() {
      this.getMessagesByFnum(true);
    }
  }
};
const _hoisted_1 = { class: "messages__coordinator_vue tw-w-full" };
const _hoisted_2 = { class: "messages__list col-md-12" };
const _hoisted_3 = {
  class: "text-center tw-ml-4",
  style: { "width": "100%" }
};
const _hoisted_4 = {
  class: "messages__list-block",
  id: "messages__list"
};
const _hoisted_5 = { class: "messages__date-section" };
const _hoisted_6 = ["onClick"];
const _hoisted_7 = { class: "messages__message-item-from" };
const _hoisted_8 = ["innerHTML"];
const _hoisted_9 = {
  key: 0,
  class: "messages__message-item-from"
};
const _hoisted_10 = { class: "messages__bottom-input" };
const _hoisted_11 = ["disabled", "placeholder"];
const _hoisted_12 = { class: "messages__bottom-input-actions" };
const _hoisted_13 = { class: "messages__actions_bar" };
const _hoisted_14 = {
  key: 0,
  class: "loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_AttachDocument = resolveComponent("AttachDocument");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("label", _hoisted_3, toDisplayString($data.translations.messages), 1),
      createBaseVNode("div", _hoisted_4, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.messageByDates, (date) => {
          return openBlock(), createElementBlock("div", {
            key: date.dates
          }, [
            createBaseVNode("div", _hoisted_5, [
              _cache[4] || (_cache[4] = createBaseVNode("hr", null, null, -1)),
              createBaseVNode("p", null, toDisplayString($options.moment(date.dates).format("DD/MM/YYYY")), 1),
              _cache[5] || (_cache[5] = createBaseVNode("hr", null, null, -1))
            ]),
            (openBlock(true), createElementBlock(Fragment, null, renderList(date.messages, (message) => {
              return openBlock(), createElementBlock("div", {
                key: message.message_id,
                class: normalizeClass(["messages__message-item", $data.user == message.user_id_from ? "messages__current_user" : "messages__other_user"])
              }, [
                createBaseVNode("div", {
                  class: normalizeClass(["messages__message-item-block", $data.user == message.user_id_from ? "messages__text-align-right" : "messages__text-align-left"]),
                  onClick: ($event) => $data.showDate != message.message_id ? $data.showDate = message.message_id : $data.showDate = 0
                }, [
                  createBaseVNode("p", null, [
                    createBaseVNode("span", _hoisted_7, toDisplayString(message.name) + " - " + toDisplayString(message.date_hour), 1)
                  ]),
                  createBaseVNode("span", {
                    class: normalizeClass([
                      "messages__message-item-span",
                      $data.user == message.user_id_from ? "messages__message-item-span_current-user" : "messages__message-item-span_other-user"
                    ]),
                    innerHTML: message.message
                  }, null, 10, _hoisted_8),
                  createBaseVNode("p", null, [
                    $data.showDate == message.message_id ? (openBlock(), createElementBlock("span", _hoisted_9, toDisplayString($options.moment(message.date_time).format("DD/MM/YYYY HH:mm")), 1)) : createCommentVNode("", true)
                  ])
                ], 10, _hoisted_6)
              ], 2);
            }), 128))
          ]);
        }), 128))
      ]),
      createVNode(Transition, {
        name: "slide-up",
        type: "transition"
      }, {
        default: withCtx(() => [
          $data.attachOpen ? (openBlock(), createBlock(_component_AttachDocument, {
            key: 0,
            user: $data.user,
            fnum: $data.fnum,
            applicant: false,
            onPushAttachmentMessage: $options.pushAttachmentMessage,
            onClose: $options.attachDocument,
            ref: "attachment"
          }, null, 8, ["user", "fnum", "onPushAttachmentMessage", "onClose"])) : createCommentVNode("", true)
        ]),
        _: 1
      }),
      createBaseVNode("div", _hoisted_10, [
        withDirectives(createBaseVNode("textarea", {
          type: "text",
          class: "messages__input_text",
          disabled: $data.attachOpen,
          rows: "1",
          spellcheck: "true",
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.message = $event),
          placeholder: $data.translations.writeMessage,
          onKeydown: _cache[1] || (_cache[1] = withKeys(withModifiers(($event) => $options.sendMessage($event), ["exact", "prevent"]), ["enter"]))
        }, null, 40, _hoisted_11), [
          [vModelText, $data.message]
        ])
      ]),
      createBaseVNode("div", _hoisted_12, [
        createBaseVNode("div", _hoisted_13, [
          createBaseVNode("span", {
            class: "messages__send-icon material-symbols-outlined",
            onClick: _cache[2] || (_cache[2] = (...args) => $options.attachDocument && $options.attachDocument(...args))
          }, "attach_file")
        ]),
        createBaseVNode("button", {
          type: "button",
          class: "messages__send_button btn btn-primary",
          onClick: _cache[3] || (_cache[3] = (...args) => $options.sendMessage && $options.sendMessage(...args))
        }, toDisplayString($data.translations.send), 1)
      ])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_14)) : createCommentVNode("", true)
  ]);
}
const MessagesCoordinator = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  MessagesCoordinator as default
};
