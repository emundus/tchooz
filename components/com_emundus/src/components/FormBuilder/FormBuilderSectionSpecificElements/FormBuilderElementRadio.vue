<template>
	<div id="form-builder-radio">
		<div v-if="!loading" class="tw-grid tw-grid-cols-1 md:tw-grid-cols-2" :class="gridClasses">
			<div
				v-for="(option, index) in element.params.sub_options.sub_labels"
				class="fabrikgrid_radio"
				:class="radioClass"
			>
				<input
					type="radio"
					:name="'input_' + element.name"
					:id="element.params.sub_options.sub_values[index]"
					class="form-control"
					:value="element.params.sub_options.sub_values[index]"
					readonly
				/>
				<label class="tw-mb-0" :for="element.params.sub_options.sub_values[index]">{{ option }}</label>
			</div>
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
	computed: {
		gridClasses() {
			if (this.element.params.options_per_row == 1) {
				return 'lg:tw-grid-cols-1 tw-gap-2';
			} else if (this.element.params.options_per_row == 2) {
				return 'lg:tw-grid-cols-2 tw-gap-4';
			} else if (this.element.params.options_per_row == 3) {
				return 'lg:tw-grid-cols-3 tw-gap-4';
			}
		},
		radioClass() {
			if (this.element.params.options_per_row != 1) {
				return 'tw-w-full';
			}
		},
	},
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
