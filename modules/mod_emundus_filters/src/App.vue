<template>
  <div id="emundus-filters" class="em-w-100">
    <section id="filters-top-actions" class="em-mb-16">
      <button id="clear-filters" class="tw-cursor-pointer tw-text-red-500 tw-mb-4 tw-underline" @click="clearFilters">
        {{ translate('MOD_EMUNDUS_FILTERS_CLEAR_FILTERS') }}
      </button>
      <div id="registered-filters-wrapper">
        <label for="registered-filters" class="tw-flex tw-flex-row tw-cursor-pointer" @click="openedRegisteredFilters = !openedRegisteredFilters">
          <span class="material-symbols-outlined" v-if="openedRegisteredFilters">expand_more</span>
          <span class="material-symbols-outlined" v-else>expand_less</span>
          <span>{{ translate('MOD_EMUNDUS_FILTERS_SAVED_FILTERS') }} ({{ registeredFilters.length }})</span>
        </label>
        <div v-if="openedRegisteredFilters">
          <div class="tw-my-4">
            <p v-if="registeredFilters.length < 1">{{ translate('MOD_EMUNDUS_FILTERS_NO_SAVED_FILTERS') }}</p>
            <ul id="registered-filters-list" v-else class="tw-list-none !tw-pl-0 tw-bg-white tw-border tw-rounded">
              <li v-for="filter in registeredFilters" :key="filter.id"
                  :class="{'active tw-text-main-500': selectedRegisteredFilter === filter.id}"
                  class="tw-p-2 tw-cursor-pointer tw-border-b last:tw-border-b-0 tw-flex tw-justify-between tw-items-center"
              >
                <div v-if="filter.id !== 'tmp'" class="tw-w-full tw-flex tw-flex-row tw-items-center tw-justify-between">
                  <div class="tw-w-full tw-flex tw-flex-row tw-items-center">
                    <span v-if="filter.favorite" class="material-icons-outlined"
                          style="font-variation-settings: 'FILL' 1, 'wght' 700, 'GRAD' 0, 'opsz' 48;"
                          @click="toggleFilterFavoriteState(filter.id, 0)">
                      star
                    </span>
                    <span v-else class="material-symbols-outlined" @click="toggleFilterFavoriteState(filter.id, 1)">star</span>

                    <div v-if="filterToRename && filterToRename.id === filter.id"
                         class="tw-flex tw-flex-row tw-items-center">
                      <input type="text" v-model="filter.name"/>
                      <span class="material-symbols-outlined tw-cursor-pointer" @click="renameFilter(filter.id)">check_circle_outline</span>
                    </div>
                    <div v-else class="tw-ml-2 tw-w-full" @click="onSelectRegisteredFilter(filter.id)">
                      <span>{{ filter.name }}</span>
                      <span v-if="filter.default" class="tw-ml-2 em-gray-color"><i>{{ translate('MOD_EMUNDUS_FILTERS_DEFAULT_FILTER') }}</i></span>
                    </div>
                  </div>
                  <popover :distance="14">
                    <template v-slot="popover">
                      <span class="material-symbols-outlined popover-opener cursor-pointer">more_vert</span>
                      <div class="popover-content">
                        <ul class="tw-list-none !tw-pl-0 em-text-color">
                          <li class="tw-flex tw-flex-row tw-p-2" @click="editFilterName(filter.id, popover)">
                            <span class="material-symbols-outlined tw-mr-2">edit</span>
                            <span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_RENAME') }}</span>
                          </li>
                          <li class="tw-flex tw-flex-row tw-p-2" @click="updateFilter(filter.id, popover)">
                            <span class="material-symbols-outlined tw-mr-2">refresh</span>
                            <span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_UPDATE') }}</span>
                          </li>
                          <li v-if="canShareFilters" class="tw-flex tw-flex-row tw-p-2" @click="shareFilter(filter.id, popover)">
                            <span class="material-symbols-outlined tw-mr-2">share</span>
                            <span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_SHARE') }}</span>
                          </li>
                          <li class="tw-flex tw-flex-row tw-p-2" @click="defineAsDefault(filter.id, popover)">
                            <span class="material-symbols-outlined tw-mr-2">check_box</span>
                            <span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_DEFINE_AS_DEFAULT') }}</span>
                          </li>
                          <li class="tw-flex tw-flex-row tw-p-2" @click="deleteFilter(filter.id, popover)">
                            <span class="material-symbols-outlined tw-mr-2 em-red-500-color">delete</span>
                            <span>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_ACTION_DELETE') }}</span>
                          </li>
                        </ul>
                      </div>
                    </template>
                  </popover>
                </div>
                <div v-else class="tw-flex tw-flex-row tw-items-center tw-justify-between tw-w-full">
                  <input type="text" id="new-filter-name" name="new-filter-name" v-model="filter.name"/>
                  <span class="material-symbols-outlined tw-cursor-pointer" @click="saveFilters('tmp')">check_circle_outline</span>
                </div>
              </li>
            </ul>
          </div>
          <button class="tw-btn-primary tw-text-white hover:tw-text-main-500 tw-w-full"
                  :class="{'tw-disabled': unsavedNewFilter}" @click="">
            <span class="material-symbols-outlined tw-mr-4 tw-text-inherit">save</span>
            <span>{{ translate('MOD_EMUNDUS_FILTERS_SAVE_FILTERS') }}</span>
          </button>
        </div>
      </div>

      <hr style="margin:20px 32px;">

      <div id="global-search-wrapper" style="position: relative;">
        <div id="global-search-values" ref="globalSearchValues"
             class="em-border-radius-8 em-border-neutral-400 tw-flex tw-flex-row em-flex-wrap em-white-bg"
             @click="onEnterGlobalSearchDiv">
          <div v-if="globalSearch.length > 0" class="tw-flex tw-flex-row em-flex-wrap">
            <div v-for="value in globalSearch" :key="value.value + '-' + value.scope"
                 class="global-search-tag tw-flex tw-flex-row em-box-shadow em-border-radius-8 em-border-neutral-400 em-w-auto tw-m-4">
              <span style="white-space: nowrap">{{ translatedScope(value.scope) }} : {{ value.value }}</span>
              <span class="material-symbols-outlined em-pointer"
                    @click="removeGlobalSearchValue(value.value, value.scope)">clear</span>
            </div>
          </div>
          <input id="current-global-search" ref="globalSearchInput" class="em-border-radius-8"
                 v-model="currentGlobalSearch" type="text"
                 @keyup.enter="(e) => {this.onGlobalSearchChange(e, 'everywhere')}"
                 :placeholder="globalSearchPlaceholder">
        </div>
        <ul id="select-scopes" class="tw-w-full em-border-radius-8 em-white-bg em-border-neutral-400 em-box-shadow"
            :class="{'hidden': currentGlobalSearch.length < 1}">
          <li v-for="option in globalSearchScopes" :key="option.value"
              @click="(e) => {this.onGlobalSearchChange(e, option.value)}" class="em-pointer global-search-scope">
            <button>{{ currentGlobalSearch }} {{ translate('MOD_EMUNDUS_FILTERS_SCOPE_IN') }} {{
                translate(option.label)
              }}
            </button>
          </li>
        </ul>
      </div>
    </section>
    <section id="applied-filters">
      <div v-for="appliedFilter in appliedFilters" :key="appliedFilter.uid">
        <MultiSelect v-if="appliedFilter.type === 'select'" :filter="appliedFilter" :module-id="moduleId"
                     :countFilterValues="countFilterValues" class="em-w-100"
                     @remove-filter="onRemoveFilter(appliedFilter)" @filter-changed="onFilterChanged"></MultiSelect>
        <DateFilter v-else-if="appliedFilter.type === 'date'" :filter="appliedFilter" :module-id="moduleId"
                    class="em-w-100" @remove-filter="onRemoveFilter(appliedFilter)"
                    @filter-changed="onFilterChanged"></DateFilter>
        <TimeFilter v-else-if="appliedFilter.type === 'time'" :filter="appliedFilter" :module-id="moduleId"
                    class="em-w-100" @remove-filter="onRemoveFilter(appliedFilter)"></TimeFilter>
        <DefaultFilter v-else :filter="appliedFilter" :module-id="moduleId" :type="appliedFilter.type" class="em-w-100"
                       @remove-filter="onRemoveFilter(appliedFilter)" @filter-changed="onFilterChanged"></DefaultFilter>
      </div>
    </section>
    <div id="filters-selection-wrapper" class="em-w-100 em-mt-16 em-mb-16" :class="{'hidden': !openFilterOptions}">
      <label for="filters-selection"> {{ translate('MOD_EMUNDUS_FILTERS_SELECT_FILTER_LABEL') }} </label>
      <AdvancedSelect :module-id="moduleId" :filters="availableFilters"
                      @filter-selected="onSelectNewFilter"></AdvancedSelect>
    </div>
    <section id="filters-bottom-actions">
      <button id="em-add-filter" class="tw-btn-cancel tw-w-full em-white-bg em-mt-16"
              @click="openFilterOptions = !openFilterOptions">
        <span class="material-symbols-outlined tw-mr-2 tw-text-inherit">add_circle_outline</span>
        <span>{{ translate('MOD_EMUNDUS_FILTERS_ADD_FILTER') }}</span>
      </button>
      <button id="em-apply-filters" class="tw-btn-primary em-mt-16 hidden" @click="applyFilters">
        {{ translate('MOD_EMUNDUS_FILTERS_APPLY_FILTERS') }}
      </button>
    </section>

    <Modal v-if="canShareFilters && showShareFiltersModal && filterToShare"
           :title="translate('MOD_EMUNDUS_FILTERS_SHARE_FILTERS')"
           @close="onCloseShareFilterModal">
      <template v-slot:body>
        <share-filters :filter="filterToShare" @close="showShareFiltersModal = false"></share-filters>
      </template>
    </Modal>
  </div>
