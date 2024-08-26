<template>
  <div id="form-builder-rules-js-conditions" class="tw-self-start tw-w-full">
    <div class="tw-flex tw-justify-between tw-items-center">
      <h3>{{ conditionLabel }}</h3>
      <div class="tw-flex tw-items-center tw-gap-2">
        <div class="tw-flex tw-items-center tw-gap-3" v-if="conditions.length > 1">
          <span v-for="type in group_types" :key="type.id"
                class="tw-cursor-pointer tw-p-2 tw-rounded-lg tw-ml-1 tw-border tw-border-neutral-500 tw-w-[50px] tw-flex tw-justify-center"
                @click="conditions_group = type.value"
                :class="{ 'label-darkblue': conditions_group == type.value }">
            {{ translate(type.label) }}
          </span>
        </div>
        <button v-if="index !== 0" type="button" @click="$emit('remove-condition', index)" class="tw-w-auto">
          <span class="material-symbols-outlined tw-text-red-600">close</span>
        </button>
      </div>

    </div>

    <div class="tw-flex tw-flex-col tw-gap-2 tw-mt-4">
      <div class="tw-ml-4 tw-flex tw-flex-col tw-gap-2" v-for="(condition,condition_key) in conditions">
        <span v-if="conditions.length > 1 && condition_key != 0" class="tw-font-medium tw-ml-1 tw-mr-2">{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_'+conditions_group) }}</span>
        <form-builder-rules-js-condition :elements="elements" :index="condition_key" :condition="condition" @remove-condition="removeCondition" :page="page" :multiple="Object.values(conditions).length > 1" />
      </div>
    </div>


    <button type="button" @click="$emit('add-condition',index)" class="tw-btn-tertiary tw-mt-2 !tw-w-max tw-float-right">{{ translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_CONDITION') }}</button>
  </div>
</template>

<script>
import formService from '../../../../services/form';

import formBuilderMixin from '../../../../mixins/formbuilder';
import globalMixin from '../../../../mixins/mixin';
import fabrikMixin from '../../../../mixins/fabrik';
import errorMixin from '../../../../mixins/errors';
import Swal from 'sweetalert2';
import Multiselect from 'vue-multiselect';
import formBuilderService from "@/services/formbuilder";
import FormBuilderRulesJsCondition
  from "@/components/FormBuilder/FormBuilderRules/FormBuilderRulesType/FormBuilderRulesJsCondition.vue";

export default {
  components: {
    FormBuilderRulesJsCondition,
    Multiselect
  },
  props: {
    page: {
      type: Object,
      default: {}
    },
    conditions: {
      type: Array,
      default: []
    },
    index: {
      type: Number,
      default: 0
    },
    elements: {
      type: Array,
      default: []
    }
  },
  mixins: [formBuilderMixin, globalMixin, errorMixin, fabrikMixin],
  data() {
    return {
      loading: false,

      group_types: [
        {id: 1, label: 'COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_OR', value: 'OR'},
        {id: 2, label: 'COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_AND', value: 'AND'}
      ],

      conditions_group: 'OR'
    };
  },
  mounted() {
    if(this.conditions.length > 1) {
      this.conditions.forEach((condition, key) => {
        this.conditions_group = condition.group_type;
      });
    }
  },
  methods: {
    removeCondition(index) {
      this.conditions.splice(index, 1);
    },

    labelTranslate({label}) {
      return label ? label.fr : '';
    },
  },
  computed: {
    conditionLabel() {
      return `-- ${this.index + 1} --`;
    }
  },
  watch: {
    conditions_group: {
      handler: function (val) {
        this.conditions.forEach((condition, key) => {
          condition.group_type = val;
        });
      },
      deep: true
    }
  }
}
</script>

<style lang="scss">
</style>
