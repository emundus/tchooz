<!-- This view is for the allmighty user who can see all the rankings and edit them, and force ranking with no limitation. -->
<template>
	<div id="admin_ranking_view" class="tw-mt-4">
		<ul id="hierarchies" class="tw-flex tw-list-none tw-flex-row tw-gap-2 tw-overflow-auto">
			<li
				v-for="hierarchy in hierarchies"
				:key="hierarchy.id"
				@click="getAllRankings(hierarchy.id)"
				class="tw-flex tw-cursor-pointer tw-items-center tw-rounded-t-lg tw-border-x tw-border-t tw-border-profile-full tw-px-4 tw-py-2 tw-transition-colors tw-duration-300"
				:class="{
					'tw-bg-neutral-200': selectedHierarchy != hierarchy.id,
					'tw-bg-white': selectedHierarchy === hierarchy.id,
				}"
			>
				<span>{{ hierarchy.label }}</span>
			</li>
		</ul>

		<div id="filters" class="tw-mb-4 tw-mt-4 tw-flex tw-flex-row tw-gap-3">
			<input
				id="file_fnum_or_applicant_name"
				name="file_fnum_or_applicant_name"
				type="text"
				v-model="filters.fileOrApplicantName"
				@focusout="getAllRankings(selectedHierarchy)"
				:placeholder="translate('COM_EMUNDUS_RANKING_APPLICANT_NAME')"
			/>

			<multiselect
				:options="filters.programOptions"
				label="label"
				track-by="id"
				placeholder="Programs"
				v-model="filters.selectedPrograms"
				@update:modelValue="getAllRankings(selectedHierarchy)"
				:multiple="true"
			/>
			<multiselect
				:options="filters.campaignOptions"
				label="label"
				track-by="id"
				placeholder="Campaigns"
				v-model="filters.selectedCampaigns"
				@update:modelValue="getAllRankings(selectedHierarchy)"
				:multiple="true"
			/>
			<multiselect
				:options="filters.statusOptions"
				label="label"
				track-by="id"
				placeholder="Status"
				v-model="filters.selectedStatus"
				@update:modelValue="getAllRankings(selectedHierarchy)"
				:multiple="true"
			>
			</multiselect>

			<input
				id="ranker"
				type="text"
				name="ranker"
				v-model="filters.rankerName"
				@focusout="getAllRankings(selectedHierarchy)"
				:placeholder="translate('COM_EMUNDUS_RANKING_EXPORT_RANKER')"
			/>
		</div>

		<div class="tw-flex tw-flex-row tw-justify-end">
			<button
				class="tw-btn-primary tw-mb-4 tw-w-fit"
				@click="getAllRankings(selectedHierarchy)"
				@keyup.enter="getAllRankings(selectedHierarchy)"
			>
				{{ translate('SEARCH') }}
			</button>
		</div>

		<table>
			<thead>
				<tr>
					<th @click="orderBy('ecc.fnum')">{{ translate('COM_EMUNDUS_RANKING_FILE_ID') }}</th>
					<th @click="orderBy('u.name')">{{ translate('COM_EMUNDUS_RANKING_APPLICANT_NAME') }}</th>
					<th @click="orderBy('ess.value')">{{ translate('COM_EMUNDUS_RANKING_FILE_STATUS') }}</th>
					<th @click="orderBy('esp.label')">{{ translate('COM_EMUNDUS_RANKING_FILE_PROGRAM') }}</th>
					<th @click="orderBy('esc.label')">{{ translate('COM_EMUNDUS_RANKING_FILE_CAMPAIGN') }}</th>
					<th @click="orderBy('er.id')">{{ translate('COM_EMUNDUS_RANKING_RANKING_ROW_ID') }}</th>
					<th @click="orderBy('er.rank')">{{ translate('COM_EMUNDUS_RANKING_RANK') }}</th>
					<th @click="orderBy('er.user_id')">{{ translate('COM_EMUNDUS_RANKING_RANKER_NAME') }}</th>
				</tr>
			</thead>
			<tbody>
				<tr v-for="ranking in rankings" :key="ranking.ccid">
					<td>{{ ranking.fnum }}</td>
					<td>{{ ranking.applicant_name }}</td>
					<td>{{ ranking.status_label }}</td>
					<td>{{ ranking.program_label }}</td>
					<td>{{ ranking.campaign_label }}</td>
					<td>{{ ranking.rank_row_id }}</td>
					<td>
						<input
							type="text"
							v-model="ranking.rank"
							@change="updateRank(ranking.rank_row_id, ranking.rank, ranking.ccid)"
						/>
					</td>
					<td
						class="tw-lex-row tw-flex tw-items-center tw-justify-between"
						v-if="editRankerForRowId != ranking.ccid + '-' + ranking.hierarchy_id"
					>
						<span>{{ ranking.ranker_name }}</span>
						<span
							v-if="ranking.rank_row_id"
							class="material-symbols-outlined tw-cursor-pointer"
							@click="editRankerForRowId = ranking.ccid + '-' + ranking.hierarchy_id"
							>edit</span
						>
					</td>
					<td v-else class="tw-flex tw-flex-row tw-items-center" style="min-width: 300px">
						<multiselect
							:options="rankersByHierarchy[selectedHierarchy]"
							label="name"
							track-by="id"
							v-model="ranking.ranker"
							@update:modelValue="updateRowIdRanker(ranking.rank_row_id, ranking.ranker, ranking.ccid)"
						>
						</multiselect>
						<span class="material-symbols-outlined tw-color-red tw-cursor-pointer" @click="editRankerForRowId = ''"
							>close</span
						>
					</td>
				</tr>
			</tbody>
		</table>
	</div>
