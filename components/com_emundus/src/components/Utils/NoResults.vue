<script>
export default {
	name: 'NoResults',
	props: {
		message: {
			type: String,
			default: 'COM_EMUNDUS_NO_RESULT',
		},
	},
};
</script>

<template>
	<div id="empty-list" class="tw-text-center">
		<img
			src="@media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg"
			class="no-result-image"
			alt="empty-list"
		/>
		<p v-html="translate(message)" />
	</div>
</template>

<style scoped>
.no-result-image {
	width: 10vw;
	height: 10vw;
	margin: 0 auto;
	display: var(--no-results);
}
</style>
