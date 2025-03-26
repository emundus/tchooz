import { e as eventsService } from "./events2.js";
import { _ as _export_sfc, M as Modal, r as resolveComponent, c as createElementBlock, o as openBlock, d as createBaseVNode, b as createCommentVNode, t as toDisplayString, F as Fragment, e as renderList, g as createVNode, f as withCtx, a as createBlock, n as normalizeClass, m as createTextVNode, u as useGlobalStore } from "./app_emundus.js";
import { I as Info } from "./Info.js";
const _sfc_main = {
  name: "ViewBooking",
  components: { Info, Modal },
  data: () => ({
    actualLanguage: "fr-FR",
    myBookings: [],
    cancelPopupOpenForBookingId: null,
    loading: false
  }),
  created() {
    this.loading = true;
    const globalStore = useGlobalStore();
    this.actualLanguage = globalStore.getCurrentLang;
    this.getApplicantBookings().then((bookings) => {
      this.myBookings = bookings.map((booking) => ({
        ...booking,
        booking_date: this.toFormattedDate(booking.start, booking.end)
      })).sort((a, b) => new Date(a.start) - new Date(b.start));
      this.loading = false;
    });
  },
  methods: {
    async getApplicantBookings() {
      return new Promise((resolve, reject) => {
        eventsService.getApplicantBookings().then((response) => {
          if (response.status) {
            resolve(response.data);
          } else {
            console.error("Error when trying to retrieve applicant bookings", response.error);
            reject([]);
          }
        });
      });
    },
    deleteBooking(booking_id) {
      eventsService.deleteBooking(booking_id).then((response) => {
        if (response.status === true) {
          Swal.fire({
            position: "center",
            icon: "success",
            title: Joomla.JText._("COM_EMUNDUS_EVENTS_RESERVATION_DELETED"),
            showConfirmButton: false,
            allowOutsideClick: false,
            reverseButtons: true,
            timer: 1500,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            }
          }).then(() => {
            this.myBookings = this.myBookings.filter((booking) => booking.id !== booking_id);
            this.$emit("close");
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: response.message
          });
        }
        this.changePopUpCancelState();
      });
    },
    canShowCancelButton(booking) {
      if (!booking.can_cancel) {
        return false;
      }
      const today = /* @__PURE__ */ new Date();
      const startDate = new Date(booking.start);
      if (booking.can_cancel_until_date) {
        const cancelUntilDate = new Date(booking.can_cancel_until_date);
        return today <= cancelUntilDate;
      } else if (booking.can_cancel_until_days !== null) {
        const cancelUntilCalculatedDate = /* @__PURE__ */ new Date();
        cancelUntilCalculatedDate.setDate(today.getDate() + booking.can_cancel_until_days);
        return cancelUntilCalculatedDate <= startDate;
      }
      return true;
    },
    changePopUpCancelState(booking_id) {
      this.cancelPopupOpenForBookingId = this.cancelPopupOpenForBookingId === booking_id ? null : booking_id;
    },
    toFormattedDate(startDate, endDate) {
      const start = new Date(startDate);
      const end = new Date(endDate);
      const dateOptions = {
        weekday: "long",
        day: "numeric",
        month: "long"
      };
      const timeOptions = {
        hour: "2-digit",
        minute: "2-digit"
      };
      const formattedDate = start.toLocaleDateString(this.actualLanguage, dateOptions);
      let formattedStartTime = start.toLocaleTimeString(this.actualLanguage, timeOptions);
      let formattedEndTime = end.toLocaleTimeString(this.actualLanguage, timeOptions);
      if (this.actualLanguage === "fr-FR") {
        formattedStartTime = formattedStartTime.replace(":", "h");
        formattedEndTime = formattedEndTime.replace(":", "h");
      } else if (this.actualLanguage === "en-GB") {
        formattedStartTime = start.toLocaleTimeString("en-EN", timeOptions);
        formattedEndTime = end.toLocaleTimeString("en-EN", timeOptions);
      }
      return `${formattedDate.charAt(0).toUpperCase() + formattedDate.slice(1)} (${formattedStartTime} - ${formattedEndTime})`;
    },
    applicantTextBeforeCancel(booking) {
      const formatDate = (date) => {
        const options = {
          weekday: "long",
          day: "numeric",
          month: "long",
          year: "numeric"
        };
        if (this.actualLanguage === "fr-FR") {
          return date.toLocaleDateString("fr-FR", options);
        } else if (this.actualLanguage === "en-GB") {
          return date.toLocaleDateString("en-GB", options);
        }
        return date.toLocaleDateString(options);
      };
      let text = "";
      if (booking.can_book_until_days !== null) {
        const currentDate = /* @__PURE__ */ new Date();
        const futureDate = new Date(currentDate);
        futureDate.setDate(currentDate.getDate() + booking.can_book_until_days);
        text = this.translate("COM_EMUNDUS_EVENT_CANT_BOOK_UNTIL_DATE");
        text = text.replace("{{date}}", formatDate(futureDate));
      }
      if (booking.can_book_until_date !== null) {
        const today = /* @__PURE__ */ new Date();
        today.setHours(0, 0, 0, 0);
        const canBookUntilDate = new Date(booking.can_book_until_date);
        canBookUntilDate.setDate(canBookUntilDate.getDate() + 1);
        if (canBookUntilDate < today) {
          return this.translate("COM_EMUNDUS_EVENT_CANT_BOOK_NOW");
        }
        text = this.translate("COM_EMUNDUS_EVENT_CANT_BOOK_FROM_DATE");
        text = text.replace("{{date}}", formatDate(canBookUntilDate));
      }
      return text;
    }
  }
};
const _hoisted_1 = { class: "tw-mb-8 tw-mt-4" };
const _hoisted_2 = { key: 0 };
const _hoisted_3 = { key: 0 };
const _hoisted_4 = { class: "tw-mb-4 tw-mt-8 tw-text-center" };
const _hoisted_5 = { class: "tw-mb-5 tw-flex tw-flex-col tw-text-center" };
const _hoisted_6 = { class: "tw-mb-1 tw-font-bold tw-leading-6" };
const _hoisted_7 = { class: "tw-mb-1 tw-font-bold tw-leading-6" };
const _hoisted_8 = { class: "tw-mb-8 tw-mt-5 tw-flex tw-justify-between" };
const _hoisted_9 = ["onClick"];
const _hoisted_10 = ["onClick"];
const _hoisted_11 = { class: "tw-flex-1" };
const _hoisted_12 = { class: "tw-text-green-700" };
const _hoisted_13 = { class: "tw-font-bold" };
const _hoisted_14 = { class: "tw-ml-12 tw-flex-1 tw-text-left" };
const _hoisted_15 = { class: "tw-text-base tw-text-neutral-600" };
const _hoisted_16 = { key: 0 };
const _hoisted_17 = { key: 0 };
const _hoisted_18 = ["href"];
const _hoisted_19 = { class: "tw-underline" };
const _hoisted_20 = { class: "tw-flex tw-flex-1 tw-justify-end tw-gap-2" };
const _hoisted_21 = ["onClick"];
const _hoisted_22 = { key: 1 };
const _hoisted_23 = { class: "tw-text-center tw-text-neutral-500" };
const _hoisted_24 = {
  key: 2,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Info = resolveComponent("Info");
  const _component_modal = resolveComponent("modal");
  return openBlock(), createElementBlock("div", null, [
    createBaseVNode("h1", _hoisted_1, toDisplayString(_ctx.translate("COM_EMUNDUS_EVENTS_MY_RESERVATIONS")), 1),
    _ctx.myBookings.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_2, [
      (openBlock(true), createElementBlock(Fragment, null, renderList(_ctx.myBookings, (booking) => {
        return openBlock(), createElementBlock("div", {
          key: booking.id,
          class: "tw-mb-4 tw-mr-36 tw-flex tw-items-center tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-sm"
        }, [
          _ctx.cancelPopupOpenForBookingId === booking.id ? (openBlock(), createElementBlock("div", _hoisted_3, [
            createVNode(_component_modal, {
              name: "add-location-modal",
              class: normalizeClass("placement-center tw-rounded tw-px-6 tw-shadow-modal"),
              transition: "nice-modal-fade",
              width: "600px",
              delay: 100,
              adaptive: true,
              clickToClose: false
            }, {
              default: withCtx(() => [
                createBaseVNode("h1", _hoisted_4, toDisplayString(_ctx.translate("COM_EMUNDUS_EVENTS_CANCEL_RESERVATION")), 1),
                createBaseVNode("div", _hoisted_5, [
                  createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_EVENTS_ARE_YOU_SURE_CANCEL_RESERVATION")), 1),
                  createBaseVNode("p", _hoisted_6, toDisplayString(booking.event_name), 1),
                  createBaseVNode("p", _hoisted_7, toDisplayString(booking.booking_date), 1)
                ]),
                $options.applicantTextBeforeCancel(booking) ? (openBlock(), createBlock(_component_Info, {
                  key: 0,
                  text: $options.applicantTextBeforeCancel(booking),
                  class: "tw-mt-4 tw-w-full tw-text-left",
                  icon: "warning",
                  "bg-color": "tw-bg-orange-100",
                  "icon-type": "material-icons",
                  "icon-color": "tw-text-orange-600"
                }, null, 8, ["text"])) : createCommentVNode("", true),
                createBaseVNode("div", _hoisted_8, [
                  createBaseVNode("button", {
                    class: "tw-btn-primary",
                    onClick: ($event) => $options.changePopUpCancelState(booking.id)
                  }, toDisplayString(_ctx.translate("BACK")), 9, _hoisted_9),
                  createBaseVNode("button", {
                    class: "tw-btn-secondary",
                    onClick: ($event) => $options.deleteBooking(booking.id)
                  }, toDisplayString(_ctx.translate("COM_EMUNDUS_EVENTS_CANCEL_RESERVATION")), 9, _hoisted_10)
                ])
              ]),
              _: 2
            }, 1024)
          ])) : createCommentVNode("", true),
          createBaseVNode("div", _hoisted_11, [
            createBaseVNode("p", _hoisted_12, toDisplayString(booking.event_name), 1),
            createBaseVNode("p", _hoisted_13, toDisplayString(booking.booking_date), 1)
          ]),
          createBaseVNode("div", _hoisted_14, [
            createBaseVNode("p", _hoisted_15, [
              createTextVNode(toDisplayString(booking.name_location) + " ", 1),
              booking.room_name ? (openBlock(), createElementBlock("span", _hoisted_16, "- " + toDisplayString(booking.room_name), 1)) : createCommentVNode("", true)
            ]),
            booking.link_registrant || booking.link_event ? (openBlock(), createElementBlock("div", _hoisted_17, [
              createBaseVNode("a", {
                href: booking.link_registrant ? booking.link_registrant : booking.link_event,
                target: "_blank",
                class: "tw-text-green-700"
              }, [
                createBaseVNode("span", _hoisted_19, toDisplayString(_ctx.translate("COM_EMUNDUS_EVENTS_JOIN_VIDEOCONFERENCE")), 1)
              ], 8, _hoisted_18)
            ])) : createCommentVNode("", true)
          ]),
          createBaseVNode("div", _hoisted_20, [
            $options.canShowCancelButton(booking) ? (openBlock(), createElementBlock("button", {
              key: 0,
              class: "tw-btn-secondary",
              onClick: ($event) => $options.changePopUpCancelState(booking.id)
            }, _cache[0] || (_cache[0] = [
              createBaseVNode("span", { class: "material-symbols-outlined" }, "delete", -1)
            ]), 8, _hoisted_21)) : createCommentVNode("", true)
          ])
        ]);
      }), 128))
    ])) : (openBlock(), createElementBlock("div", _hoisted_22, [
      createBaseVNode("p", _hoisted_23, toDisplayString(_ctx.translate("COM_EMUNDUS_EVENTS_NO_RESERVATION_FOUND")), 1)
    ])),
    _ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_24)) : createCommentVNode("", true)
  ]);
}
const ViewBooking = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-eebb88e8"]]);
export {
  ViewBooking as default
};
