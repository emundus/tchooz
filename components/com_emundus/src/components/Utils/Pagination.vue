<template>
  <div class="tw-flex tw-items-center tw-justify-between tw-py-2 tw-px-3 tw-bg-white"
       :class="stickyClass"
       :style="stickyStyle">
    <div class="tw-ml-2">
      <div class="em-ml-16 em-flex-row">
        <label for="pager-select" class="em-mb-0-important em-mr-4">Afficher</label>
        <select name="pager-select" id="pager-select" class="em-select-no-border" v-model="currentLimit">
          <option v-for="availableLimit in limits" :value="availableLimit">{{ availableLimit }}</option>
        </select>
      </div>
    </div>

    <div class="em-container-pagination-selectPage">
      <ul class="tw-flex tw-items-center tw-gap-1 pagination pagination-sm">
        <li class="tw-flex">
          <a class="tw-cursor-pointer"
             :class="{'disabled': this.currentPage === 1}"
             @click="this.currentPage !== 1 ? this.currentPage -= 1 : null"
          >
            <span class="material-symbols-outlined">navigate_before</span>
          </a>
        </li>
        <li v-for="pageAvailable in Math.ceil(dataLength / limit)"
            :key="pageAvailable"
            :class="{'active': pageAvailable === this.currentPage}"
            @click="this.currentPage = pageAvailable"
            class="tw-cursor-pointer tw-flex">
          <a>{{ pageAvailable }}</a>
        </li>
        <li class="tw-flex">
          <a class="tw-cursor-pointer"
             :class="{'disabled': this.currentPage === Math.ceil(dataLength / limit)}"
             @click="this.currentPage !== Math.ceil(dataLength / limit) ? this.currentPage += 1 : null"
          >
            <span class="material-symbols-outlined">navigate_next</span>
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  name: "Pagination",
  props: {
    limits: {
      type: Array,
      default: () => [5, 10, 25, 50, 100]
    },
    dataLength: {
      type: Number,
      default: 0
    },
    page: {
      type: Number,
      default: 1
    },
    limit: {
      type: Number,
      default: 5
    },
    sticky: {
      type: Boolean,
      default: false
    }
  },
  emits: ['update:page', 'update:limit'],
  data: () => ({
    currentPage: 1,
    currentLimit: 5
  }),
  created() {
    this.currentPage = this.page;
    this.currentLimit = this.limit;
  },
  watch: {
    currentPage() {
      this.$emit('update:page', this.currentPage);
    },
    currentLimit() {
      this.$emit('update:limit', this.currentLimit);
    }
  },
  computed: {
    stickyClass() {
      return this.sticky ? 'tw-sticky tw-border-b tw-border-neutral-400 tw-top-0' : '';
    },
    stickyStyle() {
      let banner = document.querySelector('.alerte-message-container');
      if(banner) {
        let top = banner.offsetHeight;
        return {top: `${top}px`};
      }
    }
  }
}
</script>

<style scoped>
.pagination li a {
  background: hsl(from var(--em-profile-color) h s l / 15%);
  border: none;
  color: var(--em-profile-color) !important;
  border-radius: var(--em-applicant-br);
  padding: 5px 10px;
  font-size: 12px;
  height: 30px;
  width: 30px;
  display: flex;
  justify-content: center;
  align-items: center;
  text-decoration: unset;
}
.pagination li.active a {
  background: var(--em-profile-color);
  color: var(--neutral-0) !important;
}
.pagination li:first-child a, .pagination li:last-child a {
  background: none;
  border-radius: var(--em-applicant-br);
}
.pagination li a:hover {
  background: hsl(from var(--em-profile-color) h s l / 30%);
}
.pagination li a.disabled:hover {
  background: none;
}
.pagination li a.disabled span {
  color: var(--neutral-500) !important;
}
</style>