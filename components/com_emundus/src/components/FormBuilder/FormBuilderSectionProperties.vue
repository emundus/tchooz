<template>
  <div id="form-builder-element-properties">
    <div class="tw-flex tw-items-center tw-justify-between tw-p-4">
      <p>{{ translate("COM_EMUNDUS_FORM_BUILDER_SECTION_PROPERTIES") }}</p>
      <span class="material-icons-outlined tw-cursor-pointer" @click="$emit('close')">close</span>
    </div>
    <ul id="properties-tabs" class="tw-flex tw-items-center tw-justify-between tw-p-4 tw-w-11/12">
      <li
          v-for="tab in publishedTabs"
          :key="tab.id"
          :class="{ 'is-active': tab.active, 'tw-w-2/4': publishedTabs.length == '2', 'tw-w-full': publishedTabs.length == 1 }"
          class="tw-p-4 tw-cursor-pointer"
          @click="selectTab(tab)"
      >
        {{ translate(tab.label) }}
      </li>
    </ul>
    <div id="properties">
      <div v-if="tabs[0].active" id="section-parameters" class="tw-p-4">
        <label for="section-label">{{ translate('COM_EMUNDUS_FORM_BUILDER_SECTION_LABEL') }}</label>
        <input id="section-label" name="section-label" class="tw-w-full" type="text" v-model="section_tmp.label"/>
      </div>
      <div v-if="tabs[1].active" class="tw-p-4">
        <form-builder-section-params :params="params" :section="section_tmp"></form-builder-section-params>
      </div>
    </div>
    <div class="tw-flex tw-items-center tw-justify-between actions tw-m-4">
      <button class="em-primary-button" @click="saveProperties()">
        {{ translate("COM_EMUNDUS_FORM_BUILDER_SECTION_PROPERTIES_SAVE") }}
      </button>
    </div>
  </div>
</template>

<script>
import formBuilderService from '../../services/formbuilder';
import sectionParams from '../../../data/form-builder/form-builder-groups-params.json'
import FormBuilderSectionParams from "./FormBuilderSections/FormBuilderSectionParams";


export default {
  name: 'FormBuilderSectionProperties',
  components: {FormBuilderSectionParams},
  props: {
    section_id: {
      type: Number,
      required: true
    },
    profile_id: {
      type: Number,
      required: true
    },
  },
  data() {
    return {
      section_tmp: {},
      params: [],
      tabs: [
        {
          id: 0,
          label: "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL",
          active: false,
          published: false,
        },
        {
          id: 1,
          label: "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_PARAMETERS",
          active: true,
          published: true,
        }
      ]
    };
  },
  created() {
    this.paramsAvailable();
    this.getSection();
  },
  methods: {
    saveProperties() {
      formBuilderService.updateGroupParams(this.section_tmp.id, this.section_tmp.params, this.shortDefaultLang).then(() => {
        this.$emit('close');
      });
    },
    toggleHidden() {
      this.section_tmp.params.hidden = !this.section_tmp.hidden;
    },
    selectTab(tab) {
      this.tabs.forEach(t => {
        t.active = false;
      });
      tab.active = true;
    },
    paramsAvailable() {
      if (typeof sectionParams['parameters'] !== 'undefined') {
        this.tabs[1].published = true;
        this.params = sectionParams['parameters'];
      } else {
        this.tabs[1].active = false;
        this.tabs[0].active = true;
        this.tabs[1].published = false;
      }
    },
    getSection() {
      formBuilderService.getSection(this.$props.section_id).then((response) => {
        this.section_tmp = response.data.group;
      });
    }
  },
  computed: {
    sysadmin: function () {
      return parseInt(this.$store.state.global.sysadminAccess);
    },
    publishedTabs() {
      return this.tabs.filter((tab) => {
        return tab.published;
      });
    }
  },
  watch: {
    section: function () {
      this.paramsAvailable();
      this.getSection();
    }
  }
}
</script>

<style lang="scss">
#properties-tabs {
  list-style-type: none;
  margin: auto;
  align-items: center;

  li {
    text-align: center;
    border-bottom: 2px solid #EDEDED;
    transition: all .3s;

    &.is-active {
      border-bottom: 2px solid black;
    }
  }

}
</style>
