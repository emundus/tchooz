<script>
import list from '@/views/List.vue';

export default {
	name: 'AutomationList',
	components: { list },
	props: {
		events: {
			type: Array,
			default: () => [],
		},
		actions: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			config: {
				automation: {
					title: 'COM_EMUNDUS_ONBOARD_AUTOMATIONS',
					intro: 'COM_EMUNDUS_ONBOARD_AUTOMATIONS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_AUTOMATIONS',
							key: 'automation',
							controller: 'automation',
							getter: 'getAutomations',
							noData: 'COM_EMUNDUS_ONBOARD_NO_AUTOMATIONS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=automation&layout=edit',
									label: 'COM_EMUNDUS_AUTOMATION_ADD',
									controller: 'automation',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'index.php?option=com_emundus&view=automation&layout=history',
									label: 'COM_EMUNDUS_AUTOMATION_HISTORY',
									controller: 'automation',
									name: 'secondary-head',
									type: 'redirect',
								},
								{
									action: 'index.php?option=com_emundus&view=automation&layout=edit&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'automation',
									type: 'redirect',
									name: 'edit',
								},
								{
									action: 'publishAutomation',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_PUBLISH',
									controller: 'automation',
									type: 'post',
									name: 'publish',
									multiple: true,
									showon: {
										key: 'published',
										operator: '=',
										value: '0',
									},
								},
								{
									action: 'unpublishAutomation',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH',
									controller: 'automation',
									type: 'post',
									name: 'unpublish',
									multiple: true,
									showon: {
										key: 'published',
										operator: '=',
										value: '1',
									},
								},
								{
									action: 'duplicateAutomation',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE',
									controller: 'automation',
									type: 'post',
									name: 'duplicate',
								},
								{
									action: 'deleteAutomation',
									label: 'COM_EMUNDUS_ACTIONS_DELETE',
									controller: 'automation',
									type: 'delete',
									name: 'delete',
									confirm: 'COM_EMUNDUS_AUTOMATION_DELETE_CONFIRM',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_APPLICATION_PUBLISH',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									alwaysDisplay: true,
									getter: '',
									controller: 'automation',
									key: 'filter[a.published]',
									values: [
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
											value: '1,0',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_PUBLISH',
											value: '1',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH',
											value: '0',
										},
									],
									default: '1',
								},
								{
									label: 'COM_EMUNDUS_AUTOMATION_LIST_FILTER_EVENT',
									allLabel: this.translate('COM_EMUNDUS_ONBOARD_FILTER_ALL'),
									alwaysDisplay: true,
									getter: '',
									controller: 'automation',
									key: 'filter[a.event_id]',
									values: [
										{
											label: this.translate('COM_EMUNDUS_ONBOARD_FILTER_ALL'),
											value: 'all',
										},
									].concat(
										this.events
											.map((event) => ({
												value: event.id,
												label: event.label,
											}))
											.sort((a, b) => a.label.localeCompare(b.label)),
									),
									default: 'all',
								},
								{
									label: 'COM_EMUNDUS_AUTOMATION_LIST_FILTER_ACTION',
									allLabel: this.translate('COM_EMUNDUS_ONBOARD_FILTER_ALL'),
									alwaysDisplay: true,
									getter: '',
									controller: 'automation',
									key: 'filter[action.name]',
									values: [
										{
											label: this.translate('COM_EMUNDUS_ONBOARD_FILTER_ALL'),
											value: 'all',
										},
									].concat(
										this.actions
											.map((action) => ({
												value: action.type,
												label: action.label,
											}))
											.sort((a, b) => a.label.localeCompare(b.label)),
									),
									default: 'all',
								},
							],
						},
					],
				},
			},
		};
	},
};
</script>

<template>
	<list :default-lists="config" :default-type="'automation'" :encoded="false"></list>
</template>

<style scoped></style>
