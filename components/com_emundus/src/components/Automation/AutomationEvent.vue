<script>
export default {
	name: 'AutomationEvent',
	props: {
		event: {
			type: Object,
			required: true,
		},
	},
};
</script>

<template>
	<div
		class="automation-event tw-mt-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<p>
			<strong>{{ event.label }}</strong>
		</p>
		<p v-if="event.description.length > 0">{{ event.description }}</p>
	</div>
</template>

<style scoped></style>
