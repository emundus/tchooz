import { FetchClient } from './fetchClient.js';

export default {
	async requestData(controller, method, params = {}) {
		const client = new FetchClient(controller);

		try {
			return await client.post(method, params);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
