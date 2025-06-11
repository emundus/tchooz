<template>
	<div id="onboarding_list" class="tw-mb-4 tw-w-full" :class="{ 'alert-banner-displayed': alertBannerDisplayed }">
		<skeleton v-if="loading.lists" height="40px" width="100%" class="tw-mb-4 tw-mt-4 tw-rounded-lg"></skeleton>

		<Head
			v-else
			:title="currentList.title"
			:introduction="currentList.intro"
			:add-action="addAction"
			@action="onClickAction"
		/>

		<div v-if="loading.tabs" id="tabs-loading">
			<div class="tw-flex tw-justify-between">
				<skeleton height="40px" width="20%" class="tw-mb-4 tw-rounded-lg"></skeleton>
				<skeleton height="40px" width="5%" class="tw-mb-4 tw-rounded-lg"></skeleton>
			</div>

			<div
				class="tw-flex-wrap"
				:class="{
					'skeleton-grid': viewType === 'blocs',
					'tw-flex tw-flex-col': viewType === 'table',
				}"
			>
				<skeleton v-for="i in 9" :key="i" class="skeleton-item tw-rounded-lg"></skeleton>
			</div>
		</div>

		<div v-else class="list tw-mt-4">
			<Navigation
				v-if="!loading.filters"
				:tabs="currentList.tabs"
				:filters="filters"
				:items="items"
				:checked-items="checkedItems"
				:views="viewTypeOptions"
				v-model:view="viewType"
				v-model:searches="searches"
				v-model:tab="currentTab"
				v-model:tab-key="selectedListTab"
				v-model:number-of-items-to-display="numberOfItemsToDisplay"
				@select-tab="onCheckAllitems"
				@action="onClickAction"
				@exp="onClickExport"
				@update-items="getListItems"
			/>

			<div
				v-if="loading.items"
				id="items-loading"
				:class="{
					'skeleton-grid': viewType === 'blocs',
					'tw-mb-4 tw-flex tw-flex-col': viewType === 'table',
				}"
				style="flex-wrap: wrap"
			>
				<skeleton v-for="i in 9" :key="i" class="skeleton-item tw-rounded-lg"></skeleton>
			</div>

			<div v-else>
				<div v-if="displayedItems.length > 0" id="list-items">
					<table
						v-if="viewType !== 'calendar' && viewType !== 'gantt'"
						id="list-table"
						class="tw-border-separate"
						:class="{ blocs: viewType === 'blocs' }"
					>
						<thead>
							<tr>
								<th id="check-th" class="tw-p-4">
									<input class="items-check-all" type="checkbox" @change="onCheckAllitems" />
								</th>
								<th class="tw-cursor-pointer tw-p-4" @click="orderByColumn('label')" v-if="displayLabel">
									<div :class="{ 'tw-flex tw-flex-row': 'label' === orderBy }">
										<span v-if="'label' === orderBy && order === 'ASC'" class="material-symbols-outlined"
											>arrow_upward</span
										>
										<span v-else-if="'label' === orderBy && order === 'DESC'" class="material-symbols-outlined"
											>arrow_downward</span
										>
										<label class="tw-cursor-pointer tw-font-medium">{{
											translate('COM_EMUNDUS_ONBOARD_LABEL_' + currentTab.key.toUpperCase()) ==
											'COM_EMUNDUS_ONBOARD_LABEL_' + currentTab.key.toUpperCase()
												? translate('COM_EMUNDUS_ONBOARD_LABEL')
												: translate('COM_EMUNDUS_ONBOARD_LABEL_' + currentTab.key.toUpperCase())
										}}</label>
									</div>
								</th>

								<th v-for="column in additionalColumns" :key="column.key" class="tw-p-4">
									<div
										v-if="column.order_by"
										:class="{
											'tw-flex tw-flex-row': column.order_by === orderBy,
										}"
									>
										<span v-if="column.order_by === orderBy && order === 'ASC'" class="material-symbols-outlined"
											>arrow_upward</span
										>
										<span v-else-if="column.order_by === orderBy && order === 'DESC'" class="material-symbols-outlined"
											>arrow_downward</span
										>
										<label class="tw-cursor-pointer tw-font-medium" @click="orderByColumn(column.order_by)">
											{{ column.key }}
										</label>
									</div>
									<label class="tw-font-medium" v-else>{{ column.key }}</label>
								</th>

								<th v-if="tabActionsPopover && tabActionsPopover.length > 0" class="tw-p-4"></th>
							</tr>
						</thead>
						<tbody>
							<tr
								v-for="item in displayedItems"
								:key="item.id"
								:id="'item-' + currentTab.key + '-' + item.id"
								class="tw-group/item-row table-row tw-cursor-pointer tw-rounded-coordinator tw-border"
								@click="onCheckItem(item.id, $event)"
								:class="{
									'tw-flex tw-min-h-[200px] tw-flex-col tw-justify-between tw-gap-3 tw-rounded-coordinator-cards tw-p-8 tw-shadow-card':
										viewType === 'blocs',
									'tw-shadow-table-border-profile': checkedItems.includes(item.id) && viewType === 'table',
									'tw-shadow-table-border-neutral': !checkedItems.includes(item.id) && viewType === 'table',
									'tw-border-profile-full tw-bg-main-50': checkedItems.includes(item.id) && viewType === 'blocs',
									'tw-bg-white hover:tw-bg-neutral-100': !checkedItems.includes(item.id) && viewType === 'blocs',
								}"
							>
								<td
									v-show="viewType === 'table'"
									class="tw-rounded-s-coordinator tw-p-4"
									:class="{
										'tw-bg-main-50': checkedItems.includes(item.id) && viewType === 'table',
										'tw-bg-white group-hover/item-row:tw-bg-neutral-100':
											!checkedItems.includes(item.id) && viewType === 'table',
									}"
								>
									<input
										v-show="viewType === 'table'"
										:id="'item-' + currentTab.key + '-' + item.id"
										class="item-check"
										type="checkbox"
									/>
								</td>
								<td
									v-if="item.label"
									class="tw-cursor-pointer tw-p-4"
									:class="{
										'tw-bg-main-50': checkedItems.includes(item.id) && viewType === 'table',
										'tw-bg-white group-hover/item-row:tw-bg-neutral-100':
											!checkedItems.includes(item.id) && viewType === 'table',
									}"
								>
									<span
										@click="onClickAction(editAction, item.id, false, $event)"
										:class="{
											'tw-line-clamp-2 tw-min-h-[48px] tw-font-semibold': viewType === 'blocs',
											'hover:tw-underline': editAction,
										}"
										:title="item.label[params.shortlang]"
										v-html="item.label[params.shortlang]"
									></span>
								</td>
								<td
									class="columns tw-p-4"
									:class="{
										'tw-bg-main-50': checkedItems.includes(item.id) && viewType === 'table',
										'tw-bg-white group-hover/item-row:tw-bg-neutral-100':
											!checkedItems.includes(item.id) && viewType === 'table',
									}"
									v-for="column in displayedColumns(item, viewType)"
									:key="column.key"
								>
									<div
										v-if="column.type === 'tags'"
										class="tw-flex tw-flex-wrap tw-items-center tw-gap-2"
										:class="column.classes"
									>
										<span
											v-for="tag in column.values"
											:key="tag.key"
											class="tw-mr-2 tw-h-max"
											:class="tag.classes"
											v-html="tag.value"
										></span>
									</div>
									<div v-else-if="column.hasOwnProperty('long_value')">
										<span
											@click="displayLongValue($event, column.long_value)"
											class="tw-mb-2 tw-mt-2 tw-block tw-w-fit"
											:class="column.classes"
											v-html="column.value"
										></span>
									</div>
									<span v-else :class="column.classes" v-html="column.value"></span>
								</td>
								<td
									class="actions tw-gap-6 tw-rounded-e-coordinator tw-p-4"
									:class="{
										'tw-bg-main-50': checkedItems.includes(item.id) && viewType === 'table',
										'tw-gap-6 tw-bg-white group-hover/item-row:tw-bg-neutral-100':
											!checkedItems.includes(item.id) && viewType === 'table',
									}"
								>
									<hr v-if="viewType === 'blocs'" class="!tw-m-0 tw-w-full" />
									<div
										:class="{
											'tw-flex tw-w-full tw-justify-end tw-gap-2': viewType === 'blocs',
										}"
									>
										<a
											v-if="viewType === 'blocs' && editAction"
											@click="onClickAction(editAction, item.id, false, $event)"
											class="tw-btn-primary tw-w-auto tw-cursor-pointer tw-rounded-coordinator tw-text-sm"
										>
											{{ translate(editAction.label) }}
										</a>
										<div class="tw-flex tw-items-center tw-justify-end tw-gap-2">
											<button
												v-if="editAction && viewType === 'table'"
												@click="onClickAction(editAction, item.id)"
												class="tw-btn-primary tw-flex !tw-w-auto tw-items-center tw-gap-1"
												style="padding: 0.5rem"
												:title="translate(editAction.label)"
											>
												<span class="material-symbols-outlined popover-toggle-btn tw-cursor-pointer">edit</span>
											</button>

											<button
												v-for="action in iconActions"
												:key="action.name"
												v-show="action.display"
												class="tw-btn-primary tw-flex !tw-w-auto tw-items-center tw-gap-1 tw-p-[0.5rem]"
												:class="[
													action.buttonClasses,
													{
														'tw-hidden': !(typeof action.showon === 'undefined' || evaluateShowOn(item, action.showon)),
													},
												]"
												@click="onClickAction(action, item.id, false, $event)"
											>
												<span
													class="popover-toggle-btn tw-cursor-pointer"
													:class="[
														action.spanClasses,
														{
															'material-symbols-outlined': action.iconOutlined,
															'material-icons': !action.iconOutlined,
														},
													]"
												>
													{{ action.icon }}
												</span>
											</button>

											<div v-if="showModal && currentComponentElementId === item.id">
												<Teleport to=".com_emundus_vue">
													<modal
														:name="'modal-component'"
														transition="nice-modal-fade"
														:classes="' tw-max-h-[80vh] tw-overflow-y-auto tw-rounded-coordinator tw-p-8 tw-shadow-modal'"
														:width="'600px'"
														:delay="100"
														:adaptive="true"
														:clickToClose="false"
														@click.stop
													>
														<component
															:is="resolvedComponent"
															:item="item"
															@close="closePopup()"
															@update-items="getListItems()"
														/>
													</modal>
												</Teleport>
											</div>
											<popover
												:position="'left'"
												v-if="
													tabActionsPopover &&
													tabActionsPopover.length > 0 &&
													filterShowOnActions(tabActionsPopover, item).length
												"
												:button="translate('COM_EMUNDUS_ONBOARD_ACTIONS')"
												:hide-button-label="true"
												class="custom-popover-arrow"
											>
												<ul style="list-style-type: none; margin: 0" class="em-flex-col-center tw-p-4">
													<li
														v-for="action in tabActionsPopover"
														:key="action.name"
														:class="{
															'tw-hidden': !(
																typeof action.showon === 'undefined' || evaluateShowOn(item, action.showon)
															),
														}"
														@click="onClickAction(action, item.id, false, $event)"
														class="tw-cursor-pointer tw-px-2 tw-py-1.5 tw-text-base hover:tw-rounded-coordinator hover:tw-bg-neutral-300"
													>
														{{ translate(action.label) }}
													</li>
												</ul>
											</popover>
										</div>
									</div>
								</td>
							</tr>
						</tbody>
					</table>

					<div v-else-if="viewType === 'calendar'">
						<Calendar
							:items="items"
							:edit-week-action="editWeekAction"
							@on-click-action="onClickAction"
							@update-items="getListItems()"
						/>
					</div>

					<div v-if="showExportModal">
						<modal
							:name="'modal-component-export'"
							transition="nice-modal-fade"
							:classes="'export-modal tw-max-h-[80vh] tw-overflow-y-auto tw-rounded-coordinator tw-px-4 tw-shadow-modal'"
							:width="'600px'"
							:delay="100"
							:adaptive="true"
							:clickToClose="false"
							@click.stop
						>
							<ExportsSlotsModal @selectionConfirm="onClickExportWithCheckboxes" @close="closePopup()" />
						</modal>
					</div>

					<Gantt v-else-if="viewType === 'gantt'" :language="params.shortlang" :periods="displayedItems"></Gantt>
				</div>

				<NoResults v-else :message="currentTab.noData" />

				<div v-if="showModal && currentComponentElementId === null">
					<modal
						:name="'modal-component'"
						transition="nice-modal-fade"
						:classes="' tw-max-h-[80vh] tw-overflow-y-auto tw-rounded-coordinator tw-px-4 tw-shadow-modal'"
						:width="'600px'"
						:delay="100"
						:adaptive="true"
						:clickToClose="false"
						@click.stop
					>
						<component
							:is="resolvedComponent"
							:items="checkedItems"
							@close="closePopup()"
							@update-items="getListItems"
						/>
					</modal>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import { ref } from 'vue';
