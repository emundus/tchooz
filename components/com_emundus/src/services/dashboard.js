/* jshint esversion: 8 */
import {FetchClient} from './fetchClient.js';

const client = new FetchClient('dashboard');
const programmeClient = new FetchClient('programme');

export default {
  async getWidgets() {
    try {
      return await client.get('getwidgets');
    } catch (e) {
      return {
        status: false, msg: e.message
      };
    }
  },

  async getProgrammes() {
    try {
      return await programmeClient.get('getallprogram');
    } catch (e) {
      return {
        status: false, msg: e.message
      };
    }
  },

  async getFilterProgramme() {
    try {
      return await client.get('getfilterprogramme');
    } catch (e) {
      return {
        status: false, msg: e.message
      };
    }
  },

  async setFilterProgramme(code) {
    try {
      return await client.post('setfilterprogramme', {code: code});
    } catch (e) {
      return {
        status: false, msg: e.message
      };
    }
  }
};
