<template>
  <modal
      v-show="showModal"
      :click-to-close="false"
      id="application-modal"
      name="application-modal"
      :height="'100vh'"
      ref="modal"
      v-if="selectedFile !== null && selectedFile !== undefined"
      :class="{ 'context-files': context === 'files', 'hidden': hidden }"
  >
    <div class="em-modal-header tw-w-full tw-px-3 tw-py-4 tw-bg-profile-full tw-flex tw-items-center">
      <div class="tw-flex tw-items-center tw-justify-between tw-w-full" id="evaluation-modal-close">
        <div class="tw-flex tw-items-center tw-gap-2">
          <div  @click="onClose" class="tw-w-max tw-flex tw-items-center">
               <span class="material-symbols-outlined tw-text-base" style="color: white">navigate_before</span>
              <span class="tw-ml-2 tw-text-neutral-900 tw-text-white tw-text-sm">{{ translate('BACK') }}</span>
          </div>
          <span class="tw-text-white">|</span>
          <p class="tw-text-sm" style="color: white" v-if="selectedFile.applicant_name != ''">
            {{ selectedFile.applicant_name }} - {{ selectedFile.fnum }}
          </p>
          <p class="tw-text-sm" style="color: white" v-else>
            {{ selectedFile.fnum }}
          </p>
        </div>
        <div v-if="fnums.length > 1" class="tw-flex tw-items-center">
          <span class="material-symbols-outlined tw-text-base" style="color:white;" @click="openPreviousFnum">navigate_before</span>
          <span class="material-symbols-outlined tw-text-base" style="color:white;" @click="openNextFnum">navigate_next</span>
        </div>
      </div>
    </div>

    <div class="modal-grid" :style="'grid-template-columns:' + this.ratioStyle" v-if="access">
      <div id="modal-applicationform">
        <div class="scrollable">
          <div class="tw-flex tw-items-center tw-justify-center tw-gap-4 tw-border-b tw-border-neutral-300 sticky-tab em-bg-neutral-100" style="z-index:2;">
            <div v-for="tab in tabsICanAccessTo" :key="tab.name" class="em-light-tabs tw-cursor-pointer"
                 @click="selected = tab.name" :class="selected === tab.name ? 'em-light-selected-tab' : ''">
              <span class="tw-text-sm">{{ translate(tab.label) }}</span>
            </div>
          </div>

          <div v-if="!loading">
            <div v-if="selected === 'application'" v-html="applicationform"></div>
            <Attachments
                v-if="selected === 'attachments'"
                :fnum="selectedFile.fnum"
                :user="$props.user"
                :columns="['check', 'name','date','category','status']"
                :displayEdit="false"
                :key="selectedFile.fnum"
            />
            <Comments
                v-if="selected === 'comments'"
                :fnum="selectedFile.fnum"
                :user="$props.user"
                :access="access['10']"
                :key="selectedFile.fnum"
            />
          </div>
        </div>
      </div>

      <div id="modal-evaluationgrid">
        <iframe v-if="url" :src="url" class="iframe-evaluation" id="iframe-evaluation" @load="iframeLoaded($event);"
                title="Evaluation form"/>
        <div v-else>
          {{ translate('COM_EMUNDUS_EVALUATION_NO_FORM_FOUND') }}
        </div>
        <div class="em-page-loader" v-if="loading"></div>
      </div>
    </div>
  </modal>
</template>

<script>
import axios from "axios";
import Attachments from "@/views/Attachments.vue";
import filesService from '@/services/files.js';
import errors from "@/mixins/errors.js";
import Comments from "@/components/Files/Comments.vue";
import Modal from "@/components/Modal.vue";


