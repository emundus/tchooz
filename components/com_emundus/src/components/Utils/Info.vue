<template>

  <div :class="[bgColor,borderColor]"  class="tw-rounded tw-flex tw-items-start tw-gap-2 tw-px-5 tw-py-6 tw-border">
    <span v-if="displayIcon" :class="[iconType,iconColor]">{{ icon }}</span>
    <div v-html="textValueExtracted" :class="[textColor]"></div>
  </div>


</template>

<script>

export default {
  name: "Info",
  components: {},
  props: {
    text: {
      type: String,
      required: true,
    },
    bgColor: {
      type: String,
      default: 'tw-bg-blue-50'
    },
    icon: {
      type: String,
      default: 'info'
    },
    iconColor: {
      type: String,
      default: 'tw-text-blue-500'
    },
    iconType: {
      type: String,
      default: 'material-symbols-outlined'
    },
    displayIcon: {
      type: Boolean,
      default: true
    },
    textColor: {
      type: String,
      default: 'tw-text-neutral-900'
    },
  },

  mixins: [],

  data() {
    return {
      textValueExtracted: '',
      loading : false
    }
  },
  created() {
    this.loading = true;
    this.textValueExtracted = this.translate(this.text);
    this.loading= false;

  },
  mounted() {},
  methods: {},
  computed: {
    borderColor() {
      return this.iconColor.replace('text', 'border');
    },
  },
  watch: {
    text: function (val) {
      this.textValueExtracted = this.translate(val);
    },
  },
}
</script>

<style scoped>

</style>