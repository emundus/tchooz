<script>
import Multiselect from 'vue-multiselect';

export default {
	name: 'Filters',
	components: {
		Multiselect,
	},
	props: {
		filters: {
			type: Object,
			default: () => {},
		},
		currentTabKey: {
			type: Number,
			default: 0,
		},
	},
	emits: ['update-filter'],
	data() {
		return {
			currentFilter: null,

			displayedFilters: [],
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
		const urlParams = new URLSearchParams(window.location.search);
		this.filters[this.currentTabKey].forEach((filter) => {
			if (urlParams.has(filter.key)) {
				const value = urlParams.get(filter.key);
				if (filter.options.find((option) => option.value == value)) {
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

		if (this.displayedFilters.length > 0) {
			this.displayedFilters.sort((a, b) => a.key.localeCompare(b.key));
		}
	},
	methods: {
		onChangeFilter(filter) {
			// Store value to sessionStorage
			sessionStorage.setItem(
				'tchooz_filter_' + this.currentTabKey + '_' + filter.key + '/' + document.location.hostname,
				filter.value,
			);

			// Update URL
			const urlParams = new URLSearchParams(window.location.search);
			if (urlParams.has(filter.key)) {
				urlParams.set(filter.key, filter.value);
			} else {
				urlParams.append(filter.key, filter.value);
			}
			const newUrl =
				window.location.protocol + '//' + window.location.host + window.location.pathname + '?' + urlParams.toString();
			window.history.replaceState({ path: newUrl }, '', newUrl);

			// when we change a filter, we reset the pagination
			this.$emit('update-filter');
		},

		removeFilter(filter) {
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

			this.$emit('update-filter');
		},

		labelTranslate({ label }) {
			return this.translate(label);
		},
	},
	computed: {
		availableFilters() {
			return this.filters && this.filters[this.currentTabKey]
				? this.filters[this.currentTabKey].filter((filter) => {
						return filter.options.length > 0 && !filter.alwaysDisplay;
					})
				: [];
		},
		defaultFilters() {
			return this.filters && this.filters[this.currentTabKey]
				? this.filters[this.currentTabKey].filter((filter) => {
						return filter.options.length > 0 && filter.alwaysDisplay;
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
		<div v-if="availableFilters.length > 0" :class="{ 'tw-mb-4': displayedFilters.length > 0 }">
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
				<div class="tw-flex tw-items-center tw-justify-between">
					<label class="!tw-mb-0 tw-font-medium">
						{{ translate(filter.label) }}
					</label>
				</div>
				<select v-model="filter.value" @change="onChangeFilter(filter)">
					<option v-for="option in filter.options" :key="option.value" :value="option.value">
						{{ translate(option.label) }}
					</option>
				</select>
			</div>

			<div
				v-for="filter in displayedFilters"
				:key="currentTabKey + '-' + filter.key"
				class="tw-flex tw-flex-col tw-gap-1"
			>
				<div class="tw-flex tw-items-center tw-justify-between">
					<label class="!tw-mb-0 tw-font-medium">
						{{ translate(filter.label) }}
					</label>
					<span class="material-icons-outlined tw-cursor-pointer tw-text-red-500" @click="removeFilter(filter)">
						close
					</span>
				</div>
				<select v-model="filter.value" @change="onChangeFilter(filter)">
					<option v-for="option in filter.options" :key="option.value" :value="option.value">
						{{ translate(option.label) }}
					</option>
				</select>
			</div>
		</div>
	</section>
</template>

<style scoped></style>
