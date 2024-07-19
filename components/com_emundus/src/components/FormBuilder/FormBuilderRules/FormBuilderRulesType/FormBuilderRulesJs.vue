<template>
  <div id="form-builder-rules-js" class="tw-self-start tw-w-full">
    <h2>{{ titleLabel }}</h2>
    <input class="tw-mt-2 tw-mb-4" v-model="label" :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_RULE_NAME')" />

    <div id="form-builder-rules-js-conditions-block">
      <div v-for="(grouped_condition, index) in conditions" class="tw-mt-2 tw-rounded-lg tw-bg-white tw-px-3 tw-py-4 tw-flex tw-flex-col tw-gap-6">
        <form-builder-rules-js-conditions @add-condition="addCondition" :elements="elements" :index="index" :conditions="grouped_condition" @remove-condition="removeCondition" :page="page" />
      </div>

      <div class="tw-flex tw-justify-end">
        <button type="button" @click="addGroupedCondition()" class="em-tertiary-button tw-mt-2 tw-w-auto">{{ translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_CONDITION_GROUP') }}</button>
      </div>
    </div>

    <div v-if="conditions.length > 1" class="tw-flex tw-items-center tw-gap-2">
      <p class="tw-font-bold">{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_IF') }}</p>
      <select class="tw-w-full" v-model="group">
        <option value="OR">{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_GROUP_OR') }}</option>
        <option value="AND">{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_GROUP_AND') }}</option>
      </select>
    </div>

    <div id="form-builder-rules-js-actions-block">
      <div v-for="(action, index) in actions" class="tw-mt-2 tw-rounded-lg tw-bg-white tw-px-3 tw-py-4 tw-flex tw-flex-col tw-gap-6">
        <form-builder-rules-js-action :elements="elements" :index="index" :action="action" @remove-action="removeAction" :page="page" />
      </div>

      <div class="tw-flex tw-justify-end">
        <button type="button" @click="addAction()" class="em-tertiary-button tw-mt-2 tw-w-auto">{{ translate('COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_ACTION') }}</button>
      </div>
    </div>

    <hr/>
    <button class="tw-mt-4 em-primary-button tw-w-auto tw-float-right" @click="saveRule">{{ translate('COM_EMUNDUS_FORM_BUILDER_RULE_SAVE') }}</button>

  </div>
</template>

<script>
import formService from '../../../../services/form';

import formBuilderMixin from '../../../../mixins/formbuilder';
import globalMixin from '../../../../mixins/mixin';
import errorMixin from '../../../../mixins/errors';
import Swal from 'sweetalert2';
import FormBuilderRulesJsConditions
  from "@/components/FormBuilder/FormBuilderRules/FormBuilderRulesType/FormBuilderRulesJsConditions.vue";
import FormBuilderRulesJsAction
  from "@/components/FormBuilder/FormBuilderRules/FormBuilderRulesType/FormBuilderRulesJsAction.vue";

export default {
  components: {FormBuilderRulesJsAction, FormBuilderRulesJsConditions},
  props: {
    page: {
      type: Object,
      default: {}
    },
    elements: {
      type: Array,
      default: []
    },
    rule: {
      type: Object,
      default: {}
    }
  },
  mixins: [formBuilderMixin, globalMixin, errorMixin],
  data() {
    return {
      conditions: [],
      actions: [],
      group: 'OR',
      label: '',
      loading: false,
    };
  },
  mounted() {
    if (this.page.id) {
      if(this.rule !== null && Object.values(this.rule.conditions).length > 0) {
        this.conditions = Object.values(this.rule.conditions);
      } else {
        let first_condition = [];
        first_condition.push({
          field: '',
          values: '',
          state: '=',
          group_type: 'OR'
        });

        this.conditions.push(first_condition);
      }

      if (this.rule !== null && this.rule.actions.length > 0) {
        this.actions = this.rule.actions;
      } else {
        this.actions.push({
          action: 'show',
          fields: [],
          params: []
        });
      }

      if (this.rule !== null) {
        this.group = this.rule.group;
        this.label = this.rule.label;
      }
    }
  },
  methods: {
    addCondition(index) {
      this.conditions[index].push({
        field: '',
        values: '',
        state: '=',
        group_type: 'OR'
      });
    },
    addGroupedCondition() {
      let grouped_condition = [];
      grouped_condition.push({
        field: '',
        values: '',
        state: '=',
        group_type: 'OR'
      });

      this.conditions.push(grouped_condition);
    },
    addAction() {
      this.actions.push({
        action: 'show',
        fields: []
      });
    },
    removeCondition(index) {
      this.conditions = this.conditions.filter((condition, i) => i !== index);
    },
    removeAction(index) {
      this.actions = this.actions.filter((condition, i) => i !== index);
    },

    saveRule() {
      let conditions_post = [];
      let actions_post = [];

      this.conditions.forEach((grouped_condition) => {
        let tmp_conditions = [];
        grouped_condition.forEach((condition) => {
          if (condition.field && condition.values) {
            tmp_conditions.push({
              field: condition.field.name,
              values: typeof condition.values === 'object' ? condition.values.primary_key : condition.values,
              state: condition.state,
              group_type: condition.group_type
            });
          }
        });
        conditions_post.push(tmp_conditions);
      });

      this.actions.forEach((action) => {
        if(action.fields) {
          let fields = [];
          if(action.fields.length > 1) {
            action.fields.forEach((field) => {
              fields.push(field.name);
            });
          } else {
            if(typeof action.fields[0] !== 'undefined') {
              fields.push(action.fields[0].name);
            } else {
              fields.push(action.fields.name);
            }
          }

          actions_post.push({
            action: action.action,
            fields: fields,
            params: action.params
          });
        }
      });

      if(conditions_post.length == 0) {
        this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_ERROR'), this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_ERROR_CONDITION_EMPTY'));
        return;
      }

      if(actions_post.length == 0) {
        this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_ERROR'), this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_ERROR_ACTION_EMPTY'));
        return;
      }

      if(this.rule !== null ) {
        formService.editRule(this.rule.id, conditions_post, actions_post, this.group, this.label).then(response => {
          if (response.status) {
            Swal.fire({
              title: this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_EDIT_SUCCESS'),
              type: 'success',
              showConfirmButton: false,
              customClass: {
                title: 'em-swal-title',
                actions: "em-swal-single-action",
              },
              timer: 2000,
            }).then(() => {
              this.$emit('close-rule-add-js')
            });
          } else {
            this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_ERROR'), this.translate(response.msg));
          }
        });
      } else {
        formService.addRule(this.page.id, conditions_post, actions_post, this.group, this.label).then(response => {
          if (response.status) {
            Swal.fire({
              title: this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_SUCCESS'),
              icon: 'success',
              showConfirmButton: false,
              customClass: {
                title: 'em-swal-title',
                actions: "em-swal-single-action",
              },
              timer: 2000,
            }).then(() => {
              this.$emit('close-rule-add-js')
            });
          } else {
            this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_ERROR'), this.translate(response.msg));
          }
        });
      }
    }
  },
  computed: {
    titleLabel() {
      return this.rule ? this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_EDIT_JS') : this.translate('COM_EMUNDUS_FORM_BUILDER_RULE_ADD_JS');
    }
  }
}
</script>

<style lang="scss">
</style>
