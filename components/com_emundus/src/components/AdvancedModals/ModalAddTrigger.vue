<template>
  <!-- modalC -->
  <span :id="'modalAddTrigger' + triggerAction">
    <modal
        :name="'modalAddTrigger' + triggerAction"
        height="auto"
        transition="nice-modal-fade"
        :min-width="200"
        :min-height="200"
        :delay="100"
        :adaptive="true"
        :clickToClose="false"
        @closed="beforeClose"
        @before-open="beforeOpen"
    >
      <ModalEmailPreview
          :model="this.form.model"
          :models="this.models"
      />

      <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <h4>
          {{ addTrigger }}
        </h4>
        <button class="tw-cursor-pointer tw-bg-transparent"
                @click.prevent="$modal.hide('modalAddTrigger' + triggerAction)">
          <span class="material-icons-outlined">close</span>
        </button>
      </div>

      <div>
        <div class="tw-mb-4">
          <label>{{ Model }}* :</label>
          <div class="tw-flex tw-items-center">
            <select v-if="models.length > 0" v-model="form.model" class="tw-w-full"
                    :class="{ 'is-invalid': errors.model}">
              <option v-for="(model, index) in models" :key="index" :value="model.id">{{ model.subject }}</option>
            </select>
	          <p v-else class="tw-text-red-500">{{ translate('COM_EMUNDUS_ADD_TRIGGER_MISSING_EMAIL_MODELS') }}</p>
          </div>
          <span v-if="errors.model" class="tw-text-red-500 tw-mb-2">
            <span class="tw-text-red-500">{{ ModelRequired }}</span>
          </span>
        </div>

        <div class="tw-mb-4">
          <label>{{ Status }}* :</label>
          <select v-model="form.status" class="tw-w-full" :class="{ 'is-invalid': errors.status}">
            <option v-for="(statu,index) in status" :key="index" :value="statu.step">{{ statu.value }}</option>
          </select>
          <span v-if="errors.status" class="tw-text-red-500 tw-mb-2">
            <span class="tw-text-red-500">{{ StatusRequired }}</span>
          </span>
        </div>

        <div class="tw-mb-4">
          <label>{{ Target }}* :</label>
          <select v-model="form.target" class="tw-w-full" :class="{ 'is-invalid': errors.target}">
            <option value="5">{{ Administrators }}</option>
            <option value="6">{{ Evaluators }}</option>
            <option value="1000">{{ Candidates }}</option>
            <!--            <option value="0">{{DefinedUsers}}</option>-->
          </select>
          <span v-if="errors.target" class="tw-text-red-500 tw-mb-2">
            <span class="tw-text-red-500">{{ TargetRequired }}</span>
          </span>
        </div>

        <div class="tw-mb-4" v-if="form.target == 0" style="align-items: baseline">
          <label>{{ ChooseUsers }}* :</label>
          <div class="tw-flex tw-items-center">
            <input type="text" class="tw-w-full" :placeholder="Search" v-model="searchTerm" @keyup="searchUserByTerm">
            <button type="button" class="tw-bg-transparent tw-ml-2" @click="searchUserByTerm">
              <span class="material-icons-outlined">search</span>
            </button>
          </div>
          <div class="tw-flex tw-items-center">
            <input type="checkbox" @click="selectAllUsers" v-model="selectall">
            <label class="tw-ml-2">
              {{ SelectAll }}
            </label>
          </div>

          <div :class="{ 'is-invalid': errors.selectedUsers}">
            <div v-for="(user, index) in users" :key="index">
              <input type="checkbox" v-model="selectedUsers[user.id]">
              <div class="tw-ml-2">
                  <p>{{ user.name }}</p>
                  <p>{{ user.email }}</p>
              </div>
            </div>
          </div>
          <span v-if="errors.selectedUsers" class="tw-text-red-500 tw-mb-2">
            <span class="tw-text-red-500">{{ UsersRequired }}</span>
          </span>
        </div>
      </div>

      <div class="tw-flex tw-items-center tw-justify-between tw-mb-2">
        <button type="button" class="em-secondary-button !tw-w-auto"
                @click.prevent="$modal.hide('modalAddTrigger' + triggerAction)">{{ Retour }}</button>
        <button type="button"
                class="em-primary-button !tw-w-auto"
                @click.prevent="createTrigger()"
        >{{ Continuer }}</button>
      </div>

    </modal>
  </span>
</template>

<script>
import axios from "axios";
import ModalEmailPreview from "./ModalEmailPreview";

const qs = require("qs");

