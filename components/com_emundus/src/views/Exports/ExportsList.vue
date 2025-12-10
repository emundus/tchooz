<template>
	<div id="exports-list">
		<div v-if="isModal" class="tw-sticky tw-top-0 tw-z-10 tw-bg-white tw-p-4">
			<div class="tw-mb-4 tw-flex tw-items-center tw-justify-center">
				<button class="tw-absolute tw-right-2 tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
					<span class="material-symbols-outlined">close</span>
				</button>
			</div>
		</div>
		<list
			:class="{ 'tw-p-8': isModal }"
			:default-lists="configString"
			:default-type="'exports'"
			:key="renderingKey"
			:can-check="false"
		></list>
	</div>
</template>

<script>
import list from '@/views/List.vue';

export default {
	name: 'ExportsList',
	components: { list },
	props: {
		isModal: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			renderingKey: 1,

			config: {
				exports: {
					title: 'COM_EMUNDUS_ONBOARD_EXPORTS_LIST',
					intro: 'COM_EMUNDUS_ONBOARD_EXPORTS_LIST_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_EXPORTS_LIST',
							key: 'exports',
							controller: 'export',
							getter: 'getexports',
							noData: 'COM_EMUNDUS_ONBOARD_NOEXPORTS',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							filters: [
								{
									label: 'COM_EMUNDUS_EXPORTS_STATUS',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									alwaysDisplay: true,
									getter: '',
									controller: 'export',
									key: 'status',
									values: [
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
											value: 'all',
										},
										{
											label: 'COM_EMUNDUS_EXPORTS_STATUS_IN_PROGRESS',
											value: 'in_progress',
										},
										{
											label: 'COM_EMUNDUS_EXPORTS_STATUS_COMPLETED',
											value: 'completed',
										},
									],
									default: 'all',
								},
							],
							actions: [
								{
									action: 'downloadexport',
									label: 'COM_EMUNDUS_ONBOARD_EXPORTS_DOWNLOAD',
									controller: 'export',
									name: 'download',
									multiple: false,
									method: 'get',
								},
								{
									action: 'delete',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'export',
									name: 'delete',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_EXPORT_DELETE',
								},
							],
						},
					],
				},
			},
		};
	},
	methods: {},
	computed: {
		configString() {
			return btoa(JSON.stringify(this.config));
		},
	},
};
</script>

<style scoped></style>
