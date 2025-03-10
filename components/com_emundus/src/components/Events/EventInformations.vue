<script>
import colors from '@/mixins/colors';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'EventInformations',
	props: {
		calendarEvent: {
			type: Object,
			required: true,
		},
		canBeSelected: {
			type: Boolean,
			default: false,
		},
	},
	mixins: [colors],
	data() {
		return {
			eventStartDate: null,
			eventEndDate: null,

			actualLanguage: 'fr-FR',
		};
	},

	created() {
		const globalStore = useGlobalStore();
		this.actualLanguage = globalStore.getCurrentLang;

		if (this.calendarEvent.start) {
			this.eventStartDate = new Date(this.calendarEvent.start);
		}
		if (this.calendarEvent.end) {
			this.eventEndDate = new Date(this.calendarEvent.end);
		}
	},
	methods: {
		capitalizeFirstLetter(str) {
			return str.charAt(0).toUpperCase() + str.slice(1);
		},
	},
	computed: {
		eventDay() {
			return this.eventStartDate.toLocaleDateString(this.actualLanguage, {
				weekday: 'long',
				year: 'numeric',
				month: 'long',
				day: 'numeric',
			});
		},
		eventHours() {
			return (
				this.eventStartDate.toLocaleTimeString(this.actualLanguage, { hour: '2-digit', minute: '2-digit' }) +
				' - ' +
				this.eventEndDate.toLocaleTimeString(this.actualLanguage, { hour: '2-digit', minute: '2-digit' })
			);
		},
		brightnessColor() {
			return this.lightenColor(this.calendarEvent.color, 90);
		},
		calendarStyle() {
			if (this.calendarEvent.show) {
				return {
					backgroundColor: this.calendarEvent.color,
					borderColor: this.calendarEvent.color,
				};
			} else {
				return {
					backgroundColor: this.lightenColor(this.calendarEvent.color, 90),
					borderColor: this.calendarEvent.color,
				};
			}
		},
	},
};
</script>

<template>
	<div class="tw-flex tw-gap-2 tw-items-start">
		<input
			v-if="canBeSelected"
			v-model="calendarEvent.show"
			type="checkbox"
			class="tw-cursor-pointer event-checkbox tw-appearance-none tw-w-[20px] !tw-h-[20px] tw-rounded-md tw-relative"
			:style="calendarStyle"
		/>
		<div
			v-else
			class="tw-min-w-[20px] tw-min-h-[20px] tw-rounded-md"
			:style="{ backgroundColor: this.lightenColor(calendarEvent.color, 90) }"
		></div>

		<p class="tw-font-semibold tw-text-ellipsis tw-overflow-hidden">
			{{ calendarEvent.title ? calendarEvent.title : calendarEvent.name }}
		</p>
	</div>

	<div class="tw-flex tw-items-center tw-gap-2" v-if="eventStartDate">
		<span class="material-symbols-outlined tw-text-neutral-900">calendar_today</span>
		<p>{{ capitalizeFirstLetter(eventDay) }}</p>
	</div>
	<div class="tw-flex tw-items-center tw-gap-2" v-if="eventStartDate">
		<span class="material-symbols-outlined tw-text-neutral-900">schedule</span>
		<p>{{ eventHours }}</p>
	</div>
	<div v-if="calendarEvent.location" class="tw-flex tw-items-start tw-gap-2">
		<span class="material-symbols-outlined tw-text-neutral-900">location_on</span>
		<p>{{ calendarEvent.location }}</p>
	</div>
	<div class="tw-flex tw-items-center tw-gap-2">
		<span class="material-symbols-outlined tw-text-neutral-900">groups</span>
		<p class="">{{ calendarEvent.booked_count }} / {{ calendarEvent.availabilities_count }}</p>
		<p class="tw-overflow-hidden tw-text-ellipsis tw-whitespace-nowrap">
			{{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_BOOKED_SLOT_NUMBER') }}
		</p>
	</div>
</template>

<style scoped>
input[type='checkbox'].event-checkbox {
	margin-right: 0 !important;
}
.event-checkbox:checked:before {
	content: '✓';
	color: white;
	font-size: 16px;
	font-weight: bold;
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
}
</style>
