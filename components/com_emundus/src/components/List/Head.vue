<script>
import { Button, Icon } from '@emundus/ui';

export default {
	name: 'Head',
	components: {
		Button,
		Icon,
	},
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
				<Button v-if="primaryAction" @click="onClickAction(primaryAction)">
					<template #leading v-if="primaryAction.iconLabel">
						<Icon :name="primaryAction.iconLabel" />
					</template>
					{{ translate(primaryAction.label) }}
				</Button>
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
