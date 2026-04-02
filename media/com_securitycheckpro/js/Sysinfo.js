 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

document.addEventListener("DOMContentLoaded", function () {
  // helper: añade listener si existe el elemento
  function onClick(id, handler) {
    var el = document.getElementById(id);
    if (el) {
      el.addEventListener("click", handler);
    }
  }

  // Go to Joomla Update page
  function GoToJoomlaUpdate() {
    window.location.href = "index.php?option=com_joomlaupdate";
  }

  // Go to Joomla Plugins page
  function GoToJoomlaPlugins() {
    window.location.href = "index.php?option=com_plugins&view=plugins";
  }

  // Botones principales
  onClick("GoToJoomlaUpdate_button", GoToJoomlaUpdate);
  onClick("GoToVuln_button", () => Joomla.submitbutton("GoToVuln"));
  onClick("GoToMalware_button", () => Joomla.submitbutton("GoToMalware"));
  onClick("GoToLogs_button", () => Joomla.submitbutton("GoToLogs"));
  onClick("GoToIntegrity_button", () => Joomla.submitbutton("GoToIntegrity"));
  onClick("GoToPermissions_button", () => Joomla.submitbutton("GoToPermissions"));
  onClick("GoToHtaccessProtection_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_session_protection_button", () =>  Joomla.submitbutton("GoToUserSessionProtection"));
  onClick("li_joomla_plugins_button", GoToJoomlaPlugins);
  onClick("li_headers_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_twofactor_button", () => Joomla.submitbutton("GoToCpanel"));
  onClick("li_security_status_button", () => Joomla.submitbutton("GoToFirewallLists"));
  onClick("li_security_status_logs_button", () => Joomla.submitbutton("GoToFirewallLogs"));
  onClick("li_extension_status_second_button", () => Joomla.submitbutton("GoToFirewallSecondLevel"));
  onClick("li_extension_status_exclude_button", () => Joomla.submitbutton("GoToFirewallExceptions"));
  onClick("li_extension_status_xss_button", () => Joomla.submitbutton("GoToFirewallExceptions"));
  onClick("li_extension_status_sql_button", () => Joomla.submitbutton("GoToFirewallExceptions"));
  onClick("li_extension_status_lfi_button", () => Joomla.submitbutton("GoToFirewallExceptions"));
  onClick("li_extension_status_session_button", () => Joomla.submitbutton("GoToUserSessionProtection"));
  onClick("li_extension_status_session_hijack_button", () => Joomla.submitbutton("GoToUserSessionProtection"));
  onClick("li_extension_status_upload_button", () => Joomla.submitbutton("GoToUploadScanner"));
  onClick("li_extension_status_cron_button", () => Joomla.submitbutton("GoToCpanel"));
  onClick("li_extension_status_filemanager_check_button", () => Joomla.submitbutton("GoToPermissions"));
  onClick("li_extension_status_fileintegrity_check_button", () => Joomla.submitbutton("GoToIntegrity"));
  onClick("li_extension_status_spam_button", () => Joomla.submitbutton("GoToCpanel"));
  onClick("li_extension_status_htaccess_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_browsing_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_file_injection_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_self_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_xframe_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_mime_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_default_banned_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_signature_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_eggs_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
  onClick("li_extension_status_sensible_button", () => Joomla.submitbutton("GoToHtaccessProtection"));
});

(function(){
  var hidden = document.getElementById('active_tab');
  if (!hidden) return;
  document.addEventListener('shown.bs.tab', function (ev) {
    var trigger = ev && ev.target ? ev.target : null;
    if (!trigger) return;
    var href = trigger.getAttribute('href') || '';
    if (href && href.charAt(0) === '#') {
      var id = href.slice(1);
      hidden.value = id;
      try { sessionStorage.setItem('{$tabSetId}.active', id); } catch(e) {}
    }
  }, true);
})();