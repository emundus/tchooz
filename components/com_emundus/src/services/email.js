import { FetchClient } from './fetchClient.js';

const client = new FetchClient('email');

export default {
	async getEmails() {
		try {
			return await client.get('getallemail&order_by=label&sort=asc');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getEmailById(id) {
		try {
			return await client.get('getemailbyid&id=' + id);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateEmail(id, data) {
		Object.entries(data.body).forEach((item, index) => {
			data[item[0]] = item[1];
		});
		delete data.body;

		data.selectedReceiversCC = JSON.stringify(data.selectedReceiversCC);
		data.selectedReceiversBCC = JSON.stringify(data.selectedReceiversBCC);
		data.selectedLetterAttachments = JSON.stringify(data.selectedLetterAttachments);
		data.selectedCandidateAttachments = JSON.stringify(data.selectedCandidateAttachments);
		data.selectedTags = JSON.stringify(data.selectedTags);

		try {
			return await client.post('updateemail', data);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async createEmail(data) {
		Object.entries(data.body).forEach((item, index) => {
			data[item[0]] = item[1];
		});
		delete data.body;

		data.selectedReceiversCC = JSON.stringify(data.selectedReceiversCC);
		data.selectedReceiversBCC = JSON.stringify(data.selectedReceiversBCC);
		data.selectedLetterAttachments = JSON.stringify(data.selectedLetterAttachments);
		data.selectedCandidateAttachments = JSON.stringify(data.selectedCandidateAttachments);
		data.selectedTags = JSON.stringify(data.selectedTags);

		try {
			return await client.post('createemail', data);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getEmailCategories() {
		try {
			return await client.get('getemailcategories');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getEmailTriggerById(id) {
		try {
			return await client.get('gettriggerbyid&tid=' + id);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateEmailTrigger(id, trigger) {
		try {
			return await client.post('updatetrigger', {
				tid: id,
				trigger: JSON.stringify(trigger),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async createEmailTrigger(trigger) {
		try {
			return await client.post('createtrigger', {
				trigger: JSON.stringify(trigger),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getExpertConfig() {
		try {
			return await client.get('getexpertconfig');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getExperts() {
		try {
			return await client.get('getexpertslist');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async sendExpertEmail(data) {
		try {
			return await client.post('sendmail_expert', data);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async saveTrigger(trigger) {
		console.log(trigger);

		try {
			trigger.profile_ids = trigger.profile_ids.map(function (item) {
				return item.id;
			});
			trigger.group_ids = trigger.group_ids.map(function (item) {
				return item.id;
			});

			console.log(trigger);

			return await client.post('savetrigger', {
				trigger: JSON.stringify(trigger),
			});
		} catch (e) {
			console.log(e);

			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
