/**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

/* =======================
   Utils (vanilla helpers)
   ======================= */
const qs  = (sel, ctx = document) => ctx.querySelector(sel);
const qsa = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
const byId = (id) => document.getElementById(id);

const hide = (id) => { const el = byId(id); if (el) el.style.display = "none"; };
const show = (id, display = "block") => { const el = byId(id); if (el) el.style.display = display; };
const setHTML = (id, html = "") => { const el = byId(id); if (el) el.innerHTML = html; };
const setClass = (id, className = "") => { const el = byId(id); if (el) el.className = className; };

async function httpGet(url, as = "text") {
  const res = await fetch(url, {
    method: "GET",
    headers: { "X-Requested-With": "XMLHttpRequest" },
    credentials: "same-origin",
    cache: "no-store"
  });
  if (!res.ok) throw new Error("HTTP " + res.status);
  return as === "json" ? res.json() : res.text();
}

function getOption(key, fallback = "") {
  try { return Joomla.getOptions(key, fallback); } catch { return fallback; }
}

/* =======================
   State / options
   ======================= */
let cont = 0;
let percent = 0;

const repair_log_view_header = getOption("securitycheckpro.Filemanager.repairviewlogheader");
const ended_string2          = getOption("securitycheckpro.Filemanager.end");
const in_progress_string     = getOption("securitycheckpro.Filemanager.inprogress");
const updating_stats         = getOption("securitycheckpro.Filemanager.updatingstats");
const url_to_redirect        = getOption("securitycheckpro.Filemanager.urltoredirect");
const error_button_html      = getOption("securitycheckpro.Filemanager.errorbutton");
const repair_launched        = !!getOption("securitycheckpro.Filemanager.repairlaunched", false);
const launch_new_task        = getOption("securitycheckpro.Filemanager.launchnewtask");
const div_view_log_button    = getOption("securitycheckpro.Filemanager.divviewlogbutton");

// Variables globales que el código original daba por existentes.
// Les ponemos fallback para evitar errores si no están definidas fuera.
const active_task       = window.active_task ?? "";
const task_failure      = window.task_failure ?? "";
const failure           = window.failure ?? "";
const updatingstats     = window.updatingstats ?? updating_stats ?? "";

/* =======================
   Core functions
   ======================= */
async function get_percent() {
  const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent";
  try {
    const responseText = await httpGet(url, "text");
    const value = parseInt(responseText, 10);
    if (!Number.isNaN(value) && value < 100) {
      setHTML("task_status", in_progress_string);
      setHTML("warning_message2", "");
      setClass("error_message", "alert alert-info");
      setHTML("error_message", active_task);
      hide("button_start_scan");
      cont = 3;
      boton_filenamager();
    }
  } catch (e) {
    // Silencio, como en el original
  }
}

async function estado_timediff() {
  const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getEstado_Timediff";
  try {
    const response = await httpGet(url, "json");
    const json = Object.keys(response).map(k => response[k]);
    const estado = json[0];
    const timediff = parseFloat(json[1]);

    if (((estado !== "ENDED") && (estado !== error_string)) && (timediff < 3)) {
      get_percent();
    } else if (((estado !== "ENDED") && (estado !== error_string)) && (timediff > 3)) {
      hide("button_start_scan");
      hide("task_status");
      show("task_error", "block");
      setClass("error_message", "alert alert-error");
       setHTML("error_message", error_string);
    }
  } catch (e) {
    // Silencio
  }
}

function showLog() {
  setHTML("completed_message2", "");
  setHTML("div_view_log_button", "");
  setHTML("log-container_header", repair_log_view_header);
  const el = byId("log-text");
  if (el) el.style.display = "block";
}

async function date_time(id) {
  const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=currentDateTime";
  try {
    const responseText = await httpGet(url, "text");
    setHTML(id, responseText);
  } catch (e) {
    // Silencio
  }
}

