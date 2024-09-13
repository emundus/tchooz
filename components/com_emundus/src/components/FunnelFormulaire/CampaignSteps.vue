<template>
  <div id="campaign-steps">
    <div v-for="step in steps" :key="step.id" :id="'campaign-step-' + id + '-wrapper'" class="tw-my-4">
      <h3>{{ step.label }}</h3>

      <div class="tw-mb-4 tw-flex tw-items-center">
        <div class="em-toggle">
          <input type="checkbox"
                 true-value="1"
                 false-value="0"
                 class="tw-mt-2 em-toggle-check"
                 :id="'step_' + step.id + '_infinite'"
                 :name="'step_' + step.id + '_infinite'"
                 v-model="step.infinite"
          />
          <strong class="b em-toggle-switch"></strong>
          <strong class="b em-toggle-track"></strong>
        </div>
          <span :for="'step_' + step.id + '_infinite'" class="tw-ml-2 tw-flex tw-items-center">
            {{ translate('COM_EMUNDUS_CAMPAIGNS_INFINITE_STEP') }}
          </span>
      </div>

      <div class="tw-flex tw-flex-row tw-w-full tw-gap-2" v-if="step.infinite == 0">
        <div class="tw-w-full">
          <label :for="'start_date_' + step.id">{{ translate('COM_EMUNDUS_CAMPAIGN_STEP_START_DATE') }}</label>
          <DatePicker
              :id="'campaign_step_' + step.id + '_start_date'"
              v-model="step.start_date"
              :keepVisibleOnInput="true"
              :time-accuracy="2"
              mode="dateTime"
              is24hr
              hide-time-header
              title-position="left"
              :input-debounce="500"
              :popover="{visibility: 'focus'}"
              :locale="actualLanguage">
            <template #default="{ inputValue, inputEvents }">
              <input
                  :value="inputValue"
                  v-on="inputEvents"
                  class="tw-mt-2 form-control fabrikinput tw-w-full"
                  :id="'start_date_' + step.id +'_input'"
                  :name="'start_date_' + step.id"
              />
            </template>
          </DatePicker>
        </div>
        <div class="tw-w-full">
          <label :for="'end_date_' + step.id">{{ translate('COM_EMUNDUS_CAMPAIGN_STEP_END_DATE') }}</label>
          <DatePicker
              :id="'campaign_step_' + step.id + '_end_date'"
              v-model="step.end_date"
              :keepVisibleOnInput="true"
              :time-accuracy="2"
              mode="dateTime"
              is24hr
              hide-time-header
              title-position="left"
              :input-debounce="500"
              :popover="{visibility: 'focus'}"
              :locale="actualLanguage">
            <template #default="{ inputValue, inputEvents }">
              <input
                  :value="inputValue"
                  v-on="inputEvents"
                  class="tw-mt-2 form-control fabrikinput tw-w-full"
                  :id="'end_date_' + step.id +'_input'"
                  :name="'end_date_' + step.id"
              />
            </template>
          </DatePicker>
        </div>
      </div>
    </div>

    <div class="tw-flex tw-flex-row tw-justify-end">
      <button class="tw-btn tw-btn-primary tw-mt-4" @click="saveCampaignSteps">{{ translate('COM_EMUNDUS_SAVE') }}</button>
    </div>
  </div>
</template>

<script>
import workflowService from '@/services/workflow.js';
import { DatePicker } from 'v-calendar';
import { useGlobalStore } from "@/stores/global.js";

export default {
  name: 'CampaignSteps',
  components: {
    DatePicker
  },
  props: {
    campaignId: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      steps: [],
      actualLanguage: null
    }
  },
  created() {
    this.actualLanguage = useGlobalStore().getShortLang;

    this.getCampaignSteps(this.campaignId);
  },
  methods: {
    getCampaignSteps() {
      workflowService.getCampaignSteps(this.campaignId)
        .then(response => {
          this.steps = response.data;
        })
        .catch(error => {
          console.log(error);
        });
    },
    saveCampaignSteps() {
      this.steps.forEach((step) => {
        step.start_date = step.start_date === null || step.start_date === '' || step.start_date === '0000-00-00 00:00:00' ? '0000-00-00 00:00:00' : this.formatDate(new Date(step.start_date), 'YYYY-MM-DD HH:mm:ss');
        step.end_date = step.end_date === null || step.end_date === '' || step.end_date === '0000-00-00 00:00:00' ? '0000-00-00 00:00:00' : this.formatDate(new Date(step.end_date), 'YYYY-MM-DD HH:mm:ss');
      });

      workflowService.saveCampaignSteps(this.campaignId, this.steps)
        .then(response => {
          console.log(response);
        })
        .catch(error => {
          console.log(error);
        });
    },
    formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
      if (date == '' || date == null || date == '0000-00-00 00:00:00') {
        return '0000-00-00 00:00:00';
      }
      let year = date.getFullYear();
      let month = (1 + date.getMonth()).toString().padStart(2, '0');
      let day = date.getDate().toString().padStart(2, '0');
      let hours = date.getHours().toString().padStart(2, '0');
      let minutes = date.getMinutes().toString().padStart(2, '0');
      let seconds = date.getSeconds().toString().padStart(2, '0');

      return format
        .replace('YYYY', year)
        .replace('MM', month)
        .replace('DD', day)
        .replace('HH', hours)
        .replace('mm', minutes)
        .replace('ss', seconds);
    }
  }
}
</script>

<style scoped>

</style>