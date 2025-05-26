/* jshint esversion: 8 */
import { FetchClient } from './fetchClient.js';

const fetchClient = new FetchClient('sign');

export default {
	async saveRequest(data) {
		try {
			return await fetchClient.post('saverequest', data);
		} catch (e) {
			return {
				status: false,
				error: e,
				message: e.message,
			};
		}
	},
};
