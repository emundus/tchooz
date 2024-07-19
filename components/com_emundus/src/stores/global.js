import { defineStore } from 'pinia';

export const useGlobalStore = defineStore('global', {
  state: () => ({
    datas: [],
    currentLanguage: '',
    shortLang: '',
    manyLanguages: '',
    defaultLang: '',
    coordinatorAccess: '',
    sysadminAccess: false,
    anonyme: false,
    offset: 0,
  }),
  getters: {
    getOffset: (state) => state.offset,
    getCurrentLang: state => state.currentLanguage,
    getShortLang: state => state.shortLang,
    hasManyLanguages: state => state.manyLanguages,
    hasSysadminAccess: state => state.sysadminAccess,
    hasCoordinatorAccess: state => state.coordinatorAccess,
    isAnonyme: state => state.anonyme,
    getDatas: state => state.datas
  },
  actions: {
    initAttachmentPath(path) {
      this.attachmentPath = path;
    },
    initDatas(datas) {
      this.datas = datas;
    },
    initCurrentLanguage(language) {
      this.currentLanguage = language;
    },
    initOffset(offset) {
      this.offset = offset;
    },
    initShortLang(language) {
      this.shortLang = language;
    },
    initManyLanguages(result) {
      this.manyLanguages = result;
    },
    initDefaultLang(lang) {
      this.defaultLang = lang;
    },
    initCoordinatorAccess(access) {
      this.coordinatorAccess = access;
    },
    initSysadminAccess(access) {
      this.sysadminAccess = access;
    },
    setAnonyme(anonyme) {
      this.anonyme = anonyme;
    }
  }
});
