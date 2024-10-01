<template>
  <div class="tw-absolute tw-right-6 tw-flex tw-items-center tw-justify-end tw-gap-2 -tw-top-[36px]">
    <template v-for="tab in currentTabs">
      <div @click="changeTab(tab.id)" class="tw-cursor-pointer tw-rounded-t-lg tw-flex tw-items-center tw-py-2 tw-px-4 tw-transition-colors tw-duration-300 tw-border-x" :class="tab.active ? 'tw-bg-white tw-border-neutral-300' : 'tw-bg-profile-full'">
        <span class="material-symbols-outlined tw-mr-2" :class="tab.active ? 'tw-text-profile-full' : 'tw-text-white'">{{ tab.icon }}</span>
        <span :class="tab.active ? 'tw-text-profile-full' : 'tw-text-white'">{{ translate(tab.name) }}</span>
      </div>
    </template>
  </div>
</template>

<script>
export default {
  name: "Tabs",
  props: {
    tabs: {
      type: Array,
      required: true,
    },
  },

  data() {
    return {
      currentTabs: []
    }
  },
  created() {
    this.currentTabs = this.tabs;
  },
  methods: {
    changeTab(id) {
      this.currentTabs.forEach(tab => {
        tab.active = tab.id === id;
      });
    }
  }
}
</script>

<style scoped>

</style>