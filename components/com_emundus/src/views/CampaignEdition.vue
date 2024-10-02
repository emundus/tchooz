<template>
  <div id="edit-campaign">
    <div class="em-w-custom"></div>
    <div class="em-border-cards em-card-shadow tw-rounded em-white-bg em-p-24">
      <div>
        <div class="tw-flex tw-items-center tw-cursor-pointer" @click="redirectJRoute('index.php?option=com_emundus&view=campaigns')">
          <span class="material-symbols-outlined tw-text-neutral-600">navigate_before</span>
          <span class="tw-ml-2 tw-text-neutral-900">{{ translate('BACK') }}</span>
        </div>
        <div class="tw-flex tw-items-center tw-mt-4">
          <h1>{{ translate(selectedMenuItem.label) }}</h1>
        </div>
        <p v-html="translate(selectedMenuItem.description)"></p>
        <hr>

        <div id="campaign-info-line" class="tw-flex tw-items-center tw-mb-8">
          <p>
            <b style="color: var(--em-profile-color); font-weight: 700 !important;"> {{ form.label }}</b>
            {{ translate('COM_EMUNDUS_ONBOARD_FROM') }}
            <strong>{{ form.start_date }}</strong>
            {{ translate('COM_EMUNDUS_ONBOARD_TO') }}
            <strong>{{ form.end_date }}</strong>
          </p>
        </div>

        <ul v-show="profileId" class="tw-flex tw-items-center tw-gap-2 tw-list-none tw-mb-4 tw-pl-0 tw-border-b tw-border-neutral-400">
          <li v-for="menu in displayedMenus" :key="menu.component" @click="selectMenu(menu)" class="tw-border tw-border-transparent tw-flex tw-flex-col tw-rounded-t-lg hover:tw-border-neutral-300" :class="{'tw-border tw-border-neutral-300 tw-bg-neutral-300': selectedMenu === menu.component}">
            <span :id="menu.component" class="tw-cursor-pointer tw-p-2 tw-text-neutral-700">{{ translate(menu.label) }}</span>
          </li>
        </ul>

        <br>
        <div v-if="selectedMenu === 'addEmail'" class="warning-message-program mb-1">
          <p class="tw-text-red-600 flex flex-row"><span class="material-symbols-outlined tw-mr-2 tw-text-red-600">warning_amber</span>{{ translate('COM_EMUNDUS_ONBOARD_PROGRAM_WARNING') }}
          </p>
          <ul v-if="campaignsByProgram.length > 0" class="tw-mt-2 tw-mb-8 em-pl-16">
            <li v-for="campaign in campaignsByProgram" :key="'camp_progs_' + campaign.id">{{ campaign.label }}</li>
          </ul>
        </div>
        <transition name="fade">
          <add-campaign
              v-if="selectedMenu === 'addCampaign' && campaignId !== ''"
              :campaign="campaignId"
              :coordinatorAccess="true"
              :actualLanguage="actualLanguage"
              :manyLanguages="manyLanguages"
              @nextSection="next"
              @getInformations="initInformations"
              @updateHeader="updateHeader"
          ></add-campaign>
          <campaign-more
              v-else-if="selectedMenu === 'campaignMore' && campaignId !== ''"
              :campaignId="campaignId"
              :defaultFormUrl="campaignMoreFormUrl"
          >
          </campaign-more>
          <campaign-steps
              v-else-if="selectedMenu === 'campaignSteps' && campaignId !== ''"
              :campaignId="campaignId"
              @nextSection="next"
          >
          </campaign-steps>
          <add-documents-dropfiles
              v-else-if="selectedMenu === 'addDocumentsDropfiles'"
              :funnelCategorie="selectedMenuItem.label"
              :profileId="getProfileId"
              :campaignId="campaignId"
              :langue="actualLanguage"
              :manyLanguages="manyLanguages"
          />

          <add-email
              v-else-if="selectedMenu === 'addEmail' && program.id != 0"
              :prog="Number(program.id)"
          ></add-email>

          <History v-else-if="selectedMenu === 'History'" extension="com_emundus.campaign" />
        </transition>
      </div>

      <div class="tw-flex tw-items-center tw-justify-end tw-mt-4"
           v-if="['addDocumentsDropfiles'].includes(selectedMenu)">
        <button type="button" class="tw-btn-primary tw-w-auto mb-4" @click="next">
          {{ translate('COM_EMUNDUS_ONBOARD_ADD_CONTINUER') }}
        </button>
      </div>

      <div class="em-page-loader" v-if="loading"></div>
    </div>
  </div>
