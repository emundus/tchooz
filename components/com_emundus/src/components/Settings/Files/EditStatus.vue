<template>
  <div class="tw-flex tw-flex-wrap tw-justify-start">
    <div class="tw-w-10/12">

      <div class="tw-grid tw-grid-cols-3 tw-mb-4">
        <button @click="pushStatus" class="em-primary-button tw-mb-6 tw-w-max">
          <div class="add-button-div">
            <em class="fas fa-plus tw-mr-1"></em>
            {{ translate('COM_EMUNDUS_ONBOARD_ADD_STATUS') }}
          </div>
        </button>
      </div>

      <draggable
          handle=".handle"
          v-model="status"
          :class="'draggables-list'"
          @end="updateStatusOrder"
      >
        <div v-for="(statu, index) in status" class="tw-mb-6" :title="'step_' + statu.step"  :key="statu.step" :id="'step_' + statu.step" @mouseover="enableGrab(index)" @mouseleave="disableGrab()">
          <div class="tw-flex tw-items-center tw-justify-start tw-w-full">
            <span class="handle tw-cursor-grab" :style="grab && indexGrab == index ? 'opacity: 1' : 'opacity: 0'">
              <span class="material-icons-outlined">drag_indicator</span>
            </span>
            <div class="status-field">
              <div>
                <p class="tw-px-2 tw-py-3 em-editable-content" contenteditable="true" :id="'status_label_' + statu.step" @focusout="updateStatus(statu)" @keyup.enter="manageKeyup(statu)" @keydown="checkMaxlength">{{statu.label[actualLanguage]}}</p>
              </div>
              <input type="hidden" :class="'label-' + statu.class">
            </div>
            <div class="tw-flex tw-items-center">
              <color-picker
                  v-model="statu.class"
                  @input="updateStatus(statu)"
                  :row-length="8"
                  :id="'status_swatches_'+statu.step"
              />
              <a type="button" v-if="statu.edit == 1 && statu.step != 0 && statu.step != 1" :title="translate('COM_EMUNDUS_ONBOARD_DELETE_STATUS')" @click="removeStatus(statu,index)" class="tw-flex tw-items-center tw-ml-2 tw-cursor-pointer">
                <span class="material-icons-outlined tw-text-red-500">delete_outline</span>
              </a>
              <a type="button" v-else :title="translate('COM_EMUNDUS_ONBOARD_CANNOT_DELETE_STATUS')" class="tw-flex tw-items-center tw-ml-2 tw-cursor-pointer">
                <span class="material-icons-outlined tw-text-neutral-600">delete_outline</span>
              </a>
            </div>
          </div>
          <hr/>
        </div>
      </draggable>
    </div>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
/* COMPONENTS */
import { VueDraggableNext } from 'vue-draggable-next';
import axios from "axios";

/* SERVICES */
import client from "com_emundus/src/services/axiosClient";
import mixin from "com_emundus/src/mixins/mixin";

import { useGlobalStore } from '@/stores/global';
import ColorPicker from "@/components/ColorPicker.vue";
import basicPreset from "@/assets/data/colorpicker/presets/basic.js";

export default {
  name: "editStatus",

  components: {
    ColorPicker,
    draggable: VueDraggableNext,
  },

  props: {},

  mixins: [mixin],

  data() {
    return {
      index: "",
      indexGrab: "0",

      grab: 0,
      loading: false,

      status: [],
      show: false,
      actualLanguage : '',

      colors: [],
      variables: null,
    };
  },
  setup() {
    return {
      globalStore: useGlobalStore()
    }
  },

  created() {
    let root = document.querySelector(':root');
    this.variables = getComputedStyle(root);

    for(const swatch of basicPreset) {
      let color = this.variables.getPropertyValue('--em-'+swatch);
      this.colors.push({name: swatch,value: color});
    }

    this.getStatus();
    this.actualLanguage = this.globalStore.shortLang;
  },

  methods: {
    getStatus() {
      axios.get("index.php?option=com_emundus&controller=settings&task=getstatus")
          .then(response => {
            this.status = response.data.data;
            setTimeout(() => {
              this.status.forEach(element => {
                this.getHexColors(element);
              });
            }, 100);
          });
    },

    async updateStatus(status) {
      this.$emit('updateSaving',true);

      let index = this.colors.findIndex(item => item.value === status.class);
      const formData = new FormData();
      formData.append('status', status.step);
      formData.append('label', document.getElementById(('status_label_' + status.step)).textContent);
      formData.append('color', this.colors[index].name);

      await client().post('index.php?option=com_emundus&controller=settings&task=updatestatus',
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          }
      ).then(() => {
        this.$emit('updateSaving',false);
        this.$emit('updateLastSaving',this.formattedDate('','LT'));
      });
    },

    async updateStatusOrder() {
      let status_steps = [];
      this.status.forEach((statu) => {
        status_steps.push(statu.step);
      })

      this.$emit('updateSaving',true);

      const formData = new FormData();
      formData.append('status', status_steps.join(','));

      await client().post('index.php?option=com_emundus&controller=settings&task=updatestatusorder',
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          }
      ).then(() => {
        this.$emit('updateSaving',false);
        this.$emit('updateLastSaving',this.formattedDate('','LT'));
      });
    },

    pushStatus() {
      this.$emit('updateSaving',true);

      axios({
        method: "post",
        url: 'index.php?option=com_emundus&controller=settings&task=createstatus',
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
      }).then((newstatus) => {
        this.status.push(newstatus.data);
        setTimeout(() => {
          this.getHexColors(newstatus.data);
        }, 100);

        this.$emit('updateSaving',false);
        this.$emit('updateLastSaving',this.formattedDate('','LT'));
      });
    },

    removeStatus(status, index) {
      if(status.edit == 1 && status.step != 0 && status.step != 1) {
        this.$emit('updateSaving',true);

        axios({
          method: "post",
          url: 'index.php?option=com_emundus&controller=settings&task=deletestatus',
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          data: {
            id: status.id,
            step: status.step
          }
        }).then(() => {
          this.status.splice(index, 1);

          this.$emit('updateSaving',false);
          this.$emit('updateLastSaving',this.formattedDate('','LT'));
        });
      }
    },

    manageKeyup(status){
      document.getElementById(('status_label_' + status.step)).textContent = document.getElementById(('status_label_' + status.step)).textContent.trim();
      document.activeElement.blur();
    },

    getHexColors(element) {
      element.translate = false;
      element.class = this.variables.getPropertyValue('--em-'+element.class);
    },

    checkMaxlength(event) {
      if(event.target.textContent.length === 50 && event.keyCode != 8) {
        event.preventDefault();
      }
    },

    enableGrab(index){
      if(this.status.length !== 1){
        this.indexGrab = index;
        this.grab = true;
      }
    },
    disableGrab(){
      this.indexGrab = 0;
      this.grab = false;
    },
  },
};
</script>
<style scoped lang="scss">
.status-field{
  border-radius: 5px;
  width: 100%;
  margin-right: 1em;
  display: flex;
}

.status-item{
  display: flex;
  align-items: center;
  justify-content: center;
  max-width: 95%;
  width: 100%;
}
</style>
