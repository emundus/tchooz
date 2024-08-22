<template>
  <div>
    <label v-if="parameter.hideLabel !== true" :for="'param_' + parameter.param"
           class="tw-flex tw-font-semibold tw-items-end">
      {{ translate(parameter.label) }}
      <span v-if="parameter.optional"
            :class="'tw-italic tw-text-[#727272] tw-text-xs tw-ml-1'">{{
          translate('COM_EMUNDUS_OPTIONAL')
        }}</span>
      <span v-else-if="parameter.optional===0" class="tw-text-red-600">*</span>
      <span v-if="parameter.helptext"
            class="material-symbols-outlined tw-cursor-pointer tw-scale-75 tw-text-profile-full"
            @click="displayHelp(parameter.helptext)">help_outline</span>
    </label>

    <div name="input-field">
      <select v-if="isSelect" class="dropdown-toggle w-select !tw-mb-0"
              :class="errors[parameter.param] ?'tw-rounded-lg !tw-border-red-500':''"
              :id="paramId"
              v-model="value"
              :disabled="parameter.editable === false">
        <option v-for="option in parameter.options" :key="option.value" :value="option.value">{{
            translate(option.label)
          }}
        </option>
      </select>

      <multiselect
          v-else-if="isKeywords"
          :id="paramId"
          v-model="value"
          label="name"
          track-by="code"
          :options="tagOptions"
          :multiple="true"
          :taggable="true"
          :placeholder="parameter.placeholder"
          @tag="addTag"
          :tagPlaceholder="translate('COM_EMUNDUS_MULTISELECT_ADDKEYWORDS')"
          :key="parameter.value !== undefined ? parameter.value.length : 0"
          :class="['tw-cursor-pointer']"
          :selectLabel="translate('PRESS_ENTER_TO_SELECT')"
          :selectGroupLabel="translate('PRESS_ENTER_TO_SELECT_GROUP')"
          :selectedLabel="translate('SELECTED')"
          :deselect-label="translate('PRESS_ENTER_TO_REMOVE')"
          :deselectGroupLabel="translate('PRESS_ENTER_TO_DESELECT_GROUP')"
      >
        <template #noOptions>{{ translate('COM_EMUNDUS_MULTISELECT_NOKEYWORDS') }}</template>
      </multiselect>

      <textarea v-else-if="isTextarea"
                :id="paramId"
                v-model="value"
                class="!mb-0"
                :class="errors[parameter.param] ?'tw-rounded-lg !tw-border-red-500':''"
                :maxlength="parameter.maxlength"
                :readonly="parameter.editable === false">
    </textarea>

      <div v-else-if="isTimezone">
        <multiselect
            :class="'tw-cursor-pointer'"
            v-model="value"
            label="label"
            track-by="value"
            :options="timezoneOptions"
            :multiple="false"
            :taggable="false"
            select-label=""
            selected-label=""
            deselect-label=""
            :placeholder="''"
            :close-on-select="true"
            :clear-on-select="false"
            :searchable="true"
            :allow-empty="false"
        ></multiselect>
      </div>

      <div v-else-if="isYesNo" class="tw-mb-5">
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

      <div v-else-if="isToggle" class="tw-flex tw-items-center">
        <div class="em-toggle">
          <input type="checkbox"
                 class="em-toggle-check"
                 :id="paramId+ '_input'"
                 v-model="value"
          />
          <strong class="b em-toggle-switch"></strong>
          <strong class="b em-toggle-track"></strong>
        </div>
        <label :for="paramId + '_input'" class="tw-ml-2 !tw-mb-0 tw-font-bold">{{ translate(parameter.label) }}</label>
      </div>

      <input v-else-if="isInput" :type="parameter.type" class="form-control !tw-mb-0"
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


      <div
          v-if="parameter.type ==='email' && parameter.editable ==='semi' && parameter.displayed"
          :class="'tw-flex tw-items-center '">
        <div name="input-field-semi_0" style="width: 400px;">
          <input
              :class="errors[parameter.param+'-semi-0'] && parameter.optional===0 ?'tw-rounded-lg !tw-border-red-500':''"
              :placeholder="translate(senderEmailPlaceholder)" v-model="senderEmail">
          <div v-if="errors[parameter.param+'-semi-0']"
               class="tw-mt-1 tw-mb-4 tw-text-red-600 tw-absolute"
               :id="'emailCheck-'+parameter.param">
            {{ translate(errors[parameter.param + '-semi-0']) }}
          </div>
        </div>
        <span class="tw-ml-2 tw-mr-2">@</span>
        <div name="input-field-Semi1" style="width: 400px;">
          <input v-if="customValue" :placeholder="translate(senderEmailDomainPlaceholder)" v-model="senderEmailDomain"
                 :class="errors[parameter.param+'-semi-1'] && parameter.optional===0 ?'tw-rounded-lg !tw-border-red-500':''">
          <span v-else :class="'tw-w-full'">{{ senderEmailDomain }}</span>
          <div v-if="errors[parameter.param+'-semi-1']"
               class="tw-mt-1 tw-mb-4 tw-text-red-600 tw-absolute"
               :id="'emailCheck-'+parameter.param">
            {{ translate(errors[parameter.param + '-semi-1']) }}
          </div>
        </div>
      </div>
    </div>

    <div v-show="parameter.warning && !checkPort && value!==''" v-html="translate(parameter.warning)"
         @click="SwalWarningPort" class="tw-cursor-pointer tw-text-orange-400"></div>

    <div v-if="(!parameter.warning || (parameter.warning && checkPort && value!=='') || value === '') && !['yesno','toggle'].includes(parameter.type) && parameter.displayed"
         class="tw-mt-1 tw-text-red-600 tw-min-h-[24px]"
         :class="errors[parameter.param] ?'tw-opacity-100 ':'tw-opacity-0'"
         :id="'error-message-'+parameter.param">
      {{ translate(errors[parameter.param]) }}
    </div>
  </div>
