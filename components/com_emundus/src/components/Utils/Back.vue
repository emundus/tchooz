<script>
import settingsService from '@/services/settings.js';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'Back',
	emits: ['click'],
	props: {
		link: {
			type: String,
			default: 'index.php?option=com_emundus&view=emails',
		},
	},
	methods: {
		handleClick() {
			if (this.link !== '') {
				this.redirectJRoute();
			} else {
				this.$emit('click');
			}
		},
		redirectJRoute() {
			settingsService.redirectJRoute(this.link, useGlobalStore().getCurrentLang);
		},
	},
};
</script>

<template>
	<div
		class="tw-group tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular"
		@click="handleClick"
	>
		<span class="material-symbols-outlined tw-mr-1">navigate_before</span>
		<span class="group-hover:tw-underline">{{ translate('BACK') }}</span>
	</div>
</template>
