<template>
	<div id="application-choices-list">
		<list
			:default-lists="configString"
			:default-type="'applicationchoices'"
			:key="renderingKey"
			:display-presaved-filters="true"
			:crud="crud"
		></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';
import { useCampaignStore } from '@/stores/campaign.js';
import campaignService from '@/services/campaign.js';
import settingsService from '@/services/settings.js';

export default {
	name: 'ApplicationChoicesList',
	props: {
		crud: {
			type: Object,
			default: [],
		},
	},
	components: {
		list,
	},
	data() {
		return {
			renderingKey: 1,

			config: {
				applicationchoices: {
					title: 'COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICES',
					tabs: [
						{
							title: 'COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICES',
							key: 'applicationchoices',
							controller: 'application',
							getter: 'getallapplicationchoices',
							noData: 'COM_EMUNDUS_ONBOARD_NOAPPLICATIONCHOICES',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							actions: [
								{
									action: 'updatestate',
									label: 'COM_EMUNDUS_APPLICATION_CHOICES_UPDATE_STATUS_TITLE',
									type: 'modal',
									component: 'UpdateApplicationChoiceState',
									name: 'updatestate',
									multiple: true,
									acl: 'application_choices|u',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_APPLICATION_CHOICES_FILTER_CAMPAIGNS',
									allLabel: '',
									alwaysDisplay: true,
									getter: 'getallcampaignsforfilter',
									controller: 'application',
									key: 'campaign',
									values: null,
									multiple: true,
									multiselect: true,
									default: '',
								},
								{
									label: 'COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_STATUS_FILTER',
									allLabel: '',
									alwaysDisplay: true,
									getter: 'getchoicesstatesforfilter',
									controller: 'application',
									key: 'state',
									values: null,
									multiple: true,
									multiselect: true,
									default: '',
								},
								{
									label: 'COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO_LABEL',
									allLabel: 'COM_EMUNDUS_APPLICATION_CHOICES_ALL_CAMPAIGNS',
									alwaysDisplay: true,
									getter: '',
									controller: 'campaigns',
									key: 'index',
									values: [
										{ value: 1, label: this.translate('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO_1') },
										{ value: 2, label: this.translate('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO_2') },
										{ value: 3, label: this.translate('COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_NO_3') },
									],
									multiple: true,
									multiselect: true,
									default: '',
								},
								{
									label: 'COM_EMUNDUS_APPLICATION_CHOICES_APPLICATION_CHOICE_FILE_STATUS_FILTER',
									allLabel: '',
									alwaysDisplay: true,
									getter: 'getapplicationstatusforfilter',
									controller: 'application',
									key: 'file_status',
									values: null,
									multiple: true,
									multiselect: true,
									default: '',
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
