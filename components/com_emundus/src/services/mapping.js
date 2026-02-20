import { FetchClient } from './fetchClient.js';

const client = new FetchClient('mapping');

export default {
	async save(mappingData) {
		if (typeof mappingData.target_object === 'object' && mappingData.target_object !== null) {
			mappingData.target_object = mappingData.target_object.value;
		}

		if (mappingData.params) {
			// foreach param entries, if param is an object, get its value
			Object.entries(mappingData.params).forEach(([key, param]) => {
				if (typeof param === 'object' && param !== null) {
					mappingData.params[key] = param.value;
				}
			});
		}

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
