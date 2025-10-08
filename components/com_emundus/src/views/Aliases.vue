<template>
	<div id="aliases-list">
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
			:default-type="'aliases'"
			:key="renderingKey"
			:can-check="false"
		></list>
	</div>
</template>

<script>
import list from '@/views/List.vue';

export default {
	name: 'Aliases',
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
				aliases: {
					title: 'COM_EMUNDUS_ONBOARD_ALIAS_LIST',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_ALIAS_LIST',
							key: 'aliases',
							controller: 'settings',
							getter: 'fetchaliases',
							noData: 'COM_EMUNDUS_ONBOARD_NOALIAS',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_ALIASES_FILTER_PROFILES',
									allLabel: 'COM_EMUNDUS_ALIAS_PROFILE_ALL',
									alwaysDisplay: true,
									getter: 'getaliasprofiles',
									controller: 'settings',
									key: 'profile',
									values: null,
								},
							],
							actions: [
								{
									field: 'name',
									format: '${%value%}',
									label: 'COM_EMUNDUS_ONBOARD_COPY_ALIAS',
									name: 'copy_clipboard',
									type: 'copy_clipboard',
									successMessage: 'COM_EMUNDUS_ALIAS_COPIED',
								},
							],
							exports: [
								{
									action: 'exportcsvaliases',
									label: 'COM_EMUNDUS_ONBOARD_ALIASES_EXPORTS_EXCEL',
									controller: 'settings',
									name: 'exportcsvaliases',
									method: 'get',
									multiple: true,
									exportModal: false,
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
