<template>
  <div>
    <!-- LABEL -->
    <label v-if="parameter.hideLabel !== true"
           :for="'param_' + parameter.param"
           class="tw-flex tw-font-semibold tw-items-end">
      {{ translate(parameter.label) }}

      <span v-if="parameter.optional"
            :class="'tw-italic tw-text-[#727272] tw-text-xs tw-ml-1'">
        {{ translate('COM_EMUNDUS_OPTIONAL') }}
      </span>
      <span v-else-if="parameter.optional === 0" class="tw-text-red-600">*</span>

      <span v-if="parameter.helptext"
            class="material-symbols-outlined tw-ml-1 tw-cursor-pointer tw-text-neutral-600"
            @click="displayHelp(parameter.helptext)">help_outline</span>
    </label>

    <div name="input-field" class="tw-flex tw-items-center tw-gap-2">
      <!-- SELECT -->
      <select v-if="parameter.type === 'select'"
              class="dropdown-toggle w-select !tw-mb-0"
              :class="errors[parameter.param] ?'tw-rounded-lg !tw-border-red-500':''"
              :id="paramId"
              v-model="value"
              :disabled="parameter.editable === false">
        <option v-for="option in parameter.options" :key="option.value" :value="option.value">
          {{ translate(option.label) }}
        </option>
      </select>

      <!-- MULTISELECT -->
      <multiselect
          v-else-if="parameter.type === 'multiselect'"
          :id="paramId"
          v-model="value"
          :class="[multiselectOptions.noOptions ? 'no-options' : '','tw-cursor-pointer']"
          :label="multiselectOptions.label ? multiselectOptions.label : 'name'"
          :track-by="multiselectOptions.trackBy ? multiselectOptions.trackBy : 'code'"
          :options="multiOptions"
          :multiple="multiselectOptions.multiple"
          :taggable="multiselectOptions.taggable"
          :placeholder="translate(parameter.placeholder)"
          :searchable="multiselectOptions.searchable"
          @tag="addOption"
          :tagPlaceholder="translate(multiselectOptions.optionsPlaceholder)"
          :key="parameter.value !== undefined ? parameter.value.length : 0"
          :selectLabel="translate(multiselectOptions.selectLabel)"
          :selectGroupLabel="translate(multiselectOptions.selectGroupLabel)"
          :selectedLabel="translate(multiselectOptions.selectedLabel)"
          :deselect-label="translate(multiselectOptions.deselectedLabel)"
          :deselectGroupLabel="translate(multiselectOptions.deselectGroupLabel)"
          :preserve-search="true"
          @keyup="checkComma($event)"
          @focusout="checkAddOption($event)"
      >
        <template #noOptions>{{ translate(multiselectOptions.noOptionsText) }}</template>
      </multiselect>

      <!-- TEXTAREA -->
      <textarea v-else-if="parameter.type === 'textarea'"
                :id="paramId"
                v-model="value"
                class="!mb-0"
                :class="errors[parameter.param] ?'tw-rounded-lg !tw-border-red-500':''"
                :maxlength="parameter.maxlength"
                :readonly="parameter.editable === false">
      </textarea>

      <!-- YESNO -->
      <div v-else-if="parameter.type === 'yesno'">
        <fieldset data-toggle="buttons" class="tw-flex tw-items-center tw-gap-2">
          <label :for="paramId + '_input_0'"
                 :class="[value == 0 ? 'tw-bg-red-700' : 'tw-bg-white tw-border-neutral-500 hover:tw-border-red-700']"
                 class="tw-w-60 tw-h-10 tw-p-2.5 tw-rounded-lg tw-border tw-justify-center tw-items-center tw-gap-2.5 tw-inline-flex">
            <input v-model="value" type="radio" class="fabrikinput !tw-hidden" :name="paramName"
                   :id="paramId+ '_input_0'"
                   value="0" :checked="value === 0">
            <span :class="[value == 0 ? 'tw-text-white' : 'tw-text-red-700']">{{ translate('JNO') }}</span>
          </label>

          <label :for="paramId + '_input_1'"
                 :class="[value == 1 ? 'tw-bg-green-700' : 'tw-bg-white tw-border-neutral-500 hover:tw-border-green-700']"
                 class="tw-w-60 tw-h-10 tw-p-2.5 tw-rounded-lg tw-border tw-justify-center tw-items-center tw-gap-2.5 tw-inline-flex">
            <input v-model="value" type="radio" class="fabrikinput !tw-hidden" :name="paramName"
                   :id="paramId+ '_input_1'"
                   value="1" :checked="value === 1">
            <span :class="[value == 1 ? 'tw-text-white' : 'tw-text-green-700']">{{ translate('JYES') }}</span></label>
        </fieldset>
      </div>

      <!-- TOGGLE -->
      <div v-else-if="parameter.type === 'toggle'" class="tw-flex tw-items-center">
        <div class="em-toggle">
          <input type="checkbox"
                 true-value="1" false-value="0"
                 class="em-toggle-check"
                 :id="paramId+ '_input'"
                 v-model="value"
          />
          <strong class="b em-toggle-switch"></strong>
          <strong class="b em-toggle-track"></strong>
        </div>
        <label :for="paramId + '_input'" class="tw-ml-2 !tw-mb-0 tw-font-bold tw-cursor-pointer">{{ translate(parameter.label) }}</label>
      </div>

      <!-- INPUT -->
      <input v-else-if="isInput"
             :type="parameter.type"
             class="form-control !tw-mb-0"
             :class="errors[parameter.param] ?'tw-rounded-lg !tw-border-red-500':''"
             :max="parameter.type === 'number' ? parameter.max : null"
             :min="undefined"
             :placeholder="parameter.placeholder"
             :id="paramId"
             v-model="value"
             :maxlength="parameter.maxlength"
             :readonly="parameter.editable === false"
             @change.self="checkValue(parameter)"
             @focusin="clearPassword(parameter)"
      >

      <!-- INPUT IN CASE OF SPLIT -->
      <span v-if="parameter.splitField">{{ parameter.splitChar }}</span>
      <Parameter
          v-if="parameter.splitField && parameterSecondary"
          :class="'tw-w-96'"
          :parameter-object="parameterSecondary"
          :multiselect-options="multiselectOptions"
          @valueUpdated="regroupValue(parameterSecondary)"/>
    </div>

    <!-- ERRORS -->
    <div v-show="parameter.warning && value!==''" v-html="translate(parameter.warning)"
         @click="SwalWarningPort" class="tw-cursor-pointer tw-text-orange-400"></div>

    <div
        v-if="errors[parameter.param] && !['yesno','toggle'].includes(parameter.type) && parameter.displayed"
        class="tw-absolute tw-mt-1 tw-text-red-600 tw-min-h-[24px]"
        :class="errors[parameter.param] ?'tw-opacity-100 ':'tw-opacity-0'"
        :id="'error-message-'+parameter.param">
      {{ translate(errors[parameter.param]) }}
    </div>
  </div>
