<template>
	<div id="ranking-list">
		<header class="em-flex-space-between tw-mb-2 tw-mt-4 tw-flex tw-flex-row">
			<div id="header-left" class="tw-flex tw-flex-row tw-items-center">
				<div id="nb-files" class="tw-mr-2">{{ translate('COM_EMUNDUS_NB_FILES') + ' ' }} {{ rankings.nbFiles }}</div>

				<div id="pagination" class="tw-ml-2 tw-flex tw-flex-row tw-items-center">
					<select v-model="pagination.perPage" @change="updatePerPage">
						<option v-for="option in pagination.perPageOptions" :key="option" :value="option">
							{{ translate('COM_EMUNDUS_DISPLAY') }} {{ option }}
						</option>
					</select>
				</div>
			</div>
			<div id="header-right" class="tw-flex tw-flex-row" v-show="nbPagesMax > 1">
				<!-- pagination navigation -->
				<span id="prev" class="material-symbols-outlined tw-cursor-pointer" @click="changePage('-1')"
					>keyboard_arrow_left</span
				>
				<span id="position"> {{ pagination.page }} / {{ nbPagesMax }}</span>
				<span id="next" class="material-symbols-outlined tw-cursor-pointer" @click="changePage('1')"
					>keyboard_arrow_right</span
				>
			</div>
		</header>
		<div id="btns-section" class="tw-mb-8 tw-flex tw-flex-row tw-justify-end">
			<button
				v-if="rankingsToLock"
				id="ask-to-lock-ranking"
				class="em-secondary-button tw-w-fit tw-cursor-pointer"
				@click="askToLockRankings"
			>
				<span class="material-symbols-outlined em-mr-4">lock</span>
				{{ translate('COM_EMUNDUS_CLASSEMENT_ASK_LOCK_RANKING') }}
			</button>
		</div>
		<p class="tw-alert tw-mb-2 tw-w-full" v-if="!canDragAndDropRanks">
			{{ translate('COM_EMUNDUS_RANKING_CANNOT_DRAG_AND_DROP') }}
		</p>
		<div
			v-if="rankings.myRanking && rankings.myRanking.length > 0"
			id="ranking-lists-container"
			class="em-flex-space-between tw-flex tw-flex-row"
		>
			<div id="my-ranking-list" class="tw-mr-2 tw-w-full" :class="{ dragging: dragging }">
				<table id="ranked-files" class="tw-w-full">
					<thead>
						<tr>
							<th>
								<span class="material-symbols-outlined" v-if="ismyRankingLocked">lock</span>
								<span class="material-symbols-outlined" v-else>lock_open</span>
							</th>
							<th @click="reorder(hierarchy_id, 'applicant')">{{ translate('COM_EMUNDUS_CLASSEMENT_FILE') }}</th>
							<th @click="reorder(hierarchy_id, 'rank')">
								<div class="tw-flex tw-flex-row tw-items-center">
									<span>{{ translate('COM_EMUNDUS_CLASSEMENT_YOUR_RANKING') }}</span>
									<div v-if="ordering.orderHierarchy === 'default' || ordering.orderHierarchy == hierarchy_id">
										<span class="material-symbols-outlined" v-if="ordering.order === 'ASC'">arrow_drop_up</span>
										<span class="material-symbols-outlined" v-else>arrow_drop_down</span>
									</div>
								</div>
							</th>
							<th>{{ translate('COM_EMUNDUS_RANKING_FILE_STATUS') }}</th>
							<th v-if="hierarchyData.form_id">
								<span>{{ hierarchyData.form_label }}</span>
							</th>
							<th v-for="column in additionalHeaderColumns" :key="column">
								<span> {{ column }} </span>
							</th>
						</tr>
					</thead>
					<!-- only ranked files -->
					<draggable
						name="my_ranking"
						tag="tbody"
						v-model="rankedFiles"
						id="ranked-files-list"
						group="ranked-files-list"
						:sort="true"
						class="draggables-list"
						@start="dragging = true"
						@end="onDragEnd"
						handle=".handle"
					>
						<tr v-for="file in rankedFiles" :key="file.id" :data-file-id="file.id" class="ranked-file">
							<td>
								<span class="material-symbols-outlined" v-if="file.locked == 1 || ismyRankingLocked">lock</span>
								<span class="material-symbols-outlined" v-else>lock_open</span>
								<span
									class="material-symbols-outlined handle"
									v-if="file.locked != 1 && !ismyRankingLocked && canDragAndDropRanks"
									>drag_indicator</span
								>
							</td>
							<td class="em-flex-column file-identifier em-pointer" @click="openClickOpenFile(file)">
								<span>{{ file.applicant }}</span>
								<span class="em-neutral-600-color em-font-size-14">{{ file.fnum }}</span>
							</td>
							<td v-if="!ismyRankingLocked && file.locked != 1">
								<select v-model="file.rank" @change="onChangeRankValue(file)">
									<option value="-1">{{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }}</option>
									<option v-for="i in maxRankValueAvailable" :key="i">{{ i }}</option>
								</select>
							</td>
							<td v-else>
								<span v-if="file.rank > 0">{{ file.rank }}</span>
								<span v-else> {{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }} </span>
							</td>
							<td><span v-html="getStatusTag(file.status)"></span></td>

							<td v-if="hierarchyData.form_id">
								{{
									file.reviewed
										? translate('COM_EMUNDUS_RANKING_HIERARCHY_FORM_ID_FINISHED')
										: translate('COM_EMUNDUS_RANKING_HIERARCHY_FORM_ID_NOT_FINISHED')
								}}
							</td>
							<td v-for="column in additionalHeaderColumns" :key="file.fnum + '-' + column">
								<span v-if="file.additional_columns && file.additional_columns[column]">{{
									file.additional_columns[column]
								}}</span>
								<span v-else>-</span>
							</td>
						</tr>
					</draggable>
					<draggable
						v-model="unrankedFiles"
						tag="tbody"
						id="unranked-files-list"
						class="draggables-list"
						:group="{ name: 'ranked-files-list', pull: true, put: false }"
						:sort="false"
						@start="dragging = true"
						@end="onDragEnd"
					>
						<tr v-for="file in unrankedFiles" :key="file.id" :data-file-id="file.id" class="unranked-file">
							<td>
								<span class="material-symbols-outlined" v-if="file.locked">lock</span>
								<span class="material-symbols-outlined" v-else>lock_open</span>
								<span class="material-symbols-outlined handle" v-if="file.locked != 1 && canDragAndDropRanks"
									>drag_indicator</span
								>
							</td>
							<td class="em-flex-column file-identifier tw-cursor-pointer" @click="openClickOpenFile(file)">
								<span>{{ file.applicant }}</span>
								<span class="em-neutral-600-color em-font-size-14">{{ file.fnum }}</span>
							</td>
							<td v-if="!ismyRankingLocked && file.locked != 1">
								<select v-model="file.rank" @change="onChangeRankValue(file)">
									<option value="-1">{{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }}</option>
									<option v-for="i in maxRankValueAvailableForNotRanked" :key="i">{{ i }}</option>
								</select>
							</td>
							<td v-else>
								{{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }}
							</td>
							<td><span v-html="getStatusTag(file.status)"></span></td>
							<td v-if="hierarchyData.form_id">
								{{
									file.reviewed
										? translate('COM_EMUNDUS_RANKING_HIERARCHY_FORM_ID_FINISHED')
										: translate('COM_EMUNDUS_RANKING_HIERARCHY_FORM_ID_NOT_FINISHED')
								}}
							</td>
							<td v-for="column in additionalHeaderColumns" :key="file.fnum + '-' + column">
								<span v-if="file.additional_columns && file.additional_columns[column]">{{
									file.additional_columns[column]
								}}</span>
								<span v-else>-</span>
							</td>
						</tr>
					</draggable>
				</table>
			</div>
			<div v-if="rankings.otherRankings.length > 0" id="other-ranking-lists" class="em-border-neutral-300 tw-w-full">
				<table class="tw-w-full">
					<thead>
						<template v-for="hierarchy in rankings.otherRankings" :key="hierarchy.hierarchy_id">
							<th @click="reorder(hierarchy.hierarchy_id, 'rank')" :title="hierarchy.label">
								<div class="tw-flex tw-flex-row tw-items-center">
									<span>{{ hierarchy.label }}</span>
									<div v-if="ordering.orderHierarchy == hierarchy_id && ordering.orderBy === 'rank'">
										<span class="material-symbols-outlined" v-if="ordering.order == 'ASC'">arrow_drop_up</span>
										<span class="material-symbols-outlined" v-else>arrow_drop_down</span>
									</div>
								</div>
							</th>
							<th
								:title="translate('COM_EMUNDUS_RANKING_RANKER') + ' ' + hierarchy.label"
								class="border-right"
								@click="reorder(hierarchy.hierarchy_id, 'user_id')"
							>
								<div>
									<span>{{ translate('COM_EMUNDUS_RANKING_RANKER') + ' ' + hierarchy.label }}</span>
									<div v-if="ordering.orderHierarchy == hierarchy_id && ordering.orderBy === 'user_id'">
										<span class="material-symbols-outlined" v-if="ordering.order == 'ASC'">arrow_drop_up</span>
										<span class="material-symbols-outlined" v-else>arrow_drop_down</span>
									</div>
								</div>
							</th>
						</template>
					</thead>
					<tbody>
						<!-- 1 ligne par fichier, 2 colonnes par hiérarchie (1 de classement, et une pour connaître le classeur) -->
						<tr v-for="file in orderedRankings" :key="file.id">
							<template v-for="hierarchy in rankings.otherRankings" :key="file.id + '-' + hierarchy.hierarchy_id">
								<td>
									<span
										class="material-symbols-outlined em-mr-4"
										v-if="
											rankings.otherRankings.groupedByFiles[file.id] &&
											rankings.otherRankings.groupedByFiles[file.id][hierarchy.hierarchy_id] &&
											rankings.otherRankings.groupedByFiles[file.id][hierarchy.hierarchy_id].locked == 1
										"
									>
										lock
									</span>
									<span v-else class="material-symbols-outlined em-mr-4">lock_open</span>
									<span
										v-if="
											rankings.otherRankings.groupedByFiles[file.id] &&
											rankings.otherRankings.groupedByFiles[file.id][hierarchy.hierarchy_id] &&
											rankings.otherRankings.groupedByFiles[file.id][hierarchy.hierarchy_id].rank != -1
										"
									>
										{{ rankings.otherRankings.groupedByFiles[file.id][hierarchy.hierarchy_id].rank }}
									</span>
									<span v-else>{{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }}</span>
								</td>
								<td class="tw-border-right ranker-name">
									<span
										v-if="
											rankings.otherRankings.groupedByFiles[file.id] &&
											rankings.otherRankings.groupedByFiles[file.id][hierarchy.hierarchy_id]
										"
									>
										{{
											hierarchy.rankers[
												rankings.otherRankings.groupedByFiles[file.id][hierarchy.hierarchy_id].ranker_id
											].name
										}}
									</span>
									<span v-else>-</span>
								</td>
							</template>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
		<div v-else id="empty-lists">
			<p>{{ translate('COM_EMUNDUS_RANKING_NO_FILES') }}</p>
		</div>
		<transition name="fade">
			<compare-files
				v-if="defaultFile != null && context !== 'modal' && showCompareFilesModal"
				@close="onCloseCompareFilesModal"
				:user="user"
				:default-file="defaultFile"
				:default-comparison-file="selectedOtherFile"
				:files="rankings.myRanking"
				:tabs="fileTabs"
				:default-tab="hierarchyData.form_id ? 'form-' + this.hierarchyData.form_id : 'application'"
				title="COM_EMUNDUS_CLASSEMENT_MODAL_COMPARISON_HEADER_TITLE"
				@comparison-file-changed="onComparisonFileChanged"
			>
				<template v-slot:before-default-file-tabs>
					<div class="em-ml-8 em-mt-8 tw-mb-4 tw-flex tw-flex-row">
						<div v-if="defaultFile.locked !== 1 && !ismyRankingLocked" class="flex flex-row items-center">
							<label class="em-mr-4"> {{ translate('COM_EMUNDUS_CLASSEMENT_RANKING_SELECT_LABEL') }} </label>
							<select
								v-if="defaultFile.rank > 0"
								name="default-already-ranked-file-select"
								v-model="defaultFile.rank"
								@change="onChangeRankValue(defaultFile)"
							>
								<option value="-1">{{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }}</option>
								<option v-for="i in maxRankValueAvailable" :key="i">{{ i }}</option>
							</select>
							<select
								v-else
								name="default-not-ranked-file-select"
								v-model="defaultFile.rank"
								@change="onChangeRankValue(defaultFile)"
							>
								<option value="-1">{{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }}</option>
								<option v-for="i in maxRankValueAvailableForNotRanked" :key="i">{{ i }}</option>
							</select>
						</div>
						<div v-else>
							<span> {{ translate('COM_EMUNDUS_CLASSEMENT_RANKING_SELECT_LABEL') }} </span>
							<span>{{
								defaultFile.rank > 0 ? defaultFile.rank : translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED')
							}}</span>
						</div>
					</div>
				</template>
				<template v-slot:before-compare-file-tabs>
					<div class="em-ml-8 em-mt-8 tw-mb-4 tw-flex tw-flex-row">
						<div
							v-if="selectedOtherFile && selectedOtherFile.locked !== 1 && !ismyRankingLocked"
							class="flex flex-row items-center"
						>
							<label class="em-mr-4"> {{ translate('COM_EMUNDUS_CLASSEMENT_RANKING_SELECT_LABEL') }} </label>
							<select
								name="already-ranked-other"
								v-if="selectedOtherFile.rank > 0"
								v-model="selectedOtherFile.rank"
								@change="onChangeRankValue(selectedOtherFile)"
							>
								<option value="-1">{{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }}</option>
								<option v-for="i in maxRankValueAvailable" :key="i">{{ i }}</option>
							</select>
							<select
								name="not-ranked-other"
								v-else
								v-model="selectedOtherFile.rank"
								@change="onChangeRankValue(selectedOtherFile)"
							>
								<option value="-1">{{ translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED') }}</option>
								<option v-for="i in maxRankValueAvailableForNotRanked" :key="i">{{ i }}</option>
							</select>
						</div>
						<div v-else-if="selectedOtherFile">
							<span> {{ translate('COM_EMUNDUS_CLASSEMENT_RANKING_SELECT_LABEL') }} </span>
							<span>{{
								selectedOtherFile.rank > 0 ? selectedOtherFile.rank : translate('COM_EMUNDUS_CLASSEMENT_NOT_RANKED')
							}}</span>
						</div>
					</div>
				</template>
				<template v-slot:files-to-compare-with>
					<ranking
						:key="subRankingKey"
						@other-selected-file="onSelectOtherFile"
						@ranking-updated="getRankings"
						:hierarchy_id="hierarchy_id"
						:user="user"
						context="modal"
						:showOtherHierarchies="false"
						:package-id="packageId"
						:readonly="readonly"
						:fileTabsStr="fileTabsStr"
						:specificTabs="specificTabs"
						:tabs="fileTabs"
					>
					</ranking>
				</template>
			</compare-files>
		</transition>
		<modal name="askToLockRankings" id="ask-to-lock-rankings-modal" v-if="showAskToLockRankingsModal">
			<div class="em-flex-column em-p-16">
				<div class="swal2-header em-mb-16">
					<h2 id="swal2-title" class="swal2-title em-swal-title">
						{{ translate('COM_EMUNDUS_CLASSEMENT_ASK_LOCK_RANKING') }}
					</h2>
				</div>
				<div class="swal2-content em-mt-16" style="z-index: 2">
					<label>{{ translate('COM_EMUNDUS_CLASSEMENT_ASK_HIERARCHIES_LOCK_RANKING') }}</label>
					<multiselect
						v-model="askedHierarchiesToLockRanking"
						label="label"
						track-by="hierarchy_id"
						:options="rankings.otherRankings"
						:multiple="true"
						:searchable="true"
						:close-on-select="true"
						:clear-on-select="true"
					></multiselect>

					<label class="em-mt-16">{{ translate('COM_EMUNDUS_CLASSEMENT_ASK_USERS_LOCK_RANKING') }}</label>
					<multiselect
						v-model="askedUsersToLockRanking"
						label="name"
						track-by="user_id"
						:options="otherRankingsRankers"
						:multiple="true"
						:searchable="true"
						:close-on-select="true"
						:clear-on-select="true"
					></multiselect>
				</div>
				<div class="swal2-actions">
					<button
						id="cancelAskLockRanking"
						class="swal2-cancel em-swal-cancel-button swal2-styled"
						@click="closeAskRanking"
					>
						{{ translate('COM_EMUNDUS_CLASSEMENT_CANCEL_ASK_LOCK_RANKING') }}
					</button>
					<button
						id="confirmAskLockRanking"
						class="swal2-confirm em-swal-confirm-button swal2-styled"
						@click="confirmAskLockRanking"
					>
						{{ translate('COM_EMUNDUS_CLASSEMENT_CONFIRM_ASK_LOCK_RANKING') }}
					</button>
				</div>
			</div>
		</modal>
	</div>
</template>

<script>
import translate from '@/mixins/translate.js';
import rankingService from '@/services/ranking.js';
import fileService from '@/services/file.js';

import CompareFiles from '@/components/Files/CompareFiles.vue';
import { VueDraggableNext } from 'vue-draggable-next';
import Multiselect from 'vue-multiselect';
import Swal from 'sweetalert2';
import Modal from '@/components/Modal.vue';

export default {
	name: 'Ranking',
	components: { Multiselect, CompareFiles, draggable: VueDraggableNext, Modal },
	props: {
		user: {
			type: Number,
			required: true,
		},
		hierarchy_id: {
			type: Number,
			required: true,
		},
		context: {
			type: String,
			default: 'page',
		},
		fileTabsStr: {
			type: String,
			default: '',
		},
		specificTabs: {
			type: String,
			default: '',
		},
		showOtherHierarchies: {
			type: Boolean,
			default: true,
		},
		packageId: {
			type: Number,
			default: 0,
		},
		readonly: {
			type: Boolean,
			default: false,
		},
	},
	mixins: [translate],
	data() {
		return {
			rankings: {
				nbFiles: 0,
				myRanking: [],
				otherRankings: [],
				maxRankValue: 0,
			},
			hierarchyData: {},
			defaultFile: {},
			selectedOtherFile: {},
			locked: false,
			subRankingKey: 0,
			askedHierarchiesToLockRanking: [],
			askedUsersToLockRanking: [],
			fileTabs: [],
			loading: false,
			dragging: false,
			pagination: {
				page: 1,
				perPage: 10,
				perPageOptions: [5, 10, 25, 50, 100],
			},
			ordering: {
				orderBy: 'rank',
				orderHierarchy: this.hierarchy_id,
				order: 'ASC',
			},
			emundusStatus: [],
			showAskToLockRankingsModal: false,
			showCompareFilesModal: false,
		};
	},
	created() {
		// check session value for pagination options
		const perPage = sessionStorage.getItem('rankingPerPage');
		if (perPage && !isNaN(perPage)) {
			if (this.pagination.perPageOptions.includes(parseInt(perPage))) {
				this.pagination.perPage = parseInt(perPage);
			} else {
				this.pagination.perPage = 10;
			}
		}

		this.getEmundusStatus();
		this.getRankings();
		this.getOtherHierarchyRankings();
		this.addFilterEventListener();
		this.getHierarchyData();

		if (this.fileTabsStr.length > 0) {
			// explode the string to get the tabs
			let tmpTabs = this.fileTabsStr.split(',');

			tmpTabs.forEach((tab) => {
				switch (tab) {
					case 'forms':
						this.fileTabs.push({
							label: this.translate('COM_EMUNDUS_FILES_APPLICANT_FILE'),
							name: 'application',
							access: '1',
						});
						break;
					case 'attachments':
						this.fileTabs.push({
							label: this.translate('COM_EMUNDUS_FILES_ATTACHMENTS'),
							name: 'attachments',
							access: '4',
						});
						break;
					case 'comments':
						this.fileTabs.push({
							label: this.translate('COM_EMUNDUS_FILES_COMMENTS'),
							name: 'comments',
							access: '10',
						});
						break;
					case 'evaluation':
						this.fileTabs.push({
							label: this.translate('COM_EMUNDUS_FILES_EVALUATION'),
							name: 'evaluation',
							access: '5',
						});
						break;
				}
			});
		}

		if (this.specificTabs.length > 0) {
			let tmpTabs = JSON.parse(this.specificTabs);
			tmpTabs.forEach((tab) => {
				const uniqueName = tab.label.toLowerCase().replace(/ /g, '-');

				this.fileTabs.push({
					label: tab.label,
					name: uniqueName,
					access: 1,
					url: tab.url,
				});
			});
		}
	},
	methods: {
		addFilterEventListener() {
			window.addEventListener('emundus-start-apply-filters', () => {
				this.loading = true;
			});

			window.addEventListener('emundus-apply-filters-success', () => {
				this.getRankings();
				this.getOtherHierarchyRankings();
				document.querySelector('.em-page-loader').classList.add('hidden');
				this.loading = false;
			});
		},
		changePage(direction) {
			const oldPage = this.pagination.page;

			if (direction === '-1' && this.pagination.page > 1) {
				this.pagination.page--;
			} else if (direction === '1' && this.pagination.page < this.nbPagesMax) {
				this.pagination.page++;
			}

			if (oldPage !== this.pagination.page) {
				this.getRankings();
			}
		},
		updatePerPage() {
			sessionStorage.setItem('rankingPerPage', this.pagination.perPage);
			this.getRankings(true);
		},
		/**
		 * @param value
		 * @param column
		 */
		reorder(hierarchy_id, column) {
			if (this.ordering.orderBy === column && this.ordering.orderHierarchy == hierarchy_id) {
				this.ordering.order = this.ordering.order === 'ASC' ? 'DESC' : 'ASC';
			}

			this.ordering.orderBy = column;
			this.ordering.orderHierarchy = hierarchy_id;

			this.getRankings();
		},

		/**
		 *
		 */
		getEmundusStatus() {
			fileService.getAllStatus().then((response) => {
				this.emundusStatus = response.states;
			});
		},

		/**
		 * @param resetPage {boolean} - if true, reset the page to 1, needed when changing the number of files per page
		 * @returns {Promise<void>}
		 */
		async getRankings(resetPage = false) {
			if (resetPage) {
				this.pagination.page = 1;
			}

			return await rankingService.getMyRanking(this.pagination, this.ordering, this.packageId).then((response) => {
				if (response.status) {
					this.rankings.myRanking = response.data.data;
					this.rankings.nbFiles = response.data.total;
					this.rankings.maxRankValue = response.data.maxRankValue == -1 ? 0 : response.data.maxRankValue;

					if (this.defaultFile && this.defaultFile.id) {
						this.defaultFile = this.rankings.myRanking.find((f) => f.id === this.defaultFile.id);
					}

					if (this.selectedOtherFile && this.selectedOtherFile.id) {
						this.selectedOtherFile = this.rankings.myRanking.find((f) => f.id === this.selectedOtherFile.id);
					}
				}
			});
		},
		getOtherHierarchyRankings() {
			if (this.showOtherHierarchies) {
				rankingService.getOtherHierarchyRankings().then((response) => {
					if (response.status) {
						this.rankings.otherRankings = response.data;

						// create a list of the files with all ranking values for each hierarchy
						let rankingGroupedByFiles = {};
						response.data.forEach((hierarchy) => {
							hierarchy.files.forEach((file) => {
								if (!rankingGroupedByFiles[file.id]) {
									rankingGroupedByFiles[file.id] = {};
								}

								rankingGroupedByFiles[file.id][hierarchy.hierarchy_id] = file;
							});
						});

						this.rankings.otherRankings.groupedByFiles = rankingGroupedByFiles;
					}
				});
			}
		},
		getHierarchyData() {
			rankingService.getHierarchyData(this.hierarchy_id).then((response) => {
				if (response.status) {
					this.hierarchyData = response.data;

					if (this.hierarchyData.form_id) {
						this.addSubmitHierarchyFormListener();

						// the form tab is always the first one
						this.fileTabs.unshift({
							label: this.hierarchyData.form_label,
							name: 'form-' + this.hierarchyData.form_id,
							access: '1',
							url:
								'/index.php?option=com_fabrik&view=form&formid=' +
								this.hierarchyData.form_id +
								'&' +
								this.hierarchyData.db_table_name +
								'___fnum={fnum}&tmpl=component&iframe=1',
						});
					}
				}
			});
		},
		onChangeRankValue(file) {
			if (this.readonly) {
				Swal.fire({
					title: this.translate('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_TITLE'),
					text: this.translate('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_LOCKED'),
					icon: 'error',
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
					},
				});

				return;
			}

			if (file.locked == 1) {
				Swal.fire({
					title: this.translate('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_TITLE'),
					text: this.translate('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_LOCKED'),
					icon: 'error',
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
					},
				});
				this.getRankings();
			} else {
				rankingService.updateRanking(file.id, file.rank, this.hierarchy_id).then((response) => {
					if (!response.status) {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_RANKING_UPDATE_RANKING_ERROR_TITLE'),
							text: this.translate(response.msg),
							icon: 'error',
							customClass: {
								title: 'em-swal-title',
								confirmButton: 'em-swal-confirm-button',
							},
						});
					}

					this.getRankings().then(() => {
						this.subRankingKey++;

						if (this.context === 'modal') {
							this.$emit('ranking-updated', file);
						}
					});
				});
			}
		},
		askToLockRankings() {
			if (this.rankingsToLock && this.rankings.myRanking.length > 0) {
				this.showAskToLockRankingsModal = true;
			}
		},
		confirmAskLockRanking() {
			if (this.askedUsersToLockRanking.length > 0 || this.askedHierarchiesToLockRanking) {
				let userIds = this.askedUsersToLockRanking.map((user) => {
					return user.user_id;
				});

				let hierarchyIds = this.askedHierarchiesToLockRanking.map((hierarchy) => {
					return hierarchy.hierarchy_id;
				});

				rankingService.askToLockRankings(userIds, hierarchyIds).then((response) => {
					if (response.status) {
						this.showAskToLockRankingsModal = false;

						// response data contains the list of emails that have been asked to lock the ranking
						if (response.data.length > 0) {
							const emails_html = response.data.map((email) => `<li>${email}</li>`).join('');
							Swal.fire({
								title: this.translate('COM_EMUNDUS_RANKING_LOCK_RANKING_ASK_CONFIRM_SUCCESS_TITLE'),
								html: `<ul>${emails_html}</ul>`,
								icon: 'success',
								delay: 5000,
								customClass: {
									title: 'em-swal-title',
									confirmButton: 'em-swal-confirm-button',
								},
							});
						}
					}
				});

				this.closeAskRanking();
			}
		},
		closeAskRanking() {
			this.showAskToLockRankingsModal = false;
			this.askedUsersToLockRanking = [];
			this.askedHierarchiesToLockRanking = [];
		},
		lockRanking() {
			Swal.fire({
				title: this.translate('COM_EMUNDUS_RANKING_LOCK_RANKING_CONFIRM_TITLE'),
				text: this.translate('COM_EMUNDUS_RANKING_LOCK_RANKING_CONFIRM_TEXT'),
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: this.translate('COM_EMUNDUS_RANKING_LOCK_RANKING_CONFIRM_YES'),
				cancelButtonText: this.translate('COM_EMUNDUS_RANKING_LOCK_RANKING_CONFIRM_NO'),
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					cancelButton: 'em-swal-cancel-button',
					confirmButton: 'em-swal-confirm-button',
				},
			}).then((result) => {
				if (result.value) {
					this.lockRankingConfirmed();
				}
			});
		},
		lockRankingConfirmed() {
			rankingService.lockRanking(this.hierarchy_id, 1).then((response) => {
				if (response.status) {
					this.rankings.myRanking.forEach((file) => {
						file.locked = 1;
					});
				}
			});
		},
		openClickOpenFile(file) {
			if (this.context === 'modal') {
				// dispatch event to open the file
				window.dispatchEvent(new CustomEvent('openSecondaryFile', { detail: { file: file } }));
				this.$emit('other-selected-file', file);
			} else {
				this.defaultFile = file;
				// wait for defaultFile to be set
				setTimeout(() => {
					this.showCompareFilesModal = true;
				}, 100);
			}
		},
		onSelectOtherFile(file) {
			this.selectedOtherFile = file;
		},
		onComparisonFileChanged(defaultFile, selectedFileToCompareWith) {
			this.defaultFile = defaultFile;
			this.selectedOtherFile = selectedFileToCompareWith;
		},
		/**
		 * Drag & drop functions
		 */
		onDragEnd(e) {
			this.dragging = false;
			if (e.to.id != 'ranked-files-list') {
				return;
			}

			const itemId = e.item.dataset.fileId;

			if (itemId) {
				// find the file in the list
				let file = this.rankings.myRanking.find((f) => f.id == itemId);

				if (file) {
					// get rank value at the new index
					// index can be inside of rankedFiles or unrankedFiles
					let newIndex = e.newIndex;
					let newRank = -1;

					if (newIndex < this.rankedFiles.length) {
						newRank = this.rankedFiles[newIndex].rank;
					} else {
						// if new index is superior to the number of ranked files by 1, then the new rank is the max rank value available for not ranked files
						// else do not change the rank value
						newRank = this.maxRankValueAvailableForNotRanked;
					}

					if (file.rank != newRank) {
						file.rank = newRank;
						this.onChangeRankValue(file);
					}
				}
			}
		},
		onCloseCompareFilesModal() {
			this.selectedOtherFile = {};
			this.showCompareFilesModal = false;
		},
		getStatusTag(statusId) {
			let status = this.emundusStatus.find((s) => s.step == statusId);

			if (status) {
				return `<span class="label label-${status.class}">${status.value}</span>`;
			}

			return '';
		},
		addSubmitHierarchyFormListener() {
			// listen to message posted from the iframe, "FinishedHiearchyFormEvent"
			window.addEventListener('message', (event) => {
				if (event.data.type === 'FinishedHiearchyFormEvent') {
					this.getRankings(true);
				}
			});
		},
	},
	computed: {
		nbPagesMax() {
			return this.rankings.nbFiles > 0 ? Math.ceil(this.rankings.nbFiles / this.pagination.perPage) : 1;
		},
		maxRankValue() {
			return this.rankings.maxRankValue;
		},
		maxRankValueAvailable() {
			// max rank value available is the max rank in the list + 1, if all of them are at -1, then it's 1
			if (this.maxRankValue === 0) {
				return 1;
			}

			// max rank can not be higher than the number of files
			if (this.maxRankValue > this.rankings.nbFiles) {
				return this.rankings.nbFiles;
			}

			return this.maxRankValue;
		},
		maxRankValueAvailableForNotRanked() {
			if (this.maxRankValueAvailable != this.rankings.nbFiles && this.maxRankValue > 0) {
				return this.maxRankValueAvailable + 1;
			} else {
				return this.maxRankValueAvailable;
			}
		},
		unrankedFiles() {
			return this.canDragAndDropRanks ? this.rankings.myRanking.filter((file) => file.rank == -1) : [];
		},
		rankedFiles() {
			return this.canDragAndDropRanks
				? this.rankings.myRanking.filter((file) => file.rank != -1)
				: this.rankings.myRanking;
		},
		canDragAndDropRanks() {
			let can = true;

			if (
				this.ordering.orderBy === 'applicant' ||
				(this.ordering.orderHierarchy !== 'default' && this.ordering.orderHierarchy != this.hierarchy_id)
			) {
				can = false;
			}

			return can;
		},
		orderedRankings() {
			// rankedFiles first, then unrankedFiles
			return this.rankedFiles.concat(this.unrankedFiles);
		},
		ismyRankingLocked() {
			if (this.readonly) {
				return true;
			}

			return this.rankings.myRanking.length > 0 ? this.rankings.myRanking.every((file) => file.locked == 1) : false;
		},
		rankingsToLock() {
			let rankingToLock = false;

			this.rankings.otherRankings.forEach((hierarchy) => {
				if (hierarchy && hierarchy.files) {
					const found = hierarchy.files.find((file) => {
						return file.locked == 0;
					});

					if (found) {
						rankingToLock = true;
					}
				}
			});

			return rankingToLock;
		},
		otherRankingsRankers() {
			let rankers = [];
			let ranker_ids = [];

			this.rankings.otherRankings.forEach((ranking) => {
				Object.keys(ranking.rankers).forEach((ranker_id) => {
					if (!ranker_ids.includes(ranker_id)) {
						ranker_ids.push(ranker_id);
						rankers.push(ranking.rankers[ranker_id]);
					}
				});
			});

			return rankers;
		},
		additionalHeaderColumns() {
			let columns = [];

			if (this.rankings.myRanking.length > 0) {
				let firstFile = this.rankings.myRanking[0];

				if (firstFile.additional_columns) {
					columns = Object.keys(firstFile.additional_columns);
				}
			}

			return columns;
		},
	},
};
</script>

