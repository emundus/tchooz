<template>
	<div id="crc-list">
		<list :default-lists="configString" :default-type="'crc'" :key="renderingKey" :modal-height="'60vh'"></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Crc',
	components: {
		list,
	},
	data() {
		return {
			renderingKey: 1,

			config: {
				crc: {
					title: 'COM_EMUNDUS_ONBOARD_CRC',
					intro: 'COM_EMUNDUS_ONBOARD_CRC_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_CRC_CONTACTS',
							key: 'contacts',
							controller: 'crc',
							getter: 'getcontacts',
							noData: 'COM_EMUNDUS_ONBOARD_NOCONTACTS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=crc&layout=contactform',
									label: 'COM_EMUNDUS_ONBOARD_ADD_CONTACT',
									controller: 'crc',
									name: 'add',
									type: 'redirect',
									acl: 'contact|c',
								},
								{
									action: 'index.php?option=com_emundus&view=crc&layout=contactform&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'crc',
									type: 'redirect',
									name: 'editcontact',
									acl: 'contact|u',
								},
								{
									action: 'deletecontact',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'crc',
									name: 'deletecontact',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_CONTACT_DELETE',
									acl: 'contact|d',
								},
								{
									action: 'unpublishcontact',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH',
									controller: 'crc',
									name: 'unpublishcontact',
									multiple: true,
									method: 'post',
									showon: {
										key: 'published',
										operator: '=',
										value: '1',
									},
									acl: 'contact|u',
								},
								{
									action: 'publishcontact',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_PUBLISH',
									controller: 'crc',
									name: 'publishcontact',
									multiple: true,
									method: 'post',
									showon: {
										key: 'published',
										operator: '=',
										value: '0',
									},
									acl: 'contact|u',
								},
								{
									action: 'show',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_CRC_SHOW_DETAILS',
									type: 'modal',
									component: 'ContactDetails',
									name: 'show',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_CONTACT_FILTER_PUBLISH',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									alwaysDisplay: true,
									getter: '',
									controller: 'crc',
									key: 'published',
									values: [
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
											value: 'all',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_PUBLISH',
											value: 'true',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH',
											value: 'false',
										},
									],
									default: 'true',
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_CONTACT_FILTER_NAME',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									getter: 'getfilteredcontacts',
									controller: 'crc',
									key: 'contact',
									values: null,
									multiselect: true,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_CONTACT_FILTER_PHONE_NUMBER',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									getter: 'getfilteredcontactsbyphonenumber',
									controller: 'crc',
									key: 'phone_number',
									values: null,
									multiselect: true,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_CONTACT_FILTER_ORGANIZATION',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									getter: 'getfilteredorganizations',
									controller: 'crc',
									key: 'organization',
									values: null,
									multiselect: true,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_CONTACT_FILTER_NATIONALITY',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									getter: 'getcountriesforfilters',
									controller: 'settings',
									key: 'nationality',
									values: null,
									multiselect: true,
								},
							],
							exports: [
								{
									action: 'exportcsvcontacts',
									label: 'COM_EMUNDUS_ONBOARD_CONTACTS_EXPORTS_EXCEL',
									controller: 'crc',
									name: 'exportcsvcontacts',
									method: 'get',
									multiple: true,
								},
							],
						},
						{
							title: 'COM_EMUNDUS_ONBOARD_ORGANIZATIONS',
							key: 'organizations',
							controller: 'crc',
							getter: 'getorganizations',
							noData: 'COM_EMUNDUS_ONBOARD_NOORGANIZATIONS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=crc&layout=organizationform',
									controller: 'crc',
									label: 'COM_EMUNDUS_ONBOARD_ADD_ORGANIZATION',
									name: 'add',
									type: 'redirect',
									acl: 'organization|c',
								},
								{
									action: 'index.php?option=com_emundus&view=crc&layout=organizationform&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'crc',
									type: 'redirect',
									name: 'editorganization',
									acl: 'organization|u',
								},
								{
									action: 'deleteorganization',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'crc',
									name: 'deleteorganization',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_ORG_DELETE',
									acl: 'organization|d',
								},
								{
									action: 'unpublishorganization',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH',
									controller: 'crc',
									name: 'unpublishorganization',
									multiple: true,
									method: 'post',
									acl: 'organization|u',
									showon: {
										key: 'published',
										operator: '=',
										value: '1',
									},
								},
								{
									action: 'publishorganization',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_PUBLISH',
									controller: 'crc',
									name: 'publishorganization',
									multiple: true,
									method: 'post',
									acl: 'organization|u',
									showon: {
										key: 'published',
										operator: '=',
										value: '0',
									},
								},
								{
									action: 'show',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_CRC_SHOW_DETAILS',
									type: 'modal',
									component: 'OrganizationDetails',
									name: 'show',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_CONTACT_FILTER_PUBLISH',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									alwaysDisplay: true,
									getter: '',
									controller: 'crc',
									key: 'published',
									values: [
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
											value: 'all',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_PUBLISH',
											value: 'true',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH',
											value: 'false',
										},
									],
									default: 'true',
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_ORG_FILTER_NAME',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									getter: 'getfilteredorganizations',
									controller: 'crc',
									key: 'organization',
									values: null,
									multiselect: true,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_ORG_FILTER_IDENTIFIER',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									getter: 'getfilteredorganizationsbyidentifiercode',
									controller: 'crc',
									key: 'identifier_code',
									values: null,
									multiselect: true,
								},
							],
							exports: [
								{
									action: 'exportcsvorganizations',
									label: 'COM_EMUNDUS_ONBOARD_CONTACTS_EXPORTS_EXCEL',
									controller: 'crc',
									name: 'exportcsvorganizations',
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
	created() {},
	computed: {
		configString() {
			return btoa(JSON.stringify(this.config));
		},
	},
};
</script>

<style scoped></style>
