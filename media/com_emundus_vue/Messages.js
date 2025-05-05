import { P as Parameter } from "./Parameter.js";
import { N as FetchClient, _ as _export_sfc, y as script, M as Modal, r as resolveComponent, o as openBlock, c as createElementBlock, a as createBlock, f as withCtx, d as createBaseVNode, t as toDisplayString, g as withModifiers, b as createCommentVNode, h as createVNode, m as createTextVNode, n as normalizeClass, j as normalizeStyle, w as withDirectives, C as vModelText, F as Fragment, e as renderList, v as vShow, D as withKeys } from "./app_emundus.js";
import { A as AttachDocument } from "./AttachDocument.js";
import { S as Skeleton } from "./Skeleton.js";
const client = new FetchClient("messenger");
const messengerServices = {
  async getFilesByUser() {
    try {
      return await client.get("getfilesbyuser");
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getChatroomsByUser() {
    try {
      return await client.get("getchatroomsbyuser");
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getChatroomsByFnum(fnum) {
    try {
      return await client.get("getchatroomsbyfnum", { fnum });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getMessagesByFnum(fnum) {
    try {
      return await client.get("getmessagesbyfnum", { fnum });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async createChatroom(fnum) {
    try {
      return await client.post("createchatroom", { fnum });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async closeChatroom(fnum) {
    try {
      return await client.post("closechatroom", { fnum });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async openChatroom(fnum) {
    try {
      return await client.post("openchatroom", { fnum });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async sendMessage(message, fnum) {
    try {
      return await client.post("sendmessage", { message, fnum });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async markAsRead(chatroom_id) {
    try {
      return await client.post("markasread", { chatroom_id });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async goToFile(fnum) {
    try {
      return await client.post("gotofile", { fnum });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
};
const _imports_0 = "data:image/svg+xml,%3csvg%20xmlns='http://www.w3.org/2000/svg'%20xmlns:xlink='http://www.w3.org/1999/xlink'%20id='Calque_1'%20data-name='Calque%201'%20viewBox='0%200%20841.89%20595.28'%3e%3cdefs%3e%3clinearGradient%20id='Dégradé_sans_nom_12'%20x1='321.27'%20x2='321.27'%20y1='284.49'%20y2='366.81'%20data-name='Dégradé%20sans%20nom%2012'%20gradientUnits='userSpaceOnUse'%3e%3cstop%20offset='0'%20stop-color='%23b0b0bf'/%3e%3cstop%20offset='1'%20stop-color='%235b5a72'/%3e%3c/linearGradient%3e%3clinearGradient%20id='Dégradé_sans_nom_14'%20x1='548.28'%20x2='548.28'%20y1='314.42'%20y2='368.65'%20data-name='Dégradé%20sans%20nom%2014'%20gradientUnits='userSpaceOnUse'%3e%3cstop%20offset='0'%20stop-color='%23b0b0bf'/%3e%3cstop%20offset='1'%20stop-color='%23f4f4f6'/%3e%3c/linearGradient%3e%3clinearGradient%20id='Dégradé_sans_nom_11'%20x1='330.01'%20x2='528.74'%20y1='273.49'%20y2='472.22'%20data-name='Dégradé%20sans%20nom%2011'%20gradientUnits='userSpaceOnUse'%3e%3cstop%20offset='.1'%20stop-color='%235b5a72'/%3e%3cstop%20offset='.5'%20stop-color='%23b0b0bf'/%3e%3cstop%20offset='.96'%20stop-color='%23f4f4f6'/%3e%3c/linearGradient%3e%3clinearGradient%20xlink:href='%23Dégradé_sans_nom_11'%20id='Dégradé_sans_nom_11-2'%20x1='422.87'%20x2='619.98'%20y1='157.05'%20y2='354.16'%20data-name='Dégradé%20sans%20nom%2011'/%3e%3clinearGradient%20xlink:href='%23Dégradé_sans_nom_11'%20id='Dégradé_sans_nom_11-3'%20x1='360.33'%20x2='503.57'%20y1='264.6'%20y2='407.85'%20data-name='Dégradé%20sans%20nom%2011'/%3e%3cstyle%3e.cls-1{fill:%23353544}.cls-7{fill:%23fff;opacity:.5}%3c/style%3e%3c/defs%3e%3cpath%20d='M321.27%20383.57h-13.79c-7.07%200-11.54-7.72-7.9-13.78%203.69-6.15%2010.22-12.03%2021.69-12.03s18%205.88%2021.69%2012.03c3.64%206.07-.83%2013.78-7.9%2013.78h-13.79Z'%20style='fill:url(%23Dégradé_sans_nom_12)'/%3e%3cpath%20d='M548.28%20383.57h-13.79c-7.07%200-11.54-7.72-7.9-13.78%203.69-6.15%2010.22-12.03%2021.69-12.03s18%205.88%2021.69%2012.03c3.64%206.07-.83%2013.78-7.9%2013.78h-13.79Z'%20style='fill:url(%23Dégradé_sans_nom_14)'/%3e%3cpath%20d='M337.89%20248.34v58.71c-7.3%2014.53-11.41%2030.95-11.41%2048.29V248.26c-5.32-4.72-9.05-15.82-9.05-28.82%200-17.26%206.62-23.65%2014.83-23.65s14.83%206.39%2014.83%2023.65c0%2013-3.8%2024.18-9.2%2028.9Z'%20style='fill:url(%23Dégradé_sans_nom_11)'/%3e%3cpath%20d='M541.56%20248.26v107.08c0-17.34-4.11-33.77-11.41-48.29v-58.71c-5.4-4.72-9.2-15.89-9.2-28.9%200-17.26%206.69-23.65%2014.83-23.65s14.83%206.39%2014.83%2023.65c0%2013-3.73%2024.11-9.05%2028.82Z'%20style='fill:url(%23Dégradé_sans_nom_11-2)'/%3e%3cpath%20d='M346.22%20367.83c2.41%204.01%202.46%208.85.15%2012.93a12.82%2012.82%200%200%201-2.12%202.8h48.31a49.837%2049.837%200%200%201-8.73-28.23c0-27.76%2022.51-50.19%2050.19-50.19s50.19%2022.44%2050.19%2050.19c0%2010.47-3.21%2020.19-8.71%2028.23h49.8a13.75%2013.75%200%200%201-2.12-2.8c-2.32-4.09-2.26-8.92.15-12.94%204.27-7.11%2010.49-11.58%2018.21-13.19-.11-17.08-4.2-33.26-11.4-47.59-17.72-35.14-54.07-59.24-96.13-59.24s-78.41%2024.11-96.13%2059.24c-7.16%2014.25-11.24%2030.33-11.39%2047.31%208.42%201.32%2015.18%205.9%2019.72%2013.47Z'%20style='fill:url(%23Dégradé_sans_nom_11-3)'/%3e%3cpath%20d='M494.72%20383.57c5.11-5.3%208.46-14.8%208.46-28.26%200-21.79-8.77-33.22-19.59-33.22S464%20333.52%20464%20355.31c0%2013.46%203.35%2022.96%208.46%2028.26h22.27Zm-99.12%200c5.11-5.3%208.46-14.8%208.46-28.26%200-21.79-8.77-33.22-19.59-33.22s-19.59%2011.43-19.59%2033.22c0%2013.46%203.35%2022.96%208.46%2028.26h22.27Z'%20class='cls-1'/%3e%3cellipse%20cx='478.9'%20cy='346.53'%20class='cls-7'%20rx='4.55'%20ry='3.13'/%3e%3cellipse%20cx='485.86'%20cy='336.92'%20class='cls-7'%20rx='6.96'%20ry='6.11'/%3e%3cellipse%20cx='379.77'%20cy='346.53'%20class='cls-7'%20rx='4.55'%20ry='3.13'/%3e%3cellipse%20cx='386.73'%20cy='336.92'%20class='cls-7'%20rx='6.96'%20ry='6.11'/%3e%3cpath%20d='M463.99%20287.25s18.03-7.62%2039.19%2017.89c0%200-13.54-25.18-24.28-26.84-10.02-1.55-14.91%208.95-14.91%208.95Zm-59.93%200s-18.03-7.62-39.19%2017.89c0%200%2013.54-25.18%2024.28-26.84%2010.02-1.55%2014.91%208.95%2014.91%208.95Z'%20class='cls-1'/%3e%3c/svg%3e";
const _sfc_main = {
  name: "Messages",
  components: { Skeleton, AttachDocument, Multiselect: script, Modal, Parameter },
  emits: ["close", "open"],
  props: {
    isModal: {
      type: Boolean,
      default: false
    },
    fnum: {
      type: String,
      required: true
    },
    fullname: {
      type: String,
      required: true
    },
    applicant: {
      type: Boolean,
      default: true
    },
    unread_messages: {
      type: Array
    }
  },
  data() {
    return {
      loading: true,
      messages_loading: false,
      send_progress: false,
      createNewChatroom: false,
      showClosedChatroom: false,
      files: [],
      fileSelected: null,
      chatrooms: [],
      currentChatroom: null,
      dates: [],
      messages: [],
      currentMessage: "",
      search: ""
    };
  },
  created() {
    if (this.applicant) {
      messengerServices.getFilesByUser().then((response) => {
        this.files = response.data;
      });
      messengerServices.getChatroomsByUser().then((response) => {
        this.chatrooms = response.data;
        if (this.unread_messages && this.unread_messages.length > 0) {
          this.unread_messages.forEach((unread_message) => {
            this.chatrooms.find((chatroom) => chatroom.fnum === unread_message.fnum).unread = unread_message.notifications;
          });
        }
        this.loading = false;
      });
    } else {
      messengerServices.getChatroomsByFnum(this.fnum).then((response) => {
        this.chatrooms = response.data;
        if (this.chatrooms.length > 0) {
          this.currentChatroom = this.chatrooms[0];
        }
        this.loading = false;
      });
    }
  },
  methods: {
    nameWithYear({ label, program, year }) {
      return `${label} (${program} - ${year})`;
    },
    async getMessagesByFnum(loader = true, scroll = true) {
      this.messages_loading = loader;
      messengerServices.getMessagesByFnum(this.currentChatroom.fnum).then((response) => {
        this.messages = response.data.messages;
        this.dates = response.data.dates;
        this.anonymous = parseInt(response.data.anonymous);
        if (scroll) {
          this.messages_loading = false;
        }
      });
    },
    createChatroom() {
      if (this.fileSelected === null && this.fnum === null) {
        return;
      } else if (this.fileSelected === null && this.fnum !== null) {
        this.fileSelected = { fnum: this.fnum };
      }
      let chatroomExists = this.chatrooms.find((chatroom) => chatroom.fnum === this.fileSelected.fnum);
      if (chatroomExists) {
        this.currentChatroom = chatroomExists;
        this.createNewChatroom = false;
        return;
      }
      messengerServices.createChatroom(this.fileSelected.fnum).then((response) => {
        this.chatrooms.push(response.data);
        this.currentChatroom = response.data;
        this.createNewChatroom = false;
      });
    },
    closeChatroom() {
      messengerServices.closeChatroom(this.currentChatroom.fnum).then((response) => {
        if (response.status) {
          this.currentChatroom.status = 0;
          let notifications_counter = document.querySelector('a[href*="messenger"] .notifications-counter');
          if (notifications_counter) {
            notifications_counter.remove();
          }
          let notifications_column = document.querySelector(
            'a[id="' + this.currentChatroom.fnum + '"] .messenger__notifications_counter'
          );
          if (notifications_column) {
            notifications_column.remove();
          }
          this.$emit("closedChatroom", this.currentChatroom.fnum);
        } else {
          Swal.fire({
            title: Joomla.Text._("COM_EMUNDUS_ONBOARD_ERROR"),
            text: response.msg,
            type: "error",
            showCancelButton: false,
            showConfirmButton: false,
            timer: 3e3
          });
        }
      });
    },
    openChatroom() {
      messengerServices.openChatroom(this.currentChatroom.fnum).then((response) => {
        if (response.status) {
          this.currentChatroom.status = 1;
        } else {
          Swal.fire({
            title: Joomla.Text._("COM_EMUNDUS_ONBOARD_ERROR"),
            text: response.msg,
            type: "error",
            showCancelButton: false,
            showConfirmButton: false,
            timer: 3e3
          });
        }
      });
    },
    async sendMessage(e) {
      if (this.currentMessage === "") {
        this.currentMessage = document.getElementById("messenger_message").value;
      }
      if (this.currentMessage.trim() !== "" && !this.send_progress) {
        this.send_progress = true;
        let message_id = Math.floor(Math.random() * 1e3) + 9999;
        this.pushToDatesArray({
          message_id,
          progress: true,
          user_id_from: 0,
          me: true,
          user_id_to: null,
          folder_id: 2,
          date_time: this.formatedTimestamp(),
          date_hour: (/* @__PURE__ */ new Date()).toLocaleTimeString([], {
            hour: "2-digit",
            minute: "2-digit"
          }),
          state: 0,
          priority: 0,
          subject: 0,
          message: this.currentMessage,
          email_from: null,
          email_cc: null,
          email_to: null,
          name: this.fullname
        });
        messengerServices.sendMessage(this.currentMessage, this.currentChatroom.fnum).then((response) => {
          if (response.status) {
            this.messages.forEach((message) => {
              if (message.message_id === message_id) {
                message.progress = false;
                this.chatrooms.find((chatroom) => chatroom.ccid === this.currentChatroom.ccid).messages.push({
                  message: message.message
                });
                this.chatrooms.forEach((chatroom) => {
                  if (chatroom.ccid === this.currentChatroom.ccid) {
                    chatroom.unread = 0;
                  }
                });
                let notifications_counter = document.querySelector('a[href*="messenger"] .notifications-counter');
                if (notifications_counter) {
                  notifications_counter.remove();
                }
                let notifications_column = document.querySelector(
                  'a[id="' + this.currentChatroom.fnum + '"] .messenger__notifications_counter'
                );
                if (notifications_column) {
                  notifications_column.remove();
                }
                let event = new CustomEvent("removeMessengerNotifications", {
                  detail: { fnum: this.currentChatroom.fnum }
                });
                document.dispatchEvent(event);
              }
            });
          } else {
            Swal.fire({
              title: Joomla.Text._("COM_EMUNDUS_ONBOARD_ERROR"),
              text: response.msg,
              type: "error",
              showCancelButton: false,
              showConfirmButton: false,
              timer: 3e3
            });
          }
        });
        this.currentMessage = "";
        document.getElementById("messenger_message").value = "";
        this.send_progress = false;
        this.scrollToBottom();
      }
    },
    pushToDatesArray(message) {
      let pushToDate = false;
      let message_date = (/* @__PURE__ */ new Date()).toISOString().slice(0, 10);
      if (message.date_time) {
        message_date = message.date_time.split(" ")[0];
      }
      this.dates.forEach((elt, index) => {
        if (elt.dates == message_date) {
          this.dates[index].messages.push(message.message_id);
          pushToDate = true;
        }
      });
      if (!pushToDate) {
        var new_date = {
          dates: (/* @__PURE__ */ new Date()).toISOString().slice(0, 10),
          messages: []
        };
        new_date.messages.push(message.message_id);
        this.dates.push(new_date);
      }
      this.messages.push(message);
    },
    scrollToBottom() {
      setTimeout(() => {
        const container = document.getElementById("messages__list");
        if (container) {
          container.scrollTop = container.scrollHeight;
        }
      }, 100);
    },
    formatedTimestamp() {
      const d = /* @__PURE__ */ new Date();
      const date = d.toISOString().split("T")[0];
      const time = d.toTimeString().split(" ")[0];
      return `${date} ${time}`;
    }
  },
  computed: {
    messageByDates() {
      let messages = [];
      this.dates.forEach((elt) => {
        let date = elt.dates;
        let messages_array = [];
        elt.messages.forEach((message_id) => {
          this.messages.forEach((message) => {
            if (message.message_id == message_id) {
              messages_array.push(message);
            }
          });
        });
        messages.push({ date, messages: messages_array });
      });
      return messages;
    },
    openedChatrooms() {
      if (this.search === "") {
        return this.chatrooms.filter((chatroom) => chatroom.status === 1);
      }
      let chatroomsByMessage = this.chatrooms.filter(
        (chatroom) => chatroom.messages.some((message) => message.message.toLowerCase().includes(this.search.toLowerCase()))
      );
      return chatroomsByMessage.filter((chatroom) => chatroom.status === 1);
    },
    closedChatrooms() {
      if (this.search === "") {
        return this.chatrooms.filter((chatroom) => chatroom.status === 0);
      }
      let chatroomsByMessage = this.chatrooms.filter(
        (chatroom) => chatroom.messages.some((message) => message.message.toLowerCase().includes(this.search.toLowerCase()))
      );
      return chatroomsByMessage.filter((chatroom) => chatroom.status === 0);
    },
    showCloseChatroomButton() {
      if (!this.applicant) {
        return true;
      }
      if (this.currentChatroom.status === 1 && this.messages.length > 0) {
        return this.messages[this.messages.length - 1].me === false;
      }
    }
  },
  watch: {
    currentChatroom: {
      handler: function(val, oldVal) {
        if (!oldVal && val || oldVal && val && val.id !== oldVal.id) {
          this.getMessagesByFnum();
        }
      },
      deep: true
    },
    messages: {
      handler: function(val, oldVal) {
        this.$nextTick(() => {
          this.scrollToBottom();
        });
      },
      deep: true
    }
  }
};
const _hoisted_1 = { class: "tw-h-full tw-overflow-hidden" };
const _hoisted_2 = {
  key: 0,
  class: "tw-flex tw-h-full tw-flex-col"
};
const _hoisted_3 = {
  key: 0,
  class: "tw-sticky tw-top-0 tw-z-10 tw-border-b tw-border-neutral-300 tw-bg-white tw-px-4 tw-pt-4"
};
const _hoisted_4 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_5 = { class: "tw-p-4" };
const _hoisted_6 = {
  key: 1,
  class: "tw-z-10 tw-border-b tw-border-neutral-300 tw-bg-white tw-px-4 tw-pt-4"
};
const _hoisted_7 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_8 = {
  key: 0,
  class: "tw-flex tw-h-[95%] tw-flex-col tw-items-center tw-justify-between"
};
const _hoisted_9 = { class: "tw-w-full" };
const _hoisted_10 = { class: "tw-mb-4 tw-px-4" };
const _hoisted_11 = ["placeholder"];
const _hoisted_12 = { class: "tw-w-full" };
const _hoisted_13 = ["onClick"];
const _hoisted_14 = { class: "tw-w-full" };
const _hoisted_15 = { class: "tw-flex tw-items-start tw-gap-2" };
const _hoisted_16 = { class: "!tw-mb-0 tw-line-clamp-2 tw-font-semibold" };
const _hoisted_17 = {
  key: 0,
  class: "tw-flex tw-items-center tw-justify-center tw-rounded-full tw-bg-red-500 tw-text-sm tw-text-white",
  style: { "min-width": "16px", "width": "16px", "height": "16px" }
};
const _hoisted_18 = { class: "tw-text-sm tw-italic" };
const _hoisted_19 = {
  key: 0,
  class: "tw-w-full"
};
const _hoisted_20 = { class: "!tw-mb-0 tw-cursor-pointer tw-font-semibold" };
const _hoisted_21 = ["onClick"];
const _hoisted_22 = { class: "tw-w-full" };
const _hoisted_23 = { class: "tw-font-semibold" };
const _hoisted_24 = { class: "tw-text-sm tw-italic" };
const _hoisted_25 = {
  key: 0,
  class: "tw-w-full tw-px-4"
};
const _hoisted_26 = {
  key: 1,
  class: "tw-flex tw-h-[95%] tw-flex-col"
};
const _hoisted_27 = { class: "tw-px-2" };
const _hoisted_28 = { class: "tw-font-semibold" };
const _hoisted_29 = { class: "tw-text-sm tw-italic" };
const _hoisted_30 = { class: "tw-text-sm tw-italic" };
const _hoisted_31 = {
  key: 0,
  class: "tw-mx-3 tw-mt-2"
};
const _hoisted_32 = { class: "tw-flex tw-justify-end" };
const _hoisted_33 = { class: "tw-flex tw-justify-start" };
const _hoisted_34 = { class: "tw-ml-4 tw-flex tw-items-center" };
const _hoisted_35 = { class: "tw-px-5" };
const _hoisted_36 = { class: "tw-text-sm tw-font-bold" };
const _hoisted_37 = { key: 0 };
const _hoisted_38 = { key: 1 };
const _hoisted_39 = ["innerHTML"];
const _hoisted_40 = {
  key: 0,
  class: "tw-text-italic tw-text-sm"
};
const _hoisted_41 = {
  key: 0,
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_42 = { class: "tw-w-full" };
const _hoisted_43 = ["disabled", "placeholder"];
const _hoisted_44 = { key: 2 };
const _hoisted_45 = { class: "tw-mt-6 tw-flex tw-h-full tw-flex-col tw-items-center tw-justify-center tw-gap-2" };
const _hoisted_46 = { class: "tw-text-neutral-500" };
const _hoisted_47 = { key: 3 };
const _hoisted_48 = { class: "tw-mt-6 tw-flex tw-h-full tw-flex-col tw-items-center tw-justify-center tw-gap-2" };
const _hoisted_49 = {
  key: 0,
  class: "tw-text-neutral-500"
};
const _hoisted_50 = {
  key: 1,
  class: "tw-text-neutral-500"
};
const _hoisted_51 = {
  key: 1,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Multiselect = resolveComponent("Multiselect");
  const _component_modal = resolveComponent("modal");
  const _component_skeleton = resolveComponent("skeleton");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_2, [
      $data.createNewChatroom ? (openBlock(), createBlock(_component_modal, {
        key: 0,
        name: "messenger-files-modal",
        class: normalizeClass("placement-center tw-max-h-[80vh] tw-min-w-[400px] tw-overflow-y-auto tw-rounded tw-bg-white tw-shadow-modal"),
        transition: "nice-modal-fade",
        width: "40%",
        height: "30%",
        delay: 100,
        adaptive: true,
        clickToClose: false
      }, {
        default: withCtx(() => [
          $props.isModal ? (openBlock(), createElementBlock("div", _hoisted_3, [
            createBaseVNode("div", _hoisted_4, [
              createBaseVNode("h2", null, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_SELECT_FILE")), 1),
              createBaseVNode("button", {
                class: "tw-cursor-pointer tw-bg-transparent",
                onClick: _cache[0] || (_cache[0] = withModifiers(($event) => $data.createNewChatroom = false, ["prevent"]))
              }, _cache[15] || (_cache[15] = [
                createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
              ]))
            ])
          ])) : createCommentVNode("", true),
          createBaseVNode("div", _hoisted_5, [
            createVNode(_component_Multiselect, {
              options: $data.files,
              modelValue: $data.fileSelected,
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.fileSelected = $event),
              label: "label",
              "custom-label": $options.nameWithYear,
              "track-by": "id",
              placeholder: _ctx.translate("COM_EMUNDUS_MESSENGER_SELECT_FILE"),
              selectLabel: "",
              multiple: false
            }, {
              noOptions: withCtx(() => [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_MULTISELECT_NORESULTS")), 1)
              ]),
              noResult: withCtx(() => [
                createTextVNode(toDisplayString(_ctx.translate("COM_EMUNDUS_MULTISELECT_NORESULTS")), 1)
              ]),
              _: 1
            }, 8, ["options", "modelValue", "custom-label", "placeholder"]),
            createBaseVNode("button", {
              type: "button",
              class: "tw-btn-primary tw-float-right tw-mt-3 !tw-w-auto",
              onClick: _cache[2] || (_cache[2] = (...args) => $options.createChatroom && $options.createChatroom(...args))
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM")), 1)
          ])
        ]),
        _: 1
      })) : createCommentVNode("", true),
      $props.isModal ? (openBlock(), createElementBlock("div", _hoisted_6, [
        createBaseVNode("div", _hoisted_7, [
          createBaseVNode("h2", null, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_TITLE")), 1),
          createBaseVNode("button", {
            class: "tw-cursor-pointer tw-bg-transparent",
            onClick: _cache[3] || (_cache[3] = withModifiers(($event) => _ctx.$emit("close"), ["prevent"]))
          }, _cache[16] || (_cache[16] = [
            createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
          ]))
        ])
      ])) : createCommentVNode("", true),
      $data.chatrooms.length > 0 ? (openBlock(), createElementBlock("div", {
        key: 2,
        class: normalizeClass(["tw-mt-6 tw-h-full", { "tw-grid": $props.applicant == true }]),
        style: normalizeStyle($props.applicant == true ? "grid-template-columns: 33% 66%;" : "")
      }, [
        $props.applicant ? (openBlock(), createElementBlock("div", _hoisted_8, [
          createBaseVNode("div", _hoisted_9, [
            createBaseVNode("div", _hoisted_10, [
              withDirectives(createBaseVNode("input", {
                name: "search",
                "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.search = $event),
                class: "!tw-rounded-xl",
                placeholder: _ctx.translate("COM_EMUNDUS_MESSENGER_SEARCH_IN_MESSAGES")
              }, null, 8, _hoisted_11), [
                [vModelText, $data.search]
              ])
            ]),
            createBaseVNode("div", _hoisted_12, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.openedChatrooms, (chatroom) => {
                return openBlock(), createElementBlock("div", {
                  key: chatroom.ccid,
                  class: normalizeClass([$data.currentChatroom && chatroom.ccid === $data.currentChatroom.ccid ? "tw-bg-neutral-300" : "", "tw-mt-3"])
                }, [
                  createBaseVNode("div", {
                    class: "tw-w-full tw-cursor-pointer tw-px-4 tw-py-3 hover:tw-bg-neutral-200",
                    onClick: ($event) => $data.currentChatroom = chatroom
                  }, [
                    createBaseVNode("div", _hoisted_14, [
                      createBaseVNode("div", _hoisted_15, [
                        createBaseVNode("label", _hoisted_16, toDisplayString(chatroom.campaign), 1),
                        chatroom.unread && chatroom.unread > 0 ? (openBlock(), createElementBlock("div", _hoisted_17, toDisplayString(chatroom.unread), 1)) : createCommentVNode("", true)
                      ]),
                      createBaseVNode("p", _hoisted_18, toDisplayString(chatroom.program) + " - " + toDisplayString(chatroom.year), 1)
                    ])
                  ], 8, _hoisted_13)
                ], 2);
              }), 128))
            ]),
            $options.closedChatrooms.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_19, [
              _cache[17] || (_cache[17] = createBaseVNode("hr", null, null, -1)),
              createBaseVNode("div", {
                onClick: _cache[5] || (_cache[5] = ($event) => $data.showClosedChatroom = !$data.showClosedChatroom),
                class: "tw-flex tw-cursor-pointer tw-items-center tw-justify-between tw-gap-2 tw-px-4"
              }, [
                createBaseVNode("label", _hoisted_20, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CLOSED_CHATROOMS")), 1),
                createBaseVNode("span", {
                  class: normalizeClass(["material-symbols-outlined tw-transition-transform", { "tw-rotate-90": $data.showClosedChatroom }])
                }, "chevron_right", 2)
              ]),
              withDirectives(createBaseVNode("div", null, [
                (openBlock(true), createElementBlock(Fragment, null, renderList($options.closedChatrooms, (chatroom) => {
                  return openBlock(), createElementBlock("div", {
                    key: chatroom.ccid,
                    class: normalizeClass([$data.currentChatroom && chatroom.ccid === $data.currentChatroom.ccid ? "tw-bg-neutral-300" : "", "tw-mt-3"])
                  }, [
                    createBaseVNode("div", {
                      class: "tw-w-full tw-cursor-pointer tw-px-4 tw-py-3 hover:tw-bg-neutral-200",
                      onClick: ($event) => $data.currentChatroom = chatroom
                    }, [
                      createBaseVNode("div", _hoisted_22, [
                        createBaseVNode("label", _hoisted_23, toDisplayString(chatroom.campaign), 1),
                        createBaseVNode("p", _hoisted_24, toDisplayString(chatroom.program) + " - " + toDisplayString(chatroom.year), 1)
                      ])
                    ], 8, _hoisted_21)
                  ], 2);
                }), 128))
              ], 512), [
                [vShow, $data.showClosedChatroom]
              ])
            ])) : createCommentVNode("", true)
          ]),
          $data.currentChatroom ? (openBlock(), createElementBlock("div", _hoisted_25, [
            createBaseVNode("button", {
              type: "button",
              class: "tw-btn-primary tw-w-full",
              onClick: _cache[6] || (_cache[6] = ($event) => $data.createNewChatroom = true)
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM")), 1)
          ])) : createCommentVNode("", true)
        ])) : createCommentVNode("", true),
        $data.currentChatroom ? (openBlock(), createElementBlock("div", _hoisted_26, [
          createBaseVNode("div", _hoisted_27, [
            createBaseVNode("label", _hoisted_28, toDisplayString($data.currentChatroom.campaign), 1),
            createBaseVNode("p", _hoisted_29, toDisplayString($data.currentChatroom.year), 1),
            createBaseVNode("p", _hoisted_30, "N° " + toDisplayString($data.currentChatroom.fnum), 1)
          ]),
          createBaseVNode("div", {
            class: normalizeClass(["tw-relative tw-mt-2 tw-h-full tw-bg-neutral-300", { "tw-rounded": $props.applicant == true }])
          }, [
            $data.messages_loading ? (openBlock(), createElementBlock("div", _hoisted_31, [
              createBaseVNode("div", _hoisted_32, [
                createVNode(_component_skeleton, {
                  width: "150px",
                  height: "43px",
                  classes: "tw-p-3 tw-rounded-xl tw-w-full tw-max-w-[30vw] !tw-bg-blue-300"
                })
              ]),
              createBaseVNode("div", _hoisted_33, [
                createVNode(_component_skeleton, {
                  width: "150px",
                  height: "43px",
                  classes: "tw-p-3 tw-rounded-xl tw-w-full tw-max-w-[30vw] !tw-bg-neutral-50"
                })
              ])
            ])) : createCommentVNode("", true),
            createBaseVNode("div", {
              class: normalizeClass(["tw-w-full tw-overflow-y-scroll", {
                "tw-relative tw-mb-4 tw-max-h-[65vh]": $props.applicant == false,
                "tw-absolute tw-max-h-[80%]": $props.applicant == true
              }]),
              id: "messages__list",
              style: normalizeStyle($data.messages_loading ? "opacity: 0" : "")
            }, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.messageByDates, (date) => {
                return openBlock(), createElementBlock("div", {
                  key: date.date
                }, [
                  createBaseVNode("div", _hoisted_34, [
                    _cache[18] || (_cache[18] = createBaseVNode("hr", { class: "tw-w-full" }, null, -1)),
                    createBaseVNode("p", _hoisted_35, toDisplayString(new Date(date.date).toISOString().slice(0, 10)), 1),
                    _cache[19] || (_cache[19] = createBaseVNode("hr", { class: "tw-w-full" }, null, -1))
                  ]),
                  (openBlock(true), createElementBlock(Fragment, null, renderList(date.messages, (message) => {
                    return openBlock(), createElementBlock("div", {
                      key: message.message_id,
                      class: normalizeClass(["tw-flex tw-w-full", message.me === true ? "tw-justify-end" : "tw-justify-start"])
                    }, [
                      createBaseVNode("div", {
                        class: normalizeClass(["tw-w-max-content tw-mx-3 tw-my-2 tw-flex tw-flex-col", message.me === true ? "tw-text-right" : "tw-text-left"]),
                        style: { "word-wrap": "break-word" }
                      }, [
                        createBaseVNode("p", {
                          class: normalizeClass(["tw-flex", message.me === true ? "tw-justify-end" : "tw-justify-start"])
                        }, [
                          createBaseVNode("span", _hoisted_36, [
                            _ctx.anonymous === 0 && message.me !== true ? (openBlock(), createElementBlock("span", _hoisted_37, toDisplayString(message.name) + " - ", 1)) : createCommentVNode("", true),
                            message.me === true ? (openBlock(), createElementBlock("span", _hoisted_38, toDisplayString(message.name) + " - ", 1)) : createCommentVNode("", true),
                            createTextVNode(" " + toDisplayString(message.date_hour), 1)
                          ])
                        ], 2),
                        createBaseVNode("span", {
                          class: normalizeClass(["tw-mt-1 tw-w-full tw-max-w-[30vw] tw-p-3 tw-text-start", {
                            "tw-bg-blue-500 tw-text-white": message.me === true,
                            "tw-bg-white": message.me !== true,
                            "tw-rounded-applicant": $props.applicant == true,
                            "tw-rounded-coordinator": $props.applicant == false
                          }]),
                          innerHTML: message.message
                        }, null, 10, _hoisted_39),
                        message.progress && message.progress === true ? (openBlock(), createElementBlock("span", _hoisted_40, "Envoi en cours...")) : createCommentVNode("", true)
                      ], 2)
                    ], 2);
                  }), 128))
                ]);
              }), 128))
            ], 6),
            createBaseVNode("div", {
              class: normalizeClass(["tw-bottom-3 tw-mr-3 tw-w-full tw-px-3", {
                "tw-sticky": $props.applicant == false,
                "tw-absolute": $props.applicant == true
              }])
            }, [
              $data.currentChatroom.status == 1 ? (openBlock(), createElementBlock("div", _hoisted_41, [
                createBaseVNode("div", _hoisted_42, [
                  withDirectives(createBaseVNode("textarea", {
                    type: "text",
                    id: "messenger_message",
                    class: normalizeClass(["!tw-h-auto tw-resize-none tw-p-2", {
                      "tw-rounded-applicant": $props.applicant == true,
                      "tw-rounded-coordinator": $props.applicant == false
                    }]),
                    rows: "2",
                    disabled: $data.send_progress,
                    spellcheck: "true",
                    placeholder: _ctx.translate("COM_EMUNDUS_MESSENGER_WRITE_MESSAGE"),
                    "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.currentMessage = $event),
                    onKeydown: _cache[8] || (_cache[8] = withKeys(withModifiers(($event) => $options.sendMessage($event), ["exact", "prevent"]), ["enter"]))
                  }, null, 42, _hoisted_43), [
                    [vModelText, $data.currentMessage]
                  ])
                ]),
                createBaseVNode("span", {
                  class: "material-symbols-outlined tw-cursor-pointer",
                  onClick: _cache[9] || (_cache[9] = (...args) => $options.sendMessage && $options.sendMessage(...args))
                }, "send")
              ])) : createCommentVNode("", true),
              !$data.messages_loading && $options.messageByDates.length > 0 && $options.showCloseChatroomButton && $data.currentChatroom.status == 1 ? (openBlock(), createElementBlock("button", {
                key: 1,
                type: "button",
                class: "tm-mt-2 tw-ml-2 tw-cursor-pointer tw-text-blue-500",
                onClick: _cache[10] || (_cache[10] = (...args) => $options.closeChatroom && $options.closeChatroom(...args))
              }, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CLOSE_CHATROOM")), 1)) : createCommentVNode("", true),
              $data.currentChatroom.status == 0 ? (openBlock(), createElementBlock("div", {
                key: 2,
                class: normalizeClass(["tw-flex tw-items-center tw-gap-1 tw-bg-white tw-p-2", {
                  "tw-rounded-applicant": $props.applicant == true,
                  "tw-rounded-coordinator": $props.applicant == false
                }])
              }, [
                createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CHATROOM_CLOSED")), 1),
                createBaseVNode("button", {
                  type: "button",
                  class: "tw-text-underline tw-cursor-pointer tw-text-blue-500",
                  onClick: _cache[11] || (_cache[11] = (...args) => $options.openChatroom && $options.openChatroom(...args))
                }, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_OPEN_CHATROOM")), 1)
              ], 2)) : createCommentVNode("", true)
            ], 2)
          ], 2)
        ])) : (openBlock(), createElementBlock("div", _hoisted_44, [
          createBaseVNode("div", _hoisted_45, [
            _cache[20] || (_cache[20] = createBaseVNode("img", {
              src: _imports_0,
              style: { "width": "250px", "object-fit": "cover", "height": "65px" }
            }, null, -1)),
            createBaseVNode("p", _hoisted_46, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_SELECT_CHATROOM")), 1),
            $props.applicant ? (openBlock(), createElementBlock("button", {
              key: 0,
              type: "button",
              class: "tw-btn-primary !tw-w-auto",
              onClick: _cache[12] || (_cache[12] = ($event) => $data.createNewChatroom = true)
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM")), 1)) : createCommentVNode("", true)
          ])
        ]))
      ], 6)) : (openBlock(), createElementBlock("div", _hoisted_47, [
        createBaseVNode("div", _hoisted_48, [
          _cache[21] || (_cache[21] = createBaseVNode("img", {
            src: _imports_0,
            style: { "width": "250px", "object-fit": "cover", "height": "65px" }
          }, null, -1)),
          $props.applicant ? (openBlock(), createElementBlock("p", _hoisted_49, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_NO_MESSAGES")), 1)) : (openBlock(), createElementBlock("p", _hoisted_50, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_NO_MESSAGES_COORDINATOR")), 1)),
          $props.applicant ? (openBlock(), createElementBlock("button", {
            key: 2,
            type: "button",
            class: "tw-btn-primary !tw-w-auto",
            onClick: _cache[13] || (_cache[13] = ($event) => $data.createNewChatroom = true)
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM")), 1)) : (openBlock(), createElementBlock("button", {
            key: 3,
            type: "button",
            class: "tw-btn-primary !tw-w-auto",
            onClick: _cache[14] || (_cache[14] = (...args) => $options.createChatroom && $options.createChatroom(...args))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM")), 1))
        ])
      ]))
    ])) : (openBlock(), createElementBlock("div", _hoisted_51))
  ]);
}
const Messages = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
const Messages$1 = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  default: Messages
}, Symbol.toStringTag, { value: "Module" }));
export {
  Messages as M,
  Messages$1 as a,
  messengerServices as m
};
