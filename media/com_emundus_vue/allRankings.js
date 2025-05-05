import { x as client, _ as _export_sfc, y as script, z as programmeService, A as fileService, B as rankingService, r as resolveComponent, o as openBlock, c as createElementBlock, d as createBaseVNode, F as Fragment, e as renderList, n as normalizeClass, t as toDisplayString, w as withDirectives, C as vModelText, h as createVNode, D as withKeys, b as createCommentVNode } from "./app_emundus.js";
const campaignsService = {
  async getAllCampaigns(filter = "", sort = "DESC", recherche = "", lim = 9999, page = 0, program = "all") {
    try {
      const response = await client().get("index.php?option=com_emundus&controller=campaign&task=getallcampaign", {
        params: {
          filter,
          sort,
          recherche,
          lim,
          page,
          program
        }
      });
      return response.data;
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
};
const _sfc_main = {
  name: "allRankings",
  components: {
    multiselect: script
  },
  data() {
    return {
      hierarchies: [],
      selectedHierarchy: null,
      rankings: [],
      filters: {
        programOptions: [],
        selectedPrograms: [],
        campaignOptions: [],
        selectedCampaigns: [],
        statusOptions: [],
        selectedStatus: [],
        fileOrApplicantName: "",
        rankerName: ""
      },
      rankersByHierarchy: {},
      editRankerForRowId: "",
      order: {
        column: "fnum",
        direction: "ASC"
      }
    };
  },
  created() {
    campaignsService.getAllCampaigns().then((response) => {
      this.filters.campaignOptions = response.data.datas.map((campaign) => {
        return {
          id: campaign.id,
          label: campaign.label.fr
        };
      });
    });
    programmeService.getAllPrograms().then((response) => {
      this.filters.programOptions = response.data.datas.map((program) => {
        return {
          id: program.id,
          label: program.label.fr
        };
      });
    });
    fileService.getAllStatus().then((response) => {
      this.filters.statusOptions = response.states.map((status) => {
        return {
          id: status.step,
          label: status.value
        };
      });
    });
    rankingService.getAllRankers().then((response) => {
      this.rankersByHierarchy = response.data;
    });
    rankingService.getHierarchies().then((response) => {
      this.hierarchies = response.data;
      this.getAllRankings(this.hierarchies[0].id);
    });
  },
  methods: {
    getAllRankings(hierarchy_id) {
      rankingService.getAllRankings(hierarchy_id, this.filters, this.order).then((response) => {
        this.selectedHierarchy = hierarchy_id;
        this.rankings = response.data;
      }).catch((e) => {
        console.log(e);
      });
    },
    updateRank(rankRowId, newRank, ccid) {
      rankingService.rawUpdateRank(rankRowId, newRank, ccid, this.selectedHierarchy).then((response) => {
        this.getAllRankings(this.selectedHierarchy);
      }).catch((e) => {
        console.log(e);
      });
    },
    updateRowIdRanker(rankRowId, newRanker, ccid) {
      let newRankerId = newRanker.id;
      rankingService.rawUpdateRanker(rankRowId, newRankerId, ccid, this.selectedHierarchy).then((response) => {
        this.getAllRankings(this.selectedHierarchy);
      }).catch((e) => {
        console.log(e);
      });
      this.editRankerForRowId = "";
    },
    orderBy(column) {
      if (this.order.column === column) {
        this.order.direction = this.order.direction === "ASC" ? "DESC" : "ASC";
      } else {
        this.order.column = column;
        this.order.direction = "ASC";
      }
      this.getAllRankings(this.selectedHierarchy);
    }
  }
};
const _hoisted_1 = {
  id: "admin_ranking_view",
  class: "tw-mt-4"
};
const _hoisted_2 = {
  id: "hierarchies",
  class: "tw-flex tw-list-none tw-flex-row tw-gap-2 tw-overflow-auto"
};
const _hoisted_3 = ["onClick"];
const _hoisted_4 = {
  id: "filters",
  class: "tw-mb-4 tw-mt-4 tw-flex tw-flex-row tw-gap-3"
};
const _hoisted_5 = ["placeholder"];
const _hoisted_6 = ["placeholder"];
const _hoisted_7 = { class: "tw-flex tw-flex-row tw-justify-end" };
const _hoisted_8 = ["onUpdate:modelValue", "onChange"];
const _hoisted_9 = {
  key: 0,
  class: "tw-lex-row tw-flex tw-items-center tw-justify-between"
};
const _hoisted_10 = ["onClick"];
const _hoisted_11 = {
  key: 1,
  class: "tw-flex tw-flex-row tw-items-center",
  style: { "min-width": "300px" }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("ul", _hoisted_2, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.hierarchies, (hierarchy) => {
        return openBlock(), createElementBlock("li", {
          key: hierarchy.id,
          onClick: ($event) => $options.getAllRankings(hierarchy.id),
          class: normalizeClass(["tw-flex tw-cursor-pointer tw-items-center tw-rounded-t-lg tw-border-x tw-border-t tw-border-profile-full tw-px-4 tw-py-2 tw-transition-colors tw-duration-300", {
            "tw-bg-neutral-200": $data.selectedHierarchy != hierarchy.id,
            "tw-bg-white": $data.selectedHierarchy === hierarchy.id
          }])
        }, [
          createBaseVNode("span", null, toDisplayString(hierarchy.label), 1)
        ], 10, _hoisted_3);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_4, [
      withDirectives(createBaseVNode("input", {
        id: "file_fnum_or_applicant_name",
        name: "file_fnum_or_applicant_name",
        type: "text",
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.filters.fileOrApplicantName = $event),
        onFocusout: _cache[1] || (_cache[1] = ($event) => $options.getAllRankings($data.selectedHierarchy)),
        placeholder: _ctx.translate("COM_EMUNDUS_RANKING_APPLICANT_NAME")
      }, null, 40, _hoisted_5), [
        [vModelText, $data.filters.fileOrApplicantName]
      ]),
      createVNode(_component_multiselect, {
        options: $data.filters.programOptions,
        label: "label",
        "track-by": "id",
        placeholder: "Programs",
        modelValue: $data.filters.selectedPrograms,
        "onUpdate:modelValue": [
          _cache[2] || (_cache[2] = ($event) => $data.filters.selectedPrograms = $event),
          _cache[3] || (_cache[3] = ($event) => $options.getAllRankings($data.selectedHierarchy))
        ],
        multiple: true
      }, null, 8, ["options", "modelValue"]),
      createVNode(_component_multiselect, {
        options: $data.filters.campaignOptions,
        label: "label",
        "track-by": "id",
        placeholder: "Campaigns",
        modelValue: $data.filters.selectedCampaigns,
        "onUpdate:modelValue": [
          _cache[4] || (_cache[4] = ($event) => $data.filters.selectedCampaigns = $event),
          _cache[5] || (_cache[5] = ($event) => $options.getAllRankings($data.selectedHierarchy))
        ],
        multiple: true
      }, null, 8, ["options", "modelValue"]),
      createVNode(_component_multiselect, {
        options: $data.filters.statusOptions,
        label: "label",
        "track-by": "id",
        placeholder: "Status",
        modelValue: $data.filters.selectedStatus,
        "onUpdate:modelValue": [
          _cache[6] || (_cache[6] = ($event) => $data.filters.selectedStatus = $event),
          _cache[7] || (_cache[7] = ($event) => $options.getAllRankings($data.selectedHierarchy))
        ],
        multiple: true
      }, null, 8, ["options", "modelValue"]),
      withDirectives(createBaseVNode("input", {
        id: "ranker",
        type: "text",
        name: "ranker",
        "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.filters.rankerName = $event),
        onFocusout: _cache[9] || (_cache[9] = ($event) => $options.getAllRankings($data.selectedHierarchy)),
        placeholder: _ctx.translate("COM_EMUNDUS_RANKING_EXPORT_RANKER")
      }, null, 40, _hoisted_6), [
        [vModelText, $data.filters.rankerName]
      ])
    ]),
    createBaseVNode("div", _hoisted_7, [
      createBaseVNode("button", {
        class: "tw-btn-primary tw-mb-4 tw-w-fit",
        onClick: _cache[10] || (_cache[10] = ($event) => $options.getAllRankings($data.selectedHierarchy)),
        onKeyup: _cache[11] || (_cache[11] = withKeys(($event) => $options.getAllRankings($data.selectedHierarchy), ["enter"]))
      }, toDisplayString(_ctx.translate("SEARCH")), 33)
    ]),
    createBaseVNode("table", null, [
      createBaseVNode("thead", null, [
        createBaseVNode("tr", null, [
          createBaseVNode("th", {
            onClick: _cache[12] || (_cache[12] = ($event) => $options.orderBy("ecc.fnum"))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_RANKING_FILE_ID")), 1),
          createBaseVNode("th", {
            onClick: _cache[13] || (_cache[13] = ($event) => $options.orderBy("u.name"))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_RANKING_APPLICANT_NAME")), 1),
          createBaseVNode("th", {
            onClick: _cache[14] || (_cache[14] = ($event) => $options.orderBy("ess.value"))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_RANKING_FILE_STATUS")), 1),
          createBaseVNode("th", {
            onClick: _cache[15] || (_cache[15] = ($event) => $options.orderBy("esp.label"))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_RANKING_FILE_PROGRAM")), 1),
          createBaseVNode("th", {
            onClick: _cache[16] || (_cache[16] = ($event) => $options.orderBy("esc.label"))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_RANKING_FILE_CAMPAIGN")), 1),
          createBaseVNode("th", {
            onClick: _cache[17] || (_cache[17] = ($event) => $options.orderBy("er.id"))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_RANKING_RANKING_ROW_ID")), 1),
          createBaseVNode("th", {
            onClick: _cache[18] || (_cache[18] = ($event) => $options.orderBy("er.rank"))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_RANKING_RANK")), 1),
          createBaseVNode("th", {
            onClick: _cache[19] || (_cache[19] = ($event) => $options.orderBy("er.user_id"))
          }, toDisplayString(_ctx.translate("COM_EMUNDUS_RANKING_RANKER_NAME")), 1)
        ])
      ]),
      createBaseVNode("tbody", null, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.rankings, (ranking) => {
          return openBlock(), createElementBlock("tr", {
            key: ranking.ccid
          }, [
            createBaseVNode("td", null, toDisplayString(ranking.fnum), 1),
            createBaseVNode("td", null, toDisplayString(ranking.applicant_name), 1),
            createBaseVNode("td", null, toDisplayString(ranking.status_label), 1),
            createBaseVNode("td", null, toDisplayString(ranking.program_label), 1),
            createBaseVNode("td", null, toDisplayString(ranking.campaign_label), 1),
            createBaseVNode("td", null, toDisplayString(ranking.rank_row_id), 1),
            createBaseVNode("td", null, [
              withDirectives(createBaseVNode("input", {
                type: "text",
                "onUpdate:modelValue": ($event) => ranking.rank = $event,
                onChange: ($event) => $options.updateRank(ranking.rank_row_id, ranking.rank, ranking.ccid)
              }, null, 40, _hoisted_8), [
                [vModelText, ranking.rank]
              ])
            ]),
            $data.editRankerForRowId != ranking.ccid + "-" + ranking.hierarchy_id ? (openBlock(), createElementBlock("td", _hoisted_9, [
              createBaseVNode("span", null, toDisplayString(ranking.ranker_name), 1),
              ranking.rank_row_id ? (openBlock(), createElementBlock("span", {
                key: 0,
                class: "material-symbols-outlined tw-cursor-pointer",
                onClick: ($event) => $data.editRankerForRowId = ranking.ccid + "-" + ranking.hierarchy_id
              }, "edit", 8, _hoisted_10)) : createCommentVNode("", true)
            ])) : (openBlock(), createElementBlock("td", _hoisted_11, [
              createVNode(_component_multiselect, {
                options: $data.rankersByHierarchy[$data.selectedHierarchy],
                label: "name",
                "track-by": "id",
                modelValue: ranking.ranker,
                "onUpdate:modelValue": [($event) => ranking.ranker = $event, ($event) => $options.updateRowIdRanker(ranking.rank_row_id, ranking.ranker, ranking.ccid)]
              }, null, 8, ["options", "modelValue", "onUpdate:modelValue"]),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-color-red tw-cursor-pointer",
                onClick: _cache[20] || (_cache[20] = ($event) => $data.editRankerForRowId = "")
              }, "close")
            ]))
          ]);
        }), 128))
      ])
    ])
  ]);
}
const allRankings = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  allRankings as default
};
