<template>
  <modal
      id="application-modal"
      name="application-modal"
      v-show="showModal"
      :height="'100vh'"
      :width="'100vw'"
      styles="display:flex;flex-direction:column;justify-content:center;align-items:center;"
      @before-open="beforeOpen"
      @before-close="updateURL()"
      @closed="$emit('getFiles')"
  >
    <div class="em-modal-header tw-w-full tw-h-2/4 tw-px-3 tw-py-4 tw-bg-main-900 tw-flex tw-items-center">
      <div class="tw-flex tw-items-center tw-cursor-pointer tw-gap-2" id="evaluation-modal-close">
        <div class="tw-w-max tw-flex tw-items-center" @click="showModal = false">
          <span class="material-symbols-outlined tw-text-base" style="color: white">arrow_back</span>
        </div>
        <span class="tw-text-neutral-500">|</span>
        <p class="tw-text-sm" style="color: white" v-if="file.applicant_name != ''">
          {{ file.applicant_name }} - {{ file.fnum }}
        </p>
        <p class="tw-text-sm" style="color: white" v-else>
          {{ file.fnum }}
        </p>
      </div>
    </div>

    <div class="modal-grid" :style="'grid-template-columns:' + this.ratioStyle" v-if="access">
      <div id="modal-applicationform">
        <div class="scrollable">
          <div class="tw-flex tw-items-center tw-justify-center tw-gap-4 tw-border-b tw-border-neutral-300 sticky-tab">
            <div v-for="tab in tabsICanAccessTo" :key="tab.name" class="em-light-tabs tw-cursor-pointer"
                 @click="selected = tab.name" :class="selected === tab.name ? 'em-light-selected-tab' : ''">
              <span class="tw-text-sm">{{ translate(tab.label) }}</span>
            </div>
          </div>

          <div v-for="tab in tabs" :key="tab.name">
            <div v-if="tab.name === 'application' && selected === 'application'" v-html="applicationform"></div>
            <Attachments
                v-if="tab.name === 'attachments' && selected === 'attachments'"
                :fnum="file.fnum"
                :user="$props.user"
                :columns="['check', 'name','date','category','status']"
                :displayEdit="false"
            />
            <Comments
                v-if="tab.name === 'comments' && selected === 'comments'"
                :fnum="file.fnum"
                :user="$props.user"
                :access="access['10']"
            />
          </div>
        </div>
      </div>

      <Evaluations
          v-if="selectedFile"
          :fnum="typeof selectedFile === 'string' ? selectedFile : selectedFile.fnum"
          :key="typeof selectedFile === 'string' ? selectedFile : selectedFile.fnum"
      >
      </Evaluations>
    </div>
  </modal>
</template>

<script>
import axios from "axios";
import Attachments from "@/views/Attachments.vue";
import filesService from '@/services/files.js';
import errors from "@/mixins/errors.js";
import Comments from "@/views/Comments.vue";
import Modal from '@/components/Modal.vue';
import Evaluations from "@/components/Files/Evaluations.vue";


