<template>
  <span :id="'filesTool'">
    <modal
        :name="'filesTool'"
        height="auto"
        transition="fade"
        :delay="100"
        :adaptive="true"
        :clickToClose="false"
        @closed="beforeClose"
    >
      <div class="em-modal-header">
        <div class="tw-justify-between tw-flex tw-items-center tw-cursor-pointer" @click.prevent="$modal.hide('filesTool')">
          <div class="tw-w-max tw-flex tw-items-center">
            <span class="material-icons-outlined">navigate_before</span>
            <span class="tw-ml-2 tw-text-neutral-900">{{ translate('COM_EMUNDUS_ONBOARD_ADD_RETOUR') }}</span>
          </div>
          <div v-if="saving" class="tw-flex tw-items-center tw-justify-start">
            <div class="em-loader tw-mr-2"></div>
            <p class="tw-text-sm tw-flex tw-items-center">{{
                translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_PROGRESS')
              }}</p>
          </div>
          <p class="tw-text-sm"
             v-if="!saving && last_save != null">{{ translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_TRANSLATIONS_AUTOSAVE_LAST') + last_save }}</p>
        </div>
      </div>

      <div class="em-modal-content">
        <div class="em-modal-menu__sidebar">
          <div v-for="menu in menus" :key="'menu_' + menu.index" @click="currentMenu = menu.index"
               class="translation-menu-item tw-p-4 tw-flex tw-items-center tw-justify-between pointer"
               :class="currentMenu === menu.index ? 'em-modal-menu__current' : ''">
            <p class="tw-text-base">{{ translate(menu.title) }}</p>
          </div>
        </div>

        <transition name="fade" mode="out-in">
          <EditStatus v-if="currentMenu === 1" :key="currentMenu" class="em-modal-component"
                      @updateSaving="updateSaving" @updateLastSaving="updateLastSaving"/>
          <EditTags v-if="currentMenu === 2" :key="currentMenu" class="em-modal-component" @updateSaving="updateSaving"
                    @updateLastSaving="updateLastSaving"/>
          <EditApplicants v-if="currentMenu === 3" :key="currentMenu" :type="'general'" class="em-modal-component" @updateSaving="updateSaving" @updateLastSaving="updateLastSaving" />
          <EditApplicants v-if="currentMenu === 4" :key="currentMenu" :type="'applicants'" class="em-modal-component" @updateSaving="updateSaving" @updateLastSaving="updateLastSaving" />
        </transition>
      </div>

      <div v-if="loading">
      </div>
    </modal>
  </span>
</template>

<script>
/* COMPONENTS */
import EditStatus from "./EditStatus";
import EditTags from "./EditTags";
import EditApplicants from "./EditApplicants";

export default {
  name: "filesTool",
  props: {},
  components: {EditApplicants, EditTags, EditStatus},
  data() {
    return {
      currentMenu: 1,
      menus: [
        {
          title: "COM_EMUNDUS_ONBOARD_SETTINGS_MENU_STATUS",
          index: 1
        },
        {
          title: "COM_EMUNDUS_ONBOARD_SETTINGS_MENU_TAGS",
          index: 2
        },
        {
          title: "COM_EMUNDUS_ONBOARD_SETTINGS_MENU_GENERAL",
          index: 3
        },
        {
          title: "COM_EMUNDUS_ONBOARD_SETTINGS_MENU_APPLICANTS",
          index: 4
        },
      ],

      loading: false,
      saving: false,
      last_save: null,
    }
  },
  methods: {
    beforeClose(event) {
      this.$emit('resetMenuIndex');
    },


    updateSaving(saving) {
      this.saving = saving;
    },

    updateLastSaving(last_save) {
      this.last_save = last_save;
    }
  }
}
</script>

<style scoped lang="scss">
</style>
