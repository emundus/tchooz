<template>
  <div class="com_emundus_vue em-flex-col-center">
    <Attachments
        v-if="component === 'attachments'"
        :fnum="data.fnum"
        :user="data.user"
        :defaultAttachments="data.attachments ? data.attachments : null"
        :columns="data.columns"
    ></Attachments>

    <Files
        v-else-if="component === 'files'"
        :type="data.type"
        :user="data.user"
        :ratio="data.ratio"
    ></Files>

    <ApplicationSingle
        v-else-if="component === 'application'"
        :file="data.fnum"
        :type="data.type"
        :user="data.user"
        :ratio="data.ratio"
        :context="data.context || ''"
    ></ApplicationSingle>

    <Comments
        v-else-if="component === 'comments'"
        :ccid="datas.ccid.value"
        :fnum="datas.fnum && datas.fnum.value ? datas.fnum.value : ''"
        :user="datas.user.value"
        :is-applicant="datas.is_applicant && datas.is_applicant.value == 1"
        :current-form="datas.current_form && datas.current_form.value"
        :access="datas.access && datas.access.value ? JSON.parse(datas.access.value) : {
          'c': false,
          'r': true,
          'u': false,
          'd': false
        }"
    >
    </Comments>

    <transition v-else name="slide-right">
      <component v-bind:is="$props.component"/>
    </transition>
  </div>
</template>

<script>
import moment from "moment";

import Attachments from "@/views/Attachments.vue";
import Files from '@/views/Files/Files.vue';
import Comments from '@/components/Files/Comments.vue';
import fileService from "@/services/file.js";
import list_v2 from "@/views/list.vue";
import addcampaign from "@/views/addCampaign.vue";
import addemail from "@/views/addEmail.vue";
import campaignedition from "@/views/CampaignEdition.vue";
import formbuilder from "@/views/formBuilder.vue";
import settings from "@/views/globalSettings.vue";
import messagescoordinator from "@/components/Messages/MessagesCoordinator.vue";
import messages from "@/components/Messages/Messages.vue";
import ApplicationSingle from "@/components/Files/ApplicationSingle.vue";
import TranslationTool from "@/components/Settings/Translation/TranslationTool.vue";

import settingsService from "@/services/settings.js";
import { useGlobalStore } from '@/stores/global.js';

export default {
  props: {
    datas: NamedNodeMap,
    currentLanguage: String,
    shortLang: String,
    manyLanguages: String,
    coordinatorAccess: String,
    sysadminAccess: String,
    defaultLang: {
      type: String,
      default: 'fr'
    },
    component: {
      type: String,
      required: true
    },
    data: {
      type: Object,
      default: () => ({})
    },
  },
  components: {
    ApplicationSingle,
    Attachments,
    addcampaign,
    campaignedition,
    addemail,
    formbuilder,
    settings,
    messagescoordinator,
    messages,
    Files,
    list_v2,
    TranslationTool,
    Comments
  },

  created() {
    const globalStore = useGlobalStore();

    if (this.component === 'attachments') {
      fileService.isDataAnonymized().then(response => {
        if (response.status !== false) {
          globalStore.setAnonyme(response.anonyme);
        }
      });
    }

    if (this.data.attachments) {
      this.data.attachments = JSON.parse(atob(this.data.attachments));
    }

    if (this.data.columns) {
      this.data.columns = JSON.parse(atob(this.data.columns));
    }

    if (typeof this.datas != 'undefined') {
      globalStore.initDatas(this.datas);
    }
    if (typeof this.currentLanguage != 'undefined') {
      globalStore.initCurrentLanguage(this.currentLanguage);

      moment.locale(globalStore.currentLanguage);
    } else {
      globalStore.initCurrentLanguage('fr');
      moment.locale('fr');
    }
    if (typeof this.shortLang != 'undefined') {
      globalStore.initShortLang(this.shortLang);
    }
    if (typeof this.manyLanguages != 'undefined') {
      globalStore.initManyLanguages(this.manyLanguages);
    }
    if (typeof this.defaultLang != 'undefined') {
      globalStore.initDefaultLang(this.defaultLang);
    }
    if (typeof this.coordinatorAccess != 'undefined') {
      globalStore.initCoordinatorAccess(this.coordinatorAccess);
    }
    if (typeof this.coordinatorAccess != 'undefined') {
      globalStore.initSysadminAccess(this.sysadminAccess);
    }

    settingsService.getOffset().then(response => {
      if (response.status !== false) {
        globalStore.initOffset(response.data.data);
      }
    });
  },
  mounted() {
    if (this.data.base) {
      const globalStore = useGlobalStore();

      globalStore.initAttachmentPath(this.data.base + '/images/emundus/files/');
    }
  }
};
</script>

<style lang='scss'>
@import url("./assets/css/main.scss");

.com_emundus_vue {
  margin-bottom: 8px;

  input {
    display: block;
    margin-bottom: 10px;
    padding: var(--em-coordinator-vertical) var(--em-coordinator-horizontal);
    border: 1px solid #cccccc;
    border-radius: 4px;
    -webkit-transition: border-color 200ms linear;
    transition: border-color 200ms linear;
    box-sizing: border-box !important;

    &:hover {
      border-color: #cecece;
    }

    &:focus {
      border-color: #16AFE1;
      -webkit-box-shadow: 0 0 6px #e0f3f8;
      -moz-box-shadow: 0 0 6px #e0f3f8;
      box-shadow: 0 0 6px #e0f3f8;
    }

    &::-webkit-input-placeholder {
      color: #A4A4A4;
    }

    &:-ms-input-placeholder {
      color: #A4A4A4;
    }

    &::-ms-input-placeholder {
      color: #A4A4A4;
    }

    &::placeholder {
      color: #A4A4A4;
    }
  }
}

.view-campaigns.no-layout #g-container-main .g-container,
.view-campaigns.layout-addnextcampaign #g-container-main .g-container,
.view-campaigns.layout-add #g-container-main .g-container,
.view-emails.layout-add #g-container-main .g-container,
.view-emails.no-layout #g-container-main .g-container,
.view-form #g-container-main .g-container,
.view-settings #g-container-main .g-container {
  width: auto;
  position: relative;
}

.view-campaigns.no-layout #g-container-main,
.view-campaigns.layout-addnextcampaign #g-container-main,
.view-campaigns.layout-add #g-container-main,
.view-emails.layout-add #g-container-main,
.view-emails.no-layout #g-container-main,
.view-form #g-container-main {
  padding-left: 76px;
}

#g-page-surround {
  z-index: 1;
}

.swal2-container {
  z-index: 2;
}


.com_emundus.view-settings #g-page-surround #g-container-main {
  padding: 0 !important;
}

.com_emundus.view-settings #g-page-surround #g-container-main .g-container {
  padding: 0 !important;
}

.com_emundus.view-settings #g-footer {
  display: none;
}

.com_emundus.view-settings .com_emundus_vue {
  margin-bottom: 0;
}

</style>
