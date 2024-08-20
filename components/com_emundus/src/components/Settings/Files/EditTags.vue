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

      <div v-for="(tag, index) in tags" class="tw-mb-6" :id="'tag_' + tag.id" :key="'tag_' + tag.id" @mouseover="enableGrab(index)" @mouseleave="disableGrab()">
        <div class="tw-flex tw-items-center tw-justify-start tw-w-full">
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
    </div>
  </div>
</template>

<script>
/* COMPONENTS */
import axios from "axios";

/* SERVICES */
import client from "@/services/axiosClient";
import mixin from "@/mixins/mixin";

import basicPreset from "@/assets/data/colorpicker/presets/basic";
import { useGlobalStore } from '@/stores/global';
import ColorPicker from "@/components/ColorPicker.vue";

export default {
  name: "editTags",

  components: {ColorPicker},

  props: {},

  mixins: [mixin],

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
      axios.get("index.php?option=com_emundus&controller=settings&task=gettags")
          .then(response => {
            this.tags = response.data.data;
            setTimeout(() => {
              this.tags.forEach(element => {
                this.getHexColors(element);
              });
            }, 100);
          });
    },

    async updateTag(tag) {
      this.$emit('updateSaving',true);

      let index = this.colors.findIndex(item => item.value === tag.class);
      const formData = new FormData();
      formData.append('tag', tag.id);
      formData.append('label', document.getElementById(('tag_label_' + tag.id)).textContent);
      formData.append('color', this.colors[index].name);

      await client().post('index.php?option=com_emundus&controller=settings&task=updatetags',
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
