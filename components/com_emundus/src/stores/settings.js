import { defineStore } from 'pinia';

export const useSettingsStore = defineStore('settings',{
  state: () => ({
    needSaving: false
  }),
  getters: {
    getNeedSaving: state => state.needSaving
  },
  actions: {
    updateNeedSaving(payload) {
      this.needSaving = payload;
    }
  },
});