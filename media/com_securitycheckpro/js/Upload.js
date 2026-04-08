 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

  const form = document.getElementById('adminForm');
  const fileInput = document.getElementById('file_to_import');
  const importBtn = document.getElementById('read_file_button');
  const resetBtn = document.getElementById('reset_button');

  const t = (key, fallback) => {
    try { return Joomla?.Text?._(key) || fallback || key; }
    catch { return fallback || key; }
  };

  // Habilita el botón cuando hay fichero seleccionado
  fileInput.addEventListener('change', () => {
    importBtn.disabled = !fileInput.files || fileInput.files.length === 0;
  });

  // Validación ligera: extensión/MIME JSON
  function isJsonFile(file) {
    if (!file) return false;
    const nameOk = /\.json$/i.test(file.name);
    const typeOk = (file.type || '').toLowerCase() === 'application/json';
    // Algunos navegadores no rellenan type; aceptamos por extensión en ese caso
    return nameOk || typeOk;
  }

  // Submit controlado
  importBtn.addEventListener('click', () => {
    const file = fileInput.files && fileInput.files[0];

    if (!file) {
      alert(t('COM_SECURITYCHECKPRO_IMPORT_SETTINGS_FILE_REQUIRED', 'Selecciona un archivo.'));
      fileInput.focus();
      return;
    }

    if (!isJsonFile(file)) {
      alert(t('COM_SECURITYCHECKPRO_IMPORT_SETTINGS_FILE_INVALID', 'El archivo debe ser JSON.'));
      fileInput.focus();
      return;
    }

    // Marca task y muestra spinner
    const taskField = form.querySelector('input[name="task"]');
    taskField.value = importBtn.getAttribute('data-task') || 'upload.read_file';

    importBtn.disabled = true;
    importBtn.querySelector('.spinner-border').classList.remove('d-none');

    // Enviar formulario
    form.submit();
  });

  // Reset UX
  resetBtn.addEventListener('click', () => {
    const taskField = form.querySelector('input[name="task"]');
    taskField.value = '';
    importBtn.disabled = true;
    importBtn.querySelector('.spinner-border').classList.add('d-none');
  });