<script>
export default {
	name: 'FormatSelector',

	props: {
		modelValue: {
			type: String,
			default: null,
		},
		formats: {
			type: Array,
			required: true,
		},
	},

	methods: {
		selectFormat(value) {
			this.$emit('update:modelValue', value);
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-items-center tw-justify-center tw-gap-8">
		<label
			v-for="format in formats"
			:key="format.value"
			class="tw-flex tw-cursor-pointer tw-flex-col tw-items-center tw-justify-center tw-gap-5 tw-rounded-coordinator tw-border tw-px-6 tw-py-3"
			:class="[
				modelValue === format.value ? 'tw-border-main-300 tw-bg-main-100' : 'tw-border-transparent tw-bg-neutral-200',
			]"
			@click="selectFormat(format.value)"
		>
			<img :src="format.image" alt="" class="tw-h-12 tw-w-12" />
			<span>{{ this.translate(format.label) }}</span>
		</label>
	</div>
</template>
