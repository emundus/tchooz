<template>
  <div id="form-builder-documents">
    <div id="form-builder-title" class="tw-cursor-pointer tw-flex tw-items-center tw-justify-between tw-p-4"
         @click="$emit('show-documents')">
      <span>{{ translate('COM_EMUNDUS_FORM_BUILDER_EVERY_DOCUMENTS') }}</span>
      <span id="add-document" class="material-symbols-outlined tw-cursor-pointer" @click="createDocument">add</span>
    </div>
    <div
        v-for="document in documents"
        :key="document.id"
        @click="$emit('show-documents')"
        class="tw-p-4"
    >
      <p class="document-label">{{ document.label }}</p>
    </div>
  </div>
</template>

<script>
import formService from '@/services/form.js';
import errors from "@/mixins/errors";
import { useFormBuilderStore } from "@/stores/formbuilder.js";

export default {
  name: 'FormBuilderDocuments',
  props: {
    profile_id: {
      type: Number,
      required: true
    },
    campaign_id: {
      type: Number,
      required: true
    },
  },
  mixins: [errors],
  data() {
    return {
      documents: [],
    }
  },
  setup() {
    return {
      formBuilderStore: useFormBuilderStore()
    }
  },
  created() {
    this.getDocuments();

    if (this.formBuilderStore.getDocumentModels.length === 0) {
      this.getDocumentModels();
    }
  },
  methods: {
    getDocuments() {
      formService.getDocuments(this.profile_id).then(response => {
        if (response.status) {
          this.documents = response.data;
        } else {
          this.documents = [];
          this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_GET_DOCUMENTS_FAILED'), response.msg);
        }
      });
    },
    getDocumentModels() {
      formService.getDocumentModels().then(response => {
        if (response.status) {
          this.formBuilderStore.updateDocumentModels(response.data);
        }
      });
    },
    createDocument() {
      this.$emit('open-create-document');
    },
  }
}
</script>

<style lang="scss">
#form-builder-documents {
  #form-builder-title {
    margin-top: 0;
    font-weight: 700;
    font-size: 16px;
    line-height: 19px;
    letter-spacing: .0015em;
    color: #080c12;
  }

  p {
    cursor: pointer;
    font-weight: 400;
    font-size: 14px;
    line-height: 18px;
  }
}
</style>
