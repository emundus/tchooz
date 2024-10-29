/* jshint esversion: 8 */
import {FetchClient} from './fetchClient.js';

const fetchClient = new FetchClient('settings');

export default {
  async getActiveLanguages() {
    try {
      return await fetchClient.get('getactivelanguages');
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async removeParameter(param) {
    const data = {
        param: param
    }

    try {
      return await fetchClient.post('removeparam', data);
    } catch (e) {
      return {
        status: false,
        error: e
      };
    }
  },
  async checkFirstDatabaseJoin() {
    try {
      return await fetchClient.get('checkfirstdatabasejoin');
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getEmundusParams() {
    try {
      return await fetchClient.get('getemundusparams');
    } catch (e) {
      return false;
    }
  },
  async getOnboardingLists() {
    return fetch('index.php?option=com_emundus&controller=settings&task=getonboardinglists').then((response) => {
      if (response.ok) {
        return response.json();
      } else {
        throw new Error('Get onboarding lists fetch failed');
      }
    }).then((data) => {
      return data;
    }).catch((error) => {
      return {
        status: false,
        msg: error.message
      };
    });
  },

  async getOffset() {
    try {
      return await fetchClient.get('getOffset');
    } catch (e) {
      return false;
    }
  },

  async redirectJRoute(link, language = 'fr-FR') {
    let formDatas = new FormData();
    formDatas.append('link', link);
    formDatas.append('redirect_language', language);

    fetch(window.location.origin + '/index.php?option=com_emundus&controller=settings&task=redirectjroute', {
      method: 'POST',
      body: formDatas,
    }).then((response) => {
      if (response.ok) {
        return response.json();
      }
      // eslint-disable-next-line no-undef
      throw new Error(Joomla.Text._('COM_EMUNDUS_ERROR_OCCURED'));
    }).then((result) => {
      if (result.status) {
        window.location.href = window.location.origin + '/' + result.data;
      }
    }).catch((error) => {
      window.location.reload();
    });
  },

  async getTimezoneList() {
    try {
      return await fetchClient.get('gettimezonelist');
    } catch (e) {
      return false;
    }
  },

  async saveParams(params) {
    const formData = new FormData();
    Object.keys(params).forEach(key => {
      formData.append('params[]', JSON.stringify(params[key]));
    });

    fetch(window.location.origin + '/index.php?option=com_emundus&controller=settings&task=updateemundusparams', {
      method: 'POST',
      body: formData,
    }).then((response) => {
      if (response.ok) {
        return response.json();
      }
      // eslint-disable-next-line no-undef
      throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
    }).then((result) => {
      if (result.status) {
        return result.data;
      }
    });
  },


  async saveColors(preset) {
    let data = {};
    data.preset = JSON.stringify(preset);

    try {
      return await fetchClient.post('updatecolor',data);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async getStatus() {
    try {
      return await fetchClient.get('getstatus');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getTags() {
    try {
      return await fetchClient.get('gettags');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getEmailSender() {
    try {
      return await fetchClient.get('getemailsender');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getLogo() {
    try {
      return await fetchClient.get('getlogo');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getVariables() {
    try {
      return await fetchClient.get('geteditorvariables');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getAllArticleNeedToModify()
  {
    try {
      return await fetchClient.get('getAllArticleNeedToModify');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getMedia()
  {
    try {
      return await fetchClient.get('getmedia');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async updateTagOrdering(orderedTags) {
    if (orderedTags.length > 0) {
      const data = {
        tags: orderedTags.join(',')
      };

      return fetchClient.post('updatetagsorder', data);
    } else {
      return {
        status: false,
        message: 'WRONG_PARAMETERS'
      };
    }
  },
  async getEmailParameters() {
    try {
      return await fetchClient.get('getemailparameters');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async testEmail(data) {
    try {
      return await fetchClient.post('testemail', data, null, 5000);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async saveEmailParameters(data) {
    try {
      return await fetchClient.post('saveemailparameters', data);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getLiveSite() {
    try {
      return await fetchClient.get('getlivesite');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async getsslinfo() {
    try {
      return await fetchClient.get('getsslinfo');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async sendRequest(data) {
    try {
        return await fetchClient.post('sendwebsecurityrequest', data);
    } catch (e) {
        return {
            status: false,
            msg: e.message
        };
    }
  },

  async getHistory(extension, only_pending = false, page = 1, limit = 10) {
    try {
      return await fetchClient.get('gethistory', {extension: extension, only_pending: only_pending, page: page, limit: limit});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },

  async updateHistoryStatus(id,status) {
    try {
      return await fetchClient.post('updatehistorystatus', {id: id, status: status});
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
};
