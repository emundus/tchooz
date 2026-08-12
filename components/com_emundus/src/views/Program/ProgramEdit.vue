<template>
	<div
		id="program-edition-container"
		class="em-card-shadow tw-m-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6"
	>
		<Back :link="'index.php?option=com_emundus&view=campaigns'" />

		<div v-if="this.program.id">
			<h1 class="tw-mt-4">
				{{
					translate('COM_EMUNDUS_PROGRAMS_EDITION_TITLE').replace('%s', this.program.label || this.program.code || '')
				}}
			</h1>
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
						<ProgramForm
							v-if="!this.useOldProgramForm"
							:program="this.program"
							:prestation_sociales="prestation_sociales"
						/>

						<iframe
							v-else
							title="program_form"
							class="hide-titles tw-w-full"
							style="height: 100vh"
							:src="
								'/index.php?option=com_fabrik&view=form&formid=108&rowid=' +
								this.program.id +
								'&tmpl=component&iframe=1'
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
								<Button
									v-if="crud.campaign && crud.campaign.u"
									variant="link"
									@click="
										redirectJRoute(
											'index.php?option=com_emundus&view=campaigns&layout=addnextcampaign&cid=' + campaign.id,
											true,
										)
									"
								>
									{{ campaign.label }}
								</Button>
								<span v-else class="em-profile-font">{{ campaign.label }}</span>
							</li>
						</ul>
						<Button variant="link" @click="openCampaignsList()" class="tw-mt-2">
							{{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_CAMPAIGNS') }}
						</Button>
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
							<Button variant="link" @click="redirectJRoute('index.php?option=com_emundus&view=workflows', true)">
								{{ translate('COM_EMUNDUS_PROGRAMS_ACCESS_TO_WORKFLOWS') }}
							</Button>
						</div>

						<div class="tw-mt-2 tw-flex tw-justify-end">
							<Button @click="updateProgramWorkflows">
								{{ translate('SAVE') }}
							</Button>
						</div>
					</div>
				</div>
			</div>
		</div>
		<Loader v-else />
	</div>
</template>

<script>
import campaignService from '@/services/campaign';
import workflowService from '@/services/workflow';
import programService from '@/services/programme';
import Multiselect from 'vue-multiselect';
import Tabs from '@/components/Utils/Tabs.vue';
import settingsService from '@/services/settings.js';
import { useGlobalStore } from '@/stores/global.js';
import Back from '@/components/Utils/Back.vue';
import ProgramForm from '@/views/Program/ProgramForm.vue';
import Button from '@/components/Atoms/Button.vue';
import alerts from '@/mixins/alerts.js';
import Loader from '@/components/Atoms/Loader.vue';

export default {
	name: 'ProgramEdit',
	components: { Loader, Button, ProgramForm, Back, Tabs, Multiselect },
	mixins: [alerts],
	props: {
		program: {
			type: Object,
			required: true,
		},
		crud: {
			type: Object,
			default: () => ({}),
		},
		useOldProgramForm: {
			type: Boolean,
			default: false,
		},
		prestation_sociales: {
			type: Boolean,
			required: false,
			default: false,
		},
	},
	data() {
		return {
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
			campaignService.getCampaignsByProgramId(this.program.id).then((response) => {
				this.campaigns = response.data;
			});
		},
		getAssociatedWorkflow() {
			workflowService.getWorkflowsByProgramId(this.program.id).then((response) => {
				const workflows = response.data.map((workflow) => workflow.id);
				if (workflows.length) {
					this.workflowId = workflows[0];
				}
			});
		},
		updateProgramWorkflows() {
			workflowService.updateProgramWorkflows(this.program.id, [this.workflowId]).then((response) => {
				this.alertSuccess(this.translate('COM_EMUNDUS_PROGRAM_UPDATE_ASSOCIATED_WORKFLOW_SUCCESS'));
			});
		},
		openCampaignsList() {
			const sessionTab = sessionStorage.getItem('tchooz_selected_tab/' + document.location.hostname);
			if (sessionTab) {
				sessionStorage.setItem('tchooz_selected_tab/' + document.location.hostname, 'campaigns');
			}
			this.redirectJRoute('index.php?option=com_emundus&view=campaigns');
		},
		redirectJRoute(link, newtab) {
			settingsService.redirectJRoute(link, useGlobalStore().getCurrentLang, true, newtab);
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
