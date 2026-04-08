 /**
 * @package   securitycheckpro
 * @copyright Copyright (c) 2011 - Jose A. Luque / Securitycheck Extensions
 * @license   GNU General Public License version 3, or later
 */
"use strict";
    document.addEventListener('DOMContentLoaded', function () {
		const clearBtn = document.getElementById('filter_acl_search_button');
		const input = document.getElementById('filter_acl_search');
		const form = document.getElementById('adminForm');

		if (clearBtn && input && form) {
			clearBtn.addEventListener('click', function () {
				input.value = '';
				form.submit();
			});
		}
	});  
