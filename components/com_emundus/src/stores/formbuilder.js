import { defineStore } from 'pinia';

export const useFormBuilderStore = defineStore('formbuilder', {
	state: () => ({
		lastSave: null,
		pages: null,
		pageElements: [],
		documentModels: [],
		rulesKeywords: '',
	}),
	getters: {
		getLastSave: (state) => state.lastSave,
		getPages: (state) => state.pages,
		getDocumentModels: (state) => state.documentModels,
		getRulesKeywords: (state) => state.rulesKeywords,
		getPageElements: (state) => state.pageElements,
	},
	actions: {
		updateLastSave(payload) {
			this.lastSave = payload;
		},
		updateDocumentModels(payload) {
			this.documentModels = payload;
		},
		updateRulesKeywords(payload) {
			this.rulesKeywords = payload;
		},
		updatePageElements(payload) {
			this.pageElements = payload;
		},
	},
});
