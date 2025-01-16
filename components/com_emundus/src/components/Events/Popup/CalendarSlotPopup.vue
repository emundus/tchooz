<script>
/* Components */
import Modal from "@/components/Modal.vue";
import Parameter from "@/components/Utils/Parameter.vue";

/* Services */
import eventsService from "@/services/events.js";
import {DatePicker} from "v-calendar";

/* Store */
import {useGlobalStore} from "@/stores/global.js";
import Popover from "@/components/Popover.vue";

export default {
  name: "CalendarSlotPopup" ,
  emits: ['close', 'open', 'slot-saved', 'slot-deleted'],
  components: {Popover, DatePicker, Parameter, Modal},
  props: {
    date: {
      type: String,
      default: ''
    },
    slot: {
      type: Object,
      default: null
    },
    eventId: {
      type: Number,
      default: 0
    },
    locationId: {
      type: Number,
      default: 0
    },
    duration: {
      type: Number,
      default: 0
    },
    duration_type: {
      type: String,
      default: 0
    },
    break_every: {
      type: Number,
      default: 0
    },
    break_time: {
      type: Number,
      default: 0
    },
    break_time_type: {
      type: String,
      default: 0
    },
  },
  data() {
    return {
      loading: true,
      showRepeat: false,
      displayPopover: false,

      actualLanguage: 'fr-FR',

      rooms: [],

      fields: [
        {
          param: 'users',
          type: 'multiselect',
          multiselectOptions: {
            noOptions: false,
            multiple: true,
            taggable: false,
            searchable: true,
            internalSearch: false,
            asyncRoute: 'getavailablemanagers',
            optionsPlaceholder: '',
            selectLabel: '',
            selectGroupLabel: '',
            selectedLabel: '',
            deselectedLabel: '',
            deselectGroupLabel: '',
            noOptionsText: '',
            noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
            tagValidations: [],
            options: [],
            optionsLimit: 30,
            label: 'name',
            trackBy: 'value'
          },
          value: 0,
          hideLabel: true,
          label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_MANAGER',
          placeholder: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_USERS_PLACEHOLDER',
          icon: 'group',
          displayed: true,
          optional: true,
        },
        {
          param: 'start_date',
          type: 'datetime',
          placeholder: '',
          value: 0,
          hideLabel: true,
          label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_START_DATE',
          icon: 'schedule',
          helptext: '',
          displayed: true
        },
        {
          param: 'end_date',
          type: 'datetime',
          placeholder: '',
          value: 0,
          hideLabel: true,
          label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_END_DATE',
          helptext: '',
          displayed: true
        },
        {
          param: 'room',
          type: 'select',
          placeholder: '',
          value: 0,
          hideLabel: true,
          label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_ROOM',
          icon: 'location_on',
          helptext: '',
          displayed: true,
          optional: true,
          options: [],
        },
        {
          param: 'slot_capacity',
          type: 'text',
          value: '',
          hideLabel: true,
          label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAPACITY',
          placeholder: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CAPACITY_PLACEHOLDER',
          icon: 'pin',
          helptext: '',
          displayed: true,
          optional: true,
          options: [],
        },
        {
          param: 'more_infos',
          type: 'textarea',
          value: '',
          hideLabel: true,
          label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_MORE_INFOS',
          placeholder: 'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_MORE_INFOS_PLACEHOLDER',
          icon: 'notes',
          helptext: '',
          displayed: true,
          optional: true,
          options: [],
        },
      ],

      repeat_dates: [],
      minDate: new Date(),
    }
  },
  created() {
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getShortLang;

    if(!this.$props.slot) {
      this.fields.find(field => field.param === 'start_date').value = this.roundToQuarter(this.date);

      const date = new Date(this.date);
      date.setMinutes(date.getMinutes() + 30);

      this.fields.find(field => field.param === 'end_date').value = this.roundToQuarter(null, date);
    } else {
      this.fields.find(field => field.param === 'start_date').value = this.$props.slot.start;
      this.fields.find(field => field.param === 'end_date').value = this.$props.slot.end;
      this.minDate = new Date(this.$props.slot.end);

      this.fields.forEach((field) => {
        if(this.$props.slot[field.param] && field.param !== 'start_date' && field.param !== 'end_date') {
          field.value = this.$props.slot[field.param];
        }
      });

      if(this.$props.slot.repeat_dates && this.$props.slot.repeat_dates.length > 0) {
        this.displayPopover = true;
        this.repeat_dates = this.$props.slot.repeat_dates;
      }
    }

    // fetch rooms
    this.getRooms();
  },
  methods: {
    beforeClose() {
      this.$emit('close');
    },
    beforeOpen() {
      this.$emit('open');
    },
    getRooms() {
      eventsService.getRooms(this.locationId).then((response) => {
        let options = [{
          value: 0,
          label: this.translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_GLOBAL_ROOM_SELECT')
        }];

        if (response.status) {
          Array.prototype.push.apply(options,response.data);
        }

        this.fields.find(field => field.param === 'room').options = options;
        this.loading = false;
      });
    },
    saveSlot(mode = 1) {
      let slot = {};

      // Validate all fields
      const slotValidationFailed = this.fields.some((field) => {
        if(field.displayed){
          let ref_name = 'slot_' + field.param;

          if(!this.$refs[ref_name][0].validate()) {
            // Return true to indicate validation failed
            return true;
          }

          if(field.type === 'datetime') {
            slot[field.param] = this.formatDate(new Date(field.value));
          }
          else if(field.type === 'multiselect') {
            if(field.multiselectOptions.multiple) {
              slot[field.param] = [];
              field.value.forEach((element) => {
                slot[field.param].push(element.value);
              });
            } else {
              slot[field.param] = field.value.value;
            }
          }
          else {
            slot[field.param] = field.value;
          }

          return false;
        }
      });

      if (slotValidationFailed) return;

      // Check if the start date is before the end date
      if(new Date(slot.start_date) >= new Date(slot.end_date)) {
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DATE_ERROR'),
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            confirmButton: 'em-swal-confirm-button',
            actions: 'em-swal-single-action'
          }
        });
        return;
      }

      // Check if the start date is before the current date
      if(new Date(slot.start_date) < new Date()) {
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DATE_ERROR_BEFORE_NOW'),
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            confirmButton: 'em-swal-confirm-button',
            actions: 'em-swal-single-action'
          }
        });
        return;
      }

      // Check if interval during start_date and end_date is greater than duration
      if(new Date(slot.end_date) - new Date(slot.start_date) < this.duration * 60 * 1000) {
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_ERROR'),
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            confirmButton: 'em-swal-confirm-button',
            actions: 'em-swal-single-action'
          }
        });
        return;
      }

      slot.event_id = this.eventId;
      slot.duration = this.duration;
      slot.duration_type = this.duration_type;
      slot.break_every = this.break_every;
      slot.break_time = this.break_time;
      slot.break_time_type = this.break_time_type;
      slot.mode = mode;
      slot.repeat_dates = this.repeat_dates.map(day => day.id);

      if(this.$props.slot) {
        slot.id = this.$props.slot.id;
        slot.parent_slot_id = this.$props.slot.parent_slot_id;
      }

      eventsService.saveEventSlot(slot).then((response) => {
        if(response.status === true) {
          let slots = response.data;

          Swal.fire({
            position: 'center',
            icon: 'success',
            title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_SAVED'),
            showConfirmButton: false,
            allowOutsideClick: false,
            reverseButtons: true,
            timer: 1500,
            customClass: {
              title: 'em-swal-title',
              confirmButton: 'em-swal-confirm-button',
              actions: 'em-swal-single-action'
            }
          }).then(() => {
            this.$emit('slot-saved',slots);
            this.$emit('close');
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
    },

    deleteSlot() {
      Swal.fire({
        title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM'),
        text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_TEXT'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_YES'),
        cancelButtonText: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_NO'),
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          cancelButton: 'em-swal-cancel-button'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          eventsService.deleteEventSlot(this.$props.slot.id).then((response) => {
            if(response.status === true) {
              Swal.fire({
                position: 'center',
                icon: 'success',
                title: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETED'),
                showConfirmButton: false,
                allowOutsideClick: false,
                reverseButtons: true,
                timer: 1500,
                customClass: {
                  title: 'em-swal-title',
                  confirmButton: 'em-swal-confirm-button',
                  actions: 'em-swal-single-action'
                }
              }).then(() => {
                this.$emit('slot-deleted',this.$props.slot.id);
                this.$emit('close');
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
      })
    },

    formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
      let year = date.getFullYear();
      let month = (1 + date.getMonth()).toString().padStart(2, '0');
      let day = date.getDate().toString().padStart(2, '0');
      let hours = date.getHours().toString().padStart(2, '0');
      let minutes = date.getMinutes().toString().padStart(2, '0');
      let seconds = date.getSeconds().toString().padStart(2, '0');

      return format
          .replace('YYYY', year)
          .replace('MM', month)
          .replace('DD', day)
          .replace('HH', hours)
          .replace('mm', minutes)
          .replace('ss', seconds);
    },

    roundToQuarter(stringDate = null, date = null) {
      if (stringDate) {
        date = new Date(stringDate);
      }

      let minutes = date.getMinutes();
      let roundedMinutes = Math.ceil(minutes / 10) * 10;
      date.setMinutes(roundedMinutes);
      date.setSeconds(0);
      return this.formatDate(date);
    },

    onDayClick(day) {
      if(!day.isDisabled) {
        const idx = this.repeat_dates.findIndex(d => d.id === day.id);
        if (idx >= 0) {
          this.repeat_dates.splice(idx, 1);
        } else {
          this.repeat_dates.push({
            id: day.id,
            date: day.date,
          });
        }
      }
    },

    formatDuplicateDate(date) {
      const [year, month, day] = date.split('-');
      return `${day}-${month}-${year}`;
    },

    removeDate(date) {
      const idx = this.repeat_dates.findIndex(d => d.id === date);
      if (idx >= 0) {
        this.repeat_dates.splice(idx, 1);
      }
    },

    onFormChange(parameter,oldValue,value) {
      if(parameter.param == 'end_date') {
        // Set min date for repeat_dates
        this.minDate = new Date(value);
      }
    }
  },
  computed: {
    disabledSubmit: function () {
      return this.fields.some((field) => {
        if(!field.optional) {
          return field.value === '' || field.value === 0;
        } else {
          return false;
        }
      });
    },
    dates() {
      return this.repeat_dates.map(day => day.date);
    },
    attributes() {
      return this.dates.map(date => ({
        highlight: true,
        dates: date,
      }));
    },
  }
}
</script>

<template>
  <modal
      :name="'calendar-slot-modal'"
      :class="'placement-center tw-rounded tw-shadow-modal tw-px-4 tw-max-h-[80vh] tw-overflow-y-auto tw-overflow-x-hidden'"
      transition="nice-modal-fade"
      :width="'60%'"
      :delay="100"
      :adaptive="true"
      :clickToClose="false"
      @closed="beforeClose"
      @before-open="beforeOpen"
  >
    <div class="tw-pt-4 tw-sticky tw-top-0 tw-bg-white tw-border-b tw-border-neutral-300 tw-z-10">
      <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <h2 v-if="slot">
          {{ translate("COM_EMUNDUS_ONBOARD_EDIT_SLOT") }}
        </h2>
        <h2 v-else>
          {{ translate("COM_EMUNDUS_ONBOARD_ADD_SLOT") }}
        </h2>
        <button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
          <span class="material-symbols-outlined">close</span>
        </button>
      </div>
    </div>

    <div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
      <div v-for="(field) in fields"
           v-show="field.displayed"
           :key="field.param"
           :class="{'-tw-mt-3 tw-ml-7': field.param === 'end_date', 'tw-w-fit': field.param === 'start_date' || field.param === 'end_date'}"
      >
        <Parameter
            :ref="'slot_' + field.param"
            :parameter-object="field"
            :multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
            @valueUpdated="onFormChange"
        />
      </div>

      <div>
        <div class="tw-flex tw-flex-col tw-gap-3">
          <div class="tw-flex tw-items-center tw-gap-2">
            <span class="material-symbols-outlined">repeat</span>
            <button type="button" class="tw-flex tw-items-center tw-gap-1" @click="showRepeat = !showRepeat">
              <span>{{ translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_REPEAT") }}</span>
              <span class="material-symbols-outlined tw-text-neutral-900" :class="{'tw-rotate-90': showRepeat}">chevron_right</span>
              <span v-if="repeat_dates.length > 0" class="tw-rounded-full tw-bg-profile-full tw-px-2 tw-py-1 tw-text-white">
                {{ repeat_dates.length }} {{ translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_REPEAT_SELECTED") }}
              </span>
            </button>
          </div>

          <div v-show="showRepeat" class="tw-flex tw-flex-col tw-gap-2">
            <DatePicker
                :id="'slot_repeat'"
                mode="date"
                title-position="left"
                :locale="actualLanguage"
                :attributes="attributes"
                :columns="2"
                :min-date="minDate"
                expanded
                @dayclick="onDayClick"
            >
            </DatePicker>

            <div class="tw-flex tw-items-center tw-gap-2 tw-flex-wrap">
              <div v-for="date in repeat_dates" class="tw-flex tw-items-center tw-gap-1 tw-px-2 tw-py-1 tw-bg-profile-full tw-text-white tw-rounded-full">
                <span @click="togglePopover">{{ formatDuplicateDate(date.id) }}</span>
                <span class="material-symbols-outlined tw-text-white" @click="removeDate(date.id)">close</span>
              </div>
            </div>
          </div>

        </div>

        <div>

        </div>
      </div>
    </div>

    <div
        class="tw-flex tw-mt-7 tw-mb-2"
        :class="{ 'tw-justify-end': !slot, 'tw-justify-between': slot }"
    >
      <div class="tw-flex tw-items-center tw-gap-4">
        <button v-if="slot"
                type="button"
                class="tw-text-red-500 !tw-w-auto"
                @click.prevent="deleteSlot()"
        >
          {{ translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_DELETE") }}
        </button>
      </div>

      <popover
          v-if="slot && displayPopover"
          :position="'top-left'"
          :icon="'keyboard_arrow_down'"
          :button="translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT')"
          class="custom-popover-arrow">
        <ul style="list-style-type: none; margin: 0; padding-left:0px;white-space: nowrap" class="tw-flex tw-flex-col tw-justify-center tw-h-full">
          <li class="tw-p-2 tw-cursor-pointer hover:tw-bg-neutral-300" @click="saveSlot(1)">{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ONLY_ONE') }}</li>
          <li class="tw-p-2 tw-cursor-pointer hover:tw-bg-neutral-300" @click="saveSlot(2)">{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ALL_FUTURES') }}</li>
          <li class="tw-p-2 tw-cursor-pointer hover:tw-bg-neutral-300" @click="saveSlot(3)">{{ translate('COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT_ALL') }}</li>
        </ul>
      </popover>

      <button v-else
              type="button"
              class="tw-btn-primary !tw-w-auto"
              :disabled="disabledSubmit"
              @click.prevent="saveSlot(0)"
      >
        <span v-if="slot">{{ translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_EDIT") }}</span>
        <span v-else>{{ translate("COM_EMUNDUS_ONBOARD_ADD_SLOT_CREATE") }}</span>
      </button>
    </div>
  </modal>
</template>

<style scoped>
@import '../../../assets/css/modal.scss';

.placement-center {
  position: fixed;
  left: 50%;
  transform: translate(-50%, -50%);
  top: 50%;
}
</style>