</template>

<script>
import mixin from '@/mixins/mixin.js';
import campaignService from '@/services/campaign.js';
import formService from '@/services/form.js';
import programmeService from '@/services/programme.js';
import settingsService from "@/services/settings.js";

import addCampaign from "@/views/addCampaign.vue";
import AddDocumentsDropfiles from "@/components/FunnelFormulaire/addDocumentsDropfiles.vue";
import addEmail from "@/components/FunnelFormulaire/addEmail.vue";
import campaignMore from "@/components/FunnelFormulaire/CampaignMore.vue";
import campaignSteps from "@/components/FunnelFormulaire/CampaignSteps.vue";

import { useGlobalStore } from '@/stores/global.js';
import History from "@/components/History/History.vue";


export default {
  name: 'CampaignEdition',

  components: {
    History,
    AddDocumentsDropfiles,
    addCampaign,
    addEmail,
    campaignMore,
    campaignSteps
  },

  props: {
    index: Number,
  },
  mixins: [mixin],

  data: () => ({
    campaignId: 0,
    actualLanguage: '',
    manyLanguages: 0,
    prid: '',
    menus: [
      {
        label: "COM_EMUNDUS_GLOBAL_INFORMATIONS",
        description: "COM_EMUNDUS_GLOBAL_INFORMATIONS_DESC",
        icon: "info",
        component: "addCampaign",
        displayed: true
      },
      {
        label: "COM_EMUNDUS_CAMPAIGN_MORE",
        description: "COM_EMUNDUS_CAMPAIGN_MORE_DESC",
        icon: "description",
        component: "campaignMore",
        displayed: false
      },
      {
        label: "COM_EMUNDUS_CAMPAIGN_STEPS",
        description: "COM_EMUNDUS_CAMPAIGN_STEPS_DESC",
        icon: "description",
        component: "campaignSteps",
        displayed: true
      },
      {
        label: "COM_EMUNDUS_DOCUMENTS_CAMPAIGNS",
        description: "COM_EMUNDUS_DOCUMENTS_CAMPAIGNS_DESC",
        icon: "description",
        component: "addDocumentsDropfiles",
        displayed: true
      },
      {
        label: "COM_EMUNDUS_EMAILS",
        description: "COM_EMUNDUS_EMAILS_DESC",
        icon: "description",
        component: "addEmail",
        displayed: true
      },
      {
        label: "COM_EMUNDUS_GLOBAL_HISTORY",
        description: "",
        icon: "history",
        component: "History",
        displayed: true
      }
    ],
    selectedMenu: 'addCampaign',
    formReload: 0,
    prog: 0,
    loading: false,
    closeSubmenu: true,
    profileId: null,
    profiles: [],
    campaignsByProgram: [],
    form: {},
    campaignMoreFormUrl: '',
    program: {
      id: 0,
      code: '',
      label: '',
      notes: '',
      programmes: [],
      tmpl_badge: '',
      published: 0,
      apply_online: 0,
      synthesis: '',
      tmpl_trombinoscope: '',
    },
  }),

  created() {
    const globalStore = useGlobalStore();

    // Get datas that we need with store
    this.campaignId = parseInt(globalStore.datas.campaignId.value);
    this.actualLanguage = globalStore.getCurrentLang;
    this.manyLanguages = globalStore.hasManyLanguages;
    //

    this.getCampaignMoreForm();
    this.getProgram();

    //this.loading = true;
    if (this.actualLanguage === "en") {
      this.langue = 1;
    }
  },
  methods: {
    getCampaignMoreForm() {
      campaignService.getCampaignMoreFormUrl(this.campaignId)
        .then(response => {
          if (response.status && response.data.length > 0) {
            this.menus.forEach(menu => {
              if (menu.component === 'campaignMore') {
                menu.displayed = true;
              }
            });
            this.campaignMoreFormUrl = response.data;
          }
        }).catch(error => {
          console.error(error);
        });
    },
    initInformations(campaign) {
      this.form.label = campaign.label;
      this.form.profile_id = campaign.profile_id;
      this.form.program_id = campaign.progid;

      this.initDates(campaign);

      formService.getPublishedForms().then(response => {
        this.profiles = response.data.data;
        if (this.form.profile_id == null) {
          this.profiles.length != 0 ? this.profileId = this.profiles[0].id : this.profileId = null;
          if (this.profileId != null) {
            this.formReload += 1;
          }
        } else {
          this.formReload += 1;
          this.profileId = this.form.profile_id;
        }
        this.loading = false;

        let cookie = this.getCookie('campaign_' + this.campaignId + '_menu');
        if (cookie) {
          this.menuHighlight = cookie;
          document.cookie = 'campaign_' + this.campaignId + '_menu =; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
        }
      });
    },

    updateHeader(value) {
      this.form.label = value.label[this.actualLanguage];
      this.initDates(value);
    },

    initDates(campaign) {
      this.form.start_date = campaign.start_date;
      this.form.end_date = campaign.end_date;

      let currentLanguage = useGlobalStore().getCurrentLang;
      if (currentLanguage === '' || currentLanguage === undefined) {
        currentLanguage = 'fr-FR';
      }

      const dateOptions = {dateStyle: 'long', timeStyle: 'short'};
      const startDate = new Date(campaign.start_date);
      this.form.start_date = new Intl.DateTimeFormat(currentLanguage, dateOptions).format(startDate);

      if (this.form.end_date === '0000-00-00 00:00:00') {
        this.form.end_date = null;
      } else {
        const endDate = new Date(campaign.end_date);
        this.form.end_date = new Intl.DateTimeFormat(currentLanguage, dateOptions).format(endDate);
      }
    },

    getProgram() {
      campaignService.getProgrammeByCampaignID(this.campaignId).then(response => {
        this.program = response.data;

        if (this.program.id) {
          programmeService.getCampaignsByProgram(this.program.id).then(resp => {
            this.campaignsByProgram = resp.campaigns;
          });
        }

      }).catch(e => {
        console.error(e);
      });
    },
    selectMenu(menu) {
      this.selectedMenu = menu.component;
    },

    setProfileId(prid) {
      this.profileId = prid;
    },
    next() {
      let index = this.displayedMenus.findIndex(menu => menu.component === this.selectedMenu);
      if (index < this.displayedMenus.length - 1) {
        this.selectedMenu = this.displayedMenus[index + 1].component;
      }
    },

    previous() {
      let index = this.displayedMenus.findIndex(menu => menu.component === this.selectedMenu);
      if (index > 0) {
        this.selectedMenu = this.displayedMenus[index - 1].component;
      }
    },

    redirectJRoute(link) {
      settingsService.redirectJRoute(link,useGlobalStore().getCurrentLang);
    },

    getCookie(cname) {
      var name = cname + "=";
      var decodedCookie = decodeURIComponent(document.cookie);
      var ca = decodedCookie.split(';');

      for (let c of ca) {
        while (c.charAt(0) == ' ') {
          c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
          return c.substring(name.length, c.length);
        }
      }
      return "";
    },
  },
  computed: {
    getProfileId() {
      return Number(this.profileId);
    },
    selectedMenuItem() {
      return this.menus.find(menu => menu.component === this.selectedMenu);
    },
    displayedMenus() {
      return this.menus.filter(menu => menu.displayed);
    },
  },
};
</script>

<style scoped>
@import "../assets/css/formbuilder.scss";

.w--current {
  border: solid 1px #eeeeee;
  background: #eeeeee;
}

.w--current:hover {
  color: var(--em-profile-color);
}

.tw-cursor-pointer:hover {
  color: var(--em-profile-color);
}

.em-w-custom {
  width: calc(100% - 75px) !important;
  margin-left: auto;
}

#add-form-next-campaign {
  width: 100%;
}
</style>
