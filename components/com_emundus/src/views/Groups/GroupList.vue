<template>
	<div id="groups-list">
		<list
			:default-lists="config"
			:encoded="false"
			:default-type="'groups'"
			:key="renderingKey"
			:modal-width="'auto'"
		></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'GroupList',
	props: {},
	components: {
		list,
	},
	data() {
		return {
			renderingKey: 1,

			config: {
				groups: {
					title: 'COM_EMUNDUS_ONBOARD_GROUPS',
					intro: 'COM_EMUNDUS_ONBOARD_GROUPS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_GROUPS',
							key: 'groups',
							controller: 'groups',
							getter: 'getallgroups',
							noData: 'COM_EMUNDUS_ONBOARD_NOGROUPS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=groups&layout=form',
									label: 'COM_EMUNDUS_ONBOARD_ADD_GROUP',
									controller: 'groups',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'duplicategroup',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE',
									controller: 'groups',
									name: 'duplicate',
									method: 'post',
									confirm: 'COM_EMUNDUS_ONBOARD_GROUPS_DUPLICATE_CONFIRM',
									input: 'text',
									inputLabel: 'COM_EMUNDUS_ONBOARD_GROUPS_DUPLICATE_NAME_INPUT_LABEL',
								},
								{
									action: 'index.php?option=com_emundus&view=groups&layout=form&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'groups',
									type: 'redirect',
									name: 'edit',
								},
								{
									action: 'deletegroup',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'groups',
									name: 'delete',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_GROUPS_DELETE',
									showon: {
										key: 'canDelete',
										operator: '=',
										value: true,
									},
								},
								{
									action: 'show',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_SHOW_DETAILS',
									type: 'modal',
									component: 'GroupDetails',
									name: 'show',
								},
							],
							filters: [
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
					],
				},
			},
		};
	},
};
</script>

<style scoped></style>
