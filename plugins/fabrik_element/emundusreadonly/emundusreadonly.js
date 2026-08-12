/**
 * eMundus Read-Only Fabrik Element
 *
 * @copyright (C) 2008-present eMundus
 * @license   GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

define(['jquery', 'fab/element'], function (jQuery, FbElement) {
	window.FbEmundusReadonly = new Class({
		Extends: FbElement,

		initialize: function (element, options) {
			this.parent(element, options);
		},

		update: function (val) {
			if (this.getElement()) {
				this.element.innerHTML = val;
			}
		},

		getValue: function () {
			if (!this.element) {
				return '';
			}

			var node = this.element;
			if (node.getAttribute('data-raw-value') === null) {
				node = this.element.querySelector('[data-raw-value]');
			}
			var raw = node ? node.getAttribute('data-raw-value') : '';
			if (raw === null || raw === '') {
				return '';
			}

			try {
				return raw.startsWith('[') ? JSON.parse(raw) : raw;
			} catch (e) {
				return raw;
			}
		},

		setValue: function () {
			// no-op: read-only
		}
	});

	return window.FbEmundusReadonly;
});
