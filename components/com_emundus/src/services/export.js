import { FetchClient } from './fetchClient.js';

const client = new FetchClient('export');

export default {
	async getAvailableFormats() {
		try {
			return await client.get('formats');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getElements(type) {
		try {
			return await client.get('elements', { type: type });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async export(format, async = false) {
		try {
			return await client.post(
				'export',
				{
					format: format,
					async: async,
				},
				null,
				8000,
			);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
