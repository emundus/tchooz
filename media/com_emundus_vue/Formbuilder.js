import { F as FetchClient, K as hooks, L as defineStore, z as useGlobalStore, S as Swal$1, _ as _export_sfc, h as errors, e as resolveComponent, o as openBlock, c as createElementBlock, a as createBaseVNode, b as Fragment, r as renderList, n as normalizeClass, t as toDisplayString, w as withDirectives, N as vModelText, g as createVNode, k as withCtx, p as TransitionGroup, y as createTextVNode, v as vShow, d as createCommentVNode, O as script, x as vModelSelect, j as createBlock, P as vModelDynamic, D as vModelCheckbox, l as normalizeStyle, Q as withKeys, J as Transition, G as mixin, H as formService, R as vModelRadio, U as Popover, m as campaignService, V as client$1, W as watch, M as Modal, s as settingsService } from "./app_emundus.js";
import { V as VueDraggableNext } from "./vue-draggable-next.esm-bundler.js";
import { V as V32 } from "./editor.js";
import { t as translationsService, T as Translations } from "./Translations.js";
import { S as Skeleton } from "./Skeleton.js";
import { I as IncrementalSelect } from "./IncrementalSelect.js";
import History from "./History.js";
import "./index.js";
const client = new FetchClient("formbuilder");
const formBuilderService = {
  async createSimpleElement(params) {
    try {
      const formData = new FormData();
      Object.keys(params).forEach((key) => {
        formData.append(key, params[key]);
      });
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=createsimpleelement", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async createSectionSimpleElements(params) {
    try {
      const formData = new FormData();
      Object.keys(params).forEach((key) => {
        formData.append(key, params[key]);
      });
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=createsectionsimpleelements", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((data) => {
        return data;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async createSimpleGroup(fid, label, mode) {
    try {
      const formData = new FormData();
      formData.append("fid", fid);
      formData.append("mode", mode);
      formData.append("label", JSON.stringify(label));
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=createsimplegroup", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((json) => {
        return json;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async createTestingFile(campaign_id) {
    try {
      const formData = new FormData();
      formData.append("cid", campaign_id);
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=createtestingfile", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getElement(gid, element) {
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getElement&gid=" + gid + "&element=" + element, {
        method: "GET"
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getJTEXT(text) {
    const formData = new FormData();
    formData.append("toJTEXT", text);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getJTEXT", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getJTEXTA(texts) {
    const formData = new FormData();
    texts.forEach((text, index) => {
      formData.append("toJTEXT[" + index + "]", text);
    });
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getJTEXTA", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getAllTranslations(text) {
    const formData = new FormData();
    formData.append("toJTEXT", text);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getalltranslations", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getTestingParams(id) {
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=gettestingparams&prid=" + id, {
        method: "GET"
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getDatabases() {
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getdatabasesjoin", {
        method: "GET"
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  getDatabaseJoinOrderColumns(databaseName) {
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getDatabaseJoinOrderColumns&database_name=" + databaseName, {
        method: "GET"
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async updateParams(element) {
    const formData = new FormData();
    const postData = JSON.stringify(element);
    formData.append("element", postData);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=updateparams", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return {
          status: response.scalar
        };
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async updateGroupParams(group_id, params, lang = null) {
    const formData = new FormData();
    formData.append("group_id", group_id);
    formData.append("params", JSON.stringify(params));
    if (lang != null) {
      formData.append("lang", lang);
    }
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=updategroupparams", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async updateOrder(elements, groupId, movedElement) {
    const formData = new FormData();
    formData.append("elements", JSON.stringify(elements));
    formData.append("group_id", groupId);
    formData.append("moved_el", JSON.stringify(movedElement));
    if (movedElement.length == 0) {
      return {
        status: false,
        message: "No elements to update"
      };
    }
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=updateOrder", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  updateElementOrder(groupId, elementId, newIndex) {
    const formData = new FormData();
    formData.append("group_id", groupId);
    formData.append("element_id", elementId);
    formData.append("new_index", newIndex);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=updateelementorder", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async updateDocument(data) {
    if (data.document_id == void 0 || data.profile_id == void 0 || data.document == void 0) {
      return {
        status: false,
        msg: "Missing data"
      };
    }
    const formData = new FormData();
    formData.append("document_id", data.document_id);
    formData.append("profile_id", data.profile_id);
    formData.append("document", data.document);
    formData.append("types", data.types);
    formData.append("file", data.sample);
    formData.append("has_sample", data.has_sample);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=updatedocument", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        msg: e
      };
    }
  },
  async toggleElementPublishValue(element) {
    const formData = new FormData();
    formData.append("element", element);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=publishunpublishelement", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async toggleElementHiddenValue(element) {
    const formData = new FormData();
    formData.append("element", element);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=hiddenunhiddenelement", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async reorderMenu(params, profile_id) {
    const formData = new FormData();
    formData.append("menus", JSON.stringify(params));
    formData.append("profile", profile_id);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=reordermenu", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async reorderSections(pageId, sections) {
    const formData = new FormData();
    formData.append("groups", JSON.stringify(sections));
    formData.append("fid", pageId);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=reordergroups", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async addPage(params) {
    if (!params.prid) {
      return {
        status: false,
        msg: "Missing prid"
      };
    }
    const formData = new FormData();
    Object.keys(params).forEach((key) => {
      formData.append(key, JSON.stringify(params[key]));
    });
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=createMenu", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async deletePage(page) {
    if (!page) {
      return {
        status: false,
        message: "Missing page id"
      };
    }
    const formData = new FormData();
    formData.append("mid", page);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=deletemenu", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async updateTranslation(item, tag, value) {
    const formData = new FormData();
    if (item !== null) {
      formData.append(item.key, item.value);
    }
    formData.append("labelTofind", tag);
    Object.keys(value).forEach((key) => {
      formData.append("NewSubLabel[" + key + "]", value[key]);
    });
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=formsTrad", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((data) => {
        return {
          data
        };
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async updateOption(elementId, options, index, newTranslation, lang) {
    const formData = new FormData();
    formData.append("element", elementId);
    formData.append("options", JSON.stringify(options));
    formData.append("index", index);
    formData.append("newTranslation", newTranslation);
    formData.append("lang", lang);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=updateElementOption", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  addOption(element, newOption, lang) {
    const formData = new FormData();
    formData.append("element", element);
    formData.append("newOption", newOption);
    formData.append("lang", lang);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=addElementSubOption", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  deleteElementSubOption(element, index) {
    const formData = new FormData();
    formData.append("element", element);
    formData.append("index", index);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=deleteElementSubOption", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  updateElementSubOptionsOrder(element, old_order, new_order) {
    const formData = new FormData();
    formData.append("element", element);
    formData.append("options_old_order", JSON.stringify(old_order));
    formData.append("options_new_order", JSON.stringify(new_order));
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=updateElementSubOptionsOrder", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  getElementSubOptions(element) {
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getelementsuboptions&element=" + element, {
        method: "GET"
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  deleteElement(elementId) {
    const formData = new FormData();
    formData.append("element", elementId);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=deleteElement", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  deleteGroup(groupId) {
    const formData = new FormData();
    formData.append("gid", groupId);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=deleteGroup", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async updateDefaultValue(eid, value) {
    const formData = new FormData();
    formData.append("eid", eid);
    formData.append("value", value);
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=updatedefaultvalue", {
        method: "POST",
        body: formData
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getSection(section) {
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getsection&section=" + section, {
        method: "GET"
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getModels() {
    try {
      return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getpagemodels", {
        method: "GET"
      }).then((response) => response.json()).then((response) => {
        return response;
      }).catch((error) => {
        throw error;
      });
    } catch (e) {
      return { status: false, message: e.message };
    }
  },
  async addFormModel(formId, modelLabel) {
    if (formId > 0) {
      const formData = new FormData();
      formData.append("form_id", formId);
      formData.append("label", modelLabel);
      try {
        return fetch("/index.php?option=com_emundus&controller=formbuilder&task=addformmodel", {
          method: "POST",
          body: formData
        }).then((response) => response.json()).then((response) => {
          return response;
        }).catch((error) => {
          throw error;
        });
      } catch (e) {
        return { status: false, message: e.message };
      }
    } else {
      return { status: false, message: "MISSING_PARAMS" };
    }
  },
  async deleteFormModel(formId) {
    if (formId > 0) {
      const formData = new FormData();
      formData.append("form_id", formId);
      try {
        return fetch("/index.php?option=com_emundus&controller=formbuilder&task=deleteformmodel", {
          method: "POST",
          body: formData
        }).then((response) => response.json()).then((response) => {
          return response;
        }).catch((error) => {
          throw error;
        });
      } catch (e) {
        return { status: false, message: e.message };
      }
    } else {
      return { status: false, message: "MISSING_PARAMS" };
    }
  },
  async deleteFormModelFromId(modelIds) {
    if (modelIds.length > 0) {
      const formData = new FormData();
      formData.append("model_ids", JSON.stringify(modelIds));
      try {
        return fetch("/index.php?option=com_emundus&controller=formbuilder&task=deleteformmodelfromids", {
          method: "POST",
          body: formData
        }).then((response) => response.json()).then((response) => {
          return response;
        }).catch((error) => {
          throw error;
        });
      } catch (e) {
        return { status: false, message: e.message };
      }
    } else {
      return { status: false, message: "MISSING_PARAMS" };
    }
  },
  async getDocumentSample(documentId, profileId) {
    if (documentId > 0 && profileId > 0) {
      try {
        return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getdocumentsample&document_id=" + documentId + "&profile_id=" + profileId, {
          method: "GET"
        }).then((response) => response.json()).then((response) => {
          return response;
        }).catch((error) => {
          throw error;
        });
      } catch (e) {
        return { status: false, message: e.message };
      }
    } else {
      return { status: false, message: "MISSING_PARAMS" };
    }
  },
  async checkIfModelTableIsUsedInForm(modelId, profileId) {
    let response = {
      status: false,
      msg: "MISSING_PARAMS"
    };
    if (modelId > 0 && profileId > 0) {
      try {
        return client.get("checkifmodeltableisusedinform", {
          model_id: modelId,
          profile_id: profileId
        }).then((response2) => {
          return response2;
        }).catch((error) => {
          throw error;
        });
      } catch (e) {
        response.msg = e.message;
      }
    }
    return response;
  },
  async getSqlDropdownOptions(table, key, value, translate) {
    let response = {
      status: false,
      msg: "MISSING_PARAMS"
    };
    if (table && key && value) {
      try {
        return fetch("/index.php?option=com_emundus&controller=formbuilder&task=getsqldropdownoptions&table=" + table + "&key=" + key + "&value=" + value + "&translate=" + translate, {
          method: "GET"
        }).then((response2) => response2.json()).then((response2) => {
          return response2;
        }).catch((error) => {
          throw error;
        });
      } catch (e) {
        response.msg = e.message;
      }
    }
    return response;
  },
  async updateElementParam(elementId, param, value) {
    return client.post("updateelementparam", {
      element_id: elementId,
      param,
      value
    });
  }
};
//! moment.js locale configuration
//! locale : French [fr]
//! author : John Fischer : https://github.com/jfroffice
var monthsStrictRegex = /^(janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)/i, monthsShortStrictRegex = /(janv\.?|févr\.?|mars|avr\.?|mai|juin|juil\.?|août|sept\.?|oct\.?|nov\.?|déc\.?)/i, monthsRegex = /(janv\.?|févr\.?|mars|avr\.?|mai|juin|juil\.?|août|sept\.?|oct\.?|nov\.?|déc\.?|janvier|février|mars|avril|mai|juin|juillet|août|septembre|octobre|novembre|décembre)/i, monthsParse = [
  /^janv/i,
  /^févr/i,
  /^mars/i,
  /^avr/i,
  /^mai/i,
  /^juin/i,
  /^juil/i,
  /^août/i,
  /^sept/i,
  /^oct/i,
  /^nov/i,
  /^déc/i
];
const fr = hooks.defineLocale("fr", {
  months: "janvier_février_mars_avril_mai_juin_juillet_août_septembre_octobre_novembre_décembre".split(
    "_"
  ),
  monthsShort: "janv._févr._mars_avr._mai_juin_juil._août_sept._oct._nov._déc.".split(
    "_"
  ),
  monthsRegex,
  monthsShortRegex: monthsRegex,
  monthsStrictRegex,
  monthsShortStrictRegex,
  monthsParse,
  longMonthsParse: monthsParse,
  shortMonthsParse: monthsParse,
  weekdays: "dimanche_lundi_mardi_mercredi_jeudi_vendredi_samedi".split("_"),
  weekdaysShort: "dim._lun._mar._mer._jeu._ven._sam.".split("_"),
  weekdaysMin: "di_lu_ma_me_je_ve_sa".split("_"),
  weekdaysParseExact: true,
  longDateFormat: {
    LT: "HH:mm",
    LTS: "HH:mm:ss",
    L: "DD/MM/YYYY",
    LL: "D MMMM YYYY",
    LLL: "D MMMM YYYY HH:mm",
    LLLL: "dddd D MMMM YYYY HH:mm"
  },
  calendar: {
    sameDay: "[Aujourd’hui à] LT",
    nextDay: "[Demain à] LT",
    nextWeek: "dddd [à] LT",
    lastDay: "[Hier à] LT",
    lastWeek: "dddd [dernier à] LT",
    sameElse: "L"
  },
  relativeTime: {
    future: "dans %s",
    past: "il y a %s",
    s: "quelques secondes",
    ss: "%d secondes",
    m: "une minute",
    mm: "%d minutes",
    h: "une heure",
    hh: "%d heures",
    d: "un jour",
    dd: "%d jours",
    w: "une semaine",
    ww: "%d semaines",
    M: "un mois",
    MM: "%d mois",
    y: "un an",
    yy: "%d ans"
  },
  dayOfMonthOrdinalParse: /\d{1,2}(er|)/,
  ordinal: function(number, period) {
    switch (period) {
      case "D":
        return number + (number === 1 ? "er" : "");
      default:
      case "M":
      case "Q":
      case "DDD":
      case "d":
        return number + (number === 1 ? "er" : "e");
      case "w":
      case "W":
        return number + (number === 1 ? "re" : "e");
    }
  },
  week: {
    dow: 1,
    // Monday is the first day of the week.
    doy: 4
    // The week that contains Jan 4th is the first week of the year.
  }
});
const useFormBuilderStore = defineStore("formbuilder", {
  state: () => ({
    lastSave: null,
    pages: null,
    documentModels: [],
    rulesKeywords: ""
  }),
  getters: {
    getLastSave: (state) => state.lastSave,
    getPages: (state) => state.pages,
    getDocumentModels: (state) => state.documentModels,
    getRulesKeywords: (state) => state.rulesKeywords
  },
  actions: {
    updateLastSave(payload) {
      this.lastSave = payload;
    },
    updateDocumentModels(payload) {
      this.documentModels = payload;
    },
    updateRulesKeywords(payload) {
      this.rulesKeywords = payload;
    }
  }
});
const formBuilderMixin = {
  methods: {
    updateLastSave() {
      if (useGlobalStore().shortLang === "fr") {
        hooks.locale("fr", fr);
      }
      const formBuilderStore = useFormBuilderStore();
      formBuilderStore.updateLastSave(hooks().format("LT"));
    },
    async swalConfirm(title, text, confirm, cancel, callback = null, showCancelButton = true, html = false) {
      let options = {
        title,
        text,
        icon: "warning",
        showCancelButton,
        confirmButtonText: confirm,
        cancelButtonText: cancel,
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          cancelButton: "em-swal-cancel-button",
          confirmButton: "em-swal-confirm-button"
        }
      };
      if (html) {
        options.html = text;
      } else {
        options.text = text;
      }
      Swal$1.fire({
        "title": "test"
      });
      return Swal$1.fire(options).then((result2) => {
        if (result2.value) {
          if (callback != null) {
            callback();
          }
          return true;
        } else {
          return false;
        }
      });
    }
  }
};
const formBuilderElements = [
  {
    value: "field",
    icon: "text_fields",
    name: "COM_EMUNDUS_ONBOARD_TYPE_FIELD",
    published: true
  },
  {
    value: "textarea",
    icon: "notes",
    name: "COM_EMUNDUS_ONBOARD_TYPE_TEXTAREA",
    published: true
  },
  {
    value: "nom",
    icon: "text_fields",
    name: "COM_EMUNDUS_ONBOARD_TYPE_LASTNAME",
    published: true
  },
  {
    value: "prenom",
    icon: "text_fields",
    name: "COM_EMUNDUS_ONBOARD_TYPE_FIRSTNAME",
    published: true
  },
  {
    value: "email",
    icon: "mail",
    name: "COM_EMUNDUS_ONBOARD_TYPE_EMAIL",
    published: true
  },
  {
    value: "emundus_phonenumber",
    icon: "phone",
    name: "COM_EMUNDUS_ONBOARD_TYPE_PHONE_NUMBER",
    published: true
  },
  {
    value: "checkbox",
    icon: "check_box",
    name: "COM_EMUNDUS_ONBOARD_TYPE_CHECKBOX",
    published: true
  },
  {
    value: "radiobutton",
    icon: "radio_button_checked",
    name: "COM_EMUNDUS_ONBOARD_TYPE_RADIOBUTTON",
    published: true
  },
  {
    value: "dropdown",
    icon: "arrow_drop_down_circle",
    name: "COM_EMUNDUS_ONBOARD_TYPE_DROPDOWN",
    published: true
  },
  {
    value: "jdate",
    icon: "calendar_month",
    name: "COM_EMUNDUS_ONBOARD_TYPE_CALENDAR",
    published: true
  },
  {
    value: "birthday",
    icon: "event",
    name: "COM_EMUNDUS_ONBOARD_TYPE_BIRTHDAY",
    published: true
  },
  {
    value: "yesno",
    icon: "toggle_on",
    name: "COM_EMUNDUS_ONBOARD_TYPE_YESNO",
    published: true
  },
  {
    value: "display",
    icon: "wysiwyg",
    name: "COM_EMUNDUS_ONBOARD_TYPE_DISPLAY",
    description: "COM_EMUNDUS_ONBOARD_TYPE_DISPLAY_DESC",
    published: false
  },
  {
    value: "databasejoin",
    icon: "table_rows",
    name: "COM_EMUNDUS_ONBOARD_TYPE_DATABASEJOIN",
    description: "COM_EMUNDUS_ONBOARD_TYPE_DATABASEJOIN_DESC",
    published: true
  },
  {
    value: "currency",
    icon: "money",
    name: "COM_EMUNDUS_ONBOARD_TYPE_CURRENCY",
    description: "COM_EMUNDUS_ONBOARD_TYPE_CURRENCY_DESC",
    published: true
  },
  {
    value: "panel",
    icon: "view_agenda",
    name: "COM_EMUNDUS_ONBOARD_TYPE_PANEL",
    description: "COM_EMUNDUS_ONBOARD_TYPE_PANEL_DESC",
    published: true
  },
  {
    value: "emundus_colorpicker",
    icon: "palette",
    name: "COM_EMUNDUS_ONBOARD_TYPE_COLOR",
    published: false
  },
  {
    value: "emundus_fileupload",
    icon: "file_upload",
    name: "COM_EMUNDUS_ONBOARD_TYPE_FILE",
    published: false
  },
  {
    value: "iban",
    icon: "account_balance",
    name: "COM_EMUNDUS_ONBOARD_TYPE_IBAN",
    description: "COM_EMUNDUS_ONBOARD_TYPE_IBAN_DESC",
    published: true
  },
  {
    value: "emundus_geolocalisation",
    icon: "map",
    name: "COM_EMUNDUS_ONBOARD_TYPE_GEOLOCATION",
    published: false
  }
];
const formBuilderSections = [
  {
    id: 1,
    value: "personal_details",
    icon: "recent_actors",
    name: "COM_EMUNDUS_ONBOARD_SECTIONS_PERSONAL_DETAILS",
    labels: {
      fr: "Informations personnelles",
      en: "Personal details"
    },
    published: true,
    elements: [
      {
        value: "radiobutton",
        labels: {
          fr: "Civilité",
          en: "Title"
        },
        options: [
          {
            value: "Monsieur",
            labels: {
              fr: "Monsieur",
              en: "Mr"
            }
          },
          {
            value: "Madame",
            labels: {
              fr: "Madame",
              en: "Mrs"
            }
          }
        ]
      },
      {
        value: "nom"
      },
      {
        value: "prenom"
      },
      {
        value: "field",
        labels: {
          fr: "Date de naissance",
          en: "Date of birth"
        },
        params: {
          text_input_mask: "99/99/9999"
        }
      },
      {
        value: "field",
        labels: {
          fr: "Ville de naissance",
          en: "Place of birth"
        }
      },
      {
        value: "databasejoin",
        labels: {
          fr: "Pays de naissance",
          en: "Country of birth"
        },
        params: {
          join_db_name: "data_country",
          join_key_column: "id",
          join_val_column: "label_fr",
          join_val_column_concat: "{thistable}.label_{shortlang}"
        }
      },
      {
        value: "databasejoin",
        labels: {
          fr: "Nationalité",
          en: "Nationality"
        },
        params: {
          join_db_name: "data_nationality",
          join_key_column: "id",
          join_val_column: "label_fr",
          join_val_column_concat: "{thistable}.label_{shortlang}"
        }
      }
    ]
  },
  {
    id: 2,
    value: "address",
    icon: "home",
    name: "COM_EMUNDUS_ONBOARD_SECTIONS_ADRESS",
    labels: {
      fr: "Adresse et coordonnées",
      en: "Adress"
    },
    published: true,
    elements: [
      {
        value: "field",
        labels: {
          fr: "Adresse",
          en: "Adress"
        }
      },
      {
        value: "field",
        labels: {
          fr: "Code postal",
          en: "Postal code"
        }
      },
      {
        value: "field",
        labels: {
          fr: "Ville",
          en: "City"
        }
      },
      {
        value: "databasejoin",
        labels: {
          fr: "Pays",
          en: "Country"
        },
        params: {
          join_db_name: "data_country",
          join_key_column: "id",
          join_val_column: "label_fr",
          join_val_column_concat: "{thistable}.label_{shortlang}"
        }
      },
      {
        value: "emundus_phonenumber",
        labels: {
          fr: "Téléphone",
          en: "Phone"
        }
      }
    ]
  },
  {
    id: 3,
    value: "bank_details",
    icon: "account_balance",
    name: "COM_EMUNDUS_ONBOARD_SECTIONS_BANK_DETAILS",
    labels: {
      fr: "RIB",
      en: "RIB"
    },
    published: true,
    elements: [
      {
        value: "iban",
        labels: {
          fr: "IBAN",
          en: "IBAN"
        },
        jsactions: {
          event: "change",
          code: "prefillBic(this,'$1');"
        }
      },
      {
        value: "field",
        labels: {
          fr: "BIC",
          en: "BIC"
        }
      }
    ]
  }
];
const FormBuilderElements_vue_vue_type_style_index_0_lang = "";
const _sfc_main$t = {
  components: {
    draggable: VueDraggableNext
  },
  mixins: [formBuilderMixin, errors],
  props: {
    form: {
      type: Object,
      required: false
    }
  },
  data() {
    return {
      selected: 1,
      menus: [
        {
          id: 1,
          name: "COM_EMUNDUS_FORM_BUILDER_ELEMENTS"
        },
        {
          id: 2,
          name: "COM_EMUNDUS_FORM_BUILDER_SECTIONS"
        }
      ],
      elements: [],
      groups: [],
      cloneElement: {},
      loading: false,
      elementHovered: 0,
      keywords: "",
      debounce: false
    };
  },
  setup() {
    const globalStore = useGlobalStore();
    return {
      globalStore
    };
  },
  created() {
    this.elements = formBuilderElements;
    this.groups = formBuilderSections;
  },
  methods: {
    setCloneElement(element) {
      this.cloneElement = element;
    },
    onDragEnd(event) {
      this.loading = true;
      const to = event.to;
      if (to === null) {
        this.loading = false;
        return;
      }
      const group_id = to.dataset.sid;
      if (!group_id) {
        this.loading = false;
        return;
      }
      const data = this.globalStore.getDatas;
      const mode = typeof data.mode !== "undefined" ? data.mode.value : "forms";
      formBuilderService.createSimpleElement({
        gid: group_id,
        plugin: this.cloneElement.value,
        mode
      }).then((response) => {
        if (response.status && response.data > 0) {
          formBuilderService.updateElementOrder(group_id, response.data, event.newDraggableIndex).then(() => {
            this.$emit("element-created", response.data);
            this.updateLastSave();
            this.loading = false;
          });
        } else {
          this.displayError(response.msg);
          this.loading = false;
        }
      }).catch((error) => {
        console.warn(error);
        this.loading = false;
      });
    },
    addGroup(group) {
      this.loading = true;
      const globalStore = useGlobalStore();
      const data = globalStore.datas;
      const mode = typeof data.mode !== "undefined" ? data.mode.value : "forms";
      formBuilderService.createSectionSimpleElements({
        gid: group.id,
        fid: this.form.id,
        mode
      }).then((response) => {
        if (response.status && response.data.length > 0) {
          this.$emit("element-created");
          this.updateLastSave();
          this.loading = false;
        } else {
          this.displayError(response.msg);
          this.loading = false;
        }
      }).catch((error) => {
        console.warn(error);
        this.loading = false;
      });
    },
    clickCreateElement(element) {
      if (this.debounce) {
        return;
      }
      this.debounce = true;
      this.$emit("create-element-lastgroup", element);
      setTimeout(() => {
        this.debounce = false;
      }, 1e3);
    }
  },
  computed: {
    publishedElements() {
      if (this.keywords) {
        return this.elements.filter((element) => element.published && this.translate(element.name).toLowerCase().includes(this.keywords.toLowerCase()));
      } else {
        return this.elements.filter((element) => element.published);
      }
    },
    publishedGroups() {
      if (this.keywords) {
        return this.groups.filter((group) => group.published && this.translate(group.name).toLowerCase().includes(this.keywords.toLowerCase()));
      } else {
        return this.groups.filter((group) => group.published);
      }
    }
  }
};
const _hoisted_1$t = {
  id: "form-builder-elements",
  style: { "min-width": "260px" }
};
const _hoisted_2$t = { class: "tw-flex tw-items-center tw-justify-around" };
const _hoisted_3$s = ["onClick"];
const _hoisted_4$r = {
  key: 0,
  class: "tw-mt-2"
};
const _hoisted_5$p = ["placeholder"];
const _hoisted_6$n = ["onMouseover"];
const _hoisted_7$n = {
  class: "material-symbols-outlined",
  style: { "font-size": "18px" }
};
const _hoisted_8$j = { class: "tw-w-full tw-flex tw-flex-col" };
const _hoisted_9$g = { class: "tw-text-neutral-600 tw-text-xs" };
const _hoisted_10$b = { class: "tw-flex tw-items-center tw-h-[18px] tw-w-[18px]" };
const _hoisted_11$8 = ["onClick"];
const _hoisted_12$8 = {
  key: 1,
  class: "tw-mt-2"
};
const _hoisted_13$8 = ["placeholder"];
const _hoisted_14$7 = ["onClick"];
const _hoisted_15$7 = { class: "form-builder-element tw-flex tw-items-center tw-justify-between tw-cursor-pointer tw-gap-3 tw-p-3" };
const _hoisted_16$6 = { class: "material-symbols-outlined" };
const _hoisted_17$6 = { class: "tw-w-full tw-flex tw-flex-col" };
const _hoisted_18$6 = {
  key: 2,
  class: "em-page-loader"
};
function _sfc_render$t(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$t, [
    createBaseVNode("div", _hoisted_2$t, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.menus, (menu) => {
        return openBlock(), createElementBlock("div", {
          key: menu.id,
          id: "form-builder-elements-title",
          class: normalizeClass(["em-light-tabs tw-cursor-pointer", $data.selected === menu.id ? "em-light-selected-tab" : ""]),
          onClick: ($event) => $data.selected = menu.id
        }, toDisplayString(_ctx.translate(menu.name)), 11, _hoisted_3$s);
      }), 128))
    ]),
    $data.selected === 1 ? (openBlock(), createElementBlock("div", _hoisted_4$r, [
      withDirectives(createBaseVNode("input", {
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.keywords = $event),
        type: "text",
        class: "formbuilder-searchbar",
        placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_SEARCH_ELEMENT")
      }, null, 8, _hoisted_5$p), [
        [vModelText, $data.keywords]
      ]),
      createVNode(_component_draggable, {
        modelValue: $options.publishedElements,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $options.publishedElements = $event),
        class: "draggables-list",
        group: { name: "form-builder-section-elements", pull: "clone", put: false },
        sort: false,
        clone: $options.setCloneElement,
        onEnd: $options.onDragEnd
      }, {
        default: withCtx(() => [
          createVNode(TransitionGroup, null, {
            default: withCtx(() => [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.publishedElements, (element) => {
                return openBlock(), createElementBlock("div", {
                  key: element.value,
                  onMouseover: ($event) => $data.elementHovered = element.value,
                  onMouseleave: _cache[1] || (_cache[1] = ($event) => $data.elementHovered = 0),
                  class: "form-builder-element tw-flex tw-justify-between tw-items-start tw-gap-3 tw-p-3 tw-cursor-move"
                }, [
                  createBaseVNode("span", _hoisted_7$n, toDisplayString(element.icon), 1),
                  createBaseVNode("p", _hoisted_8$j, [
                    createTextVNode(toDisplayString(_ctx.translate(element.name)) + " ", 1),
                    createBaseVNode("span", _hoisted_9$g, toDisplayString(_ctx.translate(element.description)), 1)
                  ]),
                  createBaseVNode("div", _hoisted_10$b, [
                    withDirectives(createBaseVNode("span", {
                      class: "material-symbols-outlined tw-cursor-copy",
                      style: { "font-size": "18px" },
                      onClick: ($event) => $options.clickCreateElement(element)
                    }, "add_circle_outline", 8, _hoisted_11$8), [
                      [vShow, $data.elementHovered == element.value]
                    ])
                  ])
                ], 40, _hoisted_6$n);
              }), 128))
            ]),
            _: 1
          })
        ]),
        _: 1
      }, 8, ["modelValue", "clone", "onEnd"])
    ])) : createCommentVNode("", true),
    $data.selected === 2 ? (openBlock(), createElementBlock("div", _hoisted_12$8, [
      withDirectives(createBaseVNode("input", {
        "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.keywords = $event),
        type: "text",
        class: "formbuilder-searchbar",
        placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_SEARCH_SECTION")
      }, null, 8, _hoisted_13$8), [
        [vModelText, $data.keywords]
      ]),
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.publishedGroups, (group) => {
        return openBlock(), createElementBlock("div", {
          key: group.id,
          class: "draggables-list",
          onClick: ($event) => $options.addGroup(group)
        }, [
          createBaseVNode("div", _hoisted_15$7, [
            createBaseVNode("span", _hoisted_16$6, toDisplayString(group.icon), 1),
            createBaseVNode("p", _hoisted_17$6, toDisplayString(_ctx.translate(group.name)), 1),
            _cache[4] || (_cache[4] = createBaseVNode("span", { class: "material-symbols-outlined" }, "add_circle_outline", -1))
          ])
        ], 8, _hoisted_14$7);
      }), 128))
    ])) : createCommentVNode("", true),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_18$6)) : createCommentVNode("", true)
  ]);
}
const FormBuilderElements = /* @__PURE__ */ _export_sfc(_sfc_main$t, [["render", _sfc_render$t]]);
const field = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_FIELD_TYPE",
    name: "password",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "COM_EMUNDUS_ONBOARD_BUILDER_FIELD_TYPE_TEXT"
      },
      {
        value: 5,
        label: "COM_EMUNDUS_ONBOARD_BUILDER_FIELD_TYPE_LINK"
      },
      {
        value: 6,
        label: "COM_EMUNDUS_ONBOARD_BUILDER_FIELD_TYPE_NUMBER"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_PLACEHOLDER",
    name: "placeholder",
    type: "text",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_MAXLENGTH",
    name: "maxlength",
    type: "number",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_INPUT_MASK_TITLE",
    name: "text_input_mask",
    type: "text",
    helptext: "COM_EMUNDUS_ONBOARD_BUILDER_INPUT_MASK_HINT",
    placeholder: "COM_EMUNDUS_ONBOARD_BUILDER_INPUT_MASK_PLACEHOLDER",
    published: true,
    sysadmin_only: false
  }
];
const textarea = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_TEXTAREA_DISPLAY_LABEL",
    name: "textarea_showlabel",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: false,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_PLACEHOLDER",
    name: "textarea_placeholder",
    type: "text",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_TEXTAREA_WYSIWIG",
    name: "use_wysiwyg",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_TEXTAREA_LIMIT",
    name: "textarea-maxlength",
    type: "number",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_TEXTAREA_SHOWMAX_LIMIT",
    name: "textarea-showmax",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_TEXTAREA_LIMIT_TYPE",
    name: "textarea_limit_type",
    type: "dropdown",
    options: [
      {
        value: "char",
        label: "COM_EMUNDUS_ONBOARD_BUILDER_TEXTAREA_LIMIT_TYPE_CHARACTERS"
      },
      {
        value: "word",
        label: "COM_EMUNDUS_ONBOARD_BUILDER_TEXTAREA_LIMIT_TYPE_WORDS"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  }
];
const dropdown = [];
const checkbox = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_CHECKBOX_OPTIONS_PER_ROW",
    name: "ck_options_per_row",
    type: "number",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: true
  }
];
const radiobutton = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_RADIOBUTTON_OPTIONS_PER_ROW",
    name: "options_per_row",
    type: "number",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: true
  }
];
const birthday = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_ADVANCED",
    name: "advanced_behavior",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: false,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_LIST_METHOD",
    name: "birthday_yearopt",
    type: "dropdown",
    options: [
      {
        value: "number",
        label: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_LIST_METHOD_YEARS_NUMBER"
      },
      {
        value: "since",
        label: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_LIST_METHOD_SINCE_YEAR"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_LIST_METHOD_NUMBER",
    name: "birthday_yearstart",
    type: "number",
    helptext: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_LIST_METHOD_NUMBER_TIP",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_YEARS_FUTURE_PAST",
    name: "birthday_forward",
    type: "number",
    helptext: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_YEARS_FUTURE_PAST_TIP",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_FORMAT",
    name: "list_date_format",
    type: "dropdown",
    options: [
      {
        value: "d.m.Y",
        label: "21.12.2022"
      },
      {
        value: "m.d.Y",
        label: "12.21.2022"
      },
      {
        value: "d/m/Y",
        label: "21/12/2022"
      },
      {
        value: "d m",
        label: "COM_EMUNDUS_ONBOARD_BUILDER_BIRTHDAY_FORMAT_D_M"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  }
];
const yesno = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_YESNO_DEFAULT_VALUE",
    name: "yesno_default",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  }
];
const calc = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_CALC_VALUE",
    name: "calc_calculation",
    type: "textarea",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: true
  }
];
const databasejoin = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_DISPLAY_TYPE",
    name: "database_join_display_type",
    type: "dropdown",
    options: [
      {
        value: "dropdown",
        label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_DISPLAY_TYPE_DROPDOWN"
      },
      {
        value: "radio",
        label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_DISPLAY_TYPE_RADIO"
      },
      {
        value: "auto-complete",
        label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_DISPLAY_TYPE_AUTOCOMPLETE"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_TABLE",
    name: "join_db_name",
    type: "databasejoin",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_VALUE",
    name: "join_key_column",
    type: "databasejoin_cascade",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: true
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_LABEL",
    name: "join_val_column",
    type: "databasejoin_cascade",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: true
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_LABEL_CONCAT",
    name: "join_val_column_concat",
    type: "textarea",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: true
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_SHOW_PLEASE_SELECT",
    name: "database_join_show_please_select",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_EXCLUDE",
    name: "database_join_exclude",
    type: "sqldropdown",
    table: "{join_db_name}",
    key: "{join_key_column}",
    value: "{join_val_column}",
    translate: false,
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false,
    options: [],
    multiple: true,
    reload_on_change: "join_db_name"
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_WHERE",
    name: "database_join_where_sql",
    type: "textarea",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: true
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_DATABASEJOIN_WHERE_AJAX",
    name: "databasejoin_where_ajax",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: true
  }
];
const jdate = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_CALENDAR_DEFAULT_TODAY",
    name: "jdate_defaulttotoday",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  }
];
const emundus_geolocalisation = [
  {
    label: "COM_EMUNDUS_FORM_BUILDER_GEOLOC_DEFAULT_LNG",
    name: "default_lng",
    type: "text",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_FORM_BUILDER_GEOLOC_DEFAULT_LAT",
    name: "default_lat",
    type: "text",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_FORM_BUILDER_GEOLOC_DEFAULT_ZOOM",
    name: "default_zoom",
    type: "number",
    minlength: 1,
    maxlength: 20,
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_FORM_BUILDER_GEOLOC_GET_LOCATION",
    name: "get_location",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  }
];
const emundus_phonenumber = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_PHONENUMBER_DEFAULT_COUNTRY",
    name: "default_country",
    type: "sqldropdown",
    table: "data_country",
    key: "iso2",
    value: "label",
    translate: true,
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false,
    options: []
  }
];
const panel = [
  {
    label: "COM_EMUNDUS_FORM_BUILDER_PANEL_TYPE",
    name: "type",
    type: "dropdown",
    options: [
      {
        value: 1,
        label: "COM_EMUNDUS_FORM_BUILDER_PANEL_TYPE_INFORMATION"
      },
      {
        value: 2,
        label: "COM_EMUNDUS_FORM_BUILDER_PANEL_TYPE_WARNING"
      },
      {
        value: 3,
        label: "COM_EMUNDUS_FORM_BUILDER_PANEL_TYPE_ERROR"
      },
      {
        value: 4,
        label: "COM_EMUNDUS_FORM_BUILDER_PANEL_TYPE_NONE"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_FORM_BUILDER_PANEL_ACCORDION",
    name: "accordion",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_FORM_BUILDER_PANEL_ACCORDION_TITLE",
    name: "title",
    type: "text",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  }
];
const currency = [
  {
    label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES",
    name: "all_currencies_options",
    type: "repeatable",
    published: true,
    fields: [
      {
        label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_ISO",
        name: "iso3",
        type: "dropdown",
        options: [
          {
            value: "EUR",
            label: "Euro (€ EUR)"
          },
          {
            value: "USD",
            label: "United States dollar ($ USD)"
          },
          {
            value: "JPY",
            label: "Japanese yen (¥ JPY)"
          },
          {
            value: "GBP",
            label: "British pound (£ GBP)"
          }
        ],
        helptext: "",
        placeholder: "",
        published: true,
        sysadmin_only: false
      },
      {
        label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_MIN",
        name: "minimal_value",
        type: "number",
        helptext: "",
        placeholder: "",
        published: true,
        sysadmin_only: false
      },
      {
        label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_MAX",
        name: "maximal_value",
        type: "number",
        helptext: "",
        placeholder: "",
        published: true,
        sysadmin_only: false
      },
      {
        label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_THOUSAND_SEPARATOR",
        name: "thousand_separator",
        type: "dropdown",
        options: [
          {
            value: " ",
            label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_THOUSAND_SEPARATOR_BLANK_SPACE"
          },
          {
            value: ",",
            label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_THOUSAND_SEPARATOR_COMMA"
          },
          {
            value: ".",
            label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_THOUSAND_SEPARATOR_DOT"
          }
        ],
        helptext: "",
        placeholder: "",
        published: true,
        sysadmin_only: false
      },
      {
        label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_DECIMAL_SEPARATOR",
        name: "decimal_separator",
        type: "dropdown",
        options: [
          {
            value: " ",
            label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_THOUSAND_SEPARATOR_BLANK_SPACE"
          },
          {
            value: ",",
            label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_THOUSAND_SEPARATOR_COMMA"
          },
          {
            value: ".",
            label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_THOUSAND_SEPARATOR_DOT"
          }
        ],
        helptext: "",
        placeholder: "",
        published: true,
        sysadmin_only: false
      },
      {
        label: "COM_EMUNDUS_FORM_BUILDER_CURRENCY_CURRENCIES_DECIMAL_NUMBERS",
        name: "decimal_numbers",
        type: "number",
        helptext: "",
        placeholder: "",
        published: true,
        sysadmin_only: false
      }
    ]
  }
];
const elementParams = {
  field,
  textarea,
  dropdown,
  checkbox,
  radiobutton,
  birthday,
  yesno,
  calc,
  databasejoin,
  jdate,
  emundus_geolocalisation,
  emundus_phonenumber,
  panel,
  currency
};
const _sfc_main$s = {
  name: "FormBuilderElementParams",
  components: { Multiselect: script },
  props: {
    element: {
      type: Object,
      required: false
    },
    params: {
      type: Array,
      required: false
    },
    databases: {
      type: Array,
      required: false
    },
    repeat_name: {
      type: String,
      required: false,
      default: ""
    },
    index: {
      type: Number,
      required: false,
      default: 0
    }
  },
  data: () => ({
    databasejoin_description: null,
    reloadOptions: 0,
    reloadOptionsCascade: 0,
    idElement: 0,
    loading: false
  }),
  setup() {
    return {
      globalStore: useGlobalStore()
    };
  },
  created() {
    this.params.forEach((param) => {
      if (param.type === "databasejoin") {
        param.options = this.databases;
        if (this.element.params["join_db_name"] !== "") {
          this.updateDatabasejoinParams();
        }
      }
      if (param.type === "sqldropdown") {
        this.loading = true;
        this.getSqlDropdownOptions(param);
      }
      if (param.reload_on_change) {
        let param_to_watch = this.params.find((p) => p.name === param.reload_on_change);
        if (param_to_watch) {
          this.$watch(() => this.element.params[param_to_watch.name], (newValue, oldValue) => {
            if (newValue !== oldValue) {
              this.loading = true;
              this.getSqlDropdownOptions(param);
            }
          });
        }
      }
    });
  },
  methods: {
    getSqlDropdownOptions(param) {
      let table = param.table;
      let key = param.key;
      let value = param.value;
      if (param.table.includes("{")) {
        let param_name = param.table.match(/\{(.*?)\}/g)[0].replace("{", "").replace("}", "");
        table = this.element.params[param_name];
      }
      if (param.key.includes("{")) {
        let param_name = param.key.match(/\{(.*?)\}/g)[0].replace("{", "").replace("}", "");
        key = this.element.params[param_name];
      }
      if (param.value.includes("{")) {
        let param_name = param.value.match(/\{(.*?)\}/g)[0].replace("{", "").replace("}", "");
        value = this.element.params[param_name];
      }
      if (table.includes("{") || key.includes("{") || value.includes("{")) {
        return;
      }
      formBuilderService.getSqlDropdownOptions(table, key, value, param.translate).then((response) => {
        param.options = response.data;
        if (this.element.params[param.name] && typeof this.element.params[param.name] === "string" && this.element.params[param.name].length > 0) {
          let ids_to_exclude = this.element.params[param.name].split(",");
          const regex = /\'|"/ig;
          this.element.params[param.name] = [];
          ids_to_exclude.forEach((id) => {
            id = id.replace(regex, "");
            let option = param.options.find((option2) => id == option2.value);
            if (option) {
              this.element.params[param.name].push(option);
            }
          });
        } else {
          this.element.params[param.name] = [];
        }
        this.loading = false;
      });
    },
    updateDatabasejoinParams() {
      if (!this.sysadmin) {
        const index = this.databases.map((e) => e.database_name).indexOf(this.element.params["join_db_name"]);
        if (index !== -1) {
          let database = this.databases[index];
          this.element.params["join_key_column"] = database.join_column_id;
          if (database.translation == 1) {
            this.element.params["join_val_column"] = database.join_column_val + "_fr";
            this.element.params["join_val_column_concat"] = "{thistable}." + database.join_column_val + "_{shortlang}";
          } else {
            this.element.params["join_val_column"] = database.join_column_val;
            this.element.params["join_val_column_concat"] = "";
          }
          this.databasejoin_description = this.databases[index].description;
        } else {
          let index2 = this.params.map((e) => e.name).indexOf("join_db_name");
          let new_option = {
            label: this.element.params["join_db_name"],
            database_name: this.element.params["join_db_name"]
          };
          this.params[index2].options.push(new_option);
          setTimeout(() => {
            document.getElementById("join_db_name").disabled = true;
          }, 500);
        }
      } else {
        formBuilderService.getDatabaseJoinOrderColumns(this.element.params["join_db_name"]).then((response) => {
          let index = this.params.map((e) => e.name).indexOf("join_key_column");
          this.params[index].options = response.data;
          if (this.element.params["join_key_column"] === "") {
            this.element.params["join_key_column"] = this.params[index].options[0].COLUMN_NAME;
          }
          index = this.params.map((e) => e.name).indexOf("join_val_column");
          this.params[index].options = response.data;
          if (this.element.params["join_val_column"] === "") {
            this.element.params["join_val_column"] = this.params[index].options[0].COLUMN_NAME;
          }
          this.reloadOptionsCascade += 1;
        });
      }
    },
    addRepeatableField(param) {
      let index = Object.entries(this.element.params[param]).length;
      this.element.params[param][param + index] = {};
      this.$forceUpdate();
    },
    removeRepeatableField(param, key) {
      delete this.element.params[param][param + key];
      this.$forceUpdate();
    },
    labelTranslate({ label }) {
      return this.translate(label);
    }
  },
  computed: {
    sysadmin: function() {
      return parseInt(this.globalStore.hasSysadminAccess);
    },
    index_name: function() {
      return this.repeat_name !== "" ? this.repeat_name + this.index : "";
    },
    displayedParams() {
      console.log(this.params);
      return this.params.filter((param) => {
        return param.published && !param.sysadmin_only || this.sysadmin && param.sysadmin_only && param.published;
      });
    }
  }
};
const _hoisted_1$s = { class: "form-group tw-mb-4" };
const _hoisted_2$s = { key: 0 };
const _hoisted_3$r = ["onUpdate:modelValue"];
const _hoisted_4$q = ["value"];
const _hoisted_5$o = ["onUpdate:modelValue"];
const _hoisted_6$m = ["value"];
const _hoisted_7$m = ["onUpdate:modelValue"];
const _hoisted_8$i = ["onUpdate:modelValue"];
const _hoisted_9$f = { key: 3 };
const _hoisted_10$a = ["onUpdate:modelValue", "id"];
const _hoisted_11$7 = ["value"];
const _hoisted_12$7 = {
  key: 0,
  style: { "font-size": "small" }
};
const _hoisted_13$7 = { key: 4 };
const _hoisted_14$6 = ["onUpdate:modelValue", "id"];
const _hoisted_15$6 = ["value"];
const _hoisted_16$5 = {
  key: 0,
  style: { "font-size": "small" }
};
const _hoisted_17$5 = { key: 5 };
const _hoisted_18$5 = ["onUpdate:modelValue"];
const _hoisted_19$5 = ["value"];
const _hoisted_20$5 = { key: 6 };
const _hoisted_21$3 = ["onUpdate:modelValue"];
const _hoisted_22$3 = ["value"];
const _hoisted_23$3 = { key: 7 };
const _hoisted_24$3 = { class: "tw-flex tw-justify-between tw-items-center" };
const _hoisted_25$3 = ["onClick"];
const _hoisted_26$3 = { class: "tw-flex tw-justify-end" };
const _hoisted_27$3 = ["onClick"];
const _hoisted_28$2 = ["type", "onUpdate:modelValue", "placeholder"];
const _hoisted_29$2 = ["type", "onUpdate:modelValue", "placeholder"];
const _hoisted_30$2 = {
  key: 10,
  style: { "font-size": "small" }
};
const _hoisted_31$2 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$s(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  const _component_form_builder_element_params = resolveComponent("form-builder-element-params", true);
  return openBlock(), createElementBlock("div", null, [
    (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedParams, (param) => {
      return openBlock(), createElementBlock("div", _hoisted_1$s, [
        createBaseVNode("label", {
          class: normalizeClass(param.type === "repeatable" ? "tw-font-bold" : "")
        }, toDisplayString(_ctx.translate(param.label)), 3),
        param.type === "dropdown" || param.type === "sqldropdown" ? (openBlock(), createElementBlock("div", _hoisted_2$s, [
          $props.repeat_name !== "" && param.options.length > 0 && !param.multiple ? withDirectives((openBlock(), createElementBlock("select", {
            key: 0,
            "onUpdate:modelValue": ($event) => $props.element.params[$props.repeat_name][$options.index_name][param.name] = $event,
            class: "tw-w-full"
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(param.options, (option) => {
              return openBlock(), createElementBlock("option", {
                key: option.value,
                value: option.value
              }, toDisplayString(_ctx.translate(option.label)), 9, _hoisted_4$q);
            }), 128))
          ], 8, _hoisted_3$r)), [
            [vModelSelect, $props.element.params[$props.repeat_name][$options.index_name][param.name]]
          ]) : param.options.length > 0 && !param.multiple ? withDirectives((openBlock(), createElementBlock("select", {
            key: 1,
            "onUpdate:modelValue": ($event) => $props.element.params[param.name] = $event,
            class: "tw-w-full"
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(param.options, (option) => {
              return openBlock(), createElementBlock("option", {
                value: option.value
              }, toDisplayString(_ctx.translate(option.label)), 9, _hoisted_6$m);
            }), 256))
          ], 8, _hoisted_5$o)), [
            [vModelSelect, $props.element.params[param.name]]
          ]) : param.options.length > 0 && param.multiple ? (openBlock(), createBlock(_component_multiselect, {
            key: 2,
            modelValue: $props.element.params[param.name],
            "onUpdate:modelValue": ($event) => $props.element.params[param.name] = $event,
            label: "label",
            "custom-label": $options.labelTranslate,
            "track-by": "value",
            options: param.options,
            multiple: true,
            taggable: false,
            "select-label": "",
            "selected-label": "",
            "deselect-label": "",
            placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_OPTIONS"),
            "close-on-select": false,
            "clear-on-select": false,
            searchable: true,
            "allow-empty": true
          }, null, 8, ["modelValue", "onUpdate:modelValue", "custom-label", "options", "placeholder"])) : createCommentVNode("", true)
        ])) : param.type === "textarea" && $props.repeat_name !== "" ? withDirectives((openBlock(), createElementBlock("textarea", {
          key: 1,
          "onUpdate:modelValue": ($event) => $props.element.params[$props.repeat_name][$options.index_name][param.name] = $event,
          class: "tw-w-full"
        }, null, 8, _hoisted_7$m)), [
          [vModelText, $props.element.params[$props.repeat_name][$options.index_name][param.name]]
        ]) : param.type === "textarea" ? withDirectives((openBlock(), createElementBlock("textarea", {
          key: 2,
          "onUpdate:modelValue": ($event) => $props.element.params[param.name] = $event,
          class: "tw-w-full"
        }, null, 8, _hoisted_8$i)), [
          [vModelText, $props.element.params[param.name]]
        ]) : param.type === "databasejoin" && $props.repeat_name !== "" ? (openBlock(), createElementBlock("div", _hoisted_9$f, [
          withDirectives((openBlock(), createElementBlock("select", {
            "onUpdate:modelValue": ($event) => $props.element.params[$props.repeat_name][$options.index_name][param.name] = $event,
            key: _ctx.reloadOptions,
            id: param.name,
            onChange: _cache[0] || (_cache[0] = (...args) => $options.updateDatabasejoinParams && $options.updateDatabasejoinParams(...args)),
            class: normalizeClass(["tw-w-full", _ctx.databasejoin_description ? "tw-mb-1" : ""])
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(param.options, (option) => {
              return openBlock(), createElementBlock("option", {
                key: option.database_name,
                value: option.database_name
              }, toDisplayString(option.label), 9, _hoisted_11$7);
            }), 128))
          ], 42, _hoisted_10$a)), [
            [vModelSelect, $props.element.params[$props.repeat_name][$options.index_name][param.name]]
          ]),
          _ctx.databasejoin_description ? (openBlock(), createElementBlock("label", _hoisted_12$7, toDisplayString(_ctx.databasejoin_description), 1)) : createCommentVNode("", true)
        ])) : param.type === "databasejoin" ? (openBlock(), createElementBlock("div", _hoisted_13$7, [
          withDirectives((openBlock(), createElementBlock("select", {
            "onUpdate:modelValue": ($event) => $props.element.params[param.name] = $event,
            key: _ctx.reloadOptions,
            id: param.name,
            onChange: _cache[1] || (_cache[1] = (...args) => $options.updateDatabasejoinParams && $options.updateDatabasejoinParams(...args)),
            class: normalizeClass(["tw-w-full", _ctx.databasejoin_description ? "tw-mb-1" : ""])
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(param.options, (option) => {
              return openBlock(), createElementBlock("option", {
                key: option.database_name,
                value: option.database_name
              }, toDisplayString(option.label), 9, _hoisted_15$6);
            }), 128))
          ], 42, _hoisted_14$6)), [
            [vModelSelect, $props.element.params[param.name]]
          ]),
          _ctx.databasejoin_description ? (openBlock(), createElementBlock("label", _hoisted_16$5, toDisplayString(_ctx.databasejoin_description), 1)) : createCommentVNode("", true)
        ])) : param.type === "databasejoin_cascade" && $props.repeat_name !== "" ? (openBlock(), createElementBlock("div", _hoisted_17$5, [
          withDirectives((openBlock(), createElementBlock("select", {
            "onUpdate:modelValue": ($event) => $props.element.params[$props.repeat_name][$options.index_name][param.name] = $event,
            key: _ctx.reloadOptionsCascade,
            class: "tw-w-full"
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(param.options, (option) => {
              return openBlock(), createElementBlock("option", {
                key: option.COLUMN_NAME,
                value: option.COLUMN_NAME
              }, toDisplayString(option.COLUMN_NAME), 9, _hoisted_19$5);
            }), 128))
          ], 8, _hoisted_18$5)), [
            [vModelSelect, $props.element.params[$props.repeat_name][$options.index_name][param.name]]
          ])
        ])) : param.type === "databasejoin_cascade" ? (openBlock(), createElementBlock("div", _hoisted_20$5, [
          withDirectives((openBlock(), createElementBlock("select", {
            "onUpdate:modelValue": ($event) => $props.element.params[param.name] = $event,
            key: _ctx.reloadOptionsCascade,
            class: "tw-w-full"
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(param.options, (option) => {
              return openBlock(), createElementBlock("option", {
                key: option.COLUMN_NAME,
                value: option.COLUMN_NAME
              }, toDisplayString(option.COLUMN_NAME), 9, _hoisted_22$3);
            }), 128))
          ], 8, _hoisted_21$3)), [
            [vModelSelect, $props.element.params[param.name]]
          ])
        ])) : param.type === "repeatable" ? (openBlock(), createElementBlock("div", _hoisted_23$3, [
          (openBlock(true), createElementBlock(Fragment, null, renderList(Object.entries($props.element.params[param.name]), (repeat_param, key) => {
            return openBlock(), createElementBlock("div", { key }, [
              _cache[3] || (_cache[3] = createBaseVNode("hr", null, null, -1)),
              createBaseVNode("div", _hoisted_24$3, [
                createBaseVNode("label", null, "-- " + toDisplayString(key + 1) + " --", 1),
                key != 0 && key + 1 == Object.entries($props.element.params[param.name]).length ? (openBlock(), createElementBlock("button", {
                  key: 0,
                  type: "button",
                  onClick: ($event) => $options.removeRepeatableField(param.name, key),
                  class: "mt-2 w-auto"
                }, _cache[2] || (_cache[2] = [
                  createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "close", -1)
                ]), 8, _hoisted_25$3)) : createCommentVNode("", true)
              ]),
              (openBlock(), createBlock(_component_form_builder_element_params, {
                key: param.name + key,
                element: $props.element,
                params: param.fields,
                repeat_name: param.name,
                index: key,
                databases: $props.databases
              }, null, 8, ["element", "params", "repeat_name", "index", "databases"]))
            ]);
          }), 128)),
          createBaseVNode("div", _hoisted_26$3, [
            createBaseVNode("button", {
              type: "button",
              onClick: ($event) => $options.addRepeatableField(param.name),
              class: "tw-btn-tertiary tw-mt-2 tw-w-auto"
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE")), 9, _hoisted_27$3)
          ])
        ])) : $props.repeat_name !== "" ? withDirectives((openBlock(), createElementBlock("input", {
          key: 8,
          type: param.type,
          "onUpdate:modelValue": ($event) => $props.element.params[$props.repeat_name][$options.index_name][param.name] = $event,
          class: "tw-w-full",
          placeholder: _ctx.translate(param.placeholder)
        }, null, 8, _hoisted_28$2)), [
          [vModelDynamic, $props.element.params[$props.repeat_name][$options.index_name][param.name]]
        ]) : withDirectives((openBlock(), createElementBlock("input", {
          key: 9,
          type: param.type,
          "onUpdate:modelValue": ($event) => $props.element.params[param.name] = $event,
          class: "tw-w-full",
          placeholder: _ctx.translate(param.placeholder)
        }, null, 8, _hoisted_29$2)), [
          [vModelDynamic, $props.element.params[param.name]]
        ]),
        param.helptext !== "" ? (openBlock(), createElementBlock("label", _hoisted_30$2, toDisplayString(_ctx.translate(param.helptext)), 1)) : createCommentVNode("", true)
      ]);
    }), 256)),
    _ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_31$2)) : createCommentVNode("", true)
  ]);
}
const FormBuilderElementParams = /* @__PURE__ */ _export_sfc(_sfc_main$s, [["render", _sfc_render$s]]);
const FormBuilderElementProperties_vue_vue_type_style_index_0_lang = "";
const _sfc_main$r = {
  name: "FormBuilderElementProperties",
  components: {
    FormBuilderElementParams,
    TipTapEditor: V32
  },
  props: {
    element: {
      type: Object,
      required: true
    },
    profile_id: {
      type: Number,
      required: true
    }
  },
  mixins: [formBuilderMixin],
  data() {
    return {
      databases: [],
      params: [],
      elementsNeedingDb: [
        "dropdown",
        "checkbox",
        "radiobutton",
        "databasejoin"
      ],
      tabs: [
        {
          id: 0,
          label: "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL",
          active: true,
          published: true
        },
        {
          id: 1,
          label: "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_PARAMETERS",
          active: false,
          published: true
        }
      ],
      loading: false,
      editorPlugins: ["history", "link", "image", "bold", "italic", "underline", "left", "center", "right", "h1", "h2", "ul"],
      advancedSettings: false
    };
  },
  setup() {
    return {
      globalStore: useGlobalStore()
    };
  },
  mounted() {
    this.getDatabases();
    this.paramsAvailable();
  },
  methods: {
    getDatabases() {
      formBuilderService.getDatabases().then((response) => {
        console.log(response);
        if (response.status) {
          this.databases = response.data;
        }
      });
    },
    saveProperties() {
      this.loading = true;
      formBuilderService.updateTranslation({
        value: this.element.id,
        key: "element"
      }, this.element.label_tag, this.element.label);
      if (["radiobutton", "checkbox", "dropdown"].includes(this.element.plugin)) {
        formBuilderService.getJTEXTA(this.element.params.sub_options.sub_labels).then((response) => {
          if (response) {
            this.element.params.sub_options.sub_labels.forEach((label, index) => {
              this.element.params.sub_options.sub_labels[index] = Object.values(response.data)[index];
            });
            formBuilderService.updateParams(this.element).then((response2) => {
              if (response2.status) {
                this.loading = false;
                this.updateLastSave();
                this.$emit("close");
              }
            });
          }
        });
      } else {
        formBuilderService.updateParams(this.element).then((response) => {
          if (response.status) {
            this.loading = false;
            this.updateLastSave();
            this.$emit("close");
          }
        });
      }
    },
    togglePublish() {
      this.element.publish = !this.element.publish;
      formBuilderService.toggleElementPublishValue(this.element.id).then((response) => {
        if (!response.status) {
          this.element.publish = !this.element.publish;
        }
      });
    },
    toggleHidden() {
      this.element.hidden = !this.element.hidden;
      formBuilderService.toggleElementHiddenValue(this.element.id).then((response) => {
        if (!response.status) {
          this.element.hidden = !this.element.hidden;
        }
      });
    },
    toggleShowInList() {
      this.element.show_in_list_summary = this.element.show_in_list_summary == 1 ? 0 : 1;
      formBuilderService.updateElementParam(this.element.id, "show_in_list_summary", this.element.show_in_list_summary ? 1 : 0).then((response) => {
        if (!response.status) {
          this.element.show_in_list_summary = this.element.show_in_list_summary == 1 ? 0 : 1;
        }
      });
    },
    selectTab(tab) {
      this.tabs.forEach((t) => {
        t.active = false;
      });
      tab.active = true;
    },
    paramsAvailable() {
      if (typeof elementParams[this.element.plugin] !== "undefined") {
        this.tabs[1].published = true;
        this.params = elementParams[this.element.plugin];
      } else {
        this.tabs[1].active = false;
        this.tabs[0].active = true;
        this.tabs[1].published = false;
      }
    },
    formatAlias() {
      this.element.params.alias = this.element.params.alias.toLowerCase().replace(/ /g, "_");
      this.element.params.alias = this.element.params.alias.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
      this.element.params.alias = this.element.params.alias.replace(/[^a-z0-9_]/g, "");
    }
  },
  computed: {
    componentType() {
      let type = "";
      switch (this.element.plugin) {
        case "databasejoin":
          type = this.element.params.database_join_display_type == "radio" ? "radiobutton" : this.element.params.database_join_display_type;
          break;
        case "years":
        case "date":
        case "birthday":
          type = "birthday";
          break;
        default:
          type = this.element.plugin;
          break;
      }
      return type;
    },
    isPublished() {
      return !this.element.publish;
    },
    isHidden() {
      return this.element.hidden;
    },
    sysadmin: function() {
      return parseInt(this.globalStore.hasSysadminAccess);
    },
    publishedTabs() {
      return this.tabs.filter((tab) => {
        return tab.published;
      });
    }
  },
  watch: {
    "element.eval": function(value) {
      if (value == 0) {
        this.element.default = this.element.default.replace(/<p>/g, "\n");
        this.element.default = this.element.default.replace(/(<([^>]+)>)/gi, "");
      }
    },
    "element.id": function(value) {
      this.paramsAvailable();
    }
  }
};
const _hoisted_1$r = { id: "form-builder-element-properties" };
const _hoisted_2$r = { class: "tw-flex tw-items-center tw-justify-between tw-p-4 tw-items-start" };
const _hoisted_3$q = { class: "tw-text-sm tw-text-neutral-700" };
const _hoisted_4$p = {
  id: "properties-tabs",
  class: "tw-flex tw-items-center tw-justify-between tw-p-4 tw-w-11/12"
};
const _hoisted_5$n = ["onClick"];
const _hoisted_6$l = { id: "properties" };
const _hoisted_7$l = {
  key: 0,
  id: "element-parameters",
  class: "tw-p-4"
};
const _hoisted_8$h = { for: "element-label" };
const _hoisted_9$e = {
  key: 0,
  class: "tw-mt-4"
};
const _hoisted_10$9 = { for: "element-rollover" };
const _hoisted_11$6 = { class: "tw-flex tw-items-center tw-justify-between tw-w-full tw-pt-4 tw-pb-4" };
const _hoisted_12$6 = { class: "em-toggle" };
const _hoisted_13$6 = { class: "tw-flex tw-items-center tw-justify-between tw-w-full tw-pt-4 tw-pb-4" };
const _hoisted_14$5 = { class: "em-toggle" };
const _hoisted_15$5 = { class: "tw-flex tw-items-center tw-justify-between tw-w-full tw-pt-4 tw-pb-4" };
const _hoisted_16$4 = { class: "em-toggle" };
const _hoisted_17$4 = { class: "tw-w-full tw-pt-4 tw-pb-4" };
const _hoisted_18$4 = { for: "element-default" };
const _hoisted_19$4 = {
  key: 1,
  class: "tw-p-4 tw-flex tw-flex-col tw-gap-3"
};
const _hoisted_20$4 = { key: 0 };
const _hoisted_21$2 = { for: "element-alias" };
const _hoisted_22$2 = {
  key: 1,
  class: "tw-flex tw-justify-between tw-w-full"
};
const _hoisted_23$2 = { class: "em-toggle" };
const _hoisted_24$2 = { class: "tw-flex tw-justify-between tw-w-full" };
const _hoisted_25$2 = { class: "em-toggle" };
const _hoisted_26$2 = { class: "tw-flex tw-items-center tw-justify-between actions tw-m-4" };
const _hoisted_27$2 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$r(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_tip_tap_editor = resolveComponent("tip-tap-editor");
  const _component_FormBuilderElementParams = resolveComponent("FormBuilderElementParams");
  return openBlock(), createElementBlock("div", _hoisted_1$r, [
    createBaseVNode("div", _hoisted_2$r, [
      createBaseVNode("div", null, [
        createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES")), 1),
        createBaseVNode("span", _hoisted_3$q, toDisplayString($props.element.label[_ctx.shortDefaultLang]), 1)
      ]),
      createBaseVNode("span", {
        class: "material-symbols-outlined tw-cursor-pointer",
        onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
      }, "close")
    ]),
    createBaseVNode("ul", _hoisted_4$p, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.publishedTabs, (tab) => {
        return openBlock(), createElementBlock("li", {
          key: tab.id,
          class: normalizeClass([{ "is-active": tab.active, "tw-w-2/4": $options.publishedTabs.length == 2, "tw-w-full": $options.publishedTabs.length == 1 }, "tw-p-4 tw-cursor-pointer"]),
          onClick: ($event) => $options.selectTab(tab)
        }, toDisplayString(_ctx.translate(tab.label)), 11, _hoisted_5$n);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_6$l, [
      $data.tabs[0].active ? (openBlock(), createElementBlock("div", _hoisted_7$l, [
        createBaseVNode("label", _hoisted_8$h, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_LABEL")), 1),
        withDirectives(createBaseVNode("input", {
          id: "element-label",
          name: "element-label",
          class: "tw-w-full",
          type: "text",
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $props.element.label[_ctx.shortDefaultLang] = $event)
        }, null, 512), [
          [vModelText, $props.element.label[_ctx.shortDefaultLang]]
        ]),
        $props.element.params ? (openBlock(), createElementBlock("div", _hoisted_9$e, [
          createBaseVNode("label", _hoisted_10$9, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_BUILDER_HELPTEXT")), 1),
          withDirectives(createBaseVNode("input", {
            id: "element-rollover",
            name: "element-alias",
            type: "text",
            "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $props.element.params.rollover = $event)
          }, null, 512), [
            [vModelText, $props.element.params.rollover]
          ])
        ])) : createCommentVNode("", true),
        createBaseVNode("div", _hoisted_11$6, [
          createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_UNPUBLISH")), 1),
          createBaseVNode("div", _hoisted_12$6, [
            withDirectives(createBaseVNode("input", {
              type: "checkbox",
              class: "em-toggle-check",
              "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $options.isPublished = $event),
              onClick: _cache[4] || (_cache[4] = (...args) => $options.togglePublish && $options.togglePublish(...args))
            }, null, 512), [
              [vModelCheckbox, $options.isPublished]
            ]),
            _cache[18] || (_cache[18] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
            _cache[19] || (_cache[19] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
          ])
        ]),
        withDirectives(createBaseVNode("div", _hoisted_13$6, [
          createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_REQUIRED")), 1),
          createBaseVNode("div", _hoisted_14$5, [
            withDirectives(createBaseVNode("input", {
              type: "checkbox",
              class: "em-toggle-check",
              "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $props.element.FRequire = $event),
              onClick: _cache[6] || (_cache[6] = ($event) => {
                $props.element.FRequire = !$props.element.FRequire;
              })
            }, null, 512), [
              [vModelCheckbox, $props.element.FRequire]
            ]),
            _cache[20] || (_cache[20] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
            _cache[21] || (_cache[21] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
          ])
        ], 512), [
          [vShow, !["display", "panel"].includes(this.element.plugin)]
        ]),
        withDirectives(createBaseVNode("div", _hoisted_15$5, [
          createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_ADVANCED_FORMAT")), 1),
          createBaseVNode("div", _hoisted_16$4, [
            withDirectives(createBaseVNode("input", {
              type: "checkbox",
              "true-value": "1",
              "false-value": "0",
              class: "em-toggle-check",
              "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $props.element.eval = $event),
              onClick: _cache[8] || (_cache[8] = ($event) => $props.element.eval == 1 ? $props.element.eval = 0 : $props.element.eval = 1)
            }, null, 512), [
              [vModelCheckbox, $props.element.eval]
            ]),
            _cache[22] || (_cache[22] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
            _cache[23] || (_cache[23] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
          ])
        ], 512), [
          [vShow, this.element.plugin == "panel"]
        ]),
        withDirectives(createBaseVNode("div", _hoisted_17$4, [
          createBaseVNode("label", _hoisted_18$4, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_CONTENT")), 1),
          $props.element.eval == 0 ? withDirectives((openBlock(), createElementBlock("textarea", {
            key: 0,
            id: "element-default",
            name: "element-default",
            "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => $props.element.default = $event),
            class: "tw-w-full tw-resize-y"
          }, null, 512)), [
            [vModelText, $props.element.default]
          ]) : createCommentVNode("", true),
          $props.element.eval == 1 ? (openBlock(), createBlock(_component_tip_tap_editor, {
            key: 1,
            modelValue: $props.element.default,
            "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => $props.element.default = $event),
            id: "element-default",
            "upload-url": "/index.php?option=com_emundus&controller=settings&task=uploadmedia",
            "editor-content-height": "30em",
            class: normalizeClass("tw-mt-1"),
            locale: "fr",
            preset: "custom",
            plugins: $data.editorPlugins,
            "toolbar-classes": ["tw-bg-white"],
            "editor-content-classes": ["tw-bg-white"]
          }, null, 8, ["modelValue", "plugins"])) : createCommentVNode("", true)
        ], 512), [
          [vShow, this.element.plugin == "panel"]
        ])
      ])) : createCommentVNode("", true),
      $data.tabs[1].active ? (openBlock(), createElementBlock("div", _hoisted_19$4, [
        $props.element.params ? (openBlock(), createElementBlock("div", _hoisted_20$4, [
          createBaseVNode("label", _hoisted_21$2, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_ALIAS")), 1),
          withDirectives(createBaseVNode("input", {
            id: "element-alias",
            name: "element-alias",
            type: "text",
            "onUpdate:modelValue": _cache[11] || (_cache[11] = ($event) => $props.element.params.alias = $event),
            onKeyup: _cache[12] || (_cache[12] = (...args) => $options.formatAlias && $options.formatAlias(...args))
          }, null, 544), [
            [vModelText, $props.element.params.alias]
          ])
        ])) : createCommentVNode("", true),
        $options.sysadmin ? (openBlock(), createElementBlock("div", _hoisted_22$2, [
          createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_HIDDEN")), 1),
          createBaseVNode("div", _hoisted_23$2, [
            withDirectives(createBaseVNode("input", {
              type: "checkbox",
              class: "em-toggle-check",
              "onUpdate:modelValue": _cache[13] || (_cache[13] = ($event) => $options.isHidden = $event),
              onClick: _cache[14] || (_cache[14] = (...args) => $options.toggleHidden && $options.toggleHidden(...args))
            }, null, 512), [
              [vModelCheckbox, $options.isHidden]
            ]),
            _cache[24] || (_cache[24] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
            _cache[25] || (_cache[25] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
          ])
        ])) : createCommentVNode("", true),
        createBaseVNode("div", _hoisted_24$2, [
          createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_SHOW_IN_LIST_SUMMARY")), 1),
          createBaseVNode("div", _hoisted_25$2, [
            withDirectives(createBaseVNode("input", {
              "true-value": "1",
              "false-value": "0",
              type: "checkbox",
              class: "em-toggle-check",
              id: "show-in-list-summary",
              name: "show-in-list-summary",
              "onUpdate:modelValue": _cache[15] || (_cache[15] = ($event) => $props.element.show_in_list_summary = $event),
              onClick: _cache[16] || (_cache[16] = (...args) => $options.toggleShowInList && $options.toggleShowInList(...args))
            }, null, 512), [
              [vModelCheckbox, $props.element.show_in_list_summary]
            ]),
            _cache[26] || (_cache[26] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
            _cache[27] || (_cache[27] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
          ])
        ]),
        (openBlock(), createBlock(_component_FormBuilderElementParams, {
          element: $props.element,
          params: $data.params,
          key: $props.element.id,
          databases: $data.databases
        }, null, 8, ["element", "params", "databases"]))
      ])) : createCommentVNode("", true)
    ]),
    createBaseVNode("div", _hoisted_26$2, [
      createBaseVNode("button", {
        class: "tw-btn-primary",
        onClick: _cache[17] || (_cache[17] = ($event) => $options.saveProperties())
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_SAVE")), 1)
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_27$2)) : createCommentVNode("", true)
  ]);
}
const FormBuilderElementProperties = /* @__PURE__ */ _export_sfc(_sfc_main$r, [["render", _sfc_render$r]]);
const parameters = [
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_SECTIONS_OUTRO",
    name: "outro",
    type: "textarea",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_SECTIONS_REPEAT",
    name: "repeat_group_button",
    type: "dropdown",
    options: [
      {
        value: 0,
        label: "JNO"
      },
      {
        value: 1,
        label: "JYES"
      }
    ],
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_SECTIONS_REPEAT_MIN",
    name: "repeat_min",
    type: "number",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  },
  {
    label: "COM_EMUNDUS_ONBOARD_BUILDER_SECTIONS_REPEAT_MAX",
    name: "repeat_max",
    type: "number",
    helptext: "",
    placeholder: "",
    published: true,
    sysadmin_only: false
  }
];
const sectionParams = {
  parameters
};
const _sfc_main$q = {
  name: "FormBuilderSectionParams",
  props: {
    section: {
      type: Object,
      required: false
    },
    params: {
      type: Array,
      required: false
    }
  },
  data: () => ({
    loading: false
  }),
  setup() {
    const globalStore = useGlobalStore();
    return {
      globalStore
    };
  },
  computed: {
    sysadmin: function() {
      return parseInt(this.globalStore.hasSysadminAccess);
    },
    displayedParams() {
      return this.params.filter((param) => {
        return param.published && !param.sysadmin_only || this.sysadmin && param.sysadmin_only && param.published;
      });
    }
  }
};
const _hoisted_1$q = { key: 0 };
const _hoisted_2$q = { class: "form-group tw-mb-4" };
const _hoisted_3$p = { key: 0 };
const _hoisted_4$o = ["onUpdate:modelValue"];
const _hoisted_5$m = ["value"];
const _hoisted_6$k = ["onUpdate:modelValue"];
const _hoisted_7$k = ["type", "onUpdate:modelValue", "placeholder"];
const _hoisted_8$g = {
  key: 3,
  style: { "font-size": "small" }
};
const _hoisted_9$d = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$q(_ctx, _cache, $props, $setup, $data, $options) {
  return $props.params.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_1$q, [
    (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedParams, (param) => {
      return openBlock(), createElementBlock("div", _hoisted_2$q, [
        createBaseVNode("label", null, toDisplayString(_ctx.translate(param.label)), 1),
        param.type === "dropdown" ? (openBlock(), createElementBlock("div", _hoisted_3$p, [
          withDirectives(createBaseVNode("select", {
            "onUpdate:modelValue": ($event) => $props.section.params[param.name] = $event,
            class: "tw-w-full"
          }, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(param.options, (option) => {
              return openBlock(), createElementBlock("option", {
                value: option.value
              }, toDisplayString(_ctx.translate(option.label)), 9, _hoisted_5$m);
            }), 256))
          ], 8, _hoisted_4$o), [
            [vModelSelect, $props.section.params[param.name]]
          ])
        ])) : param.type === "textarea" ? withDirectives((openBlock(), createElementBlock("textarea", {
          key: 1,
          "onUpdate:modelValue": ($event) => $props.section.params[param.name] = $event,
          class: "tw-w-full"
        }, null, 8, _hoisted_6$k)), [
          [vModelText, $props.section.params[param.name]]
        ]) : withDirectives((openBlock(), createElementBlock("input", {
          key: 2,
          type: param.type,
          "onUpdate:modelValue": ($event) => $props.section.params[param.name] = $event,
          class: "tw-w-full",
          placeholder: _ctx.translate(param.placeholder)
        }, null, 8, _hoisted_7$k)), [
          [vModelDynamic, $props.section.params[param.name]]
        ]),
        param.helptext !== "" ? (openBlock(), createElementBlock("label", _hoisted_8$g, toDisplayString(_ctx.translate(param.helptext)), 1)) : createCommentVNode("", true)
      ]);
    }), 256)),
    _ctx.loading ? (openBlock(), createElementBlock("div", _hoisted_9$d)) : createCommentVNode("", true)
  ])) : createCommentVNode("", true);
}
const FormBuilderSectionParams = /* @__PURE__ */ _export_sfc(_sfc_main$q, [["render", _sfc_render$q]]);
const FormBuilderSectionProperties_vue_vue_type_style_index_0_lang = "";
const _sfc_main$p = {
  name: "FormBuilderSectionProperties",
  components: { FormBuilderSectionParams },
  props: {
    section_id: {
      type: Number,
      required: true
    },
    profile_id: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      section_tmp: {},
      params: [],
      tabs: [
        {
          id: 0,
          label: "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL",
          active: false,
          published: false
        },
        {
          id: 1,
          label: "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_PARAMETERS",
          active: true,
          published: true
        }
      ]
    };
  },
  setup() {
    return {
      globalStore: useGlobalStore()
    };
  },
  created() {
    this.paramsAvailable();
    this.getSection();
  },
  methods: {
    saveProperties() {
      formBuilderService.updateGroupParams(this.section_tmp.id, this.section_tmp.params, this.shortDefaultLang).then(() => {
        this.$emit("close");
      });
    },
    toggleHidden() {
      this.section_tmp.params.hidden = !this.section_tmp.hidden;
    },
    selectTab(tab) {
      this.tabs.forEach((t) => {
        t.active = false;
      });
      tab.active = true;
    },
    paramsAvailable() {
      if (typeof sectionParams["parameters"] !== "undefined") {
        this.tabs[1].published = true;
        this.params = sectionParams["parameters"];
      } else {
        this.tabs[1].active = false;
        this.tabs[0].active = true;
        this.tabs[1].published = false;
      }
    },
    getSection() {
      formBuilderService.getSection(this.$props.section_id).then((response) => {
        this.section_tmp = response.group;
      });
    }
  },
  computed: {
    sysadmin: function() {
      return parseInt(this.globalStore.sysadminAccess);
    },
    publishedTabs() {
      return this.tabs.filter((tab) => {
        return tab.published;
      });
    }
  },
  watch: {
    section: function() {
      this.paramsAvailable();
      this.getSection();
    }
  }
};
const _hoisted_1$p = { id: "form-builder-element-properties" };
const _hoisted_2$p = { class: "tw-flex tw-items-center tw-justify-between tw-p-4" };
const _hoisted_3$o = {
  id: "properties-tabs",
  class: "tw-flex tw-items-center tw-justify-between tw-p-4 tw-w-11/12"
};
const _hoisted_4$n = ["onClick"];
const _hoisted_5$l = { id: "properties" };
const _hoisted_6$j = {
  key: 0,
  id: "section-parameters",
  class: "tw-p-4"
};
const _hoisted_7$j = { for: "section-label" };
const _hoisted_8$f = {
  key: 1,
  class: "tw-p-4"
};
const _hoisted_9$c = { class: "tw-flex tw-items-center tw-justify-between actions tw-m-4" };
function _sfc_render$p(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_section_params = resolveComponent("form-builder-section-params");
  return openBlock(), createElementBlock("div", _hoisted_1$p, [
    createBaseVNode("div", _hoisted_2$p, [
      createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SECTION_PROPERTIES")), 1),
      createBaseVNode("span", {
        class: "material-symbols-outlined tw-cursor-pointer",
        onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
      }, "close")
    ]),
    createBaseVNode("ul", _hoisted_3$o, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.publishedTabs, (tab) => {
        return openBlock(), createElementBlock("li", {
          key: tab.id,
          class: normalizeClass([{ "is-active": tab.active, "tw-w-2/4": $options.publishedTabs.length == "2", "tw-w-full": $options.publishedTabs.length == 1 }, "tw-p-4 tw-cursor-pointer"]),
          onClick: ($event) => $options.selectTab(tab)
        }, toDisplayString(_ctx.translate(tab.label)), 11, _hoisted_4$n);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_5$l, [
      $data.tabs[0].active ? (openBlock(), createElementBlock("div", _hoisted_6$j, [
        createBaseVNode("label", _hoisted_7$j, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SECTION_LABEL")), 1),
        withDirectives(createBaseVNode("input", {
          id: "section-label",
          name: "section-label",
          class: "tw-w-full",
          type: "text",
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.section_tmp.label = $event)
        }, null, 512), [
          [vModelText, $data.section_tmp.label]
        ])
      ])) : createCommentVNode("", true),
      $data.tabs[1].active ? (openBlock(), createElementBlock("div", _hoisted_8$f, [
        createVNode(_component_form_builder_section_params, {
          params: $data.params,
          section: $data.section_tmp
        }, null, 8, ["params", "section"])
      ])) : createCommentVNode("", true)
    ]),
    createBaseVNode("div", _hoisted_9$c, [
      createBaseVNode("button", {
        class: "tw-btn-primary",
        onClick: _cache[2] || (_cache[2] = ($event) => $options.saveProperties())
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SECTION_PROPERTIES_SAVE")), 1)
    ])
  ]);
}
const FormBuilderSectionProperties = /* @__PURE__ */ _export_sfc(_sfc_main$p, [["render", _sfc_render$p]]);
const FormBuilderElementOptions_vue_vue_type_style_index_0_lang = "";
const _sfc_main$o = {
  props: {
    element: {
      type: Object,
      required: true
    },
    type: {
      type: String,
      required: true
    }
  },
  components: {
    draggable: VueDraggableNext
  },
  data() {
    return {
      loading: false,
      newOption: "",
      arraySubValues: [],
      optionsTranslations: [],
      optionHighlight: null
    };
  },
  created() {
    this.getSubOptionsTranslation();
  },
  methods: {
    async reloadOptions(new_option = false) {
      this.loading = true;
      formBuilderService.getElementSubOptions(this.element.id).then((response) => {
        if (response.status) {
          this.element.params.sub_options = response.new_options;
          this.getSubOptionsTranslation(new_option);
        } else {
          this.loading = false;
        }
      });
    },
    async getSubOptionsTranslation(new_option = false) {
      this.loading = true;
      formBuilderService.getJTEXTA(this.element.params.sub_options.sub_labels).then((response) => {
        if (response) {
          this.optionsTranslations = Object.values(response.data);
          this.arraySubValues = this.element.params.sub_options.sub_values.map((value, i) => {
            return {
              "sub_value": value,
              "sub_label": this.element.params.sub_options.sub_labels[i]
            };
          });
          setTimeout(() => {
            if (new_option) {
              document.getElementById("new-option-" + this.element.id).focus();
            }
          }, 200);
        }
        this.loading = false;
      });
    },
    addOption() {
      if (this.newOption.trim() == "") {
        return;
      }
      this.loading = true;
      formBuilderService.addOption(this.element.id, this.newOption, this.shortDefaultLang).then((response) => {
        this.newOption = "";
        if (response.status) {
          this.reloadOptions(true);
        }
        this.loading = false;
      });
    },
    updateOption(index, option, next = false) {
      this.loading = true;
      formBuilderService.updateOption(this.element.id, this.element.params.sub_options, index, option, this.shortDefaultLang).then((response) => {
        if (response.status) {
          this.reloadOptions().then(() => {
            if (next) {
              setTimeout(() => {
                if (!document.getElementById("option-" + this.element.id + "-" + (index + 1))) {
                  document.getElementById("new-option-" + this.element.id).focus();
                } else {
                  document.getElementById("option-" + this.element.id + "-" + (index + 1)).focus();
                }
              }, 300);
            }
          });
        } else {
          this.loading = false;
        }
      });
    },
    updateOrder() {
      if (this.arraySubValues.length > 1) {
        let sub_options_in_new_order = {
          sub_values: [],
          sub_labels: []
        };
        this.arraySubValues.forEach((value, i) => {
          sub_options_in_new_order.sub_values.push(value.sub_value);
          sub_options_in_new_order.sub_labels.push(value.sub_label);
        });
        if (!this.element.params.sub_options.sub_values.every((value, index) => value === sub_options_in_new_order.sub_values[index])) {
          this.loading = true;
          formBuilderService.updateElementSubOptionsOrder(this.element.id, this.element.params.sub_options, sub_options_in_new_order).then((response) => {
            if (response.status) {
              this.reloadOptions();
            } else {
              this.loading = false;
            }
          });
        } else {
          console.log("No need to call reorder, same order");
        }
      } else {
        console.log("No need to reorder, only one element");
      }
    },
    removeOption(index) {
      this.loading = true;
      formBuilderService.deleteElementSubOption(this.element.id, index).then((response) => {
        if (response.status) {
          this.reloadOptions();
        } else {
          this.loading = false;
        }
      });
    }
  }
};
const _hoisted_1$o = { id: "form-builder-radio-button" };
const _hoisted_2$o = {
  key: 0,
  class: "em-loader"
};
const _hoisted_3$n = { key: 1 };
const _hoisted_4$m = ["onMouseover"];
const _hoisted_5$k = { class: "tw-flex tw-items-center tw-w-full" };
const _hoisted_6$i = { class: "tw-flex tw-items-center" };
const _hoisted_7$i = ["type", "name", "value"];
const _hoisted_8$e = { key: 1 };
const _hoisted_9$b = ["id", "onUpdate:modelValue", "onFocusout", "onKeyup", "placeholder"];
const _hoisted_10$8 = { class: "tw-flex tw-items-center" };
const _hoisted_11$5 = ["onClick"];
const _hoisted_12$5 = {
  id: "add-option",
  class: "tw-flex tw-items-center lg:tw-justify-start md:tw-justify-center"
};
const _hoisted_13$5 = ["type", "name"];
const _hoisted_14$4 = { key: 1 };
const _hoisted_15$4 = ["id", "placeholder"];
function _sfc_render$o(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$o, [
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_2$o)) : (openBlock(), createElementBlock("div", _hoisted_3$n, [
      createVNode(_component_draggable, {
        modelValue: $data.arraySubValues,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.arraySubValues = $event),
        handle: ".handle-options",
        onEnd: $options.updateOrder
      }, {
        default: withCtx(() => [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.arraySubValues, (option, index) => {
            return openBlock(), createElementBlock("div", {
              class: "element-option tw-flex tw-items-center tw-justify-between tw-mt-2 tw-mb-2",
              key: option,
              onMouseover: ($event) => {
                $data.optionHighlight = index;
              },
              onMouseleave: _cache[1] || (_cache[1] = ($event) => $data.optionHighlight = null)
            }, [
              createBaseVNode("div", _hoisted_5$k, [
                createBaseVNode("div", _hoisted_6$i, [
                  createBaseVNode("span", {
                    class: "icon-handle",
                    style: normalizeStyle($data.optionHighlight === index ? "opacity: 1" : "opacity: 0")
                  }, _cache[6] || (_cache[6] = [
                    createBaseVNode("span", {
                      class: "material-symbols-outlined handle-options tw-cursor-grab",
                      style: { "font-size": "18px" }
                    }, "drag_indicator", -1)
                  ]), 4)
                ]),
                $props.type !== "dropdown" ? (openBlock(), createElementBlock("input", {
                  key: 0,
                  type: $props.type,
                  name: "element-id-" + $props.element.id,
                  value: $data.optionsTranslations[index]
                }, null, 8, _hoisted_7$i)) : (openBlock(), createElementBlock("div", _hoisted_8$e, toDisplayString(index + 1) + ".", 1)),
                withDirectives(createBaseVNode("input", {
                  type: "text",
                  class: "editable-data editable-data-input tw-ml-1 tw-w-full",
                  id: "option-" + $props.element.id + "-" + index,
                  "onUpdate:modelValue": ($event) => $data.optionsTranslations[index] = $event,
                  onFocusout: ($event) => $options.updateOption(index, $data.optionsTranslations[index]),
                  onKeyup: [
                    withKeys(($event) => $options.updateOption(index, $data.optionsTranslations[index], true), ["enter"]),
                    _cache[0] || (_cache[0] = withKeys(($event) => {
                      _ctx.document.getElementById("new-option-" + $props.element.id).focus();
                    }, ["tab"]))
                  ],
                  placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_ADD_OPTION")
                }, null, 40, _hoisted_9$b), [
                  [vModelText, $data.optionsTranslations[index]]
                ])
              ]),
              createBaseVNode("div", _hoisted_10$8, [
                createBaseVNode("span", {
                  class: "material-symbols-outlined tw-cursor-pointer",
                  onClick: ($event) => $options.removeOption(index),
                  style: normalizeStyle($data.optionHighlight === index ? "opacity: 1" : "opacity: 0")
                }, "close", 12, _hoisted_11$5)
              ])
            ], 40, _hoisted_4$m);
          }), 128))
        ]),
        _: 1
      }, 8, ["modelValue", "onEnd"]),
      createBaseVNode("div", _hoisted_12$5, [
        _cache[7] || (_cache[7] = createBaseVNode("span", {
          class: "icon-handle",
          style: { "opacity": "0" }
        }, [
          createBaseVNode("span", {
            class: "material-symbols-outlined handle-options",
            style: { "font-size": "18px" }
          }, "drag_indicator")
        ], -1)),
        $props.type !== "dropdown" ? (openBlock(), createElementBlock("input", {
          key: 0,
          type: $props.type,
          name: "element-id-" + $props.element.id
        }, null, 8, _hoisted_13$5)) : (openBlock(), createElementBlock("div", _hoisted_14$4, toDisplayString($props.element.params.sub_options.sub_labels.length + 1) + ".", 1)),
        withDirectives(createBaseVNode("input", {
          type: "text",
          class: "editable-data editable-data-input tw-ml-1 tw-w-full",
          id: "new-option-" + $props.element.id,
          "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.newOption = $event),
          onFocusout: _cache[4] || (_cache[4] = (...args) => $options.addOption && $options.addOption(...args)),
          onKeyup: _cache[5] || (_cache[5] = withKeys((...args) => $options.addOption && $options.addOption(...args), ["enter"])),
          placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_ADD_OPTION")
        }, null, 40, _hoisted_15$4), [
          [vModelText, $data.newOption]
        ])
      ])
    ]))
  ]);
}
const FormBuilderElementOptions = /* @__PURE__ */ _export_sfc(_sfc_main$o, [["render", _sfc_render$o]]);
const _sfc_main$n = {
  props: {
    element: {
      type: Object,
      required: true
    },
    type: {
      type: String,
      required: true
    }
  },
  components: {
    TipTapEditor: V32
  },
  data() {
    return {
      loading: false,
      editable: false,
      dynamicComponent: 0,
      editorPlugins: ["history", "link", "bold", "italic", "underline", "left", "center", "right", "h1", "h2", "ul"]
    };
  },
  created() {
  },
  methods: {
    updateDisplayText(value) {
      this.editable = false;
      formBuilderService.updateDefaultValue(this.$props.element.id, value).then((response) => {
        this.$emit("update-element");
      });
    }
  },
  watch: {}
};
const _hoisted_1$n = { id: "form-builder-wysiwig" };
const _hoisted_2$n = {
  key: 0,
  class: "em-loader"
};
const _hoisted_3$m = { key: 1 };
const _hoisted_4$l = ["innerHTML", "id"];
function _sfc_render$n(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_tip_tap_editor = resolveComponent("tip-tap-editor");
  return openBlock(), createElementBlock("div", _hoisted_1$n, [
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_2$n)) : (openBlock(), createElementBlock("div", _hoisted_3$m, [
      withDirectives(createBaseVNode("div", {
        innerHTML: $props.element.element,
        id: $props.element.id,
        onClick: _cache[0] || (_cache[0] = ($event) => $data.editable = true)
      }, null, 8, _hoisted_4$l), [
        [vShow, !$data.editable]
      ]),
      createVNode(Transition, {
        name: "slide-down",
        type: "transition"
      }, {
        default: withCtx(() => [
          createVNode(_component_tip_tap_editor, {
            id: "editor_" + $props.element.id,
            modelValue: $props.element.default,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $props.element.default = $event),
            "editor-content-height": "30em",
            class: normalizeClass("tw-mt-1"),
            locale: "fr",
            preset: "custom",
            plugins: $data.editorPlugins,
            "toolbar-classes": ["tw-bg-white"],
            "editor-content-classes": ["tw-bg-white"]
          }, null, 8, ["id", "modelValue", "plugins"])
        ]),
        _: 1
      })
    ]))
  ]);
}
const FormBuilderElementWysiwig = /* @__PURE__ */ _export_sfc(_sfc_main$n, [["render", _sfc_render$n]]);
const _imports_0 = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAPoAAACnBAMAAADK0lwnAAAAIVBMVEU/QWP70tPsGSCbh5n4qq3sGSDsGSD///+CiZ/6xsgFFEBjSht+AAAABnRSTlP778D15DBi/uc8AAAAeElEQVR4Xu3NQQ3AIAAEsCVTgAUSjCAGCzhBA0ElFu7JozXQ76T2TK2RenS32+12u91ut9vtdrvdbrfb7Xa73W632+12u91ut9vtdrvdbrfb7Xa73W632+12u91ut9vtdrvdbrfb7Xa73W632+12u91uj/0l1WuoXSQpdCmx7sXHAAAAAElFTkSuQmCC";
const FormBuilderElementPhoneNumber_vue_vue_type_style_index_0_lang = "";
const _sfc_main$m = {
  props: {
    element: {
      type: Object,
      required: true
    },
    type: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      editable: false,
      dynamicComponent: 0
    };
  },
  created() {
  },
  methods: {},
  watch: {}
};
const _hoisted_1$m = { id: "form-builder-phone-number" };
const _hoisted_2$m = {
  key: 0,
  class: "em-loader"
};
const _hoisted_3$l = { key: 1 };
const _hoisted_4$k = { class: "tw-flex tw-items-center" };
const _hoisted_5$j = { class: "tw-h-10 country-select tw-flex tw-items-center tw-p-2 tw-justify-center" };
const _hoisted_6$h = ["src"];
const _hoisted_7$h = {
  key: 1,
  src: _imports_0
};
function _sfc_render$m(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$m, [
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_2$m)) : (openBlock(), createElementBlock("div", _hoisted_3$l, [
      createBaseVNode("div", _hoisted_4$k, [
        createBaseVNode("div", _hoisted_5$j, [
          $props.element.params.default_country ? (openBlock(), createElementBlock("img", {
            key: 0,
            src: "../../../../../../images/emundus/flags/" + $props.element.params.default_country.toLowerCase() + ".png"
          }, null, 8, _hoisted_6$h)) : (openBlock(), createElementBlock("img", _hoisted_7$h))
        ]),
        _cache[0] || (_cache[0] = createBaseVNode("div", { class: "tw-w-full" }, [
          createBaseVNode("input", {
            class: "phonenumber",
            readonly: "",
            type: "text",
            value: "+33 6 12 34 56 78"
          })
        ], -1))
      ])
    ]))
  ]);
}
const FormBuilderElementPhoneNumber = /* @__PURE__ */ _export_sfc(_sfc_main$m, [["render", _sfc_render$m]]);
const FormBuilderElementCurrency_vue_vue_type_style_index_0_lang = "";
const _sfc_main$l = {
  props: {
    element: {
      type: Object,
      required: true
    },
    type: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      editable: false,
      dynamicComponent: 0
    };
  },
  created() {
  },
  methods: {},
  watch: {},
  computed: {
    currencyIcon() {
      switch (this.element.params["all_currencies_options"]["all_currencies_options0"].iso3) {
        case "USD":
          return "$";
        case "EUR":
          return "€";
        case "GBP":
          return "£";
        case "JPY":
          return "¥";
        default:
          return "€";
      }
    }
  }
};
const _hoisted_1$l = { id: "form-builder-currency" };
const _hoisted_2$l = {
  key: 0,
  class: "em-loader"
};
const _hoisted_3$k = {
  key: 1,
  class: "tw-w-full tw-relative tw-flex tw-items-center currency-block"
};
const _hoisted_4$j = ["value"];
const _hoisted_5$i = { class: "currency-icon" };
function _sfc_render$l(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$l, [
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_2$l)) : (openBlock(), createElementBlock("div", _hoisted_3$k, [
      createBaseVNode("input", {
        class: "currency",
        readonly: "",
        type: "text",
        value: this.element.params["all_currencies_options"]["all_currencies_options0"].minimal_value
      }, null, 8, _hoisted_4$j),
      createBaseVNode("span", _hoisted_5$i, toDisplayString($options.currencyIcon), 1)
    ]))
  ]);
}
const FormBuilderElementCurrency = /* @__PURE__ */ _export_sfc(_sfc_main$l, [["render", _sfc_render$l]]);
const FormBuilderElementGeolocation_vue_vue_type_style_index_0_lang = "";
const _sfc_main$k = {
  props: {
    element: {
      type: Object,
      required: true
    },
    type: {
      type: String,
      required: true
    }
  },
  data() {
    return {
      loading: false,
      mapContainer: null
    };
  },
  mounted() {
    if (typeof L !== "undefined" && L !== null) {
      this.mapContainer = L.map("map_container_" + this.$props.element.id).setView(
        ["48.85341", "2.3488"],
        13
      );
      L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
        maxZoom: 19,
        attribution: "© OpenStreetMap"
      }).addTo(this.mapContainer);
      this.mapContainer.dragging.disable();
      this.mapContainer.touchZoom.disable();
      this.mapContainer.doubleClickZoom.disable();
      this.mapContainer.scrollWheelZoom.disable();
      this.mapContainer.boxZoom.disable();
      this.mapContainer.keyboard.disable();
      if (this.mapContainer.tap)
        this.mapContainer.tap.disable();
    }
  },
  methods: {},
  watch: {}
};
const _hoisted_1$k = { id: "form-builder-geolocation" };
const _hoisted_2$k = {
  key: 0,
  class: "em-loader"
};
const _hoisted_3$j = ["id"];
function _sfc_render$k(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$k, [
    _cache[0] || (_cache[0] = createBaseVNode("link", {
      rel: "stylesheet",
      href: "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css",
      integrity: "sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=",
      crossorigin: ""
    }, null, -1)),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_2$k)) : (openBlock(), createElementBlock("div", {
      key: 1,
      id: "map_container_" + $props.element.id,
      class: "fabrikSubElementContainer fabrikEmundusGeolocalisation"
    }, null, 8, _hoisted_3$j))
  ]);
}
const FormBuilderElementGeolocation = /* @__PURE__ */ _export_sfc(_sfc_main$k, [["render", _sfc_render$k]]);
const FormBuilderPageSectionElement_vue_vue_type_style_index_0_lang = "";
const _sfc_main$j = {
  components: {
    FormBuilderElementGeolocation,
    FormBuilderElementCurrency,
    FormBuilderElementPhoneNumber,
    FormBuilderElementWysiwig,
    FormBuilderElementOptions
  },
  props: {
    element: {
      type: Object,
      default: {}
    }
  },
  mixins: [formBuilderMixin, mixin],
  data() {
    return {
      keysPressed: [],
      options_enabled: false
    };
  },
  setup() {
    return {
      globalStore: useGlobalStore()
    };
  },
  methods: {
    updateLabel() {
      this.element.label[this.shortDefaultLang] = this.$refs["element-label-" + this.element.id].value.trim().replace(/[\r\n]/gm, "");
      formBuilderService.updateTranslation({
        value: this.element.id,
        key: "element"
      }, this.element.label_tag, this.element.label).then((response) => {
        if (response.data.status) {
          this.element.label_tag = response.data.data;
          this.updateLastSave();
        } else {
          Swal.fire({
            title: this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"),
            text: this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR_SAVE_TRANSLATION"),
            icon: "error",
            cancelButtonText: this.translate("OK")
          });
        }
      });
    },
    updateLabelKeyup() {
      document.activeElement.blur();
    },
    updateElement() {
      formBuilderService.updateParams(this.element).then((response) => {
        if (response.data.status) {
          this.$emit("update-element");
          this.updateLastSave();
        } else {
          Swal.fire({
            title: this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"),
            text: this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR_UPDATE_PARAMS"),
            icon: "error",
            cancelButtonText: this.translate("OK")
          });
        }
      });
    },
    deleteElement() {
      this.swalConfirm(
        this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_ELEMENT"),
        this.element.label[this.shortDefaultLang],
        this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_ELEMENT_CONFIRM"),
        this.translate("JNO"),
        () => {
          formBuilderService.deleteElement(this.element.id);
          this.$emit("delete-element", this.element.id);
          this.updateLastSave();
          this.tipToast(this.translate("COM_EMUNDUS_FORM_BUILDER_DELETED_ELEMENT_TEXT"));
          window.addEventListener("keydown", this.cancelDelete);
        }
      );
    },
    openAdmin() {
      navigator.clipboard.writeText(this.element.id);
      Swal.fire({
        title: "Identifiant de l'élément copié",
        icon: "success",
        showCancelButton: false,
        showConfirmButton: false,
        customClass: {
          title: "em-swal-title"
        },
        timer: 1500
      });
    },
    triggerElementProperties() {
      this.$emit("open-element-properties");
    },
    cancelDelete(event) {
      let elementsPending = this.$parent.$parent.$parent.$parent.$data.elementsDeletedPending;
      let index = elementsPending.indexOf(this.element.id);
      if (elementsPending.indexOf(this.element.id) === elementsPending.length - 1) {
        event.stopImmediatePropagation();
        this.keysPressed[event.key] = true;
        if ((this.keysPressed["Control"] || this.keysPressed["Meta"]) && event.key === "z") {
          formBuilderService.toggleElementPublishValue(this.element.id);
          this.$emit("cancel-delete-element", this.element.id);
          this.keysPressed = [];
          document.removeEventListener("keydown", this.cancelDelete);
          this.$parent.$parent.$parent.$parent.$data.elementsDeletedPending.splice(index, 1);
        }
      }
    }
  },
  computed: {
    sysadmin: function() {
      return parseInt(this.globalStore.hasSysadminAccess);
    },
    displayOptions: function() {
      return this.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$data.selectedElement !== null && this.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$data.selectedElement.id == this.element.id;
    },
    propertiesOpened: function() {
      if (this.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$data.selectedElement !== null) {
        return this.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$parent.$data.selectedElement.id;
      } else {
        return 0;
      }
    }
  }
};
const _hoisted_1$j = ["id"];
const _hoisted_2$j = { class: "tw-flex tw-items-start tw-justify-between tw-w-full tw-mb-2" };
const _hoisted_3$i = { class: "tw-w-11/12" };
const _hoisted_4$i = {
  key: 0,
  class: "material-icons !tw-text-xs tw-text-red-600 tw-mr-0",
  style: { "top": "-5px", "position": "relative" }
};
const _hoisted_5$h = ["id", "name", "placeholder"];
const _hoisted_6$g = { class: "fabrikElementTip fabrikElementTipAbove" };
const _hoisted_7$g = {
  id: "element-action-icons",
  class: "tw-flex tw-items-end tw-mt-2"
};
const _hoisted_8$d = ["innerHTML"];
function _sfc_render$j(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_element_options = resolveComponent("form-builder-element-options");
  const _component_form_builder_element_wysiwig = resolveComponent("form-builder-element-wysiwig");
  const _component_form_builder_element_phone_number = resolveComponent("form-builder-element-phone-number");
  const _component_form_builder_element_currency = resolveComponent("form-builder-element-currency");
  const _component_form_builder_element_geolocation = resolveComponent("form-builder-element-geolocation");
  return withDirectives((openBlock(), createElementBlock("div", {
    class: normalizeClass(["form-builder-page-section-element", { "unpublished": !$props.element.publish || $props.element.hidden, "properties-active": $options.propertiesOpened === $props.element.id }]),
    id: "element_" + $props.element.id
  }, [
    createBaseVNode("div", _hoisted_2$j, [
      createBaseVNode("div", _hoisted_3$i, [
        createBaseVNode("label", {
          class: "tw-w-full tw-flex tw-items-center fabrikLabel control-label tw-mb-0",
          onClick: _cache[3] || (_cache[3] = (...args) => $options.triggerElementProperties && $options.triggerElementProperties(...args))
        }, [
          $props.element.FRequire ? (openBlock(), createElementBlock("span", _hoisted_4$i, "emergency")) : createCommentVNode("", true),
          $props.element.label_value && $props.element.labelsAbove != 2 ? withDirectives((openBlock(), createElementBlock("input", {
            key: 1,
            ref: "element-label-" + $props.element.id,
            id: "element-label-" + $props.element.id,
            class: "tw-ml-2 element-title editable-data",
            name: "element-label-" + $props.element.id,
            type: "text",
            "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $props.element.label[_ctx.shortDefaultLang] = $event),
            placeholder: _ctx.translate("COM_EMUNDUS_ONBOARD_TYPE_" + $props.element.plugin.toUpperCase()),
            onFocusout: _cache[1] || (_cache[1] = (...args) => $options.updateLabel && $options.updateLabel(...args)),
            onKeyup: _cache[2] || (_cache[2] = withKeys((...args) => $options.updateLabelKeyup && $options.updateLabelKeyup(...args), ["enter"]))
          }, null, 40, _hoisted_5$h)), [
            [vModelText, $props.element.label[_ctx.shortDefaultLang]]
          ]) : createCommentVNode("", true)
        ]),
        createBaseVNode("span", _hoisted_6$g, toDisplayString($props.element.params.rollover.replace(/(<([^>]+)>)/gi, "")), 1)
      ]),
      createBaseVNode("div", _hoisted_7$g, [
        _cache[9] || (_cache[9] = createBaseVNode("span", { class: "material-symbols-outlined handle tw-cursor-grab" }, "drag_indicator", -1)),
        createBaseVNode("span", {
          id: "delete-element",
          class: "material-symbols-outlined tw-text-red-600 tw-cursor-pointer",
          onClick: _cache[4] || (_cache[4] = (...args) => $options.deleteElement && $options.deleteElement(...args))
        }, "delete"),
        $options.sysadmin ? (openBlock(), createElementBlock("span", {
          key: 0,
          class: "material-symbols-outlined tw-cursor-pointer tw-ml-2",
          onClick: _cache[5] || (_cache[5] = (...args) => $options.openAdmin && $options.openAdmin(...args))
        }, "content_copy")) : createCommentVNode("", true)
      ])
    ]),
    createBaseVNode("div", {
      class: normalizeClass("element-field fabrikElement" + $props.element.plugin),
      onClick: _cache[8] || (_cache[8] = (...args) => $options.triggerElementProperties && $options.triggerElementProperties(...args))
    }, [
      ["radiobutton", "checkbox"].includes($props.element.plugin) || $options.displayOptions && $props.element.plugin === "dropdown" ? (openBlock(), createBlock(_component_form_builder_element_options, {
        key: 0,
        element: $props.element,
        type: $props.element.plugin == "radiobutton" ? "radio" : $props.element.plugin,
        onUpdateElement: _cache[6] || (_cache[6] = ($event) => _ctx.$emit("update-element"))
      }, null, 8, ["element", "type"])) : $props.element.plugin === "display" ? (openBlock(), createBlock(_component_form_builder_element_wysiwig, {
        key: 1,
        element: $props.element,
        type: "display",
        onUpdateElement: _cache[7] || (_cache[7] = ($event) => _ctx.$emit("update-element"))
      }, null, 8, ["element"])) : $props.element.plugin === "emundus_phonenumber" ? (openBlock(), createBlock(_component_form_builder_element_phone_number, {
        key: 2,
        type: "phonenumber",
        element: $props.element
      }, null, 8, ["element"])) : $props.element.plugin === "currency" ? (openBlock(), createBlock(_component_form_builder_element_currency, {
        key: 3,
        type: "currency",
        element: $props.element
      }, null, 8, ["element"])) : $props.element.plugin === "emundus_geolocalisation" ? (openBlock(), createBlock(_component_form_builder_element_geolocation, {
        key: 4,
        type: "geolocation",
        element: $props.element
      }, null, 8, ["element"])) : (openBlock(), createElementBlock("div", {
        key: 5,
        innerHTML: $props.element.element,
        class: "fabrikElement"
      }, null, 8, _hoisted_8$d))
    ], 2)
  ], 10, _hoisted_1$j)), [
    [vShow, !$props.element.hidden && $props.element.publish !== -2 || $props.element.hidden && $options.sysadmin]
  ]);
}
const FormBuilderPageSectionElement = /* @__PURE__ */ _export_sfc(_sfc_main$j, [["render", _sfc_render$j]]);
const FormBuilderPageSection_vue_vue_type_style_index_0_lang = "";
const _sfc_main$i = {
  components: {
    FormBuilderPageSectionElement,
    draggable: VueDraggableNext
  },
  props: {
    profile_id: {
      type: Number,
      required: true
    },
    page_id: {
      type: Number,
      required: true
    },
    section: {
      type: Object,
      required: true
    },
    index: {
      type: Number,
      default: 0
    },
    totalSections: {
      type: Number,
      default: 0
    }
  },
  mixins: [formBuilderMixin, mixin],
  data() {
    return {
      closedSection: false,
      elements: [],
      emptySection: [
        {
          "text": "COM_EMUNDUS_FORM_BUILDER_EMPTY_SECTION"
        }
      ],
      elementsDeletedPending: []
    };
  },
  setup() {
    return {
      globalStore: useGlobalStore()
    };
  },
  created() {
    this.getElements();
  },
  methods: {
    getElements() {
      this.elements = Object.values(this.section.elements).length > 0 ? Object.values(this.section.elements) : [];
    },
    updateTitle() {
      this.section.label[this.shortDefaultLang] = this.section.label[this.shortDefaultLang].trim();
      formBuilderService.updateTranslation({
        value: this.section.group_id,
        key: "group"
      }, this.section.group_tag, this.section.label).then((response) => {
        if (response.data.status) {
          this.section.group_tag = response.data.data;
          this.updateLastSave();
        } else {
          Swal.fire({
            title: this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"),
            text: this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR_SAVE_TRANSLATION"),
            icon: "error",
            cancelButtonText: this.translate("OK")
          });
        }
      });
    },
    blurElement(selector) {
      document.querySelector(selector).blur();
    },
    updateIntro() {
      this.$refs.sectionIntro.innerHTML = this.$refs.sectionIntro.innerHTML.trim().replace(/[\r\n]/gm, "<br/>");
      this.section.group_intro = this.$refs.sectionIntro.innerHTML;
      formBuilderService.updateGroupParams(this.section.group_id, { "intro": this.section.group_intro }, this.shortDefaultLang).then((response) => {
        if (response.status) {
          this.updateLastSave();
        } else {
          Swal.fire({
            title: this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"),
            text: this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR_UPDATE_GROUP_PARAMS"),
            icon: "error",
            cancelButtonText: this.translate("OK")
          });
        }
      });
    },
    onDragEnd(e) {
      const toGroup = e.to.getAttribute("data-sid");
      if (toGroup == this.section.group_id) {
        const elements = this.elements.map((element, index) => {
          return { id: element.id, order: index + 1 };
        });
        const movedElement = this.elements[e.newIndex];
        formBuilderService.updateOrder(elements, this.section.group_id, movedElement).then((response) => {
          this.updateLastSave();
          let obj = {};
          this.elements.forEach((elem) => {
            obj["element" + elem.id] = elem;
          });
          this.section.elements = obj;
        });
      } else {
        this.$emit("move-element", e, this.section.group_id, toGroup);
      }
    },
    deleteElement(elementId) {
      this.section.elements["element" + elementId].publish = -2;
      this.elementsDeletedPending.push(elementId);
      this.getElements();
      this.updateLastSave();
    },
    cancelDeleteElement(elementId) {
      this.section.elements["element" + elementId].publish = true;
      this.getElements();
    },
    deleteSection() {
      this.swalConfirm(
        this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_SECTION"),
        this.section.label[this.shortDefaultLang],
        this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_SECTION_CONFIRM"),
        this.translate("JNO"),
        () => {
          formBuilderService.deleteGroup(this.section.group_id);
          this.$emit("delete-section", this.section.group_id);
          this.updateLastSave();
        }
      );
    },
    moveSection(direction = "up") {
      this.$emit("move-section", this.section.group_id, direction);
    }
  },
  watch: {
    section: {
      handler() {
        this.getElements();
      },
      deep: true
    }
  },
  computed: {
    publishedElements() {
      return this.elements && this.elements.length > 0 ? this.elements.filter((element) => {
        return element.publish === true && (element.hidden === false || this.sysadmin);
      }) : [];
    },
    sysadmin: function() {
      return parseInt(this.globalStore.hasSysadminAccess);
    }
  }
};
const _hoisted_1$i = ["id"];
const _hoisted_2$i = { class: "section-card tw-flex tw-flex-col" };
const _hoisted_3$h = { class: "material-icons tw-mr-2 tw-text-white" };
const _hoisted_4$h = { class: "material-icons tw-ml-2 tw-text-white" };
const _hoisted_5$g = { class: "material-icons tw-ml-2 tw-text-white" };
const _hoisted_6$f = { class: "tw-flex tw-items-center tw-justify-between tw-w-full" };
const _hoisted_7$f = ["placeholder"];
const _hoisted_8$c = { class: "section-actions-wrapper" };
const _hoisted_9$a = ["innerHTML"];
const _hoisted_10$7 = {
  key: 0,
  class: "empty-section-element"
};
function _sfc_render$i(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_page_section_element = resolveComponent("form-builder-page-section-element");
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", {
    id: "form-builder-page-section-" + $props.section.group_id,
    class: "form-builder-page-section tw-mt-8 tw-mb-8"
  }, [
    createBaseVNode("div", _hoisted_2$i, [
      createBaseVNode("div", {
        class: "section-identifier tw-bg-profile-full tw-cursor-pointer tw-flex tw-items-center",
        onClick: _cache[0] || (_cache[0] = ($event) => $data.closedSection = !$data.closedSection)
      }, [
        withDirectives(createBaseVNode("span", _hoisted_3$h, "library_add", 512), [
          [vShow, $props.section.repeat_group]
        ]),
        createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SECTION")) + " " + toDisplayString($props.index) + " / " + toDisplayString($props.totalSections) + " ", 1),
        withDirectives(createBaseVNode("span", _hoisted_4$h, "unfold_less", 512), [
          [vShow, !$data.closedSection]
        ]),
        withDirectives(createBaseVNode("span", _hoisted_5$g, "unfold_more", 512), [
          [vShow, $data.closedSection]
        ])
      ]),
      createBaseVNode("div", {
        class: normalizeClass(["section-content tw-w-full em-p-32", { "closed": $data.closedSection }])
      }, [
        createBaseVNode("div", _hoisted_6$f, [
          withDirectives(createBaseVNode("input", {
            id: "section-title",
            class: "editable-data tw-w-full",
            placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_ADD_PAGE_TITLE_ADD"),
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $props.section.label[_ctx.shortDefaultLang] = $event),
            onFocusout: _cache[2] || (_cache[2] = (...args) => $options.updateTitle && $options.updateTitle(...args)),
            onKeyup: _cache[3] || (_cache[3] = withKeys(($event) => $options.blurElement("#section-title"), ["enter"])),
            maxlength: "100"
          }, null, 40, _hoisted_7$f), [
            [vModelText, $props.section.label[_ctx.shortDefaultLang]]
          ]),
          createBaseVNode("div", _hoisted_8$c, [
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-cursor-pointer hover-opacity",
              onClick: _cache[4] || (_cache[4] = ($event) => $options.moveSection("up")),
              title: "Move section upwards"
            }, "keyboard_double_arrow_up"),
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-cursor-pointer hover-opacity",
              onClick: _cache[5] || (_cache[5] = ($event) => $options.moveSection("down")),
              title: "Move section downwards"
            }, "keyboard_double_arrow_down"),
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-text-red-600 tw-cursor-pointer delete hover-opacity",
              onClick: _cache[6] || (_cache[6] = (...args) => $options.deleteSection && $options.deleteSection(...args))
            }, "delete"),
            createBaseVNode("span", {
              class: "material-symbols-outlined tw-cursor-pointer hover-opacity",
              onClick: _cache[7] || (_cache[7] = ($event) => _ctx.$emit("open-section-properties"))
            }, "settings")
          ])
        ]),
        createVNode(Transition, { name: "slide-down" }, {
          default: withCtx(() => [
            withDirectives(createBaseVNode("div", null, [
              createBaseVNode("span", {
                id: "section-intro",
                class: "editable-data description",
                ref: "sectionIntro",
                contenteditable: "true",
                onFocusout: _cache[8] || (_cache[8] = (...args) => $options.updateIntro && $options.updateIntro(...args)),
                innerHTML: $props.section.group_intro
              }, null, 40, _hoisted_9$a),
              createVNode(_component_draggable, {
                modelValue: $data.elements,
                "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => $data.elements = $event),
                group: "form-builder-section-elements",
                sort: true,
                class: "draggables-list",
                onEnd: $options.onDragEnd,
                handle: ".handle",
                "data-prid": $props.profile_id,
                "data-page": $props.page_id,
                "data-sid": $props.section.group_id
              }, {
                default: withCtx(() => [
                  createVNode(TransitionGroup, null, {
                    default: withCtx(() => [
                      (openBlock(true), createElementBlock(Fragment, null, renderList($data.elements, (element) => {
                        return openBlock(), createBlock(_component_form_builder_page_section_element, {
                          key: element.id,
                          element,
                          onOpenElementProperties: ($event) => _ctx.$emit("open-element-properties", element),
                          onDeleteElement: $options.deleteElement,
                          onCancelDeleteElement: $options.cancelDeleteElement,
                          onUpdateElement: _cache[9] || (_cache[9] = ($event) => _ctx.$emit("update-element"))
                        }, null, 8, ["element", "onOpenElementProperties", "onDeleteElement", "onCancelDeleteElement"]);
                      }), 128))
                    ]),
                    _: 1
                  })
                ]),
                _: 1
              }, 8, ["modelValue", "onEnd", "data-prid", "data-page", "data-sid"]),
              $options.publishedElements.length < 1 ? (openBlock(), createElementBlock("div", _hoisted_10$7, [
                createVNode(_component_draggable, {
                  list: $data.emptySection,
                  group: "form-builder-section-elements",
                  sort: false,
                  class: "draggables-list",
                  "data-prid": $props.profile_id,
                  "data-page": $props.page_id,
                  "data-sid": $props.section.group_id
                }, {
                  default: withCtx(() => [
                    createVNode(TransitionGroup, {
                      "data-prid": $props.profile_id,
                      "data-page": $props.page_id,
                      "data-sid": $props.section.group_id
                    }, {
                      default: withCtx(() => [
                        (openBlock(true), createElementBlock(Fragment, null, renderList($data.emptySection, (item, index) => {
                          return openBlock(), createElementBlock("p", {
                            class: "tw-w-full tw-text-center",
                            key: index
                          }, toDisplayString(_ctx.translate(item.text)), 1);
                        }), 128))
                      ]),
                      _: 1
                    }, 8, ["data-prid", "data-page", "data-sid"])
                  ]),
                  _: 1
                }, 8, ["list", "data-prid", "data-page", "data-sid"])
              ])) : createCommentVNode("", true)
            ], 512), [
              [vShow, !$data.closedSection]
            ])
          ]),
          _: 1
        })
      ], 2)
    ])
  ], 8, _hoisted_1$i);
}
const FormBuilderPageSection = /* @__PURE__ */ _export_sfc(_sfc_main$i, [["render", _sfc_render$i]]);
const FormBuilderPage_vue_vue_type_style_index_0_lang = "";
const _sfc_main$h = {
  components: {
    FormBuilderPageSection
  },
  props: {
    profile_id: {
      type: Number,
      default: 0
    },
    page: {
      type: Object,
      default: () => {
      }
    },
    mode: {
      type: String,
      default: "forms"
    }
  },
  mixins: [formBuilderMixin, mixin, errors],
  data() {
    return {
      fabrikPage: {},
      title: "COM_EMUNDUS_FORM_BUILDER_NEW_PAGE",
      description: "",
      sections: [],
      loading: false
    };
  },
  mounted() {
    if (this.page.id) {
      this.title = this.page.label;
      this.getSections();
    }
  },
  methods: {
    getSections(eltid = null, scrollTo = false) {
      this.loading = true;
      formService.getPageObject(this.page.id).then((response) => {
        if (response.status && response.data !== "") {
          this.fabrikPage = response.data;
          this.title = this.fabrikPage.show_title.label[this.shortDefaultLang];
          const groups = Object.values(response.data.Groups);
          this.sections = groups.filter((group) => group.hidden_group != -1);
          this.getDescription();
          if (eltid) {
            setTimeout(() => {
              if (scrollTo) {
                document.getElementById("center_content").scrollTo(0, document.getElementById("center_content").scrollHeight);
              }
              document.getElementById("element_" + eltid).style.backgroundColor = "var(--main-50)";
              document.getElementById("element_" + eltid).style.borderColor = "var(--main-400)";
              document.getElementById("element-label-" + eltid).focus();
              setTimeout(() => {
                document.getElementById("element_" + eltid).style.backgroundColor = "inherit";
                document.getElementById("element_" + eltid).style.borderColor = "";
              }, 1500);
            }, 300);
          }
        } else {
          this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"), this.translate(response.msg));
        }
        this.loading = false;
      });
    },
    getDescription() {
      if (this.fabrikPage.intro_raw) {
        formBuilderService.getAllTranslations(this.fabrikPage.intro_raw).then((response) => {
          if (response.status && response.data) {
            if (response.data[this.shortDefaultLang] !== "") {
              let strippedString = response.data[this.shortDefaultLang].replace(/(<([^>]+)>)/gi, "");
              if (strippedString.length > 0) {
                this.description = response.data[this.shortDefaultLang];
              }
            }
          }
        });
      } else {
        this.fabrikPage.intro_raw = "FORM_" + this.profile_id + "_INTRO_" + this.fabrikPage.id;
        this.fabrikPage.intro = {};
      }
    },
    addSection() {
      if (this.sections.length < 10) {
        formBuilderService.createSimpleGroup(this.page.id, {
          fr: "Nouvelle section",
          en: "New section"
        }, this.mode).then((response) => {
          if (response.status) {
            this.getSections();
            this.updateLastSave();
          } else {
            this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_CREATE_SECTION_ERROR"), this.translate(response.msg));
          }
        }).catch((error) => {
          this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_CREATE_SECTION_ERROR"), error);
        });
      } else {
        this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_MAX_SECTION_TITLE"), this.translate("COM_EMUNDUS_FORM_BUILDER_MAX_SECTION_TEXT"));
      }
    },
    moveSection(sectionId, direction) {
      let sectionsInOrder = this.sections.map((section, index2) => {
        return {
          id: section.group_id,
          order: index2
        };
      });
      const index = sectionsInOrder.findIndex((section) => sectionId === section.id);
      const sectionToMove = sectionsInOrder[index].id;
      if (direction === "up") {
        if (index > 0) {
          sectionsInOrder[index].id = sectionsInOrder[index - 1].id;
          sectionsInOrder[index - 1].id = sectionToMove;
        }
      } else {
        if (index < sectionsInOrder.length - 1) {
          sectionsInOrder[index].id = sectionsInOrder[index + 1].id;
          sectionsInOrder[index + 1].id = sectionToMove;
        }
      }
      formBuilderService.reorderSections(this.page.id, sectionsInOrder);
      const oldOrderSections = this.sections;
      let newOrderSections = [];
      sectionsInOrder.forEach((section) => {
        newOrderSections.push(oldOrderSections.find((oldSection) => oldSection.group_id === section.id));
      });
      this.sections = newOrderSections;
    },
    updateTitle() {
      this.fabrikPage.show_title.label[this.shortDefaultLang] = this.$refs.pageTitle.innerText.trim().replace(/[\r\n]/gm, "");
      this.$refs.pageTitle.innerText = this.$refs.pageTitle.innerText.trim().replace(/[\r\n]/gm, "");
      formBuilderService.updateTranslation(null, this.fabrikPage.show_title.titleraw, this.fabrikPage.show_title.label).then((response) => {
        if (response.data.status) {
          translationsService.updateTranslations(this.fabrikPage.show_title.label[this.shortDefaultLang], "falang", this.shortDefaultLang, this.fabrikPage.menu_id, "title", "menu");
          console.log("emit update title");
          this.$emit("update-page-title", {
            page: this.page.id,
            new_title: this.$refs.pageTitle.innerText
          });
          this.updateLastSave();
        }
      });
    },
    updateTitleKeyup() {
      document.activeElement.blur();
    },
    updateDescription() {
      this.fabrikPage.intro[this.shortDefaultLang] = this.$refs.pageDescription.innerText.replace(/[\r\n]/gm, "<br/>");
      formBuilderService.updateTranslation(null, this.fabrikPage.intro_raw, this.fabrikPage.intro).then((response) => {
        if (response.data.status) {
          this.updateLastSave();
          this.fabrikPage.intro_raw = response.data.data;
        }
        if (this.$refs.pageDescription.innerText === "") {
          document.getElementById("pageDescription").textContent = this.translate("COM_EMUNDUS_FORM_BUILDER_ADD_PAGE_INTRO_ADD");
          document.getElementById("pageDescription").classList.add("em-text-neutral-600");
        }
      });
    },
    updateElementsOrder(event, fromGroup, toGroup) {
      let updated = false;
      if (fromGroup > 0 && toGroup > 0 && fromGroup != toGroup) {
        const sectionFrom = this.sections.find((section) => section.group_id === fromGroup);
        const fromElements = Object.values(sectionFrom.elements);
        const movedElement = fromElements[event.oldIndex];
        if (movedElement !== void 0 && movedElement !== null && movedElement.id) {
          const foundElement = this.$refs["section-" + toGroup][0].elements.find((element) => element.id === movedElement.id);
          if (foundElement === void 0 || foundElement === null) {
            this.$refs["section-" + toGroup][0].elements.splice(event.newIndex, 0, movedElement);
          }
          const toElements = this.$refs["section-" + toGroup][0].elements.map((element, index) => {
            return { id: element.id, order: index + 1 };
          });
          formBuilderService.updateOrder(toElements, toGroup, movedElement).then((response) => {
            updated = response.data.status;
            if (!updated) {
              this.displayError("COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED", "");
            }
          });
          this.updateLastSave();
        } else {
          this.displayError("COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED", "");
        }
      } else {
        this.displayError("COM_EMUNDUS_FORM_BUILDER_UPDATE_ELEMENTS_ORDER_FAILED", "");
      }
    },
    deleteSection(sectionId) {
      this.sections = this.sections.filter((section) => section.group_id !== sectionId);
      this.updateLastSave();
    }
  }
};
const _hoisted_1$h = { id: "form-builder-page" };
const _hoisted_2$h = { class: "tw-flex tw-items-center tw-justify-between" };
const _hoisted_3$g = ["placeholder", "innerHTML"];
const _hoisted_4$g = ["title"];
const _hoisted_5$f = ["innerHTML", "placeholder"];
const _hoisted_6$e = { class: "form-builder-page-sections tw-mt-2" };
const _hoisted_7$e = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$h(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_page_section = resolveComponent("form-builder-page-section");
  return openBlock(), createElementBlock("div", _hoisted_1$h, [
    createBaseVNode("div", _hoisted_2$h, [
      createBaseVNode("span", {
        class: "tw-text-2xl tw-font-semibold editable-data",
        id: "page-title",
        ref: "pageTitle",
        onFocusout: _cache[0] || (_cache[0] = (...args) => $options.updateTitle && $options.updateTitle(...args)),
        onKeyup: _cache[1] || (_cache[1] = withKeys((...args) => $options.updateTitleKeyup && $options.updateTitleKeyup(...args), ["enter"])),
        onKeydown: _cache[2] || (_cache[2] = (event) => _ctx.checkMaxMinlength(event, 50, 0)),
        contenteditable: "true",
        placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_ADD_PAGE_TITLE_ADD"),
        innerHTML: _ctx.translate($data.title)
      }, null, 40, _hoisted_3$g),
      createBaseVNode("button", {
        id: "add-page-modele",
        class: "tw-btn-cancel !tw-w-auto",
        onClick: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("open-create-model", $props.page.id))
      }, [
        $props.mode === "forms" ? (openBlock(), createElementBlock("span", {
          key: 0,
          class: "material-symbols-outlined tw-cursor-pointer",
          title: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_TITLE")
        }, "post_add", 8, _hoisted_4$g)) : createCommentVNode("", true),
        createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_TITLE")), 1)
      ])
    ]),
    createBaseVNode("span", {
      class: "description editable-data",
      id: "pageDescription",
      ref: "pageDescription",
      innerHTML: $data.description,
      onFocusout: _cache[4] || (_cache[4] = (...args) => $options.updateDescription && $options.updateDescription(...args)),
      contenteditable: "true",
      placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_ADD_PAGE_INTRO_ADD")
    }, null, 40, _hoisted_5$f),
    createBaseVNode("div", _hoisted_6$e, [
      $data.sections.length > 0 ? (openBlock(), createElementBlock("button", {
        key: 0,
        id: "add-section",
        class: "tw-btn-primary tw-px-6 tw-py-3",
        onClick: _cache[5] || (_cache[5] = ($event) => $options.addSection())
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ADD_SECTION")), 1)) : createCommentVNode("", true),
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.sections, (section, index) => {
        return openBlock(), createBlock(_component_form_builder_page_section, {
          key: section.group_id,
          profile_id: parseInt($props.profile_id),
          page_id: parseInt($props.page.id),
          section,
          index: index + 1,
          totalSections: $data.sections.length,
          ref_for: true,
          ref: "section-" + section.group_id,
          onOpenElementProperties: _cache[6] || (_cache[6] = ($event) => _ctx.$emit("open-element-properties", $event)),
          onMoveElement: $options.updateElementsOrder,
          onDeleteSection: $options.deleteSection,
          onUpdateElement: $options.getSections,
          onMoveSection: $options.moveSection,
          onOpenSectionProperties: ($event) => _ctx.$emit("open-section-properties", section)
        }, null, 8, ["profile_id", "page_id", "section", "index", "totalSections", "onMoveElement", "onDeleteSection", "onUpdateElement", "onMoveSection", "onOpenSectionProperties"]);
      }), 128))
    ]),
    createBaseVNode("button", {
      id: "add-section",
      class: "tw-btn-primary tw-px-6 tw-py-3",
      onClick: _cache[7] || (_cache[7] = ($event) => $options.addSection())
    }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ADD_SECTION")), 1),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_7$e)) : createCommentVNode("", true)
  ]);
}
const FormBuilderPage = /* @__PURE__ */ _export_sfc(_sfc_main$h, [["render", _sfc_render$h]]);
const FormBuilderPreviewForm_vue_vue_type_style_index_0_lang = "";
const _sfc_main$g = {
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
const _hoisted_1$g = { key: 0 };
const _hoisted_2$g = { class: "tw-text-xs tw-w-full tw-text-end tw-mb-4" };
const _hoisted_3$f = { class: "preview-groups tw-flex tw-flex-col" };
const _hoisted_4$f = { class: "section-card tw-flex tw-flex-col" };
const _hoisted_5$e = { class: "section-identifier tw-bg-profile-full tw-flex tw-items-center" };
const _hoisted_6$d = { class: "text-xxs" };
const _hoisted_7$d = { class: "section-content tw-w-full" };
const _hoisted_8$b = { class: "tw-text-xxs tw-w-full tw-text-end" };
function _sfc_render$g(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_skeleton = resolveComponent("skeleton");
  return openBlock(), createElementBlock("div", {
    id: "form-builder-preview-form",
    class: normalizeClass(["tw-h-full tw-w-full", { loading: $data.loading }])
  }, [
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_1$g, [
      createBaseVNode("p", _hoisted_2$g, toDisplayString($props.form_label), 1),
      createBaseVNode("div", _hoisted_3$f, [
        (openBlock(true), createElementBlock(Fragment, null, renderList($data.formData.groups, (group, index) => {
          return openBlock(), createElementBlock("section", {
            key: group.id,
            class: "tw-mb-2 form-builder-page-section"
          }, [
            createBaseVNode("div", _hoisted_4$f, [
              createBaseVNode("div", _hoisted_5$e, [
                createBaseVNode("span", _hoisted_6$d, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SECTION")) + " " + toDisplayString(index + 1) + " / " + toDisplayString($data.formData.groups.length), 1)
              ]),
              createBaseVNode("div", _hoisted_7$d, [
                createBaseVNode("p", _hoisted_8$b, toDisplayString(group.label.replace("Model - ", "")), 1)
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
const FormBuilderPreviewForm = /* @__PURE__ */ _export_sfc(_sfc_main$g, [["render", _sfc_render$g]]);
const FormBuilderCreatePage_vue_vue_type_style_index_0_scoped_2c2ce8ff_lang = "";
const _sfc_main$f = {
  name: "FormBuilderCreatePage.vue",
  components: {
    Skeleton,
    FormBuilderPreviewForm
  },
  props: {
    profile_id: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      loading: true,
      selected: -1,
      models: [],
      page: {
        label: {
          fr: "Nouvelle page",
          en: "New page"
        },
        intro: {
          fr: "",
          en: ""
        },
        prid: this.profile_id,
        template: 0
      },
      search: "",
      structure: "new",
      // new | initial, structure means data structure, to know if we keep same database tables or not
      canUseInitialStructure: true
    };
  },
  created() {
    this.getModels();
  },
  methods: {
    getModels() {
      formBuilderService.getModels().then((response) => {
        if (response.status) {
          this.models = response.data.map((model) => {
            model.displayed = true;
            return model;
          });
        } else {
          Swal.fire({
            type: "warning",
            title: this.translate("COM_EMUNDUS_FORM_BUILDER_GET_PAGE_MODELS_ERROR"),
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            }
          });
        }
        this.loading = false;
      });
    },
    createPage() {
      this.loading = true;
      let model_form_id = -1;
      if (this.selected > 0) {
        const found_model = this.models.find((model) => {
          return model.id === this.selected;
        });
        if (found_model) {
          model_form_id = found_model.form_id;
          this.page.label = found_model.label;
          this.page.intro = found_model.intro;
        }
        if (this.structure !== "new" && !this.canUseInitialStructure) {
          this.structure = "new";
        }
      }
      const data = { ...this.page, modelid: model_form_id, keep_structure: this.structure === "initial" };
      formBuilderService.addPage(data).then((response) => {
        if (!response.status) {
          Swal.fire({
            type: "error",
            title: this.translate("COM_EMUNDUS_FORM_BUILDER_CREATE_PAGE_ERROR"),
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            }
          });
          this.close(false);
        } else {
          this.close(true, response.id);
        }
      });
    },
    close(reload = true, newSelected = 0) {
      this.$emit("close", {
        "reload": reload,
        "newSelected": newSelected
      });
    },
    isInitialStructureAlreadyUsed() {
      let used = false;
      if (this.selected !== -1) {
        const found_model = this.models.find((model) => {
          return model.id === this.selected;
        });
        if (found_model) {
          formBuilderService.checkIfModelTableIsUsedInForm(found_model.form_id, this.profile_id).then((response) => {
            if (response.status) {
              used = response.data;
            }
            if (used) {
              this.structure = "new";
              this.canUseInitialStructure = false;
            } else {
              this.canUseInitialStructure = true;
            }
            return used;
          }).catch(() => {
            this.canUseInitialStructure = false;
            return true;
          });
        } else {
          return used;
        }
      } else {
        return used;
      }
    }
  },
  computed: {
    displayedModels() {
      return this.models.filter((model) => {
        return model.displayed;
      });
    }
  },
  watch: {
    search: function() {
      this.models.forEach((model) => {
        model.displayed = model.label[this.shortDefaultLang].toLowerCase().includes(this.search.toLowerCase().trim());
      });
    },
    selected: function() {
      if (this.selected !== -1) {
        this.isInitialStructureAlreadyUsed();
      }
    }
  }
};
const _hoisted_1$f = {
  id: "form-builder-create-page",
  class: "tw-w-full em-p-32 tw-pt-4"
};
const _hoisted_2$f = { class: "tw-mb-1 em-text-neutral-800" };
const _hoisted_3$e = { id: "new-page" };
const _hoisted_4$e = { class: "separator tw-mt-8" };
const _hoisted_5$d = { class: "line-head em-mt-4 em-p-8 tw-text-white tw-bg-profile-full" };
const _hoisted_6$c = {
  id: "models",
  class: "tw-flex tw-items-center tw-w-full"
};
const _hoisted_7$c = {
  key: 0,
  class: "tw-w-full"
};
const _hoisted_8$a = { id: "search-model-wrapper" };
const _hoisted_9$9 = { id: "structure-options" };
const _hoisted_10$6 = { class: "tw-flex tw-items-center" };
const _hoisted_11$4 = { for: "new-structure" };
const _hoisted_12$4 = { for: "initial-structure" };
const _hoisted_13$4 = { class: "models-card tw-flex tw-items-center" };
const _hoisted_14$3 = ["title", "onClick"];
const _hoisted_15$3 = {
  key: 0,
  class: "empty-model-message tw-w-full tw-text-center"
};
const _hoisted_16$3 = { class: "tw-w-full" };
const _hoisted_17$3 = {
  key: 1,
  class: "tw-w-full"
};
const _hoisted_18$3 = { class: "models-card tw-grid" };
const _hoisted_19$3 = { class: "actions tw-justify-between tw-flex tw-items-center tw-w-full" };
const _hoisted_20$3 = ["disabled"];
function _sfc_render$f(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_preview_form = resolveComponent("form-builder-preview-form");
  const _component_skeleton = resolveComponent("skeleton");
  return openBlock(), createElementBlock("div", _hoisted_1$f, [
    createBaseVNode("div", null, [
      createBaseVNode("h3", _hoisted_2$f, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_CREATE_NEW_PAGE")), 1),
      createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_CREATE_NEW_PAGE_INTRO")), 1),
      createBaseVNode("section", _hoisted_3$e, [
        createBaseVNode("div", {
          class: normalizeClass(["tw-mt-4 tw-mb-4 card-wrapper", { selected: -1 === $data.selected }]),
          onClick: _cache[2] || (_cache[2] = ($event) => {
            $data.selected = -1;
          })
        }, [
          createBaseVNode("div", {
            class: "card em-shadow-cards tw-cursor-pointer tw-flex tw-items-center",
            onDblclick: _cache[0] || (_cache[0] = (...args) => $options.createPage && $options.createPage(...args))
          }, _cache[10] || (_cache[10] = [
            createBaseVNode("span", { class: "add_circle material-symbols-outlined tw-text-profile-full" }, "add_circle", -1)
          ]), 32),
          withDirectives(createBaseVNode("input", {
            type: "text",
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.page.label[_ctx.shortDefaultLang] = $event),
            class: normalizeClass(["em-p-4", {
              "tw-text-white": -1 === $data.selected,
              "tw-bg-profile-full": -1 === $data.selected
            }])
          }, null, 2), [
            [vModelText, $data.page.label[_ctx.shortDefaultLang]]
          ])
        ], 2)
      ]),
      createBaseVNode("div", _hoisted_4$e, [
        createBaseVNode("p", _hoisted_5$d, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_CREATE_NEW_PAGE_FROM_MODEL")), 1),
        _cache[11] || (_cache[11] = createBaseVNode("div", { class: "line tw-bg-profile-full" }, null, -1))
      ]),
      createBaseVNode("section", _hoisted_6$c, [
        !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_7$c, [
          createBaseVNode("div", _hoisted_8$a, [
            withDirectives(createBaseVNode("input", {
              id: "search-model",
              class: "tw-mt-4",
              type: "text",
              "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.search = $event),
              placeholder: "Rechercher"
            }, null, 512), [
              [vModelText, $data.search]
            ]),
            createBaseVNode("span", {
              class: "reset-search material-symbols-outlined tw-cursor-pointer",
              onClick: _cache[4] || (_cache[4] = ($event) => $data.search = "")
            }, "close")
          ]),
          createBaseVNode("section", _hoisted_9$9, [
            createBaseVNode("div", _hoisted_10$6, [
              withDirectives(createBaseVNode("input", {
                type: "radio",
                id: "new-structure",
                name: "structure",
                value: "new",
                "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.structure = $event)
              }, null, 512), [
                [vModelRadio, $data.structure]
              ]),
              createBaseVNode("label", _hoisted_11$4, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_NEW_STRUCTURE")), 1)
            ]),
            createBaseVNode("div", {
              class: normalizeClass(["tw-flex tw-items-center", { "disabled": !$data.canUseInitialStructure }])
            }, [
              withDirectives(createBaseVNode("input", {
                type: "radio",
                id: "initial-structure",
                name: "structure",
                value: "initial",
                "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.structure = $event)
              }, null, 512), [
                [vModelRadio, $data.structure]
              ]),
              createBaseVNode("label", _hoisted_12$4, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_INITIAL_STRUCTURE")), 1)
            ], 2)
          ]),
          createBaseVNode("div", _hoisted_13$4, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.models, (model) => {
              return openBlock(), createElementBlock("div", {
                key: model.id,
                class: normalizeClass(["card-wrapper em-mr-32", { selected: model.id === $data.selected, hidden: !model.displayed }]),
                title: model.label[_ctx.shortDefaultLang],
                onClick: ($event) => $data.selected = model.id,
                onDblclick: _cache[7] || (_cache[7] = (...args) => $options.createPage && $options.createPage(...args))
              }, [
                createVNode(_component_form_builder_preview_form, {
                  form_id: Number(model.form_id),
                  form_label: model.label[_ctx.shortDefaultLang],
                  class: normalizeClass(["card em-shadow-cards model-preview tw-cursor-pointer", {
                    "tw-text-white": model.id === $data.selected,
                    "tw-bg-profile-full": model.id === $data.selected
                  }])
                }, null, 8, ["form_id", "form_label", "class"]),
                createBaseVNode("p", {
                  class: normalizeClass(["em-p-4", {
                    "tw-text-white": model.id === $data.selected,
                    "tw-bg-profile-full": model.id === $data.selected
                  }])
                }, toDisplayString(model.label[_ctx.shortDefaultLang]), 3)
              ], 42, _hoisted_14$3);
            }), 128)),
            $options.displayedModels.length < 1 ? (openBlock(), createElementBlock("div", _hoisted_15$3, [
              _cache[12] || (_cache[12] = createBaseVNode("span", { class: "material-symbols-outlined" }, "manage_search", -1)),
              createBaseVNode("p", _hoisted_16$3, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_EMPTY_PAGE_MODELS")), 1)
            ])) : createCommentVNode("", true)
          ])
        ])) : (openBlock(), createElementBlock("div", _hoisted_17$3, [
          createVNode(_component_skeleton, {
            width: "206px",
            height: "41px",
            classes: "tw-mt-4 tw-mb-4 tw-rounded-coordinator"
          }),
          createBaseVNode("div", _hoisted_18$3, [
            (openBlock(), createElementBlock(Fragment, null, renderList(16, (i) => {
              return createBaseVNode("div", {
                key: i,
                class: "tw-flex tw-flex-col card-wrapper tw-mr-6"
              }, [
                createVNode(_component_skeleton, {
                  width: "150px",
                  height: "200px",
                  classes: "card em-shadow-cards model-preview"
                }),
                createVNode(_component_skeleton, {
                  width: "150px",
                  height: "20px",
                  classes: "em-p-4"
                })
              ]);
            }), 64))
          ])
        ]))
      ])
    ]),
    createBaseVNode("div", _hoisted_19$3, [
      createBaseVNode("button", {
        class: "tw-btn-cancel !tw-w-auto tw-bg-white",
        onClick: _cache[8] || (_cache[8] = ($event) => $options.close(false))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_CANCEL")), 1),
      createBaseVNode("button", {
        class: "tw-btn-primary tw-w-auto tw-ml-2",
        disabled: $data.loading,
        onClick: _cache[9] || (_cache[9] = (...args) => $options.createPage && $options.createPage(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_PAGE_CREATE_SAVE")), 9, _hoisted_20$3)
    ])
  ]);
}
const FormBuilderCreatePage = /* @__PURE__ */ _export_sfc(_sfc_main$f, [["render", _sfc_render$f], ["__scopeId", "data-v-2c2ce8ff"]]);
const FormBuilderPages_vue_vue_type_style_index_0_lang = "";
const _sfc_main$e = {
  name: "FormBuilderPages",
  components: {
    draggable: VueDraggableNext,
    popover: Popover
  },
  props: {
    pages: {
      type: Array,
      required: true
    },
    selected: {
      type: Number,
      default: 0
    },
    profile_id: {
      type: Number,
      required: true
    }
  },
  mixins: [formBuilderMixin],
  data() {
    return {
      pageOptionsShown: 0
    };
  },
  methods: {
    selectPage(id) {
      this.$emit("select-page", id);
    },
    deletePage(page) {
      if (this.pages.length > 2) {
        Swal$1.fire({
          title: this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE_CONFIRMATION") + page.label,
          text: this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE_CONFIRMATION_TEXT"),
          showCancelButton: true,
          confirmButtonText: this.translate("COM_EMUNDUS_ACTIONS_DELETE"),
          cancelButtonText: this.translate("COM_EMUNDUS_ONBOARD_CANCEL"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            cancelButton: "em-swal-cancel-button",
            confirmButton: "em-swal-delete-button"
          }
        }).then((result2) => {
          if (result2.value) {
            formBuilderService.deletePage(page.id).then((response) => {
              if (response.status) {
                let deletedPage = this.pages.findIndex((p) => p.id === page.id);
                this.pages.splice(deletedPage, 1);
                this.$emit("delete-page", page.id);
                this.updateLastSave();
              }
            });
          }
        });
      } else {
        Swal$1.fire({
          title: this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE_ERROR"),
          text: this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE_ERROR_TEXT"),
          type: "error",
          showCancelButton: false,
          confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_OK"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
      }
    },
    createModelFrom(page) {
      this.$emit("open-create-model", page.id);
    },
    onDragEnd() {
      const newOrder = this.pages.map((page, index) => {
        return { rgt: index, link: page.link };
      });
      formBuilderService.reorderMenu(newOrder, this.$props.profile_id).then((response) => {
        if (response.status) {
          this.$emit("reorder-pages", this.pages);
        } else {
          Swal$1.fire({
            title: this.translate("COM_EMUNDUS_FORM_BUILDER_UPDATE_ORDER_PAGE_ERROR"),
            text: result.msg,
            type: "error",
            showCancelButton: false,
            confirmButtonText: this.translate("COM_EMUNDUS_ONBOARD_OK"),
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            }
          });
        }
      });
    }
  },
  computed: {
    // return all pages but not submission page
    formPages() {
      return this.pages.length > 0 ? this.pages.filter((page) => {
        return page.type === "form";
      }) : [];
    },
    submissionPages() {
      return this.pages.length > 0 ? this.pages.filter((page) => {
        return page.type === "submission";
      }) : [];
    }
  }
};
const _hoisted_1$e = { id: "form-builder-pages" };
const _hoisted_2$e = { class: "form-builder-title tw-flex tw-items-center md:tw-justify-center lg:tw-justify-between tw-p-4" };
const _hoisted_3$d = ["onMouseover"];
const _hoisted_4$d = ["onClick"];
const _hoisted_5$c = {
  "aria-label": "action",
  class: "em-flex-col-start"
};
const _hoisted_6$b = ["onClick"];
const _hoisted_7$b = ["onClick"];
const _hoisted_8$9 = { class: "tw-flex tw-items-center tw-justify-between" };
const _hoisted_9$8 = ["onClick"];
function _sfc_render$e(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_popover = resolveComponent("popover");
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$e, [
    createBaseVNode("p", _hoisted_2$e, [
      createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_EVERY_PAGE")), 1),
      createBaseVNode("span", {
        id: "add-page",
        class: "material-icons tw-cursor-pointer",
        onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("open-page-create"))
      }, " add ")
    ]),
    createVNode(_component_draggable, {
      "model-value": $props.pages,
      "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $props.pages = $event),
      group: "form-builder-pages",
      sort: true,
      class: "draggables-list",
      onEnd: $options.onDragEnd
    }, {
      default: withCtx(() => [
        createVNode(TransitionGroup, null, {
          default: withCtx(() => [
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.formPages, (page, index) => {
              return openBlock(), createElementBlock("div", {
                class: normalizeClass(["tw-font-medium tw-cursor-pointer", { selected: page.id == $props.selected }]),
                key: page.id
              }, [
                createBaseVNode("div", {
                  class: "tw-flex tw-items-center tw-justify-between",
                  onMouseover: ($event) => $data.pageOptionsShown = page.id,
                  onMouseleave: _cache[1] || (_cache[1] = ($event) => $data.pageOptionsShown = 0)
                }, [
                  createBaseVNode("p", {
                    onClick: ($event) => $options.selectPage(page.id),
                    class: "tw-w-full tw-p-4 form-builder-page-label"
                  }, toDisplayString(page.label !== "" ? _ctx.translate(page.label) : _ctx.translate("COM_EMUNDUS_FILES_PAGE") + " " + (index + 1)), 9, _hoisted_4$d),
                  createBaseVNode("div", {
                    class: "tw-flex tw-items-center tw-p-4",
                    style: normalizeStyle($data.pageOptionsShown === page.id ? "opacity:1" : "opacity: 0")
                  }, [
                    createVNode(_component_popover, {
                      popoverArrowClass: "custom-popover-arraow",
                      "open-class": "form-builder-pages-popover",
                      position: "left"
                    }, {
                      default: withCtx(() => [
                        createVNode(Transition, {
                          name: "slide-down",
                          type: "transition"
                        }, {
                          default: withCtx(() => [
                            createBaseVNode("div", null, [
                              createBaseVNode("nav", _hoisted_5$c, [
                                createBaseVNode("p", {
                                  onClick: ($event) => $options.deletePage(page),
                                  class: "tw-cursor-pointer tw-p-2 tw-text-base tw-text-red-600"
                                }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_PAGE")), 9, _hoisted_6$b),
                                createBaseVNode("p", {
                                  onClick: ($event) => $options.createModelFrom(page),
                                  class: "tw-cursor-pointer tw-p-2 tw-text-base"
                                }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_TITLE")), 9, _hoisted_7$b)
                              ])
                            ])
                          ]),
                          _: 2
                        }, 1024)
                      ]),
                      _: 2
                    }, 1024)
                  ], 4)
                ], 40, _hoisted_3$d)
              ], 2);
            }), 128))
          ]),
          _: 1
        })
      ]),
      _: 1
    }, 8, ["model-value", "onEnd"]),
    createVNode(TransitionGroup, null, {
      default: withCtx(() => [
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.submissionPages, (page) => {
          return openBlock(), createElementBlock("div", {
            class: normalizeClass(["tw-font-medium tw-cursor-pointer", { selected: page.id == $props.selected }]),
            key: page.id
          }, [
            createBaseVNode("div", _hoisted_8$9, [
              createBaseVNode("p", {
                onClick: ($event) => $options.selectPage(page.id),
                class: "tw-w-full tw-p-4"
              }, toDisplayString(page.label), 9, _hoisted_9$8)
            ])
          ], 2);
        }), 128))
      ]),
      _: 1
    })
  ]);
}
const FormBuilderPages = /* @__PURE__ */ _export_sfc(_sfc_main$e, [["render", _sfc_render$e]]);
const FormBuilderDocuments_vue_vue_type_style_index_0_lang = "";
const _sfc_main$d = {
  name: "FormBuilderDocuments",
  props: {
    profile_id: {
      type: Number,
      required: true
    },
    campaign_id: {
      type: Number,
      required: true
    }
  },
  mixins: [errors],
  data() {
    return {
      documents: []
    };
  },
  setup() {
    return {
      formBuilderStore: useFormBuilderStore()
    };
  },
  created() {
    this.getDocuments();
    if (this.formBuilderStore.getDocumentModels.length === 0) {
      this.getDocumentModels();
    }
  },
  methods: {
    getDocuments() {
      formService.getDocuments(this.profile_id).then((response) => {
        if (response.status) {
          this.documents = response.data;
        } else {
          this.documents = [];
          this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_GET_DOCUMENTS_FAILED"), response.msg);
        }
      });
    },
    getDocumentModels() {
      formService.getDocumentModels().then((response) => {
        if (response.status) {
          this.formBuilderStore.updateDocumentModels(response.data);
        }
      });
    },
    createDocument() {
      this.$emit("open-create-document");
    }
  }
};
const _hoisted_1$d = { id: "form-builder-documents" };
const _hoisted_2$d = { class: "document-label" };
function _sfc_render$d(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$d, [
    createBaseVNode("div", {
      id: "form-builder-title",
      class: "tw-cursor-pointer tw-flex tw-items-center tw-justify-between tw-p-4",
      onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("show-documents"))
    }, [
      createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_EVERY_DOCUMENTS")), 1),
      createBaseVNode("span", {
        id: "add-document",
        class: "material-symbols-outlined tw-cursor-pointer",
        onClick: _cache[0] || (_cache[0] = (...args) => $options.createDocument && $options.createDocument(...args))
      }, "add")
    ]),
    (openBlock(true), createElementBlock(Fragment, null, renderList($data.documents, (document2) => {
      return openBlock(), createElementBlock("div", {
        key: document2.id,
        onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("show-documents")),
        class: "tw-p-4"
      }, [
        createBaseVNode("p", _hoisted_2$d, toDisplayString(document2.label), 1)
      ]);
    }), 128))
  ]);
}
const FormBuilderDocuments = /* @__PURE__ */ _export_sfc(_sfc_main$d, [["render", _sfc_render$d]]);
const FormBuilderDocumentListElement_vue_vue_type_style_index_0_lang = "";
const _sfc_main$c = {
  name: "FormBuilderDocumentListElement",
  props: {
    document: {
      type: Object,
      required: true
    },
    totalDocuments: {
      type: Number,
      default: 1
    },
    documentIndex: {
      type: Number,
      default: 1
    },
    profile_id: {
      type: Number,
      required: true
    }
  },
  mixins: [formBuilderMixin],
  data() {
    return {
      closedSection: false,
      documentData: {},
      canBeRemoved: false,
      reasonCantRemove: ""
    };
  },
  setup() {
    return {
      formBuilderStore: useFormBuilderStore()
    };
  },
  created() {
    if (this.document.docid) {
      this.getDocumentModel(this.document.docid);
      this.checkIfDocumentCanBeDeleted();
    }
  },
  methods: {
    moveDocument(direction) {
      this.$emit("move-document", this.document, direction);
    },
    getDocumentModel(documentId = null, from_store = true) {
      this.models = from_store ? this.formBuilderStore.getDocumentModels : [];
      this.documentData = {};
      if (this.models.length > 0) {
        const foundModel = this.models.find((model) => model.id === documentId);
        if (foundModel) {
          this.documentData = foundModel;
        } else {
          formService.getDocumentModels(documentId).then((response) => {
            if (response.status) {
              this.documentData = response.data;
            }
          });
        }
      } else {
        formService.getDocumentModels(documentId).then((response) => {
          if (response.status) {
            this.documentData = response.data;
          }
        });
      }
    },
    editDocument(event) {
      if (event.target.id === "delete-section") {
        return;
      }
      this.$emit("edit-document");
    },
    deleteDocument() {
      this.swalConfirm(
        this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_DOCUMENT"),
        this.document.label,
        this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_DOCUMENT_CONFIRM"),
        this.translate("JNO"),
        () => {
          formService.removeDocumentFromProfile(this.document.id).then((response) => {
            this.$emit("delete-document", this.document.id);
          });
        }
      );
    },
    checkIfDocumentCanBeDeleted() {
      formService.checkIfDocumentCanBeDeletedForProfile(this.document.docid, this.profile_id).then((response) => {
        if (response.status) {
          if (response.data.can_be_deleted) {
            this.canBeRemoved = true;
            this.reasonCantRemove = "";
          } else {
            this.canBeRemoved = false;
            this.reasonCantRemove = response.data.reason;
          }
        } else {
          this.canBeRemoved = false;
        }
      });
    }
  },
  watch: {
    document: {
      handler(newValue) {
        this.getDocumentModel(newValue.docid, false);
      },
      deep: true
    }
  }
};
const _hoisted_1$c = { class: "section-card tw-mt-8 tw-mb-8 tw-w-full tw-flex tw-flex-col" };
const _hoisted_2$c = { class: "section-identifier tw-bg-profile-full tw-cursor-pointer" };
const _hoisted_3$c = { key: 0 };
const _hoisted_4$c = { class: "tw-w-full tw-flex tw-items-center tw-justify-between" };
const _hoisted_5$b = { class: "section-title" };
const _hoisted_6$a = { key: 1 };
const _hoisted_7$a = { class: "section-title" };
function _sfc_render$c(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    id: "form-builder-document-list-element",
    onClick: _cache[3] || (_cache[3] = (...args) => $options.editDocument && $options.editDocument(...args))
  }, [
    createBaseVNode("div", _hoisted_1$c, [
      createBaseVNode("div", _hoisted_2$c, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT")) + " " + toDisplayString($props.documentIndex) + " / " + toDisplayString($props.totalDocuments), 1),
      createBaseVNode("div", {
        class: normalizeClass(["section-content", { "closed": $data.closedSection }])
      }, [
        $data.documentData.id ? (openBlock(), createElementBlock("div", _hoisted_3$c, [
          createBaseVNode("div", _hoisted_4$c, [
            createBaseVNode("span", _hoisted_5$b, toDisplayString($data.documentData.name[_ctx.shortDefaultLang]), 1),
            createBaseVNode("div", null, [
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-cursor-pointer hover-opacity",
                onClick: _cache[0] || (_cache[0] = ($event) => $options.moveDocument("up")),
                title: "Move section upwards"
              }, "keyboard_double_arrow_up"),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-cursor-pointer hover-opacity",
                onClick: _cache[1] || (_cache[1] = ($event) => $options.moveDocument("down")),
                title: "Move section downwards"
              }, "keyboard_double_arrow_down"),
              createBaseVNode("span", {
                class: "material-symbols-outlined tw-text-red-600 tw-cursor-pointer hover-opacity",
                onClick: _cache[2] || (_cache[2] = (...args) => $options.deleteDocument && $options.deleteDocument(...args))
              }, "delete")
            ])
          ]),
          createBaseVNode("p", null, toDisplayString($data.documentData.description[_ctx.shortDefaultLang]), 1),
          createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ALLOWED_TYPES")) + " : " + toDisplayString($data.documentData.allowed_types), 1),
          createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_MAX_DOCUMENTS")) + " : " + toDisplayString($data.documentData.nbmax), 1)
        ])) : (openBlock(), createElementBlock("div", _hoisted_6$a, [
          createBaseVNode("span", _hoisted_7$a, toDisplayString($props.document.label), 1)
        ]))
      ], 2)
    ])
  ]);
}
const FormBuilderDocumentListElement = /* @__PURE__ */ _export_sfc(_sfc_main$c, [["render", _sfc_render$c]]);
const FormBuilderDocumentList_vue_vue_type_style_index_0_lang = "";
const _sfc_main$b = {
  name: "FormBuilderDocumentList",
  components: {
    FormBuilderDocumentListElement,
    draggable: VueDraggableNext
  },
  props: {
    profile_id: {
      type: Number,
      required: true
    },
    campaign_id: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      documents: [],
      emptyDocuments: [{
        text: "COM_EMUNDUS_FORM_BUILDER_EMPTY_DOCUMENTS"
      }],
      closedSection: false
    };
  },
  created() {
    this.getDocuments();
  },
  methods: {
    getDocuments() {
      formService.getDocuments(this.profile_id).then((response) => {
        if (response.status) {
          this.documents = response.data.filter((document2) => {
            return document2.id;
          });
        }
      });
    },
    moveDocument(documentToMove, direction) {
      let requiredDocumentsInOrder = this.requiredDocuments.map((document2, index) => {
        return {
          id: document2.id,
          order: index
        };
      });
      let optionalDocumentsInOrder = this.optionalDocuments.map((document2, index) => {
        return {
          id: document2.id,
          order: index
        };
      });
      let position = null;
      let lastPosition = null;
      let moved = true;
      if (documentToMove.mandatory == 1) {
        position = requiredDocumentsInOrder.findIndex((document2) => document2.id === documentToMove.id);
        lastPosition = requiredDocumentsInOrder.length;
      } else {
        position = optionalDocumentsInOrder.findIndex((document2) => document2.id === documentToMove.id);
        lastPosition = optionalDocumentsInOrder.length;
      }
      if (position != null) {
        if (documentToMove.mandatory == 1) {
          if (position == 0 && direction === "up") {
            moved = false;
          } else if (position == lastPosition - 1 && direction === "down") {
            documentToMove.mandatory = false;
            requiredDocumentsInOrder = requiredDocumentsInOrder.filter((document2) => {
              return document2.id != documentToMove.id;
            });
            optionalDocumentsInOrder.unshift({
              id: documentToMove.id,
              order: 0
            });
          } else {
            if (direction === "up") {
              requiredDocumentsInOrder[position].id = requiredDocumentsInOrder[position - 1].id;
              requiredDocumentsInOrder[position - 1].id = documentToMove.id;
            } else {
              requiredDocumentsInOrder[position].id = requiredDocumentsInOrder[position + 1].id;
              requiredDocumentsInOrder[position + 1].id = documentToMove.id;
            }
          }
        } else {
          if (position == 0 && direction == "up") {
            documentToMove.mandatory = true;
            optionalDocumentsInOrder = optionalDocumentsInOrder.filter((document2) => {
              return document2.id != documentToMove.id;
            });
            requiredDocumentsInOrder.push({
              id: documentToMove.id,
              order: requiredDocumentsInOrder.length
            });
          } else if (position == lastPosition - 1 && direction === "down") {
            moved = false;
          } else {
            if (direction === "up") {
              optionalDocumentsInOrder[position].id = optionalDocumentsInOrder[position - 1].id;
              optionalDocumentsInOrder[position - 1].id = documentToMove.id;
            } else {
              optionalDocumentsInOrder[position].id = optionalDocumentsInOrder[position + 1].id;
              optionalDocumentsInOrder[position + 1].id = documentToMove.id;
            }
          }
        }
        if (moved) {
          requiredDocumentsInOrder.forEach((doc, index) => {
            doc.order = index;
          });
          optionalDocumentsInOrder.forEach((doc, index) => {
            doc.order = index + requiredDocumentsInOrder.length;
          });
          this.documents.forEach((document2, index) => {
            const foundReq = requiredDocumentsInOrder.find((reqDocument) => {
              return reqDocument.id == document2.id;
            });
            const foundOpt = optionalDocumentsInOrder.find((optDocument) => {
              return optDocument.id == document2.id;
            });
            if (foundReq) {
              this.documents[index].mandatory = 1;
              this.documents[index].ordering = foundReq.order;
            } else if (foundOpt) {
              this.documents[index].mandatory = 0;
              this.documents[index].ordering = foundOpt.order;
            }
          });
          formService.reorderDocuments(this.documents).then((response) => {
            campaignService.setDocumentMandatory({
              mandatory: documentToMove.mandatory,
              pid: this.profile_id,
              did: documentToMove.attachment_id
            });
          });
        }
      }
    },
    addDocument(mandatory = "1") {
      this.$emit("add-document", mandatory);
    },
    editDocument(document2) {
      this.$emit("edit-document", document2);
    },
    deleteDocument() {
      this.$emit("delete-document");
      this.getDocuments();
    }
  },
  computed: {
    requiredDocuments() {
      const requiredDocuments = this.documents.filter((document2) => document2.mandatory == 1);
      return requiredDocuments.sort((a, b) => {
        return a.ordering - b.ordering;
      });
    },
    optionalDocuments() {
      const optionalDocuments = this.documents.filter((document2) => document2.mandatory == 0);
      return optionalDocuments.sort((a, b) => {
        return a.ordering - b.ordering;
      });
    }
  }
};
const _hoisted_1$b = { id: "form-builder-document-list" };
const _hoisted_2$b = {
  id: "required-documents",
  class: "tw-w-full tw-mb-8 tw-mt-8"
};
const _hoisted_3$b = { class: "tw-text-2xl tw-font-semibold" };
const _hoisted_4$b = { key: 0 };
const _hoisted_5$a = {
  key: 1,
  class: "empty-documents tw-mt-4 tw-mb-4"
};
const _hoisted_6$9 = {
  id: "optional-documents",
  class: "tw-w-full tw-mb-8 tw-mt-8"
};
const _hoisted_7$9 = { class: "tw-text-2xl tw-font-semibold" };
const _hoisted_8$8 = { key: 0 };
const _hoisted_9$7 = {
  key: 1,
  class: "empty-documents tw-mt-4 tw-mb-4"
};
function _sfc_render$b(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_document_list_element = resolveComponent("form-builder-document-list-element");
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$b, [
    createBaseVNode("div", _hoisted_2$b, [
      createBaseVNode("p", _hoisted_3$b, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_REQUIRED_DOCUMENTS")), 1),
      $options.requiredDocuments.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_4$b, [
        createVNode(_component_draggable, {
          modelValue: $options.requiredDocuments,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $options.requiredDocuments = $event),
          group: "form-builder-documents",
          id: "required-documents",
          sort: false
        }, {
          default: withCtx(() => [
            createVNode(TransitionGroup, { id: "required-documents" }, {
              default: withCtx(() => [
                (openBlock(true), createElementBlock(Fragment, null, renderList($options.requiredDocuments, (document2, index) => {
                  return openBlock(), createBlock(_component_form_builder_document_list_element, {
                    key: "required-" + document2.id,
                    document: document2,
                    documentIndex: index + 1,
                    totalDocuments: $options.requiredDocuments.length,
                    profile_id: $props.profile_id,
                    onEditDocument: ($event) => $options.editDocument(document2),
                    onDeleteDocument: $options.deleteDocument,
                    onMoveDocument: $options.moveDocument
                  }, null, 8, ["document", "documentIndex", "totalDocuments", "profile_id", "onEditDocument", "onDeleteDocument", "onMoveDocument"]);
                }), 128))
              ]),
              _: 1
            })
          ]),
          _: 1
        }, 8, ["modelValue"])
      ])) : createCommentVNode("", true),
      $options.requiredDocuments.length < 1 ? (openBlock(), createElementBlock("div", _hoisted_5$a, [
        createVNode(_component_draggable, {
          list: $data.emptyDocuments,
          group: "form-builder-documents",
          id: "required-documents",
          sort: false,
          class: "draggables-list"
        }, {
          default: withCtx(() => [
            createVNode(TransitionGroup, { id: "required-documents" }, {
              default: withCtx(() => [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.emptyDocuments, (item, index) => {
                  return openBlock(), createElementBlock("p", {
                    class: "tw-w-full tw-text-center tw-p-4",
                    key: index
                  }, toDisplayString(_ctx.translate(item.text)), 1);
                }), 128))
              ]),
              _: 1
            })
          ]),
          _: 1
        }, 8, ["list"])
      ])) : createCommentVNode("", true),
      createBaseVNode("button", {
        id: "add-document",
        class: "tw-btn-primary tw-px-6 tw-py-3",
        onClick: _cache[1] || (_cache[1] = ($event) => $options.addDocument("1"))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_CREATE_REQUIRED_DOCUMENT")), 1)
    ]),
    createBaseVNode("div", _hoisted_6$9, [
      createBaseVNode("p", _hoisted_7$9, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_OPTIONAL_DOCUMENTS")), 1),
      $options.optionalDocuments.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_8$8, [
        createVNode(_component_draggable, {
          modelValue: $options.optionalDocuments,
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $options.optionalDocuments = $event),
          group: "form-builder-documents",
          id: "optional-documents",
          sort: false
        }, {
          default: withCtx(() => [
            createVNode(TransitionGroup, { id: "optional-documents" }, {
              default: withCtx(() => [
                (openBlock(true), createElementBlock(Fragment, null, renderList($options.optionalDocuments, (document2, index) => {
                  return openBlock(), createBlock(_component_form_builder_document_list_element, {
                    key: "optional-" + document2.id,
                    document: document2,
                    documentIndex: index + 1,
                    totalDocuments: $options.optionalDocuments.length,
                    profile_id: $props.profile_id,
                    onEditDocument: ($event) => $options.editDocument(document2),
                    onDeleteDocument: $options.deleteDocument,
                    onMoveDocument: $options.moveDocument
                  }, null, 8, ["document", "documentIndex", "totalDocuments", "profile_id", "onEditDocument", "onDeleteDocument", "onMoveDocument"]);
                }), 128))
              ]),
              _: 1
            })
          ]),
          _: 1
        }, 8, ["modelValue"])
      ])) : createCommentVNode("", true),
      $options.optionalDocuments.length < 1 ? (openBlock(), createElementBlock("div", _hoisted_9$7, [
        createVNode(_component_draggable, {
          list: $data.emptyDocuments,
          group: "form-builder-documents",
          id: "optional-documents",
          sort: false,
          class: "draggables-list"
        }, {
          default: withCtx(() => [
            createVNode(TransitionGroup, { id: "optional-documents" }, {
              default: withCtx(() => [
                (openBlock(true), createElementBlock(Fragment, null, renderList($data.emptyDocuments, (item, index) => {
                  return openBlock(), createElementBlock("p", {
                    class: "tw-w-full tw-text-center tw-p-4",
                    key: index
                  }, toDisplayString(_ctx.translate(item.text)), 1);
                }), 128))
              ]),
              _: 1
            })
          ]),
          _: 1
        }, 8, ["list"])
      ])) : createCommentVNode("", true),
      createBaseVNode("button", {
        id: "add-document",
        class: "tw-btn-primary tw-px-6 tw-py-3",
        onClick: _cache[3] || (_cache[3] = ($event) => $options.addDocument("0"))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_CREATE_OPTIONAL_DOCUMENT")), 1)
    ])
  ]);
}
const FormBuilderDocumentList = /* @__PURE__ */ _export_sfc(_sfc_main$b, [["render", _sfc_render$b]]);
const fileTypes = [
  {
    title: "COM_EMUNDUS_ONBOARD_PDF_DOCUMENTS",
    value: "pdf"
  },
  {
    title: "COM_EMUNDUS_ONBOARD_PICTURES_DOCUMENTS",
    value: "jpeg;jpg;png"
  },
  {
    title: "COM_EMUNDUS_ONBOARD_OFFICE_DOCUMENTS",
    value: "doc;docx;odt;ppt;pptx"
  },
  {
    title: "COM_EMUNDUS_ONBOARD_EXCEL_DOCUMENTS",
    value: "xls;xlsx;odf"
  },
  {
    title: "COM_EMUNDUS_FORM_BUILDER_FORMATS_AUDIO",
    value: "mp3"
  },
  {
    title: "COM_EMUNDUS_FORM_BUILDER_FORMATS_VIDEO",
    value: "mp4"
  },
  {
    title: "Zip",
    value: "zip"
  }
];
const FormBuilderCreateDocument_vue_vue_type_style_index_0_lang = "";
const _sfc_main$a = {
  name: "FormBuilderCreateDocument",
  props: {
    profile_id: {
      type: Number,
      required: true
    },
    current_document: {
      type: Object,
      default: null
    },
    mandatory: {
      type: Boolean,
      default: true
    },
    mode: {
      type: String,
      default: "create"
    }
  },
  components: {
    IncrementalSelect
  },
  mixins: [mixin, formBuilderMixin, errors],
  data() {
    return {
      models: [],
      modelsUsage: [],
      document: {
        id: null,
        type: {},
        mandatory: this.$props.mandatory,
        nbmax: 1,
        description: {
          fr: "",
          en: ""
        },
        name: {
          fr: "",
          en: ""
        },
        selectedTypes: {},
        minResolution: {
          width: 0,
          height: 0
        },
        maxResolution: {
          width: 0,
          height: 0
        },
        max_pages_pdf: 0
      },
      fileTypes: [],
      activeTab: "general",
      tabs: [
        {
          id: 0,
          label: "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_GENERAL",
          active: true,
          published: true
        },
        {
          id: 1,
          label: "COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_ADVANCED",
          active: false,
          published: true
        }
      ],
      hasPDF: false,
      hasImg: false,
      hasSample: false,
      currentSample: "",
      newSample: "",
      sampleFromDocumentId: null
    };
  },
  created() {
    this.getDocumentModels();
    this.getFileTypes();
  },
  methods: {
    selectTab(tab) {
      this.tabs.forEach((t) => {
        t.active = false;
      });
      tab.active = true;
    },
    getDocumentModels() {
      formService.getDocumentModels().then((response) => {
        if (response.status) {
          this.models = response.data;
          if (this.current_document != null && (this.current_document.docid || this.current_document.id)) {
            this.selectModel({
              target: {
                value: this.current_document.docid ? this.current_document.docid : this.current_document.id
              }
            }, this.current_document.mandatory !== null && this.current_document.mandatory != "undefined" ? this.current_document.mandatory : null);
          }
          formService.getDocumentModelsUsage(this.models.map((model) => {
            return model.id;
          })).then((response2) => {
            if (response2.status) {
              this.modelsUsage = response2.data;
            }
          });
        }
      });
    },
    toggleDocumentMandatory() {
      this.document.mandatory = this.document.mandatory == "1" ? "0" : "1";
    },
    getFileTypes() {
      this.fileTypes = fileTypes;
      this.fileTypes.forEach((filetype) => {
        this.document.selectedTypes[filetype.value] = false;
      });
    },
    checkFileType(event) {
      this.document.selectedTypes[event.target.value] = event.target.checked;
      this.hasImgFormat();
      this.hasPDFFormat();
    },
    selectModel(event, mandatory = null) {
      if (event.target.value !== "none") {
        const model = this.models.find((model2) => model2.id == event.target.value);
        this.document.id = model.id;
        this.document.type = model.type;
        this.document.mandatory = mandatory == null ? model.mandatory : mandatory;
        this.document.nbmax = model.nbmax;
        this.document.description = model.description;
        this.document.name = model.name;
        this.document.minResolution = {
          width: model.min_width,
          height: model.min_height
        };
        this.document.maxResolution = {
          width: model.max_width,
          height: model.max_height
        };
        this.document.max_pages_pdf = model.max_pages_pdf;
        this.fileTypes.forEach((filetype) => {
          this.document.selectedTypes[filetype.value] = false;
        });
        let types = model.allowed_types.split(";");
        types.forEach((type) => {
          if (["pdf"].includes(type)) {
            this.document.selectedTypes["pdf"] = true;
          }
          if (["jpeg", "jpg", "png", "gif"].includes(type)) {
            this.document.selectedTypes["jpeg;jpg;png"] = true;
          }
          if (["doc", "docx", "odt", "ppt", "pptx"].includes(type)) {
            this.document.selectedTypes["doc;docx;odt;ppt;pptx"] = true;
          }
          if (["xls", "xlsx", "odf"].includes(type)) {
            this.document.selectedTypes["xls;xlsx;odf"] = true;
          }
          if (["mp3"].includes(type)) {
            this.document.selectedTypes["mp3"] = true;
          }
          if (["mp4"].includes(type)) {
            this.document.selectedTypes["mp4"] = true;
          }
          if (["zip"].includes(type)) {
            this.document.selectedTypes["zip"] = true;
          }
        });
        this.hasImgFormat();
        this.hasPDFFormat();
      }
    },
    saveDocument() {
      let empty_names = true;
      Object.values(this.document.name).forEach((name) => {
        if (name != "") {
          empty_names = false;
        }
      });
      if (empty_names === true) {
        Swal$1.fire({
          type: "warning",
          title: this.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_PLEASE_FILL_TYPE"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
        return false;
      }
      const isModel = this.models.find((model) => {
        return model.id == this.document.id;
      });
      let types = [];
      Object.entries(this.document.selectedTypes).forEach((entry) => {
        if (entry[1]) {
          types.push(entry[0]);
        }
      });
      if (types.length < 1) {
        Swal$1.fire({
          type: "warning",
          title: this.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_PLEASE_FILL_FORMAT"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
        return false;
      }
      if (!isModel) {
        this.document.id = null;
        const data = {
          pid: this.profile_id,
          types: JSON.stringify(types),
          document: JSON.stringify(this.document),
          has_sample: this.hasSample
        };
        if (this.hasSample && this.newSample !== null) {
          const sampleFileInput = this.$refs.sampleFileInput;
          const file = sampleFileInput.files[0];
          data.sample = file;
        }
        campaignService.updateDocument(data, true).then((response) => {
          if (response.status) {
            this.$emit("documents-updated");
          } else {
            this.displayError("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_SAVE_ERROR", response.msg);
          }
        });
      } else {
        const data = {
          profile_id: this.profile_id,
          document_id: this.document.id,
          types: JSON.stringify(types),
          document: JSON.stringify(this.document),
          has_sample: this.hasSample ? 1 : 0
        };
        if (this.hasSample && this.newSample !== null) {
          const sampleFileInput = this.$refs.sampleFileInput;
          const file = sampleFileInput.files[0];
          data.sample = file;
        }
        if (Object.keys(this.modelsUsage).includes(this.document.id) && this.modelsUsage[this.document.id].usage > 1) {
          this.swalConfirm(
            this.translate("COM_EMUNDUS_FORM_BUILDER_MULTIPLE_FORMS_IMPACTED"),
            this.translate("COM_EMUNDUS_FORM_BUILDER_MULTIPLE_FORMS_IMPACTED_TEXT") + " : " + this.modelsUsage[this.document.id].profiles.map((profile) => {
              return profile.label;
            }).join(", "),
            this.translate("COM_EMUNDUS_ONBOARD_OK"),
            this.translate("COM_EMUNDUS_ONBOARD_CANCEL")
          ).then((response) => {
            if (response) {
              formBuilderService.updateDocument(data).then((response2) => {
                if (response2.status) {
                  this.$emit("documents-updated");
                } else {
                  this.displayError("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_SAVE_ERROR", response2.msg);
                }
              });
            }
          });
        } else {
          formBuilderService.updateDocument(data).then((response) => {
            if (response.status) {
              this.$emit("documents-updated");
            } else {
              this.displayError("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_SAVE_ERROR", response.msg);
            }
          });
        }
      }
    },
    updateDocumentSelectedValue(document2) {
      if (document2.id) {
        this.document.name[this.shortDefaultLang] = document2.label;
        this.selectModel({ target: { value: document2.id } }, this.current_document && this.current_document.id && this.current_document.id == document2.id ? this.current_document.mandatory : this.mandatory);
      } else {
        this.document.id = null;
        this.document.name[this.shortDefaultLang] = document2.label;
      }
    },
    hasImgFormat() {
      let hasImg = false;
      const imgExtensions = ["jpeg", "jpg", "png", "gif"];
      Object.keys(this.document.selectedTypes).forEach((extensions) => {
        if (this.document.selectedTypes[extensions] && imgExtensions.some((imgExt) => extensions.includes(imgExt))) {
          hasImg = true;
        }
      });
      this.hasImg = hasImg;
    },
    hasPDFFormat() {
      let hasPDF = false;
      Object.keys(this.document.selectedTypes).forEach((extensions) => {
        if (this.document.selectedTypes[extensions] && extensions.includes("pdf")) {
          hasPDF = true;
        }
      });
      this.hasPDF = hasPDF;
    },
    onHasSampleChange() {
      if (!this.hasSample) {
        this.newSample = "";
      }
    },
    onSampleFileInputChange(event) {
      const files = event.target.files || [];
      if (files.length > 0) {
        const allowedExtensions = ["pdf", "doc", "docx", "jpg", "jpeg", "png", "xls", "xlsx"];
        const fileExtension = files[0].name.split(".").pop().toLowerCase();
        if (!allowedExtensions.includes(fileExtension)) {
          Swal$1.fire({
            type: "warning",
            title: this.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_SAMPLE_WRONG_FORMAT"),
            reverseButtons: true,
            customClass: {
              title: "em-swal-title",
              confirmButton: "em-swal-confirm-button",
              actions: "em-swal-single-action"
            }
          });
          this.newSample = null;
          return false;
        }
        this.newSample = files[0];
      } else {
        this.newSample = null;
      }
    },
    getCurrentSample() {
      this.sampleFromDocumentId = this.document.id;
      if (this.document.id === null) {
        this.hasSample = false;
        this.currentSample = "";
      } else {
        formBuilderService.getDocumentSample(Number(this.document.id), Number(this.profile_id)).then((response) => {
          if (response.status && response.data) {
            this.hasSample = response.data.has_sample == 1;
            this.currentSample = this.hasSample ? response.data.sample_filepath : "";
          } else {
            this.hasSample = false;
            this.currentSample = "";
          }
        });
      }
    }
  },
  computed: {
    activeTabs() {
      return this.tabs.filter((tab) => {
        return tab.published;
      });
    },
    documentList() {
      return this.models.map((document2) => {
        return {
          id: document2.id,
          label: document2.name[this.shortDefaultLang]
        };
      });
    },
    isMandatory() {
      return this.document.mandatory == "1";
    },
    incSelectDefaultValue() {
      let defaultValue = null;
      if (this.current_document && (this.current_document.docid || this.current_document.id)) {
        defaultValue = this.current_document.docid ? this.current_document.docid : this.current_document.id;
      }
      return defaultValue;
    }
  },
  watch: {
    current_document(newValue) {
      if (newValue && (newValue.docid || newValue.id)) {
        if (this.models.length < 1) {
          this.getDocumentModels().then(() => {
            this.selectModel({
              target: {
                value: newValue.docid ? newValue.docid : newValue.id
              }
            }, newValue.mandatory ? newValue.mandatory : null);
          });
        } else {
          this.selectModel({
            target: {
              value: newValue.docid ? newValue.docid : newValue.id
            }
          }, newValue.mandatory ? newValue.mandatory : null);
        }
      }
    },
    document: {
      handler(newValue) {
        if (newValue.id !== this.sampleFromDocumentId) {
          this.getCurrentSample();
        }
      },
      deep: true
    }
  }
};
const _hoisted_1$a = { id: "form-builder-create-document" };
const _hoisted_2$a = { class: "tw-flex tw-items-center tw-justify-between tw-p-4" };
const _hoisted_3$a = { class: "tw-font-medium" };
const _hoisted_4$a = {
  id: "properties-tabs",
  class: "tw-flex tw-items-center tw-justify-between tw-p-4 tw-w-11/12"
};
const _hoisted_5$9 = ["onClick"];
const _hoisted_6$8 = { id: "properties" };
const _hoisted_7$8 = {
  id: "general-properties",
  class: "tw-p-4"
};
const _hoisted_8$7 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_9$6 = { class: "tw-font-medium" };
const _hoisted_10$5 = { class: "em-toggle" };
const _hoisted_11$3 = { class: "tw-mb-4" };
const _hoisted_12$3 = {
  for: "title",
  class: "tw-font-medium"
};
const _hoisted_13$3 = { class: "tw-mb-4" };
const _hoisted_14$2 = { class: "tw-font-medium" };
const _hoisted_15$2 = { class: "tw-mb-4" };
const _hoisted_16$2 = { class: "tw-font-medium" };
const _hoisted_17$2 = ["id", "value", "onUpdate:modelValue"];
const _hoisted_18$2 = ["for"];
const _hoisted_19$2 = { class: "tw-mb-4" };
const _hoisted_20$2 = {
  for: "nbmax",
  class: "tw-font-medium"
};
const _hoisted_21$1 = {
  id: "advanced-properties",
  class: "tw-p-4"
};
const _hoisted_22$1 = {
  id: "resolution",
  class: "tw-mb-4"
};
const _hoisted_23$1 = { class: "tw-font-medium" };
const _hoisted_24$1 = { class: "tw-w-full tw-flex tw-items-center tw-justify-between" };
const _hoisted_25$1 = { class: "tw-w-2/4 tw-mr-1" };
const _hoisted_26$1 = {
  for: "minResolutionW",
  class: "tw-font-normal"
};
const _hoisted_27$1 = ["max"];
const _hoisted_28$1 = { class: "tw-w-2/4 tw-ml-1" };
const _hoisted_29$1 = {
  for: "maxResolutionW",
  class: "tw-font-normal"
};
const _hoisted_30$1 = ["min"];
const _hoisted_31$1 = { class: "tw-font-medium" };
const _hoisted_32$1 = { class: "tw-w-full tw-flex tw-items-center tw-justify-between" };
const _hoisted_33$1 = { class: "tw-w-2/4 tw-mr-1" };
const _hoisted_34$1 = {
  for: "minResolutionH",
  class: "tw-font-normal"
};
const _hoisted_35$1 = ["max"];
const _hoisted_36$1 = { class: "tw-w-2/4 tw-ml-1" };
const _hoisted_37 = {
  for: "maxResolutionH",
  class: "tw-font-normal"
};
const _hoisted_38 = ["min"];
const _hoisted_39 = { id: "document-sample" };
const _hoisted_40 = { class: "tw-font-medium" };
const _hoisted_41 = { class: "tw-mb-4 tw-flex tw-items-center tw-justify-between" };
const _hoisted_42 = {
  for: "has-model",
  class: "tw-font-medium"
};
const _hoisted_43 = { class: "em-toggle" };
const _hoisted_44 = {
  key: 0,
  id: "current-sample",
  class: "tw-mb-4"
};
const _hoisted_45 = ["href"];
const _hoisted_46 = { key: 1 };
const _hoisted_47 = {
  for: "sample",
  id: "formbuilder_attachments_sample_upload"
};
const _hoisted_48 = { key: 0 };
const _hoisted_49 = { key: 1 };
const _hoisted_50 = { key: 2 };
const _hoisted_51 = { class: "tw-text-neutral-700" };
const _hoisted_52 = { class: "tw-p-4" };
function _sfc_render$a(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_incremental_select = resolveComponent("incremental-select");
  return openBlock(), createElementBlock("div", _hoisted_1$a, [
    createBaseVNode("div", _hoisted_2$a, [
      createBaseVNode("p", _hoisted_3$a, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_PROPERTIES")), 1),
      createBaseVNode("span", {
        class: "material-symbols-outlined tw-cursor-pointer",
        onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
      }, "close")
    ]),
    createBaseVNode("ul", _hoisted_4$a, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.activeTabs, (tab) => {
        return openBlock(), createElementBlock("li", {
          key: tab.id,
          class: normalizeClass([{ "is-active": tab.active, "tw-w-2/4": $options.activeTabs.length == 2, "tw-w-full": $options.activeTabs.length == 1 }, "tw-p-4 tw-cursor-pointer"]),
          onClick: ($event) => $options.selectTab(tab)
        }, toDisplayString(_ctx.translate(tab.label)), 11, _hoisted_5$9);
      }), 128))
    ]),
    createBaseVNode("div", _hoisted_6$8, [
      withDirectives(createBaseVNode("div", _hoisted_7$8, [
        createBaseVNode("div", _hoisted_8$7, [
          createBaseVNode("label", _hoisted_9$6, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_REQUIRED")), 1),
          createBaseVNode("div", _hoisted_10$5, [
            withDirectives(createBaseVNode("input", {
              type: "checkbox",
              class: "em-toggle-check",
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $options.isMandatory = $event),
              onClick: _cache[2] || (_cache[2] = (...args) => $options.toggleDocumentMandatory && $options.toggleDocumentMandatory(...args))
            }, null, 512), [
              [vModelCheckbox, $options.isMandatory]
            ]),
            _cache[14] || (_cache[14] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
            _cache[15] || (_cache[15] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
          ])
        ]),
        createBaseVNode("div", _hoisted_11$3, [
          createBaseVNode("label", _hoisted_12$3, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_NAME")), 1),
          $data.models.length > 0 ? (openBlock(), createBlock(_component_incremental_select, {
            key: 0,
            options: $options.documentList,
            defaultValue: $options.incSelectDefaultValue,
            locked: $props.mode != "create",
            onUpdateValue: $options.updateDocumentSelectedValue
          }, null, 8, ["options", "defaultValue", "locked", "onUpdateValue"])) : createCommentVNode("", true)
        ]),
        createBaseVNode("div", _hoisted_13$3, [
          createBaseVNode("label", _hoisted_14$2, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_DESCRIPTION")), 1),
          withDirectives(createBaseVNode("textarea", {
            id: "",
            name: "",
            rows: "5",
            "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.document.description[_ctx.shortDefaultLang] = $event)
          }, toDisplayString($data.document.description[_ctx.shortDefaultLang]), 513), [
            [vModelText, $data.document.description[_ctx.shortDefaultLang]]
          ])
        ]),
        createBaseVNode("div", _hoisted_15$2, [
          createBaseVNode("label", _hoisted_16$2, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_TYPES")), 1),
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.fileTypes, (filetype, index) => {
            return openBlock(), createElementBlock("div", {
              key: filetype.value,
              class: "tw-flex tw-items-center tw-mb-1 tw-items-start"
            }, [
              withDirectives(createBaseVNode("input", {
                type: "checkbox",
                name: "filetypes",
                style: { "height": "auto" },
                id: filetype.value,
                value: filetype.value,
                "onUpdate:modelValue": ($event) => $data.document.selectedTypes[filetype.value] = $event,
                onChange: _cache[4] || (_cache[4] = (...args) => $options.checkFileType && $options.checkFileType(...args))
              }, null, 40, _hoisted_17$2), [
                [vModelCheckbox, $data.document.selectedTypes[filetype.value]]
              ]),
              createBaseVNode("label", {
                for: filetype.value,
                class: "tw-font-normal !tw-mb-0 tw-ml-2"
              }, toDisplayString(_ctx.translate(filetype.title)) + " (" + toDisplayString(filetype.value) + ")", 9, _hoisted_18$2)
            ]);
          }), 128))
        ]),
        createBaseVNode("div", _hoisted_19$2, [
          createBaseVNode("label", _hoisted_20$2, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_NBMAX")), 1),
          withDirectives(createBaseVNode("input", {
            type: "number",
            id: "nbmax",
            class: "tw-w-full",
            "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.document.nbmax = $event)
          }, null, 512), [
            [vModelText, $data.document.nbmax]
          ])
        ])
      ], 512), [
        [vShow, $data.tabs[0].active]
      ]),
      withDirectives(createBaseVNode("div", _hoisted_21$1, [
        withDirectives(createBaseVNode("div", _hoisted_22$1, [
          createBaseVNode("label", _hoisted_23$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_IMAGE_WIDTH")), 1),
          createBaseVNode("div", _hoisted_24$1, [
            createBaseVNode("div", _hoisted_25$1, [
              createBaseVNode("label", _hoisted_26$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_MIN_RESOLUTION_PLACEHOLDER")), 1),
              withDirectives(createBaseVNode("input", {
                type: "number",
                id: "minResolutionW",
                class: "tw-w-full",
                "onUpdate:modelValue": _cache[6] || (_cache[6] = ($event) => $data.document.minResolution.width = $event),
                max: $data.document.maxResolution.width
              }, null, 8, _hoisted_27$1), [
                [vModelText, $data.document.minResolution.width]
              ])
            ]),
            createBaseVNode("div", _hoisted_28$1, [
              createBaseVNode("label", _hoisted_29$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_MAX_RESOLUTION_PLACEHOLDER")), 1),
              withDirectives(createBaseVNode("input", {
                type: "number",
                id: "maxResolutionW",
                class: "tw-w-full",
                "onUpdate:modelValue": _cache[7] || (_cache[7] = ($event) => $data.document.maxResolution.width = $event),
                min: $data.document.minResolution.width
              }, null, 8, _hoisted_30$1), [
                [vModelText, $data.document.maxResolution.width]
              ])
            ])
          ]),
          createBaseVNode("label", _hoisted_31$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_IMAGE_HEIGHT")), 1),
          createBaseVNode("div", _hoisted_32$1, [
            createBaseVNode("div", _hoisted_33$1, [
              createBaseVNode("label", _hoisted_34$1, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_MIN_RESOLUTION_PLACEHOLDER")), 1),
              withDirectives(createBaseVNode("input", {
                type: "number",
                id: "minResolutionH",
                class: "tw-w-full",
                "onUpdate:modelValue": _cache[8] || (_cache[8] = ($event) => $data.document.minResolution.height = $event),
                max: $data.document.maxResolution.height
              }, null, 8, _hoisted_35$1), [
                [vModelText, $data.document.minResolution.height]
              ])
            ]),
            createBaseVNode("div", _hoisted_36$1, [
              createBaseVNode("label", _hoisted_37, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_MAX_RESOLUTION_PLACEHOLDER")), 1),
              withDirectives(createBaseVNode("input", {
                type: "number",
                id: "maxResolutionH",
                class: "tw-w-full",
                "onUpdate:modelValue": _cache[9] || (_cache[9] = ($event) => $data.document.maxResolution.height = $event),
                min: $data.document.minResolution.height
              }, null, 8, _hoisted_38), [
                [vModelText, $data.document.maxResolution.height]
              ])
            ])
          ])
        ], 512), [
          [vShow, $data.hasImg]
        ]),
        createBaseVNode("div", _hoisted_39, [
          createBaseVNode("label", _hoisted_40, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_DOCUMENTS_MODEL_TITLE")), 1),
          createBaseVNode("div", _hoisted_41, [
            createBaseVNode("label", _hoisted_42, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_DOCUMENTS_GIVE_MODEL")), 1),
            createBaseVNode("div", _hoisted_43, [
              withDirectives(createBaseVNode("input", {
                type: "checkbox",
                id: "has-model",
                name: "has-model",
                class: "em-toggle-check",
                "onUpdate:modelValue": _cache[10] || (_cache[10] = ($event) => $data.hasSample = $event),
                onChange: _cache[11] || (_cache[11] = (...args) => $options.onHasSampleChange && $options.onHasSampleChange(...args))
              }, null, 544), [
                [vModelCheckbox, $data.hasSample]
              ]),
              _cache[16] || (_cache[16] = createBaseVNode("strong", { class: "b em-toggle-switch" }, null, -1)),
              _cache[17] || (_cache[17] = createBaseVNode("strong", { class: "b em-toggle-track" }, null, -1))
            ])
          ]),
          $data.hasSample && $data.currentSample ? (openBlock(), createElementBlock("div", _hoisted_44, [
            createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_DOCUMENTS_CURRENT_MODEL")), 1),
            createBaseVNode("a", {
              href: $data.currentSample,
              target: "_blank"
            }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_DOCUMENT_DOWNLOAD_SAMPLE")), 9, _hoisted_45)
          ])) : createCommentVNode("", true),
          $data.hasSample ? (openBlock(), createElementBlock("div", _hoisted_46, [
            createBaseVNode("label", _hoisted_47, [
              !$data.currentSample ? (openBlock(), createElementBlock("span", _hoisted_48, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_DOCUMENTS_MODEL_ADD")), 1)) : (openBlock(), createElementBlock("span", _hoisted_49, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_DOCUMENTS_MODEL_EDIT")), 1)),
              _cache[18] || (_cache[18] = createBaseVNode("span", { class: "material-symbols-outlined tw-ml-1 tw-text-neutral-900" }, "backup", -1))
            ]),
            createBaseVNode("input", {
              id: "sample",
              style: { "display": "none" },
              name: "sample",
              type: "file",
              ref: "sampleFileInput",
              onChange: _cache[12] || (_cache[12] = (...args) => $options.onSampleFileInputChange && $options.onSampleFileInputChange(...args)),
              accept: ".pdf,.doc,.docx,.png,.jpg,.xls,.xlsx"
            }, null, 544)
          ])) : createCommentVNode("", true),
          $data.newSample !== "" ? (openBlock(), createElementBlock("div", _hoisted_50, [
            createBaseVNode("p", _hoisted_51, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_DOCUMENTS_MODEL_FILE_UPLOADED")) + " : " + toDisplayString(this.newSample.name), 1)
          ])) : createCommentVNode("", true)
        ])
      ], 512), [
        [vShow, $data.tabs[1].active]
      ])
    ]),
    createBaseVNode("div", _hoisted_52, [
      createBaseVNode("button", {
        class: "tw-btn-primary",
        onClick: _cache[13] || (_cache[13] = (...args) => $options.saveDocument && $options.saveDocument(...args))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_ELEMENT_PROPERTIES_SAVE")), 1)
    ])
  ]);
}
const FormBuilderCreateDocument = /* @__PURE__ */ _export_sfc(_sfc_main$a, [["render", _sfc_render$a]]);
const FormBuilderDocumentFormats_vue_vue_type_style_index_0_lang = "";
const _sfc_main$9 = {
  components: {
    draggable: VueDraggableNext
  },
  props: {
    profile_id: {
      type: Number,
      required: true
    }
  },
  mixins: [formBuilderMixin],
  data() {
    return {
      formats: [],
      cloneFormat: null,
      search: ""
    };
  },
  created() {
    this.getFormats();
  },
  methods: {
    getFormats() {
      formService.getDocumentModels().then((response) => {
        if (response.status) {
          this.formats = response.data;
        }
      });
    },
    setCloneFormat(format) {
      this.cloneFormat = format;
    },
    onDragEnd(event) {
      const to = event.to;
      if (to === null || to.id === "") {
        return;
      }
      this.cloneFormat.mandatory = to.id == "required-documents" ? "1" : "0";
      this.$emit("open-create-document", this.cloneFormat);
    }
  },
  computed: {
    displayedFormats() {
      return this.formats.filter((format) => {
        return this.search.length > 0 && this.formats.length > 0 ? format.name[this.shortDefaultLang].toLowerCase().includes(this.search.toLowerCase()) : true;
      });
    }
  }
};
const _hoisted_1$9 = {
  id: "form-builder-document-formats",
  class: "!tw-pr-4"
};
const _hoisted_2$9 = {
  id: "form-builder-document-title",
  class: "tw-text-center tw-full tw-p-4"
};
const _hoisted_3$9 = ["placeholder"];
const _hoisted_4$9 = ["title"];
function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_draggable = resolveComponent("draggable");
  return openBlock(), createElementBlock("div", _hoisted_1$9, [
    createBaseVNode("p", _hoisted_2$9, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_FORMATS")), 1),
    $data.formats.length > 0 ? withDirectives((openBlock(), createElementBlock("input", {
      key: 0,
      id: "search",
      "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.search = $event),
      type: "text",
      class: "tw-mt-4 tw-full",
      placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_SEARCH_FORMAT")
    }, null, 8, _hoisted_3$9)), [
      [vModelText, $data.search]
    ]) : createCommentVNode("", true),
    createVNode(_component_draggable, {
      modelValue: $options.displayedFormats,
      "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $options.displayedFormats = $event),
      class: "draggables-list",
      group: { name: "form-builder-documents", pull: "clone", put: false },
      sort: false,
      clone: $options.setCloneFormat,
      onStart: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("dragging-element")),
      onEnd: $options.onDragEnd
    }, {
      default: withCtx(() => [
        createVNode(TransitionGroup, null, {
          default: withCtx(() => [
            (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedFormats, (format) => {
              return openBlock(), createElementBlock("div", {
                key: format.id,
                class: "tw-flex tw-justify-between tw-items-center draggable-element tw-mt-2 tw-mb-2 tw-p-4"
              }, [
                createBaseVNode("span", {
                  id: "format-name",
                  class: "tw-full tw-p-4",
                  title: format.name[_ctx.shortDefaultLang]
                }, toDisplayString(format.name[_ctx.shortDefaultLang]), 9, _hoisted_4$9),
                _cache[3] || (_cache[3] = createBaseVNode("span", { class: "material-symbols-outlined" }, " drag_indicator ", -1))
              ]);
            }), 128))
          ]),
          _: 1
        })
      ]),
      _: 1
    }, 8, ["modelValue", "clone", "onEnd"])
  ]);
}
const FormBuilderDocumentFormats = /* @__PURE__ */ _export_sfc(_sfc_main$9, [["render", _sfc_render$9]]);
const FormBuilderCreateModel_vue_vue_type_style_index_0_lang = "";
const _sfc_main$8 = {
  props: {
    page: {
      type: Number,
      required: true
    }
  },
  data() {
    return {
      modelTitle: "",
      models: [],
      alreadyExists: false,
      loading: false
    };
  },
  mounted() {
    this.getModels();
  },
  methods: {
    getModels() {
      formBuilderService.getModels().then((response) => {
        if (response.status) {
          this.models = response.data;
        }
      });
    },
    checkTitleNotAlreadyExists() {
      const modelExists = this.models.filter((model) => {
        return model.label[this.shortDefaultLang] === this.modelTitle.trim();
      });
      this.alreadyExists = modelExists.length > 0;
    },
    addFormModel() {
      this.loading = true;
      this.modelTitle = this.modelTitle.trim();
      if (this.modelTitle.length < 1) {
        Swal$1.fire({
          type: "warning",
          title: this.translate("COM_EMUNDUS_FORM_BUILDER_MODEL_MUST_HAVE_TITLE"),
          reverseButtons: true,
          customClass: {
            title: "em-swal-title",
            confirmButton: "em-swal-confirm-button",
            actions: "em-swal-single-action"
          }
        });
        this.loading = false;
        return;
      }
      const modelExists = this.models.filter((model) => {
        return model.label[this.shortDefaultLang] === this.modelTitle;
      });
      this.alreadyExists = modelExists.length > 0;
      if (!this.alreadyExists) {
        formBuilderService.addFormModel(this.page, this.modelTitle).then((response) => {
          if (!response.status) {
            this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_FAILURE"), response.msg);
          } else {
            Swal$1.fire({
              type: "success",
              title: this.translate("COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_SUCCESS"),
              reverseButtons: true,
              customClass: {
                title: "em-swal-title",
                confirmButton: "em-swal-confirm-button",
                actions: "em-swal-single-action"
              }
            });
          }
          this.loading = false;
          this.$emit("close");
        });
      } else {
        this.replaceFormModel(modelExists[0].id, this.modelTitle);
      }
    },
    replaceFormModel(model_id, label) {
      formBuilderService.addFormModel(this.page, label).then((response) => {
        if (!response.status) {
          this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_SAVE_AS_MODEL_FAILURE"), response.msg);
          this.$emit("close");
        } else {
          const modelIds = [model_id];
          formBuilderService.deleteFormModelFromId(modelIds).then(() => {
            this.$emit("close");
          });
        }
        if (this.loading) {
          this.loading = false;
        }
      });
    }
  },
  watch: {
    modelTitle: function(val, oldVal) {
      if (val != oldVal) {
        this.checkTitleNotAlreadyExists();
      }
    }
  }
};
const _hoisted_1$8 = {
  id: "form-builder-create-model",
  class: "tw-flex tw-flex-col tw-justify-between tw-w-full"
};
const _hoisted_2$8 = { class: "tw-w-full" };
const _hoisted_3$8 = { class: "tw-flex tw-items-center tw-justify-between tw-p-4" };
const _hoisted_4$8 = {
  key: 0,
  id: "model-properties",
  class: "tw-flex tw-flex-col tw-justify-start tw-p-4 tw-text-end"
};
const _hoisted_5$8 = { class: "em-main-500-color" };
const _hoisted_6$7 = {
  for: "page-model-title",
  class: "tw-mt-4 tw-text-end tw-w-full"
};
const _hoisted_7$7 = {
  key: 0,
  class: "tw-text-red-600"
};
const _hoisted_8$6 = {
  key: 1,
  class: "tw-w-full tw-flex tw-items-center tw-justify-center"
};
const _hoisted_9$5 = { class: "tw-flex tw-items-center tw-justify-between actions tw-w-full" };
const _hoisted_10$4 = ["disabled"];
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$8, [
    createBaseVNode("div", _hoisted_2$8, [
      createBaseVNode("div", _hoisted_3$8, [
        createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_MODEL_PROPERTIES")), 1),
        createBaseVNode("span", {
          class: "material-symbols-outlined tw-cursor-pointer",
          onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
        }, "close")
      ]),
      !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_4$8, [
        createBaseVNode("p", _hoisted_5$8, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_MODEL_PROPERTIES_INTRO")), 1),
        createBaseVNode("label", _hoisted_6$7, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_MODEL_INPUT_LABEL")), 1),
        withDirectives(createBaseVNode("input", {
          id: "page-model-title",
          class: "tw-w-full tw-mb-4",
          type: "text",
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.modelTitle = $event)
        }, null, 512), [
          [vModelText, $data.modelTitle]
        ]),
        $data.alreadyExists ? (openBlock(), createElementBlock("p", _hoisted_7$7, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_MODEL_WITH_SAME_TITLE_EXISTS")), 1)) : createCommentVNode("", true)
      ])) : (openBlock(), createElementBlock("div", _hoisted_8$6, _cache[3] || (_cache[3] = [
        createBaseVNode("div", { class: "em-loader" }, null, -1)
      ])))
    ]),
    createBaseVNode("div", _hoisted_9$5, [
      createBaseVNode("button", {
        class: normalizeClass(["tw-btn-primary tw-m-4", { "tw-text-white tw-bg-gray-500 tw-w-full tw-px-2 tw-py-3 tw-rounded-coordinator": $data.modelTitle.length < 1 || $data.loading }]),
        onClick: _cache[2] || (_cache[2] = ($event) => $options.addFormModel()),
        disabled: $data.modelTitle.length < 1 || $data.loading
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SECTION_PROPERTIES_SAVE")), 11, _hoisted_10$4)
    ])
  ]);
}
const FormBuilderCreateModel = /* @__PURE__ */ _export_sfc(_sfc_main$8, [["render", _sfc_render$8]]);
const FormBuilderRules_vue_vue_type_style_index_0_lang = "";
const _sfc_main$7 = {
  components: { Popover },
  props: {
    page: {
      type: Object,
      default: {}
    },
    mode: {
      type: String,
      default: "forms"
    }
  },
  mixins: [formBuilderMixin, mixin, errors],
  data() {
    return {
      rules: [],
      elements: [],
      keywords: "",
      loading: false
    };
  },
  setup() {
    const formBuilderStore = useFormBuilderStore();
    return {
      formBuilderStore
    };
  },
  created() {
    this.keywords = this.formBuilderStore.getRulesKeywords;
    if (this.keywords) {
      setTimeout(() => {
        this.highlight(this.keywords);
      }, 500);
    }
    if (this.page.id) {
      this.getConditions();
      formService.getPageObject(this.page.id).then((response) => {
        if (response.status && response.data != "") {
          this.fabrikPage = response.data;
        } else {
          this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"), this.translate(response.msg));
        }
        Object.entries(this.fabrikPage.Groups).forEach(([key, group]) => {
          Object.entries(group.elements).forEach(([key2, element]) => {
            if (!element.hidden) {
              this.elements.push(element);
            }
          });
        });
      });
    }
  },
  methods: {
    getConditions() {
      this.loading = true;
      formService.getConditions(this.page.id).then((response) => {
        if (response.status && response.data != "") {
          this.rules = response.data.conditions;
        } else {
          this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"), this.translate(response.msg));
        }
        this.loading = false;
      });
    },
    operator(state) {
      switch (state) {
        case "=":
          return this.translate("COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_EQUALS");
        case "!=":
          return this.translate("COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_NOT_EQUALS");
      }
    },
    getvalues(condition) {
      if (condition.options) {
        let index = condition.options.sub_values.findIndex((option) => option == condition.values);
        return condition.options.sub_labels[index];
      } else {
        return condition.values;
      }
    },
    elementOptions(action) {
      let options = [];
      if (action.params) {
        try {
          let action_params = JSON.parse(action.params);
          if (action.action == "define_repeat_group") {
            if (action_params.length > 0) {
              let min = action_params[0].minRepeat;
              let max = action_params[0].minRepeat;
              options.push(min);
              options.push(max);
            }
          } else {
            action_params.forEach((param) => {
              options.push(param.value);
            });
          }
        } catch (e) {
          return console.error(e);
        }
      }
      if (options.length > 0) {
        if (action.action == "define_repeat_group") {
          options = options.join(" " + this.translate("COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_AND") + " ");
        } else {
          options = options.join(", ");
        }
      } else {
        options = "";
      }
      return options;
    },
    deleteRule(rule) {
      Swal$1.fire({
        title: this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_DELETE_TITLE"),
        text: this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_DELETE_CONFIRM"),
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: this.translate("COM_EMUNDUS_FORM_BUILDER_DELETE_RULE"),
        cancelButtonText: this.translate("COM_EMUNDUS_FORM_BUILDER_CANCEL"),
        reverseButtons: true,
        customClass: {
          title: "em-swal-title",
          cancelButton: "em-swal-cancel-button",
          confirmButton: "em-swal-confirm-button"
        }
      }).then((result2) => {
        if (result2.value) {
          this.loading = true;
          formService.deleteRule(rule.id).then((response) => {
            if (response.status) {
              this.getConditions();
            } else {
              this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"), this.translate(response.msg));
            }
          });
        }
      });
    },
    publishRule(rule, state) {
      this.loading = true;
      formService.publishRule(rule.id, state).then((response) => {
        if (response.status) {
          this.getConditions();
        } else {
          this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"), this.translate(response.msg));
        }
      });
    },
    ruleLabel(rule, index) {
      if (rule.label && rule.label.trim() != "") {
        return rule.label;
      } else {
        return this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION") + (index + 1);
      }
    },
    groupedType(conditions) {
      let type = "AND";
      Object.values(conditions).forEach((condition) => {
        condition.forEach((cond) => {
          type = cond.group_type;
        });
      });
      return type;
    },
    highlight(searchTerm) {
      this.formBuilderStore.updateRulesKeywords(searchTerm);
      const conditions = document.querySelectorAll(".conditions-label");
      const actions = document.querySelectorAll(".actions-label");
      const elements = [...conditions, ...actions];
      elements.forEach((element) => {
        const text = element.innerText;
        let regex = new RegExp(`(${searchTerm})`, "gi");
        if (searchTerm && text.match(regex)) {
          const parts = text.split(regex);
          const highlightedText = parts.map(
            (part) => part.match(regex) ? `<span style="background-color: var(--em-yellow-1);">${part}</span>` : part
          ).join("");
          element.innerHTML = highlightedText;
        } else {
          element.innerHTML = text;
        }
      });
    },
    removeHighlight(field2) {
      let element = document.getElementById(field2);
      if (element) {
        element.innerHTML = element.innerText;
      }
    }
    /*cloneRule(rule)
    {
      this.loading = true;
      formService.cloneRule(rule.id).then(response => {
        if (response.status) {
          this.getConditions();
        } else {
          this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
        }
      });
    }*/
  },
  computed: {
    searchedRules() {
      if (this.keywords) {
        let elements_found = this.elements.filter((element) => element.label[useGlobalStore().getShortLang].toLowerCase().includes(this.keywords.toLowerCase()));
        return this.rules.filter((rule) => {
          let found = false;
          if (rule.label) {
            found = rule.label.toLowerCase().includes(this.keywords.toLowerCase());
          }
          if (!found) {
            Object.values(rule.conditions).forEach((grouped_conditions, key) => {
              grouped_conditions.forEach((condition, index) => {
                if (elements_found.find((element) => element.name == condition.field)) {
                  found = true;
                }
              });
            });
          }
          if (!found) {
            rule.actions.forEach((action, index) => {
              action.fields.forEach((field2) => {
                if (elements_found.find((element) => element.name == field2)) {
                  found = true;
                }
              });
            });
          }
          return found;
        });
      } else {
        return this.rules;
      }
    }
  },
  watch: {
    keywords: {
      // eslint-disable-next-line vue/no-arrow-functions-in-watch
      handler: function(val) {
        this.highlight(val);
      }
    }
  }
};
const _hoisted_1$7 = {
  id: "form-builder-rules",
  class: "tw-self-start tw-w-full"
};
const _hoisted_2$7 = { class: "tw-p-8" };
const _hoisted_3$7 = {
  key: 0,
  class: "tw-mb-3"
};
const _hoisted_4$7 = { class: "tw-relative tw-flex tw-items-center" };
const _hoisted_5$7 = ["placeholder"];
const _hoisted_6$6 = {
  key: 1,
  class: "tw-flex tw-flex-col tw-gap-3 tw-mt-3"
};
const _hoisted_7$6 = { key: 0 };
const _hoisted_8$5 = { class: "tw-flex tw-justify-between tw-items-start" };
const _hoisted_9$4 = { class: "tw-cursor-pointer" };
const _hoisted_10$3 = {
  style: { "list-style-type": "none", "margin": "0" },
  class: "tw-items-center tw-pl-0"
};
const _hoisted_11$2 = ["onClick"];
const _hoisted_12$2 = ["onClick"];
const _hoisted_13$2 = ["onClick"];
const _hoisted_14$1 = ["onClick"];
const _hoisted_15$1 = ["id"];
const _hoisted_16$1 = {
  key: 0,
  class: "tw-font-medium tw-ml-1 tw-mr-2 tw-mb-2"
};
const _hoisted_17$1 = { class: "tw-flex tw-items-center" };
const _hoisted_18$1 = {
  key: 0,
  class: "material-symbols-outlined !tw-text-2xl !tw-font-medium tw-mr-1 tw-text-black"
};
const _hoisted_19$1 = {
  key: 1,
  class: "tw-font-medium tw-ml-1 tw-mr-2"
};
const _hoisted_20$1 = {
  key: 2,
  class: "tw-font-medium tw-ml-1 tw-mr-2"
};
const _hoisted_21 = { class: "tw-leading-8" };
const _hoisted_22 = {
  key: 0,
  class: "tw-font-medium tw-mr-1"
};
const _hoisted_23 = { class: "conditions-label" };
const _hoisted_24 = { class: "tw-p-1 tw-rounded-md label-darkblue tw-ml-1 tw-mr-2" };
const _hoisted_25 = ["id"];
const _hoisted_26 = {
  key: 0,
  class: "material-symbols-outlined !tw-text-2xl !tw-font-medium tw-mr-3 tw-text-black"
};
const _hoisted_27 = {
  key: 1,
  class: "material-symbols-outlined !tw-text-2xl !tw-font-medium tw-mr-3 tw-text-black"
};
const _hoisted_28 = {
  key: 2,
  class: "material-symbols-outlined !tw-text-2xl !tw-font-medium tw-mr-3 tw-text-black"
};
const _hoisted_29 = {
  key: 3,
  class: "material-symbols-outlined !tw-text-2xl !tw-font-medium tw-mr-3 tw-text-black"
};
const _hoisted_30 = { class: "tw-font-medium tw-mr-1" };
const _hoisted_31 = { key: 0 };
const _hoisted_32 = {
  key: 1,
  class: "tw-mx-1 tw-font-medium"
};
const _hoisted_33 = { key: 2 };
const _hoisted_34 = { class: "actions-label" };
const _hoisted_35 = {
  key: 0,
  class: "material-symbols-outlined tw-self-end"
};
const _hoisted_36 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_popover = resolveComponent("popover");
  return openBlock(), createElementBlock("div", _hoisted_1$7, [
    createBaseVNode("div", _hoisted_2$7, [
      $data.rules.length > 0 ? (openBlock(), createElementBlock("h2", _hoisted_3$7, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULES") + this.$props.page.label), 1)) : createCommentVNode("", true),
      createBaseVNode("button", {
        id: "add-section",
        class: "tw-btn-primary tw-px-6 tw-py-3 tw-mb-4",
        onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("add-rule", "js"))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ADD_CONDITION")), 1),
      createBaseVNode("div", _hoisted_4$7, [
        withDirectives(createBaseVNode("input", {
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.keywords = $event),
          type: "text",
          class: "formbuilder-searchbar tw-bg-transparent",
          placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_SEARCH_CONDITION")
        }, null, 8, _hoisted_5$7), [
          [vModelText, $data.keywords]
        ]),
        $data.keywords !== "" ? (openBlock(), createElementBlock("button", {
          key: 0,
          type: "button",
          onClick: _cache[2] || (_cache[2] = ($event) => $data.keywords = ""),
          class: "tw-w-auto tw-absolute tw-right-3 tw-h-[16px]"
        }, _cache[4] || (_cache[4] = [
          createBaseVNode("span", { class: "material-symbols-outlined" }, "close", -1)
        ]))) : createCommentVNode("", true)
      ]),
      !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_6$6, [
        $options.searchedRules.length == 0 ? (openBlock(), createElementBlock("h5", _hoisted_7$6, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULES_NOT_FOUND")), 1)) : createCommentVNode("", true),
        (openBlock(true), createElementBlock(Fragment, null, renderList($options.searchedRules, (rule, index) => {
          return openBlock(), createElementBlock("div", {
            key: rule.id
          }, [
            createBaseVNode("div", {
              class: normalizeClass(["tw-rounded-lg tw-px-3 tw-py-4 tw-flex tw-flex-col tw-gap-6 tw-border tw-border-neutral-600", { "tw-bg-neutral-400": rule.published == 0, "tw-bg-white": rule.published == 1 }])
            }, [
              createBaseVNode("div", _hoisted_8$5, [
                createBaseVNode("h3", null, toDisplayString($options.ruleLabel(rule, index)), 1),
                createBaseVNode("div", _hoisted_9$4, [
                  createVNode(_component_popover, {
                    class: "custom-popover-arrow",
                    position: "left"
                  }, {
                    default: withCtx(() => [
                      createBaseVNode("ul", _hoisted_10$3, [
                        createBaseVNode("li", {
                          onClick: ($event) => _ctx.$emit("add-rule", "js", rule),
                          class: "tw-py-3 tw-px-4 tw-w-full hover:tw-bg-neutral-300"
                        }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_EDIT")), 9, _hoisted_11$2),
                        rule.published == 0 ? (openBlock(), createElementBlock("li", {
                          key: 0,
                          onClick: ($event) => $options.publishRule(rule, 1),
                          class: "tw-py-3 tw-px-4 tw-w-full hover:tw-bg-neutral-300"
                        }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_PUBLISH")), 9, _hoisted_12$2)) : createCommentVNode("", true),
                        rule.published == 1 ? (openBlock(), createElementBlock("li", {
                          key: 1,
                          onClick: ($event) => $options.publishRule(rule, 0),
                          class: "tw-py-3 tw-px-4 tw-w-full hover:tw-bg-neutral-300"
                        }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_UNPUBLISH")), 9, _hoisted_13$2)) : createCommentVNode("", true),
                        createBaseVNode("li", {
                          onClick: ($event) => $options.deleteRule(rule),
                          class: "tw-py-3 tw-px-4 tw-w-full tw-text-red-600 hover:tw-bg-neutral-300"
                        }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_DELETE")), 9, _hoisted_14$1)
                      ])
                    ]),
                    _: 2
                  }, 1024)
                ])
              ]),
              createBaseVNode("div", {
                id: "condition_" + rule.id,
                class: "tw-flex tw-flex-col tw-gap-2"
              }, [
                (openBlock(true), createElementBlock(Fragment, null, renderList(Object.values(rule.conditions), (grouped_condition, key) => {
                  return openBlock(), createElementBlock("div", null, [
                    key != 0 && grouped_condition.length > 1 ? (openBlock(), createElementBlock("p", _hoisted_16$1, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_" + rule.group)), 1)) : createCommentVNode("", true),
                    createBaseVNode("div", {
                      class: normalizeClass(["tw-flex tw-flex-col tw-gap-4", { "tw-bg-neutral-300 tw-rounded tw-p-2": grouped_condition.length > 1 }])
                    }, [
                      (openBlock(true), createElementBlock(Fragment, null, renderList(grouped_condition, (condition, condition_index) => {
                        return openBlock(), createElementBlock("div", _hoisted_17$1, [
                          condition_index == 0 && grouped_condition.length > 1 || key == 0 && grouped_condition.length == 1 ? (openBlock(), createElementBlock("span", _hoisted_18$1, "alt_route")) : createCommentVNode("", true),
                          condition_index != 0 && grouped_condition.length > 1 ? (openBlock(), createElementBlock("span", _hoisted_19$1, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_" + condition.group_type)), 1)) : createCommentVNode("", true),
                          key != 0 && grouped_condition.length == 1 ? (openBlock(), createElementBlock("span", _hoisted_20$1, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_" + rule.group)), 1)) : createCommentVNode("", true),
                          createBaseVNode("div", _hoisted_21, [
                            condition_index == 0 && grouped_condition.length > 1 || key == 0 && grouped_condition.length == 1 ? (openBlock(), createElementBlock("span", _hoisted_22, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_IF")), 1)) : createCommentVNode("", true),
                            createBaseVNode("span", _hoisted_23, toDisplayString(condition.elt_label), 1),
                            createBaseVNode("span", _hoisted_24, toDisplayString($options.operator(condition.state)), 1),
                            createBaseVNode("span", null, toDisplayString($options.getvalues(condition)), 1)
                          ])
                        ]);
                      }), 256))
                    ], 2)
                  ]);
                }), 256))
              ], 8, _hoisted_15$1),
              _cache[5] || (_cache[5] = createBaseVNode("hr", { class: "m-0" }, null, -1)),
              createBaseVNode("div", {
                id: "action_" + rule.id,
                class: "tw-flex tw-flex-col tw-gap-2"
              }, [
                (openBlock(true), createElementBlock(Fragment, null, renderList(rule.actions, (action) => {
                  return openBlock(), createElementBlock("div", {
                    key: action.id,
                    class: "tw-flex tw-items-center"
                  }, [
                    ["show", "show_options"].includes(action.action) ? (openBlock(), createElementBlock("span", _hoisted_26, "visibility")) : createCommentVNode("", true),
                    ["hide", "hide_options"].includes(action.action) ? (openBlock(), createElementBlock("span", _hoisted_27, "visibility_off")) : createCommentVNode("", true),
                    ["set_optional", "set_mandatory"].includes(action.action) ? (openBlock(), createElementBlock("span", _hoisted_28, "indeterminate_check_box")) : createCommentVNode("", true),
                    ["define_repeat_group"].includes(action.action) ? (openBlock(), createElementBlock("span", _hoisted_29, "repeat")) : createCommentVNode("", true),
                    createBaseVNode("div", null, [
                      createBaseVNode("span", _hoisted_30, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_ACTION_" + action.action.toUpperCase())), 1),
                      ["show_options", "hide_options"].includes(action.action) ? (openBlock(), createElementBlock("span", _hoisted_31, toDisplayString($options.elementOptions(action)), 1)) : createCommentVNode("", true),
                      ["show_options", "hide_options"].includes(action.action) ? (openBlock(), createElementBlock("span", _hoisted_32, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_OF_FIELD")), 1)) : createCommentVNode("", true),
                      ["define_repeat_group"].includes(action.action) ? (openBlock(), createElementBlock("span", _hoisted_33, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_BETWEEN") + " " + $options.elementOptions(action) + " " + _ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_REPETITIONS")), 1)) : createCommentVNode("", true),
                      createBaseVNode("span", _hoisted_34, toDisplayString(action.labels.join(", ")), 1)
                    ])
                  ]);
                }), 128))
              ], 8, _hoisted_25),
              rule.published == 0 ? (openBlock(), createElementBlock("span", _hoisted_35, "visibility_off")) : createCommentVNode("", true)
            ], 2)
          ]);
        }), 128))
      ])) : createCommentVNode("", true),
      $options.searchedRules.length > 5 ? (openBlock(), createElementBlock("button", {
        key: 2,
        id: "add-section",
        class: "tw-btn-primary tw-px-6 tw-py-3 tw-mt-4",
        onClick: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("add-rule", "js"))
      }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ADD_CONDITION")), 1)) : createCommentVNode("", true)
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_36)) : createCommentVNode("", true)
  ]);
}
const FormBuilderRules = /* @__PURE__ */ _export_sfc(_sfc_main$7, [["render", _sfc_render$7]]);
const rulesData = [
  {
    id: 1,
    value: "js",
    icon: "visibility",
    name: "COM_EMUNDUS_ONBOARD_RULES_JS",
    published: true
  }
];
const FormBuilderRulesList_vue_vue_type_style_index_0_lang = "";
const _sfc_main$6 = {
  components: {
    draggable: VueDraggableNext
  },
  mixins: [formBuilderMixin, errors],
  props: {
    form: {
      type: Object,
      required: false
    }
  },
  data() {
    return {
      rules: [],
      loading: false,
      keywords: ""
    };
  },
  created() {
    this.rules = this.getRules();
  },
  methods: {
    getRules() {
      return rulesData;
    },
    addRule(rule) {
      this.loading = true;
    }
  },
  computed: {
    publishedRules() {
      if (this.keywords) {
        return this.rules.filter((rule) => rule.published && this.translate(rule.name).toLowerCase().includes(this.keywords.toLowerCase()));
      } else {
        return this.rules.filter((rule) => rule.published);
      }
    }
  }
};
const _hoisted_1$6 = {
  id: "form-builder-rules-list",
  style: { "min-width": "260px" }
};
const _hoisted_2$6 = { class: "tw-mt-2" };
const _hoisted_3$6 = ["placeholder"];
const _hoisted_4$6 = ["onClick"];
const _hoisted_5$6 = { class: "form-builder-element tw-flex tw-items-center tw-justify-between tw-cursor-pointer tw-gap-3 tw-p-3" };
const _hoisted_6$5 = { class: "material-symbols-outlined" };
const _hoisted_7$5 = { class: "tw-w-full" };
const _hoisted_8$4 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", _hoisted_1$6, [
    createBaseVNode("div", _hoisted_2$6, [
      withDirectives(createBaseVNode("input", {
        "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.keywords = $event),
        type: "text",
        class: "formbuilder-searchbar",
        placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_SEARCH_RULE")
      }, null, 8, _hoisted_3$6), [
        [vModelText, $data.keywords]
      ]),
      (openBlock(true), createElementBlock(Fragment, null, renderList($options.publishedRules, (rule) => {
        return openBlock(), createElementBlock("div", {
          key: rule.id,
          class: "draggables-list",
          onClick: ($event) => _ctx.$emit("add-rule", rule.value)
        }, [
          createBaseVNode("div", _hoisted_5$6, [
            createBaseVNode("span", _hoisted_6$5, toDisplayString(rule.icon), 1),
            createBaseVNode("span", _hoisted_7$5, toDisplayString(_ctx.translate(rule.name)), 1),
            _cache[1] || (_cache[1] = createBaseVNode("span", { class: "material-symbols-outlined" }, "add_circle_outline", -1))
          ])
        ], 8, _hoisted_4$6);
      }), 128))
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_8$4)) : createCommentVNode("", true)
  ]);
}
const FormBuilderRulesList = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6]]);
var fabrik = {
  methods: {
    async getDatabasejoinOptions(table_name, column_name, value, concat_value, where_clause) {
      try {
        const response = await client$1().get("index.php?option=com_emundus&controller=form&task=getdatabasejoinoptions", {
          params: {
            table_name,
            column_name,
            value,
            concat_value,
            where_clause
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
  }
};
const _sfc_main$5 = {
  components: {
    Multiselect: script
  },
  props: {
    page: {
      type: Object,
      default: () => ({})
    },
    condition: {
      type: Object,
      default: () => ({})
    },
    index: {
      type: Number,
      default: 0
    },
    elements: {
      type: Array,
      default: () => []
    },
    multiple: {
      type: Boolean,
      default: false
    }
  },
  mixins: [formBuilderMixin, mixin, errors, fabrik],
  data() {
    return {
      loading: false,
      operators: [
        { id: 1, label: "COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_EQUALS", value: "=" },
        { id: 2, label: "COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_NOT_EQUALS", value: "!=" }
      ],
      options: [],
      options_plugins: ["dropdown", "databasejoin", "radiobutton", "checkbox"],
      conditionData: null
    };
  },
  created() {
    this.conditionData = this.condition;
  },
  mounted() {
    if (this.page.id) {
      this.conditionData.field = this.elements.find((element) => element.name === this.conditionData.field);
      if (this.conditionData.field) {
        this.defineOptions(this.conditionData.field);
      }
    }
    watch(
      () => this.conditionData.field,
      (val, oldVal) => {
        if (typeof oldVal === "object") {
          this.conditionData.values = "";
        }
        this.options = [];
        if (val) {
          this.defineOptions(val);
        }
      }
    );
  },
  methods: {
    labelTranslate({ label }) {
      return label ? label[useGlobalStore().getShortLang] : "";
    },
    defineOptions(val) {
      if (this.options_plugins.includes(val.plugin)) {
        if (val.plugin == "databasejoin") {
          this.loading = true;
          this.getDatabasejoinOptions(val.params.join_db_name, val.params.join_key_column, val.params.join_val_column, val.params.join_val_column_concat).then((response) => {
            if (response.status && response.data != "") {
              this.options = response.options;
              if (this.conditionData.values) {
                this.conditionData.values = this.options.find((option) => option.primary_key == this.conditionData.values);
              }
            } else {
              this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"), this.translate(response.msg));
            }
            this.loading = false;
          });
        } else {
          formBuilderService.getJTEXTA(val.params.sub_options.sub_labels).then((response) => {
            if (response) {
              val.params.sub_options.sub_labels.forEach((label, index) => {
                val.params.sub_options.sub_labels[index] = Object.values(response.data)[index];
              });
            }
            var ctr = 0;
            Object.values(val.params.sub_options.sub_values).forEach((option, key) => {
              let new_option = {
                primary_key: option,
                value: val.params.sub_options.sub_labels[key]
              };
              this.options.push(new_option);
              ctr++;
              if (ctr === Object.entries(val.params.sub_options).length) {
                if (this.conditionData.values) {
                  this.conditionData.values = this.options.find((option2) => option2.primary_key == this.conditionData.values);
                }
              }
            });
            this.loading = false;
          });
        }
      }
      if (val.plugin == "yesno") {
        this.options = [
          { primary_key: 0, value: this.translate("COM_EMUNDUS_FORMBUILDER_NO") },
          { primary_key: 1, value: this.translate("COM_EMUNDUS_FORMBUILDER_YES") }
        ];
        if (this.conditionData.values) {
          this.conditionData.values = this.options.find((option) => option.primary_key == this.conditionData.values);
        }
      }
    }
  },
  computed: {
    conditionLabel() {
      return `-- ${this.index + 1} --`;
    }
  }
};
const _hoisted_1$5 = { class: "tw-flex tw-justify-end tw-items-center" };
const _hoisted_2$5 = { class: "tw-flex" };
const _hoisted_3$5 = { class: "tw-mr-2 tw-mt-3 tw-font-bold" };
const _hoisted_4$5 = { class: "tw-flex tw-flex-col tw-ml-2 tw-w-full" };
const _hoisted_5$5 = { class: "tw-flex tw-items-center" };
const _hoisted_6$4 = { class: "tw-mt-4" };
const _hoisted_7$4 = { class: "tw-flex tw-items-center tw-gap-3" };
const _hoisted_8$3 = ["onClick"];
const _hoisted_9$3 = { class: "tw-mt-6" };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  return openBlock(), createElementBlock("div", {
    id: "form-builder-rules-js-condition",
    class: normalizeClass(["tw-self-start tw-w-full", { "tw-bg-neutral-300 tw-rounded tw-p-2": $props.multiple }])
  }, [
    createBaseVNode("div", _hoisted_1$5, [
      $props.index !== 0 ? (openBlock(), createElementBlock("button", {
        key: 0,
        type: "button",
        onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("remove-condition", $props.index)),
        class: "tw-w-auto"
      }, _cache[4] || (_cache[4] = [
        createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "close", -1)
      ]))) : createCommentVNode("", true)
    ]),
    createBaseVNode("div", _hoisted_2$5, [
      createBaseVNode("p", _hoisted_3$5, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_IF")), 1),
      createBaseVNode("div", _hoisted_4$5, [
        createBaseVNode("div", _hoisted_5$5, [
          createVNode(_component_multiselect, {
            modelValue: $data.conditionData.field,
            "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.conditionData.field = $event),
            label: "label_tag",
            "custom-label": $options.labelTranslate,
            "track-by": "name",
            options: $props.elements,
            multiple: false,
            taggable: false,
            "select-label": "",
            "selected-label": "",
            "deselect-label": "",
            placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELD"),
            "close-on-select": true,
            "clear-on-select": false,
            searchable: true,
            "allow-empty": true
          }, null, 8, ["modelValue", "custom-label", "options", "placeholder"])
        ]),
        createBaseVNode("div", _hoisted_6$4, [
          createBaseVNode("div", _hoisted_7$4, [
            (openBlock(true), createElementBlock(Fragment, null, renderList($data.operators, (operator) => {
              return openBlock(), createElementBlock("span", {
                key: operator.id,
                class: normalizeClass(["tw-cursor-pointer tw-p-2 tw-rounded-lg tw-ml-1 tw-border tw-border-neutral-500", { "label-darkblue": $data.conditionData.state == operator.value }]),
                onClick: ($event) => $data.conditionData.state = operator.value
              }, toDisplayString(_ctx.translate(operator.label)), 11, _hoisted_8$3);
            }), 128))
          ]),
          createBaseVNode("div", _hoisted_9$3, [
            $data.conditionData.field && ($data.options_plugins.includes($data.conditionData.field.plugin) || $data.conditionData.field.plugin == "yesno") ? (openBlock(), createBlock(_component_multiselect, {
              key: 0,
              modelValue: $data.conditionData.values,
              "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.conditionData.values = $event),
              label: "value",
              "track-by": "primary_key",
              options: $data.options,
              multiple: false,
              taggable: false,
              "select-label": "",
              "selected-label": "",
              "deselect-label": "",
              placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELD"),
              "close-on-select": true,
              "clear-on-select": false,
              searchable: true,
              "allow-empty": true
            }, null, 8, ["modelValue", "options", "placeholder"])) : $data.conditionData.field ? withDirectives((openBlock(), createElementBlock("input", {
              key: 1,
              "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.conditionData.values = $event)
            }, null, 512)), [
              [vModelText, $data.conditionData.values]
            ]) : createCommentVNode("", true)
          ])
        ])
      ])
    ])
  ], 2);
}
const FormBuilderRulesJsCondition = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5]]);
const _sfc_main$4 = {
  components: {
    FormBuilderRulesJsCondition
  },
  props: {
    page: {
      type: Object,
      default: {}
    },
    conditions: {
      type: Array,
      default: () => []
    },
    index: {
      type: Number,
      default: 0
    },
    elements: {
      type: Array,
      default: () => []
    }
  },
  mixins: [formBuilderMixin, mixin, errors, fabrik],
  data() {
    return {
      loading: false,
      group_types: [
        { id: 1, label: "COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_OR", value: "OR" },
        { id: 2, label: "COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_AND", value: "AND" }
      ],
      conditions_group: "OR"
    };
  },
  mounted() {
    if (this.conditions.length > 1) {
      this.conditions.forEach((condition) => {
        this.conditions_group = condition.group_type;
      });
    }
  },
  methods: {
    removeCondition(index) {
      this.conditions.splice(index, 1);
    },
    labelTranslate({ label }) {
      return label ? label[useGlobalStore().getShortLang] : "";
    }
  },
  computed: {
    conditionLabel() {
      return `-- ${this.index + 1} --`;
    }
  },
  watch: {
    conditions_group: {
      handler: function(val) {
        this.conditions.forEach((condition) => {
          condition.group_type = val;
        });
      },
      deep: true
    }
  }
};
const _hoisted_1$4 = {
  id: "form-builder-rules-js-conditions",
  class: "tw-self-start tw-w-full"
};
const _hoisted_2$4 = { class: "tw-flex tw-justify-between tw-items-center" };
const _hoisted_3$4 = { class: "tw-flex tw-items-center tw-gap-2" };
const _hoisted_4$4 = {
  key: 0,
  class: "tw-flex tw-items-center tw-gap-3"
};
const _hoisted_5$4 = ["onClick"];
const _hoisted_6$3 = { class: "tw-flex tw-flex-col tw-gap-2 tw-mt-4" };
const _hoisted_7$3 = {
  key: 0,
  class: "tw-font-medium tw-ml-1 tw-mr-2"
};
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_rules_js_condition = resolveComponent("form-builder-rules-js-condition");
  return openBlock(), createElementBlock("div", _hoisted_1$4, [
    createBaseVNode("div", _hoisted_2$4, [
      createBaseVNode("h3", null, toDisplayString($options.conditionLabel), 1),
      createBaseVNode("div", _hoisted_3$4, [
        $props.conditions.length > 1 ? (openBlock(), createElementBlock("div", _hoisted_4$4, [
          (openBlock(true), createElementBlock(Fragment, null, renderList($data.group_types, (type) => {
            return openBlock(), createElementBlock("span", {
              key: type.id,
              class: normalizeClass(["tw-cursor-pointer tw-p-2 tw-rounded-lg tw-ml-1 tw-border tw-border-neutral-500 tw-w-[50px] tw-flex tw-justify-center", { "label-darkblue": $data.conditions_group == type.value }]),
              onClick: ($event) => $data.conditions_group = type.value
            }, toDisplayString(_ctx.translate(type.label)), 11, _hoisted_5$4);
          }), 128))
        ])) : createCommentVNode("", true),
        $props.index !== 0 ? (openBlock(), createElementBlock("button", {
          key: 1,
          type: "button",
          onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("remove-condition", $props.index)),
          class: "tw-w-auto"
        }, _cache[2] || (_cache[2] = [
          createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "close", -1)
        ]))) : createCommentVNode("", true)
      ])
    ]),
    createBaseVNode("div", _hoisted_6$3, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($props.conditions, (condition, condition_key) => {
        return openBlock(), createElementBlock("div", {
          class: "tw-ml-4 tw-flex tw-flex-col tw-gap-2",
          key: condition_key
        }, [
          $props.conditions.length > 1 && condition_key != 0 ? (openBlock(), createElementBlock("span", _hoisted_7$3, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_CONDITION_" + $data.conditions_group)), 1)) : createCommentVNode("", true),
          createVNode(_component_form_builder_rules_js_condition, {
            elements: $props.elements,
            index: condition_key,
            condition,
            onRemoveCondition: $options.removeCondition,
            page: $props.page,
            multiple: Object.values($props.conditions).length > 1
          }, null, 8, ["elements", "index", "condition", "onRemoveCondition", "page", "multiple"])
        ]);
      }), 128))
    ]),
    createBaseVNode("button", {
      type: "button",
      onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("add-condition", $props.index)),
      class: "tw-btn-tertiary tw-mt-2 !tw-w-max tw-float-right"
    }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_CONDITION")), 1)
  ]);
}
const FormBuilderRulesJsConditions = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4]]);
const _sfc_main$3 = {
  components: {
    Multiselect: script
  },
  props: {
    page: {
      type: Object,
      default: {}
    },
    action: {
      type: Object,
      default: {}
    },
    index: {
      type: Number,
      default: 0
    },
    elements: {
      type: Array,
      default: []
    }
  },
  mixins: [formBuilderMixin, mixin, errors, fabrik],
  data() {
    return {
      loading: false,
      actions: [
        { id: 1, label: "COM_EMUNDUS_FORMBUILDER_RULE_ACTION_SHOW", value: "show", multiple: true },
        { id: 2, label: "COM_EMUNDUS_FORMBUILDER_RULE_ACTION_HIDE", value: "hide", multiple: true },
        { id: 3, label: "COM_EMUNDUS_FORMBUILDER_RULE_ACTION_SHOW_OPTIONS", value: "show_options", multiple: false },
        { id: 4, label: "COM_EMUNDUS_FORMBUILDER_RULE_ACTION_HIDE_OPTIONS", value: "hide_options", multiple: false },
        { id: 5, label: "COM_EMUNDUS_FORMBUILDER_RULE_ACTION_MANDATORY", value: "set_mandatory", multiple: true },
        { id: 6, label: "COM_EMUNDUS_FORMBUILDER_RULE_ACTION_OPTIONAL", value: "set_optional", multiple: true },
        { id: 7, label: "COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_GROUP", value: "define_repeat_group", multiple: false }
      ],
      options: [],
      options_plugins: ["dropdown", "databasejoin", "radiobutton", "checkbox"],
      minRepeat: 1,
      maxRepeat: 0
    };
  },
  created() {
    if (this.page.id) {
      if (this.$props.action.params) {
        this.$props.action.params.forEach((param, index) => {
          JSON.parse(param);
          this.$props.action.params = JSON.parse(param);
        });
      }
      this.$props.action.fields.forEach((field2, index) => {
        this.$props.action.fields[index] = this.elements.find((element) => element.name === field2);
        if (this.action.action == "show_options" || this.action.action == "hide_options") {
          this.defineOptions(this.$props.action.fields[index]);
        }
      });
    }
  },
  methods: {
    labelTranslate({ label }) {
      return label[useGlobalStore().getShortLang];
    },
    defineOptions(val) {
      if (["show_options", "hide_options"].includes(this.action.action)) {
        if (this.options_plugins.includes(val.plugin)) {
          if (val.plugin == "databasejoin") {
            this.getDatabasejoinOptions(val.params.join_db_name, val.params.join_key_column, val.params.join_val_column, val.params.join_val_column_concat).then((response) => {
              if (response.status && response.data != "") {
                this.options = response.options;
              } else {
                this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"), this.translate(response.msg));
              }
            });
          } else {
            formBuilderService.getJTEXTA(val.params.sub_options.sub_labels).then((response) => {
              if (response) {
                val.params.sub_options.sub_labels.forEach((label, index) => {
                  val.params.sub_options.sub_labels[index] = Object.values(response.data)[index];
                });
              }
              Object.values(val.params.sub_options.sub_values).forEach((option, key) => {
                let new_option = {
                  primary_key: option,
                  value: val.params.sub_options.sub_labels[key]
                };
                this.options.push(new_option);
              });
            });
          }
        }
      }
    }
  },
  computed: {
    actionLabel() {
      return `Action n°${this.index + 1}`;
    },
    actionMultiple() {
      return this.actions.find((action) => action.value === this.action.action).multiple;
    },
    availableElements() {
      if (!this.actionMultiple) {
        if (this.action.action == "define_repeat_group") {
          return Object.values(this.page.Groups).filter((group) => group.repeat_group == true);
        } else {
          return this.elements.filter((element) => ["databasejoin", "dropdown", "radiobutton", "checkbox"].includes(element.plugin));
        }
      } else {
        return this.elements;
      }
    },
    multiselectTrackBy() {
      return this.action.action == "define_repeat_group" ? "group_id" : "name";
    }
  },
  watch: {
    "action.action": {
      handler: function(val, oldVal) {
        if (["show", "hide"].includes(oldVal) && ["show_options", "hide_options"].includes(val) && this.action.fields.length > 1) {
          this.action.fields = [];
        } else if (["show", "hide"].includes(oldVal) && ["show_options", "hide_options"].includes(val) && this.action.fields.length == 1) {
          this.defineOptions(this.action.fields[0]);
        }
      },
      deep: true
    },
    "action.fields": {
      handler: function(val, oldVal) {
        if (val) {
          this.defineOptions(val);
        }
      },
      deep: true
    },
    minRepeat: function(val) {
      if (this.$props.action.params[0] == void 0) {
        this.$props.action.params[0] = {};
      }
      this.$props.action.params[0].minRepeat = val;
    },
    maxRepeat: function(val) {
      if (this.$props.action.params[0] == void 0) {
        this.$props.action.params[0] = {};
      }
      this.$props.action.params[0].maxRepeat = val;
    }
  }
};
const _hoisted_1$3 = {
  id: "form-builder-rules-js-action",
  class: "tw-self-start tw-w-full"
};
const _hoisted_2$3 = { class: "tw-flex tw-justify-between tw-items-center" };
const _hoisted_3$3 = {
  key: 0,
  class: "tw-mt-4 tw-flex tw-ml-4"
};
const _hoisted_4$3 = { class: "tw-mr-4 tw-mt-3 tw-font-bold" };
const _hoisted_5$3 = { class: "tw-flex tw-flex-col tw-w-full tw-ml-2" };
const _hoisted_6$2 = { class: "tw-flex tw-items-center" };
const _hoisted_7$2 = { class: "form-group tw-w-full" };
const _hoisted_8$2 = ["value"];
const _hoisted_9$2 = { class: "mt-4" };
const _hoisted_10$2 = {
  key: 0,
  class: "tw-mt-4"
};
const _hoisted_11$1 = { key: 1 };
const _hoisted_12$1 = { class: "tw-mt-4" };
const _hoisted_13$1 = { class: "tw-mt-4" };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_multiselect = resolveComponent("multiselect");
  return openBlock(), createElementBlock("div", _hoisted_1$3, [
    createBaseVNode("div", _hoisted_2$3, [
      createBaseVNode("h2", null, toDisplayString($options.actionLabel), 1),
      $props.index !== 0 ? (openBlock(), createElementBlock("button", {
        key: 0,
        type: "button",
        onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("remove-action", $props.index)),
        class: "tw-w-auto"
      }, _cache[6] || (_cache[6] = [
        createBaseVNode("span", { class: "material-symbols-outlined tw-text-red-600" }, "close", -1)
      ]))) : createCommentVNode("", true)
    ]),
    !$data.loading ? (openBlock(), createElementBlock("div", _hoisted_3$3, [
      createBaseVNode("p", _hoisted_4$3, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_THEN")), 1),
      createBaseVNode("div", _hoisted_5$3, [
        createBaseVNode("div", _hoisted_6$2, [
          createBaseVNode("div", _hoisted_7$2, [
            withDirectives(createBaseVNode("select", {
              class: "tw-w-full",
              "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $props.action.action = $event)
            }, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($data.actions, (actionOpt) => {
                return openBlock(), createElementBlock("option", {
                  value: actionOpt.value
                }, toDisplayString(_ctx.translate(actionOpt.label)), 9, _hoisted_8$2);
              }), 256))
            ], 512), [
              [vModelSelect, $props.action.action]
            ])
          ])
        ]),
        createBaseVNode("div", _hoisted_9$2, [
          createBaseVNode("div", null, [
            (openBlock(), createBlock(_component_multiselect, {
              modelValue: $props.action.fields,
              "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $props.action.fields = $event),
              label: "label_tag",
              "custom-label": $options.labelTranslate,
              "track-by": $options.multiselectTrackBy,
              options: $options.availableElements,
              multiple: $options.actionMultiple,
              taggable: false,
              "select-label": "",
              "selected-label": "",
              "deselect-label": "",
              placeholder: $options.actionMultiple ? _ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELDS") : _ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELD"),
              "close-on-select": !$options.actionMultiple,
              "clear-on-select": false,
              searchable: true,
              "allow-empty": true,
              key: $props.action.action
            }, null, 8, ["modelValue", "custom-label", "track-by", "options", "multiple", "placeholder", "close-on-select"]))
          ]),
          ["show_options", "hide_options"].includes($props.action.action) && $data.options.length > 0 ? (openBlock(), createElementBlock("div", _hoisted_10$2, [
            createVNode(_component_multiselect, {
              modelValue: $props.action.params,
              "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $props.action.params = $event),
              label: "value",
              "track-by": "primary_key",
              options: $data.options,
              multiple: true,
              taggable: false,
              "select-label": "",
              "selected-label": "",
              "deselect-label": "",
              placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_OPTIONS"),
              "close-on-select": false,
              "clear-on-select": false,
              searchable: true,
              "allow-empty": true
            }, null, 8, ["modelValue", "options", "placeholder"])
          ])) : createCommentVNode("", true),
          $props.action.action == "define_repeat_group" ? (openBlock(), createElementBlock("div", _hoisted_11$1, [
            createBaseVNode("div", _hoisted_12$1, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_GROUP_MIN")), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.minRepeat = $event)
              }, null, 512), [
                [vModelText, $data.minRepeat]
              ])
            ]),
            createBaseVNode("div", _hoisted_13$1, [
              createBaseVNode("label", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_ACTION_DEFINE_REPEAT_GROUP_MAX")), 1),
              withDirectives(createBaseVNode("input", {
                type: "text",
                "onUpdate:modelValue": _cache[5] || (_cache[5] = ($event) => $data.maxRepeat = $event)
              }, null, 512), [
                [vModelText, $data.maxRepeat]
              ])
            ])
          ])) : createCommentVNode("", true)
        ])
      ])
    ])) : createCommentVNode("", true)
  ]);
}
const FormBuilderRulesJsAction = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3]]);
const _sfc_main$2 = {
  components: { FormBuilderRulesJsAction, FormBuilderRulesJsConditions },
  props: {
    page: {
      type: Object,
      default: {}
    },
    elements: {
      type: Array,
      default: []
    },
    rule: {
      type: Object,
      default: {}
    }
  },
  mixins: [formBuilderMixin, mixin, errors],
  data() {
    return {
      conditions: [],
      actions: [],
      group: "OR",
      label: "",
      loading: false
    };
  },
  mounted() {
    if (this.page.id) {
      if (this.rule !== null && Object.values(this.rule.conditions).length > 0) {
        this.conditions = Object.values(this.rule.conditions);
      } else {
        let first_condition = [];
        first_condition.push({
          field: "",
          values: "",
          state: "=",
          group_type: "OR"
        });
        this.conditions.push(first_condition);
      }
      if (this.rule !== null && this.rule.actions.length > 0) {
        this.actions = this.rule.actions;
      } else {
        this.actions.push({
          action: "show",
          fields: [],
          params: []
        });
      }
      if (this.rule !== null) {
        this.group = this.rule.group;
        this.label = this.rule.label;
      }
    }
  },
  methods: {
    addCondition(index) {
      this.conditions[index].push({
        field: "",
        values: "",
        state: "=",
        group_type: "OR"
      });
    },
    addGroupedCondition() {
      let grouped_condition = [];
      grouped_condition.push({
        field: "",
        values: "",
        state: "=",
        group_type: "OR"
      });
      this.conditions.push(grouped_condition);
    },
    addAction() {
      this.actions.push({
        action: "show",
        fields: []
      });
    },
    removeCondition(index) {
      this.conditions = this.conditions.filter((condition, i) => i !== index);
    },
    removeAction(index) {
      this.actions = this.actions.filter((condition, i) => i !== index);
    },
    saveRule() {
      let conditions_post = [];
      let actions_post = [];
      this.conditions.forEach((grouped_condition) => {
        let tmp_conditions = [];
        grouped_condition.forEach((condition) => {
          if (condition.field && condition.values) {
            tmp_conditions.push({
              field: condition.field.name,
              values: typeof condition.values === "object" ? condition.values.primary_key : condition.values,
              state: condition.state,
              group_type: condition.group_type
            });
          }
        });
        conditions_post.push(tmp_conditions);
      });
      this.actions.forEach((action) => {
        if (action.fields) {
          let fields = [];
          if (action.fields.length > 1) {
            action.fields.forEach((field2) => {
              fields.push(field2.name);
            });
          } else {
            if (typeof action.fields[0] !== "undefined") {
              fields.push(action.fields[0].name);
            } else {
              fields.push(action.fields.name);
            }
          }
          actions_post.push({
            action: action.action,
            fields,
            params: action.params
          });
        }
      });
      if (conditions_post.length == 0) {
        this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ERROR"), this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ERROR_CONDITION_EMPTY"));
        return;
      }
      if (actions_post.length == 0) {
        this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ERROR"), this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ERROR_ACTION_EMPTY"));
        return;
      }
      if (this.rule !== null) {
        formService.editRule(this.rule.id, conditions_post, actions_post, this.group, this.label).then((response) => {
          if (response.status) {
            Swal$1.fire({
              title: this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_EDIT_SUCCESS"),
              type: "success",
              showConfirmButton: false,
              customClass: {
                title: "em-swal-title",
                actions: "em-swal-single-action"
              },
              timer: 2e3
            }).then(() => {
              this.$emit("close-rule-add-js");
            });
          } else {
            this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ERROR"), this.translate(response.msg));
          }
        });
      } else {
        formService.addRule(this.page.id, conditions_post, actions_post, this.group, this.label).then((response) => {
          if (response.status) {
            Swal$1.fire({
              title: this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_SUCCESS"),
              icon: "success",
              showConfirmButton: false,
              customClass: {
                title: "em-swal-title",
                actions: "em-swal-single-action"
              },
              timer: 2e3
            }).then(() => {
              this.$emit("close-rule-add-js");
            });
          } else {
            this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ERROR"), this.translate(response.msg));
          }
        });
      }
    }
  },
  computed: {
    titleLabel() {
      return this.rule ? this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_EDIT_JS") : this.translate("COM_EMUNDUS_FORM_BUILDER_RULE_ADD_JS");
    }
  }
};
const _hoisted_1$2 = {
  id: "form-builder-rules-js",
  class: "tw-self-start tw-w-full"
};
const _hoisted_2$2 = ["placeholder"];
const _hoisted_3$2 = { id: "form-builder-rules-js-conditions-block" };
const _hoisted_4$2 = { class: "tw-flex tw-justify-end" };
const _hoisted_5$2 = {
  key: 0,
  class: "tw-flex tw-items-center tw-gap-2"
};
const _hoisted_6$1 = { class: "tw-font-bold" };
const _hoisted_7$1 = { value: "OR" };
const _hoisted_8$1 = { value: "AND" };
const _hoisted_9$1 = { id: "form-builder-rules-js-actions-block" };
const _hoisted_10$1 = { class: "tw-flex tw-justify-end" };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_rules_js_conditions = resolveComponent("form-builder-rules-js-conditions");
  const _component_form_builder_rules_js_action = resolveComponent("form-builder-rules-js-action");
  return openBlock(), createElementBlock("div", _hoisted_1$2, [
    createBaseVNode("h2", null, toDisplayString($options.titleLabel), 1),
    withDirectives(createBaseVNode("input", {
      class: "tw-mt-2 tw-mb-4",
      "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.label = $event),
      placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_NAME")
    }, null, 8, _hoisted_2$2), [
      [vModelText, $data.label]
    ]),
    createBaseVNode("div", _hoisted_3$2, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.conditions, (grouped_condition, index) => {
        return openBlock(), createElementBlock("div", {
          key: "condition-" + index,
          class: "tw-mt-2 tw-rounded-lg tw-bg-white tw-px-3 tw-py-4 tw-flex tw-flex-col tw-gap-6"
        }, [
          createVNode(_component_form_builder_rules_js_conditions, {
            onAddCondition: $options.addCondition,
            elements: $props.elements,
            index,
            conditions: grouped_condition,
            onRemoveCondition: $options.removeCondition,
            page: $props.page
          }, null, 8, ["onAddCondition", "elements", "index", "conditions", "onRemoveCondition", "page"])
        ]);
      }), 128)),
      createBaseVNode("div", _hoisted_4$2, [
        createBaseVNode("button", {
          type: "button",
          onClick: _cache[1] || (_cache[1] = ($event) => $options.addGroupedCondition()),
          class: "tw-btn-tertiary tw-mt-2 tw-w-auto"
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_CONDITION_GROUP")), 1)
      ])
    ]),
    $data.conditions.length > 1 ? (openBlock(), createElementBlock("div", _hoisted_5$2, [
      createBaseVNode("p", _hoisted_6$1, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_IF")), 1),
      withDirectives(createBaseVNode("select", {
        class: "tw-w-full",
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.group = $event)
      }, [
        createBaseVNode("option", _hoisted_7$1, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_GROUP_OR")), 1),
        createBaseVNode("option", _hoisted_8$1, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_RULE_GROUP_AND")), 1)
      ], 512), [
        [vModelSelect, $data.group]
      ])
    ])) : createCommentVNode("", true),
    createBaseVNode("div", _hoisted_9$1, [
      (openBlock(true), createElementBlock(Fragment, null, renderList($data.actions, (action, index) => {
        return openBlock(), createElementBlock("div", {
          key: index,
          class: "tw-mt-2 tw-rounded-lg tw-bg-white tw-px-3 tw-py-4 tw-flex tw-flex-col tw-gap-6"
        }, [
          createVNode(_component_form_builder_rules_js_action, {
            elements: $props.elements,
            index,
            action,
            onRemoveAction: $options.removeAction,
            page: $props.page
          }, null, 8, ["elements", "index", "action", "onRemoveAction", "page"])
        ]);
      }), 128)),
      createBaseVNode("div", _hoisted_10$1, [
        createBaseVNode("button", {
          type: "button",
          onClick: _cache[3] || (_cache[3] = ($event) => $options.addAction()),
          class: "tw-btn-tertiary tw-mt-2 tw-w-auto"
        }, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PARAMS_ADD_REPEATABLE_ACTION")), 1)
      ])
    ]),
    _cache[5] || (_cache[5] = createBaseVNode("hr", null, null, -1)),
    createBaseVNode("button", {
      class: "tw-mt-4 tw-btn-primary tw-w-auto tw-float-right",
      onClick: _cache[4] || (_cache[4] = (...args) => $options.saveRule && $options.saveRule(...args))
    }, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_SAVE")), 1)
  ]);
}
const FormBuilderRulesJs = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2]]);
const _sfc_main$1 = {
  components: { FormBuilderRulesJs },
  props: {
    page: {
      type: Object,
      default: {}
    },
    mode: {
      type: String,
      default: "forms"
    },
    type: {
      type: String,
      default: "js"
    },
    rule: {
      type: Object,
      default: {}
    }
  },
  mixins: [formBuilderMixin, mixin, errors],
  data() {
    return {
      fabrikPage: {},
      elements: [],
      loading: false
    };
  },
  mounted() {
    if (this.page.id) {
      this.loading = true;
      formService.getPageObject(this.page.id).then((response) => {
        if (response.status && response.data != "") {
          this.fabrikPage = response.data;
        } else {
          this.displayError(this.translate("COM_EMUNDUS_FORM_BUILDER_ERROR"), this.translate(response.msg));
        }
        Object.entries(this.fabrikPage.Groups).forEach(([key, group]) => {
          Object.entries(group.elements).forEach(([key2, element]) => {
            if (!element.hidden) {
              this.elements.push(element);
            }
          });
        });
        this.loading = false;
      });
    }
  },
  methods: {}
};
const _hoisted_1$1 = {
  id: "form-builder-rules-add",
  class: "tw-self-start tw-w-full"
};
const _hoisted_2$1 = { class: "tw-p-8" };
const _hoisted_3$1 = { class: "tw-flex tw-flex-col tw-gap-3" };
const _hoisted_4$1 = ["title"];
const _hoisted_5$1 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_rules_js = resolveComponent("form-builder-rules-js");
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", _hoisted_2$1, [
      createBaseVNode("div", _hoisted_3$1, [
        createBaseVNode("div", {
          class: "tw-flex tw-items-center tw-gap-1 tw-cursor-pointer tw-mb-2",
          title: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_GO_BACK"),
          onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close-rule-add"))
        }, [
          _cache[2] || (_cache[2] = createBaseVNode("span", { class: "material-symbols-outlined" }, "chevron_left", -1)),
          createBaseVNode("p", null, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_RULE_GO_BACK")), 1)
        ], 8, _hoisted_4$1),
        $props.type === "js" && $data.elements.length > 0 ? (openBlock(), createBlock(_component_form_builder_rules_js, {
          key: 0,
          page: $data.fabrikPage,
          elements: $data.elements,
          rule: $props.rule,
          onCloseRuleAddJs: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("close-rule-add"))
        }, null, 8, ["page", "elements", "rule"])) : createCommentVNode("", true)
      ])
    ]),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_5$1)) : createCommentVNode("", true)
  ]);
}
const FormBuilderRulesAdd = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
const Formbuilder_vue_vue_type_style_index_0_lang = "";
const _sfc_main = {
  name: "FormBuilder",
  components: {
    History,
    Translations,
    FormBuilderCreateModel,
    FormBuilderSectionProperties,
    FormBuilderCreatePage,
    FormBuilderElements,
    FormBuilderElementProperties,
    FormBuilderPage,
    FormBuilderPages,
    FormBuilderDocuments,
    FormBuilderDocumentList,
    FormBuilderCreateDocument,
    FormBuilderDocumentFormats,
    Modal,
    FormBuilderRulesAdd,
    FormBuilderRulesList,
    FormBuilderRules
  },
  mixins: [formBuilderMixin],
  data() {
    return {
      mode: "forms",
      profile_id: 0,
      form_id: 0,
      campaign_id: 0,
      title: "",
      pages: [],
      principalContainer: "default",
      showInSection: "page",
      selectedPage: 0,
      selectedSection: null,
      selectedElement: null,
      optionsSelectedElement: false,
      selectedDocument: null,
      rightPanel: {
        tabs: [
          "hierarchy",
          "element-properties",
          "section-properties",
          "create-model",
          "create-document"
        ]
      },
      showInRightPanel: "hierarchy",
      createDocumentMandatory: "1",
      lastSave: null,
      leftPanel: {
        tabs: [
          {
            title: "Elements",
            code: "page",
            icon: "edit_note",
            active: true,
            displayed: true
          },
          {
            title: "Documents",
            code: "documents",
            icon: "attach_file",
            active: false,
            displayed: true
          },
          {
            title: "Translations",
            code: "translations",
            icon: "translate",
            active: false,
            displayed: true,
            url: "/parametres-globaux?layout=translation&default_menu=2&object=emundus_setup_profiles"
          },
          {
            title: "Rules",
            code: "rules",
            icon: "alt_route",
            active: false,
            displayed: false
          },
          {
            title: "History",
            code: "history",
            icon: "history",
            active: false,
            displayed: false
          }
        ]
      },
      formBuilderCreateDocumentKey: 0,
      createDocumentMode: "create",
      activeTab: "",
      minimizedLeft: false,
      showMinimizedLeft: false,
      minimizedRight: false,
      showMinimizedRight: false,
      showConditionBuilder: false,
      currentRule: null,
      ruleType: "js",
      previewForm: false,
      loading: false
    };
  },
  setup() {
    const formBuilderStore = useFormBuilderStore();
    const globalStore = useGlobalStore();
    return {
      formBuilderStore,
      globalStore
    };
  },
  created() {
    watch(() => this.formBuilderStore.lastSave, (newValue) => {
      this.lastSave = newValue;
    });
    const data = this.globalStore.getDatas;
    if (parseInt(this.globalStore.hasManyLanguages) === 0) {
      this.leftPanel.tabs[2].displayed = false;
    }
    this.profile_id = data.prid.value;
    this.campaign_id = data.cid.value;
    if (data && data.settingsmenualias && data.settingsmenualias.value) {
      this.leftPanel.tabs[2].url = "/" + data.settingsmenualias.value;
    }
    if (data && data.enableconditionbuilder && data.enableconditionbuilder.value == 1) {
      this.leftPanel.tabs[3].displayed = true;
    }
    if (data && data.mode && data.mode.value) {
      this.mode = data.mode.value;
      if (this.mode === "eval" || this.mode === "models") {
        this.rightPanel.tabs = this.rightPanel.tabs.filter((tab) => tab !== "hierarchy" && tab !== "create-document");
        this.leftPanel.tabs = this.leftPanel.tabs.filter((tab) => tab.code != "documents" && tab.code != "translations");
        this.form_id = this.profile_id;
        this.profile_id = 0;
      }
    }
    this.getFormTitle();
    this.getPages();
  },
  mounted() {
    this.$refs.modal.open();
  },
  methods: {
    getFormTitle() {
      if (this.profile_id) {
        formService.getProfileLabelByProfileId(this.profile_id).then((response) => {
          if (response.status !== false) {
            this.title = response.data.label;
          }
        });
      }
    },
    updateFormTitle() {
      this.title = this.$refs.formTitle.innerText.trim().replace(/[\r\n]/gm, " ");
      this.$refs.formTitle.innerText = this.$refs.formTitle.innerText.trim().replace(/[\r\n]/gm, " ");
      formService.updateFormLabel({ label: this.title, prid: this.profile_id, form_id: this.form_id });
    },
    updateFormTitleKeyup() {
      document.activeElement.blur();
    },
    getPages(page_id = 0) {
      if (this.profile_id) {
        formService.getFormsByProfileId(this.profile_id).then((response) => {
          this.pages = response.data.data;
          if (page_id === 0) {
            this.selectPage(this.pages[0].id);
          } else {
            this.selectPage(String(page_id));
          }
          this.principalContainer = "default";
          formService.getSubmissionPage(this.profile_id).then((response2) => {
            const formId = response2.link.match(/formid=(\d+)/)[1];
            if (formId) {
              const page = this.pages.find((page2) => page2.id === formId);
              if (!page) {
                this.pages.push({
                  id: formId,
                  label: this.translate("COM_EMUNDUS_FORM_BUILDER_SUBMISSION_PAGE"),
                  type: "submission",
                  elements: []
                });
              } else {
                page.type = "submission";
              }
            }
          });
        });
      } else if (this.form_id) {
        formService.getFormByFabrikId(this.form_id).then((response) => {
          this.title = response.data.data.label;
          this.pages = [response.data.data];
          this.selectPage(this.pages[0].id);
          this.principalContainer = "default";
        });
      }
    },
    onReorderedPages(reorderedPages) {
      this.pages = reorderedPages;
    },
    onElementCreated(eltid, scrollTo) {
      this.$refs.formBuilderPage.getSections(eltid, scrollTo);
    },
    createElementLastGroup(element) {
      if (this.loading) {
        return;
      }
      const groups = Object.values(this.$refs.formBuilderPage.fabrikPage.Groups);
      const last_group = groups[groups.length - 1].group_id;
      formBuilderService.createSimpleElement({
        gid: last_group,
        plugin: element.value,
        mode: this.mode
      }).then((response) => {
        if (response.status && response.data > 0) {
          this.onElementCreated(response.data, true);
          this.updateLastSave();
          this.loading = false;
        } else {
          this.displayError(response.msg);
          this.loading = false;
        }
      }).catch((error) => {
        console.warn(error);
        this.loading = false;
      });
    },
    onDocumentCreated() {
      this.$refs.formBuilderDocuments.getDocuments();
      this.$refs.formBuilderDocumentList.getDocuments();
    },
    onOpenSectionProperties(event) {
      this.selectedSection = event;
      this.showInRightPanel = "section-properties";
    },
    onOpenElementProperties(event) {
      this.selectedElement = event;
      if (this.selectedElement.plugin === "dropdown") {
        this.optionsSelectedElement = true;
      } else {
        if (this.optionsSelectedElement === true) {
          this.$refs.formBuilderPage.getSections();
        }
        this.optionsSelectedElement = false;
      }
      this.showInRightPanel = "element-properties";
    },
    onUpdateDocument() {
      this.$refs.formBuilderDocumentList.getDocuments();
      this.showInRightPanel = "hierarchy";
    },
    onCloseElementProperties() {
      this.selectedElement = null;
      this.showInRightPanel = "hierarchy";
      this.$refs.formBuilderPage.getSections();
    },
    onCloseSectionProperties() {
      this.selectedSection = null;
      this.showInRightPanel = "hierarchy";
      this.$refs.formBuilderPage.getSections();
    },
    onCloseCreatePage(response) {
      if (response.reload) {
        this.getPages(response.newSelected);
      } else {
        this.principalContainer = "default";
      }
    },
    onOpenCreateModel(pageId) {
      if (pageId > 0) {
        this.selectedPage = pageId;
        this.showInRightPanel = "create-model";
      } else {
        console.error("No page id provided");
      }
    },
    onOpenCreateDocument(mandatory = "1") {
      this.selectedDocument = null;
      this.createDocumentMandatory = mandatory;
      this.createDocumentMode = "create";
      this.formBuilderCreateDocumentKey++;
      this.showInRightPanel = "create-document";
      this.setSectionShown("documents");
    },
    onEditDocument(document2) {
      this.selectedDocument = document2;
      this.createDocumentMode = "update";
      this.createDocumentMandatory = document2.mandatory;
      this.formBuilderCreateDocumentKey++;
      this.showInRightPanel = "create-document";
      this.setSectionShown("documents");
    },
    onDeleteDocument() {
      this.selectedDocument = null;
      this.showInRightPanel = "hierarchy";
      this.setSectionShown("documents");
    },
    selectTab(section) {
      this.leftPanel.tabs.forEach((tab) => {
        tab.active = tab.code === section;
      });
    },
    selectPage(page_id) {
      this.selectedPage = page_id;
      if (this.showInSection === "documents") {
        this.setSectionShown("page");
      }
      this.setSectionShown(this.showInSection);
    },
    setSectionShown(section) {
      if (section == "rules-add") {
        this.selectTab("rules");
      } else {
        this.selectTab(section);
      }
      if (section === "documents") {
        this.selectedPage = null;
      } else if (this.selectedPage == null) {
        this.selectedPage = this.pages[0].id;
      }
      this.showInSection = section;
    },
    goTo(url, blank = false) {
      const baseUrl = window.location.origin;
      if (blank) {
        window.open(baseUrl + url, "_blank");
      } else {
        window.location.href = baseUrl + url;
      }
    },
    clickGoBack() {
      if (this.previewForm) {
        this.previewForm = !this.previewForm;
      } else {
        if (this.principalContainer === "create-page") {
          this.onCloseCreatePage({ reload: false });
        } else {
          settingsService.redirectJRoute("index.php?option=com_emundus&view=form", useGlobalStore().getCurrentLang);
        }
      }
    },
    addRule(rule_type, rule = null) {
      this.ruleType = rule_type;
      this.currentRule = rule;
      if (rule !== null) {
        this.showInRightPanel = null;
      }
      this.showInSection = "rules-add";
    },
    clickTab(tab) {
      this.activeTab = tab.code;
    },
    handleSidebarSize(position = "left") {
      if (position === "left") {
        this.minimizedLeft = !this.minimizedLeft;
      } else {
        this.minimizedRight = !this.minimizedRight;
      }
    },
    afterDeletedPage(page_id) {
      if (this.selectedPage == page_id) {
        this.selectedPage = this.pages[0].id;
      }
    }
  },
  computed: {
    currentPage() {
      return this.pages.find((page) => page.id === this.selectedPage);
    },
    leftPanelActiveTab() {
      let find = this.leftPanel.tabs.find((tab) => tab.active);
      if (find) {
        return find.title;
      } else {
        return this.leftPanel.tabs[0].title;
      }
    },
    displayedLeftPanels() {
      return this.leftPanel.tabs.filter((tab) => {
        return tab.displayed;
      });
    },
    defaultLangLabel() {
      let label = "Français";
      switch (this.globalStore.defaultLang) {
        case "en-GB":
          label = "English";
          break;
        case "pt-PT":
          label = "Português";
      }
      return label;
    }
  },
  watch: {
    previewForm(newValue) {
      this.loading = true;
      if (newValue) {
        setTimeout(() => {
          const myIframe = document.getElementById("preview_iframe");
          myIframe.addEventListener("load", () => {
            let cssLink = document.createElement("link");
            cssLink.href = "media/com_fabrik/css/fabrik.css";
            cssLink.rel = "stylesheet";
            cssLink.type = "text/css";
            frames["preview_iframe"].document.head.appendChild(cssLink);
            const css = '<style type="text/css">.fabrikActions{display:none}; </style>';
            frames["preview_iframe"].document.head.insertAdjacentHTML("beforeend", css);
            this.loading = false;
          });
        }, 500);
      } else {
        this.selectTab(this.showInSection);
        this.loading = false;
      }
    }
  }
};
const _hoisted_1 = {
  id: "formBuilder",
  class: "tw-w-full tw-h-full"
};
const _hoisted_2 = {
  key: 0,
  class: "tw-justify-center tw-bg-[#FEF6EE] tw-flex tw-items-center tw-gap-3 tw-p-2"
};
const _hoisted_3 = { class: "tw-grid tw-grid-cols-3 tw-items-center" };
const _hoisted_4 = { class: "right-actions tw-flex tw-items-center tw-justify-start tw-gap-2" };
const _hoisted_5 = {
  key: 0,
  id: "saved-at",
  class: "em-font-size-14 em-main-500-color"
};
const _hoisted_6 = ["placeholder"];
const _hoisted_7 = { class: "tw-flex tw-flex-col tw-items-end" };
const _hoisted_8 = {
  class: "tw-mb-0",
  for: "previewform"
};
const _hoisted_9 = {
  class: "tw-mb-0",
  for: "previewform"
};
const _hoisted_10 = {
  key: 1,
  class: "body tw-flex tw-items-center tw-justify-between"
};
const _hoisted_11 = { class: "left-panel tw-flex tw-justify-start tw-h-full tw-relative" };
const _hoisted_12 = { class: "tabs tw-flex tw-flex-col tw-justify-start tw-h-full tw-p-3 tw-gap-3" };
const _hoisted_13 = ["onClick", "title"];
const _hoisted_14 = {
  key: 0,
  class: "tw-flex tw-flex-col tw-w-full tw-h-full",
  id: "center_content"
};
const _hoisted_15 = {
  key: 1,
  class: "tw-w-full tw-h-full",
  style: { "background": "#fafafb" }
};
const _hoisted_16 = { style: { "padding": "1.5rem" } };
const _hoisted_17 = ["src"];
const _hoisted_18 = {
  key: 0,
  id: "form-hierarchy"
};
const _hoisted_19 = { key: 2 };
const _hoisted_20 = {
  key: 0,
  class: "em-page-loader"
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_form_builder_elements = resolveComponent("form-builder-elements");
  const _component_form_builder_document_formats = resolveComponent("form-builder-document-formats");
  const _component_form_builder_rules_list = resolveComponent("form-builder-rules-list");
  const _component_form_builder_page = resolveComponent("form-builder-page");
  const _component_form_builder_document_list = resolveComponent("form-builder-document-list");
  const _component_form_builder_rules = resolveComponent("form-builder-rules");
  const _component_form_builder_rules_add = resolveComponent("form-builder-rules-add");
  const _component_history = resolveComponent("history");
  const _component_translations = resolveComponent("translations");
  const _component_form_builder_pages = resolveComponent("form-builder-pages");
  const _component_form_builder_documents = resolveComponent("form-builder-documents");
  const _component_form_builder_element_properties = resolveComponent("form-builder-element-properties");
  const _component_form_builder_section_properties = resolveComponent("form-builder-section-properties");
  const _component_form_builder_create_model = resolveComponent("form-builder-create-model");
  const _component_form_builder_create_document = resolveComponent("form-builder-create-document");
  const _component_form_builder_create_page = resolveComponent("form-builder-create-page");
  const _component_modal = resolveComponent("modal");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createVNode(_component_modal, {
      name: "formBuilder",
      height: "100vh",
      transition: "fade",
      delay: 100,
      adaptive: true,
      clickToClose: false,
      ref: "modal"
    }, {
      default: withCtx(() => [
        this.globalStore.currentLanguage !== this.globalStore.defaultLang ? (openBlock(), createElementBlock("div", _hoisted_2, [
          _cache[25] || (_cache[25] = createBaseVNode("span", { class: "material-symbols-outlined text-[#EF681F]" }, "warning_amber", -1)),
          createBaseVNode("span", null, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_FORMBUILDER_EDIT_DEFAULT_LANG")) + toDisplayString($options.defaultLangLabel), 1)
        ])) : createCommentVNode("", true),
        createBaseVNode("header", _hoisted_3, [
          createBaseVNode("div", _hoisted_4, [
            createBaseVNode("p", {
              class: "tw-flex tw-items-center tw-cursor-pointer",
              onClick: _cache[0] || (_cache[0] = (...args) => $options.clickGoBack && $options.clickGoBack(...args))
            }, [
              _cache[26] || (_cache[26] = createBaseVNode("span", {
                id: "go-back",
                class: "material-symbols-outlined tw-text-neutral-600 tw-py-3 tw-pl-5 tw-pr-1 em-pointer"
              }, " navigate_before ", -1)),
              createTextVNode(" " + toDisplayString(_ctx.translate("COM_EMUNDUS_ACTIONS_BACK")), 1)
            ]),
            $data.lastSave ? (openBlock(), createElementBlock("p", _hoisted_5, toDisplayString(_ctx.translate("COM_EMUNDUS_FORM_BUILDER_SAVED_AT")) + " " + toDisplayString($data.lastSave), 1)) : createCommentVNode("", true)
          ]),
          createBaseVNode("span", {
            class: "tw-text-sm tw-font-semibold editable-data tw-text-center",
            contenteditable: "true",
            ref: "formTitle",
            onFocusout: _cache[1] || (_cache[1] = (...args) => $options.updateFormTitle && $options.updateFormTitle(...args)),
            onKeyup: _cache[2] || (_cache[2] = withKeys((...args) => $options.updateFormTitleKeyup && $options.updateFormTitleKeyup(...args), ["enter"])),
            placeholder: _ctx.translate("COM_EMUNDUS_FORM_BUILDER_ADD_FORM_TITLE_ADD")
          }, toDisplayString($data.title), 41, _hoisted_6),
          createBaseVNode("div", _hoisted_7, [
            !$data.previewForm && ["page", "rules"].includes($data.showInSection) ? (openBlock(), createElementBlock("button", {
              key: 0,
              class: "tw-btn-primary tw-px-6 tw-py-3 tw-gap-3 em-w-auto",
              onClick: _cache[3] || (_cache[3] = ($event) => $data.previewForm = true)
            }, [
              _cache[27] || (_cache[27] = createBaseVNode("span", { class: "tw-text-white material-symbols-outlined" }, " remove_red_eye ", -1)),
              createBaseVNode("label", _hoisted_8, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_GO_TO_PREVIEW")), 1)
            ])) : createCommentVNode("", true),
            $data.previewForm ? (openBlock(), createElementBlock("button", {
              key: 1,
              class: "tw-btn-primary tw-px-6 tw-py-3 tw-gap-3 em-w-auto",
              onClick: _cache[4] || (_cache[4] = ($event) => $data.previewForm = false)
            }, [
              _cache[28] || (_cache[28] = createBaseVNode("span", { class: "tw-text-white material-symbols-outlined" }, " handyman ", -1)),
              createBaseVNode("label", _hoisted_9, toDisplayString(_ctx.translate("COM_EMUNDUS_FORMBUILDER_GO_BACK_FORMBUILDER")), 1)
            ])) : createCommentVNode("", true)
          ])
        ]),
        $data.principalContainer === "default" ? (openBlock(), createElementBlock("div", _hoisted_10, [
          withDirectives(createBaseVNode("aside", _hoisted_11, [
            createBaseVNode("div", _hoisted_12, [
              (openBlock(true), createElementBlock(Fragment, null, renderList($options.displayedLeftPanels, (tab, i) => {
                return openBlock(), createElementBlock("div", {
                  key: $data.title + "_" + i,
                  onClick: ($event) => $options.setSectionShown(tab.code),
                  class: normalizeClass(["tw-flex tw-items-start tw-w-full tw-p-2 tw-cursor-pointer tw-rounded-lg tw-group tw-user-select-none", tab.active ? "tw-font-bold tw-text-profile-full tw-bg-profile-light" : "hover:tw-bg-gray-200"]),
                  title: tab.title
                }, [
                  createBaseVNode("span", {
                    class: normalizeClass(["material-symbols-outlined tw-font-bold", tab.active ? "tw-text-profile-full" : ""])
                  }, toDisplayString(tab.icon), 3)
                ], 10, _hoisted_13);
              }), 128))
            ]),
            !$data.previewForm && $options.leftPanelActiveTab !== "Rules" && ($data.activeTab === "" || $data.activeTab === "Elements") ? (openBlock(), createElementBlock("div", {
              key: 0,
              class: normalizeClass(["tab-content tw-justify-start tw-transition-all tw-duration-300", $data.minimizedLeft === true ? "tw-max-w-0" : "tw-max-w-md"]),
              onMouseover: _cache[5] || (_cache[5] = ($event) => $data.showMinimizedLeft = true),
              onMouseleave: _cache[6] || (_cache[6] = ($event) => $data.showMinimizedLeft = false)
            }, [
              createVNode(Transition, {
                name: "slide-right",
                mode: "out-in"
              }, {
                default: withCtx(() => [
                  $options.leftPanelActiveTab === "Elements" ? (openBlock(), createBlock(_component_form_builder_elements, {
                    key: 0,
                    onElementCreated: $options.onElementCreated,
                    form: $options.currentPage,
                    onCreateElementLastgroup: $options.createElementLastGroup
                  }, null, 8, ["onElementCreated", "form", "onCreateElementLastgroup"])) : $options.leftPanelActiveTab === "Documents" ? (openBlock(), createBlock(_component_form_builder_document_formats, {
                    key: 1,
                    profile_id: $data.profile_id,
                    onOpenCreateDocument: $options.onEditDocument
                  }, null, 8, ["profile_id", "onOpenCreateDocument"])) : $options.leftPanelActiveTab === "Rules" && this.showInSection !== "rules-add" ? (openBlock(), createBlock(_component_form_builder_rules_list, {
                    key: 2,
                    form: $options.currentPage,
                    onAddRule: $options.addRule
                  }, null, 8, ["form", "onAddRule"])) : createCommentVNode("", true)
                ]),
                _: 1
              })
            ], 34)) : createCommentVNode("", true),
            $data.activeTab === "" || $data.activeTab === "Elements" ? (openBlock(), createElementBlock("div", {
              key: 1,
              class: "tw-w-[16px]",
              onMouseover: _cache[8] || (_cache[8] = ($event) => $data.showMinimizedLeft = true),
              onMouseleave: _cache[9] || (_cache[9] = ($event) => $data.showMinimizedLeft = false)
            }, [
              withDirectives(createBaseVNode("span", {
                class: normalizeClass(["material-symbols-outlined tw-absolute tw-right-[-12px] tw-top-[14px] !tw-text-xl/5 tw-bg-neutral-400 tw-rounded-full tw-cursor-pointer", $data.minimizedLeft ? "tw-rotate-180" : ""]),
                onClick: _cache[7] || (_cache[7] = ($event) => $options.handleSidebarSize("left"))
              }, "chevron_left", 2), [
                [vShow, $data.showMinimizedLeft === true || $data.minimizedLeft]
              ])
            ], 32)) : createCommentVNode("", true)
          ], 512), [
            [vShow, !$data.previewForm]
          ]),
          !$data.previewForm && ($data.activeTab === "" || $data.activeTab === "Elements") ? (openBlock(), createElementBlock("section", _hoisted_14, [
            createVNode(Transition, {
              name: "fade",
              mode: "out-in"
            }, {
              default: withCtx(() => [
                $options.currentPage && $data.showInSection === "page" ? (openBlock(), createBlock(_component_form_builder_page, {
                  key: $options.currentPage.id,
                  ref: "formBuilderPage",
                  mode: $data.mode,
                  page: $options.currentPage,
                  profile_id: parseInt($data.profile_id),
                  onOpenElementProperties: $options.onOpenElementProperties,
                  onOpenSectionProperties: $options.onOpenSectionProperties,
                  onOpenCreateModel: $options.onOpenCreateModel,
                  onUpdatePageTitle: _cache[10] || (_cache[10] = ($event) => $options.getPages($options.currentPage.id))
                }, null, 8, ["mode", "page", "profile_id", "onOpenElementProperties", "onOpenSectionProperties", "onOpenCreateModel"])) : $data.showInSection === "documents" ? (openBlock(), createBlock(_component_form_builder_document_list, {
                  key: 1,
                  ref: "formBuilderDocumentList",
                  campaign_id: parseInt($data.campaign_id),
                  profile_id: parseInt($data.profile_id),
                  onAddDocument: $options.onOpenCreateDocument,
                  onEditDocument: $options.onEditDocument,
                  onDeleteDocument: $options.onDeleteDocument
                }, null, 8, ["campaign_id", "profile_id", "onAddDocument", "onEditDocument", "onDeleteDocument"])) : $options.currentPage && $data.showInSection === "rules" ? (openBlock(), createBlock(_component_form_builder_rules, {
                  key: $options.currentPage.id,
                  mode: $data.mode,
                  page: $options.currentPage,
                  onAddRule: $options.addRule
                }, null, 8, ["mode", "page", "onAddRule"])) : $options.currentPage && $data.showInSection === "rules-add" ? (openBlock(), createBlock(_component_form_builder_rules_add, {
                  key: $options.currentPage.id,
                  page: $options.currentPage,
                  mode: $data.mode,
                  type: $data.ruleType,
                  rule: $data.currentRule,
                  onCloseRuleAdd: _cache[11] || (_cache[11] = ($event) => {
                    $data.showInSection = "rules";
                    $data.showInRightPanel = "hierarchy";
                  })
                }, null, 8, ["page", "mode", "type", "rule"])) : $data.showInSection === "history" ? (openBlock(), createBlock(_component_history, {
                  key: 4,
                  class: "tw-p-6",
                  extension: "com_emundus.formbuilder",
                  "display-title": true
                })) : $options.currentPage && $data.showInSection === "translations" ? (openBlock(), createBlock(_component_translations, {
                  key: $options.currentPage.id,
                  class: "tw-p-6",
                  objectValue: "emundus_setup_profiles",
                  dataValue: $data.profile_id,
                  childrenValue: $options.currentPage.id,
                  "display-filters": false
                }, null, 8, ["dataValue", "childrenValue"])) : createCommentVNode("", true)
              ]),
              _: 1
            })
          ])) : createCommentVNode("", true),
          $data.previewForm ? (openBlock(), createElementBlock("div", _hoisted_15, [
            createBaseVNode("h2", _hoisted_16, toDisplayString(_ctx.translate("COM_EMUNDUS_ONBOARD_PREVIEW")), 1),
            withDirectives(createBaseVNode("iframe", {
              width: "100%",
              height: "100%",
              frameborder: "0",
              style: { "padding-bottom": "36px" },
              id: "preview_iframe",
              name: "preview_iframe",
              src: "/forms/preview?formid=" + $data.selectedPage + "&tmpl=component&preview=1",
              onLoad: _cache[12] || (_cache[12] = ($event) => $data.loading = false)
            }, null, 40, _hoisted_17), [
              [vShow, !$data.loading]
            ])
          ])) : createCommentVNode("", true),
          createVNode(Transition, {
            name: "slide-fade",
            mode: "out-in"
          }, {
            default: withCtx(() => [
              $data.rightPanel.tabs.includes($data.showInRightPanel) && $data.activeTab === "" || $data.activeTab === "Elements" ? (openBlock(), createElementBlock("aside", {
                key: 0,
                class: "right-panel tw-h-full tw-flex tw-flex-col tw-relative",
                onMouseover: _cache[23] || (_cache[23] = ($event) => $data.showMinimizedRight = true),
                onMouseleave: _cache[24] || (_cache[24] = ($event) => $data.showMinimizedRight = false)
              }, [
                createBaseVNode("div", {
                  class: "tw-w-[16px] !tw-h-0",
                  onMouseover: _cache[14] || (_cache[14] = ($event) => $data.showMinimizedRight = true),
                  onMouseleave: _cache[15] || (_cache[15] = ($event) => $data.showMinimizedRight = false)
                }, [
                  withDirectives(createBaseVNode("span", {
                    class: normalizeClass(["material-symbols-outlined tw-absolute tw-left-[-12px] tw-top-[14px] !tw-text-xl/5 tw-bg-neutral-400 tw-rounded-full tw-cursor-pointer", $data.minimizedRight ? "tw-rotate-180" : ""]),
                    onClick: _cache[13] || (_cache[13] = ($event) => $options.handleSidebarSize("right"))
                  }, "chevron_right", 2), [
                    [vShow, $data.showMinimizedRight === true || $data.minimizedRight]
                  ])
                ], 32),
                createVNode(Transition, {
                  name: "fade",
                  mode: "out-in"
                }, {
                  default: withCtx(() => [
                    createBaseVNode("div", {
                      class: normalizeClass([$data.minimizedRight === true ? "tw-max-w-0" : "tw-max-w-md tw-min-w-[22rem]", "tw-transition-all tw-duration-300"])
                    }, [
                      $data.showInRightPanel === "hierarchy" && $data.rightPanel.tabs.includes("hierarchy") ? (openBlock(), createElementBlock("div", _hoisted_18, [
                        createVNode(_component_form_builder_pages, {
                          pages: $data.pages,
                          selected: parseInt($data.selectedPage),
                          profile_id: parseInt($data.profile_id),
                          onSelectPage: _cache[16] || (_cache[16] = ($event) => $options.selectPage($event)),
                          onAddPage: _cache[17] || (_cache[17] = ($event) => $options.getPages($options.currentPage.id)),
                          onDeletePage: _cache[18] || (_cache[18] = ($event) => $options.afterDeletedPage($event)),
                          onOpenPageCreate: _cache[19] || (_cache[19] = ($event) => {
                            $data.principalContainer = "create-page";
                          }),
                          onReorderPages: $options.onReorderedPages,
                          onOpenCreateModel: $options.onOpenCreateModel
                        }, null, 8, ["pages", "selected", "profile_id", "onReorderPages", "onOpenCreateModel"]),
                        _cache[29] || (_cache[29] = createBaseVNode("hr", null, null, -1)),
                        !$data.previewForm && $options.leftPanelActiveTab !== "Rules" ? (openBlock(), createBlock(_component_form_builder_documents, {
                          key: 0,
                          ref: "formBuilderDocuments",
                          profile_id: parseInt($data.profile_id),
                          campaign_id: parseInt($data.campaign_id),
                          onShowDocuments: _cache[20] || (_cache[20] = ($event) => $options.setSectionShown("documents")),
                          onOpenCreateDocument: $options.onOpenCreateDocument
                        }, null, 8, ["profile_id", "campaign_id", "onOpenCreateDocument"])) : createCommentVNode("", true)
                      ])) : createCommentVNode("", true),
                      $data.showInRightPanel === "element-properties" ? (openBlock(), createBlock(_component_form_builder_element_properties, {
                        key: 1,
                        onClose: $options.onCloseElementProperties,
                        element: $data.selectedElement,
                        profile_id: parseInt($data.profile_id)
                      }, null, 8, ["onClose", "element", "profile_id"])) : $data.showInRightPanel === "section-properties" ? (openBlock(), createBlock(_component_form_builder_section_properties, {
                        key: 2,
                        onClose: $options.onCloseSectionProperties,
                        section_id: $data.selectedSection.group_id,
                        profile_id: parseInt($data.profile_id)
                      }, null, 8, ["onClose", "section_id", "profile_id"])) : $data.showInRightPanel === "create-model" ? (openBlock(), createBlock(_component_form_builder_create_model, {
                        key: 3,
                        page: $data.selectedPage,
                        onClose: _cache[21] || (_cache[21] = ($event) => {
                          $data.showInRightPanel = "hierarchy";
                        })
                      }, null, 8, ["page"])) : $data.showInRightPanel === "create-document" && $data.rightPanel.tabs.includes("create-document") ? (openBlock(), createBlock(_component_form_builder_create_document, {
                        ref: "formBuilderCreateDocument",
                        key: $data.formBuilderCreateDocumentKey,
                        profile_id: parseInt($data.profile_id),
                        current_document: $data.selectedDocument ? $data.selectedDocument : null,
                        mandatory: $data.createDocumentMandatory,
                        mode: $data.createDocumentMode,
                        onClose: _cache[22] || (_cache[22] = ($event) => $data.showInRightPanel = "hierarchy"),
                        onDocumentsUpdated: $options.onUpdateDocument
                      }, null, 8, ["profile_id", "current_document", "mandatory", "mode", "onDocumentsUpdated"])) : createCommentVNode("", true)
                    ], 2)
                  ]),
                  _: 1
                })
              ], 32)) : createCommentVNode("", true)
            ]),
            _: 1
          })
        ])) : $data.principalContainer === "create-page" ? (openBlock(), createElementBlock("div", _hoisted_19, [
          createVNode(_component_form_builder_create_page, {
            profile_id: parseInt($data.profile_id),
            onClose: $options.onCloseCreatePage
          }, null, 8, ["profile_id", "onClose"])
        ])) : createCommentVNode("", true)
      ]),
      _: 1
    }, 512),
    $data.loading ? (openBlock(), createElementBlock("div", _hoisted_20)) : createCommentVNode("", true)
  ]);
}
const Formbuilder = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  Formbuilder as default
};
