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

		// The element never stores a value directly — it is resolved server-side.
		getValue: function () {
			return '';
		},

		setValue: function () {
			// no-op: read-only
		}
	});

	return window.FbEmundusReadonly;
});
