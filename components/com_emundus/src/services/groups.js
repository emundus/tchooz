import { FetchClient } from './fetchClient.js';

const client = new FetchClient('groups');

export default {
	async getGroups() {
		try {
			return await client.get('getgroups');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