</template>

<script>
import MultiSelect from './components/MultiSelectFilter.vue';
import AdvancedSelect from './components/AdvancedSelect.vue';
import DateFilter from './components/DateFilter.vue';
import TimeFilter from './components/TimeFilter.vue';
import DefaultFilter from './components/DefaultFilter.vue';
import filtersService from './services/filters.js';
import Modal from "@/components/Modal.vue";
import ShareFilters from "@/views/shareFilters.vue";
import Popover from "@/components/Popover.vue";

const defaultGlobalSearchScopes = [
  {value: 'everywhere', label: 'MOD_EMUNDUS_FILTERS_SCOPE_ALL'},
  {value: 'eu.firstname', label: 'MOD_EMUNDUS_FILTERS_SCOPE_FIRSTNAME'},
  {value: 'eu.lastname', label: 'MOD_EMUNDUS_FILTERS_SCOPE_LASTNAME'},
  {value: 'u.username', label: 'MOD_EMUNDUS_FILTERS_SCOPE_USERNAME'},
  {value: 'u.email', label: 'MOD_EMUNDUS_FILTERS_SCOPE_EMAIL'},
  {value: 'jecc.applicant_id', label: 'MOD_EMUNDUS_FILTERS_SCOPE_ID'},
  {value: 'jecc.fnum', label: 'MOD_EMUNDUS_FILTERS_SCOPE_FNUM'}
];

