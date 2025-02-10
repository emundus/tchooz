<template>
  <transition
    :name="transition"
    :duration="delay"
  >
    <div v-show="isOpened" :id="'modal___' + name" class="modal___container" ref="modal_container" @focusout="onFocusOut">
      <slot></slot>
    </div>
  </transition>
</template>

<script>
export default {
  props: {
    name: {
      type: String,
      required: true
    },
    width: {
      type: String,
      default: '100%'
    },
    height: {
      type: String,
      default: 'auto'
    },
    transition: {
      type: String,
      default: 'fade'
    },
    delay: {
      type: Number,
      default: 0
    },
    clickToClose: {
      type: Boolean,
      default: true
    },
  },
  emits: ['beforeOpen', 'closed'],
  data() {
    return {
      isOpened: false
    }
  },
  mounted() {
    this.open();
  },
  methods: {
    open() {
      this.$emit('beforeOpen');
      this.isOpened = true;

      this.$refs.modal_container.style.width = this.width;
      this.$refs.modal_container.style.height = this.height;
      this.$refs.modal_container.style.zIndex = 999999;
      this.$refs.modal_container.style.opacity = 1;

    },
    close() {
      this.$refs.modal_container.style.zIndex = -999999;
      this.$refs.modal_container.style.opacity = 0;

      this.$emit('closed');
    },
    onFocusOut() {
      if (this.clickToClose) {
        this.close();
      }
    }
  }
}

</script>

<style scoped>
.modal___container {
  position: fixed;
  top: 0;
  left: 0;
  z-index: -999999;
  width: 0;
  height: 0;
  background-color: white;
  opacity: 0;
}
</style>