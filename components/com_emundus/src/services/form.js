/* jshint esversion: 8 */
import client from './axiosClient';

import {FetchClient} from './fetchClient.js';
const fetchClient = new FetchClient('form');

const baseUrl = 'index.php?option=com_emundus&controller=form';

export default {
  async updateFormLabel(params) {
    try {
      return await fetchClient.post('updateformlabel', params);
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async getFilesByForm(id) {
    try {
      return await fetchClient.get('getfilesbyform',{
        pid: id
      });
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async getSubmissionPage(id) {
    try {
      return await fetchClient.get('getsubmittionpage', {
        prid: id
      });
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async getApplicantForms() {

  },
  async getFormsByProfileId(id) {
    try {
      const response = await client().get(
        baseUrl + '&task=getFormsByProfileId',
        {
          params: {
            profile_id: id
          }
        });

      response.data.data.forEach(form => {
        // if form.type is not set, then set it to 'form'
        if (typeof form.type == 'undefined') {
          form.type = 'form';
        }
      });

      return response;
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async getFormByFabrikId(id) {
    try {
      const response = await client().get(baseUrl + '&task=getFormByFabrikId', {params: {form_id: id}});
      return response;
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async getEvaluationForms() {
    try {
      return await fetchClient.get( 'getallgrilleEval');
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async createForm(params) {
    try {
      return await fetchClient.post('createform', params);
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  
  async getProfileLabelByProfileId(id) {
    try {
      return await fetchClient.get('getProfileLabelByProfileId&profile_id=' + id);
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async getDocuments(id) {
    if (id > 0) {
      try {
        const response = await client().get(baseUrl + '&task=getDocuments', {params: {pid: id}});

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
        msg: 'Missing parameter'
      };
    }
  },
  async getDocumentModels(documentId = null) {
    try {
      let data = {
        status: false,
      };

      const response = await client().get(
        baseUrl + '&task=getAttachments'
      );

      if (response.data.status) {
        if (documentId !== null) {
          const document = response.data.data.filter(document => document.id === documentId);
          if (document.length > 0) {
            data = {
              status: true,
              data: document[0]
            };
          } else {
            data = {
              status: false,
              error: 'Document not found'
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
        error: error
      };
    }
  },
  async getDocumentModelsUsage(documentIds) {
    if (documentIds.length > 0) {
      try {
        const response = await client().get(baseUrl + '&task=getdocumentsusage&documentIds=' + documentIds);

        return response.data;
      } catch (error) {
        return {
          status: false,
          error: error
        };
      }
    } else {
      return {
        status: false,
        msg: 'Missing parameter'
      };
    }
  },
  async getPageGroups(formId) {
    if (typeof formId == 'number' && formId > 0) {
      try {
        const response = await client().get(baseUrl + '&task=getpagegroups&form_id=' + formId);

        return response.data;
      } catch (error) {
        return {
          status: false,
          error: error
        };
      }
    } else {
      return {
        status: false,
        msg: 'MISSING_PARAMS'
      };
    }
  },
  async reorderDocuments(documents) {
    let data = {};
    data.documents = JSON.stringify(documents);
    try {
      return await fetchClient.post('reorderDocuments', data);
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async addDocument(params) {
    const formData = new FormData();
    Object.keys(params).forEach(key => formData.append(key, params[key]));

    try {
      const response = await client().post(baseUrl + '&task=addDocument', formData);

      return response;
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },
  async getAssociatedCampaigns(id) {
    try {
      const response = client().get(
        baseUrl + '&task=getassociatedcampaign',
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
        error: error
      };
    }
  },
  async removeDocumentFromProfile(id) {
    try {
      const response = await client().get(
        baseUrl + '&task=removeDocumentFromProfile',
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
        error: error
      };
    }
  },
  async getPageObject(formId) {
    try {
      const response = await client().get(
        '/index.php?option=com_emundus&view=form&formid=' + formId + '&format=vue_jsonclean'
      );

      if (typeof response.data !== 'object') {
        throw 'COM_EMUNDUS_FORM_BUILDER_FAILED_TO_LOAD_FORM';
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
        baseUrl + '&task=checkcandocbedeleted&docid=' + documentId + '&prid=' + profileId
      );

      return response.data;
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },

  async getConditions(formId) {
    try {
      const response = await client().get(
        baseUrl + '&task=getjsconditions&form_id=' + formId + '&format=view'
      );

      return response.data;
    } catch (error) {
      return {
        status: false,
        error: error
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
      return await fetchClient.post('addRule', data);
    } catch (error) {
      return {
        status: false,
        error: error
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
      return await fetchClient.post('editRule', data);
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },

  async deleteRule(ruleId) {
    try {
      const response = await client().get(baseUrl + '&task=deleteRule&rule_id=' + ruleId);

      return response;
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },

  async publishRule(ruleId, state) {
    try {
      const response = await client().get(baseUrl + '&task=publishRule&rule_id=' + ruleId + '&state=' + state);

      return response;
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },

  async getPublishedForms() {
    try {
      return await client().get(baseUrl + '&task=getallformpublished');
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  },

  async getUnDocuments(){
    try {
      return await fetchClient.get('getundocuments');
    } catch (error) {
      return {
        status: false,
        error: error
      };
    }
  }
};
