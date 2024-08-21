
export default {
  async createSimpleElement(params) {
    try {
      const formData = new FormData();
      Object.keys(params).forEach(key => {
        formData.append(key, params[key]);
      });

      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=createsimpleelement', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
      Object.keys(params).forEach(key => {
        formData.append(key, params[key]);
      });


      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=createsectionsimpleelements', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(data => {
        return data;
      }).catch(error => {
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
      formData.append('fid', fid);
      formData.append('mode', mode);
      formData.append('label', JSON.stringify(label));

      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=createsimplegroup', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(json => {
        return json;
      }).catch(error => {
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
      formData.append('cid', campaign_id);

      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=createtestingfile', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=getElement&gid='+gid+'&element='+element, {
        method: 'GET'
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('toJTEXT', text);

    try {
      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=getJTEXT', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
        throw error;
      });
    }  catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getJTEXTA(texts) {
    const formData = new FormData();
    texts.forEach((text, index) => {
      formData.append('toJTEXT[' + index + ']', text);
    });

    try {
      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=getJTEXTA', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
        throw error;
      });
    }  catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getAllTranslations(text) {
    const formData = new FormData();
    formData.append('toJTEXT', text);

    try {
      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=getalltranslations', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
        throw error;
      });
    }  catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getTestingParams(id) {
    try {
      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=gettestingparams&prid='+id, {
        method: 'GET'
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=getdatabasesjoin', {
        method: 'GET'
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
      return fetch ('/index.php?option=com_emundus&controller=formbuilder&task=getDatabaseJoinOrderColumns&database_name='+databaseName, {
        method: 'GET'
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('element', postData);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=updateparams', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return {
          status: response.scalar
        };
      }).catch(error => {
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
    formData.append('group_id', group_id);
    formData.append('params', JSON.stringify(params));
    if (lang != null) {
      formData.append('lang', lang);
    }

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=updategroupparams', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('elements', JSON.stringify(elements));
    formData.append('group_id', groupId);
    formData.append('moved_el', JSON.stringify(movedElement));

    if (movedElement.length == 0) {
      return {
        status: false,
        message: 'No elements to update'
      };
    }

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=updateOrder', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('group_id', groupId);
    formData.append('element_id', elementId);
    formData.append('new_index', newIndex);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=updateelementorder', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    if (data.document_id == undefined || data.profile_id == undefined || data.document == undefined) {
      return {
        status: false,
        msg: 'Missing data'
      };
    }

    const formData = new FormData();
    formData.append('document_id', data.document_id);
    formData.append('profile_id', data.profile_id);
    formData.append('document', data.document);
    formData.append('types', data.types);
    formData.append('file', data.sample);
    formData.append('has_sample', data.has_sample);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=updatedocument', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('element', element);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=publishunpublishelement', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('element', element);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=hiddenunhiddenelement', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('menus', JSON.stringify(params));
    formData.append('profile', profile_id);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=reordermenu', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('groups', JSON.stringify(sections));
    formData.append('fid', pageId);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=reordergroups', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
        msg: 'Missing prid'
      };
    }

    const formData = new FormData();
    Object.keys(params).forEach(key => {
      formData.append(key, JSON.stringify(params[key]));
    });

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=createMenu', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
        message: 'Missing page id'
      };
    }

    const formData = new FormData();
    formData.append('mid', page);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=deletemenu', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('labelTofind', tag);
    Object.keys(value).forEach(key => {
      formData.append('NewSubLabel[' + key + ']', value[key]);
    });

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=formsTrad', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(data => {
        return {
          data: data
        };
      }).catch(error => {
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
    formData.append('element', elementId);
    formData.append('options', JSON.stringify(options));
    formData.append('index', index);
    formData.append('newTranslation', newTranslation);
    formData.append('lang', lang);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=updateElementOption', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('element', element);
    formData.append('newOption', newOption);
    formData.append('lang', lang);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=addElementSubOption', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('element', element);
    formData.append('index', index);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=deleteElementSubOption', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('element', element);
    formData.append('options_old_order', JSON.stringify(old_order));
    formData.append('options_new_order', JSON.stringify(new_order));

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=updateElementSubOptionsOrder', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=getelementsuboptions&element=' + element, {
        method: 'GET'
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('element', elementId);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=deleteElement', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('gid', groupId);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=deleteGroup', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
    formData.append('eid', eid);
    formData.append('value', value);

    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=updatedefaultvalue', {
        method: 'POST',
        body: formData
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
        throw error;
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getAllDatabases() {
    try {
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=getalldatabases', {
        method: 'GET'
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=getsection&section=' + section, {
        method: 'GET'
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
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
      return fetch('/index.php?option=com_emundus&controller=formbuilder&task=getpagemodels', {
        method: 'GET'
      }).then(response => response.json()).then(response => {
        return response;
      }).catch(error => {
        throw error;
      });
    } catch (e) {
      return {status: false, message: e.message};
    }
  },
  async addFormModel(formId, modelLabel) {
    if (formId > 0) {
      const formData = new FormData();
      formData.append('form_id', formId);
      formData.append('label', modelLabel);

      try {
        return fetch('/index.php?option=com_emundus&controller=formbuilder&task=addformmodel', {
          method: 'POST',
          body: formData
        }).then(response => response.json()).then(response => {
          return response;
        }).catch(error => {
          throw error;
        });
      } catch (e) {
        return {status: false, message: e.message};
      }
    } else {
      return {status: false, message: 'MISSING_PARAMS'};
    }
  },
  async deleteFormModel(formId) {
    if (formId > 0) {
      const formData = new FormData();
      formData.append('form_id', formId);

      try {
        return fetch('/index.php?option=com_emundus&controller=formbuilder&task=deleteformmodel', {
          method: 'POST',
          body: formData
        }).then(response => response.json()).then(response => {
          return response;
        }).catch(error => {
          throw error;
        });
      } catch (e) {
        return {status: false, message: e.message};
      }
    } else {
      return {status: false, message: 'MISSING_PARAMS'};
    }
  },
  async deleteFormModelFromId(modelIds) {
    if (modelIds.length > 0) {
      const formData = new FormData();
      formData.append('model_ids', JSON.stringify(modelIds));

      try {
        return fetch('/index.php?option=com_emundus&controller=formbuilder&task=deleteformmodelfromids', {
          method: 'POST',
          body: formData
        }).then(response => response.json()).then(response => {
          return response;
        }).catch(error => {
          throw error;
        });
      } catch (e) {
        return {status: false, message: e.message};
      }
    } else {
      return {status: false, message: 'MISSING_PARAMS'};
    }
  },
  async getDocumentSample(documentId, profileId) {
    if (documentId > 0 && profileId > 0) {
      try {
        return fetch('/index.php?option=com_emundus&controller=formbuilder&task=getdocumentsample&document_id=' + documentId + '&profile_id=' + profileId, {
          method: 'GET'
        }).then(response => response.json()).then(response => {
          return response;
        }).catch(error => {
          throw error;
        });
      } catch (e) {
        return {status: false, message: e.message};
      }
    } else {
      return {status: false, message: 'MISSING_PARAMS'};
    }

  },

  async checkIfModelTableIsUsedInForm(modelId, profileId) {
    let response = {
      status: false,
      msg: 'MISSING_PARAMS'
    };

    if (modelId > 0 && profileId > 0) {

      try {
        return fetch('/index.php?option=com_emundus&controller=formbuilder&task=checkifmodeltableisusedinform&model_id=' + modelId + '&profile_id=' + profileId, {
          method: 'GET'
        }).then(response => response.json()).then(response => {
          return response.data;
        }).catch(error => {
          throw error;
        });
      } catch (e) {
        response.msg = e.message;
      }
    }

    return response;
  },

  async getSqlDropdownOptions(table,key,value,translate) {
    let response = {
      status: false,
      msg: 'MISSING_PARAMS'
    };

    if (table && key && value) {
      try {
        return fetch('/index.php?option=com_emundus&controller=formbuilder&task=getsqldropdownoptions&table='+table+'&key='+key+'&value='+value+'&translate='+translate, {
          method: 'GET'
        }).then(response => response.json()).then(response => {
          return response;
        }).catch(error => {
          throw error;
        });
      } catch (e) {
        response.msg = e.message;
      }
    }

    return response;
  }
};