export default {
  name: "Application",
  components: {Evaluations, Comments, Attachments, Modal},
  props: {
    file: Object | String,
    type: String,
    user: {
      type: String,
      required: true,
    },
    ratio: {
      type: String,
      default: '66/33'
    },
    defaultTabs: {
      type: Array,
      default: () => [
        {
          label: 'COM_EMUNDUS_FILES_APPLICANT_FILE',
          name: 'application',
          access: '1'
        },
        {
          label: 'COM_EMUNDUS_FILES_ATTACHMENTS',
          name: 'attachments',
          access: '4'
        },
        {
          label: 'COM_EMUNDUS_FILES_COMMENTS',
          name: 'comments',
          access: '10'
        },
      ]
    }
  },
  mixins: [errors],
  data: () => ({
    applicationform: '',
    selected: 'application',
    tabs: [
      {
        label: 'COM_EMUNDUS_FILES_APPLICANT_FILE',
        name: 'application',
        access: '1'
      },
      {
        label: 'COM_EMUNDUS_FILES_ATTACHMENTS',
        name: 'attachments',
        access: '4'
      },
      {
        label: 'COM_EMUNDUS_FILES_COMMENTS',
        name: 'comments',
        access: '10'
      },
    ],
    evaluation_form: 0,
    url: null,
    access: null,
    student_id: null,
    showModal: true,
    loading: false
  }),

  methods: {
    beforeOpen() {
      if (document.querySelector('body.layout-evaluation')) {
        document.querySelector('body.layout-evaluation').style.overflow = 'hidden';
      }

      const r = document.querySelector(':root');
      let ratio_array = this.$props.ratio.split('/');
      r.style.setProperty('--attachment-width', ratio_array[0] + '%');

      this.loading = true;
      let fnum = '';

      if (typeof this.$props.file == 'string') {
        fnum = this.$props.file;
      } else {
        fnum = this.$props.file.fnum;
      }

      if (typeof this.$props.file == 'string') {
        filesService.getFile(fnum).then((result) => {
          if (result.status == 1) {
            this.$props.file = result.data;
            this.access = result.rights;
            this.updateURL(this.$props.file.fnum)
            this.getApplicationForm();
            if (this.$props.type === 'evaluation') {
              this.getEvaluationForm();
            }
          } else {
            this.displayError('COM_EMUNDUS_FILES_CANNOT_ACCESS', 'COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC').then((confirm) => {
              if (confirm === true) {
                this.showModal = false;
              }
            });
          }
        });
      } else {
        filesService.checkAccess(fnum).then((result) => {
          if (result.status == true) {
            this.access = result.data;
            this.updateURL(this.$props.file.fnum)
            if (this.access['1'].r) {
              this.getApplicationForm();
            } else {
              if (this.access['4'].r) {
                this.selected = 'attachments';
              } else if (this.access['10'].r) {
                this.selected = 'comments';
              }
            }
            if (this.$props.type === 'evaluation') {
              this.getEvaluationForm();
            }
          } else {
            this.displayError('COM_EMUNDUS_FILES_CANNOT_ACCESS','COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC'
            ).then((confirm) => {
              if (confirm === true) {
                this.showModal = false;
              }
            });
          }
        });
      }
    },
    getApplicationForm() {
      axios({
        method: "get",
        url: "index.php?option=com_emundus&view=application&format=raw&layout=form&fnum=" + this.file.fnum,
      }).then(response => {
        this.applicationform = response.data;
        if (this.$props.type !== 'evaluation') {
          this.loading = false;
        }
      });
    },
    getEvaluationForm() {
      if (this.$props.file.id != null) {
        this.rowid = this.$props.file.id;
      }
      if (typeof this.$props.file.applicant_id != 'undefined') {
        this.student_id = this.$props.file.applicant_id;
      } else {
        this.student_id = this.$props.file.student_id;
      }
      let view = 'form';

      filesService.getEvaluationFormByFnum(this.$props.file.fnum).then((response) => {
        if (response.data !== 0) {
          if (typeof this.$props.file.id === 'undefined') {
            filesService.getMyEvaluation(this.$props.file.fnum).then((data) => {
              this.rowid = data.data;
              if (this.rowid == null) {
                this.rowid = "";
              }

              this.url = 'index.php?option=com_fabrik&c=form&view=' + view + '&formid=' + response.data + '&rowid=' + this.rowid + '&jos_emundus_evaluations___student_id[value]=' + this.student_id + '&jos_emundus_evaluations___campaign_id[value]=' + this.$props.file.campaign + '&jos_emundus_evaluations___fnum[value]=' + this.$props.file.fnum + '&student_id=' + this.student_id + '&tmpl=component&iframe=1'

              setTimeout(() => {
                this.loading = false;
              }, 5000);
            });
          } else {
            this.url = 'index.php?option=com_fabrik&c=form&view=' + view + '&formid=' + response.data + '&rowid=' + this.rowid + '&jos_emundus_evaluations___student_id[value]=' + this.student_id + '&jos_emundus_evaluations___campaign_id[value]=' + this.$props.file.campaign + '&jos_emundus_evaluations___fnum[value]=' + this.$props.file.fnum + '&student_id=' + this.student_id + '&tmpl=component&iframe=1'
            setTimeout(() => {
              this.loading = false;
            }, 5000);
          }
        }
      });
    },
    iframeLoaded(event) {
      this.loading = false;
    },
    updateURL(fnum = '') {
      let url = window.location.href;
      url = url.split('#');

      if (fnum === '') {
        window.history.pushState('', '', url[0]);
      } else {
        window.history.pushState('', '', url[0] + '#' + fnum);
      }
    }
  },
  computed: {
    ratioStyle() {
      let ratio_array = this.$props.ratio.split('/');
      return ratio_array[0] + '% ' + ratio_array[1] + '%';
    },
    tabsICanAccessTo() {
      return this.tabs.filter(tab => this.access[tab.access] && this.access[tab.access].r);
    }
  }
}
</script>

<style>
.modal-grid {
  display: grid;
  grid-gap: 16px;
  width: 100%;
  height: 100vh;
}

.scrollable {
  height: calc(100vh - 100px);
  overflow-y: scroll;
  overflow-x: hidden;
}

.em-container-form-heading {
  display: none;
}

#iframe {
  height: 100vh;
  overflow-y: scroll;
  overflow-x: hidden;
}

.iframe-evaluation {
  width: 100%;
  height: calc(100% - 124px);
  border: unset;
}

#modal-evaluationgrid {
  border-left: 1px solid #EBECF0;
  box-shadow: 0 4px 16px rgba(32, 35, 44, 0.1);
  padding: 24px;
}

.sticky-tab {
  position: sticky;
  top: 0;
  background: white;
}

#modal-applicationform #em-attachments .v--modal-overlay {
  height: 100% !important;
  width: var(--attachment-width) !important;
  margin-top: 50px;
}

#modal-applicationform #em-attachments .v--modal-box.v--modal {
  width: 100% !important;
  height: calc(100vh - 50px) !important;
  box-shadow: unset;
}

#modal-applicationform #em-attachments .modal-body {
  width: 100%;
}

#modal-applicationform #em-attachments #em-attachment-preview {
  width: 100%;
}
</style>