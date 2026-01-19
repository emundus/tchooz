<template>
	<div id="form-builder-dropdown">
		<div v-if="!loading">
			<select class="form-control" readonly>
				<option
					v-for="(option, index) in element.params.sub_options.sub_labels"
					:value="element.params.sub_options.sub_values[index]"
				>
					{{ option }}
				</option>
			</select>
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
