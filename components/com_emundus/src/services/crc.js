/* jshint esversion: 8 */
import { FetchClient } from './fetchClient.js';

const fetchClient = new FetchClient('crc');

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
		if (data.addresses) {
			data.addresses = JSON.stringify(data.addresses);
		}
		try {
			return await fetchClient.post('savecontact', data);
		} catch (e) {
			let message = e.message;

			try {
				const parsed = JSON.parse(message);
				message = parsed.message ?? message;
			} catch (_) {}

			return {
				status: false,
				message: message,
			};
		}
	},

	async getOrganization(id) {
		try {
			return await fetchClient.get('getorganization', { id: id });
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async saveOrganization(data) {
		try {
			return await fetchClient.post('saveorganization', data);
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},
};
