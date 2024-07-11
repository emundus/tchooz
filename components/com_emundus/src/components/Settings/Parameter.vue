<template>
  <div>
    <label v-if="parameter.hideLabel !== true" :for="'param_' + parameter.param"
           class="tw-flex tw-font-semibold tw-items-end">
      {{ translate(parameter.label) }}
      <span v-if="parameter.optional"
            :class="'tw-italic tw-text-[#727272] tw-text-xs tw-ml-1'">{{
          translate('COM_EMUNDUS_OPTIONAL')
        }}</span>
      <span v-else-if="parameter.optional===0" class="tw-text-red-500">*</span>
      <span v-if="parameter.helptext"
            class="material-icons-outlined tw-cursor-pointer tw-scale-75 tw-text-profile-full"
            @click="displayHelp(parameter.helptext)">help_outline</span>
    </label>

    <div name="input-field">
      <select v-if="isSelect" class="dropdown-toggle w-select !tw-mb-0"
              :class="empty && parameter.optional==0 ?'tw-rounded-lg !tw-border-red-500':''"
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
      >
        <template #noOptions>{{ translate('COM_EMUNDUS_MULTISELECT_NOKEYWORDS')}}</template>
      </multiselect>

      <textarea v-else-if="isTextarea"
                :id="paramId"
                v-model="value"
                class="!mb-0"
                :class="empty && parameter.optional==0 ?'tw-rounded-lg !tw-border-red-500':''"
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

      <div v-else-if="isYesNo">
        <fieldset data-toggle="buttons" class="tw-flex tw-items-center tw-gap-2">
          <label :for="paramId + '_input_0'"
                 :class="[value == 0 ? 'tw-bg-red-700' : 'tw-bg-white tw-border-neutral-400']"
                 class="tw-w-60 tw-h-10 tw-p-2.5 tw-rounded-lg tw-border tw-justify-center tw-items-center tw-gap-2.5 tw-inline-flex">
            <input v-model="value" type="radio" class="fabrikinput !tw-hidden" :name="paramName"
                   :id="paramId+ '_input_0'"
                   value="0" :checked="value === 0">
            <span :class="[value == 0 ? 'tw-text-white' : 'tw-text-red-700']">{{ translate('JNO') }}</span>
          </label>

          <label :for="paramId + '_input_1'"
                 :class="[value == 1 ? 'tw-bg-green-700' : 'tw-bg-white tw-border-neutral-400']"
                 class="tw-w-60 tw-h-10 tw-p-2.5 tw-rounded-lg tw-border tw-justify-center tw-items-center tw-gap-2.5 tw-inline-flex">
            <input v-model="value" type="radio" class="fabrikinput !tw-hidden" :name="paramName"
                   :id="paramId+ '_input_1'"
                   value="1" :checked="value === 1">
            <span :class="[value == 1 ? 'tw-text-white' : 'tw-text-green-700']">{{ translate('JYES') }}</span></label>
        </fieldset>
      </div>

      <div v-else-if="isToggle" class="tw-mt-4 tw-flex tw-items-center">
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
             :class="empty && parameter.optional==0 ?'tw-rounded-lg !tw-border-red-500':''"
             :max="parameter.type === 'number' ? parameter.max : null"
             :min="undefined"
             :placeholder="parameter.placeholder"
             :id="paramId"
             v-model="value"
             :maxlength="parameter.maxlength"
             :readonly="parameter.editable === false"
             @change.self="checkValue(parameter)"
             @keydown.enter="validate(parameter)"
             @focusout="validate(parameter)"
             @focusin="ClearPassword(parameter)"
      >


      <div
          v-if="(($props.parameter.type ==='email') && ($props.parameter.editable ==='semi') &&($props.parameter.displayed))"
          :class="'tw-flex tw-items-center '" >
        <div name="input-field-Semi0" class="tw-w-full">
        <input :class="emptySemi[0] && parameter.optional===0 ?'tw-rounded-lg !tw-border-red-500':''"  :placeholder="translate(segmentPlaceHolderEmail[0])" v-model="segmentValueEmail[0]">
        </div>
        <span class="tw-ml-2 tw-mr-2">@</span>
        <div name="input-field-Semi1" class="tw-w-full">
        <input v-if="customValue" :placeholder="translate(segmentPlaceHolderEmail[1])" v-model="segmentValueEmail[1]" :class="emptySemi[1] && parameter.optional===0 ?'tw-rounded-lg !tw-border-red-500':''">
        <span v-else :class="'tw-w-full'">{{ this.segmentValueEmail[1] }}</span>
        </div>
      </div>
    </div>

    <div v-if="parameter.warning && !checkPort && value!==''" v-html="translate(parameter.warning)"
         @click="SwalWarningPort" class="tw-cursor-pointer tw-text-orange-400 "></div>

    <div v-if="(($props.parameter.type ==='email')||(parameter.optional === 0 )) && validationString !== ''" class="tw-mt-1 tw-mb-4"
         :id="'emailCheck-'+parameter.param"
         :style="{ color: emailValidationColor[parameter.param] }">
      {{ translate(validationString) }}
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
    parameter: {
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

      tagOptions: [],
      timezoneOptions: [],
      inputValidationMessage: [],
      emailValidationColor: [],
      validationString: "",
      segmentValueEmail: [],
      segmentPlaceHolderEmail: [],
      customValue: false,
      empty: false,
      emptySemi: [false, false],
    }
  },
  created() {
    if (this.$props.parameter && this.$props.parameter.type === 'timezone') {
      settingsService.getTimezoneList().then((response) => {
        if (response.data.status) {
          this.timezoneOptions = response.data.data;
          this.value = this.timezoneOptions.find((timezone) => timezone.value === this.$props.parameter.value);
          this.initValue = this.value;
        }
      });
    } else if (this.$props.parameter) {
      this.value = this.$props.parameter.value;
      if(this.parameter.editable === 'semi'){
        this.initValue = this.segmentValue(this.parameter.value)
      }else{
        this.initValue = this.value;
      }
    }
    if (this.parameter.editable === 'semi' && this.parameter.displayed) {
      if (this.CustomValue == 1) {
        this.customValue = true
      }
      if (this.parameter.value !== null) {
        this.segmentValueEmail = this.segmentValue(this.parameter.value)
        this.segmentPlaceHolderEmail = this.segmentValue(this.parameter.placeholder)
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
      this.$props.parameter.value.push(tag)
    },

    validateEmail(email) {
      let res = /^[\w.-]+@([\w-]+\.)+[\w-]{2,4}$/;
      return res.test(email);
    },
    validate(paramEmail) {
      if (paramEmail.type === 'email') {
        let email = paramEmail.value;
        this.inputValidationMessage[paramEmail.param] = "";
        if (email === '' && paramEmail.optional === "1") {
          this.validationString = '';
          return true;
        } else if (email === '') {
          this.validationString = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL';
          this.$set(this.emailValidationColor, paramEmail.param, "red");
          return true;
        }
        if (this.validateEmail(email)) {
          this.validationString = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_YES"
          this.$set(this.emailValidationColor, paramEmail.param, "green");
          return true;
        } else {
          this.validationString = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NO"
          this.$set(this.emailValidationColor, paramEmail.param, "red");
          this.$emit('needSaving', false, this.$props.parameter, false)
          return false;
        }
      } else {
        if ((this.$props.parameter.optional == 0) && this.$props.parameter.value === '' || this.$props.parameter.value === null || this.$props.parameter.value.isNaN) {
          this.validationString = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL';
          this.empty = true;
          this.$set(this.emailValidationColor, paramEmail.param, "red");
          return false;
        } else {
          this.empty = false;
          this.validationString = '';
        }
        return true;
      }
    },
    checkValue(parameter) {
      if (parameter.type === 'number') {
        if (this.value > parameter.max) {
          this.value = parameter.max;}
      }else{
        this.validate(parameter)
      }
    },
    segmentValue(theValue) {
      if (this.parameter.type === 'email') {
        return theValue.split('@');
      }
      return []
    },
    regroupeValue() {
      if (this.segmentValueEmail[0] === '' && this.segmentValueEmail[1] === '') {
        this.$props.parameter.value = ''
      } else {
        this.$props.parameter.value = this.segmentValueEmail[0] + '@' + this.segmentValueEmail[1]
      }
      this.$emit('needSaving', true, this.$props.parameter , true)

    },
    SwalWarningPort: function () {
      Swal.fire({
        html: `
    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-center">
      <h2 class="tw-font-bold tw-pb-2">
        ${this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_HELPTEXT_TITLE')}
      </h2>
      <hr class="tw-w-full tw-my-2">
      <p class="tw-text-center">
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
    translate(key) {
      if (typeof key != undefined && key != null && Joomla !== null && typeof Joomla !== 'undefined') {
        return Joomla.JText._(key) ? Joomla.JText._(key) : key;
      } else {
        return '';
      }
    },

    ClearPassword(parameter) {
      if (parameter.type === 'password') {
        this.value = '';
      }
    }

  },
  watch: {
    value: {
      handler: function (val, oldVal) {
        this.$props.parameter.value = val;
        if (oldVal !== null) {
          if (this.initValue !== val) {
            if (this.parameter.regex !== undefined) {
              let res = new RegExp(this.parameter.regex);
              if (res.test(val)) {
                //check the value with the regex
                if (this.$props.parameter.type === 'number') {
                  this.$props.parameter.value = parseInt(val);
                } else if (val === true || val === false) {
                  this.$props.parameter.value = val ? 1 : 0;
                }
                this.$emit('needSaving', true, this.$props.parameter, true)
              } else {
                //the check failed
                if (this.parameter.optional === 0) {
                  this.$emit('needSaving', true, this.$props.parameter, false)
                } else {
                  if (this.$props.parameter.type === 'number') {
                    this.$props.parameter.value = null;
                  } else if (val === true || val === false) {
                    this.$props.parameter.value = val ? 1 : 0;
                  }
                  this.$emit('needSaving', true, this.$props.parameter, true)
                }
              }
            } else {
              if (this.parameter.optional === 0) {
                this.$emit('needSaving', true, this.$props.parameter, true)
              } else {
                this.$emit('needSaving', true, this.$props.parameter, true)
              }
            }
          } else {
            this.$emit('needSaving', true, this.$props.parameter, true)
          }
        }
      },
      deep: true
    },
    segmentValueEmail: {
      handler: function (val,oldVal) {
        if (val[0] === ''){
          this.emptySemi[0] = true;
          this.validationString = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL';
          this.emailValidationColor[this.parameter.param] = "red";
          this.$emit('needSaving', true, this.$props.parameter, false)
        }else if (val[0] !== '' ){
          this.emptySemi[0] = false;
        }
        if (val[1] === ''){
          this.emptySemi[1] = true;
          this.validationString = 'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL';
          this.emailValidationColor[this.parameter.param] = "red";
          this.$emit('needSaving', true, this.$props.parameter, false)
        }else if (val[1] !== '' ){
          this.emptySemi[1] = false;
        }
        let valueCheck =0;
        for (let i = 0; i <= val.length-1 ; ++i) {
          if (val[i] !== this.initValue[i]) {
            if (this.parameter.regex !== undefined) {
              let res = new RegExp(this.parameter.regex[i]);
              if (res.test(val[i])) {
                this.regroupeValue()
                this.validationString = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_YES"
                this.$set(this.emailValidationColor, this.parameter.param, "green");
              } else {
                if(val[i] !== '') {
                  if (i === 0) {
                    this.validationString = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_NAME_EXP"
                    this.$set(this.emailValidationColor, this.parameter.param, "red");
                    this.emptySemi[0] = true;
                    this.$emit('needSaving', true, this.$props.parameter,false)
                  } else if (i === 1) {
                    this.validationString = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_DOMAIN_EXP"
                    this.$set(this.emailValidationColor, this.parameter.param, "red");
                    this.emptySemi[1] = true;
                    this.$emit('needSaving', true, this.$props.parameter,false)
                  }
                }
              }
            }
          }else{valueCheck++}
        }
        if (oldVal.length>0) {
          if (valueCheck === val.length) {
            this.regroupeValue()
            this.validationString = "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL_YES"
            this.$set(this.emailValidationColor, this.parameter.param, "green");
          }
        }
      }
    }
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
        if (this.$props.parameter.value === goodPort[i]) {
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