<script>
import Multiselect from 'vue-multiselect';
import Filter from '@/components/List/Filter.vue';
import Button from '@/components/Atoms/Button.vue';

import filtersService from '@/services/filters.js';

import alerts from '@/mixins/alerts.js';

export default {
	name: 'Filters',
	components: {
		Button,
		Filter,
		Multiselect,
	},
	props: {
		filters: {
			type: Object,
			default: () => {},
		},
		currentTab: { type: Object, default: () => {} },
		currentTabKey: {
			type: Number,
			default: 0,
		},
		displayPresavedFilters: {
			type: Boolean,
			default: false,
		},
	},
	emits: ['update-filter'],
	mixins: [alerts],
	data() {
		return {
			currentFilter: null,

			displayedFilters: [],

			selectedPresavedFilter: 'default',
			presavedFilters: [],
			presavedFiltersLoading: true,
		};
	},
	created() {
		// Get displayedFilters from session
		const storedFilters = Object.keys(sessionStorage).filter((key) =>
			key.includes('tchooz_filter_' + this.currentTabKey),
		);
		if (storedFilters.length > 0) {
			this.displayedFilters = storedFilters.map((key) => {
				let filter = key.split('_').pop();
				filter = filter.split('/').shift();

				if (filter && this.filters[this.currentTabKey]) {
					let filterData = this.filters[this.currentTabKey].find((f) => f.key === filter);
					if (
						typeof filterData !== 'undefined' &&
						(typeof filterData.alwaysDisplay === 'undefined' || filterData.alwaysDisplay !== true)
					) {
						return filterData;
					}
				}

				return null;
			});

			// Remove null values
			this.displayedFilters = this.displayedFilters.filter((f) => f !== null);
		}

		// Check if we have a filter in the URL
		if (this.filters[this.currentTabKey] && this.filters[this.currentTabKey].length > 0) {
			const urlParams = new URLSearchParams(window.location.search);
			this.filters[this.currentTabKey].forEach((filter) => {
				if (filter.alwaysDisplay) {
					return;
				}

				if (urlParams.has(filter.key)) {
					const value = urlParams.get(filter.key);
					if (filter.options.find((option) => option.value == value) || filter.type === 'date') {
						filter.value = value;
						// Check if filter is already displayed
						if (!this.displayedFilters.find((f) => f.key === filter.key)) {
							this.displayedFilters.push(filter);
						} else {
							// Update filter
							this.displayedFilters = this.displayedFilters.map((f) => {
								if (f.key === filter.key) {
									f.value = value;
								}
								return f;
							});
						}

						sessionStorage.setItem(
							'tchooz_filter_' + this.currentTabKey + '_' + filter.key + '/' + document.location.hostname,
							filter.value,
						);
					}
				}
			});
		}

		if (this.displayedFilters.length > 0) {
			this.displayedFilters.sort((a, b) => a.key.localeCompare(b.key));
		}

		// Get presaved filters
		filtersService.getListFilters(this.currentTab.key).then((res) => {
			this.presavedFilters = res;

			// Check in sessionStorage if we have a selected presaved filter
			const storedSelectedPresavedFilter = sessionStorage.getItem(
				'tchooz_selected_presaved_filter_' + this.currentTabKey + '/' + document.location.hostname,
			);
			if (storedSelectedPresavedFilter) {
				const selectedFilter = this.presavedFilters.find((f) => f.id == storedSelectedPresavedFilter);
				if (selectedFilter) {
					this.selectedPresavedFilter = selectedFilter;
					//this.updateSelectedFilter();
				}
			}

			this.presavedFiltersLoading = false;
		});
	},
	methods: {
		setFilter(filter) {
			let fieldValue = '';
			if (filter.type === 'multiselect' && Array.isArray(filter.value)) {
				const values = filter.value.map((val) =>
					typeof val === 'object' && val !== null && 'value' in val ? val.value : val,
				);
				fieldValue = values.join(',');
			} else {
				fieldValue =
					typeof filter.value === 'object' && filter.value !== null && 'value' in filter.value
						? filter.value.value
						: filter.value;
			}

			// Store value to sessionStorage
			sessionStorage.setItem(
				'tchooz_filter_' + this.currentTabKey + '_' + filter.key + '/' + document.location.hostname,
				fieldValue,
			);

			// Update URL
			const urlParams = new URLSearchParams(window.location.search);
			if (urlParams.has(filter.key)) {
				urlParams.set(filter.key, fieldValue);
			} else {
				urlParams.append(filter.key, fieldValue);
			}
			const newUrl =
				window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + urlParams.toString();
			window.history.replaceState({ path: newUrl }, '', newUrl);
		},
		unsetFilter(filter) {
			this.displayedFilters = this.displayedFilters.filter((f) => f.key !== filter.key);

			// Reset value of filter
			filter.value = filter.default ? filter.default : 'all';

			// Remove from sessionStorage
			sessionStorage.removeItem(
				'tchooz_filter_' + this.currentTabKey + '_' + filter.key + '/' + document.location.hostname,
			);

			// Remove from URL if exists
			const urlParams = new URLSearchParams(window.location.search);
			if (urlParams.has(filter.key)) {
				urlParams.delete(filter.key);
				const newUrl =
					window.location.protocol +
					'//' +
					window.location.host +
					window.location.pathname +
					'?' +
					urlParams.toString();
				window.history.replaceState({ path: newUrl }, '', newUrl);
			}
		},
		onChangeFilter(filter) {
			this.setFilter(filter);

			// when we change a filter, we reset the pagination
			this.$emit('update-filter');
		},

		removeFilter(filter) {
			this.unsetFilter(filter);

			this.$emit('update-filter');
		},

		labelTranslate({ label }) {
			return this.translate(label);
		},

		saveCurrentFilters() {
			let filtersToSave = {};
			for (const filter of this.defaultFilters) {
				let fieldValue = '';
				if (filter.type === 'multiselect' && Array.isArray(filter.value)) {
					const values = filter.value.map((val) =>
						typeof val === 'object' && val !== null && 'value' in val ? val.value : val,
					);
					fieldValue = values.join(',');
				} else {
					fieldValue =
						typeof filter.value === 'object' && filter.value !== null && 'value' in filter.value
							? filter.value.value
							: filter.value;
				}

				filtersToSave[filter.key] = fieldValue;
			}

			// Ask a name for the filters
			let filterName = '';
			let id = 0;
			if (this.selectedPresavedFilter === 'default') {
				filterName = prompt('Entrez un nom pour ce filtre personnalisé :');
				if (filterName === '') {
					alert("Le nom du filtre est requis pour l'enregistrement.");
					return;
				}
				if (filterName === null) {
					return;
				}
			} else {
				filterName = this.selectedPresavedFilter.name;
				id = this.selectedPresavedFilter.id;
			}

			filtersService.saveListFilters(filtersToSave, filterName, this.currentTab.key, id).then((res) => {
				// Add it to the list of presaved filters if it's a new one
				if (this.selectedPresavedFilter === 'default') {
					this.presavedFilters.push(res);
					this.selectedPresavedFilter = res;

					sessionStorage.setItem(
						'tchooz_selected_presaved_filter_' + this.currentTabKey + '/' + document.location.hostname,
						res.id,
					);
				}
			});
		},
		updateSelectedFilter() {
			let value = this.selectedPresavedFilter;

			if (value === 'default') {
				// Clear all filters
				for (const filter of this.filters[this.currentTabKey]) {
					if (!filter.multiselect) {
						filter.value = filter.default ? filter.default : 'all';
					} else {
						filter.value = '';
					}

					this.setFilter(filter);
				}
			} else {
				// Apply filters
				for (const filter of this.filters[this.currentTabKey]) {
					if (value.constraints[filter.key]) {
						filter.value = value.constraints[filter.key];
						this.setFilter(filter);
					} else {
						if (!filter.multiselect) {
							filter.value = filter.default ? filter.default : 'all';
						} else {
							filter.value = '';
						}

						this.setFilter(filter);
					}
				}
			}

			// Store presaved filter selection in sessionStorage
			sessionStorage.setItem(
				'tchooz_selected_presaved_filter_' + this.currentTabKey + '/' + document.location.hostname,
				value.id,
			);

			this.$emit('update-filter');
		},
		deleteSavedFilter() {
			this.alertConfirm(
				'COM_EMUNDUS_FILTER_PRESAVED_FILTERS_DELETE',
				'COM_EMUNDUS_FILTER_PRESAVED_FILTERS_DELETE_CONFIRM',
				false,
				'COM_EMUNDUS_ONBOARD_ADD_EVENT_SLOT_DELETE_CONFIRM_YES',
			).then((confirm) => {
				if (confirm.isConfirmed) {
					filtersService.deleteListFilters(this.selectedPresavedFilter.id).then(() => {
						// Remove from list
						this.presavedFilters = this.presavedFilters.filter((f) => f.id !== this.selectedPresavedFilter.id);
						this.selectedPresavedFilter = 'default';
						sessionStorage.removeItem(
							'tchooz_selected_presaved_filter_' + this.currentTabKey + '/' + document.location.hostname,
						);

						this.updateSelectedFilter();
					});
				}
			});
		},
	},
	computed: {
		availableFilters() {
			return this.filters && this.filters[this.currentTabKey]
				? this.filters[this.currentTabKey].filter((filter) => {
						return (
							(((filter.type === 'select' || filter.type === 'multiselect') && filter.options.length > 0) ||
								filter.type === 'date' ||
								filter.type === 'time') &&
							!filter.alwaysDisplay
						);
					})
				: [];
		},
		defaultFilters() {
			return this.filters && this.filters[this.currentTabKey]
				? this.filters[this.currentTabKey].filter((filter) => {
						return (
							(((filter.type === 'select' || filter.type === 'multiselect') && filter.options.length > 0) ||
								filter.type === 'date' ||
								filter.type === 'time') &&
							filter.alwaysDisplay
						);
					})
				: [];
		},
	},
	watch: {
		currentFilter(value) {
			if (value && !this.displayedFilters.find((filter) => filter.key === value.key)) {
				this.displayedFilters.push(value);
				this.displayedFilters.sort((a, b) => a.key.localeCompare(b.key));

				// Store value to sessionStorage
				sessionStorage.setItem(
					'tchooz_filter_' + this.currentTabKey + '_' + value.key + '/' + document.location.hostname,
					value.value,
				);

				this.currentFilter = null;
			}
		},
	},
};
</script>

