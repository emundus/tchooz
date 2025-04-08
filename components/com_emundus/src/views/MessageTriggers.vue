<script>
import List from '@/views/List.vue';

export default {
	name: 'MessageTriggers',
	components: {
		List,
	},
	props: {
		context: {
			type: String,
			default: 'default',
		},
		contextId: {
			type: Number,
			default: 0,
		},
	},
	data() {
		return {
			config: {
				triggers: {
					title: 'COM_EMUNDUS_ONBOARD_TRIGGERS',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_TRIGGERS',
							key: 'triggers',
							controller: 'email',
							getter: 'getemailtriggers',
							noData: 'COM_EMUNDUS_ONBOARD_NOTRIGGERS',
							actions: [
								{
									action:
										this.context !== 'default' && this.contextId > 0
											? 'index.php?option=com_emundus&view=emails&layout=triggeredit&id=0&' +
												this.context +
												'=' +
												this.contextId
											: 'index.php?option=com_emundus&view=emails&layout=triggeredit&id=0',
									label: 'COM_EMUNDUS_ONBOARD_ADD_TRIGGER',
									controller: 'email',
									name: 'add',
									type: 'redirect',
								},
								{
									action: 'index.php?option=com_emundus&view=emails&layout=triggeredit&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'email',
									name: 'edit',
									type: 'redirect',
								},
								{
									action: 'removetrigger',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'email',
									name: 'delete',
									multiple: false,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_TRIGGER_DELETE',
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

<template>
	<div id="message-triggers-list" :class="{ context: context !== 'default' }">
		<list
			:default-lists="configString"
			:default-type="'triggers'"
			:default-filter="context !== 'default' && contextId > 0 ? `${context}=${contextId}` : ''"
		>
		</list>
	</div>
</template>

<style>
.context #onboarding_list .head {
	background-color: white !important;
}
</style>
