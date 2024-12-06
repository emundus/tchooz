import { I as client, F as FetchClient, _ as _export_sfc, r as resolveComponent, o as openBlock, e as createElementBlock, d as createBaseVNode, t as toDisplayString, g as Fragment, h as renderList, c as createBlock, i as normalizeClass } from "./app_emundus.js";
import { S as Skeleton } from "./Skeleton.js";
const fetchClient = new FetchClient("form");
const baseUrl = "index.php?option=com_emundus&controller=form";
const formService = {
  async updateFormLabel(params) {
    try {
      return await fetchClient.post("updateformlabel", params);
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getFilesByForm(id) {
    try {
      return await fetchClient.get("getfilesbyform", {
        pid: id
      });
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getSubmissionPage(id) {
    try {
      return await fetchClient.get("getsubmittionpage", {
        prid: id
      });
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getFormsByProfileId(id) {
    try {
      const response = await client().get(
        baseUrl + "&task=getFormsByProfileId",
        {
          params: {
            profile_id: id
          }
        }
      );
      response.data.data.forEach((form) => {
        if (typeof form.type == "undefined") {
          form.type = "form";
        }
      });
      return response;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getFormByFabrikId(id) {
    try {
      const response = await client().get(baseUrl + "&task=getFormByFabrikId", { params: { form_id: id } });
      return response;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async createForm(params) {
    try {
      return await fetchClient.post("createform", params);
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getProfileLabelByProfileId(id) {
    try {
      return await fetchClient.get("getProfileLabelByProfileId&profile_id=" + id);
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getDocuments(id) {
    if (id > 0) {
      try {
        const response = await client().get(baseUrl + "&task=getDocuments", { params: { pid: id } });
        return response.data;
      } catch (error) {
        return {
          status: false,
          data: [],
          msg: error
        };
      }
    } else {
      return {
        status: false,
        msg: "Missing parameter"
      };
    }
  },
  async getDocumentModels(documentId = null) {
    try {
      let data = {
        status: false
      };
      const response = await client().get(
        baseUrl + "&task=getAttachments"
      );
      if (response.data.status) {
        if (documentId !== null) {
          const document = response.data.data.filter((document2) => document2.id === documentId);
          if (document.length > 0) {
            data = {
              status: true,
              data: document[0]
            };
          } else {
            data = {
              status: false,
              error: "Document not found"
            };
          }
        } else {
          data = response.data;
        }
      }
      return data;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getDocumentModelsUsage(documentIds) {
    if (documentIds.length > 0) {
      try {
        const response = await client().get(baseUrl + "&task=getdocumentsusage&documentIds=" + documentIds);
        return response.data;
      } catch (error) {
        return {
          status: false,
          error
        };
      }
    } else {
      return {
        status: false,
        msg: "Missing parameter"
      };
    }
  },
  async getPageGroups(formId) {
    if (typeof formId == "number" && formId > 0) {
      try {
        const response = await client().get(baseUrl + "&task=getpagegroups&form_id=" + formId);
        return response.data;
      } catch (error) {
        return {
          status: false,
          error
        };
      }
    } else {
      return {
        status: false,
        msg: "MISSING_PARAMS"
      };
    }
  },
  async reorderDocuments(documents) {
    let data = {};
    data.documents = JSON.stringify(documents);
    try {
      return await fetchClient.post("reorderDocuments", data);
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async addDocument(params) {
    const formData = new FormData();
    Object.keys(params).forEach((key) => formData.append(key, params[key]));
    try {
      const response = await client().post(baseUrl + "&task=addDocument", formData);
      return response;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getAssociatedCampaigns(id) {
    try {
      const response = client().get(
        baseUrl + "&task=getassociatedcampaign",
        {
          params: {
            pid: id
          }
        }
      );
      return response;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async removeDocumentFromProfile(id) {
    try {
      const response = await client().get(
        baseUrl + "&task=removeDocumentFromProfile",
        {
          params: {
            did: id
          }
        }
      );
      return response;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getPageObject(formId) {
    try {
      const response = await client().get(
        "index.php?option=com_emundus&view=form&formid=" + formId + "&format=vue_jsonclean"
      );
      if (typeof response.data !== "object") {
        throw "COM_EMUNDUS_FORM_BUILDER_FAILED_TO_LOAD_FORM";
      }
      return response;
    } catch (error) {
      return {
        status: false,
        msg: error
      };
    }
  },
  async checkIfDocumentCanBeDeletedForProfile(documentId, profileId) {
    try {
      const response = await client().get(
        baseUrl + "&task=checkcandocbedeleted&docid=" + documentId + "&prid=" + profileId
      );
      return response.data;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getConditions(formId) {
    try {
      const response = await client().get(
        baseUrl + "&task=getjsconditions&form_id=" + formId + "&format=view"
      );
      return response.data;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async addRule(formId, conditions, actions, group, label) {
    let data = {};
    data.conditions = JSON.stringify(conditions);
    data.actions = JSON.stringify(actions);
    data.form_id = formId;
    data.group = group;
    data.label = label;
    try {
      return await fetchClient.post("addRule", data);
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async editRule(ruleId, conditions, actions, group, label) {
    let data = {};
    data.conditions = JSON.stringify(conditions);
    data.actions = JSON.stringify(actions);
    data.rule_id = ruleId;
    data.group = group;
    data.label = label;
    try {
      return await fetchClient.post("editRule", data);
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async deleteRule(ruleId) {
    try {
      const response = await client().get(baseUrl + "&task=deleteRule&rule_id=" + ruleId);
      return response;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async publishRule(ruleId, state) {
    try {
      const response = await client().get(baseUrl + "&task=publishRule&rule_id=" + ruleId + "&state=" + state);
      return response;
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getPublishedForms() {
    try {
      return await client().get(baseUrl + "&task=getallformpublished");
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getUnDocuments() {
    try {
      return await fetchClient.get("getundocuments");
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  }
};
const FormBuilderPreviewForm_vue_vue_type_style_index_0_lang = "";
const _sfc_main = {
  name: "FormBuilderPreviewForm",
  components: { Skeleton },
  props: {
    form_id: {
      type: Number,
      required: true
    },
    form_label: {
      type: String,
      default: ""
    }
  },
  data() {
    return {
      loading: true,
      formData: {}
    };
  },
  created() {
    formService.getPageGroups(this.form_id).then((response) => {
      if (response.status) {
        response.data.groups = response.data.groups.filter((group) => {
          return Number(group.published) === 1;
        });
        this.formData = response.data;
      }
      this.loading = false;
    });
  },
  methods: {}
};
const _hoisted_1 = { key: 0 };
const _hoisted_2 = { class: "tw-text-xs tw-w-full tw-text-end tw-mb-4" };
const _hoisted_3 = { class: "preview-groups tw-flex tw-flex-col" };
const _hoisted_4 = { class: "section-card tw-flex tw-flex-col" };
const _hoisted_5 = { class: "section-identifier tw-bg-profile-full tw-flex tw-items-center" };
const _hoisted_6 = { class: "text-xxs" };
const _hoisted_7 = { class: "section-content tw-w-full" };
const _hoisted_8 = { class: "tw-text-xxs tw-w-full tw-text-end" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_skeleton = resolveComponent("skeleton");
  return openBlock(), createElementBlock("div", {
    id: "form-builder-preview-form",
    class: normalizeClass(["tw-h-full tw-w-full", { loading: $data.loading }])
  }, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_1, [
      createBaseVNode("p", _hoisted_2, toDisplayString($props.form_label), 1),
      createBaseVNode("div", _hoisted_3, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.formData.groups, (group, index) => {
          return openBlock(), createElementBlock("section", {
            key: group.id,
            class: "tw-mb-2 form-builder-page-section"
          }, [
            createBaseVNode("div", _hoisted_4, [
              createBaseVNode("div", _hoisted_5, [
                createBaseVNode("span", _hoisted_6, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SECTION")) + " " + toDisplayString(index + 1) + " / " + toDisplayString($data.formData.groups.length), 1)
              ]),
              createBaseVNode("div", _hoisted_7, [
                createBaseVNode("p", _hoisted_8, toDisplayString(group.label.replace("Model - ", "")), 1)
              ])
            ])
          ]);
        }), 128))
      ])
    ])) : (openBlock(), createBlock(_component_skeleton, {
      key: 1,
      height: "100%",
      width: "100%"
    }))
  ], 2);
}
const FormBuilderPreviewForm = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  FormBuilderPreviewForm as F,
  formService as f
};
