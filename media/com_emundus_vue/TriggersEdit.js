import { _ as _export_sfc, o as openBlock, c as createElementBlock, d as createBaseVNode, C as Back, D as script, l as emailService, s as settingsService, y as smsService, E as fileService, G as groupsService, H as programmeService, u as useGlobalStore, r as resolveComponent, h as createVNode, n as normalizeClass, t as toDisplayString, w as withDirectives, z as vModelSelect, F as Fragment, e as renderList, v as vShow, f as withCtx, I as Transition, b as createCommentVNode, a as createBlock } from "./app_emundus.js";
import { P as Parameter } from "./Parameter.js";
import { I as Info } from "./Info.js";
import "./index.js";
import "./EventBooking.js";
import "./events2.js";
const _sfc_main$1 = {
  name: "ToggleInput",
  props: {
    id: {
      type: String,
      required: true
    },
    value: {
      type: [String, Number],
      default: "0"
    }
  },
  emits: ["update:value"]
};
const _hoisted_1$1 = { class: "em-toggle" };
const _hoisted_2$1 = ["id", "value", "checked"];
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("input", {
      type: "checkbox",
      "true-value": "1",
      "false-value": "0",
      class: "em-toggle-check",
      id: $props.id,
      value: $props.value,
      checked: $props.value == "1" || $props.value == 1,
      onChange: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("update:value", $event.target.checked))
    }, null, 40, _hoisted_2$1),
    _cache[1] || (_cache[1] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
    _cache[2] || (_cache[2] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
  ]);
}
const ToggleInput = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const _sfc_main = {
  name: "TriggersEdit",
  props: {
    triggerId: {
      type: Number,
      required: true
    },
    smsActivated: {
      type: Boolean,
      default: false
    },
    defaultProgramId: {
      type: Number,
      default: 0
    }
  },
  components: {
    Parameter,
    ToggleInput,
    Back,
    Multiselect: script,
    Info
  },
  data() {
    return {
      triggerData: {},
      loading: true,
      statusOptions: [],
      emailsOptions: [],
      smsOptions: [],
      programOptions: [],
      mandatoryFields: ["status", "program_ids", ["email_id", "sms_id"]],
      fieldsToDisplayError: [],
      profilesOptions: [],
      groupsOptions: [],
      userField: {
        param: "user_ids",
        type: "multiselect",
        multiselectOptions: {
          noOptions: false,
          multiple: true,
          taggable: false,
          searchable: true,
          internalSearch: false,
          asyncRoute: "getavailablemanagers",
          optionsPlaceholder: "",
          selectLabel: "",
          selectGroupLabel: "",
          selectedLabel: "",
          deselectedLabel: "",
          deselectGroupLabel: "",
          noOptionsText: "",
          noResultsText: "COM_EMUNDUS_MULTISELECT_NORESULTS",
          tagValidations: [],
          options: [],
          optionsLimit: 30,
          label: "name",
          trackBy: "value"
        },
        value: [],
        label: "COM_EMUNDUS_ONBOARD_TRIGGER_EDIT_SEND_TO_USERS",
        placeholder: "COM_EMUNDUS_ONBOARD_TRIGGER_EDIT_SEND_TO_USERS_PLACEHOLDER",
        displayed: true,
        optional: true
      },
      backUrl: ""
    };
  },
  created() {
    this.getStatusOptions();
    this.getEmailsOptions();
    this.getSmsOptions();
    this.getGroupsOptions();
    this.getProfilesOptions();
    this.getProgramOptions().then(() => {
      this.loadTriggerData();
    });
    if (document.referrer) {
      const url = new URL(document.referrer);
      let tmpUrl = url.pathname + url.search + url.hash;
      tmpUrl = tmpUrl.substring(1);
      this.backUrl = tmpUrl;
    } else {
      this.backUrl = "/index.php?option=com_emundus&view=emails&layout=messagetriggers";
    }
  },
  methods: {
    loadTriggerData() {
      this.loading = true;
      if (this.triggerId > 0) {
        emailService.getEmailTriggerById(this.triggerId).then((response) => {
          if (response.status) {
            let tmpTrigger = response.data;
            tmpTrigger.program_ids = this.formattedProgramOptions.filter((program) => {
              return tmpTrigger.program_ids.includes(program.id);
            });
            tmpTrigger.profile_ids = this.profilesOptions.filter((profile) => {
              return tmpTrigger.profile_ids.includes(profile.id);
            });
            tmpTrigger.group_ids = this.groupsOptions.filter((group) => {
              return tmpTrigger.group_ids.includes(group.id);
            });
            this.userField.value = tmpTrigger.user_ids;
            this.triggerData = response.data;
            this.loading = false;
          } else {
            this.loading = false;
          }
        });
      } else {
        this.triggerData = {
          status: 0,
          program_ids: [],
          email_id: 0,
          sms_id: 0,
          to_current_user: 0,
          to_applicant: 0,
          group_ids: [],
          profile_ids: [],
          user_ids: [],
          all_program: 1
        };
        if (this.defaultProgramId > 0) {
          this.triggerData.all_program = 0;
          this.triggerData.program_ids = this.formattedProgramOptions.filter((program) => {
            return program.id === this.defaultProgramId;
          });
        }
        this.loading = false;
      }
    },
    getStatusOptions() {
      settingsService.getStatus().then((response) => {
        if (response.status) {
          this.statusOptions = response.data.sort((a, b) => {
            return a.ordering - b.ordering;
          });
        }
      });
    },
    getEmailsOptions() {
      emailService.getEmails().then((response) => {
        if (response.status) {
          this.emailsOptions = response.data.datas;
        }
      });
    },
    getSmsOptions() {
      if (this.smsActivated) {
        smsService.getSmsTemplates().then((response) => {
          if (response.status) {
            this.smsOptions = response.data.datas.map((sms) => {
              return {
                id: sms.id,
                label: sms.label.fr
              };
            });
          }
        });
      }
    },
    getProfilesOptions() {
      fileService.getProfiles().then((response) => {
        if (response.status) {
          this.profilesOptions = response.data.filter((profile) => {
            return profile.published != 1;
          }).map((profile) => {
            return {
              id: profile.id,
              label: profile.label
            };
          });
          if (this.triggerData.profile_ids) {
            this.triggerData.profile_ids = this.profilesOptions.filter((profile) => {
              return this.triggerData.profile_ids.includes(profile);
            });
          }
        }
      });
    },
    getGroupsOptions() {
      groupsService.getGroups().then((response) => {
        if (response.status) {
          this.groupsOptions = response.data;
          if (this.triggerData.group_ids) {
            this.triggerData.group_ids = this.groupsOptions.filter((group) => {
              return this.triggerData.group_ids.includes(group);
            });
          }
        }
      });
    },
    async getProgramOptions() {
      return await programmeService.getAllPrograms().then((response) => {
        if (response.status) {
          this.programOptions = response.data.datas;
        }
      });
    },
    areMandatoryFieldsFilled() {
      let filled = true;
      this.fieldsToDisplayError = [];
      for (const field of this.mandatoryFields) {
        if (Array.isArray(field)) {
          const atLeastOneFilled = field.some((entry) => {
            if (this.triggerData[entry] === void 0 || this.triggerData[entry] === null || this.triggerData[entry] < 1) {
              this.fieldsToDisplayError.push(entry);
              return false;
            }
            return true;
          });
          if (!atLeastOneFilled) {
            filled = false;
          }
        } else {
          switch (field) {
            case "status":
              if (this.triggerData.status === void 0 || this.triggerData.status === null) {
                this.fieldsToDisplayError.push(field);
                filled = false;
              }
              break;
            case "program_ids":
              if (this.triggerData.program_ids.length === 0 && this.triggerData.all_program == 0) {
                this.fieldsToDisplayError.push(field);
                filled = false;
              }
              break;
            default:
              if (!this.triggerData[field]) {
                this.fieldsToDisplayError.push(field);
                filled = false;
              }
          }
        }
      }
      return filled;
    },
    saveTrigger() {
      if (this.loading) {
        return;
      }
      if (this.areMandatoryFieldsFilled()) {
        this.triggerData.user_ids = this.userField.value.map((user) => {
          return user.value;
        });
        this.fieldsToDisplayError = [];
        emailService.saveTrigger(this.triggerData).then((response) => {
          if (response.status) {
            Swal.fire({
              title: this.translate("COM_EMUNDUS_TRIGGER_EDIT_SAVE_SUCCESS"),
              icon: "success",
              showCancelButton: false,
              showConfirmButton: false,
              delay: 3e3
            });
            settingsService.redirectJRoute(this.backUrl, useGlobalStore().getCurrentLang);
          } else {
            Swal.fire({
              title: this.translate("COM_EMUNDUS_TRIGGER_EDIT_SAVE_ERROR"),
              text: this.translate(response.msg),
              icon: "error",
              showCancelButton: false,
              showConfirmButton: false,
              delay: 3e3
            });
          }
        });
      }
    }
  },
  computed: {
    formattedProgramOptions() {
      const options = this.programOptions.map((program) => {
        return {
          id: program.id,
          label: program.label.fr
        };
      });
      return options.sort((a, b) => {
        return a.label.localeCompare(b.label);
      });
    }
  }
};
const _hoisted_1 = {
  id: "message-triggers-edit",
  class: "tw-mb-6 tw-flex tw-flex-col tw-rounded tw-bg-white tw-p-4 tw-shadow"
};
const _hoisted_2 = {
  id: "trigger",
  class: "tw-my-4 tw-flex tw-flex-col"
};
const _hoisted_3 = {
  id: "when-to-send",
  class: "tw-pt-4"
};
const _hoisted_4 = { class: "tw-mb-4" };
const _hoisted_5 = { class: "tw-flex tw-flex-col tw-gap-4" };
const _hoisted_6 = { class: "tw-flex tw-items-end tw-font-semibold" };
const _hoisted_7 = ["value"];
const _hoisted_8 = { class: "error-message" };
const _hoisted_9 = {
  id: "all_program",
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_10 = {
  class: "tw-mb-0 tw-flex tw-items-end tw-font-semibold",
  for: "all_program"
};
const _hoisted_11 = { class: "tw-flex tw-items-end tw-font-semibold" };
const _hoisted_12 = { class: "error-message" };
const _hoisted_13 = { class: "tw-mb-4" };
const _hoisted_14 = { class: "tw-flex tw-flex-col" };
const _hoisted_15 = {
  class: "tw-flex tw-items-end tw-font-semibold",
  for: "model_id"
};
const _hoisted_16 = { value: "0" };
const _hoisted_17 = ["value"];
const _hoisted_18 = {
  class: "tw-flex tw-items-end tw-font-semibold",
  for: "sms_id"
};
const _hoisted_19 = { value: "0" };
const _hoisted_20 = ["value"];
const _hoisted_21 = { class: "error-message" };
const _hoisted_22 = {
  id: "send-to-applicant",
  class: "tw-mt-4 tw-flex tw-flex-col tw-pt-4"
};
const _hoisted_23 = { class: "tw-mb-4" };
const _hoisted_24 = { class: "tw-flex tw-flex-col tw-gap-4" };
const _hoisted_25 = {
  id: "on_applicant_action",
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_26 = {
  class: "tw-mb-0 tw-flex tw-items-end tw-font-semibold",
  for: "on_applicant_action"
};
const _hoisted_27 = {
  id: "on_manager_action",
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_28 = {
  class: "tw-mb-0 tw-flex tw-items-end tw-font-semibold",
  for: "on_manager_action"
};
const _hoisted_29 = {
  id: "send-to-others",
  class: "tw-mt-4 tw-flex tw-flex-col tw-pt-4"
};
const _hoisted_30 = { class: "tw-mb-4" };
const _hoisted_31 = { class: "tw-mt-4 tw-flex tw-flex-col tw-gap-4" };
const _hoisted_32 = {
  key: 0,
  class: "tw-flex tw-flex-col"
};
const _hoisted_33 = { class: "tw-flex tw-items-end tw-font-semibold" };
const _hoisted_34 = {
  key: 1,
  class: "tw-flex tw-flex-col"
};
const _hoisted_35 = { class: "tw-flex tw-items-end tw-font-semibold" };
const _hoisted_36 = {
  id: "actions",
  class: "tw-flex tw-flex-row tw-justify-end"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Back = resolveComponent("Back");
  const _component_toggle_input = resolveComponent("toggle-input");
  const _component_Multiselect = resolveComponent("Multiselect");
  const _component_Info = resolveComponent("Info");
  const _component_Parameter = resolveComponent("Parameter");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_Back, {
      link: $data.backUrl,
      class: normalizeClass("tw-mb-4")
    }, null, 8, ["link"]),
    createBaseVNode("h1", null, toDisplayString(this.triggerId > 0 ? _ctx.translate("COM_EMUNDUS_TRIGGER_EDIT") : _ctx.translate("COM_EMUNDUS_TRIGGER_ADD")), 1),
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode("section", _hoisted_3, [
        createBaseVNode("h2", _hoisted_4, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_WHEN_TO_SEND")), 1),
        createBaseVNode("div", _hoisted_5, [
          createBaseVNode("div", {
            class: normalizeClass(["tw-flex tw-flex-col", { error: $data.fieldsToDisplayError.includes("status") }])
          }, [
            createBaseVNode("label", _hoisted_6, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_STATUS")), 1),
            withDirectives(createBaseVNode("select", {
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.triggerData.status = $event)
            }, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($data.statusOptions, (status) => {
                return openBlock(), createElementBlock("option", {
                  key: status.step,
                  value: status.step
                }, toDisplayString(status.value), 9, _hoisted_7);
              }), 128))
            ], 512), [
              [vModelSelect, $data.triggerData.status]
            ]),
            createBaseVNode("span", _hoisted_8, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_STATUS_ERROR_MESSAGE")), 1)
          ], 2),
          createBaseVNode("div", _hoisted_9, [
            createVNode(_component_toggle_input, {
              id: "all_program",
              value: $data.triggerData.all_program,
              "onUpdate:value": _cache[1] || (_cache[1] = ($event) => $data.triggerData.all_program = $event ? 1 : 0)
            }, null, 8, ["value"]),
            createBaseVNode("label", _hoisted_10, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_ALL_PROGRAM")), 1)
          ]),
          withDirectives(createVNode(Transition, { name: "fade" }, {
            default: withCtx(() => [
              createBaseVNode("div", {
                class: normalizeClass(["tw-flex tw-flex-col", { error: $data.fieldsToDisplayError.includes("program_ids") }])
              }, [
                createBaseVNode("label", _hoisted_11, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_PROGRAMMES")), 1),
                createVNode(_component_Multiselect, {
                  modelValue: $data.triggerData.program_ids,
                  "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.triggerData.program_ids = $event),
                  label: "label",
                  "track-by": "id",
                  options: $options.formattedProgramOptions,
                  multiple: true
                }, null, 8, ["modelValue", "options"]),
                createBaseVNode("span", _hoisted_12, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_PROGRAMMES_ERROR_MESSAGE")), 1)
              ], 2)
            ]),
            _: 1
          }, 512), [
            [vShow, $data.triggerData.all_program == 0]
          ])
        ])
      ]),
      createBaseVNode("section", {
        id: "message-to-send",
        class: normalizeClass(["tw-mt-4 tw-flex tw-flex-col tw-pt-4", { error: $data.fieldsToDisplayError.includes("email_id") || $data.fieldsToDisplayError.includes("sms_id") }])
      }, [
        createBaseVNode("h2", _hoisted_13, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_MODELS_SELECTION")), 1),
        createBaseVNode("div", _hoisted_14, [
          createBaseVNode("label", _hoisted_15, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_MODELS_EMAIL_SELECTION")), 1),
          withDirectives(createBaseVNode("select", {
            id: "email_id",
            "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.triggerData.email_id = $event)
          }, [
            createBaseVNode("option", _hoisted_16, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_MODELS_EMAIL_SELECTION_DEFAULT")), 1),
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.emailsOptions, (email) => {
              return openBlock(), createElementBlock("option", {
                key: email.id,
                value: email.id
              }, toDisplayString(email.subject), 9, _hoisted_17);
            }), 128))
          ], 512), [
            [vModelSelect, $data.triggerData.email_id]
          ])
        ]),
        $props.smsActivated ? (openBlock(), createElementBlock("div", {
          key: 0,
          class: normalizeClass(["tw-flex tw-flex-col", { error: $data.fieldsToDisplayError.includes("sms_id") }])
        }, [
          createBaseVNode("label", _hoisted_18, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_MODELS_SMS_SELECTION")), 1),
          withDirectives(createBaseVNode("select", {
            id: "sms_id",
            "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.triggerData.sms_id = $event)
          }, [
            createBaseVNode("option", _hoisted_19, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_MODELS_SMS_SELECTION_DEFAULT")), 1),
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.smsOptions, (sms) => {
              return openBlock(), createElementBlock("option", {
                key: sms.id,
                value: sms.id
              }, toDisplayString(sms.label), 9, _hoisted_20);
            }), 128))
          ], 512), [
            [vModelSelect, $data.triggerData.sms_id]
          ])
        ], 2)) : createCommentVNode("", true),
        createBaseVNode("span", _hoisted_21, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_MODELS_SELECTION_ERROR_MESSAGE")), 1)
      ], 2),
      createBaseVNode("section", _hoisted_22, [
        createBaseVNode("h2", _hoisted_23, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_APPLICANT")), 1),
        createBaseVNode("div", _hoisted_24, [
          createBaseVNode("div", _hoisted_25, [
            createVNode(_component_toggle_input, {
              id: "on_applicant_action",
              value: $data.triggerData.to_current_user,
              "onUpdate:value": _cache[5] || (_cache[5] = ($event) => $data.triggerData.to_current_user = $event ? 1 : 0)
            }, null, 8, ["value"]),
            createBaseVNode("label", _hoisted_26, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_ON_APPLICANT_ACTION")), 1)
          ]),
          createBaseVNode("div", _hoisted_27, [
            createVNode(_component_toggle_input, {
              id: "on_manager_action",
              value: $data.triggerData.to_applicant,
              "onUpdate:value": _cache[6] || (_cache[6] = ($event) => $data.triggerData.to_applicant = $event ? 1 : 0)
            }, null, 8, ["value"]),
            createBaseVNode("label", _hoisted_28, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_ON_MANAGER_ACTION")), 1)
          ])
        ])
      ]),
      createBaseVNode("section", _hoisted_29, [
        createBaseVNode("h2", _hoisted_30, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_MANAGERS")), 1),
        createVNode(_component_Info, { text: "COM_EMUNDUS_TRIGGER_EDIT_SENT_TO_MANAGERS_INTRO" }),
        createBaseVNode("div", _hoisted_31, [
          $data.profilesOptions.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_32, [
            createBaseVNode("label", _hoisted_33, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_ROLE")), 1),
            createVNode(_component_Multiselect, {
              modelValue: $data.triggerData.profile_ids,
              "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.triggerData.profile_ids = $event),
              label: "label",
              "track-by": "id",
              options: $data.profilesOptions,
              multiple: true,
              placeholder: _ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_ROLE_PLACEHOLDER"),
              "select-label": _ctx.translate("PRESS_ENTER_TO_SELECT")
            }, null, 8, ["modelValue", "options", "placeholder", "select-label"])
          ])) : createCommentVNode("", true),
          $data.groupsOptions.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_34, [
            createBaseVNode("label", _hoisted_35, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_GROUPS")), 1),
            createVNode(_component_Multiselect, {
              modelValue: $data.triggerData.group_ids,
              "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.triggerData.group_ids = $event),
              label: "label",
              "track-by": "id",
              options: $data.groupsOptions,
              multiple: true,
              placeholder: _ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_SEND_TO_USERS_WITH_GROUPS_PLACEHOLDER"),
              "select-label": _ctx.translate("PRESS_ENTER_TO_SELECT")
            }, null, 8, ["modelValue", "options", "placeholder", "select-label"])
          ])) : createCommentVNode("", true),
          !$data.loading ? (openBlock(), createBlock(_component_Parameter, {
            key: 2,
            "parameter-object": $data.userField,
            "multiselect-options": $data.userField.multiselectOptions ? $data.userField.multiselectOptions : null
          }, null, 8, ["parameter-object", "multiselect-options"])) : createCommentVNode("", true)
        ])
      ])
    ]),
    createBaseVNode("div", _hoisted_36, [
      createBaseVNode("button", {
        id: "save",
        class: "tw-btn-primary",
        onClick: _cache[9] || (_cache[9] = (...args) => $options.saveTrigger && $options.saveTrigger(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_TRIGGER_EDIT_SAVE")), 1)
    ])
  ]);
}
const TriggersEdit = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  TriggersEdit as default
};
