/**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el, {html: true});
});


document.addEventListener("DOMContentLoaded", () => {
  // ---- Click handlers ----
  const clickMap = {
    disable_firewall_button: () => Joomla.submitbutton("disable_firewall"),
    enable_firewall_button: () => Joomla.submitbutton("enable_firewall"),
    disable_cron_button: () => Joomla.submitbutton("disable_cron"),
    enable_cron_button: () => Joomla.submitbutton("enable_cron"),
    disable_update_database_button: () => Joomla.submitbutton("disable_update_database"),
    enable_update_database_button: () => Joomla.submitbutton("enable_update_database"),
    disable_spam_protection_button: () => Joomla.submitbutton("disable_spam_protection"),
    enable_spam_protection_button: () => Joomla.submitbutton("enable_spam_protection"),
    manage_lists_button: () => {
      SetActiveTab("#li_lists_tab");
      Joomla.submitbutton("manage_lists");
    },
    go_system_info_buton: () => Joomla.submitbutton("Go_system_info"),
    unlock_tables_button: () => Joomla.submitbutton("unlockAll"),
    lock_tables_button: () => Joomla.submitbutton("lockSelectedTables"),
    apply_default_config_button: () => Set_Default_Config(),
    apply_easy_config_button: () => Set_Easy_Config(),
  };

  Object.entries(clickMap).forEach(([id, handler]) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener("click", handler, false);
  });

  // ---- Chart.js ----
  try {
    const blockedaccessText = Joomla.getOptions("securitycheckpro.Cpanel.blockedaccessText");
    const userandsessionprotectionText = Joomla.getOptions("securitycheckpro.Cpanel.userandsessionprotectionText");
    const firewallrulesappliedText = Joomla.getOptions("securitycheckpro.Cpanel.firewallrulesappliedText");
    const total_blocked_access = Joomla.getOptions("securitycheckpro.Cpanel.totalblockedaccess", 0);
    const total_user_session_protection = Joomla.getOptions("securitycheckpro.Cpanel.totalusersessionprotection", 0);
    const total_firewall_rules = Joomla.getOptions("securitycheckpro.Cpanel.totalfirewallrules", 0);

    // Compat v2/v3: defaults
    if (window.Chart) {
      if (Chart.defaults && Chart.defaults.global) {
        // Chart.js v2
        Chart.defaults.global.defaultFontFamily =
          '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
        Chart.defaults.global.defaultFontColor = "#cdcdcd";
      } else if (Chart.defaults) {
        // Chart.js v3+
        Chart.defaults.font = Chart.defaults.font || {};
        Chart.defaults.color = "#cdcdcd";
        Chart.defaults.font.family =
          '-apple-system,system-ui,BlinkMacSystemFont,"Segoe UI",Roboto,"Helvetica Neue",Arial,sans-serif';
      }

      const ctx = document.getElementById("piechart");
      if (ctx) {
        new Chart(ctx, {
          type: "pie",
          data: {
            labels: [blockedaccessText, userandsessionprotectionText, firewallrulesappliedText],
            datasets: [
              {
                data: [total_blocked_access, total_user_session_protection, total_firewall_rules],
                backgroundColor: ["#007bff", "#dc3545", "#ffc107"],
              },
            ],
          },
        });
      }
    }
  } catch (e) {
    // Silencioso: el gráfico es accesorio
  }

  // ---- Tooltips (Bootstrap 5) ----
  ["subscriptions_status", "scp_version", "update_database_version", "trackactions_version"].forEach((id) => {
    const el = document.getElementById(id);
    if (el && window.bootstrap && bootstrap.Tooltip) {
      new bootstrap.Tooltip(el);
    }
  });

  // ---- Mensaje informativo con fade + slide (sin jQuery) ----
  const info = document.getElementById("mensaje_informativo");
  if (info) {
    setTimeout(() => {
      info.style.transition = "opacity 0.5s, max-height 0.5s";
      // Para "slideUp" usamos max-height + overflow
      info.style.overflow = "hidden";
      info.style.maxHeight = info.scrollHeight + "px"; // estado inicial
      requestAnimationFrame(() => {
        info.style.opacity = "0";
        info.style.maxHeight = "0";
      });
      setTimeout(() => info.remove(), 600);
    }, 5000);
  }
});

// ---- Mostrar progreso ----
function muestra_progreso() {
  const btn = document.getElementById("div_boton_subida");
  const loading = document.getElementById("div_loading");
  if (btn) btn.style.display = "none";
  if (loading) loading.style.display = "block";
}

// ---- Set Easy Config ----
async function Set_Easy_Config() {
  const url =
    "index.php?option=com_securitycheckpro&controller=cpanel&format=raw&task=Set_Easy_Config";
  try {
    await fetch(url, { method: "GET", cache: "no-store" });
  } catch (e) {    
  } finally {
    location.reload();
  }
}

// ---- Set Default Config con confirm ----
async function Set_Default_Config() {
  const message = Joomla.getOptions("securitycheckpro.Cpanel.setdefaultconfigconfirmText");
  const answer = window.confirm(message);
  if (!answer) return;

  const url =
    "index.php?option=com_securitycheckpro&controller=cpanel&format=raw&task=Set_Default_Config";
  try {
    await fetch(url, { method: "GET", cache: "no-store" });
  } catch (e) {    
  } finally {
    location.reload();
  }
}

// ---- Persistencia de pestañas (localStorage) ----
let ActiveTab = "lists";

function SetActiveTab(value) {
  ActiveTab = value;
  storeValue("active", ActiveTab);
}

function storeValue(key, value) {
  try {
    localStorage.setItem(key, value);
  } catch (e) {
    // Sin fallback a cookies
  }
}
