<script>
export default {
	name: 'Button',
	emits: ['click'],
	props: {
		variant: {
			type: String,
			default: 'primary',
			validator: (v) =>
				['primary', 'secondary', 'warning', 'info', 'orange', 'link', 'cancel', 'disabled', 'dashed'].includes(v),
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
	},
	computed: {
		variantClass() {
			switch (this.variant) {
				case 'secondary':
					return 'tw-btn-secondary';
				case 'warning':
					return 'tw-btn-warning';
				case 'info':
					return 'tw-btn-info';
				case 'dashed':
					return 'tw-btn-dashed';
				case 'link':
					return 'tw-underline tw-border-0';
				case 'cancel':
					return 'tw-btn-cancel';
				case 'orange':
					return 'tw-btn-orange';
				case 'disabled':
					return 'em-disabled-button';
			}
			return 'tw-btn-primary';
		},
		typeButton() {
			return this.type === 'submit' ? 'submit' : 'button';
		},
		widthClass() {
			switch (this.width) {
				case 'full':
					return 'tw-w-full';
				case 'fit':
				default:
					return 'tw-w-fit';
			}
		},
	},
};
</script>

<template>
	<button ref="btn" :type="typeButton" :class="[variantClass, widthClass]" :disabled="disabled" @click="$emit('click')">
		<span v-if="icon && iconPosition === 'left'" class="material-symbols-outlined tw-mr-1">{{ icon }}</span>
		<slot name="default" />
		<span v-if="icon && iconPosition === 'right'" class="material-symbols-outlined tw-ml-1">{{ icon }}</span>
	</button>
</template>

<style scoped></style>
