<template>
	<div id="form-builder-databasejoin">
		<div v-if="!loading">
			<select class="form-control" readonly>
				<option v-if="element.params.database_join_show_please_select == 1" value="0">
					{{ element.please_select_label }}
				</option>
				<option v-else :value="element.example_data.value">{{ element.example_data.label }}</option>
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

<style>
.fabrikgrid_radio {
	margin-bottom: var(--em-spacing-1);
	margin-left: 0;
	display: flex;
	align-items: center;
	padding: var(--em-form-radio-padding);
	border: var(--em-form-radio-bw) solid var(--em-form-radio-bc);
	border-radius: var(--em-form-radio-br);
	width: -moz-fit-content;
	width: fit-content;
	height: auto;
}

.fabrikgrid_radio input {
	accent-color: var(--em-form-radio-color-checked);
	width: 22px;
	height: 22px;
	margin-right: var(--em-form-radio-margin-right);
	border-radius: 99px !important;
}
</style>
