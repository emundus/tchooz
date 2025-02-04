import { _ as _export_sfc, I as mixin, T as Tabs, J as History, S as Swal$1, s as settingsService, r as resolveComponent, c as createElementBlock, o as openBlock, g as createVNode, a as createCommentVNode, b as createBlock, F as Fragment, d as createBaseVNode, t as toDisplayString, e as renderList, h as withDirectives, v as vShow, D as createTextVNode, f as normalizeClass, U as script, $ as client, q as axios, R as vModelText, n as normalizeStyle, p as errors, V as VueDraggableNext, u as useGlobalStore, w as withCtx, Y as withKeys, Q as defineStore, X as V32, G as vModelCheckbox, z as mergeProps, a1 as resolveDynamicComponent } from "./app_emundus.js";
import { P as Parameter } from "./Parameter.js";
import { I as Info } from "./Info.js";
import { t as translationsService, T as Translations } from "./Translations.js";
import { q as qs } from "./index2.js";
import { C as ColorPicker, b as basicPreset } from "./ColorPicker.js";
import { v as vueDropzone } from "./vue-dropzone.js";
/* empty css       */
import WorkflowSettings from "./WorkflowSettings.js";
import "./index.js";
const _sfc_main$k = {
  name: "EditEmailJoomla",
  components: { History, Tabs, Info, Parameter },
  props: {},
  mixins: [mixin],
  data() {
    return {
      loading: true,
      errorMessage: "",
      tabs: [
        {
          id: 1,
          name: "COM_EMUNDUS_GLOBAL_EMAIL",
          icon: "email",
          active: true,
          displayed: true
        },
        {
          id: 2,
          name: "COM_EMUNDUS_GLOBAL_HISTORY",
          icon: "history",
          active: false,
          displayed: true
        }
      ],
      mailonline_key: 0,
      mailonline_parameter: {
        param: "mailonline",
        type: "toggle",
        value: 0,
        label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_ENABLE",
        displayed: true,
        hideLabel: true
      },
      reply_to_parameters: [
        {
          param: "replyto",
          type: "email",
          placeholder: "no-reply@tchooz.app",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_REPLYTO",
          helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_REPLYTO_ADRESS_HELPTEXT",
          displayed: true,
          optional: true
        },
        {
          param: "replytoname",
          type: "text",
          placeholder: "Tchooz",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_REPLY_TO_NAME",
          helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_REPLYTO_HELPTEXT",
          displayed: true,
          optional: true
        }
      ],
      custom_enable_parameter: {
        param: "custom_email_conf",
        type: "toggle",
        value: 0,
        label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CUSTOM",
        displayed: true,
        hideLabel: true
      },
      default_email_sender_param: {
        param: "default_email_mailfrom",
        type: "text",
        placeholder: "",
        value: "",
        label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER",
        helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER_ADRESS_HELPTEXT",
        displayed: true
      },
      email_sender_param: {
        param: "custom_email_mailfrom",
        type: "text",
        placeholder: "",
        value: "",
        concatValue: "",
        label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER",
        helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER_ADRESS_HELPTEXT",
        displayed: true,
        splitField: true,
        splitChar: "@"
      },
      smtp_parameters: [
        {
          param: "custom_email_smtphost",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_HOST",
          helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_HOSTSMTP_HELPTEXT",
          displayed: true
        },
        {
          param: "custom_email_smtpport",
          type: "text",
          placeholder: "465",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_PORT",
          helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_HELPTEXT",
          displayed: true
        }
      ],
      enable_smtp_auth: {
        param: "custom_email_smtpauth",
        type: "toggle",
        value: 1,
        label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_ENABLE",
        displayed: true,
        hideLabel: true
      },
      smtp_security_parameter: {
        param: "custom_email_smtpsecure",
        type: "select",
        value: 0,
        label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_SECURITY",
        helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SECURITY_HELPTEXT",
        "options": [
          {
            "value": "none",
            "label": "COM_EMUNDUS_FILTERS_CHECK_NONE"
          },
          {
            "value": "ssl",
            "label": "SSL"
          },
          {
            "value": "tls",
            "label": "TLS"
          }
        ],
        displayed: true
      },
      smtp_auth_parameters: [
        {
          param: "custom_email_smtpuser",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_USERNAME",
          helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_HOSTSMTP_HELPTEXT",
          displayed: true
        },
        {
          param: "custom_email_smtppass",
          type: "password",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_PASSWORD",
          helptext: "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_HOSTPASSWORD_HELPTEXT",
          displayed: true
        }
      ],
      default_mail_from_server: "tchooz.io"
    };
  },
  created() {
    this.getEmailParameters();
  },
  mounted() {
  },
  methods: {
    getEmailParameters() {
      settingsService.getEmailParameters().then((response) => {
        if (response.status) {
          this.mailonline_parameter.value = response.data.mailonline ? 1 : 0;
          this.reply_to_parameters[0].value = response.data.replyto;
          this.reply_to_parameters[1].value = response.data.replytoname;
          this.custom_enable_parameter.value = response.data.custom_email_conf;
          this.email_sender_param.value = response.data.custom_email_mailfrom;
          this.smtp_parameters[0].value = response.data.custom_email_smtphost;
          this.smtp_parameters[1].value = response.data.custom_email_smtpport;
          this.enable_smtp_auth.value = response.data.custom_email_smtpauth;
          this.smtp_security_parameter.value = response.data.custom_email_smtpsecure;
          this.smtp_auth_parameters[0].value = response.data.custom_email_smtpuser;
          this.smtp_auth_parameters[1].value = response.data.custom_email_smtppass;
          this.default_email_sender_param.value = response.data.default_email_mailfrom;
          this.default_email_sender_param.value = this.default_email_sender_param.value.split("@");
          this.default_mail_from_server = this.default_email_sender_param.value[1];
          this.default_email_sender_param.value = this.default_email_sender_param.value[0];
          this.loading = false;
        }
      });
    },
    async testEmail(testing_email = null) {
      this.loading = true;
      const parameters = this.prepareParameters(testing_email);
      settingsService.testEmail(parameters).then(async (response) => {
        this.loading = false;
        if (response.status) {
          Swal$1.fire({
            title: response.title,
            html: response.text,
            confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_SAVE"),
            cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
            showCancelButton: true,
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-cancel-button",
              cancelButton: "em-swal-confirm-button",
              htmlContainer: "tw-text-center"
            },
            didOpen: () => {
              document.querySelector("#sendEmailNew").addEventListener("click", () => {
                let value = document.querySelector("#otherEmail").value;
                const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (value === "" || !regex.exec(value)) {
                  document.querySelector("#otherEmail").classList.add("!tw-border-red-500");
                  return;
                }
                this.testEmail(document.querySelector("#otherEmail").value);
                Swal$1.close();
              });
            }
          }).then((result) => {
            if (result.isConfirmed) {
              this.saveConfiguration();
            }
          });
        } else {
          this.errorMessage = response.desc;
          if (!this.errorMessage) {
            this.errorMessage = this.translate("COM_EMUNDUS_ERROR_SMTP_AUTH");
          }
          if (!response.title) {
            response.title = this.translate("COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR");
          }
          Swal$1.fire({
            title: response.title,
            html: response.text,
            cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
            showConfirmButton: false,
            customClass: {
              title: "em-swal-title",
              cancelButton: "em-swal-confirm-button",
              htmlContainer: "tw-text-center"
            },
            didOpen() {
              if (response.desc !== "") {
                let errors2 = document.querySelector("#error_message_test");
                document.querySelector("#swal2-html-container").appendChild(errors2);
              }
            }
          });
        }
      });
    },
    async saveConfiguration() {
      const parameters = this.prepareParameters();
      settingsService.saveEmailParameters(parameters).then(async (response) => {
        if (response.status) {
          Swal$1.fire({
            title: response.msg,
            text: response.desc,
            confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_DOSSIERS_CLOSE"),
            showCancelButton: false,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              htmlContainer: "!tw-text-center",
              actions: "!tw-justify-center"
            }
          });
        } else {
          Swal$1.fire({
            title: response.msg,
            text: response.desc,
            confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_DOSSIERS_CLOSE"),
            showCancelButton: false,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              htmlContainer: "!tw-text-center",
              actions: "!tw-justify-center"
            }
          });
        }
      });
    },
    prepareParameters(testing_email = null) {
      this.email_sender_param.value = this.email_sender_param.concatValue;
      return {
        mailonline: this.mailonline_parameter.value,
        replyto: this.reply_to_parameters[0].value,
        replytoname: this.reply_to_parameters[1].value,
        custom_email_conf: this.custom_enable_parameter.value,
        custom_email_mailfrom: this.email_sender_param.value,
        custom_email_smtphost: this.smtp_parameters[0].value,
        custom_email_smtpport: this.smtp_parameters[1].value,
        custom_email_smtpauth: this.enable_smtp_auth.value,
        custom_email_smtpuser: this.smtp_auth_parameters[0].value,
        custom_email_smtppass: this.smtp_auth_parameters[1].value,
        custom_email_smtpsecure: this.smtp_security_parameter.value,
        default_email_mailfrom: this.default_email_sender_param.value + "@" + this.default_mail_from_server,
        testing_email: typeof testing_email === "string" ? testing_email : ""
      };
    },
    showPortWarning() {
      Swal$1.fire({
        html: `
    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-center tw--mt-5">
      <h2 class="tw-font-bold">
        ${this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_HELPTEXT_TITLE")}
      </h2>
      <p class="tw-text-center tw-mt-5 tw-text-neutral-700">
        ${this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_HELPTEXT_BODY")}
      </p>
    </div>
  `,
        showCancelButton: false,
        showConfirmButton: true,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action",
          popup: "tw-px-6 tw-py-4 tw-flex tw-justify-center tw-items-center"
        }
      });
    },
    showDisableWarning(parameter, oldVal, value) {
      if (oldVal === null) {
        return;
      }
      if (value != 1) {
        Swal$1.fire({
          title: this.translate("COM_EMUNDUS_SURE_TO_DISABLE"),
          text: this.translate("COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_SURE_TO_DISABLE_TEXT"),
          showCancelButton: true,
          showConfirmButton: true,
          confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_OK"),
          cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            cancelButton: "em-swal-cancel-button",
            confirmButton: "em-swal-confirm-button"
          }
        }).then((response) => {
          if (!response.isConfirmed) {
            this.mailonline_parameter.value = oldVal;
            this.mailonline_key = Math.random();
          } else {
            this.saveConfiguration();
          }
        });
      }
    }
  },
  computed: {
    disabledSubmit() {
      return this.mailonline_parameter.value == 0 || this.default_email_sender_param.value == "" || this.custom_enable_parameter.value == 1 && (this.email_sender_param.value == "" || this.email_sender_param.value == null || this.smtp_parameters[0].value == "" || this.smtp_parameters[0].value == null);
    },
    displayEmailParameters() {
      return this.mailonline_parameter.value == 1;
    },
    displayCustomParameters() {
      return this.custom_enable_parameter.value == 1;
    },
    displaySmtpAuthParameters() {
      return this.enable_smtp_auth.value == 1;
    },
    incorrectPort() {
      if (this.smtp_parameters[1].value !== null && this.smtp_parameters[1].value !== "" && !["25", "465", "587"].includes(this.smtp_parameters[1].value.toString())) {
        return true;
      } else {
        return false;
      }
    }
  }
};
const _hoisted_1$k = { class: "tw-w-full tw-rounded-coordinator tw-p-6 tw-bg-white tw-border tw-border-neutral-300 tw-relative" };
const _hoisted_2$k = { class: "tw-hidden" };
const _hoisted_3$k = {
  id: "error_message_test",
  class: "tw-mt-7"
};
const _hoisted_4$k = { class: "tw-text-red-500 tw-mb-2 tw-text-center" };
const _hoisted_5$i = { class: "tw-mt-7" };
const _hoisted_6$h = { class: "tw-mt-7 tw-flex tw-gap-7" };
const _hoisted_7$f = { class: "tw-mt-7" };
const _hoisted_8$d = {
  class: "tw-mt-7",
  style: { "width": "40%" }
};
const _hoisted_9$a = { class: "tw-mt-7 tw-flex tw-gap-7" };
const _hoisted_10$9 = { class: "tw-ml-1" };
const _hoisted_11$8 = { class: "tw-mt-7" };
const _hoisted_12$5 = { class: "tw-mt-7" };
const _hoisted_13$4 = {
  key: 0,
  class: "tw-mt-7 tw-flex tw-gap-7"
};
const _hoisted_14$4 = {
  key: 1,
  class: "tw-mt-7 tw-flex tw-items-end tw-gap-2"
};
const _hoisted_15$4 = { class: "tw-flex tw-gap-1 tw-mb-3" };
const _hoisted_16$3 = { class: "tw-flex tw-justify-between tw-mt-7" };
const _hoisted_17$3 = ["disabled"];
const _hoisted_18$3 = ["disabled"];
const _hoisted_19$3 = {
  key: 2,
  class: "em-page-loader"
};
function _sfc_render$k(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Tabs = resolveComponent("Tabs");
  const _component_Info = resolveComponent("Info");
  const _component_Parameter = resolveComponent("Parameter");
  const _component_History = resolveComponent("History");
  return openBlock(), createElementBlock("div", _hoisted_1$k, [
    createVNode(_component_Tabs, { tabs: $data.tabs }, null, 8, ["tabs"]),
    !$data.loading && $data.tabs[0].active ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
      createVNode(_component_Info, { text: "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_EMAIL_HELPTEXT" }),
      createBaseVNode("template", _hoisted_2$k, [
        createBaseVNode("div", _hoisted_3$k, [
          createBaseVNode("p", _hoisted_4$k, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_EMAIL_ERRORS_DETAILS")), 1),
          createVNode(_component_Info, {
            text: $data.errorMessage,
            "bg-color": "tw-bg-red-50",
            icon: "error",
            "icon-color": "tw-text-red-600",
            "text-color": "tw-text-red-500"
          }, null, 8, ["text"]),
          _cache[3] || (_cache[3] = createBaseVNode("br", null, null, -1))
        ])
      ]),
      createBaseVNode("div", _hoisted_5$i, [
        (openBlock(), createBlock(_component_Parameter, {
          key: $data.mailonline_key,
          "parameter-object": $data.mailonline_parameter,
          onValueUpdated: $options.showDisableWarning
        }, null, 8, ["parameter-object", "onValueUpdated"]))
      ]),
      $options.displayEmailParameters ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
        createBaseVNode("div", _hoisted_6$h, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.reply_to_parameters, (parameter) => {
            return withDirectives((openBlock(), createElementBlock("div", {
              key: parameter.param,
              class: "tw-w-full"
            }, [
              createVNode(_component_Parameter, { "parameter-object": parameter }, null, 8, ["parameter-object"])
            ])), [
              [vShow, parameter.displayed]
            ]);
          }), 128))
        ]),
        createBaseVNode("div", _hoisted_7$f, [
          createVNode(_component_Parameter, { "parameter-object": $data.custom_enable_parameter }, null, 8, ["parameter-object"])
        ]),
        $options.displayCustomParameters ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
          createBaseVNode("div", _hoisted_8$d, [
            createVNode(_component_Parameter, { "parameter-object": $data.email_sender_param }, null, 8, ["parameter-object"])
          ]),
          createBaseVNode("div", _hoisted_9$a, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.smtp_parameters, (parameter) => {
              return withDirectives((openBlock(), createElementBlock("div", {
                key: parameter.param,
                class: "tw-w-full"
              }, [
                createVNode(_component_Parameter, { "parameter-object": parameter }, null, 8, ["parameter-object"]),
                parameter.param == "custom_email_smtpport" && $options.incorrectPort ? (openBlock(), createElementBlock("div", {
                  key: 0,
                  class: "tw-flex tw-items-start",
                  onClick: _cache[0] || (_cache[0] = (...args) => $options.showPortWarning && $options.showPortWarning(...args))
                }, [
                  _cache[5] || (_cache[5] = createBaseVNode("span", { class: "material-symbols-outlined tw-scale-75 tw-text-orange-600 tw-pr-2" }, "warning", -1)),
                  createBaseVNode("p", null, [
                    createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING")) + " ", 1),
                    createBaseVNode("u", _hoisted_10$9, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_SEE_ALL")), 1),
                    _cache[4] || (_cache[4] = createTextVNode(". "))
                  ])
                ])) : createCommentVNode("", true)
              ])), [
                [vShow, parameter.displayed]
              ]);
            }), 128))
          ]),
          createBaseVNode("div", _hoisted_11$8, [
            createVNode(_component_Parameter, { "parameter-object": $data.smtp_security_parameter }, null, 8, ["parameter-object"])
          ]),
          createBaseVNode("div", _hoisted_12$5, [
            createVNode(_component_Parameter, { "parameter-object": $data.enable_smtp_auth }, null, 8, ["parameter-object"])
          ]),
          $options.displaySmtpAuthParameters ? (openBlock(), createElementBlock("div", _hoisted_13$4, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.smtp_auth_parameters, (parameter) => {
              return withDirectives((openBlock(), createElementBlock("div", {
                key: parameter.param,
                class: "tw-w-full"
              }, [
                createVNode(_component_Parameter, { "parameter-object": parameter }, null, 8, ["parameter-object"])
              ])), [
                [vShow, parameter.displayed]
              ]);
            }), 128))
          ])) : createCommentVNode("", true)
        ], 64)) : (openBlock(), createElementBlock("div", _hoisted_14$4, [
          createVNode(_component_Parameter, { "parameter-object": $data.default_email_sender_param }, null, 8, ["parameter-object"]),
          createBaseVNode("div", _hoisted_15$4, [
            _cache[6] || (_cache[6] = createBaseVNode("span", null, "@", -1)),
            createBaseVNode("span", null, toDisplayString($data.default_mail_from_server), 1)
          ])
        ])),
        createBaseVNode("div", _hoisted_16$3, [
          createBaseVNode("button", {
            type: "button",
            disabled: $options.disabledSubmit,
            class: "tw-btn-tertiary tw-cursor-pointer",
            onClick: _cache[1] || (_cache[1] = (...args) => $options.testEmail && $options.testEmail(...args))
          }, [
            _cache[7] || (_cache[7] = createBaseVNode("span", { class: "material-symbols-outlined tw-mr-2" }, "send", -1)),
            createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_BT")), 1)
          ], 8, _hoisted_17$3),
          createBaseVNode("button", {
            type: "button",
            disabled: $options.disabledSubmit,
            class: "tw-btn-primary tw-cursor-pointer",
            onClick: _cache[2] || (_cache[2] = (...args) => $options.saveConfiguration && $options.saveConfiguration(...args))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SAVE")), 9, _hoisted_18$3)
        ])
      ], 64)) : createCommentVNode("", true)
    ], 64)) : createCommentVNode("", true),
    !$data.loading && $data.tabs[1].active ? (openBlock(), createBlock(_component_History, {
      key: 1,
      extension: "com_emundus.settings.email",
      columns: ["title", "message_language_key", "log_date", "user_id"]
    })) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_19$3)) : createCommentVNode("", true)
  ]);
}
const EditEmailJoomla = /* @__PURE__ */ _export_sfc(_sfc_main$k, [["render", _sfc_render$k]]);
const _sfc_main$j = {
  name: "WebSecurity",
  components: { History, Tabs, Parameter, Info },
  props: {},
  data() {
    return {
      livesite: null,
      ssl_cert: null,
      current_requests: null,
      parameters: [
        {
          param: "update_web_address",
          type: "yesno",
          value: 0,
          label: "COM_EMUNDUS_GLOBAL_WEB_SECURITY_UPDATE_WEB_ADDRESS",
          helptext: "COM_EMUNDUS_GLOBAL_WEB_SECURITY_UPDATE_WEB_ADDRESS_HELPTEXT",
          displayed: true
        },
        {
          param: "new_address",
          type: "text",
          value: "",
          placeholder: "https://example.tchooz.app",
          label: "COM_EMUNDUS_GLOBAL_WEB_SECURITY_NEW_ADDRESS",
          displayed: false,
          displayedOn: "update_web_address"
        },
        {
          param: "use_own_ssl_certificate",
          type: "yesno",
          value: 0,
          label: "COM_EMUNDUS_GLOBAL_WEB_SECURITY_USE_OWN_SSL_CERTIFICATE",
          helptext: "COM_EMUNDUS_GLOBAL_WEB_SECURITY_USE_OWN_SSL_CERTIFICATE_HELPTEXT",
          displayed: true
        },
        {
          param: "technical_contacts",
          type: "multiselect",
          optional: 0,
          multiselectOptions: {
            noOptions: true,
            multiple: true,
            taggable: true,
            searchable: true,
            optionsPlaceholder: "",
            selectLabel: "",
            selectGroupLabel: "",
            selectedLabel: "",
            deselectedLabel: "",
            deselectGroupLabel: "",
            noOptionsText: "",
            tagValidations: ["email"],
            options: []
          },
          value: [],
          label: "COM_EMUNDUS_GLOBAL_WEB_SECURITY_TECHNICAL_CONTACTS",
          helptext: "COM_EMUNDUS_GLOBAL_WEB_SECURITY_TECHNICAL_CONTACTS_HELPTEXT",
          placeholder: "user1@example.fr, user2@example.fr",
          displayed: true
        }
      ],
      tabs: [
        {
          id: 1,
          name: "COM_EMUNDUS_GLOBAL_WEB_SECURITY_REQUEST",
          icon: "send",
          active: true,
          displayed: true
        },
        {
          id: 2,
          name: "COM_EMUNDUS_GLOBAL_HISTORY",
          icon: "history",
          active: false,
          displayed: true
        }
      ]
    };
  },
  created() {
    this.getLivesite();
    this.getSSLInfo();
    this.getCurrentRequests();
  },
  methods: {
    getLivesite() {
      settingsService.getLiveSite().then((response) => {
        this.livesite = response.data;
      });
    },
    getSSLInfo() {
      settingsService.getsslinfo().then((response) => {
        this.ssl_cert = response.data;
      });
    },
    getCurrentRequests() {
      settingsService.getHistory("com_emundus.settings.web_security", true).then((response) => {
        this.current_requests = response.data;
      });
    },
    checkConditional(parameter, oldValue, value) {
      let paramsToShow = this.parameters.find((param) => param.displayedOn === parameter.param);
      if (paramsToShow) {
        paramsToShow.displayed = value == 1;
      }
    },
    sendRequest() {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_CONFIRMATION"),
        html: document.querySelector("#web_security_resume").outerHTML,
        showCancelButton: true,
        confirmButtonText: this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_CONFIRMATION_BUTTON"),
        cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          cancelButton: "em-swal-cancel-button",
          confirmButton: "em-swal-confirm-button"
        }
      }).then((result) => {
        if (result.isConfirmed) {
          let data = [];
          this.parameters.forEach((param) => {
            data[param.param] = param.value;
          });
          Swal.fire({
            position: "center",
            iconHtml: '<img class="em-sending-email-img tw-w-1/2 tw-max-w-none" src="/media/com_emundus/images/tchoozy/complex-illustrations/sending-message.svg"/>',
            title: Joomla.Text._("COM_EMUNDUS_GLOBAL_WEB_SECURITY_REQUEST_PENDING"),
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
              icon: "em-swal-icon"
            }
          });
          settingsService.sendRequest(data).then((response) => {
            Swal.fire({
              title: this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_REQUEST_SENT"),
              icon: "success",
              showConfirmButton: false,
              customClass: {
                title: "em-swal-title"
              },
              timer: 3e3
            });
          });
        }
      });
    }
  },
  computed: {
    current_requests_pending() {
      return "<p>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_CURRENT_REQUESTS_PENDING") + "</p>";
    },
    information() {
      let text = "<p>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_YOUR_LIVESITE") + "<b>" + this.livesite + "</b></p>";
      if (this.ssl_cert) {
        text += "<br/><p>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_SSL_CERT") + "<b>" + this.ssl_cert.type + "</b></p>";
      }
      return text;
    },
    new_address_warning() {
      return "<p>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_NEW_ADDRESS_WARNING") + "<b>" + this.livesite + "</b></p><p>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_NEW_ADDRESS_WARNING_2") + "</p>";
    },
    own_ssl_ask() {
      return "<p>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_OWN_SSL_ASK") + "</p>";
    },
    resume() {
      let resume = "<p>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_RESUME") + "</p>";
      resume += "<ul>";
      let update_web_address = this.parameters.find((param) => param.param === "update_web_address");
      let new_address = this.parameters.find((param) => param.param === "new_address");
      if (update_web_address.value == 1) {
        resume += "<li>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_NEW_ADDRESS_TO_UPDATE") + '<b>"' + new_address.value + '"</b></li>';
      }
      let use_own_ssl_certificate = this.parameters.find((param) => param.param === "use_own_ssl_certificate");
      if (use_own_ssl_certificate.value == 1) {
        resume += "<li>" + this.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_OWN_SSL_CERTIFICATE") + "</li>";
      }
      resume += "</ul>";
      return resume;
    },
    loading() {
      if (this.livesite === null || this.ssl_cert === null || this.current_requests === null) {
        return true;
      } else {
        return false;
      }
    },
    disabledSubmit() {
      let disabled = true;
      let update_web_address = this.parameters.find((param) => param.param === "update_web_address");
      let new_address = this.parameters.find((param) => param.param === "new_address");
      let use_own_ssl_certificate = this.parameters.find((param) => param.param === "use_own_ssl_certificate");
      let technical_contacts = this.parameters.find((param) => param.param === "technical_contacts");
      if (update_web_address.value == 1) {
        if (new_address.value !== "") {
          disabled = false;
        } else {
          return true;
        }
      } else if (use_own_ssl_certificate.value == 1) {
        disabled = false;
      } else {
        return true;
      }
      if (technical_contacts.value.length > 0) {
        disabled = false;
      } else {
        return true;
      }
      return disabled;
    }
  }
};
const _hoisted_1$j = { class: "tw-w-full tw-rounded-coordinator tw-p-6 tw-bg-white tw-border tw-border-neutral-300 tw-relative" };
const _hoisted_2$j = {
  key: 2,
  class: "tw-mt-7 tw-flex tw-flex-col"
};
const _hoisted_3$j = { class: "tw-mb-7" };
const _hoisted_4$j = { class: "tw-hidden" };
const _hoisted_5$h = { id: "web_security_resume" };
const _hoisted_6$g = { class: "tw-self-end" };
const _hoisted_7$e = ["disabled"];
const _hoisted_8$c = {
  key: 2,
  class: "em-page-loader"
};
function _sfc_render$j(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Tabs = resolveComponent("Tabs");
  const _component_Info = resolveComponent("Info");
  const _component_Parameter = resolveComponent("Parameter");
  const _component_History = resolveComponent("History");
  return openBlock(), createElementBlock("div", _hoisted_1$j, [
    createVNode(_component_Tabs, { tabs: $data.tabs }, null, 8, ["tabs"]),
    !$options.loading && $data.tabs[0].active ? (openBlock(), createElementBlock(Fragment, { key: 0 }, [
      $data.current_requests.length > 0 ? (openBlock(), createBlock(_component_Info, {
        key: 0,
        text: $options.current_requests_pending,
        icon: "warning",
        "bg-color": "tw-bg-orange-50",
        "icon-type": "material-symbols-outlined",
        "icon-color": "tw-text-orange-600"
      }, null, 8, ["text"])) : createCommentVNode("", true),
      this.livesite ? (openBlock(), createBlock(_component_Info, {
        key: 1,
        text: $options.information,
        class: normalizeClass("tw-mt-3")
      }, null, 8, ["text"])) : createCommentVNode("", true),
      !this.loading ? (openBlock(), createElementBlock("div", _hoisted_2$j, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.parameters, (parameter, index) => {
          return withDirectives((openBlock(), createElementBlock("div", _hoisted_3$j, [
            createVNode(_component_Parameter, {
              "parameter-object": parameter,
              onValueUpdated: $options.checkConditional,
              "multiselect-options": parameter.multiselectOptions ? parameter.multiselectOptions : null
            }, null, 8, ["parameter-object", "onValueUpdated", "multiselect-options"]),
            parameter.param === "new_address" ? (openBlock(), createBlock(_component_Info, {
              key: 0,
              text: $options.new_address_warning,
              icon: "warning",
              "bg-color": "tw-bg-orange-50",
              "icon-type": "material-symbols-outlined",
              "icon-color": "tw-text-orange-600",
              class: normalizeClass("tw-mt-7")
            }, null, 8, ["text"])) : createCommentVNode("", true)
          ], 512)), [
            [vShow, parameter.displayed]
          ]);
        }), 256)),
        createBaseVNode("template", _hoisted_4$j, [
          createBaseVNode("div", _hoisted_5$h, [
            createVNode(_component_Info, { text: $options.resume }, null, 8, ["text"]),
            _cache[1] || (_cache[1] = createBaseVNode("br", null, null, -1)),
            createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_GLOBAL_WEB_SECURITY_CONFIRMATION_TEXT")), 1)
          ])
        ]),
        createBaseVNode("div", _hoisted_6$g, [
          createBaseVNode("button", {
            type: "button",
            disabled: $options.disabledSubmit,
            class: "tw-btn-primary",
            onClick: _cache[0] || (_cache[0] = (...args) => $options.sendRequest && $options.sendRequest(...args))
          }, [
            _cache[2] || (_cache[2] = createBaseVNode("span", { class: "material-symbols-outlined tw-mr-2" }, "send", -1)),
            createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SEND")), 1)
          ], 8, _hoisted_7$e)
        ])
      ])) : createCommentVNode("", true)
    ], 64)) : createCommentVNode("", true),
    !$options.loading && $data.tabs[1].active ? (openBlock(), createBlock(_component_History, {
      key: 1,
      extension: "com_emundus.settings.web_security",
      columns: ["title", "message_language_key", "log_date", "user_id", "status"]
    })) : createCommentVNode("", true),
    $options.loading ? (openBlock(), createElementBlock("div", _hoisted_8$c)) : createCommentVNode("", true)
  ]);
}
const WebSecurity = /* @__PURE__ */ _export_sfc(_sfc_main$j, [["render", _sfc_render$j]]);
const _sfc_main$i = {
  name: "SidebarMenu",
  components: {},
  props: {
    menusList: {
      type: Array,
      required: true
    }
  },
  mixins: [],
  data() {
    return {
      menus: [],
      activeMenu: null,
      minimized: false,
      showMinimized: false
    };
  },
  created() {
    this.menus = this.$props.menusList;
    this.activeMenu = 0;
    const sessionMenu = sessionStorage.getItem("tchooz_selected_menu/" + this.$props.id + "/" + document.location.hostname);
    const sessionSideBarMinimized = sessionStorage.getItem("tchooz_sidebar_minimized/" + document.location.hostname);
    if (sessionSideBarMinimized) {
      this.minimized = sessionSideBarMinimized === "true";
    }
    if (sessionMenu) {
      this.activeMenu = parseInt(sessionMenu);
    }
    if (window.location.hash) {
      let hash = window.location.hash.substring(1);
      for (let index in this.menus) {
        if (this.menus[index].name === hash) {
          this.activeMenu = parseInt(index);
          break;
        }
      }
    }
    this.$emit("listMenus", this.menus, "menus");
  },
  mounted() {
  },
  methods: {
    clickReturn() {
      if (window.history.length > 1) {
        window.history.back();
      } else {
        window.location.href = "/";
      }
    },
    handleSidebarSize() {
      this.minimized = !this.minimized;
    }
  },
  watch: {
    activeMenu: function(val) {
      sessionStorage.setItem("tchooz_selected_menu/" + this.$props.id + "/" + document.location.hostname, val);
      this.$emit("menuSelected", this.menus[val]);
    },
    minimized: function(val, oldVal) {
      if (oldVal !== null) {
        sessionStorage.setItem("tchooz_sidebar_minimized/" + document.location.hostname, val);
      }
    }
  }
};
const _hoisted_1$i = { class: "tw-flex tw-flex-col tw-items-left tw-gap-3 tw-p-3 tw-space-y-2 tw-font-large tw-list-none" };
const _hoisted_2$i = { class: "tw-w-10 tw-flex tw-items-center tw-justify-between" };
const _hoisted_3$i = {
  key: 0,
  class: "tw-pl-1 tw-text-neutral-900"
};
const _hoisted_4$i = {
  key: 0,
  class: "!tw-mt-0 tw-w-full"
};
const _hoisted_5$g = ["id", "onClick"];
const _hoisted_6$f = ["title", "id"];
function _sfc_render$i(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("aside", {
    id: "logo-sidebar",
    class: normalizeClass(["corner-bottom-left-background tw-sticky tw-left-0 tw-top-0 tw-h-screen tw-bg-white tw-border-r tw-border-gray-200 tw-transition-all", $data.minimized === true ? "tw-w-[64px]" : "tw-w-64"]),
    "aria-label": "Sidebar"
  }, [
    createBaseVNode("div", {
      class: "tw-h-full tw-pb-4 tw-overflow-y-auto tw-bg-white",
      onMouseover: _cache[2] || (_cache[2] = ($event) => $data.showMinimized = true),
      onMouseleave: _cache[3] || (_cache[3] = ($event) => $data.showMinimized = false)
    }, [
      createBaseVNode("ul", _hoisted_1$i, [
        createBaseVNode("li", _hoisted_2$i, [
          createBaseVNode("span", {
            class: "tw-flex tw-items-center tw-group tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300",
            onClick: _cache[0] || (_cache[0] = ($event) => $options.clickReturn())
          }, [
            _cache[4] || (_cache[4] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-600 tw-user-select-none" }, "navigate_before", -1)),
            $data.minimized === false ? (openBlock(), createElementBlock("span", _hoisted_3$i, toDisplayString(_ctx.translate("BACK")), 1)) : createCommentVNode("", true)
          ]),
          withDirectives(createBaseVNode("span", {
            class: normalizeClass(["material-symbols-outlined tw-absolute tw-right-[-12px] !tw-text-xl/5 tw-bg-neutral-400 tw-rounded-full tw-cursor-pointer", $data.minimized ? "tw-rotate-180" : ""]),
            onClick: _cache[1] || (_cache[1] = (...args) => $options.handleSidebarSize && $options.handleSidebarSize(...args))
          }, "chevron_left", 2), [
            [vShow, $data.showMinimized === true]
          ])
        ]),
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.menus, (menu, indexMenu) => {
          return openBlock(), createElementBlock(Fragment, {
            key: _ctx.$props.id + "_" + menu.name
          }, [
            menu.published ? (openBlock(), createElementBlock("li", _hoisted_4$i, [
              createBaseVNode("div", {
                id: "Menu-" + indexMenu,
                onClick: ($event) => {
                  $data.activeMenu = indexMenu;
                },
                class: normalizeClass(["tw-flex tw-items-start tw-w-full tw-p-2 tw-cursor-pointer tw-rounded-lg tw-group tw-user-select-none", $data.activeMenu === indexMenu ? "tw-font-bold tw-text-profile-full tw-bg-profile-light" : "hover:tw-bg-gray-200"])
              }, [
                createBaseVNode("span", {
                  class: normalizeClass(["material-symbols-outlined tw-font-bold tw-mr-2.5", $data.activeMenu === indexMenu ? "tw-text-profile-full" : ""]),
                  name: "icon-Menu",
                  title: _ctx.translate(menu.label),
                  id: "icon-" + indexMenu
                }, toDisplayString(menu.icon), 11, _hoisted_6$f),
                $data.minimized === false ? (openBlock(), createElementBlock("p", {
                  key: 0,
                  class: normalizeClass(["tw-font-bold tw-leading-6", $data.activeMenu === indexMenu ? "tw-text-profile-full" : ""])
                }, toDisplayString(_ctx.translate(menu.label)), 3)) : createCommentVNode("", true)
              ], 10, _hoisted_5$g)
            ])) : createCommentVNode("", true)
          ], 64);
        }), 128))
      ])
    ], 32),
    _cache[5] || (_cache[5] = createBaseVNode("div", { class: "tchoozy-corner-bottom-left-bakground-mask-image tw-h-1/3 tw-w-full tw-absolute tw-bottom-0 tw-bg-profile-full" }, null, -1))
  ], 2);
}
const SidebarMenu = /* @__PURE__ */ _export_sfc(_sfc_main$i, [["render", _sfc_render$i]]);
const _sfc_main$h = {
  name: "global",
  props: {},
  components: {
    Multiselect: script
  },
  data() {
    return {
      defaultLang: null,
      secondaryLanguages: [],
      allLanguages: [],
      otherLanguages: [],
      orphelins_count: 0,
      loading: false
    };
  },
  created() {
    this.loading = true;
    translationsService.getDefaultLanguage().then((response) => {
      this.defaultLang = response;
      this.getAllLanguages();
      this.loading = false;
    });
  },
  methods: {
    async getAllLanguages() {
      this.otherLanguages = [];
      this.secondaryLanguages = [];
      try {
        const response = await client().get("index.php?option=com_emundus&controller=translations&task=getlanguages");
        this.allLanguages = response.data;
        this.allLanguages.forEach((lang) => {
          if (lang.lang_code !== this.defaultLang.lang_code) {
            if (lang.published == 1) {
              this.secondaryLanguages.push(lang);
            }
            this.otherLanguages.push(lang);
          }
        });
        this.secondaryLanguages.forEach((sec_lang) => {
          translationsService.getOrphelins(this.defaultLang.lang_code, sec_lang.lang_code).then((orphelins) => {
            this.orphelins_count = orphelins.data.length;
            this.$emit("updateOrphelinsCount", this.orphelins_count);
          });
        });
      } catch (e) {
        return false;
      }
    },
    unpublishLanguage(option) {
      translationsService.updateLanguage(option.lang_code, 0);
    },
    publishLanguage(option) {
      translationsService.updateLanguage(option.lang_code, 1);
    },
    updateDefaultLanguage(option) {
      translationsService.updateLanguage(option.lang_code, 1, 1).then(() => {
        this.getAllLanguages();
      });
    },
    async purposeLanguage() {
      const { value: formValues } = await Swal$1.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE"),
        html: '<p class="em-body-16-semibold tw-mb-2 tw-text-end">' + this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_FIELD") + '</p><input id="language_purpose" class="em-input">',
        showCancelButton: true,
        cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
        confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SEND"),
        showLoaderOnConfirm: true,
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          cancelButton: "em-swal-cancel-button",
          confirmButton: "em-swal-confirm-button"
        },
        preConfirm: () => {
          const language = document.getElementById("language_purpose").value;
          return translationsService.sendMailToInstallLanguage(language);
        },
        allowOutsideClick: () => !Swal$1.isLoading()
      });
      if (formValues) {
        await Swal$1.fire({
          title: this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SENDED"),
          text: this.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SENDED_TEXT"),
          showCancelButton: false,
          showConfirmButton: false,
          timer: 3e3,
          customClass: {
            title: "em-swal-title"
          }
        });
      }
    }
  }
};
const _hoisted_1$h = { key: 0 };
const _hoisted_2$h = { class: "tw-mb-2" };
const _hoisted_3$h = { class: "tw-mb-6 tw-flex tw-items-center tw-justify-between" };
const _hoisted_4$h = { class: "em-body-16-semibold tw-mb-2" };
const _hoisted_5$f = { class: "tw-text-base tw-text-neutral-700" };
const _hoisted_6$e = { class: "em-w-33" };
const _hoisted_7$d = { class: "tw-mb-6 tw-flex tw-items-center tw-justify-between" };
const _hoisted_8$b = { class: "em-body-16-semibold tw-mb-2" };
const _hoisted_9$9 = { class: "tw-text-base tw-text-neutral-700" };
const _hoisted_10$8 = { class: "em-w-33 tw-text-right" };
const _hoisted_11$7 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$h(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  return $data.defaultLang ? (openBlock(), createElementBlock("div", _hoisted_1$h, [
    createBaseVNode("h2", _hoisted_2$h, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_GLOBAL")), 1),
    createBaseVNode("div", _hoisted_3$h, [
      createBaseVNode("div", null, [
        createBaseVNode("p", _hoisted_4$h, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_DEFAULT")), 1),
        createBaseVNode("p", _hoisted_5$f, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_DEFAULT_DESC")), 1)
      ]),
      createBaseVNode("div", _hoisted_6$e, [
        createVNode(_component_multiselect, {
          modelValue: $data.defaultLang,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.defaultLang = $event),
          label: "title_native",
          "track-by": "lang_code",
          options: $data.allLanguages,
          multiple: false,
          taggable: false,
          "select-label": "",
          "selected-label": "",
          "deselect-label": "",
          "close-on-select": true,
          "clear-on-select": false,
          searchable: false,
          "allow-empty": false,
          onSelect: $options.updateDefaultLanguage
        }, null, 8, ["modelValue", "options", "onSelect"])
      ])
    ]),
    createBaseVNode("div", _hoisted_7$d, [
      createBaseVNode("div", null, [
        createBaseVNode("p", _hoisted_8$b, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SECONDARY")), 1),
        createBaseVNode("p", _hoisted_9$9, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SECONDARY_DESC")), 1)
      ]),
      createBaseVNode("div", _hoisted_10$8, [
        createVNode(_component_multiselect, {
          modelValue: $data.secondaryLanguages,
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.secondaryLanguages = $event),
          label: "title_native",
          "track-by": "lang_code",
          options: $data.otherLanguages,
          multiple: true,
          taggable: false,
          "select-label": "",
          "selected-label": "",
          "deselect-label": "",
          "close-on-select": false,
          "clear-on-select": false,
          searchable: false,
          onRemove: $options.unpublishLanguage,
          onSelect: $options.publishLanguage
        }, null, 8, ["modelValue", "options", "onRemove", "onSelect"]),
        createBaseVNode("a", {
          class: "tw-cursor-pointer tw-mt-4 tw-text-base em-profile-color tw-underline",
          onClick: _cache[2] || (_cache[2] = (...args) => $options.purposeLanguage && $options.purposeLanguage(...args))
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_OTHER_LANGUAGE")), 1)
      ])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_11$7)) : createCommentVNode("", true)
  ])) : createCommentVNode("", true);
}
const Global = /* @__PURE__ */ _export_sfc(_sfc_main$h, [["render", _sfc_render$h]]);
const assetsPath$1 = "/components/com_emundus/src/assets/data/";
const getPath$1 = (path) => `${assetsPath$1}${path}`;
const _sfc_main$g = {
  name: "SiteSettings",
  components: { Global, Parameter },
  props: {
    json_source: {
      type: String,
      required: true
    },
    displayLanguage: {
      type: Boolean,
      default: false
    }
  },
  mixins: [],
  data() {
    return {
      parameters: [],
      parametersUpdated: [],
      loading: true,
      config: {}
    };
  },
  created() {
    import(getPath$1(this.$props.json_source)).then((result) => {
      if (result) {
        this.parameters = result.default;
        for (let i = 0; i < this.parameters.length; i++) {
          if (this.parameters[i].param == "offset") {
            settingsService.getTimezoneList().then((response) => {
              if (response.status) {
                this.parameters[i].multiselectOptions = {
                  options: response.data,
                  noOptions: false,
                  multiple: false,
                  taggable: false,
                  searchable: true,
                  label: "label",
                  trackBy: "value",
                  optionsPlaceholder: "",
                  selectLabel: "",
                  selectGroupLabel: "",
                  selectedLabel: "",
                  deselectedLabel: "",
                  deselectGroupLabel: "",
                  noOptionsText: "",
                  tagValidations: []
                };
              }
            });
          }
        }
      }
    });
    this.getEmundusParams();
  },
  mounted() {
  },
  methods: {
    getEmundusParams() {
      axios.get("index.php?option=com_emundus&controller=settings&task=getemundusparams").then((response) => {
        this.config = response.data;
        Object.values(this.parameters).forEach((parameter) => {
          if (parameter.type === "keywords") {
            if (this.config[parameter.component][parameter.param]) {
              let keywords = this.config[parameter.component][parameter.param].split(",");
              parameter.value = keywords.map((keyword) => {
                return {
                  name: keyword,
                  code: keyword
                };
              });
            }
          } else {
            parameter.value = this.config[parameter.component][parameter.param];
          }
          if (parameter.value === "1" || parameter.value === true || parameter.value === "true") {
            parameter.value = 1;
          }
          if (parameter.value === "0" || parameter.value === false || parameter.value === "false") {
            parameter.value = 0;
          }
        });
        this.loading = false;
      });
    },
    updateParameterToSaving(needSaving, parameter) {
      if (needSaving) {
        let checkExisting = this.parametersUpdated.find((param) => param.param === parameter.param);
        if (!checkExisting) {
          this.parametersUpdated.push(parameter);
        }
      } else {
        this.parametersUpdated = this.parametersUpdated.filter((param) => param.param !== parameter.param);
      }
    },
    displayHelp(message) {
      Swal$1.fire({
        title: this.translate("COM_EMUNDUS_SWAL_HELP_TITLE"),
        text: this.translate(message),
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
    async saveSiteSettings() {
      let params = [];
      this.parametersUpdated.forEach((param) => {
        params.push({
          component: param.component,
          param: param.param,
          value: param.value
        });
      });
      settingsService.saveParams(params).then(() => {
        this.parametersUpdated = [];
        Swal$1.fire({
          title: this.translate("COM_EMUNDUS_ONBOARD_SUCCESS"),
          text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_SUCCESS"),
          showCancelButton: false,
          showConfirmButton: false,
          customClass: {
            title: "em-swal-title"
          },
          timer: 2e3
        });
      }).catch(() => {
        Swal$1.fire({
          title: this.translate("COM_EMUNDUS_ERROR"),
          text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_ERROR"),
          showCancelButton: false,
          confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
      });
    },
    async saveMethod() {
      await this.saveSiteSettings();
      return true;
    }
  },
  computed: {
    displayedParams() {
      return this.parameters.filter((param) => param.displayed === true);
    }
  },
  watch: {
    activeSection: function(val) {
      this.$emit("sectionSelected", this.sections[val]);
    },
    parametersUpdated: {
      handler: function(val) {
        this.$emit("needSaving", val.length > 0);
      },
      deep: true
    }
  }
};
const _hoisted_1$g = { class: "em-settings-menu" };
const _hoisted_2$g = { class: "tw-w-full" };
const _hoisted_3$g = {
  key: 0,
  class: "tw-w-4/5 tw-flex tw-flex-col"
};
const _hoisted_4$g = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$g(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Parameter = resolveComponent("Parameter");
  const _component_Global = resolveComponent("Global");
  return openBlock(), createElementBlock("div", _hoisted_1$g, [
    createBaseVNode("div", _hoisted_2$g, [
      !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_3$g, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedParams, (parameter) => {
          return openBlock(), createElementBlock("div", {
            class: "form-group tw-w-full tw-mb-7",
            key: parameter.param
          }, [
            parameter.type === "multiselect" && parameter.multiselectOptions || parameter.type !== "multiselect" ? (openBlock(), createBlock(_component_Parameter, {
              key: 0,
              "parameter-object": parameter,
              "multiselect-options": parameter.multiselectOptions ? parameter.multiselectOptions : null,
              onNeedSaving: $options.updateParameterToSaving
            }, null, 8, ["parameter-object", "multiselect-options", "onNeedSaving"])) : createCommentVNode("", true)
          ]);
        }), 128)),
        $props.displayLanguage === true ? (openBlock(), createBlock(_component_Global, { key: 0 })) : createCommentVNode("", true)
      ])) : createCommentVNode("", true),
      $data.parametersUpdated.length > 0 ? (openBlock(), createElementBlock("button", {
        key: 1,
        class: "btn btn-primary tw-float-right",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.saveSiteSettings && $options.saveSiteSettings(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE")), 1)) : createCommentVNode("", true)
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_4$g)) : createCommentVNode("", true)
  ]);
}
const SiteSettings = /* @__PURE__ */ _export_sfc(_sfc_main$g, [["render", _sfc_render$g]]);
const _sfc_main$f = {
  name: "global",
  props: {},
  components: { Info },
  data() {
    return {
      RED: 0.2126,
      GREEN: 0.7152,
      BLUE: 0.0722,
      GAMMA: 2.4,
      loading: false,
      showDetails: false,
      primary: null,
      secondary: null,
      changes: false,
      rgaaState: 0,
      contrastPrimary: null,
      contrastSecondary: null
    };
  },
  async created() {
    this.loading = true;
    this.changes = false;
    await this.getAppColors();
    this.loading = false;
  },
  methods: {
    getVariable() {
      return new Promise((resolve) => {
        axios({
          method: "get",
          url: "index.php?option=com_emundus&controller=settings&task=getappVariablegantry"
        }).then(() => {
          resolve(true);
        });
      });
    },
    changeVariables(preset) {
      axios({
        method: "post",
        url: "index.php?option=com_emundus&controller=settings&task=updateVariablegantry",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        data: qs.stringify({
          preset
        })
      }).then(() => {
        console.log("jojo");
      });
    },
    getAppColors() {
      return new Promise((resolve) => {
        axios({
          method: "get",
          url: "index.php?option=com_emundus&controller=settings&task=getappcolors"
        }).then((rep) => {
          this.primary = rep.data.primary;
          this.secondary = rep.data.secondary;
          this.rgaaState = this.checkSimilarity(this.primary, this.secondary);
          this.checkContrast("#FFFFFF", this.primary).then((response) => {
            this.contrastPrimary = response;
          });
          this.checkContrast("#FFFFFF", this.secondary).then((response) => {
            this.contrastSecondary = response;
          });
          resolve(true);
        });
      });
    },
    async saveColors() {
      let preset = { id: 7, primary: this.primary, secondary: this.secondary };
      settingsService.saveColors(preset).then((response) => {
        if (response.status == 1) {
          this.changes = false;
          Swal$1.fire({
            title: this.translate("COM_EMUNDUS_ONBOARD_SUCCESS"),
            text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_THEME_SAVE_SUCCESS"),
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
              title: "em-swal-title"
            },
            timer: 2e3
          });
        }
      });
    },
    async saveMethod() {
      await this.saveColors();
      return true;
    },
    checkSimilarity(hex1, hex2) {
      let rgb1 = this.hexToRgb(hex1);
      let rgb2 = this.hexToRgb(hex2);
      const deltaECalc = this.deltaE(rgb1, rgb2);
      if (deltaECalc < 11) {
        return 0;
      } else {
        return 1;
      }
    },
    checkContrast(hex1, hex2) {
      return new Promise((resolve) => {
        fetch(
          "https://webaim.org/resources/contrastchecker/?fcolor=" + hex1.replace("#", "") + "&bcolor=" + hex2.replace("#", "") + "&api"
        ).then((response) => {
          return response.json();
        }).then((data) => {
          resolve(data);
        });
      });
    },
    /* Utilities function */
    deltaE(rgbA, rgbB) {
      let labA = this.rgb2lab(rgbA);
      let labB = this.rgb2lab(rgbB);
      let deltaL = labA[0] - labB[0];
      let deltaA = labA[1] - labB[1];
      let deltaB = labA[2] - labB[2];
      let c1 = Math.sqrt(labA[1] * labA[1] + labA[2] * labA[2]);
      let c2 = Math.sqrt(labB[1] * labB[1] + labB[2] * labB[2]);
      let deltaC = c1 - c2;
      let deltaH = deltaA * deltaA + deltaB * deltaB - deltaC * deltaC;
      deltaH = deltaH < 0 ? 0 : Math.sqrt(deltaH);
      let sc = 1 + 0.045 * c1;
      let sh = 1 + 0.015 * c1;
      let deltaLKlsl = deltaL / 1;
      let deltaCkcsc = deltaC / sc;
      let deltaHkhsh = deltaH / sh;
      let i = deltaLKlsl * deltaLKlsl + deltaCkcsc * deltaCkcsc + deltaHkhsh * deltaHkhsh;
      return i < 0 ? 0 : Math.sqrt(i);
    },
    rgb2lab(rgb) {
      let r = rgb[0] / 255, g = rgb[1] / 255, b = rgb[2] / 255, x, y, z;
      r = r > 0.04045 ? Math.pow((r + 0.055) / 1.055, 2.4) : r / 12.92;
      g = g > 0.04045 ? Math.pow((g + 0.055) / 1.055, 2.4) : g / 12.92;
      b = b > 0.04045 ? Math.pow((b + 0.055) / 1.055, 2.4) : b / 12.92;
      x = (r * 0.4124 + g * 0.3576 + b * 0.1805) / 0.95047;
      y = (r * 0.2126 + g * 0.7152 + b * 0.0722) / 1;
      z = (r * 0.0193 + g * 0.1192 + b * 0.9505) / 1.08883;
      x = x > 8856e-6 ? Math.pow(x, 1 / 3) : 7.787 * x + 16 / 116;
      y = y > 8856e-6 ? Math.pow(y, 1 / 3) : 7.787 * y + 16 / 116;
      z = z > 8856e-6 ? Math.pow(z, 1 / 3) : 7.787 * z + 16 / 116;
      return [116 * y - 16, 500 * (x - y), 200 * (y - z)];
    },
    hexToRgb(hex) {
      return hex.replace(/^#?([a-f\d])([a-f\d])([a-f\d])$/i, (m, r, g, b) => "#" + r + r + g + g + b + b).substring(1).match(/.{2}/g).map((x) => parseInt(x, 16));
    }
  },
  watch: {
    primary: function(val, oldVal) {
      if (oldVal !== null) {
        this.$emit("needSaving", true);
        this.changes = true;
        this.rgaaState = this.checkSimilarity(val, this.secondary);
        this.checkContrast("#FFFFFF", val).then((response) => {
          this.contrastPrimary = response;
        });
      }
    },
    secondary: function(val, oldVal) {
      if (oldVal !== null) {
        this.$emit("needSaving", true);
        this.changes = true;
        this.rgaaState = this.checkSimilarity(val, this.primary);
        this.checkContrast("#FFFFFF", val).then((response) => {
          this.contrastSecondary = response;
        });
      }
    }
  }
};
const _hoisted_1$f = { key: 0 };
const _hoisted_2$f = { class: "tw-flex tw-flex-row tw-gap-6" };
const _hoisted_3$f = { class: "tw-flex tw-flex-col tw-gap-3" };
const _hoisted_4$f = { class: "tw-flex tw-items-center tw-gap-3" };
const _hoisted_5$e = {
  class: "tw-font-medium tw-mb-0",
  style: { "max-width": "100px" }
};
const _hoisted_6$d = { class: "tw-flex tw-items-center tw-gap-3" };
const _hoisted_7$c = {
  class: "tw-font-medium tw-mb-0",
  style: { "max-width": "100px" }
};
const _hoisted_8$a = { class: "tw-mt-5 tw-w-full" };
const _hoisted_9$8 = { class: "tw-font-medium" };
const _hoisted_10$7 = {
  key: 0,
  class: "tw-w-full"
};
const _hoisted_11$6 = { class: "tw-mt-4" };
const _hoisted_12$4 = {
  key: 0,
  class: "tw-mt-2 tw-border-s-4 tw-border-neutral-400 tw-pl-2"
};
const _hoisted_13$3 = { class: "tw-flex tw-gap-2 tw-items-center tw-mt-1" };
const _hoisted_14$3 = {
  key: 0,
  class: "material-symbols-outlined tw-text-green-500"
};
const _hoisted_15$3 = {
  key: 1,
  class: "material-symbols-outlined tw-text-red-600"
};
const _hoisted_16$2 = { class: "tw-flex tw-gap-2 tw-items-center tw-mt-1" };
const _hoisted_17$2 = {
  key: 0,
  class: "material-symbols-outlined tw-text-green-500"
};
const _hoisted_18$2 = {
  key: 1,
  class: "material-symbols-outlined tw-text-red-600"
};
const _hoisted_19$2 = { class: "tw-mt-3" };
const _hoisted_20$2 = { class: "tw-flex tw-gap-2 tw-items-center tw-mt-1" };
const _hoisted_21$1 = {
  key: 0,
  class: "material-symbols-outlined tw-text-green-500"
};
const _hoisted_22$1 = {
  key: 1,
  class: "material-symbols-outlined tw-text-red-600"
};
const _hoisted_23$1 = { class: "tw-flex tw-gap-2 tw-items-center tw-mt-1" };
const _hoisted_24$1 = {
  key: 0,
  class: "material-symbols-outlined tw-text-green-500"
};
const _hoisted_25$1 = {
  key: 1,
  class: "material-symbols-outlined tw-text-red-600"
};
const _hoisted_26$1 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render$f(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Info = resolveComponent("Info");
  return $data.primary && $data.secondary ? (openBlock(), createElementBlock("div", _hoisted_1$f, [
    createBaseVNode("div", _hoisted_2$f, [
      createBaseVNode("div", _hoisted_3$f, [
        createBaseVNode("div", _hoisted_4$f, [
          createBaseVNode("div", null, [
            withDirectives(createBaseVNode("input", {
              type: "color",
              class: "custom-color-picker !tw-rounded-full",
              "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.primary = $event),
              id: "primary_color"
            }, null, 512), [
              [vModelText, $data.primary]
            ])
          ]),
          createBaseVNode("label", _hoisted_5$e, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PRIMARY_COLOR")), 1)
        ]),
        createBaseVNode("div", _hoisted_6$d, [
          createBaseVNode("div", null, [
            withDirectives(createBaseVNode("input", {
              type: "color",
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.secondary = $event),
              class: "custom-color-picker !tw-rounded-full",
              id: "secondary_color"
            }, null, 512), [
              [vModelText, $data.secondary]
            ])
          ]),
          createBaseVNode("label", _hoisted_7$c, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SECONDARY_COLOR")), 1)
        ])
      ])
    ]),
    createBaseVNode("div", _hoisted_8$a, [
      createBaseVNode("h3", _hoisted_9$8, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_THEME_ACCESSIBILITY")), 1),
      $data.contrastPrimary && $data.contrastSecondary ? (openBlock(), createElementBlock("div", _hoisted_10$7, [
        $data.contrastPrimary.ratio > 4.5 && $data.contrastSecondary.ratio > 4.5 ? (openBlock(), createBlock(_component_Info, {
          key: 0,
          text: "COM_EMUNDUS_ONBOARD_RGAA_OK",
          "bg-color": "tw-bg-main-50",
          icon: "check_circle",
          "icon-type": "material-icons",
          "icon-color": "tw-text-green-500",
          class: normalizeClass("tw-mt-2")
        })) : createCommentVNode("", true),
        $data.contrastPrimary.ratio < 4.5 ? (openBlock(), createBlock(_component_Info, {
          key: 1,
          text: "COM_EMUNDUS_SETTINGS_CONTRAST_ERROR_PRIMARY",
          icon: "warning",
          "bg-color": "tw-bg-orange-100",
          "icon-type": "material-icons",
          "icon-color": "tw-text-orange-600",
          class: normalizeClass("tw-mt-2")
        })) : createCommentVNode("", true),
        $data.contrastSecondary.ratio < 4.5 ? (openBlock(), createBlock(_component_Info, {
          key: 2,
          text: "COM_EMUNDUS_SETTINGS_CONTRAST_ERROR_SECONDARY",
          icon: "warning",
          "bg-color": "tw-bg-orange-100",
          "icon-type": "material-icons",
          "icon-color": "tw-text-orange-600",
          class: normalizeClass("tw-mt-2")
        })) : createCommentVNode("", true),
        $data.rgaaState === 0 ? (openBlock(), createBlock(_component_Info, {
          key: 3,
          text: "COM_EMUNDUS_ONBOARD_ERROR_COLORS_SAME",
          icon: "warning",
          "bg-color": "tw-bg-orange-100",
          "icon-type": "material-icons",
          "icon-color": "tw-text-orange-600",
          class: normalizeClass("tw-mt-2")
        })) : createCommentVNode("", true),
        createBaseVNode("div", _hoisted_11$6, [
          createBaseVNode("h4", {
            onClick: _cache[2] || (_cache[2] = ($event) => $data.showDetails = !$data.showDetails),
            class: "tw-flex tw-items-center tw-font-semibold tw-cursor-pointer"
          }, [
            createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS")) + " ", 1),
            createBaseVNode("span", {
              class: normalizeClass(["material-symbols-outlined tw-font-sm", $data.showDetails ? "tw-rotate-90" : ""])
            }, "navigate_next", 2)
          ]),
          $data.showDetails ? (openBlock(), createElementBlock("div", _hoisted_12$4, [
            createBaseVNode("div", null, [
              createBaseVNode("h5", null, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_NORMAL_TEXT")), 1),
              createBaseVNode("div", _hoisted_13$3, [
                $data.contrastPrimary.AA === "pass" ? (openBlock(), createElementBlock("span", _hoisted_14$3, "check_circle")) : createCommentVNode("", true),
                $data.contrastPrimary.AA === "fail" ? (openBlock(), createElementBlock("span", _hoisted_15$3, "highlight_off")) : createCommentVNode("", true),
                createBaseVNode("button", {
                  class: "tw-rounded-coordinator tw-px-3 tw-py-2 tw-text-white",
                  type: "button",
                  style: normalizeStyle({ backgroundColor: $data.primary, borderColor: $data.primary })
                }, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LOGIN_TEXT")), 5)
              ]),
              createBaseVNode("div", _hoisted_16$2, [
                $data.contrastSecondary.AA === "pass" ? (openBlock(), createElementBlock("span", _hoisted_17$2, "check_circle")) : createCommentVNode("", true),
                $data.contrastSecondary.AA === "fail" ? (openBlock(), createElementBlock("span", _hoisted_18$2, "highlight_off")) : createCommentVNode("", true),
                createBaseVNode("button", {
                  class: "tw-btn-secondary tw-text-white",
                  type: "button",
                  style: normalizeStyle({ backgroundColor: $data.secondary, borderColor: $data.secondary })
                }, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LOGIN_TEXT")), 5)
              ])
            ]),
            createBaseVNode("div", _hoisted_19$2, [
              createBaseVNode("h5", null, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LARGE_TEXT")), 1),
              createBaseVNode("div", _hoisted_20$2, [
                $data.contrastPrimary.AALarge === "pass" ? (openBlock(), createElementBlock("span", _hoisted_21$1, "check_circle")) : createCommentVNode("", true),
                $data.contrastPrimary.AALarge === "fail" ? (openBlock(), createElementBlock("span", _hoisted_22$1, "highlight_off")) : createCommentVNode("", true),
                createBaseVNode("button", {
                  class: "tw-rounded-coordinator tw-px-3 tw-py-2 tw-text-white tw-font-bold",
                  style: normalizeStyle({ backgroundColor: $data.primary, borderColor: $data.primary }),
                  type: "button"
                }, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LOGIN_TEXT")), 5)
              ]),
              createBaseVNode("div", _hoisted_23$1, [
                $data.contrastSecondary.AALarge === "pass" ? (openBlock(), createElementBlock("span", _hoisted_24$1, "check_circle")) : createCommentVNode("", true),
                $data.contrastSecondary.AALarge === "fail" ? (openBlock(), createElementBlock("span", _hoisted_25$1, "highlight_off")) : createCommentVNode("", true),
                createBaseVNode("button", {
                  class: "tw-btn-secondary tw-font-bold tw-text-white",
                  style: normalizeStyle({ backgroundColor: $data.secondary, borderColor: $data.secondary }),
                  type: "button"
                }, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_ACCESSIBILITY_DETAILS_LOGIN_TEXT")), 5)
              ])
            ])
          ])) : createCommentVNode("", true)
        ])
      ])) : createCommentVNode("", true)
    ]),
    $data.changes ? (openBlock(), createElementBlock("button", {
      key: 0,
      class: "tw-mt-3 btn btn-primary tw-float-right",
      onClick: _cache[3] || (_cache[3] = (...args) => $options.saveColors && $options.saveColors(...args))
    }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE")), 1)) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_26$1)) : createCommentVNode("", true)
  ])) : createCommentVNode("", true);
}
const EditTheme = /* @__PURE__ */ _export_sfc(_sfc_main$f, [["render", _sfc_render$f], ["__scopeId", "data-v-179e7d97"]]);
const _sfc_main$e = {
  name: "editStatus",
  components: {
    ColorPicker,
    draggable: VueDraggableNext
  },
  props: {},
  mixins: [mixin, errors],
  data() {
    return {
      index: "",
      indexGrab: "0",
      grab: 0,
      loading: false,
      status: [],
      show: false,
      actualLanguage: "",
      colors: [],
      variables: null
    };
  },
  setup() {
    return {
      globalStore: useGlobalStore()
    };
  },
  created() {
    let root = document.querySelector(":root");
    this.variables = getComputedStyle(root);
    for (const swatch of basicPreset) {
      let color = this.variables.getPropertyValue("--em-" + swatch);
      this.colors.push({ name: swatch, value: color });
    }
    this.getStatus();
    this.actualLanguage = this.globalStore.shortLang;
  },
  methods: {
    getStatus() {
      settingsService.getStatus().then((response) => {
        if (response.status) {
          this.status = response.data;
          setTimeout(() => {
            this.status.forEach((element) => {
              this.getHexColors(element);
            });
          }, 100);
        } else {
          this.displayError(response.msg);
        }
      });
    },
    async updateStatus(status) {
      const newLabel = document.getElementById("status_label_" + status.step).textContent;
      if (newLabel.length > 0) {
        this.$emit("updateSaving", true);
        let index = this.colors.findIndex((item) => item.value === status.class);
        const formData = new FormData();
        formData.append("status", status.step);
        formData.append("label", newLabel);
        formData.append("color", this.colors[index].name);
        await client().post(
          "index.php?option=com_emundus&controller=settings&task=updatestatus",
          formData,
          {
            headers: {
              "Content-Type": "multipart/form-data"
            }
          }
        ).then((response) => {
          this.$emit("updateSaving", false);
          if (response.status) {
            status.label[this.actualLanguage] = newLabel;
            this.$emit("updateLastSaving", this.formattedDate("", "LT"));
          } else {
            this.displayError("COM_EMUNDUS_SETTINGS_FAILED_TO_UPDATE_STATUS", response.msg);
          }
        });
      } else {
        document.getElementById("status_label_" + status.step).textContent = status.label[this.actualLanguage];
        this.displayError("COM_EMUNDUS_SETTINGS_FAILED_TO_UPDATE_STATUS", "COM_EMUNDUS_SETTINGS_FORBIDDEN_EMPTY_STATUS");
      }
    },
    async updateStatusOrder() {
      let status_steps = [];
      this.status.forEach((statu) => {
        status_steps.push(statu.step);
      });
      this.$emit("updateSaving", true);
      const formData = new FormData();
      formData.append("status", status_steps.join(","));
      await client().post(
        "index.php?option=com_emundus&controller=settings&task=updatestatusorder",
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        }
      ).then(() => {
        this.$emit("updateSaving", false);
        this.$emit("updateLastSaving", this.formattedDate("", "LT"));
      });
    },
    pushStatus() {
      this.$emit("updateSaving", true);
      axios({
        method: "post",
        url: "index.php?option=com_emundus&controller=settings&task=createstatus",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        }
      }).then((newstatus) => {
        this.status.push(newstatus.data);
        setTimeout(() => {
          this.getHexColors(newstatus.data);
        }, 100);
        this.$emit("updateSaving", false);
        this.$emit("updateLastSaving", this.formattedDate("", "LT"));
      });
    },
    removeStatus(status, index) {
      if (status.edit == 1 && status.step != 0 && status.step != 1) {
        this.$emit("updateSaving", true);
        axios({
          method: "post",
          url: "index.php?option=com_emundus&controller=settings&task=deletestatus",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          data: {
            id: status.id,
            step: status.step
          }
        }).then(() => {
          this.status.splice(index, 1);
          this.$emit("updateSaving", false);
          this.$emit("updateLastSaving", this.formattedDate("", "LT"));
        });
      }
    },
    manageKeyup(status) {
      document.getElementById("status_label_" + status.step).textContent = document.getElementById("status_label_" + status.step).textContent.trim();
      document.activeElement.blur();
    },
    getHexColors(element) {
      element.translate = false;
      element.class = this.variables.getPropertyValue("--em-" + element.class);
    },
    checkMaxlength(event) {
      if (event.target.textContent.length === 50 && event.keyCode != 8) {
        event.preventDefault();
      }
    },
    enableGrab(index) {
      if (this.status.length !== 1) {
        this.indexGrab = index;
        this.grab = true;
      }
    },
    disableGrab() {
      this.indexGrab = 0;
      this.grab = false;
    }
  }
};
const _hoisted_1$e = { class: "tw-flex tw-flex-wrap tw-justify-start" };
const _hoisted_2$e = { class: "tw-w-10/12" };
const _hoisted_3$e = { class: "tw-grid tw-grid-cols-3 tw-mb-4" };
const _hoisted_4$e = { class: "add-button-div em-flex-row" };
const _hoisted_5$d = ["title", "id", "onMouseover"];
const _hoisted_6$c = { class: "tw-flex tw-items-center tw-justify-start tw-w-full" };
const _hoisted_7$b = { class: "status-field" };
const _hoisted_8$9 = ["id", "onFocusout", "onKeyup"];
const _hoisted_9$7 = { class: "tw-flex tw-items-center" };
const _hoisted_10$6 = ["title", "onClick"];
const _hoisted_11$5 = ["title"];
const _hoisted_12$3 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$e(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_color_picker = resolveComponent("color-picker");
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$e, [
    createBaseVNode("div", _hoisted_2$e, [
      createBaseVNode("div", _hoisted_3$e, [
        createBaseVNode("button", {
          onClick: _cache[0] || (_cache[0] = (...args) => $options.pushStatus && $options.pushStatus(...args)),
          class: "tw-btn-primary tw-mb-6 tw-w-max"
        }, [
          createBaseVNode("div", _hoisted_4$e, [
            _cache[4] || (_cache[4] = createBaseVNode("span", { class: "material-symbols-outlined em-mr-4" }, "add", -1)),
            createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_STATUS")), 1)
          ])
        ])
      ]),
      createVNode(_component_draggable, {
        handle: ".handle",
        modelValue: $data.status,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.status = $event),
        class: normalizeClass("draggables-list"),
        onEnd: $options.updateStatusOrder
      }, {
        default: withCtx(() => [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.status, (statu, index) => {
            return openBlock(), createElementBlock("div", {
              class: "tw-mb-6",
              title: "step_" + statu.step,
              key: statu.step,
              id: "step_" + statu.step,
              onMouseover: ($event) => $options.enableGrab(index),
              onMouseleave: _cache[2] || (_cache[2] = ($event) => $options.disableGrab())
            }, [
              createBaseVNode("div", _hoisted_6$c, [
                createBaseVNode("span", {
                  class: "handle tw-cursor-grab",
                  style: normalizeStyle($data.grab && $data.indexGrab == index ? "opacity: 1" : "opacity: 0")
                }, _cache[5] || (_cache[5] = [
                  createBaseVNode("span", { class: "material-symbols-outlined" }, "drag_indicator", -1)
                ]), 4),
                createBaseVNode("div", _hoisted_7$b, [
                  createBaseVNode("div", null, [
                    createBaseVNode("p", {
                      class: "tw-px-2 tw-py-3 em-editable-content",
                      contenteditable: "true",
                      id: "status_label_" + statu.step,
                      onFocusout: ($event) => $options.updateStatus(statu),
                      onKeyup: withKeys(($event) => $options.manageKeyup(statu), ["enter"]),
                      onKeydown: _cache[1] || (_cache[1] = (...args) => $options.checkMaxlength && $options.checkMaxlength(...args))
                    }, toDisplayString(statu.label[$data.actualLanguage]), 41, _hoisted_8$9)
                  ]),
                  createBaseVNode("input", {
                    type: "hidden",
                    class: normalizeClass("label-" + statu.class)
                  }, null, 2)
                ]),
                createBaseVNode("div", _hoisted_9$7, [
                  createVNode(_component_color_picker, {
                    modelValue: statu.class,
                    "onUpdate:modelValue": ($event) => statu.class = $event,
                    onInput: ($event) => $options.updateStatus(statu),
                    "row-length": 8,
                    id: "status_swatches_" + statu.step
                  }, null, 8, ["modelValue", "onUpdate:modelValue", "onInput", "id"]),
                  statu.edit == 1 && statu.step != 0 && statu.step != 1 ? (openBlock(), createElementBlock("a", {
                    key: 0,
                    type: "button",
                    title: _ctx.translate("COM_EMUNDUS_ONBOARD_DELETE_STATUS"),
                    onClick: ($event) => $options.removeStatus(statu, index),
                    class: "tw-flex tw-items-center tw-ml-2 tw-cursor-pointer"
                  }, _cache[6] || (_cache[6] = [
                    createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "delete_outline", -1)
                  ]), 8, _hoisted_10$6)) : (openBlock(), createElementBlock("a", {
                    key: 1,
                    type: "button",
                    title: _ctx.translate("COM_EMUNDUS_ONBOARD_CANNOT_DELETE_STATUS"),
                    class: "tw-flex tw-items-center tw-ml-2 tw-cursor-pointer"
                  }, _cache[7] || (_cache[7] = [
                    createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-600" }, "delete_outline", -1)
                  ]), 8, _hoisted_11$5))
                ])
              ]),
              _cache[8] || (_cache[8] = createBaseVNode("hr", null, null, -1))
            ], 40, _hoisted_5$d);
          }), 128))
        ]),
        _: 1
      }, 8, ["modelValue", "onEnd"])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_12$3)) : createCommentVNode("", true)
  ]);
}
const EditStatus = /* @__PURE__ */ _export_sfc(_sfc_main$e, [["render", _sfc_render$e], ["__scopeId", "data-v-78283c16"]]);
const _sfc_main$d = {
  name: "editTags",
  components: {
    ColorPicker,
    draggable: VueDraggableNext
  },
  props: {},
  mixins: [mixin, errors],
  data() {
    return {
      index: "",
      indexGrab: "0",
      grab: 0,
      loading: false,
      tags: [],
      show: false,
      actualLanguage: "",
      colors: [],
      variables: null
    };
  },
  setup() {
    return {
      globalStore: useGlobalStore()
    };
  },
  created() {
    let root = document.querySelector(":root");
    this.variables = getComputedStyle(root);
    for (const swatch of basicPreset) {
      let color = this.variables.getPropertyValue("--em-" + swatch);
      this.colors.push({ name: swatch, value: color });
    }
    this.getTags();
    this.actualLanguage = this.globalStore.shortLang;
  },
  methods: {
    getTags() {
      settingsService.getTags().then((response) => {
        if (response.status) {
          this.tags = response.data;
          setTimeout(() => {
            this.tags.forEach((element) => {
              this.getHexColors(element);
            });
          }, 100);
        } else {
          this.displayError(response.msg, "");
        }
      });
    },
    async updateTag(tag) {
      const newLabel = document.getElementById("tag_label_" + tag.id).textContent;
      if (newLabel.length > 0) {
        this.$emit("updateSaving", true);
        let index = this.colors.findIndex((item) => item.value === tag.class);
        const formData = new FormData();
        formData.append("tag", tag.id);
        formData.append("label", newLabel);
        formData.append("color", this.colors[index].name);
        await client().post(
          "index.php?option=com_emundus&controller=settings&task=updatetags",
          formData,
          {
            headers: {
              "Content-Type": "multipart/form-data"
            }
          }
        ).then((response) => {
          this.$emit("updateSaving", false);
          if (response.status) {
            tag.label = newLabel;
            this.$emit("updateLastSaving", this.formattedDate("", "LT"));
          } else {
            this.displayError("COM_EMUNDUS_SETTINGS_FAILED_TO_UPDATE_TAG", response.msg);
          }
        });
      } else {
        document.getElementById("tag_label_" + tag.id).textContent = tag.label;
        this.displayError("COM_EMUNDUS_SETTINGS_FAILED_TO_UPDATE_TAG", "COM_EMUNDUS_SETTINGS_FORBIDDEN_EMPTY_TAG");
      }
    },
    async updateTagOrdering() {
      let orderedTags = [];
      this.tags.forEach((tag) => {
        orderedTags.push(tag.id);
      });
      this.$emit("updateSaving", true);
      settingsService.updateTagOrdering(orderedTags).then(() => {
        this.$emit("updateSaving", false);
        this.$emit("updateLastSaving", this.formattedDate("", "LT"));
      });
    },
    pushTag() {
      this.$emit("updateSaving", true);
      axios({
        method: "post",
        url: "index.php?option=com_emundus&controller=settings&task=createtag",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        }
      }).then((newtag) => {
        this.tags.push(newtag.data);
        setTimeout(() => {
          this.getHexColors(newtag.data);
        }, 100);
        this.$emit("updateSaving", false);
        this.$emit("updateLastSaving", this.formattedDate("", "LT"));
      });
    },
    removeTag(tag, index) {
      this.$emit("updateSaving", true);
      axios({
        method: "post",
        url: "index.php?option=com_emundus&controller=settings&task=deletetag",
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        data: {
          id: tag.id
        }
      }).then(() => {
        this.tags.splice(index, 1);
        this.$emit("updateSaving", false);
        this.$emit("updateLastSaving", this.formattedDate("", "LT"));
      });
    },
    manageKeyup(tag) {
      document.getElementById("tag_label_" + tag.id).textContent = document.getElementById("tag_label_" + tag.id).textContent.trim();
      document.activeElement.blur();
    },
    getHexColors(element) {
      element.translate = false;
      element.class = this.variables.getPropertyValue("--em-" + element.class.replace("label-", ""));
    },
    checkMaxlength(event) {
      if (event.target.textContent.length === 50 && event.keyCode != 8) {
        event.preventDefault();
      }
    },
    enableGrab(index) {
      if (this.tags.length !== 1) {
        this.indexGrab = index;
        this.grab = true;
      }
    },
    disableGrab() {
      this.indexGrab = 0;
      this.grab = false;
    }
  }
};
const _hoisted_1$d = { class: "tw-flex tw-flex-wrap tw-justify-start" };
const _hoisted_2$d = { class: "tw-w-10/12" };
const _hoisted_3$d = { class: "tw-grid tw-grid-cols-3 tw-mb-4" };
const _hoisted_4$d = { class: "add-button-div em-flex-row" };
const _hoisted_5$c = ["id", "onMouseover"];
const _hoisted_6$b = { class: "tw-flex tw-items-center tw-justify-start tw-w-full" };
const _hoisted_7$a = { class: "status-field" };
const _hoisted_8$8 = { style: { "width": "100%" } };
const _hoisted_9$6 = ["id", "onFocusout", "onKeyup"];
const _hoisted_10$5 = { class: "tw-flex tw-items-center" };
const _hoisted_11$4 = ["title", "onClick"];
function _sfc_render$d(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_color_picker = resolveComponent("color-picker");
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$d, [
    createBaseVNode("div", _hoisted_2$d, [
      createBaseVNode("div", _hoisted_3$d, [
        createBaseVNode("button", {
          onClick: _cache[0] || (_cache[0] = (...args) => $options.pushTag && $options.pushTag(...args)),
          class: "tw-btn-primary tw-mb-6 tw-w-max"
        }, [
          createBaseVNode("div", _hoisted_4$d, [
            _cache[4] || (_cache[4] = createBaseVNode("span", { class: "material-icons-outlined em-mr-4" }, "add", -1)),
            createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SETTINGS_ADDTAG")), 1)
          ])
        ])
      ]),
      createVNode(_component_draggable, {
        handle: ".handle",
        modelValue: $data.tags,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.tags = $event),
        class: normalizeClass("draggables-list"),
        onEnd: $options.updateTagOrdering
      }, {
        default: withCtx(() => [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.tags, (tag, index) => {
            return openBlock(), createElementBlock("div", {
              class: "tw-mb-6",
              id: "tag_" + tag.id,
              key: "tag_" + tag.id,
              onMouseover: ($event) => $options.enableGrab(index),
              onMouseleave: _cache[2] || (_cache[2] = ($event) => $options.disableGrab())
            }, [
              createBaseVNode("div", _hoisted_6$b, [
                createBaseVNode("span", {
                  class: "handle tw-cursor-grab",
                  style: normalizeStyle($data.grab && $data.indexGrab === index ? "opacity: 1" : "opacity: 0")
                }, _cache[5] || (_cache[5] = [
                  createBaseVNode("span", { class: "material-symbols-outlined" }, "drag_indicator", -1)
                ]), 4),
                createBaseVNode("div", _hoisted_7$a, [
                  createBaseVNode("div", _hoisted_8$8, [
                    createBaseVNode("p", {
                      class: "tw-px-2 tw-py-3 em-editable-content",
                      contenteditable: "true",
                      id: "tag_label_" + tag.id,
                      onFocusout: ($event) => $options.updateTag(tag),
                      onKeyup: withKeys(($event) => $options.manageKeyup(tag), ["enter"]),
                      onKeydown: _cache[1] || (_cache[1] = (...args) => $options.checkMaxlength && $options.checkMaxlength(...args))
                    }, toDisplayString(tag.label), 41, _hoisted_9$6)
                  ]),
                  createBaseVNode("input", {
                    type: "hidden",
                    class: normalizeClass(tag.class)
                  }, null, 2)
                ]),
                createBaseVNode("div", _hoisted_10$5, [
                  createVNode(_component_color_picker, {
                    modelValue: tag.class,
                    "onUpdate:modelValue": ($event) => tag.class = $event,
                    onInput: ($event) => $options.updateTag(tag),
                    "row-length": 8,
                    id: "tag_swatches_" + tag.id
                  }, null, 8, ["modelValue", "onUpdate:modelValue", "onInput", "id"]),
                  createBaseVNode("a", {
                    type: "button",
                    title: _ctx.translate("COM_EMUNDUS_ONBOARD_DELETE_TAGS"),
                    onClick: ($event) => $options.removeTag(tag, index),
                    class: "tw-flex tw-items-center tw-ml-2 tw-cursor-pointer"
                  }, _cache[6] || (_cache[6] = [
                    createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "delete_outline", -1)
                  ]), 8, _hoisted_11$4)
                ])
              ]),
              _cache[7] || (_cache[7] = createBaseVNode("hr", null, null, -1))
            ], 40, _hoisted_5$c);
          }), 128))
        ]),
        _: 1
      }, 8, ["modelValue", "onEnd"])
    ])
  ]);
}
const EditTags = /* @__PURE__ */ _export_sfc(_sfc_main$d, [["render", _sfc_render$d], ["__scopeId", "data-v-33ad2ead"]]);
const getTemplate = () => `
<div class="dz-preview dz-file-preview">
  <div class="dz-image">
    <div data-dz-thumbnail-bg></div>
  </div>
  <div class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></div>
  <div class="dz-error-message"><span data-dz-errormessage></span></div>
  <div class="dz-error-mark"><i class="fa fa-close"></i></div>
</div>
`;
const _sfc_main$c = {
  name: "global",
  props: {},
  components: {
    vueDropzone
  },
  data() {
    return {
      loading: false,
      logo_updating: false,
      favicon_updating: false,
      banner_updating: false,
      imageLink: null,
      iconLink: null,
      bannerLink: null,
      changes: false,
      hideIcon: false,
      hideLogo: false,
      InsertLogo: this.translate("COM_EMUNDUS_ONBOARD_INSERT_LOGO"),
      InsertIcon: this.translate("COM_EMUNDUS_ONBOARD_INSERT_ICON"),
      InsertBanner: this.translate("COM_EMUNDUS_ONBOARD_INSERT_BANNER"),
      logoDropzoneOptions: {
        url: "index.php?option=com_emundus&controller=settings&task=updatelogo",
        maxFilesize: 10,
        maxFiles: 1,
        autoProcessQueue: true,
        addRemoveLinks: true,
        thumbnailWidth: null,
        thumbnailHeight: null,
        resizeMimeType: "image/png",
        acceptedFiles: "image/*",
        previewTemplate: getTemplate(),
        dictCancelUpload: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD"),
        dictCancelUploadConfirmation: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD_CONFIRMATION"),
        dictRemoveFile: this.translate("COM_EMUNDUS_ONBOARD_REMOVE_FILE"),
        dictInvalidFileType: this.translate("COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE"),
        dictFileTooBig: this.translate("COM_EMUNDUS_ONBOARD_FILE_TOO_BIG") + " : 10Mo",
        dictMaxFilesExceeded: this.translate("COM_EMUNDUS_ONBOARD_MAX_FILES_EXCEEDED")
      },
      faviconDropzoneOptions: {
        url: "index.php?option=com_emundus&controller=settings&task=updateicon",
        maxFilesize: 10,
        maxFiles: 1,
        autoProcessQueue: true,
        addRemoveLinks: true,
        thumbnailWidth: null,
        thumbnailHeight: null,
        acceptedFiles: "image/png,image/jpeg,,image/x-icon,image/vnd.microsoft.icon",
        previewTemplate: getTemplate(),
        dictCancelUpload: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD"),
        dictCancelUploadConfirmation: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD_CONFIRMATION"),
        dictRemoveFile: this.translate("COM_EMUNDUS_ONBOARD_REMOVE_FILE"),
        dictInvalidFileType: this.translate("COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE"),
        dictFileTooBig: this.translate("COM_EMUNDUS_ONBOARD_FILE_TOO_BIG") + " : 10Mo",
        dictMaxFilesExceeded: this.translate("COM_EMUNDUS_ONBOARD_MAX_FILES_EXCEEDED")
      },
      bannerDropzoneOptions: {
        url: "index.php?option=com_emundus&controller=settings&task=updatebanner",
        maxFilesize: 10,
        maxFiles: 1,
        autoProcessQueue: true,
        addRemoveLinks: true,
        thumbnailWidth: null,
        thumbnailHeight: null,
        resizeMimeType: "image/png",
        acceptedFiles: "image/png,image/jpeg",
        previewTemplate: getTemplate(),
        dictCancelUpload: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD"),
        dictCancelUploadConfirmation: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPLOAD_CONFIRMATION"),
        dictRemoveFile: this.translate("COM_EMUNDUS_ONBOARD_REMOVE_FILE"),
        dictInvalidFileType: this.translate("COM_EMUNDUS_ONBOARD_INVALID_FILE_TYPE"),
        dictFileTooBig: this.translate("COM_EMUNDUS_ONBOARD_FILE_TOO_BIG") + " : 10Mo",
        dictMaxFilesExceeded: this.translate("COM_EMUNDUS_ONBOARD_MAX_FILES_EXCEEDED")
      }
    };
  },
  async created() {
    this.loading = true;
    this.changes = false;
    await this.getLogo();
    await this.getFavicon();
    await this.getBanner();
    this.changes = true;
    this.loading = false;
  },
  methods: {
    getLogo() {
      return new Promise((resolve) => {
        settingsService.getLogo().then((response) => {
          if (response.filename == null) {
            this.imageLink = "images/custom/logo.png";
          } else {
            this.imageLink = "images/custom/" + response.filename + "?" + (/* @__PURE__ */ new Date()).getTime();
          }
          resolve(true);
        });
      });
    },
    getFavicon() {
      return new Promise((resolve) => {
        axios({
          method: "get",
          url: "index.php?option=com_emundus&controller=settings&task=getfavicon"
        }).then((rep) => {
          if (rep.data.filename == null) {
            this.iconLink = "images/custom/favicon.png";
          } else {
            this.iconLink = rep.data.filename + "?" + (/* @__PURE__ */ new Date()).getTime();
          }
          resolve(true);
        });
      });
    },
    getBanner() {
      return new Promise((resolve) => {
        axios({
          method: "get",
          url: "index.php?option=com_emundus&controller=settings&task=getbanner"
        }).then((rep) => {
          if (rep.data.filename != null) {
            this.bannerLink = rep.data.filename;
          }
          resolve(true);
        });
      });
    },
    updateView(response) {
      this.hideLogo = false;
      this.imageLink = "images/custom/" + response.filename + "?" + (/* @__PURE__ */ new Date()).getTime();
      const oldLogo = document.querySelector('img[src="/images/custom/' + response.old_logo + '"]');
      if (oldLogo) {
        oldLogo.src = "/" + this.imageLink;
      }
      this.$forceUpdate();
    },
    updateIcon(response) {
      this.hideIcon = false;
      this.iconLink = window.location.origin + "//images/custom/" + response.filename + "?" + (/* @__PURE__ */ new Date()).getTime();
      document.querySelector('link[type="image/x-icon"]').href = window.location.origin + "//images/custom/" + response.filename + "?" + (/* @__PURE__ */ new Date()).getTime();
      document.querySelector(".tchooz-vertical-logo a img").src = window.location.origin + "//images/custom/" + response.filename + "?" + (/* @__PURE__ */ new Date()).getTime();
      this.$forceUpdate();
    },
    updateBanner(ext = "png") {
      this.bannerLink = "images/custom/default_banner." + ext + "?" + (/* @__PURE__ */ new Date()).getTime();
      this.$forceUpdate();
    },
    afterAdded() {
      document.getElementById("dropzone-message").style.display = "none";
    },
    afterRemoved() {
      if (this.$refs.dropzone.getAcceptedFiles().length === 0) {
        if (this.banner_updating || this.logo_updating || this.favicon_updating) {
          document.getElementById("dropzone-message").style.display = "block";
        }
      }
    },
    onComplete: function(response) {
      const ext = response.name.split(".").pop();
      if (response.status === "success") {
        if (this.logo_updating) {
          this.logo_updating = false;
          this.updateView(JSON.parse(response.xhr.response));
        }
        if (this.favicon_updating) {
          this.favicon_updating = false;
          this.updateIcon(JSON.parse(response.xhr.response));
        }
        if (this.banner_updating) {
          this.banner_updating = false;
          this.updateBanner(ext);
        }
        if (this.banner_updating) {
          this.banner_updating = false;
          this.updateBanner();
        }
      }
    },
    catchError: function(file, message) {
      Swal$1.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_ERROR"),
        text: message,
        type: "error",
        showCancelButton: false,
        showConfirmButton: false,
        timer: 3e3
      });
      this.$refs.dropzone.removeFile(file);
    },
    thumbnail: function(file, dataUrl) {
      let j, len, ref, thumbnailElement;
      if (file.previewElement) {
        file.previewElement.classList.remove("dz-file-preview");
        ref = file.previewElement.querySelectorAll("[data-dz-thumbnail-bg]");
        for (j = 0, len = ref.length; j < len; j++) {
          thumbnailElement = ref[j];
          thumbnailElement.alt = file.name;
          thumbnailElement.style.backgroundImage = 'url("' + dataUrl + '")';
        }
        return setTimeout(/* @__PURE__ */ function(_this) {
          return function() {
            return file.previewElement.classList.add("dz-image-preview");
          };
        }(), 1);
      }
    },
    uploadNewLogo() {
      this.$refs.dropzone.processQueue();
    },
    displayFaviconTip() {
      Swal$1.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_ICON"),
        text: this.translate("COM_EMUNDUS_ONBOARD_ICON_TIP_TEXT"),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action"
        }
      }).then((result) => {
      });
    },
    displayLogoTip() {
      Swal$1.fire({
        title: "Logo",
        text: this.translate("COM_EMUNDUS_ONBOARD_LOGO_TIP_TEXT"),
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
    displayBannerTip() {
      Swal$1.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_BANNER"),
        text: this.translate("COM_EMUNDUS_ONBOARD_BANNER_TIP_TEXT"),
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
    displayColorsTip() {
      Swal$1.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_COLORS"),
        text: this.translate("COM_EMUNDUS_FORM_BUILDER_COLORS_RECOMMENDED"),
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
    openFileInput() {
      setTimeout(() => {
        document.getElementsByClassName("dz-clickable")[0].click();
      }, 300);
    }
  },
  watch: {
    logo_updating: function(value) {
      if (value) {
        this.favicon_updating = false;
        this.banner_updating = false;
        this.openFileInput();
      }
    },
    favicon_updating: function(value) {
      if (value) {
        this.logo_updating = false;
        this.banner_updating = false;
        this.openFileInput();
      }
    },
    banner_updating: function(value) {
      if (value) {
        this.favicon_updating = false;
        this.logo_updating = false;
        this.openFileInput();
      }
    }
  }
};
const _hoisted_1$c = {
  key: 0,
  class: "em-grid-2"
};
const _hoisted_2$c = { class: "em-style-options tw-mb-8" };
const _hoisted_3$c = { class: "tw-flex tw-items-center" };
const _hoisted_4$c = { class: "em-text-neutral-800 tw-flex tw-items-center tw-mb-2" };
const _hoisted_5$b = { class: "tw-text-neutral-700" };
const _hoisted_6$a = { class: "tw-text-neutral-700" };
const _hoisted_7$9 = {
  key: 0,
  class: "em-logo-box pointer tw-mt-4"
};
const _hoisted_8$7 = ["src", "srcset"];
const _hoisted_9$5 = { key: 1 };
const _hoisted_10$4 = {
  key: 1,
  class: "tw-mt-4"
};
const _hoisted_11$3 = {
  class: "dropzone-custom-content",
  id: "dropzone-message"
};
const _hoisted_12$2 = { key: 0 };
const _hoisted_13$2 = { key: 1 };
const _hoisted_14$2 = { class: "em-style-options tw-mb-8" };
const _hoisted_15$2 = { class: "tw-flex tw-items-center" };
const _hoisted_16$1 = { class: "em-text-neutral-800 tw-flex tw-items-center tw-mb-2" };
const _hoisted_17$1 = { class: "tw-text-neutral-700" };
const _hoisted_18$1 = { class: "tw-text-neutral-700" };
const _hoisted_19$1 = {
  key: 0,
  class: "em-logo-box pointer tw-mt-4"
};
const _hoisted_20$1 = ["src", "srcset"];
const _hoisted_21 = { key: 1 };
const _hoisted_22 = {
  key: 1,
  class: "tw-mt-4"
};
const _hoisted_23 = {
  class: "dropzone-custom-content",
  id: "dropzone-message"
};
const _hoisted_24 = { key: 0 };
const _hoisted_25 = { key: 1 };
const _hoisted_26 = {
  key: 0,
  class: "em-h-auto em-flex-col tw-mb-8",
  style: { "align-items": "start" }
};
const _hoisted_27 = { class: "tw-flex tw-items-center" };
const _hoisted_28 = { class: "em-text-neutral-800 tw-mb-2 tw-flex tw-items-center" };
const _hoisted_29 = {
  key: 0,
  class: "em-logo-box pointer tw-mt-4"
};
const _hoisted_30 = ["src", "srcset", "alt"];
const _hoisted_31 = {
  key: 1,
  class: "tw-mt-4"
};
const _hoisted_32 = {
  class: "dropzone-custom-content",
  id: "dropzone-message"
};
const _hoisted_33 = { key: 0 };
const _hoisted_34 = { key: 1 };
const _hoisted_35 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render$c(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_vue_dropzone = resolveComponent("vue-dropzone");
  return openBlock(), createElementBlock("div", null, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_1$c, [
      createBaseVNode("div", _hoisted_2$c, [
        createBaseVNode("div", _hoisted_3$c, [
          createBaseVNode("div", null, [
            createBaseVNode("h4", _hoisted_4$c, [
              _cache[8] || (_cache[8] = createTextVNode(" Logo ")),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-ml-1 tw-text-base tw-cursor-pointer tw-text-neutral-600",
                onClick: _cache[0] || (_cache[0] = (...args) => $options.displayLogoTip && $options.displayLogoTip(...args))
              }, "help_outline")
            ]),
            createBaseVNode("p", _hoisted_5$b, [
              createBaseVNode("em", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ALLOWED_FORMATS")) + ": jpeg, jpg, png, gif, svg", 1)
            ]),
            createBaseVNode("p", _hoisted_6$a, [
              createBaseVNode("em", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_LOGO_RECOMMENDED")), 1)
            ])
          ])
        ]),
        !$data.logo_updating ? (openBlock(), createElementBlock("div", _hoisted_7$9, [
          !$data.hideLogo ? (openBlock(), createElementBlock("img", {
            key: 0,
            id: "logo-img",
            class: "logo-settings",
            src: $data.imageLink,
            alt: "Logo",
            srcset: "/" + $data.imageLink,
            onError: _cache[1] || (_cache[1] = ($event) => $data.hideLogo = true)
          }, null, 40, _hoisted_8$7)) : createCommentVNode("", true),
          $data.hideLogo ? (openBlock(), createElementBlock("p", _hoisted_9$5, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_INSERT_LOGO")), 1)) : createCommentVNode("", true)
        ])) : createCommentVNode("", true),
        $data.logo_updating ? (openBlock(), createElementBlock("div", _hoisted_10$4, [
          createVNode(_component_vue_dropzone, {
            ref: "dropzone",
            id: "customdropzone",
            "include-styling": false,
            options: $data.logoDropzoneOptions,
            useCustomSlot: true,
            onVdropzoneFileAdded: $options.afterAdded,
            onVdropzoneThumbnail: $options.thumbnail,
            onVdropzoneRemovedFile: $options.afterRemoved,
            onVdropzoneComplete: $options.onComplete,
            onVdropzoneError: $options.catchError
          }, {
            default: withCtx(() => [
              createBaseVNode("div", _hoisted_11$3, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_DROP_HERE")), 1)
            ]),
            _: 1
          }, 8, ["options", "onVdropzoneFileAdded", "onVdropzoneThumbnail", "onVdropzoneRemovedFile", "onVdropzoneComplete", "onVdropzoneError"])
        ])) : createCommentVNode("", true),
        createBaseVNode("button", {
          id: "btn-update-logo",
          onClick: _cache[2] || (_cache[2] = ($event) => $data.logo_updating = !$data.logo_updating),
          class: "tw-mt-2 tw-btn-primary"
        }, [
          !$data.logo_updating ? (openBlock(), createElementBlock("span", _hoisted_12$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_UPDATE_LOGO")), 1)) : (openBlock(), createElementBlock("span", _hoisted_13$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CANCEL")), 1))
        ])
      ]),
      createBaseVNode("div", _hoisted_14$2, [
        createBaseVNode("div", _hoisted_15$2, [
          createBaseVNode("div", null, [
            createBaseVNode("h4", _hoisted_16$1, [
              createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ICON")) + " ", 1),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-ml-1 tw-text-base tw-cursor-pointer tw-text-neutral-600",
                onClick: _cache[3] || (_cache[3] = (...args) => $options.displayFaviconTip && $options.displayFaviconTip(...args))
              }, "help_outline")
            ]),
            createBaseVNode("p", _hoisted_17$1, [
              createBaseVNode("em", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ALLOWED_FORMATS")) + ": jpeg, jpg, png, ico", 1)
            ]),
            createBaseVNode("p", _hoisted_18$1, [
              createBaseVNode("em", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ICON_RECOMMENDED")), 1)
            ])
          ])
        ]),
        !$data.favicon_updating ? (openBlock(), createElementBlock("div", _hoisted_19$1, [
          !$data.hideIcon ? (openBlock(), createElementBlock("img", {
            key: 0,
            class: "logo-settings",
            src: $data.iconLink,
            alt: "Favicon",
            srcset: "/" + $data.iconLink,
            onError: _cache[4] || (_cache[4] = ($event) => $data.hideIcon = true)
          }, null, 40, _hoisted_20$1)) : createCommentVNode("", true),
          $data.hideIcon ? (openBlock(), createElementBlock("p", _hoisted_21, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_INSERT_ICON")), 1)) : createCommentVNode("", true)
        ])) : createCommentVNode("", true),
        $data.favicon_updating ? (openBlock(), createElementBlock("div", _hoisted_22, [
          createVNode(_component_vue_dropzone, {
            ref: "dropzone",
            id: "customdropzone",
            "include-styling": false,
            options: $data.faviconDropzoneOptions,
            useCustomSlot: true,
            onVdropzoneFileAdded: $options.afterAdded,
            onVdropzoneThumbnail: $options.thumbnail,
            onVdropzoneRemovedFile: $options.afterRemoved,
            onVdropzoneComplete: $options.onComplete,
            onVdropzoneError: $options.catchError
          }, {
            default: withCtx(() => [
              createBaseVNode("div", _hoisted_23, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_DROP_HERE")), 1)
            ]),
            _: 1
          }, 8, ["options", "onVdropzoneFileAdded", "onVdropzoneThumbnail", "onVdropzoneRemovedFile", "onVdropzoneComplete", "onVdropzoneError"])
        ])) : createCommentVNode("", true),
        createBaseVNode("button", {
          id: "btn-update-favicon",
          onClick: _cache[5] || (_cache[5] = ($event) => $data.favicon_updating = !$data.favicon_updating),
          class: "tw-mt-2 tw-btn-primary"
        }, [
          !$data.favicon_updating ? (openBlock(), createElementBlock("span", _hoisted_24, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_UPDATE_ICON")), 1)) : (openBlock(), createElementBlock("span", _hoisted_25, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CANCEL")), 1))
        ])
      ]),
      $data.bannerLink ? (openBlock(), createElementBlock("div", _hoisted_26, [
        createBaseVNode("div", _hoisted_27, [
          createBaseVNode("div", null, [
            createBaseVNode("h4", _hoisted_28, [
              createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_BANNER")) + " ", 1),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-ml-1 tw-text-base tw-text-neutral-600",
                onClick: _cache[6] || (_cache[6] = (...args) => $options.displayBannerTip && $options.displayBannerTip(...args))
              }, "help_outline")
            ]),
            createBaseVNode("span", null, [
              createBaseVNode("em", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ALLOWED_FORMATS")) + ": jpeg, png", 1)
            ]),
            _cache[9] || (_cache[9] = createBaseVNode("br", null, null, -1)),
            createBaseVNode("span", null, [
              createBaseVNode("em", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RECOMMENDED_SIZE")) + ": 1440x200px", 1)
            ])
          ])
        ]),
        !$data.banner_updating ? (openBlock(), createElementBlock("div", _hoisted_29, [
          createBaseVNode("img", {
            class: "logo-settings",
            style: { "width": "180px" },
            src: $data.bannerLink,
            srcset: "/" + $data.bannerLink,
            alt: $data.InsertBanner
          }, null, 8, _hoisted_30)
        ])) : createCommentVNode("", true),
        $data.banner_updating ? (openBlock(), createElementBlock("div", _hoisted_31, [
          createVNode(_component_vue_dropzone, {
            ref: "dropzone",
            id: "customdropzone",
            "include-styling": false,
            options: $data.bannerDropzoneOptions,
            useCustomSlot: true,
            onVdropzoneFileAdded: $options.afterAdded,
            onVdropzoneThumbnail: $options.thumbnail,
            onVdropzoneRemovedFile: $options.afterRemoved,
            onVdropzoneComplete: $options.onComplete,
            onVdropzoneError: $options.catchError
          }, {
            default: withCtx(() => [
              createBaseVNode("div", _hoisted_32, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_DROP_HERE")), 1)
            ]),
            _: 1
          }, 8, ["options", "onVdropzoneFileAdded", "onVdropzoneThumbnail", "onVdropzoneRemovedFile", "onVdropzoneComplete", "onVdropzoneError"])
        ])) : createCommentVNode("", true),
        createBaseVNode("button", {
          id: "btn-update-banner",
          onClick: _cache[7] || (_cache[7] = ($event) => $data.banner_updating = !$data.banner_updating),
          class: "tw-mt-2 tw-btn-primary"
        }, [
          !$data.banner_updating ? (openBlock(), createElementBlock("span", _hoisted_33, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_UPDATE_BANNER")), 1)) : (openBlock(), createElementBlock("span", _hoisted_34, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_CANCEL")), 1))
        ])
      ])) : createCommentVNode("", true)
    ])) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_35)) : createCommentVNode("", true)
  ]);
}
const General = /* @__PURE__ */ _export_sfc(_sfc_main$c, [["render", _sfc_render$c], ["__scopeId", "data-v-69ed52ef"]]);
const _sfc_main$b = {
  name: "Orphelins",
  components: {
    Multiselect: script
  },
  mixins: [mixin],
  data() {
    return {
      defaultLang: null,
      availableLanguages: [],
      // Lists
      translations: [],
      // Values
      lang: null,
      loading: true,
      saving: false,
      last_save: null
    };
  },
  created() {
    translationsService.getDefaultLanguage().then((response) => {
      this.defaultLang = response;
      this.getAllLanguages();
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
        } else {
          this.loading = false;
        }
      } catch (e) {
        this.loading = false;
        return false;
      }
    },
    async saveTranslation(translation) {
      this.saving = true;
      const value = this.$refs["translation-" + translation.id][0].value;
      if (value) {
        translationsService.insertTranslation(value, "override", this.lang.lang_code, translation.reference_id, translation.tag, translation.reference_table).then((response) => {
          this.last_save = this.formattedDate("", "LT");
          this.saving = false;
          this.translations = this.translations.filter(function(item) {
            return item.id !== translation.id;
          });
        });
      }
    }
  },
  watch: {
    lang: function(value) {
      if (value === null || typeof value === void 0) {
        return;
      }
      this.loading = true;
      this.translations = [];
      translationsService.getOrphelins(this.defaultLang.lang_code, value.lang_code).then((response) => {
        this.translations = response.data;
        this.loading = false;
      });
    }
  }
};
const _hoisted_1$b = { class: "tw-mb-2" };
const _hoisted_2$b = {
  key: 0,
  class: "tw-text-base tw-text-neutral-700 tw-mb-6 em-h-25"
};
const _hoisted_3$b = {
  key: 1,
  class: "tw-mb-6 tw-flex tw-items-center tw-justify-start"
};
const _hoisted_4$b = { class: "tw-text-base tw-flex tw-items-center" };
const _hoisted_5$a = {
  key: 2,
  class: "tw-text-base tw-mb-6 em-h-25"
};
const _hoisted_6$9 = {
  key: 3,
  class: "tw-text-base tw-mb-6 em-h-25"
};
const _hoisted_7$8 = {
  key: 4,
  class: "tw-text-base tw-mb-6 em-h-25"
};
const _hoisted_8$6 = {
  key: 5,
  class: "em-grid-4"
};
const _hoisted_9$4 = { class: "col-md-12" };
const _hoisted_10$3 = {
  key: 0,
  class: "text-center tw-mt-20"
};
const _hoisted_11$2 = { class: "tw-mb-2" };
const _hoisted_12$1 = {
  key: 0,
  class: "tw-text-base em-text-neutral-600"
};
const _hoisted_13$1 = { key: 1 };
const _hoisted_14$1 = { class: "tw-mb-8 em-neutral-100-box em-p-24" };
const _hoisted_15$1 = { class: "tw-justify-between tw-mt-4 em-grid-50" };
const _hoisted_16 = { class: "tw-text-neutral-700" };
const _hoisted_17 = { class: "tw-text-right" };
const _hoisted_18 = ["value"];
const _hoisted_19 = ["onClick"];
const _hoisted_20 = {
  key: 6,
  class: "em-page-loader"
};
function _sfc_render$b(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("h3", _hoisted_1$b, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHELINS")), 1),
    !$data.saving && $data.last_save == null ? (openBlock(), createElementBlock("p", _hoisted_2$b, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE")), 1)) : createCommentVNode("", true),
    $data.saving ? (openBlock(), createElementBlock("div", _hoisted_3$b, [
      _cache[1] || (_cache[1] = createBaseVNode("div", { class: "em-loader tw-mr-2" }, null, -1)),
      createBaseVNode("p", _hoisted_4$b, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_PROGRESS")), 1)
    ])) : createCommentVNode("", true),
    !$data.saving && $data.last_save != null ? (openBlock(), createElementBlock("p", _hoisted_5$a, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_LAST") + $data.last_save), 1)) : createCommentVNode("", true),
    $data.availableLanguages.length === 0 && !$data.loading ? (openBlock(), createElementBlock("p", _hoisted_6$9, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_NO_LANGUAGES_AVAILABLE")), 1)) : createCommentVNode("", true),
    $data.translations.length === 0 && !$data.loading ? (openBlock(), createElementBlock("p", _hoisted_7$8, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHANS_CONGRATULATIONS")), 1)) : (openBlock(), createElementBlock("div", _hoisted_8$6, [
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
          placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_LANGUAGE"),
          "close-on-select": true,
          "clear-on-select": false,
          searchable: false,
          "allow-empty": true
        }, null, 8, ["modelValue", "options", "placeholder"])
      ])
    ])),
    _cache[2] || (_cache[2] = createBaseVNode("hr", {
      class: "col-md-12",
      style: { "z-index": "0" }
    }, null, -1)),
    createBaseVNode("div", _hoisted_9$4, [
      $data.lang === "" || $data.lang == null || $data.translations.length === 0 ? (openBlock(), createElementBlock("div", _hoisted_10$3, [
        createBaseVNode("h5", _hoisted_11$2, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_ORPHELINS_TITLE")), 1),
        $data.lang === "" || $data.lang == null ? (openBlock(), createElementBlock("p", _hoisted_12$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_NO_ORPHELINS_TEXT")), 1)) : createCommentVNode("", true)
      ])) : (openBlock(), createElementBlock("div", _hoisted_13$1, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.translations, (translation) => {
          return openBlock(), createElementBlock("div", {
            key: translation.id
          }, [
            createBaseVNode("div", _hoisted_14$1, [
              createBaseVNode("div", _hoisted_15$1, [
                createBaseVNode("p", _hoisted_16, toDisplayString(translation.override), 1),
                createBaseVNode("div", _hoisted_17, [
                  createBaseVNode("input", {
                    class: "mb-0 em-input tw-w-full",
                    type: "text",
                    value: translation.override,
                    ref_for: true,
                    ref: "translation-" + translation.id
                  }, null, 8, _hoisted_18),
                  createBaseVNode("a", {
                    role: "button",
                    class: "tw-normal-case btn btn-primary tw-cursor-pointer em-profile-color tw-mt-4 tw-text-base",
                    onClick: ($event) => $options.saveTranslation(translation)
                  }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHELIN_CONFIRM_TRANSLATION")), 9, _hoisted_19)
                ])
              ])
            ])
          ]);
        }), 128))
      ]))
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_20)) : createCommentVNode("", true)
  ]);
}
const Orphelins = /* @__PURE__ */ _export_sfc(_sfc_main$b, [["render", _sfc_render$b]]);
const useSettingsStore = defineStore("settings", {
  state: () => ({
    needSaving: false
  }),
  getters: {
    getNeedSaving: (state) => state.needSaving
  },
  actions: {
    updateNeedSaving(payload) {
      this.needSaving = payload;
    }
  }
});
const _sfc_main$a = {
  name: "editArticle",
  components: {
    Multiselect: script,
    TipTapEditor: V32
  },
  props: {
    actualLanguage: {
      type: String,
      default: "fr"
    },
    article_alias: {
      type: String,
      default: null
    },
    article_id: {
      type: Number,
      default: 0
    },
    category: {
      type: String,
      default: null
    },
    published: {
      type: Number,
      default: 1
    },
    name: {
      default: null
    },
    displayPublishedToggle: {
      type: Boolean,
      default: true
    }
  },
  mixins: [mixin],
  data() {
    return {
      defaultLang: null,
      availableLanguages: [],
      editorPlugins: ["history", "link", "image", "bold", "italic", "underline", "left", "center", "right", "h1", "h2", "ul"],
      lang: null,
      loading: false,
      dynamicComponent: 0,
      updated: false,
      form: {
        published: this.$props.published,
        content: "",
        need_notify: false
      },
      previousContent: "",
      initContent: "",
      clearNotif: false
    };
  },
  created() {
    this.loading = true;
    translationsService.getDefaultLanguage().then((response) => {
      this.defaultLang = response;
      this.getAllLanguages();
      this.loading = false;
    });
  },
  methods: {
    async getArticle() {
      let params = {
        article_id: this.$props.article_id,
        lang: this.lang.lang_code,
        field: "introtext"
      };
      if (this.$props.article_alias !== null) {
        params = {
          article_alias: this.$props.article_alias,
          lang: this.lang.lang_code,
          field: "introtext"
        };
      }
      await client().get("index.php?option=com_emundus&controller=settings&task=getarticle", {
        params
      }).then((response) => {
        this.form.content = response.data.data.introtext;
        this.form.published = response.data.data.published;
        this.dynamicComponent++;
      });
    },
    async saveContent() {
      const formData = new FormData();
      formData.append("content", this.form.content);
      formData.append("lang", this.lang.lang_code);
      if (this.$props.article_alias !== null) {
        formData.append("article_alias", this.$props.article_alias);
      } else {
        formData.append("article_id", this.$props.article_id);
      }
      formData.append("field", "introtext");
      if (this.clearNotif) {
        formData.append("note", "");
      }
      await client().post(
        `index.php?option=com_emundus&controller=settings&task=updatearticle`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        }
      ).then(async () => {
        this.updated = false;
        Swal$1.fire({
          title: this.translate("COM_EMUNDUS_ONBOARD_SUCCESS"),
          text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_SUCCESS"),
          showCancelButton: false,
          showConfirmButton: false,
          customClass: {
            title: "em-swal-title"
          },
          timer: 1500
        });
      });
    },
    async getAllLanguages() {
      await translationsService.getAllLanguages().then((response) => {
        this.availableLanguages = response;
        this.lang = this.defaultLang;
      });
    },
    async publishArticle() {
      this.$emit("updateSaving", true);
      const formData = new FormData();
      formData.append("publish", this.form.published);
      if (this.$props.article_alias !== null) {
        formData.append("article_alias", this.$props.article_alias);
      } else {
        formData.append("article_id", this.$props.article_id);
      }
      await client().post(
        `index.php?option=com_emundus&controller=settings&task=publisharticle`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        }
      ).then(() => {
        this.$emit("updateSaving", false);
        this.$emit("updateLastSaving", this.formattedDate("", "LT"));
        this.$emit("updatePublished", this.form.published);
      });
    },
    async saveMethod() {
      await this.saveContent();
      return true;
    },
    async updateArticleNotif() {
      const response = await axios.get("index.php?option=com_emundus&controller=settings&task=updateArticleNeedToModify", {
        params: {
          article_alias: this.$props.article_alias
        }
      });
      delete response.data.msg;
      return response.data;
    }
  },
  watch: {
    lang: function() {
      if (this.lang !== null) {
        this.getArticle();
      } else {
        this.form.content = "";
        this.dynamicComponent++;
      }
    },
    updated: function(val) {
      this.$emit("needSaving", val, this.$props.article_alias);
    },
    "form.content": {
      handler: function(newVal) {
        if (this.initContent === "") {
          this.initContent = newVal;
        }
        if (this.previousContent !== newVal) {
          if (this.initContent !== newVal) {
            this.clearNotif = true;
            this.previousContent = newVal;
            useSettingsStore().updateNeedSaving(1);
          } else {
            useSettingsStore().updateNeedSaving(0);
            this.clearNotif = false;
            this.updated = false;
          }
        }
      },
      immediate: true
    }
  }
};
const _hoisted_1$a = { class: "em-settings-menu" };
const _hoisted_2$a = { class: "tw-w-full tw-mb-4" };
const _hoisted_3$a = { class: "tw-w-5/6" };
const _hoisted_4$a = { class: "tw-grid tw-grid-cols-3 tw-gap-6 tw-mb-4" };
const _hoisted_5$9 = {
  key: 0,
  class: "tw-mb-4 tw-flex tw-items-center"
};
const _hoisted_6$8 = { class: "em-toggle" };
const _hoisted_7$7 = {
  for: "published",
  class: "tw-ml-2"
};
const _hoisted_8$5 = { class: "form-group controls" };
const _hoisted_9$3 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$a(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  const _component_tip_tap_editor = resolveComponent("tip-tap-editor");
  return openBlock(), createElementBlock("div", _hoisted_1$a, [
    createBaseVNode("div", _hoisted_2$a, [
      createBaseVNode("div", _hoisted_3$a, [
        createBaseVNode("div", _hoisted_4$a, [
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
            placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_LANGUAGE"),
            "close-on-select": true,
            "clear-on-select": false,
            searchable: false,
            "allow-empty": false
          }, null, 8, ["modelValue", "options", "placeholder"])
        ]),
        $props.displayPublishedToggle ? (openBlock(), createElementBlock("div", _hoisted_5$9, [
          createBaseVNode("div", _hoisted_6$8, [
            withDirectives(createBaseVNode("input", {
              type: "checkbox",
              "true-value": "1",
              "false-value": "0",
              class: "em-toggle-check",
              id: "published",
              name: "published",
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.form.published = $event),
              onChange: _cache[2] || (_cache[2] = ($event) => $options.publishArticle())
            }, null, 544), [
              [vModelCheckbox, $data.form.published]
            ]),
            _cache[6] || (_cache[6] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
            _cache[7] || (_cache[7] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
          ]),
          createBaseVNode("span", _hoisted_7$7, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SETTINGS_CONTENT_PUBLISH")), 1)
        ])) : createCommentVNode("", true),
        createBaseVNode("div", _hoisted_8$5, [
          createVNode(_component_tip_tap_editor, {
            modelValue: $data.form.content,
            "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.form.content = $event),
            "upload-url": "/index.php?option=com_emundus&controller=settings&task=uploadmedia",
            "editor-content-height": "30em",
            class: normalizeClass("tw-mt-1"),
            locale: "fr",
            preset: "custom",
            plugins: $data.editorPlugins,
            "toolbar-classes": ["tw-bg-white"],
            "editor-content-classes": ["tw-bg-white tw-mb-2"],
            onInput: _cache[4] || (_cache[4] = ($event) => $data.updated = true)
          }, null, 8, ["modelValue", "plugins"])
        ]),
        $data.updated ? (openBlock(), createElementBlock("button", {
          key: 1,
          class: "btn btn-primary tw-float-right tw-mt-3",
          onClick: _cache[5] || (_cache[5] = (...args) => $options.saveContent && $options.saveContent(...args))
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE")), 1)) : createCommentVNode("", true)
      ]),
      $data.loading ? (openBlock(), createElementBlock("div", _hoisted_9$3)) : createCommentVNode("", true)
    ])
  ]);
}
const EditArticle = /* @__PURE__ */ _export_sfc(_sfc_main$a, [["render", _sfc_render$a]]);
const _sfc_main$9 = {
  name: "EditFooter",
  components: {
    Multiselect: script,
    TipTapEditor: V32
  },
  props: {
    actualLanguage: String
  },
  mixins: [mixin],
  data() {
    return {
      loading: false,
      dynamicComponent: 0,
      selectedColumn: 0,
      updated: false,
      initcol1: "",
      initcol2: "",
      editorPlugins: ["history", "link", "image", "bold", "italic", "underline", "left", "center", "right", "h1", "h2", "ul"],
      form: {
        content: {
          col1: null,
          col2: null
        }
      },
      columns: [
        {
          index: 0,
          label: this.translate("COM_EMUNDUS_ONBOARD_COLUMN") + " 1"
        },
        {
          index: 1,
          label: this.translate("COM_EMUNDUS_ONBOARD_COLUMN") + " 2"
        }
      ]
    };
  },
  created() {
    this.loading = true;
    this.getArticles();
    this.selectedColumn = this.columns[0];
  },
  methods: {
    async getArticles() {
      await client().get("index.php?option=com_emundus&controller=settings&task=getfooterarticles").then((response) => {
        this.initcol1 = response.data.data.column1;
        this.initcol2 = response.data.data.column2;
        this.form.content.col1 = this.initcol1;
        this.form.content.col2 = this.initcol2;
        this.loading = false;
      });
    },
    async saveMethod() {
      this.$emit("updateSaving", true);
      const formData = new FormData();
      formData.append("col1", this.form.content.col1);
      formData.append("col2", this.form.content.col2);
      await client().post(
        `index.php?option=com_emundus&controller=settings&task=updatefooter`,
        formData,
        {
          headers: {
            "Content-Type": "multipart/form-data"
          }
        }
      ).then(() => {
        this.$emit("updateSaving", false);
        this.$emit("updateLastSaving", this.formattedDate("", "LT"));
        this.$emit("updatePublished", this.form.published);
        this.updated = false;
        Swal.fire({
          title: this.translate("COM_EMUNDUS_ONBOARD_SUCCESS"),
          text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_SUCCESS"),
          showCancelButton: false,
          showConfirmButton: false,
          customClass: {
            title: "em-swal-title"
          },
          timer: 1500
        });
        this.initcol1 = this.form.content.col1;
        this.initcol2 = this.form.content.col2;
      });
    }
  },
  watch: {
    selectedColumn: function() {
      this.dynamicComponent++;
    },
    "form.content.col1": function(val, oldVal) {
      if (oldVal !== null) {
        if (val !== oldVal) {
          this.form.content.col1 = val;
          this.updated = true;
        }
      }
    },
    "form.content.col2": function(val, oldVal) {
      if (oldVal !== null) {
        if (val !== oldVal) {
          this.form.content.col1 = val;
          this.updated = true;
        }
      }
    },
    updated: function(val) {
      this.$emit("needSaving", val);
    }
  }
};
const _hoisted_1$9 = { class: "em-settings-menu" };
const _hoisted_2$9 = { class: "tw-w-full" };
const _hoisted_3$9 = { class: "tw-w-5/6" };
const _hoisted_4$9 = { class: "tw-grid tw-grid-cols-3 tw-gap-6 tw-mb-4" };
const _hoisted_5$8 = {
  key: 0,
  class: "form-group controls"
};
const _hoisted_6$7 = {
  key: 1,
  class: "form-group controls"
};
const _hoisted_7$6 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  const _component_tip_tap_editor = resolveComponent("tip-tap-editor");
  return openBlock(), createElementBlock("div", _hoisted_1$9, [
    createBaseVNode("div", _hoisted_2$9, [
      createBaseVNode("div", _hoisted_3$9, [
        createBaseVNode("div", _hoisted_4$9, [
          createVNode(_component_multiselect, {
            modelValue: $data.selectedColumn,
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.selectedColumn = $event),
            label: "label",
            "track-by": "index",
            options: $data.columns,
            multiple: false,
            taggable: false,
            "select-label": "",
            "selected-label": "",
            "deselect-label": "",
            placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_COLUMN"),
            "close-on-select": true,
            "clear-on-select": false,
            searchable: false,
            "allow-empty": true
          }, null, 8, ["modelValue", "options", "placeholder"])
        ]),
        $data.selectedColumn.index === 0 && this.form.content.col1 != null ? (openBlock(), createElementBlock("div", _hoisted_5$8, [
          createVNode(_component_tip_tap_editor, {
            modelValue: $data.form.content.col1,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.form.content.col1 = $event),
            "upload-url": "/index.php?option=com_emundus&controller=settings&task=uploadmedia",
            "editor-content-height": "30em",
            class: normalizeClass("tw-mt-1"),
            locale: "fr",
            preset: "custom",
            plugins: $data.editorPlugins,
            "toolbar-classes": ["tw-bg-white"],
            "editor-content-classes": ["tw-bg-white"]
          }, null, 8, ["modelValue", "plugins"])
        ])) : createCommentVNode("", true),
        $data.selectedColumn.index === 1 && this.form.content.col2 != null ? (openBlock(), createElementBlock("div", _hoisted_6$7, [
          createVNode(_component_tip_tap_editor, {
            modelValue: $data.form.content.col2,
            "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.form.content.col2 = $event),
            "upload-url": "/index.php?option=com_emundus&controller=settings&task=uploadmedia",
            "editor-content-height": "30em",
            class: normalizeClass("tw-mt-1"),
            locale: "fr",
            preset: "custom",
            plugins: $data.editorPlugins,
            "toolbar-classes": ["tw-bg-white"],
            "editor-content-classes": ["tw-bg-white"]
          }, null, 8, ["modelValue", "plugins"])
        ])) : createCommentVNode("", true),
        $data.updated ? (openBlock(), createElementBlock("button", {
          key: 2,
          class: "btn btn-primary tw-float-right tw-mt-3",
          onClick: _cache[3] || (_cache[3] = (...args) => $options.saveMethod && $options.saveMethod(...args))
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE")), 1)) : createCommentVNode("", true)
      ]),
      $data.loading ? (openBlock(), createElementBlock("div", _hoisted_7$6)) : createCommentVNode("", true)
    ])
  ]);
}
const EditFooter = /* @__PURE__ */ _export_sfc(_sfc_main$9, [["render", _sfc_render$9]]);
const _sfc_main$8 = {
  name: "SubSection",
  components: {
    Parameter,
    Multiselect: script,
    EditArticle
  },
  props: {
    name: {
      default: null
    },
    component: {
      type: String
    },
    props: {
      type: Object
    },
    json_source: {
      type: String,
      required: true
    },
    notify: {
      type: Boolean,
      required: false
    },
    index: {
      type: Number,
      required: false
    }
  },
  mixins: [mixin],
  data() {
    return {
      defaultLang: null,
      availableLanguages: [],
      subSection: [],
      Initname: this.$props.name,
      lang: null,
      loading: false,
      dynamicComponent: 0,
      updated: false,
      subSectionNotif: this.$props.notify,
      keyNotif: 0,
      form: {
        published: this.$props.published,
        content: ""
      }
    };
  },
  created() {
    this.loading = true;
    this.loading = false;
  },
  methods: {
    toggleVisibilityContent() {
      let SubSectionArrow = document.getElementById("SubSectionArrow" + this.$props.name);
      let SubSectionContent = document.getElementById("SubSection-" + this.$props.name);
      if (SubSectionContent.style.display === "none") {
        SubSectionContent.style.display = "block";
        SubSectionArrow.style.transform = "rotate(180deg)";
      } else {
        SubSectionContent.style.display = "none";
        SubSectionArrow.style.transform = "rotate(0deg)";
      }
    },
    handleToogleContent() {
      this.toggleVisibilityContent();
    },
    handleNeedSaving(needSaving, article) {
      this.$emit("needSaving", needSaving, article);
    },
    updateNotif(needNotify) {
      this.subSectionNotif = needNotify;
      this.keyNotif++;
      this.$emit("updateNotif", this.$props.index, needNotify);
    },
    saveMethod(notif) {
      this.$emit("updateNotif", !notif);
      let vue_component = this.$refs["component_" + this.$props.name];
      if (vue_component && typeof vue_component.saveContent === "function") {
        vue_component.saveContent();
      }
    }
  },
  watch: {}
};
const _hoisted_1$8 = { class: "em-settings-menu" };
const _hoisted_2$8 = {
  key: 0,
  class: "tw-w-full"
};
const _hoisted_3$8 = { key: 0 };
const _hoisted_4$8 = { class: "tw-text-xl tw-font-bold" };
const _hoisted_5$7 = ["id"];
const _hoisted_6$6 = ["id"];
const _hoisted_7$5 = { class: "flex flex-col" };
const _hoisted_8$4 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$8, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_2$8, [
      _ctx.$props.props.published ? (openBlock(), createElementBlock("div", _hoisted_3$8, [
        createBaseVNode("div", {
          class: "tw-flex tw-items-center tw-pb-8 tw-cursor-pointer",
          onClick: _cache[0] || (_cache[0] = (...args) => $options.handleToogleContent && $options.handleToogleContent(...args))
        }, [
          createBaseVNode("span", _hoisted_4$8, toDisplayString(_ctx.translate($props.name)), 1),
          createBaseVNode("i", {
            class: "material-symbols-outlined scale-150",
            id: "SubSectionArrow" + _ctx.$props.name,
            name: "SubSectionArrows",
            style: { "transform-origin": "unset" }
          }, "expand_more", 8, _hoisted_5$7),
          $data.subSectionNotif === true ? (openBlock(), createElementBlock("div", {
            key: $data.keyNotif,
            class: "tw-inline-flex tw-items-center tw-justify-center tw-w-6 tw-h-6 tw-bg-red-500 tw-box-border-2 tw-border-white tw-rounded-full -top-2 -end-2"
          })) : createCommentVNode("", true)
        ]),
        createBaseVNode("div", {
          id: "SubSection-" + _ctx.$props.name,
          name: "SubSectionContent",
          style: { "display": "none" },
          class: "flex flex-col"
        }, [
          createBaseVNode("div", null, [
            createBaseVNode("div", _hoisted_7$5, [
              (openBlock(), createBlock(resolveDynamicComponent(_ctx.$props.component), mergeProps(_ctx.$props.props, {
                ref: "component_" + _ctx.$props.name,
                onNeedSaving: $options.handleNeedSaving,
                onNeedNotify: $options.updateNotif
              }), null, 16, ["onNeedSaving", "onNeedNotify"]))
            ])
          ])
        ], 8, _hoisted_6$6)
      ])) : createCommentVNode("", true)
    ])) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_8$4)) : createCommentVNode("", true)
  ]);
}
const SubSection = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$8]]);
const _sfc_main$7 = {
  name: "Tile",
  components: {
    //Parameter,
  },
  props: {
    name: {
      default: null
    },
    link: {
      default: null
    },
    icon: {
      default: null
    },
    title: {
      default: null
    },
    color: {
      default: null
    }
  },
  mixins: [mixin],
  data() {
    return {
      defaultLang: null,
      availableLanguages: [],
      subSection: [],
      Initname: this.$props.name,
      lang: null,
      loading: false,
      dynamicComponent: 0,
      updated: false,
      subSectionNotif: this.$props.notify,
      form: {
        published: this.$props.published,
        content: ""
      }
    };
  },
  created() {
    this.loading = true;
    this.loading = false;
  },
  methods: {
    handleNeedSaving(needSaving, article) {
      this.$store.commit("settings/setNeedSaving", needSaving);
      this.$store.commit("settings/setArticle", article);
      this.$emit("NeedSaving", needSaving, article);
    },
    saveMethod() {
      let vue_component = this.$refs["component_" + this.$props.name];
      if (vue_component && typeof vue_component.saveContent === "function") {
        vue_component.saveContent();
      }
    }
  },
  watch: {}
};
const _hoisted_1$7 = { class: "em-settings-menu" };
const _hoisted_2$7 = { key: 0 };
const _hoisted_3$7 = {
  class: "tw-flex tw-bg-white tw-rounded tw-shadow-md tw-relative tw-w-80 tw-h-56 tw-mb-8",
  name: "tilebutton"
};
const _hoisted_4$7 = { class: "material-symbols-outlined em-color-white tw-scale-[4]" };
const _hoisted_5$6 = { class: "tw-font-bold tw-flex tw-justify-center tw-items-center" };
const _hoisted_6$5 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$7, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_2$7, [
      createBaseVNode("div", _hoisted_3$7, [
        createBaseVNode("button", {
          type: "button",
          onClick: _cache[0] || (_cache[0] = ($event) => _ctx.redirect(this.$props.link)),
          class: "tw-rounded tw-flex tw-flex-col tw-justify-center tw-items-center tw-absolute tw-top-1/2 tw-left-1/2 tw-transform tw--translate-x-1/2 tw--translate-y-1/2"
        }, [
          createBaseVNode("div", {
            class: "tw-rounded tw-flex tw-justify-center tw-items-center",
            style: normalizeStyle({
              "background-color": this.$props.color,
              "width": "16em",
              "height": "10em"
            })
          }, [
            createBaseVNode("i", _hoisted_4$7, toDisplayString(this.$props.icon), 1)
          ], 4),
          createBaseVNode("div", _hoisted_5$6, toDisplayString(_ctx.translate(this.$props.title)), 1)
        ])
      ])
    ])) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_6$5)) : createCommentVNode("", true)
  ]);
}
const Tile = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7]]);
const assetsPath = "/components/com_emundus/src/assets/data/";
const getPath = (path) => `${assetsPath}${path}`;
const _sfc_main$6 = {
  name: "SettingsContent",
  components: {
    SiteSettings,
    EditTheme,
    EditStatus,
    EditTags,
    General,
    Orphelins,
    Translations,
    EditArticle,
    EditFooter,
    Info,
    SubSection,
    Tile
  },
  props: {
    json_source: {
      type: String,
      required: true
    }
  },
  mixins: [],
  data() {
    return {
      sections: [],
      activeSection: null,
      needSaving: false,
      notificationElements: [],
      sectionsToNotif: [],
      countNotifUpdate: 0,
      needToNotify: [],
      numberNotif: 0
    };
  },
  setup() {
    const settingsStore = useSettingsStore();
    return {
      settingsStore
    };
  },
  async created() {
    import(getPath(this.$props.json_source)).then((result) => {
      if (result) {
        this.sections = result.default;
      }
    });
    const sessionSection = sessionStorage.getItem("tchooz_settings_selected_section/" + document.location.hostname);
    if (sessionSection) {
      this.activeSection = parseInt(sessionSection);
    }
    this.$emit("listSections", this.sections, "sections");
  },
  mounted() {
    this.initsmallDotnotif();
  },
  methods: {
    async saveMethod() {
      await this.saveSection(this.sections[this.activeSection]);
      return true;
    },
    getSection() {
      return this.sections[this.activeSection];
    },
    async saveSection(section, index = null) {
      if (section.component === "SubSection") {
        for (let i in section.props) {
          let url = "component_Subsection-" + section.props[i].name;
          if (this.$refs[url]) {
            this.$refs[url][0].$children[0].saveContent();
          }
        }
        this.setCountNotifUpdate();
        if (index !== null) {
          this.handleActiveSection(index);
        }
      } else if (section.component !== "SubSection") {
        let vue_component = this.$refs["component_" + section.name];
        if (Array.isArray(vue_component)) {
          vue_component = vue_component[0];
        }
        if (typeof vue_component.saveMethod !== "function") {
          console.error("The component " + section.name + " does not have a saveMethod function");
          return;
        }
        vue_component.saveMethod().then((response) => {
          if (response === true) {
            if (index !== null) {
              this.handleActiveSection(index);
            }
          }
        });
      }
    },
    handleNeedSaving(needSaving) {
      this.settingsStore.updateNeedSaving(needSaving);
    },
    handleSection(index) {
      if (this.settingsStore.needSaving) {
        this.showConfirmationDialog(index).then((result) => {
          if (result.value) {
            this.handleNeedSaving(false);
            this.saveSection(this.getSection(), index);
            this.settingsStore.updateNeedSaving(false);
          } else {
            this.settingsStore.updateNeedSaving(false);
            this.handleActiveSection(index);
          }
        });
      } else {
        this.handleActiveSection(index);
      }
    },
    showConfirmationDialog: function() {
      return Swal$1.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_WARNING"),
        text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_UNSAVED"),
        showCancelButton: true,
        confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE"),
        cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPDATES"),
        reverseButtons: true,
        allowOutsideClick: false,
        customClass: {
          title: "em-swal-title",
          cancelButton: "em-swal-cancel-button",
          confirmButton: "em-swal-confirm-button"
        }
      });
    },
    saveAllSections(index) {
      this.sections.forEach((section) => {
        if (section.component === "SubSection") {
          this.saveSubSection(section);
        } else {
          this.saveSection(this.sections[this.activeSection], index);
        }
      });
    },
    saveSubSection(section) {
      for (let i = 0; i < section.props[i]; i++) {
        let vue_component = this.$refs["component_Subsection-" + section.props[i].name];
        if (vue_component && typeof vue_component.saveMethod === "function") {
          vue_component.saveMethod();
        }
      }
      this.setCountNotifUpdate();
    },
    handleActiveSection(index) {
      if (index === this.activeSection) {
        this.activeSection = null;
      } else {
        this.activeSection = index;
      }
    },
    async getNeedToModify() {
      const response = await settingsService.getAllArticleNeedToModify();
      return response.data;
    },
    async setCountNotifUpdate(index, needNotify) {
      if (index !== void 0) {
        for (let i in this.sections) {
          if (this.sections[i].notify === 1) {
            if (this.sections[i].component === "SubSection") {
              this.needToNotify[index] = needNotify ? true : false;
            }
          }
        }
        this.countNotifUpdate++;
      }
    },
    async initsmallDotnotif() {
      for (let i in this.sections) {
        if (this.sections[i].notify === 1) {
          this.sectionsToNotif.push(parseInt(i));
          const response = await this.getNeedToModify();
          this.notificationElements = Object.values(response);
          if (this.sections[i].component === "SubSection") {
            for (let k in this.notificationElements) {
              let foundIndex = this.sections[i].props.findIndex((subSection) => subSection.props.article_alias === this.notificationElements[k].alias);
              if (foundIndex !== -1) {
                this.needToNotify[foundIndex] = true;
              }
            }
          }
          this.countNotifUpdate++;
        }
      }
    }
  },
  watch: {
    activeSection: function(val) {
      sessionStorage.setItem("tchooz_settings_selected_section/" + document.location.hostname, this.activeSection);
      this.$emit("sectionSelected", this.sections[val]);
    },
    countNotifUpdate: function() {
      this.numberNotif = 0;
      for (let i = 0; i < this.needToNotify.length; i++) {
        if (this.needToNotify[i]) {
          this.numberNotif++;
        }
      }
      this.$emit("updateNotif", false);
    }
  },
  computed: {}
};
const _hoisted_1$6 = { key: 0 };
const _hoisted_2$6 = {
  key: 1,
  class: "tw-flex tw-flex-col tw-justify-between tw-w-full tw-font-medium rtl:tw-text-right tw-text-black tw-border tw-border-gray-200 tw-rounded-[15px] tw-bg-white tw-mb-6 tw-gap-3 tw-shadow",
  "data-accordion-target": "#accordion-collapse-body-1",
  "aria-expanded": "true",
  "aria-controls": "accordion-collapse-body-1"
};
const _hoisted_3$6 = { class: "tw-flex-col tw-flex" };
const _hoisted_4$6 = { class: "tw-flex tw-items-center tw-justify-between tw-p-5" };
const _hoisted_5$5 = {
  id: "accordion-collapse-heading-1",
  class: "tw-user-select-none tw-flex tw-justify-between"
};
const _hoisted_6$4 = ["id"];
const _hoisted_7$4 = { class: "tw-text-white tw-text-xs tw-font-bold" };
const _hoisted_8$3 = ["id"];
const _hoisted_9$2 = {
  key: 0,
  class: "tw-text-sm tw-text-neutral-800 tw--mt-5 tw-pb-5 tw-px-5"
};
const _hoisted_10$2 = {
  name: "SubMenuContent",
  class: "tw-flex tw-flex-col tw-px-5 tw-pb-5"
};
const _hoisted_11$1 = { key: 1 };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Tile = resolveComponent("Tile");
  const _component_Info = resolveComponent("Info");
  const _component_SubSection = resolveComponent("SubSection");
  return openBlock(), createElementBlock("div", null, [
    (openBlock(true), createElementBlock(Fragment, null, renderList($data.sections, (section, indexSection) => {
      return openBlock(), createElementBlock("div", {
        id: "accordion-collapse",
        key: indexSection
      }, [
        section.type === "tile" ? (openBlock(), createElementBlock("div", _hoisted_1$6, [
          createVNode(_component_Tile, mergeProps({ ref_for: true }, section.props), null, 16)
        ])) : (openBlock(), createElementBlock("div", _hoisted_2$6, [
          createBaseVNode("div", _hoisted_3$6, [
            createBaseVNode("div", _hoisted_4$6, [
              createBaseVNode("h2", _hoisted_5$5, [
                createBaseVNode("span", {
                  id: "Subtile" + indexSection,
                  class: "tw-text-2xl tw-user-select-none"
                }, toDisplayString(_ctx.translate(section.label)), 9, _hoisted_6$4),
                $data.sectionsToNotif.includes(indexSection) && $data.numberNotif > 0 ? (openBlock(), createElementBlock("div", {
                  key: $data.countNotifUpdate,
                  class: "tw-inline-flex tw-items-center tw-justify-center tw-w-6 tw-h-6 tw-bg-red-500 tw-box-border-2 tw-border-white tw-rounded-full -top-2 -end-2"
                }, [
                  createBaseVNode("span", _hoisted_7$4, toDisplayString($data.numberNotif), 1)
                ])) : createCommentVNode("", true)
              ]),
              createBaseVNode("span", {
                class: normalizeClass(["material-symbols-outlined tw-scale-150 tw-user-select-none hidden", $data.activeSection === indexSection ? "tw-rotate-180" : ""]),
                id: "SubtitleArrow" + indexSection,
                name: "SubtitleArrows"
              }, "expand_more", 10, _hoisted_8$3)
            ]),
            section.intro ? (openBlock(), createElementBlock("span", _hoisted_9$2, toDisplayString(_ctx.translate(section.intro)), 1)) : createCommentVNode("", true)
          ]),
          createBaseVNode("div", _hoisted_10$2, [
            section.helptext ? (openBlock(), createBlock(_component_Info, {
              key: 0,
              text: section.helptext,
              class: "tw-mb-4"
            }, null, 8, ["text"])) : createCommentVNode("", true),
            section.component !== "SubSection" ? (openBlock(), createElementBlock("div", _hoisted_11$1, [
              (openBlock(), createBlock(resolveDynamicComponent(section.component), mergeProps({
                ref_for: true,
                ref: "component_" + section.name,
                key: $data.activeSection
              }, section.props, { onNeedSaving: $options.handleNeedSaving }), null, 16, ["onNeedSaving"]))
            ])) : (openBlock(true), createElementBlock(Fragment, { key: 2 }, renderList(section.props, (subSectionElement, indexSubSection) => {
              return openBlock(), createElementBlock("div", null, [
                (openBlock(), createBlock(_component_SubSection, {
                  key: $data.countNotifUpdate,
                  name: subSectionElement.label,
                  ref_for: true,
                  ref: "component_Subsection-" + subSectionElement.name,
                  component: subSectionElement.component,
                  props: subSectionElement.props,
                  json_source: _ctx.$props.json_source,
                  notify: $data.needToNotify[indexSubSection],
                  index: indexSubSection,
                  onNeedSaving: $options.handleNeedSaving,
                  onUpdateNotif: $options.setCountNotifUpdate
                }, null, 8, ["name", "component", "props", "json_source", "notify", "index", "onNeedSaving", "onUpdateNotif"]))
              ]);
            }), 256))
          ])
        ]))
      ]);
    }), 128))
  ]);
}
const SettingsContent = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6]]);
const _sfc_main$5 = {
  name: "Content",
  components: {},
  props: {
    json_source: {
      type: String,
      required: true
    }
  },
  mixins: [],
  data() {
    return {
      addons: [
        {
          "label": "COM_EMUNDUS_GLOBAL_PARMAS_SECTIONS_SHARE_FILE",
          "published": false,
          "type": "Tile",
          "color": "#F04437",
          "icon": "share",
          "link": "",
          "helptext": ""
        },
        {
          "label": "COM_EMUNDUS_GLOBAL_PARMAS_SECTIONS_MAIL_GENERATION/PUBLISHING",
          "published": false,
          "type": "Tile",
          "color": "#D292EF",
          "icon": "mail",
          "link": "",
          "helptext": ""
        }
      ]
    };
  },
  created() {
  },
  mounted() {
  },
  methods: {},
  watch: {}
};
const _hoisted_1$5 = { class: "tw-flex tw-flex-wrap" };
const _hoisted_2$5 = {
  class: "tw-cursor-pointer tw-flex tw-flex-col tw-bg-white tw-justify-center tw-items-center tw-rounded tw-border tw-border-neutral-300 tw-p-6",
  name: "tilebutton"
};
const _hoisted_3$5 = {
  class: "material-symbols-outlined !tw-text-white",
  style: { "transform": "scale(7)" }
};
const _hoisted_4$5 = { class: "tw-font-medium" };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("div", _hoisted_1$5, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.addons, (addon) => {
        return openBlock(), createElementBlock("div", {
          class: "tw-flex tw-flex-col tw-flex-wrap tw-mr-3",
          key: addon.id
        }, [
          createBaseVNode("div", _hoisted_2$5, [
            createBaseVNode("div", {
              class: "tw-mb-10 tw-rounded tw-flex tw-justify-center tw-items-center",
              style: normalizeStyle({ "background-color": addon.color, "width": "16em", "height": "10em" })
            }, [
              createBaseVNode("span", _hoisted_3$5, toDisplayString(addon.icon), 1)
            ], 4),
            createBaseVNode("span", _hoisted_4$5, toDisplayString(_ctx.translate(addon.label)), 1)
          ])
        ]);
      }), 128))
    ])
  ]);
}
const Addons = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5]]);
const _sfc_main$4 = {
  name: "TeamsSetup",
  components: { Parameter, Info },
  props: {
    app: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      fields: [
        {
          param: "client_id",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_ID",
          helptext: "",
          displayed: true
        },
        {
          param: "client_secret",
          type: "password",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_SECRET",
          helptext: "",
          displayed: true
        },
        {
          param: "tenant_id",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_TENANT_ID",
          helptext: "",
          displayed: true
        },
        {
          param: "email",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_EMAIL",
          helptext: "",
          displayed: true
        }
      ]
    };
  },
  created() {
    let config = JSON.parse(this.app.config);
    this.fields.forEach((field) => {
      field.value = config["authentication"][field.param] || "";
    });
  },
  methods: {
    setupTeams() {
      this.loading = true;
      let setup = {};
      const teamsValidationFailed = this.fields.some((field) => {
        let ref_name = "teams_" + field.param;
        if (!this.$refs[ref_name][0].validate()) {
          return true;
        }
        setup[field.param] = field.value;
        return false;
      });
      if (teamsValidationFailed) return;
      settingsService.setupApp(this.app.id, setup).then((response) => {
        if (response.status) {
          Swal.fire({
            icon: "success",
            title: this.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_SUCCESS"),
            text: this.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_SUCCESS_DESC"),
            showConfirmButton: false,
            timer: 3e3
          }).then(() => {
            this.$emit("teamsInstalled");
          });
        } else {
          Swal.fire({
            icon: "error",
            title: this.translate("COM_EMUNDUS_ONBOARD_ERROR_MESSAGE"),
            text: response.message,
            showConfirmButton: false,
            timer: 3e3
          });
        }
        this.loading = false;
      });
    }
  },
  computed: {
    disabledSubmit: function() {
      return this.fields.some((field) => {
        if (!field.optional) {
          return field.value === "" || field.value === 0;
        } else {
          return false;
        }
      });
    }
  }
};
const _hoisted_1$4 = { class: "tw-flex tw-flex-col tw-justify-between tw-w-full tw-font-medium rtl:tw-text-right tw-text-black tw-border tw-border-neutral-300 tw-rounded-[15px] tw-bg-white tw-mb-6 tw-gap-3 tw-p-4" };
const _hoisted_2$4 = { class: "tw-mt-2" };
const _hoisted_3$4 = { class: "tw-text-medium tw-text-sm tw-text-neutral-800" };
const _hoisted_4$4 = { class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6" };
const _hoisted_5$4 = ["disabled"];
const _hoisted_6$3 = { key: 0 };
const _hoisted_7$3 = { key: 1 };
const _hoisted_8$2 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Info = resolveComponent("Info");
  const _component_Parameter = resolveComponent("Parameter");
  return openBlock(), createElementBlock("div", _hoisted_1$4, [
    createVNode(_component_Info, {
      text: "COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_REQUIREMENTS",
      icon: "warning",
      "bg-color": "tw-bg-orange-100",
      "icon-type": "material-icons",
      "icon-color": "tw-text-orange-600",
      class: normalizeClass("tw-mb-4")
    }),
    createBaseVNode("h3", null, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP")), 1),
    createBaseVNode("div", _hoisted_2$4, [
      createBaseVNode("p", _hoisted_3$4, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_DESC")), 1),
      createBaseVNode("div", _hoisted_4$4, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.fields, (field) => {
          return withDirectives((openBlock(), createElementBlock("div", {
            key: field.param,
            class: "tw-w-full"
          }, [
            createVNode(_component_Parameter, {
              ref_for: true,
              ref: "teams_" + field.param,
              "parameter-object": field,
              "help-text-type": "above"
            }, null, 8, ["parameter-object"])
          ])), [
            [vShow, field.displayed]
          ]);
        }), 128)),
        createBaseVNode("div", null, [
          createBaseVNode("button", {
            class: "tw-btn-primary tw-w-fit tw-float-right",
            disabled: $options.disabledSubmit,
            onClick: _cache[0] || (_cache[0] = ($event) => $options.setupTeams())
          }, [
            $props.app.enabled === 0 && $props.app.config === "{}" ? (openBlock(), createElementBlock("span", _hoisted_6$3, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_ADD")), 1)) : (openBlock(), createElementBlock("span", _hoisted_7$3, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE")), 1))
          ], 8, _hoisted_5$4)
        ])
      ])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_8$2)) : createCommentVNode("", true)
  ]);
}
const TeamsSetup = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4]]);
const _sfc_main$3 = {
  name: "DynamicsSetup",
  components: { History, Tabs, Parameter, Info },
  props: {
    app: {
      type: Object,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      tabs: [
        {
          id: 1,
          name: "COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_AUTH",
          icon: "encrypted",
          active: true,
          displayed: true
        },
        {
          id: 2,
          name: "COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_CONFIG",
          icon: "manufacturing",
          active: false,
          displayed: true
        },
        {
          id: 3,
          name: "COM_EMUNDUS_GLOBAL_HISTORY",
          icon: "history",
          active: false,
          displayed: true
        }
      ],
      fields: [
        {
          param: "domain",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_DOMAIN",
          helptext: "",
          displayed: true
        },
        {
          param: "client_id",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_ID",
          helptext: "",
          displayed: true
        },
        {
          param: "client_secret",
          type: "password",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_SECRET",
          helptext: "",
          displayed: true
        },
        {
          param: "tenant_id",
          type: "text",
          placeholder: "",
          value: "",
          label: "COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_TENANT_ID",
          helptext: "",
          displayed: true
        }
      ]
    };
  },
  created() {
    let config = JSON.parse(this.app.config);
    this.fields.forEach((field) => {
      field.value = config["authentication"][field.param] || "";
    });
  },
  methods: {
    setupDynamics() {
      this.loading = true;
      let setup = {};
      const teamsValidationFailed = this.fields.some((field) => {
        let ref_name = "dynamics_" + field.param;
        if (!this.$refs[ref_name][0].validate()) {
          return true;
        }
        setup[field.param] = field.value;
        return false;
      });
      if (teamsValidationFailed) return;
      settingsService.setupApp(this.app.id, setup).then((response) => {
        if (response.status) {
          Swal.fire({
            icon: "success",
            title: this.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_SUCCESS"),
            text: this.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_SUCCESS_DESC"),
            showConfirmButton: false,
            timer: 3e3
          }).then(() => {
            this.$emit("dynamicsInstalled");
          });
        } else {
          Swal.fire({
            icon: "error",
            title: this.translate("COM_EMUNDUS_ONBOARD_ERROR_MESSAGE"),
            text: response.message,
            showConfirmButton: false,
            timer: 3e3
          });
        }
        this.loading = false;
      });
    }
  },
  computed: {
    disabledSubmit: function() {
      return this.fields.some((field) => {
        if (!field.optional) {
          return field.value === "" || field.value === 0;
        } else {
          return false;
        }
      });
    }
  }
};
const _hoisted_1$3 = { class: "tw-relative tw-flex tw-flex-col tw-justify-between tw-w-full tw-font-medium rtl:tw-text-right tw-text-black tw-border tw-border-neutral-300 tw-rounded-[15px] tw-bg-white tw-mb-6 tw-gap-3 tw-p-4" };
const _hoisted_2$3 = { class: "tw-mt-2" };
const _hoisted_3$3 = { class: "tw-text-medium tw-text-sm tw-text-neutral-800" };
const _hoisted_4$3 = {
  key: 0,
  class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6"
};
const _hoisted_5$3 = ["disabled"];
const _hoisted_6$2 = { key: 0 };
const _hoisted_7$2 = { key: 1 };
const _hoisted_8$1 = { key: 1 };
const _hoisted_9$1 = {
  key: 2,
  class: "tw-mt-7 tw-flex tw-flex-col tw-gap-6"
};
const _hoisted_10$1 = {
  key: 3,
  class: "em-page-loader"
};
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Tabs = resolveComponent("Tabs");
  const _component_Parameter = resolveComponent("Parameter");
  const _component_History = resolveComponent("History");
  return openBlock(), createElementBlock("div", _hoisted_1$3, [
    createVNode(_component_Tabs, { tabs: $data.tabs }, null, 8, ["tabs"]),
    createBaseVNode("h3", null, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP")), 1),
    createBaseVNode("div", _hoisted_2$3, [
      createBaseVNode("p", _hoisted_3$3, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_DESC")), 1)
    ]),
    $data.tabs[0].active ? (openBlock(), createElementBlock("div", _hoisted_4$3, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.fields, (field) => {
        return withDirectives((openBlock(), createElementBlock("div", {
          key: field.param,
          class: "tw-w-full"
        }, [
          createVNode(_component_Parameter, {
            ref_for: true,
            ref: "dynamics_" + field.param,
            "parameter-object": field,
            "help-text-type": "above"
          }, null, 8, ["parameter-object"])
        ])), [
          [vShow, field.displayed]
        ]);
      }), 128)),
      createBaseVNode("div", null, [
        createBaseVNode("button", {
          class: "tw-btn-primary tw-w-fit tw-float-right",
          disabled: $options.disabledSubmit,
          onClick: _cache[0] || (_cache[0] = ($event) => $options.setupDynamics())
        }, [
          $props.app.enabled === 0 && $props.app.config === "{}" ? (openBlock(), createElementBlock("span", _hoisted_6$2, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_ADD")), 1)) : (openBlock(), createElementBlock("span", _hoisted_7$2, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE")), 1))
        ], 8, _hoisted_5$3)
      ])
    ])) : createCommentVNode("", true),
    $data.tabs[2].active ? (openBlock(), createElementBlock("div", _hoisted_8$1, [
      createVNode(_component_History, {
        extension: "com_emundus.microsoftdynamics",
        columns: ["title", "message_language_key", "log_date", "user_id", "status", "diff"]
      })
    ])) : createCommentVNode("", true),
    $data.tabs[1].active ? (openBlock(), createElementBlock("div", _hoisted_9$1)) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_10$1)) : createCommentVNode("", true)
  ]);
}
const DynamicsSetup = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3]]);
const _sfc_main$2 = {
  name: "Integration",
  components: { DynamicsSetup, TeamsSetup },
  data() {
    return {
      loading: true,
      apps: [],
      currentApp: null
    };
  },
  created() {
    this.getApps();
  },
  methods: {
    getApps() {
      settingsService.getApps().then((response) => {
        this.apps = response.data;
        this.loading = false;
      });
    },
    toggleEnabled(app, event) {
      let value = event.target.checked ? 1 : 0;
      settingsService.toggleAppEnabled(app.id, value);
    }
  }
};
const _hoisted_1$2 = { class: "em-grid-3-2-1" };
const _hoisted_2$2 = { class: "tw-flex tw-flex-col tw-justify-between tw-w-full tw-font-medium rtl:tw-text-right tw-text-black tw-border tw-border-neutral-300 tw-rounded-[15px] tw-bg-white tw-mb-6 tw-gap-3 tw-p-4" };
const _hoisted_3$2 = { class: "tw-flex tw-items-center tw-justify-between" };
const _hoisted_4$2 = ["src", "alt"];
const _hoisted_5$2 = {
  key: 0,
  class: "tw-flex tw-items-center"
};
const _hoisted_6$1 = { class: "em-toggle" };
const _hoisted_7$1 = ["id", "onUpdate:modelValue", "onClick"];
const _hoisted_8 = { class: "tw-mt-2" };
const _hoisted_9 = { class: "tw-text-medium tw-text-sm tw-text-neutral-800" };
const _hoisted_10 = { key: 0 };
const _hoisted_11 = ["onClick"];
const _hoisted_12 = { key: 1 };
const _hoisted_13 = ["onClick"];
const _hoisted_14 = { key: 0 };
const _hoisted_15 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_TeamsSetup = resolveComponent("TeamsSetup");
  const _component_DynamicsSetup = resolveComponent("DynamicsSetup");
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("div", _hoisted_1$2, [
      !$data.currentApp ? (openBlock(true), createElementBlock(Fragment, { key: 0 }, renderList($data.apps, (app) => {
        return openBlock(), createElementBlock("div", _hoisted_2$2, [
          createBaseVNode("div", _hoisted_3$2, [
            createBaseVNode("img", {
              class: "tw-w-[45px]",
              src: "images/emundus/icons/" + app.icon,
              alt: app.type
            }, null, 8, _hoisted_4$2),
            app.config !== "{}" ? (openBlock(), createElementBlock("div", _hoisted_5$2, [
              createBaseVNode("div", _hoisted_6$1, [
                withDirectives(createBaseVNode("input", {
                  type: "checkbox",
                  "true-value": "1",
                  "false-value": "0",
                  class: "em-toggle-check",
                  id: app.id + "_enabled_input",
                  "onUpdate:modelValue": ($event) => app.enabled = $event,
                  onClick: ($event) => $options.toggleEnabled(app, $event)
                }, null, 8, _hoisted_7$1), [
                  [vModelCheckbox, app.enabled]
                ]),
                _cache[3] || (_cache[3] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
                _cache[4] || (_cache[4] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
              ])
            ])) : createCommentVNode("", true)
          ]),
          createBaseVNode("h4", _hoisted_8, toDisplayString(app.name), 1),
          createBaseVNode("p", _hoisted_9, toDisplayString(app.description), 1),
          app.enabled === 0 && app.config === "{}" ? (openBlock(), createElementBlock("div", _hoisted_10, [
            createBaseVNode("button", {
              class: "tw-btn-tertiary tw-w-full",
              onClick: ($event) => $data.currentApp = app
            }, [
              createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_ADD")), 1)
            ], 8, _hoisted_11)
          ])) : (openBlock(), createElementBlock("div", _hoisted_12, [
            createBaseVNode("button", {
              class: "tw-btn-tertiary tw-w-full",
              onClick: ($event) => $data.currentApp = app
            }, [
              createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE")), 1)
            ], 8, _hoisted_13)
          ]))
        ]);
      }), 256)) : createCommentVNode("", true)
    ]),
    $data.currentApp ? (openBlock(), createElementBlock("div", _hoisted_14, [
      createBaseVNode("div", {
        class: "tw-flex tw-items-center tw-gap-1 tw-cursor-pointer tw-mb-2",
        onClick: _cache[0] || (_cache[0] = ($event) => $data.currentApp = null)
      }, [
        _cache[5] || (_cache[5] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-900" }, "arrow_back", -1)),
        createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_RETOUR")), 1)
      ]),
      $data.currentApp.type === "teams" ? (openBlock(), createBlock(_component_TeamsSetup, {
        key: 0,
        app: $data.currentApp,
        onTeamsInstalled: _cache[1] || (_cache[1] = ($event) => {
          $data.currentApp = null;
          $options.getApps();
        })
      }, null, 8, ["app"])) : $data.currentApp.type === "microsoft_dynamics" ? (openBlock(), createBlock(_component_DynamicsSetup, {
        key: 1,
        app: $data.currentApp,
        onDynamicsInstalled: _cache[2] || (_cache[2] = ($event) => {
          $data.currentApp = null;
          $options.getApps();
        })
      }, null, 8, ["app"])) : createCommentVNode("", true)
    ])) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_15)) : createCommentVNode("", true)
  ]);
}
const Integration = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2]]);
const _sfc_main$1 = {
  name: "SectionComponent",
  components: {
    SettingsContent,
    SidebarMenu,
    EditEmailJoomla,
    WebSecurity,
    Multiselect: script,
    Addons,
    Info
  },
  props: ["activeMenuItem", "activeSectionComponent"],
  methods: {
    handleSectionComponent(item) {
      this.$emit("handleSectionComponent", item);
    },
    handleNeedSaving(needSaving, element) {
      this.$emit("needSaving", needSaving, element);
    }
  }
};
const _hoisted_1$1 = {
  key: 0,
  id: "accordion-collapse",
  class: "flex flex-col justify-between w-full p-5 font-medium rtl:text-right text-black border border-gray-200 rounded-[15px] bg-white mb-3 gap-3 shadow",
  "data-accordion-target": "#accordion-collapse-body-1",
  "aria-expanded": "true",
  "aria-controls": "accordion-collapse-body-1"
};
const _hoisted_2$1 = { class: "flex" };
const _hoisted_3$1 = {
  id: "accordion-collapse-heading-1",
  class: "user-select-none flex flex-row justify-between"
};
const _hoisted_4$1 = {
  id: "Subtile",
  class: "text-2xl user-select-none"
};
const _hoisted_5$1 = {
  key: 0,
  name: "SubMenuContent-componentSection",
  class: "flex flex-col"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Info = resolveComponent("Info");
  return $props.activeMenuItem.type === "sectionComponent" ? (openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", {
      onClick: _cache[0] || (_cache[0] = ($event) => $options.handleSectionComponent($props.activeMenuItem)),
      class: "flex items-center justify-between cursor-pointer"
    }, [
      createBaseVNode("div", _hoisted_2$1, [
        createBaseVNode("h1", _hoisted_3$1, [
          createBaseVNode("span", _hoisted_4$1, toDisplayString(_ctx.translate($props.activeMenuItem.sectionTitle)), 1)
        ])
      ]),
      _cache[1] || (_cache[1] = createBaseVNode("span", {
        class: "material-symbols-outlined scale-150 user-select-none",
        id: "SubtitleArrow",
        name: "SubtitleArrows"
      }, "expand_more", -1))
    ]),
    $props.activeSectionComponent === $props.activeMenuItem.sectionTitle ? (openBlock(), createElementBlock("div", _hoisted_5$1, [
      $props.activeMenuItem.helptext ? (openBlock(), createBlock(_component_Info, {
        key: 0,
        text: $props.activeMenuItem.helptext
      }, null, 8, ["text"])) : createCommentVNode("", true),
      (openBlock(), createBlock(resolveDynamicComponent($props.activeMenuItem.component), mergeProps({
        ref: "content_" + $props.activeMenuItem.name,
        key: "component_" + $props.activeMenuItem.name
      }, $props.activeMenuItem.props, { onNeedSaving: $options.handleNeedSaving }), null, 16, ["onNeedSaving"]))
    ])) : createCommentVNode("", true)
  ])) : createCommentVNode("", true);
}
const SectionComponent = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const menus = [
  {
    "label": "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL",
    "name": "general_settings",
    "icon": "display_settings",
    "type": "JSON",
    "source": "general.js",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_WEB_SECURITY",
    "sectionTitle": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_WEB_SECURITY",
    "name": "web_security_settings",
    "icon": "language",
    "type": "component",
    "component": "WebSecurity",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_EMAIL",
    "sectionTitle": "COM_EMUNDUS_GLOBAL_PARAMS_SECTIONS_MANAG_SERVER_MAIL",
    "name": "email_settings",
    "icon": "email",
    "type": "component",
    "component": "EditEmailJoomla",
    "published": true,
    "helptext": "COM_EMUNDUS_GLOBAL_PARAMS_SECTIONS_EMAIL_HELPTEXT",
    "props": {
      "warning": "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_WARNING"
    }
  },
  {
    "label": "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_CONTENT",
    "name": "content_settings",
    "icon": "notes",
    "type": "JSON",
    "source": "content.js",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_MANAGE_FILES",
    "name": "files_management",
    "icon": "source",
    "type": "JSON",
    "source": "manage-files.js",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_SUPPL_MOD",
    "SectionTitle": "COM_EMUNDUS_GLOBAL_PARAMS_SECTIONS_MANAG_SUPPL_MOD",
    "name": "addon_modules",
    "icon": "dashboard_customize",
    "type": "JSON",
    "format": "Tile",
    "source": "add-on.js",
    "component": "Addons",
    "published": false
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_INTEG",
    "name": "integration",
    "icon": "lan",
    "type": "component",
    "component": "Integration",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS",
    "name": "translate",
    "icon": "translate",
    "type": "JSON",
    "source": "translate.js",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_WORKFLOWS",
    "name": "workflows",
    "icon": "schema",
    "type": "component",
    "component": "WorkflowSettings",
    "published": true,
    "props": {}
  }
];
const _sfc_main = {
  name: "globalSettings",
  components: {
    SectionComponent,
    SettingsContent,
    SidebarMenu,
    EditEmailJoomla,
    WebSecurity,
    Multiselect: script,
    Addons,
    Integration,
    Info,
    WorkflowSettings
  },
  props: {
    actualLanguage: {
      type: String,
      default: "fr"
    },
    coordinatorAccess: {
      type: Number,
      default: 1
    },
    manyLanguages: {
      type: Number,
      default: 1
    },
    helptext: {
      type: String,
      default: ""
    }
  },
  data: () => ({
    saving: false,
    endSaving: false,
    loading: null,
    needSaving: false,
    activeSectionComponent: 0,
    activeMenuItem: null,
    activeMenu: null,
    keyMenu: 0,
    clicker: 0,
    activeSection: null,
    urlRedirectMenu: false,
    urlRedirectSection: false,
    Menus: [],
    Sections: [],
    menusList: []
  }),
  setup() {
    const settingsStore = useSettingsStore();
    return {
      settingsStore
    };
  },
  created() {
    this.menusList = menus;
  },
  mounted() {
    if (sessionStorage.getItem("goToMenu")) {
      this.findMenu(sessionStorage.getItem("goToMenu"));
      this.urlRedirectMenu = true;
      sessionStorage.removeItem("goToMenu");
    }
    if (sessionStorage.getItem("goToSection")) {
      this.urlRedirectSection = true;
    }
  },
  methods: {
    handleNeedSaving(needSaving) {
      this.settingsStore.updateNeedSaving(needSaving);
    },
    GetList(list, type) {
      if (type === "menus") {
        this.Menus = list;
      } else if (type === "sections") {
        this.Sections = list;
      }
    },
    handleMenu(item) {
      if (this.settingsStore.needSaving) {
        Swal$1.fire({
          title: this.translate("COM_EMUNDUS_ONBOARD_WARNING"),
          text: this.activeMenuItem.component === "EditEmailJoomla" ? this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_UNSAVED_MUST_TEST_MAIL") : this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_UNSAVED"),
          showCancelButton: true,
          confirmButtonText: this.activeMenuItem.component === "EditEmailJoomla" ? this.translate("COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_BT") : this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE"),
          cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL_UPDATES"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            cancelButton: "em-swal-cancel-button",
            confirmButton: "em-swal-confirm-button"
          }
        }).then((result) => {
          this.handleNeedSaving(false);
          if (result.value) {
            this.saveSection(this.activeMenuItem, item);
          } else {
            this.activeMenuItem = item;
          }
        });
      } else {
        this.activeMenuItem = item;
      }
    },
    saveSection(menu, item = null) {
      let vue_component = this.$refs["content_" + menu.name];
      if (Array.isArray(vue_component)) {
        vue_component = vue_component[0];
      }
      if (typeof vue_component.saveMethod !== "function") {
        console.error("The component " + menu.name + " does not have a saveMethod function");
        return;
      }
      vue_component.saveMethod().then((response) => {
        if (response === true) {
          if (item !== null) {
            this.activeMenuItem = item;
          }
        }
      });
    },
    handleSectionComponent(element) {
      this.activeSectionComponent = this.activeSectionComponent === element.sectionTitle ? null : element.sectionTitle;
    },
    findMenu(menu) {
      for (let index in this.Menus) {
        if (this.Menus[index].name === menu) {
          this.activeMenu = index;
          break;
        }
      }
    },
    findSection(section) {
      for (let index in this.Sections) {
        if (this.Sections[index].name === section) {
          this.activeSection = index;
          break;
        }
      }
    }
  },
  watch: {
    activeMenuItem: function(val, oldVal) {
      if (oldVal !== null) {
        sessionStorage.setItem("tchooz_settings_selected_section/" + document.location.hostname, null);
      }
    },
    activeMenu: function(val) {
      sessionStorage.setItem("tchooz_selected_menu/settings_menus/" + document.location.hostname, val);
      this.keyMenu++;
    },
    activeSection: function(val) {
      sessionStorage.setItem("tchooz_settings_selected_section/" + document.location.hostname, val);
      this.clicker++;
    },
    Sections: function() {
      if (this.urlRedirectSection) {
        this.findSection(sessionStorage.getItem("goToSection"));
        this.urlRedirectSection = false;
      }
    }
  }
};
const _hoisted_1 = { class: "tw-w-full tw-flex tw-gap-8" };
const _hoisted_2 = {
  key: 0,
  class: "tw-overflow-hidden tw-w-full tw-pt-6 tw-pr-8 tw-pb-3 tw-pl-0"
};
const _hoisted_3 = { class: "tw-text-2xl tw-pl-1 tw-font-semibold tw-text-profile-full tw-mb-3" };
const _hoisted_4 = { class: "material-symbols-outlined tw-scale-150 tw-text-profile-full tw-me-2" };
const _hoisted_5 = {
  key: 1,
  id: "accordion-collapse"
};
const _hoisted_6 = { key: 2 };
const _hoisted_7 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_SidebarMenu = resolveComponent("SidebarMenu");
  const _component_SettingsContent = resolveComponent("SettingsContent");
  const _component_SectionComponent = resolveComponent("SectionComponent");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    (openBlock(), createBlock(_component_SidebarMenu, {
      key: _ctx.keyMenu,
      "menus-list": _ctx.menusList,
      id: "settings_menus",
      onListMenus: $options.GetList,
      onMenuSelected: $options.handleMenu
    }, null, 8, ["menus-list", "onListMenus", "onMenuSelected"])),
    _ctx.activeMenuItem ? (openBlock(), createElementBlock("div", _hoisted_2, [
      createBaseVNode("h1", _hoisted_3, [
        createBaseVNode("span", _hoisted_4, toDisplayString(_ctx.activeMenuItem.icon), 1),
        createTextVNode(" " + toDisplayString(_ctx.translate(_ctx.activeMenuItem.label)), 1)
      ]),
      createBaseVNode("div", null, [
        _ctx.activeMenuItem.type === "JSON" ? (openBlock(), createBlock(_component_SettingsContent, {
          ref: "content_" + _ctx.activeMenuItem.name,
          key: "json_" + _ctx.activeMenuItem.name + _ctx.clicker,
          json_source: "settings/sections/" + _ctx.activeMenuItem.source,
          onNeedSaving: $options.handleNeedSaving,
          onListSections: $options.GetList,
          class: normalizeClass(_ctx.activeMenuItem.format === "Tile" ? "tw-flex tw-flex-wrap tw-justify-between" : "")
        }, null, 8, ["json_source", "onNeedSaving", "onListSections", "class"])) : _ctx.activeMenuItem.type === "sectionComponent" ? (openBlock(), createElementBlock("div", _hoisted_5, [
          createVNode(_component_SectionComponent, {
            activeMenuItem: _ctx.activeMenuItem,
            activeSectionComponent: _ctx.activeSectionComponent,
            onHandleSectionComponent: $options.handleSectionComponent,
            onNeedSaving: $options.handleNeedSaving
          }, null, 8, ["activeMenuItem", "activeSectionComponent", "onHandleSectionComponent", "onNeedSaving"])
        ])) : (openBlock(), createElementBlock("div", _hoisted_6, [
          (openBlock(), createBlock(resolveDynamicComponent(_ctx.activeMenuItem.component), mergeProps({
            ref: "content_" + _ctx.activeMenuItem.name,
            key: "component_" + _ctx.activeMenuItem.name
          }, _ctx.activeMenuItem.props, { onNeedSaving: $options.handleNeedSaving }), null, 16, ["onNeedSaving"]))
        ]))
      ])
    ])) : createCommentVNode("", true),
    _ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_7)) : createCommentVNode("", true)
  ]);
}
const Settings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Settings as default
};
