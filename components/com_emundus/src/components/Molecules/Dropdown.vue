<script>
import { Button, Icon, DropdownMenu } from '@emundus/ui';

export default {
	name: 'Dropdown',
	components: {
		Button,
		Icon,
		DropdownMenu,
	},
	props: {
		// Trigger button configuration (mirrors @emundus/ui Button props)
		label: {
			type: String,
			default: '',
		},
		variant: {
			type: String,
			default: 'primary',
		},
		emphasis: {
			type: String,
			default: 'main',
		},
		size: {
			type: String,
			default: 'md',
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		loading: {
			type: Boolean,
			default: false,
		},
		// Trailing icon displayed on the trigger (set to '' to hide)
		icon: {
			type: String,
			default: 'keyboard_arrow_down',
		},
		// Horizontal alignment of the menu relative to the trigger
		align: {
			type: String,
			default: 'left',
			validator: (v) => ['left', 'right'].includes(v),
		},
		// Vertical direction the menu opens toward relative to the trigger
		direction: {
			type: String,
			default: 'down',
			validator: (v) => ['down', 'up'].includes(v),
		},
		ariaLabel: {
			type: String,
			default: undefined,
		},
		maxHeight: {
			type: [String, Number],
			default: undefined,
		},
		// Close the menu automatically when an item inside it is clicked
		closeOnClick: {
			type: Boolean,
			default: true,
		},
	},
	emits: ['open', 'close'],
	data() {
		return {
			open: false,
			// Vertical offset (px) applied to the menu; computed from its rendered height when opening upward
			menuOffset: 0,
		};
	},
	computed: {
		// Gap between the trigger and the menu, matching the tw-mt-1 / tw-mb-1 spacing (0.25rem)
		menuGap() {
			return 4;
		},
		menuStyle() {
			if (this.direction !== 'up') {
				return {};
			}
			return { top: `-${this.menuOffset + this.menuGap}px` };
		},
	},
	methods: {
		toggle() {
			if (this.disabled) {
				return;
			}
			this.open ? this.close() : this.show();
		},
		show() {
			this.open = true;
			this.$emit('open');
			if (this.direction === 'up') {
				this.$nextTick(() => {
					this.menuOffset = this.$refs.menu ? this.$refs.menu.offsetHeight : 0;
				});
			}
		},
		close() {
			if (!this.open) {
				return;
			}
			this.open = false;
			this.$emit('close');
		},
		onMenuClick() {
			if (this.closeOnClick) {
				this.close();
			}
		},
	},
};
</script>

<template>
	<div class="tw-relative tw-inline-block" v-click-outside="{ handler: close }">
		<Button
			:variant="variant"
			:emphasis="emphasis"
			:size="size"
			:disabled="disabled"
			:loading="loading"
			@click="toggle"
		>
			<template #leading>
				<slot name="leading" />
			</template>
			<slot name="trigger">{{ label }}</slot>
			<template #trailing>
				<slot name="trailing">
					<Icon v-if="icon" :name="icon" />
				</slot>
			</template>
		</Button>

		<transition name="fade">
			<div
				ref="menu"
				v-show="open"
				class="tw-absolute tw-z-20 tw-w-max tw-min-w-full"
				:class="[align === 'right' ? 'tw-right-0' : 'tw-left-0', direction === 'up' ? '' : 'tw-top-full tw-mt-1']"
				:style="menuStyle"
				@click="onMenuClick"
			>
				<DropdownMenu :aria-label="ariaLabel" :max-height="maxHeight">
					<slot />
				</DropdownMenu>
			</div>
		</transition>
	</div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
	transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
	opacity: 0;
}
</style>
