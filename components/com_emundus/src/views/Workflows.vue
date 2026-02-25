<template>
	<div id="workflow_id">
		<list :default-lists="configString" :default-type="'workflow'" :crud="crud"></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Workflows',
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
			workflowConfig: {
				workflow: {
					title: 'COM_EMUNDUS_ONBOARD_WORKFLOWS',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_WORKFLOWS',
							key: 'workflow',
							controller: 'workflow',
							getter: 'getworkflows',
							noData: 'COM_EMUNDUS_ONBOARD_NOWORKFLOW',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=workflows&layout=add',
									label: 'COM_EMUNDUS_ONBOARD_ADD_WORKFLOW',
									controller: 'workflow',
									name: 'add',
									type: 'redirect',
									acl: 'workflow|c',
								},
								{
									action: 'index.php?option=com_emundus&view=workflows&layout=edit&wid=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'workflow',
									type: 'redirect',
									name: 'edit',
									acl: 'workflow|u',
								},
								{
									action: 'delete',
									label: 'COM_EMUNDUS_ACTIONS_DELETE',
									controller: 'workflow',
									method: 'delete',
									multiple: true,
									name: 'delete',
									confirm: 'COM_EMUNDUS_WORKFLOW_DELETE_WORKFLOW_CONFIRMATION',
									acl: 'workflow|d',
								},
								{
									action: 'duplicate',
									label: 'COM_EMUNDUS_ACTIONS_DUPLICATE',
									controller: 'workflow',
									name: 'duplicate',
									method: 'post',
									acl: 'workflow|c',
								},
								{
									action: 'index.php?option=com_emundus&view=workflows&layout=edit&wid=%id%&readonly=1',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_SHOW_DETAILS',
									type: 'redirect',
									name: 'show',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_WORKFLOWS_FILTER_PROGRAM',
									allLabel: 'COM_EMUNDUS_ONBOARD_ALL_PROGRAMS',
									getter: 'getallprogramforfilter&type=id',
									controller: 'programme',
									key: 'program',
									alwaysDisplay: true,
									values: null,
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
			return btoa(JSON.stringify(this.workflowConfig));
		},
	},
};
</script>

<style scoped></style>
