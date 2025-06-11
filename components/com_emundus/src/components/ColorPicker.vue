<template>
	<div :id="id" class="color-picker-container tw-relative">
		<div
			class="tw-h-[24px] tw-w-[24px] tw-cursor-pointer tw-rounded-full"
			:style="selectedSwatchStyle"
			@click="togglePopover"
		></div>
		<div :class="['vue-swatches__wrapper', 'position-' + position, 'tw-flex']" :style="wrapperStyle" v-show="isOpen">
			<div
				v-for="(swatchRow, index) in computedSwatches"
				:key="index"
				class="vue-swatches__row tw-h-[24px] tw-w-[24px] tw-cursor-pointer tw-rounded-full hover:tw-scale-110"
				:style="swatchStyle(swatchRow)"
				@click="updateSwatch(swatchRow)"
			></div>
		</div>
	</div>
</template>

<script>
import basicPreset from '@/assets/data/colorpicker/presets/basic';
import darkPreset from '@/assets/data/colorpicker/presets/dark';

export const extractPropertyFromPreset = (presetName) => {
	if (typeof presetName !== 'string') {
		return null;
	} else if (presetName === 'basic' && typeof basicPreset === 'object') {
		let root = document.querySelector(':root');
		let variables = getComputedStyle(root);
		let swatches = [];

		for (const swatch of basicPreset) {
			let color = variables.getPropertyValue('--em-' + swatch);
			swatches.push(color);
		}

		return swatches;
	} else if (presetName === 'dark' && typeof darkPreset === 'object') {
		let root = document.querySelector(':root');
		let variables = getComputedStyle(root);
		let swatches = [];

		for (const swatch of darkPreset) {
			let color = variables.getPropertyValue('--em-' + swatch);
			swatches.push(color);
		}

		return swatches;
	} else {
		return null;
	}
};

export default {
	name: 'ColorPicker',
	props: {
		swatches: {
			type: [Array, String],
			default: () => 'basic',
		},
		position: {
			type: String,
			default: 'top', // top, bottom, left, right
		},
		rowLength: {
			type: Number,
			default: 6,
		},
		modelValue: {
			type: String,
			default: '',
		},
		random: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['input', 'update:modelValue'],
	data: () => ({
		isOpen: false,
	}),
	mounted() {
		document.addEventListener('click', this.handleClickOutside);
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	},
	created() {
		// If random is true, then select a random color from the swatches
		if (this.random && this.modelValue === '') {
			const randomIndex = Math.floor(Math.random() * this.computedSwatches.length);
			this.updateSwatch(this.computedSwatches[randomIndex]);
		}
	},
	methods: {
		swatchStyle(swatch) {
			const baseStyles = {
				backgroundColor: swatch !== '' ? swatch : '#FFFFFF',
			};

			return {
				...baseStyles,
			};
		},
		updateSwatch(swatch) {
			this.$emit('update:modelValue', swatch);
			this.$emit('input', swatch);
			this.isOpen = false;
		},
		togglePopover() {
			const otherColorPickers = document.querySelectorAll('.color-picker-container');
			otherColorPickers.forEach((colorPicker) => {
				colorPicker.querySelector('.vue-swatches__wrapper').style.display = 'none';
			});

			this.isOpen = !this.isOpen;
		},
		handleClickOutside(event) {
			const clickedElement = event.target;

			// if clicked element is not inside this component then close popover
			if (!clickedElement.closest('#' + this.$attrs.id)) {
				this.isOpen = false;
			}
		},
	},
	computed: {
		computedSwatches() {
			if (this.swatches instanceof Array) return this.swatches;

			if (typeof this.swatches === 'string') {
				return extractPropertyFromPreset(this.swatches);
			} else {
				return [];
			}
		},

		selectedSwatchStyle() {
			return {
				backgroundColor: this.modelValue !== '' ? this.modelValue : '#FFFFFF',
			};
		},

		wrapperStyle() {
			switch (this.position) {
				case 'top':
					return { bottom: '35px' };
				case 'bottom':
					return { top: '35px' };
				case 'left':
					return { right: '0px', top: '35px' };
				case 'right':
					return { left: '35px' };
				default:
					return { bottom: '35px' };
			}
		},
	},
};
</script>

<style scoped></style>
