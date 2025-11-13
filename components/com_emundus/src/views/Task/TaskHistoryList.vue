<script>
import list from '@/views/List.vue';

export default {
	name: 'TaskHistoryList',
	components: { list },
	props: {
		statuses: {
			type: Array,
			default: () => [],
		},
	},
	data() {
		return {
			config: {
				task: {
					title: 'COM_EMUNDUS_TASKS',
					intro: 'COM_EMUNDUS_TASKS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_TASKS',
							key: 'task',
							controller: 'task',
							getter: 'gettasks',
							noData: 'COM_EMUNDUS_NO_TASKS',
							actions: [
								{
									action: 'executeTask',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_EXECUTE',
									controller: 'task',
									type: 'post',
									name: 'execute',
									multiple: true,
									showon: {
										key: 'status',
										operator: '!=',
										value: 'completed',
									},
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_TASK_STATUS',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
									alwaysDisplay: true,
									getter: '',
									controller: 'automation',
									key: 'filter[status]',
									values: [
										{
											value: '',
											label: 'COM_EMUNDUS_ONBOARD_FILTER_ALL',
										},
									].concat(this.statuses),
									default: '',
								},
							],
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
						},
					],
				},
			},
		};
	},
};
</script>

<template>
	<list :default-lists="config" :default-type="'task'" :encoded="false"></list>
</template>

<style scoped></style>
