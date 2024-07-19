import {FetchClient} from './fetchClient.js';

const client = new FetchClient('programme');

export default {
  async getCampaignsByProgram(programId) {
    try {
      return await client.get('getcampaignsbyprogram', {
        pid: programId
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getAllPrograms() {
    try {
      return await client.get('getallprogram');
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async createProgram(program){
    try {
      return await client.post('createprogram', {
        body: JSON.stringify(program)
      });

    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
}