<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import SiteSettings from '@/components/Settings/SiteSettings.vue';

export default {
	name: 'ApplicationFileActionsSettings',
	components: {
		SiteSettings,
		ParameterForm,
	},
	data() {
		return {
			groups: [],
			fields: null,
		};
	},
	created() {},
	methods: {},
};
</script>

<template>
	<div>
		<SiteSettings :json_source="'settings/sections/file-actions-settings.js'" />
	</div>
</template>

<style scoped></style>
