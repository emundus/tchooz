import { FetchClient } from '@/services/fetchClient.js';

const client = new FetchClient('language');

export default {
	async getPlatformLanguages() {
		return await client.get('getplatformlanguages');
	},

	async saveTranslation(id, langCode, override) {
		return await client.post('savetranslation', {
			id: id,
			lang_code: langCode,
			override: override,
		});
	},

	/**
	 * Search existing language strings through the core com_languages route.
	 *
	 * @param {string} searchString
	 * @param {string} searchType  'constant' to search by key, anything else searches by value.
	 * @param {number} more  Offset of already loaded results (0 for a fresh search).
	 * @returns {Promise<{results: {constant: string, string: string, file: string}[], more: number|null}>}
	 */
	async searchStrings(searchString, searchType = 'constant', more = 0) {
		const response = await client.get('search', {
			searchstring: searchString,
			searchtype: searchType,
			more: more,
		});

		return {
			results: Array.isArray(response.data.results) ? response.data.results : [],
			more: typeof response.data.more !== 'undefined' ? response.data.more : null,
		};
	},

	/**
	 * @param {string} tag
	 * @param {Object} overrides  Map of lang_code to text. Bracket notation so PHP rebuilds an array.
	 */
	async addTranslation(tag, overrides) {
		const data = { tag: tag };
		Object.entries(overrides).forEach(([langCode, override]) => {
			data[`overrides[${langCode}]`] = override;
		});

		return await client.post('addtranslation', data);
	},
};
