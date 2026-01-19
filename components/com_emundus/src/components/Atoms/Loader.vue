<script>
export default {
	name: 'Loader',
	props: {
		type: {
			type: String,
			default: 'light',
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-h-full tw-items-center tw-justify-center">
		<div v-if="type === 'light'" class="em-loader" />
		<div v-else class="em-page-loader" />
	</div>
</template>

<style scoped></style>
