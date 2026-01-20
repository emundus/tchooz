<template>
	<div id="form-builder-application-choices">
		<div v-if="!loading">
			<Button>{{ element.params.button_label }}</Button>
		</div>
		<Loader v-else />
	</div>
</template>

<script>
import { useGlobalStore } from '@/stores/global.js';
import Loader from '@/components/Atoms/Loader.vue';
import Button from '@/components/Atoms/Button.vue';

export default {
	components: { Button, Loader },
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
	computed: {},
};
</script>
