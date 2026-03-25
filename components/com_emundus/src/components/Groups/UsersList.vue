<script>
import Chip from '@/components/Atoms/Chip.vue';
import groupsService from '@/services/groups.js';
import Loader from '@/components/Atoms/Loader.vue';
import Button from '@/components/Atoms/Button.vue';

export default {
	name: 'UsersList',
	components: { Button, Loader, Chip },
	props: {
		group: Object,
	},
	data: function () {
		return {
			users: [],

			loading: false,
			searchThroughActions: '',
			associateUser: false,
		};
	},
	created() {
		if (!this.group.users) {
			this.loading = true;
			groupsService.getUsersGroup(this.group.id).then((response) => {
				if (response.status) {
					this.users = response.data;
					this.loading = false;
				}
			});
		}
	},
	methods: {
		fullName(user) {
			return user.lastname.toUpperCase() + ' ' + user.firstname;
		},

		normalizedProfilePicture(profilePicture) {
			if (!profilePicture) return null;

			if (profilePicture.startsWith('https')) {
				return profilePicture;
			}
			const base = window.location.origin + '/';
			return base + profilePicture.replace(/^\//, '');
		},
	},
	computed: {
		displayedUsers() {
			if (!this.searchThroughActions) {
				return this.users;
			}
			const searchTerm = this.searchThroughActions.toLowerCase();
			return this.users.filter((user) => {
				const fullName = this.fullName(user).toLowerCase();
				return fullName.includes(searchTerm);
			});
		},
	},
};
</script>

<template>
	<div class="tw-mt-6">
		<div v-if="!loading">
			<div v-if="associateUser">
				<Button>
					{{ translate('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE_ADD_USER') }}
				</Button>
			</div>

			<input
				type="text"
				v-model="searchThroughActions"
				:placeholder="translate('COM_EMUNDUS_USERS_SEARCH_PLACEHOLDER')"
				class="tw-w-full tw-rounded tw-border tw-border-neutral-300 tw-p-2"
			/>

			<template v-if="displayedUsers.length === 0">
				<p class="tw-mt-4 tw-text-center tw-text-sm tw-text-neutral-500">
					{{ translate('COM_EMUNDUS_GROUPS_USERS_ASSOCIATE_NO_USERS') }}
				</p>
			</template>

			<div class="tw-mt-4 tw-grid tw-grid-cols-3 tw-gap-2">
				<Chip
					v-for="user in displayedUsers"
					:key="user.id"
					:text="fullName(user)"
					:image="normalizedProfilePicture(user.profile_picture)"
					:image-alt-text="fullName(user)"
				/>
			</div>
		</div>
		<Loader v-else />
	</div>
</template>

<style scoped></style>
