<template>
  <div id="program-edition-container" class="tw-border tw-border-neutral-300 em-card-shadow tw-rounded tw-p-6 tw-m-4 tw-bg-white">
    <div class="tw-flex tw-items-center tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300"
         @click="redirectJRoute('index.php?option=com_emundus&view=campaigns')">
      <span class="material-symbols-outlined tw-text-neutral-600">navigate_before</span>
      <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
    </div>

    <div class="tw-flex tw-items-center tw-mt-4">
      <h1 class="tw-mb-4">{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_TITLE') }}</h1>
    </div>
    <h2 class="tw-mb-2">{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_SUBTITLE') }}</h2>
    <p>{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_INTRO') }}</p>
    <hr />

    <div class="tw-mt-4">
      <Tabs :tabs="tabs"
            :classes="'tw-overflow-x-scroll tw-flex tw-items-center tw-gap-2 tw-ml-7'"></Tabs>

      <div class="tw-w-full tw-rounded-2xl tw-p-6 tw-border tw-border-neutral-300 tw-relative"
           :style="{backgroundColor: activeBackground}">
        <div class="tw-w-full" v-show="selectedMenuItem.code === 'general'">
          <iframe class="tw-w-full hide-titles" style="height: 150vh;"
                  :src="'/index.php?option=com_fabrik&view=form&formid=108&rowid=' + this.programId + '&tmpl=component&iframe=1'">
          </iframe>
        </div>

        <div class="tw-w-full tw-flex tw-flex-col tw-gap-2" v-show="selectedMenuItem.code === 'campaigns'">
          <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE') }}</label>
          <ul>
            <li v-for="campaign in campaigns" :key="campaign.id"><a class="tw-cursor-pointer" @click="redirectJRoute('index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' + campaign.id)"
                                                                    target="_blank">{{ campaign.label }}</a></li>
          </ul>
          <a @click="redirectJRoute('index.php?option=com_emundus&view=campaigns')" class="tw-cursor-pointer tw-underline" target="_blank">
            {{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_CAMPAIGNS') }} </a>
        </div>

        <div class="tw-w-full tw-flex tw-flex-col tw-gap-2" v-show="selectedMenuItem.code === 'workflows'">
          <div class="tw-flex tw-flex-col">
            <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_WORKFLOWS_ASSOCIATED_TITLE') }}</label>
            <select v-model="workflowId">
              <option v-for="workflow in workflowOptions" :key="workflow.id" :value="workflow.id">{{
                  workflow.label
                }}
              </option>
            </select>
          </div>

          <div>
            <a @click="redirectJRoute('index.php?option=com_emundus&view=workflows')" class="tw-cursor-pointer tw-underline" target="_blank">
              {{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_WORKFLOWS') }} </a>
          </div>

          <div class="tw-flex tw-justify-end tw-mt-2">
            <button class="tw-btn-primary" @click="updateProgramWorkflows">
              {{ translate('SAVE') }}
            </button>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import campaignService from '@/services/campaign';
import workflowService from '@/services/workflow';
import Multiselect from "vue-multiselect";
import Tabs from "@/components/Utils/Tabs.vue";
import settingsService from "@/services/settings.js";
import {useGlobalStore} from "@/stores/global.js";

export default {
  name: 'ProgramEdit',
  components: {Tabs, Multiselect},
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
          id: 1,
          code: 'general',
          name: 'COM_EMUNDUS_PROGRAMS_EDITION_TAB_GENERAL',
          icon: 'info',
          active: true,
          displayed: true
        },
        {
          id: 2,
          code: 'campaigns',
          name: 'COM_EMUNDUS_PROGRAMS_EDITION_TAB_CAMPAIGNS',
          icon: 'layers',
          active: false,
          displayed: true
        },
        {
          id: 3,
          code: 'workflows',
          name: 'COM_EMUNDUS_PROGRAMS_EDITION_TAB_WORKFLOWS',
          icon: 'schema',
          active: false,
          displayed: true
        },
      ],
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
    },
    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },
  },
  computed: {
    selectedMenuItem() {
      return this.tabs.find(tab => tab.active);
    },
    activeBackground() {
      console.log(this.selectedMenuItem.code);
      return this.selectedMenuItem.code === 'general' ? 'var(--em-coordinator-bg)' : '#fff';
    }
  }
}
</script>

<style>

</style>