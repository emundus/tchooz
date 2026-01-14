<template>
	<div id="emundus-filters" class="tw-w-full">
		<section id="filters-top-actions" class="tw-mb-4">
			<button id="clear-filters" class="tw-mb-4 tw-cursor-pointer tw-text-red-500 tw-underline" @click="clearFilters">
				{{ translate('MOD_EMUNDUS_FILTERS_CLEAR_FILTERS') }}
			</button>

			<RegisteredFilters
				:filters="registeredFilters"
				:selected-filter="selectedRegisteredFilter"
				:filter-to-rename="filterToRename"
				:opened="openedRegisteredFilters"
				:can-share-filters="canShareFilters"
				:has-unsaved="unsavedNewFilter"
				@toggle-opened="toggleOpened"
				@select="onSelectRegisteredFilter"
				@save-new="saveNewFilter"
				@save-temp="saveRegisteredFilters"
				@edit-name="editFilterName"
				@on-rename="onRenameFilter"
				@update="updateFilter"
				@share="shareFilter"
				@delete="deleteRegisteredFilter"
				@define-default="defineAsDefault"
				@toggle-favorite="toggleFilterFavoriteState"
			/>

			<hr style="margin: 20px 32px" />

			<div id="global-search-wrapper" class="tw-relative">
				<div
					id="global-search-values"
					ref="globalSearchValues"
					class="tw-flex tw-flex-wrap tw-items-center tw-rounded-coordinator-form tw-border tw-border-neutral-400 tw-bg-white"
					@click="onEnterGlobalSearchDiv"
				>
					<div v-if="globalSearch.length > 0" class="tw-flex tw-flex-wrap tw-items-center">
						<div
							v-for="value in globalSearch"
							:key="value.value + '-' + value.scope"
							class="global-search-tag tw-m-1 tw-flex tw-w-auto tw-items-center tw-rounded-coordinator tw-border tw-border-neutral-400 tw-shadow-sm"
						>
							<span style="white-space: nowrap">{{ translatedScope(value.scope) }} : {{ value.value }}</span>
							<span
								class="material-symbols-outlined tw-cursor-pointer"
								@click="removeGlobalSearchValue(value.value, value.scope)"
								>clear</span
							>
						</div>
					</div>
					<input
						id="current-global-search"
						ref="globalSearchInput"
						class="tw-rounded-coordinator"
						v-model="currentGlobalSearch"
						type="text"
						@keyup.enter="
							(e) => {
								this.onGlobalSearchChange(e, 'everywhere');
							}
						"
						:placeholder="globalSearchPlaceholder"
					/>
				</div>
				<ul
					id="select-scopes"
					class="tw-w-full tw-rounded-coordinator tw-border tw-border-neutral-400 tw-bg-white tw-shadow-standard"
					:class="{ hidden: currentGlobalSearch.length < 1 }"
				>
					<li
						v-for="option in globalSearchScopes"
						:key="option.value"
						@click="
							(e) => {
								this.onGlobalSearchChange(e, option.value);
							}
						"
						class="global-search-scope tw-cursor-pointer"
					>
						<button>
							{{ currentGlobalSearch }} {{ translate('MOD_EMUNDUS_FILTERS_SCOPE_IN') }}
							{{ translate(option.label) }}
						</button>
					</li>
				</ul>
			</div>
		</section>
		<section id="applied-filters">
			<div v-for="appliedFilter in appliedFilters" :key="appliedFilter.uid">
				<MultiSelect
					v-if="appliedFilter.type === 'select'"
					:filter="appliedFilter"
					:menu-id="menuId"
					:countFilterValues="countFilterValues"
					class="tw-w-full"
					@remove-filter="onRemoveFilter(appliedFilter)"
					@filter-changed="onFilterChanged"
				></MultiSelect>
				<DateFilter
					v-else-if="appliedFilter.type === 'date'"
					:filter="appliedFilter"
					:menu-id="menuId"
					class="tw-w-full"
					@remove-filter="onRemoveFilter(appliedFilter)"
					@filter-changed="onFilterChanged"
				></DateFilter>
				<TimeFilter
					v-else-if="appliedFilter.type === 'time'"
					:filter="appliedFilter"
					:menu-id="menuId"
					class="tw-w-full"
					@remove-filter="onRemoveFilter(appliedFilter)"
				></TimeFilter>
				<DefaultFilter
					v-else
					:filter="appliedFilter"
					:menu-id="menuId"
					:type="appliedFilter.type"
					class="tw-w-full"
					@remove-filter="onRemoveFilter(appliedFilter)"
					@filter-changed="onFilterChanged"
				></DefaultFilter>
			</div>
		</section>
		<Parameter
			:class="{ hidden: !openFilterOptions }"
			v-if="addFilterFieldParameter"
			:parameter-object="addFilterFieldParameter"
			:multiselect-options="addFilterFieldParameter.multiselectOptions"
			:asyncAttributes="{ menu_id: menuId }"
			:key="addFilterFieldParameter.param + '-' + addFilterFieldParameter.key"
			@value-updated="onParameterValueUpdated"
		/>
		<section id="filters-bottom-actions">
			<button
				v-if="allowAddFilter"
				id="em-add-filter"
				class="tw-btn-cancel tw-mt-4 tw-w-full tw-bg-white"
				@click="openFilterOptions = !openFilterOptions"
			>
				{{ translate('MOD_EMUNDUS_FILTERS_ADD_FILTER') }}
			</button>
			<button id="em-apply-filters" class="hidden tw-btn-primary tw-mt-4" @click="applyFilters">
				{{ translate('MOD_EMUNDUS_FILTERS_APPLY_FILTERS') }}
			</button>
		</section>

		<Modal
			:name="'share-filters-modal'"
			ref="shareFiltersModal"
			v-if="canShareFilters"
			:title="translate('MOD_EMUNDUS_FILTERS_SHARE_FILTERS')"
			:open-on-create="false"
			:center="true"
			:click-to-close="false"
			:width="'70%'"
			:classes="'tw-rounded-coordinator tw-p-4 tw-shadow-lg'"
			:moveToParentWithIdentifier="'.platform-content.container'"
		>
			<share-filters v-if="filterToShare" :filter="filterToShare" @close="onCloseShareFilterModal"></share-filters>
		</Modal>
	</div>
