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
		translate(key, replacements) {
			if (typeof key === 'undefined' || key === null || typeof Joomla === 'undefined' || Joomla === null) {
				return '';
			}

			const translated = Joomla.Text._(key) ? Joomla.Text._(key) : key;

			if (!replacements || typeof replacements !== 'object') {
				return translated;
			}

			return Object.entries(replacements).reduce((acc, [token, value]) => {
				const safeToken = String(token).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
				const pattern = new RegExp(`\\{${safeToken}\\}`, 'g');
				return acc.replace(pattern, value ?? '');
			}, translated);
		},
	},
};
