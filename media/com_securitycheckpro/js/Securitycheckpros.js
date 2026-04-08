 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";
	
	(function () {
		// Tasks que disparan la exportación
		const EXPORT_TASKS = new Set([
			'csv_export',
			'securitycheckpro.csv_export'
	]);

	// Evita la descarga de los archivos de logs una vez realizada, aunque realizemos otra acción en la página
	window.addEventListener('load', function () {		
		const form = document.adminForm || document.getElementById('adminForm');
		if (!form) return;

		form.addEventListener('submit', function () {
			// Si el submit fue por export, “despega” el task justo después
			try {
				const taskField = form.task || form.querySelector('input[name="task"]');
				if (taskField && EXPORT_TASKS.has(taskField.value)) {
					// Espera a que Joomla procese el submit y luego limpia
					setTimeout(function () {
						taskField.value = '';
					}, 0);
				}
			  } catch (e) {}
			});
		});
	})();
	
    function filter_vulnerable_extension(product) {
	  const url = 'index.php?option=com_securitycheckpro'
		+ '&controller=securitycheckpro'
		+ '&view=securitycheckpro'
		+ '&format=raw'
		+ '&task=filter_vulnerable_extension'
		+ '&product=' + encodeURIComponent(product);

	  fetch(url, {
		method: 'GET',
		headers: { 'X-Requested-With': 'XMLHttpRequest' },
		credentials: 'same-origin',
		cache: 'no-store'
	  })
		.then(response => {
		  if (!response.ok) {
			throw new Error('HTTP ' + response.status);
		  }
		  return response.text();
		})
		.then(html => {
		  const container = document.getElementById('response_result');
		  if (container) {
			container.innerHTML = html;
		  }

		  // Mostrar modal con la API Bootstrap 5
		  const modalEl = document.getElementById('modal_vuln_extension');
		  if (modalEl) {
			const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
			modal.show();
		  }
		})
		.catch(error => {
		  alert('Error: ' + error.message);
		});
	}

