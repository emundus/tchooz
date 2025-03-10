import { e as eventsService } from "./events2.js";
import { _ as _export_sfc, u as useGlobalStore, r as resolveComponent, o as openBlock, c as createElementBlock, d as createBaseVNode, t as toDisplayString, j as normalizeStyle, F as Fragment, e as renderList, n as normalizeClass, b as createCommentVNode, a as createBlock } from "./app_emundus.js";
import { I as Info } from "./Info.js";
const _sfc_main = {
  name: "EventBooking",
  components: { Info },
  props: {
    componentsProps: {
      type: Object,
      required: false
    }
  },
  emits: ["valueUpdated"],
  data() {
    return {
      loading: false,
      myBookings: [],
      currentStartIndex: 0,
      availableDates: [],
      slots: [],
      slotSelected: null,
      name: null,
      currentTimezone: {
        offset: 0,
        name: ""
      },
      location: 0
    };
  },
  created() {
    this.name = useGlobalStore().getDatas.name_element ? useGlobalStore().getDatas.name_element.value : null;
    this.currentTimezone.offset = useGlobalStore().getDatas.offset ? useGlobalStore().getDatas.offset.value : "1";
    this.currentTimezone.name = useGlobalStore().getDatas.timezone ? useGlobalStore().getDatas.timezone.value : "Europe/Paris";
    let location_filter_elt = useGlobalStore().getDatas.location_filter_elt ? useGlobalStore().getDatas.location_filter_elt.value : null;
    if (location_filter_elt && location_filter_elt !== "" && document.getElementById(location_filter_elt)) {
      location_filter_elt = document.getElementById(location_filter_elt);
    }
    if (!this.$props.componentsProps) {
      this.getMyBookings().then((bookings) => {
        this.myBookings = bookings;
        if (this.myBookings.length > 0) {
          this.slotSelected = this.myBookings[0].availability;
        }
        if (this.myBookings.length === 0 && location_filter_elt) {
          location_filter_elt.addEventListener("change", (event) => {
            this.location = event.target.value;
            if (this.location && this.location !== 0 && this.location !== "0" && this.location !== "") {
              this.getSlots();
            } else {
              this.slots = [];
              this.availableDates = [];
            }
          });
          if (this.location && this.location !== 0 && this.location !== "0" && this.location !== "") {
            this.getSlots();
          }
        } else {
          this.getSlots();
        }
      });
    }
    this.getSlots();
  },
  methods: {
    async getMyBookings() {
      return new Promise((resolve, reject) => {
        eventsService.getMyBookings().then((response) => {
          if (response.status) {
            resolve(response.data);
          } else {
            console.error("Error when try to retrieve my bookings", response.error);
            reject([]);
          }
        });
      });
    },
    async getSlots() {
      this.loading = true;
      try {
        const responseSlots = await eventsService.getAvailabilitiesByCampaignsAndPrograms(
          (/* @__PURE__ */ new Date()).toISOString().split("T"),
          "",
          this.location,
          1,
          this.$props.componentsProps ? [this.$props.componentsProps.event_id] : []
        );
        let slots = responseSlots.data;
        const groupedSlots = slots.reduce((accumulator, slot) => {
          const key = `${slot.start}_${slot.end}_${slot.event_id}`;
          if (!accumulator[key]) {
            accumulator[key] = {
              slots: [],
              totalCapacity: 0,
              totalBookers: 0,
              start: slot.start,
              end: slot.end,
              event_id: slot.event_id
            };
          }
          accumulator[key].slots.push({ ...slot, bookers: 0 });
          accumulator[key].totalCapacity += slot.capacity;
          return accumulator;
        }, {});
        const responseRegistrants = await eventsService.getAvailabilityRegistrants();
        const registrants = responseRegistrants.data;
        Object.values(groupedSlots).forEach((group) => {
          group.slots.forEach((slot) => {
            const slotRegistrants = registrants.filter((registrant) => registrant.availability === slot.id);
            slot.bookers = slotRegistrants.length;
            group.totalBookers += slot.bookers;
          });
        });
        this.slots = Object.values(groupedSlots);
        this.slots.sort((a, b) => new Date(a.start) - new Date(b.start));
        this.availableDates = [...new Set(this.slots.map((slot) => new Date(slot.start).toISOString().split("T")[0]))];
        this.availableDates.sort((a, b) => new Date(a) - new Date(b));
        if (this.$props.componentsProps) {
          const slotId = this.$props.componentsProps.slot_id;
          const isSlotIdValid = this.slots.some((slotGroup) => slotGroup.slots.some((slot) => slot.id === slotId));
          if (isSlotIdValid) {
            this.slotSelected = slotId;
          }
        }
        this.loading = false;
      } catch (error) {
        console.error("Erreur lors de la récupération des créneaux ou des registrants :", error);
        this.loading = false;
      }
    },
    /*updateVisibleDates() {
          if (this.currentStartIndex >= this.availableDates.length) {
            this.currentStartIndex = Math.max(0, this.availableDates.length - 3);
          }
          if (this.currentStartIndex < 0) {
            this.currentStartIndex = 0;
          }
    
          this.visibleDates = this.availableDates
              .slice(this.currentStartIndex, this.currentStartIndex + 3)
              .map(dateString => new Date(dateString));
        },*/
    formatDay(date) {
      return date.toLocaleDateString("fr-FR", { weekday: "long" }).charAt(0).toUpperCase() + date.toLocaleDateString("fr-FR", { weekday: "long" }).slice(1);
    },
    formatShortDate(date) {
      return date.toLocaleDateString("fr-FR", { day: "numeric", month: "short" });
    },
    nextDates() {
      if (this.currentStartIndex + 3 < this.availableDates.length) {
        this.currentStartIndex += 3;
      }
    },
    previousDates() {
      if (this.currentStartIndex > 0) {
        this.currentStartIndex -= 3;
      }
    },
    getAvailableSlotsForDate(date) {
      const now = /* @__PURE__ */ new Date();
      return this.slots.filter((slot) => {
        const slotDate = new Date(slot.start);
        return slotDate.toLocaleDateString() === date.toLocaleDateString() && slotDate >= now;
      }).map((slot) => {
        let id = 0;
        for (const innerSlot of slot.slots) {
          if (!this.$props.componentsProps) {
            if (innerSlot.capacity > innerSlot.bookers) {
              id = innerSlot.id;
              break;
            }
          } else {
            if (innerSlot.id === this.$props.componentsProps.slot_id && innerSlot.capacity + 1 > innerSlot.bookers) {
              id = innerSlot.id;
              break;
            } else if (innerSlot.capacity > innerSlot.bookers) {
              id = innerSlot.id;
              break;
            }
          }
        }
        return {
          ...slot,
          id,
          displayTime: new Date(slot.start).toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" })
        };
      });
    },
    updateSelectedSlots: function(slot_id) {
      this.slotSelected = slot_id;
      if (this.$props.componentsProps) {
        this.$emit("valueUpdated", slot_id);
      }
    },
    disabledSlot: function(slot) {
      if (this.$props.componentsProps && slot.id === this.$props.componentsProps.slot_id) {
        return slot.totalBookers >= slot.totalCapacity + 1;
      }
      return slot.totalBookers >= slot.totalCapacity;
    }
  },
  computed: {
    visibleDates: function() {
      return this.availableDates.slice(this.currentStartIndex, this.currentStartIndex + 3).map((dateString) => new Date(dateString));
    },
    selectedSlotInfo: function() {
      let text = null;
      if (this.slotSelected) {
        const selectedSlot = this.slots.flatMap((group) => group.slots).find((slot) => slot.id === this.slotSelected);
        if (selectedSlot) {
          const start = new Date(selectedSlot.start);
          const end = new Date(selectedSlot.end);
          const interval = end - start;
          let minutes = Math.floor(interval / 1e3 / 60);
          if (minutes < 60) {
            minutes = minutes + " " + this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_MINUTES");
          } else {
            const hours = Math.floor(minutes / 60);
            if (hours > 1) {
              minutes = hours + " " + this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOURS");
            } else {
              minutes = hours + " " + this.translate("COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DURATION_HOUR");
            }
          }
          text = this.translate("COM_EMUNDUS_EVENT_SLOT_RECAP");
          text = text.replace(
            "{{date}}",
            start.toLocaleDateString("fr-FR", {
              weekday: "long",
              day: "numeric",
              month: "long"
            })
          );
          text = text.replace("{{time}}", start.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" }));
          text = text.replace("{{duration}}", minutes);
        }
      }
      return text;
    },
    displayedTimezone: function() {
      return this.currentTimezone.name.replace("_", " ") + " (UTC" + (this.currentTimezone.offset > 0 ? "+" : "") + this.currentTimezone.offset + ")";
    }
  },
  watch: {
    currentStartIndex(newIndex) {
      if (newIndex >= this.availableDates.length) {
        this.currentStartIndex = Math.max(0, this.availableDates.length - 3);
      } else if (newIndex < 0) {
        this.currentStartIndex = 0;
      }
    }
  }
};
const _hoisted_1 = { class: "tw-w-full tw-rounded-coordinator tw-border tw-border-neutral-300 tw-p-4 tw-flex tw-flex-col tw-gap-4 tw-items-center tw-relative" };
const _hoisted_2 = {
  key: 0,
  class: "tw-w-full"
};
const _hoisted_3 = { class: "tw-flex tw-items-center tw-gap-1 tw-mb-3" };
const _hoisted_4 = { class: "tw-text-base" };
const _hoisted_5 = { class: "tw-flex tw-items-start tw-gap-1 tw-w-full" };
const _hoisted_6 = ["disabled"];
const _hoisted_7 = {
  key: 0,
  class: "tw-flex tw-flex-row tw-items-stretch tw-gap-4 tw-flex-1 tw-w-auto tw-justify-center"
};
const _hoisted_8 = { class: "tw-text-lg tw-text-center" };
const _hoisted_9 = { class: "tw-text-sm tw-text-neutral-500 tw-text-center" };
const _hoisted_10 = { class: "tw-mt-4 tw-grid tw-grid-cols-2 tw-gap-2 tw-w-full" };
const _hoisted_11 = ["disabled", "onClick"];
const _hoisted_12 = ["disabled"];
const _hoisted_13 = { key: 1 };
const _hoisted_14 = {
  key: 2,
  class: "em-loader"
};
const _hoisted_15 = ["id", "name", "value"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Info = resolveComponent("Info");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    $options.visibleDates.length > 0 && $data.myBookings.length === 0 && !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        _cache[2] || (_cache[2] = createBaseVNode("span", { class: "material-symbols-outlined !tw-text-base" }, "language", -1)),
        createBaseVNode("span", _hoisted_4, toDisplayString($options.displayedTimezone), 1)
      ]),
      createBaseVNode("div", _hoisted_5, [
        createBaseVNode("button", {
          class: "tw-p-2 tw-border-0 tw-bg-transparent tw-rounded-coordinator hover:tw-bg-neutral-100",
          type: "button",
          disabled: $data.currentStartIndex === 0,
          style: normalizeStyle({
            cursor: $data.currentStartIndex === 0 ? "not-allowed" : "pointer",
            opacity: $data.currentStartIndex === 0 ? 0.2 : 1
          }),
          onClick: _cache[0] || (_cache[0] = (...args) => $options.previousDates && $options.previousDates(...args))
        }, _cache[3] || (_cache[3] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "chevron_left", -1)
        ]), 12, _hoisted_6),
        $data.slots ? (openBlock(), createElementBlock("div", _hoisted_7, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.visibleDates, (date, index) => {
            return openBlock(), createElementBlock("div", {
              key: index,
              style: {
                display: "flex",
                flexDirection: "column",
                alignItems: "center",
                gap: "8px",
                flexGrow: "1"
              }
            }, [
              createBaseVNode("p", _hoisted_8, toDisplayString($options.formatDay(date)), 1),
              createBaseVNode("p", _hoisted_9, toDisplayString($options.formatShortDate(date)), 1),
              createBaseVNode("div", _hoisted_10, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($options.getAvailableSlotsForDate(date), (slot) => {
                  return openBlock(), createElementBlock("button", {
                    type: "button",
                    class: normalizeClass(["tw-flex tw-items-center tw-justify-center tw-px-4 tw-w-full tw-py-2 tw-bg-neutral-300 tw-rounded-coordinator tw-border", {
                      "tw-border-profile-full tw-bg-profile-light": $data.slotSelected === slot.id,
                      "hover:tw-bg-neutral-400": $data.slotSelected !== slot.id,
                      "tw-opacity-50 tw-line-through tw-cursor-not-allowed": $options.disabledSlot(slot)
                    }]),
                    key: slot.id,
                    disabled: $options.disabledSlot(slot),
                    onClick: ($event) => $options.updateSelectedSlots(slot.id)
                  }, toDisplayString(slot.displayTime), 11, _hoisted_11);
                }), 128))
              ])
            ]);
          }), 128))
        ])) : createCommentVNode("", true),
        createBaseVNode("button", {
          class: "tw-p-2 tw-border-0 tw-bg-transparent tw-rounded-coordinator hover:tw-bg-neutral-100",
          type: "button",
          disabled: $data.currentStartIndex + 3 >= $data.availableDates.length,
          style: normalizeStyle({
            cursor: $data.currentStartIndex + 3 >= $data.availableDates.length ? "not-allowed" : "pointer",
            opacity: $data.currentStartIndex + 3 >= $data.availableDates.length ? 0.2 : 1
          }),
          onClick: _cache[1] || (_cache[1] = (...args) => $options.nextDates && $options.nextDates(...args))
        }, _cache[4] || (_cache[4] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "chevron_right", -1)
        ]), 12, _hoisted_12)
      ])
    ])) : $options.visibleDates.length === 0 && !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_13, [
      createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_EVENT_NO_SLOT_AVAILABLE")), 1)
    ])) : $data.loading ? (openBlock(), createElementBlock("div", _hoisted_14)) : createCommentVNode("", true),
    $data.slotSelected && this.slots.length > 0 ? (openBlock(), createBlock(_component_Info, {
      key: 3,
      class: "tw-w-full",
      text: $options.selectedSlotInfo
    }, null, 8, ["text"])) : createCommentVNode("", true),
    createBaseVNode("input", {
      type: "text",
      class: "hidden fabrikinput",
      id: $data.name,
      name: $data.name,
      value: $data.slotSelected
    }, null, 8, _hoisted_15)
  ]);
}
const EventBooking = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  EventBooking as default
};
