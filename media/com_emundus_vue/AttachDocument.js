import { _ as _export_sfc, S as Swal, O as axios, r as resolveComponent, o as openBlock, c as createElementBlock, d as createBaseVNode, n as normalizeClass, t as toDisplayString, b as createCommentVNode, w as withDirectives, z as vModelSelect, F as Fragment, e as renderList, h as createVNode, f as withCtx, m as createTextVNode } from "./app_emundus.js";
import { v as vueDropzone } from "./vue-dropzone.js";
import { q as qs } from "./index2.js";
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
const _sfc_main = {
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
      Swal.fire({
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
        return setTimeout(
          /* @__PURE__ */ function(_this) {
            return function() {
              return file.previewElement.classList.add("dz-image-preview");
            };
          }(),
          1
        );
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
const _hoisted_1 = { class: "messages__vue_attach_document" };
const _hoisted_2 = ["id"];
const _hoisted_3 = ["name"];
const _hoisted_4 = { class: "messages__attach_header tw-items-center-justify-end tw-flex tw-w-full tw-p-4" };
const _hoisted_5 = { class: "messages__attach_content" };
const _hoisted_6 = {
  key: 0,
  class: "messages__attach_actions_tabs"
};
const _hoisted_7 = { class: "messages_action_container tw-pt-4" };
const _hoisted_8 = { key: 0 };
const _hoisted_9 = { key: 0 };
const _hoisted_10 = {
  key: 1,
  class: "messages__attach_applicant_doc"
};
const _hoisted_11 = { for: "applicant_attachment_input" };
const _hoisted_12 = { value: 0 };
const _hoisted_13 = ["value"];
const _hoisted_14 = {
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
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_vue_dropzone = resolveComponent("vue-dropzone");
  return openBlock(), createElementBlock("div", _hoisted_1, [
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
        createBaseVNode("div", _hoisted_4, [
          createBaseVNode("span", {
            class: "material-symbols-outlined tw-cursor-pointer",
            onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
          }, "close")
        ]),
        createBaseVNode("div", _hoisted_5, [
          !$props.applicant ? (openBlock(), createElementBlock("ul", _hoisted_6, [
            createBaseVNode("li", {
              class: normalizeClass(["messages__attach_action tw-mr-2", $data.action === 1 ? "messages__attach_action__current" : ""]),
              onClick: _cache[1] || (_cache[1] = ($event) => $data.action = 1)
            }, toDisplayString($data.translations.sendDocument), 3),
            createBaseVNode("li", {
              class: normalizeClass(["messages__attach_action tw-mr-2", $data.action === 2 ? "messages__attach_action__current" : ""]),
              onClick: _cache[2] || (_cache[2] = ($event) => $data.action = 2)
            }, toDisplayString($data.translations.askDocument), 3)
          ])) : createCommentVNode("", true),
          createBaseVNode("div", _hoisted_7, [
            $data.action === 1 ? (openBlock(), createElementBlock("div", _hoisted_8, [
              $props.applicant ? (openBlock(), createElementBlock("label", _hoisted_9, toDisplayString($data.translations.sendDocument), 1)) : createCommentVNode("", true),
              $props.applicant && $data.types.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_10, [
                createBaseVNode("label", _hoisted_11, toDisplayString($data.translations.typeAttachment), 1),
                withDirectives(createBaseVNode("select", {
                  "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.attachment_input = $event),
                  id: "applicant_attachment_input"
                }, [
                  createBaseVNode("option", _hoisted_12, toDisplayString($data.translations.pleaseSelect), 1),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($data.types, (type) => {
                    return openBlock(), createElementBlock("option", {
                      value: type.id
                    }, toDisplayString(type.value), 9, _hoisted_13);
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
                  createBaseVNode("div", _hoisted_14, [
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
      ], 40, _hoisted_3)
    ], 8, _hoisted_2),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_19)) : createCommentVNode("", true)
  ]);
}
const AttachDocument = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-22a6284a"]]);
export {
  AttachDocument as A
};
