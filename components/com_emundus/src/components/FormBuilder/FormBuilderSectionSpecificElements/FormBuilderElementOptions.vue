<template>
  <div id="form-builder-radio-button">
    <div v-if="loading" class="em-loader"></div>
    <div v-else>
      <draggable
          v-model="arraySubValues"
          handle=".handle-options"
          @end="updateOrder">
        <div class="element-option tw-flex tw-items-center tw-justify-between tw-mt-2 tw-mb-2"
             v-for="(option, index) in arraySubValues" :key="option" @mouseover="optionHighlight = index;"
             @mouseleave="optionHighlight = null">
          <div class="tw-flex tw-items-center tw-w-full">
            <div class="tw-flex tw-items-center">
              <span class="icon-handle" :style="optionHighlight === index ? 'opacity: 1' : 'opacity: 0'">
                <span class="material-icons-outlined handle-options tw-cursor-grab" style="font-size: 18px">drag_indicator</span>
              </span>
            </div>
            <input v-if="type !== 'dropdown'" :type="type" :name="'element-id-' + element.id"
                   :value="optionsTranslations[index]">
            <div v-else>{{ index + 1 }}.</div>
            <input
                type="text"
                class="editable-data editable-data-input tw-ml-1 tw-w-full"
                :id="'option-' + element.id + '-' + index"
                v-model="optionsTranslations[index]"
                @focusout="updateOption(index, optionsTranslations[index])"
                @keyup.enter="updateOption(index, optionsTranslations[index],true)"
                @keyup.tab="document.getElementById('new-option-' + element.id).focus();"
                :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_ADD_OPTION')">
          </div>
          <div class="tw-flex tw-items-center">
            <span class="material-icons-outlined tw-cursor-pointer" @click="removeOption(index)"
                  :style="optionHighlight === index ? 'opacity: 1' : 'opacity: 0'">close</span>
          </div>
        </div>
      </draggable>
      <div id="add-option" class="tw-flex tw-items-center lg:tw-justify-start md:tw-justify-center">
        <span class="icon-handle" style="opacity: 0">
          <span class="material-icons-outlined handle-options" style="font-size: 18px">drag_indicator</span>
        </span>
        <input v-if="type !== 'dropdown'" :type="type" :name="'element-id-' + element.id">
        <div v-else>{{ element.params.sub_options.sub_labels.length + 1 }}.</div>
        <input
            type="text"
            class="editable-data editable-data-input tw-ml-1 tw-w-full"
            :id="'new-option-'+ element.id"
            v-model="newOption"
            @focusout="addOption"
            @keyup.enter="addOption"
            :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_ADD_OPTION')">
      </div>
    </div>
  </div>
</template>

<script>
import formBuilderService from '../../../services/formbuilder';
import { VueDraggableNext } from 'vue-draggable-next';

export default {
  props: {
    element: {
      type: Object,
      required: true
    },
    type: {
      type: String,
      required: true
    }
  },
  components: {
    draggable: VueDraggableNext,
  },
  data() {
    return {
      loading: false,
      newOption: '',
      arraySubValues: [],
      optionsTranslations: [],
      optionHighlight: null,
    };
  },
  created() {
    this.getSubOptionsTranslation();
  },
  methods: {
    async reloadOptions(new_option = false) {
      this.loading = true;
      formBuilderService.getElementSubOptions(this.element.id).then((response) => {
        if (response.status) {
          this.element.params.sub_options = response.new_options;
          this.getSubOptionsTranslation(new_option);
        } else {
          this.loading = false;
        }
      });
    },
    async getSubOptionsTranslation(new_option = false) {
      this.loading = true;

      formBuilderService.getJTEXTA(this.element.params.sub_options.sub_labels).then(response => {
        if (response) {
          // transform object response to array
          this.optionsTranslations = Object.values(response.data);
          this.arraySubValues = this.element.params.sub_options.sub_values.map((value, i) => {
            return {
              'sub_value': value,
              'sub_label': this.element.params.sub_options.sub_labels[i],
            };
          });

          setTimeout(() => {
            if(new_option) {
              document.getElementById('new-option-' + this.element.id).focus();
            }
          }, 200)

        }

        this.loading = false;
      });
    },
    addOption() {
      if (this.newOption.trim() == '') {
        return;
      }

      this.loading = true;
      formBuilderService.addOption(this.element.id, this.newOption, this.shortDefaultLang).then((response) => {
        this.newOption = '';
        if (response.status) {
          this.reloadOptions(true);
        }
        this.loading = false;
      })
    },
    updateOption(index, option, next = false) {
      this.loading = true;
      formBuilderService.updateOption(this.element.id, this.element.params.sub_options, index, option, this.shortDefaultLang).then((response) => {
        if (response.status) {
          this.reloadOptions().then(() => {
            if(next) {
              setTimeout(() => {
                if(!document.getElementById('option-' + this.element.id + '-' + (index + 1))) {
                  document.getElementById('new-option-' + this.element.id).focus();
                } else {
                  document.getElementById('option-' + this.element.id + '-' + (index + 1)).focus();
                }
              }, 300)
            }
          })
        } else {
          this.loading = false;
        }
      });
    },
    updateOrder() {
      if (this.arraySubValues.length > 1) {
        let sub_options_in_new_order = {
          sub_values: [],
          sub_labels: []
        };

        this.arraySubValues.forEach((value, i) => {
          sub_options_in_new_order.sub_values.push(value.sub_value);
          sub_options_in_new_order.sub_labels.push(value.sub_label);
        });

        if (!this.element.params.sub_options.sub_values.every((value, index) => value === sub_options_in_new_order.sub_values[index])) {
          this.loading = true;
          formBuilderService.updateElementSubOptionsOrder(this.element.id, this.element.params.sub_options, sub_options_in_new_order).then((response) => {
            if (response.status) {
              this.reloadOptions();
            } else {
              this.loading = false;
            }
          });
        } else {
          console.log('No need to call reorder, same order');
        }
      } else {
        console.log('No need to reorder, only one element');
      }
    },
    removeOption(index) {
      this.loading = true;
      formBuilderService.deleteElementSubOption(this.element.id, index).then((response) => {
        if (response.status) {
          this.reloadOptions();
        } else {
          this.loading = false;
        }
      });
    }
  }
}
</script>

<style lang="scss">
.editable-data-input {
  padding: 0 !important;
  height: auto !important;
  border: unset !important;
  border-bottom: solid 2px transparent !important;
  border-radius: 0 !important;

  &:focus {
    outline: none !important;
    box-shadow: unset !important;
    border-bottom: solid 2px #20835F !important;
    border-radius: 0 !important;
  }

  &:hover {
    box-shadow: unset !important;
    border-bottom: solid 2px rgba(32, 131, 95, 0.87) !important;
    border-radius: 0 !important;
  }
}

.element-option, #add-option {
  .icon-handle {
    height: 18px;
    width: 18px;
    transition: all .3s;
  }
}
</style>
