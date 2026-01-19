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
					title: 'COM_EMUNDUS_ONBOARD_ALIAS_TAGS_LIST',
					intro: 'COM_EMUNDUS_ONBOARD_ALIAS_TAGS_LIST_INTRO',
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
									name: 'edit',
									type: 'copy_clipboard',
									successMessage: 'COM_EMUNDUS_ALIAS_COPIED',
									icon: 'content_copy',
								},
								{
									action: 'deletealias',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'settings',
									name: 'delete',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_ALIAS_DELETE',
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
						{
							title: 'COM_EMUNDUS_ONBOARD_FORM_TAGS_LIST',
							key: 'tags',
							controller: 'settings',
							getter: 'fetchtags',
							noData: 'COM_EMUNDUS_ONBOARD_NOTAGS',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_CAMPAIGN',
									allLabel: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_CAMPAIGN_ALL',
									alwaysDisplay: true,
									getter: 'getcampaignsfilter',
									controller: 'settings',
									key: 'campaign',
									values: null,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE',
									allLabel: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE_ALL',
									alwaysDisplay: true,
									key: 'formtype',
									values: [
										{
											value: 'all',
											label: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE_ALL',
										},
										{
											value: 'applicant',
											label: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE_APPLICANT',
										},
										{
											value: 'management',
											label: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_FORM_TYPE_MANAGEMENT',
										},
									],
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_STEPS',
									allLabel: 'COM_EMUNDUS_ONBOARD_TAGS_FILTER_STEPS_ALL',
									alwaysDisplay: true,
									getter: 'getstepsfilter',
									controller: 'settings',
									key: 'step',
									values: null,
								},
							],
							actions: [
								{
									field: 'id',
									format: '${%value%}',
									label: 'COM_EMUNDUS_ONBOARD_COPY_TAG',
									name: 'edit',
									type: 'copy_clipboard',
									successMessage: 'COM_EMUNDUS_TAG_COPIED',
									icon: 'content_copy',
								},
							],
							exports: [],
						},
						{
							title: 'COM_EMUNDUS_ONBOARD_GENERAL_TAGS_LIST',
							key: 'other_tags',
							controller: 'settings',
							getter: 'fetchgeneraltags',
							noData: 'COM_EMUNDUS_ONBOARD_NOTAGS',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							filters: [],
							actions: [
								{
									field: 'tag',
									format: '[%value%]',
									label: 'COM_EMUNDUS_ONBOARD_COPY_TAG',
									name: 'edit',
									type: 'copy_clipboard',
									successMessage: 'COM_EMUNDUS_TAG_COPIED',
									icon: 'content_copy',
								},
							],
							exports: [],
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
