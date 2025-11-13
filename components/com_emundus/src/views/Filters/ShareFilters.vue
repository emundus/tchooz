<script>
import AdvancedSelect from '@/components/AdvancedSelect.vue';
import filtersService from '@/services/filters.js';
import alertsMixin from '@/mixins/alerts.js';
import { useFiltersStore } from '@/stores/filters.js';

export default {
	name: 'ShareFilters',
	components: { AdvancedSelect },
	props: {
		filter: {
			type: Object,
			required: true,
		},
	},
	mixins: [alertsMixin],
	data() {
		return {
			displaySharings: false,
			selectedUsers: [],
			usersToShareTo: [],
			selectedGroups: [],
			groupsToShareTo: [],
			alreadySharedTo: {
				users: [],
				groups: [],
			},
		};
	},
	created() {
		this.getUsersToShareTo();
		this.getGroupsToShareTo();
	},
	mounted() {
		// if filter does not have id or name => close modal
		if (!this.filter.id || !this.filter.name) {
			this.$emit('close');
		}

		this.getAlreadySharedTo();
	},
	methods: {
		async getUsersToShareTo() {
			await useFiltersStore().fetchUsersToShareTo();
			this.usersToShareTo = useFiltersStore().usersToShareTo;
		},
		async getGroupsToShareTo() {
			await useFiltersStore().fetchGroupsToShareTo();
			this.groupsToShareTo = useFiltersStore().groupsToShareTo;
		},
		getAlreadySharedTo() {
			filtersService.getAlreadySharedTo(this.filter.id).then((response) => {
				this.alreadySharedTo = response;
			});
		},
		shareFilter() {
			filtersService.shareFilter(this.filter.id, this.selectedUsers, this.selectedGroups).then((response) => {
				if (response.status) {
					this.alertSuccess('MOD_EMUNDUS_FILTERS_SHARE_SUCCESS');

					this.$emit('close');
				} else {
					this.alertError('MOD_EMUNDUS_FILTERS_SHARE_ERROR');
				}
			});
		},
		deleteUserSharing(id) {
			filtersService.deleteSharing(this.filter.id, id, 'user_id').catch((error) => {
				console.log(error);
			});
			this.alreadySharedTo.users = this.alreadySharedTo.users.filter((user) => user.id !== id);
		},
		deleteGroupSharing(id) {
			filtersService.deleteSharing(this.filter.id, id, 'group_id').catch((error) => {
				console.log(error);
			});
			this.alreadySharedTo.groups = this.alreadySharedTo.groups.filter((group) => group.id !== id);
		},
		onSelectUser(id) {
			this.selectedUsers.push(id);
		},
		onSelectGroup(id) {
			this.selectedGroups.push(id);
		},
		removeUser(id) {
			this.selectedUsers = this.selectedUsers.filter((user) => user !== id);
		},
		removeGroup(id) {
			this.selectedGroups = this.selectedGroups.filter((group) => group !== id);
		},
	},
	computed: {
		usersOptions() {
			return this.usersToShareTo.filter((user) => {
				const foundInAlreadyShared = this.alreadySharedTo.users.find((u) => u.id === user.id);
				return !this.selectedUsers.includes(user.id) && !foundInAlreadyShared;
			});
		},
		groupsOptions() {
			return this.groupsToShareTo.filter((group) => {
				const foundInAlreadyShared = this.alreadySharedTo.groups.find((g) => g.id === group.id);
				return !this.selectedGroups.includes(group.id) && !foundInAlreadyShared;
			});
		},
		selectedUsersLabels() {
			return this.selectedUsers.map((user) => {
				return this.usersToShareTo.find((u) => u.id === user);
			});
		},
		selectedGroupsLabels() {
			return this.selectedGroups.map((group) => {
				return this.groupsToShareTo.find((g) => g.id === group);
			});
		},
	},
};
</script>

