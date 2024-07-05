<template>
  <div :id="'formBuilder'" class="tw-w-full tw-h-full">
    <modal
        :name="'formBuilder'"
        height="auto"
        transition="fade"
        :delay="100"
        :adaptive="true"
        :clickToClose="false"
    >
      <notifications
          group="foo-velocity"
          animation-type="velocity"
          :speed="500"
          position="bottom left"
          :classes="'vue-notification-custom'"
      />
      <div v-if="$store.state.global.currentLanguage !== $store.state.global.defaultLang" class="justify-center bg-[#FEF6EE] flex items-center gap-3 p-2">
        <span class="material-icons-outlined text-[#EF681F]">warning_amber</span>
        <span>{{ translate('COM_EMUNDUS_ONBOARD_FORMBUILDER_EDIT_DEFAULT_LANG') }}{{ defaultLangLabel }}</span>
      </div>
      <header class="tw-grid tw-grid-cols-3 tw-items-center">
        <div class="right-actions tw-flex tw-items-center tw-justify-start tw-gap-2">
          <span id="go-back"
                class="material-icons-outlined em-p-12-16 em-pointer"
                @click="clickGoBack">
            navigate_before
          </span>
          <p v-if="lastSave" id="saved-at" class="em-font-size-14 em-main-500-color">
            {{ translate("COM_EMUNDUS_FORM_BUILDER_SAVED_AT") }} {{ lastSave }}
          </p>
        </div>
        <span
            class="tw-text-sm	tw-font-semibold editable-data tw-text-center"
            contenteditable="true"
            ref="formTitle"
            @focusout="updateFormTitle"
            @keyup.enter="updateFormTitleKeyup"
        >
            {{ title }}
          </span>
        <div class="tw-flex tw-flex-col tw-items-end">
        <button class="em-primary-button tw-px-6 tw-py-3 tw-gap-3 em-w-auto" v-if="!previewForm && ['page','rules'].includes(showInSection)" @click="previewForm = true">
          <span class="tw-text-white material-icons-outlined">
            remove_red_eye
          </span>
          <label class="tw-mb-0" for="previewform">{{ translate('COM_EMUNDUS_FORMBUILDER_GO_TO_PREVIEW') }}</label>
        </button>
          <button class="em-primary-button tw-px-6 tw-py-3 tw-gap-3 em-w-auto" v-if="previewForm" @click="previewForm = false">
          <span class="tw-text-white material-icons-outlined">
            handyman
          </span>
            <label class="tw-mb-0" for="previewform">{{ translate('COM_EMUNDUS_FORMBUILDER_GO_BACK_FORMBUILDER') }}</label>
          </button>
        </div>
      </header>

      <div v-if="principalContainer === 'default'" class="body tw-flex tw-items-center tw-justify-between">
        <aside class="left-panel tw-flex tw-justify-start tw-h-full tw-relative" v-show="!previewForm">
          <div class="tabs tw-flex tw-flex-col tw-justify-start tw-h-full">
            <div class="tab" v-for="(tab,i) in displayedLeftPanels" :key="title + '_' + i"
                 :class="{ active: tab.active }" :title="tab.title">
              <span
                  class="material-icons-outlined tw-p-4"
                  @click="setSectionShown(tab.code)"
              >
                {{ tab.icon }}
              </span>
            </div>
          </div>
          <div v-if="!previewForm && leftPanelActiveTab !== 'Rules' && (activeTab==='' || activeTab==='Elements')"
               class="tab-content tw-justify-start tw-transition-all tw-duration-300" :class="minimizedLeft === true ? 'tw-max-w-0' : 'tw-max-w-md'"
               @mouseover="showMinimizedLeft = true"
               @mouseleave="showMinimizedLeft = false"
          >
            <transition name="slide-right" mode="out-in">
              <form-builder-elements v-if="leftPanelActiveTab === 'Elements'" @element-created="onElementCreated" :form="currentPage" @create-element-lastgroup="createElementLastGroup">
              </form-builder-elements>
              <form-builder-document-formats
                  v-else-if="leftPanelActiveTab === 'Documents'"
                  :profile_id="profile_id"
                  @open-create-document="onEditDocument"
              >
              </form-builder-document-formats>
              <form-builder-rules-list
                  v-else-if="leftPanelActiveTab === 'Rules' && this.showInSection !== 'rules-add'"
                  :form="currentPage"
                  @add-rule="addRule"
              />-->
              </transition>
          </div>
          <div v-if="activeTab==='' || activeTab==='Elements'" class="tw-w-[16px]"
               @mouseover="showMinimizedLeft = true"
               @mouseleave="showMinimizedLeft = false">
            <span class="material-icons-outlined tw-absolute tw-right-[-12px] tw-top-[14px] !tw-text-xl/5 tw-bg-neutral-400 tw-rounded-full tw-cursor-pointer"
                  :class="minimizedLeft ? 'tw-rotate-180' : ''"
                  v-show="showMinimizedLeft === true || minimizedLeft"
                  @click="handleSidebarSize('left')">chevron_left</span>
          </div>

        </aside>
        <section v-if="!previewForm && (activeTab==='' || activeTab==='Elements')" class="tw-flex tw-flex-col tw-w-full tw-h-full" id="center_content">
          <transition name="fade" mode="out-in">
            <form-builder-page
                ref="formBuilderPage"
                v-if="currentPage && showInSection === 'page'"
                :key="currentPage.id"
                :profile_id="parseInt(profile_id)"
                :page="currentPage"
                :mode="mode"
                @open-element-properties="onOpenElementProperties"
                @open-section-properties="onOpenSectionProperties"
                @open-create-model="onOpenCreateModel"
                @update-page-title="getPages(currentPage.id)"
            ></form-builder-page>
            <form-builder-document-list
                ref="formBuilderDocumentList"
                v-else-if="showInSection === 'documents'"
                :profile_id="parseInt(profile_id)"
                :campaign_id="parseInt(campaign_id)"
                @add-document="onOpenCreateDocument"
                @edit-document="onEditDocument"
                @delete-document="onDeleteDocument"
            ></form-builder-document-list>
            <form-builder-rules
                v-else-if="currentPage && showInSection === 'rules'"
                :key="currentPage.id"
                :page="currentPage"
                :mode="mode"
                @add-rule="addRule"
            />
            <form-builder-rules-add
                v-else-if="currentPage && showInSection === 'rules-add'"
                :key="currentPage.id"
                :page="currentPage"
                :mode="mode"
                :type="ruleType"
                :rule="currentRule"
                @close-rule-add="showInSection = 'rules';showInRightPanel = 'hierarchy';"
            />
            <translations :key="currentPage.id" v-if="currentPage && showInSection === 'translations'" :class="'tw-p-4'" :objectValue="'emundus_setup_profiles'" :dataValue="profile_id" :childrenValue="currentPage.id" />
          </transition>
        </section>

        <div v-if="previewForm" class="tw-w-full tw-h-full" style="background: #fafafb">
          <h2 style="padding: 1.5rem">{{ translate('COM_EMUNDUS_ONBOARD_PREVIEW') }}</h2>
          <iframe width="100%" height="100%" frameborder="0" style="min-height: 100vh;" id="preview_iframe" name="preview_iframe" :src="'/index.php?option=com_fabrik&view=form&formid='+selectedPage+'&tmpl=component&preview=1'" @load="loading = false" v-show="!loading"></iframe>
        </div>

        <transition name="slide-fade" mode="out-in">
          <aside v-if="rightPanel.tabs.includes(showInRightPanel) && activeTab==='' || activeTab==='Elements'" class="right-panel tw-h-full tw-flex tw-flex-col tw-relative"
                 @mouseover="showMinimizedRight = true"
                 @mouseleave="showMinimizedRight = false"
          >
            <div class="tw-w-[16px] !tw-h-0"
                 @mouseover="showMinimizedRight = true"
                 @mouseleave="showMinimizedRight = false">
            <span class="material-icons-outlined tw-absolute tw-left-[-12px] tw-top-[14px] !tw-text-xl/5 tw-bg-neutral-400 tw-rounded-full tw-cursor-pointer"
                  :class="minimizedRight ? 'tw-rotate-180' : ''"
                  v-show="showMinimizedRight === true || minimizedRight"
                  @click="handleSidebarSize('right')">chevron_right</span>
            </div>
            <transition name="fade" mode="out-in">
              <div :class="minimizedRight === true ? 'tw-max-w-0' : 'tw-max-w-md tw-min-w-[22rem]'" class="tw-transition-all tw-duration-300">
                <div id="form-hierarchy" v-if="showInRightPanel === 'hierarchy' && rightPanel.tabs.includes('hierarchy')">
                  <form-builder-pages
                      :pages="pages"
                      :selected="parseInt(selectedPage)"
                      :profile_id="parseInt(profile_id)"
                      @select-page="selectPage($event)"
                      @add-page="getPages(currentPage.id)"
                      @delete-page="selectedPage = pages[0].id;"
                      @open-page-create="principalContainer = 'create-page';"
                      @reorder-pages="onReorderedPages"
                      @open-create-model="onOpenCreateModel"
                  ></form-builder-pages>
                  <hr>
                  <form-builder-documents
                      v-if="!previewForm && leftPanelActiveTab !== 'Rules'"
                      ref="formBuilderDocuments"
                      :profile_id="parseInt(profile_id)"
                      :campaign_id="parseInt(campaign_id)"
                      @show-documents="setSectionShown('documents')"
                      @open-create-document="onOpenCreateDocument"
                  ></form-builder-documents>
                </div>
                <form-builder-element-properties
                    v-if="showInRightPanel === 'element-properties'"
                    @close="onCloseElementProperties"
                    :element="selectedElement"
                    :profile_id="parseInt(profile_id)"
                ></form-builder-element-properties>
                <form-builder-section-properties
                    v-if="showInRightPanel === 'section-properties'"
                    @close="onCloseSectionProperties"
                    :section_id="selectedSection.group_id"
                    :profile_id="parseInt(profile_id)"
                ></form-builder-section-properties>
                <form-builder-create-model
                    v-if="showInRightPanel === 'create-model'"
                    :page="selectedPage"
                    @close="showInRightPanel = 'hierarchy';"
                ></form-builder-create-model>
                <form-builder-create-document
                    v-if="showInRightPanel === 'create-document' && rightPanel.tabs.includes('create-document')"
                    ref="formBuilderCreateDocument"
                    :key="formBuilderCreateDocumentKey"
                    :profile_id="parseInt(profile_id)"
                    :current_document="selectedDocument ? selectedDocument : null"
                    :mandatory="createDocumentMandatory"
                    :mode="createDocumentMode"
                    @close="showInRightPanel = 'hierarchy'"
                    @documents-updated="onUpdateDocument"
                ></form-builder-create-document>
              </div>
            </transition>
          </aside>
        </transition>
      </div>
      <div v-else-if="principalContainer === 'create-page'">
        <form-builder-create-page :profile_id="parseInt(profile_id)"
                                  @close="onCloseCreatePage"></form-builder-create-page>
      </div>
    </modal>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
