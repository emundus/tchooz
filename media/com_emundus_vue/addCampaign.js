import { _ as _export_sfc, o as openBlock, c as createElementBlock, w as withDirectives, C as vModelText, d as createBaseVNode, D as withKeys, n as normalizeClass, v as vShow, F as Fragment, e as renderList, t as toDisplayString, y as script, aw as V32, u as useGlobalStore, X as campaignService, S as Swal, a4 as formService, z as programmeService, s as settingsService, r as resolveComponent, b as createCommentVNode, g as withModifiers, m as createTextVNode, h as createVNode, f as withCtx, Z as mergeProps, a1 as toHandlers, a0 as vModelCheckbox, H as vModelSelect, L as Transition } from "./app_emundus.js";
import { D as DatePicker } from "./index.js";
/* empty css       */
import { u as useCampaignStore } from "./campaign.js";
const _sfc_main$1 = {
  name: "autocomplete",
  props: {
    items: {
      type: Array,
      required: false,
      default: () => []
    },
    name: String,
    year: String,
    id: String
  },
  data() {
    return {
      search: "",
      results: [],
      isOpen: false,
      sLoading: false,
      arrowCounter: -1
    };
  },
  created() {
    this.search = this.year;
  },
  methods: {
    onSearching() {
      this.$emit("searched", this.search);
    },
    onChange() {
      this.isOpen = true;
      this.filterResults();
      this.onSearching();
    },
    filterResults() {
      this.results = this.items.filter((item) => item.toLowerCase().indexOf(this.search.toLowerCase()) > -1);
    },
    setResult(result) {
      this.search = result;
      this.isOpen = false;
      this.onSearching();
    },
    onArrowDown() {
      if (this.arrowCounter < this.results.length) {
        this.arrowCounter = this.arrowCounter + 1;
      }
    },
    onArrowUp() {
      if (this.arrowCounter > 0) {
        this.arrowCounter = this.arrowCounter - 1;
      }
    },
    onEnter() {
      this.search = this.results[this.arrowCounter];
      this.isOpen = false;
      this.arrowCounter = -1;
      this.onSearching();
    },
    handleClickOutside(evt) {
      if (!this.$el.contains(evt.target)) {
        this.isOpen = false;
        this.arrowCounter = -1;
      }
    }
  },
  mounted() {
    document.addEventListener("click", this.handleClickOutside);
  },
  destroyed() {
    document.removeEventListener("click", this.handleClickOutside);
  }
};
const _hoisted_1$1 = { class: "autocomplete tw-mt-1" };
const _hoisted_2$1 = ["id", "placeholder"];
const _hoisted_3$1 = { class: "autocomplete-results" };
const _hoisted_4$1 = ["onClick"];
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    withDirectives(createBaseVNode("input", {
      type: "text",
      id: $props.id,
      "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.search = $event),
      onInput: _cache[1] || (_cache[1] = (...args) => $options.onChange && $options.onChange(...args)),
      onKeydown: [
        _cache[2] || (_cache[2] = withKeys((...args) => $options.onArrowDown && $options.onArrowDown(...args), ["down"])),
        _cache[3] || (_cache[3] = withKeys((...args) => $options.onArrowUp && $options.onArrowUp(...args), ["up"])),
        _cache[4] || (_cache[4] = withKeys((...args) => $options.onEnter && $options.onEnter(...args), ["enter"]))
      ],
      placeholder: $props.year !== "" ? $props.year : $props.name,
      class: normalizeClass([$props.year !== "" ? "" : "placeholder", "tw-w-full"])
    }, null, 42, _hoisted_2$1), [
      [vModelText, $data.search]
    ]),
    withDirectives(createBaseVNode("ul", _hoisted_3$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.results, (result, i) => {
        return openBlock(), createElementBlock("li", {
          key: i,
          onClick: ($event) => $options.setResult(result),
          class: normalizeClass(["autocomplete-result", { "is-active": i === $data.arrowCounter }])
        }, toDisplayString(result), 11, _hoisted_4$1);
      }), 128))
    ], 512), [
      [vShow, $data.isOpen]
    ])
  ]);
}
const Autocomplete = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-54f23536"]]);
const _sfc_main = {
  name: "addCampaign",
  components: {
    Multiselect: script,
    TipTapEditor: V32,
    Autocomplete,
    DatePicker
  },
  directives: {
    focus: {
      inserted: function(el) {
        el.focus();
      }
    }
  },
  props: {
    campaign: Number
  },
  data: () => ({
    // props
    campaignId: 0,
    actualLanguage: "",
    coordinatorAccess: 0,
    quit: 1,
    isHiddenProgram: false,
    // Date picker rules
    minDate: "",
    //
    programs: [],
    applicantForms: [],
    years: [],
    languages: [],
    aliases: [],
    editorPlugins: [
      "history",
      "link",
      "image",
      "bold",
      "italic",
      "underline",
      "left",
      "center",
      "right",
      "h1",
      "h2",
      "ul"
    ],
    session: [],
    old_training: "",
    old_program_form: "",
    aliasUpdated: false,
    campaignLanguages: [],
    form: {
      label: {},
      start_date: "",
      end_date: "",
      short_description: "",
      description: null,
      training: "",
      year: "",
      published: 1,
      profile_id: 0,
      limit: 50,
      limit_status: [],
      pinned: 0,
      visible: 1,
      alias: ""
    },
    programForm: {
      code: "",
      label: "",
      notes: "",
      programmes: "",
      published: 1,
      apply_online: 1,
      color: "#1C6EF2"
    },
    year: {
      label: "",
      code: "",
      schoolyear: "",
      published: 1,
      profile_id: "",
      programmes: ""
    },
    errors: {
      label: false,
      progCode: false,
      progLabel: false,
      short_description: false,
      limit_files_number: false,
      limit_status: false
    },
    submitted: false,
    ready: false
  }),
  created() {
    const globalStore = useGlobalStore();
    this.getAllForms();
    if (this.campaign === "") {
      this.campaignId = globalStore.getDatas.campaign ? globalStore.getDatas.campaign.value : 0;
    } else {
      this.campaignId = this.$props.campaign ? this.$props.campaign : 0;
    }
    this.actualLanguage = globalStore.getShortLang;
    this.coordinatorAccess = globalStore.hasCoordinatorAccess;
    this.getLanguages().then(() => {
      this.getCampaignById();
    });
    campaignService.getAllItemsAlias(this.campaignId).then((response) => {
      this.aliases = response.data;
    });
  },
  methods: {
    changed() {
      console.debug("changed");
      throw new Error("It's not an Error, please ignore.");
    },
    displayPinnedCampaignTip() {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_PINNED_CAMPAIGN_TIP"),
        text: this.translate("COM_EMUNDUS_ONBOARD_PINNED_CAMPAIGN_TIP_TEXT"),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action"
        }
      });
    },
    displayCampaignResumeTip() {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME_TIP"),
        text: this.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME_TIP_TEXT"),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action"
        }
      });
    },
    displayCampaignDescriptionTip() {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION_TIP"),
        text: this.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION_TIP_TEXT"),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action"
        }
      });
    },
    getCampaignById() {
      if (typeof this.campaignId !== "undefined" && this.campaignId !== "" && this.campaignId > 0) {
        campaignService.getCampaignById(this.campaignId).then((response) => {
          let label = response.data.campaign.label;
          this.form = response.data.campaign;
          this.$emit("getInformations", this.form);
          this.programForm = response.data.program;
          this.form.label = response.data.label;
          this.languages.forEach((language) => {
            if (this.form.label[language.sef] === "" || this.form.label[language.sef] == null) {
              this.form.label[language.sef] = label;
            }
          });
          this.form.start_date = new Date(this.form.start_date);
          this.form.end_date = new Date(this.form.end_date);
          this.ready = true;
        }).catch((e) => {
          console.log(e);
        });
      } else {
        this.form.start_date = /* @__PURE__ */ new Date();
        this.ready = true;
      }
      this.getCampaignLanguages();
      this.getAllPrograms();
    },
    getAllForms() {
      formService.getPublishedForms().then((response) => {
        if (response.status) {
          this.applicantForms = response.data.data;
        }
      }).catch((e) => {
        console.log(e);
      });
    },
    getCampaignLanguages() {
      if (this.campaignId) {
        campaignService.getCampaignLanguages(this.campaignId).then((response) => {
          this.campaignLanguages = response.data;
        });
      }
    },
    getAllPrograms() {
      programmeService.getAllPrograms("", "", 0, 0, "p.label").then((response) => {
        if (response.status) {
          this.programs = response.data.datas;
        } else {
          this.programs = [];
        }
      }).catch((e) => {
        console.log(e);
      });
      this.getYears();
    },
    getYears() {
      campaignService.getYears().then((response) => {
        this.years = response.data;
        this.years.forEach((year) => {
          this.session.push(year.schoolyear);
        });
      }).catch((e) => {
        console.log(e);
      });
    },
    async getLanguages() {
      return settingsService.getActiveLanguages().then((response) => {
        if (response) {
          this.languages = response.data;
        }
        return response;
      });
    },
    setCategory(e) {
      this.year.programmes = e.target.options[e.target.options.selectedIndex].dataset.category;
      this.programForm = this.programs.find((program) => program.code == this.form.training);
    },
    createCampaign(form_data) {
      form_data.start_date = this.formatDate(new Date(this.form.start_date));
      form_data.end_date = this.formatDate(new Date(this.form.end_date));
      form_data.languages = this.campaignLanguages.map((language) => language.value);
      campaignService.createCampaign(form_data).then((response) => {
        if (response.status == 1) {
          this.campaignId = response.data;
          this.quitFunnelOrContinue(this.quit, response.redirect);
        }
      });
    },
    createCampaignWithNoExistingProgram(programForm) {
      programmeService.createProgram(programForm).then((response) => {
        if (response.status) {
          this.form.progid = response.data.programme_id;
          this.form.training = response.data.programme_code;
          this.programForm.code = response.data.programme_code;
          if (this.campaignId > 0) {
            this.updateCampaign();
          } else {
            this.createCampaign(this.form);
          }
        } else {
          Swal.fire({
            icon: "error",
            title: this.translate("COM_EMUNDUS_ADD_CAMPAIGN_ERROR"),
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            }
          });
          this.submitted = false;
        }
      });
    },
    submit() {
      const campaignStore = useCampaignStore();
      campaignStore.setUnsavedChanges(true);
      this.errors = {
        label: false,
        alias: false,
        start_date: false,
        end_date: false,
        year: false,
        progCode: false,
        progLabel: false,
        short_description: false,
        limit_files_number: false,
        limit_status: false
      };
      if (this.form.label[this.actualLanguage] === "" || this.form.label[this.actualLanguage] == null || typeof this.form.label[this.actualLanguage] === "undefined") {
        window.scrollTo({ top: 0, behavior: "smooth" });
        this.errors.label = true;
      }
      if (this.form.alias === "" || this.form.alias == null || typeof this.form.alias === "undefined") {
        window.scrollTo({ top: 0, behavior: "smooth" });
        this.errors.alias = true;
      }
      if (this.form.end_date === "" || this.form.end_date === "0000-00-00 00:00:00") {
        window.scrollTo({ top: 0, behavior: "smooth" });
        this.errors.end_date = true;
      }
      if (this.form.start_date === "" || this.form.start_date === "0000-00-00 00:00:00") {
        window.scrollTo({ top: 0, behavior: "smooth" });
        this.errors.start_date = true;
      }
      if (this.form.year === "") {
        window.scrollTo({ top: 0, behavior: "smooth" });
        this.errors.year = true;
        document.getElementById("year").classList.add("is-invalid");
        document.getElementById("year").classList.add("!tw-border-red-600");
      }
      if (this.form.training === "") {
        if (this.isHiddenProgram) {
          if (this.programForm.label === "") {
            this.errors.progLabel = true;
          } else {
            const similarProgram = this.programs.find((program) => {
              return program.label === this.programForm.label;
            });
            if (similarProgram !== void 0) {
              this.errors.progLabel = true;
            }
          }
        } else {
          this.errors.progCode = true;
        }
      }
      if (this.errors.label || this.errors.start_date || this.errors.end_date || this.errors.year || this.errors.limit_files_number || this.errors.limit_status || this.errors.progLabel || this.errors.progCode || this.errors.alias) {
        return 0;
      }
      this.year.label = this.form.label;
      this.year.code = this.form.training;
      this.year.schoolyear = this.form.year;
      this.year.published = this.form.published;
      this.year.profile_id = this.form.profile_id;
      if (this.form.label.en === "" || this.form.label.en == null || typeof this.form.label.en == "undefined") {
        this.form.label.en = this.form.label.fr;
      }
      this.submitted = true;
      if (typeof this.campaignId !== "undefined" && this.campaignId !== null && this.campaignId !== "" && this.campaignId !== 0) {
        if (this.form.training !== "") {
          this.updateCampaign();
        } else {
          this.createCampaignWithNoExistingProgram(this.programForm);
        }
      } else {
        if (this.form.training !== "") {
          this.programForm = this.programs.find((program) => program.code === this.form.training);
          this.form.training = this.programForm.code;
          this.createCampaign(this.form);
        } else {
          this.createCampaignWithNoExistingProgram(this.programForm);
        }
      }
    },
    updateCampaign() {
      let form_data = this.form;
      form_data.training = this.programForm.code;
      form_data.start_date = this.formatDate(new Date(this.form.start_date));
      form_data.end_date = this.formatDate(new Date(this.form.end_date));
      form_data.languages = this.campaignLanguages.map((language) => language.value);
      campaignService.updateCampaign(form_data, this.campaignId).then((response) => {
        if (!response.status) {
          Swal.fire({
            icon: "error",
            title: this.translate("COM_EMUNDUS_ADD_CAMPAIGN_ERROR"),
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            }
          });
          this.submitted = false;
          return 0;
        } else {
          this.$emit("nextSection");
          this.$emit("updateHeader", this.form);
        }
      }).catch((error) => {
        console.log(error);
      });
    },
    quitFunnelOrContinue(quit, redirect = "") {
      if (quit === 0) {
        this.redirectJRoute("index.php?option=com_emundus&view=campaigns");
      } else if (quit === 1) {
        document.cookie = "campaign_" + this.campaignId + "_menu = 1; expires=Session; path=/";
        if (redirect === "") {
          redirect = "index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=" + this.campaignId + "&index=0";
        }
        this.redirectJRoute(redirect);
      }
    },
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },
    onSearchYear(value) {
      this.form.year = value;
    },
    onFormChange() {
      const campaignStore = useCampaignStore();
      campaignStore.setUnsavedChanges(true);
    },
    displayProgram() {
      if (this.isHiddenProgram) {
        document.getElementById("add-program").style = "transform: rotate(0)";
        this.form.training = this.old_training;
        this.programForm = this.old_program_form;
        document.getElementById("select_prog").removeAttribute("disabled");
      } else {
        this.old_training = this.form.training;
        this.old_program_form = this.programForm;
        this.form.training = "";
        this.programForm = {
          code: "",
          label: "",
          notes: "",
          programmes: "",
          published: 1,
          apply_online: 1
        };
        document.getElementById("add-program").style = "transform: rotate(45deg)";
        document.getElementById("select_prog").setAttribute("disabled", "disabled");
      }
      this.isHiddenProgram = !this.isHiddenProgram;
    },
    updateAlias() {
      if (!this.aliasUpdated && this.campaignId === 0) {
        let alias = this.form.label[this.actualLanguage].normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        this.form.alias = alias.replace(/[^a-zA-Z0-9_-]+/g, "-").toLowerCase();
      }
    },
    copyAliasToClipboard() {
      navigator.clipboard.writeText(window.location.origin + "/" + this.form.alias);
      Swal.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_ALIAS_COPIED"),
        icon: "success",
        showConfirmButton: false,
        customClass: {
          title: "em-swal-title",
          actions: "em-swal-single-action"
        },
        timer: 1500
      });
    },
    formatDate(date, format = "YYYY-MM-DD HH:mm:ss") {
      let year = date.getFullYear();
      let month = (1 + date.getMonth()).toString().padStart(2, "0");
      let day = date.getDate().toString().padStart(2, "0");
      let hours = date.getHours().toString().padStart(2, "0");
      let minutes = date.getMinutes().toString().padStart(2, "0");
      let seconds = date.getSeconds().toString().padStart(2, "0");
      return format.replace("YYYY", year).replace("MM", month).replace("DD", day).replace("HH", hours).replace("mm", minutes).replace("ss", seconds);
    }
  },
  computed: {
    baseUrl() {
      return window.location.origin;
    },
    sessionPlaceholder() {
      let oneYearFromNow = /* @__PURE__ */ new Date();
      oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);
      return (/* @__PURE__ */ new Date()).getFullYear() + " - " + oneYearFromNow.getFullYear();
    },
    languageOptions() {
      return this.languages.map((language) => {
        return {
          label: language.title,
          value: language.lang_id
        };
      });
    },
    programLanguages() {
      let languages = [];
      if (this.form.training !== "") {
        let programLang = [];
        this.programs.forEach((program) => {
          if (program.code === this.form.training) {
            programLang = program.language_ids != null && Array.isArray(program.language_ids) ? program.language_ids : [];
          }
        });
        if (programLang.length > 0) {
          languages = programLang.map((language_id) => {
            return this.languages.find((language) => language.lang_id == language_id);
          });
        }
      }
      return languages;
    }
  },
  watch: {
    "form.start_date": function(val) {
      if (typeof val === "object") {
        let startDate = new Date(val);
        let endDate = new Date(this.form.end_date);
        this.minDate = new Date(startDate.setDate(startDate.getDate() + 1)).toISOString();
        if (endDate < this.minDate) {
          this.form.end_date = this.minDate;
        }
      }
    },
    "form.alias": function(val, oldVal) {
      if (val !== oldVal && val && val !== "") {
        this.form.alias = val.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-zA-Z0-9_-]+/g, "-").toLowerCase();
        if (typeof this.aliases !== "undefined" && this.aliases.includes(val)) {
          this.form.alias = val + "-1";
        }
      }
    }
  }
};
const _hoisted_1 = { class: "campaigns__add-campaign" };
const _hoisted_2 = { key: 0 };
const _hoisted_3 = { class: "tw-ml-2 tw-text-neutral-900" };
const _hoisted_4 = { class: "tw-mt-4" };
const _hoisted_5 = { class: "tw-mt-2" };
const _hoisted_6 = { class: "tw-mt-1 tw-text-red-600" };
const _hoisted_7 = { class: "tw-flex tw-flex-col tw-gap-4" };
const _hoisted_8 = { id: "campaign-label-wrapper" };
const _hoisted_9 = {
  for: "campLabel",
  class: "tw-font-medium"
};
const _hoisted_10 = {
  key: 0,
  id: "error-campaign-name",
  class: "tw-mb-1 tw-mt-1 tw-text-red-600"
};
const _hoisted_11 = {
  for: "alias",
  class: "tw-font-medium"
};
const _hoisted_12 = { class: "tw-text-base tw-text-neutral-600" };
const _hoisted_13 = { class: "tw-mt-1 tw-flex tw-items-center tw-gap-2" };
const _hoisted_14 = { class: "tw-whitespace-nowrap" };
const _hoisted_15 = { class: "tw-w-full" };
const _hoisted_16 = {
  key: 0,
  class: "tw-absolute tw-mb-1 tw-mt-1 tw-text-red-600"
};
const _hoisted_17 = { class: "tw-grid tw-grid-cols-2 tw-gap-1.5" };
const _hoisted_18 = {
  for: "startDate",
  class: "tw-font-medium"
};
const _hoisted_19 = ["value"];
const _hoisted_20 = {
  key: 0,
  class: "tw-absolute tw-mb-1 tw-mt-1 tw-text-red-600"
};
const _hoisted_21 = {
  for: "endDate",
  class: "tw-font-medium"
};
const _hoisted_22 = ["value"];
const _hoisted_23 = {
  key: 0,
  class: "tw-absolute tw-mb-1 tw-mt-1 tw-text-red-600"
};
const _hoisted_24 = {
  for: "year",
  class: "tw-font-medium"
};
const _hoisted_25 = { class: "tw-text-base tw-text-neutral-600" };
const _hoisted_26 = {
  key: 0,
  class: "tw-mb-1 tw-mt-1 tw-text-red-600"
};
const _hoisted_27 = { class: "tw-flex tw-items-center" };
const _hoisted_28 = { class: "em-toggle" };
const _hoisted_29 = {
  for: "published",
  class: "tw-ml-2"
};
const _hoisted_30 = { class: "tw-flex tw-items-center" };
const _hoisted_31 = { class: "em-toggle" };
const _hoisted_32 = {
  for: "visible",
  class: "tw-ml-2 tw-flex tw-items-center"
};
const _hoisted_33 = { class: "tw-flex tw-items-center" };
const _hoisted_34 = { class: "em-toggle" };
const _hoisted_35 = {
  for: "pinned",
  class: "tw-ml-2 tw-flex tw-items-center"
};
const _hoisted_36 = { class: "tw-flex tw-flex-col tw-gap-4" };
const _hoisted_37 = { id: "campResume" };
const _hoisted_38 = { class: "tw-flex tw-items-center" };
const _hoisted_39 = { class: "tw-mb-0 tw-font-medium" };
const _hoisted_40 = { class: "tw-flex tw-items-center" };
const _hoisted_41 = { class: "tw-mb-0 tw-font-medium" };
const _hoisted_42 = {
  key: 0,
  id: "campDescription"
};
const _hoisted_43 = { class: "tw-flex tw-flex-col tw-gap-4" };
const _hoisted_44 = { class: "tw-mt-2" };
const _hoisted_45 = { class: "tw-flex tw-items-center" };
const _hoisted_46 = ["disabled"];
const _hoisted_47 = { value: "" };
const _hoisted_48 = ["value", "data-category"];
const _hoisted_49 = ["title"];
const _hoisted_50 = {
  key: 0,
  class: "tw-mb-1 tw-mt-1 tw-text-red-600"
};
const _hoisted_51 = { key: 0 };
const _hoisted_52 = {
  for: "prog_label",
  class: "tw-font-medium"
};
const _hoisted_53 = {
  key: 0,
  class: "tw-mb-1 tw-mt-1 tw-text-red-600"
};
const _hoisted_54 = { class: "tw-text-red-600" };
const _hoisted_55 = {
  class: "tw-flex tw-flex-col tw-gap-4",
  id: "campaign-form-container"
};
const _hoisted_56 = { class: "tw-text-sm tw-text-neutral-500" };
const _hoisted_57 = { class: "tw-font-medium" };
const _hoisted_58 = { class: "tw-mb-1 tw-mt-1 tw-flex tw-items-center" };
const _hoisted_59 = { value: "0" };
const _hoisted_60 = ["value"];
const _hoisted_61 = {
  key: 0,
  class: "tw-mb-8 tw-flex tw-flex-col tw-gap-4",
  id: "select-campaign-languages"
};
const _hoisted_62 = { class: "tw-text-sm tw-text-neutral-500" };
const _hoisted_63 = {
  key: 0,
  id: "program-languages",
  class: "alert alert-info tw-mb-1 tw-flex tw-p-4"
};
const _hoisted_64 = { class: "tw-text-sm tw-font-light" };
const _hoisted_65 = { class: "tw-flex tw-justify-end" };
const _hoisted_66 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_DatePicker = resolveComponent("DatePicker");
  const _component_autocomplete = resolveComponent("autocomplete");
  const _component_tip_tap_editor = resolveComponent("tip-tap-editor");
  const _component_multiselect = resolveComponent("multiselect");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    typeof _ctx.campaignId == "undefined" || _ctx.campaignId == 0 ? (openBlock(), createElementBlock("div", _hoisted_2, [
      createBaseVNode("div", {
        class: "tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-rounded-md tw-px-2 tw-py-1 hover:tw-bg-neutral-300",
        onClick: _cache[0] || (_cache[0] = ($event) => $options.redirectJRoute("index.php?option=com_emundus&view=campaigns"))
      }, [
        _cache[31] || (_cache[31] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-600" }, "navigate_before", -1)),
        createBaseVNode("span", _hoisted_3, toDisplayString(_ctx.translate("BACK")), 1)
      ]),
      createBaseVNode("div", _hoisted_4, [
        createBaseVNode("h1", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_CAMPAIGN")), 1),
        createBaseVNode("div", _hoisted_5, [
          createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_INFORMATIONS_DESC")), 1),
          createBaseVNode("p", _hoisted_6, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE")), 1)
        ])
      ]),
      _cache[32] || (_cache[32] = createBaseVNode("hr", { class: "tw-mb-4 tw-mt-1.5" }, null, -1))
    ])) : createCommentVNode("", true),
    createBaseVNode("div", null, [
      _ctx.ready ? (openBlock(), createElementBlock("form", {
        key: 0,
        onSubmit: _cache[30] || (_cache[30] = withModifiers((...args) => $options.submit && $options.submit(...args), ["prevent"])),
        class: "emundus-form fabrikForm"
      }, [
        createBaseVNode("div", _hoisted_7, [
          createBaseVNode("div", _hoisted_8, [
            createBaseVNode("label", _hoisted_9, [
              createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_CAMPNAME")) + " ", 1),
              _cache[33] || (_cache[33] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
            ]),
            withDirectives(createBaseVNode("input", {
              id: "campLabel",
              type: "text",
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => _ctx.form.label[_ctx.actualLanguage] = $event),
              required: "",
              class: normalizeClass([{ "is-invalid !tw-border-red-600": _ctx.errors.label }, "form-control fabrikinput tw-mt-1 tw-w-full"]),
              onFocusout: _cache[2] || (_cache[2] = ($event) => $options.onFormChange()),
              onKeyup: _cache[3] || (_cache[3] = ($event) => $options.updateAlias())
            }, null, 34), [
              [vModelText, _ctx.form.label[_ctx.actualLanguage]]
            ]),
            _ctx.errors.label ? (openBlock(), createElementBlock("div", _hoisted_10, [
              createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FORM_REQUIRED_NAME")), 1)
            ])) : createCommentVNode("", true)
          ]),
          createBaseVNode("div", null, [
            createBaseVNode("label", _hoisted_11, [
              createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_ALIAS")) + " ", 1),
              _cache[34] || (_cache[34] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
            ]),
            createBaseVNode("div", null, [
              createBaseVNode("span", _hoisted_12, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_ALIAS_HELPTEXT")), 1),
              createBaseVNode("div", _hoisted_13, [
                createBaseVNode("span", _hoisted_14, toDisplayString($options.baseUrl) + "/", 1),
                createBaseVNode("div", _hoisted_15, [
                  withDirectives(createBaseVNode("input", {
                    id: "alias",
                    type: "text",
                    "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => _ctx.form.alias = $event),
                    required: "",
                    class: normalizeClass([{ "is-invalid !tw-border-red-600": _ctx.errors.alias }, "form-control fabrikinput tw-w-full"]),
                    onFocusout: _cache[5] || (_cache[5] = ($event) => $options.onFormChange()),
                    onKeyup: _cache[6] || (_cache[6] = ($event) => _ctx.form.alias !== "" ? _ctx.aliasUpdated = true : _ctx.aliasUpdated = false)
                  }, null, 34), [
                    [vModelText, _ctx.form.alias]
                  ]),
                  _ctx.errors.alias ? (openBlock(), createElementBlock("div", _hoisted_16, [
                    createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FORM_REQUIRED_LINK")), 1)
                  ])) : createCommentVNode("", true)
                ]),
                createBaseVNode("span", {
                  class: "material-symbols-outlined tw-cursor-pointer",
                  onClick: _cache[7] || (_cache[7] = ($event) => $options.copyAliasToClipboard())
                }, "content_copy")
              ])
            ])
          ]),
          createBaseVNode("div", _hoisted_17, [
            createBaseVNode("div", null, [
              createBaseVNode("label", _hoisted_18, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_STARTDATE")) + " ", 1),
                _cache[35] || (_cache[35] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
              ]),
              createVNode(_component_DatePicker, {
                id: "campaign_start_date",
                modelValue: _ctx.form.start_date,
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => _ctx.form.start_date = $event),
                keepVisibleOnInput: true,
                "time-accuracy": 2,
                mode: "dateTime",
                is24hr: "",
                "hide-time-header": "",
                "title-position": "left",
                "input-debounce": 500,
                popover: { visibility: "focus" },
                locale: _ctx.actualLanguage
              }, {
                default: withCtx(({ inputValue, inputEvents }) => [
                  createBaseVNode("input", mergeProps({ value: inputValue }, toHandlers(inputEvents, true), {
                    class: ["form-control fabrikinput tw-mt-1 tw-w-full", {
                      "is-invalid !tw-border-red-600": _ctx.errors.start_date
                    }],
                    id: "start_date_input"
                  }), null, 16, _hoisted_19),
                  _ctx.errors.start_date ? (openBlock(), createElementBlock("div", _hoisted_20, [
                    createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FORM_REQUIRED_START_DATE")), 1)
                  ])) : createCommentVNode("", true)
                ]),
                _: 1
              }, 8, ["modelValue", "locale"])
            ]),
            createBaseVNode("div", null, [
              createBaseVNode("div", null, [
                createBaseVNode("label", _hoisted_21, [
                  createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_ENDDATE")) + " ", 1),
                  _cache[36] || (_cache[36] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
                ]),
                createVNode(_component_DatePicker, {
                  id: "campaign_end_date",
                  modelValue: _ctx.form.end_date,
                  "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => _ctx.form.end_date = $event),
                  keepVisibleOnInput: true,
                  popover: { visibility: "focus" },
                  "time-accuracy": 2,
                  mode: "dateTime",
                  is24hr: "",
                  "hide-time-header": "",
                  "title-position": "left",
                  "min-date": _ctx.minDate,
                  "input-debounce": 500,
                  locale: _ctx.actualLanguage
                }, {
                  default: withCtx(({ inputValue, inputEvents }) => [
                    createBaseVNode("input", mergeProps({ value: inputValue }, toHandlers(inputEvents, true), {
                      class: ["form-control fabrikinput tw-mt-1 tw-w-full", {
                        "is-invalid !tw-border-red-600": _ctx.errors.end_date
                      }],
                      id: "end_date_input"
                    }), null, 16, _hoisted_22),
                    _ctx.errors.end_date ? (openBlock(), createElementBlock("div", _hoisted_23, [
                      createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FORM_REQUIRED_END_DATE")), 1)
                    ])) : createCommentVNode("", true)
                  ]),
                  _: 1
                }, 8, ["modelValue", "min-date", "locale"])
              ])
            ])
          ]),
          createBaseVNode("div", null, [
            createBaseVNode("label", _hoisted_24, [
              createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_PICKYEAR")) + " ", 1),
              _cache[37] || (_cache[37] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
            ]),
            createBaseVNode("div", null, [
              createBaseVNode("span", _hoisted_25, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_PICKYEAR_HELPTEXT")), 1),
              createVNode(_component_autocomplete, {
                id: "year",
                onSearched: $options.onSearchYear,
                items: this.session,
                year: _ctx.form.year,
                name: $options.sessionPlaceholder
              }, null, 8, ["onSearched", "items", "year", "name"]),
              _ctx.errors.year ? (openBlock(), createElementBlock("div", _hoisted_26, [
                createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FORM_REQUIRED_YEAR")), 1)
              ])) : createCommentVNode("", true)
            ])
          ]),
          createBaseVNode("div", _hoisted_27, [
            createBaseVNode("div", _hoisted_28, [
              withDirectives(createBaseVNode("input", {
                type: "checkbox",
                "true-value": "1",
                "false-value": "0",
                class: "em-toggle-check tw-mt-2",
                id: "published",
                name: "published",
                "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => _ctx.form.published = $event),
                onClick: _cache[11] || (_cache[11] = ($event) => $options.onFormChange())
              }, null, 512), [
                [vModelCheckbox, _ctx.form.published]
              ]),
              _cache[38] || (_cache[38] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
              _cache[39] || (_cache[39] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
            ]),
            createBaseVNode("span", _hoisted_29, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CAMPAIGN_PUBLISH")), 1)
          ]),
          createBaseVNode("div", _hoisted_30, [
            createBaseVNode("div", _hoisted_31, [
              withDirectives(createBaseVNode("input", {
                type: "checkbox",
                "true-value": "0",
                "false-value": "1",
                class: "em-toggle-check tw-mt-2",
                id: "visible",
                name: "visible",
                "onUpdate:modelValue": _cache[12] || (_cache[12] = ($event) => _ctx.form.visible = $event),
                onClick: _cache[13] || (_cache[13] = ($event) => $options.onFormChange())
              }, null, 512), [
                [vModelCheckbox, _ctx.form.visible]
              ]),
              _cache[40] || (_cache[40] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
              _cache[41] || (_cache[41] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
            ]),
            createBaseVNode("span", _hoisted_32, toDisplayString(_ctx.translate("COM_EMUNDUS_CAMPAIGNS_VISIBLE")), 1)
          ]),
          createBaseVNode("div", _hoisted_33, [
            createBaseVNode("div", _hoisted_34, [
              withDirectives(createBaseVNode("input", {
                type: "checkbox",
                "true-value": "1",
                "false-value": "0",
                class: "em-toggle-check tw-mt-2",
                id: "pinned",
                name: "pinned",
                "onUpdate:modelValue": _cache[14] || (_cache[14] = ($event) => _ctx.form.pinned = $event),
                onClick: _cache[15] || (_cache[15] = ($event) => $options.onFormChange())
              }, null, 512), [
                [vModelCheckbox, _ctx.form.pinned]
              ]),
              _cache[42] || (_cache[42] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
              _cache[43] || (_cache[43] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
            ]),
            createBaseVNode("span", _hoisted_35, [
              createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_CAMPAIGNS_PIN")) + " ", 1),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-ml-1 tw-cursor-pointer tw-text-base tw-text-neutral-600",
                onClick: _cache[16] || (_cache[16] = (...args) => $options.displayPinnedCampaignTip && $options.displayPinnedCampaignTip(...args))
              }, "help_outline")
            ])
          ])
        ]),
        _cache[48] || (_cache[48] = createBaseVNode("hr", { class: "tw-mb-4 tw-mt-1.5" }, null, -1)),
        createBaseVNode("div", _hoisted_36, [
          createBaseVNode("h2", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_INFORMATION")), 1),
          createBaseVNode("div", _hoisted_37, [
            createBaseVNode("div", _hoisted_38, [
              createBaseVNode("label", _hoisted_39, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME")), 1),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-ml-1 tw-cursor-pointer tw-text-base tw-text-neutral-600",
                onClick: _cache[17] || (_cache[17] = (...args) => $options.displayCampaignResumeTip && $options.displayCampaignResumeTip(...args))
              }, "help_outline")
            ]),
            createVNode(_component_tip_tap_editor, {
              modelValue: _ctx.form.short_description,
              "onUpdate:modelValue": _cache[18] || (_cache[18] = ($event) => _ctx.form.short_description = $event),
              "editor-content-height": "5em",
              class: normalizeClass("tw-mt-1"),
              locale: "fr",
              preset: "basic",
              "toolbar-classes": ["tw-bg-white"],
              "editor-content-classes": ["tw-bg-white"],
              placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME")
            }, null, 8, ["modelValue", "placeholder"])
          ]),
          createBaseVNode("div", null, [
            createBaseVNode("div", _hoisted_40, [
              createBaseVNode("label", _hoisted_41, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION")), 1),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-ml-1 tw-cursor-pointer tw-text-base tw-text-neutral-600",
                onClick: _cache[19] || (_cache[19] = (...args) => $options.displayCampaignDescriptionTip && $options.displayCampaignDescriptionTip(...args))
              }, "help_outline")
            ]),
            typeof _ctx.form.description != "undefined" ? (openBlock(), createElementBlock("div", _hoisted_42, [
              createVNode(_component_tip_tap_editor, {
                modelValue: _ctx.form.description,
                "onUpdate:modelValue": _cache[20] || (_cache[20] = ($event) => _ctx.form.description = $event),
                "upload-url": "/index.php?option=com_emundus&controller=settings&task=uploadmedia",
                "editor-content-height": "30em",
                class: normalizeClass("tw-mt-1"),
                locale: "fr",
                preset: "custom",
                plugins: _ctx.editorPlugins,
                "toolbar-classes": ["tw-bg-white"],
                "editor-content-classes": ["tw-bg-white"],
                placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION")
              }, null, 8, ["modelValue", "plugins", "placeholder"])
            ])) : createCommentVNode("", true)
          ])
        ]),
        _cache[49] || (_cache[49] = createBaseVNode("hr", { class: "tw-mb-4 tw-mt-1.5" }, null, -1)),
        createBaseVNode("div", _hoisted_43, [
          createBaseVNode("div", null, [
            createBaseVNode("h2", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM")), 1),
            createBaseVNode("div", _hoisted_44, [
              createBaseVNode("p", null, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGRAM_INTRO_DESC")) + " ", 1),
                _cache[44] || (_cache[44] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
              ])
            ])
          ]),
          createBaseVNode("div", null, [
            createBaseVNode("div", _hoisted_45, [
              withDirectives(createBaseVNode("select", {
                id: "select_prog",
                class: normalizeClass(["form-control fabrikinput tw-w-full", { "is-invalid !tw-border-red-600": _ctx.errors.progCode }]),
                "onUpdate:modelValue": _cache[21] || (_cache[21] = ($event) => _ctx.form.training = $event),
                onChange: _cache[22] || (_cache[22] = (...args) => $options.setCategory && $options.setCategory(...args)),
                disabled: this.programs.length <= 0
              }, [
                createBaseVNode("option", _hoisted_47, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_CHOOSEPROG")), 1),
                (openBlock(true), createElementBlock(Fragment, null, renderList(_ctx.programs, (item, index) => {
                  return openBlock(), createElementBlock("option", {
                    value: item.code,
                    "data-category": item.programmes,
                    key: index
                  }, toDisplayString(item.label && item.label[_ctx.actualLanguage] !== null && typeof item.label[_ctx.actualLanguage] != "undefined" ? item.label[_ctx.actualLanguage] : item.label), 9, _hoisted_48);
                }), 128))
              ], 42, _hoisted_46), [
                [vModelSelect, _ctx.form.training]
              ]),
              _ctx.coordinatorAccess != 0 ? (openBlock(), createElementBlock("button", {
                key: 0,
                title: _ctx.translate("COM_EMUNDUS_ONBOARD_ADDPROGRAM"),
                type: "button",
                id: "add-program",
                class: "tw-ml-2 tw-bg-transparent",
                onClick: _cache[23] || (_cache[23] = (...args) => $options.displayProgram && $options.displayProgram(...args))
              }, _cache[45] || (_cache[45] = [
                createBaseVNode("span", { class: "material-symbols-outlined em-main-500-color" }, "add_circle_outline", -1)
              ]), 8, _hoisted_49)) : createCommentVNode("", true)
            ]),
            _ctx.errors.progCode ? (openBlock(), createElementBlock("div", _hoisted_50, [
              createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FORM_REQUIRED_PROGRAM")), 1)
            ])) : createCommentVNode("", true)
          ]),
          createVNode(Transition, { name: "slide-fade" }, {
            default: withCtx(() => [
              _ctx.isHiddenProgram ? (openBlock(), createElementBlock("div", _hoisted_51, [
                createBaseVNode("div", null, [
                  createBaseVNode("div", null, [
                    createBaseVNode("label", _hoisted_52, [
                      createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROGNAME")) + " ", 1),
                      _cache[46] || (_cache[46] = createBaseVNode("span", { class: "tw-text-red-600" }, "*", -1))
                    ]),
                    withDirectives(createBaseVNode("input", {
                      type: "text",
                      id: "prog_label",
                      class: normalizeClass(["form-control fabrikinput tw-mt-1 tw-w-full", {
                        "is-invalid !tw-border-red-600": _ctx.errors.progLabel
                      }]),
                      placeholder: " ",
                      "onUpdate:modelValue": _cache[24] || (_cache[24] = ($event) => _ctx.programForm.label = $event)
                    }, null, 2), [
                      [vModelText, _ctx.programForm.label]
                    ])
                  ]),
                  _ctx.errors.progLabel ? (openBlock(), createElementBlock("div", _hoisted_53, [
                    createBaseVNode("span", _hoisted_54, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PROG_REQUIRED_LABEL")), 1)
                  ])) : createCommentVNode("", true)
                ])
              ])) : createCommentVNode("", true)
            ]),
            _: 1
          })
        ]),
        _cache[50] || (_cache[50] = createBaseVNode("hr", { class: "tw-mb-4 tw-mt-1.5" }, null, -1)),
        createBaseVNode("div", _hoisted_55, [
          createBaseVNode("h2", null, [
            createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_FORM")) + " ", 1),
            createBaseVNode("i", _hoisted_56, toDisplayString(_ctx.translate("COM_EMUNDUS_OPTIONAL")), 1)
          ]),
          createBaseVNode("div", null, [
            createBaseVNode("label", _hoisted_57, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_FORM_DESC")), 1),
            createBaseVNode("div", _hoisted_58, [
              withDirectives(createBaseVNode("select", {
                class: "tw-w-full",
                "onUpdate:modelValue": _cache[25] || (_cache[25] = ($event) => _ctx.form.profile_id = $event)
              }, [
                createBaseVNode("option", _hoisted_59, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CHOOSE_FORM")), 1),
                (openBlock(true), createElementBlock(Fragment, null, renderList(_ctx.applicantForms, (applicantForm) => {
                  return openBlock(), createElementBlock("option", {
                    key: applicantForm.id,
                    value: applicantForm.id
                  }, toDisplayString(applicantForm.label), 9, _hoisted_60);
                }), 128))
              ], 512), [
                [vModelSelect, _ctx.form.profile_id]
              ]),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-ml-2 tw-cursor-pointer",
                onClick: _cache[26] || (_cache[26] = (...args) => $options.getAllForms && $options.getAllForms(...args))
              }, "refresh")
            ]),
            createBaseVNode("a", {
              onClick: _cache[27] || (_cache[27] = ($event) => $options.redirectJRoute("index.php?option=com_emundus&view=form")),
              target: "_blank",
              class: "tw-cursor-pointer tw-underline"
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ACCESS_TO_FORMS_LIST")), 1)
          ])
        ]),
        _cache[51] || (_cache[51] = createBaseVNode("hr", { class: "tw-mb-4 tw-mt-1.5" }, null, -1)),
        $options.languageOptions.length > 1 ? (openBlock(), createElementBlock("div", _hoisted_61, [
          createBaseVNode("h2", null, [
            createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_LANGUAGES")) + " ", 1),
            createBaseVNode("i", _hoisted_62, toDisplayString(_ctx.translate("COM_EMUNDUS_OPTIONAL")), 1)
          ]),
          createBaseVNode("div", null, [
            $options.programLanguages.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_63, [
              _cache[47] || (_cache[47] = createBaseVNode("span", { class: "material-symbols-outlined tw-mr-2" }, "info", -1)),
              createBaseVNode("p", _hoisted_64, [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM_LANGUAGES")) + " ", 1),
                (openBlock(true), createElementBlock(Fragment, null, renderList($options.programLanguages, (language, index) => {
                  return openBlock(), createElementBlock("strong", {
                    key: language.lang_id
                  }, toDisplayString(language.title) + toDisplayString(index < $options.programLanguages.length - 1 ? ", " : ""), 1);
                }), 128))
              ])
            ])) : createCommentVNode("", true),
            createVNode(_component_multiselect, {
              modelValue: _ctx.campaignLanguages,
              "onUpdate:modelValue": _cache[28] || (_cache[28] = ($event) => _ctx.campaignLanguages = $event),
              label: "label",
              "track-by": "value",
              options: $options.languageOptions,
              multiple: true,
              taggable: false,
              placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_CHOOSE_LANGUAGE"),
              "select-label": "",
              "selected-label": "",
              "deselect-label": ""
            }, null, 8, ["modelValue", "options", "placeholder"])
          ])
        ])) : createCommentVNode("", true),
        createBaseVNode("div", _hoisted_65, [
          createBaseVNode("button", {
            id: "save-btn",
            type: "button",
            class: "tw-btn-primary tw-w-auto",
            onClick: _cache[29] || (_cache[29] = ($event) => {
              _ctx.quit = 1;
              $options.submit();
            })
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER")), 1)
        ])
      ], 32)) : createCommentVNode("", true)
    ]),
    _ctx.submitted || !_ctx.ready ? (openBlock(), createElementBlock("div", _hoisted_66)) : createCommentVNode("", true)
  ]);
}
const addCampaign = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-81f393d3"]]);
export {
  addCampaign as default
};
