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

	async getElements(type, format) {
		try {
			return await client.get('elements', { type: type, format: format });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getDefaultSynthesisElements(format) {
		try {
			return await client.get('defaultsynthesis', { format: format });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getDefaultHeaderElements() {
		try {
			return await client.get('defaultheader');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async export(
		format,
		selectedElementsIds,
		selectedHeadersIds,
		selectedSynthesisIds,
		selectedAttachmentIds,
		async = false,
	) {
		let timeout = format === 'xlsx' ? 10000 : null;

		try {
			return await client.post(
				'export',
				{
					format: format,
					elements: selectedElementsIds,
					headers: selectedHeadersIds,
					synthesis: selectedSynthesisIds,
					attachments: selectedAttachmentIds,
					async: async,
					version: 'next',
				},
				null,
				timeout,
			);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getExportTemplates() {
		try {
			return await client.get('templates');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getElementsFromSaveExport(id) {
		try {
			return await client.get('elementsfromsavedexport', { id: id });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async saveExport(
		name,
		format,
		selectedElementsIds,
		selectedHeadersIds,
		selectedSynthesisIds,
		selectedAttachmentIds,
		id,
	) {
		try {
			return await client.post('saveexport', {
				name: name,
				format: format,
				elements: selectedElementsIds,
				headers: selectedHeadersIds,
				synthesis: selectedSynthesisIds,
				attachments: selectedAttachmentIds,
				id: id,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async deleteExport(id) {
		try {
			return await client.post('deletetemplate', {
				id: id,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
