/**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

/* =======================
   Helpers (vanilla)
   ======================= */
const byId = (id) => document.getElementById(id);
const qs = (sel, ctx = document) => ctx.querySelector(sel);
const qsa = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

function hideElement(id) { const el = byId(id); if (el) el.style.display = "none"; }
function showElement(id, display = "block") { const el = byId(id); if (el) el.style.display = display; }
function setHTML(id, html = "") { const el = byId(id); if (el) el.innerHTML = html; }
function setClass(id, className = "") { const el = byId(id); if (el) el.className = className; }

function getOption(key, fallback = "") {
  try { return Joomla.getOptions(key, fallback); } catch { return fallback; }
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

/* =======================
   Options / State
   ======================= */
const repair_log_view_header = getOption("securitycheckpro.Fileintegrity.repairviewlogheader");
const ended_string2          = getOption("securitycheckpro.Fileintegrity.end");
const in_progress_string     = getOption("securitycheckpro.Fileintegrity.inprogress");
const updating_stats         = getOption("securitycheckpro.Fileintegrity.updatingstats");
const url_to_redirect        = getOption("securitycheckpro.Fileintegrity.urltoredirect");
const error_button_html      = getOption("securitycheckpro.Fileintegrity.errorbutton");

let cont = 0;
let percent = 0;

// Compat con globals no incluidas en el snippet
const active_task       = window.active_task ?? "";
const task_failure      = window.task_failure ?? "";


/* =======================
   Export tasks guard
   ======================= */
(function () {
  const EXPORT_TASKS = new Set([
    "export_logs_integrity",
    "filemanager.export_logs_integrity"
  ]);

  window.addEventListener("load", () => {
    const form = document.adminForm || byId("adminForm");
    if (!form) return;

    form.addEventListener("submit", () => {
      try {
        const taskField = form.task || form.querySelector('input[name="task"]');
        if (taskField && EXPORT_TASKS.has(taskField.value)) {
          setTimeout(() => { taskField.value = ""; }, 0);
        }
      } catch {}
    });
  });
})();

/* =======================
   AJAX flow
   ======================= */
async function get_percent() {
  const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent_integrity";
  try {
    const responseText = await httpGet(url, "text");
    const val = parseInt(responseText, 10);
    if (!Number.isNaN(val) && val < 100) {
      setHTML("task_status", in_progress_string);
      setHTML("warning_message2", "");
      setClass("error_message", "alert alert-info");
      setHTML("error_message", active_task);
      hideElement("button_start_scan");
      cont = 3;
      runButton();
    }
  } catch {}
}

async function estado_integrity_timediff() {
  const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getEstadoIntegrity_Timediff";
  try {
    const response = await httpGet(url, "json");
    const json = Object.keys(response).map(k => response[k]);
    const estado_integrity = json[0];
    const timediff = parseFloat(json[1]);

    if (((estado_integrity !== "ENDED") && (estado_integrity !== error_string)) && (timediff < 3)) {
      get_percent();
    } else if (((estado_integrity !== "ENDED") && (estado_integrity !== error_string)) && (timediff > 3)) {
      hideElement("button_start_scan");
      hideElement("task_status");
      showElement("task_error", "block");
      setClass("error_message", "alert alert-danger");
      setHTML("error_message", error_string);
    }
  } catch {}
}

async function date_time(id) {
  const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=currentDateTime";
  try {
    const responseText = await httpGet(url, "text");
    setHTML(id, responseText);
  } catch {}
}

async function runButton() {
  if (cont === 0) {
    showElement("backup-progress", "flex");
    setHTML("warning_message2", "");
    date_time("start_time");
    percent = 0;
  } else if (cont === 1) {
    setHTML("task_status", in_progress_string);
    const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones_integrity";
    // fire & forget
    httpGet(url, "text").catch(() => {});
  } else {
    const url = "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=get_percent_integrity";
    try {
      const responseText = await httpGet(url, "text");
      const p = parseInt(responseText, 10);
      percent = Number.isNaN(p) ? 0 : p;

      const bar = byId("bar");
      if (bar) bar.style.width = percent + "%";

      if (percent === 100) {
        await date_time("end_time");
        hideElement("error_message");
        setHTML("task_status", ended_string2);
        if (bar) bar.style.width = "100%";
        setHTML("completed_message2", process_completed);
        setHTML("warning_message2", updating_stats);
        if (url_to_redirect) window.location.href = url_to_redirect;
      }
    } catch {
      showElement("task_error", "block");
      hideElement("backup-progress");
      hideElement("task_status");
      setHTML("warning_message2", "");
      setClass("error_message", "alert alert-danger");
      // En el original se asignaba error_button al mensaje; respetamos esa decisión
      setHTML("error_message", error_button_html);
    }
  }

  cont = cont + 1;

  if (percent === 100) {
    // nada
  } else if (cont > 40 && percent < 90) {
    setTimeout(runButton, 5000);
  } else {
    setTimeout(runButton, 1000);
  }
}

/* =======================
   Pie chart (vanilla)
   Estructura esperada:
   .pieID--micro-skills
     .pie-chart__pie
     .pie-chart__legend
       <ul><li><span>valor</span> Título</li>...</ul>
   ======================= */
function sliceSize(val, total) { return (val / total) * 360; }

function addSlice(idSel, sizeDeg, pieElementSel, offset, sliceID, color) {
  const pie = qs(pieElementSel);
  if (!pie) return;

  // Crear slice
  const div = document.createElement("div");
  div.className = `slice ${sliceID}`;
  const span = document.createElement("span");
  div.appendChild(span);
  pie.appendChild(div);

  const adjOffset = offset - 1;
  const sizeRotation = -179 + sizeDeg;

  const container = qs(idSel);
  if (!container) return;

  const slice = container.querySelector("." + sliceID);
  if (slice) {
    slice.style.transform = `rotate(${adjOffset}deg) translate3d(0,0,0)`;
  }
  const spanEl = slice ? slice.querySelector("span") : null;
  if (spanEl) {
    spanEl.style.transform = `rotate(${sizeRotation}deg) translate3d(0,0,0)`;
    spanEl.style.backgroundColor = color;
  }
}

function iterateSlices(idSel, sizeDeg, pieElementSel, offset, dataCount, sliceCount, color) {
  const maxSize = 179;
  const sliceID = `s${dataCount}-${sliceCount}`;

  if (sizeDeg <= maxSize) {
    addSlice(idSel, sizeDeg, pieElementSel, offset, sliceID, color);
  } else {
    addSlice(idSel, maxSize, pieElementSel, offset, sliceID, color);
    iterateSlices(idSel, sizeDeg - maxSize, pieElementSel, offset + maxSize, dataCount, sliceCount + 1, color);
  }
}

function shuffle(array) {
  const a = array.slice();
  for (let i = a.length; i; i--) {
    const j = Math.floor(Math.random() * i);
    const x = a[i - 1];
    a[i - 1] = a[j];
    a[j] = x;
  }
  return a;
}

function createPie(idSel) {
  const container = qs(idSel);
  if (!container) return;

  let listData = [];
  let listTotal = 0;
  let offset = 0;
  const pieElementSel = `${idSel} .pie-chart__pie`;
  const dataElementSel = `${idSel} .pie-chart__legend`;

  let color = [
    "cornflowerblue","olivedrab","orange","tomato","crimson",
    "purple","turquoise","forestgreen","navy"
  ];
  color = shuffle(color);

  qsa(`${dataElementSel} span`).forEach(span => {
    const val = Number(span.textContent || "0");
    listData.push(val);
  });

  for (let i = 0; i < listData.length; i++) listTotal += listData[i];

  for (let i = 0; i < listData.length; i++) {
    const size = sliceSize(listData[i], listTotal);
    iterateSlices(idSel, size, pieElementSel, offset, i, 0, color[i % color.length]);
    const li = qs(`${dataElementSel} li:nth-child(${i + 1})`);
    if (li) li.style.borderColor = color[i % color.length];
    offset += size;
  }
}

function createPieCharts() {
  // Si quieres soportar múltiples charts, itera un qsa aquí:
  // qsa('.pieID--micro-skills').forEach((_, idx) => createPie('.pieID--micro-skills'));
  createPie(".pieID--micro-skills");
}

/* =======================
   DOM Ready
   ======================= */
document.addEventListener("DOMContentLoaded", () => {
  // Bootstrap tooltip (si está disponible)
  const ttEl = byId("extensions_updated_tooltip");
  if (ttEl && window.bootstrap?.Tooltip) {
    new bootstrap.Tooltip(ttEl);
  }

  const clearBtn = byId("filter_fileintegrity_search_clear");
  if (clearBtn) {
    clearBtn.addEventListener("click", () => {
      const input = byId("filter_fileintegrity_search");
      if (input) input.value = "";
      const form = document.adminForm || byId("adminForm");
      if (form) form.submit();
    });
  }

  const addExceptionBtn = byId("add_exception_button");
  if (addExceptionBtn) addExceptionBtn.addEventListener("click", () => Joomla.submitbutton("manageExceptionsAdd"));

  const delExceptionBtn = byId("delete_exception_button");
  if (delExceptionBtn) delExceptionBtn.addEventListener("click", () => Joomla.submitbutton("manageExceptionsDelete"));

  const startBtn = byId("button_start_scan");
  if (startBtn) {
    startBtn.addEventListener("click", () => {
      hideElement("button_start_scan");
      hideElement("container_resultado");
      hideElement("container_repair");
      hideElement("completed_message2");
      runButton();
    });
  }

  const viewLogBtn = byId("view_modal_log_button");
  if (viewLogBtn && typeof window.view_modal_log === "function") {
    viewLogBtn.addEventListener("click", () => window.view_modal_log());
  }

  hideElement("backup-progress");
  estado_integrity_timediff();

  const btnClose = byId("buttonclose");
  if (btnClose) {
    btnClose.addEventListener("click", () => {
      setTimeout(() => window.location.reload(), 1000);
    });
  }

  createPieCharts();
});

// Expone funciones si otras partes las llaman
window.runButton = runButton;
window.date_time = date_time;
