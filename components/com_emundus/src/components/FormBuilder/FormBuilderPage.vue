<template>
  <div id="form-builder-page">
    <div class="tw-flex tw-items-center tw-justify-between">
      <span
          class="tw-text-2xl tw-font-semibold editable-data"
          id="page-title"
          ref="pageTitle"
          @focusout="updateTitle"
          @keyup.enter="updateTitleKeyup"
          @keydown="(event) => checkMaxMinlength(event, 50, 0)"
          contenteditable="true"
          :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_ADD_PAGE_TITLE_ADD')"
          v-html="translate(title)"></span>
      <button id="add-page-modele" class="em-secondary-button !tw-w-auto"  @click="$emit('open-create-model', page.id)">
        <span class="material-icons-outlined tw-cursor-pointer"
              v-if="mode === 'forms'"
              :title="translate('COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_TITLE')"
        >post_add</span>
        {{ translate('COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_TITLE') }}
      </button>
    </div>
    <span class="description editable-data"
          id="pageDescription"
          ref="pageDescription"
          v-html="description"
          @focusout="updateDescription"
          contenteditable="true"
          :placeholder="translate('COM_EMUNDUS_FORM_BUILDER_ADD_PAGE_INTRO_ADD')"></span>

    <div class="form-builder-page-sections tw-mt-2">
      <button v-if="sections.length > 0" id="add-section" class="em-primary-button tw-px-6 tw-py-3" @click="addSection()">
        {{ translate('COM_EMUNDUS_FORM_BUILDER_ADD_SECTION') }}
      </button>
      <form-builder-page-section
          v-for="(section, index) in sections"
          :key="section.group_id"
          :profile_id="parseInt(profile_id)"
          :page_id="parseInt(page.id)"
          :section="section"
          :index="index+1"
          :totalSections="sections.length"
          :ref="'section-'+section.group_id"
          @open-element-properties="$emit('open-element-properties', $event)"
          @move-element="updateElementsOrder"
          @delete-section="deleteSection"
          @update-element="getSections"
          @move-section="moveSection"
          @open-section-properties="$emit('open-section-properties', section)"
      >
      </form-builder-page-section>
    </div>
    <button id="add-section" class="em-primary-button tw-px-6 tw-py-3" @click="addSection()">
      {{ translate('COM_EMUNDUS_FORM_BUILDER_ADD_SECTION') }}
    </button>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
import formService from '@/services/form.js';
import formBuilderService from '@/services/formbuilder.js';
import translationService from '@/services/translations.js';

import FormBuilderPageSection from '@/components/FormBuilder/FormBuilderPageSection.vue';
import formBuilderMixin from '@/mixins/formbuilder.js';
import globalMixin from '@/mixins/mixin.js';
import errorMixin from '@/mixins/errors.js';

