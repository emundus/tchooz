<template>
  <div class="tchooz-widget" :class="[selectedWidget.class]">
    <div
      class="section-sub-menu"
      style="margin-bottom: 10px"
      :class="
        selectedWidget.type === 'article'
          ? 'tw-overflow-y-auto tw-overflow-x-hidden'
          : ''
      "
    >
      <div id="chart-container" class="tw-bg-white tw-relative" v-if="selectedWidget.type === 'chart'">

        <div v-if="loading" class="tw-absolute tw-h-full tw-w-full tw-flex tw-items-center tw-justify-center tw-z-10 tw-rounded-coordinator-cards">
          <div class="em-loader">
          </div>
        </div>

        <div class="tw-flex tw-items-center tw-float-right tw-justify-end tw-w-full tw-gap-3 tw-mb-3" v-if="!loading">
          <div id="multi-filters" class="tw-flex tw-items-center">
            <div v-for="filter in notEmptyFilters" :key="filter.key">
              <multiselect v-if="selectedFilters[filter.key] !== undefined"
                :id="filter.key"
                v-model="selectedFilters[filter.key]"
                class="tw-relative tw-cursor-pointer tw-right-0 tw-top-0 !tw-w-[220px]"
                label="label"
                track-by="value"
                :options="filter.options"
                :multiple="true"
                :taggable="false"
                :placeholder="translate('COM_EMUNDUS_DASHBOARD_SELECT_FILTER')"
                select-label=""
                selected-label=""
                deselect-label=""
                :close-on-select="true"
                :clear-on-select="false"
                :searchable="true"
                @select="onSelectFilter"
                @remove="onUnSelectFilter"
              >
              </multiselect>
            </div>
          </div>
          <select
            class="tw-h-form tw-rounded-coordinator tw-py-2 tw-px-1.5 tw-cursor-pointer"
            v-model="selectedWidgetId"
            @change="updateWidgetRender"
          >
            <option v-for="widget in widgets" :value="widget.id"
              >{{ widget.label }}
            </option>
          </select>
        </div>

        <div style="min-height: 350px" class="tw-w-full">
          <fusioncharts
              v-if="!loading && chart_render !== 0"
              :key="chart_render"
              :type="chart_type"
              :width="'100%'"
              :height="'300'"
              :dataFormat="dataFormat"
              :dataSource="dataSource"
          >
          </fusioncharts>
        </div>
      </div>
      <div v-else :class="selectedWidget.class">
        <div v-html="datas"></div>
      </div>
    </div>
  </div>
</template>

<script>
import Multiselect from "vue-multiselect";