</template>

<script>
import Multiselect from "vue-multiselect";
import settingsService from "../../services/settings";
import axios from "axios";
import Swal from "sweetalert2";

export default {
  name: "Parameter",
  components: {Multiselect},
  props: {
    parameterObject: {
      type: Object,
      required: true
    },
    CustomValue: {
      type: Boolean,
      required: false
    },

  },
  data() {
    return {
      initValue: null,
      value: null,
      config: {},
      parameter: {},

      senderEmail: '',
      senderEmailDomain: '',
      senderEmailPlaceholder: '',
      senderEmailDomainPlaceholder: '',

      tagOptions: [],
      timezoneOptions: [],
      inputValidationMessage: [],
      emailValidationColor: [],
      validationString: "",
      customValue: false,

      errors: {},
    }
  },
  created() {
    this.parameter = this.parameterObject;
    if (this.parameter && this.parameter.type === 'timezone') {
      settingsService.getTimezoneList().then((response) => {
        if (response.data.status) {
          this.timezoneOptions = response.data.data;
          this.value = this.timezoneOptions.find((timezone) => timezone.value === this.parameter.value);
          this.initValue = this.value;
        }
      });
    } else if (this.parameter) {
      this.value = this.parameter.value;
      if (this.parameter.editable === 'semi') {
        if (this.parameter.type === 'email') {
          this.initValue = this.parameter.value;
          let sender = this.parameter.value.split('@');
          let senderPlaceholder = this.parameter.placeholder.split('@');
          this.senderEmail = sender[0];
          this.senderEmailDomain = sender[1];
          this.senderEmailPlaceholder = senderPlaceholder[0];
          this.senderEmailDomainPlaceholder = senderPlaceholder[1];
        }
      } else {
        this.initValue = this.value;
      }
    }

    if (this.parameter.editable === 'semi' && this.parameter.displayed) {
      if (this.CustomValue == 1) {
        this.customValue = true
      }
    }
  },
  methods: {
    getEmundusParamsJoomlaConfiguration() {
      axios.get("index.php?option=com_emundus&controller=settings&task=getemundusparams")
          .then(response => {
            this.config = response.data;

            Object.values(this.params).forEach((param) => {

              param.value = this.config[param.component][param.param];
              if ((param.value === "1") || (param.value === true) || (param.value === "true")) {
                param.value = 1;
              }
              if ((param.value === "0") || (param.value === false) || (param.value === "false")) {
                param.value = 0;
              }
            });
            this.customValue = this.config['emundus']['custom_email_conf'];
          });
    },
    displayHelp(message) {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_SWAL_HELP_TITLE"),
        text: this.translate(message),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          actions: "em-swal-single-action",
        },
      });
    }
    ,
    addTag(newTag) {
      const tag = {
        name: newTag,
        code: newTag
      }
      this.tagOptions.push(tag)
      this.parameter.value.push(tag)
    },

    validateEmail(email) {
      let res = /^[\w.-]+@([\w-]+\.)+[\w-]{2,4}$/;
      return res.test(email);
    },
    validate() {
      if (this.parameter.value === '' && this.parameter.optional == 1) {
        delete this.errors[this.parameter.param];
        return true;
      }
      else if(this.parameter.value === '' && this.parameter.optional == 0) {
        this.errors[this.parameter.param] = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL';
        return false;
      }
      else {
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
    regroupeValue() {
      if (this.senderEmail === '' && this.senderEmailDomain === '') {
        this.value = ''
      } else {
        this.value = this.senderEmail + '@' + this.senderEmailDomain
      }
    },
    SwalWarningPort: function () {
      Swal.fire({
        html: `
    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-center tw--mt-5">
      <h2 class="tw-font-bold">
        ${this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_HELPTEXT_TITLE')}
      </h2>
      <p class="tw-text-center tw-mt-5">
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
    }
  },
  watch: {
    senderEmail: {
      handler: function (val, oldVal) {
        if (val === '') {
          this.errors[this.parameter.param + '-semi-0'] = this.parameter.error;
          this.$emit('needSaving', true, this.parameter, false)
        } else if (val !== '') {
          delete this.errors[this.parameter.param + '-semi-0'];
        }

        if (this.parameter.regex !== undefined && val !== '') {
          let res = new RegExp(this.parameter.regex[0]);
          if (res.test(val)) {
            delete this.errors[this.parameter.param + '-semi-0'];
            this.regroupeValue()
          } else {
            this.errors[this.parameter.param + '-semi-0'] = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NAME_EXP";
            this.$emit('needSaving', true, this.parameter, false)
          }
        }
      },
      deep: true
    },
    senderEmailDomain: {
      handler: function (val, oldVal) {
        if (val === '') {
          this.errors[this.parameter.param + '-semi-1'] = this.parameter.error;
          this.$emit('needSaving', true, this.parameter, false)
        } else if (val !== '') {
          delete this.errors[this.parameter.param + '-semi-1'];
        }

        if (this.parameter.regex !== undefined && val !== '') {
          let res = new RegExp(this.parameter.regex[1]);
          if (res.test(val)) {
            delete this.errors[this.parameter.param + '-semi-1'];
            this.regroupeValue()
          } else {
            this.errors[this.parameter.param + '-semi-1'] = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_DOMAIN_EXP";
            this.$emit('needSaving', true, this.parameter, false)
          }
        }
      },
      deep: true
    },
    value: {
      handler: function (val, oldVal) {
        if(val !== oldVal && val !== this.initValue) {
          let valid = true;
          this.parameter.value = val;

          if (['text', 'email', 'number', 'password', 'textarea'].includes(this.parameter.type)) {
            valid = this.validate();
          }

          this.$emit('needSaving', true, this.parameter, valid)
        }

        if(val == this.initValue) {
          this.$emit('needSaving', false, this.parameter, true)
        }
      },
      deep: true
    },
  },
  computed: {
    isSelect() {
      return this.parameter.type === 'select' && this.parameter.displayed;
    },
    isKeywords() {
      return this.parameter.type === 'keywords' && this.parameter.displayed
    },
    isTextarea() {
      return this.parameter.type === 'textarea' && this.parameter.displayed;
    },
    isTimezone() {
      return this.parameter.type === 'timezone' && this.parameter.displayed;
    },
    isYesNo() {
      return this.parameter.type === 'yesno' && this.parameter.displayed;
    },
    isToggle() {
      return this.parameter.type === 'toggle' && this.parameter.displayed;
    },
    isInput() {
      return ['text', 'email', 'number', 'password'].includes(this.parameter.type) && this.parameter.displayed && this.parameter.editable !== "semi";
    },
    isEmailCheck() {
      return this.parameter.type === 'email' && this.parameter.displayed;
    },
    paramId() {
      return 'param_' + this.parameter.param;
    },
    paramName() {
      return 'param_' + this.parameter.param + '[]';
    },
    checkPort() {
      let goodPort = [25, 587, 465]
      for (let i = 0; i < goodPort.length; i++) {
        if (this.parameter.value === goodPort[i]) {
          return true;
        }
      }
      this.$
      return false;
    },
  }
}
</script>

<style scoped>
.adaptativeMultiselect {

}
</style>