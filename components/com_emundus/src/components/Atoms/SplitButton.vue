<script>
import Button from '@/components/Atoms/Button.vue';
import Popover from '@/components/Popover.vue';

export default {
	name: 'SplitButton',
	components: { Popover, Button },
	emits: ['click'],
	props: {
		label: {
			type: String,
			required: true,
		},
		variant: {
			type: String,
			default: 'primary',
			validator: (v) => ['primary', 'secondary', 'link', 'cancel', 'disabled'].includes(v),
		},
		type: {
			type: String,
			default: 'button',
			validator: (v) => ['button', 'submit'].includes(v),
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		icon: {
			type: String,
			default: null,
		},
		iconPosition: {
			type: String,
			default: 'left',
			validator: (v) => ['left', 'right'].includes(v),
		},
		width: {
			type: String,
			default: 'fit',
			validator: (v) => ['fit', 'full'].includes(v),
		},
		position: {
			type: String,
			default: 'bottom-left',
		},
	},
	computed: {
		disabledClass() {
			return this.disabled ? 'tw-opacity-60 tw-cursor-not-allowed' : '';
		},
	},
};
</script>

<template>
	<div class="tw-inline-flex tw-items-stretch">
		<!-- Main action -->
		<Button :variant="variant" :disabled="disabled" class="tw-rounded-r-none" @click="$emit('click')">
			{{ label }}
		</Button>

		<!-- Dropdown -->
		<div
			class="tw-flex tw-items-center tw-rounded-l-none tw-rounded-r-applicant tw-bg-profile-full"
			:class="disabledClass"
		>
			<popover
				:button="''"
				:icon="'keyboard_arrow_down'"
				iconClass="tw-text-white hover:tw-bg-white hover:tw-text-profile-full tw-border tw-border-profile-full !tw-rounded-l-none !tw-rounded-r-applicant"
				:position="position"
				:disabled="disabled"
			>
				<slot />
			</popover>
		</div>
	</div>
</template>

<style scoped></style>
