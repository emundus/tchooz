<template>
	<div id="registrants-list">
		<list :default-lists="configString" :default-type="'registrants'"></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Registrants',
	components: {
		list,
	},
	data() {
		return {
			config: {
				registrants: {
					title: 'COM_EMUNDUS_ONBOARD_REGISTRANTS',
					intro: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_REGISTRANTS',
							key: 'registrants',
							controller: 'events',
							getter: 'getregistrants',
							viewsOptions: [
								{ value: 'table', icon: 'dehaze' },
								{ value: 'calendar', icon: 'calendar_today' },
							],
							noData: 'COM_EMUNDUS_ONBOARD_NO_REGISTRANTS',
							displaySearch: false,
							actions: [
								{
									action: 'addregistrant',
									label: 'COM_EMUNDUS_ONBOARD_REGISTRANT_ADD',
									component: 'EditSlot',
									name: 'add',
									type: 'modal',
								},
								{
									action: 'index.php?option=com_emundus&view=events&layout=add&event=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'events',
									type: 'redirect',
									name: 'edit',
									view: 'calendar',
									calendarView: 'week',
								},
								{
									action: 'editslot',
									type: 'modal',
									component: 'EditSlot',
									name: 'editslot',
									multiple: false,
									icon: 'edit',
									acl: 'booking|u',
								},
								{
									action: 'deletebooking',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_DELETE',
									controller: 'events',
									name: 'delete',
									method: 'delete',
									multiple: true,
									confirm: 'COM_EMUNDUS_ONBOARD_REGISTRANT_DELETE_CONFIRM',
									icon: 'delete_outline',
									iconOutlined: true,
									buttonClasses:
										'tw-group tw-bg-red-500 tw-border-red-500 tw-text-white hover:tw-bg-white hover:tw-border-red-500',
									spanClasses: 'group-hover:tw-text-red-500',
									acl: 'booking|d',
								},
								{
									action: 'resend',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_REGISTRANTS_RESEND',
									controller: 'events',
									name: 'resend',
									method: 'post',
									multiple: true,
									confirm: 'COM_EMUNDUS_ONBOARD_REGISTRANT_RESEND_CONFIRM',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_LABEL',
									allLabel: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_EVENT_ALL',
									getter: 'getfilterevents',
									controller: 'events',
									key: 'event',
									values: null,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_LABEL',
									allLabel: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_LOCATION_ALL',
									getter: 'getlocations',
									controller: 'events',
									key: 'location',
									values: null,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_APPLICANT_LABEL',
									allLabel: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_APPLICANT_ALL',
									getter: 'getfilterapplicants',
									controller: 'events',
									key: 'applicant',
									values: null,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ASSOC_USER_LABEL',
									allLabel: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_ASSOC_USER_ALL',
									getter: 'getfilterassocusers',
									controller: 'events',
									key: 'assoc_user',
									values: null,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_FILTER_DAY_LABEL',
									type: 'date',
									key: 'day',
								},
							],
							exports: [
								{
									action: 'exportpdf',
									label: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_EXPORTS_EMARGEMENT',
									controller: 'events',
									name: 'exportpdf',
									method: 'get',
									multiple: true,
								},
								{
									action: 'exportexcel',
									label: 'COM_EMUNDUS_ONBOARD_REGISTRANTS_EXPORTS_EXCEL',
									controller: 'events',
									name: 'exportcsv',
									method: 'get',
									multiple: true,
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
