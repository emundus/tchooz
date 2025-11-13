<template>
	<div id="campaigns-list">
		<list v-if="!loading" :default-lists="configString" :default-type="'campaigns'" :key="renderingKey"></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';
import { useCampaignStore } from '@/stores/campaign.js';
import campaignService from '@/services/campaign.js';
import settingsService from '@/services/settings.js';

export default {
	name: 'Campaigns',
	components: {
		list,
	},
	data() {
		return {
			campaignActivated: null,
			renderingKey: 1,

			loading: true,

			config: {
				campaigns: {
					title: 'COM_EMUNDUS_ONBOARD_CAMPAIGNS',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_CAMPAIGNS',
							key: 'campaign',
							controller: 'campaign',
							getter: 'getallcampaign',
							noData: 'COM_EMUNDUS_ONBOARD_NOCAMPAIGN',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=campaigns&layout=add',
									label: 'COM_EMUNDUS_ONBOARD_ADD_CAMPAIGN',
									controller: 'campaign',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'duplicatecampaign',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE',
									controller: 'campaign',
									name: 'duplicate',
									method: 'post',
								},
								{
									action: 'index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'campaign',
									type: 'redirect',
									name: 'edit',
								},
								{
									action: 'deletecampaign',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'campaign',
									name: 'delete',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_CAMPDELETE',
									showon: {
										key: 'nb_files',
										operator: '<',
										value: '1',
									},
								},
								{
									action: 'unpublishcampaign',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH',
									controller: 'campaign',
									name: 'unpublish',
									multiple: true,
									method: 'post',
									showon: {
										key: 'published',
										operator: '=',
										value: '1',
									},
								},
								{
									action: 'publishcampaign',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_PUBLISH',
									controller: 'campaign',
									name: 'publish',
									multiple: true,
									method: 'post',
									showon: {
										key: 'published',
										operator: '=',
										value: '0',
									},
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_FILTER_PUBLISH',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									alwaysDisplay: true,
									getter: '',
									controller: 'campaigns',
									key: 'filter',
									values: [
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
											value: 'all',
										},
										{
											label: 'COM_EMUNDUS_CAMPAIGN_YET_TO_COME',
											value: 'yettocome',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_OPEN',
											value: 'ongoing',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_CLOSE',
											value: 'Terminated',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_PUBLISH',
											value: 'Publish',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH',
											value: 'Unpublish',
										},
									],
									default: 'Publish',
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_FILTER_PROGRAMS',
									allLabel: 'COM_EMUNDUS_ONBOARD_ALL_PROGRAMS',
									alwaysDisplay: true,
									getter: 'getallprogramforfilter',
									controller: 'programme',
									key: 'program',
									values: null,
								},
							],
						},
						{
							title: 'COM_EMUNDUS_ONBOARD_PROGRAMS',
							key: 'programs',
							controller: 'programme',
							getter: 'getallprogram',
							noData: 'COM_EMUNDUS_ONBOARD_NOPROGRAM',
							actions: [
								{
									action: 'index.php?option=com_fabrik&view=form&formid=108',
									controller: 'programme',
									label: 'COM_EMUNDUS_ONBOARD_ADD_PROGRAM',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'index.php?option=com_emundus&view=programme&layout=edit&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'programme',
									type: 'redirect',
									name: 'edit',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_ALL_PROGRAM_CATEGORIES_LABEL',
									allLabel: 'COM_EMUNDUS_ONBOARD_ALL_PROGRAM_CATEGORIES',
									getter: 'getprogramcategories',
									controller: 'programme',
									key: 'category',
									alwaysDisplay: true,
									values: null,
								},
							],
						},
					],
				},
			},

			importAction: {
				action: 'importfiles',
				label: 'COM_EMUNDUS_ONBOARD_ACTION_IMPORT_FILES',
				type: 'modal',
				component: 'Import',
				name: 'import',
				multiple: false,
			},
		};
	},
	created() {
		if (useCampaignStore().getActivated === null) {
			campaignService.isImportActivated().then((response) => {
				this.campaignActivated = response.data;
				if (this.campaignActivated) {
					useCampaignStore().updateActivated(true);
					this.config.campaigns.tabs[0].actions.push(this.importAction);
				} else {
					useCampaignStore().updateActivated(false);
				}
			});
		} else {
			this.campaignActivated = useCampaignStore().getActivated;
		}

		settingsService.checkAddonStatus('choices').then((response) => {
			if (response.data.enabled) {
				// Add a filter on parent campaign
				this.config.campaigns.tabs[0].filters.push({
					label: 'COM_EMUNDUS_ONBOARD_CAMPAIGNS_FILTER_PARENT',
					allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
					alwaysDisplay: true,
					getter: 'getparentcampaignsforfilter',
					controller: 'campaign',
					key: 'parent_campaign',
					values: null,
				});
			}

			this.loading = false;
		});
	},
	computed: {
		configString() {
			return btoa(JSON.stringify(this.config));
		},
	},
};
</script>

<style scoped></style>