import Swal from 'sweetalert2';

/* List components */
import Head from '@/components/List/Head.vue';
import Navigation from '@/components/List/Navigation.vue';
import NoResults from '@/components/Utils/NoResults.vue';
import ExportsSlotsModal from '@/views/Events/ExportSlotsModal.vue';

/* Components */
import Skeleton from '@/components/Skeleton.vue';
import Popover from '@/components/Popover.vue';
import Gantt from '@/components/Gantt/Gantt.vue';
import Calendar from '@/views/Events/Calendar.vue';
import Modal from '@/components/Modal.vue';
import EditSlot from '@/views/Events/EditSlot.vue';
import AssociateUser from '@/components/Events/Popup/AssociateUser.vue';
import Import from '@/components/Campaigns/Import.vue';
import SaveRequest from '@/views/Sign/SaveRequest.vue';

/* Services */
import settingsService from '@/services/settings.js';
import userService from '@/services/user.js';
import { FetchClient } from '../services/fetchClient.js';

/* Stores */
import { useGlobalStore } from '@/stores/global.js';

/* Mixins */
import alerts from '@/mixins/alerts.js';

export default {
	name: 'List',
	components: {
		ExportsSlotsModal,
		Modal,
		NoResults,
		Navigation,
		Head,
		Calendar,
		Skeleton,
		Popover,
		Gantt,
		EditSlot,
		Import,
		AssociateUser,
		SaveRequest,
	},
	props: {
		defaultLists: {
			type: String | Object,
			default: null,
		},
		defaultType: {
			type: String,
			default: null,
		},
		defaultFilter: {
			type: String,
			default: null,
		},
		encoded: {
			type: Boolean,
			default: true,
		},
	},
	mixins: [alerts],
	data() {
		return {
			loading: {
				lists: false,
				tabs: false,
				items: false,
				filters: true,
			},
			components: {
				EditSlot,
				AssociateUser,
				Import,
				SaveRequest,
			},

			lists: {},
			type: 'forms',
			params: {},
			currentList: { title: '', tabs: [] },
			selectedListTab: 0,
			items: {},

			title: '',
			viewType: null,
			defaultViewsOptions: [
				{ value: 'table', icon: 'dehaze' },
				{ value: 'blocs', icon: 'grid_view' },
			],

			searches: {},
			filters: {},
			alertBannerDisplayed: false,

			orderBy: null,
			order: 'DESC',

			checkedItems: [],
			numberOfItemsToDisplay: 25,

			currentComponent: null,
			currentComponentElementId: null,
			showModal: false,
			showExportModal: false,
			exportClicked: null,
			eventExportClicked: null,
		};
	},
	created() {
		const alertMessageContainer = document.querySelector('.alerte-message-container');
		if (alertMessageContainer) {
			this.alertBannerDisplayed = true;

			alertMessageContainer.querySelector('#close-preprod-alerte-container').addEventListener('click', () => {
				this.alertBannerDisplayed = false;
			});
		}

		this.loading.lists = true;
		this.loading.tabs = true;

		const globalStore = useGlobalStore();
		if (this.defaultType !== null) {
			this.params = {
				type: this.defaultType,
				shortlang: globalStore.getShortLang,
			};
		} else {
			const data = globalStore.getDatas;
			this.params = Object.assign({}, ...Array.from(data).map(({ name, value }) => ({ [name]: value })));
		}
		this.type = this.params.type;

		const storageNbItemsDisplay = localStorage.getItem(
			'tchooz_number_of_items_to_display/' + document.location.hostname,
		);
		if (storageNbItemsDisplay !== null) {
			this.numberOfItemsToDisplay =
				storageNbItemsDisplay !== 'all' ? parseInt(storageNbItemsDisplay) : storageNbItemsDisplay;
		}

		this.initList();
	},

	methods: {
		initList() {
			if (this.encoded) {
				this.lists = JSON.parse(atob(this.defaultLists));
			} else {
				this.lists = this.defaultLists;
			}

			if (typeof this.lists[this.type] === 'undefined') {
				console.error('List type ' + this.type + ' does not exist');
				window.location.href = '/';
			}

			this.currentList = this.lists[this.type];
			if (Object.prototype.hasOwnProperty.call(this.params, 'tab')) {
				this.onSelectTab(this.params.tab);
			} else {
				const sessionTab = sessionStorage.getItem('tchooz_selected_tab/' + document.location.hostname);
				if (sessionTab !== null && this.currentList.tabs.some((tab) => tab.key === sessionTab)) {
					this.onSelectTab(sessionTab);
				} else {
					this.onSelectTab(this.currentList.tabs[0].key);
				}
			}

			let availableViews = this.currentTab.viewsOptions ? this.currentTab.viewsOptions : this.defaultViewsOptions;
			this.viewType = localStorage.getItem('tchooz_view_type/' + document.location.hostname);
			let isViewTypeAvailable = availableViews.some((view) => view.value === this.viewType);

			if (this.viewType === null || typeof this.viewType === 'undefined' || !isViewTypeAvailable) {
				this.viewType = availableViews[0].value;

				if (this.viewType === null || typeof this.viewType === 'undefined') {
					// Do not update session storage if the view type is just no available in this menu
					localStorage.setItem('tchooz_view_type/' + document.location.hostname, this.viewType);
				}
			}

			this.loading.lists = false;

			this.getListItems();
		},

		orderByColumn(column) {
			this.orderBy = column;
			this.order = this.order === 'ASC' ? 'DESC' : 'ASC';
			this.getListItems(1, this.selectedListTab);
		},

		async getListItems(page = 1, tab = null, refreshFilters = false) {
			this.checkedItems = [];

			if (tab === null) {
				this.loading.tabs = true;
				this.items = ref(Object.assign({}, ...this.currentList.tabs.map((tab) => ({ [tab.key]: [] }))));
			} else {
				this.loading.items = true;
			}

			const tabs = tab === null ? this.currentList.tabs : [this.currentTab];
			if (tabs.length > 0) {
				tabs.forEach((tab) => {
					if (typeof this.searches[tab.key] === 'undefined') {
						this.searches[tab.key] = {
							search: '',
							lastSearch: '',
							debounce: null,
						};
					}

					for (const action of tab.actions) {
						action.display = true;

						if (action.acl) {
							const acl_options = action.acl.split('|');

							if (acl_options.length === 2) {
								userService.getAcl(acl_options[0], acl_options[1]).then((response) => {
									if (response.status) {
										action.display = response.right;
									} else {
										action.display = false;
									}
								});
							} else {
								action.display = false;
							}
						}
					}

					// Init search value from sessionStorage
					const searchValue = sessionStorage.getItem(
						'tchooz_filter_' + this.selectedListTab + '_search/' + document.location.hostname,
					);
					if (searchValue !== null && this.searches[this.selectedListTab]) {
						this.searches[this.selectedListTab].search = searchValue;
						this.searches[this.selectedListTab].lastSearch = searchValue;
					}

					this.setTabFilters(tab, refreshFilters).then(() => {
						if (typeof tab.getter !== 'undefined') {
							let url =
								'/index.php?option=com_emundus&controller=' +
								tab.controller +
								'&task=' +
								tab.getter +
								'&lim=' +
								this.numberOfItemsToDisplay +
								'&page=' +
								page;
							if (this.searches[tab.key].search !== '') {
								url += '&recherche=' + this.searches[tab.key].search;
							}

							if (this.orderBy !== null && this.orderBy !== '') {
								url += '&order_by=' + this.orderBy;
								url += '&sort=' + this.order;
							}

							if (typeof this.filters[tab.key] !== 'undefined') {
								this.filters[tab.key].forEach((filter) => {
									const filterValue =
										typeof filter.value === 'object' && filter.value !== null && 'value' in filter.value
											? filter.value.value
											: filter.value;

									if (filterValue !== '' && filterValue !== 'all') {
										url += '&' + filter.key + '=' + filterValue;
									}
								});
							}

							url += '&view=' + this.viewType;

							if (this.defaultFilter && this.defaultFilter.length > 0) {
								url += '&' + this.defaultFilter;
							}

							try {
								fetch(url)
									.then((response) => response.json())
									.then((response) => {
										if (response.status === true) {
											if (typeof response.data.datas !== 'undefined') {
												this.items[tab.key] = response.data.datas;

												tab.pagination = {
													current: page,
													count: response.data.count,
													total: Math.ceil(response.data.count / this.numberOfItemsToDisplay),
												};
											}
										} else {
											console.error('Failed to get data : ' + response.msg);
										}
										this.loading.tabs = false;
										this.loading.items = false;
									})
									.catch((error) => {
										console.error(error);
										this.loading.tabs = false;
										this.loading.items = false;
									});
							} catch (e) {
								console.error(e);
								this.loading.tabs = false;
								this.loading.items = false;
							}
						} else {
							this.loading.tabs = false;
							this.loading.items = false;
						}
					});
				});
			} else {
				this.loading.tabs = false;
				this.loading.items = false;
			}
		},

		async setTabFilters(tab, refreshFilters = false) {
			return new Promise(async (resolve) => {
				const urlParams = new URLSearchParams(window.location.search);

				if (typeof tab.filters !== 'undefined' && tab.filters.length > 0) {
					if (typeof this.filters[tab.key] === 'undefined' || refreshFilters) {
						this.loading.filters = true;

						this.filters[tab.key] = [];

						for (const filter of tab.filters) {
							let filterValue = filter.default ? filter.default : 'all';
							let filterValueSession = sessionStorage.getItem(
								'tchooz_filter_' + this.selectedListTab + '_' + filter.key + '/' + document.location.hostname,
							);

							if (urlParams.has(filter.key)) {
								filterValue = urlParams.get(filter.key);
							} else if (filterValueSession) {
								filterValue = filterValueSession;
							}

							if (!filter.values || refreshFilters) {
								if (filter.getter) {
									this.filters[tab.key].push({
										key: filter.key,
										label: filter.label,
										value: filterValue,
										alwaysDisplay: filter.alwaysDisplay || false,
										options: [],
										type: filter.multiselect ? 'multiselect' : filter.type || 'select',
									});

									await this.setFilterOptions(
										typeof filter.controller !== 'undefined' ? filter.controller : tab.controller,
										filter,
										tab.key,
									);
								} else {
									this.filters[tab.key].push({
										key: filter.key,
										label: filter.label,
										value: filterValue,
										alwaysDisplay: filter.alwaysDisplay || false,
										options: filter.values || [],
										type: filter.multiselect ? 'multiselect' : filter.type || 'select',
									});
								}
							} else {
								this.filters[tab.key].push({
									key: filter.key,
									label: filter.label,
									value: filterValue,
									alwaysDisplay: filter.alwaysDisplay || false,
									options: filter.values || [],
									type: filter.multiselect ? 'multiselect' : filter.type || 'select',
								});
							}
						}

						this.loading.filters = false;
					}
				} else {
					this.loading.filters = false;
				}
				resolve();
			});
		},

		async setFilterOptions(controller, filter, tab) {
			return await fetch('index.php?option=com_emundus&controller=' + controller + '&task=' + filter.getter)
				.then((response) => response.json())
				.then((response) => {
					if (response.status === true) {
						let options = response.data;

						// if options is an array of strings, convert it to an array of objects
						if (typeof options[0] === 'string') {
							options = options.map((option) => ({
								value: option,
								label: option,
							}));
						}

						options.unshift({
							value: 'all',
							label: this.translate(filter.allLabel),
						});

						this.filters[tab].find((f) => f.key === filter.key).options = options;
					} else {
						return [];
					}
				});
		},

		onClickAction(action, itemId = null, multiple = false, event = null) {
			if (
				action === null ||
				typeof action !== 'object' ||
				(typeof action.showon !== 'undefined' && !this.evaluateShowOn(null, action.showon))
			) {
				return false;
			}

			if (event !== null) {
				event.stopPropagation();
			}

			let item = null;
			if (itemId !== null) {
				item = this.items[this.selectedListTab].find((item) => item.id === itemId);
			}

			if (action.name === 'preview') {
				this.onClickPreview(item);
				return;
			}

			if (action.type === 'modal') {
				this.currentComponent = action.component;
				this.showModal = true;
				this.currentComponentElementId = itemId;
				return;
			}

			if (action.type === 'redirect') {
				let url = action.action;
				if (item !== null) {
					Object.keys(item).forEach((key) => {
						url = url.replace('%' + key + '%', item[key]);
					});
				}

				settingsService.redirectJRoute(url, useGlobalStore().getCurrentLang);
			} else {
				if (multiple) {
					if (this.checkedItems.length === 0) {
						return;
					}
				}

				let url = 'index.php?option=com_emundus&controller=' + action.controller + '&task=' + action.action;
				let parameters = [];

				if (itemId !== null) {
					if (action.parameters) {
						let url_parameters = action.parameters;
						if (item !== null) {
							Object.keys(item).forEach((key) => {
								url_parameters = url_parameters.replace('%' + key + '%', item[key]);
							});
						}

						url += url_parameters;
					} else {
						parameters = { id: itemId };
					}
				} else if (multiple && this.checkedItems.length > 0) {
					parameters = { ids: this.checkedItems };
				}

				if (Object.prototype.hasOwnProperty.call(action, 'confirm')) {
					Swal.fire({
						icon: 'warning',
						title: this.translate(action.label),
						text: this.translate(action.confirm),
						input: action.input ? action.input : null,
						inputLabel: action.inputLabel ? this.translate(action.inputLabel) : null,
						showCancelButton: true,
						confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_OK'),
						cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							cancelButton: 'em-swal-cancel-button',
							actions: 'em-swal-double-action',
						},
					}).then((result) => {
						if (result.isConfirmed) {
							if (action.input) {
								parameters['input'] = result.value;
							}
							this.executeAction(url, parameters, action.method);
						}
					});
				} else {
					this.executeAction(url, parameters, action.method);
				}
			}
		},
		closePopup() {
			this.currentComponent = null;
			this.showModal = false;
			this.showExportModal = false;
			this.currentComponentElementId = null;
		},
		onClickExport(exp, event = null) {
			if (event !== null) {
				event.stopPropagation();
			}

			if (
				exp === null ||
				typeof exp !== 'object' ||
				(typeof exp.showon !== 'undefined' && !this.evaluateShowOn(null, exp.showon))
			) {
				return false;
			}

			if (exp.exportModal) {
				this.showExportModal = true;
				this.exportClicked = exp;
				this.eventExportClicked = null;
				return;
			}

			let url = 'index.php?option=com_emundus&controller=' + exp.controller + '&task=' + exp.action;
			let parameters = {
				ids: this.checkedItems.length > 0 ? this.checkedItems : this.displayedItems.map((item) => item.id),
			};

			if (Object.prototype.hasOwnProperty.call(exp, 'confirm')) {
				Swal.fire({
					icon: 'warning',
					title: this.translate(exp.label),
					text: this.translate(exp.confirm),
					showCancelButton: true,
					confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_OK'),
					cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						cancelButton: 'em-swal-cancel-button',
						actions: 'em-swal-double-action',
					},
				}).then((result) => {
					if (result.value) {
						this.executeAction(url, parameters, exp.method);
					}
				});
			} else {
				this.executeAction(url, parameters, exp.method);
			}
		},
		onClickExportWithCheckboxes(checkboxesValues) {
			if (this.eventExportClicked !== null) {
				this.eventExportClicked.stopPropagation();
			}

			if (
				this.exportClicked === null ||
				typeof this.exportClicked !== 'object' ||
				(typeof this.exportClicked.showon !== 'undefined' && !this.evaluateShowOn(null, this.exportClicked.showon))
			) {
				return false;
			}

			let url =
				'index.php?option=com_emundus&controller=' +
				this.exportClicked.controller +
				'&task=' +
				this.exportClicked.action;
			let parameters = {
				ids: this.checkedItems.length > 0 ? this.checkedItems : this.displayedItems.map((item) => item.id),
				checkboxesValuesFromView: JSON.stringify(checkboxesValues.viewSelection),
				checkboxesValuesFromProfile: JSON.stringify(checkboxesValues.profileSelection),
			};

			if (Object.prototype.hasOwnProperty.call(this.exportClicked, 'confirm')) {
				Swal.fire({
					icon: 'warning',
					title: this.translate(this.exportClicked.label),
					text: this.translate(this.exportClicked.confirm),
					showCancelButton: true,
					confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_OK'),
					cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						cancelButton: 'em-swal-cancel-button',
						actions: 'em-swal-double-action',
					},
				}).then((result) => {
					if (result.value) {
						this.executeAction(url, parameters, this.exportClicked.method);
					}
				});
			} else {
				this.executeAction(url, parameters, this.exportClicked.method);
			}
		},

		async executeAction(url, data = null, method = 'get') {
			this.loading.items = true;

			let controller = url.split('controller=')[1].split('&')[0];
			let task = url.split('task=')[1].split('&')[0];
			let fetchClient = new FetchClient(controller);

			if (controller && task) {
				if (typeof method === 'undefined') {
					method = 'get';
				}

				let response = null;

				addLoader();

				if (method === 'get') {
					response = await fetchClient.get(task, data).catch((error) => {
						console.error(error.message);
						this.alertError('COM_EMUNDUS_ERROR', error.message);
						removeLoader();
					});
				} else if (method === 'post') {
					response = await fetchClient.post(task, data);
				} else if (method === 'delete') {
					response = await fetchClient.delete(task, data);
				}
				removeLoader();

				if (response) {
					if (response.status === true || response.status === 1) {
						if (response.download_file) {
							Swal.fire({
								position: 'center',
								icon: 'success',
								title: this.translate('COM_EMUNDUS_REGISTRANTS_FILE_READY'),
								showCancelButton: true,
								showConfirmButton: true,
								confirmButtonText: this.translate('LINK_TO_DOWNLOAD'),
								cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_EDITOR_UNDO'),
								reverseButtons: true,
								allowOutsideClick: false,
								customClass: {
									cancelButton: 'em-swal-cancel-button',
									confirmButton: 'em-swal-confirm-button btn btn-success',
									title: 'w-full justify-center',
								},
								preConfirm: () => {
									var link = document.createElement('a');
									link.href = response.download_file;
									link.download = '';
									link.click();
								},
							});
						}
						if (response.redirect) {
							window.location.href = response.redirect;
						}

						this.getListItems();
					} else {
						if (response.msg || response.message) {
							Swal.fire({
								icon: 'error',
								title: this.translate(response.msg || response.message),
								reverseButtons: true,
								customClass: {
									title: 'em-swal-title',
									confirmButton: 'em-swal-confirm-button',
									actions: 'em-swal-single-action',
								},
							});
						}
					}
				}
			}

			this.loading.items = false;
		},

		onClickPreview(item) {
			if (this.previewAction && this.previewAction.method) {
				Swal.fire({
					title: this.translate(this.previewAction.title),
					html: this.previewAction.method(item),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						actions: 'tw-flex tw-flex-row tw-w-full !tw-justify-center',
					},
					confirmButtonText: this.translate('COM_EMUNDUS_LIST_CLOSE_PREVIEW'),
				});
			} else if (this.previewAction && (this.previewAction.title || this.previewAction.content)) {
				Swal.fire({
					title:
						this.previewAction.title === 'label'
							? item[this.previewAction.title][this.params.shortlang]
							: item[this.previewAction.title],
					html: '<div style="text-align: left;">' + item[this.previewAction.content] + '</div>',
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						actions: 'tw-flex tw-flex-row tw-w-full !tw-justify-center',
					},
					confirmButtonText: this.translate('COM_EMUNDUS_LIST_CLOSE_PREVIEW'),
				});
			}
		},

		onSelectTab(tabKey) {
			let selected = false;

			if (this.selectedListTab !== tabKey) {
				this.onCheckAllitems();

				// check if the tab exists
				if (this.currentList.tabs.find((tab) => tab.key === tabKey) !== 'undefined') {
					this.orderBy = null;
					this.selectedListTab = tabKey;
					sessionStorage.setItem('tchooz_selected_tab/' + document.location.hostname, tabKey);
					selected = true;
				}
			}

			return selected;
		},

		filterShowOnActions(actions, item) {
			return actions.filter((action) => {
				if (Object.prototype.hasOwnProperty.call(action, 'showon')) {
					return this.evaluateShowOn(item, action.showon);
				}

				return true;
			});
		},

		evaluateShowOn(item = null, showon = null) {
			if (item === null && showon === null) {
				return false;
			}

			let items = [];
			if (item === null) {
				items = this.checkedItems;
			} else {
				items = [item];
			}

			let show = [];

			items.forEach((item) => {
				// If item is an id, we get the item from the list
				if (typeof item === 'number') {
					item = this.items[this.selectedListTab].find((i) => i.id === item);
				}
				switch (showon.operator) {
					case '==':
					case '=':
						show.push(item[showon.key] == showon.value);
						break;
					case '!=':
						show.push(item[showon.key] != showon.value);
						break;
					case '>':
						show.push(item[showon.key] > showon.value);
						break;
					case '<':
						show.push(item[showon.key] < showon.value);
						break;
					case '>=':
						show.push(item[showon.key] >= showon.value);
						break;
					case '<=':
						show.push(item[showon.key] <= showon.value);
						break;
					default:
						show.push(true);
				}
			});

			// Return true if all items match the condition
			return show.every((s) => s === true);
		},

		onCheckAllitems(e) {
			if (typeof e !== 'undefined' && e.target.checked) {
				this.displayedItems.map(
					(item) =>
						(document.querySelector('#item-' + this.currentTab.key + '-' + item.id + ' .item-check').checked = true),
				);
				this.checkedItems = this.displayedItems.map((item) => item.id);
			} else {
				this.displayedItems.map(
					(item) =>
						(document.querySelector('#item-' + this.currentTab.key + '-' + item.id + ' .item-check').checked = false),
				);
				this.checkedItems = [];
				if (document.querySelector('#check-th input')) {
					document.querySelector('#check-th input').checked = false;
				}
			}
		},

		onCheckItem(id, e) {
			// Do not check item if the click is on a link or a popover button
			if (e.target.tagName === 'A' || e.target.classList.contains('popover-toggle-btn')) {
				return;
			}

			let checkbox = document.querySelector('#item-' + this.currentTab.key + '-' + id + ' .item-check');
			if (this.checkedItems.includes(id)) {
				this.checkedItems.splice(this.checkedItems.indexOf(id), 1);
				if (checkbox.checked) {
					checkbox.checked = false;
				}
			} else {
				this.checkedItems.push(id);
				if (!checkbox.checked) {
					checkbox.checked = true;
				}
			}

			document.querySelector('#check-th input').checked = this.checkedItems.length === this.displayedItems.length;
		},

		displayedColumns(item, viewType) {
			let columns = [];

			if (item && item.additional_columns) {
				columns = item.additional_columns.filter((column) => {
					return column.display === viewType || column.display === 'all';
				});
			}

			return columns;
		},

		displayLongValue(e, html) {
			if (e) {
				e.stopPropagation();
			}

			Swal.fire({
				html: '<div style="text-align: left;">' + html + '</div>',
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					actions: 'em-swal-single-action',
				},
			});
		},
	},
	computed: {
		resolvedComponent() {
			return this.components[this.currentComponent] || null;
		},
		currentTab() {
			return this.currentList.tabs.find((tab) => {
				return tab.key === this.selectedListTab;
			});
		},

		tabActionsPopover() {
			return typeof this.currentTab.actions !== 'undefined'
				? this.currentTab.actions.filter((action) => {
						return (
							!['add', 'edit'].includes(action.name) &&
							!Object.prototype.hasOwnProperty.call(action, 'icon') &&
							action.display
						);
					})
				: [];
		},

		editAction() {
			return typeof this.currentTab !== 'undefined' && typeof this.currentTab.actions !== 'undefined'
				? this.currentTab.actions.find((action) => {
						return action.name === 'edit' && (action.view === this.viewType || typeof action.view === 'undefined');
					})
				: false;
		},

		editWeekAction() {
			return typeof this.currentTab !== 'undefined' && typeof this.currentTab.actions !== 'undefined'
				? this.currentTab.actions.find((action) => {
						return action.name === 'edit' && action.view === 'calendar' && action.calendarView === 'week';
					})
				: false;
		},

		addAction() {
			return typeof this.currentTab !== 'undefined' && typeof this.currentTab.actions !== 'undefined'
				? this.currentTab.actions.find((action) => {
						return action.name === 'add' && action.display;
					})
				: false;
		},

		previewAction() {
			return typeof this.currentTab !== 'undefined' && typeof this.currentTab.actions !== 'undefined'
				? this.currentTab.actions.find((action) => {
						return action.name === 'preview';
					})
				: false;
		},

		iconActions() {
			return typeof this.currentTab.actions !== 'undefined'
				? this.currentTab.actions.filter((action) => {
						return (
							!['add', 'edit', 'preview'].includes(action.name) && Object.prototype.hasOwnProperty.call(action, 'icon')
						);
					})
				: [];
		},

		displayedItems() {
			return typeof this.items[this.selectedListTab] !== 'undefined' ? this.items[this.selectedListTab] : [];
		},

		additionalColumns() {
			let columns = [];
			let items = typeof this.items[this.selectedListTab] !== 'undefined' ? this.items[this.selectedListTab] : [];

			if (items.length > 0 && items[0].additional_columns && items[0].additional_columns.length > 0) {
				items[0].additional_columns.forEach((column) => {
					if (column.display === 'all' || column.display === this.viewType) {
						columns.push(column);
					}
				});
			}

			return columns;
		},

		viewTypeOptions() {
			if (typeof this.currentTab !== 'undefined' && this.currentTab.viewsOptions) {
				return this.currentTab.viewsOptions;
			} else {
				return this.defaultViewsOptions;
			}
		},

		displayLabel() {
			let display = false;

			// at least one of the items columns is a label
			if (this.displayedItems.length > 0) {
				this.displayedItems.forEach((item) => {
					if (item.label) {
						display = true;
					}
				});
			}

			return display;
		},
	},
	watch: {
		'currentTab.pagination.current': function (newPage, oldPage) {
			if (newPage !== oldPage && typeof oldPage !== 'undefined') {
				this.getListItems(newPage, this.selectedListTab);
			}
		},
		numberOfItemsToDisplay() {
			this.getListItems();
			localStorage.setItem(
				'tchooz_number_of_items_to_display/' + document.location.hostname,
				this.numberOfItemsToDisplay,
			);
		},
		viewType(value, oldValue) {
			// If calendar view, we need to load the items
			if (oldValue != null && oldValue !== value && (value === 'calendar' || oldValue === 'calendar')) {
				this.getListItems(1, this.selectedListTab);
			}
		},
	},
};
</script>

