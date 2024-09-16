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

    <div class="tw-w-full tw-p-4" v-show="selectedTab === 'workflows'">
      <p class="tw-mb-2">{{ translate('COM_EMUNDUS_ONBOARD_WORKFLOWS_ASSOCIATED_TITLE') }}</p>
      <ul class="tw-my-4"><li v-for="workflow in workflows" :key="workflow.id"> {{ workflow.label }} </li></ul>
      <a href="/workflows" class="tw-underline" target="_blank"> {{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_WORKFLOWS') }} </a>
    </div>
  </div>
</template>

<script>
import campaignService from '@/services/campaign';
import workflowService from '@/services/workflow';

export default {
  name: 'ProgramEdit',
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
      workflows: [],
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
    this.getAssociatedCampaigns();
    this.getAssociatedWorkflows();
  },
  methods: {
    getAssociatedCampaigns() {
      campaignService.getCampaignsByProgramId(this.programId).then((response) => {
        this.campaigns = response.data;
      });
    },
    getAssociatedWorkflows() {
      workflowService.getWorkflowsByProgramId(this.programId).then((response) => {
        this.workflows = response.data;
      });
    },
  }
}
</script>

<style>

</style>