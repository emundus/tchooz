<template>
  <div id="form-builder-rules-js-condition" class="tw-self-start tw-w-full"
       :class="{ 'tw-bg-neutral-300 tw-rounded tw-p-2': multiple}">
    <div class="tw-flex tw-justify-end tw-items-center">
      <button v-if="index !== 0" type="button" @click="$emit('remove-condition', index)" class="tw-w-auto">
        <span class="material-symbols-outlined tw-text-red-600">close</span>
      </button>
    </div>

    <div class="tw-flex">
      <p class="tw-mr-2 tw-mt-3 tw-font-bold">{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_IF') }}</p>

      <div class="tw-flex tw-flex-col tw-ml-2 tw-w-full">
        <div class="tw-flex tw-items-center">
          <multiselect
              v-model="conditionData.field"
              label="label_tag"
              :custom-label="labelTranslate"
              track-by="name"
              :options="elements"
              :multiple="false"
              :taggable="false"
              select-label=""
              selected-label=""
              deselect-label=""
              :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELD')"
              :close-on-select="true"
              :clear-on-select="false"
              :searchable="true"
              :allow-empty="true"
          ></multiselect>
        </div>

        <div class="tw-mt-4">
          <div class="tw-flex tw-items-center tw-gap-3">
          <span v-for="operator in operators" :key="operator.id"
                class="tw-cursor-pointer tw-p-2 tw-rounded-lg tw-ml-1 tw-border tw-border-neutral-500"
                @click="conditionData.state = operator.value"
                :class="{ 'label-darkblue': conditionData.state == operator.value }">
            {{ translate(operator.label) }}
          </span>
          </div>

          <div class="tw-mt-6">
            <multiselect
                v-if="conditionData.field && (options_plugins.includes(conditionData.field.plugin) || conditionData.field.plugin == 'yesno')"
                v-model="conditionData.values"
                label="value"
                track-by="primary_key"
                :options="options"
                :multiple="false"
                :taggable="false"
                select-label=""
                selected-label=""
                deselect-label=""
                :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELD')"
                :close-on-select="true"
                :clear-on-select="false"
                :searchable="true"
                :allow-empty="true"
            ></multiselect>
            <input v-else-if="conditionData.field" v-model="conditionData.values"/>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import formBuilderMixin from '@/mixins/formbuilder.js';
import globalMixin from '@/mixins/mixin.js';
import fabrikMixin from '@/mixins/fabrik.js';
import errorMixin from '@/mixins/errors.js';

import formBuilderService from "@/services/formbuilder.js";
import { useGlobalStore } from "@/stores/global.js";

import Multiselect from 'vue-multiselect';
import { watch } from 'vue';

export default {
  components: {
    Multiselect
  },
  props: {
    page: {
      type: Object,
      default: () => ({}),
    },
    condition: {
      type: Object,
      default: () => ({}),
    },
    index: {
      type: Number,
      default: 0
    },
    elements: {
      type: Array,
      default: () => []
    },
    multiple: {
      type: Boolean,
      default: false
    }
  },
  mixins: [formBuilderMixin, globalMixin, errorMixin, fabrikMixin],
  data() {
    return {
      loading: false,
      operators: [
        {id: 1, label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_EQUALS', value: '='},
        {id: 2, label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_NOT_EQUALS', value: '!='}
      ],
      options: [],
      options_plugins: ['dropdown', 'databasejoin', 'radiobutton', 'checkbox'],
      conditionData: null
    };
  },
  created() {
    this.conditionData = this.condition;
  },
  mounted() {
    if (this.page.id) {
      this.conditionData.field = this.elements.find(element => element.name === this.conditionData.field);
      if(this.conditionData.field) {
        this.defineOptions(this.conditionData.field);
      }
    }

    watch(
      () => this.conditionData.field,
      (val, oldVal) => {
        if (typeof oldVal === 'object') {
          this.conditionData.values = '';
        }
        this.options = [];

        if (val) {
          this.defineOptions(val);
        }
      }
    )
  },
  methods: {
    labelTranslate({label}) {
      return label ? label[useGlobalStore().getShortLang] : '';
    },
    defineOptions(val) {
      if (this.options_plugins.includes(val.plugin)) {
        if (val.plugin == 'databasejoin') {
          this.loading = true;

          this.getDatabasejoinOptions(val.params.join_db_name, val.params.join_key_column, val.params.join_val_column, val.params.join_val_column_concat).then(response => {
            if (response.status && response.data != '') {
              this.options = response.options;

              if (this.conditionData.values) {
                this.conditionData.values = this.options.find(option => option.primary_key == this.conditionData.values);
              }
            } else {
              this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
            }
            this.loading = false;
          });
        } else {
          formBuilderService.getJTEXTA(val.params.sub_options.sub_labels).then(response => {
            if (response) {
              val.params.sub_options.sub_labels.forEach((label, index) => {
                val.params.sub_options.sub_labels[index] = Object.values(response.data)[index];
              });
            }

            var ctr = 0;
            Object.values(val.params.sub_options.sub_values).forEach((option, key) => {
              let new_option = {
                primary_key: option,
                value: val.params.sub_options.sub_labels[key]
              };

              this.options.push(new_option);

              ctr++;
              if (ctr === val.params.sub_options.sub_values.length) {
                if (this.conditionData.values) {
                  this.conditionData.values = this.options.find(option => option.primary_key == this.conditionData.values);
                }
              }
            });

            this.loading = false;
          });
        }
      }

      if (val.plugin == 'yesno') {
        this.options = [
          {primary_key: 0, value: this.translate('COM_EMUNDUS_FORMBUILDER_NO')},
          {primary_key: 1, value: this.translate('COM_EMUNDUS_FORMBUILDER_YES')}
        ];

        if (this.conditionData.values) {
          this.conditionData.values = this.options.find(option => option.primary_key == this.conditionData.values);
        }
      }
    }
  },
  computed: {
    conditionLabel() {
      return `-- ${this.index + 1} --`;
    }
  }
}
</script>
