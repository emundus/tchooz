 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";
   (function () {
		const form = document.getElementById('adminForm');
		const input = document.getElementById('filter_rules_search');
		const clearBtn = document.getElementById('filter_rules_clear');
		const resetEmptyLink = document.getElementById('resetEmptyState');

		function clearAndSubmit(e) {
			if (e) e.preventDefault();
			if (input) input.value = '';
			if (form) form.submit();
		}

		if (clearBtn) clearBtn.addEventListener('click', clearAndSubmit);
		if (resetEmptyLink) resetEmptyLink.addEventListener('click', clearAndSubmit);
	})();

