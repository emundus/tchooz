import { N as FetchClient, _ as _export_sfc, A as fileService, o as openBlock, c as createElementBlock, d as createBaseVNode, F as Fragment, e as renderList, n as normalizeClass, t as toDisplayString, w as withDirectives, v as vShow, b as createCommentVNode, T as Tabs, r as resolveComponent, h as createVNode, O as Comments, Q as Attachments, M as Modal, R as errors, U as filesService, V as axios, a as createBlock, f as withCtx, j as normalizeStyle } from "./app_emundus.js";
import { M as Messages } from "./Messages.js";
import "./Parameter.js";
import "./index.js";
import "./EventBooking.js";
import "./events2.js";
import "./Info.js";
import "./AttachDocument.js";
import "./vue-dropzone.js";
import "./index2.js";
import "./Skeleton.js";
const fetchClient = new FetchClient("evaluation");
const evaluationService = {
  async getEvaluationsForms(fnum, readonly = false) {
    try {
      return await fetchClient.get("getevaluationsforms", {
        fnum,
        readonly: readonly ? 1 : 0
      });
    } catch (e) {
      return false;
    }
  },
  async getEvaluations(stepId, ccid) {
    try {
      return await fetchClient.get("getstepevaluationsforfile", {
        step_id: stepId,
        ccid
      });
    } catch (e) {
      return false;
    }
  }
};
const _sfc_main$3 = {
  name: "Evaluations",
  props: {
    fnum: {
      type: String,
      required: true
    },
    defaultCcid: {
      type: Number,
      default: 0
    },
    onlyEditionAccess: {
      type: Boolean,
      default: true
    }
  },
  data() {
    return {
      evaluations: [],
      selectedTab: 0,
      ccid: 0,
      loading: false
    };
  },
  mounted() {
    this.getFileId();
    this.getEvaluationsForms();
  },
  methods: {
    getFileId() {
      if (this.defaultCcid > 0) {
        this.ccid = this.defaultCcid;
      } else {
        fileService.getFileIdFromFnum(this.fnum).then((response) => {
          if (response.status) {
            this.ccid = response.data;
          }
        });
      }
    },
    getEvaluationsForms() {
      this.loading = true;
      evaluationService.getEvaluationsForms(this.fnum).then((response) => {
        if (this.onlyEditionAccess) {
          this.evaluations = response.data.filter((evaluation) => evaluation.user_access.can_edit);
        } else {
          this.evaluations = response.data;
        }
        if (this.evaluations.length > 0) {
          this.selectedTab = this.evaluations[0].id;
        } else {
          this.loading = false;
        }
      }).catch((error) => {
        console.log(error);
      });
    },
    iframeLoaded(event) {
      this.loading = false;
      let iframeDoc = event.target.contentDocument || event.target.contentWindow.document;
      if (iframeDoc.querySelector(".emundus-form")) {
        iframeDoc.querySelector(".emundus-form").classList.add("eval-form-split-view");
        iframeDoc.querySelector("body .platform-content > div").classList.add("eval-form-split-view-container");
      }
    }
  },
  computed: {
    selectedEvaluation() {
      return this.evaluations.length > 0 ? this.evaluations.find((evaluation) => evaluation.id === this.selectedTab) : {};
    }
  }
};
const _hoisted_1$3 = { id: "evaluations-container" };
const _hoisted_2$3 = {
  key: 0,
  class: "tw-flex tw-h-full tw-flex-col"
};
const _hoisted_3$2 = { class: "tw-pt-1" };
const _hoisted_4$2 = { class: "tw-flex tw-list-none tw-flex-row" };
const _hoisted_5$2 = ["onClick"];
const _hoisted_6$1 = ["src"];
const _hoisted_7$1 = {
  key: 0,
  class: "em-page-loader"
};
const _hoisted_8$1 = {
  key: 1,
  class: "tw-m-2 tw-rounded tw-border tw-border-blue-500 tw-bg-blue-50 tw-p-2 tw-text-center tw-text-neutral-900"
};
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$3, [
    $data.evaluations.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_2$3, [
      createBaseVNode("nav", _hoisted_3$2, [
        createBaseVNode("ul", _hoisted_4$2, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.evaluations, (evaluation) => {
            return openBlock(), createElementBlock("li", {
              key: evaluation.id,
              class: normalizeClass(["tw-cursor-pointer tw-rounded-t-lg tw-px-2.5 tw-py-3 tw-shadow", {
                "em-bg-main-500 em-text-neutral-300": $data.selectedTab === evaluation.id
              }]),
              onClick: ($event) => $data.selectedTab = evaluation.id
            }, toDisplayString(evaluation.label), 11, _hoisted_5$2);
          }), 128))
        ])
      ]),
      $data.ccid > 0 && $options.selectedEvaluation && $options.selectedEvaluation.form_id ? withDirectives((openBlock(), createElementBlock("iframe", {
        src: $options.selectedEvaluation.url,
        class: "iframe-evaluation-list tw-w-full tw-grow tw-bg-coordinator-bg tw-p-6",
        key: $data.selectedTab,
        onLoad: _cache[0] || (_cache[0] = ($event) => $options.iframeLoaded($event))
      }, null, 40, _hoisted_6$1)), [
        [vShow, !$data.loading]
      ]) : createCommentVNode("", true),
      createBaseVNode("div", null, [
        $data.loading ? (openBlock(), createElementBlock("div", _hoisted_7$1)) : createCommentVNode("", true)
      ])
    ])) : (openBlock(), createElementBlock("p", _hoisted_8$1, toDisplayString(_ctx.translate("COM_EMUNDUS_EVALUATIONS_LIST_NO_EDITABLE_EVALUATIONS")), 1))
  ]);
}
const Evaluations = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-ca80c115"]]);
const _sfc_main$2 = {
  name: "EvaluationList",
  props: {
    ccid: {
      type: Number,
      required: true
    },
    step: {
      type: Object,
      required: true
    }
  },
  data: () => {
    return {
      evaluations: [],
      selectedEvaluation: 0
    };
  },
  components: {
    Tabs
  },
  created() {
    this.getEvaluations();
  },
  methods: {
    getEvaluations() {
      evaluationService.getEvaluations(this.step.id, this.ccid).then((response) => {
        this.evaluations = response.data;
        if (this.evaluations.length > 0) {
          this.selectedEvaluation = this.evaluations[0];
        }
      }).catch((error) => {
        console.log(error);
      });
    },
    onChangeTab(tabId) {
      this.selectedEvaluation = this.evaluations.find((evaluation) => {
        return evaluation.id == tabId;
      });
    }
  },
  computed: {
    evaluationsTabs() {
      return this.evaluations.map((evaluation, index) => {
        return {
          id: evaluation.id,
          name: evaluation.evaluator_name,
          displayed: true,
          active: index == 0,
          icon: null
        };
      });
    }
  }
};
const _hoisted_1$2 = ["id"];
const _hoisted_2$2 = { class: "tw-mb-4" };
const _hoisted_3$1 = {
  key: 0,
  class: "tw-h-full tw-p-4"
};
const _hoisted_4$1 = ["src"];
const _hoisted_5$1 = {
  key: 1,
  class: "tw-m-2 tw-rounded tw-border tw-border-blue-500 tw-bg-blue-50 tw-p-2 tw-text-center tw-text-neutral-900"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Tabs = resolveComponent("Tabs");
  return openBlock(), createElementBlock("div", {
    id: "evaluation-step-" + $props.step.id + "-list"
  }, [
    createBaseVNode("h2", _hoisted_2$2, toDisplayString(_ctx.translate("COM_EMUNDUS_EVALUATIONS_LIST")), 1),
    _ctx.evaluations.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_3$1, [
      createVNode(_component_Tabs, {
        tabs: $options.evaluationsTabs,
        classes: "tw-overflow-x-scroll tw-flex tw-items-center tw-justify-start tw-gap-2",
        onChangeTabActive: $options.onChangeTab
      }, null, 8, ["tabs", "onChangeTabActive"]),
      (openBlock(), createElementBlock("iframe", {
        src: _ctx.selectedEvaluation.url,
        key: _ctx.selectedEvaluation.id,
        class: "iframe-selected-evaluation tw-w-full"
      }, null, 8, _hoisted_4$1))
    ])) : (openBlock(), createElementBlock("p", _hoisted_5$1, toDisplayString(_ctx.translate("COM_EMUNDUS_EVALUATIONS_LIST_NO_EVALUATIONS")), 1))
  ], 8, _hoisted_1$2);
}
const EvaluationList = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-754fc6df"]]);
const _sfc_main$1 = {
  name: "Synthesis",
  props: {
    fnum: {
      type: String,
      required: true
    },
    content: {
      type: String,
      default: ""
    }
  },
  data() {
    return {
      synthesis: "",
      loading: true,
      error: false
    };
  },
  mounted() {
    if (!this.content) {
      this.getSynthesis();
    } else {
      this.synthesis = this.content;
      this.loading = false;
    }
  },
  methods: {
    getSynthesis() {
      this.loading = true;
      this.error = false;
      fileService.getFileSynthesis(this.fnum).then((response) => {
        this.synthesis = response.data;
        this.loading = false;
      }).catch((error) => {
        this.error = true;
        this.loading = false;
        console.error("Error fetching synthesis:", error);
      });
    }
  }
};
const _hoisted_1$1 = {
  id: "application-synthesis",
  class: "tw-m-4 tw-rounded tw-border tw-p-4"
};
const _hoisted_2$1 = ["innerHTML"];
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    !$data.loading && !$data.error ? (openBlock(), createElementBlock("div", {
      key: 0,
      innerHTML: $data.synthesis
    }, null, 8, _hoisted_2$1)) : createCommentVNode("", true)
  ]);
}
const Synthesis = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "ApplicationSingle",
  components: {
    Synthesis,
    Messages,
    EvaluationList,
    Comments,
    Attachments,
    Modal,
    Evaluations
  },
  props: {
    file: Object | String,
    type: String,
    user: {
      type: String,
      required: true
    },
    ratio: {
      type: String,
      default: "66/33"
    },
    context: {
      type: String,
      default: ""
    },
    defaultTabs: {
      type: Array,
      default: () => []
    },
    fullname: {
      type: String,
      required: true
    },
    applicant: {
      type: Boolean,
      default: false
    }
  },
  mixins: [errors],
  data: () => ({
    showModal: true,
    fnums: [],
    selectedFile: null,
    applicationform: "",
    selected: "application",
    tabs: [
      {
        label: "COM_EMUNDUS_FILES_APPLICANT_FILE",
        name: "application",
        access: "1"
      },
      {
        label: "COM_EMUNDUS_FILES_ATTACHMENTS",
        name: "attachments",
        access: "4"
      },
      {
        label: "COM_EMUNDUS_FILES_COMMENTS",
        name: "comments",
        access: "10"
      },
      {
        label: "COM_EMUNDUS_FILES_MESSENGER",
        name: "messenger",
        access: "36"
      },
      {
        label: "COM_EMUNDUS_APPLICATION_SYNTHESIS",
        name: "synthesis",
        access: "1"
      }
    ],
    ccid: 0,
    url: null,
    access: null,
    student_id: null,
    hidden: false,
    loading: false,
    filesSynthesis: {}
  }),
  created() {
    if (this.defaultTabs.length > 0) {
      this.tabs = this.defaultTabs;
      this.selected = this.defaultTabs[0].name;
    }
    if (document.querySelector("body.layout-evaluation")) {
      document.querySelector("body.layout-evaluation").style.overflow = "hidden";
    }
    const r = document.querySelector(":root");
    let ratio_array = this.$props.ratio.split("/");
    r.style.setProperty("--attachment-width", ratio_array[0] + "%");
    this.selectedFile = this.file;
    if (typeof this.selectedFile !== "undefined" && this.selectedFile !== null) {
      this.render();
    } else {
      const hash = window.location.hash;
      if (hash) {
        this.selectedFile = hash.replace("#", "");
        this.render();
      } else {
        this.showModal = false;
      }
    }
    this.addEventListeners();
  },
  onBeforeDestroy() {
    window.removeEventListener("openSingleApplicationWithFnum");
  },
  methods: {
    addEventListeners() {
      window.addEventListener("openSingleApplicationWithFnum", (e) => {
        this.showModal = true;
        if (e.detail.fnum) {
          this.selectedFile = e.detail.fnum;
        }
        if (e.detail.fnums) {
          this.fnums = e.detail.fnums;
        }
        if (typeof this.selectedFile !== "undefined" && this.selectedFile !== null) {
          this.render();
          if (this.$refs["modal"]) {
            this.$refs["modal"].open();
          }
        }
      });
    },
    getSynthesis(fnum) {
      fileService.getFileSynthesis(fnum).then((response) => {
        if (response.data.length == 0) {
          this.tabs = this.tabs.filter((tab) => tab.name !== "synthesis");
        } else {
          if (!this.tabs.find((tab) => tab.name === "synthesis")) {
            this.tabs.push({
              label: "COM_EMUNDUS_APPLICATION_SYNTHESIS",
              name: "synthesis",
              access: "1"
            });
          }
          this.filesSynthesis[this.selectedFile.fnum] = response.data;
        }
      }).catch((error) => {
        console.error("Error fetching synthesis:", error);
      });
    },
    render() {
      this.loading = true;
      let fnum = "";
      if (typeof this.selectedFile == "string") {
        fnum = this.selectedFile;
      } else {
        fnum = this.selectedFile.fnum;
      }
      this.getSynthesis(fnum);
      if (typeof this.selectedFile == "string") {
        filesService.getFile(fnum, this.$props.type).then((result) => {
          if (result.status == 1) {
            this.selectedFile = result.data;
            this.access = result.rights;
            if (this.defaultTabs.length > 0) {
              this.selected = this.defaultTabs[0].name;
            } else {
              this.selected = "application";
            }
            this.updateURL(this.selectedFile.fnum);
            this.getApplicationForm();
            this.getReadonlyEvaluations();
            this.showModal = true;
            this.hidden = false;
            this.loading = false;
          } else {
            this.displayError("COM_EMUNDUS_FILES_CANNOT_ACCESS", result.msg).then((confirm) => {
              if (confirm === true) {
                this.showModal = false;
                this.hidden = true;
              }
            });
            this.loading = false;
          }
        });
      } else {
        filesService.checkAccess(fnum).then((result) => {
          if (result.status == true) {
            this.access = result.data;
            this.updateURL(this.selectedFile.fnum);
            if (this.access["1"].r) {
              this.getApplicationForm();
            } else {
              if (this.access["4"].r) {
                this.selected = "attachments";
              } else if (this.access["10"].r) {
                this.selected = "comments";
              }
            }
            this.getReadonlyEvaluations();
            this.showModal = true;
            this.hidden = false;
          } else {
            this.displayError("COM_EMUNDUS_FILES_CANNOT_ACCESS", "COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC").then(
              (confirm) => {
                if (confirm === true) {
                  this.showModal = false;
                  this.hidden = true;
                }
              }
            );
          }
        }).catch((error) => {
          this.displayError("COM_EMUNDUS_FILES_CANNOT_ACCESS", "COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC");
          this.loading = false;
        });
      }
    },
    getApplicationForm() {
      axios({
        method: "get",
        url: "index.php?option=com_emundus&view=application&format=raw&layout=form&fnum=" + this.selectedFile.fnum + "&context=modal"
      }).then((response) => {
        this.applicationform = response.data;
        if (this.$props.type !== "evaluation") {
          this.loading = false;
        }
      });
    },
    getReadonlyEvaluations() {
      const fnum = typeof this.selectedFile === "string" ? this.selectedFile : this.selectedFile.fnum;
      fileService.getFileIdFromFnum(fnum).then((response) => {
        if (response.status) {
          this.ccid = response.data;
          evaluationService.getEvaluationsForms(fnum, true).then((response2) => {
            response2.data.forEach((step) => {
              this.access[step.action_id] = {
                r: true,
                c: false
              };
              if (this.tabs.find((tab) => tab.name === "step-" + step.id)) {
                return;
              }
              if (step.url) {
                this.tabs.push({
                  label: step.label,
                  name: "step-" + step.id,
                  access: step.action_id,
                  type: "iframe",
                  url: step.url
                });
              } else if (step.multiple) {
                this.tabs.push({
                  label: step.label,
                  name: "step-" + step.id,
                  access: step.action_id,
                  type: "evaluation-list",
                  step
                });
              }
            });
          }).catch((error) => {
            console.log(error);
          });
        }
      });
    },
    updateURL(fnum = "") {
      let url = window.location.href;
      url = url.split("#");
      if (fnum === "") {
        window.history.pushState("", "", url[0]);
      } else {
        window.history.pushState("", "", url[0] + "#" + fnum);
      }
    },
    onClose(e) {
      e.preventDefault();
      this.hidden = true;
      this.showModal = false;
      document.querySelector("body").style.overflow = "visible";
      this.updateURL();
      window.postMessage("reloadData");
    },
    openNextFnum() {
      let index = typeof this.selectedFile === "string" ? this.fnums.indexOf(this.selectedFile) : this.fnums.indexOf(this.selectedFile.fnum);
      if (index !== -1 && index < this.fnums.length - 1) {
        const newIndex = index + 1;
        if (newIndex > this.fnums.length) {
          this.selectedFile = this.fnums[0];
        } else {
          this.selectedFile = this.fnums[newIndex];
        }
        this.render();
      }
    },
    openPreviousFnum() {
      let index = typeof this.selectedFile === "string" ? this.fnums.indexOf(this.selectedFile) : this.fnums.indexOf(this.selectedFile.fnum);
      if (index !== -1 && index > 0) {
        const newIndex = index - 1;
        if (newIndex < 0) {
          this.selectedFile = this.fnums[this.fnums.length - 1];
        } else {
          this.selectedFile = this.fnums[newIndex];
        }
        this.render();
      }
    },
    replaceTagsIframeUrl(url) {
      return url.replace("{fnum}", this.selectedFile.fnum);
    }
  },
  computed: {
    ratioStyle() {
      let ratio_array = this.$props.ratio.split("/");
      return ratio_array[0] + "% " + ratio_array[1] + "%";
    },
    tabsICanAccessTo() {
      return this.tabs.filter((tab) => this.access[tab.access].r || this.access[tab.access].c);
    }
  }
};
const _hoisted_1 = { class: "em-modal-header tw-flex tw-w-full tw-items-center tw-bg-profile-full tw-px-3 tw-py-4" };
const _hoisted_2 = {
  class: "tw-flex tw-w-full tw-items-center tw-justify-between",
  id: "evaluation-modal-close"
};
const _hoisted_3 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_4 = { class: "tw-ml-2 tw-text-sm tw-text-neutral-900 tw-text-white" };
const _hoisted_5 = {
  key: 0,
  class: "tw-text-sm",
  style: { "color": "white" }
};
const _hoisted_6 = {
  key: 1,
  class: "tw-text-sm",
  style: { "color": "white" }
};
const _hoisted_7 = {
  key: 0,
  class: "tw-flex tw-items-center"
};
const _hoisted_8 = { id: "modal-applicationform" };
const _hoisted_9 = {
  class: "scrollable",
  style: { "height": "calc(100vh - 56px)" }
};
const _hoisted_10 = {
  class: "sticky-tab em-bg-neutral-100 tw-flex tw-items-center tw-justify-center tw-gap-4 tw-border-b tw-border-neutral-300",
  style: { "z-index": "2" }
};
const _hoisted_11 = ["onClick"];
const _hoisted_12 = { class: "tw-text-sm" };
const _hoisted_13 = { key: 0 };
const _hoisted_14 = ["innerHTML"];
const _hoisted_15 = { key: 5 };
const _hoisted_16 = ["id", "src"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Attachments = resolveComponent("Attachments");
  const _component_Comments = resolveComponent("Comments");
  const _component_Messages = resolveComponent("Messages");
  const _component_Synthesis = resolveComponent("Synthesis");
  const _component_evaluation_list = resolveComponent("evaluation-list");
  const _component_Evaluations = resolveComponent("Evaluations");
  const _component_modal = resolveComponent("modal");
  return _ctx.selectedFile !== null && _ctx.selectedFile !== void 0 ? withDirectives((openBlock(), createBlock(_component_modal, {
    key: 0,
    "click-to-close": false,
    id: "application-modal",
    name: "application-modal",
    height: "100vh",
    ref: "modal",
    class: normalizeClass({ "context-files": $props.context === "files", hidden: _ctx.hidden })
  }, {
    default: withCtx(() => [
      createBaseVNode("div", _hoisted_1, [
        createBaseVNode("div", _hoisted_2, [
          createBaseVNode("div", _hoisted_3, [
            createBaseVNode("div", {
              onClick: _cache[0] || (_cache[0] = (...args) => $options.onClose && $options.onClose(...args)),
              class: "tw-flex tw-w-max tw-cursor-pointer tw-items-center"
            }, [
              _cache[3] || (_cache[3] = createBaseVNode("span", {
                class: "material-symbols-outlined tw-text-base",
                style: { "color": "white" }
              }, "navigate_before", -1)),
              createBaseVNode("span", _hoisted_4, toDisplayString(_ctx.translate("BACK")), 1)
            ]),
            _cache[4] || (_cache[4] = createBaseVNode("span", { class: "tw-text-white" }, "|", -1)),
            _ctx.selectedFile.applicant_name != "" ? (openBlock(), createElementBlock("p", _hoisted_5, toDisplayString(_ctx.selectedFile.applicant_name) + " - " + toDisplayString(_ctx.selectedFile.fnum), 1)) : (openBlock(), createElementBlock("p", _hoisted_6, toDisplayString(_ctx.selectedFile.fnum), 1))
          ]),
          _ctx.fnums.length > 1 ? (openBlock(), createElementBlock("div", _hoisted_7, [
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-cursor-pointer tw-text-base",
              style: { "color": "white" },
              onClick: _cache[1] || (_cache[1] = (...args) => $options.openPreviousFnum && $options.openPreviousFnum(...args))
            }, "navigate_before"),
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-cursor-pointer tw-text-base",
              style: { "color": "white" },
              onClick: _cache[2] || (_cache[2] = (...args) => $options.openNextFnum && $options.openNextFnum(...args))
            }, "navigate_next")
          ])) : createCommentVNode("", true)
        ])
      ]),
      _ctx.access ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: "modal-grid",
        style: normalizeStyle([{ "height": "calc(100% - 56px)" }, "grid-template-columns:" + this.ratioStyle])
      }, [
        createBaseVNode("div", _hoisted_8, [
          createBaseVNode("div", _hoisted_9, [
            createBaseVNode("div", _hoisted_10, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.tabsICanAccessTo, (tab) => {
                return openBlock(), createElementBlock("div", {
                  key: tab.name,
                  class: normalizeClass(["em-light-tabs tw-cursor-pointer", _ctx.selected === tab.name ? "em-light-selected-tab" : ""]),
                  onClick: ($event) => _ctx.selected = tab.name
                }, [
                  createBaseVNode("span", _hoisted_12, toDisplayString(_ctx.translate(tab.label)), 1)
                ], 10, _hoisted_11);
              }), 128))
            ]),
            !_ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_13, [
              (openBlock(true), createElementBlock(Fragment, null, renderList(_ctx.tabs, (tab) => {
                return openBlock(), createElementBlock("div", {
                  key: tab.name
                }, [
                  tab.name === "application" && _ctx.selected === "application" ? (openBlock(), createElementBlock("div", {
                    key: 0,
                    innerHTML: _ctx.applicationform
                  }, null, 8, _hoisted_14)) : createCommentVNode("", true),
                  tab.name === "attachments" && _ctx.selected === "attachments" ? (openBlock(), createBlock(_component_Attachments, {
                    fnum: _ctx.selectedFile.fnum,
                    user: _ctx.$props.user,
                    columns: ["check", "name", "date", "category", "status"],
                    displayEdit: false,
                    key: _ctx.selectedFile.fnum
                  }, null, 8, ["fnum", "user"])) : createCommentVNode("", true),
                  tab.name === "comments" && _ctx.selected === "comments" ? (openBlock(), createBlock(_component_Comments, {
                    fnum: _ctx.selectedFile.fnum,
                    user: _ctx.$props.user,
                    access: _ctx.access["10"],
                    key: _ctx.selectedFile.fnum
                  }, null, 8, ["fnum", "user", "access"])) : createCommentVNode("", true),
                  tab.name === "messenger" && _ctx.selected === "messenger" ? (openBlock(), createBlock(_component_Messages, {
                    key: 3,
                    fnum: _ctx.selectedFile.fnum,
                    fullname: _ctx.$props.fullname,
                    applicant: _ctx.$props.applicant
                  }, null, 8, ["fnum", "fullname", "applicant"])) : createCommentVNode("", true),
                  tab.name === "synthesis" && _ctx.selected === "synthesis" ? (openBlock(), createBlock(_component_Synthesis, {
                    key: 4,
                    fnum: _ctx.selectedFile.fnum,
                    content: _ctx.filesSynthesis[_ctx.selectedFile.fnum]
                  }, null, 8, ["fnum", "content"])) : createCommentVNode("", true),
                  tab.type && tab.type === "iframe" && _ctx.selected === tab.name ? (openBlock(), createElementBlock("div", _hoisted_15, [
                    createBaseVNode("iframe", {
                      id: tab.name,
                      src: $options.replaceTagsIframeUrl(tab.url),
                      class: "tw-h-screen tw-w-full"
                    }, null, 8, _hoisted_16)
                  ])) : createCommentVNode("", true),
                  tab.type && tab.type === "evaluation-list" && _ctx.selected === tab.name ? (openBlock(), createBlock(_component_evaluation_list, {
                    key: 6,
                    step: tab.step,
                    ccid: this.ccid
                  }, null, 8, ["step", "ccid"])) : createCommentVNode("", true)
                ]);
              }), 128))
            ])) : createCommentVNode("", true)
          ])
        ]),
        _ctx.selectedFile ? (openBlock(), createBlock(_component_Evaluations, {
          fnum: typeof _ctx.selectedFile === "string" ? _ctx.selectedFile : _ctx.selectedFile.fnum,
          key: typeof _ctx.selectedFile === "string" ? _ctx.selectedFile : _ctx.selectedFile.fnum,
          defaultCcid: _ctx.ccid
        }, null, 8, ["fnum", "defaultCcid"])) : createCommentVNode("", true)
      ], 4)) : createCommentVNode("", true)
    ]),
    _: 1
  }, 8, ["class"])), [
    [vShow, _ctx.showModal]
  ]) : createCommentVNode("", true);
}
const ApplicationSingle = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  ApplicationSingle as default
};
