<script>
import {v4 as uuid} from 'uuid'

/* Components */
import Parameter from "@/components/Utils/Parameter.vue";

/* Services */
import eventsService from "@/services/events";
import settingsService from "@/services/settings.js";

/* Stores */
import {useGlobalStore} from "@/stores/global.js";

export default {
  name: "LocationForm",
  components: {Parameter},
  emits: ['close', 'open'],
  props: {
    isModal: {
      type: Boolean,
      default: false,
    },
    id: {
      type: Number,
      default: 0
    }
  },
  data() {
    return {
      location_id: 0,
      location: {},

      loading: true,
      specifications: [],

      fields: [
        {
          param: 'name',
          type: 'text',
          placeholder: '',
          maxlength: 150,
          value: '',
          label: 'COM_EMUNDUS_ONBOARD_ADD_LOCATION_NAME',
          helptext: '',
          displayed: true,
        },
        {
          param: 'address',
          type: 'textarea',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_ONBOARD_ADD_LOCATION_ADDRESS',
          helptext: '',
          displayed: true,
          optional: true
        },
        {
          param: 'description',
          type: 'textarea',
          placeholder: '',
          value: '',
          label: 'COM_EMUNDUS_ONBOARD_ADD_LOCATION_DESCRIPTION',
          helptext: '',
          displayed: true,
          optional: true,
        },
      ],

      rooms: []
    }
  },
  created() {
    if(useGlobalStore().datas.locationid) {
      this.location_id = parseInt(useGlobalStore().datas.locationid.value);
    }
    else if(this.$props.id) {
      this.location_id = this.$props.id;
    }

    this.getSpecifications().then((response) => {
      if (response) {
        if (this.location_id) {
          this.getLocation(this.location_id);
        } else {
          this.loading = false;
        }
      }
    });
  },
  methods: {
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },

    // Form
    addRepeatBlock(name = '', specifications = []) {
      let new_room = {};
      new_room.id = uuid();
      new_room.fields = [
        {
          param: 'name',
          type: 'text',
          placeholder: '',
          maxlength: 150,
          value: name,
          label: 'COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOM_NAME',
          helptext: '',
          displayed: true,
        },
        {
          param: 'specifications',
          type: 'multiselect',
          multiselectOptions: {
            noOptions: false,
            multiple: true,
            taggable: false,
            searchable: true,
            optionsPlaceholder: '',
            selectLabel: '',
            selectGroupLabel: '',
            selectedLabel: '',
            deselectedLabel: '',
            deselectGroupLabel: '',
            noOptionsText: '',
            noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
            tagValidations: [],
            options: this.specifications,
            label: 'label',
            trackBy: 'value'
          },
          value: specifications,
          label: 'COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOM_SPECS',
          helptext: '',
          placeholder: '',
          displayed: true,
          optional: true,
        },
      ];
      this.rooms.push(new_room);
    },

    removeRepeatBlock(room_id) {
      const key = this.rooms.findIndex(room => room.id === room_id);
      this.rooms.splice(key, 1);

      this.$forceUpdate();
    },

    duplicateRepeatBlock(room_id) {
      const key = this.rooms.findIndex(room => room.id === room_id);

      let new_room = {};
      new_room.id = uuid();

      // Manually deep clone the array without reactivity
      new_room.fields = this.rooms[key].fields.map(field => {
        return {
          ...field,
          // Deep copy the nested `multiselectOptions` object
          multiselectOptions: field.multiselectOptions
              ? {...field.multiselectOptions}
              : null,
        };
      });

      // Push the deep-cloned array to the `rooms` array
      this.rooms.push(new_room);
    },

    // Services
    getSpecifications() {
      return new Promise((resolve, reject) => {
        eventsService.getSpecifications().then((response) => {
          if (response.status) {
            this.specifications = response.data;

            resolve(true);
          } else {
            reject('Failed to get specifications');
          }
        });
      });
    },

    getLocation(location_id) {
      eventsService.getLocation(location_id).then((response) => {
        if (response.status) {
          this.location = response.data;

          for (const field of this.fields) {
            if (this.location[field.param]) {
              field.value = this.location[field.param];
            }
          }

          for (const room of this.location.rooms) {
            this.addRepeatBlock(room.label, room.specifications);
          }
        }

        this.loading = false;
      });
    },

    saveLocation() {
      let location = {};
      location.rooms = [];

      // Validate all fields
      const locationValidationFailed = this.fields.some((field) => {
        let ref_name = 'location_' + field.param;

        if (!this.$refs[ref_name][0].validate()) {
          // Return true to indicate validation failed
          return true;
        }

        location[field.param] = field.value;
        return false;
      });

      if (locationValidationFailed) return;

      // Validate all rooms
      const roomValidationFailed = this.rooms.some((room) => {
        let roomObject = {};

        room.fields.forEach((field) => {
          let ref_name = 'room_' + room.id + '_' + field.param;

          if (!this.$refs[ref_name][0].validate()) {
            // Return true to indicate validation failed
            return true;
          }

          roomObject[field.param] = field.value;
        });

        location.rooms.push(roomObject);
        return false;
      });

      if(this.location_id) {
        location.id = this.location_id;
      }

      eventsService.saveLocation(location).then((response) => {
        if (response.status === true) {
          if (this.$props.isModal) {
            this.$emit('close', response.data);
          } else {
            this.redirectJRoute('index.php?option=com_emundus&view=events');
          }
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
      let field_bool = this.fields.some((field) => {
        if (!field.optional) {
          return field.value === '' || field.value === 0;
        }
      });
      if (!field_bool && this.rooms.length > 0) {
        return this.rooms.some((room) => {
          return room.fields.some((field) => {
            if (!field.optional) {
              return field.value === '' || field.value === 0 || field.value.length === 0;
            } else {
              return false
            }
          });
        });
      }
      return field_bool;
    }
  }
}
</script>

<template>
  <div>
    <div v-if="!loading"
         :class="{'tw-rounded-coordinator-cards tw-shadow-card tw-bg-neutral-0 tw-border tw-border-neutral-300 tw-p-6': !isModal}"
    >
      <div v-if="isModal" class="tw-pt-4 tw-sticky tw-top-0 tw-bg-white tw-border-b tw-border-neutral-300 tw-z-10">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
          <h2>
            {{ translate("COM_EMUNDUS_ONBOARD_ADD_LOCATION") }}
          </h2>
          <button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
      </div>

      <div v-else>
        <div class="tw-flex tw-items-center tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300"
             @click="redirectJRoute('index.php?option=com_emundus&view=events')">
          <span class="material-symbols-outlined tw-text-neutral-600">navigate_before</span>
          <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
        </div>

        <h1 class="tw-mt-4">
          {{
            this.location && Object.keys(this.location).length > 0
                ? translate('COM_EMUNDUS_ONBOARD_EDIT_LOCATION') + " " + this.location["name"]
                : translate('COM_EMUNDUS_ONBOARD_ADD_LOCATION')
          }}
        </h1>

        <hr class="tw-mt-1.5 tw-mb-8">
      </div>

      <div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
        <div v-for="(field) in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
          <Parameter
              :ref="'location_' + field.param"
              :parameter-object="field"
          />
        </div>

        <!-- REPEAT GROUP -->
        <div class="tw-mt-4 tw-flex tw-flex-col tw-gap-3">
          <h3>{{ translate("COM_EMUNDUS_ONBOARD_ADD_LOCATION_ROOMS") }}</h3>

          <div v-for="(room) in rooms" :key="room.id"
               class="tw-flex tw-flex-col tw-gap-2 tw-bg-white tw-rounded-coordinator tw-border tw-border-neutral-400 tw-px-3 tw-py-4">
            <div class="tw-flex tw-justify-end tw-items-center tw-gap-2">
              <button type="button"
                      @click="duplicateRepeatBlock(room.id)" class="w-auto">
                <span class="material-symbols-outlined !tw-text-neutral-900">content_copy</span>
              </button>
              <button v-if="rooms.length > 0" type="button"
                      @click="removeRepeatBlock(room.id)" class="w-auto">
                <span class="material-symbols-outlined tw-text-red-600">close</span>
              </button>
            </div>

            <div class="tw-flex tw-flex-col tw-gap-6">
              <div v-for="(field) in room.fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
                <Parameter
                    :ref="'room_' + room.id + '_' + field.param"
                    :parameter-object="field"
                    :multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
                />
              </div>
            </div>
          </div>

          <div class="tw-flex tw-justify-end">
            <button type="button" @click="addRepeatBlock()" class="tw-mt-2 tw-w-auto tw-flex tw-items-center tw-gap-1">
              <span class="material-symbols-outlined !tw-text-neutral-900">add</span>
              <span>{{ translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_ROOM') }}</span>
            </button>
          </div>
        </div>
      </div>

      <div class="tw-flex tw-justify-end tw-mt-7 tw-mb-2">
        <button type="button"
                class="tw-btn-primary !tw-w-auto"
                :disabled="disabledSubmit"
                @click.prevent="saveLocation()"
        >
          <span v-if="location_id">{{ translate("COM_EMUNDUS_ONBOARD_EDIT_LOCATION") }}</span>
          <span v-else>{{ translate("COM_EMUNDUS_ONBOARD_ADD_LOCATION_CREATE") }}</span>
        </button>
      </div>
    </div>

    <div v-else class="em-page-loader"></div>
  </div>
</template>