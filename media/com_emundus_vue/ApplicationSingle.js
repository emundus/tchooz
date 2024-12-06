import { F as FetchClient, _ as _export_sfc, C as Comments, A as Attachments, M as Modal, a as axios, r as resolveComponent, w as withDirectives, v as vShow, o as openBlock, c as createBlock, b as withCtx, d as createBaseVNode, t as toDisplayString, e as createElementBlock, f as createCommentVNode, n as normalizeStyle, g as Fragment, h as renderList, i as normalizeClass } from "./app_emundus.js";
import { e as errors } from "./errors.js";
const client = new FetchClient("file");
const filesService = {
  // eslint-disable-next-line no-unused-vars
  async getFiles(type = "default", refresh = false, limit = 25, page = 0) {
    try {
      return await client.get("getfiles", {
        type,
        refresh
      });
    } catch (e) {
      return false;
    }
  },
  async getColumns(type = "default") {
    try {
      return await client.get("getcolumns", {
        type
      });
    } catch (e) {
      return false;
    }
  },
  async getEvaluationFormByFnum(fnum, type) {
    try {
      return await client.get("getevaluationformbyfnum", {
        fnum,
        type
      });
    } catch (e) {
      return false;
    }
  },
  async getMyEvaluation(fnum) {
    try {
      return await client.get("getmyevaluation", {
        fnum
      });
    } catch (e) {
      return false;
    }
  },
  async checkAccess(fnum) {
    try {
      return await client.get("checkaccess", {
        fnum
      });
    } catch (e) {
      return false;
    }
  },
  async getLimit(type = "default") {
    try {
      return await client.get("getlimit", {
        type
      });
    } catch (e) {
      return false;
    }
  },
  async getPage(type = "default") {
    try {
      return await client.get("getpage", {
        type
      });
    } catch (e) {
      return false;
    }
  },
  async updateLimit(limit) {
    try {
      return await client.get("updatelimit", {
        limit
      });
    } catch (e) {
      return false;
    }
  },
  async updatePage(page) {
    try {
      return await client.get("updatepage", {
        page
      });
    } catch (e) {
      return false;
    }
  },
  async getSelectedTab(type) {
    try {
      return await client.get("getselectedtab", {
        type
      });
    } catch (e) {
      return false;
    }
  },
  async setSelectedTab(tab, type = "evaluation") {
    try {
      return await client.get("setselectedtab", {
        tab,
        type
      });
    } catch (e) {
      return false;
    }
  },
  async getFile(fnum, type = "default") {
    try {
      return await client.get("getfile", {
        fnum,
        type
      });
    } catch (e) {
      return false;
    }
  },
  async getFilters() {
    try {
      return await client.get("getfilters");
    } catch (e) {
      return false;
    }
  },
  async applyFilters(filters) {
    const data = {
      filters: JSON.stringify(filters)
    };
    try {
      return await client.post("applyfilters", data);
    } catch (e) {
      return false;
    }
  }
};
const ApplicationSingle_vue_vue_type_style_index_0_lang = "";
const _sfc_main = {
  name: "ApplicationSingle",
  components: { Comments, Attachments, Modal },
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
      }
    ],
    evaluation_form: 0,
    url: null,
    access: null,
    student_id: null,
    hidden: false,
    loading: false
  }),
  created() {
    document.querySelector("body").style.overflow = "hidden";
    var r = document.querySelector(":root");
    let ratio_array = this.$props.ratio.split("/");
    r.style.setProperty("--attachment-width", ratio_array[0] + "%");
    this.selectedFile = this.file;
    if (typeof this.selectedFile !== "undefined" && this.selectedFile !== null) {
      this.render();
    } else {
      this.showModal = false;
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
    render() {
      this.loading = true;
      let fnum = "";
      if (typeof this.selectedFile == "string") {
        fnum = this.selectedFile;
      } else {
        fnum = this.selectedFile.fnum;
      }
      if (typeof this.selectedFile == "string") {
        filesService.getFile(fnum, this.$props.type).then((result) => {
          if (result.status == 1) {
            this.selectedFile = result.data;
            this.access = result.rights;
            this.selected = "application";
            this.updateURL(this.selectedFile.fnum);
            this.getApplicationForm();
            if (this.$props.type === "evaluation") {
              this.getEvaluationForm();
            }
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
            if (this.$props.type === "evaluation") {
              this.getEvaluationForm();
            }
            this.showModal = true;
            this.hidden = false;
          } else {
            this.displayError("COM_EMUNDUS_FILES_CANNOT_ACCESS", "COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC").then((confirm) => {
              if (confirm === true) {
                this.showModal = false;
                this.hidden = true;
              }
            });
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
        url: "index.php?option=com_emundus&view=application&format=raw&layout=form&fnum=" + this.selectedFile.fnum
      }).then((response) => {
        this.applicationform = response.data;
        if (this.$props.type !== "evaluation") {
          this.loading = false;
        }
      });
    },
    getEvaluationForm() {
      if (this.selectedFile.id != null) {
        this.rowid = this.selectedFile.id;
      }
      if (typeof this.selectedFile.applicant_id != "undefined") {
        this.student_id = this.selectedFile.applicant_id;
      } else {
        this.student_id = this.selectedFile.student_id;
      }
      let view = "form";
      filesService.getEvaluationFormByFnum(this.selectedFile.fnum, this.$props.type).then((response) => {
        if (response.data !== 0 && response.data !== null) {
          if (typeof this.selectedFile.id === "undefined") {
            filesService.getMyEvaluation(this.selectedFile.fnum).then((data) => {
              this.rowid = data.data;
              if (this.rowid == null) {
                this.rowid = "";
              }
              this.url = "index.php?option=com_fabrik&c=form&view=" + view + "&formid=" + response.data + "&rowid=" + this.rowid + "&jos_emundus_evaluations___student_id[value]=" + this.student_id + "&jos_emundus_evaluations___campaign_id[value]=" + this.selectedFile.campaign + "&jos_emundus_evaluations___fnum[value]=" + this.selectedFile.fnum + "&student_id=" + this.student_id + "&tmpl=component&iframe=1";
            });
          } else {
            this.url = "index.php?option=com_fabrik&c=form&view=" + view + "&formid=" + response.data + "&rowid=" + this.rowid + "&jos_emundus_evaluations___student_id[value]=" + this.student_id + "&jos_emundus_evaluations___campaign_id[value]=" + this.selectedFile.campaign + "&jos_emundus_evaluations___fnum[value]=" + this.selectedFile.fnum + "&student_id=" + this.student_id + "&tmpl=component&iframe=1";
          }
        }
      });
    },
    iframeLoaded() {
      this.loading = false;
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
      swal.close();
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
    }
  },
  computed: {
    ratioStyle() {
      let ratio_array = this.$props.ratio.split("/");
      return ratio_array[0] + "% " + ratio_array[1] + "%";
    },
    tabsICanAccessTo() {
      return this.tabs.filter((tab) => this.access[tab.access].r);
    }
  }
};
const _hoisted_1 = { class: "em-modal-header tw-w-full tw-px-3 tw-py-4 tw-bg-profile-full tw-flex tw-items-center" };
const _hoisted_2 = {
  class: "tw-flex tw-items-center tw-justify-between tw-w-full",
  id: "evaluation-modal-close"
};
const _hoisted_3 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_4 = { class: "tw-ml-2 tw-text-neutral-900 tw-text-white tw-text-sm" };
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
const _hoisted_9 = { class: "scrollable" };
const _hoisted_10 = {
  class: "tw-flex tw-items-center tw-justify-center tw-gap-4 tw-border-b tw-border-neutral-300 sticky-tab em-bg-neutral-100",
  style: { "z-index": "2" }
};
const _hoisted_11 = ["onClick"];
const _hoisted_12 = { class: "tw-text-sm" };
const _hoisted_13 = { key: 0 };
const _hoisted_14 = ["innerHTML"];
const _hoisted_15 = { id: "modal-evaluationgrid" };
const _hoisted_16 = ["src"];
const _hoisted_17 = { key: 1 };
const _hoisted_18 = {
  key: 2,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Attachments = resolveComponent("Attachments");
  const _component_Comments = resolveComponent("Comments");
  const _component_modal = resolveComponent("modal");
  return _ctx.selectedFile !== null && _ctx.selectedFile !== void 0 ? withDirectives((openBlock(), createBlock(_component_modal, {
    key: 0,
    "click-to-close": false,
    id: "application-modal",
    name: "application-modal",
    height: "100vh",
    ref: "modal",
    class: normalizeClass({ "context-files": $props.context === "files", "hidden": _ctx.hidden })
  }, {
    default: withCtx(() => [
      createBaseVNode("div", _hoisted_1, [
        createBaseVNode("div", _hoisted_2, [
          createBaseVNode("div", _hoisted_3, [
            createBaseVNode("div", {
              onClick: _cache[0] || (_cache[0] = (...args) => $options.onClose && $options.onClose(...args)),
              class: "tw-w-max tw-flex tw-items-center"
            }, [
              _cache[4] || (_cache[4] = createBaseVNode("span", {
                class: "material-symbols-outlined tw-text-base",
                style: { "color": "white" }
              }, "navigate_before", -1)),
              createBaseVNode("span", _hoisted_4, toDisplayString(_ctx.translate("BACK")), 1)
            ]),
            _cache[5] || (_cache[5] = createBaseVNode("span", { class: "tw-text-white" }, "|", -1)),
            _ctx.selectedFile.applicant_name != "" ? (openBlock(), createElementBlock("p", _hoisted_5, toDisplayString(_ctx.selectedFile.applicant_name) + " - " + toDisplayString(_ctx.selectedFile.fnum), 1)) : (openBlock(), createElementBlock("p", _hoisted_6, toDisplayString(_ctx.selectedFile.fnum), 1))
          ]),
          _ctx.fnums.length > 1 ? (openBlock(), createElementBlock("div", _hoisted_7, [
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-text-base",
              style: { "color": "white" },
              onClick: _cache[1] || (_cache[1] = (...args) => $options.openPreviousFnum && $options.openPreviousFnum(...args))
            }, "navigate_before"),
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-text-base",
              style: { "color": "white" },
              onClick: _cache[2] || (_cache[2] = (...args) => $options.openNextFnum && $options.openNextFnum(...args))
            }, "navigate_next")
          ])) : createCommentVNode("", true)
        ])
      ]),
      _ctx.access ? (openBlock(), createElementBlock("div", {
        key: 0,
        class: "modal-grid",
        style: normalizeStyle("grid-template-columns:" + this.ratioStyle)
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
              _ctx.selected === "application" ? (openBlock(), createElementBlock("div", {
                key: 0,
                innerHTML: _ctx.applicationform
              }, null, 8, _hoisted_14)) : createCommentVNode("", true),
              _ctx.selected === "attachments" ? (openBlock(), createBlock(_component_Attachments, {
                fnum: _ctx.selectedFile.fnum,
                user: _ctx.$props.user,
                columns: ["check", "name", "date", "category", "status"],
                displayEdit: false,
                key: _ctx.selectedFile.fnum
              }, null, 8, ["fnum", "user"])) : createCommentVNode("", true),
              _ctx.selected === "comments" ? (openBlock(), createBlock(_component_Comments, {
                fnum: _ctx.selectedFile.fnum,
                user: _ctx.$props.user,
                access: _ctx.access["10"],
                key: _ctx.selectedFile.fnum
              }, null, 8, ["fnum", "user", "access"])) : createCommentVNode("", true)
            ])) : createCommentVNode("", true)
          ])
        ]),
        createBaseVNode("div", _hoisted_15, [
          _ctx.url ? (openBlock(), createElementBlock("iframe", {
            key: 0,
            src: _ctx.url,
            class: "iframe-evaluation",
            id: "iframe-evaluation",
            onLoad: _cache[3] || (_cache[3] = ($event) => {
              $options.iframeLoaded($event);
            }),
            title: "Evaluation form"
          }, null, 40, _hoisted_16)) : (openBlock(), createElementBlock("div", _hoisted_17, toDisplayString(_ctx.translate("COM_EMUNDUS_EVALUATION_NO_FORM_FOUND")), 1)),
          _ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_18)) : createCommentVNode("", true)
        ])
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
