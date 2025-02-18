<template>
  <div class="em-settings-menu">

    <div class="tw-w-full tw-mb-4">
      <div class="tw-w-5/6">
        <div class="tw-grid tw-grid-cols-3 tw-gap-6 tw-mb-4">
          <multiselect
              v-model="lang"
              label="title_native"
              track-by="lang_code"
              :options="availableLanguages"
              :multiple="false"
              :taggable="false"
              select-label=""
              selected-label=""
              deselect-label=""
              :placeholder="translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SELECT_LANGUAGE')"
              :close-on-select="true"
              :clear-on-select="false"
              :searchable="false"
              :allow-empty="false"
          ></multiselect>
        </div>

        <div class="tw-mb-4 tw-flex tw-items-center" v-if="displayPublishedToggle">
          <div class="em-toggle">
            <input type="checkbox"
                   true-value="1"
                   false-value="0"
                   class="em-toggle-check"
                   id="published"
                   name="published"
                   v-model="form.published"
                   @change="publishArticle()"
            />
            <strong class="b em-toggle-switch"></strong>
            <strong class="b em-toggle-track"></strong>
          </div>
          <span for="published" class="tw-ml-2">{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_CONTENT_PUBLISH') }}</span>
        </div>

        <div class="form-group controls">
          <tip-tap-editor
              v-model="form.content"
              :upload-url="'/index.php?option=com_emundus&controller=settings&task=uploadmedia'"
              :editor-content-height="'30em'"
              :class="'tw-mt-1'"
              :locale="'fr'"
              :preset="'custom'"
              :plugins="editorPlugins"
              :toolbar-classes="['tw-bg-white']"
              :editor-content-classes="['tw-bg-white tw-mb-2']"
              @input="updated = true"
              @paste="updated = true"
          />
        </div>
      <button class="btn btn-primary tw-float-right tw-mt-3" v-if="updated" @click="saveContent">
        {{ translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE") }}
      </button>
      </div>
    <div class="em-page-loader" v-if="loading"></div>
  </div>
</div>
</template>

<script>
/* COMPONENTS */
import Multiselect from 'vue-multiselect';
import TipTapEditor from 'tip-tap-editor'
import 'tip-tap-editor/style.css'
import '../../../../../../templates/g5_helium/css/editor.css'

/* SERVICES */
import client from "@/services/axiosClient";
import translationsService from "@/services/translations";
import mixin from "@/mixins/mixin";
import Swal from "sweetalert2";
import axios from "axios";

import { useSettingsStore } from "@/stores/settings.js";

export default {
  name: "editArticle",

  components: {
    Multiselect,
    TipTapEditor
  },

  props: {
    actualLanguage: {
      type: String,
      default: "fr"
    },
    article_alias: {
      type: String,
      default: null
    },
    article_id: {
      type: Number,
      default: 0
    },
    category: {
      type: String,
      default: null
    },
    published: {
      type: Number,
      default: 1
    },
    name: {
      default: null
    },
    displayPublishedToggle: {
      type: Boolean,
      default: true
    }
  },

  mixins: [mixin],
  data() {
    return {
      defaultLang: null,
      availableLanguages: [],
      editorPlugins: ['history', 'link', 'image', 'bold', 'italic', 'underline','left','center','right','h1', 'h2', 'ul'],

      lang: null,
      loading: false,
      dynamicComponent: 0,
      updated: false,

      form: {
        published: this.$props.published,
        content: '',
        need_notify: false,
      },
      previousContent: '',
      initContent:'',
      clearNotif: false,
    };
  },

  created() {
    this.loading = true;
    translationsService.getDefaultLanguage().then((response) => {
      this.defaultLang = response;
      this.getAllLanguages();
      this.loading = false;
    });
  },

  methods: {
    async getArticle() {
      let params = {
        article_id: this.$props.article_id,
        lang: this.lang.lang_code,
        field: 'introtext',
      }
      if (this.$props.article_alias !== null) {
        params = {
          article_alias: this.$props.article_alias,
          lang: this.lang.lang_code,
          field: 'introtext',
        }
      }

      await client().get("index.php?option=com_emundus&controller=settings&task=getarticle", {
        params: params
      }).then(response => {
        this.form.content = response.data.data.introtext;
        this.form.published = response.data.data.published;
        this.dynamicComponent++;
      });
    },

    async saveContent() {
      const formData = new FormData();

      formData.append('content', this.form.content);
      formData.append('lang', this.lang.lang_code);
      if (this.$props.article_alias !== null) {
        formData.append('article_alias', this.$props.article_alias);
      } else {
        formData.append('article_id', this.$props.article_id);
      }
      formData.append('field', 'introtext');
      if (this.clearNotif) {
        formData.append('note', '');
      }
      await client().post(`index.php?option=com_emundus&controller=settings&task=updatearticle`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }
      ).then(async () => {
        this.updated = false;
        Swal.fire({
          title: this.translate("COM_EMUNDUS_ONBOARD_SUCCESS"),
          text: this.translate("COM_EMUNDUS_ONBOARD_SETTINGS_GENERAL_SAVE_SUCCESS"),
          showCancelButton: false,
          showConfirmButton: false,
          customClass: {
            title: 'em-swal-title'
          },
          timer: 1500,
        });
      });
    },

    async getAllLanguages() {
      await translationsService.getAllLanguages().then((response) => {
        this.availableLanguages = response;
        this.lang = this.defaultLang;
      })
    },

    async publishArticle() {
      this.$emit('updateSaving', true);

      const formData = new FormData();
      formData.append('publish', this.form.published);
      if (this.$props.article_alias !== null) {
        formData.append('article_alias', this.$props.article_alias);
      } else {
        formData.append('article_id', this.$props.article_id);
      }

      await client().post(`index.php?option=com_emundus&controller=settings&task=publisharticle`,
        formData,
        {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        }
      ).then(() => {
        this.$emit('updateSaving', false);
        this.$emit('updateLastSaving', this.formattedDate('', 'LT'));
        this.$emit('updatePublished', this.form.published);
      });
    },

    async saveMethod() {
      await this.saveContent();
      return true;
    },

    async updateArticleNotif(){
      const response = await axios.get('index.php?option=com_emundus&controller=settings&task=updateArticleNeedToModify', {
        params: {
          article_alias: this.$props.article_alias,
        }
      });
      delete response.data.msg;
      return response.data;
    }
  },


  watch: {
    lang: function () {
      if (this.lang !== null) {
        this.getArticle();
      } else {
        this.form.content = '';
        this.dynamicComponent++;
      }
    },
    updated: function (val) {
      this.$emit('needSaving', val , this.$props.article_alias);
    },
    'form.content': {
      handler: function (newVal) {
        if(this.initContent === ''){
          this.initContent = newVal;
        }
        if (  this.previousContent !== newVal) {
          if (this.initContent !== newVal) {
            this.clearNotif = true;
            this.previousContent = newVal;
            useSettingsStore().updateNeedSaving(1);
          } else {
            useSettingsStore().updateNeedSaving(0);
            this.clearNotif = false;
            this.updated = false;
          }
        }
      },
      immediate: true,
    },
  }
};
</script>
<style scoped>
</style>
