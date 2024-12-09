<template>
  <div id="program-edition-container" class="em-border-cards em-card-shadow tw-rounded em-white-bg em-p-24 tw-m-4">
    <h1 class="tw-mb-4">{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_TITLE') }}</h1>
    <h2 class="tw-mb-2">{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_SUBTITLE') }}</h2>
    <p>{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_INTRO') }}</p>

    <nav class="tw-mt-4">
      <ul class="tw-flex tw-flex-row tw-list-none">
        <li class="tw-cursor-pointer tw-shadow tw-rounded-t-lg tw-px-2.5 tw-py-3"
            :class="{'em-bg-main-500 em-text-neutral-300': selectedTab === tab.name}"
            v-for="tab in tabs" :key="tab.name"
            @click="selectedTab = tab.name"
        >
          {{ translate(tab.label) }}
        </li>
      </ul>
    </nav>

    <div class="tw-w-full" v-show="selectedTab === 'general'">
      <iframe class="tw-w-full hide-titles" style="height: 150vh;" :src="'/campaigns/modifier-un-programme?rowid=' + this.programId + '&tmpl=component&iframe=1'">
      </iframe>
    </div>

    <div class="tw-w-full tw-p-4" v-show="selectedTab === 'campaigns'">
      <p class="tw-mb-2">{{ translate('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE') }}</p>
      <ul class="tw-my-4"><li v-for="campaign in campaigns" :key="campaign.id"><a :href="'/campaigns/edit?cid=' + campaign.id" target="_blank">{{ campaign.label }}</a> </li></ul>
      <a href="/campaigns" class="tw-underline" target="_blank"> {{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_CAMPAIGNS') }} </a>
    </div>

    <div class="tw-w-full tw-my-4" v-show="selectedTab === 'workflows'">
      <label class="tw-mb-2 tw-mr-2">{{ translate('COM_EMUNDUS_ONBOARD_WORKFLOWS_ASSOCIATED_TITLE') }}</label>
      <select v-model="workflowId">
        <option v-for="workflow in workflowOptions" :key="workflow.id" :value="workflow.id">{{ workflow.label }}</option>
      </select>
      <div class="tw-flex tw-flex-row tw-justify-between">
        <a href="/workflows" class="tw-underline" target="_blank"> {{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_WORKFLOWS') }} </a>
        <button class="tw-btn-primary" @click="updateProgramWorkflows">
          {{ translate('SAVE') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import campaignService from '@/services/campaign';
import workflowService from '@/services/workflow';
import Multiselect from "vue-multiselect";

export default {
  name: 'ProgramEdit',
  components: {Multiselect},
  props: {
    programId: {
      type: Number,
      required: true,
    }
  },
  data() {
    return {
      program: {},
      campaigns: [],
      workflowId: 0,
      workflowOptions: [],
      tabs: [
        {
          name: 'general',
          label: this.translate('COM_EMUNDUS_PROGRAMS_EDITION_TAB_GENERAL')
        },
        {
          name: 'campaigns',
          label: this.translate('COM_EMUNDUS_PROGRAMS_EDITION_TAB_CAMPAIGNS')
        },
        {
          name: 'workflows',
          label: this.translate('COM_EMUNDUS_PROGRAMS_EDITION_TAB_WORKFLOWS')
        },
      ],
      selectedTab: 'general',
    };
  },
  created() {
    this.getWorkflows();
    this.getAssociatedCampaigns();
    this.getAssociatedWorkflow();
  },
  methods: {
    getWorkflows() {
      workflowService.getWorkflows().then((response) => {
        if (response.status) {
          this.workflowOptions = response.data.datas.map((workflow) => {
            return {
              id: workflow.id,
              label: workflow.label.fr,
            };
          });
        }
      });
    },
    getAssociatedCampaigns() {
      campaignService.getCampaignsByProgramId(this.programId).then((response) => {
        this.campaigns = response.data;
      });
    },
    getAssociatedWorkflow() {
      workflowService.getWorkflowsByProgramId(this.programId).then((response) => {
        const workflows = response.data.map((workflow) => workflow.id);
        if (workflows.length) {
          this.workflowId = workflows[0];
        }
      });
    },
    updateProgramWorkflows() {
      workflowService.updateProgramWorkflows(this.programId, [this.workflowId]).then((response) => {
        Swal.fire({
          icon: 'success',
          title: this.translate('COM_EMUNDUS_PROGRAM_UPDATE_ASSOCIATED_WORKFLOW_SUCCESS'),
          showConfirmButton: false,
          timer: 1500
        });
      });
    }
  }
}
</script>

<style>

</style>