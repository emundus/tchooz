import { FetchClient } from './fetchClient.js';

const client = new FetchClient('import');

export default {
	async getEntityImportInformation(type) {
		try {
			return await client.get('getEntityImportInformation&type=' + type, {});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async dryRun(type, file, mode) {
		try {
			return await client.post('dryrun&type=' + type + '&mode=' + mode, { file }, null, 30000);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async importFile(type, file, mode) {
		try {
			return await client.post('import&type=' + type + '&mode=' + mode, { file }, null, 30000);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getImportProgress(importId) {
		try {
			return await client.get('getimportprogress&id=' + importId, {});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getImportReport(importId) {
		try {
			return await client.get('getimportreport&id=' + importId, {});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async cancelImport(importId) {
		try {
			return await client.post('cancelimport&id=' + importId, {});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getImportModel(type, format = 'xlsx') {
		try {
			return await client.post('getimportmodel&type=' + type, { format: format });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
