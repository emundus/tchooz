<template>
	<div id="imports-list">
		<list :default-lists="configString" :default-type="'imports'" :can-check="false"></list>
	</div>
</template>

<script>
import list from '@/views/List.vue';

export default {
	name: 'ImportsList',
	components: { list },
	data() {
		return {
			config: {
				imports: {
					title: 'COM_EMUNDUS_ONBOARD_IMPORTS_LIST',
					intro: 'COM_EMUNDUS_ONBOARD_IMPORTS_LIST_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_IMPORTS_LIST',
							key: 'imports',
							controller: 'import',
							getter: 'getimports',
							noData: 'COM_EMUNDUS_ONBOARD_NOIMPORTS',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							filters: [
								{
									label: 'COM_EMUNDUS_IMPORTS_STATUS',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									alwaysDisplay: true,
									getter: '',
									controller: 'import',
									key: 'status',
									values: [
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
											value: 'all',
										},
										{
											label: 'COM_EMUNDUS_IMPORTS_STATUS_PROCESSING',
											value: 'processing',
										},
										{
											label: 'COM_EMUNDUS_IMPORTS_STATUS_COMPLETED',
											value: 'completed',
										},
										{
											label: 'COM_EMUNDUS_IMPORTS_STATUS_FAILED',
											value: 'failed',
										},
										{
											label: 'COM_EMUNDUS_IMPORTS_STATUS_CANCELLED',
											value: 'cancelled',
										},
									],
									default: 'all',
								},
							],
							actions: [
								{
									label: 'COM_EMUNDUS_IMPORTS_SHOW_REPORT',
									type: 'modal',
									component: 'ImportReport',
									name: 'report',
									multiple: false,
									showon: { key: 'status', operator: '!=', value: 'processing' },
								},
								{
									action: 'cancelimport',
									label: 'COM_EMUNDUS_IMPORT_CANCEL',
									controller: 'import',
									name: 'cancel',
									multiple: false,
									method: 'post',
									confirm: 'COM_EMUNDUS_IMPORTS_CANCEL_CONFIRM',
									showon: { key: 'status', operator: '==', value: 'processing' },
								},
							],
						},
					],
				},
			},
		};
	},
	computed: {
		configString() {
			return btoa(JSON.stringify(this.config));
		},
	},
};
</script>

<style scoped></style>