<template>
	<section id="tab-filters" class="tw-w-full">
		<div
			v-if="availableFilters.length > 0"
			:class="{ 'tw-mb-4': displayedFilters.length > 0 || defaultFilters.length > 0 }"
		>
			<label class="tw-mb-2 tw-font-medium">
				{{ translate('COM_EMUNDUS_ADD_FILTER') }}
			</label>
			<div class="tw-grid tw-grid-cols-3 tw-gap-4">
				<multiselect
					:id="'select-filter-' + currentTabKey"
					v-model="currentFilter"
					:label="'label'"
					:custom-label="labelTranslate"
					:track-by="'key'"
					:options="availableFilters"
					:options-limit="100"
					:multiple="false"
					:taggable="false"
					:placeholder="translate('COM_EMUNDUS_FILTERS_CHOOSE_FILTER')"
					:select-label="translate('PRESS_ENTER_TO_SELECT')"
					:searchable="true"
					:preserve-search="true"
				>
					<template #noResult>{{ translate('COM_EMUNDUS_MULTISELECT_NORESULTS') }}</template>
				</multiselect>
			</div>
		</div>

		<div class="tw-grid tw-grid-cols-3 tw-gap-4">
			<div
				v-for="filter in defaultFilters"
				:key="currentTabKey + '-' + filter.key"
				class="tw-flex tw-flex-col tw-gap-1"
			>
				<Filter :filter="filter" @change-filter="onChangeFilter" />
			</div>

			<div
				v-for="filter in displayedFilters"
				:key="currentTabKey + '-' + filter.key"
				class="tw-flex tw-flex-col tw-gap-1"
			>
				<Filter :filter="filter" @change-filter="onChangeFilter" @remove-filter="removeFilter" />
			</div>
		</div>

		<div
			class="tw-mt-4 tw-flex tw-flex-wrap tw-items-end tw-gap-2"
			v-if="displayPresavedFilters && !presavedFiltersLoading"
		>
			<div class="tw-flex tw-flex-col tw-gap-1">
				<label class="!tw-mb-0 tw-font-medium">
					{{ translate('COM_EMUNDUS_FILTER_PRESAVED_FILTERS') }}
				</label>
				<select v-model="selectedPresavedFilter" @change="updateSelectedFilter">
					<option value="default">{{ translate('COM_EMUNDUS_FILTER_PRESAVED_FILTERS_PLEASE_SELECT') }}</option>
					<option v-for="filter in presavedFilters" :key="filter.id" :value="filter">
						{{ filter.name }}
					</option>
				</select>
			</div>
			<Button style="margin-bottom: 2px" @click="saveCurrentFilters">{{
				translate('COM_EMUNDUS_FILTER_PRESAVED_FILTERS_SAVE_CURRENT_FILTER')
			}}</Button>
			<Button
				style="margin-bottom: 2px"
				variant="cancel"
				@click="deleteSavedFilter"
				v-if="selectedPresavedFilter !== 'default'"
			>
				<span class="material-symbols-outlined">delete</span>
			</Button>
		</div>
	</section>
</template>

<style scoped></style>
