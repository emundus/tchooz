import { _ as _export_sfc, Q as workflowService, r as resolveComponent, o as openBlock, c as createElementBlock, F as Fragment, e as renderList, d as createBaseVNode, w as withDirectives, A as vModelText, b as createCommentVNode, a as createBlock, t as toDisplayString, n as normalizeClass } from "./app_emundus.js";
const _sfc_main$1 = {
  name: "StepTypesByLevel",
  props: {
    defaultTypes: {
      type: Array,
      required: true
    },
    parentId: {
      type: Number,
      default: 0
    },
    level: {
      type: Number,
      default: 0
    },
    levelMax: {
      type: Number,
      default: 1
    }
  },
  data() {
    return {
      types: []
    };
  },
  mounted() {
    this.types = this.defaultTypes;
  },
  methods: {
    addStepType() {
      this.types.push({
        id: this.lastId + 1,
        label: "Nouveau type",
        parent_id: this.parentId
      });
      this.$emit("updateTypes", this.types);
    },
    deleteType(id) {
      this.types = this.types.filter((type) => type.id !== id);
      this.$emit("updateTypes", this.types);
    },
    addChildrenStepType(type) {
      this.types.push({
        id: this.lastId + 1,
        label: "Nouveau type",
        parent_id: type.id
      });
      this.$emit("updateTypes", this.types);
    },
    stepTypesOfParentId(parentId) {
      return this.types.filter((type) => type.parent_id === parentId);
    },
    saveStepTypes() {
      workflowService.saveTypes(this.types).then((response) => {
        if (response.status) {
          Swal.fire({
            icon: "success",
            title: this.translate("COM_EMUNDUS_WORKFLOW_SAVE_STEP_TYPES_SUCCESS"),
            showConfirmButton: false,
            timer: 1500
          });
        }
      }).catch((error) => {
        console.log(error);
      });
    },
    onUpdateTypes(types) {
      this.types = types;
      this.$emit("updateTypes", this.types);
    }
  },
  computed: {
    stepTypesByParentId() {
      return this.types.filter((type) => type.parent_id === this.parentId);
    },
    lastId() {
      return this.types.reduce((acc, type) => {
        return type.id > acc ? type.id : acc;
      }, 0);
    }
  }
};
const _hoisted_1$1 = { class: "tw-mb-2 tw-flex tw-w-full tw-flex-row tw-items-center" };
const _hoisted_2$1 = ["id", "name", "onUpdate:modelValue"];
const _hoisted_3$1 = ["onClick"];
const _hoisted_4 = {
  key: 0,
  class: "tw-flex tw-w-full tw-flex-row tw-items-center"
};
const _hoisted_5 = ["onClick"];
const _hoisted_6 = {
  key: 0,
  class: "tw-flex tw-flex-row tw-justify-end"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_StepTypesByLevel = resolveComponent("StepTypesByLevel", true);
  return openBlock(), createElementBlock("div", {
    class: normalizeClass("step-types-level-" + $props.parentId)
  }, [
    (openBlock(true), createElementBlock(Fragment, null, renderList($options.stepTypesByParentId, (type) => {
      return openBlock(), createElementBlock("div", {
        key: type.id
      }, [
        createBaseVNode("div", _hoisted_1$1, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($props.level, (i) => {
            return openBlock(), createElementBlock("span", {
              key: i,
              class: "material-symbols-outlined"
            }, "horizontal_rule");
          }), 128)),
          withDirectives(createBaseVNode("input", {
            id: "type-" + type.id + "-label",
            name: "type-" + type.id + "-label",
            "onUpdate:modelValue": ($event) => type.label = $event
          }, null, 8, _hoisted_2$1), [
            [vModelText, type.label]
          ]),
          !type.system ? (openBlock(), createElementBlock("span", {
            key: 0,
            class: "material-symbols-outlined tw-cursor-pointer",
            onClick: ($event) => $options.deleteType(type.id)
          }, " delete ", 8, _hoisted_3$1)) : createCommentVNode("", true)
        ]),
        createBaseVNode("div", null, [
          $options.stepTypesOfParentId(type.id).length > 0 ? (openBlock(), createBlock(_component_StepTypesByLevel, {
            key: 0,
            onUpdateTypes: $options.onUpdateTypes,
            defaultTypes: $data.types,
            parentId: type.id,
            level: $props.level + 1
          }, null, 8, ["onUpdateTypes", "defaultTypes", "parentId", "level"])) : createCommentVNode("", true)
        ]),
        $props.level < $props.levelMax ? (openBlock(), createElementBlock("div", _hoisted_4, [
          createBaseVNode("button", {
            onClick: ($event) => $options.addChildrenStepType(type),
            class: "tw-btn-secondary tw-mb-2 tw-mt-2"
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_WORKFLOW_ADD_CHILDREN_STEP_TYPE")), 9, _hoisted_5)
        ])) : createCommentVNode("", true)
      ]);
    }), 128)),
    $props.level === 0 ? (openBlock(), createElementBlock("div", _hoisted_6, [
      createBaseVNode("button", {
        onClick: _cache[0] || (_cache[0] = (...args) => $options.saveStepTypes && $options.saveStepTypes(...args)),
        class: "tw-btn-primary"
      }, toDisplayString(_ctx.translate("SAVE")), 1)
    ])) : createCommentVNode("", true)
  ], 2);
}
const StepTypesByLevel = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "WorkflowSettings",
  components: {
    StepTypesByLevel
  },
  data() {
    return {
      stepTypes: []
    };
  },
  created() {
    this.getStepTypes();
  },
  methods: {
    getStepTypes() {
      workflowService.getStepTypes().then((response) => {
        this.stepTypes = response.data.map((type) => {
          type.label = this.translate(type.label);
          return type;
        });
      }).catch((error) => {
        console.log(error);
      });
    },
    onUpdateTypes(types) {
      this.stepTypes = types;
    }
  }
};
const _hoisted_1 = {
  id: "workflow-settings",
  class: "tw-mb-6 tw-w-full tw-rounded-coordinator-cards tw-border tw-border-gray-200 tw-bg-neutral-0 tw-p-5 tw-shadow"
};
const _hoisted_2 = { id: "step-types" };
const _hoisted_3 = { class: "tw-pb-5" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_StepTypesByLevel = resolveComponent("StepTypesByLevel");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("h2", _hoisted_3, toDisplayString(_ctx.translate("COM_EMUNDUS_WORKFLOW_STEP_TYPES")), 1),
      $data.stepTypes.length > 0 ? (openBlock(), createBlock(_component_StepTypesByLevel, {
        key: 0,
        onUpdateTypes: $options.onUpdateTypes,
        defaultTypes: $data.stepTypes,
        parentId: 0
      }, null, 8, ["onUpdateTypes", "defaultTypes"])) : createCommentVNode("", true)
    ])
  ]);
}
const WorkflowSettings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  WorkflowSettings as default
};
