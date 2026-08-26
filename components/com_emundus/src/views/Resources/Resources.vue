<template>
	<div id="resources-list">
		<list
			:default-lists="configString"
			:modal-width="'50%'"
			:default-type="'resources'"
			:key="renderingKey"
			:crud="crud"
		></list>
	</div>
</template>

<script>
import list from '@/views/list.vue';

export default {
	name: 'Resources',
	props: {
		crud: {
			type: Object,
			default: [],
		},
		title: {
			type: String,
			default: 'COM_EMUNDUS_RESOURCES_TITLE',
		},
		intro: {
			type: String,
			default: 'COM_EMUNDUS_RESOURCES_INTRO',
		},
	},
	components: {
		list,
	},
	data() {
		return {
			renderingKey: 1,

			config: {
				resources: {
					title: this.$props.title,
					intro: this.$props.intro,
					tabs: [
						{
							title: this.$props.title,
							key: 'resource',
							controller: 'resource',
							getter: 'getresources',
							noData: 'COM_EMUNDUS_RESOURCES_NO_RESULTS',
							viewsOptions: [{ value: 'table', icon: 'dehaze' }],
							actions: [
								{
									name: 'add',
									label: 'COM_EMUNDUS_RESOURCES_IMPORT_DOCUMENT',
									type: 'modal',
									component: 'ResourceImport',
									iconLabel: 'file_download',
									acl: 'resource|c',
									width: '60%',
								},
								{
									name: 'show',
									type: 'modal',
									component: 'ResourcePreview',
									showon: [
										{ key: 'type', operator: '==', value: 'file' },
										{ key: 'can_view', operator: '==', value: true },
									],
									width: '100%',
									height: '100vh',
								},
								{
									name: 'navigate',
									type: 'navigate',
									param: 'folder_id',
									rootLabel: 'COM_EMUNDUS_RESOURCES_ROOT',
									showon: { key: 'type', operator: '==', value: 'folder' },
								},
								{
									name: 'secondary-head',
									controller: 'resource',
									action: 'createfolder',
									method: 'post',
									label: 'COM_EMUNDUS_RESOURCES_ADD_FOLDER',
									showon: { key: 'currentFolderId', operator: '==', value: null },
									confirmIcon: null,
									confirmButton: 'COM_EMUNDUS_RESOURCES_ADD_FOLDER_CONFIRM_BUTTON',
									iconLabel: 'create_new_folder',
									showCancelButton: false,
									showCloseButton: true,
									confirm: '',
									inputLabel: 'COM_EMUNDUS_RESOURCES_ADD_FOLDER_INPUT_LABEL',
									input: 'text',
									inputAttributes: { maxlength: 255 },
									acl: 'resource|c',
								},
								{
									name: 'download',
									controller: 'resource',
									action: 'download',
									iconLabel: 'file_download',
									method: 'get',
									label: 'COM_EMUNDUS_RESOURCES_ACTION_DOWNLOAD',
									showon: [
										{ key: 'type', operator: '==', value: 'file' },
										{ key: 'can_view', operator: '==', value: true },
									],
								},
								{
									name: 'download-archive',
									controller: 'resource',
									action: 'downloadfolderarchive',
									iconLabel: 'folder_zip',
									method: 'get',
									label: 'COM_EMUNDUS_RESOURCES_ACTION_DOWNLOAD_ARCHIVE',
									showon: { key: 'type', operator: '==', value: 'folder' },
								},
								{
									name: 'share',
									label: 'COM_EMUNDUS_RESOURCE_ACCESS_TITLE',
									type: 'modal',
									component: 'ResourceShare',
									iconLabel: 'group',
									width: '70%',
									showon: [
										{ key: 'type', operator: '==', value: 'file' },
										{ key: 'can_manage', operator: '==', value: true },
									],
								},
								{
									name: 'share-folder',
									label: 'COM_EMUNDUS_RESOURCE_ACCESS_FOLDER_TITLE',
									type: 'modal',
									component: 'ResourceShare',
									iconLabel: 'group',
									width: '70%',
									showon: [
										{ key: 'type', operator: '==', value: 'folder' },
										{ key: 'can_manage', operator: '==', value: true },
									],
								},
								{
									name: 'rename-folder',
									controller: 'resource',
									action: 'rename',
									method: 'post',
									label: 'COM_EMUNDUS_RESOURCES_ACTION_RENAME',
									iconLabel: 'edit',
									confirmIcon: null,
									confirmButton: 'COM_EMUNDUS_RESOURCES_RENAME_FOLDER_CONFIRM_BUTTON',
									showCancelButton: false,
									showCloseButton: true,
									confirm: '',
									input: 'text',
									inputLabel: 'COM_EMUNDUS_RESOURCES_RENAME_FOLDER_INPUT_LABEL',
									inputAttributes: { maxlength: 255 },
									inputValueField: 'name',
									showon: [
										{ key: 'type', operator: '==', value: 'folder' },
										{ key: 'can_update', operator: '==', value: true },
									],
								},
								{
									name: 'rename-file',
									controller: 'resource',
									action: 'rename',
									method: 'post',
									label: 'COM_EMUNDUS_RESOURCES_ACTION_RENAME',
									iconLabel: 'edit',
									confirmIcon: null,
									confirmButton: 'COM_EMUNDUS_RESOURCES_RENAME_FILE_CONFIRM_BUTTON',
									showCancelButton: false,
									showCloseButton: true,
									confirm: '',
									input: 'text',
									inputLabel: 'COM_EMUNDUS_RESOURCES_RENAME_FILE_INPUT_LABEL',
									inputAttributes: { maxlength: 255 },
									inputValueField: 'name',
									showon: [
										{ key: 'type', operator: '==', value: 'file' },
										{ key: 'can_update', operator: '==', value: true },
									],
								},
								{
									name: 'move',
									controller: 'resource',
									action: 'move',
									method: 'post',
									label: 'COM_EMUNDUS_RESOURCES_ACTION_MOVE',
									confirmIcon: null,
									confirmButton: 'COM_EMUNDUS_RESOURCES_MOVE_CONFIRM_BUTTON',
									showCancelButton: false,
									showCloseButton: true,
									iconLabel: 'drive_file_move',
									confirm: '',
									input: 'select',
									inputLabel: 'COM_EMUNDUS_RESOURCES_MOVE_INPUT_LABEL',
									optionsGetter: { controller: 'resource', action: 'getfolderoptions' },
									showon: [
										{ key: 'type', operator: '==', value: 'file' },
										{ key: 'can_update', operator: '==', value: true },
									],
								},
								{
									name: 'delete',
									controller: 'resource',
									action: 'delete',
									method: 'delete',
									iconLabel: 'delete',
									label: 'COM_EMUNDUS_RESOURCES_ACTION_DELETE',
									confirm: 'COM_EMUNDUS_RESOURCES_DELETE_FOLDER_CONFIRM',
									confirmLabel: 'COM_EMUNDUS_RESOURCES_ACTION_DELETE_FOLDER_CONFIRM_LABEL',
									confirmButton: 'COM_EMUNDUS_RESOURCES_DELETE_CONFIRM_BUTTON',
									cancelButton: 'COM_EMUNDUS_RESOURCES_DELETE_CANCEL_BUTTON',
									containerClass: '!tw-text-center',
									confirmIcon: null,
									showon: [
										{ key: 'type', operator: '==', value: 'folder' },
										{ key: 'can_update', operator: '==', value: true },
									],
								},
								{
									name: 'delete',
									controller: 'resource',
									action: 'delete',
									method: 'delete',
									iconLabel: 'delete',
									label: 'COM_EMUNDUS_RESOURCES_ACTION_DELETE',
									confirm: 'COM_EMUNDUS_RESOURCES_DELETE_FILE_CONFIRM',
									confirmLabel: 'COM_EMUNDUS_RESOURCES_ACTION_DELETE_FILE_CONFIRM_LABEL',
									confirmButton: 'COM_EMUNDUS_RESOURCES_DELETE_CONFIRM_BUTTON',
									cancelButton: 'COM_EMUNDUS_RESOURCES_DELETE_CANCEL_BUTTON',
									containerClass: '!tw-text-center',
									confirmIcon: null,
									showon: [
										{ key: 'type', operator: '==', value: 'file' },
										{ key: 'can_update', operator: '==', value: true },
									],
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_RESOURCES_FILTER_TYPE',
									allLabel: 'COM_EMUNDUS_RESOURCES_FILTER_TYPE_ALL',
									alwaysDisplay: true,
									key: 'resource_type',
									values: [
										{ label: 'COM_EMUNDUS_RESOURCES_FILTER_TYPE_ALL', value: 'all' },
										{ label: 'COM_EMUNDUS_RESOURCES_FILTER_TYPE_FILE', value: 'file' },
										{ label: 'COM_EMUNDUS_RESOURCES_FILTER_TYPE_FOLDER', value: 'folder' },
									],
									default: 'all',
								},
								{
									label: 'COM_EMUNDUS_RESOURCES_FILTER_FORMAT',
									allLabel: 'COM_EMUNDUS_RESOURCES_FILTER_FORMAT_ALL',
									alwaysDisplay: true,
									key: 'format',
									controller: 'resource',
									getter: 'getformats',
									multiselect: true,
									multiple: true,
								},
							],
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
