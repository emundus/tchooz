<template>
	<div id="forms-list">
		<list :default-lists="configString" :default-type="'forms'"></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Forms',
	components: {
		list,
	},
	data() {
		return {
			config: {
				forms: {
					title: 'COM_EMUNDUS_ONBOARD_FORMS',
					tabs: [
						{
							title: 'COM_EMUNDUS_FORM_MY_FORMS',
							key: 'form',
							controller: 'form',
							getter: 'getallform',
							noData: 'COM_EMUNDUS_ONBOARD_NOFORM',
							actions: [
								{
									action: 'duplicateform',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE',
									controller: 'form',
									method: 'post',
									name: 'duplicate',
								},
								{
									action: 'unpublishform',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH',
									controller: 'form',
									method: 'post',
									name: 'unpublish',
									confirm: 'COM_EMUNDUS_ONBOARD_ACTION_CONFIRM_UNPUBLISH',
									showon: {
										key: 'status',
										operator: '=',
										value: '1',
									},
								},
								{
									action: 'publishform',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_PUBLISH',
									controller: 'form',
									method: 'post',
									name: 'publish',
									showon: {
										key: 'status',
										operator: '=',
										value: '0',
									},
								},
								{
									action: 'index.php?option=com_emundus&view=form&layout=formbuilder&prid=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'form',
									type: 'redirect',
									name: 'edit',
								},
								{
									action: 'createform',
									controller: 'form',
									label: 'COM_EMUNDUS_ONBOARD_ADD_FORM',
									name: 'add',
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_FORMS_FILTER_PUBLISH',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_PUBLISH',
									alwaysDisplay: true,
									getter: '',
									controller: 'form',
									key: 'filter',
									values: [
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_PUBLISH',
											value: 'Publish',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH',
											value: 'Unpublish',
										},
									],
									default: 'Publish',
								},
							],
						},
						{
							title: 'COM_EMUNDUS_FORM_MY_EVAL_FORMS',
							key: 'form_evaluations',
							controller: 'form',
							getter: 'getallgrilleEval',
							noData: 'COM_EMUNDUS_ONBOARD_NOFORM',
							actions: [
								{
									action: 'createformeval',
									label: 'COM_EMUNDUS_ONBOARD_ADD_EVAL_FORM',
									controller: 'form',
									name: 'add',
								},
								{
									action: '\/index.php?option=com_emundus&view=form&layout=formbuilder&prid=%id%&mode=eval',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'form',
									type: 'redirect',
									name: 'edit',
								},
								{
									action: 'publishFabrikForm',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_PUBLISH',
									controller: 'form',
									method: 'post',
									name: 'publish',
									showon: {
										key: 'published',
										operator: '=',
										value: '0',
									},
								},
								{
									action: 'unpublishFabrikForm',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_UNPUBLISH',
									controller: 'form',
									method: 'post',
									name: 'unpublish',
									confirm: 'COM_EMUNDUS_ONBOARD_ACTION_CONFIRM_UNPUBLISH',
									showon: {
										key: 'published',
										operator: '=',
										value: '1',
									},
								},
								{
									action: 'duplicateFabrikForm',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_DUPLICATE',
									controller: 'form',
									method: 'post',
									name: 'duplicate',
								}
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_FORMS_FILTER_PUBLISH',
									allLabel: 'COM_EMUNDUS_ONBOARD_FILTER_PUBLISH',
									alwaysDisplay: true,
									getter: '',
									controller: 'form',
									key: 'filter',
									values: [
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_PUBLISH',
											value: 'Publish',
										},
										{
											label: 'COM_EMUNDUS_ONBOARD_FILTER_UNPUBLISH',
											value: 'Unpublish',
										},
									],
									default: 'Publish',
								},
							],
						},
						{
							title: 'COM_EMUNDUS_FORM_PAGE_MODELS',
							key: 'form_models',
							controller: 'formbuilder',
							getter: 'getallmodels',
							noData: 'COM_EMUNDUS_ONBOARD_NOFORM',
							actions: [
								{
									action: 'deleteformmodelfromids',
									label: 'COM_EMUNDUS_ACTIONS_DELETE',
									controller: 'formbuilder',
									name: 'delete',
									method: 'delete',
								},
								{
									action: '\/index.php?option=com_emundus&view=form&layout=formbuilder&prid=%form_id%&mode=models',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'form',
									type: 'redirect',
									name: 'edit',
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
