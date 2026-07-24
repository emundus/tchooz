<template>
	<div id="polls-list">
		<list
			:default-lists="configString"
			:modal-width="'50%'"
			:default-type="'polls'"
			:key="renderingKey"
			:crud="crud"
		></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Polls',
	props: {
		crud: {
			type: Object,
			default: [],
		},
		title: {
			type: String,
			default: 'COM_EMUNDUS_POLLS',
		},
		multipleExport: {
			type: Boolean,
			default: true,
		},
	},
	components: {
		list,
	},
	data() {
		return {
			renderingKey: 1,

			config: {
				polls: {
					title: this.$props.title,
					tabs: [
						{
							title: this.$props.title,
							key: 'poll',
							controller: 'poll',
							getter: 'getpolls',
							noData: 'COM_EMUNDUS_NO_POLLS_FOUND',
							acl: 'poll|r',
							actions: [
								{
									action: 'index.php?option=com_emundus&view=polls&layout=add',
									label: 'COM_EMUNDUS_POLLS_ADD',
									controller: 'poll',
									name: 'add',
									iconLabel: 'control_point',
									type: 'redirect',
									acl: 'poll|c',
								},
								{
									action: 'index.php?option=com_emundus&view=polls&layout=edit&id=%id%',
									label: 'COM_EMUNDUS_ONBOARD_MODIFY',
									controller: 'poll',
									type: 'redirect',
									name: 'edit',
									acl: 'poll|u',
								},
								{
									action: 'runpoll',
									label: 'COM_EMUNDUS_POLLS_RUN',
									type: 'modal',
									component: 'PollRun',
									multiple: true,
									iconLabel: 'library_add_check',
									acl: 'poll|u',
									width: '50%',
									showon: {
										key: 'status',
										operator: '!=',
										value: 'open',
									},
								},
								{
									action: 'closepoll',
									label: 'COM_EMUNDUS_POLLS_CLOSE',
									type: 'modal',
									component: 'PollClose',
									multiple: true,
									iconLabel: 'tab_close',
									acl: 'poll|u',
									width: '50%',
									showon: {
										key: 'status',
										operator: '=',
										value: 'open',
									},
								},
								{
									action: 'contactparticipants',
									label: 'COM_EMUNDUS_ONBOARD_ACTION_CONTACT_PARTICIPANTS',
									type: 'modal',
									component: 'PollContact',
									iconLabel: 'forward_to_inbox',
									acl: 'poll|u',
									multiple: true,
									width: '70%',
									height: '90%',
								},
								{
									action: 'show',
									label: 'COM_EMUNDUS_POLL_SHOW_SLOT_DETAILS',
									type: 'modal',
									component: 'PollDetails',
									acl: 'poll|r',
									name: 'show',
									iconLabel: 'manage_search',
									width: '70%',
									height: '70%',
								},
								{
									action: 'reply',
									label: 'COM_EMUNDUS_POLLS_ACTION_REPLY',
									type: 'modal',
									component: 'PollReply',
									name: 'reply',
									icon: 'edit_calendar',
									width: '70%',
									height: '70%',
									showon: {
										key: 'can_reply',
										operator: '=',
										value: true,
									},
								},
								{
									action: 'exportexcel',
									label: 'COM_EMUNDUS_POLLS_EXPORTS_EXCEL',
									controller: 'poll',
									name: 'exportexcel',
									method: 'get',
									multiple: this.$props.multipleExport,
									iconLabel: 'file_upload',
									showon: {
										key: 'can_export',
										operator: '=',
										value: true,
									},
								},
								{
									action: 'delete',
									label: 'COM_EMUNDUS_POLLS_DELETE_TITLE',
									controller: 'poll',
									name: 'delete',
									multiple: true,
									method: 'post',
									confirm: 'COM_EMUNDUS_POLLS_DELETE',
									confirmButton: 'COM_EMUNDUS_POLLS_DELETE_CONFIRM_BUTTON',
									cancelButton: 'COM_EMUNDUS_POLLS_DELETE_CANCEL_BUTTON',
									acl: 'poll|d',
									iconLabel: 'delete',
								},
							],
							filters: [],
							exports: [],
						},
					],
				},
			},
		};
	},
	created() {},
	computed: {
		configString() {
			return btoa(JSON.stringify(this.config));
		},
	},
};
</script>

<style scoped></style>
