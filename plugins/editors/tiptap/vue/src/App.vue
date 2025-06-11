<template>
  <tip-tap-editor
      v-if="ready"
      v-model="content"
      :upload-url="'/index.php?option=com_emundus&controller=settings&task=uploadmedia'"
      :editor-content-height="'30em'"
      :class="'tw-mt-1'"
      :locale="'fr'"
      :preset="preset"
      :plugins="plugins"
      :suggestions="editorSuggestions"
  />
</template>

<script>
import TipTapEditor from 'tip-tap-editor'
import 'tip-tap-editor/tip-tap-editor.css'
import '../../../../../templates/g5_helium/css/editor.css'

export default {
  components: {
    TipTapEditor
  },
  props: {
    // Textarea element linked to the editor
    textareaId: {
      type: Number,
      required: true
    },
    // Enable suggestions
    enableSuggestions: {
      type: Boolean,
      default: false
    },
    // List of suggestions
    suggestions: {
      type: Array,
      default: () => []
    },
    // List of plugins to use
    plugins: {
      type: Array,
      default: () => []
    }
  },
  data: () => ({
    ready: false,
    content: '',
    editorSuggestions: [],
    preset: 'basic',

    textareaElement: null
  }),
  mounted() {
    this.textareaElement = document.getElementById(this.$props.textareaId);
    this.content = this.textareaElement.value;

    this.textareaElement.addEventListener('input', () => {
      this.content = this.textareaElement.value;
    });

    if(this.$props.plugins.length > 0) {
      this.preset = 'custom';
    }

    if(this.$props.enableSuggestions) {
      if (this.$props.suggestions.length === 0) {
        this.getSuggestions();
      } else {
        this.editorSuggestions = this.$props.suggestions;
      }
    } else {
      this.ready = true;
    }
  },
  methods: {
    async getSuggestions() {
      fetch('/index.php?option=com_emundus&controller=settings&task=geteditorvariables')
          .then(response => response.json())
          .then(data => {
            if(data.status) {
              this.editorSuggestions = data.data;
            }
            this.ready = true;
          });
    }
  },
  watch: {
    content: {
      handler: function (value) {
        this.textareaElement.value = value;
      }
    }
  }
}
</script>

<style scoped>
</style>