export default {
  name: "Custom",

  components: {
    Multiselect,
  },

  props: {
    widget: Object,
    colors: String
  },

  data: () => ({
    widgets: [],
    chart_render: 0,
    position: null,
    selectedWidget: null,
    selectedWidgetId: null,
    selectedFilters: {
      campaign_id: [],
      status: []
    },
    filters: [],
    widget_filters: {},

    loading: false,
    // Fusion charts variables
    datas: {},
    chart_type: "column2d",
    renderAt: "chart-container",
    dataFormat: "json",
    dataSource: {},
    chart_values: []
  }),

  created() {
    this.selectedWidget = this.widget;
    this.selectedWidgetId = this.widget.id;
    this.position = this.selectedWidget.position;

    this.render();
    this.getWidgets();
  },

  methods: {
    render() {
      switch (this.selectedWidget.type) {
        case "article":
          this.getArticle();
          break;
        case "other":
          this.getEval();
          break;
        case "chart":
          this.getFilters().then(() => {
            this.renderChart();
          });
          break;
        default:
          this.getEval();
      }
    },

    renderChart(filter_key = null) {
      this.dataSource = {};
      this.loading = true;

      let chartFilters = {};
      if (filter_key !== null && this.selectedFilters[filter_key]) {
        chartFilters[filter_key] = {};
        chartFilters[filter_key]['value'] = this.selectedFilters[filter_key].map(option => option.value);
      } else if (this.widget_filters) {
        chartFilters = this.widget_filters;
      }

      let formData = new FormData();
      formData.append("widget", this.selectedWidget.id);
      formData.append("filters", JSON.stringify(chartFilters));

      fetch(
        "/index.php?option=com_emundus&controller=dashboard&task=renderchartbytag",
        {
          method: "POST",
          body: formData
        }
      )
        .then(response => response.json())
        .then(data => {
          this.chart_type = this.selectedWidget.chart_type;
          this.dataSource = data.dataset;

          if (typeof this.dataSource.filters !== "undefined" && this.dataSource.filters.length > 0) {

            for(const filter of this.dataSource.filters) {
              if (typeof this.selectedFilters[filter.key] == "undefined" || this.selectedFilters[filter.key] == null) {
                this.selectedFilters[filter.key] = [];

                if(this.widget_filters[filter.key]) {
                  this.selectedFilters[filter.key] = filter.options.filter((option) => this.widget_filters[filter.key].value.includes(option.value));
                }
              }
            }

            this.filters = this.dataSource.filters;
          } else {
            this.filters = [];
          }

          this.chart_render++;
          this.loading = false;
        })
        .catch(error => {
          this.loading = false;
        });
    },

    getArticle() {
      fetch(
        "/index.php?option=com_emundus&controller=dashboard&task=getarticle&widget="+this.selectedWidget.id+'&article='+this.selectedWidget.article_id
      )
        .then(response => response.json())
        .then(data => {
          this.datas = data.data;
        });
    },

    getEval() {
      fetch(
        "/index.php?option=com_emundus&controller=dashboard&task=geteval&widget="+this.selectedWidget.id
      )
        .then(response => response.json())
        .then(data => {
          this.datas = data.data;
        });
    },

    getWidgets() {
      fetch(
        "/index.php?option=com_emundus&controller=dashboard&task=getwidgets&all=true"
      )
        .then(response => response.json())
        .then(data => {
          this.widgets = data.data;
        });
    },

    updateDashboard() {
      let formData = new FormData();
      formData.append("widget", this.selectedWidget.id);
      formData.append("position", this.position);

      fetch(
        "/index.php?option=com_emundus&controller=dashboard&task=updatemydashboard",
        {
          method: "POST",
          body: formData
        }
      )
        .then(() => {
          this.render();
        })
        .catch(error => {});
    },

    async getFilters() {
      return new Promise((resolve, reject) => {
        fetch(
          "/index.php?option=com_emundus&controller=dashboard&task=getfilters&widget="+this.selectedWidget.id
        )
          .then(response => response.json())
          .then(data => {
            if (data.filters !== 'null') {
              let filters = JSON.parse(data.filters);
              Object.keys(filters).forEach(key => {
                this.widget_filters[key] = filters[key];
              });
            } else {
              this.widget_filters = {};
            }

            resolve(true);
          })
          .catch(error => {
            reject(error);
          });
      });
    },
    updateWidgetRender() {
      this.selectedWidget = this.widgets.find(
        widget => widget.id == this.selectedWidgetId
      );
      if (this.chart_render !== 0) {
        this.updateDashboard();
      }
    },
    onSelectFilter(selectedOption, id) {
      if (this.chart_render !== 0 && selectedOption.value) {
        this.renderChart(id);
      }
    },
    onUnSelectFilter(removedOption, id) {
      if (this.chart_render !== 0) {
        this.renderChart(id);
      }
    }
  },
  computed: {
    notEmptyFilters() {
      return this.filters.filter(filter => {
        if (
          typeof this.selectedFilters[filter.key] == "undefined" ||
          this.selectedFilters[filter.key] == null
        ) {
          this.selectedFilters[filter.key] = [];
        }

        return filter.options.length > 0;
      });
    }
  }
};
</script>

<style scoped>
.section-sub-menu {
  display: block;
  width: 100%;
  height: 100%;
  justify-content: center;
  border-radius: var(--em-coordinator-br-cards);
  background-color: #fff;
  color: #1f1f1f;
  box-shadow: var(--em-box-shadow-x-1) var(--em-box-shadow-y-1)
      var(--em-box-shadow-blur-1) var(--em-box-shadow-color-1),
    var(--em-box-shadow-x-2) var(--em-box-shadow-y-2)
      var(--em-box-shadow-blur-2) var(--em-box-shadow-color-2),
    var(--em-box-shadow-x-3) var(--em-box-shadow-y-3)
      var(--em-box-shadow-blur-3) var(--em-box-shadow-color-3);
  padding: 30px;
}
</style>
