<script>
import { Avatar, Button, Icon, Chip } from '@emundus/ui';

/**
 * A single access row inside the "Access & sharing" modal: shows the target
 * (role/group label, or an avatar chip for a user), a permission select and a
 * delete button. Fully controlled — it owns no state, it only emits changes.
 */
export default {
	name: 'AccessRow',
	components: { Avatar, Button, Icon, Chip },
	props: {
		/** Access entry: { type, targetId, permission, targetLabel }. */
		entry: {
			type: Object,
			required: true,
		},
		/** Permission options: [{ value, label }]. */
		permissionOptions: {
			type: Array,
			default: () => [],
		},
	},
	emits: ['update:permission', 'remove'],
	computed: {
		isUser() {
			return this.entry.type === 'user';
		},
		// Clean name only — drives the avatar initials (never mixed with the email).
		name() {
			return this.entry.targetLabel || '';
		},
		// Chip text: the name, followed by the email when the target is a user that has one.
		label() {
			return this.entry.targetEmail ? `${this.name} (${this.entry.targetEmail})` : this.name;
		},
	},
	methods: {
		onPermissionChange(event) {
			this.$emit('update:permission', event.target.value);
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-w-full tw-items-center tw-gap-2">
		<div class="tw-flex tw-min-w-0 tw-flex-1 tw-items-center">
			<Chip v-if="isUser" :label="label">
				<template #before>
					<Avatar :name="name" size="sm" />
				</template>
			</Chip>
			<span v-else class="tw-truncate tw-text-base tw-font-medium tw-text-black">{{ label }}</span>
		</div>

		<div class="tw-relative tw-shrink-0">
			<select :value="entry.permission" @change="onPermissionChange">
				<option v-for="option in permissionOptions" :key="option.value" :value="option.value">
					{{ option.label }}
				</option>
			</select>
		</div>

		<Button variant="danger" :title="translate('COM_EMUNDUS_DELETE')" @click="$emit('remove')">
			<template #leading>
				<Icon name="delete" />
			</template>
		</Button>
	</div>
</template>

<style scoped></style>
