<template>
	<div id="export-ranking" class="!tw-rounded tw-p-4">
		<div class="tw-flex tw-flex-row tw-items-center tw-justify-between">
			<h4>{{ translate('COM_EMUNDUS_RANKING_EXPORT_TITLE') }}</h4>
			<span class="material-icons-outlined tw-cursor-pointer" @click="$emit('close')">close</span>
		</div>

		<div class="tw-p-2">
			<h5>{{ translate('COM_EMUNDUS_RANKING_EXPORT_PACKAGES') }}</h5>
			<div id="select-packages-wrapper" class="tw-p-4">
				<div class="tw-flex tw-flex-row tw-items-center">
					<input
						type="checkbox"
						v-model="selectAllPackages"
						@change="toggleAllPackages"
						name="selectAll"
						id="selectAll"
					/>
					<label for="selectAll">{{ translate('COM_EMUNDUS_SELECT_ALL') }}</label>
				</div>
				<div id="select-packages-options" class="tw-grid tw-grid-cols-4">
					<div
						v-for="rankingPackage in userPackages"
						:key="rankingPackage.id"
						class="tw-flex tw-flex-row tw-items-center"
					>
						<input
							type="checkbox"
							v-model="selectedPackages"
							:value="rankingPackage.id"
							name="selectedPackages"
							:id="'package-' + rankingPackage.id"
						/>
						<label :for="'package-' + rankingPackage.id">{{ rankingPackage.label }}</label>
					</div>
				</div>
			</div>

			<div class="p-4" v-if="hierarchies.length > 0">
				<h5>{{ translate('COM_EMUNDUS_RANKING_EXPORT_HIERARCHIES') }}</h5>
				<div v-for="hierarchy in hierarchies" :key="hierarchy.id" class="flex flex-row items-center">
					<input
						type="checkbox"
						v-model="selectedHierarchies"
						:value="hierarchy.id"
						name="selectedHierarchies"
						:id="'hierarchy-' + hierarchy.id"
					/>
					<label :for="'hierarchy-' + hierarchy.id">{{ hierarchy.label }}</label>
				</div>
			</div>

			<h5>{{ translate('COM_EMUNDUS_RANKING_EXPORT_COLUMNS') }}</h5>
			<div class="p-4">
				<draggable
					id="columns-to-export"
					name="columns-to-export"
					:v-model="selectedColumns"
					class="list-group"
					:sort="true"
					handle=".handle"
					@start="dragging = true"
					@end="dragging = false"
				>
					<div
						class="list-group-item tw-flex tw-flex-row tw-items-center tw-justify-between"
						v-for="element in selectedColumns"
						:key="element.id"
					>
						<div class="tw-flex tw-flex-row tw-items-center tw-justify-start">
							<span class="material-icons-outlined tw-handle tw-cursor-grab" title="move column">drag_indicator</span>
							<span>{{ translate(element.label) }}</span>
						</div>
						<span
							class="material-icons-outlined tw-cursor-pointer"
							title="remove column"
							@click="removeColumn(element.id)"
							>remove</span
						>
					</div>
				</draggable>

				<div id="add-column-to-export" class="tw-mb-4 tw-mt-4 tw-flex tw-flex-row" v-if="addableColumns.length > 0">
					<select v-model="columnToAdd" @change="addColumn" id="add-column-to-export-select" class="tw-w-fit">
						<option value="">{{ translate('COM_EMUNDUS_RANKING_SELECT_COLUMN') }}</option>
						<option v-for="addableColumn in addableColumns" :key="addableColumn.id" :value="addableColumn.id">
							{{ translate(addableColumn.label) }}
						</option>
					</select>
				</div>
			</div>
		</div>

		<div class="tw-flex tw-w-full tw-justify-end">
			<span class="tw-btn-primary tw-mr-2 tw-w-fit" @click="exportRanking">
				{{ translate('COM_EMUNDUS_RANKING_EXPORT_BUTTON') }}
			</span>
			<a v-if="downloadLink" class="tw-btn-primary tw-w-fit" :href="downloadLink" download>
				<span>{{ translate('COM_EMUNDUS_RANKING_EXPORT_DOWNLOAD_FILE') }}</span>
				<span class="material-icons-outlined em-text-neutral-300">file_download</span>
			</a>
		</div>
	</div>
