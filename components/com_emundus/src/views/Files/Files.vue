<template>
  <div class="tw-ml-8 em-files">
    <Application v-if="currentFile" :file="currentFile" :type="$props.type" :user="$props.user" :ratio="$props.ratio"
                 @getFiles="getFiles(true)"/>

    <div class="tw-mb-4 tw-flex tw-items-center tw-justify-between">
      <h4>{{ translate('COM_EMUNDUS_FILES_' + type.toUpperCase()) }}</h4>
    </div>

    <div v-if="error.displayed" class="alert">
      <p>{{ error.message }}</p>
    </div>

    <div v-if="files">
      <div class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <div class="tw-flex tw-items-center">
          <div class="tw-flex tw-items-center">
            <span>{{ translate('COM_EMUNDUS_FILES_TOTAL') }}</span>
            <span class="tw-ml-1">{{ total_count }}</span>
          </div>
          <span class="tw-ml-3 tw-mr-3">|</span>
          <div class="tw-flex tw-items-center">
            <span>{{ translate('COM_EMUNDUS_FILES_DISPLAY_PAGE') }}</span>
            <select class="em-select-no-border tw-ml-3" style="width: max-content; height: fit-content;" v-model="limit">
              <option>10</option>
              <option>25</option>
              <option>50</option>
              <option>100</option>
            </select>
          </div>
        </div>
        <template v-if="pages !== null">
          <div class="tw-flex tw-items-center" v-if="pages.length > 1">
            <span>{{ translate('COM_EMUNDUS_FILES_PAGE') }}</span>
            <select class="em-select-no-border tw-ml-3" style="width: 40px;" v-model="page">
              <option v-for="no_page in pages" :value="no_page">{{ displayPage(no_page) }}</option>
            </select>
            <span class="tw-ml-3">{{ translate('COM_EMUNDUS_FILES_PAGE_ON') }}</span>
            <span class="tw-ml-3 tw-mr-3">{{ pages.length }}</span>
          </div>
        </template>
      </div>
    </div>

    <div v-if="files">
      <tabs v-if="$props.type === 'evaluation'" :tabs="tabs" @updateTab="updateTab"></tabs>
      <hr/>

      <div v-if="!filtersLoading" class="tw-flex tw-justify-between tw-items-start tw-mb-4">
        <div id="filters" class="tw-flex tw-flex-col">
          <div id="default-filters" class="tw-mb-4" v-if="defaultFilters.length > 0"
               v-click-outside="onDefaultFiltersClickOutside">
            <div class="em-tabs tw-cursor-pointer tw-flex tw-items-center md:tw-justify-center"
                 @click="openedFilters = !openedFilters">
              <span>{{ translate('COM_EMUNDUS_FILES_FILTER') }}</span>
              <span class="material-symbols-outlined tw-ml-3">filter_list</span>
            </div>
            <ul :class="{'tw-hidden': !openedFilters, 'em-input': true}">
              <li v-for="filter in defaultFilters" :key="filter.id" @click="addFilter(filter)" class="tw-cursor-pointer">
                {{ filter.label }}
              </li>
            </ul>
          </div>
          <div id="applied-filters" v-if="filters.length > 0" class="tw-flex tw-items-center">
            <div v-for="filter in filters" :key="filter.key" class="applied-filter tw-ml-3 tw-flex tw-items-center">
              <label class="filter-label tw-mr-3" :for="filter.id + '-' + filter.key" :title="filter.label">{{
                  filter.label
                }}</label>
              <select class="tw-mr-3" v-model="filter.selectedOperator">
                <option v-for="operator in filter.operators" :key="operator.value" :value="operator.value">
                  {{ operator.label }}
                </option>
              </select>
              <input v-if="filter.type == 'field'"
                     :name="filter.id + '-' + filter.key"
                     type="text"
                     :placeholder="filter.label"
                     v-model="filter.selectedValue"
                     @keyup.enter="applyFilters"
              />
              <input v-else-if="filter.type == 'date'" :name="filter.id + '-' + filter.key" type="date"
                     v-model="filter.selectedValue">
              <multiselect
                  v-else-if="filter.type == 'select'"
                  v-model="filter.selectedValue"
                  label="label"
                  track-by="value"
                  :options="filter.values"
                  :multiple="true"
                  :taggable="false"
                  select-label=""
                  :placeholder="filter.label"
                  selected-label=""
                  deselect-label=""
                  :close-on-select="true"
                  :clear-on-select="false"
                  :searchable="true"
                  :allow-empty="true"
                  width="250px"
              >
                <span slot="noResult">{{translate('COM_EMUNDUS_FILES_FILTER_NO_ELEMENTS_FOUND')}}</span>
              </multiselect>
              <span class="material-symbols-outlined tw-cursor-pointer tw-text-red-600 tw-ml-3" @click="removeFilter(filter)">close</span>
            </div>
          </div>
        </div>
        <div v-if="defaultFilters.length > 0" class="tw-flex tw-items-center">
          <span class="material-symbols-outlined tw-mr-4 tw-text-red-600"
                :class="{'tw-cursor-pointer': filters.length > 0, 'tw-cursor-pointer-disbabled': filters.length < 1 }"
                :alt="translate('COM_EMUNDUS_FILES_RESET_FILTERS')" @click="resetFilters">filter_alt_off</span>
          <button class="tw-btn-primary tw-cusor-pointer" @click="applyFilters">
            {{ translate('COM_EMUNDUS_FILES_APPLY_FILTER') }}
          </button>
        </div>
      </div>
      <div v-else class="tw-flex tw-items-center tw-justify-between tw-mb-4">
        <skeleton height="40px" width="96px" class="tw-rounded-coordinator"></skeleton>
        <skeleton height="40px" width="120px" class="tw-rounded-coordinator"></skeleton>
      </div>
    </div>

    <div class="tw-flex tw-items-center tw-items-start" v-if="files && columns && files.length > 0" :key="reloadFiles">
      <div id="table_columns_move_right" :class="moveRight ? '' : 'em-disabled-state'"
           class="table-columns-move tw-flex tw-flex-col tw-mr-1" @click="scrollToRight">
        <span class="material-symbols-outlined tw-cursor-pointer" style="font-size: 16px">arrow_back</span>
      </div>

      <el-table
          ref="tableFiles"
          style="width: 100%"
          height="calc(100vh - 250px)"
          :data="files"
          :default-sort="{prop: 'file', order: 'ascending'}"
          :key="reloadFiles"
          @select-all="selectRow"
          @select="selectRow">
        <el-table-column
            type="selection"
            width="55">
        </el-table-column>
        <el-table-column
            :label="translate('COM_EMUNDUS_ONBOARD_FILE')"
            width="270"
            prop="file"
            sortable
            :sort-method="(a, b) => sortBy(a, b, 'file')">
          <template slot-scope="scope">
            <div @click="openApplication(scope.row)" class="tw-cursor-pointer">
              <p class="tw-font-medium">
                {{ scope.row.applicant_name.charAt(0).toUpperCase() + scope.row.applicant_name.slice(1) }}</p>
              <span class="tw-text-neutral-500 tw-text-sm">{{ scope.row.fnum }}</span>
            </div>
          </template>
        </el-table-column>

        <template v-for="column in columns" v-if="column.show_in_list_summary == 1">
          <el-table-column
              v-if="column.name === 'status'"
              prop="status"
              sortable
              min-width="180">
            <template slot="header" slot-scope="scope">
              <span :title="translate('COM_EMUNDUS_ONBOARD_STATUS')"
                    class="tw-text-neutral-700">{{ translate('COM_EMUNDUS_ONBOARD_STATUS') }}</span>
            </template>
            <template slot-scope="scope">
              <p :class="'label label-'+scope.row.status_color" class="em-status">{{ scope.row.status }}</p>
            </template>
          </el-table-column>

          <el-table-column
              v-else-if="column.name === 'assocs'"
              min-width="180">
            <template slot="header" slot-scope="scope">
              <span :title="translate('COM_EMUNDUS_FILES_ASSOCS')"
                    class="tw-text-neutral-700">{{ translate('COM_EMUNDUS_FILES_ASSOCS') }}</span>
            </template>
            <template slot-scope="scope">
              <div class="em-group-assoc-column">
                <span v-for="group in scope.row.assocs" :class="group.class" class="em-status tw-mb-1">{{
                    group.label
                  }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column
              v-else-if="column.name === 'tags'"
              min-width="180">
            <template slot="header" slot-scope="scope">
              <span :title="translate('COM_EMUNDUS_FILES_TAGS')"
                    class="tw-text-neutral-700">{{ translate('COM_EMUNDUS_FILES_TAGS') }}</span>
            </template>
            <template slot-scope="scope">
              <div class="em-group-assoc-column">
                <span v-for="tag in scope.row.tags" :class="tag.class" class="em-status tw-mb-1">{{ tag.label }}</span>
              </div>
            </template>
          </el-table-column>

          <el-table-column
              v-else
              min-width="180"
              :prop="column.name"
              sortable
              :sort-method="(a, b) => sortBy(a, b, column.name)">
            <template slot="header" slot-scope="scope">
              <span :title="column.label" class="tw-text-neutral-700">{{ column.label }}</span>
            </template>
            <template slot-scope="scope">
              <p v-html="formatter(scope.row[column.name],column)"></p>
            </template>
          </el-table-column>
        </template>
      </el-table>

      <div id="table_columns_move_left" v-if="moveLeft" class="table-columns-move tw-flex tw-flex-col tw-ml-1"
           @click="scrollToLeft">
        <span class="material-symbols-outlined tw-cursor-pointer" style="font-size: 16px">arrow_forward</span>
      </div>
    </div>

    <div v-if="files && columns && files.length === 0">
      <h6>{{ translate('COM_EMUNDUS_ONBOARD_NOFILES') }}</h6>
    </div>

    <div v-if="rows_selected.length > 0" class="selected-rows-tip">
      <div class="selected-rows-tip__content tw-flex tw-items-center">
        <span v-if="rows_selected.length === 1">{{
            rows_selected.length
          }} {{ translate('COM_EMUNDUS_FILES_ELEMENT_SELECTED') }} :</span>
        <span v-else-if="rows_selected.length > 1">{{
            rows_selected.length
          }} {{ translate('COM_EMUNDUS_FILES_ELEMENTS_SELECTED') }} :</span>
        <a class="tw-cursor-pointer em-ml-16" @click="toggleSelection()">{{ translate('COM_EMUNDUS_FILES_UNSELECT') }}</a>
        <a class="tw-cursor-pointer em-ml-16" @click="openInNewTab()">{{ translate('COM_EMUNDUS_FILES_OPEN_IN_NEW_TAB') }}</a>
      </div>

    </div>

    <div class="em-page-loader" v-if="loading"></div>
  </div>
</template>

<script>
import Tabs from "@/components/Files/Tabs.vue";
// use element-plus instead
import { ElTable } from 'element-plus';

/** SERVICES **/
import filesService from '@/services/files.js'
import errors from '@/mixins/errors';
import Application from '@/components/Files/Application.vue';
import multiselect from 'vue-multiselect';
import Skeleton from '@/components/Skeleton.vue';

export default {
  name: 'Files',
  components: {
    Skeleton,
    Application,
    Tabs,
    'el-table': ElTable,
	  multiselect
  },
  props: {
    type: {
      String,
      default: ''
    },
    ratio: {
      type: String,
      default: '66/33'
    },
    user: {
      type: String,
      required: true,
    },
  },
  mixins: [errors],
  data: () => ({
    loading: false,
    filtersLoading: false,
    moveRight: false,
    moveLeft: true,
    scrolling: null,
    reloadFiles: 0,

    total_count: 0,
    tabs: [
      {
        label: 'COM_EMUNDUS_FILES_TO_EVALUATE',
        name: 'to_evaluate',
        total: 0,
        selected: false,
      },
      {
        label: 'COM_EMUNDUS_FILES_EVALUATED',
        name: 'evaluated',
        total: 0,
        selected: false,
      },
      {
        label: 'COM_EMUNDUS_FILES_ALL',
        name: 'all',
        total: 0,
        selected: false,
      },
    ],
    selected_tab: 0,
    files: null,
    columns: null,
    page: null,
    pages: null,
    limit: null,
    defaultFilters: [],
    filters: [],
    openedFilters: false,
    currentFile: null,
    rows_selected: [],
    error: {
      displayed: false,
      message: ''
    }
  }),
  created() {
    this.addKeyupEnterEventlistener();

    this.getLimit();
    this.getPage();
    if (this.$props.type === 'evaluation') {
      filesService.getSelectedTab(this.$props.type).then((tab) => {
        this.tabs.forEach((value, i) => {
          if (value.name === tab.data) {
            this.tabs[i].selected = true;
            this.selected_tab = i;
          }
        });

        this.getFiles();
      });
    }

  },
  methods: {
    formatter(row, column) {
      if (typeof row == 'string') {
        return row.charAt(0).toUpperCase() + row.slice(1);
      } else {
        return row;
      }
    },

    sortBy(a, b, prop) {
      if (prop === 'file') {
        if (a.applicant_name.toUpperCase() < b.applicant_name.toUpperCase()) return -1;
        if (a.applicant_name.toUpperCase() > b.applicant_name.toUpperCase()) return 1;
      }

      if (typeof a[prop] === 'string') {
        if (a[prop].toUpperCase() < b[prop].toUpperCase()) return -1;
        if (a[prop].toUpperCase() > b[prop].toUpperCase()) return 1;
      }

      if (a[prop] < b[prop]) return -1;
      if (a[prop] > b[prop]) return 1;
    },

    addKeyupEnterEventlistener() {
      window.document.addEventListener('keyup', (e) => {
        if (e.key === 'Enter') {
          e.preventDefault();
          e.stopPropagation();
        }
      });
    },
    getLimit() {
      filesService.getLimit(this.$props.type).then((limit) => {
        if (limit.status == 1) {
          this.limit = limit.data;
        } else {
          this.displayError('COM_EMUNDUS_ERROR_OCCURED', limit.msg);
        }
      });
    },
    getPage() {
      filesService.getPage(this.$props.type).then((page) => {
        if (page.status == 1) {
          this.page = page.data;
        } else {
          this.displayError('COM_EMUNDUS_ERROR_OCCURED', page.msg);
        }
      });
    },
    getFiles(refresh = false) {
      document.querySelector('body.layout-evaluation').style.overflow = 'visible';
      this.loading = true;
      this.error.displayed = false;
      this.error.message = '';

      let fnum = window.location.href.split('#')[1];
      if (typeof fnum == 'undefined') {
        fnum = '';
      }

      if (this.$props.type === 'evaluation') {
        filesService.getFiles(this.$props.type, refresh, this.limit, this.page).then((files) => {
          if (files.status == 1) {
            this.total_count = files.total;
            if (typeof files.data.all !== 'undefined') {
              this.files = files.data.all;
            } else {
              this.files = [];
            }
            this.tabs.forEach((tab, i) => {
              if (files[tab.name]) {
                this.tabs[i].total = files[tab.name].total;
              }
            })

            filesService.getColumns(this.$props.type).then((columns) => {
              this.columns = columns.data;

              if (fnum !== '') {
                this.openModal(fnum);
              }

              this.getFilters();
              this.loading = false;
              this.reloadFiles++;

              let total_pages = Math.ceil(this.tabs[this.selected_tab].total / this.limit);
              this.pages = Array.from(Array(total_pages).keys())
            });


          } else {
            this.loading = false;
            this.displayError('COM_EMUNDUS_ERROR_OCCURED', files.msg);
            this.error.displayed = true;
            this.error.message = files.msg;
          }
        });
      }
    },
    getFilters() {
      this.filtersLoading = true;
      filesService.getFilters().then((response) => {
        if (response.status == 1) {
          if (this.filters.length == 0 && response.data.applied_filters.length > 0) {
            response.data.applied_filters.forEach((applied_filter) => {
              const filter = response.data.default_filters.find((default_filter) => {
                return default_filter.id == applied_filter.id;
              });

              this.addFilter(filter, applied_filter.selectedValue, applied_filter.selectedOperator);
            });
          }

          this.defaultFilters = response.data.default_filters;
          this.filtersLoading = false;
        } else {
          this.displayError('COM_EMUNDUS_ERROR_OCCURED', response.msg);
          this.filtersLoading = false;
        }
      });
    },
    addFilter(filter, selectedValue = null, selectedOperator = null) {
      this.filters.push({
        id: filter.id,
        key: Math.random(),
        type: filter.type,
        values: filter.values,
        label: filter.label,
        selectedValue: selectedValue,
        operators: filter.operators,
        selectedOperator: selectedOperator === null ? filter.operators[0].value : selectedOperator
      });
    },
    removeFilter(filterToRemove) {
      this.filters.find((filter, index) => {
        if (filter.key == filterToRemove.key) {
          this.filters.splice(index, 1);
        }
      });
    },
    resetFilters() {
      if (this.filters.length > 0) {
        this.filters = [];

        filesService.applyFilters(this.filters).then((response) => {
          this.getFiles(true);
        });
      }
    },
    applyFilters() {
      const filtersToApply = this.filters.map((filter) => {
        if (filter.selectedValue !== null) {
          return {
            id: filter.id,
            type: filter.type,
            selectedValue: filter.selectedValue,
            selectedOperator: filter.selectedOperator
          }
        }
      });

      filesService.applyFilters(filtersToApply).then((response) => {
        this.getFiles(true);
      });
    },
    updateLimit(limit) {
      this.loading = true;
      filesService.updateLimit(limit).then((result) => {
        if (result.status == 1) {
          this.page = 0;
          this.getFiles(true);
        } else {
          this.loading = false;
          this.displayError('COM_EMUNDUS_ERROR_OCCURED', result.msg);
        }
      });
    },
    prevPage() {
      this.page--;
      this.updatePage();
    },
    nextPage() {
      this.page++;
      this.updatePage();
    },
    updatePage(page) {
      filesService.updatePage(page).then((result) => {
        if (result.status == 1) {
          this.getFiles(true);
        } else {
          this.loading = false;
          this.displayError('COM_EMUNDUS_ERROR_OCCURED', result.msg);
        }
      });
    },

    openModal(file) {
      this.currentFile = file;

      setTimeout(() => {
        this.$modal.show("application-modal");
      }, 500)
    },
    updateTab(tab) {
      this.selected_tab = this.tabs.map(e => e.name).indexOf(tab);

      filesService.setSelectedTab(tab).then(() => {
        this.getLimit();
        this.getPage();
        this.getFiles(true);
      });
    },
    openApplication(row) {
      this.openModal(row);
    },
    selectRow(selection, row) {
      this.rows_selected = selection;
    },
    toggleSelection() {
      this.$refs.tableFiles.clearSelection();
      this.rows_selected = [];
    },
    openInNewTab() {
      this.rows_selected.forEach((row) => {
        window.open(window.location.href + '#' + row.fnum, '_blank');
      });
    },
    scrollToLeft() {
      this.moveRight = true;

      let tableScroll = document.getElementsByClassName('el-table__body-wrapper')[0];
      tableScroll.scrollLeft += 180;
    },
    scrollToRight() {
      let tableScroll = document.getElementsByClassName('el-table__body-wrapper')[0];
      tableScroll.scrollLeft -= 180;
      if (tableScroll.scrollLeft == 0) {
        this.moveRight = false;
      }
    },

    stopScrolling() {
      clearInterval(this.scrolling);
      this.scrolling = null;
    },

    displayPage(page) {
      return page + 1;
    },

    onDefaultFiltersClickOutside() {
      if (this.openedFilters) {
        this.openedFilters = false;
      }
    }
  },
  watch: {
    limit: function (value, oldVal) {
      if (oldVal !== null && !this.loading) {
        this.updateLimit(value);
      }
    },
    page: function (value, oldVal) {
      if (oldVal !== null && !this.loading) {
        this.updatePage(value);
      }
    }
  },
}
</script>

<style lang="scss" scoped>
.em-files {
  width: 98% !important;
  margin: auto;
}

.table-columns-move {
  height: calc(100vh - 250px);
  border-radius: 8px;
  background: white;
  width: 24px;
}

select.em-select-no-border {
  background-color: transparent !important;
}

#filters {
  align-items: flex-start;

  #default-filters {
    position: relative;

    ul {
      position: absolute;
      top: 50px;
      z-index: 5;
      background-color: white;
      margin: 0;
      padding: 0;
      list-style-type: none;
      min-width: 300px;
      max-height: 500px;
      overflow-y: scroll;

      li {
        padding: 8px;
        transition: all .3s;

        &:hover {
          background: ghostwhite;
        }
      }
    }
  }

  #applied-filters {
    max-width: 90%;
    flex-wrap: wrap;

    .applied-filter {
      padding: 8px 0 8px 0;
      border-bottom: solid 1px var(--neutral-400);
    }

    .multiselect {
      height: 40px !important;

      .multiselect__tags {
        height: 40px !important;

        .multiselect__tags-wrap {
          height: 24px !important;
        }
      }
    }
  }
}

.filter-label {
  min-width: 100px;
  max-width: 220px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.em-group-assoc-column {
  display: flex;
  flex-direction: column;
  overflow-y: scroll;
  height: 75px;
  scrollbar-width: none;
}

.em-group-assoc-column::-webkit-scrollbar {
  display: none;
}
</style>