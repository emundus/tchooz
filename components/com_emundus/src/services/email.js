import {FetchClient} from './fetchClient.js';

const client = new FetchClient('email');

export default {
  async getEmails() {
    try {
      return await client.get('getallemail');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getEmailById(id) {
    try {
      return await client.get('getemailbyid&id=' + id);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async updateEmail(id, data) {
    try {
      return await client.post('updateemail', {
        code: id,
        body: JSON.stringify(data.body),
        selectedReceiversCC: JSON.stringify(data.selectedReceiversCC),
        selectedReceiversBCC: JSON.stringify(data.selectedReceiversBCC),
        selectedLetterAttachments: JSON.stringify(data.selectedLetterAttachments),
        selectedCandidateAttachments: JSON.stringify(data.selectedCandidateAttachments),
        selectedTags: JSON.stringify(data.selectedTags),
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async createEmail(data) {
    try {
      return await client.post('createemail', {
        body: JSON.stringify(data.body),
        selectedReceiversCC: JSON.stringify(data.selectedReceiversCC),
        selectedReceiversBCC: JSON.stringify(data.selectedReceiversBCC),
        selectedLetterAttachments: JSON.stringify(data.selectedLetterAttachments),
        selectedCandidateAttachments: JSON.stringify(data.selectedCandidateAttachments),
        selectedTags: JSON.stringify(data.selectedTags),
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getEmailCategories() {
    try {
      return await client.get('getemailcategories');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getEmailTriggerById(id) {
    try {
      return await client.get('gettriggerbyid&tid=' + id);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async updateEmailTrigger(id, trigger) {
    try {
      return await client.post('updatetrigger', {
        tid: id,
        trigger: JSON.stringify(trigger)
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async createEmailTrigger(trigger) {
    try {
      return await client.post('createtrigger', {
        trigger: JSON.stringify(trigger)
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
}