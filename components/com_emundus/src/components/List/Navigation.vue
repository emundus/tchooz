<script>
import Pagination from "@/components/Utils/Pagination.vue";
import Filters from "@/components/List/Filters.vue";
import Actions from "@/components/List/Actions.vue";
import Exports from "@/components/List/Exports.vue";

export default {
  name: "Navigation",
  components: {
    Exports,
    Actions,
    Filters,
    Pagination
  },
  props: {
    tabs: {
      type: Array,
      default: () => []
    },
    filters: {
      type: Object,
      default: () => []
    },
    items: {
      type: Object,
      default: () => {}
    },
    checkedItems: {
      type: Array,
      default: () => []
    },
    views: {
      type: Object,
      default: () => {}
    },
    
    // V-Model
    view: {
      type: String,
      default: 'table'
    },
    searches: {
      type: Object,
      default: () => {}
    },
    tab: {
      type: Object,
      default: () => {}
    },
    tabKey: {
      type: Number,
      default: ''
    },
    numberOfItemsToDisplay: {
      type: [Number, String],
      default: 5
    },
  },
  emits: ['update:views', 'update:searches', 'update:tab', 'update:tabKey', 'update:numberOfItemsToDisplay'],
  data() {
    return {
      currentView: this.view,
      currentSearches: this.searches,
      currentTab: this.tab,
      currentTabKey: this.tabKey,
      currentNumberOfItemsToDisplay: this.numberOfItemsToDisplay,
    };
  },
  methods: {
    onSelectTab(tabKey) {
      let selected = false;

      if (this.currentTabKey !== tabKey) {
        // check if the tab exists
        if (this.tabs.find(tab => tab.key === tabKey) !== 'undefined') {
          this.orderBy = null;
          this.currentTabKey = tabKey;
          this.currentTab = this.tabs.find(tab => tab.key === tabKey);
          sessionStorage.setItem('tchooz_selected_tab/' + document.location.hostname, tabKey);
          selected = true;
        }

        this.$emit('selectTab');
      }

      return selected;
    },

    onClickAction(action) {
      this.$emit('action', action, null, true);
    },
    onClickExport(exp) {
      this.$emit('exp', exp);
    },

    updateItems(page, tabKey) {
      this.$emit('updateItems', page, tabKey);
    },

    onChangeFilter() {
      // when we change a filter, we reset the pagination
      this.$emit('updateItems', 1, this.currentTabKey);
    },

  },
  watch: {
    currentView() {
      this.$emit('update:view', this.currentView);
    },
    currentSearches() {
      this.$emit('update:searches', this.currentSearches);
    },
    currentTab() {
      this.$emit('update:tab', this.currentTab);
    },
    currentTabKey() {
      this.$emit('update:tabKey', this.currentTabKey);
    },
    currentNumberOfItemsToDisplay() {
      this.$emit('update:numberOfItemsToDisplay', this.currentNumberOfItemsToDisplay);
    }
  },
}
</script>

<template>
  <div>
    <nav v-if="tabs.length > 1" id="list-nav">
      <ul class="tw-flex tw-ml-0 tw-pl-0 tw-list-none">
        <li v-for="tab in tabs" :key="tab.key"
            class="tw-cursor-pointer tw-font-normal"
            :class="{
								'em-light-tabs em-light-selected-tab': currentTabKey === tab.key,
								'em-light-tabs ': currentTabKey !== tab.key
							}"
            @click="onSelectTab(tab.key)"
        >
          {{ translate(tab.title) }}
        </li>
      </ul>
    </nav>

    <section id="actions" class="tw-flex tw-items-start tw-justify-between tw-mt-4 tw-mb-4">
      <Filters :filters="filters"
               :currentTabKey="currentTabKey"
               @update-filter="onChangeFilter"
      />

        <Actions :items="items"
                 :checkedItems="checkedItems"
                 :views="views"
                 :tab="currentTab"
                 :tab-key="currentTabKey"
                 v-model:view="currentView"
                 v-model:searches="currentSearches"
                 @action="onClickAction"
                 @exp="onClickExport"
                 @update-items="updateItems"
        />
    </section>

    <Pagination v-if="this.items[this.currentTabKey].length > 0 && view !== 'calendar'"
                :limits="[5, 10, 25, 50, 100, 'all']"
                :dataLength="currentTab.pagination.count"
                v-model:page="currentTab.pagination.current"
                v-model:limit="currentNumberOfItemsToDisplay"
    />
  </div>
</template>

<style scoped>

</style>