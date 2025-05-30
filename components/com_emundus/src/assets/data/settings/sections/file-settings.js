export default [
	{
		displayed: true,
		component: 'emundus',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANT_CAN_RENEW',
		param: 'applicant_can_renew',
		type: 'select',
		options: [
			{
				label: 'JNO',
				value: 0,
			},
			{
				label: 'JYES',
				value: 1,
			},
			{
				label: 'COM_EMUNDUS_APPLICANT_CAN_RENEW_CAMPAIGN',
				value: 2,
			},
			{
				label: 'COM_EMUNDUS_APPLICANT_CAN_RENEW_YEAR',
				value: 3,
			},
		],
		value: 0,
	},
	{
		displayed: true,
		component: 'emundus',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANT_CAN_EDIT_UNTIL_DEADLINE',
		param: 'can_edit_until_deadline',
		type: 'yesno',
		options: [
			{
				label: 'JNO',
				value: 0,
			},
			{
				label: 'JYES',
				value: 1,
			},
		],
		value: 0,
	},
	{
		displayed: true,
		component: 'emundus',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANT_CAN_SUBMIT_ENCRYPTED',
		param: 'can_submit_encrypted',
		type: 'yesno',
		options: [
			{
				label: 'JNO',
				value: 0,
			},
			{
				label: 'JYES',
				value: 1,
			},
		],
		value: 0,
	},
	{
		displayed: true,
		component: 'emundus',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_OTHER_USER_EDITING_SAME_FILE',
		param: 'display_other_user_editing_same_file',
		helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_OTHER_USER_EDITING_SAME_FILE_HELPTEXT',
		type: 'yesno',
		options: [
			{
				label: 'JNO',
				value: 0,
			},
			{
				label: 'JYES',
				value: 1,
			},
		],
		value: 1,
	},
	{
		displayed: true,
		component: 'emundus',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_ALLOW_APPLICANT_TO_COMMENT',
		helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_ALLOW_APPLICANT_TO_COMMENT_DESC',
		param: 'allow_applicant_to_comment',
		type: 'yesno',
		options: [
			{ label: 'JNO', value: 0 },
			{ label: 'JYES', value: 1 },
		],
		value: 0,
	},
	{
		displayed: false,
		component: 'emundus',
		label: 'COM_EMUNDUS_ONBOARD_SETTINGS_APPLICANT_CAN_SUBMIT_ANONYM_FILE',
		param: 'allow_anonym_files',
		type: 'yesno',
		options: [
			{
				label: 'JNO',
				value: 0,
			},
			{
				label: 'JYES',
				value: 1,
			},
		],
		value: 0,
	},
];
