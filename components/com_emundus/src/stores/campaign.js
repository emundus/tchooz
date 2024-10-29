import { defineStore } from 'pinia';
export const useCampaignStore = defineStore('campaign',{
  state: () => ({
    unsavedChanges: false,
    allowPinnedCampaign: false,
    pinned: 0
  }),
  actions: {
    setUnsavedChanges(value) {
      this.unsavedChanges = value;
    },
    setPinned(value) {
      this.pinned = value;
    },
    setAllowPinnedCampaign(value) {
      this.allowPinnedCampaign = value;
    },
  }
});