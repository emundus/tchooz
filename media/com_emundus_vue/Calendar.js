import { _ as _export_sfc, u as useGlobalStore, c as createElementBlock, o as openBlock, d as createBaseVNode, b as createCommentVNode, w as withDirectives, R as vModelCheckbox, j as normalizeStyle, t as toDisplayString, F as Fragment, r as resolveComponent, g as createVNode, n as normalizeClass, M as Modal, i as shallowRef, a as createBlock, f as withCtx, a8 as resolveDynamicComponent, h as withModifiers, p as Teleport, e as renderList, v as vShow } from "./app_emundus.js";
import { e as colors, f as d, r, g as d$1, y, u, k, h as E, _ as _o, E as EventDay, c as createEventsServicePlugin, b as createCalendarControlsPlugin, a as createCalendar, m as mergeLocales, d as createViewWeek, i as createViewDay, t as translations } from "./core.js";
import EditSlot from "./EditSlot.js";
import { e as eventsService } from "./events2.js";
import "./index.js";
import "./Parameter.js";
import "./EventBooking.js";
import "./Info.js";
import "./ColorPicker.js";
import "./LocationPopup.js";
import "./LocationForm.js";
const _sfc_main$2 = {
  name: "EventInformations",
  props: {
    calendarEvent: {
      type: Object,
      required: true
    },
    canBeSelected: {
      type: Boolean,
      default: false
    }
  },
  mixins: [colors],
  data() {
    return {
      eventStartDate: null,
      eventEndDate: null,
      actualLanguage: "fr-FR"
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
    }
  },
  computed: {
    eventDay() {
      return this.eventStartDate.toLocaleDateString(this.actualLanguage, {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric"
      });
    },
    eventHours() {
      return this.eventStartDate.toLocaleTimeString(this.actualLanguage, {
        hour: "2-digit",
        minute: "2-digit"
      }) + " - " + this.eventEndDate.toLocaleTimeString(this.actualLanguage, {
        hour: "2-digit",
        minute: "2-digit"
      });
    },
    brightnessColor() {
      return this.lightenColor(this.calendarEvent.color, 90);
    },
    calendarStyle() {
      if (this.calendarEvent.show) {
        return {
          backgroundColor: this.calendarEvent.color,
          borderColor: this.calendarEvent.color
        };
      } else {
        return {
          backgroundColor: this.lightenColor(this.calendarEvent.color, 90),
          borderColor: this.calendarEvent.color
        };
      }
    }
  }
};
const _hoisted_1$2 = { class: "tw-flex tw-items-start tw-gap-2" };
const _hoisted_2$1 = { class: "tw-overflow-hidden tw-text-ellipsis tw-font-semibold" };
const _hoisted_3$1 = {
  key: 0,
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_4$1 = {
  key: 1,
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_5$1 = {
  key: 2,
  class: "tw-flex tw-items-start tw-gap-2"
};
const _hoisted_6$1 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_7$1 = { class: "" };
const _hoisted_8 = { class: "tw-overflow-hidden tw-text-ellipsis tw-whitespace-nowrap" };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock(Fragment, null, [
    createBaseVNode("div", _hoisted_1$2, [
      $props.canBeSelected ? withDirectives((openBlock(), createElementBlock("input", {
        key: 0,
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $props.calendarEvent.show = $event),
        type: "checkbox",
        class: "event-checkbox tw-relative !tw-h-[20px] tw-w-[20px] tw-cursor-pointer tw-appearance-none tw-rounded-md",
        style: normalizeStyle($options.calendarStyle)
      }, null, 4)), [
        [vModelCheckbox, $props.calendarEvent.show]
      ]) : (openBlock(), createElementBlock("div", {
        key: 1,
        class: "tw-min-h-[20px] tw-min-w-[20px] tw-rounded-md",
        style: normalizeStyle({ backgroundColor: this.lightenColor($props.calendarEvent.color, 90) })
      }, null, 4)),
      createBaseVNode("p", _hoisted_2$1, toDisplayString($props.calendarEvent.title ? $props.calendarEvent.title : $props.calendarEvent.name), 1)
    ]),
    $data.eventStartDate ? (openBlock(), createElementBlock("div", _hoisted_3$1, [
      _cache[1] || (_cache[1] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-900" }, "calendar_today", -1)),
      createBaseVNode("p", null, toDisplayString($options.capitalizeFirstLetter($options.eventDay)), 1)
    ])) : createCommentVNode("", true),
    $data.eventStartDate ? (openBlock(), createElementBlock("div", _hoisted_4$1, [
      _cache[2] || (_cache[2] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-900" }, "schedule", -1)),
      createBaseVNode("p", null, toDisplayString($options.eventHours), 1)
    ])) : createCommentVNode("", true),
    $props.calendarEvent.location ? (openBlock(), createElementBlock("div", _hoisted_5$1, [
      _cache[3] || (_cache[3] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-900" }, "location_on", -1)),
      createBaseVNode("p", null, toDisplayString($props.calendarEvent.location), 1)
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_6$1, [
      _cache[4] || (_cache[4] = createBaseVNode("span", { class: "material-symbols-outlined tw-text-neutral-900" }, "groups", -1)),
      createBaseVNode("p", _hoisted_7$1, toDisplayString($props.calendarEvent.booked_count) + " / " + toDisplayString($props.calendarEvent.availabilities_count), 1),
      createBaseVNode("p", _hoisted_8, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_BOOKED_SLOT_NUMBER")), 1)
    ])
  ], 64);
}
const EventInformations = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-8e53e84d"]]);
const _sfc_main$1 = {
  name: "EventModal",
  components: { EventInformations },
  props: {
    calendarEvent: {
      type: Object,
      required: true
    },
    editAction: {
      type: String
    },
    view: {
      type: String,
      required: true
    }
  },
  mixins: [colors],
  data() {
    return {
      popupPosition: ""
    };
  },
  created() {
    setTimeout(() => {
      this.setPopupPosition();
    }, 150);
  },
  methods: {
    editEvent() {
      this.$emit("edit-event", this.editAction, this.calendarEvent.event_id);
    },
    setPopupPosition() {
      const modal = document.getElementsByClassName("card-event");
      const event = document.querySelector('div[data-event-id="' + this.calendarEvent.id + '"]');
      if (modal[0] && event) {
        const modalPosition = modal[0].getBoundingClientRect().left;
        const eventPosition = event.getBoundingClientRect().left;
        if (modalPosition > eventPosition) {
          this.popupPosition = "left";
        } else {
          this.popupPosition = "right";
        }
      }
    }
  }
};
const _hoisted_1$1 = { class: "tw-flex tw-justify-end" };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_EventInformations = resolveComponent("EventInformations");
  return $props.view == "week" ? (openBlock(), createElementBlock("div", {
    key: 0,
    class: normalizeClass(["card-event tw-flex tw-flex-col tw-gap-2 tw-rounded-lg tw-border-neutral-400 tw-px-6 tw-py-4 tw-shadow", {
      "card-event-left": $data.popupPosition === "left",
      "card-event-right": $data.popupPosition === "right"
    }]),
    style: normalizeStyle({
      borderColor: $props.calendarEvent.color,
      "--event-arrow-color": $props.calendarEvent.color
    })
  }, [
    createVNode(_component_EventInformations, { "calendar-event": $props.calendarEvent }, null, 8, ["calendar-event"]),
    createBaseVNode("div", _hoisted_1$1, [
      createBaseVNode("button", {
        type: "button",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.editEvent && $options.editEvent(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_EDIT_ITEM")), 1)
    ])
  ], 6)) : createCommentVNode("", true);
}
const EventModal$1 = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-df7e6317"]]);
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
  const [modalId] = d$1(randomStringId());
  const { value: calendarEvent } = $app.config.plugins.eventModal.calendarEvent;
  const [isDisplayed, setIsDisplayed] = d$1(false);
  const customComponent = $app.config._customComponentFns.eventModal;
  const [eventWrapperStyle, setEventWrapperStyle] = d$1("sx__event-modal");
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
  defaultView: vm.defaultView,
  dayBoundaries: {
    start: "08:00",
    end: "21:00"
  },
  weekOptions: {
    gridHeight: 2500,
    eventWidth: 95,
    eventOverlap: false
  },
  views: [createViewWeek(), createViewDay()],
  events: [],
  plugins: [eventModal, eventsServicePlugin, calendarControls],
  callbacks: {
    onRender($app) {
      const range = $app.calendarState.range.value;
      let start = /* @__PURE__ */ new Date();
      let startString = start.toISOString().split("T")[0];
      if (vm.items.registrants && vm.items.registrants.length > 0) {
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
          startString = start.toISOString().split("T")[0];
        }
      }
      calendarControls.setDate(startString);
      if (vm.normalizeDate(startString) >= vm.normalizeDate(range.start) && vm.normalizeDate(startString) <= vm.normalizeDate(range.end)) {
        if (calendarControls.getView() === "day") {
          vm.getEventsAvailabilities(range.start, range.end);
        } else {
          vm.getEventsSlots(range.start, range.end);
        }
      }
    },
    onRangeUpdate(range) {
      if (calendarControls.getView() === "day") {
        vm.getEventsAvailabilities(range.start, range.end);
      } else {
        vm.getEventsSlots(range.start, range.end);
      }
    }
  },
  translations: mergeLocales(translations, {
    frFR: {
      Week: "Vue semaine",
      Day: "Vue jour",
      Today: "Revenir à aujourd'hui"
    },
    enGB: {
      Week: "Week View",
      Day: "Day View",
      Today: "Back to today"
    }
  })
});
const _sfc_main = {
  name: "Calendar",
  components: {
    Modal,
    EventInformations,
    EventDay,
    EventModal: EventModal$1,
    ScheduleXCalendar: _o,
    EditSlot
  },
  props: {
    items: {
      type: Object,
      required: true
    },
    editWeekAction: {
      type: String,
      required: true
    }
  },
  mixins: [colors],
  emits: ["valueUpdated", "update-items"],
  data() {
    return {
      actualLanguage: "fr",
      calendarApp: shallowRef(null),
      view: "week",
      calendars: {},
      showModal: false,
      currentSlot: null
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
      const view = sessionStorage.getItem("tchooz_calendar_view/" + document.location.hostname);
      const vm = {
        getEventsSlots: this.getEventsSlots,
        getEventsAvailabilities: this.getEventsAvailabilities,
        normalizeDate: this.normalizeDate,
        items: this.items,
        defaultView: view ? view : "week"
      };
      this.calendarApp = createCalendar(createCalendarConfig(vm));
    },
    getEventsSlots(start, end) {
      this.view = "week";
      this.calendars = {};
      if (this.items.registrants && this.items.registrants.length > 0) {
        let eventsIds = this.items.registrants.map((event) => event.id);
        eventsIds = eventsIds.join(",");
        eventsService.getEventsSlots(start, end, eventsIds).then(async (response) => {
          if (response.status && response.data.length > 0) {
            for (const item of this.items.registrants) {
              if (item.availabilities_count === 0) {
                continue;
              }
              this.calendars["calendar_" + item.id] = this.buildCalendar(item, true);
            }
            let events = await this.prepareEvents(response.data);
            if (events.length > 0) {
              const calendarsToShow = Object.keys(this.calendars).filter((key) => this.calendars[key].show);
              calendarControls.setCalendars(calendarsToShow);
              calendarControls.setWeekOptions({
                gridHeight: 1e3,
                eventWidth: 95
              });
              eventsServicePlugin.set(events);
            }
          }
        });
      }
    },
    getEventsAvailabilities(start, end) {
      this.view = "day";
      this.calendars = {};
      if (this.items.registrants && this.items.registrants.length > 0) {
        let eventsIds = this.items.registrants.map((event) => event.id);
        eventsIds = eventsIds.join(",");
        eventsService.getEventsAvailabilities(start, end, eventsIds).then(async (response) => {
          if (response.status && response.data.length > 0) {
            for (const item of this.items.registrants) {
              if (item.availabilities_count === 0) {
                continue;
              }
              this.calendars["calendar_" + item.id] = this.buildCalendar(item);
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
              for (const key in this.calendars) {
                if (this.calendars[key].events.length === 0) {
                  delete this.calendars[key];
                }
              }
              const calendarsToShow = Object.keys(this.calendars).filter((key) => this.calendars[key].show);
              calendarControls.setCalendars(calendarsToShow);
              calendarControls.setWeekOptions({
                gridHeight: 1800,
                eventWidth: 95
              });
              eventsServicePlugin.set(events);
            } else {
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
        id: "calendar_" + item.id,
        colorName: "calendar_" + item.id,
        lightColors: {
          main: item.color,
          container: item.color,
          onContainer: item.color
        },
        color: item.color,
        name: item.label[this.actualLanguage],
        location: item.location,
        availabilities_count: 0,
        booked_count: 0,
        show: defaultShow,
        events: [],
        columnSize: 0
      };
    },
    prepareEvents(datas, check_show = true) {
      return new Promise((resolve) => {
        let events = [];
        let columns = [];
        let calendarSizes = {};
        if (check_show) {
          datas = datas.filter((event) => this.calendars["calendar_" + event.event_id].show);
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
          if (event.people && typeof event.people === "string") {
            event.people = event.people.split(",");
          }
          event.calendarId = "calendar_" + event.event_id;
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
            event.left = `calc(${colIndex / totalColumns * 100}%)`;
            events.push(event);
          });
        });
        Object.keys(calendarSizes).forEach((event_id) => {
          let calendarKey = "calendar_" + event_id;
          if (this.calendars[calendarKey]) {
            this.calendars[calendarKey].columnSize = calendarSizes[event_id];
          }
        });
        resolve(events);
      });
    },
    editEvent(action, id) {
      this.$emit("on-click-action", action, id);
    },
    calendarStyle(calendar) {
      let style = {
        borderColor: calendar.color
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
          borderColor: calendar.color
        };
      } else {
        return {
          backgroundColor: this.lightenColor(calendar.color, 90),
          borderColor: calendar.color
        };
      }
    },
    updateItems() {
      this.$emit("update-items");
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
      const d2 = new Date(date);
      d2.setHours(0, 0, 0, 0);
      return d2;
    }
  },
  watch: {
    view(value) {
      sessionStorage.setItem("tchooz_calendar_view/" + document.location.hostname, value);
    }
  }
};
const _hoisted_1 = {
  key: 1,
  class: "tw-flex tw-flex-col tw-gap-4"
};
const _hoisted_2 = ["onClick"];
const _hoisted_3 = ["checked"];
const _hoisted_4 = { style: { "word-wrap": "anywhere" } };
const _hoisted_5 = { class: "tw-flex tw-flex-col tw-gap-4" };
const _hoisted_6 = {
  key: 0,
  class: "calendars-list tw-grid tw-gap-3",
  style: { "padding-left": "var(--sx-calendar-week-grid-padding-left)" }
};
const _hoisted_7 = ["onClick"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_modal = resolveComponent("modal");
  const _component_EventInformations = resolveComponent("EventInformations");
  const _component_EventDay = resolveComponent("EventDay");
  const _component_EventModal = resolveComponent("EventModal");
  const _component_ScheduleXCalendar = resolveComponent("ScheduleXCalendar");
  return $data.calendarApp ? (openBlock(), createElementBlock("div", {
    key: 0,
    class: normalizeClass({
      "day-grid tw-grid tw-gap-4": $data.view === "day"
    })
  }, [
    $data.showModal ? (openBlock(), createBlock(Teleport, {
      key: 0,
      to: ".com_emundus_vue"
    }, [
      createVNode(_component_modal, {
        name: "modal-component",
        transition: "nice-modal-fade",
        class: normalizeClass("placement-center tw-max-h-[80vh] tw-overflow-y-auto tw-rounded tw-px-4 tw-shadow-modal"),
        width: "600px",
        delay: 100,
        adaptive: true,
        clickToClose: false,
        onClick: _cache[2] || (_cache[2] = withModifiers(() => {
        }, ["stop"]))
      }, {
        default: withCtx(() => [
          (openBlock(), createBlock(resolveDynamicComponent("EditSlot"), {
            slot: this.currentSlot,
            onClose: _cache[0] || (_cache[0] = ($event) => $options.closePopup()),
            onUpdateItems: _cache[1] || (_cache[1] = ($event) => $options.updateItems())
          }, null, 40, ["slot"]))
        ]),
        _: 1
      })
    ])) : createCommentVNode("", true),
    $data.view === "day" ? (openBlock(), createElementBlock("div", _hoisted_1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.calendars, (calendar) => {
        return openBlock(), createElementBlock("div", {
          class: "tw-flex tw-cursor-pointer tw-gap-2",
          onClick: ($event) => $options.toggleCalendar(calendar)
        }, [
          createBaseVNode("input", {
            checked: calendar.show,
            type: "checkbox",
            style: normalizeStyle($options.checkboxCalendarStyle(calendar)),
            class: "event-checkbox tw-relative !tw-h-[20px] tw-w-[20px] tw-cursor-pointer tw-appearance-none tw-rounded-md"
          }, null, 12, _hoisted_3),
          createBaseVNode("p", _hoisted_4, toDisplayString(calendar.title ? calendar.title : calendar.name), 1)
        ], 8, _hoisted_2);
      }), 256))
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_5, [
      $data.view === "day" ? (openBlock(), createElementBlock("div", _hoisted_6, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.calendars, (calendar) => {
          return withDirectives((openBlock(), createElementBlock("div", {
            class: "tw-flex tw-w-full tw-cursor-pointer tw-flex-col tw-gap-2 tw-rounded-lg tw-border-neutral-400 tw-bg-white tw-px-6 tw-py-4 tw-shadow",
            style: normalizeStyle($options.calendarStyle(calendar)),
            onClick: ($event) => $options.toggleCalendar(calendar)
          }, [
            createVNode(_component_EventInformations, {
              "calendar-event": calendar,
              "can-be-selected": true
            }, null, 8, ["calendar-event"])
          ], 12, _hoisted_7)), [
            [vShow, calendar.show]
          ]);
        }), 256))
      ])) : createCommentVNode("", true),
      createVNode(_component_ScheduleXCalendar, { "calendar-app": $data.calendarApp }, {
        timeGridEvent: withCtx(({ calendarEvent }) => [
          $data.calendars && Object.keys(this.calendars).length > 0 ? (openBlock(), createBlock(_component_EventDay, {
            key: 0,
            "calendar-event": calendarEvent,
            view: $data.view,
            onUpdateItems: $options.updateItems,
            onEditModal: $options.openModal
          }, null, 8, ["calendar-event", "view", "onUpdateItems", "onEditModal"])) : createCommentVNode("", true)
        ]),
        eventModal: withCtx(({ calendarEvent }) => [
          createVNode(_component_EventModal, {
            "calendar-event": calendarEvent,
            editAction: $props.editWeekAction,
            onEditEvent: $options.editEvent,
            view: $data.view
          }, null, 8, ["calendar-event", "editAction", "onEditEvent", "view"])
        ]),
        _: 1
      }, 8, ["calendar-app"])
    ])
  ], 2)) : createCommentVNode("", true);
}
const Calendar = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-ac25df4a"]]);
export {
  Calendar as default
};
