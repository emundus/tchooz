import { defineStore } from 'pinia';

export const useFormBuilderStore = defineStore('formbuilder', {
	state: () => ({
		lastSave: null,
		pages: null,
		pageElements: [],
		pageRules: [],
		documentModels: [],
		rulesKeywords: '',
		formId: 0,
	}),
	getters: {
		getLastSave: (state) => state.lastSave,
		getPages: (state) => state.pages,
		getDocumentModels: (state) => state.documentModels,
		getRulesKeywords: (state) => state.rulesKeywords,
		getPageElements: (state) => state.pageElements,
		getPageRules: (state) => state.pageRules,
		getFormId: (state) => state.formId,
		// Set of fabrik element names referenced by at least one rule
		// (as a condition field or an action target) on the current page.
		getRuledElementNames: (state) => {
			const names = new Set();

			state.pageRules.forEach((rule) => {
				Object.values(rule.conditions || {}).forEach((grouped_conditions) => {
					grouped_conditions.forEach((condition) => {
						if (condition.field) {
							names.add(condition.field);
						}
					});
				});

				(rule.actions || []).forEach((action) => {
					(action.fields || []).forEach((field) => {
						if (field) {
							names.add(field);
						}
					});
				});
			});

			return names;
		},
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
		updatePageRules(payload) {
			this.pageRules = payload;
		},
		updatePages(payload) {
			this.pages = payload;
		},
		updateFormId(payload) {
			this.formId = payload;
		},
	},
});
