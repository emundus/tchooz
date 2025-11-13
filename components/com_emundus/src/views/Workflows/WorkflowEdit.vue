<template>
	<div class="tw-m-2">
		<div v-if="!loading">
			<div
				class="tw-group tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular"
				@click="goBack"
			>
				<span class="material-symbols-outlined tw-mr-1 tw-text-link-regular">navigate_before</span>
				<span class="group-hover:tw-underline">{{ translate('BACK') }}</span>
			</div>

			<div id="header" class="tw-mt-4">
				<div class="tw-flex tw-flex-row tw-justify-between">
					<input id="workflow-label" name="workflow-label" class="!tw-w-[350px]" type="text" v-model="workflow.label" />
					<button class="tw-btn-primary tw-flex tw-items-center tw-gap-1" @click="save">
						<span class="material-symbols-outlined">check</span>
						<span>{{ translate('SAVE') }}</span>
					</button>
				</div>

				<div class="tw-mt-4 tw-flex tw-w-full tw-flex-row tw-items-center tw-justify-between">
					<div>
						<select v-if="sortByOptions.length > 0">
							<option value="0">{{ translate('SORT_BY') }}</option>
						</select>
					</div>
				</div>
			</div>

			<Tabs :tabs="tabs" :classes="'tw-flex tw-items-center tw-gap-2 tw-ml-7'"></Tabs>

			<div
				id="tabs-wrapper"
				class="tw-relative tw-w-full tw-rounded-coordinator tw-border tw-border-neutral-300 tw-bg-white tw-p-6"
			>
				<div v-if="activeTab.id === 'steps'" id="workflow-steps-wrapper" class="tw-flex tw-flex-col">
					<a class="tw-btn-primary tw-mb-4 tw-h-fit tw-w-fit tw-cursor-pointer" type="button" @click="addStep">
						{{ translate('COM_EMUNDUS_WORKFLOW_ADD_STEP') }}
					</a>

					<!-- checkbox to show archived steps -->
					<div class="tw-mb-4 tw-flex tw-cursor-pointer tw-items-center tw-gap-2">
						<input id="show-archived-steps" type="checkbox" class="tw-cursor-pointer" v-model="showArchivedSteps" />
						<label for="show-archived-steps" class="!tw-mb-0 tw-cursor-pointer">{{
							translate('COM_EMUNDUS_WORKFLOW_SHOW_ARCHIVED_STEPS')
						}}</label>
					</div>

					<div id="workflow-steps" v-if="this.displayedSteps.length > 0">
						<draggable
							v-model="displayedSteps"
							:sort="true"
							class="draggables-list tw-flex tw-flex-row tw-gap-3 tw-overflow-auto"
							handle=".handle"
						>
							<div v-for="step in displayedSteps" :key="step.id" class="workflow-step tw-max-w-sm">
								<div
									class="tw-flex tw-w-fit tw-flex-row tw-items-center tw-rounded-t-lg tw-border-x tw-border-t tw-px-3 tw-pb-1 tw-pt-2"
									:class="stepHeaderClass(step)"
								>
									<span v-if="isPaymentStep(step)" class="material-symbols-outlined tw-mr-1 tw-text-white">paid</span>
									<span v-else-if="isChoicesStep(step)" class="material-symbols-outlined tw-mr-1 tw-text-white"
										>checklist_rtl</span
									>
									<span v-else class="material-symbols-outlined tw-mr-1 tw-text-white">group</span>
									<span class="tw-text-sm tw-text-white"> {{ stepTypeLabel(step.type) }} </span>
									<span v-if="step.state != 1" class="tw-ml-1 tw-text-sm tw-text-white">
										({{ translate('COM_EMUNDUS_WORKFLOW_ARCHIVED_STEP') }})</span
									>
								</div>

								<div class="tw-rounded-b-lg tw-rounded-r-lg tw-border tw-p-4 tw-shadow-sm" :class="stepClass(step)">
									<div class="workflow-step-head tw-flex tw-w-full tw-flex-row tw-gap-2">
										<div class="tw-mb-4 tw-flex tw-w-full tw-flex-row tw-items-center tw-justify-between">
											<span
												class="material-symbols-outlined handle tw-cursor-grab"
												:class="'tw-text-' + stepColor(step) + '-500'"
											>
												drag_indicator
											</span>
											<h4 class="tw-line-clamp-2 tw-break-all">
												<input type="text" v-model="step.label" />
											</h4>
											<popover
												:iconClass="
													'tw-btn-primary tw-p-2 tw-border-' +
													stepColor(step) +
													'-500 tw-bg-' +
													stepColor(step) +
													'-500'
												"
											>
												<ul class="tw-list-none !tw-p-3">
													<li
														class="archive-workflow-step tw-cursor-pointer tw-rounded-lg tw-px-3 tw-pb-2 tw-pt-2 hover:tw-bg-neutral-300"
														@click="duplicateStep(step.id)"
													>
														{{ translate('COM_EMUNDUS_ACTIONS_DUPLICATE') }}
													</li>
													<li
														v-if="step.state == 1"
														class="archive-workflow-step tw-cursor-pointer tw-rounded-lg tw-px-3 tw-pb-2 tw-pt-2 hover:tw-bg-neutral-300"
														@click="updateStepState(step.id, 0)"
													>
														{{ translate('COM_EMUNDUS_ACTIONS_ARCHIVE') }}
													</li>
													<li
														v-else
														class="archive-workflow-step tw-cursor-pointer tw-rounded-lg tw-px-3 tw-pb-2 tw-pt-2 hover:tw-bg-neutral-300"
														@click="updateStepState(step.id, 1)"
													>
														{{ translate('COM_EMUNDUS_ACTIONS_UNARCHIVE') }}
													</li>
													<li
														class="delete-workflow-step tw-cursor-pointer tw-rounded-lg tw-px-3 tw-pb-2 tw-pt-2 hover:tw-bg-neutral-300"
														@click="beforeDeleteStep(step.id)"
													>
														{{ translate('COM_EMUNDUS_ACTIONS_DELETE') }}
													</li>
												</ul>
											</popover>
										</div>
									</div>

									<div v-if="relatedStep(step) !== null" class="tw-mb-4">
										<div
											class="tw-whitespace-nowrap tw-rounded-full tw-border tw-px-3 tw-pb-2 tw-pt-2"
											:class="'tw-border-' + stepColor(step) + '-500'"
										>
											<div class="tw-w-full tw-overflow-hidden tw-text-ellipsis tw-whitespace-nowrap">
												<p
													class="tw-w-full tw-overflow-hidden tw-text-ellipsis tw-whitespace-nowrap"
													:class="'tw-text-' + stepColor(step) + '-500'"
													:title="getStepLabelById(relatedStep(step).id)"
												>
													{{ translate('COM_EMUNDUS_WORKFLOW_PAYMENT_STEP_RELATED_TO') }}
													<strong>{{ getStepLabelById(relatedStep(step).id) }}</strong>
												</p>
											</div>
										</div>
									</div>

									<div class="workflow-step-content">
										<div class="tw-mb-4 tw-flex tw-flex-col">
											<label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_TYPE') }}</label>
											<select v-model="step.type" @change="onChangeStepType(step)">
												<option v-for="type in stepTypes" :key="type.id" :value="type.id">
													<span v-if="type.parent_id > 0"> - </span>
													{{ translate(type.label) }}
												</option>
											</select>
										</div>

										<div v-if="isChoicesStep(step)" class="tw-mb-4 tw-flex tw-flex-col">
											<label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_MAX_CHOICES') }}</label>
											<input v-model="step.max" type="number" min="1" />

											<span
												class="tw-text-red-600"
												v-if="displayErrors && fieldsInError[step.id] && fieldsInError[step.id].includes('max_choices')"
											>
												{{ translate('COM_EMUNDUS_WORKFLOW_STEP_MAX_CHOICES_REQUIRED') }}
											</span>
										</div>

										<div v-if="isChoicesStep(step)" class="tw-mb-4 tw-flex tw-flex-col">
											<label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_CAN_BE_ORDERING') }}</label>
											<select v-model="step.can_be_ordering">
												<option value="0">{{ translate('JNO') }}</option>
												<option value="1">{{ translate('JYES') }}</option>
											</select>
										</div>

										<div v-if="isChoicesStep(step)" class="tw-mb-4 tw-flex tw-flex-col">
											<label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_CAN_BE_CONFIRMED') }}</label>
											<select v-model="step.can_be_confirmed">
												<option value="0">{{ translate('JNO') }}</option>
												<option value="1">{{ translate('JYES') }}</option>
											</select>
										</div>

										<div v-if="isApplicantStep(step)" class="tw-mb-4 tw-flex tw-flex-col">
											<label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_PROFILE') }}</label>
											<select v-model="step.profile_id">
												<option v-for="profile in applicantProfiles" :key="profile.id" :value="profile.id">
													{{ profile.label }}
												</option>
											</select>

											<span
												class="tw-text-red-600"
												v-if="displayErrors && fieldsInError[step.id] && fieldsInError[step.id].includes('form_id')"
											>
												{{ translate('COM_EMUNDUS_WORKFLOW_STEP_FORM_REQUIRED') }}
											</span>
										</div>

										<div v-else-if="isEvaluationStep(step)" class="tw-mb-4 tw-flex tw-flex-col">
											<label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_PROFILE') }}</label>
											<select v-model="step.form_id">
												<option v-for="form in evaluationForms" :key="form.id" :value="form.id">
													{{ form.label }}
												</option>
											</select>

											<span
												class="tw-text-red-600"
												v-if="displayErrors && fieldsInError[step.id] && fieldsInError[step.id].includes('form_id')"
											>
												{{ translate('COM_EMUNDUS_WORKFLOW_STEP_FORM_REQUIRED') }}
											</span>
										</div>

										<div class="tw-mb-4 tw-flex tw-flex-col">
											<label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS') }}</label>
											<Multiselect
												:options="statuses"
												v-model="step.entry_status"
												label="label"
												track-by="id"
												:placeholder="translate('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS_SELECT')"
												:selectLabel="translate('PRESS_ENTER_TO_SELECT')"
												:multiple="true"
											>
											</Multiselect>

											<span
												class="tw-text-red-600"
												v-if="
													displayErrors && fieldsInError[step.id] && fieldsInError[step.id].includes('entry_status')
												"
											>
												{{ translate('COM_EMUNDUS_WORKFLOW_STEP_ENTRY_STATUS_REQUIRED') }}
											</span>
										</div>

										<div
											v-if="isApplicantStep(step) || isPaymentStep(step) || isChoicesStep(step)"
											class="tw-mb-4 tw-flex tw-flex-col"
										>
											<label class="tw-mb-2">{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS') }}</label>
											<select v-model="step.output_status">
												<option value="-1">
													{{ translate('COM_EMUNDUS_WORKFLOW_STEP_OUTPUT_STATUS_SELECT') }}
												</option>
												<option v-for="status in statuses" :key="status.id" :value="status.id">
													{{ status.label }}
												</option>
											</select>
										</div>

										<div
											v-if="isEvaluationStep(step)"
											class="tw-mb-4 tw-flex tw-cursor-pointer tw-flex-row tw-items-center"
										>
											<input
												v-model="step.multiple"
												true-value="1"
												false-value="0"
												type="checkbox"
												:name="'step-' + step.id + '-multiple'"
												:id="'step-' + step.id + '-multiple'"
												class="tw-cursor-pointer"
											/>
											<label :for="'step-' + step.id + '-multiple'" class="tw-mb-0 tw-cursor-pointer">{{
												translate('COM_EMUNDUS_WORKFLOW_STEP_IS_MULTIPLE')
											}}</label>
										</div>

										<div
											v-if="isEvaluationStep(step)"
											class="tw-mb-4 tw-flex tw-cursor-pointer tw-flex-row tw-items-center"
										>
											<input
												v-model="step.lock"
												true-value="1"
												false-value="0"
												type="checkbox"
												:name="'step-' + step.id + '-lock'"
												:id="'step-' + step.id + '-lock'"
												class="tw-cursor-pointer"
											/>
											<label :for="'step-' + step.id + '-lock'" class="tw-mb-0 tw-cursor-pointer">{{
												translate('COM_EMUNDUS_WORKFLOW_STEP_IS_LOCKED')
											}}</label>
										</div>

										<div v-if="isEvaluationStep(step)" class="step-associated-groups">
											<label>{{ translate('COM_EMUNDUS_WORKFLOW_STEP_GROUPS') }}</label>
											<ul class="tw-my-2 tw-overflow-auto" style="max-height: 100px">
												<li v-for="group_id in getGroupsFromStepType(step.type)" :key="group_id">
													{{ getGroupLabel(group_id) }}
												</li>
											</ul>

											<a href="/users-menu/groups" class="tw-underline">{{
												translate('COM_EMUNDUS_WORKFLOW_EDIT_RIGHTS')
											}}</a>
										</div>

										<div v-if="isPaymentStep(step) && step.id > 0">
											<a
												:href="
													'/index.php?option=com_emundus&view=workflows&layout=editpaymentstep&wid=' +
													workflowId +
													'&step_id=' +
													step.id
												"
												class="tw-btn-primary"
												:class="
													'tw-border-' +
													stepColor(step) +
													'-500 tw-bg-' +
													stepColor(step) +
													'-500 hover-style-' +
													stepColor(step)
												"
											>
												{{ translate('COM_EMUNDUS_WORKFLOW_CONFIGURE_PAYMENT_STEP') }}
											</a>
										</div>
									</div>
								</div>
							</div>
						</draggable>
						<p v-if="steps.length < 1" class="tw-w-full tw-text-center">
							{{ translate('COM_EMUNDUS_WORKFLOW_NO_STEPS') }}
						</p>
					</div>
				</div>
				<div v-else-if="activeTab.id === 'programs'">
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
						<div v-for="program in displayedProgramsOptions" :key="program.id">
							<div class="tw-mb-4 tw-flex tw-cursor-pointer tw-flex-row tw-items-baseline">
								<input
									:id="'program-' + program.id"
									type="checkbox"
									v-model="programs"
									:value="program"
									class="tw-cursor-pointer"
									@change="onCheckProgram(program)"
								/>
								<label
									:for="'program-' + program.id"
									class="tw-m-0 tw-cursor-pointer"
									:class="{
										'tw-text-gray-300': isProgramAssociatedToAnotherWorkflow(program),
									}"
								>
									{{ program.label }}
								</label>
							</div>
						</div>
						<p v-if="programsOptions.length < 1" class="tw-w-full tw-text-center">
							{{ translate('COM_EMUNDUS_WORKFLOW_NO_PROGRAMS') }}
						</p>
					</div>
				</div>
			</div>
		</div>

		<div v-else class="em-page-loader" />
	</div>
