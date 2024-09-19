import {FetchClient} from './fetchClient.js';
const fetchClient = new FetchClient('evaluation');


export default {
  async getEvaluationsForms(fnum) {
    try {
      return await fetchClient.get('getevaluationsforms', {
        fnum: fnum
      });
    } catch (e) {
      return false;
    }
  }
}