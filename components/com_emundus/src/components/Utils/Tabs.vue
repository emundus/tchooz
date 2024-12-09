<template>
  <div :class="classes">
    <template v-for="tab in currentTabs">
      <div v-show="tab.displayed" @click="changeTab(tab.id)" class="tw-cursor-pointer tw-rounded-t-lg tw-flex tw-items-center tw-py-2 tw-px-4 tw-transition-colors tw-duration-300 tw-border-x tw-border-t" :class="tab.active ? 'tw-bg-white tw-border-profile-full' : 'tw-bg-neutral-200 tw-border-neutral-400'">
        <span class="material-symbols-outlined tw-mr-2" :class="tab.active ? 'tw-text-profile-full' : 'tw-text-neutral-700'">{{ tab.icon }}</span>
        <span :class="tab.active ? 'tw-text-profile-full' : 'tw-text-neutral-700'" class="tw-whitespace-nowrap">{{ translate(tab.name) }}</span>
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
    classes: {
      type: String,
      default: 'tw-overflow-x-scroll tw-absolute tw-right-6 tw-flex tw-items-center tw-justify-end tw-gap-2 -tw-top-[36px]'
    }
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

      this.$emit('changeTabActive', id);
    }
  }
}
</script>

<style scoped>

</style>