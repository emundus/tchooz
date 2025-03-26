import { defineStore } from 'pinia';

export const useSmsStore = defineStore('sms', {
    state: () => ({
        activated: null,
    }),
    getters: {
        getActivated: (state) => state.activated,
    },
    actions: {
        updateActivated(payload) {
            this.activated = payload
        }
    },
});
