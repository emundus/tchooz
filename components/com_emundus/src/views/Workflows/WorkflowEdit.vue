<template>
  <div class="tw-m-2">
    <div id="header">
      <div class="tw-flex tw-flex-row tw-justify-between">
        <input id="workflow-label" name="workflow-label" class="!tw-w-[350px]" type="text" v-model="workflow.label" />
        <a class="tw-btn-primary tw-flex tw-items-center tw-gap-1" href="#" @click="save">
          <span class="material-icons-outlined">check</span>
          <span>{{ translate('SAVE') }}</span>
        </a>
      </div>

      <div class="tw-mt-4 tw-w-full tw-flex tw-flex-row tw-justify-between tw-items-center">
        <div>
          <select>
            <option value="0">{{ translate('SORT_BY') }}</option>
          </select>
        </div>
        <div class="tw-flex tw-flex-row tw-items-center">
          <div class="tw-flex tw-flex-row tw-items-center">
            <label>{{ translate('COM_EMUNDUS_WORKFLOW_ASSOCIATED_PROGRAMS') }}</label>
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
          <div class="tw-flex tw-flex-row tw-items-center tw-gap-1 tw-ml-4">
            <span class="material-icons-outlined tw-cursor-pointer tw-p-2 tw-rounded tw-border" @click="currentView = 'steps'">label</span>
            <span class="material-icons-outlined tw-cursor-pointer tw-p-2 tw-rounded tw-border" @click="currentView = 'gantt'">account_tree</span>
          </div>
        </div>
      </div>
    </div>
    <transition name="fade">
      <div v-show="currentView === 'steps'" id="workflow-steps-wrapper" class="tw-my-4">
        <div id="workflow-steps" class=" tw-flex tw-flex-row tw-gap-3">
          <div v-for="step in steps" :key="step.id" class="workflow-step tw-rounded tw-border-2 tw-shadow-sm tw-p-4 em-white-bg">
            <div class="workflow-step-header tw-mb-4 tw-flex tw-flex-col">
              <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_LABEL') }}</label>
              <input type="text" v-model="step.label" />
            </div>

            <div class="workflow-step-content">
              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE') }}</label>
                <select v-model="step.type">
                  <option v-for="type in stepTypes" :key="type.id" :value="type.id">{{ type.label }}</option>
                </select>
              </div>

              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_START_DATE') }}</label>
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
                    :popover="{visibility: 'focus'}"
                    :locale="{data: 'YYYY-MM-DD HH:mm'}"
                >
                  <template #default="{ inputValue, inputEvents }">
                    <input
                        :value="inputValue"
                        v-on="inputEvents"
                        class="form-control fabrikinput tw-w-full"
                        :id="'step_' + step.id + '_start_date'"
                    />
                  </template>
                </DatePicker>
              </div>

              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_END_DATE') }}</label>
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
                    :popover="{visibility: 'focus'}"
                    :locale="{data: 'YYYY-MM-DD HH:mm'}"
                >
                  <template #default="{ inputValue, inputEvents }">
                    <input
                        :value="inputValue"
                        v-on="inputEvents"
                        class="form-control fabrikinput tw-w-full"
                        :id="'step_' + step.id + '_end_date'"
                    />
                  </template>
                </DatePicker>
              </div>

              <div v-if="step.type !== 'applicant'" class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_ROLES') }}</label>
                <Multiselect
                    :options="nonApplicantProfiles"
                    v-model="step.roles"
                    label="label"
                    track-by="id"
                    placeholder="Select a role"
                    :multiple="true">
                </Multiselect>
              </div>

              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_PROFILE') }}</label>
                <select v-model="step.profile_id">
                  <option v-for="profile in applicantProfiles" :key="profile.id" :value="profile.id">{{ profile.label }}</option>
                </select>
              </div>

              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS') }}</label>
                <Multiselect
                    :options="statuses"
                    v-model="step.entry_status"
                    label="label"
                    track-by="id"
                    placeholder="Select a status"
                    :multiple="true">
                </Multiselect>
              </div>

              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS') }}</label>
                <select v-model="step.output_status">
                  <option value="-1">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS_SELECT') }}</option>
                  <option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.label }}</option>
                </select>
              </div>
            </div>
          </div>
          <p v-if="steps.length < 1"> {{ translate('COM_EMUNDUS_WORKFLOW_NO_STEPS') }} </p>
          <a class="tw-btn-primary tw-h-fit" href="#" @click="addStep"> {{ translate('COM_EMUNDUS_WORKFLOW_ADD_STEP') }} </a>
        </div>
      </div>
    </transition>
    <transition name="fade">
      <div v-show="currentView === 'gantt'" class="tw-my-4">
        <g-gantt-chart
            chart-start="2024-01-01 00:00"
            chart-end="2024-12-31 23:59"
            precision="month"
            bar-start="start_date"
            bar-end="end_date"
        >
          <g-gantt-row v-for="row in stepsGantBars" :key="'row-' + row.key" :bars="row" :label="row.label" />
        </g-gantt-chart>
      </div>
    </transition>
  </div>
