import { s as script, V as V32 } from "./vue-multiselect.esm.js";
import { I as IncrementalSelect } from "./IncrementalSelect.js";
import { F as FetchClient, _ as _export_sfc, q as mixin, u as useGlobalStore, s as settingsService, r as resolveComponent, o as openBlock, e as createElementBlock, d as createBaseVNode, k as withModifiers, t as toDisplayString, p as createTextVNode, w as withDirectives, B as vModelText, i as normalizeClass, f as createCommentVNode, c as createBlock, v as vShow, j as createVNode } from "./app_emundus.js";
import { e as emailService } from "./email.js";
const client = new FetchClient("messages");
const messagesService = {
  async getAllAttachments() {
    try {
      return await client.get("getallattachments");
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getAllDocumentsLetters() {
    try {
      return await client.get("getalldocumentsletters");
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
};
const addEmail_vue_vue_type_style_index_0_scoped_0d908019_lang = "";
const _sfc_main = {
  name: "addEmail",
  mixins: [mixin],
  components: {
    IncrementalSelect,
    Multiselect: script,
    TipTapEditor: V32
  },
  props: {
    mode: {
      type: String,
      default: "create"
    }
  },
  data: () => ({
    email: 0,
    actualLanguage: "",
    langue: 0,
    dynamicComponent: false,
    displayAdvancedParameters: false,
    categories: [],
    enableTip: false,
    searchTerm: "",
    selectall: false,
    tags: [],
    documents: [],
    selectedTags: [],
    selectedCandidateAttachments: [],
    selectedCategory: 0,
    form: {
      lbl: "",
      subject: "",
      name: "",
      emailfrom: "",
      message: "",
      type: 2,
      category: "",
      published: 1
    },
    errors: {
      subject: false,
      message: false,
      button: false
    },
    submitted: false,
    loading: false,
    displayButtonField: false,
    selectedReceiversCC: [],
    selectedReceiversBCC: [],
    selectedLetterAttachments: [],
    receivers_cc: [],
    receivers_bcc: [],
    attached_letters: [],
    action_tags: [],
    candidate_attachments: [],
    email_sender: "",
    editor_ready: false,
    editorPlugins: ["history", "link", "image", "bold", "italic", "underline", "left", "center", "right", "h1", "h2", "ul"],
    suggestions: [],
    medias: []
  }),
  created() {
    const globalStore = useGlobalStore();
    this.loading = true;
    this.prepareEditor();
    this.getEmailSender();
    this.getAllAttachments();
    this.getAllTags();
    this.getAllDocumentLetter();
    this.actualLanguage = globalStore.getShortLang;
    emailService.getEmailCategories().then((response) => {
      this.categories = response.data;
      this.email = globalStore.getDatas.email.value;
      if (typeof this.email !== "undefined" && this.email !== 0 && this.email !== "") {
        this.getEmailById(this.email);
      } else {
        this.dynamicComponent = true;
        this.loading = false;
      }
    }).catch((e) => {
      console.log(e);
    });
    setTimeout(() => {
      this.enableVariablesTip();
    }, 2e3);
  },
  mounted() {
    if (this.actualLanguage === "en") {
      this.langue = 1;
    }
  },
  methods: {
    prepareEditor() {
      settingsService.getVariables().then((response) => {
        this.suggestions = response.data;
        settingsService.getMedia().then((response2) => {
          this.medias = response2.data;
          this.editor_ready = true;
        });
      });
    },
    getMedia() {
      settingsService.getMedia().then((response) => {
        this.medias = response.data;
      });
    },
    getEmailById() {
      emailService.getEmailById(this.email).then((resp) => {
        if (resp.data === false || resp.status == 0) {
          this.runError(void 0, resp.msg);
          return;
        }
        this.form = resp.data.email;
        this.dynamicComponent = true;
        this.selectedLetterAttachments = resp.data.letter_attachment ? resp.data.letter_attachment : [];
        this.selectedCandidateAttachments = resp.data.candidate_attachment ? resp.data.candidate_attachment : [];
        this.selectedTags = resp.data.tags ? resp.data.tags : [];
        if (resp.data.receivers !== null && resp.data.receivers !== void 0 && resp.data.receivers !== "") {
          this.setEmailReceivers(resp.data.receivers);
        }
        if (this.form.button !== "" && this.form.button !== null && this.form.button !== void 0) {
          this.displayButtonField = true;
        }
        this.loading = false;
      }).catch((e) => {
        console.log(e);
        this.runError(void 0, e.data.msg);
      });
    },
    setEmailReceivers(receivers) {
      let receiver_cc = [];
      let receiver_bcc = [];
      for (let index = 0; index < receivers.length; index++) {
        receiver_cc[index] = {};
        receiver_bcc[index] = {};
        if (receivers[index].type === "receiver_cc_email" || receivers[index].type === "receiver_cc_fabrik") {
          receiver_cc[index]["id"] = receivers[index].id;
          receiver_cc[index]["email"] = receivers[index].receivers;
        } else if (receivers[index].type === "receiver_bcc_email" || receivers[index].type === "receiver_bcc_fabrik") {
          receiver_bcc[index]["id"] = receivers[index].id;
          receiver_bcc[index]["email"] = receivers[index].receivers;
        }
      }
      const cc_filtered = receiver_cc.filter((el) => {
        return el["id"] !== null && el["id"] !== void 0;
      });
      const bcc_filtered = receiver_bcc.filter((el) => {
        return el["id"] !== null && el["id"] !== void 0;
      });
      this.selectedReceiversCC = cc_filtered;
      this.selectedReceiversBCC = bcc_filtered;
    },
    displayAdvanced() {
      this.displayAdvancedParameters = !this.displayAdvancedParameters;
    },
    addNewCC(newCC) {
      const tag = {
        email: newCC,
        id: newCC.substring(0, 2) + Math.floor(Math.random() * 1e7)
      };
      this.receivers_cc.push(tag);
      this.selectedReceiversCC.push(tag);
    },
    /// add new BCC
    addNewBCC(newBCC) {
      const tag = {
        email: newBCC,
        id: newBCC.substring(0, 2) + Math.floor(Math.random() * 1e7)
      };
      this.receivers_bcc.push(tag);
      this.selectedReceiversBCC.push(tag);
    },
    getEmailSender() {
      settingsService.getEmailSender().then((response) => {
        this.email_sender = response.data;
      });
    },
    submit() {
      this.errors = {
        subject: false,
        message: false
      };
      if (this.form.subject == "") {
        this.errors.subject = true;
        return 0;
      }
      if (this.form.message == "") {
        this.errors.message = true;
        return 0;
      }
      if (this.displayButtonField && this.form.button == "") {
        this.errors.button = true;
        return 0;
      }
      this.submitted = true;
      if (this.email !== "") {
        emailService.updateEmail(this.email, {
          body: this.form,
          selectedReceiversCC: this.selectedReceiversCC,
          selectedReceiversBCC: this.selectedReceiversBCC,
          selectedLetterAttachments: this.selectedLetterAttachments,
          selectedCandidateAttachments: this.selectedCandidateAttachments,
          selectedTags: this.selectedTags
        }).then(() => {
          history.back();
        }).catch((error) => {
          console.log(error);
        });
      } else {
        emailService.createEmail({
          body: this.form,
          selectedReceiversCC: this.selectedReceiversCC,
          selectedReceiversBCC: this.selectedReceiversBCC,
          selectedLetterAttachments: this.selectedLetterAttachments,
          selectedCandidateAttachments: this.selectedCandidateAttachments,
          selectedTags: this.selectedTags
        }).then(() => {
          this.redirectJRoute("index.php?option=com_emundus&view=emails");
        }).catch((error) => {
          console.log(error);
        });
      }
    },
    onSearchCategory(value) {
      this.form.category = value;
    },
    enableVariablesTip() {
      if (!this.enableTip) {
        this.enableTip = true;
        this.tipToast(
          this.translate("COM_EMUNDUS_ONBOARD_VARIABLESTIP") + " <strong style='font-size: 16px'>/</strong>"
        );
      }
    },
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },
    /// get all tags
    getAllTags: function() {
      settingsService.getTags().then((response) => {
        this.action_tags = response.data;
      }).catch((error) => {
        console.log(error);
      });
    },
    getAllDocumentLetter: function() {
      messagesService.getAllDocumentsLetters().then((response) => {
        this.attached_letters = response.documents;
      }).catch((error) => {
        console.log(error);
      });
    },
    getAllAttachments: function() {
      messagesService.getAllAttachments().then((response) => {
        this.candidate_attachments = response.attachments;
      }).catch((error) => {
        console.log(error);
      });
    },
    updateCategorySelectedValue(category) {
      if (category.label) {
        this.form.category = category.label;
      } else {
        this.selectedCategory = null;
        this.form.category = "";
      }
    }
  },
  computed: {
    categoriesList() {
      return this.categories.map((category, index) => {
        return {
          id: index + 1,
          label: category
        };
      });
    },
    incSelectDefaultValue() {
      let defaultValue = null;
      if (this.form && this.form.category) {
        this.categories.forEach((category, index) => {
          if (category === this.form.category) {
            defaultValue = index + 1;
          }
        });
      }
      return defaultValue;
    }
  }
};
const _hoisted_1 = { class: "emails__add-email" };
const _hoisted_2 = { class: "tw-mb-4" };
const _hoisted_3 = { class: "tw-mb-2" };
const _hoisted_4 = { class: "tw-text-red-600 tw-mb-2" };
const _hoisted_5 = { class: "tw-mb-4" };
const _hoisted_6 = {
  key: 0,
  class: "tw-text-red-600 tw-mt-1"
};
const _hoisted_7 = { class: "tw-text-red-600" };
const _hoisted_8 = { class: "tw-mb-4" };
const _hoisted_9 = { class: "tw-mt-2" };
const _hoisted_10 = {
  href: "/export-tags",
  class: "em-main-500-color em-hover-main-600 em-text-underline",
  target: "_blank"
};
const _hoisted_11 = {
  key: 1,
  class: "tw-text-red-600 tw-mt-1"
};
const _hoisted_12 = { class: "tw-text-red-600" };
const _hoisted_13 = {
  key: 0,
  class: "tw-mb-4"
};
const _hoisted_14 = { class: "tw-mt-1 tw-mb-1 tw-text-xs tw-text-neutral-700" };
const _hoisted_15 = {
  key: 0,
  class: "tw-text-red-600 tw-mt-1"
};
const _hoisted_16 = { class: "tw-text-red-600" };
const _hoisted_17 = { class: "form-group" };
const _hoisted_18 = { class: "em-container-accordeon" };
const _hoisted_19 = { class: "tw-flex tw-items-center tw-gap-1 tw-justify-between" };
const _hoisted_20 = ["title"];
const _hoisted_21 = ["title"];
const _hoisted_22 = {
  key: 0,
  id: "email-advanced-parameters",
  class: "tw-mt-4"
};
const _hoisted_23 = { class: "form-group tw-mb-4" };
const _hoisted_24 = { class: "tw-mt-2 tw-text-neutral-700" };
const _hoisted_25 = { class: "form-group tw-mb-4" };
const _hoisted_26 = { class: "form-group tw-mb-4" };
const _hoisted_27 = { class: "tw-text-xs tw-text-neutral-700" };
const _hoisted_28 = {
  class: "form-group tw-mb-4",
  id: "receivers_cc"
};
const _hoisted_29 = {
  class: "form-group tw-mb-4",
  id: "receivers_bcc"
};
const _hoisted_30 = {
  key: 0,
  class: "form-group tw-mb-4",
  id: "attached_letters"
};
const _hoisted_31 = {
  key: 1,
  class: "form-group tw-mb-4"
};
const _hoisted_32 = { class: "form-group tw-mb-4" };
const _hoisted_33 = { class: "tw-flex tw-justify-between tw-mt-4" };
const _hoisted_34 = {
  type: "button",
  class: "tw-btn-cancel !tw-w-auto",
  onclick: "history.back()"
};
const _hoisted_35 = {
  type: "submit",
  class: "tw-btn-primary !tw-w-auto"
};
const _hoisted_36 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_tip_tap_editor = resolveComponent("tip-tap-editor");
  const _component_incremental_select = resolveComponent("incremental-select");
  const _component_multiselect = resolveComponent("multiselect");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", null, [
      createBaseVNode("form", {
        onSubmit: _cache[13] || (_cache[13] = withModifiers((...args) => $options.submit && $options.submit(...args), ["prevent"])),
        class: "fabrikForm emundus-form"
      }, [
        createBaseVNode("div", null, [
          createBaseVNode("div", _hoisted_2, [
            createBaseVNode("h1", _hoisted_3, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EMAIL")), 1),
            createBaseVNode("span", _hoisted_4, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE")), 1)
          ]),
          createBaseVNode("div", null, [
            createBaseVNode("div", _hoisted_5, [
              createBaseVNode("label", null, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_NAME")) + " ", 1),
                _cache[14] || (_cache[14] = createBaseVNode("span", { style: { "color": "#e5283b" } }, "*", -1))
              ]),
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: normalizeClass(["tw-w-full tw-mt-2", { "is-invalid !tw-border-red-600": _ctx.errors.subject }]),
                "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => _ctx.form.subject = $event)
              }, null, 2), [
                [vModelText, _ctx.form.subject]
              ]),
              _ctx.errors.subject ? (openBlock(), createElementBlock("p", _hoisted_6, [
                createBaseVNode("span", _hoisted_7, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SUBJECT_REQUIRED")), 1)
              ])) : createCommentVNode("", true)
            ]),
            createBaseVNode("div", _hoisted_8, [
              createBaseVNode("label", null, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_BODY")) + " ", 1),
                _cache[15] || (_cache[15] = createBaseVNode("span", { style: { "color": "#e5283b" } }, "*", -1))
              ]),
              _ctx.editor_ready ? (openBlock(), createBlock(_component_tip_tap_editor, {
                key: 0,
                modelValue: _ctx.form.message,
                "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => _ctx.form.message = $event),
                "upload-url": "/index.php?option=com_emundus&controller=settings&task=uploadmedia",
                "delete-media-url": "/index.php?option=com_emundus&controller=settings&task=deletemedia",
                "editor-content-height": "30em",
                class: normalizeClass("tw-mt-1"),
                locale: "fr",
                preset: "custom",
                plugins: _ctx.editorPlugins,
                "toolbar-classes": ["tw-bg-white"],
                "editor-content-classes": ["tw-bg-white"],
                suggestions: _ctx.suggestions,
                "media-files": _ctx.medias,
                onUploadedImage: $options.getMedia
              }, null, 8, ["modelValue", "plugins", "suggestions", "media-files", "onUploadedImage"])) : createCommentVNode("", true),
              createBaseVNode("div", _hoisted_9, [
                createBaseVNode("a", _hoisted_10, toDisplayString(_ctx.translate("COM_EMUNDUS_EMAIL_SHOW_TAGS")), 1)
              ]),
              _ctx.errors.message ? (openBlock(), createElementBlock("p", _hoisted_11, [
                createBaseVNode("span", _hoisted_12, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_BODY_REQUIRED")), 1)
              ])) : createCommentVNode("", true)
            ]),
            _ctx.displayButtonField ? (openBlock(), createElementBlock("div", _hoisted_13, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_BUTTON_TEXT")), 1),
              createBaseVNode("p", _hoisted_14, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_BUTTON_TEXT_TIP")), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: normalizeClass(["tw-w-full tw-mt-2", { "is-invalid !tw-border-red-600": _ctx.errors.button }]),
                "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => _ctx.form.button = $event)
              }, null, 2), [
                [vModelText, _ctx.form.button]
              ]),
              _ctx.errors.button ? (openBlock(), createElementBlock("p", _hoisted_15, [
                createBaseVNode("span", _hoisted_16, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_BUTTON_REQUIRED")), 1)
              ])) : createCommentVNode("", true)
            ])) : createCommentVNode("", true),
            createBaseVNode("div", _hoisted_17, [
              createBaseVNode("label", null, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CHOOSECATEGORY")), 1),
                _cache[16] || (_cache[16] = createBaseVNode("span", { style: { "color": "#e5283b" } }, "*", -1))
              ]),
              (openBlock(), createBlock(_component_incremental_select, {
                options: this.categoriesList,
                defaultValue: $options.incSelectDefaultValue,
                locked: $props.mode != "create",
                key: _ctx.categories.length,
                onUpdateValue: $options.updateCategorySelectedValue
              }, null, 8, ["options", "defaultValue", "locked", "onUpdateValue"]))
            ])
          ])
        ]),
        _cache[19] || (_cache[19] = createBaseVNode("hr", null, null, -1)),
        createBaseVNode("div", _hoisted_18, [
          createBaseVNode("div", _hoisted_19, [
            createBaseVNode("h2", {
              class: "tw-cursor-pointer !tw-mb-0 tw-w-full",
              onClick: _cache[3] || (_cache[3] = (...args) => $options.displayAdvanced && $options.displayAdvanced(...args))
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING")), 1),
            withDirectives(createBaseVNode("button", {
              title: _ctx.translate("COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING"),
              type: "button",
              class: "tw-bg-transparent tw-flex tw-flex-col",
              onClick: _cache[4] || (_cache[4] = (...args) => $options.displayAdvanced && $options.displayAdvanced(...args))
            }, _cache[17] || (_cache[17] = [
              createBaseVNode("span", { class: "material-symbols-outlined em-main-500-color" }, "add_circle_outline", -1)
            ]), 8, _hoisted_20), [
              [vShow, !_ctx.displayAdvancedParameters]
            ]),
            withDirectives(createBaseVNode("button", {
              title: _ctx.translate("COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING"),
              type: "button",
              onClick: _cache[5] || (_cache[5] = (...args) => $options.displayAdvanced && $options.displayAdvanced(...args)),
              class: "tw-bg-transparent tw-flex tw-flex-col"
            }, _cache[18] || (_cache[18] = [
              createBaseVNode("span", { class: "material-symbols-outlined em-main-500-color" }, "remove_circle_outline", -1)
            ]), 8, _hoisted_21), [
              [vShow, _ctx.displayAdvancedParameters]
            ])
          ]),
          _ctx.displayAdvancedParameters ? (openBlock(), createElementBlock("div", _hoisted_22, [
            createBaseVNode("div", _hoisted_23, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_SENDER_EMAIL")), 1),
              createBaseVNode("p", _hoisted_24, toDisplayString(_ctx.email_sender), 1)
            ]),
            createBaseVNode("div", _hoisted_25, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_RECEIVER")), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: "tw-w-full fabrikinput tw-mt-2",
                "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => _ctx.form.name = $event)
              }, null, 512), [
                [vModelText, _ctx.form.name]
              ])
            ]),
            createBaseVNode("div", _hoisted_26, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESS")), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: "tw-w-full fabrikinput tw-mt-2",
                "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => _ctx.form.emailfrom = $event),
                placeholder: "reply-to@tchooz.io"
              }, null, 512), [
                [vModelText, _ctx.form.emailfrom]
              ]),
              createBaseVNode("p", _hoisted_27, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESTIP")), 1)
            ]),
            createBaseVNode("div", _hoisted_28, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_RECEIVER_CC_TAGS")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-2"),
                modelValue: _ctx.selectedReceiversCC,
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => _ctx.selectedReceiversCC = $event),
                label: "email",
                "track-by": "email",
                options: _ctx.receivers_cc,
                multiple: true,
                searchable: true,
                taggable: true,
                "select-label": "",
                "selected-label": "",
                "deselect-label": "",
                onTag: $options.addNewCC,
                "close-on-select": false,
                "clear-on-select": false
              }, null, 8, ["modelValue", "options", "onTag"])
            ]),
            createBaseVNode("div", _hoisted_29, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_RECEIVER_BCC_TAGS")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-2"),
                modelValue: _ctx.selectedReceiversBCC,
                "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => _ctx.selectedReceiversBCC = $event),
                label: "email",
                "track-by": "email",
                options: _ctx.receivers_bcc,
                multiple: true,
                searchable: true,
                taggable: true,
                "select-label": "",
                "selected-label": "",
                "deselect-label": "",
                onTag: $options.addNewBCC,
                "close-on-select": false,
                "clear-on-select": false
              }, null, 8, ["modelValue", "options", "onTag"])
            ]),
            _ctx.attached_letters ? (openBlock(), createElementBlock("div", _hoisted_30, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-2"),
                modelValue: _ctx.selectedLetterAttachments,
                "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => _ctx.selectedLetterAttachments = $event),
                label: "value",
                "track-by": "id",
                options: _ctx.attached_letters,
                multiple: true,
                taggable: true,
                "select-label": "",
                "selected-label": "",
                "deselect-label": "",
                placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_PLACEHOLDER_EMAIL_DOCUMENT"),
                "close-on-select": false,
                "clear-on-select": false
              }, null, 8, ["modelValue", "options", "placeholder"])
            ])) : createCommentVNode("", true),
            _ctx.tags ? (openBlock(), createElementBlock("div", _hoisted_31, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EMAIL_TAGS")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-2"),
                modelValue: _ctx.selectedTags,
                "onUpdate:modelValue": _cache[11] || (_cache[11] = ($event) => _ctx.selectedTags = $event),
                label: "label",
                "track-by": "id",
                options: _ctx.action_tags,
                multiple: true,
                taggable: true,
                "select-label": "",
                "selected-label": "",
                "deselect-label": "",
                placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_PLACEHOLDER_EMAIL_TAGS"),
                "close-on-select": false,
                "clear-on-select": false
              }, null, 8, ["modelValue", "options", "placeholder"])
            ])) : createCommentVNode("", true),
            createBaseVNode("div", _hoisted_32, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CANDIDAT_ATTACHMENTS")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-2"),
                modelValue: _ctx.selectedCandidateAttachments,
                "onUpdate:modelValue": _cache[12] || (_cache[12] = ($event) => _ctx.selectedCandidateAttachments = $event),
                label: "value",
                "track-by": "id",
                options: _ctx.candidate_attachments,
                multiple: true,
                taggable: true,
                "select-label": "",
                "selected-label": "",
                "deselect-label": "",
                placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_PLACEHOLDER_CANDIDAT_ATTACHMENTS"),
                "close-on-select": false,
                "clear-on-select": false
              }, null, 8, ["modelValue", "options", "placeholder"])
            ])
          ])) : createCommentVNode("", true)
        ]),
        createBaseVNode("div", _hoisted_33, [
          createBaseVNode("button", _hoisted_34, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_RETOUR")), 1),
          createBaseVNode("button", _hoisted_35, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER")), 1)
        ])
      ], 32)
    ]),
    _ctx.loading || _ctx.submitted ? (openBlock(), createElementBlock("div", _hoisted_36)) : createCommentVNode("", true)
  ]);
}
const addEmail = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-0d908019"]]);
export {
  addEmail as default
};
