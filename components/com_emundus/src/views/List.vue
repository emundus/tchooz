<template>
  <div id="onboarding_list" class="tw-w-full" :class="{'alert-banner-displayed': alertBannerDisplayed}">
    <skeleton v-if="loading.lists" height="40px" width="100%" class="tw-mb-4 tw-mt-4 tw-rounded-lg"></skeleton>
    <div v-else class="head tw-flex tw-items-center tw-justify-between">
      <h1>{{ translate(currentList.title) }}</h1>
      <a v-if="addAction" id="add-action-btn" class="tw-btn-primary tw-w-auto tw-cursor-pointer"
         @click="onClickAction(addAction)">{{ translate(addAction.label) }}</a>
    </div>

    <div v-if="loading.tabs" id="tabs-loading">
      <div class="tw-flex tw-justify-between">
        <skeleton height="40px" width="20%" class="tw-mb-4 tw-rounded-lg"></skeleton>
        <skeleton height="40px" width="5%" class="tw-mb-4 tw-rounded-lg"></skeleton>
      </div>
      <div :class="{'skeleton-grid': viewType === 'blocs','tw-flex tw-flex-col': viewType === 'list'}"
           style="flex-wrap: wrap">
        <skeleton v-for="i in 9" :key="i" class="tw-rounded-lg skeleton-item"></skeleton>
      </div>
    </div>
    <div v-else class="list tw-mt-4">
      <nav v-if="currentList.tabs.length > 1" id="list-nav">
        <ul style="list-style-type: none;margin-left:0; padding-left: 0" class="tw-flex">
          <li v-for="tab in currentList.tabs" :key="tab.key"
              class="tw-cursor-pointer tw-font-normal"
              :class="{
								'em-light-tabs em-light-selected-tab': selectedListTab === tab.key,
								'em-light-tabs ': selectedListTab !== tab.key
							}"
              @click="onSelectTab(tab.key)"
          >
            {{ translate(tab.title) }}
          </li>
        </ul>
      </nav>
      <section id="actions" class="tw-flex tw-justify-between tw-mt-4 tw-mb-4">
        <section id="tab-actions">
          <select v-for="filter in displayedFilters" :key="selectedListTab + '-' + filter.key"
                  v-model="filter.value" @change="onChangeFilter(filter)" class="tw-mr-2">
            <option v-for="option in filter.options" :key="option.value" :value="option.value">
              {{ translate(option.label) }}
            </option>
          </select>
        </section>

        <section id="default-actions" class="tw-flex">
          <div class="tw-flex tw-items-center">
            <input name="search" type="text" v-model="searches[selectedListTab].search"
                   :placeholder="translate('COM_EMUNDUS_ONBOARD_SEARCH')"
                   class="tw-rounded-lg"
                   :class="{'em-disabled-events': items[this.selectedListTab].length < 1 && searches[selectedListTab].search === ''}"
                   style="margin: 0;"
                   :disabled="items[this.selectedListTab].length < 1 && searches[selectedListTab].search === ''"
                   @change="searchItems" @keyup="searchItems">
            <span class="material-symbols-outlined tw-mr-2 tw-cursor-pointer" style="margin-left: -32px"
                  @click="searchItems">
							search
						</span>
          </div>
          <div class="view-type tw-flex tw-items-center">
					<span v-for="viewTypeOption in viewTypeOptions" :key="viewTypeOption.value"
                style="padding: 4px;border-radius: calc(var(--em-default-br)/2);display: flex;height: 38px;width: 38px;align-items: center;justify-content: center;background: var(--neutral-0);"
                class="material-symbols-outlined tw-ml-2 tw-cursor-pointer"
                :class="{
								'active em-main-500-color em-border-main-500': viewTypeOption.value === viewType,
								'em-neutral-600-color em-border-neutral-600': viewTypeOption.value !== viewType
							}"
                @click="changeViewType(viewTypeOption)"
          >{{ viewTypeOption.icon }}</span>
          </div>
        </section>
      </section>

      <section id="pagination-wrapper" class="tw-flex tw-justify-end tw-items-center tw-mb-3"
               v-if="this.items[this.selectedListTab].length > 0">
        <select name="numberOfItemsToDisplay" v-model="numberOfItemsToDisplay" @change="getListItems()">
          <option value='10'>{{ translate('COM_EMUNDUS_ONBOARD_RESULTS') }} 10</option>
          <option value='25'>{{ translate('COM_EMUNDUS_ONBOARD_RESULTS') }} 25</option>
          <option value='50'>{{ translate('COM_EMUNDUS_ONBOARD_RESULTS') }} 50</option>
          <option value='all'>{{ translate('ALL') }}</option>
        </select>
        <div
            v-if="typeof currentTab.pagination !== undefined && currentTab.pagination && currentTab.pagination.total > 1"
            id="pagination" class="tw-text-center">
          <ul class="tw-flex tw-list-none tw-gap-1">
						<span :class="{'tw-text-neutral-600 em-disabled-events': currentTab.pagination.current === 1}"
                  class="material-symbols-outlined tw-cursor-pointer tw-mr-2 tw-items-center"
                  style="display: flex"
                  @click="getListItems(currentTab.pagination.current - 1, selectedListTab)">
							chevron_left
						</span>
            <li v-for="i in currentTab.pagination.total" :key="i"
                class="tw-cursor-pointer em-square-button"
                :class="{'active': i === currentTab.pagination.current}"
                @click="getListItems(i, selectedListTab)">
              {{ i }}
            </li>
            <span
                :class="{'tw-text-neutral-600 em-disabled-events': currentTab.pagination.current === currentTab.pagination.total}"
                class="material-symbols-outlined tw-cursor-pointer tw-ml-2 tw-items-center"
                style="display: flex"
                @click="getListItems(currentTab.pagination.current + 1, selectedListTab)">
							chevron_right
						</span>
          </ul>
        </div>
      </section>


      <div v-if="loading.items"
           id="items-loading"
           :class="{'skeleton-grid': viewType === 'blocs','tw-flex tw-flex-col tw-mb-4': viewType === 'list'}"
           style="flex-wrap: wrap"
      >
        <skeleton v-for="i in 9" :key="i" class="tw-rounded-lg skeleton-item"></skeleton>
      </div>

      <div v-else>
        <div v-if="displayedItems.length > 0" id="list-items">
          <table v-if="viewType != 'gantt'"  id="list-table" :class="{'blocs': viewType === 'blocs'}">
            <thead>
            <tr>
              <th>{{ translate('COM_EMUNDUS_ONBOARD_LABEL_' + currentTab.key.toUpperCase()) == ('COM_EMUNDUS_ONBOARD_LABEL_' + currentTab.key.toUpperCase()) ?
                  translate('COM_EMUNDUS_ONBOARD_LABEL') : translate('COM_EMUNDUS_ONBOARD_LABEL_' + currentTab.key.toUpperCase()) }}</th>
              <th v-for="column in additionalColumns" :key="column"> {{ column }}</th>
              <th v-if="tabActionsPopover && tabActionsPopover.length > 0">{{
                  translate('COM_EMUNDUS_ONBOARD_ACTIONS')
                }}
              </th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="item in displayedItems"
                :key="item.id"
                :id="'item-' + currentTab.key + '-' + item.id"
                class="em-border-cards table-row"
                :class="{'em-card-neutral-100 em-card-shadow em-p-24' : viewType === 'blocs'}"
            >
              <td class="tw-cursor-pointer" @click="onClickAction(editAction, item.id)">
                <span :class="{'tw-font-semibold tw-mb-4 tw-text-ellipsis tw-overflow-hidden':  viewType === 'blocs'}"
                      :title="item.label[params.shortlang]">{{ item.label[params.shortlang] }}</span>
              </td>
              <td class="columns" v-for="column in displayedColumns(item, viewType)" :key="column.key">
                <div v-if="column.type === 'tags'" class="tw-flex tw-items-center tw-flex-wrap tw-gap-2"
                     :class="column.classes">
                  <span v-for="tag in column.values" :key="tag.key" class="tw-mr-2 tw-h-max" :class="tag.classes"
                        v-html="tag.value"></span>
                </div>
                <div v-else-if="column.hasOwnProperty('long_value')">
                  <span @click="displayLongValue(column.long_value)" class="tw-mt-2 tw-mb-2" :class="column.classes"
                        v-html="column.value"></span>
                </div>
                <span v-else class="tw-mt-2 tw-mb-2" :class="column.classes" v-html="column.value"></span>
              </td>
              <div>
                <hr v-if="viewType === 'blocs'" class="tw-w-full tw-mt-1.5 tw-mb-3">
                <td class="actions">
                  <a v-if="viewType === 'blocs' && editAction" @click="onClickAction(editAction, item.id)"
                     class="tw-btn-primary tw-text-sm tw-cursor-pointer tw-w-auto">
                    {{ translate(editAction.label) }}
                  </a>
                  <div class="tw-flex tw-items-center tw-gap-2">
                    <span v-if="previewAction" class="material-symbols-outlined tw-cursor-pointer"
                          @click="onClickPreview(item)">visibility</span>
                    <span v-for="action in iconActions" :key="action.name" class="tw-cursor-pointer"
                          :class="{
															'material-symbols-outlined': action.iconOutlined,
															'material-icons': !action.iconOutlined,
															'tw-hidden': !(typeof action.showon === 'undefined' || evaluateShowOn(item, action.showon))
														}"
                          @click="onClickAction(action, item.id)"
                    >
												{{ action.icon }}
											</span>
                    <popover
                        :position="'left'"
                        v-if="tabActionsPopover && tabActionsPopover.length > 0 && filterShowOnActions(tabActionsPopover, item).length"
                        class="custom-popover-arrow">
                      <ul style="list-style-type: none; margin: 0; padding-left:0px;" class="em-flex-col-center">
                        <li v-for="action in tabActionsPopover"
                            :key="action.name"
                            :class="{'tw-hidden': !(typeof action.showon === 'undefined' || evaluateShowOn(item, action.showon))}"
                            @click="onClickAction(action, item.id)"
                            class="tw-cursor-pointer tw-p-2 tw-text-base"
                        >
                          {{ translate(action.label) }}
                        </li>
                      </ul>
                    </popover>
                  </div>
                </td>
              </div>
            </tr>
            </tbody>
          </table>
          <Gantt v-else
            :language="params.shortlang"
            :periods="displayedItems"
          ></Gantt>

        </div>
        <div v-else id="empty-list" class="noneDiscover tw-text-center" v-html="noneDiscoverTranslation"></div>
      </div>

    </div>
  </div>
