<script>
import Tabs from '@/components/Utils/Tabs.vue';
import settingsService from '@/services/settings.js';
import { useGlobalStore } from '@/stores/global.js';
import groupsService from '@/services/groups.js';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import Button from '@/components/Atoms/Button.vue';
import programmeService from '@/services/programme.js';
import AccessRightsTable from '@/components/Groups/AccessRightsTable.vue';
import UsersList from '@/components/Groups/UsersList.vue';
import Loader from '@/components/Atoms/Loader.vue';

export default {
	name: 'GroupForm',
	components: { Loader, UsersList, AccessRightsTable, Button, ParameterForm, Tabs },
	props: {
		group_id: {
			type: Number,
			default: 0,
		},
		colors: {
			type: Array,
			default: [],
		},
		statuses: {
			type: Array,
			default: [],
		},
		attachments: {
			type: Array,
			default: [],
		},
		groups: {
			type: Array,
			default: [],
		},
	},
	data: () => ({
		loading: true,
		loader: false,

		group: null,
		searchThroughPrograms: '',
		checkall: 0,

		tabs: [
			{
				id: 1,
				name: 'COM_EMUNDUS_GROUPS_ADD_GROUP_GENERAL',
				description: '',
				icon: 'info',
				active: true,
				displayed: true,
			},
			{
				id: 2,
				name: 'COM_EMUNDUS_GROUPS_ADD_GROUP_PROGRAMMES',
				description: '',
				icon: 'event_list',
				active: false,
				displayed: true,
				disabled: true,
			},
			{
				id: 3,
				name: 'COM_EMUNDUS_GROUPS_ADD_GROUP_RIGHTS',
				description: '',
				icon: 'shield_toggle',
				active: false,
				displayed: true,
				disabled: true,
			},
			{
				id: 4,
				name: 'COM_EMUNDUS_GROUPS_USERS_ASSOCIATE',
				description: '',
				icon: 'patient_list',
				active: false,
				displayed: true,
				disabled: true,
			},
		],

		generalGroup: {
			id: 'default-group',
			title: '',
			description: '',
			helpTextType: 'above',
			parameters: [
				{
					param: 'label',
					type: 'text',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_GROUPS_GROUP_LABEL',
					helptext: '',
					displayed: true,
				},
				{
					param: 'description',
					type: 'textarea',
					maxlength: 500,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_GROUPS_DESCRIPTION',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'class',
					type: 'color',
					swatches: null,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_GROUPS_COLOR',
					helptext: '',
					displayed: true,
				},
				{
					param: 'filter_status',
					type: 'toggle',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_GROUPS_GROUP_FILTER_STATUS_LABEL',
					helptext: '',
					displayed: true,
					hideLabel: true,
				},
				{
					param: 'status',
					type: 'multiselect',
					reload: 0,
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: true,
						asyncRoute: '',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [],
						optionsLimit: 30,
						label: 'name',
						trackBy: 'value',
					},
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_GROUPS_GROUP_FILTER_STATUS_LABEL',
					helptext: '',
					displayed: false,
					displayRules: [
						{
							field: 'filter_status',
							value: 1,
						},
					],
				},
				{
					param: 'anonymize',
					type: 'toggle',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_GROUPS_GROUP_ANONYMIZE_LABEL',
					helptext: '',
					displayed: true,
					hideLabel: true,
				},
				{
					param: 'visible_groups',
					type: 'multiselect',
					reload: 0,
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: true,
						closeOnSelect: false,
						asyncRoute: '',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [],
						optionsLimit: 30,
						label: 'label',
						trackBy: 'id',
						groupValues: 'groups',
						groupLabel: 'formLabel',
						groupSelect: true,
					},
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_GROUPS_VISIBLE_GROUPS_LABEL',
					helptext: 'COM_EMUNDUS_GROUPS_VISIBLE_GROUPS_DESC',
					optional: true,
					displayed: true,
				},
				{
					param: 'visible_attachments',
					type: 'multiselect',
					reload: 0,
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: true,
						asyncRoute: '',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [],
						optionsLimit: 100,
						label: 'name',
						trackBy: 'value',
					},
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_GROUPS_GROUP_ATTACHMENTS_LABEL',
					helptext: 'COM_EMUNDUS_GROUPS_GROUP_ATTACHMENTS_DESC',
					optional: true,
					displayed: true,
				},
			],
			isRepeatable: false,
		},
		programs: [],

		programsOptions: [],
	}),
	created() {
		this.generalGroup.parameters.find((field) => field.param === 'class').swatches =
			this.colors && this.colors.length > 0 ? this.colors : null;
		this.generalGroup.parameters.find((field) => field.param === 'status').multiselectOptions.options = this.statuses;
		this.generalGroup.parameters.find((field) => field.param === 'visible_attachments').multiselectOptions.options =
			this.attachments;
		this.generalGroup.parameters.find((field) => field.param === 'visible_groups').multiselectOptions.options =
			this.groups;

		this.getPrograms().then(() => {
			if (this.group_id) {
				this.getGroup(this.group_id);
			} else {
				this.loading = false;
			}
		});
	},
	methods: {
		goBack() {
			if (typeof window.history !== 'undefined') {
				window.history.back();
			} else {
				this.redirectJRoute('index.php?option=com_emundus&view=groups');
			}
		},
		redirectJRoute(link) {
			settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
		},
		handleChangeTab(tab_id) {
			this.$refs.tabsComponent.changeTab(tab_id);
		},
		displayDisabledMessage(tab) {
			Swal.fire({
				position: 'center',
				icon: 'warning',
				title: Joomla.JText._('COM_EMUNDUS_GROUPS_ADD_PLEASE_CREATE_FIRST'),
				showConfirmButton: true,
				allowOutsideClick: false,
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					actions: 'em-swal-single-action',
				},
			});
		},
		onClickCheckAllProgram() {
			if (this.checkall) {
				this.displayedProgramsOptions.forEach((program) => {
					this.programs.push(program);
				});
			} else {
				this.displayedProgramsOptions.forEach((program) => {
					this.programs = this.programs.filter((p) => p.code !== program.code);
				});
			}
		},

		async getPrograms() {
			return await programmeService
				.getAllPrograms('', '', 0, 0, 'p.label', 'ASC')
				.then((response) => {
					this.programsOptions = response.data.datas.map((program) => {
						return {
							code: program.code,
							label: program.label[useGlobalStore().shortLang],
						};
					});
				})
				.catch((e) => {
					console.log(e);
				});
		},
		getGroup(group_id, change_tab = 0) {
			groupsService.getGroup(group_id).then((response) => {
				if (response.status) {
					this.group = response.data;

					this.loading = false;

					this.generalGroup.parameters.forEach((field) => {
						if (this.group[field.param]) {
							field.value = this.group[field.param];
						}
					});

					this.group.programs.forEach((program) => {
						let program_option = this.programsOptions.find((option) => option.code === program.code);
						if (program_option) {
							this.programs.push(program_option);
						}
					});

					groupsService.getAccessRights(group_id).then((response) => {
						if (response.status) {
							this.group.access_rights = response.data;
						}
					});

					this.tabs[1].disabled = false;
					this.tabs[2].disabled = false;
					this.tabs[3].disabled = false;
				}

				if (change_tab) {
					this.handleChangeTab(change_tab);
				}
			});
		},

		saveGroup() {
			let group_form = {};

			// Validate all fields
			const groupValidationFailed = this.generalGroup.parameters.some((field) => {
				if (field.displayed) {
					let ref_name = 'field_' + field.param;

					if (this.$refs[ref_name] && !this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					if (field.type === 'multiselect') {
						if (field.multiselectOptions.multiple) {
							group_form[field.param] = [];
							field.value.forEach((element) => {
								group_form[field.param].push(element[field.multiselectOptions.trackBy]);
							});
						} else {
							group_form[field.param] = field.value ? field.value[field.multiselectOptions.trackBy] : null;
						}
					} else {
						group_form[field.param] = field.value;
					}

					return false;
				}
			});
			if (groupValidationFailed) return;

			group_form['id'] = this.group && this.group.id ? this.group.id : null;

			this.loader = true;
			groupsService.saveGroup(group_form).then((response) => {
				if (response.status === true) {
					if (group_form['id'] === null) {
						this.group = response.data;

						const urlParams = new URLSearchParams(window.location.search);
						urlParams.set('id', this.group.id);
						window.history.replaceState({}, '', `${window.location.pathname}?${urlParams}`);
					}

					this.loader = false;

					Swal.fire({
						position: 'center',
						icon: 'success',
						title: response.msg,
						showConfirmButton: true,
						allowOutsideClick: false,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							actions: 'em-swal-single-action',
						},
						timer: 1500,
					}).then(() => {
						this.tabs[1].disabled = false;
						this.tabs[2].disabled = false;
						this.tabs[3].disabled = false;

						this.handleChangeTab(2);
					});
				} else {
					// Handle error
					Swal.fire({
						icon: 'error',
						title: 'Oops...',
						text: response.message,
					});
				}
			});
		},
		associatePrograms() {
			groupsService
				.associatePrograms(
					this.group.id,
					this.programs.map((program) => program.code),
				)
				.then((response) => {
					if (response.status === true) {
						Swal.fire({
							position: 'center',
							icon: 'success',
							title: response.msg,
							showConfirmButton: true,
							allowOutsideClick: false,
							reverseButtons: true,
							customClass: {
								title: 'em-swal-title',
								confirmButton: 'em-swal-confirm-button',
								actions: 'em-swal-single-action',
							},
							timer: 1500,
						}).then(() => {
							this.handleChangeTab(3);
						});
					} else {
						// Handle error
						Swal.fire({
							icon: 'error',
							title: 'Oops...',
							text: response.message,
						});
					}
				});
		},
	},
	computed: {
		displayedProgramsOptions() {
			return this.programsOptions.filter((program) => {
				return program.label.toLowerCase().includes(this.searchThroughPrograms.toLowerCase());
			});
		},
	},
};
</script>

