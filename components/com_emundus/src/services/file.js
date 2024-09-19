import {FetchClient} from './fetchClient.js';
const fetchClient = new FetchClient('files');
export default {
  async getFnums() {
    try {
      return await client.get('getallfnums');
    } catch (e) {
      return false;
    }
  },
  async getFnumInfos(fnum) {
    try {
      return await client.get('getfnuminfos', {
        fnum: fnum
      });
    } catch (e) {
      return false;
    }
  },
  async isDataAnonymized() {
    try {
      return await client.get('isdataanonymized');
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
  },
  async getFileIdFromFnum(fnum) {
    try {
      return await fetchClient.get('getFileIdFromFnum', {
        fnum: fnum
      });
    } catch (e) {
      return {
        status: false,
        message: e.message
      };
    }
  }
}