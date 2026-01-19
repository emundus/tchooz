import { FetchClient } from './fetchClient.js';

const client = new FetchClient('mapping');

export default {
	async save(mappingData) {
		mappingData.rows.map((row) => {
			// if source_field is an object, get its value
			if (typeof row.source_field === 'object' && row.source_field !== null) {
				row.source_field = row.source_field.value;
			}

			return row;
		});

		try {
			return await client.post('flush', {
				mapping: JSON.stringify(mappingData),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
