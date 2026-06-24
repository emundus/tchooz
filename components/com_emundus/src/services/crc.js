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

	async updateContactFiles(contactId, filesIds) {
		try {
			return await fetchClient.post('updatecontactfiles', {
				contact_id: contactId,
				filesIds: filesIds,
			});
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async updateOrganizationFiles(organizationId, fnums) {
		try {
			return await fetchClient.post('updateorganizationfiles', {
				organization_id: organizationId,
				fnums: fnums,
			});
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async updateFileContacts(data) {
		try {
			return await fetchClient.post('updatefilecontacts', data);
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async getFileContacts(fnum) {
		try {
			return await fetchClient.get('getfilecontacts', { fnum: fnum });
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async updateFileOrganizations(data) {
		try {
			return await fetchClient.post('updatefileorganizations', data);
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async getFileOrganizations(fnum) {
		try {
			return await fetchClient.get('getfileorganizations', { fnum: fnum });
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async getContactFiles(id) {
		try {
			return await fetchClient.get('getcontactfiles', { id: id });
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async getOrganizationFiles(id) {
		try {
			return await fetchClient.get('getorganizationfiles', { id: id });
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},
};
