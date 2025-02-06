import { _ as _export_sfc, U as script, s as settingsService, S as Swal, u as useGlobalStore, a2 as reactive, r as resolveComponent, c as createElementBlock, o as openBlock, a as createCommentVNode, d as createBaseVNode, f as normalizeClass, D as createTextVNode, t as toDisplayString, h as withDirectives, b as createBlock, B as vModelSelect, F as Fragment, e as renderList, w as withCtx, R as vModelText, Z as vModelRadio, G as vModelCheckbox, W as vModelDynamic, i as withModifiers, z as mergeProps, H as toHandlers } from "./app_emundus.js";
import { D as DatePicker } from "./index.js";
const _sfc_main = {
  name: "Parameter",
  components: { DatePicker, Multiselect: script },
  props: {
    parameterObject: {
      type: Object,
      required: true
    },
    multiselectOptions: {
      type: Object,
      required: false,
      default: () => {
        return {
          options: [],
          noOptions: false,
          multiple: true,
          taggable: false,
          searchable: true,
          internalSearch: true,
          asyncRoute: "",
          optionsLimit: 100,
          optionsPlaceholder: "COM_EMUNDUS_MULTISELECT_ADDKEYWORDS",
          selectLabel: "PRESS_ENTER_TO_SELECT",
          selectGroupLabel: "PRESS_ENTER_TO_SELECT_GROUP",
          selectedLabel: "SELECTED",
          deselectedLabel: "PRESS_ENTER_TO_REMOVE",
          deselectGroupLabel: "PRESS_ENTER_TO_DESELECT_GROUP",
          noOptionsText: "COM_EMUNDUS_MULTISELECT_NOKEYWORDS",
          noResultsText: "COM_EMUNDUS_MULTISELECT_NORESULTS",
          // Can add tag validations (ex. email, phone, regex)
          tagValidations: [],
          tagRegex: ""
        };
      }
    },
    helpTextType: {
      type: String,
      required: false,
      default: "icon"
    }
  },
  emits: ["valueUpdated", "needSaving"],
  data() {
    return {
      initValue: null,
      value: null,
      valueSecondary: null,
      parameter: {},
      parameterSecondary: {},
      multiOptions: [],
      isLoading: false,
      errors: {},
      abortController: null,
      debounceTimeout: null,
      actualLanguage: "fr-FR"
    };
  },
  async created() {
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getShortLang;
    this.parameter = this.parameterObject;
    if (this.parameter.type === "multiselect") {
      if (this.$props.multiselectOptions.asyncRoute) {
        await this.asyncFind("");
      } else {
        this.multiOptions = this.$props.multiselectOptions.options;
      }
      if (!this.multiselectOptions.multiple) {
        this.value = this.multiOptions.find((option) => option.value === this.parameter.value);
      } else {
        if (this.parameter.value && this.parameter.value.length > 0 && typeof this.parameter.value[0] !== "object") {
          this.value = this.multiOptions.filter((option) => this.parameter.value.includes(option.value));
        } else {
          this.value = this.parameter.value;
        }
      }
      if (!this.value) {
        this.value = [];
      }
    } else if (this.parameter) {
      this.value = this.parameter.value;
    }
    if (this.parameter.splitField) {
      if (this.value) {
        let splitValue = this.value.split(this.parameter.splitChar);
        this.value = splitValue[0];
        this.valueSecondary = splitValue[1];
      }
      this.parameterSecondary = reactive({ ...this.parameter });
      if (this.parameter.secondParameterType) {
        this.parameterSecondary.type = this.parameter.secondParameterType;
      }
      if (this.parameter.secondParameterOptions) {
        this.parameterSecondary.options = this.parameter.secondParameterOptions;
      }
      this.parameterSecondary.splitField = false;
      this.parameterSecondary.hideLabel = true;
      if (this.parameter.secondParameterDefault && (!this.valueSecondary || this.valueSecondary === "")) {
        this.parameterSecondary.value = this.parameter.secondParameterDefault;
      } else {
        this.parameterSecondary.value = this.valueSecondary;
      }
    }
    this.initValue = this.value;
  },
  methods: {
    displayHelp(message) {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_SWAL_HELP_TITLE"),
        html: this.translate(message),
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
    // MULTISELECT
    addOption(newOption) {
      if (this.multiselectOptions.taggable) {
        if (this.multiOptions.find((option2) => option2.name === newOption)) {
          return false;
        }
        if (this.$props.multiselectOptions.tagValidations.length > 0) {
          let valid = false;
          this.$props.multiselectOptions.tagValidations.forEach((validation) => {
            switch (validation) {
              case "email":
                valid = this.validateEmail(newOption);
                break;
              case "regex":
                valid = new RegExp(this.$props.multiselectOptions.tagRegex).test(newOption);
                break;
            }
          });
          if (!valid) {
            return false;
          }
        }
        const option = {
          name: newOption,
          code: newOption
        };
        this.multiOptions.push(option);
        this.value.push(option);
        this.parameter.value.push(option.code);
      }
    },
    checkAddOption(event) {
      if (this.multiselectOptions.taggable) {
        event.preventDefault();
        let added = this.addOption(event.srcElement.value);
        if (!added) {
          event.srcElement.value = "";
        }
      }
    },
    checkComma(event) {
      if (this.$props.multiselectOptions.tagValidations.includes("email") && event && event.key === "," && this.multiselectOptions.taggable) {
        this.addOption(event.srcElement.value.replace(",", ""));
      }
    },
    async asyncFind(search_query) {
      if (this.$props.multiselectOptions.asyncRoute) {
        return new Promise((resolve, reject) => {
          if (this.abortController) {
            this.abortController.abort();
          }
          this.abortController = new AbortController();
          const signal = this.abortController.signal;
          clearTimeout(this.debounceTimeout);
          this.debounceTimeout = setTimeout(() => {
            this.isLoading = true;
            let data = {
              search_query,
              limit: this.$props.multiselectOptions.optionsLimit
            };
            settingsService.getAsyncOptions(this.$props.multiselectOptions.asyncRoute, data, { signal }).then((response) => {
              this.multiOptions = response.data;
              this.isLoading = false;
              resolve(true);
            });
          }, 500);
        });
      }
    },
    // VALIDATIONS
    validate() {
      if (this.parameter.value === "" && this.parameter.optional === true) {
        delete this.errors[this.parameter.param];
        return true;
      } else if (this.parameter.value === "") {
        this.errors[this.parameter.param] = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL";
        return false;
      } else {
        if (this.parameter.type === "email") {
          if (!this.validateEmail(this.parameter.value)) {
            this.errors[this.parameter.param] = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NO";
            return false;
          }
        }
        delete this.errors[this.parameter.param];
        return true;
      }
    },
    checkValue(parameter) {
      if (parameter.type === "number") {
        if (this.value > parameter.max) {
          this.value = parameter.max;
        }
      } else {
        this.validate(parameter);
      }
    },
    clearPassword(parameter) {
      if (parameter.type === "password") {
        this.value = "";
      }
    },
    regroupValue(parameter) {
      this.valueSecondary = parameter.value;
    },
    validateEmail(email) {
      let res = /^[\w.-]+@([\w-]+\.)+[\w-]{2,4}$/;
      return res.test(email);
    }
    //
  },
  watch: {
    value: {
      handler: function(val, oldVal) {
        if (this.parameter.type !== "multiselect" || this.parameter.type === "multiselect" && !this.multiselectOptions.taggable) {
          this.parameter.value = val;
        }
        if (this.parameter.splitField) {
          this.parameter.concatValue = val + this.parameter.splitChar + this.valueSecondary;
        }
        this.$emit("valueUpdated", this.parameter, oldVal, val);
        if (val !== oldVal && val !== this.initValue) {
          let valid = true;
          if (["text", "email", "number", "password", "textarea"].includes(this.parameter.type)) {
            valid = this.validate();
          }
          this.$emit("needSaving", true, this.parameter, valid);
        }
        if (val == this.initValue) {
          this.$emit("needSaving", false, this.parameter, true);
        }
      },
      deep: true
    },
    valueSecondary: {
      handler: function(val, oldVal) {
        this.parameter.concatValue = this.value + this.parameter.splitChar + val;
      },
      deep: true
    }
  },
  computed: {
    isInput() {
      return ["text", "email", "number", "password"].includes(this.parameter.type) && this.parameter.displayed && this.parameter.editable !== "semi";
    },
    paramId() {
      return "param_" + this.parameter.param + "_" + Math.floor(Math.random() * 100);
    },
    paramName() {
      return "param_" + this.parameter.param + "[]";
    }
  }
};
const _hoisted_1 = ["for"];
const _hoisted_2 = {
  key: 0,
  class: "tw-ml-1 tw-text-red-600"
};
const _hoisted_3 = {
  key: 1,
  class: "tw-text-base tw-text-neutral-600"
};
const _hoisted_4 = { key: 0 };
const _hoisted_5 = ["title"];
const _hoisted_6 = ["id", "disabled"];
const _hoisted_7 = ["value"];
const _hoisted_8 = ["id", "placeholder", "maxlength", "readonly"];
const _hoisted_9 = { key: 4 };
const _hoisted_10 = {
  "data-toggle": "buttons",
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_11 = ["for"];
const _hoisted_12 = ["name", "id", "checked"];
const _hoisted_13 = ["for"];
const _hoisted_14 = ["name", "id", "checked"];
const _hoisted_15 = { key: 5 };
const _hoisted_16 = {
  "data-toggle": "radio_buttons",
  class: "tw-grid tw-grid-cols-1 md:tw-grid-cols-2 lg:tw-grid-cols-2 tw-gap-4"
};
const _hoisted_17 = ["name", "id", "value", "checked"];
const _hoisted_18 = ["for"];
const _hoisted_19 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_20 = ["src", "alt"];
const _hoisted_21 = {
  key: 6,
  class: "tw-flex tw-items-center"
};
const _hoisted_22 = { class: "em-toggle" };
const _hoisted_23 = ["id"];
const _hoisted_24 = ["for"];
const _hoisted_25 = {
  key: 0,
  class: "material-symbols-outlined tw-mr-1 tw-text-neutral-900"
};
const _hoisted_26 = ["type", "max", "placeholder", "id", "maxlength", "readonly"];
const _hoisted_27 = ["value", "id"];
const _hoisted_28 = { key: 9 };
const _hoisted_29 = {
  key: 10,
  class: "tw-ml-2"
};
const _hoisted_30 = ["id"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  const _component_DatePicker = resolveComponent("DatePicker");
  const _component_Parameter = resolveComponent("Parameter", true);
  return openBlock(), createElementBlock("div", null, [
    $data.parameter.hideLabel !== true ? (openBlock(), createElementBlock("label", {
      key: 0,
      for: $options.paramId,
      class: normalizeClass(["tw-flex tw-font-semibold tw-items-end", $data.parameter.helptext && $props.helpTextType === "above" ? "tw-mb-0" : ""])
    }, [
      createTextVNode(toDisplayString(_ctx.translate($data.parameter.label)) + " ", 1),
      $data.parameter.optional !== true ? (openBlock(), createElementBlock("span", _hoisted_2, "*")) : createCommentVNode("", true),
      $data.parameter.helptext && $props.helpTextType === "icon" ? (openBlock(), createElementBlock("span", {
        key: 1,
        class: "material-symbols-outlined tw-ml-1 tw-cursor-pointer tw-text-neutral-600",
        onClick: _cache[0] || (_cache[0] = ($event) => $options.displayHelp($data.parameter.helptext))
      }, "help_outline")) : createCommentVNode("", true)
    ], 10, _hoisted_1)) : createCommentVNode("", true),
    $data.parameter.helptext && $props.helpTextType === "above" ? (openBlock(), createElementBlock("span", _hoisted_3, toDisplayString(_ctx.translate($data.parameter.helptext)), 1)) : createCommentVNode("", true),
    createBaseVNode("div", {
      name: "input-field",
      class: normalizeClass(["tw-flex tw-items-center", { "input-split-field": $data.parameter.splitField, "input-split-field-select": $data.parameter.splitField && $data.parameter.secondParameterType === "select", "tw-gap-2": $data.parameter.splitField && $data.parameter.secondParameterType !== "select" }])
    }, [
      $data.parameter.icon ? (openBlock(), createElementBlock("div", _hoisted_4, [
        createBaseVNode("span", {
          title: _ctx.translate($data.parameter.label),
          class: "material-symbols-outlined tw-mr-2 tw-text-neutral-900"
        }, toDisplayString($data.parameter.icon), 9, _hoisted_5)
      ])) : createCommentVNode("", true),
      $data.parameter.type === "select" ? withDirectives((openBlock(), createElementBlock("select", {
        key: 1,
        class: normalizeClass(["dropdown-toggle w-select !tw-mb-0 tw-min-w-[30%]", $data.errors[$data.parameter.param] ? "tw-rounded-lg !tw-border-red-500" : ""]),
        id: $options.paramId,
        "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.value = $event),
        disabled: $data.parameter.editable === false
      }, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.parameter.options, (option) => {
          return openBlock(), createElementBlock("option", {
            key: option.value,
            value: option.value
          }, toDisplayString(_ctx.translate(option.label)), 9, _hoisted_7);
        }), 128))
      ], 10, _hoisted_6)), [
        [vModelSelect, $data.value]
      ]) : $data.parameter.type === "multiselect" ? (openBlock(), createBlock(_component_multiselect, {
        id: $options.paramId,
        modelValue: $data.value,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.value = $event),
        class: normalizeClass([$props.multiselectOptions.noOptions ? "no-options" : "", "tw-cursor-pointer"]),
        label: $props.multiselectOptions.label ? $props.multiselectOptions.label : "name",
        "track-by": $props.multiselectOptions.trackBy ? $props.multiselectOptions.trackBy : "code",
        options: $data.multiOptions,
        "options-limit": $props.multiselectOptions.optionsLimit ? $props.multiselectOptions.optionsLimit : 100,
        multiple: $props.multiselectOptions.multiple ? $props.multiselectOptions.multiple : false,
        taggable: $props.multiselectOptions.taggable ? $props.multiselectOptions.taggable : false,
        placeholder: _ctx.translate($data.parameter.placeholder),
        searchable: $props.multiselectOptions.searchable ? $props.multiselectOptions.searchable : true,
        tagPlaceholder: _ctx.translate($props.multiselectOptions.optionsPlaceholder),
        key: $options.paramId,
        selectLabel: _ctx.translate($props.multiselectOptions.selectLabel),
        selectGroupLabel: _ctx.translate($props.multiselectOptions.selectGroupLabel),
        selectedLabel: _ctx.translate($props.multiselectOptions.selectedLabel),
        "deselect-label": _ctx.translate($props.multiselectOptions.deselectedLabel),
        deselectGroupLabel: _ctx.translate($props.multiselectOptions.deselectGroupLabel),
        "preserve-search": true,
        "internal-search": $props.multiselectOptions.internalSearch ? $props.multiselectOptions.internalSearch : true,
        loading: $data.isLoading,
        onTag: $options.addOption,
        onKeyup: _cache[3] || (_cache[3] = ($event) => $options.checkComma($event)),
        onFocusout: _cache[4] || (_cache[4] = ($event) => $options.checkAddOption($event)),
        onSearchChange: $options.asyncFind
      }, {
        noOptions: withCtx(() => [
          createTextVNode(toDisplayString(_ctx.translate($props.multiselectOptions.noOptionsText)), 1)
        ]),
        noResult: withCtx(() => [
          createTextVNode(toDisplayString(_ctx.translate($props.multiselectOptions.noResultsText)), 1)
        ]),
        _: 1
      }, 8, ["id", "modelValue", "class", "label", "track-by", "options", "options-limit", "multiple", "taggable", "placeholder", "searchable", "tagPlaceholder", "selectLabel", "selectGroupLabel", "selectedLabel", "deselect-label", "deselectGroupLabel", "internal-search", "loading", "onTag", "onSearchChange"])) : $data.parameter.type === "textarea" ? withDirectives((openBlock(), createElementBlock("textarea", {
        key: 3,
        id: $options.paramId,
        "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.value = $event),
        class: normalizeClass(["!mb-0", $data.errors[$data.parameter.param] ? "tw-rounded-lg !tw-border-red-500" : ""]),
        placeholder: _ctx.translate($data.parameter.placeholder),
        maxlength: $data.parameter.maxlength,
        readonly: $data.parameter.editable === false
      }, "      ", 10, _hoisted_8)), [
        [vModelText, $data.value]
      ]) : $data.parameter.type === "yesno" ? (openBlock(), createElementBlock("div", _hoisted_9, [
        createBaseVNode("fieldset", _hoisted_10, [
          createBaseVNode("label", {
            for: $options.paramId + "_input_0",
            class: normalizeClass([[$data.value == 0 ? "tw-bg-red-700" : "tw-bg-white tw-border-neutral-500 hover:tw-border-red-700"], "tw-w-60 tw-h-10 tw-p-2.5 tw-rounded-lg tw-border tw-justify-center tw-items-center tw-gap-2.5 tw-inline-flex"])
          }, [
            withDirectives(createBaseVNode("input", {
              "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.value = $event),
              type: "radio",
              class: "fabrikinput !tw-hidden",
              name: $options.paramName,
              id: $options.paramId + "_input_0",
              value: "0",
              checked: $data.value === 0
            }, null, 8, _hoisted_12), [
              [vModelRadio, $data.value]
            ]),
            createBaseVNode("span", {
              class: normalizeClass([$data.value == 0 ? "tw-text-white" : "tw-text-red-700"])
            }, toDisplayString(_ctx.translate("JNO")), 3)
          ], 10, _hoisted_11),
          createBaseVNode("label", {
            for: $options.paramId + "_input_1",
            class: normalizeClass([[$data.value == 1 ? "tw-bg-green-700" : "tw-bg-white tw-border-neutral-500 hover:tw-border-green-700"], "tw-w-60 tw-h-10 tw-p-2.5 tw-rounded-lg tw-border tw-justify-center tw-items-center tw-gap-2.5 tw-inline-flex"])
          }, [
            withDirectives(createBaseVNode("input", {
              "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.value = $event),
              type: "radio",
              class: "fabrikinput !tw-hidden",
              name: $options.paramName,
              id: $options.paramId + "_input_1",
              value: "1",
              checked: $data.value === 1
            }, null, 8, _hoisted_14), [
              [vModelRadio, $data.value]
            ]),
            createBaseVNode("span", {
              class: normalizeClass([$data.value == 1 ? "tw-text-white" : "tw-text-green-700"])
            }, toDisplayString(_ctx.translate("JYES")), 3)
          ], 10, _hoisted_13)
        ])
      ])) : $data.parameter.type === "radiobutton" ? (openBlock(), createElementBlock("div", _hoisted_15, [
        createBaseVNode("fieldset", _hoisted_16, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.parameter.options, (option) => {
            return openBlock(), createElementBlock("div", {
              key: option.value,
              class: "fabrikgrid_radio"
            }, [
              withDirectives(createBaseVNode("input", {
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.value = $event),
                type: "radio",
                class: normalizeClass(["fabrikinput", $data.parameter.hideRadio ? "!tw-hidden" : ""]),
                name: $options.paramName,
                id: $options.paramId + "_input_" + option.value,
                value: option.value,
                checked: $data.value === option.value
              }, null, 10, _hoisted_17), [
                [vModelRadio, $data.value]
              ]),
              createBaseVNode("label", {
                for: $options.paramId + "_input_" + option.value
              }, [
                createBaseVNode("span", _hoisted_19, [
                  option.img ? (openBlock(), createElementBlock("img", {
                    key: 0,
                    src: "/images/emundus/icons/" + option.img,
                    alt: option.altImg,
                    style: { "width": "16px" }
                  }, null, 8, _hoisted_20)) : createCommentVNode("", true),
                  createTextVNode(" " + toDisplayString(_ctx.translate(option.label)), 1)
                ])
              ], 8, _hoisted_18)
            ]);
          }), 128))
        ])
      ])) : $data.parameter.type === "toggle" ? (openBlock(), createElementBlock("div", _hoisted_21, [
        createBaseVNode("div", _hoisted_22, [
          withDirectives(createBaseVNode("input", {
            type: "checkbox",
            "true-value": "1",
            "false-value": "0",
            class: "em-toggle-check",
            id: $options.paramId + "_input",
            "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => $data.value = $event)
          }, null, 8, _hoisted_23), [
            [vModelCheckbox, $data.value]
          ]),
          _cache[15] || (_cache[15] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
          _cache[16] || (_cache[16] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
        ]),
        createBaseVNode("label", {
          for: $options.paramId + "_input",
          class: "tw-ml-2 !tw-mb-0 tw-font-bold tw-cursor-pointer tw-flex tw-items-center"
        }, [
          $data.parameter.iconLabel ? (openBlock(), createElementBlock("span", _hoisted_25, toDisplayString($data.parameter.iconLabel), 1)) : createCommentVNode("", true),
          createTextVNode(" " + toDisplayString(_ctx.translate($data.parameter.label)), 1)
        ], 8, _hoisted_24)
      ])) : $options.isInput ? withDirectives((openBlock(), createElementBlock("input", {
        key: 7,
        type: $data.parameter.type,
        class: normalizeClass(["form-control !tw-mb-0 tw-min-w-[30%]", $data.errors[$data.parameter.param] ? "tw-rounded-lg !tw-border-red-500" : ""]),
        max: $data.parameter.type === "number" ? $data.parameter.max : null,
        min: void 0,
        placeholder: _ctx.translate($data.parameter.placeholder),
        id: $options.paramId,
        "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => $data.value = $event),
        maxlength: $data.parameter.maxlength,
        readonly: $data.parameter.editable === false,
        onChange: _cache[11] || (_cache[11] = withModifiers(($event) => $options.checkValue($data.parameter), ["self"])),
        onFocusin: _cache[12] || (_cache[12] = ($event) => $options.clearPassword($data.parameter))
      }, null, 42, _hoisted_26)), [
        [vModelDynamic, $data.value]
      ]) : $data.parameter.type === "datetime" ? (openBlock(), createBlock(_component_DatePicker, {
        key: 8,
        id: $options.paramId,
        modelValue: $data.value,
        "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => $data.value = $event),
        keepVisibleOnInput: true,
        popover: { visibility: "focus", placement: "right" },
        rules: { minutes: { interval: 10 } },
        "time-accuracy": 2,
        mode: "dateTime",
        is24hr: "",
        "hide-time-header": "",
        "title-position": "left",
        "input-debounce": 500,
        locale: $data.actualLanguage
      }, {
        default: withCtx(({ inputValue, inputEvents }) => [
          createBaseVNode("input", mergeProps({ value: inputValue }, toHandlers(inputEvents, true), {
            class: "form-control fabrikinput tw-w-full",
            id: $options.paramId + "_input"
          }), null, 16, _hoisted_27)
        ]),
        _: 1
      }, 8, ["id", "modelValue", "locale"])) : createCommentVNode("", true),
      $data.parameter.splitField ? (openBlock(), createElementBlock("span", _hoisted_28, toDisplayString($data.parameter.splitChar), 1)) : createCommentVNode("", true),
      $data.parameter.endText ? (openBlock(), createElementBlock("span", _hoisted_29, toDisplayString(_ctx.translate($data.parameter.endText)), 1)) : createCommentVNode("", true),
      $data.parameter.splitField && $data.parameterSecondary ? (openBlock(), createBlock(_component_Parameter, {
        key: 11,
        class: normalizeClass("tw-w-96"),
        "parameter-object": $data.parameterSecondary,
        "multiselect-options": $props.multiselectOptions,
        onValueUpdated: _cache[14] || (_cache[14] = ($event) => $options.regroupValue($data.parameterSecondary))
      }, null, 8, ["parameter-object", "multiselect-options"])) : createCommentVNode("", true)
    ], 2),
    $data.errors[$data.parameter.param] && !["yesno", "toggle"].includes($data.parameter.type) && $data.parameter.displayed ? (openBlock(), createElementBlock("div", {
      key: 2,
      class: normalizeClass(["tw-absolute tw-mt-1 tw-text-red-600 tw-min-h-[24px]", $data.errors[$data.parameter.param] ? "tw-opacity-100 " : "tw-opacity-0"]),
      id: "error-message-" + $data.parameter.param
    }, toDisplayString(_ctx.translate($data.errors[$data.parameter.param])), 11, _hoisted_30)) : createCommentVNode("", true)
  ]);
}
const Parameter = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Parameter as P
};
