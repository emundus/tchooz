<template>
	<div id="rankings-by-package">
		<div class="tw-flex tw-w-full tw-justify-end">
			<button v-if="canExport" class="tw-btn-primary tw-w-fit" @click="openExportView">
				{{ translate('COM_EMUNDUS_RANKING_EXPORT_RANKINGS_BTN') }}
			</button>
		</div>
		<div>
			<ul
				v-if="packagesGroups.length > 1"
				id="ranking-navigation-packages-groups"
				class="tw-flex tw-list-none tw-flex-row tw-overflow-auto"
			>
				<li
					v-for="group in packagesGroups"
					:key="group.id"
					class="ranking-navigation-item tw-cursor-pointer tw-rounded-t-lg tw-px-2.5 tw-py-3 tw-text-center tw-shadow"
					:class="{
						'em-bg-main-500 em-text-neutral-300 opacity-80': selectedGroup === group.id,
						'tw-bg-white': selectedGroup !== group.id,
					}"
					@click="
						selectedGroup = group.id;
						selectedPackage = selectedPackages[0].id;
					"
					:title="group.label"
				>
					<span>{{ group.label }}</span>
				</li>
			</ul>

			<div class="tw-rounded tw-p-4 tw-shadow">
				<nav id="ranking-navigation">
					<ul id="ranking-navigation-packages" class="tw-flex tw-list-none tw-flex-row tw-overflow-auto">
						<li
							v-for="rankingPackage in selectedPackages"
							:key="rankingPackage.id"
							class="ranking-navigation-item tw-cursor-pointer tw-rounded-t-lg tw-px-2.5 tw-py-3 tw-text-center tw-shadow"
							:class="{
								'em-bg-main-500 em-text-neutral-300': selectedPackage === rankingPackage.id,
								'tw-bg-white': selectedPackage !== rankingPackage.id,
							}"
							@click="selectedPackage = rankingPackage.id"
							:title="rankingPackage.label"
						>
							<span>{{ rankingPackage.label }}</span>
						</li>
					</ul>
				</nav>
				<div v-if="selectedPackage !== null" class="tw-rounded tw-bg-white tw-p-4 tw-shadow">
					<h3>{{ selectedPackageItem.label }}</h3>

					<div class="package-dates tw-mt-2">
						<p v-if="selectedPackageItem.start_date" :id="'package-start-date-' + selectedPackageItem.id">
							{{ translate('COM_EMUNDUS_RANKING_PACKAGE_START_DATE') }}
							<strong>{{ selectedPackageItem.start_date }}</strong>
						</p>
						<p v-if="selectedPackageItem.end_date" :id="'package-end-date-' + selectedPackageItem.id">
							{{ translate('COM_EMUNDUS_RANKING_PACKAGE_END_DATE') }}
							<strong>{{ selectedPackageItem.end_date }}</strong>
						</p>
					</div>

					<ranking
						:key="'classement-' + selectedPackage"
						:user="user"
						:hierarchy_id="hierarchy_id"
						:fileTabsStr="fileTabsStr"
						:specificTabs="specificTabs"
						:packageId="selectedPackage"
						:readonly="readonly"
					>
					</ranking>
				</div>
			</div>
		</div>

		<modal id="export-modal" name="export-modal" v-if="showExportModal" class="tw-shadow" :clickToClose="false">
			<export-ranking
				:user="user"
				:packages="packages"
				:current-package="selectedPackage !== null ? selectedPackageItem.id : 0"
				@close="closeExportModal"
			>
			</export-ranking>
		</modal>
	</div>
</template>

<script>
import rankingService from '@/services/ranking.js';
import Ranking from '@/views/Ranking/ranking.vue';
import ExportRanking from '@/views/Ranking/ExportRanking.vue';
import Modal from '@/components/Modal.vue';

export default {
	name: 'rankings',
	components: { Ranking, ExportRanking, Modal },
	props: {
		user: {
			type: Number,
			required: true,
		},
		hierarchy_id: {
			type: Number,
			required: true,
		},
		fileTabsStr: {
			type: String,
			default: '',
		},
		specificTabs: {
			type: String,
			default: '',
		},
		canExport: {
			type: Boolean,
			default: true,
		},
		readonly: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			packages: [],
			packagesGroups: [],
			selectedGroup: null,
			selectedPackage: null,
			showExportModal: false,
		};
	},
	created() {
		this.getPackages();
	},
	methods: {
		getPackages() {
			rankingService
				.getPackages()
				.then((response) => {
					this.packages = response.data;

					this.packages.forEach((item) => {
						if (item.group_id) {
							if (!this.packagesGroups.find((group) => group.id === item.group_id)) {
								this.packagesGroups.push({
									id: item.group_id,
									label: item.group_id,
								});
							}
						}
					});

					this.selectedPackage = this.packages[0].id;
				})
				.catch((error) => {
					console.log(error);
				});
		},
		openExportView() {
			this.showExportModal = true;
		},
		closeExportModal() {
			this.showExportModal = false;
		},
	},
	computed: {
		selectedPackages() {
			return this.selectedGroup !== null
				? this.packages.filter((item) => item.group_id === this.selectedGroup)
				: this.packages;
		},
		selectedPackageItem() {
			return this.selectedPackage != null ? this.packages.find((item) => item.id === this.selectedPackage) : null;
		},
	},
};
</script>

<style>
#rankings-by-package {
	.ranking-navigation-item {
		min-width: 200px;
		max-width: 200px;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	#export-modal {
		max-height: 80vh !important;
		width: 80vw !important;
		top: 10vh !important;
		left: 10vw !important;
		height: auto !important;
		border-radius: 0.25rem !important;
	}
}
</style>
