<template>
  <span :id="'modalEmailPreview_' + model">
    <modal
        :name="'modalEmailPreview_' + model"
        height="auto"
        transition="nice-modal-fade"
        :min-width="200"
        :min-height="200"
        :delay="100"
        :adaptive="true"
        :clickToClose="false"
        @before-open="beforeOpen"
    >

      <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <h4>
          {{ ModelPreview }}
        </h4>
        <button class="tw-cursor-pointer tw-bg-transparent" @click.prevent="$modal.hide('modalEmailPreview_' + model)">
          <span class="material-icons-outlined">close</span>
        </button>
      </div>

      <div class="tw-mb-4">
          <p v-if="email != null"><span v-html="email.message"></span></p>
      </div>

    </modal>
  </span>
</template>

<script>
const qs = require("qs");

export default {
  name: "modalEmailPreview",
  props: {model: String, models: Array},
  data() {
    return {
      email: null,
      ModelPreview: this.translate("COM_EMUNDUS_ONBOARD_EMAIL_PREVIEWMODEL"),
    };
  },
  methods: {
    beforeOpen() {
      this.models.forEach(element => {
        if (element.id == this.model) {
          this.email = element;
        }
      });
    },
  },
};
</script>

<style scoped>
</style>
