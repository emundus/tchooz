import { _ as _export_sfc, S as Swal, r as resolveComponent, o as openBlock, e as createElementBlock, d as createBaseVNode, c as createBlock, b as withCtx, t as toDisplayString, j as createVNode, T as TransitionGroup, g as Fragment, h as renderList, f as createCommentVNode, m as mergeProps, M as Modal, s as settingsService, k as withModifiers, w as withDirectives, i as normalizeClass, l as vModelSelect, a as axios, p as createTextVNode, u as useGlobalStore, q as mixin, v as vShow, x as Transition } from "./app_emundus.js";
import { c as campaignService } from "./campaign.js";
import { F as FormBuilderPreviewForm, f as formService } from "./FormBuilderPreviewForm.js";
import { a as addCampaign, p as programmeService } from "./addCampaign.js";
import { v as vueDropzone, T as Tabs } from "./Tabs.js";
import { V as VueDraggableNext, q as qs } from "./index.js";
import { e as emailService } from "./email.js";
import { S as Skeleton } from "./Skeleton.js";
import History from "./History.js";
import "./vue-multiselect.esm.js";
const _imports_0$1 = "/media/com_emundus_vue/assets/pdf.png";
const _imports_0 = "/media/com_emundus_vue/assets/doc.png";
const _imports_2 = "/media/com_emundus_vue/assets/excel.png";
const _imports_3 = "/media/com_emundus_vue/assets/image.png";
const _imports_4 = "/media/com_emundus_vue/assets/zip.png";
const _imports_5 = "/media/com_emundus_vue/assets/svg.png";
const addDocumentsDropfiles_vue_vue_type_style_index_0_scoped_6272a5a1_lang = "";
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
const _sfc_main$6 = {
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
      if (bytes === 0)
        return "0 Bytes";
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
const _hoisted_1$6 = { id: "documents-dropfiles" };
const _hoisted_2$5 = { class: "w-form" };
const _hoisted_3$4 = {
  class: "dropzone-custom-content",
  id: "dropzone-message"
};
const _hoisted_4$4 = ["id"];
const _hoisted_5$4 = ["onClick"];
const _hoisted_6$4 = { class: "tw-flex tw-items-center tw-w-full tw-justify-center" };
const _hoisted_7$4 = ["onClick"];
const _hoisted_8$4 = {
  key: 0,
  src: _imports_0$1,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_9$4 = {
  key: 1,
  src: _imports_0,
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
const _hoisted_12$3 = {
  key: 4,
  src: _imports_4,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_13$2 = {
  key: 5,
  src: _imports_5,
  class: "em-filetype-icon",
  alt: "filetype"
};
const _hoisted_14$2 = { class: "tw-mt-2" };
const _hoisted_15$2 = { class: "em-overflow-ellipsis em-max-width-250 tw-mr-1" };
const _hoisted_16$2 = { id: "itemDocSize" };
const _hoisted_17$2 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_vue_dropzone = resolveComponent("vue-dropzone");
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$6, [
    createBaseVNode("div", _hoisted_2$5, [
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
            class: "tw-grid tw-grid-cols-3 tw-gap-6 tw-w-full handle"
          }, {
            default: withCtx(() => [
              (openBlock(true), createElementBlock(Fragment, null, renderList($data.documents, (document2, indexDoc) => {
                return openBlock(), createElementBlock("div", {
                  id: "itemDoc" + document2.id,
                  key: document2.id,
                  class: "em-document-dropzone-card tw-cursor-grab handle tw-mr-2"
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
                      class: "tw-flex tw-flex-col tw-items-center em-edit-cursor",
                      onClick: ($event) => $options.editName(document2)
                    }, [
                      document2.ext === "pdf" ? (openBlock(), createElementBlock("img", _hoisted_8$4)) : ["docx", "doc", "odf"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_9$4)) : ["xls", "xlsx", "csv"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_10$4)) : ["png", "gif", "jpg", "jpeg"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_11$4)) : ["zip", "rar"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_12$3)) : ["svg"].includes(document2.ext) ? (openBlock(), createElementBlock("img", _hoisted_13$2)) : createCommentVNode("", true),
                      createBaseVNode("div", _hoisted_14$2, [
                        createBaseVNode("span", _hoisted_15$2, toDisplayString(document2.title), 1)
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
const AddDocumentsDropfiles = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6], ["__scopeId", "data-v-6272a5a1"]]);
const ModalAddTrigger_vue_vue_type_style_index_0_scoped_5f2a6404_lang = "";
const _sfc_main$5 = {
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
const _hoisted_1$5 = { class: "tw-flex tw-items-center tw-justify-between tw-mb-4" };
const _hoisted_2$4 = { class: "tw-mb-4" };
const _hoisted_3$3 = { class: "tw-flex tw-items-center" };
const _hoisted_4$3 = { value: "-1" };
const _hoisted_5$3 = ["value"];
const _hoisted_6$3 = {
  key: 1,
  class: "tw-text-red-600"
};
const _hoisted_7$3 = {
  key: 0,
  class: "tw-text-red-600 tw-mb-2"
};
const _hoisted_8$3 = { class: "tw-text-red-600" };
const _hoisted_9$3 = { class: "tw-mb-4" };
const _hoisted_10$3 = { value: "-1" };
const _hoisted_11$3 = ["value"];
const _hoisted_12$2 = {
  key: 0,
  class: "tw-text-red-600 tw-mb-2"
};
const _hoisted_13$1 = { class: "tw-text-red-600" };
const _hoisted_14$1 = { class: "tw-mb-4" };
const _hoisted_15$1 = { value: "-1" };
const _hoisted_16$1 = { value: "5" };
const _hoisted_17$1 = { value: "6" };
const _hoisted_18$1 = { value: "1000" };
const _hoisted_19$1 = {
  key: 0,
  class: "tw-text-red-600 tw-mb-2"
};
const _hoisted_20$1 = { class: "tw-text-red-600" };
const _hoisted_21$1 = { class: "tw-flex tw-items-center tw-justify-between tw-mb-2" };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
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
      createBaseVNode("div", _hoisted_1$5, [
        createBaseVNode("h4", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EMAIL_ADDTRIGGER")), 1),
        createBaseVNode("button", {
          class: "tw-cursor-pointer tw-bg-transparent",
          onClick: _cache[0] || (_cache[0] = withModifiers(($event) => _ctx.$emit("close"), ["prevent"]))
        }, _cache[6] || (_cache[6] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
        ]))
      ]),
      createBaseVNode("div", null, [
        createBaseVNode("div", _hoisted_2$4, [
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
          $data.errors.status ? (openBlock(), createElementBlock("span", _hoisted_12$2, [
            createBaseVNode("span", _hoisted_13$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS_REQUIRED")), 1)
          ])) : createCommentVNode("", true)
        ]),
        createBaseVNode("div", _hoisted_14$1, [
          createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET")) + "* :", 1),
          withDirectives(createBaseVNode("select", {
            id: "modal-recipient",
            "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.form.target = $event),
            class: normalizeClass(["tw-w-full", { "is-invalid": $data.errors.target }])
          }, [
            createBaseVNode("option", _hoisted_15$1, toDisplayString(_ctx.translate("COM_EMUNDUS_PLEASE_SELECT")), 1),
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
const ModalAddTrigger = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5], ["__scopeId", "data-v-5f2a6404"]]);
const addEmail_vue_vue_type_style_index_0_scoped_bb742359_lang = "";
const _sfc_main$4 = {
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
const _hoisted_1$4 = { id: "candidate-action" };
const _hoisted_2$3 = { class: "tw-flex tw-items-center" };
const _hoisted_3$2 = { class: "tw-flex tw-items-center tw-items-start tw-justify-between tw-w-full" };
const _hoisted_4$2 = { class: "tw-mb-2" };
const _hoisted_5$2 = { class: "tw-mt-2 tw-mb-2" };
const _hoisted_6$2 = { style: { "font-weight": "bold" } };
const _hoisted_7$2 = { key: 0 };
const _hoisted_8$2 = { key: 0 };
const _hoisted_9$2 = { key: 1 };
const _hoisted_10$2 = { key: 2 };
const _hoisted_11$2 = { class: "tw-flex tw-items-center em-flex-end" };
const _hoisted_12$1 = ["onClick"];
const _hoisted_13 = ["onClick", "title"];
const _hoisted_14 = { id: "manager-action" };
const _hoisted_15 = { class: "tw-flex tw-items-center" };
const _hoisted_16 = { class: "tw-mt-4" };
const _hoisted_17 = { class: "tw-flex tw-items-center tw-items-start tw-justify-between tw-w-full" };
const _hoisted_18 = { class: "tw-mb-2" };
const _hoisted_19 = { class: "tw-mt-2 tw-mb-2" };
const _hoisted_20 = { style: { "font-weight": "bold" } };
const _hoisted_21 = { key: 0 };
const _hoisted_22 = { key: 0 };
const _hoisted_23 = { key: 1 };
const _hoisted_24 = { key: 2 };
const _hoisted_25 = { class: "tw-flex tw-items-center em-flex-end" };
const _hoisted_26 = ["onClick"];
const _hoisted_27 = ["onClick"];
const _hoisted_28 = {
  key: 2,
  class: "em-page-loader"
};
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
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
      onClose: _cache[0] || (_cache[0] = ($event) => {
        $data.showModalAddTriggerApplicant = false;
      })
    }, null, 8, ["prog", "trigger", "onUpdateTriggers"])) : $data.showModalAddTriggerManual ? (openBlock(), createBlock(_component_ModalAddTrigger, {
      prog: this.prog,
      trigger: this.triggerSelected,
      triggerAction: "manual",
      key: "manual-" + $data.manual_trigger,
      classes: "tw-rounded tw-shadow-modal tw-p-4",
      placement: "center",
      onUpdateTriggers: $options.getTriggers,
      onClose: _cache[1] || (_cache[1] = ($event) => {
        $data.showModalAddTriggerManual = false;
      })
    }, null, 8, ["prog", "trigger", "onUpdateTriggers"])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_1$4, [
      createBaseVNode("div", _hoisted_2$3, [
        createBaseVNode("h4", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CANDIDATE_ACTION")), 1)
      ]),
      createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_THE_CANDIDATE_DESCRIPTION")), 1),
      createBaseVNode("button", {
        class: "tw-btn-primary tw-w-auto tw-mt-2",
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
                  ]), 8, _hoisted_12$1),
                  createBaseVNode("a", {
                    class: "tw-cursor-pointer",
                    onClick: ($event) => $options.removeTrigger(trigger.trigger_id),
                    title: _ctx.removeTrig
                  }, _cache[5] || (_cache[5] = [
                    createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "close", -1)
                  ]), 8, _hoisted_13)
                ])
              ])
            ]);
          }), 128))
        ]),
        _: 1
      })
    ]),
    createBaseVNode("div", _hoisted_14, [
      createBaseVNode("div", _hoisted_15, [
        createBaseVNode("h4", _hoisted_16, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_MANAGER_ACTION")), 1)
      ]),
      createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_MANUAL_DESCRIPTION")), 1),
      createBaseVNode("button", {
        class: "tw-btn-primary tw-w-auto tw-mt-2",
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
                    class: "tw-cursor-pointer tw-mr-2",
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
const addEmail = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__scopeId", "data-v-bb742359"]]);
const FormBuilderPreviewAttachments_vue_vue_type_style_index_0_lang = "";
const _sfc_main$3 = {
  name: "FormBuilderPreviewAttachments",
  components: { Skeleton },
  props: {
    document_id: {
      type: Number,
      required: true
    },
    document_label: {
      type: String,
      default: ""
    }
  },
  data() {
    return {
      loading: false
    };
  },
  created() {
  },
  methods: {}
};
const _hoisted_1$3 = {
  key: 0,
  class: "tw-text-center"
};
const _hoisted_2$2 = { class: "tw-text-xs tw-w-full tw-mt-4" };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_skeleton = resolveComponent("skeleton");
  return openBlock(), createElementBlock("div", {
    id: "form-builder-preview-attachment",
    class: normalizeClass(["tw-h-full tw-w-full", { loading: $data.loading }])
  }, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_1$3, [
      _cache[0] || (_cache[0] = createBaseVNode("img", {
        src: _imports_0,
        class: "em-m-center",
        style: { "width": "50px" },
        alt: "filetype"
      }, null, -1)),
      createBaseVNode("p", _hoisted_2$2, toDisplayString($props.document_label), 1)
    ])) : (openBlock(), createBlock(_component_skeleton, {
      key: 1,
      height: "100%",
      width: "100%"
    }))
  ], 2);
}
const FormBuilderPreviewAttachments = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3]]);
const addFormulaire_vue_vue_type_style_index_0_scoped_79c001dc_lang = "";
const _sfc_main$2 = {
  name: "addFormulaire",
  props: {
    profileId: String,
    campaignId: Number,
    profiles: Array,
    formulaireEmundus: Number,
    visibility: Number
  },
  components: {
    FormBuilderPreviewAttachments,
    FormBuilderPreviewForm
  },
  data() {
    return {
      selectedProfileId: 0,
      EmitIndex: "0",
      formList: [],
      documentsList: [],
      loading: false,
      form: {
        label: "Nouveau formulaire",
        description: "",
        published: 1
      }
    };
  },
  created() {
    this.selectedProfileId = this.profileId;
    this.getForms(this.selectedProfileId);
    this.getDocuments(this.selectedProfileId);
  },
  methods: {
    getEmitIndex(value) {
      this.EmitIndex = value;
    },
    getForms(profile_id) {
      this.loading = true;
      formService.getFormsByProfileId(profile_id).then((response) => {
        this.formList = response.data.data;
        this.loading = false;
      }).catch((e) => {
        console.log(e);
      });
    },
    getDocuments(profile_id) {
      formService.getDocuments(profile_id).then((response) => {
        if (response.status && response.data) {
          this.documentsList = response.data;
        } else {
          this.documentsList = [];
        }
      });
    },
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },
    addNewForm() {
      this.loading = true;
      formService.createForm({ body: JSON.stringify(this.form) }).then((response) => {
        this.loading = false;
        this.$props.profileId = response.data;
        window.location.href = "/" + response.redirect;
      }).catch((error) => {
        console.log(error);
      });
    },
    updateProfileCampaign() {
      campaignService.updateProfile(this.selectedProfileId, this.campaignId).then(() => {
        this.getForms(this.selectedProfileId);
        this.getDocuments(this.selectedProfileId);
        this.$emit("profileId", this.selectedProfileId);
      });
    },
    formbuilder(index) {
      index = 0;
      this.redirectJRoute("index.php?option=com_emundus&view=form&layout=formbuilder&prid=" + this.selectedProfileId + "&index=" + index + "&cid=" + this.campaignId);
    }
  },
  computed: {
    fabrikFormList() {
      return this.formList.filter((form) => form.link.includes("fabrik"));
    }
  }
};
const _hoisted_1$2 = { id: "addFormulaireContent" };
const _hoisted_2$1 = { class: "tw-mb-1 tw-mt-4 em-text-color" };
const _hoisted_3$1 = { class: "tw-mb-6 tw-flex tw-flex-col tw-items-start" };
const _hoisted_4$1 = ["value"];
const _hoisted_5$1 = {
  id: "formPagesReview",
  class: "tw-flex tw-items-center em-flex-wrap"
};
const _hoisted_6$1 = ["title"];
const _hoisted_7$1 = {
  key: 0,
  id: "formAttachments"
};
const _hoisted_8$1 = { class: "em-mt-12" };
const _hoisted_9$1 = { class: "tw-flex tw-items-center" };
const _hoisted_10$1 = ["title"];
const _hoisted_11$1 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_preview_form = resolveComponent("form-builder-preview-form");
  const _component_form_builder_preview_attachments = resolveComponent("form-builder-preview-attachments");
  return openBlock(), createElementBlock("div", _hoisted_1$2, [
    createBaseVNode("div", _hoisted_2$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CHOOSE_FORM")) + " :", 1),
    createBaseVNode("div", _hoisted_3$1, [
      withDirectives(createBaseVNode("select", {
        id: "select_profile",
        class: "!tw-mb-1",
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.selectedProfileId = $event),
        onChange: _cache[1] || (_cache[1] = (...args) => $options.updateProfileCampaign && $options.updateProfileCampaign(...args))
      }, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($props.profiles, (profile) => {
          return openBlock(), createElementBlock("option", {
            key: profile.id,
            value: profile.id
          }, toDisplayString(profile.form_label), 9, _hoisted_4$1);
        }), 128))
      ], 544), [
        [vModelSelect, $data.selectedProfileId]
      ]),
      createBaseVNode("a", {
        id: "editCurrentForm",
        class: "tw-cursor-pointer em-profile-color em-text-underline",
        onClick: _cache[2] || (_cache[2] = (...args) => $options.formbuilder && $options.formbuilder(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EDIT_FORM")), 1)
    ]),
    createBaseVNode("a", {
      id: "addNewForm",
      class: "tw-cursor-pointer em-profile-color tw-underline",
      onClick: _cache[3] || (_cache[3] = (...args) => $options.addNewForm && $options.addNewForm(...args))
    }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_NO_FORM_FOUND_ADD_FORM")), 1),
    _cache[4] || (_cache[4] = createBaseVNode("hr", null, null, -1)),
    createBaseVNode("h5", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_PAGES_PREVIEW")), 1),
    createBaseVNode("div", _hoisted_5$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.fabrikFormList, (form) => {
        return openBlock(), createElementBlock("div", {
          key: form.id,
          class: "card-wrapper em-mr-32",
          title: form.label
        }, [
          createVNode(_component_form_builder_preview_form, {
            form_id: Number(form.id),
            form_label: form.label,
            class: "card em-shadow-cards model-preview"
          }, null, 8, ["form_id", "form_label"])
        ], 8, _hoisted_6$1);
      }), 128))
    ]),
    $data.documentsList.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_7$1, [
      createBaseVNode("h5", _hoisted_8$1, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_ATTACHMENTS_PREVIEW")), 1),
      createBaseVNode("div", _hoisted_9$1, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.documentsList, (document2) => {
          return openBlock(), createElementBlock("div", {
            key: document2.id,
            class: "card-wrapper em-mr-32",
            title: document2.label
          }, [
            createVNode(_component_form_builder_preview_attachments, {
              document_id: Number(document2.id),
              document_label: document2.label,
              class: "card em-shadow-cards model-preview"
            }, null, 8, ["document_id", "document_label"])
          ], 8, _hoisted_10$1);
        }), 128))
      ])
    ])) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_11$1)) : createCommentVNode("", true)
  ]);
}
const addFormulaire = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-79c001dc"]]);
const CampaignMore_vue_vue_type_style_index_0_scoped_a04b9d4b_lang = "";
const _sfc_main$1 = {
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
const _hoisted_1$1 = ["src"];
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", null, [
    $data.formUrl.length > 0 ? (openBlock(), createElementBlock("iframe", {
      key: 0,
      id: "more-form-iframe",
      src: $data.formUrl,
      width: "100%"
    }, null, 8, _hoisted_1$1)) : createCommentVNode("", true)
  ]);
}
const campaignMore = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-a04b9d4b"]]);
const CampaignEdition_vue_vue_type_style_index_0_scoped_adf674f4_lang = "";
const _sfc_main = {
  name: "CampaignEdition",
  components: {
    Tabs,
    History,
    AddDocumentsDropfiles,
    addCampaign,
    addFormulaire,
    addEmail,
    campaignMore
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
        id: 3,
        code: "attachments",
        name: "COM_EMUNDUS_DOCUMENTS_CAMPAIGNS",
        description: "COM_EMUNDUS_DOCUMENTS_CAMPAIGNS_DESC",
        icon: "description",
        active: false,
        displayed: true
      },
      {
        id: 4,
        code: "form",
        name: "COM_EMUNDUS_FORM_CAMPAIGN",
        description: "COM_EMUNDUS_FORM_CAMPAIGN_DESC",
        icon: "format_list_bulleted",
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
const _hoisted_2 = { class: "tw-ml-2 tw-text-neutral-900" };
const _hoisted_3 = { class: "tw-flex tw-items-center tw-mt-4" };
const _hoisted_4 = ["innerHTML"];
const _hoisted_5 = {
  id: "campaign-info-line",
  class: "tw-flex tw-items-center tw-mb-8"
};
const _hoisted_6 = { style: { "color": "var(--em-profile-color)", "font-weight": "700 !important" } };
const _hoisted_7 = { class: "tw-w-full tw-rounded-coordinator tw-p-6 tw-bg-white tw-border tw-border-neutral-300 tw-relative" };
const _hoisted_8 = {
  key: 0,
  class: "warning-message-program mb-1"
};
const _hoisted_9 = { class: "tw-text-red-600 flex flex-row" };
const _hoisted_10 = {
  key: 0,
  class: "tw-mt-2 tw-mb-8 em-pl-16"
};
const _hoisted_11 = {
  key: 0,
  class: "tw-flex tw-items-center tw-justify-between tw-float-right"
};
const _hoisted_12 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Tabs = resolveComponent("Tabs");
  const _component_add_campaign = resolveComponent("add-campaign");
  const _component_campaign_more = resolveComponent("campaign-more");
  const _component_addFormulaire = resolveComponent("addFormulaire");
  const _component_add_documents_dropfiles = resolveComponent("add-documents-dropfiles");
  const _component_add_email = resolveComponent("add-email");
  const _component_History = resolveComponent("History");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    _cache[5] || (_cache[5] = createBaseVNode("div", { class: "em-w-custom" }, null, -1)),
    createBaseVNode("div", null, [
      createBaseVNode("div", null, [
        createBaseVNode("div", {
          class: "tw-flex tw-items-center tw-cursor-pointer",
          onClick: _cache[0] || (_cache[0] = ($event) => $options.redirectJRoute("index.php?option=com_emundus&view=campaigns"))
        }, [
          _cache[2] || (_cache[2] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-600" }, "navigate_before", -1)),
          createBaseVNode("span", _hoisted_2, toDisplayString(_ctx.translate("BACK")), 1)
        ]),
        createBaseVNode("div", _hoisted_3, [
          createBaseVNode("h1", null, toDisplayString(_ctx.translate($options.selectedMenuItem.name)), 1)
        ]),
        createBaseVNode("p", {
          innerHTML: _ctx.translate($options.selectedMenuItem.description)
        }, null, 8, _hoisted_4),
        _cache[4] || (_cache[4] = createBaseVNode("hr", null, null, -1)),
        createBaseVNode("div", _hoisted_5, [
          createBaseVNode("p", null, [
            createBaseVNode("b", _hoisted_6, toDisplayString(_ctx.form.label), 1),
            createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FROM")) + " ", 1),
            createBaseVNode("strong", null, toDisplayString(_ctx.form.start_date), 1),
            createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TO")) + " ", 1),
            createBaseVNode("strong", null, toDisplayString(_ctx.form.end_date), 1)
          ])
        ]),
        withDirectives(createVNode(_component_Tabs, {
          tabs: _ctx.tabs,
          classes: "tw-overflow-x-scroll tw-flex tw-items-center tw-gap-2 tw-ml-7"
        }, null, 8, ["tabs"]), [
          [vShow, _ctx.profileId]
        ]),
        createBaseVNode("div", _hoisted_7, [
          $options.selectedMenuItem.id === 5 ? (openBlock(), createElementBlock("div", _hoisted_8, [
            createBaseVNode("p", _hoisted_9, [
              _cache[3] || (_cache[3] = createBaseVNode("span", { class: "material-symbols-outlined tw-mr-2 tw-text-red-600" }, "warning_amber", -1)),
              createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_WARNING")), 1)
            ]),
            _ctx.campaignsByProgram.length > 0 ? (openBlock(), createElementBlock("ul", _hoisted_10, [
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
              }, null, 8, ["campaignId", "defaultFormUrl"])) : $options.selectedMenuItem.id === 4 ? (openBlock(), createBlock(_component_addFormulaire, {
                profileId: _ctx.profileId,
                campaignId: _ctx.campaignId,
                profiles: _ctx.profiles,
                key: _ctx.formReload,
                onProfileId: $options.setProfileId,
                visibility: null
              }, null, 8, ["profileId", "campaignId", "profiles", "onProfileId"])) : $options.selectedMenuItem.id === 3 ? (openBlock(), createBlock(_component_add_documents_dropfiles, {
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
      ["addDocumentsDropfiles", "addFormulaire"].includes(_ctx.selectedMenu) ? (openBlock(), createElementBlock("div", _hoisted_11, [
        createBaseVNode("button", {
          type: "button",
          class: "tw-btn-primary tw-w-auto mb-4",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.next && $options.next(...args))
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER")), 1)
      ])) : createCommentVNode("", true),
      _ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_12)) : createCommentVNode("", true)
    ])
  ]);
}
const CampaignEdition = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-adf674f4"]]);
export {
  CampaignEdition as default
};
