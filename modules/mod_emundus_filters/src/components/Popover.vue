<template>
  <div :id="'popover-' + uniqID">
    <slot :close="close">
      <button class="popover-opener">{{ translate(this.btnText) }}</button>
      <div class="popover-content">
        <p> {{ this.translate(this.defaulText)}} </p>
      </div>
    </slot>
  </div>
</template>

<script>
export default {
  name: "Popover",
  props: {
    placement: {
      type: String,
      default: "bottom-end"
    },
    distance: {
      type: Number,
      default: 10
    },
    opener: {
      type: String,
      default: ".popover-opener"
    },
    content: {
      type: String,
      default: ".popover-content"
    },
    uniqID: {
      type: String,
      default: () => Math.random().toString(36).substring(7)
    },
    btnText: {
      type: String,
      default: "MOD_EMUNDUS_FILTERS_POPOVER_DEFAULT_BTN"
    },
    defaulText: {
      type: String,
      default: "MOD_EMUNDUS_FILTERS_POPOVER_DEFAULT_TEXT"
    }
  },
  data() {
    return {
      isOpen: false
    }
  },
  mounted() {
    this.init();
  },
  beforeUnmount() {
    document.removeEventListener("click", this.hide);
  },
  methods: {
    init() {
      this.hide();
      if (this.popoverWrapper) {
        this.popoverWrapper.position = "relative";
      }

      if (this.popoverOpener) {
        this.popoverOpener.addEventListener("click", () => {
          if (this.isOpen) {
            this.hide();
          } else {
            this.show();
          }
        });
      }

      if (this.popoverContent) {
        this.popoverContent.classList.add("tw-bg-white");
        this.popoverContent.classList.add("tw-shadow");
        this.popoverContent.classList.add("tw-rounded");
        this.popoverContent.classList.add("tw-border");
      }

      this.addEventListeners();
    },
    addEventListeners() {
      // close popover when clicking outside
      document.addEventListener("click", (e) => {
        if (this.isOpen && !this.popoverContent.contains(e.target) && !this.popoverOpener.contains(e.target)) {
          this.hide();
        }
      });
    },
    hide() {
      if (this.popoverContent) {
        this.popoverContent.style.display = "none";
        this.isOpen = false;
      }
    },
    show() {
      // set popover position
      this.popoverContent.style.position = "absolute";
      this.popoverContent.style.zIndex = 9999;
      this.popoverContent.style.top = this.getOffset(this.popoverOpener).top + "px";
      this.popoverContent.style.right = "0px";
      this.popoverContent.style.display = "block";
      this.isOpen = true;
    },
    close() {
      this.hide();
    },
    getOffset(el) {
      return {
        left: el.offsetLeft,
        top: el.offsetTop + el.offsetHeight + this.distance
      };
    }
  },
  computed: {
    popoverWrapper() {
      return document.querySelector("#popover-" + this.uniqID);
    },
    popoverOpener() {
      return this.popoverWrapper ? this.popoverWrapper.querySelector(this.opener) : null;
    },
    popoverContent() {
      return this.popoverWrapper ? this.popoverWrapper.querySelector(this.content) : null;
    }
  }
}
</script>

<style scoped>
</style>