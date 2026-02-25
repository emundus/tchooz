<script>
import Button from '@/components/Atoms/Button.vue';
import Loader from '@/components/Atoms/Loader.vue';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'FormBuilderElementOrderlist',
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
};
</script>

<template>
	<div id="form-builder-orderlist">
		<div v-if="!loading">
			<ol>
				<li v-for="(option, index) in element.params.sub_options.sub_labels">
					<span>{{ option }}</span>
				</li>
			</ol>
		</div>
		<Loader v-else />
	</div>
</template>

<style scoped></style>
