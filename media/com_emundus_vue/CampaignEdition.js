import { _ as _export_sfc, V as VueDraggableNext, S as Swal, L as campaignService, r as resolveComponent, c as createElementBlock, o as openBlock, d as createBaseVNode, b as createCommentVNode, a as createBlock, h as createVNode, f as withCtx, t as toDisplayString, N as TransitionGroup, F as Fragment, e as renderList, O as mergeProps, M as Modal, l as emailService, s as settingsService, g as withModifiers, w as withDirectives, n as normalizeClass, y as vModelSelect, I as axios, m as createTextVNode, Q as workflowService, u as useGlobalStore, R as vModelCheckbox, U as toHandlers, W as mixin, X as History, T as Tabs, v as vShow, Y as Transition, Z as programmeService, $ as formService } from "./app_emundus.js";
import addCampaign from "./addCampaign.js";
import { v as vueDropzone } from "./vue-dropzone.js";
import { q as qs } from "./index2.js";
import { D as DatePicker } from "./index.js";
/* empty css       */
const _imports_0 = "/media/com_emundus_vue/assets/pdf.png";
const _imports_1 = "/media/com_emundus_vue/assets/doc.png";
const _imports_2 = "/media/com_emundus_vue/assets/excel.png";
const _imports_3 = "/media/com_emundus_vue/assets/image.png";
const _imports_4 = "/media/com_emundus_vue/assets/zip.png";
const _imports_5 = "/media/com_emundus_vue/assets/svg.png";
const getTemplate = () => `
<div class="dz-preview dz-file-preview">
  <div class="dz-image">
    <div data-dz-thumbnail-bg></div>
  </div>
  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
  <div class="dz-error-message"><span data-dz-errormessage></span></div>
  <div class="dz-success-mark"><i class="fa fa-check"></i></div>
  <div class="dz-error-mark"><i class="fa fa-close"></i></div>
</div>
`;
const _sfc_main$5 = {
  name: "addDocumentsDropfiles",
  components: {
    vueDropzone,
    draggable: VueDraggableNext
  },
  props: {
    funnelCategorie: String,
    profileId: Number,
    campaignId: Number,
    langue: String,
    manyLanguages: Number
  },
  data() {
    return {
      dropzoneOptions: {
        url: window.location.origin + "/index.php?option=com_emundus&controller=settings&task=uploaddropfiledoc&cid=" + this.campaignId,
        maxFilesize: 10,
        maxFiles: 1,
        autoProcessQueue: true,
        addRemoveLinks: true,
        thumbnailWidth: null,
        thumbnailHeight: null,
        acceptedFiles: "image/*,application/pdf,.doc,.csv,.xls,.xlsx,.docx,.odf,.zip",
        previewTemplate: getTemplate(),
        dictCancelUpload: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD"),
        dictCancelUploadConfirmation: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD_CONFIRMATION"),
        dictRemoveFile: this.translate("COM_EMUNDUS_ONBOARD_REMOVE_FILE"),
        dictInvalidFileType: this.translate("COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE"),
        dictFileTooBig: this.translate("COM_EMUNDUS_ONBOARD_FILE_TOO_BIG"),
        dictMaxFilesExceeded: this.translate("COM_EMUNDUS_ONBOARD_MAX_FILES_EXCEEDED"),
        uploadMultiple: false
      },
      documents: [],
      Retour: this.translate("COM_EMUNDUS_ONBOARD_ADD_RETOUR"),
      Continuer: this.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER"),
      DropHere: this.translate("COM_EMUNDUS_ONBOARD_DROP_FILE_HERE"),
      Error: this.translate("COM_EMUNDUS_ONBOARD_ERROR"),
      DocumentName: this.translate("COM_EMUNDUS_ONBOARD_DOCUMENT_NAME"),
      drag: false,
      loading: false
    };
  },
  methods: {
    getMediaSize() {
      campaignService.get("getmediasize").then((response) => {
        if (response.status) {
          this.dropzoneOptions.maxFilesize = parseInt(response.size);
        }
      }).finally(() => {
        this.loading = false;
      });
    },
    getDocumentsDropfiles() {
      this.loading = true;
      campaignService.get("getdocumentsdropfiles", { cid: this.campaignId }).then((response) => {
        this.documents = response.documents;
      }).finally(() => {
        this.loading = false;
      });
    },
    updateDocumentsOrder() {
      this.documents.forEach((document2, index) => {
        document2.ordering = index;
      });
      campaignService.reorderDropfileDocuments(this.documents);
    },
    editName(doc) {
      Swal.fire({
        title: "",
        html: '<div class="form-group campaign-label"><label for="campLabel">' + this.DocumentName + '</label><input type="text" class="tw-mt-2" maxlength="200" id="label_' + doc.id + '" value="' + doc.title + '"/></div>',
        showCloseButton: true,
        allowOutsideClick: false,
        confirmButtonText: this.translate("COM_EMUNDUS_OK"),
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action",
          content: "text-start"
        }
      }).then((value) => {
        if (value) {
          let newname = document.getElementById("label_" + doc.id).value;
          if (newname.length > 200) {
            newname = newname.substring(0, 200);
          }
          campaignService.editDropfileDocument(doc.id, newname).then(() => {
            doc.title = newname;
          });
        }
      });
    },
    deleteDoc(index, id) {
      this.documents.splice(index, 1);
      campaignService.deleteDropfileDocument(id);
    },
    formatBytes(bytes, decimals = 2) {
      if (bytes === 0) return "0 Bytes";
      const k = 1024;
      const dm = decimals < 0 ? 0 : decimals;
      const sizes = ["Bytes", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB"];
      const i = Math.floor(Math.log(bytes) / Math.log(k));
      return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + " " + sizes[i];
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
      this.documents.push(JSON.parse(response.xhr.response));
      this.$refs.dropzone.removeFile(response);
    },
    catchError: function(file, message) {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_ERROR"),
        text: message,
        type: "error",
        showCancelButton: false,
        showConfirmButton: false,
        timer: 3e3
      });
      this.$refs.dropzone.removeFile(file);
    }
  },
  computed: {
    dragOptions() {
      return {
        animation: 200,
        group: "description",
        disabled: false,
        ghostClass: "ghost"
      };
    }
  },
  created() {
    this.getDocumentsDropfiles();
    this.getMediaSize();
  }
};
const _hoisted_1$5 = { id: "documents-dropfiles" };
const _hoisted_2$4 = { class: "w-form" };
const _hoisted_3$4 = {
  class: "dropzone-custom-content",
  id: "dropzone-message"
};
const _hoisted_4$4 = ["id"];
const _hoisted_5$4 = ["onClick"];
const _hoisted_6$4 = { class: "tw-flex tw-w-full tw-items-center tw-justify-center" };
const _hoisted_7$4 = ["onClick"];
const _hoisted_8$4 = {
  key: 0,
  src: _imports_0,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_9$4 = {
  key: 1,
  src: _imports_1,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_10$4 = {
  key: 2,
  src: _imports_2,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_11$4 = {
  key: 3,
  src: _imports_3,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_12$4 = {
  key: 4,
  src: _imports_4,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_13$4 = {
  key: 5,
  src: _imports_5,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_14$3 = { class: "tw-mt-2" };
const _hoisted_15$3 = { class: "em-overflow-ellipsis em-max-width-250 tw-mr-1" };
const _hoisted_16$2 = { id: "itemDocSize" };
const _hoisted_17$2 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_vue_dropzone = resolveComponent("vue-dropzone");
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$5, [
    createBaseVNode("div", _hoisted_2$4, [
      (openBlock(), createBlock(_component_vue_dropzone, {
        key: $data.dropzoneOptions.maxFilesize,
        ref: "dropzone",
        id: "customdropzone",
        style: { "width": "100%" },
        "include-styling": false,
        options: $data.dropzoneOptions,
        useCustomSlot: true,
        onVdropzoneFileAdded: $options.afterAdded,
        onVdropzoneRemovedFile: $options.afterRemoved,
        onVdropzoneSuccess: $options.onComplete,
        onVdropzoneError: $options.catchError
      }, {
        default: withCtx(() => [
          createBaseVNode("div", _hoisted_3$4, toDisplayString($data.DropHere), 1)
        ]),
        _: 1
      }, 8, ["options", "onVdropzoneFileAdded", "onVdropzoneRemovedFile", "onVdropzoneSuccess", "onVdropzoneError"])),
      _cache[3] || (_cache[3] = createBaseVNode("hr", null, null, -1)),
      createVNode(_component_draggable, mergeProps({
        modelValue: $data.documents,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.documents = $event),
        id: "campaignDocs",
        style: { "margin": "0" },
        handle: ".handle",
        class: "tw-flex tw-items-center",
        "chosen-class": "em-grabbing"
      }, $options.dragOptions, { onEnd: $options.updateDocumentsOrder }), {
        default: withCtx(() => [
          createVNode(TransitionGroup, {
            type: "transition",
            value: !$data.drag ? "flip-list" : null,
            class: "handle tw-grid tw-w-full tw-grid-cols-3 tw-gap-6"
          }, {
            default: withCtx(() => [
              (openBlock(true), createElementBlock(Fragment, null, renderList($data.documents, (document2, indexDoc) => {
                return openBlock(), createElementBlock("div", {
                  id: "itemDoc" + document2.id,
                  key: document2.id,
                  class: "em-document-dropzone-card handle tw-mr-2 tw-cursor-grab"
                }, [
                  createBaseVNode("button", {
                    type: "button",
                    class: "tw-float-right tw-bg-transparent",
                    onClick: ($event) => $options.deleteDoc(indexDoc, document2.id)
                  }, _cache[1] || (_cache[1] = [
                    createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
                  ]), 8, _hoisted_5$4),
                  createBaseVNode("div", _hoisted_6$4, [
                    createBaseVNode("div", {
                      class: "em-edit-cursor tw-flex tw-flex-col tw-items-center",
                      onClick: ($event) => $options.editName(document2)
                    }, [
                      document2.ext === "pdf" ? (openBlock(), createElementBlock("img", _hoisted_8$4)) : ["docx", "doc", "odf"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_9$4)) : ["xls", "xlsx", "csv"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_10$4)) : ["png", "gif", "jpg", "jpeg"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_11$4)) : ["zip", "rar"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_12$4)) : ["svg"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_13$4)) : createCommentVNode("", true),
                      createBaseVNode("div", _hoisted_14$3, [
                        createBaseVNode("span", _hoisted_15$3, toDisplayString(document2.title), 1)
                      ])
                    ], 8, _hoisted_7$4)
                  ]),
                  _cache[2] || (_cache[2] = createBaseVNode("hr", null, null, -1)),
                  createBaseVNode("div", _hoisted_16$2, [
                    createBaseVNode("span", null, [
                      createBaseVNode("strong", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FILE_SIZE")) + " : ", 1)
                    ]),
                    createBaseVNode("span", null, toDisplayString($options.formatBytes(document2.size)), 1)
                  ])
                ], 8, _hoisted_4$4);
              }), 128))
            ]),
            _: 1
          }, 8, ["value"])
        ]),
        _: 1
      }, 16, ["modelValue", "onEnd"])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_17$2)) : createCommentVNode("", true)
  ]);
}
const AddDocumentsDropfiles = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__scopeId", "data-v-e4db7f87"]]);
const _sfc_main$4 = {
  name: "modalAddTrigger",
  components: {
    Modal
  },
  props: {
    prog: Number,
    trigger: Number,
    triggerAction: String,
    classes: {
      type: String,
      default: ""
    },
    placement: {
      type: String,
      default: "default"
    }
  },
  data() {
    return {
      errors: {
        model: false,
        status: false,
        action_status: false,
        target: false
      },
      form: {
        model: -1,
        status: -1,
        action_status: null,
        target: -1,
        program: this.prog
      },
      models: [],
      status: [],
      changes: false
    };
  },
  mounted() {
  },
  methods: {
    beforeClose() {
      this.form = {
        model: -1,
        status: null,
        action_status: null,
        target: null,
        program: this.prog
      };
      this.$emit("close");
    },
    beforeOpen() {
      this.searchTerm = "";
      this.getEmailModels();
      this.getStatus();
      setTimeout(() => {
        if (this.trigger != null) {
          this.getTrigger();
        }
      }, 200);
      if (this.triggerAction === "candidate") {
        this.form.action_status = "to_current_user";
      } else {
        this.form.action_status = "to_applicant";
      }
    },
    createTrigger() {
      this.errors = {
        model: false,
        status: false,
        action_status: false,
        target: false,
        selectedUsers: false
      };
      if (this.form.model === -1) {
        this.errors.model = true;
        return 0;
      }
      if (this.form.status == null) {
        this.errors.status = true;
        return 0;
      }
      if (this.form.action_status == null) {
        this.errors.action_status = true;
        return 0;
      }
      if (this.form.target == null) {
        this.errors.target = true;
        return 0;
      }
      if (this.trigger != null) {
        emailService.updateEmailTrigger(this.trigger, this.form).then(() => {
          this.$emit("UpdateTriggers");
          this.$emit("close");
        });
      } else {
        emailService.createEmailTrigger(this.form).then(() => {
          this.$emit("UpdateTriggers");
          this.$emit("close");
        });
      }
    },
    getEmailModels() {
      emailService.getEmails().then((response) => {
        if (response.status) {
          this.models = response.data.datas;
        }
      });
    },
    getStatus() {
      settingsService.getStatus().then((response) => {
        this.status = response.data;
      });
    },
    getTrigger() {
      emailService.getEmailTriggerById(this.trigger).then((response) => {
        this.form.model = response.data.model;
        this.form.status = response.data.status;
        if (response.data.target != 5 && response.data.target != 6) {
          this.form.target = 1e3;
        } else {
          if (response.data.to_current_user === 1) {
            this.form.target = 1e3;
          } else {
            this.form.target = response.data.target;
          }
        }
      });
    }
  }
};
const _hoisted_1$4 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_2$3 = { class: "tw-mb-4" };
const _hoisted_3$3 = { class: "tw-flex tw-items-center" };
const _hoisted_4$3 = { value: "-1" };
const _hoisted_5$3 = ["value"];
const _hoisted_6$3 = {
  key: 1,
  class: "tw-text-red-600"
};
const _hoisted_7$3 = {
  key: 0,
  class: "tw-mb-2 tw-text-red-600"
};
const _hoisted_8$3 = { class: "tw-text-red-600" };
const _hoisted_9$3 = { class: "tw-mb-4" };
const _hoisted_10$3 = { value: "-1" };
const _hoisted_11$3 = ["value"];
const _hoisted_12$3 = {
  key: 0,
  class: "tw-mb-2 tw-text-red-600"
};
const _hoisted_13$3 = { class: "tw-text-red-600" };
const _hoisted_14$2 = { class: "tw-mb-4" };
const _hoisted_15$2 = { value: "-1" };
const _hoisted_16$1 = { value: "5" };
const _hoisted_17$1 = { value: "6" };
const _hoisted_18$1 = { value: "1000" };
const _hoisted_19$1 = {
  key: 0,
  class: "tw-mb-2 tw-text-red-600"
};
const _hoisted_20$1 = { class: "tw-text-red-600" };
const _hoisted_21$1 = { class: "tw-mb-2 tw-flex tw-items-center tw-justify-between" };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_modal = resolveComponent("modal");
  return openBlock(), createBlock(_component_modal, {
    name: "modalAddTrigger" + $props.triggerAction,
    class: normalizeClass("placement-" + $props.placement + " " + $props.classes),
    transition: "nice-modal-fade",
    width: "600px",
    delay: 100,
    adaptive: true,
    clickToClose: false,
    onClosed: $options.beforeClose,
    onBeforeOpen: $options.beforeOpen
  }, {
    default: withCtx(() => [
      createBaseVNode("div", _hoisted_1$4, [
        createBaseVNode("h4", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EMAIL_ADDTRIGGER")), 1),
        createBaseVNode("button", {
          class: "tw-cursor-pointer tw-bg-transparent",
          onClick: _cache[0] || (_cache[0] = withModifiers(($event) => _ctx.$emit("close"), ["prevent"]))
        }, _cache[6] || (_cache[6] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
        ]))
      ]),
      createBaseVNode("div", null, [
        createBaseVNode("div", _hoisted_2$3, [
          createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERMODEL")) + "* :", 1),
          createBaseVNode("div", _hoisted_3$3, [
            $data.models.length > 0 ? withDirectives((openBlock(), createElementBlock("select", {
              key: 0,
              id: "modal-email-model",
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.form.model = $event),
              class: normalizeClass(["tw-w-full", { "is-invalid": $data.errors.model }])
            }, [
              createBaseVNode("option", _hoisted_4$3, toDisplayString(_ctx.translate("COM_EMUNDUS_PLEASE_SELECT")), 1),
              (openBlock(true), createElementBlock(Fragment, null, renderList($data.models, (model, index) => {
                return openBlock(), createElementBlock("option", {
                  key: index,
                  value: model.id
                }, toDisplayString(model.subject), 9, _hoisted_5$3);
              }), 128))
            ], 2)), [
              [vModelSelect, $data.form.model]
            ]) : (openBlock(), createElementBlock("p", _hoisted_6$3, toDisplayString(_ctx.translate("COM_EMUNDUS_ADD_TRIGGER_MISSING_EMAIL_MODELS")), 1))
          ]),
          $data.errors.model ? (openBlock(), createElementBlock("span", _hoisted_7$3, [
            createBaseVNode("span", _hoisted_8$3, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERMODEL_REQUIRED")), 1)
          ])) : createCommentVNode("", true)
        ]),
        createBaseVNode("div", _hoisted_9$3, [
          createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS")) + "* :", 1),
          withDirectives(createBaseVNode("select", {
            id: "modal-status-trigger",
            "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.form.status = $event),
            class: normalizeClass(["tw-w-full", { "is-invalid": $data.errors.status }])
          }, [
            createBaseVNode("option", _hoisted_10$3, toDisplayString(_ctx.translate("COM_EMUNDUS_PLEASE_SELECT")), 1),
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.status, (statu, index) => {
              return openBlock(), createElementBlock("option", {
                key: index,
                value: statu.step
              }, toDisplayString(statu.value), 9, _hoisted_11$3);
            }), 128))
          ], 2), [
            [vModelSelect, $data.form.status]
          ]),
          $data.errors.status ? (openBlock(), createElementBlock("span", _hoisted_12$3, [
            createBaseVNode("span", _hoisted_13$3, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS_REQUIRED")), 1)
          ])) : createCommentVNode("", true)
        ]),
        createBaseVNode("div", _hoisted_14$2, [
          createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET")) + "* :", 1),
          withDirectives(createBaseVNode("select", {
            id: "modal-recipient",
            "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.form.target = $event),
            class: normalizeClass(["tw-w-full", { "is-invalid": $data.errors.target }])
          }, [
            createBaseVNode("option", _hoisted_15$2, toDisplayString(_ctx.translate("COM_EMUNDUS_PLEASE_SELECT")), 1),
            createBaseVNode("option", _hoisted_16$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS")), 1),
            createBaseVNode("option", _hoisted_17$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS")), 1),
            createBaseVNode("option", _hoisted_18$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_CANDIDATES")), 1)
          ], 2), [
            [vModelSelect, $data.form.target]
          ]),
          $data.errors.target ? (openBlock(), createElementBlock("span", _hoisted_19$1, [
            createBaseVNode("span", _hoisted_20$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET_REQUIRED")), 1)
          ])) : createCommentVNode("", true)
        ])
      ]),
      createBaseVNode("div", _hoisted_21$1, [
        createBaseVNode("button", {
          type: "button",
          class: "tw-btn-cancel !tw-w-auto",
          onClick: _cache[4] || (_cache[4] = withModifiers(($event) => _ctx.$emit("close"), ["prevent"]))
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_RETOUR")), 1),
        createBaseVNode("button", {
          type: "button",
          class: "tw-btn-primary !tw-w-auto",
          onClick: _cache[5] || (_cache[5] = withModifiers(($event) => $options.createTrigger(), ["prevent"]))
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER")), 1)
      ])
    ]),
    _: 1
  }, 8, ["name", "class", "onClosed", "onBeforeOpen"]);
}
const ModalAddTrigger = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__scopeId", "data-v-8d704e3d"]]);
const _sfc_main$3 = {
  name: "addEmail",
  components: { ModalAddTrigger },
  props: {
    funnelCategorie: String,
    prog: Number
  },
  data() {
    return {
      triggers: [],
      triggerSelected: null,
      manual_trigger: 0,
      candidate_trigger: 0,
      loading: false,
      showModalAddTriggerApplicant: false,
      showModalAddTriggerManual: false
    };
  },
  methods: {
    editTrigger(trigger) {
      this.triggerSelected = trigger.trigger_id;
      this.manual_trigger += 1;
      this.candidate_trigger += 1;
      setTimeout(() => {
        if (trigger.candidate == 1) {
          this.showModalAddTriggerApplicant = true;
        } else {
          this.showModalAddTriggerManual = true;
        }
      }, 500);
    },
    removeTrigger(trigger) {
      axios({
        method: "post",
        url: "index.php?option=com_emundus&controller=email&task=removetrigger",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        data: qs.stringify({
          tid: trigger
        })
      }).then(() => {
        this.getTriggers();
      });
    },
    getTriggers() {
      axios.get("index.php?option=com_emundus&controller=email&task=gettriggersbyprogram&pid=" + this.prog).then((response) => {
        this.triggers = response.data.data;
        this.loading = false;
      });
    },
    triggerUsersWithProfile(trigger) {
      if (trigger.profile !== null) {
        return trigger.users;
      }
      return [];
    },
    triggerUsersNoProfile(trigger) {
      if (trigger.profile === null && trigger.users.length > 0) {
        return trigger.users;
      }
      return [];
    }
  },
  computed: {
    applicantTriggers() {
      return this.triggers.filter((trigger) => trigger.candidate == 1);
    },
    manualTriggers() {
      return this.triggers.filter((trigger) => trigger.manual == 1);
    }
  },
  created() {
    this.loading = true;
    this.getTriggers();
  }
};
const _hoisted_1$3 = { id: "candidate-action" };
const _hoisted_2$2 = { class: "tw-flex tw-items-center" };
const _hoisted_3$2 = { class: "tw-flex tw-w-full tw-items-start tw-items-center tw-justify-between" };
const _hoisted_4$2 = { class: "tw-mb-2" };
const _hoisted_5$2 = { class: "tw-mb-2 tw-mt-2" };
const _hoisted_6$2 = { style: { "font-weight": "bold" } };
const _hoisted_7$2 = { key: 0 };
const _hoisted_8$2 = { key: 0 };
const _hoisted_9$2 = { key: 1 };
const _hoisted_10$2 = { key: 2 };
const _hoisted_11$2 = { class: "em-flex-end tw-flex tw-items-center" };
const _hoisted_12$2 = ["onClick"];
const _hoisted_13$2 = ["onClick", "title"];
const _hoisted_14$1 = { id: "manager-action" };
const _hoisted_15$1 = { class: "tw-flex tw-items-center" };
const _hoisted_16 = { class: "tw-mt-4" };
const _hoisted_17 = { class: "tw-flex tw-w-full tw-items-start tw-items-center tw-justify-between" };
const _hoisted_18 = { class: "tw-mb-2" };
const _hoisted_19 = { class: "tw-mb-2 tw-mt-2" };
const _hoisted_20 = { style: { "font-weight": "bold" } };
const _hoisted_21 = { key: 0 };
const _hoisted_22 = { key: 0 };
const _hoisted_23 = { key: 1 };
const _hoisted_24 = { key: 2 };
const _hoisted_25 = { class: "em-flex-end tw-flex tw-items-center" };
const _hoisted_26 = ["onClick"];
const _hoisted_27 = ["onClick"];
const _hoisted_28 = {
  key: 2,
  class: "em-page-loader"
};
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_ModalAddTrigger = resolveComponent("ModalAddTrigger");
  return openBlock(), createElementBlock("div", null, [
    $data.showModalAddTriggerApplicant ? (openBlock(), createBlock(_component_ModalAddTrigger, {
      prog: this.prog,
      trigger: this.triggerSelected,
      triggerAction: "candidate",
      key: "candidate" + $data.candidate_trigger,
      classes: "tw-rounded tw-shadow-modal tw-p-4",
      placement: "center",
      onUpdateTriggers: $options.getTriggers,
      onClose: _cache[0] || (_cache[0] = ($event) => $data.showModalAddTriggerApplicant = false)
    }, null, 8, ["prog", "trigger", "onUpdateTriggers"])) : $data.showModalAddTriggerManual ? (openBlock(), createBlock(_component_ModalAddTrigger, {
      prog: this.prog,
      trigger: this.triggerSelected,
      triggerAction: "manual",
      key: "manual-" + $data.manual_trigger,
      classes: "tw-rounded tw-shadow-modal tw-p-4",
      placement: "center",
      onUpdateTriggers: $options.getTriggers,
      onClose: _cache[1] || (_cache[1] = ($event) => $data.showModalAddTriggerManual = false)
    }, null, 8, ["prog", "trigger", "onUpdateTriggers"])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_1$3, [
      createBaseVNode("div", _hoisted_2$2, [
        createBaseVNode("h4", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CANDIDATE_ACTION")), 1)
      ]),
      createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_THE_CANDIDATE_DESCRIPTION")), 1),
      createBaseVNode("button", {
        class: "tw-btn-primary tw-mt-2 tw-w-auto",
        onClick: _cache[2] || (_cache[2] = ($event) => {
          $data.showModalAddTriggerApplicant = true;
          $data.triggerSelected = null;
        })
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EMAIL_ADDTRIGGER")), 1),
      createVNode(TransitionGroup, {
        name: "slide-down",
        type: "transition",
        class: "em-grid-2 tw-m-4",
        style: { "margin-left": "0" }
      }, {
        default: withCtx(() => [
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.applicantTriggers, (trigger) => {
            return openBlock(), createElementBlock("div", {
              key: trigger.trigger_id,
              class: "em-email-card mt-4"
            }, [
              createBaseVNode("div", _hoisted_3$2, [
                createBaseVNode("div", null, [
                  createBaseVNode("span", _hoisted_4$2, toDisplayString(trigger.subject), 1),
                  createBaseVNode("div", _hoisted_5$2, [
                    createBaseVNode("span", _hoisted_6$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET")) + " : ", 1),
                    (openBlock(true), createElementBlock(Fragment, null, renderList($options.triggerUsersWithProfile(trigger), (user, index) => {
                      return openBlock(), createElementBlock("span", {
                        key: "user_" + index
                      }, [
                        createTextVNode(toDisplayString(user.firstname) + " " + toDisplayString(user.lastname) + " ", 1),
                        index != Object.keys(trigger.users).length - 1 ? (openBlock(), createElementBlock("span", _hoisted_7$2, ", ")) : createCommentVNode("", true)
                      ]);
                    }), 128)),
                    trigger.users.length == 0 && trigger.profile != 5 && trigger.profile != 6 ? (openBlock(), createElementBlock("span", _hoisted_8$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_THE_CANDIDATE")), 1)) : createCommentVNode("", true),
                    trigger.profile == 5 ? (openBlock(), createElementBlock("span", _hoisted_9$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS")), 1)) : createCommentVNode("", true),
                    trigger.profile == 6 ? (openBlock(), createElementBlock("span", _hoisted_10$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS")), 1)) : createCommentVNode("", true)
                  ]),
                  createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS")) + " " + toDisplayString(trigger.status), 1)
                ]),
                createBaseVNode("div", _hoisted_11$2, [
                  createBaseVNode("a", {
                    class: "tw-mr-2 tw-cursor-pointer",
                    onClick: ($event) => $options.editTrigger(trigger)
                  }, _cache[4] || (_cache[4] = [
                    createBaseVNode("span", { class: "material-symbols-outlined" }, "edit", -1)
                  ]), 8, _hoisted_12$2),
                  createBaseVNode("a", {
                    class: "tw-cursor-pointer",
                    onClick: ($event) => $options.removeTrigger(trigger.trigger_id),
                    title: _ctx.removeTrig
                  }, _cache[5] || (_cache[5] = [
                    createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "close", -1)
                  ]), 8, _hoisted_13$2)
                ])
              ])
            ]);
          }), 128))
        ]),
        _: 1
      })
    ]),
    createBaseVNode("div", _hoisted_14$1, [
      createBaseVNode("div", _hoisted_15$1, [
        createBaseVNode("h4", _hoisted_16, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_MANAGER_ACTION")), 1)
      ]),
      createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_MANUAL_DESCRIPTION")), 1),
      createBaseVNode("button", {
        class: "tw-btn-primary tw-mt-2 tw-w-auto",
        onClick: _cache[3] || (_cache[3] = ($event) => {
          $data.showModalAddTriggerManual = true;
          $data.triggerSelected = null;
        })
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EMAIL_ADDTRIGGER")), 1),
      createVNode(TransitionGroup, {
        name: "slide-down",
        type: "transition",
        class: "em-grid-2 tw-m-4",
        style: { "margin-left": "0" }
      }, {
        default: withCtx(() => [
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.manualTriggers, (trigger) => {
            return openBlock(), createElementBlock("div", {
              key: trigger.trigger_id,
              class: "em-email-card mt-4"
            }, [
              createBaseVNode("div", _hoisted_17, [
                createBaseVNode("div", null, [
                  createBaseVNode("span", _hoisted_18, toDisplayString(trigger.subject), 1),
                  createBaseVNode("div", _hoisted_19, [
                    createBaseVNode("span", _hoisted_20, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET")) + " : ", 1),
                    (openBlock(true), createElementBlock(Fragment, null, renderList($options.triggerUsersNoProfile(trigger), (user, index) => {
                      return openBlock(), createElementBlock("span", {
                        key: "user_manual_" + index
                      }, [
                        createTextVNode(toDisplayString(user.firstname) + " " + toDisplayString(user.lastname) + " ", 1),
                        index != Object.keys(trigger.users).length - 1 ? (openBlock(), createElementBlock("span", _hoisted_21, ", ")) : createCommentVNode("", true)
                      ]);
                    }), 128)),
                    trigger.users.length == 0 && trigger.profile != 5 && trigger.profile != 6 ? (openBlock(), createElementBlock("span", _hoisted_22, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_THE_CANDIDATE")), 1)) : createCommentVNode("", true),
                    trigger.profile == 5 ? (openBlock(), createElementBlock("span", _hoisted_23, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS")), 1)) : createCommentVNode("", true),
                    trigger.profile == 6 ? (openBlock(), createElementBlock("span", _hoisted_24, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS")), 1)) : createCommentVNode("", true)
                  ]),
                  createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS")) + " " + toDisplayString(trigger.status), 1)
                ]),
                createBaseVNode("div", _hoisted_25, [
                  createBaseVNode("a", {
                    class: "tw-mr-2 tw-cursor-pointer",
                    onClick: ($event) => $options.editTrigger(trigger)
                  }, _cache[6] || (_cache[6] = [
                    createBaseVNode("span", { class: "material-symbols-outlined" }, "edit", -1)
                  ]), 8, _hoisted_26),
                  createBaseVNode("a", {
                    class: "tw-cursor-pointer",
                    onClick: ($event) => $options.removeTrigger(trigger.trigger_id)
                  }, _cache[7] || (_cache[7] = [
                    createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "close", -1)
                  ]), 8, _hoisted_27)
                ])
              ])
            ]);
          }), 128))
        ]),
        _: 1
      })
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_28)) : createCommentVNode("", true)
  ]);
}
const addEmail = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-8ab8b30a"]]);
const _sfc_main$2 = {
  name: "CampaignMore",
  props: {
    campaignId: {
      type: Number,
      required: true
    },
    defaultFormUrl: {
      type: String,
      required: false,
      default: ""
    }
  },
  data() {
    return {
      formUrl: ""
    };
  },
  created() {
    if (this.defaultFormUrl.length > 0) {
      this.formUrl = this.defaultFormUrl;
    } else {
      this.getFormUrl();
    }
  },
  methods: {
    getFormUrl() {
      campaignService.getCampaignMoreFormUrl(this.campaignId).then((response) => {
        if (response.status) {
          this.formUrl = response.data;
        }
      }).catch((error) => {
        console.error(error);
      });
    }
  }
};
const _hoisted_1$2 = ["src"];
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", null, [
    $data.formUrl.length > 0 ? (openBlock(), createElementBlock("iframe", {
      key: 0,
      id: "more-form-iframe",
      src: $data.formUrl,
      width: "100%"
    }, null, 8, _hoisted_1$2)) : createCommentVNode("", true)
  ]);
}
const campaignMore = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-6abd6b51"]]);
const _sfc_main$1 = {
  name: "CampaignSteps",
  components: {
    DatePicker
  },
  props: {
    campaignId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      steps: [],
      actualLanguage: null
    };
  },
  created() {
    this.actualLanguage = useGlobalStore().getShortLang;
    this.getCampaignSteps(this.campaignId);
  },
  methods: {
    getCampaignSteps() {
      workflowService.getCampaignSteps(this.campaignId).then((response) => {
        this.steps = response.data;
      }).catch((error) => {
        console.log(error);
      });
    },
    saveCampaignSteps() {
      this.steps.forEach((step) => {
        step.start_date = step.start_date === null || step.start_date === "" || step.start_date === "0000-00-00 00:00:00" ? "0000-00-00 00:00:00" : this.formatDate(new Date(step.start_date), "YYYY-MM-DD HH:mm:ss");
        step.end_date = step.end_date === null || step.end_date === "" || step.end_date === "0000-00-00 00:00:00" ? "0000-00-00 00:00:00" : this.formatDate(new Date(step.end_date), "YYYY-MM-DD HH:mm:ss");
      });
      workflowService.saveCampaignSteps(this.campaignId, this.steps).then((response) => {
        if (response.status) {
          this.goNext();
        }
      }).catch((error) => {
        console.log(error);
      });
    },
    goNext() {
      this.$emit("nextSection");
    },
    formatDate(date, format = "YYYY-MM-DD HH:mm:ss") {
      if (date == "" || date == null || date == "0000-00-00 00:00:00") {
        return "0000-00-00 00:00:00";
      }
      let year = date.getFullYear();
      let month = (1 + date.getMonth()).toString().padStart(2, "0");
      let day = date.getDate().toString().padStart(2, "0");
      let hours = date.getHours().toString().padStart(2, "0");
      let minutes = date.getMinutes().toString().padStart(2, "0");
      let seconds = date.getSeconds().toString().padStart(2, "0");
      return format.replace("YYYY", year).replace("MM", month).replace("DD", day).replace("HH", hours).replace("mm", minutes).replace("ss", seconds);
    }
  }
};
const _hoisted_1$1 = { id: "campaign-steps" };
const _hoisted_2$1 = ["id"];
const _hoisted_3$1 = { class: "tw-my-2 tw-flex tw-items-center" };
const _hoisted_4$1 = { class: "em-toggle" };
const _hoisted_5$1 = ["id", "name", "onUpdate:modelValue"];
const _hoisted_6$1 = ["for"];
const _hoisted_7$1 = {
  key: 0,
  class: "tw-flex tw-w-full tw-flex-row tw-gap-2"
};
const _hoisted_8$1 = { class: "tw-w-full" };
const _hoisted_9$1 = ["for"];
const _hoisted_10$1 = ["value", "id", "name"];
const _hoisted_11$1 = { class: "tw-w-full" };
const _hoisted_12$1 = ["for"];
const _hoisted_13$1 = ["value", "id", "name"];
const _hoisted_14 = { key: 0 };
const _hoisted_15 = { class: "tw-flex tw-flex-row tw-justify-end" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_DatePicker = resolveComponent("DatePicker");
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    (openBlock(true), createElementBlock(Fragment, null, renderList($data.steps, (step) => {
      return openBlock(), createElementBlock("div", {
        key: step.id,
        id: "campaign-step-" + _ctx.id + "-wrapper",
        class: "tw-my-4"
      }, [
        createBaseVNode("h3", null, toDisplayString(step.label), 1),
        createBaseVNode("div", _hoisted_3$1, [
          createBaseVNode("div", _hoisted_4$1, [
            withDirectives(createBaseVNode("input", {
              type: "checkbox",
              "true-value": "1",
              "false-value": "0",
              class: "em-toggle-check tw-mt-2",
              id: "step_" + step.id + "_infinite",
              name: "step_" + step.id + "_infinite",
              "onUpdate:modelValue": ($event) => step.infinite = $event
            }, null, 8, _hoisted_5$1), [
              [vModelCheckbox, step.infinite]
            ]),
            _cache[2] || (_cache[2] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
            _cache[3] || (_cache[3] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
          ]),
          createBaseVNode("span", {
            for: "step_" + step.id + "_infinite",
            class: "tw-ml-2 tw-flex tw-items-center"
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_CAMPAIGNS_INFINITE_STEP")), 9, _hoisted_6$1)
        ]),
        step.infinite == 0 ? (openBlock(), createElementBlock("div", _hoisted_7$1, [
          createBaseVNode("div", _hoisted_8$1, [
            createBaseVNode("label", {
              for: "start_date_" + step.id
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_CAMPAIGN_STEP_START_DATE")), 9, _hoisted_9$1),
            createVNode(_component_DatePicker, {
              id: "campaign_step_" + step.id + "_start_date",
              modelValue: step.start_date,
              "onUpdate:modelValue": ($event) => step.start_date = $event,
              keepVisibleOnInput: true,
              "time-accuracy": 2,
              mode: "dateTime",
              is24hr: "",
              "hide-time-header": "",
              "title-position": "left",
              "input-debounce": 500,
              popover: { visibility: "focus" },
              locale: $data.actualLanguage
            }, {
              default: withCtx(({ inputValue, inputEvents }) => [
                createBaseVNode("input", mergeProps({ value: inputValue }, toHandlers(inputEvents, true), {
                  class: "form-control fabrikinput tw-mt-2 tw-w-full",
                  id: "start_date_" + step.id + "_input",
                  name: "start_date_" + step.id
                }), null, 16, _hoisted_10$1)
              ]),
              _: 2
            }, 1032, ["id", "modelValue", "onUpdate:modelValue", "locale"])
          ]),
          createBaseVNode("div", _hoisted_11$1, [
            createBaseVNode("label", {
              for: "end_date_" + step.id
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_CAMPAIGN_STEP_END_DATE")), 9, _hoisted_12$1),
            createVNode(_component_DatePicker, {
              id: "campaign_step_" + step.id + "_end_date",
              modelValue: step.end_date,
              "onUpdate:modelValue": ($event) => step.end_date = $event,
              keepVisibleOnInput: true,
              "time-accuracy": 2,
              mode: "dateTime",
              is24hr: "",
              "hide-time-header": "",
              "title-position": "left",
              "input-debounce": 500,
              popover: { visibility: "focus" },
              locale: $data.actualLanguage
            }, {
              default: withCtx(({ inputValue, inputEvents }) => [
                createBaseVNode("input", mergeProps({ value: inputValue }, toHandlers(inputEvents, true), {
                  class: "form-control fabrikinput tw-mt-2 tw-w-full",
                  id: "end_date_" + step.id + "_input",
                  name: "end_date_" + step.id
                }), null, 16, _hoisted_13$1)
              ]),
              _: 2
            }, 1032, ["id", "modelValue", "onUpdate:modelValue", "locale"])
          ])
        ])) : createCommentVNode("", true)
      ], 8, _hoisted_2$1);
    }), 128)),
    $data.steps.length < 1 ? (openBlock(), createElementBlock("div", _hoisted_14, [
      createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_CAMPAIGN_NO_STEPS_FOUND")), 1)
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_15, [
      $data.steps.length > 0 ? (openBlock(), createElementBlock("button", {
        key: 0,
        class: "tw-btn tw-btn-primary tw-mt-4",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.saveCampaignSteps && $options.saveCampaignSteps(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER")), 1)) : (openBlock(), createElementBlock("button", {
        key: 1,
        class: "tw-btn tw-btn-primary tw-mt-4",
        onClick: _cache[1] || (_cache[1] = (...args) => $options.goNext && $options.goNext(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CONTINUE")), 1))
    ])
  ]);
}
const campaignSteps = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "CampaignEdition",
  components: {
    Tabs,
    History,
    AddDocumentsDropfiles,
    addCampaign,
    addEmail,
    campaignMore,
    campaignSteps
  },
  props: {
    index: Number
  },
  mixins: [mixin],
  data: () => ({
    campaignId: 0,
    actualLanguage: "",
    manyLanguages: 0,
    prid: "",
    tabs: [
      {
        id: 1,
        code: "global",
        name: "COM_EMUNDUS_GLOBAL_INFORMATIONS",
        description: "COM_EMUNDUS_GLOBAL_INFORMATIONS_DESC",
        icon: "info",
        active: true,
        displayed: true
      },
      {
        id: 2,
        code: "more",
        name: "COM_EMUNDUS_CAMPAIGN_MORE",
        description: "COM_EMUNDUS_CAMPAIGN_MORE_DESC",
        icon: "note_stack",
        active: false,
        displayed: false
      },
      {
        id: 7,
        code: "steps",
        name: "COM_EMUNDUS_CAMPAIGN_STEPS",
        description: "",
        icon: "description",
        active: false,
        displayed: true
      },
      {
        id: 3,
        code: "attachments",
        name: "COM_EMUNDUS_DOCUMENTS_CAMPAIGNS",
        description: "COM_EMUNDUS_DOCUMENTS_CAMPAIGNS_DESC",
        icon: "description",
        active: false,
        displayed: true
      },
      {
        id: 5,
        code: "emails",
        name: "COM_EMUNDUS_EMAILS",
        description: "COM_EMUNDUS_EMAILS_DESC",
        icon: "mail",
        active: false,
        displayed: true
      },
      {
        id: 6,
        code: "history",
        name: "COM_EMUNDUS_GLOBAL_HISTORY",
        description: "",
        icon: "history",
        active: false,
        displayed: true
      }
    ],
    selectedMenu: "addCampaign",
    formReload: 0,
    prog: 0,
    loading: true,
    closeSubmenu: true,
    profileId: null,
    profiles: [],
    campaignsByProgram: [],
    form: {},
    campaignMoreFormUrl: "",
    program: {
      id: 0,
      code: "",
      label: "",
      notes: "",
      programmes: [],
      tmpl_badge: "",
      published: 0,
      apply_online: 0,
      synthesis: "",
      tmpl_trombinoscope: ""
    }
  }),
  created() {
    const globalStore = useGlobalStore();
    this.campaignId = parseInt(globalStore.datas.campaignId.value);
    this.actualLanguage = globalStore.getCurrentLang;
    this.manyLanguages = globalStore.hasManyLanguages;
    this.getProgram();
    this.getCampaignMoreForm().then((response) => {
      if (globalStore.datas.tabs) {
        let tabsToDisplay = globalStore.datas.tabs.value.split(",");
        this.tabs.forEach((tab) => {
          if (tab.code !== "more") {
            tab.displayed = tabsToDisplay.includes(tab.code);
            if (!tab.displayed) {
              tab.active = false;
            }
          }
        });
      }
      let firstTabDisplayed = this.tabs.find((tab) => tab.displayed);
      if (firstTabDisplayed) {
        firstTabDisplayed.active = true;
      }
      if (!this.tabs[0].displayed) {
        campaignService.getCampaignById(this.campaignId).then((response2) => {
          this.form = response2.data.campaign;
          this.initInformations(this.form);
        });
      }
      this.loading = false;
    });
    if (this.actualLanguage === "en") {
      this.langue = 1;
    }
  },
  methods: {
    getCampaignMoreForm() {
      return new Promise((resolve, reject) => {
        campaignService.getCampaignMoreFormUrl(this.campaignId).then((response) => {
          if (response.status && response.data.length > 0) {
            const globalStore = useGlobalStore();
            if (globalStore.datas.tabs) {
              let tabsToDisplay = globalStore.datas.tabs.value.split(",");
              if (tabsToDisplay.includes("more")) {
                this.tabs[1].displayed = true;
              }
            } else {
              this.tabs[1].displayed = true;
            }
            this.campaignMoreFormUrl = response.data;
          }
          resolve();
        }).catch((error) => {
          reject(error);
          console.error(error);
        });
      });
    },
    initInformations(campaign) {
      this.form.label = campaign.label;
      this.form.profile_id = campaign.profile_id;
      this.form.program_id = campaign.progid;
      this.initDates(campaign);
      formService.getPublishedForms().then((response) => {
        this.profiles = response.data.data;
        if (this.form.profile_id == null) {
          this.profiles.length != 0 ? this.profileId = this.profiles[0].id : this.profileId = null;
          if (this.profileId != null) {
            this.formReload += 1;
          }
        } else {
          this.formReload += 1;
          this.profileId = this.form.profile_id;
        }
        this.loading = false;
        let cookie = this.getCookie("campaign_" + this.campaignId + "_menu");
        if (cookie) {
          this.menuHighlight = cookie;
          document.cookie = "campaign_" + this.campaignId + "_menu =; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
        }
      });
    },
    updateHeader(value) {
      this.form.label = value.label[this.actualLanguage];
      this.initDates(value);
    },
    initDates(campaign) {
      this.form.start_date = campaign.start_date;
      this.form.end_date = campaign.end_date;
      let currentLanguage = useGlobalStore().getCurrentLang;
      if (currentLanguage === "" || currentLanguage === void 0) {
        currentLanguage = "fr-FR";
      }
      const dateOptions = { dateStyle: "long", timeStyle: "short" };
      const startDate = new Date(campaign.start_date);
      this.form.start_date = new Intl.DateTimeFormat(currentLanguage, dateOptions).format(startDate);
      if (this.form.end_date === "0000-00-00 00:00:00") {
        this.form.end_date = null;
      } else {
        const endDate = new Date(campaign.end_date);
        this.form.end_date = new Intl.DateTimeFormat(currentLanguage, dateOptions).format(endDate);
      }
    },
    getProgram() {
      campaignService.getProgrammeByCampaignID(this.campaignId).then((response) => {
        this.program = response.data;
        if (this.program.id) {
          programmeService.getCampaignsByProgram(this.program.id).then((resp) => {
            this.campaignsByProgram = resp.campaigns;
          });
        }
      }).catch((e) => {
        console.error(e);
      });
    },
    setProfileId(prid) {
      this.profileId = prid;
    },
    next() {
      let index = this.tabs.findIndex((tab) => tab.active);
      if (index < this.tabs.length - 1) {
        this.tabs[index].active = false;
        if (this.tabs[index + 1].displayed) {
          this.tabs[index + 1].active = true;
        } else {
          this.tabs[index + 2].active = true;
        }
      }
    },
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },
    getCookie(cname) {
      var name = cname + "=";
      var decodedCookie = decodeURIComponent(document.cookie);
      var ca = decodedCookie.split(";");
      for (let c of ca) {
        while (c.charAt(0) == " ") {
          c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
          return c.substring(name.length, c.length);
        }
      }
      return "";
    }
  },
  computed: {
    getProfileId() {
      return Number(this.profileId);
    },
    selectedMenuItem() {
      return this.tabs.find((tab) => tab.active);
    }
  }
};
const _hoisted_1 = { id: "edit-campaign" };
const _hoisted_2 = { class: "tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card" };
const _hoisted_3 = { class: "group-hover:tw-underline" };
const _hoisted_4 = { class: "tw-mt-4 tw-flex tw-items-center" };
const _hoisted_5 = ["innerHTML"];
const _hoisted_6 = {
  id: "campaign-info-line",
  class: "tw-mb-8 tw-flex tw-items-center"
};
const _hoisted_7 = { style: { "color": "var(--em-profile-color)", "font-weight": "700 !important" } };
const _hoisted_8 = { class: "tw-relative tw-rounded-coordinator tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card" };
const _hoisted_9 = {
  key: 0,
  class: "warning-message-program mb-1"
};
const _hoisted_10 = { class: "flex flex-row tw-text-red-600" };
const _hoisted_11 = {
  key: 0,
  class: "em-pl-16 tw-mb-8 tw-mt-2"
};
const _hoisted_12 = {
  key: 0,
  class: "tw-mt-4 tw-flex tw-items-center tw-justify-end"
};
const _hoisted_13 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Tabs = resolveComponent("Tabs");
  const _component_add_campaign = resolveComponent("add-campaign");
  const _component_campaign_more = resolveComponent("campaign-more");
  const _component_campaign_steps = resolveComponent("campaign-steps");
  const _component_add_documents_dropfiles = resolveComponent("add-documents-dropfiles");
  const _component_add_email = resolveComponent("add-email");
  const _component_History = resolveComponent("History");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    _cache[5] || (_cache[5] = createBaseVNode("div", { class: "em-w-custom" }, null, -1)),
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("div", null, [
        createBaseVNode("button", {
          type: "button",
          class: "tw-group tw-flex tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular",
          onClick: _cache[0] || (_cache[0] = ($event) => $options.redirectJRoute("index.php?option=com_emundus&view=campaigns"))
        }, [
          _cache[2] || (_cache[2] = createBaseVNode("span", { class: "material-symbols-outlined tw-mr-1 tw-text-link-regular" }, "navigate_before", -1)),
          createBaseVNode("span", _hoisted_3, toDisplayString(_ctx.translate("BACK")), 1)
        ]),
        createBaseVNode("div", _hoisted_4, [
          createBaseVNode("h1", null, toDisplayString(_ctx.translate($options.selectedMenuItem.name)), 1)
        ]),
        createBaseVNode("p", {
          innerHTML: _ctx.translate($options.selectedMenuItem.description)
        }, null, 8, _hoisted_5),
        _cache[4] || (_cache[4] = createBaseVNode("hr", null, null, -1)),
        createBaseVNode("div", _hoisted_6, [
          createBaseVNode("p", null, [
            createBaseVNode("b", _hoisted_7, toDisplayString(_ctx.form.label), 1),
            createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FROM")) + " ", 1),
            createBaseVNode("strong", null, toDisplayString(_ctx.form.start_date), 1),
            createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TO")) + " ", 1),
            createBaseVNode("strong", null, toDisplayString(_ctx.form.end_date), 1)
          ])
        ]),
        withDirectives(createVNode(_component_Tabs, {
          tabs: _ctx.tabs,
          classes: "tw-overflow-auto tw-flex tw-items-center tw-gap-2 tw-ml-7"
        }, null, 8, ["tabs"]), [
          [vShow, _ctx.profileId]
        ]),
        createBaseVNode("div", _hoisted_8, [
          $options.selectedMenuItem.id === 5 ? (openBlock(), createElementBlock("div", _hoisted_9, [
            createBaseVNode("p", _hoisted_10, [
              _cache[3] || (_cache[3] = createBaseVNode("span", { class: "material-symbols-outlined tw-mr-2 tw-text-red-600" }, "warning_amber", -1)),
              createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_WARNING")), 1)
            ]),
            _ctx.campaignsByProgram.length > 0 ? (openBlock(), createElementBlock("ul", _hoisted_11, [
              (openBlock(true), createElementBlock(Fragment, null, renderList(_ctx.campaignsByProgram, (campaign) => {
                return openBlock(), createElementBlock("li", {
                  key: "camp_progs_" + campaign.id
                }, toDisplayString(campaign.label), 1);
              }), 128))
            ])) : createCommentVNode("", true)
          ])) : createCommentVNode("", true),
          createVNode(Transition, { name: "fade" }, {
            default: withCtx(() => [
              $options.selectedMenuItem.id === 1 && _ctx.campaignId !== "" ? (openBlock(), createBlock(_component_add_campaign, {
                key: 0,
                campaign: _ctx.campaignId,
                coordinatorAccess: true,
                actualLanguage: _ctx.actualLanguage,
                manyLanguages: _ctx.manyLanguages,
                onNextSection: $options.next,
                onGetInformations: $options.initInformations,
                onUpdateHeader: $options.updateHeader
              }, null, 8, ["campaign", "actualLanguage", "manyLanguages", "onNextSection", "onGetInformations", "onUpdateHeader"])) : $options.selectedMenuItem.id === 2 && _ctx.campaignId !== "" ? (openBlock(), createBlock(_component_campaign_more, {
                key: 1,
                campaignId: _ctx.campaignId,
                defaultFormUrl: _ctx.campaignMoreFormUrl
              }, null, 8, ["campaignId", "defaultFormUrl"])) : $options.selectedMenuItem.name === "COM_EMUNDUS_CAMPAIGN_STEPS" && _ctx.campaignId !== "" ? (openBlock(), createBlock(_component_campaign_steps, {
                key: 2,
                campaignId: _ctx.campaignId,
                onNextSection: $options.next
              }, null, 8, ["campaignId", "onNextSection"])) : $options.selectedMenuItem.id === 3 ? (openBlock(), createBlock(_component_add_documents_dropfiles, {
                key: 3,
                funnelCategorie: $options.selectedMenuItem.label,
                profileId: $options.getProfileId,
                campaignId: _ctx.campaignId,
                langue: _ctx.actualLanguage,
                manyLanguages: _ctx.manyLanguages
              }, null, 8, ["funnelCategorie", "profileId", "campaignId", "langue", "manyLanguages"])) : $options.selectedMenuItem.id === 5 && _ctx.program.id != 0 ? (openBlock(), createBlock(_component_add_email, {
                key: 4,
                prog: Number(_ctx.program.id)
              }, null, 8, ["prog"])) : $options.selectedMenuItem.id === 6 ? (openBlock(), createBlock(_component_History, {
                key: 5,
                extension: "com_emundus.campaign",
                itemId: _ctx.campaignId
              }, null, 8, ["itemId"])) : createCommentVNode("", true)
            ]),
            _: 1
          })
        ])
      ]),
      ["addDocumentsDropfiles"].includes(_ctx.selectedMenu) ? (openBlock(), createElementBlock("div", _hoisted_12, [
        createBaseVNode("button", {
          type: "button",
          class: "mb-4 tw-btn-primary tw-w-auto",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.next && $options.next(...args))
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER")), 1)
      ])) : createCommentVNode("", true),
      _ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_13)) : createCommentVNode("", true)
    ])
  ]);
}
const CampaignEdition = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-e0da353e"]]);
export {
  CampaignEdition as default
};
