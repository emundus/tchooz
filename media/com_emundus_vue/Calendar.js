import { _ as _export_sfc, u as useGlobalStore, o as openBlock, c as createElementBlock, a as createBaseVNode, t as toDisplayString, e as createCommentVNode, j as shallowRef, r as resolveComponent, g as createVNode, w as withCtx } from "./app_emundus.js";
import { f as d, r, h, y, u, k, g as E, c as createEventsServicePlugin, a as createCalendarControlsPlugin, E as EventDay, _ as _o, b as createCalendar, d as createViewDay, e as createViewWeek } from "./core.js";
import { e as eventsService } from "./events.js";
const _sfc_main$1 = {
  name: "EventModal",
  props: {
    calendarEvent: {
      type: Object,
      required: true
    },
    editAction: {
      type: String
    }
  },
  data() {
    return {
      actualLanguage: "fr-FR",
      eventStartDate: null,
      eventEndDate: null,
      eventDay: ""
    };
  },
  created() {
    console.log(this.calendarEvent);
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getCurrentLang;
    this.eventStartDate = new Date(this.calendarEvent.start);
    this.eventEndDate = new Date(this.calendarEvent.end);
  },
  methods: {
    editEvent() {
      this.$emit("edit-event", this.editAction, this.calendarEvent.event_id);
    }
  },
  computed: {
    eventDay() {
      return this.eventStartDate.toLocaleDateString(this.actualLanguage, { weekday: "long", year: "numeric", month: "long", day: "numeric" });
    },
    eventHours() {
      return this.eventStartDate.toLocaleTimeString(this.actualLanguage, { hour: "2-digit", minute: "2-digit" }) + " - " + this.eventEndDate.toLocaleTimeString(this.actualLanguage, { hour: "2-digit", minute: "2-digit" });
    }
  }
};
const _hoisted_1$1 = { class: "tw-rounded-lg tw-px-6 tw-py-4 tw-shadow-sm tw-border tw-border-neutral-400 tw-flex tw-flex-col tw-gap-2" };
const _hoisted_2 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_3 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_4 = {
  key: 0,
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_5 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_6 = { class: "tw-flex tw-justify-end" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("p", null, [
      createBaseVNode("strong", null, toDisplayString($props.calendarEvent.title), 1)
    ]),
    createBaseVNode("div", _hoisted_2, [
      _cache[1] || (_cache[1] = createBaseVNode("span", { class: "material-symbols-outlined" }, "calendar_today", -1)),
      createBaseVNode("p", null, toDisplayString($options.eventDay), 1)
    ]),
    createBaseVNode("div", _hoisted_3, [
      _cache[2] || (_cache[2] = createBaseVNode("span", { class: "material-symbols-outlined" }, "schedule", -1)),
      createBaseVNode("p", null, toDisplayString($options.eventHours), 1)
    ]),
    $props.calendarEvent.location ? (openBlock(), createElementBlock("div", _hoisted_4, [
      _cache[3] || (_cache[3] = createBaseVNode("span", { class: "material-symbols-outlined" }, "location_on", -1)),
      createBaseVNode("p", null, toDisplayString($props.calendarEvent.location), 1)
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_5, [
      _cache[4] || (_cache[4] = createBaseVNode("span", { class: "material-symbols-outlined" }, "groups", -1)),
      createBaseVNode("p", null, toDisplayString($props.calendarEvent.booked_count) + " / " + toDisplayString($props.calendarEvent.availabilities_count), 1)
    ]),
    createBaseVNode("div", _hoisted_6, [
      createBaseVNode("button", {
        type: "button",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.editEvent && $options.editEvent(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_EDIT_ITEM")), 1)
    ])
  ]);
}
const EventModal$1 = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
var PluginName;
(function(PluginName2) {
  PluginName2["DragAndDrop"] = "dragAndDrop";
  PluginName2["EventModal"] = "eventModal";
  PluginName2["ScrollController"] = "scrollController";
  PluginName2["EventRecurrence"] = "eventRecurrence";
  PluginName2["Resize"] = "resize";
  PluginName2["CalendarControls"] = "calendarControls";
  PluginName2["CurrentTime"] = "currentTime";
})(PluginName || (PluginName = {}));
const randomStringId = () => "s" + Math.random().toString(36).substring(2, 11);
const createClickOutsideListener = ($app, modalId) => {
  return function(e) {
    if (!(e.target instanceof HTMLElement))
      return;
    if (e.target.closest(`#${modalId}`))
      return;
    $app.config.plugins.eventModal.close();
  };
};
const setPosition = (appDOMRect, eventDOMRect, modalHeight = 250) => {
  const MODAL_WIDTH = 400;
  const INLINE_SPACE_BETWEEN_MODAL_AND_EVENT = 10;
  const WIDTH_NEEDED = MODAL_WIDTH + INLINE_SPACE_BETWEEN_MODAL_AND_EVENT;
  const hasSpaceTop = eventDOMRect.bottom - appDOMRect.top > modalHeight;
  const eventBottomLessThanAppBottom = eventDOMRect.bottom < appDOMRect.bottom;
  const eventTopLessThanAppTop = eventDOMRect.top < appDOMRect.top;
  let top = 0;
  let left = 0;
  let animationStart = "0%";
  if (appDOMRect.bottom - eventDOMRect.top > modalHeight && !eventTopLessThanAppTop) {
    top = eventDOMRect.top;
  } else if (hasSpaceTop && eventBottomLessThanAppBottom) {
    top = eventDOMRect.bottom - modalHeight;
  } else if (hasSpaceTop && !eventBottomLessThanAppBottom) {
    top = appDOMRect.bottom - modalHeight;
  } else {
    top = appDOMRect.top;
  }
  if (appDOMRect.right - eventDOMRect.right > WIDTH_NEEDED) {
    left = eventDOMRect.right + INLINE_SPACE_BETWEEN_MODAL_AND_EVENT;
    animationStart = "-10%";
  } else if (eventDOMRect.left - appDOMRect.left > WIDTH_NEEDED) {
    left = eventDOMRect.left - WIDTH_NEEDED;
    animationStart = "10%";
  } else {
    left = appDOMRect.left;
  }
  document.documentElement.style.setProperty("--sx-event-modal-animation-start", animationStart);
  document.documentElement.style.setProperty("--sx-event-modal-top", `${top}px`);
  document.documentElement.style.setProperty("--sx-event-modal-left", `${left}px`);
};
function TimeIcon({ strokeColor }) {
  return u(k, { children: u("svg", { className: "sx__event-icon", viewBox: "0 0 24 24", fill: "none", xmlns: "http://www.w3.org/2000/svg", children: [u("g", { id: "SVGRepo_bgCarrier", "stroke-width": "0" }), u("g", { id: "SVGRepo_tracerCarrier", "stroke-linecap": "round", "stroke-linejoin": "round" }), u("g", { id: "SVGRepo_iconCarrier", children: [u("path", { d: "M12 8V12L15 15", stroke: strokeColor, "stroke-width": "2", "stroke-linecap": "round" }), u("circle", { cx: "12", cy: "12", r: "9", stroke: strokeColor, "stroke-width": "2" })] })] }) });
}
function UserIcon({ strokeColor }) {
  return u(k, { children: u("svg", { className: "sx__event-icon", viewBox: "0 0 24 24", fill: "none", xmlns: "http://www.w3.org/2000/svg", children: [u("g", { id: "SVGRepo_bgCarrier", "stroke-width": "0" }), u("g", { id: "SVGRepo_tracerCarrier", "stroke-linecap": "round", "stroke-linejoin": "round" }), u("g", { id: "SVGRepo_iconCarrier", children: [u("path", { d: "M15 7C15 8.65685 13.6569 10 12 10C10.3431 10 9 8.65685 9 7C9 5.34315 10.3431 4 12 4C13.6569 4 15 5.34315 15 7Z", stroke: strokeColor, "stroke-width": "2" }), u("path", { d: "M5 19.5C5 15.9101 7.91015 13 11.5 13H12.5C16.0899 13 19 15.9101 19 19.5V20C19 20.5523 18.5523 21 18 21H6C5.44772 21 5 20.5523 5 20V19.5Z", stroke: strokeColor, "stroke-width": "2" })] })] }) });
}
const concatenatePeople = (people) => {
  return people.reduce((acc, person, index) => {
    if (index === 0)
      return person;
    if (index === people.length - 1)
      return `${acc} & ${person}`;
    return `${acc}, ${person}`;
  }, "");
};
function LocationPinIcon({ strokeColor }) {
  return u(k, { children: u("svg", { className: "sx__event-icon", viewBox: "0 0 24 24", fill: "none", xmlns: "http://www.w3.org/2000/svg", children: [u("g", { id: "SVGRepo_bgCarrier", "stroke-width": "0" }), u("g", { id: "SVGRepo_tracerCarrier", "stroke-linecap": "round", "stroke-linejoin": "round" }), u("g", { id: "SVGRepo_iconCarrier", children: [u("g", { "clip-path": "url(#clip0_429_11046)", children: [u("rect", { x: "12", y: "11", width: "0.01", height: "0.01", stroke: strokeColor, "stroke-width": "2", "stroke-linejoin": "round" }), u("path", { d: "M12 22L17.5 16.5C20.5376 13.4624 20.5376 8.53757 17.5 5.5C14.4624 2.46244 9.53757 2.46244 6.5 5.5C3.46244 8.53757 3.46244 13.4624 6.5 16.5L12 22Z", stroke: strokeColor, "stroke-width": "2", "stroke-linejoin": "round" })] }), u("defs", { children: u("clipPath", { id: "clip0_429_11046", children: u("rect", { width: "24", height: "24", fill: "white" }) }) })] })] }) });
}
function DescriptionIcon({ strokeColor }) {
  return u(k, { children: u("svg", { className: "sx__event-icon", viewBox: "0 0 24 24", fill: "none", xmlns: "http://www.w3.org/2000/svg", children: [u("g", { id: "SVGRepo_bgCarrier", "stroke-width": "0" }), u("g", { id: "SVGRepo_tracerCarrier", "stroke-linecap": "round", "stroke-linejoin": "round" }), u("g", { id: "SVGRepo_iconCarrier", children: [u("rect", { x: "4", y: "4", width: "16", height: "16", rx: "3", stroke: strokeColor, "stroke-width": "2" }), u("path", { d: "M16 10L8 10", stroke: strokeColor, "stroke-width": "2", "stroke-linecap": "round" }), u("path", { d: "M16 14L8 14", stroke: strokeColor, "stroke-width": "2", "stroke-linecap": "round" })] })] }) });
}
const toIntegers = (dateTimeSpecification) => {
  const hours = dateTimeSpecification.slice(11, 13), minutes = dateTimeSpecification.slice(14, 16);
  return {
    year: Number(dateTimeSpecification.slice(0, 4)),
    month: Number(dateTimeSpecification.slice(5, 7)) - 1,
    date: Number(dateTimeSpecification.slice(8, 10)),
    hours: hours !== "" ? Number(hours) : void 0,
    minutes: minutes !== "" ? Number(minutes) : void 0
  };
};
const dateFn = (dateTimeString, locale) => {
  const { year, month, date } = toIntegers(dateTimeString);
  return new Date(year, month, date).toLocaleDateString(locale, {
    day: "numeric",
    month: "long",
    year: "numeric"
  });
};
const timeFn = (dateTimeString, locale) => {
  const { year, month, date, hours, minutes } = toIntegers(dateTimeString);
  return new Date(year, month, date, hours, minutes).toLocaleTimeString(locale, {
    hour: "numeric",
    minute: "numeric"
  });
};
const getTimeStamp = (calendarEvent, locale, delimiter = "–") => {
  const eventTime = { start: calendarEvent.start, end: calendarEvent.end };
  if (calendarEvent._isSingleDayFullDay) {
    return dateFn(eventTime.start, locale);
  }
  if (calendarEvent._isMultiDayFullDay) {
    return `${dateFn(eventTime.start, locale)} ${delimiter} ${dateFn(eventTime.end, locale)}`;
  }
  if (calendarEvent._isSingleDayTimed && eventTime.start !== eventTime.end) {
    return `${dateFn(eventTime.start, locale)} <span aria-hidden="true">⋅</span> ${timeFn(eventTime.start, locale)} ${delimiter} ${timeFn(eventTime.end, locale)}`;
  }
  if (calendarEvent._isSingleDayTimed && calendarEvent.start === calendarEvent.end) {
    return `${dateFn(eventTime.start, locale)}, ${timeFn(eventTime.start, locale)}`;
  }
  return `${dateFn(eventTime.start, locale)}, ${timeFn(eventTime.start, locale)} ${delimiter} ${dateFn(eventTime.end, locale)}, ${timeFn(eventTime.end, locale)}`;
};
const useIconColors = ($app) => {
  const ICON_COLOR_LIGHT_MODE = "var(--sx-internal-color-text)";
  const ICON_COLOR_DARK_MODE = "var(--sx-color-neutral-variant)";
  const iconColor = d($app.calendarState.isDark.value ? ICON_COLOR_DARK_MODE : ICON_COLOR_LIGHT_MODE);
  E(() => {
    if ($app.calendarState.isDark.value)
      iconColor.value = ICON_COLOR_DARK_MODE;
    else
      iconColor.value = ICON_COLOR_LIGHT_MODE;
  });
  return iconColor;
};
const isScrollable = (el) => {
  if (el) {
    const hasScrollableContent = el.scrollHeight > el.clientHeight;
    const overflowYStyle = window.getComputedStyle(el).overflowY;
    const isOverflowHidden = overflowYStyle.indexOf("hidden") !== -1;
    return hasScrollableContent && !isOverflowHidden;
  }
  return true;
};
const getScrollableParents = (el, acc = []) => {
  if (!el || el === document.body || el.nodeType === Node.DOCUMENT_FRAGMENT_NODE) {
    acc.push(window);
    return acc;
  }
  if (isScrollable(el)) {
    acc.push(el);
  }
  return getScrollableParents(el.assignedSlot ? el.assignedSlot.parentNode : el.parentNode, acc);
};
const nextTick = (cb) => {
  setTimeout(() => {
    cb();
  });
};
function EventModal({ $app }) {
  const [modalId] = h(randomStringId());
  const { value: calendarEvent } = $app.config.plugins.eventModal.calendarEvent;
  const [isDisplayed, setIsDisplayed] = h(false);
  const customComponent = $app.config._customComponentFns.eventModal;
  const [eventWrapperStyle, setEventWrapperStyle] = h("sx__event-modal");
  const callSetPosition = () => {
    var _a, _b, _c;
    setPosition((_a = $app.elements.calendarWrapper) === null || _a === void 0 ? void 0 : _a.getBoundingClientRect(), (_b = $app.config.plugins.eventModal) === null || _b === void 0 ? void 0 : _b.calendarEventDOMRect.value, ((_c = $app.elements.calendarWrapper) === null || _c === void 0 ? void 0 : _c.querySelector(".sx__event-modal")).clientHeight);
  };
  const scrollListener = () => {
    var _a, _b;
    $app.config.plugins.eventModal.calendarEventDOMRect.value = (_b = (_a = $app.config.plugins.eventModal) === null || _a === void 0 ? void 0 : _a.calendarEventElement.value) === null || _b === void 0 ? void 0 : _b.getBoundingClientRect();
    callSetPosition();
  };
  y(() => {
    var _a;
    if (customComponent) {
      customComponent(document.querySelector(`[data-ccid=${modalId}]`), {
        calendarEvent: calendarEvent === null || calendarEvent === void 0 ? void 0 : calendarEvent._getExternalEvent()
      });
    } else {
      setEventWrapperStyle(eventWrapperStyle.concat(" sx__event-modal-default"));
    }
    nextTick(() => {
      callSetPosition();
    });
    setIsDisplayed(true);
    const clickOutsideListener = createClickOutsideListener($app, modalId);
    const scrollableAncestors = getScrollableParents(((_a = $app.config.plugins.eventModal) === null || _a === void 0 ? void 0 : _a.calendarEventElement.value) || null);
    scrollableAncestors.forEach((el) => el.addEventListener("scroll", scrollListener));
    document.addEventListener("click", clickOutsideListener);
    return () => {
      document.removeEventListener("click", clickOutsideListener);
      scrollableAncestors.forEach((el) => el.removeEventListener("scroll", scrollListener));
    };
  }, []);
  const iconColor = useIconColors($app);
  return u(k, { children: calendarEvent && u("div", { id: modalId, tabIndex: 0, "data-ccid": modalId, className: `${eventWrapperStyle}${isDisplayed ? " is-open" : ""}`, children: !customComponent && u(k, { children: [u("div", { className: "sx__has-icon sx__event-modal__title", children: [u("div", { style: {
    backgroundColor: `var(--sx-color-${calendarEvent._color}-container)`
  }, className: "sx__event-modal__color-icon sx__event-icon" }), calendarEvent.title] }), u("div", { className: "sx__has-icon sx__event-modal__time", children: [u(TimeIcon, { strokeColor: iconColor.value }), u("div", { dangerouslySetInnerHTML: {
    __html: getTimeStamp(calendarEvent, $app.config.locale.value)
  } })] }), calendarEvent.people && calendarEvent.people.length && u("div", { className: "sx__has-icon sx__event-modal__people", children: [u(UserIcon, { strokeColor: iconColor.value }), concatenatePeople(calendarEvent.people)] }), calendarEvent.location && u("div", { className: "sx__has-icon sx__event-modal__location", children: [u(LocationPinIcon, { strokeColor: iconColor.value }), calendarEvent.location] }), calendarEvent.description && u("div", { className: "sx__has-icon sx__event-modal__description", children: [u(DescriptionIcon, { strokeColor: iconColor.value }), calendarEvent.description] })] }) }) });
}
const definePlugin = (name, definition) => {
  definition.name = name;
  return definition;
};
class EventModalPluginImpl {
  constructor() {
    Object.defineProperty(this, "name", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: PluginName.EventModal
    });
    Object.defineProperty(this, "calendarEvent", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: d(null)
    });
    Object.defineProperty(this, "calendarEventDOMRect", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: d(null)
    });
    Object.defineProperty(this, "calendarEventElement", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: d(null)
    });
    Object.defineProperty(this, "ComponentFn", {
      enumerable: true,
      configurable: true,
      writable: true,
      value: EventModal
    });
  }
  setCalendarEvent(event, eventTargetDOMRect) {
    r(() => {
      this.calendarEvent.value = event;
      this.calendarEventDOMRect.value = eventTargetDOMRect;
    });
  }
  close() {
    r(() => {
      this.calendarEvent.value = null;
      this.calendarEventDOMRect.value = null;
    });
  }
}
const createEventModalPlugin = () => {
  return definePlugin("eventModal", new EventModalPluginImpl());
};
const eventsServicePlugin = createEventsServicePlugin();
const calendarControls = createCalendarControlsPlugin();
const eventModal = createEventModalPlugin();
const createCalendarConfig = (vm) => ({
  locale: "fr-FR",
  dayBoundaries: {
    start: "08:00",
    end: "21:00"
  },
  weekOptions: {
    gridHeight: 900,
    eventWidth: 95
  },
  views: [
    createViewDay(),
    createViewWeek()
  ],
  events: [],
  plugins: [
    eventModal,
    eventsServicePlugin,
    calendarControls
  ],
  callbacks: {
    onRender($app) {
      const range = $app.calendarState.range.value;
      let start = new Date(range.start);
      let end = new Date(range.end);
      if (start.getDate() === end.getDate()) {
        vm.getEventsAvailabilities(range.start, range.end);
      } else {
        vm.getEventsSlots(range.start, range.end);
      }
    },
    onRangeUpdate(range) {
      let start = new Date(range.start);
      let end = new Date(range.end);
      if (start.getDate() === end.getDate()) {
        vm.getEventsAvailabilities(range.start, range.end);
      } else {
        vm.getEventsSlots(range.start, range.end);
      }
    }
  }
});
const _sfc_main = {
  name: "Calendar",
  components: { EventDay, EventModal: EventModal$1, ScheduleXCalendar: _o },
  props: {
    items: {
      type: Object,
      required: true
    },
    editAction: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      calendarApp: shallowRef(null),
      view: "week"
    };
  },
  created() {
    this.initCalendar();
  },
  methods: {
    initCalendar() {
      const vm = {
        getEventsSlots: this.getEventsSlots,
        getEventsAvailabilities: this.getEventsAvailabilities
      };
      this.calendarApp = createCalendar(createCalendarConfig(vm));
    },
    getEventsSlots(start, end) {
      this.view = "week";
      const calendars = {};
      if (this.items.events && this.items.events.length > 0) {
        for (const item of this.items.events) {
          calendars[item.id] = {
            colorName: item.id,
            lightColors: {
              main: item.color.main,
              container: item.color.container,
              onContainer: item.color.onContainer
            }
          };
        }
        calendarControls.setCalendars(calendars);
        let eventsIds = this.items.events.map((event) => event.id);
        eventsIds = eventsIds.join(",");
        eventsService.getEventsSlots(start, end, eventsIds).then((response) => {
          if (response.status) {
            let events = [];
            response.data.forEach((event) => {
              event.title = event.name;
              if (event.people) {
                event.people = event.people.split(",");
              }
              event.calendarId = event.event_id;
              events.push(event);
            });
            calendarControls.setWeekOptions({
              gridHeight: 900,
              eventWidth: 95
            });
            eventsServicePlugin.set(events);
          }
        });
      }
    },
    getEventsAvailabilities(start, end) {
      this.view = "day";
      const calendars = {};
      if (this.items.events && this.items.events.length > 0) {
        for (const item of this.items.events) {
          calendars[item.id] = {
            colorName: item.id,
            lightColors: {
              main: item.color.main,
              container: item.color.container,
              onContainer: item.color.onContainer
            }
          };
        }
        calendarControls.setCalendars(calendars);
        let min_duration = null;
        let eventsIds = this.items.events.map((event) => event.id);
        eventsIds = eventsIds.join(",");
        eventsService.getEventsAvailabilities(start, end, eventsIds).then((response) => {
          if (response.status) {
            let events = [];
            response.data.forEach((event) => {
              event.title = event.name;
              if (event.people) {
                event.people = event.people.split(",");
              }
              event.calendarId = event.event_id;
              events.push(event);
              if (event.slot_duration_type == "hours") {
                event.slot_duration = event.slot_duration * 60;
              }
              if (!min_duration) {
                min_duration = event.slot_duration;
              }
              min_duration = Math.min(min_duration, event.slot_duration);
            });
            calendarControls.setWeekOptions({
              gridHeight: this.updateGridHeight(min_duration),
              eventWidth: 95
            });
            eventsServicePlugin.set(events);
          }
        });
      }
    },
    updateGridHeight(slot_duration) {
      const minSlotDuration = 5;
      const maxSlotDuration = 60;
      if (slot_duration > maxSlotDuration) {
        return 900;
      }
      slot_duration = Math.max(minSlotDuration, Math.min(maxSlotDuration, slot_duration));
      const gridHeight = 3e3 * (1 - (slot_duration - minSlotDuration) / (maxSlotDuration - minSlotDuration));
      return Math.round(gridHeight);
    }
  }
};
const _hoisted_1 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_EventDay = resolveComponent("EventDay");
  const _component_EventModal = resolveComponent("EventModal");
  const _component_ScheduleXCalendar = resolveComponent("ScheduleXCalendar");
  return $data.calendarApp ? (openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_ScheduleXCalendar, { "calendar-app": $data.calendarApp }, {
      timeGridEvent: withCtx(({ calendarEvent }) => [
        createVNode(_component_EventDay, {
          "calendar-event": calendarEvent,
          view: $data.view
        }, null, 8, ["calendar-event", "view"])
      ]),
      eventModal: withCtx(({ calendarEvent }) => [
        createVNode(_component_EventModal, {
          "calendar-event": calendarEvent,
          editAction: $props.editAction,
          onEditEvent: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("on-click-action", $props.editAction))
        }, null, 8, ["calendar-event", "editAction"])
      ]),
      _: 1
    }, 8, ["calendar-app"])
  ])) : createCommentVNode("", true);
}
const Calendar = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Calendar as default
};
