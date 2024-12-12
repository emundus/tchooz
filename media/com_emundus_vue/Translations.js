import { V as client, i as axios, _ as _export_sfc, O as script, G as mixin, o as openBlock, c as createElementBlock, b as Fragment, r as renderList, a as createBaseVNode, t as toDisplayString, d as createCommentVNode, h as errors, e as resolveComponent, m as createVNode, w as withDirectives, v as vShow } from "./app_emundus.js";
import { q as qs } from "./index.js";
const translationsService = {
  async checkSetup() {
    try {
      return await client().get("index.php?option=com_emundus&controller=translations&task=checksetup");
    } catch (e) {
      return false;
    }
  },
  async configureSetup() {
    try {
      return await client().get("index.php?option=com_emundus&controller=translations&task=configuresetup");
    } catch (e) {
      return false;
    }
  },
  async getAllLanguages() {
    try {
      const response = await client().get("index.php?option=com_emundus&controller=translations&task=getlanguages");
      return response.data;
    } catch (e) {
      return false;
    }
  },
  async getDefaultLanguage() {
    try {
      const response = await client().get("index.php?option=com_emundus&controller=translations&task=getdefaultlanguage");
      return response.data;
    } catch (e) {
      return false;
    }
  },
  async updateLanguage(lang_code, published, default_lang = 0) {
    try {
      const formData = new FormData();
      formData.append("published", published);
      formData.append("lang_code", lang_code);
      formData.append("default_lang", default_lang);
      return await client().post(
        `index.php?option=com_emundus&controller=translations&task=updatelanguage`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        }
      );
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getObjects() {
    try {
      return await client().get(`index.php?option=com_emundus&controller=translations&task=gettranslationsobjects`);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getDatas(table, reference_id, label, filters) {
    try {
      return await client().get(`index.php?option=com_emundus&controller=translations&task=getdatas`, {
        params: {
          table,
          reference_id,
          label,
          filters
        }
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getChildrens(table, reference_id, label) {
    try {
      return await client().get(`index.php?option=com_emundus&controller=translations&task=getchildrens`, {
        params: {
          table,
          reference_id,
          label
        }
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getTranslations(type, default_lang, lang_to, reference_id = "", fields = "", reference_table = "") {
    switch (type) {
      case "falang":
        try {
          return await client().get(`index.php?option=com_emundus&controller=translations&task=getfalangtranslations`, {
            params: {
              default_lang,
              lang_to,
              reference_table,
              reference_id,
              fields
            }
          }).then((response) => {
            return response.data;
          });
        } catch (e) {
          return {
            status: false,
            msg: e.message
          };
        }
      case "override":
        var params = {
          default_lang,
          lang_to,
          reference_table,
          reference_id,
          fields
        };
        var myAxios = axios.create({
          paramsSerializer: (params2) => qs.stringify(params2)
        });
        try {
          return await myAxios.get(`index.php?option=com_emundus&controller=translations&task=gettranslations`, { params });
        } catch (e) {
          return {
            status: false,
            msg: e.message
          };
        }
    }
  },
  async updateTranslations(value, type, lang_to, reference_id, field, reference_table, reference_field) {
    switch (type) {
      case "falang":
        try {
          const formData = new FormData();
          formData.append("value", value);
          formData.append("lang_to", lang_to);
          formData.append("field", field);
          formData.append("reference_table", reference_table);
          formData.append("reference_id", reference_id);
          return await client().post(
            `index.php?option=com_emundus&controller=translations&task=updatefalangtranslation`,
            formData,
            {
              headers: {
                "Content-Type": "multipart/form-data"
              }
            }
          );
        } catch (e) {
          return {
            status: false,
            msg: e.message
          };
        }
      case "override":
        try {
          const formData = new FormData();
          formData.append("value", value);
          formData.append("lang_to", lang_to);
          formData.append("tag", field);
          formData.append("reference_table", reference_table);
          formData.append("reference_id", reference_id);
          formData.append("reference_field", reference_field);
          return await client().post(
            `index.php?option=com_emundus&controller=translations&task=updatetranslation`,
            formData,
            {
              headers: {
                "Content-Type": "multipart/form-data"
              }
            }
          );
        } catch (e) {
          return {
            status: false,
            msg: e.message
          };
        }
    }
  },
  async insertTranslation(value, type, lang_to, reference_id, field, reference_table) {
    switch (type) {
      case "override":
        try {
          const formData = new FormData();
          formData.append("value", value);
          formData.append("lang_to", lang_to);
          formData.append("tag", field);
          formData.append("reference_table", reference_table);
          formData.append("reference_id", reference_id);
          return await client().post(
            `index.php?option=com_emundus&controller=translations&task=inserttranslation`,
            formData,
            {
              headers: {
                "Content-Type": "multipart/form-data"
              }
            }
          );
        } catch (e) {
          return {
            status: false,
            msg: e.message
          };
        }
    }
  },
  async getOrphelins(default_lang, lang_to) {
    try {
      return await client().get(`index.php?option=com_emundus&controller=translations&task=getorphelins`, {
        params: {
          default_lang,
          lang_to
        }
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async sendMailToInstallLanguage(language, comment = "") {
    try {
      const formData = new FormData();
      formData.append("suggest_language", language);
      formData.append("comment", comment);
      return await client().post(
        `index.php?option=com_emundus&controller=translations&task=sendpurposenewlanguage`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        }
      );
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getJText(tag) {
    try {
      return await client().get("index.php?option=com_emundus&controller=formbuilder&task=getJTEXT", {
        params: {
          toJTEXT: tag
        }
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
};
const _sfc_main$1 = {
  name: "TranslationRow",
  components: {
    Multiselect: script
  },
  mixins: [mixin],
  props: {
    section: Object,
    translations: Array
  },
  data() {
    return {
      translations_rows: {},
      key_fields: []
    };
  },
  created() {
    this.initTranslations();
  },
  methods: {
    initTranslations() {
      this.key_fields = Object.keys(this.$props.section.indexedFields);
      Object.values(this.$props.translations).forEach((translations_reference) => {
        Object.values(translations_reference).forEach((translation) => {
          if (this.key_fields.includes(translation.reference_field) && translation.reference_table === this.$props.section.Name) {
            translation.reference_field_order = this.key_fields.indexOf(translation.reference_field);
            translation.reference_label = this.$props.section.indexedFields[translation.reference_field].Label;
            translation.field_type = this.$props.section.indexedFields[translation.reference_field].Type;
            if (!translation.hasOwnProperty("tag")) {
              translation.tag = translation.reference_field;
            }
            if (!this.translations_rows.hasOwnProperty(translation.reference_id)) {
              this.translations_rows[translation.reference_id] = [];
            }
            this.translations_rows[translation.reference_id].push(translation);
          }
        });
      });
      Object.values(this.translations_rows).forEach((translation_reference) => {
        translation_reference.sort((a, b) => a.reference_field_order > b.reference_field_order ? 1 : -1);
      });
    },
    async saveTranslation(value, translation) {
      this.$emit("saveTranslation", { value, translation });
    }
  }
};
const _hoisted_1$1 = { class: "tw-mb-8 em-neutral-100-box em-p-24" };
const _hoisted_2$1 = { class: "tw-mb-6" };
const _hoisted_3$1 = { class: "tw-justify-between tw-mt-4 em-grid-50 em-ml-24" };
const _hoisted_4$1 = { class: "tw-text-neutral-700" };
const _hoisted_5$1 = ["value", "onFocusout"];
const _hoisted_6$1 = ["value", "onFocusout"];
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", null, [
    (openBlock(true), createElementBlock(Fragment, null, renderList($data.translations_rows, (translation) => {
      return openBlock(), createElementBlock("div", _hoisted_1$1, [
        (openBlock(true), createElementBlock(Fragment, null, renderList(translation, (field, index) => {
          return openBlock(), createElementBlock("div", _hoisted_2$1, [
            createBaseVNode("p", null, toDisplayString(field.reference_label ? field.reference_label.toUpperCase() : field.reference_id), 1),
            createBaseVNode("div", _hoisted_3$1, [
              createBaseVNode("p", _hoisted_4$1, toDisplayString(field.default_lang), 1),
              field.field_type === "field" ? (openBlock(), createElementBlock("input", {
                key: 0,
                class: "mb-0 em-input tw-w-full",
                type: "text",
                value: field.lang_to,
                onFocusout: ($event) => $options.saveTranslation($event.target.value, field)
              }, null, 40, _hoisted_5$1)) : createCommentVNode("", true),
              field.field_type === "textarea" ? (openBlock(), createElementBlock("textarea", {
                key: 1,
                class: "mb-0 em-input",
                value: field.lang_to,
                onFocusout: ($event) => $options.saveTranslation($event.target.value, field)
              }, null, 40, _hoisted_6$1)) : createCommentVNode("", true)
            ])
          ]);
        }), 256))
      ]);
    }), 256))
  ]);
}
const TranslationRow = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "Translations",
  components: {
    TranslationRow,
    Multiselect: script
  },
  props: {
    objectValue: {
      type: String,
      required: false
    },
    dataValue: {
      type: String,
      required: false
    },
    childrenValue: {
      type: String,
      required: false
    },
    displayFilters: {
      type: Boolean,
      required: false,
      default: true
    }
  },
  mixins: [mixin, errors],
  data() {
    return {
      defaultLang: null,
      availableLanguages: [],
      // Lists
      objects: [],
      datas: [],
      childrens: [],
      translations: {},
      // Values
      lang: null,
      object: null,
      data: null,
      children_type: null,
      children: null,
      loading: false,
      init_translations: false,
      firstLoadObjects: true,
      firstLoadDatas: true
    };
  },
  created() {
    this.loading = true;
    translationsService.getDefaultLanguage().then((response) => {
      this.defaultLang = response;
      this.getAllLanguages().then(() => {
        this.loading = false;
      });
    });
  },
  methods: {
    async getAllLanguages() {
      try {
        const response = await client().get("index.php?option=com_emundus&controller=translations&task=getlanguages");
        this.allLanguages = response.data;
        for (const lang of this.allLanguages) {
          if (lang.lang_code !== this.defaultLang.lang_code) {
            if (lang.published == 1) {
              this.availableLanguages.push(lang);
            }
          }
        }
        if (this.availableLanguages.length === 1) {
          this.lang = this.availableLanguages[0];
          await this.getObjects();
        }
      } catch (e) {
        this.loading = false;
        return false;
      }
    },
    async getObjects() {
      this.loading = true;
      this.translations = [];
      this.childrens = [];
      this.datas = [];
      this.objects = [];
      this.object = null;
      this.data = null;
      this.children = null;
      translationsService.getObjects().then((response) => {
        this.objects = response.data;
        if (this.firstLoadObjects) {
          const urlParams = new URLSearchParams(window.location.search);
          const object = urlParams.get("object");
          if (object) {
            this.object = this.objects.find((obj) => obj.table.name === object);
          }
          this.firstLoadObjects = false;
        }
        this.loading = false;
      });
    },
    async getDatas(value) {
      this.loading = true;
      translationsService.getDatas(
        value.table.name,
        value.table.reference,
        value.table.label,
        value.table.filters
      ).then(async (response) => {
        if (response.status) {
          if (response.data.length > 0) {
            this.datas = response.data;
            if (value.table.load_all === "true") {
              let fields = [];
              await this.asyncForEach(this.object.fields.Fields, async (field) => {
                fields.push(field.Name);
              });
              fields = fields.join(",");
              const build = async () => {
                for (const data of this.datas) {
                  await translationsService.getTranslations(
                    this.object.table.type,
                    this.defaultLang.lang_code,
                    this.lang.lang_code,
                    data.id,
                    fields,
                    this.object.table.name
                  ).then(async (rep) => {
                    console.log(rep);
                    if (rep.status) {
                      for (const translation of Object.values(rep.data)) {
                        this.translations[data.id] = {};
                        this.object.fields.Fields.forEach((field) => {
                          this.translations[data.id][field.Name] = translation[field.Name];
                        });
                      }
                    } else {
                      this.displayError(rep.message, "");
                    }
                  });
                }
                this.init_translations = true;
                this.loading = false;
              };
              await build();
            } else if (value.table.load_first_data === "true") {
              if (this.firstLoadDatas) {
                const urlParams = new URLSearchParams(window.location.search);
                const dataParam = urlParams.get("data");
                if (dataParam) {
                  this.data = this.datas.find((d) => parseInt(d.id) === parseInt(dataParam));
                } else {
                  this.data = this.datas[0];
                }
                this.firstLoadDatas = false;
              } else {
                this.data = this.datas[0];
              }
            } else {
              this.loading = false;
            }
          } else {
            this.loading = false;
          }
        } else {
          this.loading = false;
        }
      });
    },
    async getTranslations(value) {
      let fields = [];
      this.object.fields.Fields.forEach((field) => {
        fields.push(field.Name);
      });
      fields = fields.join(",");
      translationsService.getTranslations(
        this.object.table.type,
        this.defaultLang.lang_code,
        this.lang.lang_code,
        value.id,
        fields,
        this.object.table.name
      ).then((response) => {
        this.translations = response.data;
        this.init_translations = true;
        this.loading = false;
      });
    },
    async saveTranslation({ value, translation }) {
      this.$emit("updateSaving", true);
      translationsService.updateTranslations(value, this.object.table.type, this.lang.lang_code, translation.reference_id, translation.tag, translation.reference_table, translation.reference_field).then((response) => {
        if (response.status) {
          this.$emit("updateLastSaving", this.formattedDate("", "LT"));
          this.$emit("updateSaving", false);
        } else {
          console.error(response.msg);
        }
      });
    },
    async exportToCsv() {
      window.open("/index.php?option=com_emundus&controller=translations&task=export&profile=" + this.data.id, "_blank");
    },
    translate(key) {
      if (typeof key != void 0 && key != null && Joomla !== null && typeof Joomla !== "undefined") {
        return Joomla.JText._(key) ? Joomla.JText._(key) : key;
      } else {
        return "";
      }
    }
  },
  watch: {
    objects: function(value) {
      if (value.length > 0) {
        if (this.objectValue) {
          this.object = this.objects.find((obj) => obj.table.name === this.objectValue);
        }
      }
    },
    object: function(value) {
      this.init_translations = false;
      this.translations = {};
      this.childrens = [];
      this.children = null;
      this.datas = [];
      this.data = null;
      if (value != null) {
        this.getDatas(value);
      }
    },
    datas: function(value) {
      if (value.length > 0) {
        if (this.dataValue) {
          this.data = this.datas.find((d) => d.id == this.dataValue);
        }
      }
    },
    data: function(value) {
      this.loading = true;
      this.init_translations = false;
      this.translations = {};
      this.childrens = [];
      this.children = null;
      this.children_type = null;
      var children_existing = false;
      if (value != null) {
        this.object.fields.Fields.forEach((field) => {
          if (field.Type === "children") {
            this.children_type = field.Label;
            children_existing = true;
            translationsService.getChildrens(field.Label, this.data.id, field.Name).then((response) => {
              this.childrens = response.data;
              if (this.object.table.load_first_child === "true") {
                this.children = this.childrens[0];
              }
              this.loading = false;
            });
          }
        });
        if (!children_existing) {
          this.getTranslations(value);
        }
      } else {
        this.getDatas(this.object);
      }
    },
    childrens: function(value) {
      if (value.length > 0) {
        if (this.childrenValue) {
          this.children = this.childrens.find((c) => c.id == this.childrenValue);
        }
      }
    },
    children: function(value) {
      this.loading = true;
      this.init_translations = false;
      this.translations = {};
      if (value != null) {
        let tables = [];
        this.object.fields.Sections.forEach((section) => {
          const table = {
            table: section.Table,
            join_table: section.TableJoin,
            join_column: section.TableJoinColumn,
            reference_column: section.ReferenceColumn,
            fields: Object.keys(section.indexedFields)
          };
          tables.push(table);
        });
        translationsService.getTranslations(
          this.object.table.type,
          this.defaultLang.lang_code,
          this.lang.lang_code,
          value.id,
          "",
          tables
        ).then((response) => {
          this.translations = response.data;
          this.init_translations = true;
          this.loading = false;
        });
      } else {
        this.loading = false;
      }
    }
  }
};
const _hoisted_1 = { class: "tw-mb-2" };
const _hoisted_2 = { class: "tw-text-base tw-text-neutral-700 tw-mb-6 em-h-25" };
const _hoisted_3 = {
  key: 0,
  class: "tw-text-base tw-mb-6 em-h-25"
};
const _hoisted_4 = {
  key: 1,
  class: "em-grid-4"
};
const _hoisted_5 = { key: 0 };
const _hoisted_6 = { key: 1 };
const _hoisted_7 = { key: 2 };
const _hoisted_8 = { class: "col-md-12" };
const _hoisted_9 = {
  key: 0,
  class: "text-center tw-mt-20"
};
const _hoisted_10 = { class: "tw-mb-2" };
const _hoisted_11 = { class: "tw-text-base em-text-neutral-600" };
const _hoisted_12 = { key: 1 };
const _hoisted_13 = { class: "mb-2" };
const _hoisted_14 = {
  key: 2,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  const _component_TranslationRow = resolveComponent("TranslationRow");
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("h3", _hoisted_1, toDisplayString(this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS")), 1),
    createBaseVNode("p", _hoisted_2, toDisplayString(this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE")), 1),
    $data.availableLanguages.length === 0 && !$data.loading ? (openBlock(), createElementBlock("p", _hoisted_3, toDisplayString(this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_NO_LANGUAGES_AVAILABLE")), 1)) : (openBlock(), createElementBlock("div", _hoisted_4, [
      createBaseVNode("div", null, [
        createVNode(_component_multiselect, {
          modelValue: $data.lang,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.lang = $event),
          label: "title_native",
          "track-by": "lang_code",
          options: $data.availableLanguages,
          multiple: false,
          taggable: false,
          "select-label": "",
          "selected-label": "",
          "deselect-label": "",
          placeholder: this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_LANGUAGE"),
          "close-on-select": true,
          "clear-on-select": false,
          searchable: false,
          "allow-empty": true,
          onSelect: $options.getObjects
        }, null, 8, ["modelValue", "options", "placeholder", "onSelect"])
      ]),
      $data.lang ? withDirectives((openBlock(), createElementBlock("div", _hoisted_5, [
        createVNode(_component_multiselect, {
          modelValue: $data.object,
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.object = $event),
          label: "name",
          "track-by": "name",
          options: $data.objects,
          multiple: false,
          taggable: false,
          "select-label": "",
          "selected-label": "",
          "deselect-label": "",
          placeholder: this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_OBJECT"),
          "close-on-select": true,
          "clear-on-select": false,
          searchable: false,
          "allow-empty": true
        }, null, 8, ["modelValue", "options", "placeholder"])
      ], 512)), [
        [vShow, $props.displayFilters]
      ]) : createCommentVNode("", true),
      $data.object ? withDirectives((openBlock(), createElementBlock("div", _hoisted_6, [
        createVNode(_component_multiselect, {
          modelValue: $data.data,
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.data = $event),
          label: "label",
          "track-by": "id",
          options: $data.datas,
          multiple: false,
          taggable: false,
          "select-label": "",
          "selected-label": "",
          "deselect-label": "",
          placeholder: this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT"),
          "close-on-select": true,
          "clear-on-select": false,
          searchable: true,
          "allow-empty": true
        }, null, 8, ["modelValue", "options", "placeholder"])
      ], 512)), [
        [vShow, $props.displayFilters]
      ]) : createCommentVNode("", true),
      $data.childrens.length > 0 ? withDirectives((openBlock(), createElementBlock("div", _hoisted_7, [
        createVNode(_component_multiselect, {
          modelValue: $data.children,
          "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.children = $event),
          label: "label",
          "track-by": "id",
          options: $data.childrens,
          multiple: false,
          taggable: false,
          "select-label": "",
          "selected-label": "",
          "deselect-label": "",
          placeholder: this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT"),
          "close-on-select": true,
          "clear-on-select": false,
          searchable: true,
          "allow-empty": true
        }, null, 8, ["modelValue", "options", "placeholder"])
      ], 512)), [
        [vShow, $props.displayFilters]
      ]) : createCommentVNode("", true)
    ])),
    _cache[5] || (_cache[5] = createBaseVNode("hr", {
      class: "col-md-12",
      style: { "z-index": "0" }
    }, null, -1)),
    createBaseVNode("div", _hoisted_8, [
      $data.lang === "" || $data.lang == null || $data.object === "" || $data.object == null || $data.init_translations === false ? (openBlock(), createElementBlock("div", _hoisted_9, [
        createBaseVNode("h5", _hoisted_10, toDisplayString(this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_TRANSLATION_TITLE")), 1),
        createBaseVNode("p", _hoisted_11, toDisplayString(this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_TRANSLATION_TEXT")), 1)
      ])) : (openBlock(), createElementBlock("div", _hoisted_12, [
        $data.object.table.name === "emundus_setup_profiles" ? (openBlock(), createElementBlock("button", {
          key: 0,
          class: "float-right em-profile-color em-text-underline",
          onClick: _cache[4] || (_cache[4] = (...args) => $options.exportToCsv && $options.exportToCsv(...args))
        }, toDisplayString(this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_EXPORT")), 1)) : createCommentVNode("", true),
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.object.fields.Sections, (section) => {
          return openBlock(), createElementBlock("div", {
            key: section.Table,
            class: "tw-mb-8"
          }, [
            createBaseVNode("h4", _hoisted_13, toDisplayString(section.Label), 1),
            createVNode(_component_TranslationRow, {
              section,
              translations: $data.translations,
              onSaveTranslation: $options.saveTranslation
            }, null, 8, ["section", "translations", "onSaveTranslation"])
          ]);
        }), 128))
      ]))
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_14)) : createCommentVNode("", true)
  ]);
}
const Translations = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Translations as T,
  translationsService as t
};