// components
import FormBuilderElements from "../components/FormBuilder/FormBuilderElements";
import FormBuilderElementProperties from "../components/FormBuilder/FormBuilderElementProperties";
import FormBuilderSectionProperties from "../components/FormBuilder/FormBuilderSectionProperties";
import FormBuilderPage from "../components/FormBuilder/FormBuilderPage";
import FormBuilderCreatePage from "../components/FormBuilder/FormBuilderCreatePage";
import FormBuilderPages from "../components/FormBuilder/FormBuilderPages";
import FormBuilderDocuments from "../components/FormBuilder/FormBuilderDocuments";
import FormBuilderDocumentList from "../components/FormBuilder/FormBuilderDocumentList";
import FormBuilderCreateDocument from "../components/FormBuilder/FormBuilderCreateDocument";
import FormBuilderDocumentFormats from "../components/FormBuilder/FormBuilderDocumentFormats";
import FormBuilderRules from "../components/FormBuilder/FormBuilderRules/FormBuilderRules";
import FormBuilderRulesList from "../components/FormBuilder/FormBuilderRules/FormBuilderRulesList.vue";
import FormBuilderRulesAdd from "@/components/FormBuilder/FormBuilderRules/FormBuilderRulesAdd.vue";

// services
import formService from '../services/form.js';
import FormBuilderCreateModel from "../components/FormBuilder/FormBuilderCreateModel";
import formBuilderService from "@/services/formbuilder";

