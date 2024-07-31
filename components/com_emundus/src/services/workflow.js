import {FetchClient} from './fetchClient.js';

const client = new FetchClient('workflow');

export default {
  async getWorkflow(id) {
    try {
      return await client.get('getworkflow', {id: id});
    } catch (e) {
      return {
        status: false, msg: e.message
      };
    }
  }
};