</template>

<script>
import {ref} from 'vue';
// Components
import Skeleton from '@/components/Skeleton.vue';
import Popover from '@/components/Popover.vue';
import Gantt from '@/components/Gantt/Gantt.vue';

// Services
import settingsService from '@/services/settings.js';
import Swal from 'sweetalert2';
import {useGlobalStore} from "@/stores/global.js";

export default {
  name: 'list',
  components: {
    Skeleton,
    Popover,
    Gantt
  },
  props: {
    defaultLists: {
      type: String,
      default: null
    },
    defaultType: {
      type: String,
      default: null
    }
  },
  data() {
    return {
      loading: {
        'lists': false,
        'tabs': false,
        'items': false,
      },
      numberOfItemsToDisplay: 25,
      lists: {},
      type: 'forms',
      params: {},
      currentList: {'title': '', 'tabs': []},
      selectedListTab: 0,
      items: {},
      title: '',
      viewType: 'table',
      viewTypeOptions: [
        {value: 'table', icon: 'dehaze'},
        {value: 'blocs', icon: 'grid_view'},
        /*{value: 'gantt', icon: 'view_timeline'}*/
      ],
      searches: {},
      filters: {},
      alertBannerDisplayed: false
    }
  },
  created() {
    const alertMessageContainer = document.querySelector('.alerte-message-container');
    if (alertMessageContainer) {
      this.alertBannerDisplayed = true;

      alertMessageContainer.querySelector('#close-preprod-alerte-container').addEventListener('click', () => {
        this.alertBannerDisplayed = false;
      });
    }

    this.loading.lists = true;
    this.loading.tabs = true;

    const globalStore = useGlobalStore();
    if (this.defaultType !== null) {
      this.params = {
        'type': this.defaultType,
        'shortlang': globalStore.getShortLang
      };
    } else {
      const data = globalStore.getDatas;
      this.params = Object.assign({}, ...Array.from(data).map(({name, value}) => ({[name]: value})));
    }
    this.type = this.params.type;

    this.viewType = localStorage.getItem('tchooz_view_type/' + document.location.hostname)
    if (this.viewType === null || typeof this.viewType === 'undefined' || (this.viewType !== 'blocs' && this.viewType !== 'table')) {
      this.viewType = 'blocs';
      localStorage.setItem('tchooz_view_type/' + document.location.hostname, 'blocs');
    }
    const storageNbItemsDisplay = localStorage.getItem('tchooz_number_of_items_to_display/' + document.location.hostname);
    if (storageNbItemsDisplay !== null) {
      this.numberOfItemsToDisplay = storageNbItemsDisplay !== 'all' ? parseInt(storageNbItemsDisplay) : storageNbItemsDisplay;
    }

    this.initList();
  },
  methods: {
    initList() {
      if (this.defaultLists !== null) {
        this.lists = JSON.parse(atob(this.defaultLists));
        if (typeof this.lists[this.type] === 'undefined') {
          console.error('List type ' + this.type + ' does not exist');
          window.location.href = '/';
        }

        this.currentList = this.lists[this.type];
        if (Object.prototype.hasOwnProperty.call(this.params, 'tab')) {
          this.onSelectTab(this.params.tab);
        } else {
          const sessionTab = sessionStorage.getItem('tchooz_selected_tab/' + document.location.hostname);
          if (sessionTab !== null && this.currentList.tabs.some(tab => tab.key === sessionTab)) {
            this.onSelectTab(sessionTab)
          } else {
            this.onSelectTab(this.currentList.tabs[0].key)
          }
        }

        this.loading.lists = false;
        this.getListItems();
      } else {
        this.getLists();
      }
    },
    getLists() {
      settingsService.getOnboardingLists().then(response => {
        if (response.status) {
          this.lists = response.data;

          if (typeof this.lists[this.type] === 'undefined') {
            console.error('List type ' + this.type + ' does not exist');
            window.location.href = '/';
          }

          this.currentList = this.lists[this.type];
          if (Object.prototype.hasOwnProperty.call(this.params, 'tab')) {
            this.onSelectTab(this.params.tab);
          } else {
            const sessionTab = sessionStorage.getItem('tchooz_selected_tab/' + document.location.hostname);
            if (sessionTab !== null && typeof this.currentList.tabs.find(tab => tab.key === sessionTab) !== 'undefined') {
              this.onSelectTab(sessionTab)
            } else {
              this.onSelectTab(this.currentList.tabs[0].key)
            }
          }

          this.loading.lists = false;

          this.getListItems();
        } else {
          console.error('Error while getting onboarding lists');
          this.loading.lists = false;
        }
      });
    },
    getListItems(page = 1, tab = null) {
      if (tab === null) {
        this.loading.tabs = true;
        this.items = ref(Object.assign({}, ...this.currentList.tabs.map(tab => ({[tab.key]: []}))));
      } else {
        this.loading.items = true;
      }

      const tabs = tab === null ? this.currentList.tabs : [this.currentTab];
      if (tabs.length > 0) {
        tabs.forEach(tab => {
          if (typeof this.searches[tab.key] === 'undefined') {
            this.searches[tab.key] = {
              search: '',
              lastSearch: '',
              debounce: null
            };
          }

          // Init search value from sessionStorage
          const searchValue = sessionStorage.getItem('tchooz_filter_' + this.selectedListTab + '_search/' + document.location.hostname);
          if (searchValue !== null && this.searches[this.selectedListTab]) {
            this.searches[this.selectedListTab].search = searchValue;
            this.searches[this.selectedListTab].lastSearch = searchValue;
          }

          this.setTabFilters(tab);
          if (typeof tab.getter !== 'undefined') {
            let url = 'index.php?option=com_emundus&controller=' + tab.controller + '&task=' + tab.getter + '&lim=' + this.numberOfItemsToDisplay + '&page=' + page;
            if (this.searches[tab.key].search !== '') {
              url += '&recherche=' + this.searches[tab.key].search;
            }
            if (typeof this.filters[tab.key] !== 'undefined') {
              this.filters[tab.key].forEach(filter => {
                if (filter.value !== '' && filter.value !== 'all') {
                  url += '&' + filter.key + '=' + filter.value;
                }
              });
            }

            try {
              fetch(url).then(response => response.json())
                .then(response => {
                  if (response.status === true) {
                    if (typeof response.data.datas !== 'undefined') {
                      this.items[tab.key] = response.data.datas;
                      tab.pagination = {
                        current: page,
                        total: Math.ceil(response.data.count / this.numberOfItemsToDisplay)
                      }
                    }
                  } else {
                    console.error('Failed to get data : ' + response.msg);
                  }
                  this.loading.tabs = false;
                  this.loading.items = false;
                })
                .catch(error => {
                  console.error(error);
                  this.loading.tabs = false;
                  this.loading.items = false;
                });
            } catch (e) {
              console.error(e);
              this.loading.tabs = false;
              this.loading.items = false;
            }
          } else {
            this.loading.tabs = false;
            this.loading.items = false;
          }
        });
      } else {
        this.loading.tabs = false;
        this.loading.items = false;
      }
    },
    async setTabFilters(tab) {
      if (typeof tab.filters !== 'undefined' && tab.filters.length > 0) {
        if (typeof this.filters[tab.key] === 'undefined') {
          this.filters[tab.key] = [];

          tab.filters.forEach(filter => {
            //get the filter value from sessionStorage
            let filterValue = sessionStorage.getItem('tchooz_filter_' + this.selectedListTab + '_' + filter.key + '/' + document.location.hostname);
            if (filterValue == null) {
              filterValue = filter.default ? filter.default : 'all'
            }

            if (filter.values === null) {
              if (filter.getter) {
                this.filters[tab.key].push({
                  key: filter.key,
                  value: filterValue,
                  options: []
                });

                this.setFilterOptions((typeof filter.controller !== 'undefined' ? filter.controller : tab.controller), filter, tab.key);
              }
            } else {
              this.filters[tab.key].push({
                key: filter.key,
                value: filterValue,
                options: filter.values
              });
            }
          });
        }
      }
    },
    async setFilterOptions(controller, filter, tab) {
      return await fetch('index.php?option=com_emundus&controller=' + controller + '&task=' + filter.getter)
        .then(response => response.json())
        .then(response => {
          if (response.status === true) {
            let options = response.data;

            // if options is an array of strings, convert it to an array of objects
            if (typeof options[0] === 'string') {
              options = options.map(option => ({value: option, label: option}));
            }

            options.unshift({value: 'all', label: this.translate(filter.label)});

            this.filters[tab].find(f => f.key === filter.key).options = options;
          } else {
            return [];
          }
        });
    },
    searchItems() {
      if (this.searches[this.selectedListTab].searchDebounce !== null) {
        clearTimeout(this.searches[this.selectedListTab].searchDebounce);
      }

      if (this.searches[this.selectedListTab].search === '') {
        sessionStorage.removeItem('tchooz_filter_' + this.selectedListTab + '_search/' + document.location.hostname);
      } else {
        sessionStorage.setItem('tchooz_filter_' + this.selectedListTab + '_search/' + document.location.hostname, this.searches[this.selectedListTab].search);
      }

      this.searches[this.selectedListTab].searchDebounce = setTimeout(() => {
        if (this.searches[this.selectedListTab].search !== this.searches[this.selectedListTab].lastSearch) {
          this.searches[this.selectedListTab].lastSearch = this.searches[this.selectedListTab].search;

          // when we are searching through the list, we reset the pagination
          this.getListItems(1, this.selectedListTab);
        }
      }, 500);
    },
    onClickAction(action, itemId = null) {
      if (action === null || typeof action !== 'object') {
        return false;
      }

      let item = null;
      if (itemId !== null) {
        item = this.items[this.selectedListTab].find(item => item.id === itemId);
      }

      if (action.type === 'redirect') {
        let url = action.action;
        if (item !== null) {
          Object.keys(item).forEach(key => {
            url = url.replace('%' + key + '%', item[key]);
          });
        }

        settingsService.redirectJRoute(url,useGlobalStore().getCurrentLang)

        //window.location.href = url;
      } else {
        let url = 'index.php?option=com_emundus&controller=' + action.controller + '&task=' + action.action;

        if (itemId !== null) {
          if (action.parameters) {
            let url_parameters = action.parameters;
            if (item !== null) {
              Object.keys(item).forEach(key => {
                url_parameters = url_parameters.replace('%' + key + '%', item[key]);
              });
            }

            url += url_parameters;
          } else {
            url += '&id=' + itemId;
          }
        }

        if (Object.prototype.hasOwnProperty.call(action, 'confirm')) {
          Swal.fire({
            icon: 'warning',
            title: this.translate(action.label),
            text: this.translate(action.confirm),
            showCancelButton: true,
            confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_OK'),
            cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
            reverseButtons: true,
            customClass: {
              title: 'em-swal-title',
              confirmButton: 'em-swal-confirm-button',
              cancelButton: 'em-swal-cancel-button',
              actions: 'em-swal-double-action'
            }
          }).then((result) => {
            if (result.value) {
              this.executeAction(url);
            }
          });
        } else {
          this.executeAction(url);
        }
      }
    },
    executeAction(url) {
      this.loading.items = true;

      fetch(url).then(response => response.json())
        .then(response => {
          if (response.status === true || response.status === 1) {
            if (response.redirect) {
              window.location.href = response.redirect;
            }

            this.getListItems();
          } else {
            if (response.msg) {
              Swal.fire({
                icon: 'error',
                title: this.translate(response.msg),
                reverseButtons: true,
                customClass: {
                  title: 'em-swal-title',
                  confirmButton: 'em-swal-confirm-button',
                  actions: 'em-swal-single-action'
                }
              });
            }
          }

          this.loading.items = false;
        }).catch(error => {
          console.error(error);
          this.loading.items = false;
        });
    },
    onClickPreview(item) {
      if (this.previewAction && (this.previewAction.title || this.previewAction.content)) {
        Swal.fire({
          title: item[this.previewAction.title],
          html: '<div style="text-align: left;">' + item[this.previewAction.content] + '</div>',
          reverseButtons: true,
          customClass: {
            title: 'em-swal-title',
            confirmButton: 'em-swal-confirm-button',
            actions: 'em-swal-single-action',
          }
        });
      }
    },
    onChangeFilter(filter) {
      // Store value to sessionStorage
      sessionStorage.setItem('tchooz_filter_' + this.selectedListTab + '_' + filter.key + '/' + document.location.hostname, filter.value);

      // when we change a filter, we reset the pagination
      this.getListItems(1, this.selectedListTab);
    },
    onSelectTab(tabKey) {
      let selected = false;

      if (this.selectedListTab !== tabKey) {
        // check if the tab exists
        if (this.currentList.tabs.find(tab => tab.key === tabKey) !== 'undefined') {
          this.selectedListTab = tabKey;
          sessionStorage.setItem('tchooz_selected_tab/' + document.location.hostname, tabKey);
          selected = true;
        }
      }

      return selected;
    },
    changeViewType(viewType) {
      this.viewType = viewType.value;
      localStorage.setItem('tchooz_view_type/' + document.location.hostname, viewType.value);
    },
    filterShowOnActions(actions, item) {
      return actions.filter(action => {
        if (Object.prototype.hasOwnProperty.call(action, 'showon')) {
          return this.evaluateShowOn(item, action.showon);
        }

        return true;
      });
    },
    evaluateShowOn(item, showon) {
      let show = true;
      switch (showon.operator) {
      case '==':
      case '=':
        show = item[showon.key] == showon.value;
        break;
      case '!=':
        show = item[showon.key] != showon.value;
        break;
      case '>':
        show = item[showon.key] > showon.value;
        break;
      case '<':
        show = item[showon.key] < showon.value;
        break;
      case '>=':
        show = item[showon.key] >= showon.value;
        break;
      case '<=':
        show = item[showon.key] <= showon.value;
        break;
      default:
        show = true;
      }

      return show;
    },
    displayedColumns(item, viewType) {
      let columns = [];

      if (item && item.additional_columns) {
        columns = item.additional_columns.filter((column) => {
          return column.display === viewType || column.display === 'all';
        });
      }

      return columns;
    },
    displayLongValue(html) {
      Swal.fire({
        html: '<div style="text-align: left;">' + html + '</div>',
        reverseButtons: true,
        customClass: {
          title: 'em-swal-title',
          confirmButton: 'em-swal-confirm-button',
          actions: 'em-swal-single-action',
        }
      });
    }
  },
  computed: {
    currentTab() {
      return this.currentList.tabs.find((tab) => {
        return tab.key === this.selectedListTab;
      });
    },
    tabActionsPopover() {
      return typeof this.currentTab.actions !== 'undefined' ? this.currentTab.actions.filter((action) => {
        return !(['add', 'edit', 'preview'].includes(action.name)) && !Object.prototype.hasOwnProperty.call(action, 'icon');
      }) : [];
    },
    editAction() {
      return typeof this.currentTab !== 'undefined' && typeof this.currentTab.actions !== 'undefined' ? this.currentTab.actions.find((action) => {
        return action.name === 'edit';
      }) : false;
    },
    addAction() {
      return typeof this.currentTab !== 'undefined' && typeof this.currentTab.actions !== 'undefined' ? this.currentTab.actions.find((action) => {
        return action.name === 'add';
      }) : false;
    },
    previewAction() {
      return typeof this.currentTab !== 'undefined' && typeof this.currentTab.actions !== 'undefined' ? this.currentTab.actions.find((action) => {
        return action.name === 'preview';
      }) : false;
    },
    iconActions() {
      return typeof this.currentTab.actions !== 'undefined' ? this.currentTab.actions.filter((action) => {
        return !(['add', 'edit', 'preview'].includes(action.name)) && Object.prototype.hasOwnProperty.call(action, 'icon');
      }) : [];
    },
    displayedItems() {
      let items = typeof this.items[this.selectedListTab] !== 'undefined' ? this.items[this.selectedListTab] : [];
      /*return items.filter((item) => {
        return item.label[this.params.shortlang].toLowerCase().includes(this.searches[this.selectedListTab].search.toLowerCase());
      });*/
      return items;
    },
    additionalColumns() {
      let columns = [];
      let items = typeof this.items[this.selectedListTab] !== 'undefined' ? this.items[this.selectedListTab] : [];

      // eslint-disable-next-line valid-typeof
      if (items.length > 0 && items[0].additional_columns && items[0].additional_columns.length > 0) {
        items[0].additional_columns.forEach((column) => {

          if (column.display === 'all' || (column.display === this.viewType)) {
            columns.push(column.key);
          }
        });
      }

      return columns;
    },
    noneDiscoverTranslation() {
      let translation = '<img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">';

      if (this.type === "campaigns") {
        if (this.currentTab.key === 'programs') {
          translation += '<span>'+this.translate('COM_EMUNDUS_ONBOARD_NOPROGRAM')+'</span>';
        } else {
          translation += '<span>'+this.translate('COM_EMUNDUS_ONBOARD_NOCAMPAIGN')+'</span>';
        }
      } else if (this.type === "emails") {
        translation += '<span>'+this.translate('COM_EMUNDUS_ONBOARD_NOEMAIL')+'</span>';
      } else if (this.type === "forms") {
        translation += '<span>'+this.translate('COM_EMUNDUS_ONBOARD_NOFORM')+'</span>';
      }

      return translation;
    },
    displayedFilters() {
      return this.filters && this.filters[this.selectedListTab] ? this.filters[this.selectedListTab].filter(filter => filter.options.length > 0) : [];
    },
  },
  watch: {
    numberOfItemsToDisplay() {
      localStorage.setItem('tchooz_number_of_items_to_display/' + document.location.hostname, this.numberOfItemsToDisplay);
    },
  }
}
</script>

