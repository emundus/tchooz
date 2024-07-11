<template>
  <div class="em-settings-menu">
    <div class="tw-w-full" v-if="!loading">


      <Info :text="'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_EMAIL_HELPTEXT'" :icon-type="'material-icons'"
            :class="'tw-mt-2'"></Info>

      <!-- GLOBAL CONFIGURATION -->
      <div class="tw-flex tw-items-center tw-justify-between tw-mt-6">
        <div class="tw-flex tw-items-center tw-mb-3">
          <div class="em-toggle">
            <input type="checkbox"
                   class="em-toggle-check"
                   :id="'published'"
                   v-model=" computedEnableEmail "
            />
            <strong class="b em-toggle-switch"></strong>
            <strong class="b em-toggle-track"></strong>
          </div>
          <label for="published" class="tw-ml-2 !tw-mb-0 tw-font-bold">{{
              translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_ENABLE')
            }}</label>
        </div>
      </div>

      <Info v-if="!computedEnableEmail" :text="warning" :icon="'warning'" :bg-color="'tw-bg-orange-100'"
            :icon-type="'material-icons'" :icon-color="'tw-text-orange-600'"></Info>

      <div v-if="enableEmail  && computedEnableEmail">
        <!--<label class="font-medium">{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_GLOBAL') }}</label>  -->
        <div class="tw-grid tw-grid-cols-2 tw-gap-6">
          <div class="form-group tw-w-full" v-for="param in globalInformations"
               :key="param.param">
            <Parameter :parameter="param" @needSaving="updateParameterToSaving" v-if="param.displayed === true"/>
          </div>
        </div>
      </div>

      <!-- CUSTOM CONFIGURATION -->
      <div class="tw-flex tw-items-center tw-mb-3 tw-mt-6" v-if="enableEmail && computedEnableEmail">
        <div class="em-toggle">
          <input type="checkbox"
                 class="em-toggle-check"
                 :id="'custom_published'"
                 v-model="customConfigurationToggle"
          />
          <strong class="b em-toggle-switch"></strong>
          <strong class="b em-toggle-track"></strong>
        </div>
        <label for="custom_published" class="tw-ml-2 !tw-mb-0 tw-font-bold">{{
            translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CUSTOM')
          }}</label>
      </div>


      <!-- visible even when the toogle custom value is false  -->
      <div v-if="enableEmail && computedEnableEmail" class="tw-flex tw-flex-col tw-gap-6">
        <div v-for="param in customInformations" :key="param.param"
             v-if="param.param === 'mailfrom' || param.param === 'fromname'">
          <Parameter :key="keyParamsCustom" :CustomValue="!!+customConfigurationToggle" :parameter="param"
                     @needSaving="updateParameterToSaving" v-if="param.displayed === true"/>
        </div>
      </div>

      <!-- only visible when the toogle custom value is true  -->
      <div class="tw-mt-6" v-if="customConfigurationToggle && enableEmail && computedEnableEmail">
        <div class="tw-grid tw-grid-cols-2 tw-gap-4">
          <div class="form-group tw-w-full !tw-ml-0 tw-mr-0 tw-mt-0"
               :class="['smtpsecure','smtpauth'].includes(param.param) ? 'tw-col-span-full' : ''"
               v-for="param in customInformations"
               v-if="checkSmtpAuth(param) && param.param !== 'mailfrom' && param.param !== 'fromname'"
               :key="param.param">
            <Parameter :parameter="param" @needSaving="updateParameterToSaving"/>
          </div>
          <!-- <Info :text="'COM_EMUNDUS_GLOBAL_PARAMS_SECTIONS_MAIL_SUBSECTION_SERVER_EMAIL_CONF_ADVICE'"
                 class="tw-mt-4"></Info> -->
        </div>
      </div>
      <div class="tw-flex tw-justify-between tw-mt-6">
        <button
            :class=" noSendTestClick ?  'tw-bg-gray-200 tw-text-gray-400 tw-border-gray-300 tw-cursor-not-allowed tw-flex tw-items-center tw-rounded-coordinator tw-py-2 tw-px-3' :'' +   'tw-flex tw-items-center tw-bg-transparent hover:tw-bg-profile-full hover:tw-text-white tw-text-profile-full tw-font-semibold tw-py-2 tw-px-3 tw-border tw-border-profile-full hover:tw-border-transparent tw-rounded-coordinator'"
            :disabled="noSendTestClick"
            @mouseover="hover =true"
            @mouseout="hover = false"
            @click="CheckSendMail">
          <span id="iconSend" class="material-icons-outlined" :class="iconClasses">send</span>
          {{ translate("COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_BT") }}
        </button>
          <div v-if="loadingMail" class="em-page-loader"> </div>


        <div :title="computedTitle">
          <button id="saveBT" class="btn btn-primary" :class="!this.updatable ? 'disabled' :''"
                  @click="saveEmailSettings">
            {{ translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE") }}
          </button>
        </div>
      </div>

      <div class="em-page-loader" v-if="loading"></div>
    </div>
  </div>

</template>

<script>
import axios from "axios";

import mixin from "@/mixins/mixin";
import Swal from "sweetalert2";
import Parameter from "@/components/Settings/Parameter.vue";
import Info from "@/components/info.vue";
import settingsService from "@/services/settings";

//const qs = require("qs");

export default {
  name: "EditEmailJoomla",
  components: {Info, Parameter},
  props: {
    type: String,
    showValueMail: {
      type: Number,
      default: -1,
      required: false
    },
    customValue: {
      type: Number,
      default: -1,
      required: false
    },
    warning: {
      type: String,
      default: "",
      required: false
    },
  },

  mixins: [mixin],

  data() {
    return {
      loading: true,
      loadingMail: false,
      parametersUpdated: [],
      updatable: false,

      params: [],
      enableEmail: null,
      customConfigurationToggle: null,
      globalInformations: [],
      customInformations: [],
      config: {},

      CustomConfigServerMail: {},
      ParamJoomlaEmundusExtensions: {},

      AuthSMTP: false,
      editableParamsServerMail: null,
      keyParamsCustom: 0,
      clicker: 0,
      noSendTestClick: null,
      hover: null,
      isAccordionPanelVisible: false,
      tooltipText: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_NEED_TEST'),
      allgood:null,
      allgoodPromiseResolve: null,
    };
  },

  created() {
    // eslint-disable-next-line no-undef
    this.globalInformations = require('../../../data/settings/emails/global.json');
    // eslint-disable-next-line no-undef
    this.customInformations = require('../../../data/settings/emails/custom.json');
  },
  mounted() {
    this.getEmundusParamsJoomlaConfiguration()
  },

  methods: {
    async saveMethod() {
      await this.CheckSendMail();
      return await this.waitForAllGood();
    },
    waitForAllGood() {
      //loop on the state of allgood until it is not null and then return it to make work globalSettings saveSection
      return new Promise((resolve) => {
        if (this.allgood !== null) {
          resolve(this.allgood);
        } else {
          this.allgoodPromiseResolve = resolve;
        }
      });
    },



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
          this.loading = false;

          this.enableEmail = this.getEmundusparamsEmailValue('mailonline', 'boolean')
          this.AuthSMTP = this.config["joomla"]['smtpauth'];
          this.customConfigurationToggle = this.config['emundus']['custom_email_conf'];
          this.customConfigurationToggle = this.customConfigurationToggle == 1 ? true : false;
          this.putValueIntoInputs(this.customConfigurationToggle);

          for (let index in this.customInformations) {
            if (this.customInformations[index].param === 'smtpauth') {
              this.customInformations[index].value = this.AuthSMTP;
            }
          }
        });

    },
    putValueIntoInputs(customConfigurationToggle) {
      for (let index in this.globalInformations) {
        switch (this.globalInformations[index].param) {
        case 'replyto':
          this.globalInformations[index].value = this.config['emundus']['custom_email_replyto'];
          break;
        case 'replytoname':
          this.globalInformations[index].value = this.config['emundus']['custom_email_replytoname'];
          break;
        }
      }
      if (customConfigurationToggle == 1) {
        this.assignValues('custom_email');
      } else {
        this.assignValues('default_email');
      }
    },

    assignValues(source) {
      for (let index in this.customInformations) {
        const param = this.customInformations[index].param;
        if (this.config['emundus'][source + '_' + param]) {
          this.customInformations[index].value = this.config['emundus'][source + '_' + param];
        }
      }
    },
    getEmundusparamsEmailValue(specificValue, type) {
      let variableInput = null;
      for (let index in this.config) {
        if (this.config[index][specificValue]) {
          if (type === 'boolean') {
            if (this.config[index][specificValue] == 1 || this.config[index][specificValue] == true || this.config[index][specificValue] == "true") {
              variableInput = true;
            } else {
              variableInput = false;
            }
            return variableInput;
          }
        }
      }
    },


    checkSmtpAuth(param) {
      if (param.param === 'smtpuser' || param.param === 'smtppass') {
        let smtpAuthParameter = this.customInformations.find((element) => element.param === 'smtpauth');

        if (smtpAuthParameter.value == 1) {
          return true;
        } else {
          return false;
        }
      }
      return true;
    }
    ,

    updateParameterToSaving(needSaving, parameter, valid = true) {
      if (needSaving) {
        let checkExisting = this.parametersUpdated.find((param) => param.param === parameter.param);
        if (!checkExisting) {
          this.parametersUpdated.push(parameter);
        }
        this.noSendTestClick = false;
        this.updatable = false;
      } else {
        this.parametersUpdated = this.parametersUpdated.filter((param) => param.param !== parameter.param);
      }
      if (!valid)
      {
        this.noSendTestClick = true;
        this.updatable = false;
      }
    },


    async saveEmailSettings() {
      let params = [];
      this.parametersUpdated.forEach((param) => {
        params.push({
          component: param.component,
          param: param.param,
          value: param.value
        });
        if (this.customConfigurationToggle == 1 && param.param !== 'custom_email_conf' && param.param !== 'mailonline') {
          params.push({
            component: 'emundus',
            param: 'custom_email_' + param.param,
            value: param.value
          })
        } else if (this.customConfigurationToggle == 0 && param.param !== 'custom_email_conf' && param.param !== 'mailonline') {
          params.push({
            component: 'emundus',
            param: 'default_email_' + param.param,
            value: param.value
          })
        }
      });

      settingsService.saveParams(params)
        .then(() => {
          this.parametersUpdated = [];
          Swal.fire({
            title: this.translate("COM_EMUNDUS_ONBOARD_SUCCESS"),
            text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_SUCCESS"),
            showCancelButton: false,
            showConfirmButton: false,
            customClass: {
              title: 'em-swal-title'
            },
            timer: 1500,
          }).then(() => {
            this.updatable = false;
            this.allgood = true;
            return this.allgood;
          });
        })
        .catch(() => {
          this.allgood = false;
          Swal.fire({
            title: this.translate("COM_EMUNDUS_ERROR"),
            text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_ERROR"),
            showCancelButton: false,
            confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
            reverseButtons: true,
            allowOutsideClick: false,
            customClass: {
              title: 'em-swal-title',
              confirmButton: 'em-swal-confirm-button',
              actions: "em-swal-single-action",
            },
          });
          return this.allgood;
        });
    },

    async CheckSendMail() {
      this.noSendTestClick = true;
      let params = [];
      params.push({component: 'joomla', param: 'mailonline', value: this.computedEnableEmail ? 1 : 0});
      params = [...params, ...this.globalInformations];
      params = [...params, ...this.customInformations];
      this.loadingMail = true;
      axios.post('index.php?option=com_emundus&controller=settings&task=sendTestMail', params)
        .then(async response => {
          this.loadingMail = false;
          let colorBT = response.data.data[3] == 'success' ? 'green' : 'red';
          response.data.data[3] === 'success' ? this.updatable = true : this.updatable = false;
          await this.generateSweetAlert(response, colorBT);
        });
    },
    generateSweetAlert: async function (response, colorBT) {
      Swal.fire({
        html: `
      <div class="tw-flex tw-flex-col tw-items-center tw-justify-center">
        <h2 class="tw-flex tw-items-center tw-pb-2 tw-font-bold ">${this.translate(response.data.data[0])}</h2>
        <p class="tw-flex tw-items-center tw-justify-center tw-text-center">${this.translate(response.data.data[1])} ${this.translate(response.data.data[2])}</p>            <hr class="tw-self-stretch">
        <button type="button" class="tw-flex tw-items-center tw-font-bold" id="hideDivButton" ${colorBT === "red" ? 'style="color:red;"' : 'style="display: none;"'}>
            ${this.translate('COM_EMUNDUS_CLICK_HERE_INFO')}
            <i class="material-icons-outlined scale-150" ${colorBT === "red" ? 'style="color:red;"' : 'style="display: none;"'}>expand_more</i>
        </button>
        <div id="accordion-panel" class="tw-mt-2 " ${this.isAccordionPanelVisible && colorBT === "red" ? 'style="color:red; display:block;"' : 'style="display: none;"'}>
            ${this.translate(response.data.data[4])}
        </div>
      </div>
    `,
        width: 'auto',
        showCancelButton: true,
        cancelButtonText: this.translate('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_SAVE_ANYWAY'),
        cancelButtonColor: colorBT,
        showConfirmButton: true,
        confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_DOSSIERS_CLOSE'),
        confirmButtonColor: 'black',
        allowOutsideClick: false,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'my-button-class',
          cancelButton: 'my-button-class',
        },
        onOpen: () => {
          const hideDivButton = document.getElementById('hideDivButton');
          if (hideDivButton) {
            hideDivButton.addEventListener('click', () => {
              const accordionPanel = document.getElementById('accordion-panel');
              if (accordionPanel.style.display === 'none') {
                accordionPanel.style.display = 'block';
                accordionPanel.style.color = 'red';
                accordionPanel.style.backgroundColor = 'rgba(255, 0, 0, 0.1)';
                accordionPanel.style.border = '0.1em solid red';
                accordionPanel.style.borderRadius = '0.5em';
                accordionPanel.style.padding = '1em';

              } else {
                accordionPanel.style.display = 'none';
              }
            });
          } else {
            console.error('hideDivButton not found');
          }
        }
      }).then((result) => {
        if (result.dismiss === 'cancel') {
          setTimeout(async () => {
            await this.saveEmailSettings();
          }, 500);
        } else {
          this.allgood = true;
        }
        this.noSendTestClick = false;
      });
    },
    translate(key) {
      if (typeof key != undefined && key != null && Joomla !== null && typeof Joomla !== 'undefined') {
        return Joomla.JText._(key) ? Joomla.JText._(key) : key;
      } else {
        return '';
      }
    },
    generateConfirmModal(valueOfToggle) {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_SURE_TO_DISABLE"),
        text: this.translate("COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_SURE_TO_DISABLE_TEXT"),
        showCancelButton: true,
        showConfirmButton: true,
        confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_OK"),
        cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          cancelButton: 'em-swal-cancel-button',
          confirmButton: 'em-swal-confirm-button',
        }
      }).then((result) => {
        if (result.value) {
          this.updateParameterToSaving(true, {component: "joomla", param: 'mailonline', value: valueOfToggle});
          this.updatable = !valueOfToggle;
          this.noSendTestClick = valueOfToggle === 0;
        } else {
          this.enableEmail = 1;
        }
      });
    },
  },
  computed: {
    computedEnableEmail: {
      get() {
        return this.enableEmail == 1 ? true : false;
      },
      set(value) {
        this.enableEmail = value ? 1 : 0;
      },
    },
    computedTitle() {
      return !this.updatable ? this.tooltipText : '';
    },
    iconClasses() {
      return [
        'tw-mr-2',
        'material-icons-outlined',
        this.noSendTestClick ? 'tw-text-gray-400' : (this.hover ? 'tw-text-white' : 'tw-text-profile-full')

      ];
    },
  },

  watch: {
    allgood(newValue) {
      if (this.allgoodPromiseResolve) {
        this.allgoodPromiseResolve(newValue);
        this.allgoodPromiseResolve = null;
      }
    },
    parametersUpdated: function (val) {
      this.$emit('needSaving', val.length > 0)
    },
    enableEmail(val) {
      const oldVal = this.config["joomla"]['mailonline'];
      if (val !== oldVal && val !== undefined) {
        if (val === 0) {
          this.generateConfirmModal(val);
        } else {
          this.updateParameterToSaving(true, {component: "joomla", param: 'mailonline', value: val});
          this.updatable = !val;
          this.noSendTestClick = val === 0;
        }


      } else {
        this.updateParameterToSaving(false, {component: "joomla", param: 'mailonline', value: val});
        this.noSendTestClick = false;
        this.updatable = false;
      }
    },
    customConfigurationToggle: function (val, oldVal) {
      if (val == 1) {
        val = '1';
      } else if (val == 0) {
        val = '0';
      }
      if (oldVal == null) {
        oldVal = this.config['emundus']['custom_email_conf'];
      } else {
        oldVal = oldVal ? '1' : '0';
      }

      if (oldVal != null) {
        this.keyParamsCustom++;
        if (val !== oldVal) {
          this.putValueIntoInputs(val);
          this.updateParameterToSaving(true, {component: 'emundus', param: 'custom_email_conf', value: val});
          if (val === '0') {
            for (let index in this.customInformations) {
              this.customInformations[index].value = this.config['emundus']['default_email_' + this.customInformations[index].param];
              this.updateParameterToSaving(true, this.customInformations[index])
            }
          } else if (val === '1') {
            for (let index in this.customInformations) {
              this.customInformations[index].value = this.config['emundus']['custom_email_' + this.customInformations[index].param];
              this.updateParameterToSaving(true, this.customInformations[index])
            }
          }
        } else {
          this.updateParameterToSaving(false, {component: 'emundus', param: 'custom_email_conf', value: val});

        }
      }
    },
  },



};
</script>
<style scoped>
.form-group label {
  width: 100%;
}

button.swal2-styled.swal2-cancel {
  background: #FFFFFF;
  border: none !important;
  color: white !important;
  padding-left: 0 !important;
  padding-right: 0 !important;
}

</style>
