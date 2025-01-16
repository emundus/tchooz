import { defineStore } from 'pinia';
export const useEventStore = defineStore('event',{
  state: () => ({
    unsavedChanges: false,
  }),
  actions: {
    setUnsavedChanges(value) {
      this.unsavedChanges = value;
    }
  }
});