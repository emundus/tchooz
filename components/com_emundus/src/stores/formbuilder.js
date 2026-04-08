import { defineStore } from 'pinia';

export const useFormBuilderStore = defineStore('formbuilder', {
	state: () => ({
		lastSave: null,
		pages: null,
		pageElements: [],
		documentModels: [],
		rulesKeywords: '',
		formId: null,
	}),
	getters: {
		getLastSave: (state) => state.lastSave,
		getPages: (state) => state.pages,
		getDocumentModels: (state) => state.documentModels,
		getRulesKeywords: (state) => state.rulesKeywords,
		getPageElements: (state) => state.pageElements,
		getFormId: (state) => state.formId,
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
		updatePages(payload) {
			this.pages = payload;
		},
		updateFormId(payload) {
			this.formId = payload;
		},
	},
});
