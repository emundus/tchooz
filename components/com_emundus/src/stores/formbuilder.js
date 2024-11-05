import { defineStore } from 'pinia';

export const useFormBuilderStore = defineStore('formbuilder',{
  state: () => ({
    lastSave: null,
    pages: null,
    documentModels: [],
    rulesKeywords: ''
  }),
  getters: {
    getLastSave: state => state.lastSave,
    getPages: state => state.pages,
    getDocumentModels: state => state.documentModels,
    getRulesKeywords: state => state.rulesKeywords,
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
    }
  },
});