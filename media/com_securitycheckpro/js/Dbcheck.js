/**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

const tables_data = Joomla.getOptions("securitycheckpro.Dbcheck.tables");

// ---------- Helpers ----------
function $(sel, ctx) {
  return (ctx || document).querySelector(sel);
}
function $all(sel, ctx) {
  return Array.from((ctx || document).querySelectorAll(sel));
}
function hideElement(id) {
  const el = typeof id === "string" ? document.getElementById(id) : id;
  if (el) el.style.display = "none";
}
function unhide(selOrEl) {
  const el = typeof selOrEl === "string" ? $(selOrEl) : selOrEl;
  if (!el) return null;
  el.classList.remove("hidden");
  el.style.display = ""; 
  return el;
}

// ---------- Tooltips ----------
document.addEventListener("DOMContentLoaded", () => {
  const t1 = document.getElementById("show_tables");
  const t2 = document.getElementById("last_optimization_tooltip");
  if (window.bootstrap && bootstrap.Tooltip) {
    if (t1) new bootstrap.Tooltip(t1);
    if (t2) new bootstrap.Tooltip(t2);
  }

  const btn = document.getElementById("start_db_check");
  if (btn) btn.addEventListener("click", StartDbCheck, false);  

});

// ---------- Timeout config ----------
const requestTimeOut = { Seconds: 60 }; 

// ---------- Database.Check ----------
const Database = {};
Database.Check = {
  tables: [],
  tablesNum: 0,
  table: null,     // elemento <table> del bloque actual
  content: null,   // contenedor del bloque
  prefix: "",
  _aborted: false,
  _controllers: [],

  unhide(item) {
    return unhide(item);
  },

  // Obtiene el nombre del token CSRF de Joomla (sin jQuery)
  /*_getCsrfTokenName() {
    try {
      // Si Joomla está disponible en global, úsalo (J5/J6)
      if (window.Joomla && typeof Joomla.getOptions === "function") {
        const name = Joomla.getOptions("csrf.token");
        if (name) return name;
      }
    } catch (_) {}
    // Fallback: intenta localizar input hidden con value=1 que tenga pinta de token
    const candidate = document.querySelector('input[type="hidden"][name][value="1"]');
    return candidate ? candidate.name : null;
  },*/

  startCheck() {
    this._aborted = false;
    this._controllers = [];

    this.table = document.getElementById(this.prefix + "-table");
    this.content = document.getElementById(this.prefix);
    if (!this.tables.length) return false;

    this.unhide(this.content);
    // Emular hide().show('fast')
    this.content.style.display = "none";
    requestAnimationFrame(() => {
      this.content.style.display = "";
      // Arrancamos el proceso
      Database.Check.stepCheck(0);
    });
  },

  stopCheck(message) {
	// Marca abortado y cancela peticiones en vuelo
    this._aborted = true;
    this._controllers.forEach(c => { try { c.abort(); } catch(_) {} });
    this._controllers = [];

    // Mensaje opcional a UI (usa tu método de avisos si tienes uno)
    if (message) {		
      try {
        if (window.Joomla && Joomla.renderMessages) {
          Joomla.renderMessages({ error: [message] });
        }
      } catch (_) {}
    }
  },

  setProgress(index) {
    const bar = document.querySelector(`#${this.prefix}-progress .securitycheckpro-bar`);
    if (!bar || !this.tablesNum) return;
    const currentProgress = (100 / this.tablesNum) * index;
    bar.style.width = currentProgress + "%";
  },

  async stepCheck(index) {
    if (this._aborted) return;
    this.setProgress(index);

    if (!this.tables || !this.tables.length || index >= this.tables.length) {
      this.stopCheck(); // fin normal
      return false;
    }

    // Muestra la fila
    if (this.table) {
      const rows = this.table.querySelectorAll("tr");
      const row = rows[index + 1]; // +1 para saltar cabecera
      if (row) this.unhide(row);
    }

    // Toma nombre y engine de tables_data
    const entry  = tables_data[index] || {};
    const table  = entry["Name"]   || "";
    const engine = entry["Engine"] || "";

    // --- Construye payload con CSRF ---
    const formData = new FormData();
    formData.set("task", "optimize");
    formData.set("table", table);
    formData.set("engine", engine);
    formData.set("sid", String(Math.random()));

    /*const tokenName = this._getCsrfTokenName();
    if (tokenName) formData.set(tokenName, "1");*/

    // Control para abortar esta request si se detiene el proceso
    const controller = new AbortController();
    this._controllers.push(controller);

    let resultEl = document.getElementById("result" + index);

    try {
	  const url = "index.php?option=com_securitycheckpro&task=dbcheck.optimize";

		const resp = await fetch(url, {
		  method: "POST",
		  body: formData,
		  headers: { "X-Requested-With": "XMLHttpRequest" },
		  cache: "no-store",
		  credentials: "same-origin",
		  signal: controller.signal,
		});

		const raw = await resp.text().catch(() => "");
		let payload = null;
		try { payload = raw ? JSON.parse(raw) : null; } catch (_) {}

		if (!resp.ok) {
		  const msg = payload?.message || payload?.error || raw || `HTTP ${resp.status}`;
		  if (resultEl) resultEl.textContent = msg;
		  this.stopCheck(msg);
		  return;
		}

		// Si por lo que sea tu backend aún devuelve success:false, muéstralo pero NO “mates” todo si no es error real
		if (payload && payload.success === false) {
		  const msg = payload.message || "Operation failed.";
		  if (resultEl) resultEl.innerHTML = msg;
		  this.stopCheck(msg);
		  return;
		}

		if (resultEl) resultEl.innerHTML = payload?.message ?? "OK";

      // Avanza SOLO si ha ido bien
      if (this._aborted) return;
      if (requestTimeOut.Seconds !== 0) {
        setTimeout(() => Database.Check.stepCheck(index + 1), 60);
      } else {
        Database.Check.stepCheck(index + 1);
      }
    } catch (err) {
	  const aborted = err?.name === "AbortError";
	  const msg = aborted ? "Request cancelled." : "Network error.";
	  if (resultEl) resultEl.textContent = msg;
	  this.stopCheck?.(msg);
	}
  },
};


// ---------- DB Check launcher ----------
function StartDbCheck() {
  hideElement("buttondatabase");
  Database.Check.unhide("#securitycheck-bootstrap-database");

  Database.Check.prefix = "securitycheck-bootstrap-database";
  Database.Check.tables = tables_data.map(t => t["Name"]);
  Database.Check.tablesNum = Database.Check.tables.length;

  // --- DECORAR stopCheck, no reemplazarla ---
  const baseStop = Database.Check.stopCheck.bind(Database.Check);
  Database.Check.stopCheck = function (message) {
    // 1) Lógica real: marca _aborted, aborta fetches y muestra mensaje
    baseStop(message);

    // 2) Efecto visual que ya tenías
    const prog = document.getElementById("securitycheck-bootstrap-database-progress");
    if (!prog) return;
    prog.style.transition = "opacity 0.2s";
    requestAnimationFrame(() => {
      prog.style.opacity = "0";
      setTimeout(() => prog.remove(), 220);
    });
  };

  Database.Check.startCheck();
}

