<script>
import Popover from '@/components/Popover.vue';

export default {
  name: "Actions",
  components: {
    Popover
  },
  props: {
    items: {
      type: Object,
      default: () => {
      }
    },
    checkedItems: {
      type: Array,
      default: () => []
    },
    views: {
      type: Object,
      default: () => {
      }
    },
    tab: {
      type: Object,
      default: () => {
      }
    },
    tabKey: {
      type: Number,
      default: ''
    },

    // V-Model
    view: {
      type: String,
      default: 'table'
    },
    searches: {
      type: Object,
      default: () => {
      }
    },
  },
  emits: ['update:view', 'update:searches'],
  data() {
    return {
      currentView: this.view,
      currentSearches: this.searches,
    }
  },
  methods: {
    evaluateShowOn(showon = null) {
      if (showon === null) {
        return false;
      }

      let items = this.checkedItems;

      let show = [];

      items.forEach((item) => {
        // If item is an id, we get the item from the list
        if (typeof item === 'number') {
          item = this.items[this.tabKey].find(i => i.id === item);
        }
        switch (showon.operator) {
          case '==':
          case '=':
            show.push(item[showon.key] == showon.value);
            break;
          case '!=':
            show.push(item[showon.key] != showon.value);
            break;
          case '>':
            show.push(item[showon.key] > showon.value);
            break;
          case '<':
            show.push(item[showon.key] < showon.value);
            break;
          case '>=':
            show.push(item[showon.key] >= showon.value);
            break;
          case '<=':
            show.push(item[showon.key] <= showon.value);
            break;
          default:
            show.push(true);
        }
      });

      // Return true if all items match the condition
      return show.every(s => s === true);
    },

    searchItems() {
      if (this.currentSearches[this.tabKey].searchDebounce !== null) {
        clearTimeout(this.currentSearches[this.tabKey].searchDebounce);
      }

      if (this.currentSearches[this.tabKey].search === '') {
        sessionStorage.removeItem('tchooz_filter_' + this.tabKey + '_search/' + document.location.hostname);
      } else {
        sessionStorage.setItem('tchooz_filter_' + this.tabKey + '_search/' + document.location.hostname, this.currentSearches[this.tabKey].search);
      }

      this.currentSearches[this.tabKey].searchDebounce = setTimeout(() => {
        if (this.currentSearches[this.tabKey].search !== this.currentSearches[this.tabKey].lastSearch) {
          this.currentSearches[this.tabKey].lastSearch = this.currentSearches[this.tabKey].search;

          // when we are searching through the list, we reset the pagination
          this.$emit('updateItems', 1, this.tabKey);
        }
      }, 500);
    },

    changeViewType(currentView) {
      this.currentView = currentView.value;
      localStorage.setItem('tchooz_view_type/' + document.location.hostname, currentView.value);
    },

    onClickAction(action) {
      this.$emit('action', action);
    },
  },
  computed: {
    multipleActionsPopover() {
      let actions = [];

      if (this.checkedItems.length > 0) {
        actions = this.tab.actions.filter((action) => {
          return action.multiple;
        });
      }

      return actions;
    },
  },
  watch: {
    currentView() {
      this.$emit('update:view', this.currentView);
    },
    currentSearches() {
      this.$emit('update:searches', this.currentSearches);
    },
  }
}
</script>

<template>
  <section id="default-actions" class="tw-flex tw-gap-4">
    <div class="tw-flex tw-items-center tw-gap-2">
      <popover
          v-if="checkedItems.length > 0 && multipleActionsPopover.length > 0"
          :button="translate('COM_EMUNDUS_ONBOARD_ACTIONS')"
          :button-class="'tw-bg-white tw-border tw-h-[38px] hover:tw-border-form-border-hover tw-rounded-form'"
          :icon="'keyboard_arrow_down'"
          :position="'bottom-left'"
          class="custom-popover-arrow">
        <ul class="tw-items-center tw-p-4 tw-list-none tw-m-0">
          <li v-for="action in multipleActionsPopover"
              :key="action.name"
              @click="onClickAction(action)"
              class="tw-py-1.5 tw-px-2"
              :class="{
                      'tw-cursor-not-allowed tw-text-neutral-500': !(typeof action.showon === 'undefined' || evaluateShowOn(action.showon)),
                      'tw-cursor-pointer tw-text-base hover:tw-bg-neutral-300 hover:tw-rounded-coordinator': (typeof action.showon !== 'undefined' && evaluateShowOn(action.showon) || typeof action.showon === 'undefined'),
                    }"
          >
            {{ translate(action.label) }}
          </li>
        </ul>
      </popover>

      <div class="tw-flex tw-items-center tw-min-w-[15rem]"
           v-if="tab.displaySearch === true || typeof tab.displaySearch === 'undefined'">
        <input name="search" type="text" v-model="searches[tabKey].search"
               :placeholder="translate('COM_EMUNDUS_ONBOARD_SEARCH')"
               class="!tw-rounded-coordinator !tw-h-[38px] tw-m-0"
               :class="{
                       'em-disabled-events': items[tabKey].length < 1 && searches[tabKey].search === ''
                     }"
               :disabled="items[tabKey].length < 1 && searches[tabKey].search === ''"
               @change="searchItems" @keyup="searchItems">
        <span class="material-symbols-outlined tw-mr-2 tw-cursor-pointer tw-ml-[-32px]"
              @click="searchItems">
                search
              </span>
      </div>
    </div>

    <div class="view-type tw-flex tw-items-center tw-gap-2">
            <span v-for="viewTypeOption in views" :key="viewTypeOption.value"
                  class="material-symbols-outlined tw-border tw-cursor-pointer tw-p-4 tw-rounded-coordinator !tw-flex tw-items-center tw-justify-center tw-bg-neutral-0 tw-h-[38px] tw-w-[38px]"
                  :class="{
                  'active tw-text-main-500 tw-border-main-500': viewTypeOption.value === currentView,
                  'tw-text-neutral-600 tw-border-neutral-600': viewTypeOption.value !== currentView
                }"
                  @click="changeViewType(viewTypeOption)"
            >{{ viewTypeOption.icon }}</span>
    </div>
  </section>
</template>

<style scoped>

</style>