<script>
import list from '@/views/List.vue';
import AutomationHistoryItem from '@/views/Automation/AutomationHistoryItem.vue';

export default {
	name: 'AutomationHistoryList',
	components: { list, AutomationHistoryItem },
	data() {
		return {
			selectedItem: {},
			config: {
				automationHistory: {
					title: 'COM_EMUNDUS_AUTOMATION_HISTORY',
					intro: 'COM_EMUNDUS_AUTOMATION_HISTORY_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_AUTOMATION_HISTORY',
							key: 'automationHistory',
							controller: 'automation',
							getter: 'getAutomationsHistory',
							noData: 'COM_EMUNDUS_AUTOMATION_HISTORY_NO_RECORDS',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=automation',
									label: 'COM_EMUNDUS_AUTOMATION_BACK_TO_LIST',
									controller: 'automation',
									name: 'secondary-head',
									type: 'redirect',
								},
								{
									action: 'preview',
									label: 'COM_EMUNDUS_ONBOARD_VISUALIZE',
									controller: 'payment',
									name: 'preview',
									title: 'COM_EMUNDUS_AUTOMATION_DETAILS',
									method: async (item) => {
										await this.previewItem(item);
										await this.$nextTick(); // attend le rendu du DOM
										return this.$refs.historyItem?.$el?.innerHTML || '';
									},
								},
							],
						},
					],
				},
			},
		};
	},
	methods: {
		async previewItem(item) {
			this.selectedItem = item;
			await this.$nextTick();
		},
	},
};
</script>

<template>
	<div>
		<list :defaultLists="config" :defaultType="'automationHistory'" :encoded="false" />
		<AutomationHistoryItem
			v-if="selectedItem"
			:key="selectedItem.id"
			ref="historyItem"
			:item="selectedItem"
			class="hidden"
		/>
	</div>
</template>

<style scoped></style>
