<script>
import { shallowRef, nextTick } from 'vue';
import Swal from 'sweetalert2';

/* Components */
import CalendarSlotPopup from '@/components/Events/Popup/CalendarSlotPopup.vue';
import EventDay from '@/components/Events/EventDay.vue';

/* Schedule X */
import { ScheduleXCalendar } from '@schedule-x/vue';
import { createCalendar, createViewWeek, viewWeek } from '@schedule-x/calendar';
import '@schedule-x/theme-default/dist/index.css';
import { createEventsServicePlugin } from '@schedule-x/events-service';
import { createCalendarControlsPlugin } from '@schedule-x/calendar-controls';
import { translations, mergeLocales } from '@schedule-x/translations';

const eventsServicePlugin = createEventsServicePlugin();
const calendarControls = createCalendarControlsPlugin();

// Do not use a ref here, as the calendar instance is not reactive, and doing so might cause issues
// For updating events, use the events service plugin
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
	name: 'EventCalendarSettings',
	components: { EventDay, CalendarSlotPopup, ScheduleXCalendar },
	props: {
		event: Object,
	},
	emits: ['go-back'],
	data() {
		return {
			calendarApp: shallowRef(null),
			loading: true,
			openedSlotPopup: false,

			dateClicked: null,
			currentSlot: null,
			view: 'week',

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
	mounted() {
		// Keep a reference to the Vue instance's methods and data
		const vm = {
			openSlotPopup: this.openSlotPopup,
			dateClicked: this.dateClicked,
			addEventListeners: this.addEventListeners,
			updateCellSize: this.updateCellSize,
		};

		// Initialize calendarApp with shallowRef
		this.calendarApp = createCalendar(createCalendarConfig(vm));

		for (const slot of this.$props.event.slots) {
			slot.color = this.event.color;
		}

		// Set selected date corresponding to first slot in future
		if (this.$props.event.slots.length > 0) {
			let key = 0;
			//let selectedDate = new Date(this.$props.event.slots[this.$props.event.slots.length - 1].start);
			while (this.$props.event.slots[key] && this.$props.event.slots[key].start < new Date().toISOString()) {
				key++;
			}
			let selectedDate = new Date(this.$props.event.slots[key].start);

			//let selectedDate = new Date(this.$props.event.slots[0].start);
			selectedDate = selectedDate.toISOString().split('T')[0];
			calendarControls.setDate(selectedDate);
		}

		eventsServicePlugin.set(this.$props.event.slots);
	},
	beforeUnmount() {
		this.removeEventListeners();
	},
	created() {
		this.loading = false;
	},
	methods: {
		addEventListeners() {
			document.querySelectorAll('.sx__time-grid-day').forEach((el) => {
				el.addEventListener('mousemove', this.handleMouseMove);
				el.addEventListener('mouseleave', this.hideSelection);
			});

			window.addEventListener('scroll', this.updateSelectionOnScroll);
			window.addEventListener('resize', this.updateCellSize);
		},
		removeEventListeners() {
			document.querySelectorAll('.sx__time-grid-day').forEach((el) => {
				el.removeEventListener('mousemove', this.handleMouseMove);
				el.removeEventListener('mouseleave', this.hideSelection);
			});

			window.removeEventListener('scroll', this.updateSelectionOnScroll);
			window.removeEventListener('resize', this.updateCellSize);
		},

		openSlotPopup(date, slot = null) {
			// Do not open if no duration
			if (!this.event.slot_duration) {
				Swal.fire({
					icon: 'error',
					title: 'Oops...',
					text: Joomla.JText._('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_REQUIRED'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						actions: 'em-swal-single-action',
					},
				});
				return;
			}

			if (slot) {
				slot.repeat_dates = [];

				// Search if other slots are linked to this slot via parent_slot_id
				let parent_slot_id = slot.id;
				let parent_slot = this.$props.event.slots.find((s) => s.id === slot.parent_slot_id);
				if (parent_slot) {
					parent_slot_id = parent_slot.id;
				}
				let child_slots = this.$props.event.slots.filter(
					(s) => s.parent_slot_id !== 0 && s.parent_slot_id === parent_slot_id,
				);

				if (child_slots.length > 0) {
					for (const child_slot of child_slots) {
						let repeat_date = {};
						repeat_date.id = child_slot.start.split(' ')[0];
						repeat_date.date = child_slot.start;
						slot.repeat_dates.push(repeat_date);
					}
				}
				//
			}

			this.dateClicked = date;
			this.currentSlot = slot;
			this.openedSlotPopup = true;
		},
		updateSlots(slots) {
			for (const slot of slots) {
				let existingSlot = eventsServicePlugin.get(slot.id);

				if (existingSlot) {
					eventsServicePlugin.update(slot);
				} else {
					eventsServicePlugin.add(slot);
				}
			}
		},
		deleteSlot(slot_id) {
			eventsServicePlugin.remove(slot_id);
		},

		updateCellSize() {
			this.cellWidth = document.querySelector('.sx__time-grid-day').offsetWidth;
			this.cellHeight = document.querySelector('.sx__week-grid__hour').offsetHeight;
			if (this.$props.event.slot_duration_type === 'minutes') {
				this.cellHeight = (this.cellHeight / 60) * this.$props.event.slot_duration;
			} else {
				this.cellHeight = this.cellHeight * this.$props.event.slot_duration;
			}
		},

		handleMouseMove(event) {
			if (!this.$refs.calendar) return;

			this.lastMouseX = event.clientX;
			this.lastMouseY = event.clientY;
			this.lastMouseTarget = event.target;

			// If event.target.id does not have sx__time-grid-day class, return
			if (!event.target.classList.contains('sx__time-grid-day')) {
				this.hideSelection();
				return;
			}

			if (this.animationFrame) {
				cancelAnimationFrame(this.animationFrame);
			}

			this.animationFrame = requestAnimationFrame(() => {
				const rect = event.target;
				let calendarGrid = document.querySelector('.sx__view-container ');
				const rectCalendar = calendarGrid.getBoundingClientRect();

				const header =
					document.querySelector('.sx__calendar-header').offsetHeight +
					document.querySelector('.sx__week-header').offsetHeight;
				const relativeX = rect.offsetLeft + 23;
				const relativeY = event.clientY - rectCalendar.top + header + 20;

				this.selection = {
					visible: true,
					top: relativeY,
					left: relativeX,
					width: this.cellWidth,
					height: this.cellHeight,
				};

				this.animationFrame = null;
			});
		},
		updateSelectionOnScroll() {
			if (this.selection.visible) {
				// Force une mise à jour quand on scroll
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
	computed: {},
};
</script>

<template>
	<div>
		<CalendarSlotPopup
			v-if="openedSlotPopup"
			:date="dateClicked"
			:slot="currentSlot"
			:event-id="event.id"
			:location-id="event.location"
			:duration="event.slot_duration"
			:duration_type="event.slot_duration_type"
			:break_every="event.slot_break_every"
			:break_time="event.slot_break_time"
			:break_time_type="event.slot_break_time_type"
			@close="openedSlotPopup = false"
			@slot-saved="updateSlots"
			@slot-deleted="deleteSlot"
		/>

		<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6" v-if="!this.loading">
			<div>
				<div>
					<label class="tw-mb-0 tw-flex tw-items-end tw-font-semibold">
						{{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CALENDAR') }}
					</label>
					<span class="tw-text-base tw-text-neutral-600">
						{{ translate('COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_CALENDAR_HELP') }}
					</span>
				</div>

				<div class="calendar-container tw-mt-4" v-if="calendarApp" ref="calendar">
					<ScheduleXCalendar :calendar-app="calendarApp" class="tw-relative">
						<template #timeGridEvent="{ calendarEvent }">
							<EventDay :calendar-event="calendarEvent" :view="view" :preset="'full'" />
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
