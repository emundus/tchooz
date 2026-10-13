import { useGlobalStore } from '@/stores/global';

export default {
	data() {
		return {
			shortDefaultLang: 'fr',
		};
	},
	beforeMount() {
		if (this.$data.translations !== null && typeof this.$data.translations !== 'undefined') {
			Object.entries(this.$data.translations).forEach(([key, value]) => {
				this.$data.translations[key] = this.translate(value);
			});
		}
	},
	mounted() {
		const globalStore = useGlobalStore();
		this.shortDefaultLang = globalStore.defaultLang ? globalStore.defaultLang.substring(0, 2) : 'fr';
	},
	methods: {
		translate(key, ...args) {
			if (typeof key === 'undefined' || key == null || typeof Joomla === 'undefined' || Joomla === null) {
				return '';
			}

			const translation = Joomla.Text._(key) ? Joomla.Text._(key) : key;

			if (args.length === 1 && args[0] !== null && typeof args[0] === 'object') {
				return this.replaceTokens(translation, args[0]);
			}

			return args.length > 0 ? this.sprintf(translation, args) : translation;
		},
		// Named {token} placeholders, e.g. translate('KEY', { name: 'foo' }) on "{name} (system template)".
		replaceTokens(str, replacements) {
			return Object.entries(replacements).reduce((acc, [token, value]) => {
				const safeToken = String(token).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

				return acc.replace(new RegExp(`\\{${safeToken}\\}`, 'g'), () => value ?? '');
			}, str);
		},
		// Minimal sprintf: sequential %s / %d and positional %1$s (matching Joomla .ini conventions).
		sprintf(str, args) {
			let index = 0;

			return str.replace(/%(\d+)\$s|%s|%d/g, (match, position) => {
				const value = position ? args[position - 1] : args[index++];

				return typeof value !== 'undefined' ? value : match;
			});
		},
	},
};