export default {
  components: {
    FormBuilderPageSection
  },
  props: {
    profile_id: {
      type: Number,
      default: 0
    },
    page: {
      type: Object,
      default: () => {}
    },
    mode: {
      type: String,
      default: 'forms'
    }
  },
  mixins: [formBuilderMixin, globalMixin, errorMixin],
  data() {
    return {
      fabrikPage: {},
      title: 'COM_EMUNDUS_FORM_BUILDER_NEW_PAGE',
      description: '',
      sections: [],

      loading: false,
    };
  },
  mounted() {
    if (this.page.id) {
      this.title = this.page.label;
      this.getSections();
    }
  },
  methods: {
    getSections(eltid = null, scrollTo = false) {
      this.loading = true;
      formService.getPageObject(this.page.id).then(response => {
        if (response.status && response.data !== '') {
          this.fabrikPage = response.data;
          this.title = this.fabrikPage.show_title.label[this.shortDefaultLang];
          const groups = Object.values(response.data.Groups);
          this.sections = groups.filter(group => group.hidden_group != -1);
          this.getDescription();
          if(eltid) {
            setTimeout(() => {
              if(scrollTo) {
                document.getElementById('center_content').scrollTo(0, document.getElementById('center_content').scrollHeight);
              }
              document.getElementById('element_' + eltid).style.backgroundColor = 'var(--main-50)';
              document.getElementById('element_' + eltid).style.borderColor = 'var(--main-400)';
              document.getElementById('element-label-'+eltid).focus();
              setTimeout(() => {
                document.getElementById('element_' + eltid).style.backgroundColor = 'inherit';
                document.getElementById('element_' + eltid).style.borderColor = '';
              }, 1500)
            }, 300)
          }
        } else {
          this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
        }

        this.loading = false;
      });
    },
    getDescription() {
      if (this.fabrikPage.intro_raw) {
        formBuilderService.getAllTranslations(this.fabrikPage.intro_raw).then(response => {
          if (response.status && response.data) {
            if (response.data[this.shortDefaultLang] !== '') {
              let strippedString = response.data[this.shortDefaultLang].replace(/(<([^>]+)>)/gi, "");

              if (strippedString.length > 0) {
                this.description = response.data[this.shortDefaultLang];
              }
            }
          }
        });
      } else {
        this.fabrikPage.intro_raw = 'FORM_' + this.profile_id + '_INTRO_' + this.fabrikPage.id;
        this.fabrikPage.intro = {};
      }
    },
    addSection() {
      if (this.sections.length < 10) {
        formBuilderService.createSimpleGroup(this.page.id, {
          fr: 'Nouvelle section',
          en: 'New section'
        }, this.mode).then(response => {
          if (response.status) {
            this.getSections();
            this.updateLastSave();
          } else {
            this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_CREATE_SECTION_ERROR'), this.translate(response.msg));
          }
        }).catch(error => {
          this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_CREATE_SECTION_ERROR'), error);
        });
      } else {
        this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_MAX_SECTION_TITLE'), this.translate('COM_EMUNDUS_FORM_BUILDER_MAX_SECTION_TEXT'))
      }
    },
    moveSection(sectionId, direction) {
      let sectionsInOrder = this.sections.map((section, index) => {
        return {
          id: section.group_id,
          order: index
        };
      });

      const index = sectionsInOrder.findIndex(section => sectionId === section.id);
      const sectionToMove = sectionsInOrder[index].id;
      if (direction === 'up') {
        if (index > 0) {
          sectionsInOrder[index].id = sectionsInOrder[index - 1].id;
          sectionsInOrder[index - 1].id = sectionToMove;
        }
      } else {
        if (index < sectionsInOrder.length - 1) {
          sectionsInOrder[index].id = sectionsInOrder[index + 1].id;
          sectionsInOrder[index + 1].id = sectionToMove;
        }
      }

      formBuilderService.reorderSections(this.page.id, sectionsInOrder);

      const oldOrderSections = this.sections;
      let newOrderSections = [];
      sectionsInOrder.forEach(section => {
        newOrderSections.push(oldOrderSections.find(oldSection => oldSection.group_id === section.id));
      });
      this.sections = newOrderSections;
    },
    updateTitle() {
      this.fabrikPage.show_title.label[this.shortDefaultLang] = this.$refs.pageTitle.innerText.trim().replace(/[\r\n]/gm, "");
      this.$refs.pageTitle.innerText = this.$refs.pageTitle.innerText.trim().replace(/[\r\n]/gm, "");

      formBuilderService.updateTranslation(null, this.fabrikPage.show_title.titleraw, this.fabrikPage.show_title.label).then(response => {
        if (response.data.status) {
          translationService.updateTranslations(this.fabrikPage.show_title.label[this.shortDefaultLang], 'falang', this.shortDefaultLang, this.fabrikPage.menu_id, 'title', 'menu');
          console.log('emit update title')
          this.$emit('update-page-title', {
            page: this.page.id,
            new_title: this.$refs.pageTitle.innerText
          });
          this.updateLastSave();
        }
      });
    },
    updateTitleKeyup() {
      document.activeElement.blur();
    },
    updateDescription() {
      this.fabrikPage.intro[this.shortDefaultLang] = this.$refs.pageDescription.innerText.replace(/[\r\n]/gm, "<br/>");

      formBuilderService.updateTranslation(null, this.fabrikPage.intro_raw, this.fabrikPage.intro).then((response) => {
        if (response.data.status) {
          this.updateLastSave();
          this.fabrikPage.intro_raw = response.data.data;
        }

        if (this.$refs.pageDescription.innerText === '') {
          document.getElementById('pageDescription').textContent = this.translate('COM_EMUNDUS_FORM_BUILDER_ADD_PAGE_INTRO_ADD');
          document.getElementById('pageDescription').classList.add('em-text-neutral-600');
        }
      });
    },
    updateElementsOrder(event, fromGroup, toGroup) {
      let updated = false;

      if (fromGroup > 0 && toGroup > 0 && fromGroup != toGroup) {
        const sectionFrom = this.sections.find(section => section.group_id === fromGroup);
        const fromElements = Object.values(sectionFrom.elements);
        const movedElement = fromElements[event.oldIndex];

        if (movedElement !== undefined && movedElement !== null && movedElement.id) {
          const foundElement = this.$refs['section-' + toGroup][0].elements.find(element => element.id === movedElement.id);

          if (foundElement === undefined || foundElement === null) {
            this.$refs['section-' + toGroup][0].elements.splice(event.newIndex, 0, movedElement);
          }

          const toElements = this.$refs['section-' + toGroup][0].elements.map((element, index) => {
            return {id: element.id, order: index + 1};
          });
          formBuilderService.updateOrder(toElements, toGroup, movedElement).then((response) => {
            updated = response.data.status;

            if (!updated) {
              this.displayError('COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED', '');
            }
          });
          this.updateLastSave();
        } else {
          this.displayError('COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED', '');
        }
      } else {
        this.displayError('COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED', '');
      }
    },
    deleteSection(sectionId) {
      this.sections = this.sections.filter(section => section.group_id !== sectionId);
      this.updateLastSave();
    }
  },
}
</script>

<style lang="scss">
#form-builder-page {
  width: calc(100% - 80px);
  margin: 40px 40px;

  .description {
    display: block;
  }

  #add-section {
    width: fit-content;
    margin: auto;
  }
}
</style>
