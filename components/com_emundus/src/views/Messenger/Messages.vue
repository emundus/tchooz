<script>
import {v4 as uuid} from 'uuid'

/* Components */
import Parameter from "@/components/Utils/Parameter.vue";

/* Services */
import messengerServices from "@/services/messenger";

/* Stores */
import Modal from "@/components/Modal.vue";
import Multiselect from "vue-multiselect";
import AttachDocument from "@/components/Messages/modals/AttachDocument.vue";
import Skeleton from "@/components/Skeleton.vue";

export default {
  name: "Messages",
  components: {Skeleton, AttachDocument, Multiselect, Modal, Parameter},
  emits: ['close', 'open'],
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

      currentMessage: '',
      search: '',
    }
  },
  created() {
    if (this.applicant) {
      messengerServices.getFilesByUser().then(response => {
        this.files = response.data;
      });

      messengerServices.getChatroomsByUser().then(response => {
        this.chatrooms = response.data;

        /*if (this.chatrooms.length > 0) {
          this.currentChatroom = this.chatrooms.find(chatroom => chatroom.status === 1);
        }*/

        if(this.unread_messages && this.unread_messages.length > 0) {
          this.unread_messages.forEach((unread_message) => {
            this.chatrooms.find(chatroom => chatroom.fnum === unread_message.fnum).unread = unread_message.notifications;
          });
        }

        this.loading = false;
      });
    } else {
      messengerServices.getChatroomsByFnum(this.fnum).then(response => {
        this.chatrooms = response.data;

        if (this.chatrooms.length > 0) {
          // Set the first opened chatroom as the current chatroom
          this.currentChatroom = this.chatrooms[0];
        }

        this.loading = false;
      });
    }
  },
  methods: {
    nameWithYear({label, year}) {
      return `${label} (${year})`
    },

    async getMessagesByFnum(loader = true, scroll = true) {
      this.messages_loading = loader;
      messengerServices.getMessagesByFnum(this.currentChatroom.fnum).then(response => {
        this.messages = response.data.messages;
        this.dates = response.data.dates;
        this.anonymous = parseInt(response.data.anonymous);

        if (scroll) {
          //this.scrollToBottom();
          this.messages_loading = false;
        }
      });
    },

    createChatroom() {
      if (this.fileSelected === null && this.fnum === null) {
        return;
      }
      else if (this.fileSelected === null && this.fnum !== null) {
        this.fileSelected = {fnum: this.fnum};
      }

      // Check if chatroom already exists
      let chatroomExists = this.chatrooms.find(chatroom => chatroom.fnum === this.fileSelected.fnum);

      if (chatroomExists) {
        this.currentChatroom = chatroomExists;
        this.createNewChatroom = false;
        return;
      }
      messengerServices.createChatroom(this.fileSelected.fnum).then(response => {
        this.chatrooms.push(response.data);
        this.currentChatroom = response.data;
        this.createNewChatroom = false;
      });
    },

    closeChatroom() {
      messengerServices.closeChatroom(this.currentChatroom.fnum).then(response => {
        if (response.status) {
          this.currentChatroom.status = 0;

          let notifications_counter = document.querySelector('a[href*="messenger"] .notifications-counter');
          if(notifications_counter) {
            notifications_counter.remove();
          }
          let notifications_column = document.querySelector('a[id="'+this.currentChatroom.fnum+'"] .messenger__notifications_counter');
          if(notifications_column) {
            notifications_column.remove();
          }

          this.$emit('closedChatroom', this.currentChatroom.fnum);
        } else {
          Swal.fire({
            title: Joomla.Text._("COM_EMUNDUS_ONBOARD_ERROR"),
            text: response.msg,
            type: "error",
            showCancelButton: false,
            showConfirmButton: false,
            timer: 3000,
          });
        }
      });
    },

    openChatroom() {
      messengerServices.openChatroom(this.currentChatroom.fnum).then(response => {
        if (response.status) {
          this.currentChatroom.status = 1;
        } else {
          Swal.fire({
            title: Joomla.Text._("COM_EMUNDUS_ONBOARD_ERROR"),
            text: response.msg,
            type: "error",
            showCancelButton: false,
            showConfirmButton: false,
            timer: 3000,
          });
        }
      });
    },

    async sendMessage(e) {
      if(this.currentMessage === '') {
        this.currentMessage = document.getElementById('messenger_message').value;
      }

      if (this.currentMessage.trim() !== '' && !this.send_progress) {
        this.send_progress = true;
        let message_id = Math.floor(Math.random() * 1000) + 9999;

        // Push instantly to the messages array
        this.pushToDatesArray({
          message_id: message_id,
          progress: true,
          user_id_from: 0,
          me: true,
          user_id_to: null,
          folder_id: 2,
          date_time: this.formatedTimestamp(),
          date_hour: new Date().toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'}),
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
            // If message is sent update progress attribute and message_id with the response
            this.messages.forEach((message) => {
              if (message.message_id === message_id) {
                message.progress = false;

                // Add message in chatroom messages
                this.chatrooms.find(chatroom => chatroom.ccid === this.currentChatroom.ccid).messages.push({
                  message: message.message
                });

                this.chatrooms.forEach((chatroom) => {
                  if (chatroom.ccid === this.currentChatroom.ccid) {
                    chatroom.unread = 0;
                  }
                });

                let notifications_counter = document.querySelector('a[href*="messenger"] .notifications-counter');
                if(notifications_counter) {
                  notifications_counter.remove();
                }
                let notifications_column = document.querySelector('a[id="'+this.currentChatroom.fnum+'"] .messenger__notifications_counter');
                if(notifications_column) {
                  notifications_column.remove();
                }

                // Dispatch vanilla js event to remove notifications
                let event = new CustomEvent('removeMessengerNotifications', {detail: {fnum: this.currentChatroom.fnum}});
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
              timer: 3000,
            });
          }
        });

        this.currentMessage = '';
        document.getElementById('messenger_message').value = '';
        this.send_progress = false;
        this.scrollToBottom();
      }
    },

    pushToDatesArray(message) {
      let pushToDate = false;
      // Replace moment by vanilla js date
      let message_date = new Date().toISOString().slice(0, 10);
      if (message.date_time) {
        message_date = message.date_time.split(' ')[0];
      }

      this.dates.forEach((elt, index) => {
        if (elt.dates == message_date) {
          this.dates[index].messages.push(message.message_id);
          pushToDate = true;
        }
      });
      if (!pushToDate) {
        var new_date = {
          dates: new Date().toISOString().slice(0, 10),
          messages: []
        }
        new_date.messages.push(message.message_id);
        this.dates.push(new_date);
      }
      this.messages.push(message);
    },
    scrollToBottom() {
      setTimeout(() => {
        const container = document.getElementById("messages__list");
        if(container) {
          container.scrollTop = container.scrollHeight;
        }

        //this.messages_loading = false;
      }, 100);
    },
    formatedTimestamp() {
      const d = new Date()
      const date = d.toISOString().split('T')[0];
      const time = d.toTimeString().split(' ')[0];
      return `${date} ${time}`
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
        messages.push({date: date, messages: messages_array});
      });

      return messages;
    },

    openedChatrooms() {
      // Search in messages
      if(this.search === '') {
        return this.chatrooms.filter(chatroom => chatroom.status === 1);
      }

      let chatroomsByMessage = this.chatrooms.filter(chatroom => chatroom.messages.some(message => message.message.toLowerCase().includes(this.search.toLowerCase())));
      return chatroomsByMessage.filter(chatroom => chatroom.status === 1);
    },

    closedChatrooms() {
      if(this.search === '') {
        return this.chatrooms.filter(chatroom => chatroom.status === 0);
      }

      let chatroomsByMessage = this.chatrooms.filter(chatroom => chatroom.messages.some(message => message.message.toLowerCase().includes(this.search.toLowerCase())));
      return chatroomsByMessage.filter(chatroom => chatroom.status === 0);
    },

    showCloseChatroomButton() {
      // Always display the close chatroom button if the user is a coordinator
      if (!this.applicant) {
        return true;
      }

      // Display the close chatroom button if the user is not a coordinator and the chatroom is open and the last message is not from the user
      if (this.currentChatroom.status === 1 && this.messages.length > 0) {
        return this.messages[this.messages.length - 1].me === false;
      }
    }
  },
  watch: {
    currentChatroom: {
      handler: function (val,oldVal) {
        if ((!oldVal && val) || (oldVal && val && val.id !== oldVal.id)) {
          this.getMessagesByFnum();
        }
      },
      deep: true
    },
    messages: {
      handler: function (val,oldVal) {
        this.$nextTick(() => {
          this.scrollToBottom();
        });
      },
      deep: true
    }
  }
}
</script>

