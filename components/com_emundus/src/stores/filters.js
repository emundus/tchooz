import { defineStore } from 'pinia';
import groupsService from '@/services/groups.js';

export const useFiltersStore = defineStore('filters', {
	state: () => ({
		usersToShareTo: [],
		usersToShareToLoaded: false,

		groupsToShareTo: [],
		groupsToShareToLoaded: false,
	}),
	getters: {
		getUsersToShareTo: (state) => state.usersToShareTo,
		getGroupsToShareTo: (state) => state.groupsToShareTo,
		getUsersToShareToLoaded: (state) => state.usersToShareToLoaded,
		getGroupsToShareToLoaded: (state) => state.groupsToShareToLoaded,
	},
	actions: {
		async fetchUsersToShareTo() {
			if (!this.usersToShareToLoaded) {
				this.usersToShareTo = await groupsService.getUsersToShareTo();
				this.usersToShareToLoaded = true;
			}
		},
		async fetchGroupsToShareTo() {
			if (!this.groupsToShareToLoaded) {
				this.groupsToShareTo = await groupsService.getGroupsToShareTo();
				this.groupsToShareToLoaded = true;
			}
		},
	},
});
