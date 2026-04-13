<script>
export default {
	name: 'Reference',
	props: {
		reference: {
			type: String,
			required: true,
		},
		shortReference: {
			type: String,
			required: false,
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-items-end tw-gap-1 tw-whitespace-nowrap">
		<label class="tw-mb-0">{{ reference }}</label>
		<span v-if="shortReference" class="tw-text-sm tw-text-neutral-500">#{{ shortReference }}</span>
	</div>
</template>

<style scoped></style>
