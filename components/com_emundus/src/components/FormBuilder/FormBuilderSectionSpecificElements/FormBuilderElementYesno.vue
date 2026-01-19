<template>
	<div id="form-builder-yesno">
		<div v-if="!loading" class="tw-flex tw-items-center tw-gap-3">
			<input
				type="radio"
				:name="element.name + '[]'"
				:id="element.name + '0'"
				class="form-control tw-hidden"
				:value="0"
				checked
				readonly
			/>
			<label class="tw-mb-0" :for="element.name + '0'">{{ translate('JNO') }}</label>
			<input
				type="radio"
				:name="element.name + '[]'"
				:id="element.name + '1'"
				class="form-control tw-hidden"
				:value="1"
				readonly
			/>
			<label class="tw-mb-0" :for="element.name + '1'">{{ translate('JYES') }}</label>
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
#form-builder-yesno input[value='0']:checked + label {
	color: var(--neutral-0);
	border: 1px solid var(--red-700);
	background: var(--red-700);
}
#form-builder-yesno input[value='0'] + label {
	align-items: center;
	padding: var(--p-12);
	box-shadow: none;
	cursor: pointer;
	border: 1px solid var(--neutral-500);
	background: var(--neutral-0);
	border-radius: var(--em-form-yesno-br) !important;
	width: 200px;
	display: flex;
	justify-content: center;
	color: var(--red-700);
	height: var(--em-form-height);
}

#form-builder-yesno input[value='1']:checked + label {
	color: var(--neutral-0);
	border: 1px solid var(--em-green-2);
	background: var(--em-green-2);
}
#form-builder-yesno input[value='1'] + label {
	align-items: center;
	box-shadow: none;
	cursor: pointer;
	border: 1px solid var(--neutral-500);
	background: var(--neutral-0);
	color: var(--em-green-2);
	border-radius: var(--em-form-yesno-br) !important;
	width: 200px;
	display: flex;
	justify-content: center;
	height: var(--em-form-height);
}
</style>
