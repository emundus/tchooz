<template>
	<div id="form-builder-panel">
		<div v-if="!loading">
			<Info
				:text="element.default"
				:bg-color="backgroundColor"
				:icon="icon"
				:icon-color="iconColor"
				:text-color="textColor"
			/>
		</div>
		<Loader v-else />
	</div>
</template>

<script>
import { useGlobalStore } from '@/stores/global.js';
import Loader from '@/components/Atoms/Loader.vue';
import Info from '@/components/Utils/Info.vue';

export default {
	components: { Info, Loader },
	props: {
		element: {
			type: Object,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: false,
		};
	},
	mounted() {
		this.locale = useGlobalStore().getShortLang;
	},
	computed: {
		backgroundColor() {
			let type = parseInt(this.element.params.type);

			switch (type) {
				case 1:
					return 'tw-bg-alert-info-bg';
				case 2:
					return 'tw-bg-alert-warning-bg';
				case 3:
					return 'tw-bg-alert-error-bg';
				default:
					return 'tw-bg-transparent';
			}
		},
		icon() {
			let type = parseInt(this.element.params.type);

			switch (type) {
				case 1:
					return 'info';
				case 2:
					return 'report_problem';
				case 3:
					return 'cancel';
				default:
					return '';
			}
		},
		borderColor() {
			let type = parseInt(this.element.params.type);

			switch (type) {
				case 1:
					return 'tw-border-alert-info-border';
				case 2:
					return 'tw-border-alert-warning-border';
				case 3:
					return 'tw-border-alert-error-border';
				default:
					return 'tw-border-transparent';
			}
		},
		iconColor() {
			let type = parseInt(this.element.params.type);

			switch (type) {
				case 1:
					return 'tw-text-alert-info-icon';
				case 2:
					return 'tw-text-alert-warning-icon';
				case 3:
					return 'tw-text-alert-error-icon';
				default:
					return 'tw-text-transparent';
			}
		},
		textColor() {
			let type = parseInt(this.element.params.type);

			switch (type) {
				case 1:
					return 'tw-text-alert-info';
				case 2:
					return 'tw-text-alert-warning';
				case 3:
					return 'tw-text-alert-error';
				default:
					return 'tw-text-neutral-900';
			}
		},
	},
};
</script>
