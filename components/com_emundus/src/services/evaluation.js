import { FetchClient } from './fetchClient.js';
const fetchClient = new FetchClient('evaluation');

export default {
	async getEvaluationsForms(fnum, readonly = false, stepId = null) {
		try {
			return await fetchClient.get('getevaluationsforms', {
				fnum: fnum,
				readonly: readonly ? 1 : 0,
				step_id: stepId,
			});
		} catch (e) {
			return false;
		}
	},
	async getEvaluations(stepId, ccid) {
		try {
			return await fetchClient.get('getstepevaluationsforfile', {
				step_id: stepId,
				ccid: ccid,
			});
		} catch (e) {
			return false;
		}
	},
};
