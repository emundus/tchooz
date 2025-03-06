import { m as FetchClient, _ as _export_sfc, J as mixin, U as script, X as V32, u as useGlobalStore, k as emailService, s as settingsService, r as resolveComponent, o as openBlock, c as createElementBlock, a as createBaseVNode, i as withModifiers, t as toDisplayString, l as createTextVNode, h as withDirectives, R as vModelText, d as normalizeClass, e as createCommentVNode, f as createBlock, v as vShow, g as createVNode } from "./app_emundus.js";
import { I as IncrementalSelect } from "./IncrementalSelect.js";
/* empty css       */
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
      console.log(link);
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
const _hoisted_1 = { class: "tw-border tw-border-neutral-300 em-card-shadow tw-rounded-2xl tw-bg-white tw-p-6" };
const _hoisted_2 = { class: "tw-ml-2 tw-text-neutral-900" };
const _hoisted_3 = { class: "tw-mt-4" };
const _hoisted_4 = { class: "tw-mt-2" };
const _hoisted_5 = { class: "tw-text-red-600 tw-mt-1" };
const _hoisted_6 = { class: "tw-flex tw-flex-col tw-gap-4" };
const _hoisted_7 = { class: "tw-font-medium" };
const _hoisted_8 = {
  key: 0,
  class: "tw-text-red-600 tw-mb-1 tw-mt-1"
};
const _hoisted_9 = { class: "tw-text-red-600" };
const _hoisted_10 = { class: "tw-font-medium" };
const _hoisted_11 = { class: "tw-mt-1" };
const _hoisted_12 = {
  href: "/export-tags",
  class: "em-main-500-color em-hover-main-600 em-text-underline",
  target: "_blank"
};
const _hoisted_13 = {
  key: 1,
  class: "tw-text-red-600 tw-mb-1"
};
const _hoisted_14 = { class: "tw-text-red-600" };
const _hoisted_15 = {
  key: 0,
  class: "tw-mb-4"
};
const _hoisted_16 = { class: "tw-mt-1 tw-mb-1 tw-text-xs tw-text-neutral-700" };
const _hoisted_17 = {
  key: 0,
  class: "tw-text-red-600 tw-mt-1"
};
const _hoisted_18 = { class: "tw-text-red-600" };
const _hoisted_19 = { class: "tw-font-medium" };
const _hoisted_20 = { class: "em-container-accordeon tw-shadow" };
const _hoisted_21 = { class: "tw-flex tw-items-center tw-gap-1" };
const _hoisted_22 = ["title"];
const _hoisted_23 = ["title"];
const _hoisted_24 = {
  key: 0,
  id: "email-advanced-parameters",
  class: "tw-mt-4 tw-pl-4 em-border-left-main-500 tw-flex tw-flex-col tw-gap-4"
};
const _hoisted_25 = { class: "tw-font-medium" };
const _hoisted_26 = { class: "tw-mt-1 tw-text-neutral-700" };
const _hoisted_27 = { class: "tw-font-medium" };
const _hoisted_28 = { class: "tw-font-medium" };
const _hoisted_29 = { class: "tw-text-xs tw-text-neutral-700 tw-mt-1" };
const _hoisted_30 = { id: "receivers_cc" };
const _hoisted_31 = { class: "tw-font-medium" };
const _hoisted_32 = { id: "receivers_bcc" };
const _hoisted_33 = { class: "tw-font-medium" };
const _hoisted_34 = {
  key: 0,
  id: "attached_letters"
};
const _hoisted_35 = { class: "tw-font-medium" };
const _hoisted_36 = { key: 1 };
const _hoisted_37 = { class: "tw-font-medium" };
const _hoisted_38 = { class: "tw-font-medium" };
const _hoisted_39 = { class: "tw-flex tw-justify-end" };
const _hoisted_40 = {
  type: "submit",
  class: "tw-btn-primary !tw-w-auto"
};
const _hoisted_41 = {
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
        onSubmit: _cache[14] || (_cache[14] = withModifiers((...args) => $options.submit && $options.submit(...args), ["prevent"])),
        class: "fabrikForm emundus-form"
      }, [
        createBaseVNode("div", null, [
          createBaseVNode("div", null, [
            createBaseVNode("div", {
              class: "tw-flex tw-items-center tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300",
              onClick: _cache[0] || (_cache[0] = ($event) => $options.redirectJRoute("index.php?option=com_emundus&view=emails"))
            }, [
              _cache[15] || (_cache[15] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-600" }, "navigate_before", -1)),
              createBaseVNode("span", _hoisted_2, toDisplayString(_ctx.translate("BACK")), 1)
            ]),
            createBaseVNode("div", _hoisted_3, [
              createBaseVNode("h1", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EMAIL")), 1),
              createBaseVNode("div", _hoisted_4, [
                createBaseVNode("p", _hoisted_5, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE")), 1)
              ])
            ])
          ]),
          _cache[18] || (_cache[18] = createBaseVNode("hr", { class: "tw-mt-1.5 tw-mb-4" }, null, -1)),
          createBaseVNode("div", _hoisted_6, [
            createBaseVNode("div", null, [
              createBaseVNode("label", _hoisted_7, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_NAME")) + " ", 1),
                _cache[16] || (_cache[16] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
              ]),
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: normalizeClass(["tw-w-full tw-mt-1", { "is-invalid": _ctx.errors.subject }]),
                "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => _ctx.form.subject = $event)
              }, null, 2), [
                [vModelText, _ctx.form.subject]
              ]),
              _ctx.errors.subject ? (openBlock(), createElementBlock("div", _hoisted_8, [
                createBaseVNode("span", _hoisted_9, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SUBJECT_REQUIRED")), 1)
              ])) : createCommentVNode("", true)
            ]),
            createBaseVNode("div", null, [
              createBaseVNode("label", _hoisted_10, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_BODY")) + " ", 1),
                _cache[17] || (_cache[17] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
              ]),
              _ctx.editor_ready ? (openBlock(), createBlock(_component_tip_tap_editor, {
                key: 0,
                modelValue: _ctx.form.message,
                "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => _ctx.form.message = $event),
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
              createBaseVNode("div", _hoisted_11, [
                createBaseVNode("a", _hoisted_12, toDisplayString(_ctx.translate("COM_EMUNDUS_EMAIL_SHOW_TAGS")), 1)
              ]),
              _ctx.errors.message ? (openBlock(), createElementBlock("div", _hoisted_13, [
                createBaseVNode("span", _hoisted_14, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_BODY_REQUIRED")), 1)
              ])) : createCommentVNode("", true)
            ]),
            _ctx.displayButtonField ? (openBlock(), createElementBlock("div", _hoisted_15, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_BUTTON_TEXT")), 1),
              createBaseVNode("p", _hoisted_16, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_BUTTON_TEXT_TIP")), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: normalizeClass(["tw-w-full tw-mt-2", { "is-invalid !tw-border-red-600": _ctx.errors.button }]),
                "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => _ctx.form.button = $event)
              }, null, 2), [
                [vModelText, _ctx.form.button]
              ]),
              _ctx.errors.button ? (openBlock(), createElementBlock("div", _hoisted_17, [
                createBaseVNode("span", _hoisted_18, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_BUTTON_REQUIRED")), 1)
              ])) : createCommentVNode("", true)
            ])) : createCommentVNode("", true),
            createBaseVNode("div", null, [
              createBaseVNode("label", _hoisted_19, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CHOOSECATEGORY")), 1),
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
        _cache[21] || (_cache[21] = createBaseVNode("hr", { class: "tw-mt-1.5 tw-mb-4" }, null, -1)),
        createBaseVNode("div", _hoisted_20, [
          createBaseVNode("div", _hoisted_21, [
            createBaseVNode("h2", {
              class: "tw-cursor-pointer tw-w-full",
              onClick: _cache[4] || (_cache[4] = (...args) => $options.displayAdvanced && $options.displayAdvanced(...args))
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING")), 1),
            withDirectives(createBaseVNode("button", {
              title: _ctx.translate("COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING"),
              type: "button",
              class: "tw-bg-transparent tw-flex tw-flex-col",
              onClick: _cache[5] || (_cache[5] = (...args) => $options.displayAdvanced && $options.displayAdvanced(...args))
            }, _cache[19] || (_cache[19] = [
              createBaseVNode("span", { class: "material-symbols-outlined em-main-500-color" }, "add_circle_outline", -1)
            ]), 8, _hoisted_22), [
              [vShow, !_ctx.displayAdvancedParameters]
            ]),
            withDirectives(createBaseVNode("button", {
              title: _ctx.translate("COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING"),
              type: "button",
              onClick: _cache[6] || (_cache[6] = (...args) => $options.displayAdvanced && $options.displayAdvanced(...args)),
              class: "tw-bg-transparent tw-flex tw-flex-col"
            }, _cache[20] || (_cache[20] = [
              createBaseVNode("span", { class: "material-symbols-outlined em-main-500-color" }, "remove_circle_outline", -1)
            ]), 8, _hoisted_23), [
              [vShow, _ctx.displayAdvancedParameters]
            ])
          ]),
          _ctx.displayAdvancedParameters ? (openBlock(), createElementBlock("div", _hoisted_24, [
            createBaseVNode("div", null, [
              createBaseVNode("label", _hoisted_25, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_SENDER_EMAIL")), 1),
              createBaseVNode("p", _hoisted_26, toDisplayString(_ctx.email_sender), 1)
            ]),
            createBaseVNode("div", null, [
              createBaseVNode("label", _hoisted_27, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_RECEIVER")), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: "tw-w-full fabrikinput tw-mt-1",
                "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => _ctx.form.name = $event)
              }, null, 512), [
                [vModelText, _ctx.form.name]
              ])
            ]),
            createBaseVNode("div", null, [
              createBaseVNode("label", _hoisted_28, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESS")), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                class: "tw-w-full fabrikinput tw-mt-1",
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => _ctx.form.emailfrom = $event),
                placeholder: "reply-to@tchooz.io"
              }, null, 512), [
                [vModelText, _ctx.form.emailfrom]
              ]),
              createBaseVNode("p", _hoisted_29, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESTIP")), 1)
            ]),
            createBaseVNode("div", _hoisted_30, [
              createBaseVNode("label", _hoisted_31, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_RECEIVER_CC_TAGS")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-1"),
                modelValue: _ctx.selectedReceiversCC,
                "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => _ctx.selectedReceiversCC = $event),
                label: "email",
                "track-by": "email",
                options: _ctx.receivers_cc,
                multiple: true,
                searchable: true,
                taggable: true,
                placeholder: _ctx.translate("PLEASE_SELECT"),
                "select-label": "",
                "selected-label": "",
                "deselect-label": "",
                onTag: $options.addNewCC,
                "close-on-select": false,
                "clear-on-select": false
              }, null, 8, ["modelValue", "options", "placeholder", "onTag"])
            ]),
            createBaseVNode("div", _hoisted_32, [
              createBaseVNode("label", _hoisted_33, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_RECEIVER_BCC_TAGS")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-1"),
                modelValue: _ctx.selectedReceiversBCC,
                "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => _ctx.selectedReceiversBCC = $event),
                label: "email",
                "track-by": "email",
                options: _ctx.receivers_bcc,
                multiple: true,
                searchable: true,
                taggable: true,
                placeholder: _ctx.translate("PLEASE_SELECT"),
                "select-label": "",
                "selected-label": "",
                "deselect-label": "",
                onTag: $options.addNewBCC,
                "close-on-select": false,
                "clear-on-select": false
              }, null, 8, ["modelValue", "options", "placeholder", "onTag"])
            ]),
            _ctx.attached_letters ? (openBlock(), createElementBlock("div", _hoisted_34, [
              createBaseVNode("label", _hoisted_35, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-1"),
                modelValue: _ctx.selectedLetterAttachments,
                "onUpdate:modelValue": _cache[11] || (_cache[11] = ($event) => _ctx.selectedLetterAttachments = $event),
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
            _ctx.tags ? (openBlock(), createElementBlock("div", _hoisted_36, [
              createBaseVNode("label", _hoisted_37, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_EMAIL_TAGS")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-1"),
                modelValue: _ctx.selectedTags,
                "onUpdate:modelValue": _cache[12] || (_cache[12] = ($event) => _ctx.selectedTags = $event),
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
            createBaseVNode("div", null, [
              createBaseVNode("label", _hoisted_38, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CANDIDAT_ATTACHMENTS")), 1),
              createVNode(_component_multiselect, {
                class: normalizeClass("tw-mt-1"),
                modelValue: _ctx.selectedCandidateAttachments,
                "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => _ctx.selectedCandidateAttachments = $event),
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
        _cache[22] || (_cache[22] = createBaseVNode("hr", { class: "tw-mt-1.5 tw-mb-4" }, null, -1)),
        createBaseVNode("div", _hoisted_39, [
          createBaseVNode("button", _hoisted_40, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER")), 1)
        ])
      ], 32)
    ]),
    _ctx.loading || _ctx.submitted ? (openBlock(), createElementBlock("div", _hoisted_41)) : createCommentVNode("", true)
  ]);
}
const addEmail = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-378c06f6"]]);
export {
  addEmail as default
};
