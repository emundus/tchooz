import DOMPurify from 'dompurify';

const COLOURED_TAGS = 'strong, b, em, i, u, s';
const BLOCK_TAGS = 'p, div, li, table';

export default {
	methods: {
		cleanHTML(dirty) {
			return DOMPurify.sanitize(dirty);
		},

		/**
		 * Wrap a formatting tag carrying a text colour into a span, the only shape the editor
		 * reads a colour from. Tags holding block children are left untouched.
		 */
		normalizeEditorTextStyles(html) {
			if (!html || !html.includes('color')) {
				return html;
			}

			const container = document.createElement('div');
			container.innerHTML = html;

			container.querySelectorAll(COLOURED_TAGS).forEach((element) => {
				if (!element.style.color || [...element.children].some((child) => child.matches(BLOCK_TAGS))) {
					return;
				}

				const span = document.createElement('span');
				span.style.color = element.style.color;
				element.style.removeProperty('color');

				if (element.getAttribute('style') === '') {
					element.removeAttribute('style');
				}

				element.replaceWith(span);
				span.append(element);
			});

			return container.innerHTML;
		},
	},
};
