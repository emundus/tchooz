<template>
	<div id="form-builder-booking">
		<div
			class="tw-relative tw-flex tw-w-full tw-items-center tw-rounded-coordinator tw-border tw-border-neutral-300 tw-p-4"
		>
			<div class="tw-flex tw-w-full tw-items-start tw-gap-1">
				<button
					class="tw-rounded-coordinator tw-border-0 tw-bg-transparent tw-p-2 hover:tw-bg-neutral-100"
					type="button"
					disabled=""
					style="cursor: not-allowed; opacity: 0.2"
				>
					<span class="material-symbols-outlined">chevron_left</span>
				</button>

				<div class="tw-flex tw-w-auto tw-flex-1 tw-flex-row tw-items-stretch tw-justify-center tw-gap-4">
					<div class="tw-flex tw-flex-col tw-items-center tw-gap-2">
						<p class="tw-text-center tw-text-lg">{{ currentDay }}</p>
						<p class="tw-text-center tw-text-sm tw-text-neutral-500">
							{{ currentDateMonth }}
						</p>

						<div class="tw-mt-4 tw-grid tw-w-full tw-grid-cols-3 tw-gap-2">
							<button
								type="button"
								class="tw-flex tw-w-full tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-coordinator tw-border tw-border-transparent tw-bg-neutral-200 tw-px-4 tw-py-2 hover:tw-bg-neutral-300"
							>
								09:00
							</button>
							<button
								type="button"
								class="tw-flex tw-w-full tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-coordinator tw-border tw-border-transparent tw-bg-neutral-200 tw-px-4 tw-py-2 hover:tw-bg-neutral-300"
							>
								09:20
							</button>
							<button
								type="button"
								class="tw-flex tw-w-full tw-cursor-pointer tw-items-center tw-justify-center tw-rounded-coordinator tw-border tw-border-transparent tw-bg-neutral-200 tw-px-4 tw-py-2 hover:tw-bg-neutral-300"
							>
								09:40
							</button>
						</div>
					</div>
				</div>
				<button
					class="tw-rounded-coordinator tw-border-0 tw-bg-transparent tw-p-2 hover:tw-bg-neutral-100"
					type="button"
					disabled=""
					style="cursor: not-allowed; opacity: 0.2"
				>
					<span class="material-symbols-outlined">chevron_right</span>
				</button>
			</div>
		</div>
	</div>
</template>

<script>
import { useGlobalStore } from '@/stores/global.js';

export default {
	props: {
		element: {
			type: Object,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: false,

			date: new Date(),
			locale: 'fr-FR',
		};
	},
	mounted() {
		this.locale = useGlobalStore().getShortLang;
	},
	computed: {
		currentDay() {
			// First letter in uppercase
			return (
				this.date.toLocaleDateString(this.locale, { weekday: 'long' }).charAt(0).toUpperCase() +
				this.date.toLocaleDateString(this.locale, { weekday: 'long' }).slice(1)
			);
		},
		currentDateMonth() {
			return this.date.toLocaleDateString(this.locale, {
				day: 'numeric',
				month: 'short',
			});
		},
	},
};
</script>

<style lang="scss">
#form-builder-booking {
	.fabrikEmundusGeolocalisation {
		height: 150px;
	}
}
</style>
