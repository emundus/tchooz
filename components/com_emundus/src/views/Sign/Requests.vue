<template>
	<div id="requests-list">
		<list :default-lists="configString" :default-type="'requests'"></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Requests',
	components: {
		list,
	},
	data() {
		return {
			config: {
				requests: {
					title: 'COM_EMUNDUS_ONBOARD_REQUESTS',
					intro: 'COM_EMUNDUS_ONBOARD_REQUESTS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_REQUESTS',
							key: 'requests',
							controller: 'sign',
							getter: 'getrequests',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							noData: 'COM_EMUNDUS_ONBOARD_NO_REQUESTS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=sign&layout=add',
									label: 'COM_EMUNDUS_ONBOARD_ADD_REQUEST',
									controller: 'sign',
									name: 'add',
									type: 'redirect',
									acl: 'sign_request|c',
								},
								{
									action: 'downloadsigneddocument',
									controller: 'sign',
									name: 'download',
									multiple: false,
									icon: 'find_in_page',
									acl: 'sign_request|r',
									showon: {
										key: 'status',
										operator: '=',
										value: 'signed',
									},
									iconOutlined: true,
								},
								{
									action: 'sendreminder',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_SEND_REMINDER',
									controller: 'sign',
									name: 'sendreminder',
									confirm: 'COM_EMUNDUS_ONBOARD_REQUEST_SEND_REMINDER_CONFIRM',
									multiple: true,
									acl: 'sign_request|u',
									showon: {
										key: 'status',
										operator: '=',
										value: 'to_sign',
									},
								},
								{
									action: 'cancelrequest',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_CANCEL_REQUEST',
									controller: 'sign',
									name: 'delete',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_REQUEST_CANCEL_CONFIRM',
									input: 'textarea',
									inputLabel: 'COM_EMUNDUS_ONBOARD_REQUEST_CANCEL_REASON',
									acl: 'sign_request|d',
									spanClasses: 'group-hover:tw-text-red-500',
									showon: {
										key: 'status',
										operator: '=',
										value: 'to_sign',
									},
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_SIGN_FILTER_APPLICANTS_LABEL',
									allLabel: 'COM_EMUNDUS_ONBOARD_SIGN_FILTER_APPLICANTS_ALL',
									getter: 'getfilterapplicants',
									controller: 'sign',
									key: 'applicant',
									values: null,
									multiselect: true,
									alwaysDisplay: true,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_SIGN_FILTER_ATTACHMENTS_LABEL',
									allLabel: 'COM_EMUNDUS_ONBOARD_SIGN_FILTER_ATTACHMENTS_ALL',
									getter: 'getfilterattachments',
									controller: 'sign',
									key: 'attachment',
									values: null,
									alwaysDisplay: true,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_SIGN_FILTER_STATUS_LABEL',
									allLabel: 'COM_EMUNDUS_ONBOARD_SIGN_FILTER_STATUS_ALL',
									getter: 'getfilterstatus',
									controller: 'sign',
									key: 'status',
									values: null,
									alwaysDisplay: true,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_SIGN_FILTER_SIGNED_DATE_LABEL',
									type: 'date',
									key: 'signed_date',
									alwaysDisplay: true,
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
