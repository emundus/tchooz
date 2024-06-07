<template>
  <div id="comments">
    <div class="tw-flex tw-flex-col tw-justify-center tw-mt-6">
      <div v-for="comment in comments" class="em-input-card tw-w-2/4 tw-mb-4" :key="comment.id">
        <div class="tw-flex tw-items-center tw-justify-between">
          <div>
            <p>{{ comment.user }}</p>
            <span class="tw-text-neutral-500">{{ formattedDate(comment.date, 'LLLL', '+0200') }}</span>
          </div>
          <div v-click-outside="hideOptions">
            <span class="material-icons-outlined tw-cursor-pointer" @click="show_options = comment.id">more_vert</span>
            <div v-if="show_options === comment.id" class="em-comment-option">
              <span class="tw-cursor-pointer comment-delete"
                    v-if="$props.access.d || ($props.access.c && comment.user_id == $props.user)"
                    @click="deleteComment(comment.id)">{{ translate('COM_EMUNDUS_FILES_COMMENT_DELETE') }}</span>
            </div>
          </div>
        </div>

        <hr/>
        <div class="comment-content">
          <strong class="tw-mb-2">{{ comment.reason }}</strong>
          <p style="word-break: break-all;">{{ comment.comment_body }}</p>
        </div>
      </div>
    </div>

    <div class="tw-flex tw-items-center tw-justify-center tw-mt-6" v-if="adding_comment">
      <div class="em-input-card tw-w-2/4">
        <div>
          <span class="material-icons-outlined tw-cursor-pointer tw-float-right tw-mb-1"
                @click="adding_comment = false">close</span>
        </div>


        <div class="tw-mb-2">
          <label for="reason">{{ translate('COM_EMUNDUS_FILES_COMMENT_TITLE') }}</label>
          <input class="tw-w-full" id="reason" type="text" v-model="comment.reason"/>
        </div>

        <div>
          <label class="tw-mb-1" for="body">{{ translate('COM_EMUNDUS_FILES_COMMENT_BODY') }}</label>
          <textarea id="body" v-model="comment.comment_body"/>
        </div>

        <div class="tw-mt-2">
          <button class="em-primary-button !tw-w-auto tw-float-right" @click="saveComment">
            {{ translate('COM_EMUNDUS_FILES_VALIDATE_COMMENT') }}
          </button>
        </div>

      </div>
    </div>

    <div v-if="$props.access.c && !adding_comment" class="tw-flex tw-items-center tw-justify-center tw-mt-8">
      <button class="em-primary-button !tw-w-auto" @click="adding_comment = true;">
        {{ translate('COM_EMUNDUS_FILES_ADD_COMMENT') }}
      </button>
    </div>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
import filesService from 'com_emundus/src/services/files';
import mixins from '../../mixins/mixin';
import errors from '../../mixins/errors';

export default {
  name: "Comments",
  props: {
    user: {
      type: String,
      required: true,
    },
    fnum: {
      type: String,
      required: true,
    },
    access: {
      type: Object,
      required: true,
    },
  },
  mixins: [mixins, errors],
  data: () => ({
    comments: [],
    comment: {
      reason: '',
      comment_body: '',
    },

    loading: false,
    adding_comment: false,
    show_options: false,
  }),
  created() {
    this.getComments();
  },
  methods: {
    getComments() {
      this.loading = true;
      filesService.getComments(this.$props.fnum).then((response) => {
        if (response.status == 1) {
          this.comments = response.data;
          this.loading = false;
        } else {
          this.displayError(
              'COM_EMUNDUS_FILES_CANNOT_GET_COMMENTS',
              'COM_EMUNDUS_FILES_CANNOT_GET_COMMENTS_DESC'
          );
          this.loading = false;
        }
      });
    },

    saveComment() {
      this.loading = true;
      filesService.saveComment(this.$props.fnum, this.comment).then((response) => {
        if (response.status == 1) {
          this.comments.push(response.data);
          this.comment = {reason: '', comment_body: ''};
          this.adding_comment = false;
          this.loading = false;
        } else {
          this.displayError(
              'COM_EMUNDUS_FILES_CANNOT_SAVE_COMMENT',
              response.msg
          );
          this.loading = false;
        }
      });
    },

    deleteComment(cid) {
      let deleted = false;

      if ((this.access.d || (this.access.c && comment.user_id == this.user)) && cid !== null && cid > 0) {
        this.loading = true;
        filesService.deleteComment(cid).then((response) => {
          if (response.status == 1) {
            deleted = true;
            this.comments.splice(this.comments.findIndex(v => v.id === cid), 1);
            this.loading = false;
          } else {
            this.displayError(
                'COM_EMUNDUS_FILES_CANNOT_GET_COMMENTS',
                'COM_EMUNDUS_FILES_CANNOT_GET_COMMENTS_DESC'
            );
            this.loading = false;
          }
        });
      }

      return deleted;
    },

    hideOptions() {
      this.show_options = false;
    }
  }
}
</script>

<style scoped>
#comments .em-input-card {
  position: relative;
}

.em-comment-option {
  position: absolute;
  border-radius: var(--em-coordinator-br);
  padding: 12px 16px;
  height: auto;
  background: #fff;
  border: 1px solid #E3E3E3;
}
</style>