<style scoped lang="scss">
#list-table {
	transition: all 0.3s;
	border: 0;
	border-spacing: 0 3px;

	input[type='checkbox'] {
		appearance: none;
		width: 20px;
		height: 20px;
		border: 2px solid #333;
		border-radius: 4px;
		background-color: white;
		cursor: pointer;
		position: relative;
		display: block;
		padding: 0;
		margin-right: 0;
	}

	input[type='checkbox']:checked {
		background-color: var(--em-profile-color);
		border-color: var(--em-profile-color);
	}

	input[type='checkbox']:checked::before {
		content: '✓';
		color: white;
		font-size: 16px;
		font-weight: bold;
		position: absolute;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
	}

	thead th {
		background-color: transparent !important;
		border: unset !important;
		box-shadow: unset;
	}

	&.blocs {
		border: 0;

		thead {
			display: none;
		}

		tbody {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
			column-gap: 24px;
			row-gap: 24px;

			tr {
				td {
					display: flex;
					flex-direction: row;
					justify-content: space-between;
					padding: 0;

					&.actions {
						align-items: center;
						flex-direction: column;
					}

					ul:not(.tw-p-4) {
						display: flex;
						flex-direction: column;
						justify-content: flex-end;
						align-items: flex-end;
						padding: 0;
						margin: 0;

						li {
							list-style: none;
							cursor: pointer;
							width: 100%;
						}
					}
				}
			}
		}
	}
}

.skeleton-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
	column-gap: 24px;
	row-gap: 24px;
}

.placement-center {
	position: fixed;
	left: 50%;
	transform: translate(-50%, -50%);
	top: 50%;
}

#tabs-loading,
#items-loading {
	:not(.skeleton-grid) .skeleton-item,
	&:not(.skeleton-grid) .skeleton-item {
		height: 40px !important;
		width: 100% !important;
		margin-bottom: 16px !important;
	}

	.skeleton-grid .skeleton-item,
	&.skeleton-grid .skeleton-item {
		height: 200px !important;
		min-width: 340px !important;
	}
}
</style>
