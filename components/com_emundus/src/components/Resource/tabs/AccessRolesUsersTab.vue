<script>
import AccessSection from '@/components/Resource/AccessSection.vue';

import resourceService from '@/services/resource.js';
import userService from '@/services/user.js';
import groupsService from '@/services/groups.js';

import alerts from '@/mixins/alerts.js';

const PERMISSION_KEYS = [
	{ value: 'view', label: 'COM_EMUNDUS_RESOURCE_PERMISSION_VIEW' },
	{ value: 'edit', label: 'COM_EMUNDUS_RESOURCE_PERMISSION_EDIT' },
	//{ value: 'manage', label: 'COM_EMUNDUS_RESOURCE_PERMISSION_MANAGE' },
];

// Users can number in the tens of thousands, so the user list is loaded remotely:
// first page on open, then debounced server-side search as the coordinator types.
const USER_SEARCH_DEBOUNCE_MS = 300;
const USER_PAGE_SIZE = 20;

/**
 * "Roles & users" tab of the "Access & sharing" modal. Owns the resource access
 * list (roles/groups/users) and the selectable targets. Exposes save() so the
 * parent modal's single "Enregistrer" button can persist it.
 */
export default {
	name: 'AccessRolesUsersTab',
	components: { AccessSection },
	mixins: [alerts],
	props: {
		// Prefixed resource reference ("file-42" / "folder-5"); passed as-is to the access
		// endpoints, which resolve file vs folder from it.
		resourceRef: {
			type: String,
			required: true,
		},
	},
	emits: ['dirty-change'],
	data() {
		return {
			access: [],
			// Serialized snapshot of `access` as loaded; drives isDirty so the parent can
			// keep the save button disabled until something actually changes.
			initialSnapshot: '',
			//roleOptions: [],
			//groupOptions: [],
			userOptions: [],
			userLoading: false,
			userSearchTimer: null,
			isLoading: true,
		};
	},
	watch: {
		isDirty(dirty) {
			this.$emit('dirty-change', dirty);
		},
	},
	computed: {
		// Order-independent comparison: shares are the same set regardless of add order.
		isDirty() {
			return this.serializeAccess(this.access) !== this.initialSnapshot;
		},
		permissionOptions() {
			return PERMISSION_KEYS.map((permission) => ({
				value: permission.value,
				label: this.translate(permission.label),
			}));
		},
		/*roleEntries() {
			return this.access.filter((entry) => entry.type === 'role');
		},
		groupEntries() {
			return this.access.filter((entry) => entry.type === 'group');
		},*/
		userEntries() {
			return this.access.filter((entry) => entry.type === 'user');
		},
	},
	created() {
		this.load();
	},
	beforeUnmount() {
		if (this.userSearchTimer) {
			clearTimeout(this.userSearchTimer);
		}
	},
	methods: {
		async load() {
			this.isLoading = true;

			const [access, users] = await Promise.all([
				resourceService.getAccess(this.resourceRef),
				//userService.getProfiles(),
				//groupsService.getGroupsToShareTo(),
				groupsService.getUsersToShareTo('', USER_PAGE_SIZE, 0),
			]);

			this.access = access.map((entry) => ({
				type: entry.type,
				targetId: Number(entry.targetId),
				permission: entry.permission,
				targetLabel: entry.targetLabel,
				targetEmail: entry.targetEmail || '',
			}));

			// "Déposant" profiles (setup_profiles.published = 1) are applicant roles and must not be
			// offered as resource share targets.
			//const shareableRoles = (roles.data || []).filter((role) => Number(role.published) !== 1);

			//this.roleOptions = this.normalizeOptions(shareableRoles);
			//this.groupOptions = this.normalizeOptions(groups);
			this.userOptions = this.normalizeOptions(users);

			this.initialSnapshot = this.serializeAccess(this.access);
			this.isLoading = false;
		},
		// Stable string key for the access set, independent of entry order.
		serializeAccess(list) {
			return JSON.stringify(
				list
					.map((entry) => ({ type: entry.type, targetId: entry.targetId, permission: entry.permission }))
					.sort((a, b) => a.type.localeCompare(b.type) || a.targetId - b.targetId),
			);
		},
		// Debounced remote search of shareable users (name/email), triggered from the
		// users multiselect. An empty query reloads the default first page.
		onUserSearch(query) {
			if (this.userSearchTimer) {
				clearTimeout(this.userSearchTimer);
			}

			this.userSearchTimer = setTimeout(async () => {
				this.userLoading = true;
				try {
					const users = await groupsService.getUsersToShareTo(query || '', USER_PAGE_SIZE, 0);
					this.userOptions = this.normalizeOptions(users);
				} finally {
					this.userLoading = false;
				}
			}, USER_SEARCH_DEBOUNCE_MS);
		},
		// Accepts profiles (users controller) / groups / users which all expose { id, label|name }.
		// Keeps `name` and `email` apart from the dropdown `label` so the chip can show a clean
		// name (correct avatar initials) alongside the email.
		normalizeOptions(items) {
			if (!Array.isArray(items)) {
				return [];
			}

			return items
				.filter((item) => item && item.id)
				.map((item) => ({
					id: Number(item.id),
					label: item.label || item.name || String(item.id),
					name: item.name || item.label || String(item.id),
					email: item.email || '',
				}));
		},
		onAdd(type, { targetId, name, label, email }) {
			this.access = [
				...this.access,
				{ type, targetId, permission: 'view', targetLabel: name || label, targetEmail: email || '' },
			];
		},
		onRemove(target) {
			this.access = this.access.filter((entry) => !(entry.type === target.type && entry.targetId === target.targetId));
		},
		onUpdatePermission({ entry, permission }) {
			this.access = this.access.map((item) =>
				item.type === entry.type && item.targetId === entry.targetId ? { ...item, permission } : item,
			);
		},
		async save() {
			const payload = this.access.map((entry) => ({
				type: entry.type,
				target_id: entry.targetId,
				permission: entry.permission,
			}));

			const result = await resourceService.saveAccess(this.resourceRef, payload);
			if (!result.status) {
				throw new Error(result.msg || this.translate('COM_EMUNDUS_RESOURCE_ACCESS_SAVE_ERROR'));
			}

			// Persisted state is the new baseline: the form is no longer dirty.
			this.initialSnapshot = this.serializeAccess(this.access);
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-w-full tw-flex-col tw-gap-6">
		<!--		<AccessSection
			:title="translate('COM_EMUNDUS_RESOURCE_ACCESS_ROLES')"
			:add-label="translate('COM_EMUNDUS_RESOURCE_ACCESS_ADD_ROLE')"
			:entries="roleEntries"
			:options="roleOptions"
			:permission-options="permissionOptions"
			@add="onAdd('role', $event)"
			@remove="onRemove"
			@update-permission="onUpdatePermission"
		/>

		<AccessSection
			:title="translate('COM_EMUNDUS_RESOURCE_ACCESS_GROUPS')"
			:add-label="translate('COM_EMUNDUS_RESOURCE_ACCESS_ADD_GROUP')"
			:entries="groupEntries"
			:options="groupOptions"
			:permission-options="permissionOptions"
			@add="onAdd('group', $event)"
			@remove="onRemove"
			@update-permission="onUpdatePermission"
		/>-->

		<AccessSection
			:title="translate('COM_EMUNDUS_RESOURCE_ACCESS_USERS')"
			:add-label="translate('COM_EMUNDUS_RESOURCE_ACCESS_ADD_USER')"
			:entries="userEntries"
			:options="userOptions"
			:permission-options="permissionOptions"
			:async="true"
			:loading="userLoading"
			@add="onAdd('user', $event)"
			@remove="onRemove"
			@update-permission="onUpdatePermission"
			@search="onUserSearch"
		/>
	</div>
</template>

<style scoped></style>
