import { c as campaignService } from "./campaign.js";
import { _ as _export_sfc, s as script, w as workflowService, r as resolveComponent, o as openBlock, c as createElementBlock, a as createBaseVNode, t as toDisplayString, F as Fragment, b as renderList, n as normalizeClass, d as withDirectives, v as vShow, e as createVNode } from "./app_emundus.js";
const _sfc_main = {
  name: "ProgramEdit",
  components: { Multiselect: script },
  props: {
    programId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      program: {},
      campaigns: [],
      workflows: [],
      workflowOptions: [],
      tabs: [
        {
          name: "general",
          label: this.translate("COM_EMUNDUS_PROGRAMS_EDITION_TAB_GENERAL")
        },
        {
          name: "campaigns",
          label: this.translate("COM_EMUNDUS_PROGRAMS_EDITION_TAB_CAMPAIGNS")
        },
        {
          name: "workflows",
          label: this.translate("COM_EMUNDUS_PROGRAMS_EDITION_TAB_WORKFLOWS")
        }
      ],
      selectedTab: "general"
    };
  },
  created() {
    this.getWorkflows();
    this.getAssociatedCampaigns();
    this.getAssociatedWorkflows();
  },
  methods: {
    getWorkflows() {
      workflowService.getWorkflows().then((response) => {
        if (response.status) {
          this.workflowOptions = response.data.datas.map((workflow) => {
            return {
              id: workflow.id,
              label: workflow.label.fr
            };
          });
        }
      });
    },
    getAssociatedCampaigns() {
      campaignService.getCampaignsByProgramId(this.programId).then((response) => {
        this.campaigns = response.data;
      });
    },
    getAssociatedWorkflows() {
      workflowService.getWorkflowsByProgramId(this.programId).then((response) => {
        this.workflows = response.data.map((workflow) => {
          console.log(workflow);
          return {
            id: workflow.id,
            label: workflow.label
          };
        });
      });
    },
    updateProgramWorkflows() {
      workflowService.updateProgramWorkflows(this.programId, this.workflows).then((response) => {
      });
    }
  }
};
const _hoisted_1 = {
  id: "program-edition-container",
  class: "em-border-cards em-card-shadow tw-rounded em-white-bg em-p-24 tw-m-4"
};
const _hoisted_2 = { class: "tw-mb-4" };
const _hoisted_3 = { class: "tw-mb-2" };
const _hoisted_4 = { class: "tw-mt-4" };
const _hoisted_5 = { class: "tw-flex tw-flex-row tw-list-none" };
const _hoisted_6 = ["onClick"];
const _hoisted_7 = { class: "tw-w-full" };
const _hoisted_8 = ["src"];
const _hoisted_9 = { class: "tw-w-full tw-p-4" };
const _hoisted_10 = { class: "tw-mb-2" };
const _hoisted_11 = { class: "tw-my-4" };
const _hoisted_12 = ["href"];
const _hoisted_13 = {
  href: "/campaigns",
  class: "tw-underline",
  target: "_blank"
};
const _hoisted_14 = { class: "tw-w-full tw-my-4" };
const _hoisted_15 = { class: "tw-mb-2" };
const _hoisted_16 = { class: "tw-flex tw-flex-row tw-justify-between" };
const _hoisted_17 = {
  href: "/workflows",
  class: "tw-underline",
  target: "_blank"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Multiselect = resolveComponent("Multiselect");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("h1", _hoisted_2, toDisplayString(_ctx.translate("COM_EMUNDUS_PROGRAMS_EDITION_TITLE")), 1),
    createBaseVNode("h2", _hoisted_3, toDisplayString(_ctx.translate("COM_EMUNDUS_PROGRAMS_EDITION_SUBTITLE")), 1),
    createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_PROGRAMS_EDITION_INTRO")), 1),
    createBaseVNode("nav", _hoisted_4, [
      createBaseVNode("ul", _hoisted_5, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.tabs, (tab) => {
          return openBlock(), createElementBlock("li", {
            class: normalizeClass(["tw-cursor-pointer tw-shadow tw-rounded-t-lg tw-px-2.5 tw-py-3", { "em-bg-main-500 em-text-neutral-300": $data.selectedTab === tab.name }]),
            key: tab.name,
            onClick: ($event) => $data.selectedTab = tab.name
          }, toDisplayString(_ctx.translate(tab.label)), 11, _hoisted_6);
        }), 128))
      ])
    ]),
    withDirectives(createBaseVNode("div", _hoisted_7, [
      createBaseVNode("iframe", {
        class: "tw-w-full hide-titles",
        style: { "height": "150vh" },
        src: "/campaigns/modifier-un-programme?rowid=" + this.programId + "&tmpl=component&iframe=1"
      }, null, 8, _hoisted_8)
    ], 512), [
      [vShow, $data.selectedTab === "general"]
    ]),
    withDirectives(createBaseVNode("div", _hoisted_9, [
      createBaseVNode("p", _hoisted_10, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE")), 1),
      createBaseVNode("ul", _hoisted_11, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.campaigns, (campaign) => {
          return openBlock(), createElementBlock("li", {
            key: campaign.id
          }, [
            createBaseVNode("a", {
              href: "/campaigns/edit?cid=" + campaign.id,
              target: "_blank"
            }, toDisplayString(campaign.label), 9, _hoisted_12)
          ]);
        }), 128))
      ]),
      createBaseVNode("a", _hoisted_13, toDisplayString(_ctx.translate("COM_EMUNDUS_PROGRAMS_ACCESS_TO_CAMPAIGNS")), 1)
    ], 512), [
      [vShow, $data.selectedTab === "campaigns"]
    ]),
    withDirectives(createBaseVNode("div", _hoisted_14, [
      createBaseVNode("label", _hoisted_15, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_WORKFLOWS_ASSOCIATED_TITLE")), 1),
      createVNode(_component_Multiselect, {
        options: $data.workflowOptions,
        class: "tw-my-4",
        modelValue: $data.workflows,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.workflows = $event),
        label: "label",
        "track-by": "id",
        placeholder: "Select a program",
        multiple: true
      }, null, 8, ["options", "modelValue"]),
      createBaseVNode("div", _hoisted_16, [
        createBaseVNode("a", _hoisted_17, toDisplayString(_ctx.translate("COM_EMUNDUS_PROGRAMS_ACCESS_TO_WORKFLOWS")), 1),
        createBaseVNode("button", {
          class: "tw-btn-primary",
          onClick: _cache[1] || (_cache[1] = (...args) => $options.updateProgramWorkflows && $options.updateProgramWorkflows(...args))
        }, toDisplayString(_ctx.translate("SAVE")), 1)
      ])
    ], 512), [
      [vShow, $data.selectedTab === "workflows"]
    ])
  ]);
}
const ProgramEdit = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  ProgramEdit as default
};
