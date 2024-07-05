"use strict";

import Vue from 'vue';
import Vuex from 'vuex';

Vue.use(Vuex);

const state = {
  needSaving: false,
};

const getters = {
  needSaving: state => state.needSaving,
};

const actions = {
  setNeedSaving({ commit }, needSaving) {
    commit('setNeedSaving', needSaving);
  },
};

const mutations = {
  setNeedSaving(state, needSaving) {
    state.needSaving = needSaving;
  },
};

export default {
  namespaced: true,
  state,
  getters,
  actions,
  mutations
};
