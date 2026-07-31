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
const setText = (id, text = "") => { const el = byId(id); if (el) el.textContent = text; };
const setHTML = (id, html = "") => { const el = byId(id); if (el) el.innerHTML = html; }; // only for trusted server HTML
const setClass = (id, className = "") => { const el = byId(id); if (el) el.className = className; };

/* ── Scan progress line via CSS custom properties ── */
function scanLineSet(pct) {
  const ab = document.querySelector('.scp-actionbar');
  if (ab) ab.style.setProperty('--scp-scan-pct', pct + '%');
}
function scanLineState(state) {
  const ab = document.querySelector('.scp-actionbar');
  if (!ab) return;
  if (state === 'scanning') {
    ab.style.setProperty('--scp-scan-opacity', '1');
    ab.style.setProperty('--scp-scan-color', 'var(--bs-info, #0dcaf0)');
  } else if (state === 'done') {
    ab.style.setProperty('--scp-scan-color', 'var(--bs-success, #198754)');
    setTimeout(() => ab.style.setProperty('--scp-scan-opacity', '0'), 1500);
  } else if (state === 'error') {
    ab.style.setProperty('--scp-scan-color', 'var(--bs-danger, #dc3545)');
  } else {
    ab.style.setProperty('--scp-scan-opacity', '0');
  }
}

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

const ended_string2          = getOption("securitycheckpro.Filemanager.end");
const in_progress_string     = getOption("securitycheckpro.Filemanager.inprogress");
const updating_stats         = getOption("securitycheckpro.Filemanager.updatingstats");
const url_to_redirect        = getOption("securitycheckpro.Filemanager.urltoredirect");
const error_button_html      = getOption("securitycheckpro.Filemanager.errorbutton");

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
      setText("warning_message2", "");
      setClass("error_message", "alert alert-info");
      setHTML("error_message", active_task);
      hide("button_start_scan");
      scanLineSet(value);
      scanLineState("scanning");
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

    if (((estado !== "ENDED") && (estado !== error_string)) && (timediff < 1)) {
      get_percent();
    } else if (((estado !== "ENDED") && (estado !== error_string)) && (timediff > 1)) {
      show("button_start_scan");
      hide("task_status");
      show("task_error", "block");
      scanLineState("error");
      setClass("error_message", "alert alert-error");
      setHTML("error_message", error_string);
    }
  } catch (e) {
    // Silencio
  }
}

async function date_time(id) {
  const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=currentDateTime";
  try {
    const responseText = await httpGet(url, "text");
    setText(id, responseText);
  } catch (e) {
    // Silencio
  }
}

async function boton_filenamager() {
  const csrfToken = Joomla.getOptions("csrf.token");

  if (cont === 0) {
    scanLineSet(0);
    scanLineState("scanning");
    setText("warning_message2", "");
    date_time("start_time");
    percent = 0;
  } else if (cont === 1) {
    setHTML("task_status", in_progress_string);
    const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones" + "&" + encodeURIComponent(csrfToken) + "=1";
    // Llamada "fire & forget", sin usar la respuesta
    httpGet(url, "text").catch(() => {});
  } else {
    const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent";
    try {
      const responseText = await httpGet(url, "text");
      const p = parseInt(responseText, 10);
      percent = Number.isNaN(p) ? 0 : p;

      scanLineSet(percent);

      if (percent === 100) {
        await date_time("end_time");
        hide("error_message");
        setHTML("task_status", ended_string2);
        scanLineState("done");
        setHTML("completed_message2", process_completed);
        setHTML("warning_message2", updating_stats);
        // Redirección inmediata (como en el original, que comentaba el reload y hacía redirect)
        if (url_to_redirect) window.location.href = url_to_redirect;
      }
    } catch (e) {
      show("task_error", "block");
      scanLineState("error");
      hide("task_status");
      setText("warning_message2", "");
      setClass("error_message", "alert alert-error");
      setHTML("error_message", failure);
      setHTML("error_button", error_button_html); // trusted server HTML (button markup)
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
  scanLineSet(100);
  scanLineState("done");
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
      hide("view_log_button");
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
window.boton_filenamager = boton_filenamager;
window.repair = repair;
