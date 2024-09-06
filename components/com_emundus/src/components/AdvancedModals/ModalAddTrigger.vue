<template>
  <!-- modalC -->
  <modal
      :name="'modalAddTrigger' + triggerAction"
      :class="'placement-' + placement + ' ' + classes"
      transition="nice-modal-fade"
      :width="'600px'"
      :delay="100"
      :adaptive="true"
      :clickToClose="false"
      @closed="beforeClose"
      @before-open="beforeOpen"
  >

    <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
      <h4>
        {{ translate("COM_EMUNDUS_ONBOARD_EMAIL_ADDTRIGGER") }}
      </h4>
      <button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>

    <div>
      <div class="tw-mb-4">
        <label>{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERMODEL") }}* :</label>
        <div class="tw-flex tw-items-center">
          <select :id="'modal-email-model'" v-if="models.length > 0" v-model="form.model" class="tw-w-full"
                  :class="{ 'is-invalid': errors.model}">
            <option v-for="(model, index) in models" :key="index" :value="model.id">{{ model.subject }}</option>
          </select>
          <p v-else class="tw-text-red-600">{{ translate('COM_EMUNDUS_ADD_TRIGGER_MISSING_EMAIL_MODELS') }}</p>
        </div>
        <span v-if="errors.model" class="tw-text-red-600 tw-mb-2">
            <span class="tw-text-red-600">{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERMODEL_REQUIRED") }}</span>
          </span>
      </div>

      <div class="tw-mb-4">
        <label>{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS") }}* :</label>
        <select :id="'modal-status-trigger'" v-model="form.status" class="tw-w-full" :class="{ 'is-invalid': errors.status}">
          <option v-for="(statu,index) in status" :key="index" :value="statu.step">{{ statu.value }}</option>
        </select>
        <span v-if="errors.status" class="tw-text-red-600 tw-mb-2">
            <span class="tw-text-red-600">{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS_REQUIRED") }}</span>
          </span>
      </div>

      <div class="tw-mb-4">
        <label>{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET") }}* :</label>
        <select :id="'modal-recipient'" v-model="form.target" class="tw-w-full" :class="{ 'is-invalid': errors.target}">
          <option value="5">{{ translate("COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS") }}</option>
          <option value="6">{{ translate("COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS") }}</option>
          <option value="1000">{{ translate("COM_EMUNDUS_ONBOARD_PROGRAM_CANDIDATES") }}</option>
        </select>
        <span v-if="errors.target" class="tw-text-red-600 tw-mb-2">
            <span class="tw-text-red-600">{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET_REQUIRED") }}</span>
          </span>
      </div>
    </div>

    <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
      <button type="button" class="tw-btn-cancel !tw-w-auto"
              @click.prevent="$emit('close')">{{ translate("COM_EMUNDUS_ONBOARD_ADD_RETOUR") }}</button>
      <button type="button"
              class="tw-btn-primary !tw-w-auto"
              @click.prevent="createTrigger()"
      >{{ translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER") }}</button>
    </div>

  </modal>
</template>

<script>
import Modal from '@/components/Modal.vue';

import emailService from '@/services/email.js';
import settingsService from '@/services/settings.js';

export default {
  name: 'modalAddTrigger',
  components: {
    Modal
  },
  props: {
    prog: Number,
    trigger: Number,
    triggerAction: String,
    classes: {
      type: String,
      default: ''
    },
    placement: {
      type: String,
      default: 'default'
    }
  },
  data() {
    return {
      errors: {
        model: false,
        status: false,
        action_status: false,
        target: false
      },
      form: {
        model: -1,
        status: null,
        action_status: null,
        target: null,
        program: this.prog
      },
      models: [],
      status: [],
      changes: false,
    };
  },
  mounted() {

  },
  methods: {
    beforeClose() {
      this.form = {
        model: -1,
        status: null,
        action_status: null,
        target: null,
        program: this.prog
      };

      this.$emit('close');
    },
    beforeOpen() {
      this.searchTerm = '';
      this.getEmailModels();
      this.getStatus();
      setTimeout(() => {
        if (this.trigger != null) {
          this.getTrigger();
        }
      }, 200);
      if (this.triggerAction === 'candidate') {
        this.form.action_status = 'to_current_user';
      } else {
        this.form.action_status = 'to_applicant';
      }
    },
    createTrigger() {
      this.errors = {
        model: false,
        status: false,
        action_status: false,
        target: false,
        selectedUsers: false,
      };
      if (this.form.model === -1) {
        this.errors.model = true;
        return 0;
      }
      if (this.form.status == null) {
        this.errors.status = true;
        return 0;
      }
      if (this.form.action_status == null) {
        this.errors.action_status = true;
        return 0;
      }
      if (this.form.target == null) {
        this.errors.target = true;
        return 0;
      }

      if (this.trigger != null) {
        emailService.updateEmailTrigger(this.trigger, this.form).then(() => {
          this.$emit('UpdateTriggers');
          this.$emit('close');
        });

      } else {
        emailService.createEmailTrigger(this.form).then(() => {
          this.$emit('UpdateTriggers');
          this.$emit('close');
        });
      }
    },
    getEmailModels() {
      emailService.getEmails().then(response => {
        if (response.status) {
          this.models = response.data.datas;
        }
      });
    },
    getStatus() {
      settingsService.getStatus().then(response => {
        this.status = response.data;
      });
    },
    getTrigger() {
      emailService.getEmailTriggerById(this.trigger).then((response) => {
        this.form.model = response.data.model;
        this.form.status = response.data.status;
        if (response.data.target != 5 && response.data.target != 6) {
          this.form.target = 1000;
        } else {
          if (response.data.to_current_user === 1) {
            this.form.target = 1000;
          } else {
            this.form.target = response.data.target;
          }
        }
      })
    },
  },
};
</script>

<style scoped>
@import '../../assets/css/modal.scss';

.placement-center {
  position: fixed;
  left: 50%;
  transform: translate(-50%, -50%);
  top: 50%;
}

</style>
