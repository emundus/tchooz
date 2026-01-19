<template>
	<div id="form-builder-field">
		<input v-if="!loading" type="text" class="form-control" readonly :placeholder="placeholder" />
		<Loader v-else />
	</div>
</template>

<script>
import { useGlobalStore } from '@/stores/global.js';
import Loader from '@/components/Atoms/Loader.vue';

export default {
	components: { Loader },
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
		placeholder() {
			if (this.element.params.text_input_mask !== '') {
				return this.element.params.text_input_mask;
			}

			return '';
		},
	},
};
</script>
