import { FetchClient } from './fetchClient.js';
import { useExportStore } from '../stores/export.js';

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

	async getElements(type, format, abortController = null) {
		const exportStore = useExportStore();
		if (exportStore.hasElement(type)) {
			return exportStore.getElement(type);
		}
		try {
			const result = await client.get(
				'elements',
				{ type: type, format: format },
				abortController && abortController.signal ? abortController.signal : null,
			);
			exportStore.setElement(type, result);
			return result;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getSubElements(elementId, type, abortController = null) {
		const exportStore = useExportStore();
		const key = `${elementId}_${type}`;
		if (exportStore.hasSubElement(key)) {
			return exportStore.getSubElement(key);
		}
		try {
			const result = await client.get(
				'getSubElements',
				{ elementId: elementId, type: type },
				abortController && abortController.signal ? abortController.signal : null,
			);
			exportStore.setSubElement(key, result);
			return result;
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
				title: e.name ? e.name : 'Error',
				msg: e.message,
				code: e.code ? e.code : null,
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
