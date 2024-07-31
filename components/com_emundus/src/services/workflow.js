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
  },
  async saveWorkflow(workflow, steps, programs) {
    try {
      const data = {
        workflow: JSON.stringify(workflow),
        steps: JSON.stringify(steps),
        programs: JSON.stringify(programs)
      };

      return await client.post('updateworkflow', data);
    } catch (e) {
      return {
        status: false, msg: e.message
      };
    }
  }
};