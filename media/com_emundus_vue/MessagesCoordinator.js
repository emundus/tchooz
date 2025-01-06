import { _ as _export_sfc, S as Swal$1, i as axios, e as resolveComponent, o as openBlock, c as createElementBlock, a as createBaseVNode, n as normalizeClass, t as toDisplayString, d as createCommentVNode, w as withDirectives, y as vModelSelect, b as Fragment, r as renderList, g as createVNode, k as withCtx, z as createTextVNode, B as useGlobalStore, N as hooks, j as createBlock, L as Transition, P as vModelText, W as withKeys, x as withModifiers } from "./app_emundus.js";
import { v as vueDropzone } from "./vue-dropzone.js";
import { q as qs } from "./index.js";
const getTemplate = () => `
<div class="dz-preview dz-file-preview">
  <div class="dz-image">
    <img src="/images/emundus/messenger/file_download.svg" style="max-width: 50px"/>
  </div>
  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
  <div class="dz-error-message"><span data-dz-errormessage></span></div>
  <div class="dz-success-mark"><i class="fa fa-check"></i></div>
  <div class="dz-error-mark"><i class="fa fa-close"></i></div>
</div>
`;
const _sfc_main$1 = {
  name: "AttachDocument",
  props: {
    user: Number,
    fnum: String,
    applicant: Boolean
  },
  components: {
    vueDropzone
  },
  data() {
    return {
      translations: {
        sendDocument: Joomla.JText._("COM_EMUNDUS_MESSENGER_SEND_DOCUMENT"),
        askDocument: Joomla.JText._("COM_EMUNDUS_MESSENGER_ASK_DOCUMENT"),
        DropHere: Joomla.JText._("COM_EMUNDUS_MESSENGER_DROP_HERE"),
        send: Joomla.JText._("COM_EMUNDUS_MESSENGER_SEND"),
        typeAttachment: Joomla.JText._("COM_EMUNDUS_MESSENGER_TYPE_ATTACHMENT"),
        pleaseSelect: Joomla.JText._("COM_EMUNDUS_PLEASE_SELECT")
      },
      types: [],
      message_input: "",
      attachment_input: 0,
      loading: false,
      action: 1,
      dropzoneOptions: {
        url: "index.php?option=com_emundus&controller=messenger&task=uploaddocument&fnum=" + this.fnum + "&applicant=" + this.applicant,
        maxFilesize: 10,
        maxFiles: 5,
        autoProcessQueue: false,
        addRemoveLinks: true,
        thumbnailWidth: null,
        thumbnailHeight: null,
        previewTemplate: getTemplate()
      }
    };
  },
  methods: {
    beforeClose() {
    },
    afterAdded() {
      document.getElementById("dropzone-message").style.display = "none";
    },
    afterRemoved() {
      if (this.$refs.dropzone.getAcceptedFiles().length === 0) {
        document.getElementById("dropzone-message").style.display = "block";
      }
    },
    onComplete: function(response) {
      this.message_input = "";
      if (response.status == "success") {
        this.$emit("pushAttachmentMessage", JSON.parse(response.xhr.response).data);
      }
    },
    sendingEvent(file, xhr, formData) {
      formData.append("message", this.message_input);
      formData.append("attachment", this.attachment_input);
    },
    catchError: function(file, message, xhr) {
      Swal$1.fire({
        title: Joomla.JText._("COM_EMUNDUS_ONBOARD_ERROR"),
        text: message,
        icon: "error",
        showCancelButton: false,
        showConfirmButton: false,
        timer: 3e3
      });
      this.$refs.dropzone.removeFile(file);
    },
    thumbnail: function(file, dataUrl) {
      var j, len, ref, thumbnailElement;
      if (file.previewElement) {
        file.previewElement.classList.remove("dz-file-preview");
        ref = file.previewElement.querySelectorAll("[data-dz-thumbnail-bg]");
        for (j = 0, len = ref.length; j < len; j++) {
          thumbnailElement = ref[j];
          thumbnailElement.alt = file.name;
          thumbnailElement.style.backgroundImage = 'url("' + dataUrl + '")';
        }
        return setTimeout(/* @__PURE__ */ function(_this) {
          return function() {
            return file.previewElement.classList.add("dz-image-preview");
          };
        }(), 1);
      }
    },
    getTypesByCampaign() {
      axios({
        method: "get",
        url: "index.php?option=com_emundus&controller=messenger&task=getdocumentsbycampaign",
        params: {
          fnum: this.fnum,
          applicant: this.applicant
        },
        paramsSerializer: (params) => {
          return qs.stringify(params);
        }
      }).then((response) => {
        this.types = response.data.data;
      });
    },
    askAttachment() {
      axios({
        method: "post",
        url: "index.php?option=com_emundus&controller=messenger&task=askattachment",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        data: qs.stringify({
          fnum: this.fnum,
          attachment: this.attachment_input,
          message: this.message_input
        })
      }).then((response) => {
        this.$emit("pushAttachmentMessage", response.data.data);
      });
    },
    sendMessage(message) {
      if (this.action === 1) {
        if (!this.applicant) {
          this.message_input = message;
        }
        this.$refs.dropzone.processQueue();
      } else {
        if (this.attachment_input) {
          this.message_input = "Demande de document : ";
          const type = this.types.find((type2) => type2.id == this.attachment_input);
          if (type) {
            this.message_input += type.value;
          }
          this.askAttachment();
        }
      }
    }
  }
};
const _hoisted_1$1 = { class: "messages__vue_attach_document" };
const _hoisted_2$1 = ["id"];
const _hoisted_3$1 = ["name"];
const _hoisted_4$1 = { class: "messages__attach_header tw-p-4 tw-w-full tw-flex tw-items-center-justify-end" };
const _hoisted_5$1 = { class: "messages__attach_content" };
const _hoisted_6$1 = {
  key: 0,
  class: "messages__attach_actions_tabs"
};
const _hoisted_7$1 = { class: "messages_action_container tw-pt-4" };
const _hoisted_8$1 = { key: 0 };
const _hoisted_9$1 = { key: 0 };
const _hoisted_10$1 = {
  key: 1,
  class: "messages__attach_applicant_doc"
};
const _hoisted_11$1 = { for: "applicant_attachment_input" };
const _hoisted_12$1 = { value: 0 };
const _hoisted_13$1 = ["value"];
const _hoisted_14$1 = {
  class: "dropzone-custom-content",
  id: "dropzone-message"
};
const _hoisted_15 = { key: 1 };
const _hoisted_16 = { for: "attachment_input" };
const _hoisted_17 = { value: 0 };
const _hoisted_18 = ["value"];
const _hoisted_19 = {
  key: 0,
  class: "loader"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_vue_dropzone = resolveComponent("vue-dropzone");
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", {
      id: "attach_documents" + $props.fnum
    }, [
      createBaseVNode("div", {
        name: "attach_documents" + $props.fnum,
        transition: "nice-modal-fade",
        adaptive: true,
        height: "auto",
        width: "30%",
        scrollable: true,
        delay: 100,
        clickToClose: true,
        onClosed: _cache[6] || (_cache[6] = (...args) => $options.beforeClose && $options.beforeClose(...args)),
        onOpened: _cache[7] || (_cache[7] = (...args) => $options.getTypesByCampaign && $options.getTypesByCampaign(...args))
      }, [
        createBaseVNode("div", _hoisted_4$1, [
          createBaseVNode("span", {
            class: "material-symbols-outlined tw-cursor-pointer",
            onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
          }, "close")
        ]),
        createBaseVNode("div", _hoisted_5$1, [
          !$props.applicant ? (openBlock(), createElementBlock("ul", _hoisted_6$1, [
            createBaseVNode("li", {
              class: normalizeClass(["messages__attach_action tw-mr-2", $data.action === 1 ? "messages__attach_action__current" : ""]),
              onClick: _cache[1] || (_cache[1] = ($event) => $data.action = 1)
            }, toDisplayString($data.translations.sendDocument), 3),
            createBaseVNode("li", {
              class: normalizeClass(["messages__attach_action tw-mr-2", $data.action === 2 ? "messages__attach_action__current" : ""]),
              onClick: _cache[2] || (_cache[2] = ($event) => $data.action = 2)
            }, toDisplayString($data.translations.askDocument), 3)
          ])) : createCommentVNode("", true),
          createBaseVNode("div", _hoisted_7$1, [
            $data.action === 1 ? (openBlock(), createElementBlock("div", _hoisted_8$1, [
              $props.applicant ? (openBlock(), createElementBlock("label", _hoisted_9$1, toDisplayString($data.translations.sendDocument), 1)) : createCommentVNode("", true),
              $props.applicant && $data.types.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_10$1, [
                createBaseVNode("label", _hoisted_11$1, toDisplayString($data.translations.typeAttachment), 1),
                withDirectives(createBaseVNode("select", {
                  "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.attachment_input = $event),
                  id: "applicant_attachment_input"
                }, [
                  createBaseVNode("option", _hoisted_12$1, toDisplayString($data.translations.pleaseSelect), 1),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($data.types, (type) => {
                    return openBlock(), createElementBlock("option", {
                      value: type.id
                    }, toDisplayString(type.value), 9, _hoisted_13$1);
                  }), 256))
                ], 512), [
                  [vModelSelect, $data.attachment_input]
                ])
              ])) : createCommentVNode("", true),
              createVNode(_component_vue_dropzone, {
                ref: "dropzone",
                id: "customdropzone_messenger",
                "include-styling": false,
                options: $data.dropzoneOptions,
                useCustomSlot: true,
                onVdropzoneFileAdded: $options.afterAdded,
                onVdropzoneThumbnail: $options.thumbnail,
                onVdropzoneRemovedFile: $options.afterRemoved,
                onVdropzoneComplete: $options.onComplete,
                onVdropzoneError: $options.catchError,
                onVdropzoneSending: $options.sendingEvent
              }, {
                default: withCtx(() => [
                  createBaseVNode("div", _hoisted_14$1, [
                    _cache[8] || (_cache[8] = createBaseVNode("em", { class: "fas fa-file-image" }, null, -1)),
                    createTextVNode(" " + toDisplayString($data.translations.DropHere), 1)
                  ])
                ]),
                _: 1
              }, 8, ["options", "onVdropzoneFileAdded", "onVdropzoneThumbnail", "onVdropzoneRemovedFile", "onVdropzoneComplete", "onVdropzoneError", "onVdropzoneSending"]),
              $props.applicant ? (openBlock(), createElementBlock("button", {
                key: 2,
                type: "button",
                class: "messages__send_button",
                onClick: _cache[4] || (_cache[4] = (...args) => $options.sendMessage && $options.sendMessage(...args))
              }, toDisplayString($data.translations.send), 1)) : createCommentVNode("", true)
            ])) : (openBlock(), createElementBlock("div", _hoisted_15, [
              createBaseVNode("label", _hoisted_16, toDisplayString($data.translations.typeAttachment), 1),
              withDirectives(createBaseVNode("select", {
                "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.attachment_input = $event),
                id: "attachment_input"
              }, [
                createBaseVNode("option", _hoisted_17, toDisplayString($data.translations.pleaseSelect), 1),
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.types, (type) => {
                  return openBlock(), createElementBlock("option", {
                    key: type.id,
                    value: type.id
                  }, toDisplayString(type.value), 9, _hoisted_18);
                }), 128))
              ], 512), [
                [vModelSelect, $data.attachment_input]
              ])
            ]))
          ])
        ])
      ], 40, _hoisted_3$1)
    ], 8, _hoisted_2$1),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_19)) : createCommentVNode("", true)
  ]);
}
const AttachDocument = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-620e554d"]]);
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
                    class: normalizeClass(["messages__message-item-span", $data.user == message.user_id_from ? "messages__message-item-span_current-user" : "messages__message-item-span_other-user"]),
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
            class: "messages__send-icon material-icons",
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
