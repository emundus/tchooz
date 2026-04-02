 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";

// Tasks que disparan la exportación
(function () {
  const EXPORT_TASKS = new Set(['exportLogs', 'trackactions_logs.exportLogs']);

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

document.addEventListener('DOMContentLoaded', () => {
  if (window.bootstrap) {
    const list = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    list.forEach(el => new bootstrap.Tooltip(el));
  }
});
