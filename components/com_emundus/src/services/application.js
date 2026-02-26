/* jshint esversion: 8 */
import { FetchClient } from './fetchClient.js';

const client = new FetchClient('application');

export default {
	async getApplicationChoices(fnum, step_id = 0) {
		try {
			return await client.get('getapplicationchoices', { fnum, step_id });
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async addChoice(campaign_id, fnum) {
		try {
			return await client.post('addchoice', {
				campaign_id: campaign_id,
				fnum: fnum,
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async removeChoice(id, fnum) {
		try {
			return await client.post('removechoice', {
				id: id,
				fnum: fnum,
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async reorderChoices(choices, fnum) {
		try {
			return await client.post('reorderchoices', {
				choices: JSON.stringify(choices),
				fnum: fnum,
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async sendChoicesStep() {
		try {
			return await client.post('sendchoicesstep');
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async updateStatus(id, status) {
		try {
			return await client.post('updatechoicestatus', {
				id: id,
				status: status,
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async getChoicesConfiguration(fnum) {
		try {
			return await client.get('getchoicesconfiguration', { fnum });
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async confirmChoice(choice_id, fnum) {
		try {
			return await client.post('confirmchoice', {
				id: choice_id,
				fnum: fnum,
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async refuseChoice(choice_id, fnum) {
		try {
			return await client.post('refusechoice', {
				id: choice_id,
				fnum: fnum,
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async getChoicesStates() {
		try {
			return await client.get('getchoicesstates');
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async getUploadById(id) {
		try {
			return await client.get('getuploadbyid', {
				id: id,
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},
};
