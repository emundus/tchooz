<template>
	<div :id="id" class="color-picker-container tw-relative tw-mt-2">
		<template v-if="variant === 'field'">
			<label v-if="label" class="tw-mb-2 tw-block tw-text-[16px] tw-font-medium tw-leading-[1.5] tw-text-[#1a1a27]">
				{{ label }}
			</label>
			<div class="tw-flex tw-items-center tw-gap-2">
				<button
					type="button"
					class="tw-flex tw-h-10 tw-min-w-[40px] tw-items-center tw-justify-center tw-rounded-lg tw-border tw-border-solid tw-p-2 tw-transition-opacity hover:tw-opacity-90"
					:style="triggerButtonStyle"
					@click="togglePopover"
				>
					<span class="material-symbols-outlined tw-text-[20px] tw-text-white">palette</span>
				</button>
				<div
					class="tw-flex tw-h-10 tw-flex-1 tw-cursor-pointer tw-items-center tw-gap-1 tw-rounded-lg tw-border tw-border-solid tw-border-[#e4e4ed] tw-bg-white tw-px-3 tw-py-2"
					@click="togglePopover"
				>
					<p class="tw-m-0 tw-flex-1 tw-text-[16px] tw-font-medium tw-leading-[1.5] tw-text-[#1a1a27]">
						{{ displayHex }}
					</p>
					<p v-if="displayName" class="tw-m-0 tw-text-[16px] tw-font-medium tw-leading-[1.5] tw-text-[#b9b9cb]">
						{{ displayName }}
					</p>
				</div>
			</div>
		</template>
		<template v-else>
			<div
				class="tw-h-[24px] tw-w-[24px] tw-cursor-pointer tw-rounded-full tw-border"
				:style="selectedSwatchStyle"
				@click="togglePopover"
			></div>
		</template>
		<div
			:class="[
				'vue-swatches__wrapper',
				'position-' + effectivePosition,
				'color-picker-popup-card',
				'tw-absolute',
				'tw-z-30',
				'tw-flex',
				'tw-w-[252px]',
				'tw-flex-col',
				'tw-gap-6',
				'tw-rounded-lg',
				'tw-border',
				'tw-border-solid',
				'tw-border-[#e4e4ed]',
				'tw-bg-white',
				'tw-p-6',
			]"
			:style="wrapperStyle"
			v-show="isOpen"
		>
			<div class="tw-flex tw-w-[204px] tw-max-w-[232px] tw-flex-wrap tw-items-center tw-gap-3">
				<button
					v-for="(swatchRow, index) in computedSwatches"
					:key="index"
					type="button"
					:class="[
						'vue-swatches__row tw-h-[24px] tw-w-[24px] tw-cursor-pointer tw-rounded-full tw-border-0 tw-p-0 tw-transition-transform hover:tw-scale-110',
						isSwatchSelected(swatchRow) ? 'tw-ring-2 tw-ring-[#1a1a27] tw-ring-offset-2' : '',
					]"
					:style="swatchStyle(swatchRow)"
					@click="updateSwatch(swatchRow)"
				></button>
			</div>
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

const FALLBACK_TRIGGER_COLOR = '#1a1a27';

export default {
	name: 'ColorPicker',
	props: {
		swatches: {
			type: [Array, String],
			default: () => 'basic',
		},
		position: {
			type: String,
			default: '', // top, bottom, left, right — empty means "auto based on variant"
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
		variant: {
			type: String,
			default: 'compact', // 'compact' (legacy small swatch) | 'field' (Figma labelled field)
		},
		label: {
			type: String,
			default: '',
		},
		colorNames: {
			type: Object,
			default: () => ({}),
		},
		placeholder: {
			type: String,
			default: '',
		},
	},
	emits: ['input', 'update:modelValue'],
	data() {
		return {
			isOpen: false,
			hexInputValue: '',
		};
	},
	watch: {
		modelValue: {
			immediate: true,
			handler(value) {
				this.hexInputValue = value ? String(value).toUpperCase() : '';
			},
		},
	},
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
		emitColor(value) {
			this.$emit('update:modelValue', value);
			this.$emit('input', value);
		},
		updateSwatch(swatch) {
			this.emitColor(swatch);
			this.isOpen = false;
		},
		isSwatchSelected(swatch) {
			if (!this.modelValue || !swatch) return false;
			return String(swatch).trim().toLowerCase() === String(this.modelValue).trim().toLowerCase();
		},
		onHexInputInput(event) {
			this.hexInputValue = event.target.value;
		},
		commitHexInput() {
			const raw = String(this.hexInputValue || '').trim();
			const match = /^#?([0-9a-fA-F]{6})$/.exec(raw);
			if (match) {
				const normalized = ('#' + match[1]).toUpperCase();
				this.hexInputValue = normalized;
				this.emitColor(normalized);
			} else {
				this.hexInputValue = this.modelValue ? String(this.modelValue).toUpperCase() : '';
			}
		},
		togglePopover() {
			const otherColorPickers = document.querySelectorAll('.color-picker-container');
			otherColorPickers.forEach((colorPicker) => {
				const wrapper = colorPicker.querySelector('.vue-swatches__wrapper');
				if (wrapper) wrapper.style.display = 'none';
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
		lookupColorName(value) {
			if (!value || !this.colorNames) return '';
			if (this.colorNames[value]) return this.colorNames[value];
			const normalized = String(value).trim().toLowerCase();
			const match = Object.keys(this.colorNames).find((key) => key.toLowerCase() === normalized);
			return match ? this.colorNames[match] : '';
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

		triggerButtonStyle() {
			const color = this.modelValue !== '' ? this.modelValue : FALLBACK_TRIGGER_COLOR;
			return {
				backgroundColor: color,
				borderColor: color,
			};
		},

		displayHex() {
			if (!this.modelValue) return this.placeholder;
			const value = String(this.modelValue).trim();
			return value.startsWith('#') ? value.toUpperCase() : value;
		},

		displayName() {
			return this.lookupColorName(this.modelValue);
		},

		effectivePosition() {
			if (this.position) return this.position;
			return this.variant === 'field' ? 'bottom' : 'top';
		},

		wrapperStyle() {
			switch (this.effectivePosition) {
				case 'top':
					return { bottom: 'calc(100% + 8px)', left: '0' };
				case 'bottom':
					return { top: 'calc(100% + 8px)', left: '0' };
				case 'left':
					return { right: 'calc(100% + 8px)', top: '0' };
				case 'right':
					return { left: 'calc(100% + 8px)', top: '0' };
				default:
					return { bottom: 'calc(100% + 8px)', left: '0' };
			}
		},
	},
};
</script>

<style scoped>
.color-picker-popup-card {
	box-shadow:
		0 12px 17px 0 rgba(5, 47, 55, 0.07),
		0 5px 22px 0 rgba(5, 47, 55, 0.06),
		0 7px 8px 0 rgba(5, 47, 55, 0.02);
}
</style>
