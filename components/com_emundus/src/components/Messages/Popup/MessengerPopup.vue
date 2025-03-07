<script>
import Modal from "@/components/Modal.vue";
import Messages from "@/views/Messenger/Messages.vue";

export default {
  name: "MessengerPopup",
  components: {Messages, Modal},
  emits: ['close', 'open'],
  props: {
    fnum: {
      type: String,
      required: true
    },
    fullname: {
      type: String,
      required: true
    },
    unread_messages: {
      type: Array
    }
  },
  methods: {
    beforeClose() {
      this.$emit('close');
    },
    beforeOpen() {
      this.$emit('open');
    },
    closeModal() {
      this.$emit('close');
    },
    closedChatroom(fnum) {
      this.$emit('closedChatroom', fnum);
    }
  }
}
</script>

<template>
  <Teleport to="body">
    <modal
        :name="'messenger-modal'"
        :class="'placement-center tw-bg-white tw-rounded tw-shadow-modal tw-max-h-[80vh] tw-overflow-y-auto'"
        transition="nice-modal-fade"
        :width="'95%'"
        :height="'95%'"
        :delay="100"
        :adaptive="true"
        :clickToClose="false"
        @closed="beforeClose"
        @before-open="beforeOpen"
    >
      <Messages :is-modal="true" :fnum="fnum" :fullname="fullname" :unread_messages="unread_messages" @close="closeModal" @closedChatroom="closedChatroom" />
    </modal>
  </Teleport>
</template>

<style scoped>
@import '../../../assets/css/modal.scss';
</style>