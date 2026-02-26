<template>
	<div
		id="program-edition-container"
		class="em-card-shadow tw-m-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6"
	>
		<button
			type="button"
			class="tw-group tw-flex tw-cursor-pointer tw-items-center tw-border-0 tw-font-semibold tw-text-link-regular"
			@click="redirectJRoute('index.php?option=com_emundus&view=campaigns')"
		>
			<span class="material-symbols-outlined tw-mr-1 tw-text-link-regular">navigate_before</span>
			<span class="group-hover:tw-underline">{{ translate('BACK') }}</span>
		</button>

		<div class="tw-mt-4 tw-flex tw-items-center">
			<h1 class="tw-mb-4">
				{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_TITLE') }}
			</h1>
		</div>
		<h2 class="tw-mb-2">
			{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_SUBTITLE') }}
		</h2>
		<p class="em-profile-font">
			{{ translate('COM_EMUNDUS_PROGRAMS_EDITION_INTRO') }}
		</p>
		<hr />

		<div class="tw-mt-4">
			<Tabs :tabs="tabs" :classes="'tw-overflow-auto tw-flex tw-items-center tw-gap-2 tw-ml-7'"></Tabs>

			<div
				class="tw-bg-(--neutral-0) tw-relative tw-w-full tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-p-6"
			>
				<div class="tw-w-full" v-show="selectedMenuItem.code === 'general'">
					<iframe
						class="hide-titles tw-w-full"
						style="height: 100vh"
						:src="
							'/index.php?option=com_fabrik&view=form&formid=108&rowid=' + this.programId + '&tmpl=component&iframe=1'
						"
					>
					</iframe>
				</div>

				<div class="tw-flex tw-w-full tw-flex-col tw-gap-2" v-show="selectedMenuItem.code === 'campaigns'">
					<label class="em-profile-font tw-font-medium">{{
						translate('COM_EMUNDUS_ONBOARD_CAMPAIGNS_ASSOCIATED_TITLE')
					}}</label>
					<ul>
						<li v-for="campaign in campaigns" :key="campaign.id">
							<a
								v-if="crud.campaign && crud.campaign.u"
								class="em-profile-font tw-cursor-pointer"
								@click="
									redirectJRoute(
										'index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' + campaign.id,
									)
								"
								target="_blank"
								>{{ campaign.label }}</a
							>
							<span v-else class="em-profile-font">{{ campaign.label }}</span>
						</li>
					</ul>
					<a
						@click="redirectJRoute('index.php?option=com_emundus&view=campaigns')"
						class="em-profile-font tw-cursor-pointer tw-underline"
						target="_blank"
					>
						{{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_CAMPAIGNS') }}
					</a>
				</div>

				<div class="tw-flex tw-w-full tw-flex-col tw-gap-2" v-show="selectedMenuItem.code === 'workflows'">
					<div class="tw-flex tw-flex-col">
						<label class="tw-font-medium">{{ translate('COM_EMUNDUS_ONBOARD_WORKFLOWS_ASSOCIATED_TITLE') }}</label>
						<select v-model="workflowId">
							<option v-for="workflow in workflowOptions" :key="workflow.id" :value="workflow.id">
								{{ workflow.label }}
							</option>
						</select>
					</div>

					<div>
						<a
							@click="redirectJRoute('index.php?option=com_emundus&view=workflows')"
							class="tw-cursor-pointer tw-underline"
							target="_blank"
						>
							{{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_WORKFLOWS') }}
						</a>
					</div>

					<div class="tw-mt-2 tw-flex tw-justify-end">
						<button class="tw-btn-primary" @click="updateProgramWorkflows">
							{{ translate('SAVE') }}
						</button>
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import campaignService from '@/services/campaign';
import workflowService from '@/services/workflow';
import Multiselect from 'vue-multiselect';
import Tabs from '@/components/Utils/Tabs.vue';
import settingsService from '@/services/settings.js';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'ProgramEdit',
	components: { Tabs, Multiselect },
	props: {
		programId: {
			type: Number,
			required: true,
		},
		crud: {
			type: Object,
			default: () => ({}),
		},
	},
	data() {
		return {
			program: {},
			campaigns: [],
			workflowId: 0,
			workflowOptions: [],
			tabs: [
				{
					id: 1,
					code: 'general',
					name: 'COM_EMUNDUS_PROGRAMS_EDITION_TAB_GENERAL',
					icon: 'info',
					active: true,
					displayed: true,
				},
				{
					id: 2,
					code: 'campaigns',
					name: 'COM_EMUNDUS_PROGRAMS_EDITION_TAB_CAMPAIGNS',
					icon: 'layers',
					active: false,
					displayed: true,
				},
				{
					id: 3,
					code: 'workflows',
					name: 'COM_EMUNDUS_PROGRAMS_EDITION_TAB_WORKFLOWS',
					icon: 'schema',
					active: false,
					displayed: true,
				},
			],
		};
	},
	created() {
		this.getWorkflows();
		this.getAssociatedCampaigns();
		this.getAssociatedWorkflow();

		this.tabs[1].displayed = this.crud.campaign && this.crud.campaign.r;
		this.tabs[2].displayed = this.crud.workflow && this.crud.workflow.r;
		console.log(this.crud);
	},
	methods: {
		getWorkflows() {
			workflowService.getWorkflows().then((response) => {
				if (response.status) {
					this.workflowOptions = response.data.datas.map((workflow) => {
						return {
							id: workflow.id,
							label: workflow.label.fr,
						};
					});
				}
			});
		},
		getAssociatedCampaigns() {
			campaignService.getCampaignsByProgramId(this.programId).then((response) => {
				this.campaigns = response.data;
			});
		},
		getAssociatedWorkflow() {
			workflowService.getWorkflowsByProgramId(this.programId).then((response) => {
				const workflows = response.data.map((workflow) => workflow.id);
				if (workflows.length) {
					this.workflowId = workflows[0];
				}
			});
		},
		updateProgramWorkflows() {
			workflowService.updateProgramWorkflows(this.programId, [this.workflowId]).then((response) => {
				Swal.fire({
					icon: 'success',
					title: this.translate('COM_EMUNDUS_PROGRAM_UPDATE_ASSOCIATED_WORKFLOW_SUCCESS'),
					showConfirmButton: false,
					timer: 1500,
				});
			});
		},
		redirectJRoute(link) {
			settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang);
		},
	},
	computed: {
		selectedMenuItem() {
			return this.tabs.find((tab) => tab.active);
		},
		activeBackground() {
			return this.selectedMenuItem.code === 'general' ? 'var(--em-coordinator-bg)' : '#fff';
		},
	},
};
</script>

<style></style>
