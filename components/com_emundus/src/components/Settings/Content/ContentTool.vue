<template>
  <span :id="'contentTool'">
    <modal
        :name="'contentTool'"
        height="auto"
        transition="fade"
        :delay="100"
        :adaptive="true"
        :clickToClose="false"
        @closed="beforeClose"
    >
      <div class="em-modal-header">
        <div class="tw-justify-between tw-flex tw-items-center tw-cursor-pointer" @click.prevent="$modal.hide('contentTool')">
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
          <div v-for="menu in menus" :key="'menu_' + menu.index"
               @click="currentMenu = menu.index"
               class="translation-menu-item tw-p-4 tw-flex tw-items-center tw-justify-between pointer"
               :class="currentMenu === menu.index ? 'em-modal-menu__current' : ''"
          >
            <p class="tw-text-base">{{ translate(menu.title) }}</p>
          </div>
        </div>

        <transition name="fade" mode="out-in" v-if="selectedMenu">
          <EditArticle v-if="selectedMenu.type === 'article'"
                       :key="currentMenu"
                       :article_id="selectedMenu.id" :article_alias="selectedMenu.alias"
                       :category="selectedMenu.category" :published="selectedMenu.published"
                       class="em-modal-component"
                       @updateSaving="updateSaving"
                       @updateLastSaving="updateLastSaving"
                       @updatePublished="updatePublished"
          ></EditArticle>
          <EditFooter v-else-if="selectedMenu.type === 'footer'" class="em-modal-component" @updateSaving="updateSaving"
                      @updateLastSaving="updateLastSaving"></EditFooter>
        </transition>
      </div>

      <div v-if="loading">
      </div>
    </modal>
  </span>
</template>

<script>
/* COMPONENTS */
import EditArticle from "./EditArticle";
import EditFooter from "./EditFooter";
import client from "com_emundus/src/services/axiosClient";
import mixin from "com_emundus/src/mixins/mixin";

export default {
  name: "contentTool",
  props: {},
  components: {EditFooter, EditArticle},
  mixins: [mixin],
  data() {
    return {
      currentMenu: 1,
      menus: [],

      loading: false,
      saving: false,
      last_save: null,
    }
  },
  created() {
    let index = 1;

    client().get("index.php?option=com_emundus&controller=settings&task=gethomearticle").then(response => {
      this.menus.push({
        type: "article",
        id: response.data.data,
        title: "COM_EMUNDUS_ONBOARD_CONTENT_TOOL_HOMEPAGE",
        index: index,
        category: 'homepage',
        published: 1
      });

      client().get("index.php?option=com_emundus&controller=settings&task=getrgpdarticles").then(response => {
        response.data.data.forEach((article) => {
          index++;
          if (article.id) {
            this.menus.push({
              type: "article",
              id: parseInt(article.id),
              title: article.title,
              index: index,
              category: 'rgpd',
              published: article.published
            });
          } else {
            this.menus.push({
              type: "article",
              alias: article.alias,
              title: article.title,
              index: index,
              category: 'rgpd',
              published: article.published
            });
          }
        });

        index++;
        this.menus.push({
          type: "footer",
          title: "COM_EMUNDUS_ONBOARD_CONTENT_TOOL_FOOTER",
          index: index
        });
      });
    });
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
    },

    updatePublished(published) {
      this.menus.forEach((menu) => {
        if (menu.index === this.currentMenu) {
          menu.published = Number(published);
        }
      });
    }
  },
  computed: {
    selectedMenu() {
      return this.menus.find(menu => menu.index === this.currentMenu);
    }
  }
}
</script>

<style scoped lang="scss">
@media all and (max-width: 959px) {
  .view-settings .em-modal-menu__sidebar .translation-menu-item p {
    word-break: break-word;
    hyphens: auto;
  }
}
</style>
