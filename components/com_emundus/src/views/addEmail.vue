<template>
  <div class="tw-border tw-border-neutral-300 em-card-shadow tw-rounded tw-bg-white tw-p-6">
    <div>
      <form @submit.prevent="submit" class="fabrikForm emundus-form">
        <div>
          <div>
            <div
                class="tw-flex tw-items-center tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300"
                @click="redirectJRoute('index.php?option=com_emundus&view=campaigns')">
              <span class="material-symbols-outlined tw-text-neutral-600">navigate_before</span>
              <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
            </div>

            <div class="tw-mt-4">
              <h1>{{ translate('COM_EMUNDUS_ONBOARD_ADD_EMAIL') }}</h1>
              <div class="tw-mt-2">
                <p class="tw-text-red-600 tw-mt-1">{{ translate('COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE') }}</p>
              </div>
            </div>
          </div>

          <hr class="tw-mt-1.5 tw-mb-4"/>

          <div class="tw-flex tw-flex-col tw-gap-4">
            <div>
              <label class="tw-font-medium">
                {{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_NAME') }}
                <span class="tw-text-red-600">*</span>
              </label>
              <input
                  type="text"
                  class="tw-w-full tw-mt-1"
                  v-model="form.subject"
                  :class="{ 'is-invalid': errors.subject }"
              />
              <div v-if="errors.subject" class="tw-text-red-600 tw-mb-1 tw-mt-1">
                <span class="tw-text-red-600">{{ translate('COM_EMUNDUS_ONBOARD_SUBJECT_REQUIRED') }}</span>
              </div>
            </div>

            <div>
              <label
                  class="tw-font-medium"
              >{{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_BODY') }}
                <span class="tw-text-red-600">*</span></label
              >
              <tip-tap-editor
                  v-if="editor_ready"
                  v-model="form.message"
                  :upload-url="'/index.php?option=com_emundus&controller=settings&task=uploadmedia'"
                  :delete-media-url="'/index.php?option=com_emundus&controller=settings&task=deletemedia'"
                  :editor-content-height="'30em'"
                  :class="'tw-mt-1'"
                  :locale="'fr'"
                  :preset="'custom'"
                  :plugins="editorPlugins"
                  :toolbar-classes="['tw-bg-white']"
                  :editor-content-classes="['tw-bg-white']"
                  :suggestions="suggestions"
                  :media-files="medias"
                  @uploadedImage="getMedia"
              />
              <div class="tw-mt-1">
                <a
                    href="/export-tags"
                    class="em-main-500-color em-hover-main-600 em-text-underline"
                    target="_blank"
                >{{ translate('COM_EMUNDUS_EMAIL_SHOW_TAGS') }}</a
                >
              </div>
              <div v-if="errors.message" class="tw-text-red-600 tw-mb-1">
                <span class="tw-text-red-600">{{ translate('COM_EMUNDUS_ONBOARD_BODY_REQUIRED') }}</span>
              </div>
            </div>

            <div>
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_CHOOSECATEGORY') }}</label>
              <incremental-select
                  :options="this.categoriesList"
                  :defaultValue="incSelectDefaultValue"
                  :locked="mode != 'create'"
                  :key="categories.length"
                  @update-value="updateCategorySelectedValue"
              >
              </incremental-select>
            </div>
          </div>
        </div>

        <hr class="tw-mt-1.5 tw-mb-4" />

        <div class="em-container-accordeon tw-shadow">
          <div class="tw-flex tw-items-center tw-gap-1">
            <h2 class="tw-cursor-pointer tw-w-full" @click="displayAdvanced">
              {{ translate('COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING') }}
            </h2>

            <button
                :title="translate('COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING')"
                type="button"
                class="tw-bg-transparent tw-flex tw-flex-col"
                @click="displayAdvanced"
                v-show="!displayAdvancedParameters"
            >
              <span class="material-symbols-outlined em-main-500-color">add_circle_outline</span>
            </button>
            <button
                :title="translate('COM_EMUNDUS_ONBOARD_ADVANCED_CUSTOMING')"
                type="button"
                @click="displayAdvanced"
                class="tw-bg-transparent tw-flex tw-flex-col"
                v-show="displayAdvancedParameters"
            >
              <span class="material-symbols-outlined em-main-500-color">remove_circle_outline</span>
            </button>
          </div>

          <div id="email-advanced-parameters" class="tw-mt-4 tw-pl-4 em-border-left-main-500 tw-flex tw-flex-col tw-gap-4" v-if="displayAdvancedParameters">

            <div>
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_SENDER_EMAIL') }}</label>
              <p class="tw-mt-1 tw-text-neutral-700">{{ email_sender }}</p>
            </div>

            <div>
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_RECEIVER') }}</label>
              <input type="text" class="tw-w-full fabrikinput tw-mt-1" v-model="form.name"/>
            </div>

            <div>
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESS') }}</label>
              <input
                  type="text"
                  class="tw-w-full fabrikinput tw-mt-1"
                  v-model="form.emailfrom"
                  placeholder="reply-to@tchooz.io"
              />
              <p class="tw-text-xs tw-text-neutral-700 tw-mt-1">
                {{ translate('COM_EMUNDUS_ONBOARD_ADDEMAIL_ADDRESTIP') }}
              </p>
            </div>

            <div id="receivers_cc">
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_RECEIVER_CC_TAGS') }}</label>
              <multiselect
                  :class="'tw-mt-1'"
                  v-model="selectedReceiversCC"
                  label="email"
                  track-by="email"
                  :options="receivers_cc"
                  :multiple="true"
                  :searchable="true"
                  :taggable="true"
                  :placeholder="translate('PLEASE_SELECT')"
                  select-label=""
                  selected-label=""
                  deselect-label=""
                  @tag="addNewCC"
                  :close-on-select="false"
                  :clear-on-select="false"
              ></multiselect>
            </div>

            <!-- Email -- BCC (in form of email adress or fabrik element -->
            <div id="receivers_bcc">
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_RECEIVER_BCC_TAGS') }}</label>
              <multiselect
                  :class="'tw-mt-1'"
                  v-model="selectedReceiversBCC"
                  label="email"
                  track-by="email"
                  :options="receivers_bcc"
                  :multiple="true"
                  :searchable="true"
                  :taggable="true"
                  :placeholder="translate('PLEASE_SELECT')"
                  select-label=""
                  selected-label=""
                  deselect-label=""
                  @tag="addNewBCC"
                  :close-on-select="false"
                  :clear-on-select="false"
              >
              </multiselect>
            </div>

            <!-- Email -- Associated letters (in form of email adress or fabrik element -->
            <div id="attached_letters" v-if="attached_letters">
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_EMAIL_DOCUMENT') }}</label>
              <multiselect
                  :class="'tw-mt-1'"
                  v-model="selectedLetterAttachments"
                  label="value"
                  track-by="id"
                  :options="attached_letters"
                  :multiple="true"
                  :taggable="true"
                  select-label=""
                  selected-label=""
                  deselect-label=""
                  :placeholder="translate('COM_EMUNDUS_ONBOARD_PLACEHOLDER_EMAIL_DOCUMENT')"
                  :close-on-select="false"
                  :clear-on-select="false"
              ></multiselect>
            </div>

            <!-- Email -- Action tags -->
            <div v-if="tags">
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_EMAIL_TAGS') }}</label>
              <multiselect
                  :class="'tw-mt-1'"
                  v-model="selectedTags"
                  label="label"
                  track-by="id"
                  :options="action_tags"
                  :multiple="true"
                  :taggable="true"
                  select-label=""
                  selected-label=""
                  deselect-label=""
                  :placeholder="translate('COM_EMUNDUS_ONBOARD_PLACEHOLDER_EMAIL_TAGS')"
                  :close-on-select="false"
                  :clear-on-select="false"
              ></multiselect>
            </div>

            <!-- Email -- Candidat attachments -->
            <div>
              <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_CANDIDAT_ATTACHMENTS') }}</label>
              <multiselect
                  :class="'tw-mt-1'"
                  v-model="selectedCandidateAttachments"
                  label="value"
                  track-by="id"
                  :options="candidate_attachments"
                  :multiple="true"
                  :taggable="true"
                  select-label=""
                  selected-label=""
                  deselect-label=""
                  :placeholder="translate('COM_EMUNDUS_ONBOARD_PLACEHOLDER_CANDIDAT_ATTACHMENTS')"
                  :close-on-select="false"
                  :clear-on-select="false"
              ></multiselect>
            </div>
          </div>
        </div>

        <hr class="tw-mt-1.5 tw-mb-4" />

        <div class="tw-flex tw-justify-end">
          <button type="submit" class="tw-btn-primary !tw-w-auto">
            {{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTINUER') }}
          </button>
        </div>
      </form>
    </div>

    <div class="em-page-loader" v-if="loading || submitted"></div>
  </div>
</template>

<script>
import Multiselect from 'vue-multiselect'
import IncrementalSelect from '@/components/IncrementalSelect.vue'
import settingsService from '@/services/settings.js'
import emailService from '@/services/email.js'
import messagesService from '@/services/messages.js'
import {useGlobalStore} from '@/stores/global.js'
import mixin from '@/mixins/mixin.js'

import TipTapEditor from 'tip-tap-editor'
import 'tip-tap-editor/style.css'
import '../../../../templates/g5_helium/css/editor.css'

export default {
  name: 'addEmail',

  mixins: [mixin],

  components: {
    IncrementalSelect,
    Multiselect,
    TipTapEditor,
  },
  props: {
    mode: {
      type: String,
      default: 'create',
    },
  },

  data: () => ({
    email: 0,
    actualLanguage: '',

    langue: 0,

    dynamicComponent: false,
    displayAdvancedParameters: false,

    categories: [],
    enableTip: false,
    searchTerm: '',
    selectall: false,

    tags: [],
    documents: [],

    selectedTags: [],
    selectedCandidateAttachments: [],
    selectedCategory: 0,

    form: {
      lbl: '',
      subject: '',
      name: '',
      emailfrom: '',
      message: '',
      type: 2,
      category: '',
      published: 1,
    },
    errors: {
      subject: false,
      message: false,
      button: false
    },
    submitted: false,
    loading: false,
    displayButtonField: false,

    selectedReceiversCC: [],
    selectedReceiversBCC: [],
    selectedLetterAttachments: [],

    receivers_cc: [],
    receivers_bcc: [],
    attached_letters: [],

    action_tags: [],
    candidate_attachments: [],
    email_sender: '',

    editor_ready: false,
    editorPlugins: ['history', 'link', 'image', 'bold', 'italic', 'underline', 'left', 'center', 'right', 'h1', 'h2', 'ul'],
    suggestions: [],
    medias: [],
  }),
  created() {
    const globalStore = useGlobalStore()
    this.loading = true

    this.prepareEditor();
    this.getEmailSender()
    this.getAllAttachments()
    this.getAllTags()
    this.getAllDocumentLetter()
    this.actualLanguage = globalStore.getShortLang

    emailService
        .getEmailCategories()
        .then((response) => {
          this.categories = response.data
          this.email = globalStore.getDatas.email.value
          if (typeof this.email !== 'undefined' && this.email !== 0 && this.email !== '') {
            this.getEmailById(this.email)
          } else {
            this.dynamicComponent = true
            this.loading = false
          }
        })
        .catch((e) => {
          console.log(e)
        })

    setTimeout(() => {
      this.enableVariablesTip()
    }, 2000)
  },
  mounted() {
    if (this.actualLanguage === 'en') {
      this.langue = 1
    }
  },
  methods: {
    prepareEditor() {
      settingsService.getVariables().then((response) => {
        this.suggestions = response.data
        settingsService.getMedia().then((response) => {
          this.medias = response.data
          this.editor_ready = true
        });
      })
    },
    getMedia() {
      settingsService.getMedia().then((response) => {
        this.medias = response.data
      });
    },
    getEmailById() {
      emailService
          .getEmailById(this.email)
          .then((resp) => {
            if (resp.data === false || resp.status == 0) {
              this.runError(undefined, resp.msg)
              return
            }

            this.form = resp.data.email
            this.dynamicComponent = true

            this.selectedLetterAttachments = resp.data.letter_attachment ? resp.data.letter_attachment : []
            this.selectedCandidateAttachments = resp.data.candidate_attachment
                ? resp.data.candidate_attachment
                : []
            this.selectedTags = resp.data.tags ? resp.data.tags : []

            if (
                resp.data.receivers !== null &&
                resp.data.receivers !== undefined &&
                resp.data.receivers !== ''
            ) {
              this.setEmailReceivers(resp.data.receivers)
            }

            if (this.form.button !== '' && this.form.button !== null && this.form.button !== undefined) {
              this.displayButtonField = true
            }
            this.loading = false
          })
          .catch((e) => {
            console.log(e)
            this.runError(undefined, e.data.msg)
          })
    },
    setEmailReceivers(receivers) {
      let receiver_cc = []
      let receiver_bcc = []
      for (let index = 0; index < receivers.length; index++) {
        receiver_cc[index] = {}
        receiver_bcc[index] = {}
        if (receivers[index].type === 'receiver_cc_email' || receivers[index].type === 'receiver_cc_fabrik') {
          receiver_cc[index]['id'] = receivers[index].id
          receiver_cc[index]['email'] = receivers[index].receivers
        } else if (
            receivers[index].type === 'receiver_bcc_email' ||
            receivers[index].type === 'receiver_bcc_fabrik'
        ) {
          receiver_bcc[index]['id'] = receivers[index].id
          receiver_bcc[index]['email'] = receivers[index].receivers
        }
      }

      const cc_filtered = receiver_cc.filter((el) => {
        return el['id'] !== null && el['id'] !== undefined
      })
      const bcc_filtered = receiver_bcc.filter((el) => {
        return el['id'] !== null && el['id'] !== undefined
      })

      this.selectedReceiversCC = cc_filtered
      this.selectedReceiversBCC = bcc_filtered
    },
    displayAdvanced() {
      this.displayAdvancedParameters = !this.displayAdvancedParameters
    },
    addNewCC(newCC) {
      const tag = {
        email: newCC,
        id: newCC.substring(0, 2) + Math.floor(Math.random() * 10000000),
      }
      this.receivers_cc.push(tag)
      this.selectedReceiversCC.push(tag)
    },

    /// add new BCC
    addNewBCC(newBCC) {
      const tag = {
        email: newBCC,
        id: newBCC.substring(0, 2) + Math.floor(Math.random() * 10000000),
      }
      this.receivers_bcc.push(tag)
      this.selectedReceiversBCC.push(tag)
    },
    getEmailSender() {
      settingsService.getEmailSender().then((response) => {
        this.email_sender = response.data
      })
    },

    submit() {
      this.errors = {
        subject: false,
        message: false,
      }

      if (this.form.subject == '') {
        this.errors.subject = true
        return 0
      }

      if (this.form.message == '') {
        this.errors.message = true
        return 0
      }

      this.submitted = true

      if (this.email !== '') {
        emailService
            .updateEmail(this.email, {
              body: this.form,
              selectedReceiversCC: this.selectedReceiversCC,
              selectedReceiversBCC: this.selectedReceiversBCC,
              selectedLetterAttachments: this.selectedLetterAttachments,
              selectedCandidateAttachments: this.selectedCandidateAttachments,
              selectedTags: this.selectedTags,
            })
            .then(() => {
              history.back()
            })
            .catch((error) => {
              console.log(error)
            })
      } else {
        emailService
            .createEmail({
              body: this.form,
              selectedReceiversCC: this.selectedReceiversCC,
              selectedReceiversBCC: this.selectedReceiversBCC,
              selectedLetterAttachments: this.selectedLetterAttachments,
              selectedCandidateAttachments: this.selectedCandidateAttachments,
              selectedTags: this.selectedTags,
            })
            .then(() => {
              this.redirectJRoute('index.php?option=com_emundus&view=emails')
            })
            .catch((error) => {
              console.log(error)
            })
      }
    },

    onSearchCategory(value) {
      this.form.category = value
    },

    enableVariablesTip() {
      if (!this.enableTip) {
        this.enableTip = true
        this.tipToast(
            this.translate('COM_EMUNDUS_ONBOARD_VARIABLESTIP') + " <strong style='font-size: 16px'>/</strong>",
        )
      }
    },

    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang)
    },

    /// get all tags
    getAllTags: function () {
      settingsService
          .getTags()
          .then((response) => {
            this.action_tags = response.data
          })
          .catch((error) => {
            console.log(error)
          })
    },

    getAllDocumentLetter: function () {
      messagesService
          .getAllDocumentsLetters()
          .then((response) => {
            this.attached_letters = response.documents
          })
          .catch((error) => {
            console.log(error)
          })
    },

    getAllAttachments: function () {
      messagesService
          .getAllAttachments()
          .then((response) => {
            this.candidate_attachments = response.attachments
          })
          .catch((error) => {
            console.log(error)
          })
    },

    updateCategorySelectedValue(category) {
      if (category.label) {
        this.form.category = category.label
      } else {
        this.selectedCategory = null
        this.form.category = ''
      }
    },
  },

  computed: {
    categoriesList() {
      return this.categories.map((category, index) => {
        return {
          id: index + 1,
          label: category,
        }
      })
    },

    incSelectDefaultValue() {
      let defaultValue = null
      if (this.form && this.form.category) {
        this.categories.forEach((category, index) => {
          if (category === this.form.category) {
            defaultValue = index + 1
          }
        })
      }
      return defaultValue
    },
  },
}
</script>

<style scoped>
.emails__add-email {
  width: 100%;
  margin-left: auto;
}

.em-container-accordeon {
  background: var(--neutral-0);
  padding: 24px;
  border-radius: var(--em-coordinator-br-cards);
}

div.emails__add-email {
  padding: var(--em-spacing-6);
  background: var(--neutral-0);
  border: 1px solid var(--neutral-300);
  border-radius: var(--em-coordinator-br);
}
</style>