<template>
  <div class="tw-h-full tw-overflow-hidden">
    <div v-if="!loading" class="tw-h-full tw-flex tw-flex-col">

      <modal
          :name="'messenger-files-modal'"
          :class="'placement-center tw-bg-white tw-rounded tw-shadow-modal tw-max-h-[80vh] tw-overflow-y-auto'"
          transition="nice-modal-fade"
          :width="'40%'"
          :height="'30%'"
          :delay="100"
          :adaptive="true"
          :clickToClose="false"
          v-if="createNewChatroom"
      >
        <div v-if="isModal"
             class="tw-pt-4 tw-px-4 tw-sticky tw-top-0 tw-bg-white tw-border-b tw-border-neutral-300 tw-z-10">
          <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
            <h2>
              {{ translate("COM_EMUNDUS_MESSENGER_SELECT_FILE") }}
            </h2>
            <button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="createNewChatroom = false">
              <span class="material-symbols-outlined">close</span>
            </button>
          </div>
        </div>

        <div class="tw-p-4">
          <Multiselect
              :options="files"
              v-model="fileSelected"
              label="label"
              :custom-label="nameWithYear"
              track-by="id"
              :placeholder="translate('COM_EMUNDUS_MESSENGER_SELECT_FILE')"
              :selectLabel="''"
              :multiple="false">
            <template #noOptions>{{ translate('COM_EMUNDUS_MULTISELECT_NORESULTS') }}</template>
            <template #noResult>{{ translate('COM_EMUNDUS_MULTISELECT_NORESULTS') }}</template>
          </Multiselect>

          <button type="button" class="tw-btn-primary !tw-w-auto tw-float-right tw-mt-3" @click="createChatroom">
            {{ translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM") }}
          </button>
        </div>

      </modal>

      <!-- HEADER -->
      <div v-if="isModal" class="tw-pt-4 tw-px-4 tw-bg-white tw-border-b tw-border-neutral-300 tw-z-10">
        <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
          <h2>
            {{ translate("COM_EMUNDUS_MESSENGER_TITLE") }}
          </h2>
          <button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="$emit('close')">
            <span class="material-symbols-outlined">close</span>
          </button>
        </div>
      </div>

      <!-- MAIN -->
      <div v-if="chatrooms.length > 0" class="tw-mt-6 tw-h-full" :class="{'tw-grid': applicant == true}"
           :style="applicant == true ? 'grid-template-columns: 33% 66%;' : ''">

        <!-- ASIDE -->
        <div class="tw-h-[95%] tw-flex tw-flex-col tw-justify-between tw-items-center" v-if="applicant">
          <div class="tw-w-full">
            <div class="tw-px-4 tw-mb-4">
              <input name="search" v-model="search" class="!tw-rounded-xl"
                     :placeholder="translate('COM_EMUNDUS_MESSENGER_SEARCH_IN_MESSAGES')"/>
            </div>

            <!-- OPENED CHATROOMS -->
            <div class="tw-w-full">
              <div v-for="chatroom in openedChatrooms" :key="chatroom.ccid"
                   :class="currentChatroom && chatroom.ccid === currentChatroom.ccid ? 'tw-bg-neutral-300' : ''"
                   class="tw-mt-3">
                <div class="tw-px-4 tw-py-3 tw-w-full tw-cursor-pointer hover:tw-bg-neutral-200"
                     @click="currentChatroom = chatroom">
                  <div class="tw-w-full">
                    <div class="tw-flex tw-items-start tw-gap-2">
                      <label class="tw-font-semibold !tw-mb-0 tw-line-clamp-2">
                        {{ chatroom.campaign }}
                      </label>
                      <div class="tw-flex tw-items-center tw-justify-center tw-bg-red-500 tw-rounded-full tw-text-sm tw-text-white" style="min-width: 16px;width: 16px;height: 16px" v-if="chatroom.unread && chatroom.unread > 0">{{ chatroom.unread }}</div>
                    </div>

                    <p class="tw-italic tw-text-sm">{{ chatroom.year }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- CLOSED CHATROOMS -->
            <div v-if="closedChatrooms.length > 0" class="tw-w-full">
              <hr/>
              <div @click="showClosedChatroom = !showClosedChatroom"
                   class="tw-cursor-pointer tw-flex tw-items-center tw-justify-between tw-gap-2 tw-px-4">
                <label class="tw-cursor-pointer tw-font-semibold !tw-mb-0">{{
                    translate('COM_EMUNDUS_MESSENGER_CLOSED_CHATROOMS')
                  }}</label>
                <span class="material-symbols-outlined tw-transition-transform"
                      :class="{'tw-rotate-90': showClosedChatroom}">chevron_right</span>
              </div>

              <div v-show="showClosedChatroom">
                <div v-for="chatroom in closedChatrooms" :key="chatroom.ccid"
                     :class="currentChatroom && chatroom.ccid === currentChatroom.ccid ? 'tw-bg-neutral-300' : ''"
                     class="tw-mt-3">
                  <div class="tw-px-4 tw-py-3 tw-w-full tw-cursor-pointer hover:tw-bg-neutral-200"
                       @click="currentChatroom = chatroom">
                    <div class="tw-w-full">
                      <label class="tw-font-semibold">{{ chatroom.campaign }}</label>
                      <p class="tw-italic tw-text-sm">{{ chatroom.year }}</p>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="tw-px-4 tw-w-full" v-if="currentChatroom">
            <button type="button" class="tw-btn-primary tw-w-full" @click="createNewChatroom = true">
              {{ translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM") }}
            </button>
          </div>

        </div>

        <!-- MESSAGES -->
        <div v-if="currentChatroom" class="tw-flex tw-flex-col tw-h-[95%]">
          <div class="tw-px-2">
            <label class="tw-font-semibold">{{ currentChatroom.campaign }}</label>
            <p class="tw-italic tw-text-sm">{{ currentChatroom.year }}</p>
            <p class="tw-italic tw-text-sm">N° {{ currentChatroom.fnum }}</p>
          </div>

          <div class="tw-mt-2 tw-bg-neutral-300 tw-h-full tw-relative"
               :class="{'tw-rounded': applicant == true}">
            <div class="tw-mt-2 tw-mx-3" v-if="messages_loading">
              <div class="tw-flex tw-justify-end">
                <skeleton width="150px" height="43px"
                          classes="tw-p-3 tw-rounded-xl tw-w-full tw-max-w-[30vw] !tw-bg-blue-300"/>
              </div>
              <div class="tw-flex tw-justify-start">
                <skeleton width="150px" height="43px"
                          classes="tw-p-3 tw-rounded-xl tw-w-full tw-max-w-[30vw] !tw-bg-neutral-50"/>
              </div>
            </div>

            <div class="tw-w-full tw-overflow-y-scroll" id="messages__list"
                 :class="{'tw-relative tw-mb-4 tw-max-h-[65vh]': applicant == false, 'tw-absolute tw-max-h-[80%]': applicant == true}"
                 :style="messages_loading ? 'opacity: 0' : ''">
              <div v-for="date in messageByDates" :key="date.date">
                <div class="tw-flex tw-items-center tw-ml-4">
                  <hr class="tw-w-full">
                  <p class="tw-px-5">{{ new Date(date.date).toISOString().slice(0, 10) }}</p>
                  <hr class="tw-w-full">
                </div>

                <div v-for="message in date.messages" :key="message.message_id" class="tw-w-full tw-flex"
                     :class="message.me === true ? 'tw-justify-end' : 'tw-justify-start'">
                  <div class="tw-w-max-content tw-flex tw-flex-col tw-mx-3 tw-my-2" style="word-wrap: break-word;"
                       :class="message.me === true ? 'tw-text-right' : 'tw-text-left'">
                    <p class="tw-flex" :class="(message.me === true) ? 'tw-justify-end' : 'tw-justify-start'">
                      <span class="tw-text-sm tw-font-bold">
                        <span v-if="anonymous === 0 && message.me !== true">{{ message.name }} - </span>
                        <span v-if="message.me === true">{{ message.name }} - </span>
                        {{ message.date_hour }}
                      </span>
                    </p>
                    <span class="tw-mt-1 tw-p-3 tw-w-full tw-max-w-[30vw] tw-text-start"
                          :class="{'tw-bg-blue-500 tw-text-white': message.me === true, 'tw-bg-white': message.me !== true, 'tw-rounded-applicant': applicant == true, 'tw-rounded-coordinator': applicant == false}"
                          v-html="message.message"></span>
                    <span v-if="message.progress && message.progress === true" class="tw-text-sm tw-text-italic">Envoi en cours...</span>
                  </div>
                </div>
              </div>
            </div>

            <div class="tw-bottom-3 tw-px-3 tw-mr-3 tw-w-full"
                 :class="{'tw-sticky': applicant == false, 'tw-absolute': applicant == true}">
              <div class="tw-flex tw-items-center tw-gap-2" v-if="currentChatroom.status == 1">
                <div class="tw-w-full">
                <textarea type="text"
                          id="messenger_message"
                          class="tw-p-2 tw-resize-none !tw-h-auto"
                          :class="{'tw-rounded-applicant': applicant == true, 'tw-rounded-coordinator': applicant == false}"
                          rows="2"
                          :disabled="send_progress"
                          spellcheck="true"
                          :placeholder="translate('COM_EMUNDUS_MESSENGER_WRITE_MESSAGE')"
                          v-model="currentMessage"
                          @keydown.enter.exact.prevent="sendMessage($event)"
                />
                </div>
                <span class="material-symbols-outlined tw-cursor-pointer" @click="sendMessage">send</span>
              </div>
              <button type="button" class=" tm-mt-2 tw-ml-2 tw-cursor-pointer tw-text-blue-500"
                      v-if="!messages_loading && messageByDates.length > 0 && showCloseChatroomButton && currentChatroom.status == 1"
                      @click="closeChatroom">{{ translate('COM_EMUNDUS_MESSENGER_CLOSE_CHATROOM') }}
              </button>

              <div v-if="currentChatroom.status == 0" class="tw-p-2 tw-bg-white tw-flex tw-items-center tw-gap-1"
                   :class="{'tw-rounded-applicant': applicant == true, 'tw-rounded-coordinator': applicant == false}">
                <p>{{ translate('COM_EMUNDUS_MESSENGER_CHATROOM_CLOSED') }}</p>
                <button type="button" class="tw-cursor-pointer tw-text-blue-500 tw-text-underline"
                        @click="openChatroom">{{ translate('COM_EMUNDUS_MESSENGER_OPEN_CHATROOM') }}
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else>
          <div class="tw-flex tw-flex-col tw-gap-2 tw-items-center tw-justify-center tw-h-full tw-mt-6">
            <img src="../../../../../media/com_emundus/images/tchoozy/complex-illustrations/hiding.svg"
                 style="width: 250px; object-fit: cover; height: 65px"/>
            <p class="tw-text-neutral-500">{{ translate("COM_EMUNDUS_MESSENGER_SELECT_CHATROOM") }}</p>
            <button v-if="applicant" type="button" class="tw-btn-primary !tw-w-auto" @click="createNewChatroom = true">
              {{ translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM") }}
            </button>
          </div>
        </div>
      </div>

      <div v-else>
        <div class="tw-flex tw-flex-col tw-gap-2 tw-items-center tw-justify-center tw-h-full tw-mt-6">
          <img src="../../../../../media/com_emundus/images/tchoozy/complex-illustrations/hiding.svg"
               style="width: 250px; object-fit: cover; height: 65px"/>
          <p class="tw-text-neutral-500" v-if="applicant">
            {{ translate("COM_EMUNDUS_MESSENGER_NO_MESSAGES") }}
          </p>
          <p v-else class="tw-text-neutral-500">
            {{ translate("COM_EMUNDUS_MESSENGER_NO_MESSAGES_COORDINATOR") }}
          </p>
          <button v-if="applicant" type="button" class="tw-btn-primary !tw-w-auto" @click="createNewChatroom = true">
            {{ translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM") }}
          </button>
          <button v-else type="button" class="tw-btn-primary !tw-w-auto" @click="createChatroom">
            {{ translate("COM_EMUNDUS_MESSENGER_CREATE_CHATROOM") }}
          </button>
        </div>
      </div>
    </div>

    <div v-else class="em-page-loader"></div>
  </div>
</template>