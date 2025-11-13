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
		primaryAction: {
			type: Object,
			default: null,
		},
		secondaryAction: {
			type: Object,
			default: null,
		},
	},
	methods: {
		onClickAction(action) {
			this.$emit('action', action);
		},
	},
	computed: {
		isHtmlIntro() {
			return this.introduction && this.translate(this.introduction).includes('</');
		},
	},
};
</script>

<template>
	<div class="head tw-py-6">
		<div class="tw-mb-6 tw-flex tw-items-center tw-justify-between">
			<h1>{{ translate(title) }}</h1>
			<div class="tw-flex tw-flex-row tw-items-center tw-gap-2">
				<a
					v-if="secondaryAction"
					id="secondary-action-btn"
					class="tw-btn-secondary tw-mr-2 tw-w-auto tw-cursor-pointer tw-rounded-coordinator"
					@click="onClickAction(secondaryAction)"
				>
					{{ translate(secondaryAction.label) }}
				</a>
				<a
					v-if="primaryAction"
					id="primary-action-btn"
					class="tw-btn-primary tw-w-auto tw-cursor-pointer tw-rounded-coordinator"
					@click="onClickAction(primaryAction)"
					>{{ translate(primaryAction.label) }}</a
				>
			</div>
		</div>

		<div v-if="isHtmlIntro" v-html="translate(introduction)" class="tw-text-neutral-700"></div>

		<p v-else-if="introduction" class="tw-text-neutral-700">
			{{ translate(introduction) }}
		</p>
	</div>
</template>

<style scoped>
.head {
	padding: 0 0 20px 0;
}

#onboarding_list .head {
	background: transparent;
	z-index: 9;
}

.view-settings #onboarding_list .head {
	position: inherit;
}
</style>
