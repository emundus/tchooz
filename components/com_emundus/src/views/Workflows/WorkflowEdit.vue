<template>
  <div>
    <div id="header">
      <div class="tw-flex tw-flex-row tw-flex-space-between">
        <input type="text" v-model="workflow.label" />
        <a class="tw-btn-primary" href="#" @click="save"> {{ translate('SAVE') }} </a>
      </div>

      <div>
        <Multiselect
            :options="programsOptions"
            v-model="programs"
            label="label"
            track-by="id"
            placeholder="Select a program"
            :multiple="true"
        >
        </Multiselect>
      </div>
    </div>
    <div id="workflow-steps" class="tw-my-4 tw-flex tw-flex-row tw-gap-3">
      <div v-for="step in steps" :key="step.id" class="workflow-step tw-rounded tw-border-2 tw-shadow-sm tw-p-4 em-white-bg">
        <div class="workflow-step-header">
          <input type="text" v-model="step.label" />
        </div>

        <div class="workflow-step-content">
          <label>{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE') }}</label>
          <input type="text" v-model="step.type">

          <label>{{ translate('COM_EMUNDUS_WORKFLOW_STEP_START_DATE') }}</label>
          <DatePicker
              :id="'step_' + step.id + '_start_date'"
              v-model="step.start_date"
              :keepVisibleOnInput="true"
              :time-accuracy="2"
              mode="dateTime"
              is24hr
              hide-time-header
              title-position="left"
              :input-debounce="500"
              :popover="{visibility: 'focus'}">
            <template #default="{ inputValue, inputEvents }">
              <input
                  :value="inputValue"
                  v-on="inputEvents"
                  class="tw-mt-2 form-control fabrikinput tw-w-full"
                  :id="'step_' + step.id + '_start_date'"
              />
            </template>
          </DatePicker>

          <label>{{ translate('COM_EMUNDUS_WORKFLOW_STEP_END_DATE') }}</label>
          <DatePicker
              :id="'step_' + step.id + '_end_date'"
              v-model="step.end_date"
              :keepVisibleOnInput="true"
              :time-accuracy="2"
              mode="dateTime"
              is24hr
              hide-time-header
              title-position="left"
              :input-debounce="500"
              :popover="{visibility: 'focus'}">
            <template #default="{ inputValue, inputEvents }">
              <input
                  :value="inputValue"
                  v-on="inputEvents"
                  class="tw-mt-2 form-control fabrikinput tw-w-full"
                  :id="'step_' + step.id + '_end_date'"
              />
            </template>
          </DatePicker>

          <Multiselect
              :options="statuses"
              v-model="step.entry_status"
              label="label"
              track-by="id"
              placeholder="Select a status"
              :multiple="true">
          </Multiselect>

          <label>{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS') }}</label>
          <select v-model="step.output_status">
            <option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.label }}</option>
          </select>
        </div>
      </div>
      <p v-if="steps.length < 1"> {{ translate('COM_EMUNDUS_WORKFLOW_NO_STEPS') }} </p>
    </div>
    <a class="tw-btn-primary" href="#" @click="addStep"> {{ translate('COM_EMUNDUS_WORKFLOW_ADD_STEP') }} </a>
  </div>
</template>

<script>
import workflowService from '@/services/workflow.js';
import settingsService from '@/services/settings.js';
import programmeService from '@/services/programme.js';

import { DatePicker } from 'v-calendar';
import Multiselect from "vue-multiselect";

export default {
  name: 'WorkflowEdit',
  props: {
    workflowId: {
      type: Number,
      required: true
    }
  },
  components: {
    DatePicker,
    Multiselect
  },
  data() {
    return {
      workflow: {
        id: 0,
        label: ''
      },
      steps: [],
      programs: [],
      newStep: {
        label: '',
        type: '',
        start_date: '',
        end_date: '',
        roles: [],
        profile_id: 0,
        entry_status: [],
        output_status: 0,
      },

      statuses : [],
      profiles: [],
      programsOptions: []
    }
  },
  mounted() {
    this.getWorkflow();
    this.getStatuses();
    this.getPrograms();
  },
  methods: {
    getWorkflow() {
      workflowService.getWorkflow(this.workflowId)
        .then(response => {
          this.workflow = response.data.workflow;
          this.steps = response.data.steps;
          this.programs = response.data.programs;
        })
        .catch(e => {
          console.log(e);
        });
    },
    getStatuses() {
      settingsService.getStatus()
        .then(response => {
          this.statuses = response.data.map(status => {
            return {
              id: status.step,
              label: status.label.fr
            }
          });
        })
        .catch(e => {
          console.log(e);
        });
    },
    getPrograms() {
      programmeService.getAllPrograms()
        .then(response => {
          this.programsOptions = response.data.datas.map(program => {
            return {
              id: program.id,
              label: program.label.fr
            }
          });
        })
        .catch(e => {
          console.log(e);
        });
    },
    addStep() {
      this.steps.push(this.newStep);
    },
    save() {
      workflowService.saveWorkflow(this.workflow, this.steps, this.programs)
        .then(response => {
          console.log(response);
        })
        .catch(e => {
          console.log(e);
        });
    }
  }
}
</script>

<style scoped>

</style>