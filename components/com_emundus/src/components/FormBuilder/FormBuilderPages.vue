<template>
  <div id="form-builder-pages">
    <p class="form-builder-title tw-flex tw-items-center md:tw-justify-center lg:tw-justify-between tw-p-4">
      <span>{{ translate('COM_EMUNDUS_FORM_BUILDER_EVERY_PAGE') }}</span>
      <span id="add-page" class="material-symbols-outlined tw-cursor-pointer" @click="$emit('open-page-create')"> add </span>
    </p>
    <draggable :model-value="pages" @update:model-value="pages = $event" group="form-builder-pages" :sort="true" class="draggables-list" @end="onDragEnd">
      <transition-group>
        <div
            class="tw-font-medium tw-cursor-pointer"
            v-for="(page, index) in formPages"
            :key="page.id"
            :class="{selected: page.id == selected}"
        >
          <div class="tw-flex tw-items-center tw-justify-between" @mouseover="pageOptionsShown = page.id"
               @mouseleave="pageOptionsShown = 0">
            <p @click="selectPage(page.id)" class="tw-w-full tw-p-4 form-builder-page-label">{{
                page.label !== '' ? translate(page.label) : (translate('COM_EMUNDUS_FILES_PAGE') + ' ' + (index + 1))
              }}</p>
            <div class="tw-flex tw-items-center tw-p-4" :style="pageOptionsShown === page.id ? 'opacity:1' : 'opacity: 0'">
              <popover :popoverArrowClass="'custom-popover-arraow'" :open-class="'form-builder-pages-popover'" :position="'left'">
                <transition :name="'slide-down'" type="transition">
                  <div>
                    <nav aria-label="action" class="em-flex-col-start">
                      <p @click="deletePage(page)" class="tw-cursor-pointer tw-p-2 tw-text-base tw-text-red-600">
                        {{ translate('COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE') }}
                      </p>
                      <p @click="createModelFrom(page)" class="tw-cursor-pointer tw-p-2 tw-text-base">
                        {{ translate('COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_TITLE') }}
                      </p>
                    </nav>
                  </div>
                </transition>
              </popover>
            </div>
          </div>
        </div>
      </transition-group>
    </draggable>

    <transition-group>
      <div
          class="tw-font-medium tw-cursor-pointer"
          v-for="page in submissionPages"
          :key="page.id"
          :class="{selected: page.id == selected}"
      >
        <div class="tw-flex tw-items-center tw-justify-between">
          <p @click="selectPage(page.id)" class="tw-w-full tw-p-4">{{ page.label }}</p>
        </div>
      </div>
    </transition-group>
  </div>
</template>

<script>
import formBuilderService from '@/services/formbuilder';
import formBuilderMixin from '@/mixins/formbuilder';
import popover from '@/components/Popover.vue';
import { VueDraggableNext } from 'vue-draggable-next';
import Swal from "sweetalert2";

export default {
  name: 'FormBuilderPages',
  components: {
    draggable: VueDraggableNext,
    popover
  },
  props: {
    pages: {
      type: Array,
      required: true
    },
    selected: {
      type: Number,
      default: 0
    },
    profile_id: {
      type: Number,
      required: true
    }
  },
  mixins: [formBuilderMixin],
  data() {
    return {
      pageOptionsShown: 0,
    };
  },
  methods: {
    selectPage(id) {
      this.$emit('select-page', id);
    },
    deletePage(page) {
      if (this.pages.length > 2) {
        Swal.fire({
          title: this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE_CONFIRMATION') + page.label,
          text: this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE_CONFIRMATION_TEXT'),
          showCancelButton: true,
          confirmButtonText: this.translate('COM_EMUNDUS_ACTIONS_DELETE'),
          cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            cancelButton: 'em-swal-cancel-button',
            confirmButton: 'em-swal-delete-button',
          },
        }).then(result => {
          if (result.value) {
            formBuilderService.deletePage(page.id).then(response => {
							if (response.status) {
								let deletedPage = this.pages.findIndex(p => p.id === page.id);
								this.pages.splice(deletedPage, 1);
                this.$emit('delete-page', page.id);
								this.updateLastSave();
							}
            });
          }
        });
      } else {
        Swal.fire({
          title: this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE_ERROR'),
          text: this.translate('COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE_ERROR_TEXT'),
          type: 'error',
          showCancelButton: false,
          confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_OK'),
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            confirmButton: 'em-swal-confirm-button',
            actions: "em-swal-single-action",
          },
        });
      }
    },
    createModelFrom(page) {
      // @click="$emit('open-create-model', page.id)"
      this.$emit('open-create-model', page.id);
    },
    onDragEnd() {
      const newOrder = this.pages.map((page, index) => {
        return {rgt: index, link: page.link};
      });

      formBuilderService.reorderMenu(newOrder, this.$props.profile_id).then((response) => {
        if (response.status) {
          this.$emit('reorder-pages', this.pages);
        } else {
          Swal.fire({
            title: this.translate('COM_EMUNDUS_FORM_BUILDER_UPDATE_ORDER_PAGE_ERROR'),
            text: result.msg,
            type: 'error',
            showCancelButton: false,
            confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_OK'),
            reverseButtons: true,
            customClass: {
              title: 'em-swal-title',
              confirmButton: 'em-swal-confirm-button',
              actions: "em-swal-single-action",
            },
          });
        }
      });
    }
  },
  computed: {
    // return all pages but not submission page
    formPages() {
      return this.pages.length > 0 ? this.pages.filter((page) => {
        return page.type === 'form';
      }): [];
    },
    submissionPages() {
      return this.pages.length > 0 ? this.pages.filter((page) => {
        return page.type === 'submission';
      }) : [];
    }
  },
}
</script>

<style lang="scss">
#form-builder-pages {
  p {
    font-weight: 400;
    font-size: 14px;
    line-height: 18px;

    &:last-child {
      margin-bottom: 0 !important;
    }
  }

  .selected {
    background: #f8f8f8;

    p {
      font-weight: 600;
    }
  }

  #form-builder-pages-sections-list {
    list-style: none;
  }

  .save {
    &.already-saved {
      color: #20835f;
    }
  }
}
</style>
