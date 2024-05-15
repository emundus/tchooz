<template>
  <div>
    <div v-for="translation in translations_rows" class="tw-mb-8 em-neutral-100-box em-p-24">
      <div v-for="(field,index) in translation" class="tw-mb-6">
        <p>{{ field.reference_label ? field.reference_label.toUpperCase() : field.reference_id }}</p>
        <div class="tw-justify-between tw-mt-4 em-grid-50 em-ml-24">
          <p class="tw-text-neutral-700">{{ field.default_lang }}</p>
          <input v-if="field.field_type === 'field'" class="mb-0 em-input tw-w-full" type="text" :value="field.lang_to"
                 @focusout="saveTranslation($event.target.value,field)"/>
          <textarea v-if="field.field_type === 'textarea'" class="mb-0 em-input" :value="field.lang_to"
                    @focusout="saveTranslation($event.target.value,field)"/>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import Multiselect from "vue-multiselect";
import mixin from "com_emundus/src/mixins/mixin";
import translationsService from "com_emundus/src/services/translations";

export default {
  name: "TranslationRow",
  components: {
    Multiselect
  },
  mixins: [mixin],
  props: {
    section: Object,
    translations: Array
  },
  data() {
    return {
      translations_rows: {},
      key_fields: [],
    }
  },
  created() {
    this.initTranslations();
  },
  methods: {
    initTranslations() {
      this.key_fields = Object.keys(this.$props.section.indexedFields);

      Object.values(this.$props.translations).forEach((translations_reference) => {
        Object.values(translations_reference).forEach((translation) => {
          if (this.key_fields.includes(translation.reference_field) && translation.reference_table === this.$props.section.Name) {
            translation.reference_field_order = this.key_fields.indexOf(translation.reference_field);
            translation.reference_label = this.$props.section.indexedFields[translation.reference_field].Label;
            translation.field_type = this.$props.section.indexedFields[translation.reference_field].Type;
            // For FALANG translation we need this
            if (!translation.hasOwnProperty('tag')) {
              translation.tag = translation.reference_field;
            }
            //
            if (!this.translations_rows.hasOwnProperty(translation.reference_id)) {
              this.translations_rows[translation.reference_id] = [];
            }
            this.translations_rows[translation.reference_id].push(translation);
          }
        })
      })

      Object.values(this.translations_rows).forEach((translation_reference) => {
        translation_reference.sort((a, b) => (a.reference_field_order > b.reference_field_order) ? 1 : -1);
      });
    },

    async saveTranslation(value, translation) {
      this.$emit('saveTranslation', {value: value, translation: translation});
    }
  },
}
</script>

<style scoped>

</style>