export default {
  name: 'App',
  components: {ShareFilters, Modal, DateFilter, AdvancedSelect, MultiSelect, TimeFilter, DefaultFilter, Popover},
  props: {
    moduleId: {
      type: Number,
      required: true
    },
    defaultAppliedFilters: {
      type: Array,
      default: () => []
    },
    defaultQuickSearchFilters: {
      type: Array,
      default: () => []
    },
    defaultFilters: {
      type: Array,
      default: () => []
    },
    countFilterValues: {
      type: Boolean,
      default: false
    },
    canShareFilters: {
      type: Boolean,
      default: false
    },
    userId: {
      type: Number,
      default: 0
    },
  },
  data() {
    return {
      applySuccessEvent: null,
      startApplyFilters: null,
      appliedFilters: [],
      openFilterOptions: false,
      newFilterName: '',
      registeredFilters: [],
      openedRegisteredFilters: true,
      selectedRegisteredFilter: 0,
      showSaveFilter: false,
      showShareFiltersModal: false,
      currentGlobalSearch: '',
      globalSearch: [],
      currentGlobalSearchScope: 'everywhere',
      globalSearchScopes: [],
      filters: [],
      filterToShare: null,
      filterToRename: null
    }
  },
  mounted() {
    this.applySuccessEvent = new Event('emundus-apply-filters-success');
    this.startApplyFilters = new Event('emundus-start-apply-filters');
    this.filters = this.defaultFilters;

		this.getRegisteredFilters(0);
		this.selectedRegisteredFilter = sessionStorage.getItem('emundus-current-filter') || 0;
		this.appliedFilters = this.defaultAppliedFilters.map((filter) => {
			if (!filter.hasOwnProperty('operator')) {
				filter.operator = '=';
			}
			if (!filter.hasOwnProperty('andorOperator')) {
				filter.andorOperator = 'OR';
			}

      return filter;
    });
    this.globalSearch = this.defaultQuickSearchFilters;
    this.mapSearchScopesToAppliedFilters();
    this.addKeyEvents();

    window.addEventListener('refresh-emundus-module-filters', () => {
      this.applyFilters();
    });
  },
  methods: {
    addKeyEvents() {
      // add key events on up and down to focus on the next or previous global search scope
      const globalSearchScope = document.getElementById('global-search-wrapper');
      globalSearchScope.addEventListener('keydown', (event) => {
        const currentFocusedScope = globalSearchScope.querySelector('.global-search-scope button:focus');
        const currentFocusedInput = globalSearchScope.querySelector('#current-global-search:focus');

        if (currentFocusedScope || currentFocusedInput) {
          if (event.code === 'ArrowUp') {
            event.preventDefault();

            if (currentFocusedScope) {
              // focus on the previous scope
              const previousScope = currentFocusedScope.parentElement.previousElementSibling;
              if (previousScope) {
                const previousScopeButton = previousScope.querySelector('button');
                previousScopeButton.focus();
              } else {
                // focus on the input
                this.$refs.globalSearchInput.focus();
              }
            } else {
              // focus on the last scope
              const lastScope = globalSearchScope.querySelector('.global-search-scope:last-child button');
              lastScope.focus();
            }
          } else if (event.code === 'ArrowDown') {
            event.preventDefault();
            if (currentFocusedScope) {
              // focus on the next scope
              const nextScope = currentFocusedScope.parentElement.nextElementSibling;
              if (nextScope) {
                const nextScopeButton = nextScope.querySelector('button');
                nextScopeButton.focus();
              } else {
                this.$refs.globalSearchInput.focus();
              }
            } else {
              // focus on the first scope
              const firstScope = globalSearchScope.querySelector('.global-search-scope:first-child button');
              firstScope.focus();
            }
          }
        }
      });
    },
    onSelectNewFilter(filterId) {
      let added = false;

      const foundFilter = this.filters.find((filter) => filter.id === filterId);
      if (foundFilter) {
        // JSON stringify and parse to remove binding to the original filter
        let newFilter = JSON.parse(JSON.stringify(foundFilter));

				newFilter.uid = new Date().getTime();
				newFilter.default = false;
				newFilter.operator = newFilter.hasOwnProperty('operator') && newFilter.operator != '' ? newFilter.operator : '=';
				newFilter.andorOperator = 'OR';

        switch (newFilter.type) {
          case 'select':
            newFilter.value = ['all'];
            newFilter.operator = 'IN';
            break;
          case 'date':
            newFilter.value = ['', ''];
            break;
          default:
            newFilter.value = '';
            break;
        }

        if (newFilter.type === 'select' && newFilter.values.length < 1) {
          filtersService.getFilterValues(newFilter.id).then((values) => {
            newFilter.values = values;

            this.appliedFilters.push(newFilter);
            this.openFilterOptions = false;
            this.applyFilters();

            return true;
          });
        } else {
          this.appliedFilters.push(newFilter);
          this.openFilterOptions = false;
          added = true;
          this.applyFilters();

          return added;
        }
      } else {
        console.error('Filter not found');
        return added;
      }
    },
    applyFilters() {
      window.dispatchEvent(this.startApplyFilters);
      filtersService.applyFilters(this.appliedFilters, this.globalSearch, this.applySuccessEvent).then((applied) => {
        if (applied && this.countFilterValues) {
          filtersService.countFiltersValues(this.moduleId).then((response) => {
            if (response.status) {
              this.appliedFilters = response.data;
            }
          });
        }

        filtersService.getFiltersAvailable(this.moduleId).then((filters) => {
          this.filters = filters;
        }).catch((error) => {
          console.error(error);
        });
      });
		},
		clearFilters() {
      this.onSelectRegisteredFilter(0);
      sessionStorage.removeItem('emundus-current-filter');
			this.globalSearch = [];
			// reset applied filters values
			this.appliedFilters = this.appliedFilters.map((filter) => {
				filter.operator = '=';

        if (filter.type === 'select') {
          filter.operator = 'IN';

					// TODO: too specific to the published filter, should create a default_value field.
					if (filter.uid === 'published') {
						filter.value = [1];
					} else {
						filter.value = [];
					}
				} else if (filter.type === 'date' || filter.type === 'time') {
					filter.value = ['', ''];
				} else {
					filter.value = '';
				}

        return filter;
      });
      this.applyFilters();
    },
    saveNewFilter() {
      if (this.registeredFilters.find((filter) => filter.id === 'tmp')) {
        return;
      }

      this.registeredFilters.push({
        id: 'tmp',
        name: 'Nouveau filtre',
        constraints: JSON.stringify(this.appliedFilters),
        shared: false,
        shared_by: null
      });

      this.selectedRegisteredFilter = 'tmp';
    },
    saveFilters(filterId) {
      let saved = false;
      const newFilter = this.registeredFilters.find((filter) => filter.id === filterId)

      if (newFilter.name.length > 0) {
        filtersService.saveFilters(this.appliedFilters, newFilter.name, this.moduleId).then((saved) => {
          if (saved) {
            const ids = this.registeredFilters.map((filter) => filter.id);
            this.getRegisteredFilters().then(() => {
              const newID = this.registeredFilters.map((filter) => filter.id).filter((id) => !ids.includes(id))[0];
              this.onSelectRegisteredFilter(newID);
            })
          }
        });
      }

      return saved;
    },
    editFilterName(filterId, popover = null) {
      let renamed = false;

      if (filterId) {
        this.filterToRename = this.registeredFilters.find((filter) => filter.id === filterId);
      }

      if (popover) {
        popover.close();
      }

      return renamed;
    },
    renameFilter(filterId) {
      if (filterId) {
        const foundFilter = this.registeredFilters.find((filter) => filter.id === filterId);

        if (foundFilter) {
          filtersService.renameFilter(filterId, foundFilter.name).then(() => {
            this.getRegisteredFilters();
          });
        }
      }

      this.filterToRename = null;
    },
    updateFilter(filterId, popover = null) {
      let updated = false;

      if (filterId > 0) {
        updated = filtersService.updateFilter(this.appliedFilters, this.moduleId, filterId);

        if (updated) {
          this.getRegisteredFilters();
        }
      }

      if (popover) {
        popover.close();
      }

      return updated;
    },
    getRegisteredFilters(firstLoad = false) {
      return filtersService.getRegisteredFilters(this.moduleId).then((filters) => {
        this.registeredFilters = filters;
        if (firstLoad) {
          this.selectedRegisteredFilter = Number(sessionStorage.getItem('emundus-current-filter')) || 0;
        }
      });
    },
    onSelectRegisteredFilter(newFilterId = 0) {
      this.selectedRegisteredFilter = newFilterId;

      if (this.selectedRegisteredFilter > 0) {
        const foundFilter = this.registeredFilters.find((filter) => filter.id === this.selectedRegisteredFilter);

        if (foundFilter) {
          sessionStorage.setItem('emundus-current-filter', foundFilter.id);
          this.appliedFilters = JSON.parse(foundFilter.constraints);
          this.applyFilters();
        }
      } else {
        sessionStorage.removeItem('emundus-current-filter');
      }
    },
    deleteFilter(filterId, popover = null) {
      if (filterId > 0) {
        Swal.fire({
          title: this.translate('MOD_EMUNDUS_FILTERS_DELETE_FILTER_CONFIRMATION'),
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: this.translate('MOD_EMUNDUS_FILTERS_CONFIRM'),
          cancelButtonText: this.translate('MOD_EMUNDUS_FILTERS_CANCEL'),
          customClass: {
            title: 'em-swal-title',
            cancelButton: 'em-swal-cancel-button',
            confirmButton: 'em-swal-confirm-button',
          },
        }).then(result => {
          if (result.value) {
            filtersService.deleteFilter(filterId);
            this.registeredFilters = this.registeredFilters.filter((filter) => filter.id !== filterId);

            if (this.selectedRegisteredFilter === filterId) {
              this.onSelectRegisteredFilter(0);
            }
          }
        });
      }

      if (popover) {
        popover.close();
      }
    },
    shareFilter(filterId, popover = null) {
      if (this.canShareFilters) {
        this.showShareFiltersModal = true;
        this.filterToShare = this.registeredFilters.find((filter) => filter.id === filterId);
      }

      if (popover) {
        popover.close();
      }
    },
    defineAsDefault(filterId, popover = null) {
      filtersService.defineAsDefaultFilter(filterId).then((response) => {
        if (response.status) {
          this.getRegisteredFilters();
        }
      });

      if (popover) {
        popover.close();
      }
    },
    toggleFilterFavoriteState(filterId, favorite) {
      filtersService.toggleFilterFavoriteState(filterId, favorite).then(() => {
        this.getRegisteredFilters();
      });
    },
    onCloseShareFilterModal() {
      this.showShareFiltersModal = false;
      this.filterToShare = null;
    },
    onClickSaveFilter() {
      if (this.selectedRegisteredFilter > 0) {
        const foundFilter = this.registeredFilters.find((filter) => filter.id === this.selectedRegisteredFilter);

        if (foundFilter) {
					this.updateFilter(foundFilter.id);
				}
			} else {
				this.openSaveFilter = !this.openSaveFilter;

				if (this.openSaveFilter) {
					// focus on the input new-filter-name
					this.$nextTick(() => {
						this.$refs['new-filter-name'].focus();
					});
				}
			}
		},
		onRemoveFilter(filter) {
			this.appliedFilters = this.appliedFilters.filter((appliedFilter) => appliedFilter.uid !== filter.uid);
			this.applyFilters();
		},
		onFilterChanged() {
			this.applyFilters();
		},
		onGlobalSearchChange(event, scope = 'everywhere') {
			event.stopPropagation();
			event.preventDefault();

			if (this.currentGlobalSearch.length > 0) {
        // if currentGlobalSearch contains ; then split it and add each value as a new search
        if (this.currentGlobalSearch.includes(';')) {
          const searches = this.currentGlobalSearch.split(';');
          searches.forEach((search) => {
            const foundSearch = this.globalSearch.find((existingSearch) => existingSearch.value === search && existingSearch.scope === scope);

            if (!foundSearch) {
              this.globalSearch.push({value: search, scope: scope});
            }
          });

          this.applyFilters();
        } else {
          // if the current search is already in the list, no need to add it again
          const foundSearch = this.globalSearch.find((search) => search.value === this.currentGlobalSearch && search.scope === scope);

          if (!foundSearch) {
            this.globalSearch.push({value: this.currentGlobalSearch, scope: scope});
            this.applyFilters();
          }
        }
			}

      this.currentGlobalSearch = '';
      // scroll to top of the div
      this.$refs.globalSearchValues.scrollTop = 0;
    },
    removeGlobalSearchValue(value, scope) {
      this.globalSearch = this.globalSearch.filter((search) => {
        return search.value !== value || search.scope !== scope;
      });

      // scroll to top of the div #global-search-values
      // remove focus from the input #global-search-input
      document.activeElement.blur();
      this.$refs.globalSearchValues.scrollTop = 0;
      this.applyFilters();
    },
    onEnterGlobalSearchDiv() {
      this.$refs.globalSearchValues.scrollTop = this.$refs.globalSearchValues.scrollHeight;
      this.$refs.globalSearchInput.focus();
    },
    translatedScope(scope) {
      const foundScope = this.globalSearchScopes.find((s) => s.value === scope);

      return foundScope ? this.translate(foundScope.label) : scope;
    },
    mapSearchScopesToAppliedFilters() {
      this.globalSearchScopes = [];
      this.globalSearchScopes = defaultGlobalSearchScopes;

      /*
      TODO: hard to give an open search on label for all filters elements
      this.appliedFilters.forEach((filter) => {
        const foundScope = this.globalSearchScopes.find((s) => s.value === filter.id);

        if (!foundScope) {
          this.globalSearchScopes.push({
            value: filter.id,
            label: filter.label
          });
        }
      });*/
    }
  },
  computed: {
    availableFilters() {
      return this.filters.filter((filter) => {
        return filter.available;
      });
    },
    globalSearchPlaceholder() {
      return this.globalSearch.length < 1 ? this.translate('MOD_EMUNDUS_FILTERS_GLOBAL_SEARCH_PLACEHOLDER') : '';
    },
    selectedRegisteredFilterObject() {
      return this.registeredFilters.find((filter) => filter.id === this.selectedRegisteredFilter);
    },
    canDeleteSelectedFilter() {
      let canDelete = false;

      if (this.selectedRegisteredFilterObject) {
        if (this.selectedRegisteredFilterObject.shared_by !== undefined && this.selectedRegisteredFilterObject.shared_by !== null) {
          if (this.selectedRegisteredFilterObject.shared_by === this.userId) {
            canDelete = true;
          }
        } else {
          canDelete = true;
        }
      }

      return canDelete;
    },
    sharedFilters() {
      return this.registeredFilters.filter((filter) => filter.shared && filter.shared_by !== this.userId);
    },
    userRegisteredFilters() {
      return this.registeredFilters.filter((filter) => filter.shared_by === this.userId || filter.shared_by === null || filter.shared_by === undefined);
    },
    unsavedNewFilter() {
      return this.registeredFilters.find((filter) => filter.id === 'tmp') !== undefined;
    }
  }
}
</script>

<style>
#emundus-filters {
  position: relative;
}

#emundus-filters .recap-label {
  display: -webkit-box !important;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-overflow: ellipsis;
}

#select-scopes:not(.hidden) {
	position: absolute;
  top: 83px;
	z-index:2;
	list-style-type: none;
	margin: 0;
	padding: 8px;
}

#select-scopes li {
  padding: 8px;
}

#global-search-values {
  height: 42px;
  overflow-y: auto;
  align-items: flex-start;
}

.global-search-values-wide {
  height: 84px !important;
}

#em-files-filters input[type="text"]:focus, #em-user-filters input[type="text"]:focus {
  box-shadow: none;
}

.global-search-scope button {
	white-space: break-spaces;
	text-align: left;
}

#current-global-search {
  border: none;
  border-radius: 0;
  box-shadow: none;
  outline: 0;
}

.global-search-tag {
  padding: 4px;
}

#save-filter-new-name {
  position: absolute;
  top: 0;
}

#registered-filters-list {
  max-height: 150px;
  overflow-y: auto;
}

</style>