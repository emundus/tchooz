const clickOutside = {
	mounted(el, binding) {
		el.clickOutsideEvent = (event) => {
			const options = typeof binding.value === 'function' ? { handler: binding.value } : binding.value;
			if (options.disabled) {
				return;
			}

			const excludedElements = options.exclude || [];
			const clickedOnExcludedElement = excludedElements.some((selector) => {
				const elements = document.querySelectorAll(selector);
				return Array.from(elements).some((excludedEl) => {
					return excludedEl === event.target || excludedEl.contains(event.target);
				});
			});

			if (clickedOnExcludedElement) {
				return;
			}
			const isElementVisible = (el) => {
				const rect = el.getBoundingClientRect();
				return (
					rect.top >= 0 &&
					rect.left >= 0 &&
					rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
					rect.right <= (window.innerWidth || document.documentElement.clientWidth) &&
					getComputedStyle(el).display !== 'none' &&
					getComputedStyle(el).opacity !== '0' &&
					getComputedStyle(el).visibility !== 'hidden'
				);
			};

			if (!(el === event.target || el.contains(event.target)) && isElementVisible(el)) {
				options.handler(event);
			}
		};
		document.addEventListener('click', el.clickOutsideEvent);
	},
	unmounted(el) {
		document.removeEventListener('click', el.clickOutsideEvent);
	},
};

export default clickOutside;
