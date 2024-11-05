import {FetchClient} from './fetchClient.js';

const client = new FetchClient('messages');

export default {
  async getAllAttachments() {
    try {
      return await client.get('getallattachments');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getAllDocumentsLetters() {
    try {
      return await client.get('getalldocumentsletters');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
}