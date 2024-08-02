<template>
  <span :id="'translationTool'">
    <modal
        :name="'translationTool'"
        height="auto"
        transition="fade"
        :delay="100"
        :adaptive="true"
        :clickToClose="false"
        @opened="checkSetup"
        @closed="beforeClose"
    >
      <div class="em-modal-header">
        <div class="tw-justify-between tw-flex tw-items-center tw-cursor-pointer" @click.prevent="beforeClose">
          <div class="tw-w-max tw-flex tw-items-center">
            <span class="material-icons-outlined tw-text-neutral-600">navigate_before</span>
            <span class="tw-ml-2 tw-text-neutral-900">{{ translate('COM_EMUNDUS_ONBOARD_ADD_RETOUR') }}</span>
          </div>
          <div v-if="saving" class="tw-flex tw-items-center tw-justify-start">
            <div class="em-loader tw-mr-2"></div>
            <p class="tw-text-sm tw-flex tw-items-center">{{ translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_PROGRESS') }}</p>
          </div>
          <p class="tw-text-sm" v-if="!saving && last_save != null">{{ translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_LAST') + last_save}}</p>
        </div>
      </div>

      <div class="em-modal-content">
        <div class="em-modal-menu__sidebar">
          <div v-for="(menu) in menus" :key="'menu_' + menu.index" @click="currentMenu = menu.index" class="translation-menu-item tw-p-4 tw-flex tw-items-center tw-justify-between pointer" :class="currentMenu === menu.index ? 'em-modal-menu__current' : ''">
            <p class="tw-text-base">{{translate(menu.title)}}</p>
            <div v-if="menu.index === 3 && orphelins_count > 0" class="em-notifications-yellow"></div>
          </div>
        </div>

        <transition name="fade">
          <Global v-if="currentMenu === 1" v-show="!setup_success" class="em-modal-component"
                  @updateOrphelinsCount="updateOrphelinsCount"></Global>
          <Translations v-else-if="currentMenu === 2" v-show="!setup_success" class="em-modal-component"
                        @updateSaving="updateSaving" @updateLastSaving="updateLastSaving"></Translations>
          <Orphelins v-else-if="currentMenu === 3" v-show="!setup_success" class="em-modal-component"></Orphelins>
        </transition>

        <img v-if="setup_success" alt="checked-animation" class="em-success-animation" :src="'/images/emundus/animations/checked.gif'" />
      </div>

      <div v-if="loading">
        <div class="em-page-loader" v-if="!setup_success"></div>
        <p class="em-page-loader-text" v-if="!setup_success">{{ translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SETUP_PROGRESSING') }}</p>
        <p class="em-page-loader-text em-fade-loader" v-if="setup_success">{{ translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SETUP_SUCCESS') }}</p>
      </div>
    </modal>
  </span>
</template>

<script>
import Global from "./Global.vue";
import Translations from "./Translations.vue";
import Orphelins from "./Orphelins.vue";

import translationsService from "@/services/translations";
import {useGlobalStore} from "@/stores/global.js";

export default {
  name: "translationTool",
  props: {
    showModalOnLoad: {
      type: Number,
      default: 0
    },
    defaultMenuIndex: {
      type: Number,
      default: 1
    }
  },
  components: {Orphelins, Translations, Global},
  data() {
    return {
      orphelins_count: 0,
      currentMenu: 0,
      menus: [
        {
          title: "COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_GLOBAL",
          index: 1
        },
        {
          title: "COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS",
          index: 2
        },
        {
          title: "COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_ORPHELINS",
          index: 3
        },
      ],

      loading: false,
      setup_success: false,
      saving: false,
      last_save: null,
    }
  },
  created() {
    const data = useGlobalStore().datas;

    if (this.showModalOnLoad === 0) {
      if (data.showModalOnLoad !== undefined) {
        this.showModalOnLoad = Number(data.showModalOnLoad.value) || 0;
      }
    }

    if (data.hasOwnProperty('defaultMenuIndex')) {
      this.defaultMenuIndex = Number(data.defaultMenuIndex.value) || 1;
    }

    if (this.showModalOnLoad > 0) {
      setTimeout(() => {
        this.$modal.show('translationTool');
      }, 1000);
    }
  },
  methods:{
    beforeClose() {
      const data = useGlobalStore().datas;
      if (data.hasOwnProperty('redirectOnClose')) {
        window.location.href = data.redirectOnClose.value;
      }

      this.$emit('resetMenuIndex');
    },

    updateOrphelinsCount(count) {
      this.orphelins_count = count;
    },

    checkSetup(){
      translationsService.checkSetup().then((response) => {
        if(response.data === 0){
          this.loading = true;
          translationsService.configureSetup().then((result) => {
            if(result.data === 1){
              this.loading = false;
              this.setup_success = true;
              this.currentMenu = this.defaultMenuIndex;
              setTimeout(() => {
                this.setup_success = false;
              },2700)
            }
          });
        } else {
          this.currentMenu = this.defaultMenuIndex;
        }
      })
    },

    updateSaving(saving){
      this.saving = saving;
    },

    updateLastSaving(last_save){
      this.last_save = last_save;
    }
  }
}
</script>

<style scoped lang="scss">
</style>
