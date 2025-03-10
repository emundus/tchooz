import { FetchClient } from './fetchClient.js';

const client = new FetchClient('sms');

export default {
	async getSmsTemplate(id) {
		try {
			return await client.get('getSmsTemplate', { id: id });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getSmsTemplates() {
		try {
			return await client.get('getSmsTemplates');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateTemplate(sms) {
		try {
			return await client.post('updateTemplate', {
				id: sms.id,
				label: sms.label,
				message: sms.message,
				category_id: sms.category_id,
				success_tag: sms.success_tag,
				failure_tag: sms.failure_tag,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getGlobalHistory(page = 1, limit = 20, search = '', status = '') {
		try {
			return await client.get('getGlobalSMSHistory', { page: page, limit: limit, search: search, status: status });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getHistory(fnum) {
		try {
			return await client.get('getSMSHistory', { fnum: fnum });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async sendSMS(fnums, message, template_id = 0) {
		try {
			return await client.post('sendSMS', {
				message: message,
				fnums: fnums,
				template_id: template_id,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getSMSCategories() {
		try {
			return await client.get('getSMSCategories');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateSMSCategory(categoryId, label) {
		try {
			return await client.post('updateSMSCategory', {
				category_id: categoryId,
				label: label,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async createSMSCategory(label) {
		try {
			return await client.post('createSMSCategory', {
				label: label,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async isSMSActivated() {
		try {
			return await client.get('isSMSActivated');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
