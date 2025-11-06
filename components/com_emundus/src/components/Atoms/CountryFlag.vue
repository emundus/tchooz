<script lang="ts">
export default {
	name: 'CountryFlag',
	props: {
		flagImage: {
			type: String,
			default: '',
		},
		flagAltText: {
			type: String,
			default: 'Flag',
		},
	},
};
</script>

<template>
	<img
		:src="'/images/emundus/flags/' + flagImage"
		:alt="flagAltText"
		class="tw-h-10 tw-w-10 tw-rounded-full tw-object-cover tw-shadow tw-outline tw-outline-1 tw-outline-neutral-400"
	/>
</template>

<style scoped></style>
