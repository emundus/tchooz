import { FetchClient } from './fetchClient.js';

const client = new FetchClient('workflow');

export default {
	async getWorkflow(id) {
		try {
			return await client.get('getworkflow', { id: id });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getWorkflows() {
		try {
			return await client.get('getworkflows');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async saveWorkflow(workflow, steps, programs) {
		try {
			const data = {
				workflow: JSON.stringify(workflow),
				steps: JSON.stringify(steps),
				programs: JSON.stringify(programs),
			};

			return await client.post('updateworkflow', data);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateProgramWorkflows(programId, workflows) {
		try {
			return await client.post('updateprogramworkflows', {
				program_id: programId,
				workflows: JSON.stringify(workflows),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async deleteWorkflowStep(stepId) {
		if (stepId > 0) {
			return await client.post('deleteworkflowstep', { step_id: stepId });
		} else {
			return {
				status: false,
				msg: 'Invalid step id.',
			};
		}
	},
	async updateStepState(stepId, state) {
		if (stepId > 0) {
			return await client.post('updatestepstate', { step_id: stepId, state: state });
		} else {
			return {
				status: false,
				msg: 'Invalid step id.',
			};
		}
	},
	async getStepTypes() {
		try {
			return await client.get('getsteptypes');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async saveTypes(types) {
		try {
			return await client.post('savesteptypes', { types: JSON.stringify(types) });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getCampaignSteps(campaignId) {
		try {
			return await client.get('getcampaignsteps', { campaign_id: campaignId });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async saveCampaignSteps(campaignId, steps) {
		try {
			return await client.post('savecampaignstepsdates', { campaign_id: campaignId, steps: JSON.stringify(steps) });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getWorkflowsByProgramId(programId) {
		try {
			return await client.get('getworkflowsbyprogramid', { program_id: programId });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getProgramsWorkflows() {
		try {
			return await client.get('getprogramsworkflows');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
