<template>
  <div class="tw-flex tw-flex-col tw-justify-between tw-w-full tw-font-medium rtl:tw-text-right tw-text-black tw-border tw-border-neutral-300 tw-rounded-[15px] tw-bg-white tw-mb-6 tw-gap-3 tw-p-4">
    <Info :text="'COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_REQUIREMENTS'"
          :icon="'warning'"
          :bg-color="'tw-bg-orange-100'"
          :icon-type="'material-symbols-outlined'"
          :icon-color="'tw-text-orange-600'"
          :class="'tw-mb-4'"
    />
    <h3>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP') }}</h3>

    <div class="tw-mt-2">
      <p class="tw-text-medium tw-text-sm tw-text-neutral-800">
        {{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_DESC') }}
      </p>

      <div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
        <div v-for="(field) in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
          <Parameter
              :ref="'teams_' + field.param"
              :parameter-object="field"
              :help-text-type="'above'"
              @needSaving="parameterNeedSaving"
          />
        </div>

        <div>
          <button class="tw-btn-primary tw-w-fit tw-float-right" :disabled="disabledSubmit" @click="setupOvh()">
            <span v-if="app.enabled === 0 && app.config === '{}'">{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_ADD') }}</span>
            <span v-else>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE') }}</span>
          </button>
        </div>

      </div>
    </div>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
import Info from "@/components/Utils/Info.vue";
import Parameter from "@/components/Utils/Parameter.vue";

import settingsService from "@/services/settings";

export default {
  name: "OVHSetup",
  components: {Parameter, Info},
  props: {
    app: {
      type: Object,
      required: true,
    }
  },
  data() {
    return {
      loading: false,
      fields: [
        {
          param: 'client_id',
          type: 'text',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_CLIENT_ID',
          helptext: '',
          displayed: true,
        },
        {
          param: 'client_secret',
          type: 'password',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_CLIENT_SECRET',
          helptext: '',
          displayed: true,
        },
        {
          param: 'consumer_key',
          type: 'text',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_CONSUMER_KEY',
          helptext: '',
          displayed: true,
        }
      ],
      fieldsToSave: [],
    }
  },
  created() {
    let config = JSON.parse(this.app.config);

    if(typeof config['authentication'] !== 'undefined') {
      this.fields.forEach((field) => {
        field.value = config['authentication'][field.param] || '';
      });
    }
  },
  methods: {
    setupOvh() {
      this.loading = true;

      let setup = {};

      if (this.fieldsToSave.length < 1) {
        this.loading = false;
        return;
      }

      this.fields.forEach((field) => {
        if (this.fieldsToSave.includes(field.param)) {
          setup[field.param] = field.value;
        }
      });

      settingsService.setupApp(this.app.id, setup).then((response) => {
        if(response.status) {
          Swal.fire({
            icon: 'success',
            title: this.translate('COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_SUCCESS'),
            text: this.translate('COM_EMUNDUS_SETTINGS_INTEGRATION_OVH_SETUP_SUCCESS_DESC'),
            showConfirmButton: false,
            timer: 3000,
          }).then(() => {
            this.$emit('teamsInstalled');
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: this.translate('COM_EMUNDUS_ONBOARD_ERROR_MESSAGE'),
            text: response.message,
            showConfirmButton: false,
            timer: 3000,
          });
        }

        this.loading = false;
      }).catch(() => {
        Swal.fire({
          icon: 'error',
          title: this.translate('COM_EMUNDUS_ONBOARD_ERROR_MESSAGE'),
          showConfirmButton: false,
          timer: 3000,
        });
        this.loading = false;
      });
    },
    parameterNeedSaving(needSaving, parameter) {
      if (needSaving) {
        if (!this.fieldsToSave.find((field) => field.param === parameter.param)) {
          this.fieldsToSave.push(parameter.param);
        }
      } else {
        this.fieldsToSave = this.fieldsToSave.filter((field) => field !== parameter.param);
      }
    }
  }
}
</script>

<style scoped>

</style>