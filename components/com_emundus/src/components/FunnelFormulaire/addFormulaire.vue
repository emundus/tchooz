<template>
  <div id="addFormulaireContent">
    <div class="tw-mb-1 tw-mt-4 em-text-color">{{ translate("COM_EMUNDUS_ONBOARD_CHOOSE_FORM") }} :</div>
    <div class="tw-mb-6 tw-flex tw-flex-col tw-items-start">
      <select id="select_profile" class="!tw-mb-1" v-model="selectedProfileId" @change="updateProfileCampaign">
        <option v-for="profile in profiles" :key="profile.id" :value="profile.id">
          {{ profile.form_label }}
        </option>
      </select>
      <a id="editCurrentForm" class="tw-cursor-pointer em-profile-color em-text-underline" @click="formbuilder">{{ translate('COM_EMUNDUS_ONBOARD_EDIT_FORM') }}</a>
    </div>

    <a id="addNewForm" class="tw-cursor-pointer em-profile-color tw-underline" @click="addNewForm">{{ translate('COM_EMUNDUS_ONBOARD_NO_FORM_FOUND_ADD_FORM') }}</a>

    <hr/>
    <h5>{{ translate('COM_EMUNDUS_FORM_PAGES_PREVIEW') }}</h5>
    <div id="formPagesReview" class="tw-flex tw-items-center em-flex-wrap">
      <div v-for="form in fabrikFormList" :key="form.id"
           class="card-wrapper em-mr-32"
           :title="form.label"
      >
        <form-builder-preview-form
            :form_id="Number(form.id)"
            :form_label="form.label"
            class="card em-shadow-cards model-preview"
        ></form-builder-preview-form>
      </div>
    </div>

    <div id="formAttachments" v-if="documentsList.length > 0">
      <h5 class="em-mt-12">{{ translate('COM_EMUNDUS_FORM_ATTACHMENTS_PREVIEW') }}</h5>
      <div class="tw-flex tw-items-center">
        <div v-for="document in documentsList" :key="document.id"
             class="card-wrapper em-mr-32"
             :title="document.label"
        >
          <form-builder-preview-attachments
              :document_id="Number(document.id)"
              :document_label="document.label"
              class="card em-shadow-cards model-preview"
          ></form-builder-preview-attachments>
        </div>
      </div>
    </div>


    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
import FormBuilderPreviewForm from '@/components/FormBuilder/FormBuilderPreviewForm.vue';
import FormBuilderPreviewAttachments from '@/components/FormBuilder/FormBuilderPreviewAttachments.vue';
import settingsService from '@/services/settings.js';
import formService from '@/services/form.js';
import campaignService from '@/services/campaign.js';

export default {
  name: 'addFormulaire',

  props: {
    profileId: String,
    campaignId: Number,
    profiles: Array,
    formulaireEmundus: Number,
    visibility: Number
  },
  components: {
    FormBuilderPreviewAttachments,
    FormBuilderPreviewForm,
  },

  data() {
    return {
      selectedProfileId: 0,
      EmitIndex: '0',
      formList: [],
      documentsList: [],
      loading: false,

      form: {
        label: 'Nouveau formulaire',
        description: '',
        published: 1
      },
    };
  },
  created() {
    this.selectedProfileId = this.profileId;
    this.getForms(this.selectedProfileId);
    this.getDocuments(this.selectedProfileId);
  },
  methods: {
    getEmitIndex(value) {
      this.EmitIndex = value;
    },
    getForms(profile_id) {
      this.loading = true;

      formService.getFormsByProfileId(profile_id)
        .then(response => {
          this.formList = response.data.data;
          this.loading = false;
        })
        .catch(e => {
          console.log(e);
        });
    },

    getDocuments(profile_id) {
      formService.getDocuments(profile_id).then(response => {
        this.documentsList = response.data;
      });
    },

    redirectJRoute(link) {
      settingsService.redirectJRoute(link);
    },

    addNewForm() {
      this.loading = true;

      formService.createForm({body: JSON.stringify(this.form)}).then(response => {
        this.loading = false;
        this.$props.profileId = response.data;
        window.location.href = '/' + response.redirect;
      }).catch(error => {
        console.log(error);
      });
    },

    updateProfileCampaign() {
      campaignService.updateProfile(this.selectedProfileId, this.campaignId).then(() => {
        this.getForms(this.selectedProfileId);
        this.getDocuments(this.selectedProfileId);
        this.$emit('profileId', this.selectedProfileId);
      });
    },

    formbuilder(index) {
      index = 0;
      this.redirectJRoute('index.php?option=com_emundus&view=form&layout=formbuilder&prid=' +
          this.selectedProfileId +
          '&index=' +
          index +
          '&cid=' +
          this.campaignId)
    },
  },
  computed: {
    fabrikFormList() {
      return this.formList.filter(form => form.link.includes('fabrik'));
    },
  }
};
</script>

<style scoped lang='scss'>
.card-wrapper {
  width: 150px;

  .em-shadow-cards {
    background-color: white;
    width: 150px;
    border: 2px solid transparent;
  }

  .card {
    margin: 24px 0 12px 0;
  }

  p {
    text-align: center;
    border-radius: 4px;
    padding: 4px;
    transition: all .3s;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 12px;
  }

  input {
    width: 200px;
    height: 20px;
    font-size: 12px;
    border: 0;
    text-align: center;
  }

  &.selected {
    .em-shadow-cards {
      border: 2px solid #20835F;
    }

    p, input {
      color: white !important;
      background-color: #20835F !important;
    }
  }
}

#select_profile {
  min-width: 250px;
  width: max-content;
  max-width: 350px;
}
</style>

