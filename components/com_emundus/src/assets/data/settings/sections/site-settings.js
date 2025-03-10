export default [
	{
		displayed: true,
		component: 'joomla',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SITENAME',
		param: 'sitename',
		type: 'text',
		helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SITENAME_HELPTEXT',
		maxlength: 50,
		placeholder: '',
		optional: null,
	},
	{
		displayed: true,
		component: 'joomla',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SITEDESCRIPTION',
		param: 'MetaDesc',
		type: 'textarea',
		maxlength: 255,
		placeholder: '',
		optional: 1,
	},
	{
		displayed: true,
		param: 'offset',
		component: 'joomla',
		label: 'COM_EMUNDUS_GLOBAL_PARAMS_SITE_TIMEZONE',
		type: 'multiselect',
		helptext: '',
		placeholder: '',
		optional: null,
	},
	{
		displayed: true,
		component: 'joomla',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_LIST_LIMIT',
		param: 'list_limit',
		type: 'select',
		options: [
			{
				label: '5',
				value: 5,
			},
			{
				label: '10',
				value: 10,
			},
			{
				label: '15',
				value: 15,
			},
			{
				label: '20',
				value: 20,
			},
			{
				label: '25',
				value: 25,
			},
			{
				label: '30',
				value: 30,
			},
			{
				label: '50',
				value: 50,
			},
			{
				label: '100',
				value: 100,
			},
			{
				label: '200',
				value: 200,
			},
		],
		value: 20,
		optional: null,
	},
];
