<template>
	<div
		:id="'rankingTool'"
		class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<h2>{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_GENERAL') }}</h2>

		<hr />

		<h3>{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHIES') }}</h3>
		<div id="hierarchies" class="mt-4">
			<div
				v-for="hierarchy in hierarchies"
				:key="hierarchy.id"
				class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
			>
				<div class="tw-flex tw-flex-row tw-items-start tw-justify-between">
					<div>
						<label :for="'hierarchy_label' + hierarchy.id">
							{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHY_LABEL') }}
						</label>
						<input
							:id="'hierarchy_label' + hierarchy.id"
							name="hierarchy_label"
							type="text"
							v-model="hierarchy.label"
						/>
					</div>
					<span
						class="material-icons-outlined tw-cursor-pointer"
						:alt="translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_DELETE_HIERARCHY')"
						@click="deleteHierarchy(hierarchy.id)"
						>delete</span
					>
				</div>
				<div>
					<div class="profiles tw-mt-4">
						<label>{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHY_PROFILES') }}</label>
						<multiselect
							v-model="hierarchy.profiles"
							label="label"
							track-by="id"
							:options="profilesOpts"
							:multiple="true"
							:taggable="false"
							:placeholder="translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_SELECT_VALUE')"
							:close-on-select="true"
							:clear-on-select="false"
							:searchable="false"
							:allow-empty="true"
						></multiselect>
					</div>
					<div id="editable_status" class="tw-mt-4">
						<label>{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHY_EDIT_STATUS') }}</label>
						<multiselect
							v-model="hierarchy.editable_status"
							label="value"
							track-by="step"
							:options="states"
							:multiple="true"
							:taggable="false"
							:placeholder="translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_SELECT_VALUE')"
							:close-on-select="true"
							:clear-on-select="false"
							:searchable="false"
							:allow-empty="true"
						></multiselect>
					</div>
					<div id="visible_status" class="tw-mt-4">
						<label>{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHY_VISIBLE_STATUS') }}</label>
						<multiselect
							v-model="hierarchy.visible_status"
							label="value"
							track-by="step"
							:options="states"
							:multiple="true"
							:taggable="false"
							:placeholder="translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_SELECT_VALUE')"
							:close-on-select="true"
							:clear-on-select="false"
							:searchable="false"
							:allow-empty="true"
						></multiselect>
					</div>
					<div id="visible_hierarchies" class="tw-mt-4">
						<label>{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHY_VISIBLE_HIERARCHIES') }}</label>
						<multiselect
							v-model="hierarchy.visible_hierarchy_ids"
							label="label"
							track-by="id"
							:options="hierarchiesOpts"
							:multiple="true"
							:taggable="false"
							:placeholder="translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_SELECT_VALUE')"
							:close-on-select="true"
							:clear-on-select="false"
							:searchable="false"
							:allow-empty="true"
						></multiselect>
					</div>
					<div class="published tw-mt-4">
						<label>{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHY_PUBLISHED') }}</label>
						<div class="tw-flex tw-flex-row tw-items-center tw-gap-2">
							<div class="tw-flex tw-flex-row tw-items-center tw-gap-2">
								<input
									type="radio"
									:id="'hierarchy-published-' + hierarchy.id + '-yes'"
									:name="'hierarchy-published-' + hierarchy.id"
									value="1"
									v-model="hierarchy.published"
								/>
								<label class="!tw-m-0" :for="'hierarchy-published-' + hierarchy.id + '-yes'">{{
									translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_YES')
								}}</label>
							</div>
							<div class="tw-flex tw-flex-row tw-items-center tw-gap-2">
								<input
									type="radio"
									:id="'hierarchy-published-' + hierarchy.id + '-no'"
									:name="'hierarchy-published-' + hierarchy.id"
									value="0"
									v-model="hierarchy.published"
								/>
								<label class="!tw-m-0" :for="'hierarchy-published-' + hierarchy.id + '-no'">{{
									translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_NO')
								}}</label>
							</div>
						</div>
					</div>

					<div id="form_id" class="tw-mt-4 tw-flex tw-flex-col">
						<label>{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_FORM_ID') }}</label>
						<select v-model="hierarchy.form_id">
							<option :value="0">{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_SELECT_VALUE') }}</option>
							<option v-for="form in forms" :key="form.id" :value="form.id">{{ form.label }}</option>
						</select>
					</div>
				</div>
				<div class="tw-mt-2 tw-flex tw-flex-row tw-justify-end">
					<button class="tw-btn-primary tw-w-fit" @click="saveHierarchy(hierarchy.id)">
						{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_SAVE_HIERARCHY') }}
					</button>
				</div>
			</div>
			<div v-if="!newHierarchy" class="tw-mt-4 tw-flex tw-flex-row tw-justify-end">
				<button class="tw-btn-primary tw-w-fit" @click="addHierarchy">
					{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_ADD_HIERARCHY') }}
				</button>
			</div>
		</div>
	</div>
</template>

<script>
import Multiselect from 'vue-multiselect';

import rankingService from '@/services/ranking';
import userService from '@/services/user';
import fileService from '@/services/file';
import settingsService from '@/services/settings';

export default {
	name: 'RankingTool',
	props: {},
	components: {
		Multiselect,
	},
	data() {
		return {
			hierarchies: [],
			profiles: [],
			states: [],
			forms: [],
			currentMenu: 1,
			menus: [
				{
					title: 'COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_GENERAL',
					index: 1,
				},
			],
			loading: false,
			saving: false,
			last_save: null,
			default_hierarchy: {
				id: 'tmp',
				parent_id: '0',
				label: 'Nouvelle hiérarchie',
				published: '1',
				package_by: 'jos_emundus_setup_campaigns.id',
				package_start_date_field: '',
				package_end_date_field: '',
				visible_status: [],
				editable_status: [],
				visible_hierarchy_ids: [],
				profiles: [],
			},
		};
	},
	created() {
		this.initialise();
	},
	methods: {
		async initialise() {
			this.loading = true;
			await this.getProfiles();
			await this.getStates();
			this.getForms();
			await this.getHierarchies();
			this.loading = false;
		},
		getHierarchies() {
			return rankingService.getHierarchies().then((response) => {
				let hierarchies = response.data;

				hierarchies.forEach((hierarchy) => {
					// visible status is currently an array of integers, we need to convert it to an array of objects
					hierarchy.visible_status =
						hierarchy.visible_status.length > 0
							? hierarchy.visible_status.map((status) => {
									return this.states.find((state) => {
										return state.step == status;
									});
								})
							: [];

					hierarchy.editable_status =
						hierarchy.editable_status.length > 0
							? hierarchy.editable_status.map((status) => {
									return this.states.find((state) => {
										return state.step == status;
									});
								})
							: [];

					// profiles is currently an array of integers, we need to convert it to an array of objects
					hierarchy.profiles =
						hierarchy.profiles.length > 0
							? hierarchy.profiles.map((profile_id) => {
									return this.profiles.find((profile) => {
										return profile.id == profile_id;
									});
								})
							: [];

					// visible hierarchy ids is currently an array of integers, we need to convert it to an array of objects
					hierarchy.visible_hierarchy_ids =
						hierarchy.visible_hierarchy_ids.length > 0
							? hierarchy.visible_hierarchy_ids.map((id) => {
									return hierarchies.find((hierarchy_data) => {
										return hierarchy_data.id == id;
									});
								})
							: [];
				});

				this.hierarchies = hierarchies;
			});
		},
		getProfiles() {
			return userService.getProfiles().then((response) => {
				this.profiles = response.data;
			});
		},
		getStates() {
			return fileService.getAllStatus().then((response) => {
				this.states = response.states;
			});
		},
		async getForms() {
			return settingsService.getFabrikFormsList().then((response) => {
				if (response.status) {
					this.forms = response.data ? response.data : [];
				}
			});
		},
		addHierarchy() {
			if (!this.newHierarchy) {
				this.hierarchies.push(this.default_hierarchy);
			}
		},
		saveHierarchy(hierarchyId) {
			if (hierarchyId) {
				const hierarchyToSave = this.hierarchies.find((hierarchy) => {
					return hierarchy.id === hierarchyId;
				});

				if (hierarchyToSave) {
					this.saving = true;
					rankingService.saveHierarchy(hierarchyToSave).then((response) => {
						if (response.status) {
							Swal.fire({
								type: 'success',
								title: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHY_SAVED'),
								showConfirmButton: false,
								timer: 1500,
							});
							this.last_save = new Date().toLocaleTimeString();
							this.getHierarchies();
						}

						this.saving = false;
					});
				}
			}
		},
		deleteHierarchy(hierarchyId) {
			this.hierarchies = this.hierarchies.filter((hierarchy) => {
				return hierarchy.id !== hierarchyId;
			});

			if (hierarchyId !== 'tmp') {
				rankingService.deleteHierarchy(hierarchyId).then((response) => {
					if (!response.data) {
						rankingService.getHierarchies();
					} else {
						Swal.fire({
							type: 'success',
							title: this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_RANKING_HIERARCHY_DELETED'),
							showConfirmButton: false,
							timer: 1500,
						});
					}
				});
			}
		},
		beforeClose() {
			this.$emit('resetMenuIndex');
		},
	},
	computed: {
		newHierarchy() {
			return this.hierarchies.find((hierarchy) => {
				return hierarchy.id === 'tmp';
			});
		},
		hierarchiesOpts() {
			return this.hierarchies.map((hierarchy) => {
				return {
					id: hierarchy.id,
					label: hierarchy.label,
				};
			});
		},
		profilesOpts() {
			return this.profiles.map((profile) => {
				return {
					id: profile.id,
					label: profile.label,
				};
			});
		},
	},
};
</script>

<style scoped></style>
