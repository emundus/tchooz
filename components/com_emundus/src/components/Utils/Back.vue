<script>
import settingsService from '@/services/settings.js';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'Back',
	props: {
		link: {
			type: String,
			default: 'index.php?option=com_emundus&view=emails',
		},
	},
	methods: {
		redirectJRoute() {
			settingsService.redirectJRoute(this.link, useGlobalStore().getCurrentLang);
		},
	},
};
</script>

<template>
	<div
		class="tw-group tw-flex tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular"
		@click="redirectJRoute"
	>
		<span class="material-symbols-outlined tw-mr-1">navigate_before</span>
		<span class="group-hover:tw-underline">{{ translate('BACK') }}</span>
	</div>
</template>
