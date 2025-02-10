<script>
/* Components */
import Parameter from "@/components/Utils/Parameter.vue";

/* Services */
import settingsService from "@/services/settings";
import Tabs from "@/components/Utils/Tabs.vue";
import History from "@/views/History.vue";

export default {
  name: "DynamicsSetup",
  components: {History, Tabs, Parameter},
  props: {
    app: {
      type: Object,
      required: true,
    }
  },
  data() {
    return {
      loading: false,

      tabs: [
        {
          id: 1,
          name: 'COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_AUTH',
          icon: 'encrypted',
          active: true,
          displayed: true,
        },
        /*{
          id: 2,
          name: 'COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_CONFIG',
          icon: 'manufacturing',
          active: false,
          displayed: true
        },*/
        {
          id: 2,
          name: 'COM_EMUNDUS_GLOBAL_HISTORY',
          icon: 'history',
          active: false,
          displayed: true
        },
      ],

      fields: [
        {
          param: 'domain',
          type: 'text',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_DOMAIN',
          helptext: '',
          displayed: true,
        },
        {
          param: 'client_id',
          type: 'text',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_ID',
          helptext: '',
          displayed: true,
        },
        {
          param: 'client_secret',
          type: 'password',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_SECRET',
          helptext: '',
          displayed: true,
        },
        {
          param: 'tenant_id',
          type: 'text',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_TENANT_ID',
          helptext: '',
          displayed: true,
        }
      ]
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
    setupDynamics() {
      this.loading = true;

      let setup = {};

      const teamsValidationFailed = this.fields.some((field) => {
        let ref_name = 'dynamics_' + field.param;

        if (!this.$refs[ref_name][0].validate()) {
          // Return true to indicate validation failed
          return true;
        }

        setup[field.param] = field.value;
        return false;
      });

      if (teamsValidationFailed) return;

      settingsService.setupApp(this.app.id, setup).then(response => {
        if (response.status) {
          Swal.fire({
            icon: 'success',
            title: this.translate('COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_SUCCESS'),
            text: this.translate('COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_SUCCESS_DESC'),
            showConfirmButton: false,
            timer: 3000,
          }).then(() => {
            this.$emit('dynamicsInstalled');
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
      });
    }
  },
  computed: {
    disabledSubmit: function () {
      return this.fields.some((field) => {
        if (!field.optional) {
          return field.value === '' || field.value === 0;
        } else {
          return false;
        }
      });
    }
  }
}
</script>

<template>
  <div
      class="tw-relative tw-flex tw-flex-col tw-justify-between tw-w-full tw-font-medium rtl:tw-text-right tw-text-black tw-border tw-border-neutral-300 tw-rounded-[15px] tw-bg-white tw-mb-6 tw-gap-3 tw-p-4">
    <Tabs :tabs="tabs"/>

    <h3>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP') }}</h3>

    <div class="tw-mt-2">
      <p class="tw-text-medium tw-text-sm tw-text-neutral-800">
        {{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_DYNAMICS_SETUP_DESC') }}
      </p>
    </div>

    <div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6" v-if="tabs[0].active">
      <div v-for="(field) in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
        <Parameter
            :ref="'dynamics_' + field.param"
            :parameter-object="field"
            :help-text-type="'above'"
        />
      </div>

      <div>
        <button class="tw-btn-primary tw-w-fit tw-float-right" :disabled="disabledSubmit" @click="setupDynamics()">
          <span v-if="app.enabled === 0 && app.config === '{}'">{{
              translate('COM_EMUNDUS_SETTINGS_INTEGRATION_ADD')
            }}</span>
          <span v-else>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE') }}</span>
        </button>
      </div>
    </div>

    <div v-if="tabs[1].active">
      <History :extension="'com_emundus.microsoftdynamics'" :columns="['title', 'message_language_key', 'log_date', 'user_id', 'status', 'diff']" />
    </div>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<style scoped>

</style>