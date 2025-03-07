export default
[
  {
    "label": "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL",
    "name": "general_settings",
    "icon": "display_settings",
    "type": "JSON",
    "source": "general.js",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_WEB_SECURITY",
    "sectionTitle": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_WEB_SECURITY",
    "name": "web_security_settings",
    "icon": "language",
    "type": "component",
    "component": "WebSecurity",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_EMAIL",
    "sectionTitle": "COM_EMUNDUS_GLOBAL_PARAMS_SECTIONS_MANAG_SERVER_MAIL",
    "name": "email_settings",
    "icon": "email",
    "type": "component",
    "component": "EditEmailJoomla",
    "published": true,
    "helptext": "COM_EMUNDUS_GLOBAL_PARAMS_SECTIONS_EMAIL_HELPTEXT",
    "props": {
      "warning": "COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_WARNING"
    }
  },
  {
    "label": "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_CONTENT",
    "name": "content_settings",
    "icon": "notes",
    "type": "JSON",
    "source": "content.js",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_MANAGE_FILES",
    "name": "files_management",
    "icon": "source",
    "type": "JSON",
    "source": "manage-files.js",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_SUPPL_MOD",
    "name": "addons",
    "icon": "dashboard_customize",
    "type": "component",
    "component": "Addons",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_INTEG",
    "name": "integration",
    "icon": "lan",
    "type": "component",
    "component": "Integration",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS",
    "name": "translate",
    "icon": "translate",
    "type": "JSON",
    "source": "translate.js",
    "published": true
  },
  {
    "label": "COM_EMUNDUS_GLOBAL_PARAMS_MENUS_WORKFLOWS",
    "name": "workflows",
    "icon": "schema",
    "type": "component",
    "component": "WorkflowSettings",
    "published": true,
    "props": {}
  }
]