async function boton_filenamager() {
  if (cont === 0) {
    show("backup-progress", "flex");
    setHTML("warning_message2", "");
    date_time("start_time");
    percent = 0;
  } else if (cont === 1) {
    setHTML("task_status", in_progress_string);
    const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones";
    // Llamada "fire & forget", sin usar la respuesta
    httpGet(url, "text").catch(() => {});
  } else {
    const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent";
    try {
      const responseText = await httpGet(url, "text");
      const p = parseInt(responseText, 10);
      percent = Number.isNaN(p) ? 0 : p;

      const bar = byId("bar");
      if (bar) bar.style.width = percent + "%";

      if (percent === 100) {
        await date_time("end_time");
        hide("error_message");
        setHTML("task_status", ended_string2);
        if (bar) bar.style.width = "100%";
        setHTML("completed_message2", process_completed);
        setHTML("warning_message2", updating_stats);
        // Redirección inmediata (como en el original, que comentaba el reload y hacía redirect)
        if (url_to_redirect) window.location.href = url_to_redirect;
      }
    } catch (e) {
      show("task_error", "block");
      hide("backup-progress");
      hide("task_status");
      setHTML("warning_message2", "");
      setClass("error_message", "alert alert-error");
      setHTML("error_message", failure);
      setHTML("error_button", error_button_html);
    }
  }

  cont = cont + 1;

  if (percent === 100) {
    // Nada
  } else if (cont > 40 && percent < 90) {
    setTimeout(boton_filenamager, 75000);
  } else {
    setTimeout(boton_filenamager, 1000);
  }
}

function repair() {
  hide("container_resultado");
  show("backup-progress", "block");
  const bar = byId("bar");
  if (bar) bar.style.width = "100%";
  setHTML("completed_message2", process_completed);
  setHTML("warning_message2", updatingstats);
}

/* =======================
   DOM Ready (vanilla)
   ======================= */
document.addEventListener("DOMContentLoaded", () => {
  // Botón iniciar
  const btnStart = byId("button_start_scan");
  if (btnStart) {
    btnStart.addEventListener("click", () => {
      hide("button_start_scan");
      hide("container_resultado");
      hide("container_repair");
      hide("completed_message2");
      boton_filenamager();
    });
  }

  // Limpiar búsqueda y enviar form
  const btnClear = byId("filter_filemanager_search_clear_button");
  if (btnClear) {
    btnClear.addEventListener("click", () => {
      const input = byId("filter_filemanager_search");
      if (input) input.value = "";
      const form = document.adminForm || byId("adminForm");
      if (form) form.submit();
    });
  }

  // Acciones Joomla
  const addExceptionBtn = byId("add_exception_button");
  if (addExceptionBtn) addExceptionBtn.addEventListener("click", () => Joomla.submitbutton("manageExceptionsAdd"));

  const repairBtn = byId("repair_button");
  if (repairBtn) addExceptionBtn?.addEventListener("click", () => {}); // evita duplicidad accidental
  if (repairBtn) repairBtn.addEventListener("click", () => Joomla.submitbutton("repair"));

  const delExceptionBtn = byId("delete_exception_button");
  if (delExceptionBtn) delExceptionBtn.addEventListener("click", () => Joomla.submitbutton("manageExceptionsDelete"));

  // Estado inicial
  hide("container_repair");

  if (repair_launched) {
    hide("container_resultado");
    show("container_repair", "block");
    setHTML("completed_message2", process_completed);
    setHTML("log-container_remember_text", launch_new_task);
    setHTML("div_view_log_button", div_view_log_button);
    hide("log-text");
  }

  hide("backup-progress");
  estado_timediff();

  // Botón cerrar (modal initialize data) → recarga
  const btnClose = byId("buttonclose");
  if (btnClose) {
    btnClose.addEventListener("click", () => {
      setTimeout(() => window.location.reload(), 1000);
    });
  }
});

/* =======================
   Expose functions used elsewhere
   ======================= */
window.showLog = showLog;
window.boton_filenamager = boton_filenamager;
window.repair = repair;
