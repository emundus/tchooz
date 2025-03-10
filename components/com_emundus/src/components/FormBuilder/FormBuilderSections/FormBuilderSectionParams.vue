<template>
	<div v-if="params.length > 0">
		<div v-for="param in displayedParams" class="form-group tw-mb-4">
			<label>{{ translate(param.label) }}</label>

			<!-- DROPDOWN -->
			<div v-if="param.type === 'dropdown'">
				<select v-model="section.params[param.name]" class="tw-w-full">
					<option v-for="option in param.options" :value="option.value">{{ translate(option.label) }}</option>
				</select>
			</div>

			<!-- TEXTAREA -->
			<textarea v-else-if="param.type === 'textarea'" v-model="section.params[param.name]" class="tw-w-full"></textarea>

			<!-- INPUT (TEXT,NUMBER) -->
			<input
				v-else
				:type="param.type"
				v-model="section.params[param.name]"
				class="tw-w-full"
				:placeholder="translate(param.placeholder)"
			/>

			<!-- HELPTEXT -->
			<label v-if="param.helptext !== ''" style="font-size: small">{{ translate(param.helptext) }}</label>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'FormBuilderSectionParams',
	props: {
		section: {
			type: Object,
			required: false,
		},
		params: {
			type: Array,
			required: false,
		},
	},
	data: () => ({
		loading: false,
	}),
	setup() {
		const globalStore = useGlobalStore();
		return {
			globalStore,
		};
	},
	computed: {
		sysadmin: function () {
			return parseInt(this.globalStore.hasSysadminAccess);
		},
		displayedParams() {
			return this.params.filter((param) => {
				return (param.published && !param.sysadmin_only) || (this.sysadmin && param.sysadmin_only && param.published);
			});
		},
	},
};
</script>

<style scoped></style>
