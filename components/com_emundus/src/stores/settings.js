import { defineStore } from 'pinia';

export const useSettingsStore = defineStore('settings', {
	state: () => ({
		needSaving: false,
	}),
	getters: {
		getNeedSaving: (state) => state.needSaving,
		getStatuses: (state) => state.statuses,
	},
	actions: {
		updateNeedSaving(payload) {
			this.needSaving = payload;
		},
		setStatuses(statuses) {
			this.statuses = statuses;
		},
	},
});
