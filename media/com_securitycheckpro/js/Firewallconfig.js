/**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

let textdecoded = "no";
const ownip = Joomla.getOptions("securitycheckpro.Firewallconfig.currentip", 0);
const dynamic_blacklist_message = Joomla.Text._('COM_SECURITYCHECKPRO_DYNAMIC_BLACKLIST_MESSAGE');

// Tasks que disparan la exportación
(function () {
  const EXPORT_TASKS = new Set(['export_list', 'firewallconfig.export_list']);

  window.addEventListener('load', function () {
    const form = document.adminForm || document.getElementById('adminForm');
    if (!form) return;

    form.addEventListener('submit', function () {
      try {
        const taskField = form.task || form.querySelector('input[name="task"]');
        if (taskField && EXPORT_TASKS.has(taskField.value)) {
          setTimeout(function () {
            taskField.value = '';
          }, 0);
        }
      } catch (e) {}
    });
  });
})();

// Utilidades mínimas
function $(sel, ctx = document) { return ctx.querySelector(sel); }
function $all(sel, ctx = document) { return Array.prototype.slice.call(ctx.querySelectorAll(sel)); }
function createHidden(name, value) {
  const input = document.createElement('input');
  input.type = 'hidden';
  input.name = name;
  input.value = value;
  return input;
}
function triggerChange(el) { el.dispatchEvent(new Event('change', { bubbles: true })); }

// Base64 (mantiene tu implementación original)
const Base64 = {
  _keyStr: "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",
  encode: function (input) {
    let output = "", chr1, chr2, chr3, enc1, enc2, enc3, enc4, i = 0;
    input = Base64._utf8_encode(input);
    while (i < input.length) {
      chr1 = input.charCodeAt(i++); chr2 = input.charCodeAt(i++); chr3 = input.charCodeAt(i++);
      enc1 = chr1 >> 2;
      enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
      enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
      enc4 = chr3 & 63;
      if (isNaN(chr2)) { enc3 = enc4 = 64; } else if (isNaN(chr3)) { enc4 = 64; }
      output += this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) + this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);
    }
    return output;
  },
  decode: function (input) {
    let output = "", chr1, chr2, chr3, enc1, enc2, enc3, enc4, i = 0;
    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
    while (i < input.length) {
      enc1 = this._keyStr.indexOf(input.charAt(i++));
      enc2 = this._keyStr.indexOf(input.charAt(i++));
      enc3 = this._keyStr.indexOf(input.charAt(i++));
      enc4 = this._keyStr.indexOf(input.charAt(i++));
      chr1 = (enc1 << 2) | (enc2 >> 4);
      chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
      chr3 = ((enc3 & 3) << 6) | enc4;
      output += String.fromCharCode(chr1);
      if (enc3 !== 64) output += String.fromCharCode(chr2);
      if (enc4 !== 64) output += String.fromCharCode(chr3);
    }
    return Base64._utf8_decode(output);
  },
  _utf8_encode: function (string) {
    string = string.replace(/\r\n/g, "\n");
    let utftext = "";
    for (let n = 0; n < string.length; n++) {
      const c = string.charCodeAt(n);
      if (c < 128) utftext += String.fromCharCode(c);
      else if (c < 2048) { utftext += String.fromCharCode((c >> 6) | 192); utftext += String.fromCharCode((c & 63) | 128); }
      else { utftext += String.fromCharCode((c >> 12) | 224); utftext += String.fromCharCode(((c >> 6) & 63) | 128); utftext += String.fromCharCode((c & 63) | 128); }
    }
    return utftext;
  },
  _utf8_decode: function (utftext) {
    let string = "", i = 0, c = 0, c2 = 0, c3 = 0;
    while (i < utftext.length) {
      c = utftext.charCodeAt(i);
      if (c < 128) { string += String.fromCharCode(c); i++; }
      else if (c > 191 && c < 224) { c2 = utftext.charCodeAt(i + 1); string += String.fromCharCode(((c & 31) << 6) | (c2 & 63)); i += 2; }
      else { c2 = utftext.charCodeAt(i + 1); c3 = utftext.charCodeAt(i + 2); string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63)); i += 3; }
    }
    return string;
  }
};

// ========= Persistencia de tabs (vanilla) para UITab con IDs nuevos =========
(function () {
  // Mapea id del <joomla-tab> con su hidden
  const TABSETS = [
    { tabsetId: 'WafConfigurationTabs', hiddenId: 'activeTab_WafConfigurationTabs' },
    { tabsetId: 'ListsTabs', hiddenId: 'activeTab_ListsTabs' },
	{ tabsetId: 'ExceptionsTabs', hiddenId: 'activeTab_ExceptionsTabs' },	
  ];

  function wireTabset({ tabsetId, hiddenId }) {
    const tabset = document.getElementById(tabsetId);
    const hidden  = document.getElementById(hiddenId);
    if (!tabset || !hidden) return;

    // Función para escribir el id del pane activo
    const writeActive = (tabEl) => {
      const paneId = tabEl && tabEl.getAttribute('aria-controls');
      if (paneId) hidden.value = paneId; // p.ej. "li_lists_tab"
    };

    // 1) Al cargar: si hay un tab con aria-selected="true", úsalo
    const selected = tabset.querySelector('[role="tab"][aria-selected="true"]');
    if (selected) writeActive(selected);

    // 2) Observa cambios de aria-selected para actualizar el hidden
    const observer = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.type === 'attributes' && m.attributeName === 'aria-selected') {
          const el = m.target;
          if (el.getAttribute('aria-selected') === 'true') {
            writeActive(el);
          }
        }
      }
    });

    // Observa todos los tabs de este set
    tabset.querySelectorAll('[role="tab"]').forEach(tab => {
      observer.observe(tab, { attributes: true, attributeFilter: ['aria-selected'] });
    });

    // 3) Fallback: al hacer click/tecla, espera al cambio y escribe
    tabset.addEventListener('click', (e) => {
      const btn = e.target.closest('[role="tab"]');
      if (!btn) return;
      // tras el click, aria-selected cambia en el mismo tick o el siguiente
      setTimeout(() => {
        if (btn.getAttribute('aria-selected') === 'true') writeActive(btn);
      }, 0);
    });
    tabset.addEventListener('keydown', (e) => {
      if (e.key !== 'Enter' && e.key !== ' ') return;
      const btn = e.target.closest('[role="tab"]');
      if (!btn) return;
      setTimeout(() => {
        if (btn.getAttribute('aria-selected') === 'true') writeActive(btn);
      }, 0);
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    TABSETS.forEach(wireTabset);
  });
})();



// Handlers DOMContentLoaded
document.addEventListener('DOMContentLoaded', function () {
  Disable();

  const form = document.adminForm || document.getElementById('adminForm');

  const btnClear = $('#filter_lists_search_clear');
  if (btnClear) btnClear.addEventListener('click', function () {
    const inp = $('#filter_lists_search');
    if (inp) inp.value = '';
    if (form) form.submit();
  });

  const btnEnableInspector = $('#enable_url_inspector_button');
  if (btnEnableInspector) btnEnableInspector.addEventListener('click', function () {
    Joomla.submitbutton('enable_url_inspector');
  });
  
  const btnSearch = $('#search_button');
  if (btnSearch) btnSearch.addEventListener('click', function () {
    const f = $('#filter_search');
    if (f) f.value = '';
    if (this.form) this.form.submit();
  });

  const btnUploadImport = $('#upload_import_button');
  if (btnUploadImport) btnUploadImport.addEventListener('click', function () {
    Joomla.submitbutton('import_list');
  });

  const addIpWhitelistButtons = ['#add_ip_whitelist_button', '#add_ip_whitelist_button2'];
  addIpWhitelistButtons.forEach(sel => {
    const btn = $(sel);
    if (btn) btn.addEventListener('click', function () {
      setOwnIP();
      Joomla.submitbutton('addip_whitelist');
    });
  });

  const btnAddBlacklist = $('#add_ip_blacklist_button');
  if (btnAddBlacklist && form) btnAddBlacklist.addEventListener('click', function () {
    form.appendChild(createHidden('task', 'addip_blacklist'));
    form.submit();
  });

  const btnExportBlacklist = $('#export_blacklist_button');
  if (btnExportBlacklist && form) btnExportBlacklist.addEventListener('click', function () {
    form.appendChild(createHidden('export', 'blacklist'));
    Joomla.submitbutton('export_list');
  });

  const btnDelBlacklist = $('#delete_ip_blacklist_button');
  if (btnDelBlacklist) btnDelBlacklist.addEventListener('click', function () {
    Joomla.submitbutton('deleteip_blacklist');
  });

  const btnDelDynBlacklist = $('#deleteip_dynamic_blacklist_button');
  if (btnDelDynBlacklist) btnDelDynBlacklist.addEventListener('click', function () {
    Joomla.submitbutton('deleteip_dynamic_blacklist');
  });

  const toggleDynBlacklist = $('#toggle_dynamic_blacklist');
  if (toggleDynBlacklist) toggleDynBlacklist.addEventListener('click', function () {
    Joomla.checkAll(this);
  });

  const btnImportWhitelist = $('#import_whitelist_button');
  if (btnImportWhitelist) btnImportWhitelist.addEventListener('click', function () {
    Joomla.submitbutton('import_list');
  });

  const btnAddWhitelist = $('#addip_whitelist_button');
  if (btnAddWhitelist && form) btnAddWhitelist.addEventListener('click', function () {
    form.appendChild(createHidden('task', 'addip_whitelist'));
    form.submit();
  });

  const btnSelectBlacklist = $('#select_blacklist_file_to_upload');
  if (btnSelectBlacklist) btnSelectBlacklist.addEventListener('click', function () {
    const el = createHidden('import', 'blacklist');
    el.id = 'import';
    const container = $('#div_boton_subida_blacklist');
    if (container) container.appendChild(el);
  });

  const btnSelectWhitelist = $('#select_whitelist_file_to_upload');
  if (btnSelectWhitelist) btnSelectWhitelist.addEventListener('click', function () {
    const el = createHidden('import', 'whitelist');
    el.id = 'import';
    const container = $('#div_boton_subida_whitelist');
    if (container) container.appendChild(el);
  });

  const btnExportWhitelist = $('#export_whitelist_button');
  if (btnExportWhitelist && form) btnExportWhitelist.addEventListener('click', function () {
    form.appendChild(createHidden('export', 'whitelist'));
    Joomla.submitbutton('export_list');
  });

  const btnDelWhitelist = $('#deleteip_whitelist_button');
  if (btnDelWhitelist) btnDelWhitelist.addEventListener('click', function () {
    Joomla.submitbutton('deleteip_whitelist');
  });

  const btnTestEmail = $('#boton_test_email');
  if (btnTestEmail) btnTestEmail.addEventListener('click', function () {
    Joomla.submitbutton('send_email_test');
  });

  const second = $('#second_level_words');
  if (second) {
    second.addEventListener('focusin', function () {
      if (textdecoded === "no") {
        const decoded = Base64.decode(second.value || "");
        second.value = decoded;
        textdecoded = "si";
      }
    });
    second.addEventListener('focusout', function () {
      if (textdecoded === "si") {
        const encoded = Base64.encode(second.value || "");
        second.value = encoded;
        textdecoded = "no";
      }
    });
  }

  const dynBL = $('#dynamic_blacklist');
  if (dynBL) {
    dynBL.addEventListener('change', function () {
      const dynamic_blacklist_status = dynBL.value;
      if (Number(dynamic_blacklist_status) === 0) {
        const redir = $('#redirect_after_attack');
        if (redir) {
          redir.value = 0;
          triggerChange(redir); // en lugar de "chosen:updated"
        }
        alert(dynamic_blacklist_message);
      }
    });
  }  
});

// Helpers específicos
function setOwnIP() {
  const el = document.getElementById('whitelist_add_ip');
  if (el) el.value = ownip;
}

function muestra_progreso() {
  const btn = document.getElementById('select_blacklist_file_to_upload');
  if (btn) btn.style.display = '';
}

function Disable() {
  const form = document.adminForm || document.getElementById('adminForm');
  if (!form) return;

  // redirect_options
  const redirectSel = form.elements["redirect_options"];
  const redirectUrl = document.getElementById('redirect_url');
  if (redirectSel && redirectUrl) {
    const idx = redirectSel.selectedIndex;
    redirectUrl.readOnly = (idx === 0);
  }

  // strip_all_tags
  const stripSel = form.elements["strip_all_tags"];
  const tagsDiv = document.getElementById('tags_to_filter_div');
  if (stripSel && tagsDiv) {
    const idx = stripSel.selectedIndex;
    if (idx === 1) {
      // ocultar
      tagsDiv.classList.add('d-none');
    } else {
      tagsDiv.classList.remove('d-none');
    }
  }
}

function CheckAll(idname, checktoggle, continentname) {
  const container = document.getElementById(idname);
  if (!container) return;
  const checks = container.getElementsByTagName('input');
  const cont = document.getElementById(continentname);
  if (cont) cont.checked = checktoggle;

  for (let i = 0; i < checks.length; i++) {
    if (checks[i].type === 'checkbox') checks[i].checked = checktoggle;
  }
}

function disable_continent_checkbox(continentname, name) {
  const checkbox = document.getElementById(name);
  if (!checkbox) return;
  if (checkbox.checked !== true) {
    const cont = document.getElementById(continentname);
    if (cont) cont.checked = false;
  }
}


// === Click global para captar pestañas de <joomla-tab> incluso en shadow DOM ===
(function () {
  function ensureHidden(form, name, value) {
    let f = form.querySelector(`input[name="${name}"]`);
    if (!f) {
      f = document.createElement('input');
      f.type = 'hidden';
      f.name = name;
      form.appendChild(f);
    }
    f.value = value;
  }
  function saveActive(tabsetId, panelId) {
    try { localStorage.setItem(`uitab:${tabsetId}`, panelId); } catch (_) {}
  }

  document.addEventListener('click', function (ev) {
    const path = ev.composedPath ? ev.composedPath() : (ev.path || []);
    if (!path || !path.length) return;

    // 1) Encuentra el <button role="tab" aria-controls="...">
    let tabBtn = null;
    for (const node of path) {
      if (!node || node.nodeType !== 1) continue;
      // role="tab" + aria-controls
      if (node.getAttribute && node.getAttribute('role') === 'tab' && node.hasAttribute('aria-controls')) {
        tabBtn = node;
        break;
      }
    }
    if (!tabBtn) return;

    // 2) Encuentra el <joomla-tab id="..."> contenedor
    let tabset = null;
    for (const node of path) {
      if (node && node.nodeType === 1 && node.tagName === 'JOOMLA-TAB' && node.id) {
        tabset = node;
        break;
      }
    }
    if (!tabset) return;

    const panelId = tabBtn.getAttribute('aria-controls'); // p.ej. "li_lists_tab"
    if (!panelId) return;

    // Guarda y actualiza el hidden esperado por PHP: activeTab_<id del joomla-tab>
    const form = document.adminForm || document.getElementById('adminForm');
    if (form) {
      const hiddenName = `activeTab_${tabset.id}`;
      ensureHidden(form, hiddenName, panelId);
    }

    // Opcional: persistimos por si acaso
    saveActive(tabset.id, panelId);
  }, true); // captura para atravesar el shadow DOM
})();


// === Refuerzo antes de enviar: vuelca el activo real de cada <joomla-tab> ===
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.adminForm || document.getElementById('adminForm');
    if (!form) return;

    function ensureHidden(form, name, value) {
      let f = form.querySelector(`input[name="${name}"]`);
      if (!f) {
        f = document.createElement('input');
        f.type = 'hidden';
        f.name = name;
        form.appendChild(f);
      }
      f.value = value;
    }

    form.addEventListener('submit', function () {
      const tabsets = Array.from(document.querySelectorAll('joomla-tab[id]'));
      tabsets.forEach(tabset => {
        const hiddenName = `activeTab_${tabset.id}`;
        let panelId = null;

        // 1) Intenta leer el seleccionado real dentro del shadow DOM
        const sr = tabset.shadowRoot;
        if (sr) {
          const selected = sr.querySelector('[role="tab"][aria-selected="true"]');
          if (selected) panelId = selected.getAttribute('aria-controls') || null;
        }

        // 2) Fallback: lo último guardado en localStorage
        if (!panelId) {
          try { panelId = localStorage.getItem(`uitab:${tabset.id}`); } catch (_) {}
        }

        // 3) Fallback adicional: atributo "active" del propio <joomla-tab>
        if (!panelId) {
          const a = tabset.getAttribute('active');
          if (a) panelId = String(a).replace(/^#/, '');
        }

        if (panelId) {
          ensureHidden(form, hiddenName, panelId);
        }
      });
    });
  });
})();

