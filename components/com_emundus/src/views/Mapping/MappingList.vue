<script>
import list from '@/views/List.vue';

export default {
	name: 'MappingList',
	components: { list },
	props: {},
	data() {
		return {
			config: {
				mappings: {
					title: 'COM_EMUNDUS_ONBOARD_MAPPINGS',
					intro: 'COM_EMUNDUS_ONBOARD_MAPPINGS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_MAPPINGS',
							key: 'mappings',
							controller: 'mapping',
							getter: 'getMappings',
							noData: 'COM_EMUNDUS_ONBOARD_NO_MAPPINGS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=mapping&layout=edit',
									label: 'COM_EMUNDUS_MAPPING_ADD',
									controller: 'mapping',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'index.php?option=com_emundus&view=mapping&layout=edit&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'mapping',
									type: 'redirect',
									name: 'edit',
								},
								{
									action: 'delete',
									label: 'COM_EMUNDUS_ACTIONS_DELETE',
									controller: 'mapping',
									type: 'delete',
									name: 'delete',
									confirm: 'COM_EMUNDUS_MAPPING_DELETE_CONFIRM',
								},
							],
							filters: [],
						},
					],
				},
			},
		};
	},
};
</script>

<template>
	<list :default-lists="config" :default-type="'mappings'" :encoded="false"></list>
</template>

<style scoped></style>
