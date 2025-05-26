/* jshint esversion: 8 */
import { FetchClient } from './fetchClient.js';

const fetchClient = new FetchClient('contacts');

export default {
	async getContact(id) {
		try {
			return await fetchClient.get('getcontact', { id: id });
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async saveContact(data) {
		try {
			return await fetchClient.post('savecontact', data);
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},
};
