import { defineStore } from 'pinia';

export const useResourceStore = defineStore('resource', {
	state: () => ({
		// Loaded document previews, keyed by numeric resource id, so browsing back to an
		// already-viewed document renders instantly instead of refetching it.
		previews: {},
	}),
	getters: {
		getPreview: (state) => (id) => state.previews[id] ?? null,
	},
	actions: {
		setPreview(id, preview) {
			this.previews[id] = preview;
		},
	},
});