<style lang="scss">
.alert.mb-2 {
	margin-bottom: var(--em-spacing-2) !important;
}

#ranking-list {
	#ranking-lists-container {
		align-items: flex-start;

		.file-identifier {
			align-items: flex-start;
		}

		tr,
		td {
			height: 64px;
			white-space: nowrap;
		}

		table:not(#unranked-files) th {
			height: 98px;
		}

		#my-ranking-list,
		#other-ranking-lists {
			border-radius: 4px;
			border-spacing: 0;
			border-collapse: separate;
		}

		#other-ranking-lists {
			overflow: auto;

			th div {
				max-height: 5em;
				overflow: scroll;
				text-overflow: ellipsis;
				font-weight: 700;
			}

			th.border-right,
			td.border-right {
				border-right: 1px solid var(--neutral-300);
			}
		}

		table#ranked-files {
			border-bottom: 0;
		}

		#my-ranking-list {
			border: solid var(--main-200);

			thead th {
				background-color: var(--main-100) !important;
			}

			tbody td,
			tbody tr {
				background-color: var(--main-50);
				border: 0;
			}
		}
	}

	button.em-primary-button {
		span {
			color: var(--neutral-0);
		}

		&:hover {
			span {
				color: var(--main-500);
			}
		}
	}

	button.em-secondary-button {
		span {
			color: var(--em-coordinator-secondary-color);
		}

		&:hover {
			span {
				color: var(--neutral-0);
			}
		}
	}

	.handle:hover {
		cursor: grab;
	}

	.dragging {
		cursor: grabbing;
	}

	.dragging #ranked-files tbody#ranked-files-list {
		border: 4px dashed var(--main-200);
	}

	.dragging #unranked-files-list td {
		background-color: var(--grey-bg-color) !important;
	}
}

#ask-to-lock-rankings-modal .v--modal {
	overflow: unset !important;
	border-radius: 0.3125em !important;
	height: fit-content !important;
}

#compareFiles #my-ranking-list {
	overflow-x: auto;
}
</style>
