export default {
	methods: {
		/**
		 * Convenience pass-through that calls `validate()` on a ParameterForm child via its ref.
		 *
		 * ParameterForm owns the inner Parameter refs (`field_<param>`), so validation must be
		 * delegated to it. Parents declare `<ParameterForm ref="parameterForm" ... />` and call
		 * `this.validateParameterForm(this.$refs.parameterForm)`.
		 *
		 * @param {{ validate?: Function }} parameterFormRef - The ParameterForm component instance.
		 * @returns {{ isValid: boolean, form: Object }} `form` is empty when `isValid` is false.
		 */
		validateParameterForm(parameterFormRef) {
			if (!parameterFormRef || typeof parameterFormRef.validate !== 'function') {
				return { isValid: false, form: {} };
			}
			return parameterFormRef.validate();
		},

		/**
		 * Fill the `value` of every parameter in the given formGroups from a
		 * source object, by matching `parameter.param` against the object keys.
		 *
		 * Accepts either a single formGroup or an array of formGroups so it can
		 * be called with `this.formGroups` or `this.formGroups[0]`.
		 *
		 * Skips keys missing from the source so existing defaults are preserved.
		 *
		 * @param {Object|Array<Object>} groups - A formGroup or array of formGroups.
		 * @param {Object} source - Object whose keys match `parameter.param`.
		 * @returns {void}
		 */
		fillFormGroupsFromObject(groups, source) {
			if (!groups || !source || typeof source !== 'object') return;

			const base = window.location.origin + '/';
			const groupList = Array.isArray(groups) ? groups : [groups];

			for (const group of groupList) {
				if (!group || !Array.isArray(group.parameters)) continue;

				for (const parameter of group.parameters) {
					if (!parameter || !parameter.param) continue;
					if (!Object.prototype.hasOwnProperty.call(source, parameter.param)) continue;

					if (parameter.type === 'file' && source[parameter.param]) {
						parameter.value = source[parameter.param].startsWith('https')
							? source[parameter.param]
							: base + source[parameter.param];
					} else {
						parameter.value = source[parameter.param];
					}

					// Parameter caches `value` at mount; bump `reload` so Vue
					// remounts it and the input picks up the new value.
					parameter.reload = (parameter.reload || 0) + 1;
				}
			}
		},
	},
};
