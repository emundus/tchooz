<template>
  <div>
    <ModalAddTrigger
        v-if="showModalAddTriggerApplicant"
        :prog="this.prog"
        :trigger="this.triggerSelected"
        :triggerAction="'candidate'"
        :key="'candidate' + candidate_trigger"
        :classes="'tw-rounded tw-shadow tw-p-4'"
        :placement="'center'"
        @UpdateTriggers="getTriggers"
        @close="showModalAddTriggerApplicant = false;"
    />
    <ModalAddTrigger
        v-else-if="showModalAddTriggerManual"
        :prog="this.prog"
        :trigger="this.triggerSelected"
        :triggerAction="'manual'"
        :key="'manual-' + manual_trigger"
        :classes="'tw-rounded tw-shadow tw-p-4'"
        :placement="'center'"
        @UpdateTriggers="getTriggers"
        @close="showModalAddTriggerManual = false;"
    />

    <div id="candidate-action">
      <div class="tw-flex tw-items-center">
        <h4>{{ translate('COM_EMUNDUS_ONBOARD_CANDIDATE_ACTION') }}</h4>
      </div>
      <p>{{ translate('COM_EMUNDUS_ONBOARD_THE_CANDIDATE_DESCRIPTION') }}</p>

      <button class="tw-btn-primary tw-w-auto tw-mt-2"
              @click="showModalAddTriggerApplicant = true; triggerSelected = null">
        {{ translate("COM_EMUNDUS_ONBOARD_EMAIL_ADDTRIGGER") }}
      </button>

      <transition-group :name="'slide-down'" type="transition" class="em-grid-2 tw-m-4" style="margin-left: 0">
        <div
            v-for="trigger in applicantTriggers"
            :key="trigger.trigger_id"
            class="em-email-card mt-4"
        >
          <div class="tw-flex tw-items-center tw-items-start tw-justify-between tw-w-full">
            <div>
              <span class="tw-mb-2">{{ trigger.subject }}</span>
              <div class="tw-mt-2 tw-mb-2">
                <span style="font-weight: bold">{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET") }} : </span>
                <span v-for="(user, index) in triggerUsersWithProfile(trigger)" :key="'user_' + index">
                  {{ user.firstname }} {{ user.lastname }}
                  <span v-if="index != Object.keys(trigger.users).length - 1">, </span>
                </span>
                <span
                    v-if="trigger.users.length == 0 && trigger.profile != 5 && trigger.profile != 6">{{ translate("COM_EMUNDUS_ONBOARD_THE_CANDIDATE") }}</span>
                <span v-if="trigger.profile == 5">{{ translate("COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS") }}</span>
                <span v-if="trigger.profile == 6">{{ translate("COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS") }}</span>
              </div>
              <span>{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS") }} {{ trigger.status }}</span>
            </div>

            <div class="tw-flex tw-items-center em-flex-end">
              <a class="tw-mr-2 tw-cursor-pointer" @click="editTrigger(trigger)">
                <span class="material-icons-outlined">edit</span>
              </a>
              <a class="tw-cursor-pointer" @click="removeTrigger(trigger.trigger_id)" :title="removeTrig">
                <span class="material-icons-outlined tw-text-red-600">close</span>
              </a>
            </div>
          </div>
        </div>
      </transition-group>
    </div>

    <div id="manager-action">
      <div class="tw-flex tw-items-center">
        <h4 class="tw-mt-4">{{ translate('COM_EMUNDUS_ONBOARD_MANAGER_ACTION') }}</h4>
      </div>
      <p>{{ translate('COM_EMUNDUS_ONBOARD_MANUAL_DESCRIPTION') }}</p>

      <button class="tw-btn-primary tw-w-auto tw-mt-2"
              @click="showModalAddTriggerManual = true; triggerSelected = null">
        {{ translate("COM_EMUNDUS_ONBOARD_EMAIL_ADDTRIGGER") }}
      </button>

      <transition-group :name="'slide-down'" type="transition" class="em-grid-2 tw-m-4" style="margin-left: 0">
        <div v-for="trigger in manualTriggers" :key="trigger.trigger_id" class="em-email-card mt-4">

          <div class="tw-flex tw-items-center tw-items-start tw-justify-between tw-w-full">
            <div>
              <span class="tw-mb-2">{{ trigger.subject }}</span>
              <div class="tw-mt-2 tw-mb-2">
                <span style="font-weight: bold">{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET") }} : </span>
                <span
                    v-for="(user, index) in triggerUsersNoProfile(trigger)"
                    :key="'user_manual_' + index"
                >
                {{ user.firstname }} {{ user.lastname }}
                <span v-if="index != Object.keys(trigger.users).length - 1">, </span>
              </span>
                <span v-if="trigger.users.length == 0 && trigger.profile != 5 && trigger.profile != 6">{{ translate("COM_EMUNDUS_ONBOARD_THE_CANDIDATE") }}</span>
                <span v-if="trigger.profile == 5">{{ translate("COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS") }}</span>
                <span v-if="trigger.profile == 6">{{ translate("COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS") }}</span>
              </div>
              <span>{{ translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS") }} {{ trigger.status }}</span>
            </div>

            <div class="tw-flex tw-items-center em-flex-end">
              <a class="tw-cursor-pointer tw-mr-2" @click="editTrigger(trigger)">
                <span class="material-icons-outlined">edit</span>
              </a>
              <a class="tw-cursor-pointer" @click="removeTrigger(trigger.trigger_id)">
                <span class="material-icons-outlined tw-text-red-600">close</span>
              </a>
            </div>
          </div>
        </div>
      </transition-group>
    </div>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
import ModalAddTrigger from "@/components/AdvancedModals/ModalAddTrigger.vue";
import axios from "axios";

import qs from "qs";

export default {
  name: "addEmail",
  components: {ModalAddTrigger},
  props: {
    funnelCategorie: String,
    prog: Number
  },

  data() {
    return {
      triggers: [],
      triggerSelected: null,
      manual_trigger: 0,
      candidate_trigger: 0,
      loading: false,
      showModalAddTriggerApplicant: false,
      showModalAddTriggerManual: false
    };
  },
  methods: {
    editTrigger(trigger) {
      this.triggerSelected = trigger.trigger_id;
      this.manual_trigger += 1;
      this.candidate_trigger += 1;
      setTimeout(() => {
        if (trigger.candidate == 1) {
          this.showModalAddTriggerApplicant = true;
        } else {
          this.showModalAddTriggerManual = true;
        }
      }, 500);
    },
    removeTrigger(trigger) {
      axios({
        method: "post",
        url: 'index.php?option=com_emundus&controller=email&task=removetrigger',
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        data: qs.stringify({
          tid: trigger,
        })
      }).then(() => {
        this.getTriggers();
      });
    },
    getTriggers() {
      axios.get("index.php?option=com_emundus&controller=email&task=gettriggersbyprogram&pid=" + this.prog)
        .then(response => {
          this.triggers = response.data.data;

          this.loading = false;
        });
    },
    triggerUsersWithProfile(trigger) {
      if (trigger.profile !== null) {
        return trigger.users
      }

      return [];
    },
    triggerUsersNoProfile(trigger) {
      if (trigger.profile === null && trigger.users.length > 0) {
        return trigger.users
      }

      return [];
    }
  },
  computed: {
    applicantTriggers() {
      return this.triggers.filter(trigger => trigger.candidate == 1);
    },
    manualTriggers() {
      return this.triggers.filter(trigger => trigger.manual == 1);
    }
  },
  created() {
    this.loading = true;
    this.getTriggers();
  }
};
</script>
<style scoped>
.em-email-card {
  background: white;
  border-radius: 5px;
  padding: 16px 24px;
}

a.tw-cursor-pointer:nth-child(2) .material-icons {
  color: #DB333E;
}

a.tw-cursor-pointer:nth-child(2):hover .material-icons {
  color: #C31924;
}
</style>
