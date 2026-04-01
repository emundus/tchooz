/**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque
 * @license   GNU General Public License version 3, or later
 */
"use strict";

(function () {
  // --- Constantes de tareas ---
  const EXPORT_TASKS = new Set(['download_log_file', 'onlinechecks.download_log_file']);
  const VIEW_TASKS   = new Set(['view_log', 'onlinechecks.view_log']);
  
  function t(key, fallback = '') {
	  const out = (window.Joomla?.Text?._(key)) ?? '';
	  return (out && out !== key) ? out : fallback;
	}

	const loading_message = t('JLOADING', 'Loading...');
	const make_a_selection_message = t('JLIB_HTML_PLEASE_MAKE_A_SELECTION', 'Please make a selection.');
	const only_one_message = t(
	  'COM_SECURITYCHECKPRO_SELECT_ONLY_ONE',
	  t('JLIB_HTML_PLEASE_SELECT_ONLY_ONE_ITEM', 'Please select only one item.')
	);  

  // --- Referencias de DOM ---
  const form      = document.adminForm || document.getElementById('adminForm');
  const modalEl   = document.getElementById('view_file');
  const modal     = modalEl ? new (window.bootstrap?.Modal ?? function(){ return null; })(modalEl) : null;
  const contentEl = document.getElementById('logContent');
  const metaEl    = document.getElementById('logMeta');
  const clearBtn  = document.getElementById('filter_onlinechecks_search_button');
  const searchInp = document.getElementById('filter_onlinechecks_search');

  let currentName = null;
  let tailBytes   = 200 * 1024;
  let clickLock   = false; // evita dobles llamadas

  // --- Utilidades selección ---
  function getSelectedFilenames() {
    if (!form) return [];
    const checked = form.querySelectorAll('input[name="onlinechecks_logs_table[]"]:checked');
    return Array.from(checked).map(cb => cb.value).filter(Boolean);
  }

  function countSelected() {
    return getSelectedFilenames().length;
  }

  // --- Botón toolbar "ver log": habilitar/deshabilitar ---
  function getViewLogButtonElements() {
    const host = document.querySelector('joomla-toolbar-button[task="view_log"]');
    let innerBtn = null;
    if (host && host.shadowRoot) {
      innerBtn = host.shadowRoot.querySelector('button, a, [role="button"]');
    }
    return { host, innerBtn };
  }

  function setViewBtnDisabled(disabled) {
    const { host, innerBtn } = getViewLogButtonElements();
    if (!host) return;
    // host (web component)
    if (disabled) {
      host.setAttribute('disabled', '');
      host.setAttribute('aria-disabled', 'true');
    } else {
      host.removeAttribute('disabled');
      host.removeAttribute('aria-disabled');
    }
    // botón interno para temas que dependan de él
    if (innerBtn) {
      innerBtn.toggleAttribute('disabled', disabled);
      innerBtn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
      if (disabled) {
        innerBtn.addEventListener('click', (e) => {
          e.preventDefault(); e.stopImmediatePropagation(); e.stopPropagation();
        }, { capture: true, once: true });
      }
    }
  }

  function updateViewBtnState() {
    setViewBtnDisabled(countSelected() !== 1);
  }

  // --- Fetch del log ---
  async function fetchLogByName(name, opts = {}) {
    const tokenName = (window.Joomla?.getOptions?.('csrf.token')) ?? ''; // nombre real del token
    const params = new URLSearchParams();
    if (tokenName) params.set(tokenName, '1');       // CSRF por GET
    params.set('logfilename', name);
    params.set('html', '1');                         // pedir HTML saneado
    if (opts.tail)  params.set('tail', String(opts.tail));
    if (opts.lines) params.set('lines', String(opts.lines));

    const url = 'index.php?option=com_securitycheckpro&task=filemanager.fetchlog&format=json&' + params.toString();

    let raw, json;
    try {
      const res = await fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        cache: 'no-store',
        credentials: 'same-origin'
      });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      raw = await res.text();
      try { json = JSON.parse(raw); }
      catch {
        if (contentEl) contentEl.textContent = 'Error: invalid JSON response';
        if (metaEl) metaEl.textContent = '';
        console.debug('Fetch response was not JSON:', raw);
        return;
      }
    } catch (e) {
      if (contentEl) contentEl.textContent = 'Error: ' + e.message;
      if (metaEl) metaEl.textContent = '';
      return;
    }

    if ((json && json.success === false) || (json && json.error === true)) {
      if (contentEl) contentEl.textContent = json.message || 'Error';
      if (metaEl) metaEl.textContent = '';
      return;
    }

    const payload =
      (json && json.data && typeof json.data === 'object') ? json.data :
      (json && json.ok) ? json :
      json;

    if (!payload) {
      if (contentEl) contentEl.textContent = 'Error';
      if (metaEl) metaEl.textContent = '';
      return;
    }

    if (contentEl) contentEl.innerHTML = payload.content ?? ''; // ya pides HTML saneado en servidor
    const meta = [];
    if (payload.path)    meta.push('Path: ' + payload.path);
    if (payload.size_h)  meta.push('Size: ' + payload.size_h);
    if (payload.mtime_h) meta.push('Modified: ' + payload.mtime_h);
    if (payload.truncated) meta.push('(tail view)');
    if (metaEl) metaEl.textContent = meta.join(' · ');
  }

  // --- Click en toolbar "ver log" (delegado global) ---
  function getToolbarViewBtnFromEvent(ev) {
    const path = ev.composedPath ? ev.composedPath() : [];
    for (const el of path) {
      if (el && el.nodeType === 1 && el.tagName === 'JOOMLA-TOOLBAR-BUTTON') {
        if (el.getAttribute('task') === 'view_log') return el;
      }
    }
    return null;
  }

  function handleViewClick(ev) {
    const host = getToolbarViewBtnFromEvent(ev);
    if (!host) return;

    ev.preventDefault();
    ev.stopPropagation();
    ev.stopImmediatePropagation();

    if (clickLock) return;
    clickLock = true;
    setTimeout(() => { clickLock = false; }, 300);

    const selected = getSelectedFilenames();
    if (selected.length === 0) { alert(make_a_selection_message); return; }
    if (selected.length > 1)  { alert(only_one_message); return; }

    const name = selected[0];
    currentName = name;

    if (contentEl) contentEl.textContent = loading_message;
    if (metaEl)    metaEl.textContent = "";
    modal?.show();

    fetchLogByName(name, { tail: tailBytes });
  }

  // --- Guardias de submit para VIEW_TASKS y limpieza de EXPORT_TASKS ---
  if (form) {
    form.addEventListener('submit', function (e) {
      const taskField = form.task || form.querySelector('input[name="task"]');
      const taskVal = taskField ? String(taskField.value || '') : '';

      // Intercepta "ver log"
      if (VIEW_TASKS.has(taskVal)) {
        e.preventDefault();
        e.stopImmediatePropagation();
        taskField.value = '';
        handleViewClick(e);
        return false;
      }

      // Limpia tareas de exportación tras el submit
      if (EXPORT_TASKS.has(taskVal)) {
        setTimeout(function () { taskField.value = ''; }, 0);
      }
    }, true);
  }

  // --- Delegado global para clicks en toolbar (view log) ---
  document.addEventListener('click', handleViewClick, true);

  // --- Botón limpiar búsqueda ---
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      if (searchInp) searchInp.value = '';
      if (this.form) this.form.submit();
    });
  }

  // --- Habilitar/deshabilitar "ver log" según selección ---
  function wireSelectionListeners() {
    if (!form) return;
    const onMaybeChange = (e) => {
      const t = e.target;
      if (!t) return;
      if (t.matches('input[name="onlinechecks_logs_table[]"]') || t.matches('input[name="toggle"]')) {
        // deja actualizar checked antes de recalcular
        setTimeout(updateViewBtnState, 0);
      }
    };
    form.addEventListener('change', onMaybeChange);
    form.addEventListener('click', onMaybeChange);
  }

  // --- Init al cargar ---
  function init() {
    wireSelectionListeners();
    updateViewBtnState();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();
