<script>
import { Button, Icon } from '@emundus/ui';
import Multiselect from 'vue-multiselect';
import AccessRow from '@/components/Resource/AccessRow.vue';

/**
 * One access section (Roles / Groups / Users) of the "Access & sharing" modal.
 * Lists the current entries of a single type and offers an inline add flow
 * (select a not-yet-added target + confirm). Fully controlled: it keeps only the
 * transient "adding" UI state and emits every mutation to its parent.
 */
export default {
	name: 'AccessSection',
	components: { AccessRow, Button, Icon, Multiselect },
	props: {
		title: {
			type: String,
			default: '',
		},
		addLabel: {
			type: String,
			default: '',
		},
		/** Access entries of this section's type: [{ type, targetId, permission, targetLabel }]. */
		entries: {
			type: Array,
			default: () => [],
		},
		/** Selectable targets of this type: [{ id, label }]. */
		options: {
			type: Array,
			default: () => [],
		},
		permissionOptions: {
			type: Array,
			default: () => [],
		},
		/** When true, filtering is delegated to the parent via the `search` event (remote/async). */
		async: {
			type: Boolean,
			default: false,
		},
		/** Loading state of the remote search (async mode only). */
		loading: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['add', 'remove', 'update-permission', 'search'],
	data() {
		return {
			isAdding: false,
		};
	},
	computed: {
		// Only targets that are not already part of the section.
		availableOptions() {
			const usedIds = this.entries.map((entry) => Number(entry.targetId));
			return this.options.filter((option) => !usedIds.includes(Number(option.id)));
		},
	},
	methods: {
		startAdding() {
			this.isAdding = true;
		},
		cancelAdding() {
			this.isAdding = false;
		},
		// Selecting a target adds it straight away — no separate confirm step.
		onSelect(target) {
			if (!target) {
				return;
			}

			this.$emit('add', {
				targetId: Number(target.id),
				label: target.label,
				name: target.name,
				email: target.email,
			});
			this.cancelAdding();
		},
		onSearchChange(query) {
			if (this.async) {
				this.$emit('search', query);
			}
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-w-full tw-flex-col tw-gap-3">
		<h3 class="tw-text-xl tw-font-light tw-text-black">{{ title }}</h3>

		<div v-if="entries.length" class="tw-flex tw-w-full tw-flex-col tw-gap-2">
			<template v-for="(entry, index) in entries" :key="entry.targetId">
				<div v-if="index > 0" class="tw-h-px tw-w-full tw-bg-neutral-100"></div>
				<AccessRow
					:entry="entry"
					:permission-options="permissionOptions"
					@update:permission="$emit('update-permission', { entry, permission: $event })"
					@remove="$emit('remove', entry)"
				/>
			</template>
		</div>

		<div class="tw-flex tw-w-full tw-flex-col tw-items-end tw-gap-2">
			<div v-if="isAdding" class="tw-flex tw-w-full tw-items-center tw-gap-2">
				<multiselect
					:model-value="null"
					class="tw-flex-1"
					label="label"
					track-by="id"
					:options="availableOptions"
					:multiple="false"
					:searchable="true"
					:internal-search="!async"
					:loading="loading"
					:close-on-select="true"
					:allow-empty="true"
					:placeholder="addLabel"
					:select-label="''"
					:selected-label="''"
					:deselect-label="''"
					@select="onSelect"
					@search-change="onSearchChange"
				>
					<template #noResult>{{ translate('COM_EMUNDUS_RESOURCE_ACCESS_NO_OPTION') }}</template>
					<template #noOptions>{{ translate('COM_EMUNDUS_RESOURCE_ACCESS_NO_OPTION') }}</template>
				</multiselect>
				<Button variant="danger" :title="translate('COM_EMUNDUS_CANCEL')" @click="cancelAdding">
					<template #leading>
						<Icon name="close" />
					</template>
				</Button>
			</div>

			<Button
				v-else
				variant="success"
				emphasis="lite"
				:label="addLabel"
				:disabled="!availableOptions.length"
				@click="startAdding"
			>
				<template #leading>
					<Icon name="add_circle" />
				</template>
			</Button>
		</div>
	</div>
</template>

<style scoped></style>
