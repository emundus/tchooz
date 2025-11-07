<script>
import workflowService from '@/services/workflow.js';

export default {
	name: 'WorkflowStepsTimeline',
	props: {
		fnum: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			steps: [],
			activeStepIndex: 0,
		};
	},
	created() {
		this.getStepsFromFnum();
	},
	mounted() {
		window.addEventListener('resize', this.calculateLinesWidth);
	},
	beforeUnmount() {
		window.removeEventListener('resize', this.calculateLinesWidth);
	},
	methods: {
		async getStepsFromFnum() {
			workflowService
				.getStepsFromFnum(this.fnum)
				.then((response) => {
					this.steps = response.data;
					this.calculateLinesWidth();

					this.$nextTick(() => {
						this.calculateLinesWidth();
					});
				})
				.catch((error) => {
					console.log(error);
				});
		},
		stepDisplayedDate(step) {
			if (step.dates.infinite) {
				return this.translate('COM_EMUNDUS_ONBOARD_INFINITE');
			} else if (
				step.dates.relative_date == 1 &&
				(step.dates.relative_date_value === '' ||
					step.dates.relative_date_value == 0 ||
					step.dates.relative_date_value == null)
			) {
				return (
					this.translate('COM_EMUNDUS_ONBOARD_DURATION') +
					' : ' +
					step.dates.relative_end_date_value +
					' ' +
					this.translate(
						'COM_EMUNDUS_' +
							step.dates.relative_end_date_unit.toUpperCase() +
							(step.dates.relative_end_date_value > 1 ? 'S' : ''),
					)
				);
			}

			return (
				this.translate('COM_EMUNDUS_ONBOARD_FROM') +
				' ' +
				step.dates.start_date +
				' ' +
				this.translate('COM_EMUNDUS_ONBOARD_TO') +
				' ' +
				step.dates.end_date
			);
		},
		onClickStep(step, index) {
			if (index === this.activeStepIndex) {
				return;
			}

			this.activeStepIndex = index;

			const stepSelectedEvent = new CustomEvent('stepSelected', {
				detail: {
					step: step,
				},
			});
			window.dispatchEvent(stepSelectedEvent);

			this.$emit('stepSelected', step);
		},
		calculateLinesWidth() {
			const nbSteps = this.steps.length;

			for (let i = 0; i < nbSteps - 1; i++) {
				const lineElement = document.getElementById('step-' + i + '-line');

				if (lineElement) {
					// distance between the center of the current step and the center of the next step circle item
					const currentStepCircle = document.getElementById('step-' + i + '-circle');
					const nextStepCircle = document.getElementById('step-' + (i + 1) + '-circle');
					const distance = nextStepCircle.getBoundingClientRect().left - currentStepCircle.getBoundingClientRect().left;

					lineElement.style.width = distance - currentStepCircle.offsetWidth - 7 + 'px';
					lineElement.style.right = '-' + (distance - currentStepCircle.offsetWidth - 4) + 'px';
				}
			}
		},
	},
	computed: {
		displayedSteps() {
			return this.steps.filter((step) => step.state == 1);
		},
		futureSteps() {
			// the steps that are relative and not yet started are always future steps
			let futureSteps = this.displayedSteps.filter(
				(step) =>
					step.dates.relative_date == 1 &&
					(step.dates.relative_date_value === '' ||
						step.dates.relative_date_value == 0 ||
						step.dates.relative_date_value == null),
			);

			// the steps that start in the future who that are after a step not yet completed
			let futureStepsFromCurrent = this.displayedSteps.filter((step) => {
				return new Date(step.dates.start_date_raw) > new Date();
			});
			futureSteps = futureSteps.concat(futureStepsFromCurrent);

			// in current steps, check the last completed step
			let currentSteps = this.displayedSteps.filter((step) => new Date(step.dates.start_date_raw) <= new Date());
			let lastCompletedStepIndex = -1;
			for (let i = currentSteps.length - 1; i >= 0; i--) {
				if (currentSteps[i].completed) {
					lastCompletedStepIndex = i;
					break;
				}
			}

			if (lastCompletedStepIndex >= 0) {
				// add all current steps after the last completed step to futureSteps
				futureSteps = futureSteps.concat(currentSteps.slice(lastCompletedStepIndex + 1));
			}

			return futureSteps;
		},
	},
};
</script>

<template>
	<div
		id="workflow-steps-timeline"
		class="tw-flex tw-border-separate tw-flex-row tw-justify-evenly tw-gap-8 tw-rounded-coordinator-cards tw-bg-neutral-0 tw-p-4 tw-shadow-card"
	>
		<div
			v-for="(step, index) in displayedSteps"
			:key="step.id"
			class="step-item tw-cursor-pointer"
			@click="onClickStep(step, index)"
		>
			<div class="tw-flex tw-flex-col tw-items-center tw-gap-2">
				<div
					:id="'step-' + index + '-circle'"
					class="tw-relative tw-flex tw-h-8 tw-w-8 tw-items-center tw-justify-center tw-rounded-full tw-text-neutral-0 tw-outline tw-outline-[2px] tw-outline-offset-2 tw-transition"
					:class="{
						'tw-bg-profile-full tw-outline-profile-full': index === activeStepIndex,
						'tw-outline-hidden tw-bg-neutral-700': index !== activeStepIndex && !futureSteps.includes(step),
						'tw-outline-hidden tw-bg-neutral-300': index !== activeStepIndex && futureSteps.includes(step),
					}"
				>
					<span :class="{ 'tw-text-neutral-700': index !== activeStepIndex && futureSteps.includes(step) }">
						{{ index + 1 }}
					</span>
					<span
						v-if="step.completed"
						class="material-symbols-outlined tw-color-profile-full tw-absolute tw-right-[-4px] tw-top-[-4px] tw-rounded-full tw-bg-white !tw-text-[14px]"
					>
						check_circle
					</span>
					<span
						v-if="index !== displayedSteps.length"
						:id="'step-' + index + '-line'"
						class="tw-absolute tw-h-[2px] tw-bg-neutral-700"
					></span>
				</div>
				<p
					class="tw-text-center"
					:class="{
						'tw-text-profile-full': index === activeStepIndex,
						'tw-text-neutral-700': index !== activeStepIndex && !futureSteps.includes(step),
						'tw-text-neutral-600': index !== activeStepIndex && futureSteps.includes(step),
					}"
				>
					<strong>{{ step.label }}</strong>
				</p>
				<p
					:id="'step-' + step.id + '-dates'"
					v-if="!step.dates.infinite"
					class="tw-text-center tw-text-sm"
					:class="{
						'tw-text-profile-full': index === activeStepIndex,
						'tw-text-neutral-700': index !== activeStepIndex && !futureSteps.includes(step),
						'tw-text-neutral-600': index !== activeStepIndex && futureSteps.includes(step),
					}"
				>
					{{ stepDisplayedDate(step) }}
				</p>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
