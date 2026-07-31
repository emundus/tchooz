<script>
import { shallowRef, nextTick } from 'vue';

import date from '@/mixins/date';
import colors from '@/mixins/colors';
import alerts from '@/mixins/alerts';

/* Components */
import PollSlotPopup from '@/components/Polls/Popup/PollSlotPopup.vue';
//import Slider from '@/components/Molecules/Slider.vue';
import { Icon, Slider } from '@emundus/ui';

/* Services */
import pollService from '@/services/poll.js';

/* Schedule X */
import { ScheduleXCalendar } from '@schedule-x/vue';
import { createCalendar, createViewWeek, viewWeek } from '@schedule-x/calendar';
import '@schedule-x/theme-default/dist/index.css';
import { createEventsServicePlugin } from '@schedule-x/events-service';
import { createCalendarControlsPlugin } from '@schedule-x/calendar-controls';
import { translations, mergeLocales } from '@schedule-x/translations';
import { useGlobalStore } from '@/stores/global.js';
import { Button } from '@emundus/ui';

const eventsServicePlugin = createEventsServicePlugin();
const calendarControls = createCalendarControlsPlugin();

const createCalendarConfig = (vm) => ({
	locale: 'fr-FR',
	defaultView: viewWeek.name,
	dayBoundaries: {
		start: '08:00',
		end: '21:00',
	},
	weekOptions: {
		gridHeight: 900,
		eventWidth: 100,
	},
	views: [createViewWeek()],
	events: [],
	plugins: [eventsServicePlugin, calendarControls],
	callbacks: {
		onClickDateTime: (dateTime) => {
			vm.openSlotPopup(dateTime);
		},
		onEventClick: (event) => {
			vm.openSlotPopup(null, event);
		},
		onRender($app) {
			nextTick(() => {
				vm.addEventListeners();
				vm.updateCellSize();
			});
		},
		onRangeUpdate(range) {
			nextTick(() => {
				vm.addEventListeners();
			});
		},
	},
	translations: mergeLocales(translations, {
		frFR: {
			Week: 'Vue semaine',
			Day: 'Vue jour',
			Today: "Revenir à aujourd'hui",
		},
		enGB: {
			Week: 'Week View',
			Day: 'Day View',
			Today: 'Back to today',
		},
	}),
});