export default {
  name: "modalAddTrigger",
  components: {ModalEmailPreview},
  props: {prog: Number, trigger: Number, triggerAction: String},
  data() {
    return {
      errors: {
        model: false,
        status: false,
        action_status: false,
        target: false,
        selectedUsers: false,
      },
      form: {
        model: -1,
        status: null,
        action_status: null,
        target: null,
        program: this.prog
      },
      users: [],
      selectedUsers: [],
      models: [],
      status: [],
      searchTerm: '',
      changes: false,
      selectall: false,
      addTrigger: this.translate("COM_EMUNDUS_ONBOARD_EMAIL_ADDTRIGGER"),
      Retour: this.translate("COM_EMUNDUS_ONBOARD_ADD_RETOUR"),
      Continuer: this.translate("COM_EMUNDUS_ONBOARD_ADD_CONTINUER"),
      Model: this.translate("COM_EMUNDUS_ONBOARD_TRIGGERMODEL"),
      ModelRequired: this.translate("COM_EMUNDUS_ONBOARD_TRIGGERMODEL_REQUIRED"),
      Status: this.translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS"),
      StatusRequired: this.translate("COM_EMUNDUS_ONBOARD_TRIGGERSTATUS_REQUIRED"),
      Target: this.translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET"),
      TargetRequired: this.translate("COM_EMUNDUS_ONBOARD_TRIGGERTARGET_REQUIRED"),
      Administrators: this.translate("COM_EMUNDUS_ONBOARD_PROGRAM_ADMINISTRATORS"),
      Evaluators: this.translate("COM_EMUNDUS_ONBOARD_PROGRAM_EVALUATORS"),
      Candidates: this.translate("COM_EMUNDUS_ONBOARD_PROGRAM_CANDIDATES"),
      DefinedUsers: this.translate("COM_EMUNDUS_ONBOARD_PROGRAM_DEFINED_USERS"),
      ChooseUsers: this.translate("COM_EMUNDUS_ONBOARD_TRIGGER_CHOOSE_USERS"),
      UsersRequired: this.translate("COM_EMUNDUS_ONBOARD_TRIGGER_USERS_REQUIRED"),
      Search: this.translate("COM_EMUNDUS_ONBOARD_SEARCH_USERS"),
      ChangedActionStatus: this.translate("COM_EMUNDUS_ONBOARD_CHANGED_ACTION_STATUS"),
      TheCandidate: this.translate("COM_EMUNDUS_ONBOARD_THE_CANDIDATE"),
      Manual: this.translate("COM_EMUNDUS_ONBOARD_MANUAL"),
      SelectAll: this.translate("COM_EMUNDUS_ONBOARD_SELECT_ALL"),
    };
  },
  methods: {
    beforeClose(event) {
      this.trigger = null;
      this.form = {
        model: -1,
        status: null,
        action_status: null,
        target: null,
        program: this.prog
      };
    },
    beforeOpen(event) {
      this.searchTerm = '';
      this.getUsers();
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
      } else if (this.form.target == 0) {
        if (this.selectedUsers.length === 0) {
          this.errors.selectedUsers = true;
          return 0;
        }
      }

      if (this.trigger != null) {
        axios({
          method: "post",
          url: 'index.php?option=com_emundus&controller=email&task=updatetrigger',
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          data: qs.stringify({
            tid: this.trigger,
            trigger: this.form,
            users: this.selectedUsers
          })
        }).then((rep) => {
          this.selectedUsers = [];
          this.$emit("UpdateTriggers");
          this.$modal.hide('modalAddTrigger' + this.triggerAction)
        });
      } else {
        axios({
          method: "post",
          url: 'index.php?option=com_emundus&controller=email&task=createtrigger',
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          data: qs.stringify({
            trigger: this.form,
            users: this.selectedUsers
          })
        }).then((rep) => {
          this.selectedUsers = [];
          this.$emit("UpdateTriggers");
          this.$modal.hide('modalAddTrigger' + this.triggerAction)
        });
      }
    },
    getUsers() {
      axios.get("index.php?option=com_emundus&controller=programme&task=getuserswithoutapplicants")
          .then(response => {
            this.users = response.data.data;
          });
    },
    searchUserByTerm() {
      axios.get("index.php?option=com_emundus&controller=programme&task=searchuserbytermwithoutapplicants&term=" + this.searchTerm)
          .then(response => {
            this.users = response.data.data;
          });
    },
    getEmailModels() {
      axios.get("index.php?option=com_emundus&controller=email&task=getallemail")
          .then(response => {
            if (response.data.status) {
              this.models = response.data.data.datas;
            }
          });
    },
    getStatus() {
      axios.get("index.php?option=com_emundus&controller=email&task=getstatus")
          .then(response => {
            this.status = response.data.data;
          });
    },
    getTrigger() {
      axios.get("index.php?option=com_emundus&controller=email&task=gettriggerbyid&tid=" + this.trigger)
          .then(response => {
            this.form.model = response.data.data.model;
            this.form.status = response.data.data.status;
            if (response.data.data.target == null && response.data.data.to_current_user === 0 && response.data.data.to_applicant === 0) {
              this.form.target = 0;
              response.data.data.users.forEach(element => {
                this.selectedUsers[element.user_id] = true;
              });
            } else if (response.data.data.target != 5 && response.data.data.target != 6) {
              this.form.target = 1000;
            } else {
              if (response.data.data.to_current_user === 1) {
                this.form.target = 1000;
              } else {
                this.form.target = response.data.data.target;
              }
            }
          });
    },
    selectAllUsers() {
      this.users.forEach(element => {
        if (!this.selectall) {
          this.selectedUsers[element.id] = true;
        } else {
          this.selectedUsers[element.id] = false;
        }
      });
      this.$forceUpdate();
    }
  },
};
</script>

<style scoped>
@import "../../assets/css/modal.scss";
</style>