</template>

<script>
import rankingService from '@/services/ranking.js';
import campaignsService from '@/services/campaigns.js';
import programsService from '@/services/programme.js';
import fileService from '@/services/file.js';
import multiselect from 'vue-multiselect';

export default {
	name: 'allRankings',
	components: {
		multiselect,
	},
	data() {
		return {
			hierarchies: [],
			selectedHierarchy: null,
			rankings: [],
			filters: {
				programOptions: [],
				selectedPrograms: [],
				campaignOptions: [],
				selectedCampaigns: [],
				statusOptions: [],
				selectedStatus: [],
				fileOrApplicantName: '',
				rankerName: '',
			},
			rankersByHierarchy: {},
			editRankerForRowId: '',
			order: {
				column: 'fnum',
				direction: 'ASC',
			},
		};
	},
	created() {
		campaignsService.getAllCampaigns().then((response) => {
			this.filters.campaignOptions = response.data.datas.map((campaign) => {
				return {
					id: campaign.id,
					label: campaign.label.fr,
				};
			});
		});

		programsService.getAllPrograms().then((response) => {
			this.filters.programOptions = response.data.datas.map((program) => {
				return {
					id: program.id,
					label: program.label.fr,
				};
			});
		});

		fileService.getAllStatus().then((response) => {
			this.filters.statusOptions = response.states.map((status) => {
				return {
					id: status.step,
					label: status.value,
				};
			});
		});

		rankingService.getAllRankers().then((response) => {
			this.rankersByHierarchy = response.data;
		});

		rankingService.getHierarchies().then((response) => {
			this.hierarchies = response.data;
			this.getAllRankings(this.hierarchies[0].id);
		});
	},
	methods: {
		getAllRankings(hierarchy_id) {
			rankingService
				.getAllRankings(hierarchy_id, this.filters, this.order)
				.then((response) => {
					this.selectedHierarchy = hierarchy_id;
					this.rankings = response.data;
				})
				.catch((e) => {
					console.log(e);
				});
		},
		updateRank(rankRowId, newRank, ccid) {
			rankingService
				.rawUpdateRank(rankRowId, newRank, ccid, this.selectedHierarchy)
				.then((response) => {
					this.getAllRankings(this.selectedHierarchy);
				})
				.catch((e) => {
					console.log(e);
				});
		},
		updateRowIdRanker(rankRowId, newRanker, ccid) {
			let newRankerId = newRanker.id;

			rankingService
				.rawUpdateRanker(rankRowId, newRankerId, ccid, this.selectedHierarchy)
				.then((response) => {
					this.getAllRankings(this.selectedHierarchy);
				})
				.catch((e) => {
					console.log(e);
				});

			this.editRankerForRowId = '';
		},
		orderBy(column) {
			if (this.order.column === column) {
				this.order.direction = this.order.direction === 'ASC' ? 'DESC' : 'ASC';
			} else {
				this.order.column = column;
				this.order.direction = 'ASC';
			}

			this.getAllRankings(this.selectedHierarchy);
		},
	},
};
</script>

<style></style>