</template>

<script>
import { VueDraggableNext } from 'vue-draggable-next';
import workflowService from '@/services/workflow.js';
import settingsService from '@/services/settings.js';
import programmeService from '@/services/programme.js';
import fileService from '@/services/file.js';
import formService from '@/services/form.js';
import groupsService from '@/services/groups.js';

import Popover from '@/components/Popover.vue';
import Tabs from '@/components/Utils/Tabs.vue';
import Multiselect from 'vue-multiselect';
import errors from '@/mixins/errors.js';

import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'WorkflowEdit',
	props: {
		workflowId: {
			type: Number,
			required: true,
		},
	},
	components: {
		Multiselect,
		Popover,
		Tabs,
		draggable: VueDraggableNext,
	},
	mixins: [errors],
	data() {
		return {
			workflow: {
				id: 0,
				label: '',
			},
			steps: [],
			programs: [],
			stepTypes: [],
			sortByOptions: [],
			statuses: [],
			profiles: [],
			groups: [],
			evaluationForms: [],
			programsOptions: [],
			stepMandatoryFields: ['label', 'type', 'entry_status'],
			mandatoryFieldsByTypes: {
				evaluation: ['form_id'],
				applicant: ['profile_id'],
				payment: ['output_status'],
				choices: ['max'],
			},
			fieldsInError: {},
			displayErrors: false,

			searchThroughPrograms: '',
			checkall: 0,

			tabs: [
				{
					id: 'steps',
					name: 'COM_EMUNDUS_WORKFLOW_STEPS',
					description: 'COM_EMUNDUS_WORKFLOW_STEPS_DESC',
					icon: 'schema',
					active: true,
					displayed: true,
				},
				{
					id: 'programs',
					name: 'COM_EMUNDUS_WORKFLOW_PROGRAMS',
					description: 'COM_EMUNDUS_WORKFLOW_PROGRAMS_DESC',
					icon: 'join',
					active: false,
					displayed: true,
				},
			],

			colorsByStepId: {},

			loading: false,
			showArchivedSteps: false,
		};
	},
	mounted() {
		this.loading = true;
		this.getStepTypes();
		this.getStatuses().then(() => {
			this.getPrograms().then(() => {
				this.getProfiles().then(() => {
					this.getWorkflow();
					this.loading = false;
				});
			});
		});
		this.getEvaluationForms();
		this.getGroups();
	},
	methods: {
		getWorkflow() {
			workflowService
				.getWorkflow(this.workflowId)
				.then((response) => {
					this.workflow = response.data.workflow;
					let tmpSteps = response.data.steps;
					tmpSteps.forEach((step) => {
						step.entry_status = this.statuses.filter((status) => step.entry_status.includes(status.id.toString()));
					});
					this.steps = tmpSteps;

					let program_ids = response.data.programs;
					this.programs = this.programsOptions.filter((program) => program_ids.includes(program.id));
				})
				.catch((e) => {
					console.log(e);
				});
		},
		async getStepTypes() {
			return await workflowService
				.getStepTypes()
				.then((response) => {
					this.stepTypes = response.data.map((type) => {
						type.group_ids = type.group_ids.map((groupId) => parseInt(groupId));

						return type;
					});
				})
				.catch((e) => {
					console.log(e);
				});
		},
		async getStatuses() {
			return await settingsService
				.getStatus()
				.then((response) => {
					return (this.statuses = response.data.map((status) => {
						return {
							id: status.step,
							label: status.label[useGlobalStore().shortLang],
						};
					}));
				})
				.catch((e) => {
					console.log(e);
				});
		},
		async getPrograms() {
			return await programmeService
				.getAllPrograms('', '', 0, 0, 'p.label', 'ASC')
				.then((response) => {
					this.programsOptions = response.data.datas.map((program) => {
						return {
							id: program.id,
							label: program.label[useGlobalStore().shortLang],
							workflows: [],
						};
					});

					this.getProgramWorkflows();
				})
				.catch((e) => {
					console.log(e);
				});
		},
		async getProgramWorkflows() {
			return await workflowService
				.getProgramsWorkflows()
				.then((response) => {
					this.programsOptions.forEach((program) => {
						if (response.data[program.id]) {
							program.workflows = response.data[program.id].map((workflow) => parseInt(workflow));
						}
					});
				})
				.catch((e) => {
					console.log(e);
				});
		},
		async getProfiles() {
			return await fileService
				.getProfiles()
				.then((response) => {
					const filteredProfiles = response.data.filter((profile) => {
						return profile.label !== 'noprofile';
					});

					this.profiles = filteredProfiles.map((profile) => {
						return {
							id: profile.id,
							label: profile.label,
							applicantProfile: profile.published,
						};
					});
				})
				.catch((e) => {
					console.log(e);
				});
		},
		getEvaluationForms() {
			formService.getEvaluationForms().then((response) => {
				if (response.status) {
					this.evaluationForms = response.data.datas.map((form) => {
						return {
							id: form.id,
							label: form.label[useGlobalStore().shortLang],
						};
					});
				}
			});
		},
		getStepSubTypes(stepType) {
			return this.stepTypes.filter((type) => type.parent_id == stepType);
		},
		async getGroups() {
			return await groupsService
				.getGroups()
				.then((response) => {
					this.groups = response.data.map((group) => {
						return {
							id: group.id,
							label: group.label,
						};
					});
				})
				.catch((e) => {
					console.log(e);
				});
		},
		getGroupsFromStepType(type) {
			const stepType = this.stepTypes.find((stepType) => stepType.id === type);

			return stepType ? stepType.group_ids : [];
		},
		getGroupLabel(groupId) {
			const group = this.groups.find((group) => group.id == groupId);

			return group ? group.label : '';
		},
		addStep() {
			const newStep = {
				id: 0,
				label: this.translate('COM_EMUNDUS_WORKFLOW_NEW_STEP_LABEL'),
				type: 1,
				roles: [],
				profile_id: 9,
				entry_status: [],
				output_status: 0,
				state: 1,
			};

			// set a new id inferior to 0 to be able to delete it without calling the API
			newStep.id =
				this.steps.reduce((acc, step) => {
					if (step.id < acc) {
						acc = step.id;
					}
					return acc;
				}, 0) - 1;

			this.steps.push(newStep);
			this.scrollToLastStep();
		},

		duplicateStep(stepId) {
			const step = this.steps.find((step) => step.id == stepId);

			if (step) {
				const newStep = { ...step };

				newStep.id =
					this.steps.reduce((acc, step) => {
						if (step.id < acc) {
							acc = step.id;
						}
						return acc;
					}, 0) - 1;

				this.steps.push(newStep);
				this.scrollToLastStep();
			}
		},
		scrollToLastStep() {
			this.$nextTick(() => {
				const stepElement = document.querySelector(`.workflow-step:nth-child(${this.steps.length})`);
				if (stepElement) {
					stepElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
				}
			});
		},
		async updateStepState(stepId, state = 0) {
			let archived = false;

			if (stepId > 0) {
				const response = await workflowService.updateStepState(stepId, state);

				if (response.status) {
					this.steps = this.steps.map((step) => {
						if (step.id == stepId) {
							step.state = state;
						}
						return step;
					});
					archived = true;

					return archived;
				} else {
					this.displayError('COM_EMUNDUS_WORKFLOW_ARCHIVE_FAILED', response.message);
				}
			} else {
				return archived;
			}
		},
		beforeDeleteStep(stepId) {
			Swal.fire({
				title: this.translate('COM_EMUNDUS_WORKFLOW_DELETE_STEP_CONFIRMATION'),
				icon: 'warning',
				showCancelButton: true,
				confirmButtonText: this.translate('COM_EMUNDUS_ACTIONS_DELETE'),
				cancelButtonText: this.translate('CANCEL'),
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					confirmButton: 'em-swal-confirm-button',
					cancelButton: 'em-swal-cancel-button',
					actions: 'em-swal-double-action',
				},
			}).then((result) => {
				if (result.isConfirmed) {
					this.deleteStep(stepId);
				}
			});
		},
		async deleteStep(stepId) {
			let deleted = false;

			if (stepId < 1) {
				this.steps = this.steps.filter((step) => {
					return step.id != stepId;
				});
				deleted = true;
			} else {
				try {
					const response = await workflowService.deleteWorkflowStep(stepId);

					if (response.status) {
						this.steps = this.steps.filter((step) => {
							return step.id != stepId;
						});
						deleted = true;
					}
				} catch (e) {
					const error = JSON.parse(e.message);
					await this.displayError('COM_EMUNDUS_WORKFLOW_DELETE_STEP_FAILED', error.message);
				}
			}

			if (deleted) {
				delete this.colorsByStepId[stepId];
			}

			return deleted;
		},

		isFieldEmpty(step, field) {
			let emptyField = true;

			if (!step[field]) {
				return true;
			}

			switch (typeof step[field]) {
				case 'string':
					emptyField = step[field].trim() === '';
					break;
				case 'object':
					emptyField = step[field].length < 1;
					break;
				default:
					emptyField = step[field] === '' || step[field] === null || step[field] === undefined || step[field] < 0;
			}

			return emptyField;
		},
		onBeforeSave() {
			let check = false;

			let stepsCheck = [];

			this.fieldsInError = {};
			this.steps.forEach((step) => {
				this.fieldsInError[step.id] = [];

				stepsCheck.push(
					this.stepMandatoryFields.every((field) => {
						let emptyField = this.isFieldEmpty(step, field);

						if (emptyField) {
							this.fieldsInError[step.id].push(field);
						} else {
							if (this.isApplicantStep(step)) {
								this.mandatoryFieldsByTypes.applicant.forEach((field) => {
									emptyField = this.isFieldEmpty(step, field);
									if (emptyField) {
										this.fieldsInError[step.id].push(field);
									}
								});
							} else if (this.isEvaluationStep(step)) {
								this.mandatoryFieldsByTypes.evaluation.forEach((field) => {
									emptyField = this.isFieldEmpty(step, field);

									if (emptyField) {
										this.fieldsInError[step.id].push(field);
									}
								});
							} else if (this.isPaymentStep(step)) {
								this.mandatoryFieldsByTypes.payment.forEach((field) => {
									emptyField = this.isFieldEmpty(step, field);

									if (emptyField) {
										this.fieldsInError[step.id].push(field);
									}
								});
							} else if (this.isChoicesStep(step)) {
								this.mandatoryFieldsByTypes.choices.forEach((field) => {
									emptyField = this.isFieldEmpty(step, field);

									if (emptyField) {
										this.fieldsInError[step.id].push(field);
									}
								});
							}
						}

						return !emptyField;
					}),
				);

				if (this.fieldsInError[step.id].length > 0) {
					if (step.state !== 1) {
						this.showArchivedSteps = true;
					}
				}
			});

			check = stepsCheck.every((stepCheck) => {
				return stepCheck;
			});

			return check;
		},
		onCheckProgram(program) {
			if (this.isProgramAssociatedToAnotherWorkflow(program)) {
				Swal.fire({
					icon: 'warning',
					title: this.translate('COM_EMUNDUS_WORKFLOW_PROGRAM_ASSOCIATED_TO_ANOTHER_WORKFLOW'),
					html: this.translate('COM_EMUNDUS_WORKFLOW_PROGRAM_ASSOCIATED_TO_ANOTHER_WORKFLOW_TEXT').replace(
						'%s',
						program.label,
					),
					showConfirmButton: true,
					confirmButtonText: this.translate('COM_EMUNDUS_WORKFLOW_CONFIRM_CHANGE_PROGRAM_ASSOCIATION'),
					showCancelButton: true,
					cancelButtonText: this.translate('CANCEL'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						confirmButton: 'em-swal-confirm-button',
						cancelButton: 'em-swal-cancel-button',
						actions: 'em-swal-double-action',
					},
				}).then((result) => {
					if (result.value) {
						program.workflows = [this.workflow.id];
					} else {
						this.programs = this.programs.filter((p) => p.id !== program.id);
					}
				});
			}
		},
		onClickCheckAllProgram() {
			if (this.checkall) {
				this.displayedProgramsOptions.forEach((program) => {
					if (!this.isProgramAssociatedToAnotherWorkflow(program) && !this.programs.includes(program)) {
						this.programs.push(program);
					}
				});
			} else {
				this.displayedProgramsOptions.forEach((program) => {
					this.programs = this.programs.filter((p) => p.id !== program.id);
				});
			}
		},
		save() {
			const checked = this.onBeforeSave();

			if (checked) {
				workflowService
					.saveWorkflow(this.workflow, this.steps, this.programs)
					.then((response) => {
						if (response.status) {
							Swal.fire({
								icon: 'success',
								title: this.translate('COM_EMUNDUS_WORKFLOW_SAVE_SUCCESS'),
								showConfirmButton: false,
								timer: 1500,
							});

							this.getWorkflow();
						} else {
							this.displayError('COM_EMUNDUS_WORKFLOW_SAVE_FAILED', response.msg);
						}
					})
					.catch((e) => {
						console.log(e);
						this.displayError('COM_EMUNDUS_WORKFLOW_SAVE_FAILED', '');
					});
			} else {
				this.displayErrors = true;

				setTimeout(() => {
					this.displayErrors = false;
				}, 15000);
			}
		},
		goBack() {
			window.history.back();
		},
		isApplicantStep(step) {
			let isApplicantStep = step.type == 1;

			if (!isApplicantStep) {
				const stepType = this.stepTypes.find((stepType) => stepType.id === step.type);

				if (stepType && stepType.parent_id == 1) {
					isApplicantStep = true;
				}
			}

			return isApplicantStep;
		},
		isEvaluationStep(step) {
			let isEvaluationStep = step.type == 2;

			if (!isEvaluationStep) {
				const stepType = this.stepTypes.find((stepType) => stepType.id === step.type);

				if (stepType && stepType.parent_id == 2) {
					isEvaluationStep = true;
				}
			}

			return isEvaluationStep;
		},
		isChoicesStep(step) {
			let stepType = this.stepTypes.find((stepType) => stepType.id === step.type);
			if (!stepType) {
				return false;
			}
			return stepType.code === 'choices';
		},
		isPaymentStep(step) {
			let stepType = this.stepTypes.find((stepType) => stepType.id === step.type);
			if (!stepType) {
				return false;
			}
			return stepType.code === 'payment';
		},
		isProgramAssociatedToAnotherWorkflow(program) {
			return program.workflows && program.workflows.length > 0 && !program.workflows.includes(this.workflow.id);
		},
		getStepLabelById(stepId) {
			let label = '';

			const foundStep = this.steps.find((step) => step.id == stepId);
			if (foundStep) {
				label = foundStep.label;
			}

			return label;
		},
		relatedStep(currentStep) {
			let step = null;

			if (currentStep.adjust_balance_step_id && currentStep.adjust_balance_step_id > 0) {
				step = this.steps.find((s) => s.id === currentStep.adjust_balance_step_id);
			} else if (this.steps.some((s) => s.adjust_balance_step_id === currentStep.id)) {
				step = this.steps.find((s) => s.adjust_balance_step_id === currentStep.id);
			}

			return step;
		},
		stepTypeLabel(type) {
			let label = '';
			const stepType = this.stepTypes.find((stepType) => stepType.id === type);

			if (stepType) {
				label = stepType.label;
			} else {
				label = 'COM_EMUNDUS_WORKFLOW_STEP_TYPE_UNKNOWN';
			}

			return this.translate(label);
		},
		stepHeaderClass(step) {
			return 'tw-bg-' + this.stepColor(step) + '-500 tw-border-' + this.stepColor(step) + '-500 tw-border-2';
		},
		stepColor(step) {
			let stepColor = 'blue';

			if (this.colorsByStepId[step.id]) {
				stepColor = this.colorsByStepId[step.id];
			} else {
				const stepType = this.stepTypes.find((stepType) => stepType.id === step.type);

				if (stepType && stepType.class && stepType.class !== '') {
					stepColor = stepType.class;
				}

				this.colorsByStepId[step.id] = stepColor;
			}

			return stepColor;
		},
		stepClass(step) {
			let stepClass = '';

			if (step.state == 1) {
				stepClass += ' tw-bg-' + this.stepColor(step) + '-50 tw-border-' + this.stepColor(step) + '-500 tw-border-2';
			} else {
				stepClass += ' tw-bg-slate-50';
			}

			if (this.displayErrors && this.fieldsInError[step.id] && this.fieldsInError[step.id].length > 0) {
				stepClass += ' tw-border-red-600';
			}

			return stepClass;
		},
		onChangeStepType(step) {
			delete this.colorsByStepId[step.id];
		},
	},
	computed: {
		nonApplicantProfiles() {
			return this.profiles.filter((profile) => !profile.applicantProfile);
		},
		applicantProfiles() {
			return this.profiles.filter((profile) => profile.applicantProfile);
		},
		parentStepTypes() {
			return this.stepTypes.filter((type) => type.parent_id === 0);
		},
		activeTab() {
			return this.tabs.find((tab) => tab.active);
		},
		displayedProgramsOptions() {
			return this.programsOptions.filter((program) => {
				return program.label.toLowerCase().includes(this.searchThroughPrograms.toLowerCase());
			});
		},
		displayedSteps: {
			get: function () {
				if (this.showArchivedSteps) {
					return this.steps;
				}

				return this.steps.filter((step) => {
					let stepType = this.stepTypes.find((stepType) => stepType.id === step.type);
					if (!stepType) {
						return false;
					}

					return step.state === 1;
				});
			},
			set: function (value) {
				// Update the original steps array based on the displayedSteps changes but keep archived steps
				let newSteps = value;
				if (!this.showArchivedSteps) {
					const archivedSteps = this.steps.filter((step) => step.state !== 1);
					newSteps = [...value, ...archivedSteps];
				}

				this.steps = newSteps.map((step, index) => {
					step.ordering = index + 1;
					return step;
				});
			},
		},
	},
};
</script>

<style scoped>
.workflow-step {
	min-width: 350px;
}

.hover-style-main:hover {
	color: var(--main-500) !important;
	border: 1px solid var(--main-500);
}

.hover-style-neutral:hover {
	color: var(--neutral-500) !important;
	border: 1px solid var(--neutral-500);
}

.hover-style-blue:hover {
	color: var(--blue-500) !important;
	border: 1px solid var(--blue-500);
}

.hover-style-orange:hover {
	color: var(--orange-500) !important;
	border: 1px solid var(--orange-500);
}

.hover-style-red:hover {
	color: var(--red-500) !important;
	border: 1px solid var(--red-500);
}

.hover-style-yellow:hover {
	color: rgb(234 179 8 / var(--tw-bg-opacity, 1)) !important;
	border: 1px solid rgb(234 179 8 / var(--tw-bg-opacity, 1));
}
</style>