<style lang="scss">
.head {
  padding: 0 0 20px 0;
}

#onboarding_list .head {
  position: fixed;
  display: flex;
  justify-content: space-between;
  width: -webkit-fill-available;
  width: -moz-available;
  width: stretch;
  background: var(--em-coordinator-bg);
  top: 72px;
  box-shadow: var(--em-box-shadow-x-1) var(--em-box-shadow-y-1) var(--em-box-shadow-blur-1) var(--em-box-shadow-color-1), var(--em-box-shadow-x-2) var(--em-box-shadow-y-2) var(--em-box-shadow-blur-2) var(--em-box-shadow-color-2), var(--em-box-shadow-x-3) var(--em-box-shadow-y-3) var(--em-box-shadow-blur-3) var(--em-box-shadow-color-3);
  left: 75px;
  padding: 24px 33px 24px 33px;
  min-height: 86px;
}

.view-settings #onboarding_list .head {
  position: inherit;
}

#onboarding_list .list {
  margin-top: 77px;
}

#onboarding_list.alert-banner-displayed .head{
  top: 114px;
}

#list-nav {
  li {
    transition: all .3s;
  }
}

#list-table {
  transition: all .3s;
  border: 0;

  thead th {
    background-color: transparent;
  }

  &.blocs {
    border: 0;


    thead {
      display: none;
    }

    tbody {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
      column-gap: 24px;
      row-gap: 24px;

      tr {
        background: #fff;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 200px;

        td {
          display: flex;
          flex-direction: row;
          justify-content: space-between;
          padding: 0;

          &.actions {
            align-items: center;
          }

          ul {
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            align-items: flex-end;
            padding: 0;
            margin: 0;

            li {
              list-style: none;
              cursor: pointer;
              width: 100%;
            }
          }
        }
      }
    }
  }
}

.skeleton-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  column-gap: 24px;
  row-gap: 24px;
}

#tabs-loading, #items-loading {

  :not(.skeleton-grid) .skeleton-item,
  &:not(.skeleton-grid) .skeleton-item {
    height: 40px !important;
    width: 100% !important;
    margin-bottom: 16px !important;
  }

  .skeleton-grid .skeleton-item,
  &.skeleton-grid .skeleton-item {
    height: 200px !important;
    min-width: 340px !important;
  }
}

#pagination {
  transition: all .3s;
  overflow: hidden;

  ul {
    overflow: auto;
  }

  li {
    transition: all .3s;
    font-size: 12px;
    padding: 0 12px;
  }
}
</style>