</template>

<script>
import workflowService from '@/services/workflow.js';
import settingsService from '@/services/settings.js';
import programmeService from '@/services/programme.js';
import fileService from '@/services/file.js';

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
        id: 0,
        label: this.translate('COM_EMUNDUS_WORKFLOW_NEW_STEP_LABEL'),
        type: 'applicant',
        start_date: '',
        end_date: '',
        roles: [],
        profile_id: 9,
        entry_status: [],
        output_status: 0,
      },

      currentView: 'steps', // steps, gantt
      stepTypes: [
        { id: 'applicant', label: this.translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE_APPLICANT') },
        { id: 'evaluator', label: this.translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE_EVALUATOR') },
      ],
      statuses : [],
      profiles: [],
      programsOptions: []
    }
  },
  mounted() {
    this.getStatuses().then(() => {
      this.getPrograms().then(() => {
        this.getWorkflow();
      });
    });
    this.getProfiles();
  },
  methods: {
    getWorkflow() {
      workflowService.getWorkflow(this.workflowId)
        .then(response => {
          this.workflow = response.data.workflow;
          let tmpSteps = response.data.steps;
          tmpSteps.forEach((step) => {
            step.start_date = new Date(step.start_date);
            step.end_date = new Date(step.end_date);

            step.entry_status = this.statuses.filter(status => step.entry_status.includes(status.id.toString()));
          });
          this.steps = tmpSteps;

          let program_ids = response.data.programs;
          this.programs = this.programsOptions.filter(program => program_ids.includes(program.id));
        })
        .catch(e => {
          console.log(e);
        });
    },
    async getStatuses() {
      return await settingsService.getStatus()
        .then(response => {
          return this.statuses = response.data.map(status => {
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
    async getPrograms() {
      return await programmeService.getAllPrograms()
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
    getProfiles() {
      fileService.getProfiles()
        .then(response => {
          this.profiles = response.data.map(profile => {
            return {
              id: profile.id,
              label: profile.label,
              applicantProfile: profile.published
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
      console.log(this.steps);
      this.steps.forEach((step) => {
        if (step.start_date !== '') {
          step.start_date = this.formatDate(step.start_date);
        } else {
          step.start_date = '0000-00-00 00:00:00';
        }

        if (step.end_date !== '') {
          step.end_date = this.formatDate(step.end_date);
        } else {
          step.end_date = '0000-00-00 00:00:00';
        }
      });

      workflowService.saveWorkflow(this.workflow, this.steps, this.programs)
        .then(response => {
          if (response.status) {
            this.getWorkflow();
          }
        })
        .catch(e => {
          console.log(e);
        });
    },
    formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
      let year = date.getFullYear();
      let month = (1 + date.getMonth()).toString().padStart(2, '0');
      let day = date.getDate().toString().padStart(2, '0');
      let hours = date.getHours().toString().padStart(2, '0');
      let minutes = date.getMinutes().toString().padStart(2, '0');
      let seconds = date.getSeconds().toString().padStart(2, '0');

      return format.replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
    }
  },
  computed: {
    nonApplicantProfiles() {
      return this.profiles.filter(profile => !profile.applicantProfile);
    },
    applicantProfiles() {
      return this.profiles.filter(profile => profile.applicantProfile);
    },
    stepsGantBars() {
      return this.steps.map(step => {
        return [{
          key: step.id,
          label: step.label,
          start_date: step.start_date,
          end_date: step.end_date,
          ganttBarConfig: {
            id: step.id,
            label: step.label
          }
        }]
      });
    }
  }
}
</script>

<style scoped>

</style>