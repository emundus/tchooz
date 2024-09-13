<template>
  <div class="tw-m-2">
    <div class="tw-flex tw-items-center tw-cursor-pointer tw-mb-4" @click="goBack">
      <span class="material-icons-outlined">navigate_before</span>
      <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
    </div>
    <div id="header">
      <div class="tw-flex tw-flex-row tw-justify-between">
        <input id="workflow-label" name="workflow-label" class="!tw-w-[350px]" type="text" v-model="workflow.label" />
        <button class="tw-btn-primary tw-flex tw-items-center tw-gap-1" href="#" @click="save">
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
        </div>
      </div>
    </div>
    <div id="workflow-steps-wrapper" class="tw-my-4 tw-flex tw-flex-col tw-p-2 tw-border tw-rounded">
      <a class="tw-btn-primary tw-h-fit tw-w-fit tw-mb-4" href="#" @click="addStep"> {{ translate('COM_EMUNDUS_WORKFLOW_ADD_STEP') }} </a>

      <div id="workflow-steps" class="tw-grid tw-grid-cols-3 tw-gap-3 tw-overflow-auto">
        <div v-for="step in steps" :key="step.id" class="workflow-step tw-rounded tw-border tw-shadow-sm tw-p-4 em-white-bg">
          <div class="workflow-step-head tw-flex tw-flex-row tw-justify-between">
            <h4>{{ step.label }}</h4>
            <popover>
              <ul class="tw-list-none !tw-p-0">
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
              <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE_PARENT') }}</label>
              <select v-model="step.type">
                <option v-for="type in parentStepTypes" :key="type.id" :value="type.id">{{ translate(type.label) }}</option>
              </select>
            </div>

            <div v-if="getStepSubTypes(step.type).length > 0" class="tw-mb-4 tw-flex tw-flex-col">
              <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE_CHILDREN') }}</label>
              <select v-model="step.sub_type">
                <option v-for="type in getStepSubTypes(step.type)" :key="type.id" :value="type.id">{{ translate(type.label) }}</option>
              </select>
            </div>

            <div v-if="step.type != 1" class="tw-mb-4 tw-flex tw-flex-col">
              <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_GROUPS') }}</label>
              <Multiselect
                  :options="groups"
                  v-model="step.group_ids"
                  label="label"
                  track-by="id"
                  placeholder="Select a group"
                  :multiple="true">
              </Multiselect>
            </div>

            <div v-if="step.type == 1" class="tw-mb-4 tw-flex tw-flex-col">
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
                  placeholder="Select a status"
                  :multiple="true">
              </Multiselect>
            </div>

            <div v-if="step.type == 1" class="tw-mb-4 tw-flex tw-flex-col">
              <label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS') }}</label>
              <select v-model="step.output_status">
                <option value="-1">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS_SELECT') }}</option>
                <option v-for="status in statuses" :key="status.id" :value="status.id">{{ status.label }}</option>
              </select>
            </div>
          </div>
        </div>
        <p v-if="steps.length < 1" class="tw-w-full tw-text-center"> {{ translate('COM_EMUNDUS_WORKFLOW_NO_STEPS') }} </p>
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
import groupsService from '@/services/groups.js';

import Popover from '@/components/Popover.vue';
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
    Popover
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
      groups: [],
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
    }
  },
  mounted() {
    this.getStepTypes();
    this.getGroups();
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
            step.group_ids = this.groups.filter(group => step.group_ids.includes(group.id.toString()));
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
    async getGroups() {
      return await groupsService.getGroups()
        .then(response => {
          this.groups = response.data.map(group => {
            return {
              id: group.id,
              label: group.label
            }
          });
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
        type: 'applicant',
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
      window.history.go(-1);
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
    }
  }
}
</script>

<style scoped>

</style>