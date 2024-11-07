<template>
  <div class="tw-m-2">
    <div class="tw-flex tw-items-center tw-cursor-pointer tw-mb-4" @click="goBack">
      <span class="material-icons-outlined">navigate_before</span>
      <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
    </div>
    <div id="header">
      <div class="tw-flex tw-flex-row tw-justify-between">
        <input id="workflow-label" name="workflow-label" class="!tw-w-[350px]" type="text" v-model="workflow.label" />
        <button class="tw-btn-primary tw-flex tw-items-center tw-gap-1" @click="save">
          <span class="material-icons-outlined">check</span>
          <span>{{ translate('SAVE') }}</span>
        </button>
      </div>

      <div class="tw-mt-4 tw-w-full tw-flex tw-flex-row tw-justify-between tw-items-center">
        <div>
          <select v-if="sortByOptions.length > 0">
            <option value="0">{{ translate('SORT_BY') }}</option>
          </select>
        </div>
      </div>
    </div>

    <Tabs :tabs="tabs" :classes="'tw-flex tw-items-center tw-gap-2 tw-ml-7'"></Tabs>

    <div id="tabs-wrapper" class="tw-w-full tw-rounded-coordinator tw-p-6 tw-bg-white tw-border tw-border-neutral-300 tw-relative">
      <div v-if="activeTab.id == 'steps'" id="workflow-steps-wrapper" class="tw-my-4 tw-flex tw-flex-col tw-p-2">
        <a class="tw-btn-primary tw-h-fit tw-w-fit tw-mb-4" href="#" @click="addStep"> {{ translate('COM_EMUNDUS_WORKFLOW_ADD_STEP') }} </a>

        <div id="workflow-steps" class="tw-flex tw-flex-row tw-gap-3 tw-overflow-auto">
          <div v-for="step in steps" :key="step.id"
               class="workflow-step tw-rounded tw-border tw-shadow-sm tw-p-4"
               :class="{
                'em-gray-bg': step.state != 1,
                'em-white-bg': step.state == 1
             }"
          >
            <div class="workflow-step-head tw-flex tw-flex-row tw-justify-between">
              <h4>{{ step.label }}</h4>
              <popover>
                <ul class="tw-list-none !tw-p-0">
                  <li class="archive-workflow-step tw-cursor-pointer tw-p-2" @click="duplicateStep(step.id)">{{ translate('COM_EMUNDUS_ACTIONS_DUPLICATE') }}</li>
                  <li v-if="step.state == 1" class="archive-workflow-step tw-cursor-pointer tw-p-2" @click="updateStepState(step.id, 0)">{{ translate('COM_EMUNDUS_ACTIONS_ARCHIVE') }}</li>
                  <li v-else class="archive-workflow-step tw-cursor-pointer tw-p-2" @click="updateStepState(step.id, 1)">{{ translate('COM_EMUNDUS_ACTIONS_UNARCHIVE') }}</li>
                  <li class="delete-workflow-step tw-cursor-pointer tw-p-2" @click="beforeDeleteStep(step.id)">{{ translate('COM_EMUNDUS_ACTIONS_DELETE') }}</li>
                </ul>
              </popover>
            </div>

            <div class="workflow-step-content">
              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_LABEL') }}</label>
                <input type="text" v-model="step.label" />
                <span v-if="displayError && fieldsInError[step.id] && fieldsInError[step.id].includes('label')">
                  {{ translate('COM_EMUNDUS_WORKFLOW_STEP_LABEL_REQUIRED') }}
                </span>
              </div>

              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE') }}</label>
                <select v-model="step.type">
                  <option v-for="type in stepTypes" :key="type.id" :value="type.id">
                    <span v-if="type.parent_id > 0"> - </span>
                    {{ translate(type.label) }}
                  </option>
                </select>
              </div>

              <div v-if="isApplicantStep(step)" class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_PROFILE') }}</label>
                <select v-model="step.profile_id">
                  <option v-for="profile in applicantProfiles" :key="profile.id" :value="profile.id">{{ profile.label }}</option>
                </select>
              </div>

              <div v-else class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_PROFILE') }}</label>
                <select v-model="step.form_id">
                  <option v-for="form in evaluationForms" :key="form.id" :value="form.id">{{ form.label }}</option>
                </select>
              </div>

              <div class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS') }}</label>
                <Multiselect
                    :options="statuses"
                    v-model="step.entry_status"
                    label="label"
                    track-by="id"
                    :placeholder="translate('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS_SELECT')"
                    :multiple="true">
                </Multiselect>
              </div>

              <div v-if="isApplicantStep(step)" class="tw-mb-4 tw-flex tw-flex-col">
                <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS') }}</label>
                <select v-model="step.output_status">
                  <option value="-1">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS_SELECT') }}</option>
                  <option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.label }}</option>
                </select>
              </div>

              <div v-if="!isApplicantStep(step)" class="tw-flex tw-flex-row tw-items-center tw-cursor-pointer">
                <input v-model="step.multiple" true-value="1" false-value="0" type="checkbox" :name="'step-' + step.id + '-multiple'" :id="'step-' + step.id + '-multiple'" class="tw-cursor-pointer"/>
                <label :for="'step-' + step.id + '-multiple'" class="tw-cursor-pointer">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_IS_MULTIPLE') }}</label>
              </div>
            </div>
          </div>
          <p v-if="steps.length < 1" class="tw-w-full tw-text-center"> {{ translate('COM_EMUNDUS_WORKFLOW_NO_STEPS') }} </p>
        </div>
      </div>
      <div v-else-if="activeTab.id === 'programs'">
        <!-- set a checkbox input for each programsOptions -->
        <input type="text" v-model="searchThroughPrograms" :placeholder="translate('COM_EMUNDUS_WORKFLOW_SEARCH_PROGRAMS_PLACEHOLDER')" class="tw-w-full tw-p-2 tw-mb-4 tw-border tw-border-neutral-300 tw-rounded" />

        <div class="tw-mt-4 tw-grid tw-grid-cols-4 tw-gap-3 tw-overflow-auto">
          <div v-for="program in displayedProgramsOptions" :key="program.id">
              <div class="tw-mb-4 tw-flex tw-flex-row tw-items-center tw-cursor-pointer">
                <input :id="'program-' + program.id" type="checkbox" v-model="programs" :value="program" class="tw-cursor-pointer" @change="onCheckProgram(program)" />
                <label :for="'program-' + program.id" class="tw-cursor-pointer tw-m-0" :class="{'tw-text-gray-300': program.associatedToAnotherWorkflow}"> {{ program.label }} </label>
              </div>
          </div>
          <p v-if="programsOptions.length < 1" class="tw-w-full tw-text-center"> {{ translate('COM_EMUNDUS_WORKFLOW_NO_PROGRAMS') }} </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import workflowService from '@/services/workflow.js';
