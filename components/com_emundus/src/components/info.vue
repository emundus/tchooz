<template>

  <div :class="[bgColor]"  class="tw-rounded tw-flex tw-items-center tw-gap-2 tw-px-5 tw-py-6">
    <span :class="[iconType,iconColor]" class="tw-mr-3">{{ icon }}</span>
    <div v-html="textValueExtracted" :class="[textColor]"></div>
  </div>


</template>

<script>

export default {
  name: "info",
  components: {},
  props: {
    text: {
      type: String,
      required: true,
    },
    bgColor: {
      type: String,
      default: 'tw-bg-blue-100'
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
  watch: {
    text: function (val) {
      this.textValueExtracted = this.translate(val);
    },
  },
}
</script>

<style scoped>

</style>