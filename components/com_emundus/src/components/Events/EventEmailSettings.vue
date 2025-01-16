<script>
/* Components */
import Parameter from "@/components/Utils/Parameter.vue";
import Info from "@/components/Utils/Info.vue";

/* Services */
import emailService from "@/services/email";

/* Stores */
import {useGlobalStore} from "@/stores/global.js";
import eventsService from "@/services/events.js";
import Swal from "sweetalert2";

export default {
  name: "EventEmailSettings",
  components: {Info, Parameter},
  emits: ['reload-event','go-back'],
  props: {
    event: Object,
  },
  data() {
    return {
      loading: true,

      fields: [
        {
          param: 'applicant_notify',
          type: 'toggle',
          value: 1,
          label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_NOTIFY',
          displayed: true,
          hideLabel: true,
          optional: true,
        },
        {
          param: 'applicant_notify_email',
          type: 'select',
          placeholder: '',
          value: 0,
          default: 0,
          label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_NOTIFY_EMAIL',
          helptext: '',
          displayed: false,
          displayedOn: 'applicant_notify',
          displayedOnValue: 1,
          options: [],
          reload: 0,
          optional: true,
        },
        // {
        //   param: 'applicant_recall',
        //   type: 'toggle',
        //   value: 1,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL',
        //   displayed: true,
        //   hideLabel: true,
        //   optional: true,
        // },
        // {
        //   param: 'applicant_recall_frequency',
        //   type: 'text',
        //   value: 1,
        //   default: 7,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY',
        //   displayed: false,
        //   displayedOn: 'applicant_recall',
        //   displayedOnValue: 1,
        //   endText: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY_END_TEXT',
        //   optional: true,
        // },
        // {
        //   param: 'applicant_recall_email',
        //   type: 'select',
        //   value: 0,
        //   default: 0,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_EMAIL',
        //   displayed: false,
        //   displayedOn: 'applicant_recall',
        //   displayedOnValue: 1,
        //   options: [],
        //   reload: 0
        // },
        // {
        //   param: 'manager_recall',
        //   type: 'toggle',
        //   value: 1,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_MANAGER_RECALL',
        //   displayed: true,
        //   hideLabel: true,
        //   optional: true,
        // },
        // {
        //   param: 'manager_recall_frequency',
        //   type: 'text',
        //   value: 1,
        //   default: 7,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY',
        //   displayed: false,
        //   displayedOn: 'manager_recall',
        //   displayedOnValue: 1,
        //   endText: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_MANAGER_RECALL_FREQUENCY_END_TEXT'
        // },
        // {
        //   param: 'manager_recall_email',
        //   type: 'select',
        //   value: 0,
        //   default: 0,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_MANAGER_RECALL_EMAIL',
        //   displayed: false,
        //   displayedOn: 'manager_recall',
        //   displayedOnValue: 1,
        //   options: [],
        //   reload: 0
        // },
        // {
        //   param: 'users_recall',
        //   type: 'toggle',
        //   value: 1,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_USERS_RECALL',
        //   displayed: true,
        //   hideLabel: true,
        //   optional: true,
        // },
        // {
        //   param: 'users_recall_frequency',
        //   type: 'text',
        //   value: 1,
        //   default: 7,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY',
        //   displayed: false,
        //   displayedOn: 'users_recall',
        //   displayedOnValue: 1,
        //   endText: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_APPLICANT_RECALL_FREQUENCY_END_TEXT',
        // },
        // {
        //   param: 'users_recall_email',
        //   type: 'select',
        //   value: 0,
        //   default: 0,
        //   label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_NOTIFICATIONS_MANAGER_RECALL_EMAIL',
        //   displayed: false,
        //   displayedOn: 'users_recall',
        //   displayedOnValue: 1,
        //   options: [],
        //   reload: 0
        // },
      ]
    }
  },
  created: function () {
    // fetch emails
    this.getEmails().then((response) => {
      if (response.status === true && this.event && this.event['notifications']) {
        for(const field of this.fields) {
          field.value = this.event['notifications'][field.param];
        }
      }

      console.log(this.fields);

      this.loading = false;
    });
  },
  methods: {
    getEmails(email_id = 0) {
      return new Promise((resolve, reject) => {
        this.loading = true;
        emailService.getEmails().then((response) => {
          if(response.status) {
            let emails = [];
            for(const email of response.data.datas) {
              emails.push({
                value: email.id,
                label: email.subject
              });
            }

            let options = [{
              value: 0,
              label: this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_EMAIL_SELECT')
            }];

            if (response.status) {
              Array.prototype.push.apply(options, emails);
            }

            this.fields.find(field => field.param === 'applicant_notify_email').options = options;
            // this.fields.find(field => field.param === 'applicant_recall_email').options = options;

            resolve({status: true, options: options});
          } else {
            reject({status: false});
          }
        });
      });
    },
    // Hooks
    checkConditional(parameter, oldValue, value) {
      // Find all fields that are displayed based on the current field
      let fields = this.fields.filter(field => field.displayedOn === parameter.param);

      // Check if the current field is displayed based on the value
      for (let field of fields) {
        field.displayed = field.displayedOnValue == value;
        if (!field.displayed) {
          if (field.default) {
            field.value = field.default;
            if(field.reload) {
              field.reload = field.reload + 1;
            }
          } else {
            field.value = '';
          }
          this.checkConditional(field, field.value, '');
        }
      }
    },

    saveBookingNotifications() {
      let notifications = {};

      let fields = this.fields;

      // Validate all fields
      const notificationsValidationFailed = fields.some((field) => {
        if (field.displayed) {
          let ref_name = 'event_emails_' + field.param;

          if (!this.$refs[ref_name][0].validate()) {
            // Return true to indicate validation failed
            return true;
          }

          notifications[field.param] = field.value;

          return false;
        }
      });

      if (notificationsValidationFailed) return;

      notifications.event_id = this.event.id;

      eventsService.saveBookingNotifications(notifications).then((response) => {
        if (response.status === true) {
          Swal.fire({
            position: 'center',
            icon: 'success',
            title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_SETUP_SUCCESS'),
            showConfirmButton: false,
            allowOutsideClick: false,
            reverseButtons: true,
            customClass: {
              title: 'em-swal-title',
              confirmButton: 'em-swal-confirm-button',
              actions: 'em-swal-single-action'
            },
            timer: 1500,
          }).then(() => {
            this.$emit('go-back');
          })
        } else {
          // Handle error
          Swal.fire({
            icon: 'error',
            title: 'Oops...',
            text: response.message,
          });
        }
      });
    }
  },
  computed: {
    disabledSubmit: function () {
      return this.fields.some((field) => {
        if (!field.optional && field.displayed) {
          return field.value === '' || field.value === 0;
        } else {
          return false;
        }
      });
    },
  }
}
</script>

<template>
  <div>
    <div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6" v-if="!loading">
      <div v-for="(field) in fields"
           v-show="field.displayed"
           :key="field.param"
      >
        <Parameter
            v-if="field.displayed"
            :ref="'event_emails_' + field.param"
            :key="field.reload ? field.reload : field.param"
            :parameter-object="field"
            :help-text-type="'above'"
            :multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
            @valueUpdated="checkConditional"
        />
      </div>
    </div>

    <div class="tw-flex tw-justify-end tw-mt-7">
      <button
          type="button"
          :disabled="disabledSubmit"
          @click="saveBookingNotifications"
          class="tw-btn-primary tw-cursor-pointer"
      >
        {{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SAVE_AND_EXIT') }}
      </button>
    </div>


    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<style scoped>

</style>