</template>

<script>
import MultiSelect from '@/components/Filters/MultiSelectFilter.vue';
import AdvancedSelect from '@/components/Filters/AdvancedSelect.vue';
import DateFilter from '@/components/Filters/DateFilter.vue';
import TimeFilter from '@/components/Filters/TimeFilter.vue';
import DefaultFilter from '@/components/Filters/DefaultFilter.vue';
import filtersService from '@/services/filters.js';
import Popover from '@/components/Popover.vue';
import Modal from '@/components/Modal.vue';
import ShareFilters from '@/views/Filters/ShareFilters.vue';
import RegisteredFilters from '@/views/Filters/RegisteredFilters.vue';
import alert from '@/mixins/alerts.js';
import transformIntoParameterField from '@/mixins/transformIntoParameterField.js';
import Parameter from '@/components/Utils/Parameter.vue';

const defaultGlobalSearchScopes = [
	{ value: 'everywhere', label: 'MOD_EMUNDUS_FILTERS_SCOPE_ALL' },
	{ value: 'eu.firstname', label: 'MOD_EMUNDUS_FILTERS_SCOPE_FIRSTNAME' },
	{ value: 'eu.lastname', label: 'MOD_EMUNDUS_FILTERS_SCOPE_LASTNAME' },
	{ value: 'u.username', label: 'MOD_EMUNDUS_FILTERS_SCOPE_USERNAME' },
	{ value: 'u.email', label: 'MOD_EMUNDUS_FILTERS_SCOPE_EMAIL' },
	{ value: 'jecc.applicant_id', label: 'MOD_EMUNDUS_FILTERS_SCOPE_ID' },
	{ value: 'jecc.fnum', label: 'MOD_EMUNDUS_FILTERS_SCOPE_FNUM' },
];

