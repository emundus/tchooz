<script>
export default {
	name: 'Head',
	props: {
		title: {
			type: String,
			default: null,
		},
		introduction: {
			type: String,
			default: null,
		},
		addAction: {
			type: Object,
			default: null,
		},
	},
	methods: {
		onClickAction(action) {
			this.$emit('action', action);
		},
	},
};
</script>

<template>
	<div class="head tw-py-6">
		<div class="tw-mb-6 tw-flex tw-items-center tw-justify-between">
			<h1>{{ translate(title) }}</h1>
			<a
				v-if="addAction"
				id="add-action-btn"
				class="tw-btn-primary tw-w-auto tw-cursor-pointer"
				@click="onClickAction(addAction)"
				>{{ translate(addAction.label) }}</a
			>
		</div>

		<p v-if="introduction" class="tw-text-neutral-700">
			{{ translate(introduction) }}
		</p>
	</div>
</template>

<style scoped>
.head {
	padding: 0 0 20px 0;
}

#onboarding_list .head {
	background: var(--em-coordinator-bg);
	z-index: 9;
}

.view-settings #onboarding_list .head {
	position: inherit;
}
</style>
