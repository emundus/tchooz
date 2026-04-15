export default [
	{
		label: 'COM_EMUNDUS_STATUS',
		intro: 'COM_EMUNDUS_STATUS_INTRO',
		name: 'edit_status',
		component: 'EditStatus',
		helptext: 'COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE',
	},
	{
		label: 'COM_EMUNDUS_TAGS',
		intro: 'COM_EMUNDUS_TAGS_INTRO',
		name: 'edit_tags',
		component: 'EditTags',
		helptext: 'COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE',
	},
	{
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_FILES_TOOL',
		intro: 'COM_EMUNDUS_FILES_SETTINGS_INTRO',
		name: 'file_settings',
		component: 'SiteSettings',
		props: {
			json_source: 'settings/sections/file-settings.js',
		},
	},
	{
		label: 'COM_EMUNDUS_CUSTOM_REFERENCE',
		intro: '',
		name: 'custom_reference',
		component: 'CustomReference',
	},
	{
		label: 'COM_EMUNDUS_SETTINGS_FILES_ACTIONS',
		intro: 'COM_EMUNDUS_SETTINGS_FILES_ACTIONS_INTRO',
		name: 'file_actions_settings',
		component: 'SiteSettings',
		props: {
			json_source: 'settings/sections/file-actions-settings.js',
		},
	},
];
