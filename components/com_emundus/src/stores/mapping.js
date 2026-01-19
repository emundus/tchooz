// stores/automation.js
import { defineStore } from 'pinia';

export const useMappingStore = defineStore('mapping', {
	state: () => ({
		transformers: [],
	}),
	actions: {
		setTransformers(transformers) {
			this.transformers = transformers;
		},
		setDataResolvers(dataResolvers) {
			this.dataResolvers = dataResolvers;
		},
	},
	getters: {
		getTransformerByType(state) {
			return (type) => state.transformers.find((transformer) => transformer.type === type);
		},
		getTransformers(state) {
			return () => state.transformers;
		},
		getDataResolvers(state) {
			return () => state.dataResolvers;
		},
		getDataResolverByType(state) {
			return (type) => state.dataResolvers.find((resolver) => resolver.targetType === type);
		},
	},
});
