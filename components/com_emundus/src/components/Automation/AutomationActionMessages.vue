<script>
import { Tag } from '@emundus/ui';

export default {
	name: 'AutomationActionMessages',
	components: { Tag },
	props: {
		messages: {
			type: Array,
			default: () => [],
		},
	},
	methods: {
		tagVariant(type) {
			switch (type) {
				case 'error':
					return 'danger';
				case 'warning':
					return 'warning';
				default:
					return 'info';
			}
		},
		typeLabel(type) {
			switch (type) {
				case 'error':
					return this.translate('COM_EMUNDUS_AUTOMATION_ACTION_MESSAGE_TYPE_ERROR');
				case 'warning':
					return this.translate('COM_EMUNDUS_AUTOMATION_ACTION_MESSAGE_TYPE_WARNING');
				default:
					return this.translate('COM_EMUNDUS_AUTOMATION_ACTION_MESSAGE_TYPE_INFO');
			}
		},
	},
};
</script>

<template>
	<div v-if="messages.length > 0" class="tw-flex tw-flex-col tw-gap-2">
		<div v-for="(message, index) in messages" :key="index" class="tw-flex tw-items-center tw-gap-3">
			<Tag :variant="tagVariant(message.type)" :label="typeLabel(message.type)" />
			<div>{{ message.message }}</div>
		</div>
	</div>
	<span v-else>{{ translate('COM_EMUNDUS_NO_EXECUTION_MESSAGES') }}</span>
</template>

<style scoped></style>