<template>
	<div class="groups__add_group">
		<div>
			<div
				class="tw-group tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular"
				@click="goBack"
			>
				<span class="material-symbols-outlined tw-mr-1 tw-text-link-regular">navigate_before</span>
				<span class="group-hover:tw-underline">{{ translate('BACK') }}</span>
			</div>

			<h1 class="tw-mt-4">
				{{
					this.group && Object.keys(this.group).length > 0
						? translate('COM_EMUNDUS_GROUPS_EDIT_TITLE') + ' ' + this.group['label']
						: translate('COM_EMUNDUS_GROUPS_ADD_TITLE')
				}}
			</h1>

			<hr class="tw-mb-8 tw-mt-1.5" />

			<template v-if="!loading">
				<Tabs
					ref="tabsComponent"
					:classes="'tw-flex tw-items-center tw-gap-2 tw-ml-7'"
					:tabs="tabs"
					:context="group_id ? 'event_form_' + group_id : ''"
					@click-disabled-tab="displayDisabledMessage"
				/>

				<div
					class="tw-relative tw-w-full tw-rounded-2xl tw-border tw-border-neutral-300 tw-bg-white tw-p-6"
					v-show="!loader"
				>
					<div v-show="tabs[0].active">
						<ParameterForm
							:groups="[generalGroup]"
							:title="translate('COM_EMUNDUS_GROUPS_ADD_GROUP_GENERAL')"
							:description="translate('COM_EMUNDUS_GROUPS_ADD_GROUP_GENERAL_INTRO')"
						/>
						<div class="tw-mt-7 tw-flex tw-justify-end">
							<Button @click="saveGroup">
								{{
									this.group && Object.keys(this.group).length > 0
										? translate('COM_EMUNDUS_GROUPS_EDIT_SAVE')
										: translate('COM_EMUNDUS_GROUPS_ADD_SAVE')
								}}
							</Button>
						</div>
					</div>

					<template v-if="tabs[1].active">
						<div>
							<div class="tw-mb-3" v-html="translate('COM_EMUNDUS_GROUPS_ADD_GROUP_PROGRAMMES_INTRO')"></div>
							<!-- set a checkbox input for each programsOptions -->
							<input
								type="text"
								v-model="searchThroughPrograms"
								:placeholder="translate('COM_EMUNDUS_WORKFLOW_SEARCH_PROGRAMS_PLACEHOLDER')"
								class="tw-mb-4 tw-w-full tw-rounded tw-border tw-border-neutral-300 tw-p-2"
							/>

							<div class="tw-mt-4 tw-flex tw-cursor-pointer tw-flex-row tw-items-center">
								<input
									id="check-all"
									class="tw-cursor-pointer"
									type="checkbox"
									v-model="checkall"
									@change="onClickCheckAllProgram"
								/>
								<label for="check-all" class="!tw-mb-0 tw-cursor-pointer tw-font-medium">{{
									translate('COM_EMUNDUS_WORKFLOW_CHECK_ALL')
								}}</label>
							</div>

							<div class="tw-mt-4 tw-grid tw-grid-cols-4 tw-gap-3 tw-overflow-auto">
								<div v-for="program in displayedProgramsOptions" :key="program.code">
									<div class="tw-mb-4 tw-flex tw-cursor-pointer tw-items-center">
										<input
											:id="'program-' + program.code"
											type="checkbox"
											v-model="programs"
											:value="program"
											class="tw-cursor-pointer"
										/>
										<label :for="'program-' + program.code" class="tw-m-0 tw-cursor-pointer">
											{{ program.label }}
										</label>
									</div>
								</div>
								<p v-if="programsOptions.length < 1" class="tw-w-full tw-text-center">
									{{ translate('COM_EMUNDUS_WORKFLOW_NO_PROGRAMS') }}
								</p>
							</div>
						</div>

						<div class="tw-mt-7 tw-flex tw-justify-end">
							<Button @click="associatePrograms">
								{{ translate('COM_EMUNDUS_GROUPS_EDIT_SAVE') }}
							</Button>
						</div>
					</template>

					<template v-if="tabs[2].active && this.group">
						<AccessRightsTable :group="this.group" :can-update="true" />
					</template>

					<template v-if="tabs[3].active && this.group">
						<UsersList :group="this.group" />
					</template>
				</div>

				<Loader v-if="loader" />
			</template>
		</div>
	</div>
</template>

<style scoped></style>