export default {
	name: 'PollCalendar',
	components: { Icon, Button, ScheduleXCalendar, PollSlotPopup, Slider },
	mixins: [date, colors, alerts],
	props: {
		poll: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			calendarApp: shallowRef(null),
			loading: true,
			actualLanguage: 'fr-FR',

			openedSlotPopup: false,
			dateClicked: null,
			currentSlot: null,
			tempSlotIdCounter: -1,

			// ScheduleX drops unknown custom props on its events, so we keep the free-text
			// location keyed by slot id and re-attach it when opening the edit popup.
			slotLocations: {},

			slotDuration: 60,
			customDuration: 60,
			customDurationActive: false,
			durationPresets: [30, 60, 90],

			selection: {
				visible: false,
				top: 0,
				left: 0,
				width: 0,
				height: 0,
			},
			cellHeight: 50,
			cellWidth: 100,
			lastMouseX: 0,
			lastMouseY: 0,
			lastMouseTarget: null,

			animationFrame: null,
		};
	},
	created() {
		const globalStore = useGlobalStore();
		this.actualLanguage = globalStore.getCurrentLang;
		this.loading = false;
	},
	mounted() {
		const vm = {
			openSlotPopup: this.openSlotPopup,
			addEventListeners: this.addEventListeners,
			updateCellSize: this.updateCellSize,
		};

		this.calendarApp = createCalendar(createCalendarConfig(vm));

		if (this.$props.poll.slots && this.$props.poll.slots.length > 0) {
			let key = 0;
			while (this.$props.poll.slots[key] && this.$props.poll.slots[key].start < new Date().toISOString()) {
				key++;
			}
			if (key >= this.$props.poll.slots.length) {
				key = this.$props.poll.slots.length - 1;
			}
			let selectedDate = new Date(this.$props.poll.slots[key].start);
			selectedDate = selectedDate.toISOString().split('T')[0];
			const plainDate = Temporal.PlainDate.from(selectedDate);
			calendarControls.setDate(plainDate);
		}

		(this.$props.poll.slots || []).forEach((slot) => {
			this.slotLocations[slot.id] = slot.location_text || '';
		});

		const normalizedSlots = (this.$props.poll.slots || []).map((slot) => ({
			...slot,
			color: '#0644AE',
			start: this.convertOldDateTimeStringToZonedDateTime(slot.start),
			end: this.convertOldDateTimeStringToZonedDateTime(slot.end),
		}));

		eventsServicePlugin.set(normalizedSlots);
	},
	beforeUnmount() {
		this.removeEventListeners();
	},
	computed: {
		durationOptions() {
			const presetOptions = this.durationPresets.map((preset) => ({
				value: preset,
				label: `${preset} min`,
			}));
			return [
				...presetOptions,
				{ value: 'custom', label: this.translate('COM_EMUNDUS_POLL_FIELD_SLOT_DURATION_CUSTOM') },
			];
		},
		durationSelection: {
			get() {
				return this.customDurationActive ? 'custom' : this.slotDuration;
			},
			set(value) {
				if (value === 'custom') {
					this.enableCustomDuration();
				} else {
					this.setSlotDuration(value);
				}
			},
		},
	},
	methods: {
		openSlotPopup(date, slot = null) {
			this.dateClicked = date;
			if (slot && slot.id != null && this.slotLocations[slot.id] !== undefined) {
				slot = { ...slot, location_text: this.slotLocations[slot.id] };
			}
			this.currentSlot = slot;
			this.openedSlotPopup = true;
		},
		closeSlotPopup() {
			this.openedSlotPopup = false;
			document.body.style.overflow = '';
		},

		isTempSlotId(id) {
			return typeof id === 'number' && id < 0;
		},
		generateTempSlotId() {
			const id = this.tempSlotIdCounter;
			this.tempSlotIdCounter -= 1;
			return id;
		},
		async handleSlotSaved(slotData) {
			const persistedSlot = {
				id: slotData.id || 0,
				start_date: slotData.start_date,
				end_date: slotData.end_date,
				slot_capacity: slotData.slot_capacity,
				location_text: slotData.location_text || '',
			};

			if (this.poll.id) {
				const response = await pollService.savePollSlot(slotData);
				if (response.status !== true) {
					this.alertError(response.message || response.msg || 'COM_EMUNDUS_ONBOARD_ERROR');
					return;
				}
				const saved = response.data || slotData;
				persistedSlot.id = saved.id;
				persistedSlot.location_text = saved.location_text != null ? saved.location_text : persistedSlot.location_text;
			} else {
				if (persistedSlot.id && this.isTempSlotId(persistedSlot.id)) {
					const index = this.poll.slots.findIndex((s) => s.id === persistedSlot.id);
					if (index !== -1) {
						this.poll.slots.splice(index, 1, persistedSlot);
					} else {
						this.poll.slots.push(persistedSlot);
					}
				} else {
					persistedSlot.id = this.generateTempSlotId();
					this.poll.slots.push(persistedSlot);
				}
			}

			this.slotLocations[persistedSlot.id] = persistedSlot.location_text || '';

			this.addOrUpdateCalendarEvent(persistedSlot);
			this.alertSuccess('COM_EMUNDUS_POLL_SLOT_SAVED_SUCCESS');
		},
		async handleSlotDeleted(slotId) {
			if (this.isTempSlotId(slotId)) {
				const index = this.poll.slots.findIndex((s) => s.id === slotId);
				if (index !== -1) {
					this.poll.slots.splice(index, 1);
				}
				this.removeSlotFromCalendar(slotId);
				this.alertSuccess('COM_EMUNDUS_POLL_SLOT_DELETED_SUCCESS');
				return;
			}

			const response = await pollService.deletePollSlot(slotId);
			if (response.status === true) {
				this.removeSlotFromCalendar(slotId);
				this.alertSuccess('COM_EMUNDUS_POLL_SLOT_DELETED_SUCCESS');
			} else {
				this.alertError(response.message || response.msg || 'COM_EMUNDUS_ONBOARD_ERROR');
			}
		},

		addOrUpdateCalendarEvent(slots) {
			const normalizedSlots = Array.isArray(slots) ? slots : [slots];

			for (const slot of normalizedSlots) {
				const event = {
					id: slot.id,
					start: this.convertOldDateTimeStringToZonedDateTime(slot.start_date),
					end: this.convertOldDateTimeStringToZonedDateTime(slot.end_date),
					slot_capacity: slot.slot_capacity,
					location_text: slot.location_text || '',
					color: '#0644AE',
				};

				const existingEvent = slot.id !== 0 ? eventsServicePlugin.get(slot.id) : null;
				if (existingEvent) {
					eventsServicePlugin.update(event);
				} else {
					eventsServicePlugin.add(event);
				}
			}
		},
		removeSlotFromCalendar(slotId) {
			eventsServicePlugin.remove(slotId);
		},

		addEventListeners() {
			if (this._listenersAttached) return;

			document.addEventListener('mousemove', this.handleMouseMove);
			window.addEventListener('scroll', this.updateSelectionOnScroll);
			window.addEventListener('resize', this.updateCellSize);

			this._listenersAttached = true;
		},
		removeEventListeners() {
			if (!this._listenersAttached) return;

			document.removeEventListener('mousemove', this.handleMouseMove);
			window.removeEventListener('scroll', this.updateSelectionOnScroll);
			window.removeEventListener('resize', this.updateCellSize);

			this._listenersAttached = false;
		},

		updateCellSize() {
			const dayEl = document.querySelector('.sx__time-grid-day');
			const hourEl = document.querySelector('.sx__week-grid__hour');
			if (!dayEl || !hourEl) return;

			this.cellWidth = dayEl.offsetWidth;
			this.cellHeight = (hourEl.offsetHeight / 60) * this.slotDuration;
		},

		setSlotDuration(value) {
			this.customDurationActive = false;
			this.slotDuration = value;
			this.updateCellSize();
		},
		enableCustomDuration() {
			this.customDurationActive = true;
			this.slotDuration = this.customDuration;
			this.updateCellSize();
		},
		applyCustomDuration() {
			const value = parseInt(this.customDuration, 10);
			if (isNaN(value) || value <= 0) return;
			this.customDuration = value;
			this.slotDuration = value;
			this.updateCellSize();
		},

		handleMouseMove(event) {
			if (!this.$refs.calendar) return;

			const dayEl = event.target.closest('.sx__time-grid-day');
			if (!dayEl || !this.$refs.calendar.contains(dayEl)) {
				this.hideSelection();
				return;
			}

			this.lastMouseX = event.clientX;
			this.lastMouseY = event.clientY;
			this.lastMouseTarget = dayEl;

			if (this.animationFrame) {
				cancelAnimationFrame(this.animationFrame);
			}

			this.animationFrame = requestAnimationFrame(() => {
				const containerRect = this.$refs.calendar.getBoundingClientRect();
				const dayRect = dayEl.getBoundingClientRect();

				const relativeX = dayRect.left - containerRect.left;
				const relativeY = event.clientY - containerRect.top;

				this.selection = {
					visible: true,
					top: relativeY,
					left: relativeX,
					width: this.cellWidth || dayRect.width,
					height: this.cellHeight,
				};

				this.animationFrame = null;
			});
		},
		updateSelectionOnScroll() {
			if (this.selection.visible) {
				this.handleMouseMove({
					clientX: this.lastMouseX,
					clientY: this.lastMouseY,
					target: this.lastMouseTarget,
				});
			}
		},
		hideSelection() {
			this.selection.visible = false;
		},
	},
};
</script>