export default {
  name: "ApplicationSingle",
  components: {Comments, Attachments, Modal},
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
    context: {
      type: String,
      default: ''
    }
  },
  mixins: [errors],
  data: () => ({
    showModal: true,
    fnums: [],
    selectedFile: null,
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
    hidden: false,
    loading: false
  }),

  created() {
    document.querySelector('body').style.overflow = 'hidden';
    var r = document.querySelector(':root');
    let ratio_array = this.$props.ratio.split('/');
    r.style.setProperty('--attachment-width', ratio_array[0] + '%');

    this.selectedFile = this.file;

    // if props file is not null, then render
    if (typeof this.selectedFile !== 'undefined' && this.selectedFile !== null) {
      this.render();
    } else {
      // hide modal if no file is selected
      this.showModal = false;
    }

    this.addEventListeners();
  },
  onBeforeDestroy() {
    window.removeEventListener('openSingleApplicationWithFnum');
  },

  methods: {
    addEventListeners() {
      window.addEventListener('openSingleApplicationWithFnum', (e) => {
        this.showModal = true;
        if (e.detail.fnum) {
          this.selectedFile = e.detail.fnum;
        }

        if (e.detail.fnums) {
          this.fnums = e.detail.fnums;
        }

        if (typeof this.selectedFile !== 'undefined' && this.selectedFile !== null) {
          this.render();
          if (this.$refs['modal']) {
            this.$refs['modal'].open();
          }
        }
      });
    },
    render() {
      this.loading = true;
      let fnum = '';

      if (typeof this.selectedFile == 'string') {
        fnum = this.selectedFile;
      } else {
        fnum = this.selectedFile.fnum;
      }

      if (typeof this.selectedFile == 'string') {
        filesService.getFile(fnum, this.$props.type).then((result) => {
          if (result.status == 1) {
            this.selectedFile = result.data;
            this.access = result.rights;
            this.selected = 'application';
            this.updateURL(this.selectedFile.fnum)
            this.getApplicationForm();
            if (this.$props.type === 'evaluation') {
              this.getEvaluationForm();
            }

            this.showModal = true;
            this.hidden = false;
            this.loading = false;
          } else {
            this.displayError('COM_EMUNDUS_FILES_CANNOT_ACCESS', result.msg).then((confirm) => {
              if (confirm === true) {
                this.showModal = false;
                this.hidden = true;
              }
            });
            this.loading = false;
          }
        });
      } else {
        filesService.checkAccess(fnum).then((result) => {
          if (result.status == true) {
            this.access = result.data;
            this.updateURL(this.selectedFile.fnum)
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
            this.showModal = true;
            this.hidden = false;
          } else {
            this.displayError('COM_EMUNDUS_FILES_CANNOT_ACCESS', 'COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC').then((confirm) => {
              if (confirm === true) {
                this.showModal = false;
                this.hidden = true;
              }
            });
          }
        }).catch((error) => {
          this.displayError('COM_EMUNDUS_FILES_CANNOT_ACCESS', 'COM_EMUNDUS_FILES_CANNOT_ACCESS_DESC');
          this.loading = false;
        });
      }
    },

    getApplicationForm() {
      axios({
        method: "get",
        url: "index.php?option=com_emundus&view=application&format=raw&layout=form&fnum=" + this.selectedFile.fnum,
      }).then(response => {
        this.applicationform = response.data;
        if (this.$props.type !== 'evaluation') {
          this.loading = false;
        }
      });
    },
    getEvaluationForm() {
      if (this.selectedFile.id != null) {
        this.rowid = this.selectedFile.id;
      }
      if (typeof this.selectedFile.applicant_id != 'undefined') {
        this.student_id = this.selectedFile.applicant_id;
      } else {
        this.student_id = this.selectedFile.student_id;
      }
      let view = 'form';

      filesService.getEvaluationFormByFnum(this.selectedFile.fnum, this.$props.type).then((response) => {
        if (response.data !== 0 && response.data !== null) {
          if (typeof this.selectedFile.id === 'undefined') {
            filesService.getMyEvaluation(this.selectedFile.fnum).then((data) => {
              this.rowid = data.data;
              if (this.rowid == null) {
                this.rowid = "";
              }

              this.url = 'index.php?option=com_fabrik&c=form&view=' + view + '&formid=' + response.data + '&rowid=' + this.rowid + '&jos_emundus_evaluations___student_id[value]=' + this.student_id + '&jos_emundus_evaluations___campaign_id[value]=' + this.selectedFile.campaign + '&jos_emundus_evaluations___fnum[value]=' + this.selectedFile.fnum + '&student_id=' + this.student_id + '&tmpl=component&iframe=1'
            });
          } else {
            this.url = 'index.php?option=com_fabrik&c=form&view=' + view + '&formid=' + response.data + '&rowid=' + this.rowid + '&jos_emundus_evaluations___student_id[value]=' + this.student_id + '&jos_emundus_evaluations___campaign_id[value]=' + this.selectedFile.campaign + '&jos_emundus_evaluations___fnum[value]=' + this.selectedFile.fnum + '&student_id=' + this.student_id + '&tmpl=component&iframe=1'
          }
        }
      });
    },
    iframeLoaded() {
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
    },
    onClose(e) {
      e.preventDefault();
      this.hidden = true;
      this.showModal = false;
      document.querySelector('body').style.overflow= 'visible';
      swal.close();
    },
    openNextFnum() {
      let index = typeof this.selectedFile === 'string' ? this.fnums.indexOf(this.selectedFile) : this.fnums.indexOf(this.selectedFile.fnum);
      if (index !== -1 && index < this.fnums.length - 1) {
        const newIndex = index + 1;
        if (newIndex > this.fnums.length) {
          this.selectedFile = this.fnums[0];
        } else {
          this.selectedFile = this.fnums[newIndex];
        }

        this.render();
      }
    },
    openPreviousFnum() {
      let index = typeof this.selectedFile === 'string' ? this.fnums.indexOf(this.selectedFile) : this.fnums.indexOf(this.selectedFile.fnum);

      if (index !==-1 && index > 0) {
        const newIndex = index - 1;
        if (newIndex < 0) {
          // open last fnum
          this.selectedFile = this.fnums[this.fnums.length - 1];
        } else {
          this.selectedFile = this.fnums[newIndex];
        }
        this.render();
      }
    },
  },
  computed: {
    ratioStyle() {
      let ratio_array = this.$props.ratio.split('/');
      return ratio_array[0] + '% ' + ratio_array[1] + '%';
    },
    tabsICanAccessTo() {
      return this.tabs.filter(tab => this.access[tab.access].r);
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

.context-files:not(.hidden) {
  position: fixed;
  top: 0;
  left: 0;
  background-color: white;
  z-index: 9999;
  width: 100vw;
  height: 100vh;
  opacity: 1;
}

.hidden {
  display: none;
  z-index: -1;
  margin: 0;
  padding: 0;
  width: 0;
  height: 0;
}
</style>