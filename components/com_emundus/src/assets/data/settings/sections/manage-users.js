export default [
	{
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_MULTIFACTOR_AUTHENTICATION',
		intro: '',
		name: 'multifactor_authentication',
		component: 'MultifactorAuthentication',
	},
	{
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_SECURITY_RULES',
		intro: '',
		name: 'security_rules',
		component: 'SecurityRules',
	},
	{
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_USER_CATEGORIES',
		intro: 'COM_EMUNDUS_ONBOARD_SETTINGS_USER_CATEGORIES_INTRO',
		name: 'categories',
		component: 'Categories',
	},
	{
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_MANAGE_GROUPS_TITLE',
		name: 'groups_settings',
		component: 'SiteSettings',
		props: {
			json_source: 'settings/sections/groups-settings.js',
		},
		intro: '',
	},
];
