import { defineStore } from 'pinia';

export const useExportStore = defineStore('export', {
	state: () => ({
		elements: {}, // { [key]: data }
		subElements: {}, // { [key]: data }
	}),
	actions: {
		hasElement(key) {
			return !!this.elements[key];
		},
		setElement(key, data) {
			this.elements[key] = data;
		},
		getElement(key) {
			return this.elements[key];
		},
		hasSubElement(key) {
			return !!this.subElements[key];
		},
		setSubElement(key, data) {
			this.subElements[key] = data;
		},
		getSubElement(key) {
			return this.subElements[key];
		},
	},
});
