import { _ as _export_sfc, o as openBlock, c as createElementBlock, a as createBaseVNode, b as Fragment, r as renderList, t as toDisplayString, U as Popover, y as useGlobalStore, s as settingsService, a6 as ref, S as Swal, h as resolveComponent, i as createBlock, d as createCommentVNode, m as createVNode, n as normalizeClass, w as withDirectives, u as vModelSelect, N as vModelText, j as withCtx } from "./app_emundus.js";
import { S as Skeleton } from "./Skeleton.js";
const _sfc_main$1 = {
  name: "Gantt",
  props: {
    defaultDisplay: {
      type: String,
      default: "year"
    },
    defaultStartDate: {
      type: String,
      default: /* @__PURE__ */ new Date()
    },
    defaultEndDate: {
      type: String,
      default: (/* @__PURE__ */ new Date()).setDate((/* @__PURE__ */ new Date()).getDate() + 365)
    },
    language: {
      type: String,
      default: "fr-FR"
    },
    periods: {
      type: Array,
      required: true
    }
  },
  data() {
    return {
      display: this.defaultDisplay,
      displayValues: ["year", "month", "week", "day"],
      dateRange: []
    };
  },
  mounted() {
    this.setDateRange(this.defaultStartDate, this.defaultEndDate);
  },
  methods: {
    changeDisplay(value) {
      if (this.displayValues.includes(value)) {
        this.display = value;
      }
    },
    setDateRange(startDate, endDate) {
      if (startDate && endDate) {
        switch (this.display) {
          case "year":
            const start = new Date(startDate);
            const end = new Date(endDate);
            let date = new Date(start);
            this.dateRange = [];
            while (date <= end) {
              const formattedString = new Intl.DateTimeFormat(this.language, { month: "short", year: "numeric" }).format(date);
              this.dateRange.push(formattedString);
              date.setMonth(date.getMonth() + 1);
            }
            break;
        }
      }
    }
  }
};
const _hoisted_1$1 = { id: "gantt-view" };
const _hoisted_2$1 = {
  id: "gantt-head",
  class: "tw-flex tw-flex-row"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    _cache[0] || (_cache[0] = createBaseVNode("div", { id: "gantt-options" }, null, -1)),
    createBaseVNode("div", _hoisted_2$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.dateRange, (value) => {
        return openBlock(), createElementBlock("span", { key: value }, toDisplayString(value), 1);
      }), 128))
    ]),
    _cache[1] || (_cache[1] = createBaseVNode("div", { id: "gantt-rows" }, null, -1))
  ]);
}
const Gantt = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const List_vue_vue_type_style_index_0_lang = "";
const _sfc_main = {
  name: "list",
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
        "lists": false,
        "tabs": false,
        "items": false
      },
      numberOfItemsToDisplay: 25,
      lists: {},
      type: "forms",
      params: {},
      currentList: { "title": "", "tabs": [] },
      selectedListTab: 0,
      items: {},
      title: "",
      viewType: "table",
      viewTypeOptions: [
        { value: "table", icon: "dehaze" },
        { value: "blocs", icon: "grid_view" }
        /*{value: 'gantt', icon: 'view_timeline'}*/
      ],
      searches: {},
      filters: {},
      alertBannerDisplayed: false
    };
  },
  created() {
    const alertMessageContainer = document.querySelector(".alerte-message-container");
    if (alertMessageContainer) {
      this.alertBannerDisplayed = true;
      alertMessageContainer.querySelector("#close-preprod-alerte-container").addEventListener("click", () => {
        this.alertBannerDisplayed = false;
      });
    }
    this.loading.lists = true;
    this.loading.tabs = true;
    const globalStore = useGlobalStore();
    if (this.defaultType !== null) {
      this.params = {
        "type": this.defaultType,
        "shortlang": globalStore.getShortLang
      };
    } else {
      const data = globalStore.getDatas;
      this.params = Object.assign({}, ...Array.from(data).map(({ name, value }) => ({ [name]: value })));
    }
    this.type = this.params.type;
    this.viewType = localStorage.getItem("tchooz_view_type/" + document.location.hostname);
    if (this.viewType === null || typeof this.viewType === "undefined" || this.viewType !== "blocs" && this.viewType !== "table") {
      this.viewType = "blocs";
      localStorage.setItem("tchooz_view_type/" + document.location.hostname, "blocs");
    }
    const storageNbItemsDisplay = localStorage.getItem("tchooz_number_of_items_to_display/" + document.location.hostname);
    if (storageNbItemsDisplay !== null) {
      this.numberOfItemsToDisplay = storageNbItemsDisplay !== "all" ? parseInt(storageNbItemsDisplay) : storageNbItemsDisplay;
    }
    this.initList();
  },
  methods: {
    initList() {
      if (this.defaultLists !== null) {
        this.lists = JSON.parse(atob(this.defaultLists));
        if (typeof this.lists[this.type] === "undefined") {
          console.error("List type " + this.type + " does not exist");
          window.location.href = "/";
        }
        this.currentList = this.lists[this.type];
        if (Object.prototype.hasOwnProperty.call(this.params, "tab")) {
          this.onSelectTab(this.params.tab);
        } else {
          const sessionTab = sessionStorage.getItem("tchooz_selected_tab/" + document.location.hostname);
          if (sessionTab !== null && typeof this.currentList.tabs.find((tab) => tab.key === sessionTab) !== "undefined") {
            this.onSelectTab(sessionTab);
          } else {
            this.onSelectTab(this.currentList.tabs[0].key);
          }
        }
        this.loading.lists = false;
        this.getListItems();
      } else {
        this.getLists();
      }
    },
    getLists() {
      settingsService.getOnboardingLists().then((response) => {
        if (response.status) {
          this.lists = response.data;
          if (typeof this.lists[this.type] === "undefined") {
            console.error("List type " + this.type + " does not exist");
            window.location.href = "/";
          }
          this.currentList = this.lists[this.type];
          if (Object.prototype.hasOwnProperty.call(this.params, "tab")) {
            this.onSelectTab(this.params.tab);
          } else {
            const sessionTab = sessionStorage.getItem("tchooz_selected_tab/" + document.location.hostname);
            if (sessionTab !== null && typeof this.currentList.tabs.find((tab) => tab.key === sessionTab) !== "undefined") {
              this.onSelectTab(sessionTab);
            } else {
              this.onSelectTab(this.currentList.tabs[0].key);
            }
          }
          this.loading.lists = false;
          this.getListItems();
        } else {
          console.error("Error while getting onboarding lists");
          this.loading.lists = false;
        }
      });
    },
    getListItems(page = 1, tab = null) {
      if (tab === null) {
        this.loading.tabs = true;
        this.items = ref(Object.assign({}, ...this.currentList.tabs.map((tab2) => ({ [tab2.key]: [] }))));
      } else {
        this.loading.items = true;
      }
      const tabs = tab === null ? this.currentList.tabs : [this.currentTab];
      if (tabs.length > 0) {
        tabs.forEach((tab2) => {
          if (typeof this.searches[tab2.key] === "undefined") {
            this.searches[tab2.key] = {
              search: "",
              lastSearch: "",
              debounce: null
            };
          }
          const searchValue = sessionStorage.getItem("tchooz_filter_" + this.selectedListTab + "_search/" + document.location.hostname);
          if (searchValue !== null) {
            this.searches[this.selectedListTab].search = searchValue;
            this.searches[this.selectedListTab].lastSearch = searchValue;
          }
          this.setTabFilters(tab2);
          if (typeof tab2.getter !== "undefined") {
            let url = "index.php?option=com_emundus&controller=" + tab2.controller + "&task=" + tab2.getter + "&lim=" + this.numberOfItemsToDisplay + "&page=" + page;
            if (this.searches[tab2.key].search !== "") {
              url += "&recherche=" + this.searches[tab2.key].search;
            }
            if (typeof this.filters[tab2.key] !== "undefined") {
              this.filters[tab2.key].forEach((filter) => {
                if (filter.value !== "" && filter.value !== "all") {
                  url += "&" + filter.key + "=" + filter.value;
                }
              });
            }
            try {
              fetch(url).then((response) => response.json()).then((response) => {
                if (response.status === true) {
                  if (typeof response.data.datas !== "undefined") {
                    this.items[tab2.key] = response.data.datas;
                    tab2.pagination = {
                      current: page,
                      total: Math.ceil(response.data.count / this.numberOfItemsToDisplay)
                    };
                  }
                } else {
                  console.error("Failed to get data : " + response.msg);
                }
                this.loading.tabs = false;
                this.loading.items = false;
              }).catch((error) => {
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
      if (typeof tab.filters !== "undefined" && tab.filters.length > 0) {
        if (typeof this.filters[tab.key] === "undefined") {
          this.filters[tab.key] = [];
          tab.filters.forEach((filter) => {
            let filterValue = sessionStorage.getItem("tchooz_filter_" + this.selectedListTab + "_" + filter.key + "/" + document.location.hostname);
            if (filterValue == null) {
              filterValue = filter.default ? filter.default : "all";
            }
            if (filter.values === null) {
              if (filter.getter) {
                this.filters[tab.key].push({
                  key: filter.key,
                  value: filterValue,
                  options: []
                });
                this.setFilterOptions(typeof filter.controller !== "undefined" ? filter.controller : tab.controller, filter, tab.key);
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
      return await fetch("index.php?option=com_emundus&controller=" + controller + "&task=" + filter.getter).then((response) => response.json()).then((response) => {
        if (response.status === true) {
          let options = response.data;
          if (typeof options[0] === "string") {
            options = options.map((option) => ({ value: option, label: option }));
          }
          options.unshift({ value: "all", label: this.translate(filter.label) });
          this.filters[tab].find((f) => f.key === filter.key).options = options;
        } else {
          return [];
        }
      });
    },
    searchItems() {
      if (this.searches[this.selectedListTab].searchDebounce !== null) {
        clearTimeout(this.searches[this.selectedListTab].searchDebounce);
      }
      if (this.searches[this.selectedListTab].search === "") {
        sessionStorage.removeItem("tchooz_filter_" + this.selectedListTab + "_search/" + document.location.hostname);
      } else {
        sessionStorage.setItem("tchooz_filter_" + this.selectedListTab + "_search/" + document.location.hostname, this.searches[this.selectedListTab].search);
      }
      this.searches[this.selectedListTab].searchDebounce = setTimeout(() => {
        if (this.searches[this.selectedListTab].search !== this.searches[this.selectedListTab].lastSearch) {
          this.searches[this.selectedListTab].lastSearch = this.searches[this.selectedListTab].search;
          this.getListItems(1, this.selectedListTab);
        }
      }, 500);
    },
    onClickAction(action, itemId = null) {
      if (action === null || typeof action !== "object") {
        return false;
      }
      let item = null;
      if (itemId !== null) {
        item = this.items[this.selectedListTab].find((item2) => item2.id === itemId);
      }
      if (action.type === "redirect") {
        let url = action.action;
        if (item !== null) {
          Object.keys(item).forEach((key) => {
            url = url.replace("%" + key + "%", item[key]);
          });
        }
        settingsService.redirectJRoute(url, useGlobalStore().getCurrentLang);
      } else {
        let url = "index.php?option=com_emundus&controller=" + action.controller + "&task=" + action.action;
        if (itemId !== null) {
          if (action.parameters) {
            let url_parameters = action.parameters;
            if (item !== null) {
              Object.keys(item).forEach((key) => {
                url_parameters = url_parameters.replace("%" + key + "%", item[key]);
              });
            }
            url += url_parameters;
          } else {
            url += "&id=" + itemId;
          }
        }
        if (Object.prototype.hasOwnProperty.call(action, "confirm")) {
          Swal.fire({
            icon: "warning",
            title: this.translate(action.label),
            text: this.translate(action.confirm),
            showCancelButton: true,
            confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_OK"),
            cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              cancelButton: "em-swal-cancel-button",
              actions: "em-swal-double-action"
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
      fetch(url).then((response) => response.json()).then((response) => {
        if (response.status === true || response.status === 1) {
          if (response.redirect) {
            window.location.href = response.redirect;
          }
          this.getListItems();
        } else {
          if (response.msg) {
            Swal.fire({
              icon: "error",
              title: this.translate(response.msg),
              reverseButtons: true,
              customClass: {
                title: "em-swal-title",
                confirmButton: "em-swal-confirm-button",
                actions: "em-swal-single-action"
              }
            });
          }
        }
        this.loading.items = false;
      }).catch((error) => {
        console.error(error);
        this.loading.items = false;
      });
    },
    onClickPreview(item) {
      if (this.previewAction && (this.previewAction.title || this.previewAction.content)) {
        Swal.fire({
          title: item[this.previewAction.title],
          html: '<div style="text-align: left;">' + item[this.previewAction.content] + "</div>",
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
      }
    },
    onChangeFilter(filter) {
      sessionStorage.setItem("tchooz_filter_" + this.selectedListTab + "_" + filter.key + "/" + document.location.hostname, filter.value);
      this.getListItems(1, this.selectedListTab);
    },
    onSelectTab(tabKey) {
      let selected = false;
      if (this.selectedListTab !== tabKey) {
        if (this.currentList.tabs.find((tab) => tab.key === tabKey) !== "undefined") {
          this.selectedListTab = tabKey;
          sessionStorage.setItem("tchooz_selected_tab/" + document.location.hostname, tabKey);
          selected = true;
        }
      }
      return selected;
    },
    changeViewType(viewType) {
      this.viewType = viewType.value;
      localStorage.setItem("tchooz_view_type/" + document.location.hostname, viewType.value);
    },
    filterShowOnActions(actions, item) {
      return actions.filter((action) => {
        if (Object.prototype.hasOwnProperty.call(action, "showon")) {
          return this.evaluateShowOn(item, action.showon);
        }
        return true;
      });
    },
    evaluateShowOn(item, showon) {
      let show = true;
      switch (showon.operator) {
        case "==":
        case "=":
          show = item[showon.key] == showon.value;
          break;
        case "!=":
          show = item[showon.key] != showon.value;
          break;
        case ">":
          show = item[showon.key] > showon.value;
          break;
        case "<":
          show = item[showon.key] < showon.value;
          break;
        case ">=":
          show = item[showon.key] >= showon.value;
          break;
        case "<=":
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
          return column.display === viewType || column.display === "all";
        });
      }
      return columns;
    },
    displayLongValue(html) {
      Swal.fire({
        html: '<div style="text-align: left;">' + html + "</div>",
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          confirmButton: "em-swal-confirm-button",
          actions: "em-swal-single-action"
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
      return typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.filter((action) => {
        return !["add", "edit", "preview"].includes(action.name) && !Object.prototype.hasOwnProperty.call(action, "icon");
      }) : [];
    },
    editAction() {
      return typeof this.currentTab !== "undefined" && typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.find((action) => {
        return action.name === "edit";
      }) : false;
    },
    addAction() {
      return typeof this.currentTab !== "undefined" && typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.find((action) => {
        return action.name === "add";
      }) : false;
    },
    previewAction() {
      return typeof this.currentTab !== "undefined" && typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.find((action) => {
        return action.name === "preview";
      }) : false;
    },
    iconActions() {
      return typeof this.currentTab.actions !== "undefined" ? this.currentTab.actions.filter((action) => {
        return !["add", "edit", "preview"].includes(action.name) && Object.prototype.hasOwnProperty.call(action, "icon");
      }) : [];
    },
    displayedItems() {
      let items = typeof this.items[this.selectedListTab] !== "undefined" ? this.items[this.selectedListTab] : [];
      return items;
    },
    additionalColumns() {
      let columns = [];
      let items = typeof this.items[this.selectedListTab] !== "undefined" ? this.items[this.selectedListTab] : [];
      if (items.length > 0 && items[0].additional_columns && items[0].additional_columns.length > 0) {
        items[0].additional_columns.forEach((column) => {
          if (column.display === "all" || column.display === this.viewType) {
            columns.push(column.key);
          }
        });
      }
      return columns;
    },
    noneDiscoverTranslation() {
      let translation = '<img src="/media/com_emundus/images/tchoozy/complex-illustrations/no-result.svg" alt="empty-list" style="width: 10vw; height: 10vw; margin: 0 auto;">';
      if (this.type === "campaigns") {
        if (this.currentTab.key === "programs") {
          translation += "<span>" + this.translate("COM_EMUNDUS_ONBOARD_NOPROGRAM") + "</span>";
        } else {
          translation += "<span>" + this.translate("COM_EMUNDUS_ONBOARD_NOCAMPAIGN") + "</span>";
        }
      } else if (this.type === "emails") {
        translation += "<span>" + this.translate("COM_EMUNDUS_ONBOARD_NOEMAIL") + "</span>";
      } else if (this.type === "forms") {
        translation += "<span>" + this.translate("COM_EMUNDUS_ONBOARD_NOFORM") + "</span>";
      }
      return translation;
    },
    displayedFilters() {
      return this.filters && this.filters[this.selectedListTab] ? this.filters[this.selectedListTab].filter((filter) => filter.options.length > 0) : [];
    }
  },
  watch: {
    numberOfItemsToDisplay() {
      localStorage.setItem("tchooz_number_of_items_to_display/" + document.location.hostname, this.numberOfItemsToDisplay);
    }
  }
};
const _hoisted_1 = {
  key: 1,
  class: "head tw-flex tw-items-center tw-justify-between"
};
const _hoisted_2 = {
  key: 2,
  id: "tabs-loading"
};
const _hoisted_3 = { class: "tw-flex tw-justify-between" };
const _hoisted_4 = {
  key: 3,
  class: "list tw-mt-4"
};
const _hoisted_5 = {
  key: 0,
  id: "list-nav"
};
const _hoisted_6 = {
  style: { "list-style-type": "none", "margin-left": "0", "padding-left": "0" },
  class: "tw-flex"
};
const _hoisted_7 = ["onClick"];
const _hoisted_8 = {
  id: "actions",
  class: "tw-flex tw-justify-between tw-mt-4 tw-mb-4"
};
const _hoisted_9 = { id: "tab-actions" };
const _hoisted_10 = ["onUpdate:modelValue", "onChange"];
const _hoisted_11 = ["value"];
const _hoisted_12 = {
  id: "default-actions",
  class: "tw-flex"
};
const _hoisted_13 = { class: "tw-flex tw-items-center" };
const _hoisted_14 = ["placeholder", "disabled"];
const _hoisted_15 = { class: "view-type tw-flex tw-items-center" };
const _hoisted_16 = ["onClick"];
const _hoisted_17 = {
  key: 1,
  id: "pagination-wrapper",
  class: "tw-flex tw-justify-end tw-items-center tw-mb-3"
};
const _hoisted_18 = { value: "10" };
const _hoisted_19 = { value: "25" };
const _hoisted_20 = { value: "50" };
const _hoisted_21 = { value: "all" };
const _hoisted_22 = {
  key: 0,
  id: "pagination",
  class: "tw-text-center"
};
const _hoisted_23 = { class: "tw-flex tw-list-none tw-gap-1" };
const _hoisted_24 = ["onClick"];
const _hoisted_25 = { key: 3 };
const _hoisted_26 = {
  key: 0,
  id: "list-items"
};
const _hoisted_27 = { key: 0 };
const _hoisted_28 = ["id"];
const _hoisted_29 = ["onClick"];
const _hoisted_30 = ["title"];
const _hoisted_31 = ["innerHTML"];
const _hoisted_32 = { key: 1 };
const _hoisted_33 = ["onClick", "innerHTML"];
const _hoisted_34 = ["innerHTML"];
const _hoisted_35 = {
  key: 0,
  class: "tw-w-full tw-mt-1.5 tw-mb-3"
};
const _hoisted_36 = { class: "actions" };
const _hoisted_37 = ["onClick"];
const _hoisted_38 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_39 = ["onClick"];
const _hoisted_40 = ["onClick"];
const _hoisted_41 = {
  style: { "list-style-type": "none", "margin": "0", "padding-left": "0px" },
  class: "em-flex-col-center"
};
const _hoisted_42 = ["onClick"];
const _hoisted_43 = ["innerHTML"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_skeleton = resolveComponent("skeleton");
  const _component_popover = resolveComponent("popover");
  const _component_Gantt = resolveComponent("Gantt");
  return openBlock(), createElementBlock("div", {
    id: "onboarding_list",
    class: normalizeClass(["tw-w-full", { "alert-banner-displayed": $data.alertBannerDisplayed }])
  }, [
    $data.loading.lists ? (openBlock(), createBlock(_component_skeleton, {
      key: 0,
      height: "40px",
      width: "100%",
      class: "tw-mb-4 tw-mt-4 tw-rounded-lg"
    })) : (openBlock(), createElementBlock("div", _hoisted_1, [
      createBaseVNode("h1", null, toDisplayString(_ctx.translate($data.currentList.title)), 1),
      $options.addAction ? (openBlock(), createElementBlock("a", {
        key: 0,
        id: "add-action-btn",
        class: "tw-btn-primary tw-w-auto tw-cursor-pointer",
        onClick: _cache[0] || (_cache[0] = ($event) => $options.onClickAction($options.addAction))
      }, toDisplayString(_ctx.translate($options.addAction.label)), 1)) : createCommentVNode("", true)
    ])),
    $data.loading.tabs ? (openBlock(), createElementBlock("div", _hoisted_2, [
      createBaseVNode("div", _hoisted_3, [
        createVNode(_component_skeleton, {
          height: "40px",
          width: "20%",
          class: "tw-mb-4 tw-rounded-lg"
        }),
        createVNode(_component_skeleton, {
          height: "40px",
          width: "5%",
          class: "tw-mb-4 tw-rounded-lg"
        })
      ]),
      createBaseVNode("div", {
        class: normalizeClass({ "skeleton-grid": $data.viewType === "blocs", "tw-flex tw-flex-col": $data.viewType === "list" }),
        style: { "flex-wrap": "wrap" }
      }, [
        (openBlock(), createElementBlock(Fragment, null, renderList(9, (i) => {
          return createVNode(_component_skeleton, {
            key: i,
            class: "tw-rounded-lg skeleton-item"
          });
        }), 64))
      ], 2)
    ])) : (openBlock(), createElementBlock("div", _hoisted_4, [
      $data.currentList.tabs.length > 1 ? (openBlock(), createElementBlock("nav", _hoisted_5, [
        createBaseVNode("ul", _hoisted_6, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.currentList.tabs, (tab) => {
            return openBlock(), createElementBlock("li", {
              key: tab.key,
              class: normalizeClass(["tw-cursor-pointer tw-font-normal", {
                "em-light-tabs em-light-selected-tab": $data.selectedListTab === tab.key,
                "em-light-tabs ": $data.selectedListTab !== tab.key
              }]),
              onClick: ($event) => $options.onSelectTab(tab.key)
            }, toDisplayString(_ctx.translate(tab.title)), 11, _hoisted_7);
          }), 128))
        ])
      ])) : createCommentVNode("", true),
      createBaseVNode("section", _hoisted_8, [
        createBaseVNode("section", _hoisted_9, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedFilters, (filter) => {
            return withDirectives((openBlock(), createElementBlock("select", {
              key: $data.selectedListTab + "-" + filter.key,
              "onUpdate:modelValue": ($event) => filter.value = $event,
              onChange: ($event) => $options.onChangeFilter(filter),
              class: "tw-mr-2"
            }, [
              (openBlock(true), createElementBlock(Fragment, null, renderList(filter.options, (option) => {
                return openBlock(), createElementBlock("option", {
                  key: option.value,
                  value: option.value
                }, toDisplayString(_ctx.translate(option.label)), 9, _hoisted_11);
              }), 128))
            ], 40, _hoisted_10)), [
              [vModelSelect, filter.value]
            ]);
          }), 128))
        ]),
        createBaseVNode("section", _hoisted_12, [
          createBaseVNode("div", _hoisted_13, [
            withDirectives(createBaseVNode("input", {
              name: "search",
              type: "text",
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.searches[$data.selectedListTab].search = $event),
              placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_SEARCH"),
              class: normalizeClass(["tw-rounded-lg", { "em-disabled-events": $data.items[this.selectedListTab].length < 1 && $data.searches[$data.selectedListTab].search === "" }]),
              style: { "margin": "0" },
              disabled: $data.items[this.selectedListTab].length < 1 && $data.searches[$data.selectedListTab].search === "",
              onChange: _cache[2] || (_cache[2] = (...args) => $options.searchItems && $options.searchItems(...args)),
              onKeyup: _cache[3] || (_cache[3] = (...args) => $options.searchItems && $options.searchItems(...args))
            }, null, 42, _hoisted_14), [
              [vModelText, $data.searches[$data.selectedListTab].search]
            ]),
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-mr-2 tw-cursor-pointer",
              style: { "margin-left": "-32px" },
              onClick: _cache[4] || (_cache[4] = (...args) => $options.searchItems && $options.searchItems(...args))
            }, " search ")
          ]),
          createBaseVNode("div", _hoisted_15, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.viewTypeOptions, (viewTypeOption) => {
              return openBlock(), createElementBlock("span", {
                key: viewTypeOption.value,
                style: { "padding": "4px", "border-radius": "calc(var(--em-default-br)/2)", "display": "flex", "height": "38px", "width": "38px", "align-items": "center", "justify-content": "center", "background": "var(--neutral-0)" },
                class: normalizeClass(["material-symbols-outlined tw-ml-2 tw-cursor-pointer", {
                  "active em-main-500-color em-border-main-500": viewTypeOption.value === $data.viewType,
                  "em-neutral-600-color em-border-neutral-600": viewTypeOption.value !== $data.viewType
                }]),
                onClick: ($event) => $options.changeViewType(viewTypeOption)
              }, toDisplayString(viewTypeOption.icon), 11, _hoisted_16);
            }), 128))
          ])
        ])
      ]),
      this.items[this.selectedListTab].length > 0 ? (openBlock(), createElementBlock("section", _hoisted_17, [
        withDirectives(createBaseVNode("select", {
          name: "numberOfItemsToDisplay",
          "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.numberOfItemsToDisplay = $event),
          onChange: _cache[6] || (_cache[6] = ($event) => $options.getListItems())
        }, [
          createBaseVNode("option", _hoisted_18, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_RESULTS")) + " 10", 1),
          createBaseVNode("option", _hoisted_19, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_RESULTS")) + " 25", 1),
          createBaseVNode("option", _hoisted_20, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_RESULTS")) + " 50", 1),
          createBaseVNode("option", _hoisted_21, toDisplayString(_ctx.translate("ALL")), 1)
        ], 544), [
          [vModelSelect, $data.numberOfItemsToDisplay]
        ]),
        typeof $options.currentTab.pagination !== void 0 && $options.currentTab.pagination && $options.currentTab.pagination.total > 1 ? (openBlock(), createElementBlock("div", _hoisted_22, [
          createBaseVNode("ul", _hoisted_23, [
            createBaseVNode("span", {
              class: normalizeClass([{ "tw-text-neutral-600 em-disabled-events": $options.currentTab.pagination.current === 1 }, "material-symbols-outlined tw-cursor-pointer tw-mr-2 tw-items-center"]),
              style: { "display": "flex" },
              onClick: _cache[7] || (_cache[7] = ($event) => $options.getListItems($options.currentTab.pagination.current - 1, $data.selectedListTab))
            }, " chevron_left ", 2),
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.currentTab.pagination.total, (i) => {
              return openBlock(), createElementBlock("li", {
                key: i,
                class: normalizeClass(["tw-cursor-pointer em-square-button", { "active": i === $options.currentTab.pagination.current }]),
                onClick: ($event) => $options.getListItems(i, $data.selectedListTab)
              }, toDisplayString(i), 11, _hoisted_24);
            }), 128)),
            createBaseVNode("span", {
              class: normalizeClass([{ "tw-text-neutral-600 em-disabled-events": $options.currentTab.pagination.current === $options.currentTab.pagination.total }, "material-symbols-outlined tw-cursor-pointer tw-ml-2 tw-items-center"]),
              style: { "display": "flex" },
              onClick: _cache[8] || (_cache[8] = ($event) => $options.getListItems($options.currentTab.pagination.current + 1, $data.selectedListTab))
            }, " chevron_right ", 2)
          ])
        ])) : createCommentVNode("", true)
      ])) : createCommentVNode("", true),
      $data.loading.items ? (openBlock(), createElementBlock("div", {
        key: 2,
        id: "items-loading",
        class: normalizeClass({ "skeleton-grid": $data.viewType === "blocs", "tw-flex tw-flex-col tw-mb-4": $data.viewType === "list" }),
        style: { "flex-wrap": "wrap" }
      }, [
        (openBlock(), createElementBlock(Fragment, null, renderList(9, (i) => {
          return createVNode(_component_skeleton, {
            key: i,
            class: "tw-rounded-lg skeleton-item"
          });
        }), 64))
      ], 2)) : (openBlock(), createElementBlock("div", _hoisted_25, [
        $options.displayedItems.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_26, [
          $data.viewType != "gantt" ? (openBlock(), createElementBlock("table", {
            key: 0,
            id: "list-table",
            class: normalizeClass({ "blocs": $data.viewType === "blocs" })
          }, [
            createBaseVNode("thead", null, [
              createBaseVNode("tr", null, [
                createBaseVNode("th", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_LABEL_" + $options.currentTab.key.toUpperCase()) == "COM_EMUNDUS_ONBOARD_LABEL_" + $options.currentTab.key.toUpperCase() ? _ctx.translate("COM_EMUNDUS_ONBOARD_LABEL") : _ctx.translate("COM_EMUNDUS_ONBOARD_LABEL_" + $options.currentTab.key.toUpperCase())), 1),
                (openBlock(true), createElementBlock(Fragment, null, renderList($options.additionalColumns, (column) => {
                  return openBlock(), createElementBlock("th", { key: column }, toDisplayString(column), 1);
                }), 128)),
                $options.tabActionsPopover && $options.tabActionsPopover.length > 0 ? (openBlock(), createElementBlock("th", _hoisted_27, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_ACTIONS")), 1)) : createCommentVNode("", true)
              ])
            ]),
            createBaseVNode("tbody", null, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedItems, (item) => {
                return openBlock(), createElementBlock("tr", {
                  key: item.id,
                  id: "item-" + $options.currentTab.key + "-" + item.id,
                  class: normalizeClass(["em-border-cards table-row", { "em-card-neutral-100 em-card-shadow em-p-24": $data.viewType === "blocs" }])
                }, [
                  createBaseVNode("td", {
                    class: "tw-cursor-pointer",
                    onClick: ($event) => $options.onClickAction($options.editAction, item.id)
                  }, [
                    createBaseVNode("span", {
                      class: normalizeClass({ "tw-font-semibold tw-mb-4 tw-text-ellipsis tw-overflow-hidden": $data.viewType === "blocs" }),
                      title: item.label[$data.params.shortlang]
                    }, toDisplayString(item.label[$data.params.shortlang]), 11, _hoisted_30)
                  ], 8, _hoisted_29),
                  (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedColumns(item, $data.viewType), (column) => {
                    return openBlock(), createElementBlock("td", {
                      class: "columns",
                      key: column.key
                    }, [
                      column.type === "tags" ? (openBlock(), createElementBlock("div", {
                        key: 0,
                        class: normalizeClass(["tw-flex tw-items-center tw-flex-wrap tw-gap-2", column.classes])
                      }, [
                        (openBlock(true), createElementBlock(Fragment, null, renderList(column.values, (tag) => {
                          return openBlock(), createElementBlock("span", {
                            key: tag.key,
                            class: normalizeClass(["tw-mr-2 tw-h-max", tag.classes]),
                            innerHTML: tag.value
                          }, null, 10, _hoisted_31);
                        }), 128))
                      ], 2)) : column.hasOwnProperty("long_value") ? (openBlock(), createElementBlock("div", _hoisted_32, [
                        createBaseVNode("span", {
                          onClick: ($event) => $options.displayLongValue(column.long_value),
                          class: normalizeClass(["tw-mt-2 tw-mb-2", column.classes]),
                          innerHTML: column.value
                        }, null, 10, _hoisted_33)
                      ])) : (openBlock(), createElementBlock("span", {
                        key: 2,
                        class: normalizeClass(["tw-mt-2 tw-mb-2", column.classes]),
                        innerHTML: column.value
                      }, null, 10, _hoisted_34))
                    ]);
                  }), 128)),
                  createBaseVNode("div", null, [
                    $data.viewType === "blocs" ? (openBlock(), createElementBlock("hr", _hoisted_35)) : createCommentVNode("", true),
                    createBaseVNode("td", _hoisted_36, [
                      $data.viewType === "blocs" && $options.editAction ? (openBlock(), createElementBlock("a", {
                        key: 0,
                        onClick: ($event) => $options.onClickAction($options.editAction, item.id),
                        class: "tw-btn-primary tw-text-sm tw-cursor-pointer tw-w-auto"
                      }, toDisplayString(_ctx.translate($options.editAction.label)), 9, _hoisted_37)) : createCommentVNode("", true),
                      createBaseVNode("div", _hoisted_38, [
                        $options.previewAction ? (openBlock(), createElementBlock("span", {
                          key: 0,
                          class: "material-symbols-outlined tw-cursor-pointer",
                          onClick: ($event) => $options.onClickPreview(item)
                        }, "visibility", 8, _hoisted_39)) : createCommentVNode("", true),
                        (openBlock(true), createElementBlock(Fragment, null, renderList($options.iconActions, (action) => {
                          return openBlock(), createElementBlock("span", {
                            key: action.name,
                            class: normalizeClass(["tw-cursor-pointer", {
                              "material-symbols-outlined": action.iconOutlined,
                              "material-icons": !action.iconOutlined,
                              "tw-hidden": !(typeof action.showon === "undefined" || $options.evaluateShowOn(item, action.showon))
                            }]),
                            onClick: ($event) => $options.onClickAction(action, item.id)
                          }, toDisplayString(action.icon), 11, _hoisted_40);
                        }), 128)),
                        $options.tabActionsPopover && $options.tabActionsPopover.length > 0 && $options.filterShowOnActions($options.tabActionsPopover, item).length ? (openBlock(), createBlock(_component_popover, {
                          key: 1,
                          position: "left",
                          class: "custom-popover-arrow"
                        }, {
                          default: withCtx(() => [
                            createBaseVNode("ul", _hoisted_41, [
                              (openBlock(true), createElementBlock(Fragment, null, renderList($options.tabActionsPopover, (action) => {
                                return openBlock(), createElementBlock("li", {
                                  key: action.name,
                                  class: normalizeClass([{ "tw-hidden": !(typeof action.showon === "undefined" || $options.evaluateShowOn(item, action.showon)) }, "tw-cursor-pointer tw-p-2 tw-text-base"]),
                                  onClick: ($event) => $options.onClickAction(action, item.id)
                                }, toDisplayString(_ctx.translate(action.label)), 11, _hoisted_42);
                              }), 128))
                            ])
                          ]),
                          _: 2
                        }, 1024)) : createCommentVNode("", true)
                      ])
                    ])
                  ])
                ], 10, _hoisted_28);
              }), 128))
            ])
          ], 2)) : (openBlock(), createBlock(_component_Gantt, {
            key: 1,
            language: $data.params.shortlang,
            periods: $options.displayedItems
          }, null, 8, ["language", "periods"]))
        ])) : (openBlock(), createElementBlock("div", {
          key: 1,
          id: "empty-list",
          class: "noneDiscover tw-text-center",
          innerHTML: $options.noneDiscoverTranslation
        }, null, 8, _hoisted_43))
      ]))
    ]))
  ], 2);
}
const list = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  list as default
};
