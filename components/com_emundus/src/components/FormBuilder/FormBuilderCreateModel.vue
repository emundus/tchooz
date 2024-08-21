<template>
  <div id='form-builder-create-model' class="tw-flex tw-flex-col tw-justify-between tw-w-full">
    <div class="tw-w-full">
      <div class="tw-flex tw-items-center tw-justify-between tw-p-4">
        <p>{{ translate('COM_EMUNDUS_FORM_BUILDER_MODEL_PROPERTIES') }}</p>
        <span class="material-symbols-outlined tw-cursor-pointer" @click="$emit('close')">close</span>
      </div>

      <div v-if="!loading" id="model-properties" class="tw-flex tw-flex-col tw-justify-start tw-p-4 tw-text-end">
        <p class="em-main-500-color">{{ translate('COM_EMUNDUS_FORM_BUILDER_MODEL_PROPERTIES_INTRO') }}</p>
        <label for="page-model-title" class="tw-mt-4 tw-text-end tw-w-full">{{
            translate('COM_EMUNDUS_FORM_BUILDER_MODEL_INPUT_LABEL')
          }}</label>
        <input id="page-model-title" class="tw-w-full tw-mb-4" type="text" v-model="modelTitle"/>
        <p v-if="alreadyExists" class="tw-text-red-600">
          {{ translate('COM_EMUNDUS_FORM_BUILDER_MODEL_WITH_SAME_TITLE_EXISTS') }}</p>
      </div>
      <div v-else class="tw-w-full tw-flex tw-items-center tw-justify-center">
        <div class="em-loader"></div>
      </div>
    </div>
    <div class="tw-flex tw-items-center tw-justify-between actions tw-w-full">
      <button
          class="tw-btn-primary tw-m-4"
          @click="addFormModel()"
          :disabled="modelTitle.length < 1 || loading"
          :class="{'tw-text-white tw-bg-gray-500 tw-w-full tw-px-2 tw-py-3 tw-rounded-coordinator': modelTitle.length < 1 || loading,}"
      >
        {{ translate('COM_EMUNDUS_FORM_BUILDER_SECTION_PROPERTIES_SAVE') }}
      </button>
    </div>
  </div>
</template>

<script>
import formBuilderService from '@/services/formbuilder';
import Swal from "sweetalert2";

export default {
  props: {
    page: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      modelTitle: '',
      models: [],
      alreadyExists: false,
      loading: false
    }
  },
  mounted() {
    this.getModels();
  },
  methods: {
    getModels() {
      formBuilderService.getModels().then((response) => {
        if (response.status) {
          this.models = response.data;
        }
      });
    },
    checkTitleNotAlreadyExists() {
      const modelExists = this.models.filter((model) => {
        return model.label[this.shortDefaultLang] === this.modelTitle.trim();
      });

      this.alreadyExists = modelExists.length > 0;
    },
    addFormModel() {
      this.loading = true;
      this.modelTitle = this.modelTitle.trim();

      if (this.modelTitle.length < 1) {
        Swal.fire({
          type: 'warning',
          title: this.translate('COM_EMUNDUS_FORM_BUILDER_MODEL_MUST_HAVE_TITLE'),
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            confirmButton: 'em-swal-confirm-button',
            actions: 'em-swal-single-action',
          }
        });

        this.loading = false;
        return;
      }

      const modelExists = this.models.filter((model) => {
        return model.label[this.shortDefaultLang] === this.modelTitle;
      });

      this.alreadyExists = modelExists.length > 0;

      if (!this.alreadyExists) {
        formBuilderService.addFormModel(this.page, this.modelTitle).then((response) => {
          if (!response.status) {
            this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_FAILURE'), response.msg);
          } else {
            Swal.fire({
              type: 'success',
              title: this.translate('COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_SUCCESS'),
              reverseButtons: true,
              customClass: {
                title: 'em-swal-title',
                confirmButton: 'em-swal-confirm-button',
                actions: 'em-swal-single-action',
              }
            });
          }
          this.loading = false;
          this.$emit('close');
        });
      } else {
        this.replaceFormModel(modelExists[0].id, this.modelTitle);
      }
    },
    replaceFormModel(model_id, label) {
      formBuilderService.addFormModel(this.page, label).then((response) => {
        if (!response.status) {
          this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_FAILURE'), response.msg);

          this.$emit('close');
        } else {
          const modelIds = [model_id];
          formBuilderService.deleteFormModelFromId(modelIds).then(() => {
            this.$emit('close');
          })
        }

        if (this.loading) {
          this.loading = false;
        }
      });
    }
  },
  watch: {
    modelTitle: function (val, oldVal) {
      if (val != oldVal) {
        this.checkTitleNotAlreadyExists();
      }
    }
  }
}
</script>

<style lang="scss">
#form-builder-create-model {
  #model-properties {
    height: fit-content;
  }

  .tw-btn-primary:disabled {
    cursor: not-allowed;
    border-color: var(--grey-color);
    background: var(--grey-color);

    &:hover {
      color: white;
    }
  }
}
</style>