<template>
	<div id="share-filters" class="tw-w-full tw-p-4">
		<div v-if="!displaySharings">
			<section id="share-to" class="tw-mb-2">
				<h3>
					{{
						translate('MOD_EMUNDUS_FILTERS_SHARE_WITH_1') + filter.name + translate('MOD_EMUNDUS_FILTERS_SHARE_WITH_2')
					}}
				</h3>
				<div v-if="usersToShareTo.length > 0" class="tw-mb-2 tw-mt-2">
					<label>{{ translate('MOD_EMUNDUS_FILTERS_SHARE_WITH_USERS') }} : </label>
					<div v-if="selectedUsersLabels.length > 0" id="selected-users" class="tw-mb-2 tw-mt-2 tw-flex">
						<div
							v-for="user in selectedUsersLabels"
							:key="user.id"
							class="label label-default tw-mb-2 tw-mr-2 !tw-flex tw-items-center"
						>
							<span class="material-symbols-outlined tw-cursor-pointer" @click="removeUser(user.id)">close</span>
							<span class="tw-ml-2">{{ user.label }}</span>
						</div>
					</div>
					<advanced-select
						:filters="usersOptions"
						:close-on-choose="false"
						:position-absolute="true"
						:max-height="150"
						@filter-selected="onSelectUser"
					></advanced-select>
				</div>

				<div v-if="groupsToShareTo.length > 0" class="tw-mb-2 tw-mt-2">
					<label>{{ translate('MOD_EMUNDUS_FILTERS_SHARE_WITH_GROUPS') }} : </label>
					<div
						v-if="selectedGroupsLabels.length > 0"
						id="selected-groups"
						class="selected-values-to-share tw-mb-2 tw-mt-2 tw-flex"
					>
						<div
							v-for="group in selectedGroupsLabels"
							:key="group.id"
							class="label label-default tw-mb-2 tw-mr-2 !tw-flex tw-items-center"
						>
							<span class="material-symbols-outlined tw-cursor-pointer" @click="removeGroup(group.id)">close</span>
							<span class="tw-ml-2">{{ group.label }}</span>
						</div>
					</div>
					<advanced-select
						:filters="groupsOptions"
						:close-on-choose="false"
						:position-absolute="true"
						:max-height="150"
						@filter-selected="onSelectGroup"
					></advanced-select>
				</div>
			</section>
			<button @click="displaySharings = true" class="tw-underline tw-underline-offset-1">
				{{ translate('MOD_EMUNDUS_FILTERS_DISPLAY_ALREADY_SHARED_TO') }}
			</button>
			<section id="share-actions" class="tw-flex tw-justify-end">
				<button class="tw-btn-secondary tw-mr-2" @click="$emit('close')">
					{{ translate('MOD_EMUNDUS_FILTERS_CANCEL') }}
				</button>
				<button class="btn btn-primary not-to-close-modal" @click="shareFilter">
					{{ translate('MOD_EMUNDUS_FILTERS_SHARE_BUTTON') }}
				</button>
			</section>
		</div>
		<div v-else>
			<h2>{{ translate('MOD_EMUNDUS_FILTERS_SHARINGS_FOR') + ' ' + filter.name }}</h2>
			<section id="already-shared-with" class="tw-mb-4 tw-mt-4">
				<p>{{ translate('MOD_EMUNDUS_FILTERS_ALREADY_SHARED_WITH') }}</p>
				<div v-if="alreadySharedTo.users.length > 0" class="tw-mb-2 tw-mt-2">
					<label>{{ translate('MOD_EMUNDUS_FILTERS_ALREADY_SHARED_WITH_USERS') }} : </label>
					<div id="already-shared-users" class="selected-values-to-share tw-mb-2 tw-mt-2 tw-flex">
						<div
							v-for="user in alreadySharedTo.users"
							:key="user.id"
							class="label label-default tw-mb-2 tw-mr-2 !tw-flex tw-items-center"
						>
							<span class="material-symbols-outlined tw-cursor-pointer" @click="deleteUserSharing(user.id)">close</span>
							<span class="tw-ml-2">{{ user.label }}</span>
						</div>
					</div>
				</div>

				<div v-if="alreadySharedTo.groups.length > 0" class="em-mt-8 em-mb-8">
					<label>{{ translate('MOD_EMUNDUS_FILTERS_ALREADY_SHARED_WITH_GROUPS') }} : </label>
					<div id="already-shared-groups" class="tw-mb-2 tw-mt-2 tw-flex">
						<div
							v-for="group in alreadySharedTo.groups"
							:key="group.id"
							class="label label-default tw-mb-2 tw-mr-2 !tw-flex tw-items-center"
						>
							<span class="material-symbols-outlined tw-cursor-pointer" @click="deleteGroupSharing(group.id, 'group')"
								>close</span
							>
							<span class="tw-ml-2">{{ group.label }}</span>
						</div>
					</div>
				</div>
			</section>
			<div class="tw-flex tw-items-end tw-justify-end">
				<button class="tw-btn-primary tw-w-fit" @click="displaySharings = false">{{ translate('OK') }}</button>
			</div>
		</div>
	</div>
</template>

<style scoped>
.selected-values-to-share {
	flex-wrap: wrap;
	max-height: 100px;
	overflow-y: auto;
}
</style>
