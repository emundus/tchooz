/* jshint esversion: 8 */
import client from './axiosClient';

import {FetchClient} from './fetchClient.js';
const fetchClient = new FetchClient('files');

export default {
  async getFnums() {
    try {
      const response = await client().get('index.php?option=com_emundus&controller=files&task=getallfnums');

      return response.data;
    } catch (e) {
      return false;
    }
  },
  async getFnumInfos(fnum) {
    try {
      const response = await client().get('index.php?option=com_emundus&controller=files&task=getfnuminfos', {
        params: {
          fnum
        }
      });

      return response.data;
    } catch (e) {
      return false;
    }
  },
  async isDataAnonymized() {
    try {
      const response = await client().get('index.php?option=com_emundus&controller=files&task=isdataanonymized');

      return response.data;
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  },
  async getProfiles() {
    try {
      return await fetchClient.get('getprofiles');
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  }
}