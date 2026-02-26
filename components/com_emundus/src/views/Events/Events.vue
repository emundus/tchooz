<template>
	<div id="events-list">
		<list :default-lists="configString" :default-type="'events'" :crud="crud"></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Events',
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
			config: {
				events: {
					title: 'COM_EMUNDUS_ONBOARD_EVENTS',
					intro: 'COM_EMUNDUS_ONBOARD_EVENTS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_EVENTS',
							key: 'events',
							controller: 'events',
							getter: 'getevents',
							noData: 'COM_EMUNDUS_ONBOARD_NO_EVENTS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=events&layout=add',
									label: 'COM_EMUNDUS_ONBOARD_ADD_EVENT',
									controller: 'events',
									name: 'add',
									type: 'redirect',
									acl: 'event|c',
								},
								{
									action: 'duplicateevent',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE',
									controller: 'events',
									name: 'duplicate',
									method: 'post',
									acl: 'event|c',
								},
								{
									action: 'index.php?option=com_emundus&view=events&layout=add&event=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'events',
									type: 'redirect',
									name: 'edit',
									acl: 'event|u',
								},
								{
									action: 'deleteevent',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'events',
									name: 'delete',
									method: 'delete',
									multiple: true,
									confirm: 'COM_EMUNDUS_ONBOARD_EVENT_DELETE_CONFIRM',
									showon: {
										key: 'registrant_count',
										operator: '<',
										value: '1',
									},
									acl: 'event|d',
								},
								{
									action: 'show',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_SHOW_DETAILS',
									type: 'modal',
									component: 'EventDetails',
									name: 'show',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS',
									allLabel: 'COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS_ALL',
									getter: 'getlocations',
									controller: 'events',
									key: 'location',
									alwaysDisplay: true,
									values: null,
								},
							],
						},
						{
							title: 'COM_EMUNDUS_ONBOARD_EVENT_LOCATIONS',
							key: 'locations',
							controller: 'events',
							getter: 'getalllocations',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=events&layout=addlocation',
									controller: 'events',
									label: 'COM_EMUNDUS_ONBOARD_ADD_LOCATION',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'index.php?option=com_emundus&view=events&layout=addlocation&location=%id%',
									controller: 'events',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									name: 'edit',
									type: 'redirect',
								},
								{
									action: 'deletelocation',
									controller: 'events',
									label: 'COM_EMUNDUS_ACTIONS_DELETE',
									name: 'delete',
									method: 'delete',
									multiple: true,
									confirm: 'COM_EMUNDUS_ONBOARD_LOCATION_DELETE_CONFIRM',
									showon: {
										key: 'nb_events',
										operator: '<',
										value: '1',
									},
								},
							],
							filters: [],
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