</template>

<script>
import Multiselect from "vue-multiselect";
import settingsService from "../../services/settings";
import Swal from "sweetalert2";

import { reactive } from 'vue';

export default {
  name: "Parameter",
  components: {Multiselect},
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
          optionsPlaceholder: 'COM_EMUNDUS_MULTISELECT_ADDKEYWORDS',
          selectLabel: 'PRESS_ENTER_TO_SELECT',
          selectGroupLabel: 'PRESS_ENTER_TO_SELECT_GROUP',
          selectedLabel: 'SELECTED',
          deselectedLabel: 'PRESS_ENTER_TO_REMOVE',
          deselectGroupLabel: 'PRESS_ENTER_TO_DESELECT_GROUP',
          noOptionsText: 'COM_EMUNDUS_MULTISELECT_NOKEYWORDS',
          // Can add tag validations (ex. email, phone, regex)
          tagValidations: [],
          tagRegex: ''
        }
      }
    },
  },
  emits: ['valueUpdated', 'needSaving'],
  data() {
    return {
      initValue: null,
      value: null,
      valueSecondary: null,

      parameter: {},
      parameterSecondary: {},

      multiOptions: [],

      errors: {},
    }
  },
  created() {
    this.parameter = this.parameterObject;

    if (this.parameter.type === 'multiselect') {
      this.multiOptions = this.$props.multiselectOptions.options;

      if(!this.multiselectOptions.multiple) {
        this.value = this.multiOptions.find((option) => option.value === this.parameter.value);
      } else {
        this.value = this.multiOptions.filter((option) => this.parameter.value.includes(option.value));
      }

      if(!this.value) {
        this.value = [];
      }
    }
    else if (this.parameter) {
      this.value = this.parameter.value;
    }

    if(this.parameter.splitField) {
      if(this.value) {
        let splitValue = this.value.split(this.parameter.splitChar);
        this.value = splitValue[0];
        this.valueSecondary = splitValue[1];
      }

      this.parameterSecondary = reactive({...this.parameter});
      // Pass splitField to false to avoid infinite loop
      this.parameterSecondary.splitField = false;
      this.parameterSecondary.hideLabel = true;
      this.parameterSecondary.value = this.valueSecondary;
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
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          actions: "em-swal-single-action",
        },
      });
    },
    addOption(newOption) {
      // Check if newOption is already in the list
      if (this.multiOptions.find(option => option.name === newOption)) {
        return false
      }

      if (this.$props.multiselectOptions.tagValidations.length > 0) {
        let valid = false
        this.$props.multiselectOptions.tagValidations.forEach(validation => {
          switch (validation) {
            case 'email':
              valid = this.validateEmail(newOption)
              break
            case 'regex':
              valid = new RegExp(this.$props.multiselectOptions.tagRegex).test(newOption)
              break
            default:
              break
          }
        });
        if (!valid) {
          return false
        }
      }

      const option = {
        name: newOption,
        code: newOption
      }

      this.multiOptions.push(option)
      this.value.push(option)
      this.parameter.value.push(option.code)
    },
    checkAddOption(event) {
      event.preventDefault();
      let added = this.addOption(event.srcElement.value);
      if(!added) {
        event.srcElement.value = '';
      }
    },
    checkComma(event) {
      if (this.$props.multiselectOptions.tagValidations.includes('email') && event && event.key === ',') {
        this.addOption(event.srcElement.value.replace(',', ''));
      }
    },

    validate() {
      if (this.parameter.value === '' && this.parameter.optional) {
        delete this.errors[this.parameter.param];
        return true;
      } else if (this.parameter.value === '' && !this.parameter.optional) {
        this.errors[this.parameter.param] = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL';
        return false;
      } else {
        if (this.parameter.type === 'email') {
          if (!this.validateEmail(this.parameter.value)) {
            this.errors[this.parameter.param] = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NO';
            return false;
          }
        }

        delete this.errors[this.parameter.param];
        return true;
      }
    },
    checkValue(parameter) {
      if (parameter.type === 'number') {
        if (this.value > parameter.max) {
          this.value = parameter.max;
        }
      } else {
        this.validate(parameter)
      }
    },
    SwalWarningPort: function () {
      Swal.fire({
        html: `
    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-center tw--mt-5">
      <h2 class="tw-font-bold">
        ${this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_HELPTEXT_TITLE')}
      </h2>
      <p class="tw-text-center tw-mt-5 tw-text-neutral-700">
        ${this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_HELPTEXT_BODY')}
      </p>
    </div>
  `,
        showCancelButton: false,
        showConfirmButton: true,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        confirmButtonColor: '#1ea211',
        reverseButtons: true,
        customClass: {
          confirmButton: 'em-swal-confirm-button',
          actions: "em-swal-single-action",
          popup: 'tw-px-6 tw-py-4 tw-flex tw-justify-center tw-items-center',
        },
      });
    },

    clearPassword(parameter) {
      if (parameter.type === 'password') {
        this.value = '';
      }
    },
    pastedFromClipboard(event) {
      const clipboardData = event.clipboardData
      const pastedData = clipboardData?.getData('Text')
      const emails = pastedData?.trim().split(',')
      if (emails) {
        emails.map(email => {
          const emailAddress = email.trim()
          if (!this.ValidateEmail(emailAddress)) {
            this.$refs.email.onCreate(this.emailTagCreate({label: emailAddress, value: emailAddress}))
          }
        })
      }
    },

    regroupValue(parameter) {
      this.valueSecondary = parameter.value;
    },

    /* VALIDATIONS */
    validateEmail(email) {
      let res = /^[\w.-]+@([\w-]+\.)+[\w-]{2,4}$/;
      return res.test(email);
    },
  },
  watch: {
    value: {
      handler: function (val, oldVal) {
        if (this.parameter.type !== 'multiselect') {
          this.parameter.value = val;
        }

        if (this.parameter.splitField) {
          this.parameter.concatValue = val + this.parameter.splitChar + this.valueSecondary;
        }

        this.$emit('valueUpdated', this.parameter, oldVal, val)

        if (val !== oldVal && val !== this.initValue) {
          let valid = true;

          if (['text', 'email', 'number', 'password', 'textarea'].includes(this.parameter.type)) {
            valid = this.validate();
          }

          this.$emit('needSaving', true, this.parameter, valid)
        }

        if (val == this.initValue) {
          this.$emit('needSaving', false, this.parameter, true)
        }
      },
      deep: true
    },
    valueSecondary: {
      handler: function (val, oldVal) {
        this.parameter.concatValue = this.value + this.parameter.splitChar + val;
      },
      deep: true
    }
  },
  computed: {
    isInput() {
      return ['text', 'email', 'number', 'password'].includes(this.parameter.type) && this.parameter.displayed && this.parameter.editable !== "semi";
    },
    paramId() {
      return 'param_' + this.parameter.param;
    },
    paramName() {
      return 'param_' + this.parameter.param + '[]';
    },
  }
}
</script>

<style>
.no-options .multiselect__content-wrapper {
  display: none !important;
}

.no-options .multiselect__select {
  display: none !important;
}
</style>