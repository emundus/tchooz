<template>
	<div id="form-builder-currency">
		<div v-if="loading" class="em-loader"></div>
		<p>
			{{ translate('COM_EMUNDUS_ONBOARD_BUILDER_APPLICATIONCHOICES_ELEMENT_PREVIEW') }}
		</p>
	</div>
</template>

<script>
import { useGlobalStore } from '@/stores/global.js';

export default {
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

<style lang="scss">
#form-builder-booking {
	.fabrikEmundusGeolocalisation {
		height: 150px;
	}
}
</style>
