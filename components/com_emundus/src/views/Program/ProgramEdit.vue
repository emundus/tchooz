<template>
  <div id="program-edition-container" class="em-border-cards em-card-shadow tw-rounded em-white-bg em-p-24 tw-m-4">
    <h1>{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_TITLE') }}</h1>
    <h2>{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_SUBTITLE') }}</h2>
    <p>{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_INTRO') }}</p>

    <nav>
      <ul class="tw-flex tw-flex-row tw-list-none">
        <li class="tw-cursor-pointer" v-for="tab in tabs" :key="tab.name" @click="selectedTab = tab.name"> {{ translate(tab.label) }}</li>
      </ul>
    </nav>

    <div class="tw-w-full" v-show="selectedTab === 'general'">
      <iframe class="tw-w-full hide-titles" style="height: 150vh;" :src="'/campaigns/modifier-un-programme?rowid=' + this.programId + '&tmpl=component&iframe=1'">
      </iframe>
    </div>

    <div class="tw-w-full" v-show="selectedTab === 'campaigns'">
      <p>Display list of campaigns</p>
    </div>

    <div class="tw-w-full" v-show="selectedTab === 'workflows'">
      <p>Display list of workflows</p>
    </div>
  </div>
</template>

<script>
export default {
  name: "ProgramEdit",
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
}
</script>

<style>

</style>