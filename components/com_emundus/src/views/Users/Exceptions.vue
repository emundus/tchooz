<template>
	<div id="exceptions-list">
		<list
			v-if="!loading"
			:default-lists="configString"
			:default-type="'exceptions'"
			:key="renderingKey"
			:crud="crud"
		></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Exceptions',
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
			renderingKey: 1,

			loading: true,

			config: {
				exceptions: {
					title: 'COM_EMUNDUS_ONBOARD_USERS_EXCEPTIONS',
					intro: 'COM_EMUNDUS_ONBOARD_USERS_EXCEPTIONS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_USERS_EXCEPTIONS',
							key: 'exceptions',
							controller: 'users',
							getter: 'getallexceptions',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							noData: 'COM_EMUNDUS_ONBOARD_USERS_NOEXCEPTIONS',
							actions: [
								{
									action: 'addexception',
									label: 'COM_EMUNDUS_ONBOARD_EXCEPTIONS_ADD',
									component: 'AddUser',
									name: 'add',
									type: 'modal',
									acl: 'user|u',
								},
								{
									action: 'deleteexception',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DELETE',
									controller: 'users',
									name: 'deleteexception',
									multiple: true,
									method: 'delete',
									confirm: 'COM_EMUNDUS_ONBOARD_EXCEPTIONS_DELETE',
								},
							],
							filters: [],
						},
					],
				},
			},
		};
	},
	created() {
		this.loading = false;
	},
	computed: {
		configString() {
			return btoa(JSON.stringify(this.config));
		},
	},
};
</script>

<style scoped></style>