</template>

<script>
import rankingService from '@/services/ranking.js';
import { VueDraggableNext } from 'vue-draggable-next';

export default {
	name: 'export-ranking',
	props: {
		user: {
			type: Number,
			required: true,
		},
		packages: {
			type: Array,
			default: () => {
				return [];
			},
		},
		currentPackage: {
			type: Number,
			default: 0,
		},
	},
	components: {
		draggable: VueDraggableNext,
	},
	data() {
		return {
			userPackages: [],
			selectAllPackages: false,
			selectedPackages: [],
			hierarchies: [],
			selectedHierarchies: [],
			columns: [
				{ id: 'id', label: 'COM_EMUNDUS_RANKING_EXPORT_FILE_ID' },
				{ id: 'fnum', label: 'COM_EMUNDUS_RANKING_EXPORT_FILE_FNUM' },
				{ id: 'rank', label: 'COM_EMUNDUS_RANKING_EXPORT_RANKING' },
				{ id: 'applicant', label: 'COM_EMUNDUS_RANKING_EXPORT_APPLICANT' },
				{ id: 'status', label: 'COM_EMUNDUS_RANKING_EXPORT_STATUS' },
				{ id: 'ranker', label: 'COM_EMUNDUS_RANKING_EXPORT_RANKER' },
			],
			selectedColumns: [],
			downloadLink: null,
			dragging: false,
			columnToAdd: '',
		};
	},
	created() {
		if (this.packages.length === 0) {
			this.getPackages();
		} else {
			this.userPackages = this.packages;
		}

		if (this.currentPackage != 0) {
			this.selectedPackages.push(this.currentPackage);
		}

		this.selectedColumns = this.columns;
		this.getHierarchiesUserCanSee();
	},
	methods: {
		getPackages() {
			rankingService
				.getPackages()
				.then((response) => {
					this.userPackages = response.data;
				})
				.catch((error) => {
					console.log(error);
				});
		},
		getHierarchiesUserCanSee() {
			rankingService
				.getHierarchiesUserCanSee()
				.then((response) => {
					this.hierarchies = response.data;
				})
				.catch((error) => {
					console.log(error);
				});
		},
		toggleAllPackages() {
			if (this.selectAllPackages) {
				this.selectedPackages = this.userPackages.map((p) => p.id);
			} else {
				this.selectedPackages = [];
			}
		},
		exportRanking() {
			this.downloadLink = null;
			if (this.selectedPackages.length === 0) {
				alert('Please select at least one package');
				return;
			}

			rankingService
				.exportRanking(this.selectedPackages, this.selectedHierarchies, this.selectedColumns)
				.then((response) => {
					if (response.data) {
						this.downloadLink = response.data.data;
					}
				})
				.catch((error) => {
					console.log(error);
				});
		},
		removeColumn(columnId) {
			this.selectedColumns = this.selectedColumns.filter((column) => {
				return column.id !== columnId;
			});
		},
		addColumn() {
			if (this.columnToAdd && this.columnToAdd !== '') {
				let columnToAddObject = this.columns.find((column) => {
					return column.id === this.columnToAdd;
				});

				if (columnToAddObject) {
					this.selectedColumns.push(columnToAddObject);
				}
			}

			this.columnToAdd = '';
		},
	},
	computed: {
		addableColumns() {
			return this.columns.filter((column) => {
				return !this.selectedColumns.find((selectedColumn) => {
					return selectedColumn.id === column.id;
				});
			});
		},
	},
};
</script>

<style scoped>
label {
	margin: 0;
}

#select-packages-options {
	max-height: 400px;
	overflow-y: auto;
}

.tw-btn-primary:hover {
	.material-icons-outlined {
		color: var(--em-profile-color);
	}
}
</style>
