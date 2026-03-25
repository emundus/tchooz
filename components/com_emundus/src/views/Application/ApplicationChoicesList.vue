<template>
	<div id="application-choices-list">
		<list
			v-if="!loading"
			:default-lists="config"
			:encoded="false"
			:default-type="'applicationchoices'"
			:key="renderingKey"
			:display-presaved-filters="true"
			:crud="crud"
		></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';
import applicationService from '@/services/application.js';

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
			loading: true,
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
							exports: [
								{
									action: 'exportapplicationchoices',
									label: 'COM_EMUNDUS_APPLICATION_CHOICES_EXPORT_EXCEL',
									controller: 'application',
									name: 'exportapplicationchoices',
									method: 'get',
									multiple: true,
									exportModal: false,
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
	created() {
		applicationService.getApplicationChoicesMoreFilters().then((response) => {
			if (response.status && response.data.length > 0) {
				// Merge this.config.applicationchoices.tabs[0].filters with the response.data
				for (const filter of response.data) {
					const existingFilterIndex = this.config.applicationchoices.tabs[0].filters.findIndex(
						(f) => f.key === filter.key,
					);
					if (existingFilterIndex !== -1) {
						// If the filter already exists, update its values
						this.config.applicationchoices.tabs[0].filters[existingFilterIndex].values = filter.values;
					} else {
						// If the filter does not exist, add it to the filters array
						this.config.applicationchoices.tabs[0].filters.push(filter);
					}
				}

				this.loading = false;
			}
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
