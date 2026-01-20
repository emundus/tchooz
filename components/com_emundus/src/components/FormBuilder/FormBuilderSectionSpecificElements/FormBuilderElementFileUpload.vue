<template>
	<div id="form-builder-file-upload">
		<div
			v-if="!loading"
			class="tw-w-full tw-rounded-coordinator tw-border tw-border-dashed tw-border-neutral-600 tw-p-12 tw-text-center"
		>
			<p class="tw-text-neutral-700">{{ translate('COM_EMUNDUS_FABRIK_ELEMENT_FILEUPLOAD_TEXT') }}</p>
		</div>
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
	computed: {},
};
</script>
