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
  async getAllPrograms(search = '', category = '', lim = 0, page = 0, order_by = '', order = 'ASC') {
    try {
      return await client.get('getallprogram', {
        recherche: search,
        category: category,
        lim: lim,
        page: page,
        sort: order,
        order_by: order_by
      });
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