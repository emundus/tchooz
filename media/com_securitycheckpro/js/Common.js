/**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

let cont_otp = 0;

// ---------------------
// Mostrar logs por AJAX
// ---------------------
document.addEventListener("click", async (e) => {
  const btn = e.target.closest(".js-view-log");
  if (!btn) return;

  const baseUrl = btn.getAttribute("data-url") || "";
  const filename = btn.getAttribute("data-logfilename") || "";

  const spinner = document.getElementById("logSpinner");
  const content = document.getElementById("logContent");

  content.textContent = "";
  spinner?.classList.remove("d-none");

  try {
    // Construye URL bien (evita problemas con & ?)
    const u = new URL(baseUrl, window.location.href);
    u.searchParams.set("logfilename", filename);

    const resp = await fetch(u.toString(), {
      method: "GET",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
        "Accept": "application/json",
      },
      credentials: "same-origin",
      cache: "no-store",
      redirect: "follow",
    });

    const raw = await resp.text();

    // Si viene vacío, NO hacemos JSON.parse
    if (!raw || raw.trim() === "") {
      throw new Error(`Empty response (HTTP ${resp.status})`);
    }

    // Intenta parsear JSON; si falla, probablemente es HTML (login / error)
    let data;
    try {
      data = JSON.parse(raw);
    } catch (parseErr) {
      // Muestra un trozo del HTML/texto para diagnosticar
      const preview = raw.slice(0, 300).replace(/\s+/g, " ").trim();
      throw new Error(`Non-JSON response (HTTP ${resp.status}): ${preview}`);
    }

    // Joomla JsonResponse => { success, message, messages, data }
    if (!resp.ok || data.success === false) {
      throw new Error(data.message || `Request failed (HTTP ${resp.status})`);
    }

    const logText = data?.data?.content;
    content.textContent = (typeof logText === "string" && logText !== "") ? logText : "No log info";
  } catch (err) {
    content.textContent = "Error: " + (err?.message || String(err));
  } finally {
    spinner?.classList.add("d-none");
  }
});

// ---------------------
// Autocierre mensajes
// ---------------------
window.addEventListener("DOMContentLoaded", () => {
  setTimeout(() => {
    const container = document.getElementById("system-message-container");
    if (!container) return;

    container.style.transition = "opacity 0.5s, max-height 0.5s";
    container.style.opacity = "0";
    container.style.maxHeight = "0";

    setTimeout(() => container.remove(), 600);
  }, 3000);
});

// ---------------------
// Toast Bootstrap 5
// ---------------------
function configure_toast(text, auto) {
  document.getElementById("toast-body").innerHTML = text;
  document.getElementById("toast-auto").innerHTML = auto;

  const toastEl = document.getElementById("toast");
  if (toastEl) {
    const toast = new bootstrap.Toast(toastEl);
    toast.show();
  }
}

document.getElementById('boton_purge_sessions')?.addEventListener('click', function () {
	muestra_progreso_purge();
	purgeSessionsSubmit();
});

// ---------------------
// Mostrar progreso purge
// ---------------------
function muestra_progreso_purge() {
  const divBtn = document.getElementById("div_boton_purge_sessions");
  const divLoading = document.getElementById("div_loading");
  if (divBtn) divBtn.style.display = "none";
  if (divLoading) divLoading.style.display = "block";
}

// ---------------------
// Purgar sessiones
// ---------------------
function purgeSessionsSubmit() {
  window.location.href = "index.php?option=com_securitycheckpro&view=filemanager&controller=filemanager&task=purgeSessions";

}

// ---------------------
// Helpers
// ---------------------
function hideElement(Id) {
  const el = document.getElementById(Id);
  if (el) el.style.display = "none";
}

let cont_initialize = 0;
let etiqueta_initialize = "";
let url_initialize = "";
let request_initialize = "";
let request_clean_tmp_dir = "";
let clean_tmp_dir_result = "";

const loadinggif = Joomla.getOptions("securitycheckpro.Common.loadinggif");
const process_completed = Joomla.Text._("COM_SECURITYCHECKPRO_FILEMANAGER_PROCESS_COMPLETED");
const ended_string_initialize = Joomla.Text._("COM_SECURITYCHECKPRO_FILEMANAGER_ENDED");
const filemanager_warning_message = Joomla.Text._("COM_SECURITYCHECKPRO_FILEMANAGER_WARNING_MESSAGE");
const completed_error = Joomla.Text._("COM_SECURITYCHECKPRO_COMPLETED_ERRORS");
const passed = Joomla.Text._("COM_SECURITYCHECKPRO_PASSED");
const failed = Joomla.Text._("COM_SECURITYCHECKPRO_FAILED");
const otpstatus = Joomla.Text._("COM_SECURITYCHECKPRO_OTP_STATUS");
const moreinfo = Joomla.Text._("COM_SECURITYCHECKPRO_MORE_INFO");
const error_string = Joomla.Text._("COM_SECURITYCHECKPRO_FILEMANAGER_ERROR");

// ---------------------
// Clear data
// ---------------------
async function clear_data_button() {
  if (cont_initialize === 0) {
    document.getElementById("loading-container").innerHTML = loadinggif;
    document.getElementById("warning_message").innerHTML = filemanager_warning_message;
  } else if (cont_initialize === 1) {
    url_initialize =
      "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones_clear_data";
    etiqueta_initialize = "current_task";
  } else {
    url_initialize =
      "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getEstadoClearData";
    etiqueta_initialize = "warning_message";
  }

  try {
    const resp = await fetch(url_initialize);
    request_initialize = await resp.text();
  } catch (e) {
    request_initialize = "";
  }

  cont_initialize++;

  if (request_initialize === ended_string_initialize) {
    hideElement("loading-container");
    hideElement("warning_message");
    document.getElementById("completed_message").innerHTML = process_completed;
    document.getElementById("buttonclose").style.display = "block";
    cont_initialize = 0;
  } else {
    setTimeout(clear_data_button, 1000);
  }
}

// ---------------------
// Clean tmp dir
// ---------------------
async function clean_tmp_dir() {
  if (cont_initialize === 0) {
    document.getElementById("tmpdir-container").innerHTML = loadinggif;
    document.getElementById("warning_message_tmpdir").innerHTML = filemanager_warning_message;
  } else if (cont_initialize === 1) {
    url_initialize =
      "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=acciones_clean_tmp_dir";
    etiqueta_initialize = "current_task";
  } else {
    url_initialize =
      "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getEstadocleantmpdir";
    etiqueta_initialize = "warning_message_tmpdir";
  }

  try {
    const resp = await fetch(url_initialize);
    request_clean_tmp_dir = await resp.text();
  } catch (e) {
    request_clean_tmp_dir = "";
  }

  cont_initialize++;

  if (request_clean_tmp_dir === ended_string_initialize) {
    hideElement("tmpdir-container");
    hideElement("warning_message_tmpdir");

    try {
      const respMsg = await fetch(
        "index.php?option=com_securitycheckpro&controller=filemanager&format=raw&task=getcleantmpdirmessage"
      );
      clean_tmp_dir_result = await respMsg.text();

      if (clean_tmp_dir_result !== "") {
        const el = document.getElementById("completed_message_tmpdir");
        el.classList.add("color_rojo");
        el.innerHTML = completed_error;

        document.getElementById("container_result_area").value = clean_tmp_dir_result;
        document.getElementById("container_result").style.display = "block";
      } else {
        const el = document.getElementById("completed_message_tmpdir");
        el.classList.add("color_verde");
        el.innerHTML = process_completed;
      }
    } catch (e) {}

    document.getElementById("buttonclose_tmpdir").style.display = "block";
    cont_initialize = 0;
  } else {
    setTimeout(clean_tmp_dir, 1000);
  }
}

// ---------------------
// OTP Status
// ---------------------
function get_otp_status() {
  const twofactor_status = Joomla.getOptions("securitycheckpro.Common.twofactorstatus");
  const otp_enabled = Joomla.getOptions("securitycheckpro.Common.otpenabled");

  let type = "error";
  let text_message = failed;

  if (otp_enabled == 1 && twofactor_status >= 2) {
    type = "success";
    text_message = passed;
  }

  show_otp_status(text_message, type, twofactor_status, otp_enabled);
}

function show_otp_status(otp_text, otp_type, twofactor_status, otp_enabled) {
  swal(
    {
      title: otpstatus,
      text: otp_text,
      type: otp_type,
      showCancelButton: true,
      cancelButtonClass: "btn-success",
      cancelButtonText: moreinfo,
    },
    function (isConfirm) {
      if (!isConfirm) {
        window.open("https://scpdocs.securitycheckextensions.com/troubleshooting/otp");
      }
    }
  );

  // Contenido extra
  const extra_content = Joomla.getOptions("securitycheckpro.Common.extracontent");

  if (extra_content && cont_otp < 1) {
    document.querySelectorAll(".form-group").forEach((el) =>
      el.insertAdjacentHTML("afterend", extra_content)
    );
    cont_otp++;
  }

  if (otp_enabled == 0) {
    const otp_enabled_content = Joomla.getOptions("securitycheckpro.Common.otpenabledcontent");
    if (cont_otp < 2) {
      document.querySelectorAll(".form-group").forEach((el) =>
        el.insertAdjacentHTML("afterend", otp_enabled_content)
      );
      cont_otp++;
    }
  }

  if (twofactor_status == 0) {
    const status_content = Joomla.getOptions("securitycheckpro.Common.no2faenabled");
    if (cont_otp < 2) {
      document.querySelectorAll(".form-group").forEach((el) =>
        el.insertAdjacentHTML("afterend", status_content)
      );
      cont_otp++;
    }
  } else if (twofactor_status == 1) {
    const status_content = Joomla.getOptions("securitycheckpro.Common.no2fauserenabled");
    if (cont_otp < 2) {
      document.querySelectorAll(".form-group").forEach((el) =>
        el.insertAdjacentHTML("afterend", status_content)
      );
      cont_otp++;
    }
  }
}
