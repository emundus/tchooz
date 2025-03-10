<script>
import { shallowRef } from 'vue';
import { useGlobalStore } from '@/stores/global.js';
import colors from '@/mixins/colors';

/* COMPONENTS */
import EventModal from '@/components/Events/EventModal.vue';
import EventDay from '@/components/Events/EventDay.vue';
import EventInformations from '@/components/Events/EventInformations.vue';
import Modal from '@/components/Modal.vue';
import EditSlot from '@/views/Events/EditSlot.vue';

/* Services */
import eventsService from '@/services/events';

/* Schedule X */
import { ScheduleXCalendar } from '@schedule-x/vue';
import { createCalendar, createViewDay, createViewWeek } from '@schedule-x/calendar';
import '@schedule-x/theme-default/dist/index.css';
import { createEventsServicePlugin } from '@schedule-x/events-service';
import { createCalendarControlsPlugin } from '@schedule-x/calendar-controls';
import { createEventModalPlugin } from '@schedule-x/event-modal';
import { translations, mergeLocales } from '@schedule-x/translations';

const eventsServicePlugin = createEventsServicePlugin();
const calendarControls = createCalendarControlsPlugin();
const eventModal = createEventModalPlugin();

const createCalendarConfig = (vm) => ({
	locale: 'fr-FR',
	defaultView: vm.defaultView,
	dayBoundaries: {
		start: '08:00',
		end: '21:00',
	},
	weekOptions: {
		gridHeight: 2500,
		eventWidth: 95,
		eventOverlap: false,
	},
	views: [createViewWeek(), createViewDay()],
	events: [],
	plugins: [eventModal, eventsServicePlugin, calendarControls],
	callbacks: {
		onRender($app) {
			const range = $app.calendarState.range.value;
			let start = new Date();
			let startString = start.toISOString().split('T')[0];

			if (vm.items.registrants && vm.items.registrants.length > 0) {
				// Set calendar date range to the nearest start_date of items.registrants
				const nearestEvent = vm.items.registrants.reduce((prev, curr) => {
					if (!curr.start_date) {
						return prev;
					}
					if (!prev.start_date) {
						return curr;
					}

					return new Date(curr.start_date) < new Date(prev.start_date) ? curr : prev;
				});

				if (nearestEvent && nearestEvent.start_date) {
					start = new Date(nearestEvent.start_date);
					startString = start.toISOString().split('T')[0];
				}
			}

			calendarControls.setDate(startString);

			// If startString is between range dispatch onRangeUpdate event
			if (
				vm.normalizeDate(startString) >= vm.normalizeDate(range.start) &&
				vm.normalizeDate(startString) <= vm.normalizeDate(range.end)
			) {
				if (calendarControls.getView() === 'day') {
					vm.getEventsAvailabilities(range.start, range.end);
				} else {
					vm.getEventsSlots(range.start, range.end);
				}
			}
		},
		onRangeUpdate(range) {
			if (calendarControls.getView() === 'day') {
				vm.getEventsAvailabilities(range.start, range.end);
			} else {
				vm.getEventsSlots(range.start, range.end);
			}
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
	name: 'Calendar',
	components: { Modal, EventInformations, EventDay, EventModal, ScheduleXCalendar, EditSlot },
	props: {
		items: {
			type: Object,
			required: true,
		},
		editWeekAction: {
			type: String,
			required: true,
		},
	},
	mixins: [colors],
	emits: ['valueUpdated', 'update-items'],
	data() {
		return {
			actualLanguage: 'fr',

			calendarApp: shallowRef(null),
			view: 'week',

			calendars: {},

			showModal: false,
			currentSlot: null,
		};
	},
	created() {
		const globalStore = useGlobalStore();
		this.actualLanguage = globalStore.getShortLang;

		this.initCalendar();
	},
	methods: {
		openModal(slot, registrant) {
			this.showModal = false;
			this.$nextTick(() => {
				slot.registrantSelected = registrant;
				this.currentSlot = slot;
				this.showModal = true;
			});
		},

		closePopup() {
			this.showModal = false;
			this.currentSlot = null;
		},
		initCalendar() {
			const view = sessionStorage.getItem('tchooz_calendar_view/' + document.location.hostname);

			// Keep a reference to the Vue instance's methods and data
			const vm = {
				getEventsSlots: this.getEventsSlots,
				getEventsAvailabilities: this.getEventsAvailabilities,
				normalizeDate: this.normalizeDate,
				items: this.items,
				defaultView: view ? view : 'week',
			};

			// Initialize calendarApp with shallowRef
			this.calendarApp = createCalendar(createCalendarConfig(vm));
		},

		getEventsSlots(start, end) {
			this.view = 'week';

			this.calendars = {};

			if (this.items.registrants && this.items.registrants.length > 0) {
				let eventsIds = this.items.registrants.map((event) => event.id);
				eventsIds = eventsIds.join(',');

				eventsService.getEventsSlots(start, end, eventsIds).then(async (response) => {
					if (response.status && response.data.length > 0) {
						for (const item of this.items.registrants) {
							if (item.availabilities_count === 0) {
								continue;
							}

							this.calendars['calendar_' + item.id] = this.buildCalendar(item, true);
						}

						let events = await this.prepareEvents(response.data);

						if (events.length > 0) {
							const calendarsToShow = Object.keys(this.calendars).filter((key) => this.calendars[key].show);
							calendarControls.setCalendars(calendarsToShow);

							calendarControls.setWeekOptions({
								gridHeight: 1000,
								eventWidth: 95,
							});

							eventsServicePlugin.set(events);
						}
					}
				});
			}
		},

		getEventsAvailabilities(start, end) {
			this.view = 'day';

			this.calendars = {};

			if (this.items.registrants && this.items.registrants.length > 0) {
				let eventsIds = this.items.registrants.map((event) => event.id);
				eventsIds = eventsIds.join(',');

				eventsService.getEventsAvailabilities(start, end, eventsIds).then(async (response) => {
					if (response.status && response.data.length > 0) {
						for (const item of this.items.registrants) {
							if (item.availabilities_count === 0) {
								continue;
							}

							this.calendars['calendar_' + item.id] = this.buildCalendar(item);
						}

						let events = await this.prepareEvents(response.data, false);

						if (events.length > 0) {
							for (const event of events) {
								let calendarId = event.calendarId;

								if (this.calendars[calendarId].events) {
									if (this.calendars[calendarId].events.some((e) => e.id === event.id)) {
										continue;
									}

									this.calendars[calendarId].availabilities_count += event.availabilities_count;
									this.calendars[calendarId].booked_count += event.booked_count;
									this.calendars[calendarId].events.push(event);
									this.calendars[calendarId].show = true;
								}
							}

							// Remove calendars with no events
							for (const key in this.calendars) {
								if (this.calendars[key].events.length === 0) {
									delete this.calendars[key];
								}
							}

							const calendarsToShow = Object.keys(this.calendars).filter((key) => this.calendars[key].show);
							calendarControls.setCalendars(calendarsToShow);

							calendarControls.setWeekOptions({
								gridHeight: 1800,
								eventWidth: 95,
							});

							eventsServicePlugin.set(events);
						} else {
							// Remove all calendars if no events
							this.calendars = {};
						}
					} else {
						this.calendars = {};
					}
				});
			}
		},

		buildCalendar(item, defaultShow = false) {
			return {
				id: 'calendar_' + item.id,
				colorName: 'calendar_' + item.id,
				lightColors: {
					main: item.color,
					container: item.color,
					onContainer: item.color,
				},
				color: item.color,
				name: item.label[this.actualLanguage],
				location: item.location,
				availabilities_count: 0,
				booked_count: 0,
				show: defaultShow,
				events: [],
				columnSize: 0,
			};
		},

		prepareEvents(datas, check_show = true) {
			return new Promise((resolve) => {
				let events = [];
				let columns = [];
				let calendarSizes = {};

				if (check_show) {
					datas = datas.filter((event) => this.calendars['calendar_' + event.event_id].show);
				}

				let groupedEvents = {};
				datas.forEach((event) => {
					if (!groupedEvents[event.event_id]) {
						groupedEvents[event.event_id] = [];
					}
					groupedEvents[event.event_id].push(event);
				});

				let sortedGroupedEvents = Object.values(groupedEvents).map((group) => group.sort((a, b) => a.start - b.start));

				let sortedEvents = sortedGroupedEvents.flat();

				sortedEvents.forEach((event) => {
					event.title = event.name;
					if (event.people && typeof event.people === 'string') {
						event.people = event.people.split(',');
					}
					event.calendarId = 'calendar_' + event.event_id;

					let placed = false;
					for (let column of columns) {
						if (!column.some((e) => e.end > event.start) && column.every((e) => e.slot_id === event.slot_id)) {
							column.push(event);
							placed = true;
							break;
						}
					}
					if (!placed) {
						columns.push([event]);
					}

					let usedColumns = columns.length;
					calendarSizes[event.event_id] = Math.max(calendarSizes[event.event_id] || 1, usedColumns);
				});

				let totalColumns = columns.length;
				columns.forEach((column, colIndex) => {
					column.forEach((event) => {
						event.width = `calc(100% / ${totalColumns})`;
						event.left = `calc(${(colIndex / totalColumns) * 100}%)`;

						events.push(event);
					});
				});

				Object.keys(calendarSizes).forEach((event_id) => {
					let calendarKey = 'calendar_' + event_id;
					if (this.calendars[calendarKey]) {
						this.calendars[calendarKey].columnSize = calendarSizes[event_id];
					}
				});

				resolve(events);
			});
		},

		editEvent(action, id) {
			this.$emit('on-click-action', action, id);
		},

		calendarStyle(calendar) {
			let style = {
				borderColor: calendar.color,
			};

			if (calendar.show) {
				style.backgroundColor = this.lightenColor(calendar.color, 90);
				style.border = `2px solid ${calendar.color}`;
				style.borderLeft = `4px solid ${calendar.color}`;

				let gridColumnSize = calendar.columnSize;
				let key = Object.keys(this.calendars).indexOf(calendar.id);
				if (key > 0) {
					let previousCalendar = Object.values(this.calendars)[key - 1];
					gridColumnSize -= previousCalendar.columnSize;
				}
				style.gridColumn = `span ${gridColumnSize}`;
			} else {
				style.borderLeft = `4px solid ${calendar.color}`;
			}

			return style;
		},

		checkboxCalendarStyle(calendar) {
			if (calendar.show) {
				return {
					backgroundColor: calendar.color,
					borderColor: calendar.color,
				};
			} else {
				return {
					backgroundColor: this.lightenColor(calendar.color, 90),
					borderColor: calendar.color,
				};
			}
		},
		updateItems() {
			this.$emit('update-items');
		},

		toggleCalendar(calendar) {
			calendar.show = !calendar.show;

			let datas = [];
			for (const key in this.calendars) {
				datas = datas.concat(this.calendars[key].events);
			}

			this.prepareEvents(datas).then((events) => {
				eventsServicePlugin.set(events);
			});
		},

		normalizeDate(date) {
			const d = new Date(date);
			d.setHours(0, 0, 0, 0);
			return d;
		},
	},
	watch: {
		view(value) {
			sessionStorage.setItem('tchooz_calendar_view/' + document.location.hostname, value);
		},
	},
};
</script>

<template>
	<div
		v-if="calendarApp"
		:class="{
			'day-grid tw-grid tw-gap-4': view === 'day',
		}"
	>
		<template v-if="showModal">
			<Teleport to=".com_emundus_vue">
				<modal
					:name="'modal-component'"
					transition="nice-modal-fade"
					:class="'placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-rounded tw-px-4 tw-shadow-modal'"
					:width="'600px'"
					:delay="100"
					:adaptive="true"
					:clickToClose="false"
					@click.stop
				>
					<component :is="'EditSlot'" :slot="this.currentSlot" @close="closePopup()" @update-items="updateItems()" />
				</modal>
			</Teleport>
		</template>

		<div v-if="view === 'day'" class="tw-flex tw-flex-col tw-gap-4">
			<div v-for="calendar in calendars" class="tw-flex tw-cursor-pointer tw-gap-2" @click="toggleCalendar(calendar)">
				<input
					:checked="calendar.show"
					type="checkbox"
					:style="checkboxCalendarStyle(calendar)"
					class="event-checkbox tw-relative !tw-h-[20px] tw-w-[20px] tw-cursor-pointer tw-appearance-none tw-rounded-md"
				/>
				<p style="word-wrap: anywhere">{{ calendar.title ? calendar.title : calendar.name }}</p>
			</div>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-4">
			<div
				v-if="view === 'day'"
				class="calendars-list tw-grid tw-gap-3"
				style="padding-left: var(--sx-calendar-week-grid-padding-left)"
			>
				<div
					v-for="calendar in calendars"
					class="tw-flex tw-w-full tw-cursor-pointer tw-flex-col tw-gap-2 tw-rounded-lg tw-border-neutral-400 tw-bg-white tw-px-6 tw-py-4 tw-shadow"
					:style="calendarStyle(calendar)"
					@click="toggleCalendar(calendar)"
					v-show="calendar.show"
				>
					<EventInformations :calendar-event="calendar" :can-be-selected="true" />
				</div>
			</div>

			<ScheduleXCalendar :calendar-app="calendarApp">
				<template #timeGridEvent="{ calendarEvent }">
					<EventDay
						v-if="calendars && Object.keys(this.calendars).length > 0"
						:calendar-event="calendarEvent"
						:view="view"
						@update-items="updateItems"
						@edit-modal="openModal"
					/>
				</template>

				<template #eventModal="{ calendarEvent }">
					<EventModal
						:calendar-event="calendarEvent"
						:editAction="editWeekAction"
						@edit-event="editEvent"
						:view="view"
					/>
				</template>
			</ScheduleXCalendar>
		</div>
	</div>
</template>

<style scoped>
.placement-center {
	position: fixed;
	left: 50%;
	transform: translate(-50%, -50%);
	top: 50%;
}

.day-grid {
	grid-template-columns: 15% 85%;
}

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

.calendars-list {
	grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
}
</style>
