/* jshint esversion: 8 */
import { FetchClient } from './fetchClient.js';

const client = new FetchClient('reference');

export default {
	async generate() {
		try {
			return await client.post('generate', []);
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async save() {
		try {
			return await client.post('save', []);
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},
};