// mixins
import formBuilderMixin from '../mixins/formbuilder';
import Translations from "@/components/Settings/TranslationTool/Translations.vue";

export default {
  name: 'FormBuilder',
  components: {
    Translations,
    FormBuilderCreateModel,
    FormBuilderSectionProperties,
    FormBuilderCreatePage,
    FormBuilderElements,
    FormBuilderElementProperties,
    FormBuilderPage,
    FormBuilderPages,
    FormBuilderDocuments,
    FormBuilderDocumentList,
    FormBuilderCreateDocument,
    FormBuilderDocumentFormats,
    FormBuilderRulesAdd,
    FormBuilderRulesList,
    FormBuilderRules,
  },
  mixins: [formBuilderMixin],
  data() {
    return {
      mode: 'forms',
      profile_id: 0,
      form_id: 0,
      campaign_id: 0,
      title: '',
      pages: [],
      principalContainer: 'default',
      showInSection: 'page',
      selectedPage: 0,
      selectedSection: null,
      selectedElement: null,
      optionsSelectedElement: false,
      selectedDocument: null,
      rightPanel: {
        tabs: [
          'hierarchy',
          'element-properties',
          'section-properties',
          'create-model',
          'create-document',
        ]
      },
      showInRightPanel: 'hierarchy',
      createDocumentMandatory: '1',
      lastSave: null,
      leftPanel: {
        tabs: [
          {
            title: 'Elements',
            code: 'page',
            icon: 'edit_note',
            active: true,
            displayed: true
          },
          {
            title: 'Documents',
            code: 'documents',
            icon: 'attach_file',
            active: false,
            displayed: true
          },
          {
            title: 'Translations',
            code: 'translations',
            icon: 'translate',
            active: false,
            displayed: true,
            url: '/parametres-globaux?layout=translation&default_menu=2&object=emundus_setup_profiles'
          },
          {
            title: 'Rules',
            code: 'rules',
            icon: 'alt_route',
            active: false,
            displayed: false
          }
        ],
      },
      formBuilderCreateDocumentKey: 0,
      createDocumentMode: 'create',
      activeTab:'',
      minimizedLeft: false,
      showMinimizedLeft: false,
      minimizedRight: false,
      showMinimizedRight: false,

      showConditionBuilder: false,
      currentRule: null,
      ruleType: 'js',

      previewForm: false,
      loading: false
    }
  },
  created() {
    const data = this.$store.getters['global/datas'];
    if (parseInt(this.$store.state.global.manyLanguages) === 0) {
      this.leftPanel.tabs[2].displayed = false;
    }
    this.profile_id = data.prid.value;
    this.campaign_id = data.cid.value;

    if (data && data.settingsmenualias && data.settingsmenualias.value) {
      this.leftPanel.tabs[2].url = '/' + data.settingsmenualias.value;
    }

    if(data && data.enableconditionbuilder && data.enableconditionbuilder.value == 1) {
      this.leftPanel.tabs[3].displayed = true;
    }

    if (data && data.mode && data.mode.value) {
      this.mode = data.mode.value;

      if (this.mode === 'eval' || this.mode == 'models') {
        this.rightPanel.tabs = this.rightPanel.tabs.filter(tab => tab !== 'hierarchy' && tab !== 'create-document');
        this.leftPanel.tabs = this.leftPanel.tabs.filter(tab => tab.title != 'Documents');
        this.form_id = this.profile_id;
        this.profile_id = 0;
      }
    }

    this.getFormTitle();
    this.getPages();
  },
  mounted() {
    this.$modal.show('formBuilder');
  },
  methods: {
    getFormTitle() {
      if (this.profile_id) {
        formService.getProfileLabelByProfileId(this.profile_id).then(response => {
          if (response.status !== false) {
            this.title = response.data.data.label;
          }
        });
      }
    },
    updateFormTitle() {
      this.title = this.$refs.formTitle.innerText.trim().replace(/[\r\n]/gm, " ");
      this.$refs.formTitle.innerText = this.$refs.formTitle.innerText.trim().replace(/[\r\n]/gm, " ");
      formService.updateFormLabel({label: this.title, prid: this.profile_id, form_id: this.form_id});
    },
    updateFormTitleKeyup() {
      document.activeElement.blur();
    },
    getPages(page_id = 0) {
      if (this.profile_id) {
        formService.getFormsByProfileId(this.profile_id).then(response => {
          this.pages = response.data.data;

          if (page_id === 0) {
            this.selectPage(this.pages[0].id);
          } else {
            this.selectPage(String(page_id));
          }
          this.principalContainer = 'default';

          formService.getSubmissionPage(this.profile_id).then(response => {
            const formId = response.data.link.match(/formid=(\d+)/)[1];
            if (formId) {
              // check if the form is already in the pages
              const page = this.pages.find(page => page.id === formId);
              if (!page) {
                this.pages.push({
                  id: formId,
                  label: this.translate('COM_EMUNDUS_FORM_BUILDER_SUBMISSION_PAGE'),
                  type: 'submission',
                  elements: [],
                });
              }
            }
          });
        });
      } else if (this.form_id) {
        formService.getFormByFabrikId(this.form_id).then(response => {
          this.title = response.data.data.label;
          this.pages = [response.data.data];
          this.selectPage(this.pages[0].id);
          this.principalContainer = 'default';
        });
      }
    },
    onReorderedPages(reorderedPages) {
      this.pages = reorderedPages;
    },
    onElementCreated(eltid,scrollTo) {
      this.$refs.formBuilderPage.getSections(eltid,scrollTo);
    },
    createElementLastGroup(element) {
      const groups = Object.values(this.$refs.formBuilderPage.fabrikPage.Groups);
      const last_group = groups[groups.length - 1].group_id;

      formBuilderService.createSimpleElement({
        gid: last_group,
        plugin: element.value,
        mode: this.mode
      }).then(response => {
        if (response.status && response.data > 0) {
          this.onElementCreated(response.data,true);
          this.updateLastSave();
          this.loading = false;
        } else {
          this.displayError(response.msg);
          this.loading = false;
        }
      }).catch((error) => {
        console.warn(error);
        this.loading = false;
      });
    },
    onDocumentCreated() {
      this.$refs.formBuilderDocuments.getDocuments();
      this.$refs.formBuilderDocumentList.getDocuments();
    },
    onOpenSectionProperties(event) {
      this.selectedSection = event;
      this.showInRightPanel = 'section-properties';
    },
    onOpenElementProperties(event) {
      this.selectedElement = event;
      if (this.selectedElement.plugin === 'dropdown') {
        this.optionsSelectedElement = true;
      } else {
        if (this.optionsSelectedElement === true) {
          this.$refs.formBuilderPage.getSections();
        }
        this.optionsSelectedElement = false;
      }
      this.showInRightPanel = 'element-properties';
    },
    onUpdateDocument() {
      this.$refs.formBuilderDocumentList.getDocuments();
      this.showInRightPanel = 'hierarchy';
    },
    onCloseElementProperties() {
      this.selectedElement = null;
      this.showInRightPanel = 'hierarchy';
      this.$refs.formBuilderPage.getSections();
    },
    onCloseSectionProperties() {
      this.selectedSection = null;
      this.showInRightPanel = 'hierarchy';
      this.$refs.formBuilderPage.getSections();
    },
    onCloseCreatePage(response) {
      if (response.reload) {
        this.getPages(response.newSelected);
      } else {
        this.principalContainer = 'default';
      }
    },
    onOpenCreateModel(pageId) {
      if (pageId > 0) {
        this.selectedPage = pageId;
        this.showInRightPanel = 'create-model';
      } else {
        console.error('No page id provided');
      }
    },
    onOpenCreateDocument(mandatory = '1') {
      this.selectedDocument = null;
      this.createDocumentMandatory = mandatory;
      this.createDocumentMode = 'create';
      this.formBuilderCreateDocumentKey++;
      this.showInRightPanel = 'create-document';
      this.setSectionShown('documents');
    },
    onEditDocument(document) {
      this.selectedDocument = document;
      this.createDocumentMode = 'update';
      this.createDocumentMandatory = document.mandatory;
      this.formBuilderCreateDocumentKey++;
      this.showInRightPanel = 'create-document';
      this.setSectionShown('documents');
    },
    onDeleteDocument() {
      this.selectedDocument = null;
      this.showInRightPanel = 'hierarchy';
      this.setSectionShown('documents');
    },
    selectTab(section) {
      this.leftPanel.tabs.forEach((tab) => {
        tab.active = tab.code === section;
      });
    },
    selectPage(page_id) {
      this.selectedPage = page_id;
      if(this.showInSection === 'documents') {
        this.setSectionShown('page');
      }
      this.setSectionShown(this.showInSection);
    },
    setSectionShown(section) {
      if(section == 'rules-add') {
        this.selectTab('rules')
      } else {
        this.selectTab(section)
      }

      if (section === 'documents') {
        this.selectedPage = null;
      } else if(this.selectedPage == null) {
        this.selectedPage = this.pages[0].id;
      }
      this.showInSection = section;
    },
    goTo(url, blank = false) {
      const baseUrl = window.location.origin;

      if (blank) {
        window.open(baseUrl + url, '_blank');
      } else {
        window.location.href = baseUrl + url;
      }
    },
    clickGoBack() {
      if(this.previewForm) {
        this.previewForm = !this.previewForm;
      } else {
        if (this.principalContainer === 'create-page') {
          this.onCloseCreatePage({reload: false});
        } else {
          window.history.go(-1);
        }
      }
    },
    addRule(rule_type, rule = null) {
      this.ruleType = rule_type;
      this.currentRule = rule;
      if(rule !== null) {
        this.showInRightPanel = null;
      }
      this.showInSection = 'rules-add';
    },
    clickTab(tab) {
      //todo display the composant translation
      this.activeTab = tab.code;

    },
    handleSidebarSize(position = 'left') {
      if (position === 'left') {
        this.minimizedLeft = !this.minimizedLeft;
      } else {
        this.minimizedRight = !this.minimizedRight;
      }
    },
  },
  computed: {
    currentPage() {
      return this.pages.find(page => page.id === this.selectedPage);
    },
    leftPanelActiveTab() {
      let find = this.leftPanel.tabs.find(tab => tab.active);
      if (find) {
        return find.title;
      } else {
        this.leftPanel.tabs[0].active = true;
        return this.leftPanel.tabs[0].title;
      }
    },
    displayedLeftPanels() {
      return this.leftPanel.tabs.filter((tab) => {
        return tab.displayed;
      });
    },
    defaultLangLabel() {
      let label = 'Français';

      switch (this.$store.state.global.defaultLang) {
        case 'en-GB':
          label = 'English';
          break;
        case 'pt-PT':
          label = 'Português';
      }

      return label;
    }
  },
  watch: {
    "$store.state.formBuilder.lastSave": {
      handler(newValue) {
        this.lastSave = newValue;
      },
      deep: true
    },
    previewForm(newValue) {
      this.loading = true;
      if (newValue) {
        setTimeout(() => {
          var myIframe = document.getElementById('preview_iframe');
          myIframe.addEventListener("load", () => {
            var cssLink = document.createElement("link");
            cssLink.href = "media/com_fabrik/css/fabrik.css";
            cssLink.rel = "stylesheet";
            cssLink.type = "text/css";
            frames['preview_iframe'].document.head.appendChild(cssLink);

            var css = '<style type="text/css">' +
                '.fabrikActions{display:none}; ' +
                '</style>';
            frames['preview_iframe'].document.head.insertAdjacentHTML('beforeend', css);

            this.loading = false;
          });
        }, 500);
      } else {
        this.selectTab(this.showInSection);
        this.loading = false;
      }
    }
  }
}
</script>

