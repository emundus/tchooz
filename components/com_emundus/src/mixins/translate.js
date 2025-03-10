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
		translate(key) {
			if (typeof key !== 'undefined' && key != null && Joomla !== null && typeof Joomla !== 'undefined') {
				return Joomla.Text._(key) ? Joomla.Text._(key) : key;
			} else {
				return '';
			}
		},
	},
};
