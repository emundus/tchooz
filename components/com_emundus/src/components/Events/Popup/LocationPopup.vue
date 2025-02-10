<script>
import Modal from "@/components/Modal.vue";
import LocationForm from "@/views/Events/LocationForm.vue";

export default {
  name: "LocationPopup",
  components: {LocationForm, Modal},
  emits: ['close', 'open'],
  methods: {
    beforeClose() {
      this.$emit('close');
    },
    beforeOpen() {
      this.$emit('open');
    },
    closeModal(location_id) {
      this.$emit('close',location_id);
    }
  }
}
</script>

<template>
  <modal
      :name="'add-location-modal'"
      :class="'placement-center tw-rounded tw-shadow-modal tw-px-4 tw-max-h-[80vh] tw-overflow-y-auto'"
      transition="nice-modal-fade"
      :width="'600px'"
      :delay="100"
      :adaptive="true"
      :clickToClose="false"
      @closed="beforeClose"
      @before-open="beforeOpen"
  >
    <LocationForm :is-modal="true" @close="closeModal" />
  </modal>
</template>

<style scoped>
@import '../../../assets/css/modal.scss';

.placement-center {
  position: fixed;
  left: 50%;
  transform: translate(-50%, -50%);
  top: 50%;
}
</style>