<script>
import { Button, Tab, TabList } from '@emundus/ui';

import ModalHeader from '@/components/Utils/Modal/Header.vue';
import AccessRolesUsersTab from '@/components/Resource/tabs/AccessRolesUsersTab.vue';

import alerts from '@/mixins/alerts.js';

/**
 * "Access & sharing" modal for a resource document. Hosts the tabs
 * (Roles & users / Display spaces / Share link) and a single "Enregistrer"
 * button that persists every editable tab at once.
 *
 * The "Display spaces" tab is intentionally disabled for now.
 */
export default {
	name: 'ResourceShare',
	components: { ModalHeader, Button, Tab, TabList, AccessRolesUsersTab },
	mixins: [alerts],
	props: {
		item: {
			type: Object,
			required: true,
		},
	},
	emits: ['close', 'update-items'],
	data() {
		return {
			activeTab: 'roles',
			isSaving: false,
			isDirty: false,
		};
	},
	computed: {
		// List rows expose a prefixed reference ("file-42" / "folder-5"); the backend resolves the
		// type from it, so file and folder shares go through the same access endpoints.
		resourceRef() {
			return String(this.item.id);
		},
	},
	methods: {
		async onSave() {
			if (this.isSaving) {
				return;
			}

			this.isSaving = true;
			try {
				await this.$refs.rolesTab.save();

				this.$emit('update-items');
				this.$emit('close');

				await this.alertSuccess(this.translate('COM_EMUNDUS_RESOURCE_ACCESS_SAVED'));
			} catch (e) {
				await this.alertError(this.translate('COM_EMUNDUS_RESOURCE_ACCESS_SAVE_ERROR'), e.message);
			} finally {
				this.isSaving = false;
			}
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-flex-col tw-gap-6 tw-p-2">
		<ModalHeader @close="$emit('close')">
			<template #leading><span></span></template>
			<h2 class="tw-text-center">{{ translate('COM_EMUNDUS_RESOURCE_ACCESS_TITLE') }}</h2>
		</ModalHeader>

		<TabList v-model="activeTab" :aria-label="translate('COM_EMUNDUS_RESOURCE_ACCESS_TITLE')">
			<Tab value="roles" :label="translate('COM_EMUNDUS_RESOURCE_ACCESS_TAB_ROLES')" />
		</TabList>

		<div class="tw-min-h-[320px]">
			<AccessRolesUsersTab
				v-show="activeTab === 'roles'"
				ref="rolesTab"
				:resource-ref="resourceRef"
				@dirty-change="isDirty = $event"
			/>
		</div>

		<div class="tw-flex tw-justify-center">
			<Button
				variant="primary"
				:label="translate('COM_EMUNDUS_RESOURCE_SHARE_SAVE')"
				:loading="isSaving"
				:disabled="!isDirty || isSaving"
				@click="onSave"
			/>
		</div>
	</div>
</template>

<style scoped></style>
