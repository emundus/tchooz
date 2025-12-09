<script>
export default {
	name: 'Stepper',
	props: {
		steps: {
			type: Array,
			default: [],
		},
		direction: {
			type: String,
			default: 'horizontal',
		},
		reloadWidth: {
			type: Number | String,
			default: 0,
		},
	},
	emits: ['step-clicked'],
	data() {
		return {
			calculatedWidth: '0px',
		};
	},
	mounted() {
		this.computeWidth();
		window.addEventListener('resize', this.computeWidth);
	},
	beforeUnmount() {
		window.removeEventListener('resize', this.computeWidth);
	},
	methods: {
		stepClass(step) {
			if ((step.active && step.completed) || step.active) {
				return 'tw-bg-blue-600 tw-outline-blue-600 tw-text-white';
			} else if (step.completed) {
				return 'tw-bg-green-600 tw-outline-green-600 tw-text-white tw-cursor-pointer';
			} else {
				return 'tw-bg-white tw-outline-gray-400 tw-text-gray-400';
			}
		},

		bgClass(step) {
			if ((step.active && step.completed) || step.active) {
				return 'tw-bg-blue-600';
			} else if (step.completed) {
				return 'tw-bg-green-600';
			} else {
				return 'tw-bg-gray-400';
			}
		},
		computeWidth() {
			this.$nextTick(() => {
				const container = this.$el.querySelector('.step-container');
				const index = this.$el.querySelector('.step-index');

				if (!container || !index) return;

				const containerWidth = container.offsetWidth;
				const stepIndex = index.offsetWidth;

				if (containerWidth === 0 || stepIndex === 0) {
					// Create a resize observer to wait for the element to have a width
					const resizeObserver = new ResizeObserver(() => {
						const newContainerWidth = container.offsetWidth;
						const newStepIndex = index.offsetWidth;

						if (newContainerWidth > 0 && newStepIndex > 0) {
							this.calculatedWidth = newContainerWidth / 2 - (newStepIndex / 2 + 2) + 'px';
							resizeObserver.disconnect();
						}
					});

					resizeObserver.observe(container);
				}

				this.calculatedWidth = containerWidth / 2 - (stepIndex / 2 + 2) + 'px';
			});
		},
	},
	computed: {
		gridCols() {
			return `tw-grid-cols-${this.steps.length}`;
		},
	},
	watch: {
		steps() {
			this.computeWidth();
		},
		reloadWidth() {
			this.computeWidth();
		},
	},
};
</script>

<template>
	<div class="tw-grid" :class="gridCols">
		<div
			v-for="(step, index) in steps"
			class="step-container tw-flex tw-flex-col tw-items-center tw-justify-center tw-gap-3"
		>
			<div class="tw-relative tw-flex tw-items-center">
				<div
					v-if="index !== 0"
					class="tw-absolute tw-right-8 tw-mr-[2px] tw-h-[2px]"
					:style="{ width: calculatedWidth }"
					:class="bgClass(step)"
				></div>
				<span
					v-if="step.completed"
					class="material-symbols-outlined tw-absolute tw-rounded-full tw-bg-white tw-text-green-600"
					style="right: -5px; top: -10px"
					>check_circle</span
				>
				<div
					class="step-index tw-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-full tw-outline tw-outline-2 tw-outline-offset-2"
					:class="stepClass(step)"
					@click="$emit('step-clicked', index)"
				>
					{{ index + 1 }}
				</div>
				<div
					v-if="index + 1 !== steps.length"
					class="tw-absolute tw-left-8 tw-ml-[2px] tw-h-[2px]"
					:style="{ width: calculatedWidth }"
					:class="bgClass(step)"
				></div>
			</div>
			<div>
				{{ this.translate(step.label) }}
			</div>
		</div>
	</div>
</template>

<style scoped></style>