import settingsService from '@/services/settings.js';
import programmeService from '@/services/programme.js';
import fileService from '@/services/file.js';
import formService from '@/services/form.js';

import Popover from '@/components/Popover.vue';
import Tabs from '@/components/Utils/Tabs.vue';
import Multiselect from "vue-multiselect";
import errors from '@/mixins/errors.js';

import { useGlobalStore } from '@/stores/global.js';

export default {
  name: 'WorkflowEdit',
  props: {
    workflowId: {
      type: Number,
      required: true
    }
  },
  components: {
    Multiselect,
    Popover,
    Tabs
  },
  mixins: [errors],
  data() {
    return {
      workflow: {
        id: 0,
        label: ''
      },
      steps: [],
      programs: [],
      stepTypes: [],
      sortByOptions: [],
      statuses : [],
      profiles: [],
      evaluationForms: [],
      programsOptions: [],
      stepMandatoryFields: [
        'label',
        'type',
        'entry_status',
      ],
      fieldsInError: {},
      displayErrors: false,

      searchThroughPrograms: '',

      tabs: [
        {
          id: 'steps',
          name: 'COM_EMUNDUS_WORKFLOW_STEPS',
          description: 'COM_EMUNDUS_WORKFLOW_STEPS_DESC',
          icon: 'schema',
          active: true,
          displayed: true
        },
        {
          id: 'programs',
          name: 'COM_EMUNDUS_WORKFLOW_PROGRAMS',
          description: 'COM_EMUNDUS_WORKFLOW_PROGRAMS_DESC',
          icon: 'join',
          active: false,
          displayed: true
        }
      ]
    }
  },
  mounted() {
    this.getStepTypes();
    this.getStatuses().then(() => {
      this.getPrograms().then(() => {
        this.getProfiles().then(() => {
          this.getWorkflow();
        });
      });
    });
    this.getEvaluationForms();
  },
  methods: {
    getWorkflow() {
      workflowService.getWorkflow(this.workflowId)
        .then(response => {
          this.workflow = response.data.workflow;
          let tmpSteps = response.data.steps;
          tmpSteps.forEach((step) => {
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
    async getStepTypes() {
      return await workflowService.getStepTypes()
        .then(response => {
          this.stepTypes = response.data
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
              label: status.label[useGlobalStore().shortLang]
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
              label: program.label[useGlobalStore().shortLang]
            }
          });

          this.getProgramWorkflows();
        })
        .catch(e => {
          console.log(e);
        });
    },
    async getProgramWorkflows() {
      return await workflowService.getProgramsWorkflows()
        .then(response => {
          this.programsOptions.forEach((program) => {
            if (response.data[program.id]) {
              program.workflows = response.data[program.id].map(workflow => parseInt(workflow));
            } else {
              program.workflows = [];
            }
          });
        })
        .catch(e => {
          console.log(e);
        });
    },
    async getProfiles() {
      return await fileService.getProfiles()
        .then(response => {
          const filteredProfiles = response.data.filter((profile) => {
            return profile.label !== 'noprofile';
          });

          this.profiles = filteredProfiles.map(profile => {
            return {
              id: profile.id,
              label: profile.label,
              applicantProfile: profile.published
            };
          });
        })
        .catch(e => {
          console.log(e);
        });
    },
    getEvaluationForms() {
      formService.getEvaluationForms().then((response) => {
        if (response.status) {
          this.evaluationForms = response.data.datas.map((form) => {
            return { id: form.id, label: form.label[useGlobalStore().shortLang] };
          });
        }
      });
    },
    getStepSubTypes(stepType) {
      return this.stepTypes.filter(type => type.parent_id == stepType);
    },
    addStep() {
      const newStep = {
        id: 0,
        label: this.translate('COM_EMUNDUS_WORKFLOW_NEW_STEP_LABEL'),
        type: 1,
        roles: [],
        profile_id: 9,
        entry_status: [],
        output_status: 0,
      };

      // set a new id inferior to 0 to be able to delete it without calling the API
      newStep.id = this.steps.reduce((acc, step) => {
        if (step.id < acc) {
          acc = step.id;
        }
        return acc;
      }, 0) - 1;

      this.steps.push(newStep);
    },

    duplicateStep(stepId) {
      const step = this.steps.find((step) => step.id == stepId);

      if (step) {
        const newStep = { ...step };

        newStep.id = this.steps.reduce((acc, step) => {
          if (step.id < acc) {
            acc = step.id;
          }
          return acc;
        }, 0) - 1;

        this.steps.push(newStep);
      }
    },
    async updateStepState(stepId, state = 0) {
      let archived = false;

      if (stepId > 0) {
        const response = await workflowService.updateStepState(stepId, state);

        if (response.status) {
          this.steps = this.steps.map((step) => {
            if (step.id == stepId) {
              step.state = state;
            }
            return step;
          });
          archived = true;

          return archived;
        } else {
          this.displayError('COM_EMUNDUS_WORKFLOW_ARCHIVE_FAILED', response.message);
        }
      } else {
        return archived;
      }
    },
    beforeDeleteStep(stepId) {
      Swal.fire({
        title: this.translate('COM_EMUNDUS_WORKFLOW_DELETE_STEP_CONFIRMATION'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: this.translate('COM_EMUNDUS_ACTIONS_DELETE'),
        cancelButtonText: this.translate('CANCEL'),
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          cancelButton: 'em-swal-cancel-button',
          actions: 'em-swal-double-action'
        }
      }).then((result) => {
        if (result.isConfirmed) {
          this.deleteStep(stepId);
        }
      });
    },
    async deleteStep(stepId) {
      let deleted = false;

      if (stepId < 1) {
        this.steps = this.steps.filter((step) => {
          return step.id != stepId;
        });
        deleted = true;
      } else {
        const response = await workflowService.deleteWorkflowStep(stepId);

        if (response.status) {
          this.steps = this.steps.filter((step) => {
            return step.id != stepId;
          });
          deleted = true;
        }
      }

      return deleted;
    },
    onBeforeSave() {
      let check = false;

      let stepsCheck = [];

      this.fieldsInError = {};
      this.steps.forEach((step) => {
        this.fieldsInError[step.id] = [];

        stepsCheck.push(this.stepMandatoryFields.every((field) => {
          let emptyField = true;
          switch (typeof step[field]) {
          case 'string':
            emptyField = step[field].trim() === '';
            break;
          case 'object':
            emptyField = step[field].length < 1;
            break;
          default:
            emptyField = step[field] === '';
          }

          if (emptyField) {
            this.fieldsInError[step.id].push(field);
          }

          return !emptyField;
        }));
      });

      check = stepsCheck.every((stepCheck) => {
        return stepCheck;
      });

      return check;
    },
    onCheckProgram(program) {
      if (program.associatedToAnotherWorkflow) {
        Swal.fire({
          icon: 'warning',
          title: this.translate('COM_EMUNDUS_WORKFLOW_PROGRAM_ASSOCIATED_TO_ANOTHER_WORKFLOW'),
          text: this.translate('COM_EMUNDUS_WORKFLOW_PROGRAM_ASSOCIATED_TO_ANOTHER_WORKFLOW_TEXT'),
          showConfirmButton: true,
          confirmButtonText: this.translate('COM_EMUNDUS_WORKFLOW_CONFIRM_CHANGE_PROGRAM_ASSOCIATION'),
          showCancelButton: true,
          cancelButtonText: this.translate('CANCEL'),
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            confirmButton: 'em-swal-confirm-button',
            cancelButton: 'em-swal-cancel-button',
            actions: 'em-swal-double-action'
          }
        }).then((result) => {
          if (result.value) {
            program.workflows = [this.workflow.id];
          } else {
            this.programs = this.programs.filter((p) => p.id !== program.id);
          }
        });
      }
    },
    save() {
      const checked = this.onBeforeSave();

      if (checked) {
        workflowService.saveWorkflow(this.workflow, this.steps, this.programs)
          .then(response => {
            if (response.status) {
              Swal.fire({
                icon: 'success',
                title: this.translate('COM_EMUNDUS_WORKFLOW_SAVE_SUCCESS'),
                showConfirmButton: false,
                timer: 1500
              });

              this.getWorkflow();
            } else {
              this.displayError('COM_EMUNDUS_WORKFLOW_SAVE_FAILED', response.message);
            }
          })
          .catch((e) => {
            console.log(e);
            this.displayError('COM_EMUNDUS_WORKFLOW_SAVE_FAILED', '');
          });
      } else {
        this.displayErrors = true;

        setTimeout(() => {
          this.displayErrors = false;
        }, 15000);
      }
    },
    goBack() {
      window.location.href = '/workflows';
    },
    isApplicantStep(step) {
      let isApplicantStep = step.type == 1;

      if (!isApplicantStep) {
        const stepType = this.stepTypes.find((stepType) => stepType.id === step.type);

        if (stepType.parent_id == 1) {
          isApplicantStep = true;
        }
      }

      return isApplicantStep;
    }
  },
  computed: {
    nonApplicantProfiles() {
      return this.profiles.filter(profile => !profile.applicantProfile);
    },
    applicantProfiles() {
      return this.profiles.filter(profile => profile.applicantProfile);
    },
    parentStepTypes() {
      return this.stepTypes.filter(type => type.parent_id === 0);
    },
    activeTab() {
      return this.tabs.find(tab => tab.active);
    },
    displayedProgramsOptions() {
      return this.programsOptions.filter((program) => {
        program.associatedToAnotherWorkflow = program.workflows && program.workflows.length > 0 && !(program.workflows.includes(this.workflow.id));
        return program.label.toLowerCase().includes(this.searchThroughPrograms.toLowerCase());
      });
    }
  }
}
</script>

<style scoped>

.workflow-step {
  width: 350px;
}

</style>