<style lang="scss">
#formBuilder {
  background: white;

  ul {
    list-style-position: inside;
  }

  header {
    box-shadow: inset 0px -1px 0px #E3E5E8;

    button {
      margin: 8px 16px;
      height: 32px;
      &:hover {
        span {
          color: var(--em-profile-color) !important;
        }
      }
    }

    #saved-at {
      white-space: nowrap;
    }
  }

  .body {
    height: calc(100% - 48px);

    aside, section {
      justify-content: flex-start;
    }

    aside {
      transition: all .3s;
    }

    section {
      overflow-y: auto;
      background: #f8f8f8;
      scroll-behavior: smooth;
    }

    .right-panel {
      border-left: solid 1px #E3E5E8;

      > div {
        height: 100%;
        overflow: auto;
      }
    }

    .left-panel {
      padding: 0;
      border-right: solid 1px #E3E5E8;
      align-self: flex-start;

      .tabs {
        align-self: flex-start;
        align-items: flex-start;
        border-right: solid 1px #E3E5E8;

        .tab {
          cursor: pointer;

          &.active {
            background-color: var(--em-profile-color);
            span {
              color: white !important;
            }
          }

          .material-icons, .material-icons-outlined {
            font-size: 22px !important;
          }
        }
      }

      .tab-content {
        align-items: flex-start;
        height: 100%;
        overflow: auto;

        #form-builder-elements,#form-builder-document-formats {
          padding: 0 0 0 16px;
        }
      }
    }

    .form-builder-title {
      font-weight: 700;
      font-size: 16px;
      line-height: 19px;
      letter-spacing: 0.0015em;
      color: #080C12;
    }
  }

  .editable-data {
    padding: 4px 8px !important;
    border-radius: 4px;
    margin-bottom: 0;

    &:focus {
      background-color: #DFF5E9;
    }
  }

  input.editable-data {
    border: none !important;

    &:focus {
      background-color: #DFF5E9;
    }
  }
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.fabrikActions {
  display: none;
}
</style>
