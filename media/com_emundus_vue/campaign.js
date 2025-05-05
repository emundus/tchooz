import { ax as defineStore } from "./app_emundus.js";
const useCampaignStore = defineStore("campaign", {
  state: () => ({
    unsavedChanges: false,
    allowPinnedCampaign: false,
    pinned: 0,
    activated: null
  }),
  getters: {
    getActivated: (state) => state.activated
  },
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
    updateActivated(payload) {
      this.activated = payload;
    }
  }
});
export {
  useCampaignStore as u
};