<template>
	<div>
		<PollSlotPopup
			v-if="openedSlotPopup"
			:date="dateClicked"
			:slot="currentSlot"
			:poll-id="poll.id"
			:duration="slotDuration"
			@close="closeSlotPopup"
			@slot-saved="handleSlotSaved"
			@slot-deleted="handleSlotDeleted"
		/>

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6" v-if="!this.loading">
			<div>
				<div>
					<label class="tw-mb-0 tw-flex tw-items-end tw-font-semibold">
						{{ translate('COM_EMUNDUS_POLL_FIELD_SLOT_LABEL') }}
					</label>
				</div>

				<div class="tw-mt-4 tw-flex tw-items-center tw-gap-3">
					<Slider v-model="durationSelection" :options="durationOptions" />
					<div v-if="customDurationActive" class="tw-flex tw-items-center tw-gap-1">
						<input
							type="number"
							min="1"
							v-model.number="customDuration"
							@change="applyCustomDuration"
							class="tw-w-20 tw-rounded-md tw-border tw-border-neutral-300 tw-px-2 tw-py-1 tw-text-sm"
						/>
						<span class="tw-text-sm tw-text-neutral-600">min</span>
					</div>
				</div>

				<div class="calendar-container tw-relative tw-mt-4" v-if="calendarApp" ref="calendar">
					<ScheduleXCalendar :calendar-app="calendarApp" class="tw-relative">
						<template #timeGridEvent="{ calendarEvent }">
							<div
								class="tw-flex tw-h-full tw-flex-col tw-gap-2 tw-overflow-auto tw-border tw-border-s-4 tw-p-1 tw-pl-2"
								:style="{
									backgroundColor: lightenColor(calendarEvent.color, 90),
									color: calendarEvent.color,
									borderColor: calendarEvent.color,
								}"
							>
								<div v-if="calendarEvent.title">
									<span class="tw-flex tw-overflow-hidden tw-text-ellipsis tw-text-xs tw-font-semibold">
										{{ calendarEvent.title }}
									</span>
								</div>

								<div class="tw-flex tw-items-center tw-gap-2">
									<span class="material-symbols-outlined !tw-text-sm" :style="{ color: calendarEvent.color }">
										schedule
									</span>
									<p class="tw-text-xs" :style="{ color: calendarEvent.color }">
										{{
											calendarEvent.start.toLocaleString(actualLanguage, {
												hour: '2-digit',
												minute: '2-digit',
											})
										}}
										-
										{{
											calendarEvent.end.toLocaleString(actualLanguage, {
												hour: '2-digit',
												minute: '2-digit',
											})
										}}
									</p>
								</div>
							</div>
						</template>
					</ScheduleXCalendar>

					<div
						v-if="selection.visible"
						class="selection-box"
						:style="{
							top: selection.top + 'px',
							left: selection.left + 'px',
							width: selection.width + 'px',
							height: selection.height + 'px',
						}"
					></div>

					<Button class="tw-mt-4" @click="openSlotPopup(null, null)">
						<template #leading>
							<Icon name="control_point" />
						</template>
						{{ translate('COM_EMUNDUS_POLL_FIELD_SLOT_ADD') }}
					</Button>
				</div>
			</div>
		</div>
	</div>
</template>

<style>
.selection-box {
	position: absolute;
	background: hsl(from var(--em-profile-color) h s l / 15%);
	border: 1px solid var(--em-profile-color);
	pointer-events: none;
	transition:
		top 0.1s,
		left 0.1s;
	border-radius: 4px;
	cursor: pointer;
}

.sx-vue-calendar-wrapper {
	height: 100% !important;
	max-height: unset !important;
}
</style>
