export default
[
  {
    "displayed": true,
    "component": "joomla",
    "label": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER",
    "helptext": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER_ADRESS_HELPTEXT",
    "param": "mailfrom",
    "type": "email",
    "value": "",
    "placeholder": "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CUSTOM_PLACEHOLDER_MAILFROM_NAME@COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CUSTOM_PLACEHOLDER_MAILFROM_DOMAIN",
    "editable": "semi",
    "visibility": "all",
    "optional": 0,
    "regex": ["^[a-zA-Z0-9._-]+$","^[a-zA-Z0-9_-]+\\.[a-zA-Z]{2,5}$"],
    "error": "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_CHECK_INPUT_MAIL"
  },
  {
    "displayed": true,
    "component": "joomla",
    "label": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER_NAME",
    "param": "fromname",
    "type": "text",
    "placeholder": "Tchooz",
    "value": "",
    "visibility": "all",
    "optional": 0
  },
  {
    "displayed": true,
    "component": "joomla",
    "label": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_HOST",
    "helptext": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_HOSTSMTP_HELPTEXT",
    "param": "smtphost",
    "type": "text",
    "placeholder": "smtp.tchooz.app",
    "value": "",
    "optional": 0
  },
  {
    "displayed": true,
    "component": "joomla",
    "label": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_PORT",
    "helptext": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_HELPTEXT",
    "param": "smtpport",
    "type": "number",
    "placeholder": "25, 465, 587",
    "value": "",
    "max": 65535,
    "warning": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING",
    "optional": 0,
    "regex": "[0-9]+$"
  },
  {
    "displayed": true,
    "component": "joomla",
    "label": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_SECURITY",
    "helptext": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SECURITY_HELPTEXT",
    "param": "smtpsecure",
    "type": "select",
    "options": [
      {
        "value": "none",
        "label": "COM_EMUNDUS_FILTERS_CHECK_NONE"
      },
      {
        "value": "ssl",
        "label": "SSL"
      },
      {
        "value": "tls",
        "label": "TLS"
      }
    ],
    "value": "",
    "optional": null
  },
  {
    "displayed": true,
    "component": "joomla",
    "label": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_ENABLE",
    "param": "smtpauth",
    "type": "toggle",
    "value": false,
    "optional": null,
    "hideLabel": true
  },
  {
    "displayed": true,
    "component": "joomla",
    "label": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_USERNAME",
    "param": "smtpuser",
    "type": "text",
    "placeholder": "",
    "value": "",
    "optional": 0
  },
  {
    "displayed": true,
    "component": "joomla",
    "label": "COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_PASSWORD",
    "param": "smtppass",
    "type": "password",
    "value": "",
    "optional": 0
  }
]