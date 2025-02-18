<template>
  <div class="campaigns__add-campaign">

    <div v-if="typeof campaignId == 'undefined' || campaignId == 0">
      <div
          class="tw-flex tw-items-center tw-cursor-pointer tw-w-fit tw-px-2 tw-py-1 tw-rounded-md hover:tw-bg-neutral-300"
          @click="redirectJRoute('index.php?option=com_emundus&view=campaigns')">
        <span class="material-symbols-outlined tw-text-neutral-600">navigate_before</span>
        <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
      </div>

      <div class="tw-mt-4">
        <h1>{{ translate('COM_EMUNDUS_ONBOARD_ADD_CAMPAIGN') }}</h1>
        <div class="tw-mt-2">
          <p>{{ translate('COM_EMUNDUS_GLOBAL_INFORMATIONS_DESC') }}</p>
          <p class="tw-text-red-600 tw-mt-1">{{ translate('COM_EMUNDUS_ONBOARD_REQUIRED_FIELDS_INDICATE') }}</p>
        </div>
      </div>

      <hr class="tw-mt-1.5 tw-mb-4" />
    </div>

    <div>
      <form @submit.prevent="submit" v-if="ready" class="emundus-form fabrikForm">
        <div class="tw-flex tw-flex-col tw-gap-4">
          <div id="campaign-label-wrapper">
            <label for="campLabel" class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_CAMPNAME') }} <span
                class="tw-text-red-600">*</span></label>
            <input
                id="campLabel"
                type="text"
                v-model="form.label[actualLanguage]"
                required
                :class="{ 'is-invalid !tw-border-red-600': errors.label }"
                class="tw-mt-1 form-control fabrikinput tw-w-full"
                @focusout="onFormChange()"
                @keyup="updateAlias()"
            />
            <div v-if="errors.label" id="error-campaign-name" class="tw-text-red-600 tw-mt-1 tw-mb-1">
              <span>{{ translate('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_NAME') }}</span>
            </div>
          </div>

          <div>
            <label for="alias" class="tw-font-medium">
              {{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_ALIAS') }} <span class="tw-text-red-600">*</span>
            </label>
            <div>
              <span class="tw-text-base tw-text-neutral-600">
              {{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_ALIAS_HELPTEXT') }}
              </span>

              <div class="tw-mt-1 tw-flex tw-items-center tw-gap-2">
                <span class="tw-whitespace-nowrap">{{ baseUrl }}/</span>
                <div class="tw-w-full">
                  <input
                      id="alias"
                      type="text"
                      v-model="form.alias"
                      required
                      :class="{ 'is-invalid !tw-border-red-600': errors.alias }"
                      class="form-control fabrikinput tw-w-full"
                      @focusout="onFormChange()"
                      @keyup="form.alias !== '' ? aliasUpdated = true : aliasUpdated = false"
                  />
                  <div v-if="errors.alias" class="tw-text-red-600 tw-mb-1 tw-mt-1 tw-absolute">
                    <span>{{ translate('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_LINK') }}</span>
                  </div>
                </div>
                <span class="material-symbols-outlined tw-cursor-pointer"
                      @click="copyAliasToClipboard();">content_copy</span>
              </div>
            </div>
          </div>

          <div class="tw-grid tw-grid-cols-2 tw-gap-1.5">
            <div>
              <label for="startDate" class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_STARTDATE') }}
                <span
                    class="tw-text-red-600">*</span></label>
              <DatePicker
                  id="campaign_start_date"
                  v-model="form.start_date"
                  :keepVisibleOnInput="true"
                  :time-accuracy="2"
                  mode="dateTime"
                  is24hr
                  hide-time-header
                  title-position="left"
                  :input-debounce="500"
                  :popover="{visibility: 'focus'}"
                  :locale="actualLanguage">
                <template #default="{ inputValue, inputEvents }">
                  <input
                      :value="inputValue"
                      v-on="inputEvents"
                      class="tw-mt-1 form-control fabrikinput tw-w-full"
                      :class="{ 'is-invalid !tw-border-red-600': errors.start_date }"
                      id="start_date_input"
                  />
                  <div v-if="errors.start_date" class="tw-text-red-600 tw-mb-1 tw-mt-1 tw-absolute">
                    <span>{{ translate('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_START_DATE') }}</span>
                  </div>
                </template>
              </DatePicker>
            </div>
            <div>
              <div>
                <label for="endDate" class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_ENDDATE') }} <span
                    class="tw-text-red-600">*</span></label>
                <DatePicker
                    id="campaign_end_date"
                    v-model="form.end_date"
                    :keepVisibleOnInput="true"
                    :popover="{visibility: 'focus'}"
                    :time-accuracy="2"
                    mode="dateTime"
                    is24hr
                    hide-time-header
                    title-position="left"
                    :min-date="minDate"
                    :input-debounce="500"
                    :locale="actualLanguage">
                  <template #default="{ inputValue, inputEvents }">

                    <input
                        :value="inputValue"
                        v-on="inputEvents"
                        class="tw-mt-1 form-control fabrikinput tw-w-full"
                        :class="{ 'is-invalid !tw-border-red-600': errors.end_date }"
                        id="end_date_input"
                    />
                    <div v-if="errors.end_date" class="tw-text-red-600 tw-mb-1 tw-mt-1 tw-absolute">
                      <span>{{ translate('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_END_DATE') }}</span>
                    </div>
                  </template>
                </DatePicker>
              </div>
            </div>
          </div>

          <div>
            <label for="year" class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_PICKYEAR') }} <span
                class="tw-text-red-600">*</span></label>
            <div>
              <span class="tw-text-base tw-text-neutral-600">
              {{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_PICKYEAR_HELPTEXT') }}
              </span>
              <autocomplete
                  :id="'year'"
                  @searched="onSearchYear"
                  :items="this.session"
                  :year="form.year"
                  :name="sessionPlaceholder"
              />
              <div v-if="errors.year" class="tw-text-red-600 tw-mb-1 tw-mt-1">
                <span>{{ translate('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_YEAR') }}</span>
              </div>
            </div>
          </div>

          <div class="tw-flex tw-items-center">
            <div class="em-toggle">
              <input type="checkbox"
                     true-value="1"
                     false-value="0"
                     class="tw-mt-2 em-toggle-check"
                     id="published"
                     name="published"
                     v-model="form.published"
                     @click="onFormChange()"
              />
              <strong class="b em-toggle-switch"></strong>
              <strong class="b em-toggle-track"></strong>
            </div>
            <span for="published" class="tw-ml-2">{{ translate('COM_EMUNDUS_ONBOARD_CAMPAIGN_PUBLISH') }}</span>
          </div>

          <div class="tw-flex tw-items-center">
            <div class="em-toggle">
              <input type="checkbox"
                     true-value="0"
                     false-value="1"
                     class="tw-mt-2 em-toggle-check"
                     id="visible"
                     name="visible"
                     v-model="form.visible"
                     @click="onFormChange()"
              />
              <strong class="b em-toggle-switch"></strong>
              <strong class="b em-toggle-track"></strong>
            </div>
            <span for="visible" class="tw-ml-2 tw-flex tw-items-center">
              {{ translate('COM_EMUNDUS_CAMPAIGNS_VISIBLE') }}
            </span>
          </div>

          <div class="tw-flex tw-items-center">
            <div class="em-toggle">
              <input type="checkbox"
                     true-value="1"
                     false-value="0"
                     class="tw-mt-2 em-toggle-check"
                     id="pinned"
                     name="pinned"
                     v-model="form.pinned"
                     @click="onFormChange()"
              />
              <strong class="b em-toggle-switch"></strong>
              <strong class="b em-toggle-track"></strong>
            </div>
            <span for="pinned" class="tw-ml-2 tw-flex tw-items-center">{{ translate('COM_EMUNDUS_CAMPAIGNS_PIN') }}
              <span class="material-symbols-outlined tw-ml-1 tw-text-base tw-cursor-pointer tw-text-neutral-600"
                    @click="displayPinnedCampaignTip">help_outline</span>
            </span>
          </div>
        </div>

        <hr class="tw-mt-1.5 tw-mb-4">

        <div class="tw-flex tw-flex-col tw-gap-4">
          <h2>{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_INFORMATION') }}</h2>

          <div id="campResume">
            <div class="tw-flex tw-items-center">
              <label class="tw-font-medium tw-mb-0">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME') }}</label>
              <span class="material-symbols-outlined tw-ml-1 tw-text-base tw-cursor-pointer tw-text-neutral-600"
                    @click="displayCampaignResumeTip">help_outline</span>
            </div>
            <tip-tap-editor
                v-model="form.short_description"
                :editor-content-height="'5em'"
                :class="'tw-mt-1'"
                :locale="'fr'"
                :preset="'basic'"
                :toolbar-classes="['tw-bg-white']"
                :editor-content-classes="['tw-bg-white']"
                :placeholder="translate('COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME')"
            />
          </div>

          <div>
            <div class="tw-flex tw-items-center">
              <label class="tw-font-medium tw-mb-0">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION') }}</label>
              <span class="material-symbols-outlined tw-ml-1 tw-text-base tw-cursor-pointer tw-text-neutral-600"
                    @click="displayCampaignDescriptionTip">help_outline</span>
            </div>
            <div id="campDescription" v-if="typeof form.description != 'undefined'">
              <tip-tap-editor
                  v-model="form.description"
                  :upload-url="'/index.php?option=com_emundus&controller=settings&task=uploadmedia'"
                  :editor-content-height="'30em'"
                  :class="'tw-mt-1'"
                  :locale="'fr'"
                  :preset="'custom'"
                  :plugins="editorPlugins"
                  :toolbar-classes="['tw-bg-white']"
                  :editor-content-classes="['tw-bg-white']"
                  :placeholder="translate('COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION')"
              />
            </div>
          </div>
        </div>

        <hr class="tw-mt-1.5 tw-mb-4" />

        <div class="tw-flex tw-flex-col tw-gap-4">
          <div>
            <h2>{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM') }}</h2>
            <div class="tw-mt-2">
              <p>
                {{ translate('COM_EMUNDUS_ONBOARD_PROGRAM_INTRO_DESC') }}
                <span class="tw-text-red-600">*</span>
              </p>
            </div>
          </div>

          <div>
            <div class="tw-flex tw-items-center">
              <select
                  id="select_prog"
                  class="form-control fabrikinput tw-w-full"
                  :class="{ 'is-invalid !tw-border-red-600': errors.progCode }"
                  v-model="form.training"
                  v-on:change="setCategory"
                  :disabled="this.programs.length <= 0"
              >
                <option value="">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_CHOOSEPROG') }}</option>
                <option
                    v-for="(item, index) in programs"
                    v-bind:value="item.code"
                    v-bind:data-category="item.programmes"
                    :key="index">
                  {{
                    item.label && item.label[actualLanguage] !== null && typeof item.label[actualLanguage] != 'undefined' ? item.label[actualLanguage] : item.label
                  }}
                </option>
              </select>
              <button v-if="coordinatorAccess != 0" :title="translate('COM_EMUNDUS_ONBOARD_ADDPROGRAM')" type="button"
                      id="add-program" class="tw-ml-2 tw-bg-transparent" @click="displayProgram">
                <span class="material-symbols-outlined em-main-500-color">add_circle_outline</span>
              </button>
            </div>
            <div v-if="errors.progCode" class="tw-text-red-600 tw-mb-1 tw-mt-1">
              <span>{{ translate('COM_EMUNDUS_ONBOARD_FORM_REQUIRED_PROGRAM') }}</span>
            </div>
          </div>

          <transition name="slide-fade">
            <div v-if="isHiddenProgram">
              <div>
                <div>
                  <label for="prog_label" class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_PROGNAME') }} <span
                      class="tw-text-red-600">*</span></label>
                  <input
                      type="text"
                      id="prog_label"
                      class="tw-mt-1 form-control fabrikinput tw-w-full"
                      placeholder=" "
                      v-model="programForm.label"
                      :class="{ 'is-invalid !tw-border-red-600': errors.progLabel }"
                  />
                </div>
                <div v-if="errors.progLabel" class="tw-text-red-600 tw-mb-1 tw-mt-1">
                  <span class="tw-text-red-600">{{ translate('COM_EMUNDUS_ONBOARD_PROG_REQUIRED_LABEL') }}</span>
                </div>
              </div>
            </div>
          </transition>
        </div>

        <hr class="tw-mt-1.5 tw-mb-4" />

        <div class="tw-flex tw-flex-col tw-gap-4" id="campaign-form-container">
          <h2>{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_FORM') }} <i
              class="tw-text-sm tw-text-neutral-500">{{ translate('COM_EMUNDUS_OPTIONAL') }}</i></h2>

          <div>
            <label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_FORM_DESC') }}</label>

            <div class="tw-flex tw-items-center tw-mt-1 tw-mb-1">
              <select class="tw-w-full" v-model="form.profile_id">
                <option value="0">{{ translate('COM_EMUNDUS_ONBOARD_CHOOSE_FORM') }}</option>
                <option v-for="applicantForm in applicantForms" :key="applicantForm.id" :value="applicantForm.id">
                  {{ applicantForm.label }}
                </option>
              </select>
              <span class="material-symbols-outlined tw-cursor-pointer tw-ml-2" @click="getAllForms">refresh</span>
            </div>
            <a href="/forms" target="_blank" class="tw-underline">
              {{ translate('COM_EMUNDUS_ONBOARD_ACCESS_TO_FORMS_LIST') }}
            </a>
          </div>
        </div>

        <hr class="tw-mt-1.5 tw-mb-4" />

        <div class="tw-flex tw-flex-col tw-gap-4" id="select-campaign-languages" v-if="languageOptions.length > 1">
          <h2>
            {{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_LANGUAGES') }}
            <i class="tw-text-sm tw-text-neutral-500">{{ translate('COM_EMUNDUS_OPTIONAL') }}</i>
          </h2>

          <div>
            <div v-if="programLanguages.length > 0" id="program-languages" class="tw-mb-1 tw-p-4 alert alert-info tw-flex">
              <span class="material-symbols-outlined tw-mr-2">info</span>
              <p class="tw-font-light tw-text-sm"> {{ translate('COM_EMUNDUS_ONBOARD_ADDCAMP_PROGRAM_LANGUAGES') }}
                <strong v-for="(language, index) in programLanguages" :key="language.lang_id">
                  {{ language.title }}{{ (index < (programLanguages.length - 1)) ? ', ' : '' }}
                </strong>
              </p>
            </div>

            <multiselect
                v-model="campaignLanguages"
                label="label"
                track-by="value"
                :options="languageOptions"
                :multiple="true"
                :taggable="false"
                :placeholder="translate('COM_EMUNDUS_ONBOARD_CHOOSE_LANGUAGE')"
                select-label=""
                selected-label=""
                deselect-label=""
            ></multiselect>
          </div>

        </div>

        <hr class="tw-mt-1.5 tw-mb-4" />

        <div class="tw-flex tw-justify-end">
          <button
              id="save-btn"
              type="button"
              class="tw-btn-primary tw-w-auto"
              @click="quit = 1; submit()">
            {{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTINUER') }}
          </button>
        </div>
      </form>
    </div>

    <div class="em-page-loader" v-if="submitted || !ready"></div>
  </div>
</template>

<script>
import Swal from "sweetalert2";
import Autocomplete from "@/components/autocomplete.vue";

/** VCalendar **/
import {DatePicker} from 'v-calendar';
import 'v-calendar/dist/style.css';

/** TipTap Editor **/
import TipTapEditor from 'tip-tap-editor'
import 'tip-tap-editor/style.css'
import '../../../../templates/g5_helium/css/editor.css'

/** SERVICES **/
import campaignService from '@/services/campaign.js'
import settingsService from '@/services/settings.js';
import programmeService from '@/services/programme.js';

import {useGlobalStore} from "@/stores/global.js";
import {useCampaignStore} from "@/stores/campaign.js";
import fileService from "@/services/file.js";
import Multiselect from "vue-multiselect";

export default {
  name: "addCampaign",

  components: {
    Multiselect,
    TipTapEditor,
    Autocomplete,
    DatePicker
  },

  directives: {
    focus: {
      inserted: function (el) {
        el.focus()
      }
    }
  },

  props: {
    campaign: Number,
  },

  data: () => ({
    // props
    campaignId: 0,
    actualLanguage: "",
    coordinatorAccess: 0,
    quit: 1,

    isHiddenProgram: false,

    // Date picker rules
    minDate: "",
    //

    programs: [],
    applicantForms: [],
    years: [],
    languages: [],
    aliases: [],
    editorPlugins: ['history', 'link', 'image', 'bold', 'italic', 'underline', 'left', 'center', 'right', 'h1', 'h2', 'ul'],

    session: [],
    old_training: "",
    old_program_form: "",
    aliasUpdated: false,
    campaignLanguages: [],
    form: {
      label: {},
      start_date: "",
      end_date: "",
      short_description: "",
      description: null,
      training: "",
      year: "",
      published: 1,
      is_limited: 0,
      profile_id: 0,
      limit: 50,
      limit_status: [],
      pinned: 0,
      visible: 1,
      alias: '',
    },
    programForm: {
      code: "",
      label: "",
      notes: "",
      programmes: "",
      published: 1,
      apply_online: 1,
      color: "#1C6EF2"
    },

    year: {
      label: "",
      code: "",
      schoolyear: "",
      published: 1,
      profile_id: "",
      programmes: ""
    },

    errors: {
      label: false,
      progCode: false,
      progLabel: false,
      short_description: false,
      limit_files_number: false,
      limit_status: false
    },

    submitted: false,
    ready: false,
  }),

  created() {
    const globalStore = useGlobalStore();
    this.getAllForms();

    if (this.campaign === '') {
      this.campaignId = globalStore.getDatas.campaign ? globalStore.getDatas.campaign.value : 0;
    } else {
      this.campaignId = this.$props.campaign ? this.$props.campaign : 0;
    }

    this.actualLanguage = globalStore.getShortLang;
    this.coordinatorAccess = globalStore.hasCoordinatorAccess;

    this.getLanguages().then(() => {
      this.getCampaignById();
    });

    campaignService.getAllItemsAlias(this.campaignId).then((response) => {
      this.aliases = response.data;
    });
  },
  methods: {
    changed() {
      console.debug('changed');
      throw new Error('It\'s not an Error, please ignore.');
    },

    displayPinnedCampaignTip() {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_PINNED_CAMPAIGN_TIP"),
        text: this.translate("COM_EMUNDUS_ONBOARD_PINNED_CAMPAIGN_TIP_TEXT"),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          actions: "em-swal-single-action",
        },
      });
    },

    displayCampaignResumeTip() {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME_TIP"),
        text: this.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_RESUME_TIP_TEXT"),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          actions: "em-swal-single-action",
        },
      });
    },

    displayCampaignDescriptionTip() {
      Swal.fire({
        title: this.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION_TIP"),
        text: this.translate("COM_EMUNDUS_ONBOARD_ADDCAMP_DESCRIPTION_TIP_TEXT"),
        showCancelButton: false,
        confirmButtonText: this.translate("COM_EMUNDUS_SWAL_OK_BUTTON"),
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          actions: "em-swal-single-action",
        },
      });
    },

    getCampaignById() {
      if (typeof this.campaignId !== 'undefined' && this.campaignId !== '' && this.campaignId > 0) {
        campaignService.getCampaignById(this.campaignId).then((response) => {
          let label = response.data.campaign.label;

          this.form = response.data.campaign;
          this.$emit('getInformations', this.form);
          this.programForm = response.data.program;

          // Check label translations
          this.form.label = response.data.label;
          this.languages.forEach((language) => {
            if (this.form.label[language.sef] === '' || this.form.label[language.sef] == null) {
              this.form.label[language.sef] = label;
            }
          });
          //

          // Convert date
          this.form.start_date = new Date(this.form.start_date);
          this.form.end_date = new Date(this.form.end_date);
          //

          if (typeof response.data.campaign.status != 'undefined') {
            this.form.limit_status = [];
            this.form.is_limited = 1;
            Object.values(response.data.campaign.status).forEach((statu) => {
              this.form.limit_status[parseInt(statu.limit_status)] = true;
            });
          } else {
            this.form.limit_status = [];
          }
          this.ready = true;
        }).catch(e => {
          console.log(e);
        });
      } else {
        this.form.start_date = new Date();
        this.ready = true;
      }

      this.getCampaignLanguages();
      this.getAllPrograms();
    },

    getAllForms() {
      fileService.getProfiles().then(response => {
        if (response.status) {
          this.applicantForms = response.data.filter(form => form.published === 1 && form.menutype !== '');
        }
      }).catch(e => {
        console.log(e);
      });
    },

    getCampaignLanguages() {
      if (this.campaignId) {
        campaignService.getCampaignLanguages(this.campaignId).then((response) => {
          this.campaignLanguages = response.data;
        });
      }
    },

    getAllPrograms() {
      programmeService.getAllPrograms('', '', 0, 0, 'p.label').then(response => {
        if (response.status) {
          this.programs = response.data.datas;
        } else {
          this.programs = [];
        }
      }).catch(e => {
        console.log(e);
      });
      this.getYears();
    },

    getYears() {
      campaignService.getYears()
          .then(response => {
            this.years = response.data;

            this.years.forEach((year) => {
              this.session.push(year.schoolyear);
            });

          }).catch(e => {
        console.log(e);
      });
    },

    async getLanguages() {
      return settingsService.getActiveLanguages().then(response => {
        if (response) {
          this.languages = response.data;
        }

        return response;
      });
    },

    setCategory(e) {
      this.year.programmes = e.target.options[e.target.options.selectedIndex].dataset.category;
      this.programForm = this.programs.find(program => program.code == this.form.training);
    },

    createCampaign(form_data) {
      form_data.start_date = this.formatDate(new Date(this.form.start_date));
      form_data.end_date = this.formatDate(new Date(this.form.end_date));
      form_data.languages = this.campaignLanguages.map((language) => language.value);

      campaignService.createCampaign(form_data).then((response) => {
        if (response.status == 1) {
          this.campaignId = response.data;
          this.quitFunnelOrContinue(this.quit, response.redirect);
        }
      });
    },

    createCampaignWithNoExistingProgram(programForm) {
      programmeService.createProgram(programForm).then((response) => {
        if (response.status) {
          this.form.progid = response.data.programme_id;
          this.form.training = response.data.programme_code;
          this.programForm.code = response.data.programme_code;

          if (this.campaignId > 0) {
            this.updateCampaign();
          } else {
            this.createCampaign(this.form);
          }
        } else {
          Swal.fire({
            icon: 'error',
            title: this.translate('COM_EMUNDUS_ADD_CAMPAIGN_ERROR'),
            reverseButtons: true,
            customClass: {
              title: 'em-swal-title',
              confirmButton: 'em-swal-confirm-button',
              actions: "em-swal-single-action",
            },
          });
          this.submitted = false;
        }
      });
    },

    submit() {
      const campaignStore = useCampaignStore();
      campaignStore.setUnsavedChanges(true);

      // Checking errors
      this.errors = {
        label: false,
        alias: false,
        start_date: false,
        end_date: false,
        year: false,
        progCode: false,
        progLabel: false,
        short_description: false,
        limit_files_number: false,
        limit_status: false
      }
      if (this.form.label[this.actualLanguage] === '' || this.form.label[this.actualLanguage] == null || typeof this.form.label[this.actualLanguage] === 'undefined') {
        window.scrollTo({top: 0, behavior: 'smooth'});
        this.errors.label = true;
      }

      if (this.form.alias === '' || this.form.alias == null || typeof this.form.alias === 'undefined') {
        window.scrollTo({top: 0, behavior: 'smooth'});
        this.errors.alias = true;
      }

      if (this.form.end_date === '' || this.form.end_date === '0000-00-00 00:00:00') {
        window.scrollTo({top: 0, behavior: 'smooth'});
        this.errors.end_date = true;
      }

      if (this.form.start_date === '' || this.form.start_date === '0000-00-00 00:00:00') {
        window.scrollTo({top: 0, behavior: 'smooth'});
        this.errors.start_date = true;
      }

      if (this.form.year === '') {
        window.scrollTo({top: 0, behavior: 'smooth'});
        this.errors.year = true;
        document.getElementById('year').classList.add('is-invalid');
        document.getElementById('year').classList.add('!tw-border-red-600');
      }

      if (this.form.is_limited == 1) {
        let least_one_status = this.form.limit_status.every((value) => {
          return value === false;
        });
        if (this.form.limit === '') {
          window.scrollTo({top: 0, behavior: 'smooth'});
          this.errors.limit_files_number = true;
        }
        if (this.form.limit_status.length == 0 || least_one_status) {
          window.scrollTo({top: 0, behavior: 'smooth'});
          this.errors.limit_status = true;
        }
      }

      if (this.form.training === '') {
        if (this.isHiddenProgram) {
          if (this.programForm.label === '') {
            this.errors.progLabel = true;
          } else {
            // does this label already exists
            const similarProgram = this.programs.find((program) => {
              return program.label === this.programForm.label;
            });

            if (similarProgram !== undefined) {
              this.errors.progLabel = true;
            }
          }
        } else {
          this.errors.progCode = true;
        }
      }

      if (this.errors.label || this.errors.start_date || this.errors.end_date || this.errors.year || this.errors.limit_files_number || this.errors.limit_status || this.errors.progLabel || this.errors.progCode || this.errors.alias) {
        return 0;
      }

      // Set year object values
      this.year.label = this.form.label;
      this.year.code = this.form.training;
      this.year.schoolyear = this.form.year;
      this.year.published = this.form.published;
      this.year.profile_id = this.form.profile_id;
      //

      if (this.form.label.en === '' || this.form.label.en == null || typeof this.form.label.en == "undefined") {
        this.form.label.en = this.form.label.fr;
      }

      this.submitted = true;

      if (typeof this.campaignId !== 'undefined' && this.campaignId !== null && this.campaignId !== "" && this.campaignId !== 0) {
        if (this.form.training !== '') {
          this.updateCampaign();
        } else {
          this.createCampaignWithNoExistingProgram(this.programForm);
        }
      } else {
        if (this.form.training !== '') {
          this.programForm = this.programs.find(program => program.code === this.form.training);
          this.form.training = this.programForm.code;
          this.createCampaign(this.form);
        } else {
          this.createCampaignWithNoExistingProgram(this.programForm);
        }
      }
    },

    updateCampaign() {
      let form_data = this.form;
      form_data.training = this.programForm.code;
      form_data.start_date = this.formatDate(new Date(this.form.start_date));
      form_data.end_date = this.formatDate(new Date(this.form.end_date));
      form_data.languages = this.campaignLanguages.map((language) => language.value);

      campaignService.updateCampaign(form_data, this.campaignId).then((response) => {
        if (!response.status) {
          Swal.fire({
            icon: 'error',
            title: this.translate('COM_EMUNDUS_ADD_CAMPAIGN_ERROR'),
            reverseButtons: true,
            customClass: {
              title: 'em-swal-title',
              confirmButton: 'em-swal-confirm-button',
              actions: "em-swal-single-action",
            },
          });
          this.submitted = false;
          return 0;
        } else {
          this.$emit('nextSection');
          this.$emit('updateHeader', this.form);
        }
      }).catch(error => {
        console.log(error);
      });
    },

    quitFunnelOrContinue(quit, redirect = '') {
      if (quit === 0) {
        this.redirectJRoute('index.php?option=com_emundus&view=campaigns');
      } else if (quit === 1) {
        document.cookie = 'campaign_' + this.campaignId + '_menu = 1; expires=Session; path=/';
        if (redirect === '') {
          redirect = 'index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' + this.campaignId + '&index=0'
        }

        this.redirectJRoute(redirect)
      }
    },

    redirectJRoute(link) {
      settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
    },

    onSearchYear(value) {
      this.form.year = value;
    },
    onFormChange() {
      const campaignStore = useCampaignStore();
      campaignStore.setUnsavedChanges(true);
    },
    displayProgram() {
      if (this.isHiddenProgram) {
        document.getElementById('add-program').style = 'transform: rotate(0)';
        this.form.training = this.old_training;
        this.programForm = this.old_program_form;
        document.getElementById('select_prog').removeAttribute('disabled');
      } else {
        this.old_training = this.form.training;
        this.old_program_form = this.programForm;
        this.form.training = '';
        this.programForm = {
          code: '',
          label: '',
          notes: '',
          programmes: '',
          published: 1,
          apply_online: 1
        }
        document.getElementById('add-program').style = 'transform: rotate(45deg)';
        document.getElementById('select_prog').setAttribute('disabled', 'disabled');
      }
      this.isHiddenProgram = !this.isHiddenProgram;
    },

    updateAlias() {
      if (!this.aliasUpdated && this.campaignId === 0) {
        let alias = this.form.label[this.actualLanguage].normalize("NFD").replace(/[\u0300-\u036f]/g, "")
        this.form.alias = alias.replace(/[^a-zA-Z0-9_-]+/g, '-').toLowerCase();
      }
    },

    copyAliasToClipboard() {
      navigator.clipboard.writeText(window.location.origin + '/' + this.form.alias);
      Swal.fire({
        title: this.translate('COM_EMUNDUS_ONBOARD_ALIAS_COPIED'),
        icon: 'success',
        showConfirmButton: false,
        customClass: {
          title: 'em-swal-title',
          actions: "em-swal-single-action",
        },
        timer: 1500,
      });
    },

    formatDate(date, format = 'YYYY-MM-DD HH:mm:ss') {
      let year = date.getFullYear();
      let month = (1 + date.getMonth()).toString().padStart(2, '0');
      let day = date.getDate().toString().padStart(2, '0');
      let hours = date.getHours().toString().padStart(2, '0');
      let minutes = date.getMinutes().toString().padStart(2, '0');
      let seconds = date.getSeconds().toString().padStart(2, '0');

      return format
          .replace('YYYY', year)
          .replace('MM', month)
          .replace('DD', day)
          .replace('HH', hours)
          .replace('mm', minutes)
          .replace('ss', seconds);
    }
  },

  computed: {
    baseUrl() {
      return window.location.origin;
    },
    sessionPlaceholder() {
      let oneYearFromNow = new Date();
      oneYearFromNow.setFullYear(oneYearFromNow.getFullYear() + 1);

      return new Date().getFullYear() + ' - ' + oneYearFromNow.getFullYear();
    },
    languageOptions() {
      return this.languages.map((language) => {
        return {
          label: language.title,
          value: language.lang_id
        }
      });
    },
    programLanguages() {
      let languages = [];

      // check selected program
      if (this.form.training !== '') {
        let programLang = [];

        this.programs.forEach((program) => {
          if (program.code === this.form.training) {
            programLang = program.language_ids != null && Array.isArray(program.language_ids) ? program.language_ids : [];
          }
        });

        if (programLang.length > 0) {
          languages = programLang.map((language_id) => {
            return this.languages.find(language => language.lang_id == language_id);
          });
        }
      }

      return languages;
    }
  },

  watch: {
    'form.start_date': function (val) {
      if (typeof val === 'object') {
        let startDate = new Date(val);
        let endDate = new Date(this.form.end_date);
        this.minDate = new Date(startDate.setDate(startDate.getDate() + 1)).toISOString();
        if (endDate < this.minDate) {
          this.form.end_date = this.minDate;
        }
      }
    },

    'form.alias': function (val, oldVal) {
      if (val !== oldVal && val && val !== '') {
        this.form.alias = val.normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/[^a-zA-Z0-9_-]+/g, '-').toLowerCase();
        // Check if alias already exists
        if (typeof this.aliases !== 'undefined' && this.aliases.includes(val)) {
          this.form.alias = val + '-1';
        }
      }
    }
  }
};
</script>

<style scoped>
#add-program {
  height: 24px;
  width: 24px;
  padding: unset;
  transition: transform 0.1s ease-in-out;
}

.em-color-round {
  height: 30px;
  width: 30px;
  border-radius: 50%;
}

.modal-date-picker {
  transform: translate(-50%, -50%);
  box-shadow: 0 0 9999px 9999px rgb(0 0 0 / 50%);
}
</style>
