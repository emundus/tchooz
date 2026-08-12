<script>
export default {
	name: 'Button',
	emits: ['click'],
	props: {
		variant: {
			type: String,
			default: 'primary',
			validator: (v) =>
				[
					'primary',
					'secondary',
					'link',
					'cancel',
					'disabled',
					'dashed',
					'success',
					'warning',
					'info',
					'danger',
					'orange',
				].includes(v),
		},
		type: {
			type: String,
			default: 'button',
			validator: (v) => ['button', 'submit'].includes(v),
		},
		isActivationButton: {
			type: Boolean,
			default: false,
		},
		defaultActive: {
			type: Boolean,
			default: false,
		},
		active: {
			type: Boolean,
			default: null,
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
	data() {
		return {
			isActive: false,
		};
	},
	created() {
		this.isActive = this.$props.active !== null ? this.$props.active : this.$props.defaultActive;
	},
	watch: {
		active(val) {
			if (val !== null) {
				this.isActive = val;
			}
		},
	},
	methods: {
		clickAction() {
			if (this.$props.isActivationButton) {
				const next = !this.isActive;
				if (this.$props.active === null) {
					this.isActive = next;
				}
				this.$emit('click', next);
			} else {
				this.$emit('click');
			}
		},
	},
	computed: {
		variantClass() {
			let activeClass = '';
			if (this.$props.isActivationButton) {
				activeClass = '-active';
			}
			switch (this.variant) {
				case 'secondary':
					return `tw-btn${activeClass}-secondary`;
				case 'dashed':
					return `tw-btn${activeClass}-dashed`;
				case 'link':
					return 'tw-underline tw-border-0';
				case 'cancel':
					return `tw-btn${activeClass}-cancel`;
				case 'disabled':
					return 'em-disabled-button';
				case 'success':
					return `tw-btn${activeClass}-success`;
				case 'warning':
					return `tw-btn${activeClass}-warning`;
				case 'info':
					return `tw-btn${activeClass}-info`;
				case 'orange':
					return `tw-btn${activeClass}-orange`;
				case 'danger':
					return `tw-btn${activeClass}-danger`;
			}
			return `tw-btn${activeClass}-primary`;
		},
		activeClass() {
			if (this.$props.isActivationButton) {
				let classActive = 'tw-btn-active';
				if (this.isActive) {
					classActive += ' tw-is-active';
				}

				return classActive;
			}
			return '';
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
	<button
		ref="btn"
		:type="typeButton"
		:class="[activeClass, variantClass, widthClass]"
		:disabled="disabled"
		@click="clickAction()"
	>
		<span v-if="icon && iconPosition === 'left'" class="material-symbols-outlined tw-mr-1">{{ icon }}</span>
		<slot name="default" />
		<span v-if="icon && iconPosition === 'right'" class="material-symbols-outlined tw-ml-1">{{ icon }}</span>
	</button>
</template>

<style scoped></style>