export default {
	name: 'App',
	components: {
		Parameter,
		RegisteredFilters,
		Modal,
		ShareFilters,
		DateFilter,
		AdvancedSelect,
		MultiSelect,
		TimeFilter,
		DefaultFilter,
		Popover,
	},
	props: {
		menuId: {
			type: Number,
			required: true,
		},
		defaultAppliedFilters: {
			type: Array,
			default: () => [],
		},
		defaultQuickSearchFilters: {
			type: Array,
			default: () => [],
		},
		countFilterValues: {
			type: Boolean,
			default: false,
		},
		allowAddFilter: {
			type: Boolean,
			default: true,
		},
		canShareFilters: {
			type: Boolean,
			default: false,
		},
		defaultSelectedRegisteredFilterId: {
			type: Number,
			default: 0,
		},
		addFilterField: {
			type: Object,
			default: () => ({}),
		},
	},
	mixins: [alert, transformIntoParameterField],
	data() {
		return {
			applySuccessEvent: null,
			startApplyFilters: null,
			appliedFilters: [],
			openFilterOptions: false,
			openSaveFilter: false,
			newFilterName: '',
			registeredFilters: [],
			selectedRegisteredFilter: 0,
			showSaveFilter: false,
			currentGlobalSearch: '',
			globalSearch: [],
			currentGlobalSearchScope: 'everywhere',
			globalSearchScopes: [],
			filters: [],
			openedRegisteredFilters: false,
			filterToShare: null,
			filterToRename: null,
			showShareFiltersModal: false,
			addFilterFieldParameter: null,
		};
	},
	mounted() {
		this.applySuccessEvent = new Event('emundus-apply-filters-success');
		this.startApplyFilters = new Event('emundus-start-apply-filters');
		this.filters = [];
		this.addFilterFieldParameter = this.fromFieldEntityToParameter(this.addFilterField);

		if (this.defaultSelectedRegisteredFilterId > 0) {
			sessionStorage.setItem('emundus-current-filter', this.defaultSelectedRegisteredFilterId);
		}
		this.selectedRegisteredFilter = sessionStorage.getItem('emundus-current-filter') || 0;

		this.getRegisteredFilters(true);
		this.appliedFilters = this.defaultAppliedFilters.map((filter) => {
			if (!filter.hasOwnProperty('operator')) {
				filter.operator = '=';
			}
			if (!filter.hasOwnProperty('andorOperator')) {
				filter.andorOperator = 'OR';
			}

			return filter;
		});
		this.globalSearch = this.defaultQuickSearchFilters;
		this.mapSearchScopesToAppliedFilters();
		this.addKeyEvents();

		window.addEventListener('refresh-emundus-module-filters', () => {
			this.applyFilters();
		});
	},
	methods: {
		addKeyEvents() {
			// add key events on up and down to focus on the next or previous global search scope
			const globalSearchScope = document.getElementById('global-search-wrapper');
			globalSearchScope.addEventListener('keydown', (event) => {
				const currentFocusedScope = globalSearchScope.querySelector('.global-search-scope button:focus');
				const currentFocusedInput = globalSearchScope.querySelector('#current-global-search:focus');

				if (currentFocusedScope || currentFocusedInput) {
					if (event.code === 'ArrowUp') {
						event.preventDefault();

						if (currentFocusedScope) {
							// focus on the previous scope
							const previousScope = currentFocusedScope.parentElement.previousElementSibling;
							if (previousScope) {
								const previousScopeButton = previousScope.querySelector('button');
								previousScopeButton.focus();
							} else {
								// focus on the input
								this.$refs.globalSearchInput.focus();
							}
						} else {
							// focus on the last scope
							const lastScope = globalSearchScope.querySelector('.global-search-scope:last-child button');
							lastScope.focus();
						}
					} else if (event.code === 'ArrowDown') {
						event.preventDefault();
						if (currentFocusedScope) {
							// focus on the next scope
							const nextScope = currentFocusedScope.parentElement.nextElementSibling;
							if (nextScope) {
								const nextScopeButton = nextScope.querySelector('button');
								nextScopeButton.focus();
							} else {
								this.$refs.globalSearchInput.focus();
							}
						} else {
							// focus on the first scope
							const firstScope = globalSearchScope.querySelector('.global-search-scope:first-child button');
							firstScope.focus();
						}
					}
				}
			});
		},
		onSelectNewFilter(filterId) {
			let added = false;

			const foundFilter = this.filters.find((filter) => filter.id === filterId);
			if (foundFilter) {
				// JSON stringify and parse to remove binding to the original filter
				let newFilter = JSON.parse(JSON.stringify(foundFilter));

				newFilter.uid = new Date().getTime();
				newFilter.default = false;
				newFilter.operator =
					newFilter.hasOwnProperty('operator') && newFilter.operator != '' ? newFilter.operator : '=';
				newFilter.andorOperator = 'OR';

				switch (newFilter.type) {
					case 'select':
						newFilter.value = ['all'];
						newFilter.operator = 'IN';
						break;
					case 'date':
						newFilter.value = ['', ''];
						break;
					default:
						newFilter.value = '';
						break;
				}

				if (newFilter.type === 'select' && newFilter.values.length < 1) {
					filtersService.getFilterValues(newFilter.id).then((values) => {
						newFilter.values = values;

						this.appliedFilters.push(newFilter);
						this.openFilterOptions = false;
						this.applyFilters();

						return true;
					});
				} else {
					this.appliedFilters.push(newFilter);
					this.openFilterOptions = false;
					added = true;
					this.applyFilters();

					return added;
				}
			} else {
				console.error('Filter not found');
				return added;
			}
		},
		applyFilters() {
			window.dispatchEvent(this.startApplyFilters);
			filtersService.applyFilters(this.appliedFilters, this.globalSearch, this.applySuccessEvent).then((applied) => {
				if (applied && this.countFilterValues) {
					filtersService.countFiltersValues(this.menuId).then((response) => {
						if (response.status) {
							this.appliedFilters = response.data;
						}
					});
				}

				filtersService
					.getFiltersAvailable(this.menuId)
					.then((filters) => {
						this.filters = filters;
					})
					.catch((error) => {
						console.error(error);
					});
			});
		},
		clearFilters() {
			sessionStorage.removeItem('emundus-current-filter');
			this.selectedRegisteredFilter = 0;
			this.globalSearch = [];
			// reset applied filters values
			this.appliedFilters = this.appliedFilters.map((filter) => {
				filter.operator = '=';

				if (filter.type === 'select') {
					filter.operator = 'IN';

					// TODO: too specific to the published filter, should create a default_value field.
					if (filter.uid === 'published') {
						filter.value = [1];
					} else {
						filter.value = [];
					}
				} else if (filter.type === 'date' || filter.type === 'time') {
					filter.value = ['', ''];
				} else {
					filter.value = '';
				}

				return filter;
			});
			this.applyFilters();
		},
		saveNewFilter() {
			if (this.registeredFilters.find((filter) => filter.id === 'tmp')) {
				return;
			}

			this.alertInput('MOD_EMUNDUS_FILTERS_NEW_FILTER_NAME_PROMPT').then((result) => {
				if (result.isConfirmed && result.value) {
					this.registeredFilters.push({
						id: 'tmp',
						name: result.value,
						constraints: JSON.stringify(this.appliedFilters),
						shared: false,
						shared_by: null,
					});

					this.saveRegisteredFilters('tmp');
				}
			});
		},
		saveRegisteredFilters(filterId) {
			let saved = false;
			const newFilter = this.registeredFilters.find((filter) => filter.id === filterId);

			if (newFilter.name.length > 0) {
				const filterContent = this.appliedFilters.map((filter) => {
					// remove the values, not needed to save the filter
					let filterCopy = JSON.parse(JSON.stringify(filter));
					delete filterCopy.values;
					return filterCopy;
				});

				filtersService.saveFilters(filterContent, newFilter.name, this.menuId).then((saved) => {
					if (saved) {
						const ids = this.registeredFilters.map((filter) => filter.id);
						this.getRegisteredFilters().then(() => {
							const newID = this.registeredFilters.map((filter) => filter.id).filter((id) => !ids.includes(id))[0];
							this.onSelectRegisteredFilter(newID);
						});
					}
				});
			}

			return saved;
		},
		editFilterName(filterId) {
			let renamed = false;

			if (filterId) {
				this.filterToRename = this.registeredFilters.find((filter) => filter.id === filterId);
			}
			this.closeFilterPopover(filterId);

			return renamed;
		},
		onRenameFilter(filterId) {
			if (filterId) {
				const foundFilter = this.registeredFilters.find((filter) => filter.id === filterId);

				this.alertInput('MOD_EMUNDUS_FILTERS_NEW_FILTER_NAME_PROMPT', foundFilter.name).then((result) => {
					if (result.isConfirmed && result.value) {
						this.renameFilter(filterId, result.value);
					}
				});
			}
		},
		renameFilter(filterId, newName = '') {
			if (filterId) {
				const foundFilter = this.registeredFilters.find((filter) => filter.id === filterId);
				if (newName !== '') {
					foundFilter.name = newName;
				}

				if (foundFilter) {
					filtersService.renameFilter(filterId, foundFilter.name).then(() => {
						this.getRegisteredFilters();
					});
				}
			}

			this.filterToRename = null;
		},
		updateFilter(filterId) {
			let updated = false;

			if (filterId > 0) {
				updated = filtersService.updateFilter(this.appliedFilters, this.menuId, filterId);

				if (updated) {
					this.alertSuccess('MOD_EMUNDUS_FILTERS_FILTER_UPDATED_SUCCESS');
					this.getRegisteredFilters();
				} else {
					this.alertError('MOD_EMUNDUS_FILTERS_FILTER_UPDATED_ERROR');
				}
			}

			this.closeFilterPopover(filterId);

			return updated;
		},
		getRegisteredFilters(firstLoad = false) {
			return filtersService.getRegisteredFilters(this.menuId).then((filters) => {
				this.registeredFilters = filters;
				if (firstLoad) {
					this.selectedRegisteredFilter = Number(sessionStorage.getItem('emundus-current-filter')) || 0;

					if (this.availableFilters.length > 0) {
						// Check via session storage if the registered filters panel should be opened
						const opened = sessionStorage.getItem('emundus-registered-filters-opened');
						this.openedRegisteredFilters = opened === '1';
					}
				}
			});
		},
		async onSelectRegisteredFilter(filterId = null) {
			if (filterId !== null) {
				this.selectedRegisteredFilter = filterId;
			}

			if (this.selectedRegisteredFilter > 0) {
				const foundFilter = this.registeredFilters.find((filter) => filter.id === this.selectedRegisteredFilter);

				if (foundFilter) {
					sessionStorage.setItem('emundus-current-filter', foundFilter.id);
					this.appliedFilters = await Promise.all(
						JSON.parse(foundFilter.constraints).map(async (filter) => {
							if (!filter.hasOwnProperty('operator')) {
								filter.operator = '=';
							}
							if (!filter.hasOwnProperty('andorOperator')) {
								filter.andorOperator = 'OR';
							}

							if (!filter.hasOwnProperty('values') || filter.values.length < 1) {
								filter.values = [];
								this.appliedFilters.forEach((defaultFilter) => {
									if (defaultFilter.id === filter.id) {
										filter.values = defaultFilter.values;
									}
								});

								if (filter.values.length < 1 && filter.type === 'select') {
									filter.values = await filtersService.getFilterValues(filter.id);
								}
							}

							return filter;
						}),
					);

					this.applyFilters();
				}
			} else {
				sessionStorage.removeItem('emundus-current-filter');
			}
		},
		onRemoveFilter(filter) {
			this.appliedFilters = this.appliedFilters.filter((appliedFilter) => appliedFilter.uid !== filter.uid);
			this.applyFilters();
		},
		onFilterChanged() {
			this.applyFilters();
		},
		onGlobalSearchChange(event, scope = 'everywhere') {
			event.stopPropagation();
			event.preventDefault();

			if (this.currentGlobalSearch.length > 0) {
				// if currentGlobalSearch contains ; then split it and add each value as a new search
				if (this.currentGlobalSearch.includes(';')) {
					const searches = this.currentGlobalSearch.split(';');
					searches.forEach((search) => {
						const foundSearch = this.globalSearch.find(
							(existingSearch) => existingSearch.value === search && existingSearch.scope === scope,
						);

						if (!foundSearch) {
							this.globalSearch.push({ value: search, scope: scope });
						}
					});

					this.applyFilters();
				} else {
					// if the current search is already in the list, no need to add it again
					const foundSearch = this.globalSearch.find(
						(search) => search.value === this.currentGlobalSearch && search.scope === scope,
					);

					if (!foundSearch) {
						this.globalSearch.push({ value: this.currentGlobalSearch, scope: scope });
						this.applyFilters();
					}
				}
			}

			this.currentGlobalSearch = '';
			// scroll to top of the div
			this.$refs.globalSearchValues.scrollTop = 0;
		},
		removeGlobalSearchValue(value, scope) {
			this.globalSearch = this.globalSearch.filter((search) => {
				return search.value !== value || search.scope !== scope;
			});

			// scroll to top of the div #global-search-values
			// remove focus from the input #global-search-input
			document.activeElement.blur();
			this.$refs.globalSearchValues.scrollTop = 0;
			this.applyFilters();
		},
		onEnterGlobalSearchDiv() {
			this.$refs.globalSearchValues.scrollTop = this.$refs.globalSearchValues.scrollHeight;
			this.$refs.globalSearchInput.focus();
		},
		translatedScope(scope) {
			const foundScope = this.globalSearchScopes.find((s) => s.value === scope);

			return foundScope ? this.translate(foundScope.label) : scope;
		},
		mapSearchScopesToAppliedFilters() {
			this.globalSearchScopes = [];
			this.globalSearchScopes = defaultGlobalSearchScopes;

			/*
            TODO: hard to give an open search on label for all filters elements
            this.appliedFilters.forEach((filter) => {
                const foundScope = this.globalSearchScopes.find((s) => s.value === filter.id);

                if (!foundScope) {
                    this.globalSearchScopes.push({
                        value: filter.id,
                        label: filter.label
                    });
                }
            });*/
		},

		shareFilter(filterId) {
			if (this.canShareFilters) {
				this.filterToShare = this.registeredFilters.find((filter) => filter.id === filterId);
				this.$refs.shareFiltersModal.open();
			}

			this.closeFilterPopover(filterId);
		},
		deleteRegisteredFilter(filterId) {
			if (filterId > 0) {
				let filter = this.registeredFilters.find((f) => f.id === filterId);
				Swal.fire({
					title: this.translate('MOD_EMUNDUS_FILTERS_DELETE_FILTER_CONFIRMATION') + (filter ? filter.name : ''),
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: this.translate('MOD_EMUNDUS_FILTERS_CONFIRM'),
					cancelButtonText: this.translate('MOD_EMUNDUS_FILTERS_CANCEL'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						cancelButton: 'em-swal-cancel-button',
						confirmButton: 'em-swal-confirm-button',
					},
				}).then((result) => {
					if (result.value) {
						filtersService.deleteFilter(filterId);
						this.registeredFilters = this.registeredFilters.filter((filter) => filter.id !== filterId);

						if (this.selectedRegisteredFilter === filterId) {
							this.onSelectRegisteredFilter(0);
						}
					}

					this.closeFilterPopover(filterId);
				});
			}
		},
		defineAsDefault(filterId) {
			return;

			filtersService.defineAsDefaultFilter(filterId).then((response) => {
				if (response.status) {
					this.getRegisteredFilters();
				}

				this.closeFilterPopover(filterId);
			});
		},
		toggleFilterFavoriteState(filterId, favorite) {
			filtersService.toggleFilterFavoriteState(filterId, favorite).then(() => {
				this.getRegisteredFilters();
			});
		},
		closeFilterPopover(filterId) {
			if (this.$refs['popover' + filterId]) {
				this.$refs['popover' + filterId][0].close();
			}
		},
		onCloseShareFilterModal() {
			this.$refs.shareFiltersModal.close();
			this.filterToShare = null;
		},
		toggleOpened() {
			this.openedRegisteredFilters = !this.openedRegisteredFilters;

			sessionStorage.setItem('emundus-registered-filters-opened', this.openedRegisteredFilters ? '1' : '0');
		},

		onParameterValueUpdated(param) {
			if (param.value === null || param.value.id === undefined || param.value.id < 1) {
				return;
			}

			const found = this.filters.find((filter) => filter.id === param.value.id);

			if (!found) {
				this.filters.push(param.value);
			}

			let newFilterId = param.value.id;

			this.addFilterFieldParameter.value = null;
			this.addFilterFieldParameter.key += 1;
			this.onSelectNewFilter(newFilterId);
		},
	},
	computed: {
		availableFilters() {
			return this.filters.filter((filter) => {
				return filter.available;
			});
		},
		globalSearchPlaceholder() {
			return this.globalSearch.length < 1 ? this.translate('MOD_EMUNDUS_FILTERS_GLOBAL_SEARCH_PLACEHOLDER') : '';
		},
		unsavedNewFilter() {
			return this.registeredFilters.find((filter) => filter.id === 'tmp') !== undefined;
		},
	},
};
</script>

<style>
#emundus-filters {
	position: relative;
}

#emundus-filters .recap-label {
	display: -webkit-box !important;
	-webkit-line-clamp: 3;
	-webkit-box-orient: vertical;
	overflow: hidden;
	text-overflow: ellipsis;
}

#select-scopes:not(.hidden) {
	position: absolute;
	top: 42px;
	z-index: 2;
	list-style-type: none;
	margin: 0;
	padding: 8px;
}

#select-scopes li {
	padding: 8px;
}

#global-search-values {
	height: 42px;
	overflow-y: auto;
}

.global-search-scope button {
	white-space: break-spaces;
	text-align: left;
}

#current-global-search {
	border: none;
	border-radius: 0;
	box-shadow: none;
	outline: 0;
}

.global-search-tag {
	padding: 4px;
}

#save-filter-new-name {
	position: absolute;
	top: 0;
}

.favorite {
	font-variation-settings:
		'FILL' 0,
		'wght' 400,
		'GRAD' 0,
		'opsz' 24;
}
</style>
