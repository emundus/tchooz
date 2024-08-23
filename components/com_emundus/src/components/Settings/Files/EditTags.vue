<template>
  <div class="tw-flex tw-flex-wrap tw-justify-start">
    <div class="tw-w-10/12">

      <div class="tw-grid tw-grid-cols-3 tw-mb-4">
        <button @click="pushTag" class="tw-btn-primary tw-mb-6 tw-w-max">
          <div class="add-button-div">
            <em class="fas fa-plus tw-mr-1"></em>
            {{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_ADDTAG') }}
          </div>
        </button>
      </div>

      <draggable
          handle=".handle"
          v-model="tags"
          :class="'draggables-list'"
          @end="updateTagOrdering"
      >
        <div v-for="(tag, index) in tags" class="tw-mb-6" :id="'tag_' + tag.id" :key="'tag_' + tag.id" @mouseover="enableGrab(index)" @mouseleave="disableGrab()">
          <div class="tw-flex tw-items-center tw-justify-start tw-w-full">
            <span class="handle tw-cursor-grab" :style="grab && indexGrab === index ? 'opacity: 1' : 'opacity: 0'">
              <span class="material-symbols-outlined">drag_indicator</span>
            </span>
            <div class="status-field">
              <div style="width: 100%">
                <p class="tw-px-2 tw-py-3 em-editable-content" contenteditable="true" :id="'tag_label_' + tag.id" @focusout="updateTag(tag)" @keyup.enter="manageKeyup(tag)" @keydown="checkMaxlength">{{tag.label}}</p>
              </div>
              <input type="hidden" :class="tag.class">
            </div>
            <div class="tw-flex tw-items-center">
              <color-picker
                  v-model="tag.class"
                  @input="updateTag(tag)"
                  :row-length="8"
                  :id="'tag_swatches_'+tag.id"
              />
              <a type="button" :title="translate('COM_EMUNDUS_ONBOARD_DELETE_TAGS')" @click="removeTag(tag,index)" class="tw-flex tw-items-center tw-ml-2 tw-cursor-pointer">
                <span class="material-symbols-outlined tw-text-red-600">delete_outline</span>
              </a>
            </div>
          </div>
          <hr/>
        </div>
      </draggable>
    </div>
  </div>
</template>

<script>
/* COMPONENTS */
import axios from "axios";

/* SERVICES */
import client from "@/services/axiosClient";
import mixin from "@/mixins/mixin";
import errors from '@/mixins/errors.js';
import settingsService from "@/services/settings.js";

import basicPreset from "@/assets/data/colorpicker/presets/basic";
import { useGlobalStore } from '@/stores/global';
import ColorPicker from "@/components/ColorPicker.vue";
import { VueDraggableNext } from 'vue-draggable-next';

export default {
  name: "editTags",

  components: {
    ColorPicker,
    draggable: VueDraggableNext
  },

  props: {},

  mixins: [mixin, errors],

  data() {
    return {
      index: "",
      indexGrab: "0",

      grab: 0,
      loading: false,

      tags: [],
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

    this.getTags();
    this.actualLanguage = this.globalStore.shortLang;
  },

  methods: {
    getTags() {
      settingsService.getTags().then(response => {
        if (response.status) {
          this.tags = response.data;
          setTimeout(() => {
            this.tags.forEach(element => {
              this.getHexColors(element);
            });
          }, 100);
        } else {
          this.displayError(response.msg, '');
        }
      });
    },

    async updateTag(tag) {
      const newLabel = document.getElementById(('tag_label_' + tag.id)).textContent;

      if (newLabel.length > 0) {
        this.$emit('updateSaving',true);

        let index = this.colors.findIndex(item => item.value === tag.class);
        const formData = new FormData();
        formData.append('tag', tag.id);
        formData.append('label', newLabel);
        formData.append('color', this.colors[index].name);

        await client().post('index.php?option=com_emundus&controller=settings&task=updatetags',
          formData,
          {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          }
        ).then((response) => {
          this.$emit('updateSaving',false);

          if (response.status) {
            tag.label = newLabel;
            this.$emit('updateLastSaving',this.formattedDate('','LT'));
          } else {
            this.displayError('COM_EMUNDUS_SETTINGS_FAILED_TO_UPDATE_TAG', response.msg);
          }

        });
      } else {
        document.getElementById(('tag_label_' + tag.id)).textContent = tag.label;
        this.displayError('COM_EMUNDUS_SETTINGS_FAILED_TO_UPDATE_TAG', 'COM_EMUNDUS_SETTINGS_FORBIDDEN_EMPTY_TAG');
      }
    },

    async updateTagOrdering() {
      let orderedTags = [];
      this.tags.forEach((tag) => {
        orderedTags.push(tag.id);
      })

      this.$emit('updateSaving',true);

      settingsService.updateTagOrdering(orderedTags).then(() => {
        this.$emit('updateSaving',false);
        this.$emit('updateLastSaving',this.formattedDate('','LT'));
      })
    },

    pushTag() {
      this.$emit('updateSaving',true);

      axios({
        method: "post",
        url: 'index.php?option=com_emundus&controller=settings&task=createtag',
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
      }).then((newtag) => {
        this.tags.push(newtag.data);
        setTimeout(() => {
          this.getHexColors(newtag.data);
        }, 100);

        this.$emit('updateSaving',false);
        this.$emit('updateLastSaving',this.formattedDate('','LT'));
      });
    },

    removeTag(tag, index) {
      this.$emit('updateSaving',true);

      axios({
        method: "post",
        url: 'index.php?option=com_emundus&controller=settings&task=deletetag',
        headers: {
          "Content-Type": "application/x-www-form-urlencoded"
        },
        data: {
          id: tag.id
        }
      }).then(() => {
        this.tags.splice(index,1);

        this.$emit('updateSaving',false);
        this.$emit('updateLastSaving',this.formattedDate('','LT'));
      });
    },

    manageKeyup(tag){
      document.getElementById(('tag_label_' + tag.id)).textContent = document.getElementById(('tag_label_' + tag.id)).textContent.trim();
      document.activeElement.blur();
    },

    getHexColors(element) {
      element.translate = false;
      element.class = this.variables.getPropertyValue('--em-'+element.class.replace('label-',''));
    },

    checkMaxlength(event) {
      if(event.target.textContent.length === 50 && event.keyCode != 8) {
        event.preventDefault();
      }
    },

    enableGrab(index){
      if(this.tags.length !== 1){
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
<style scoped>
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
