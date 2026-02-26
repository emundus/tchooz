/* jshint esversion: 8 */
import { FetchClient } from './fetchClient.js';

const client = new FetchClient('campaign');

export default {
	async get(task, params) {
		try {
			return client.get(task, params);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateDocument(params, create = false) {
		const task = create ? 'createdocument' : 'updatedocument';

		try {
			return await client.post(task, params);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async setDocumentMandatory(params) {
		try {
			return await client.post('updatedocumentmandatory', params);
		} catch (error) {
			return {
				status: false,
				error: error,
			};
		}
	},

	async getAllCampaigns(filter = '', sort = 'DESC', recherche = '', lim = 9999, page = 0, program = 'all') {
		try {
			const response = await client.get('getallcampaign', {
				filter: filter,
				sort: sort,
				recherche: recherche,
				lim: lim,
				page: page,
				program: program,
			});

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async createCampaign(form) {
		// label, start_date and end_date are required
		if (!form.label || !form.start_date || !form.end_date) {
			return {
				status: false,
				msg: 'Label, start date and end date are required',
			};
		}

		try {
			return await client.post(`createcampaign`, {
				body: JSON.stringify(form),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async updateCampaign(form, cid) {
		if (cid < 1) {
			return this.createCampaign(form);
		} else {
			try {
				return await client.post(`updatecampaign`, {
					body: JSON.stringify(form),
					cid: cid,
				});
			} catch (e) {
				return {
					status: false,
					msg: e.message,
				};
			}
		}
	},

	async pinCampaign(cid) {
		// cid must be an integer
		if (cid < 1) {
			return {
				status: false,
				msg: 'Invalid campaign ID',
			};
		}

		try {
			const formData = new FormData();
			formData.append('cid', cid);

			return await client.post(`pincampaign`, formData, {
				'Content-Type': 'multipart/form-data',
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getCampaignMoreFormUrl(cid) {
		if (cid < 1) {
			return {
				status: false,
				msg: 'Invalid campaign ID',
			};
		}

		try {
			return await client.get(`getcampaignmoreformurl&cid=${cid}`);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getAllItemsAlias(campaignId) {
		try {
			return await client.get('getallitemsalias&campaign_id=' + campaignId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getProgrammeByCampaignID(campaignId) {
		try {
			return await client.get('getProgrammeByCampaignID&campaign_id=' + campaignId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getCampaignById(campaignId) {
		try {
			return await client.get('getcampaignbyid&id=' + campaignId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getYears() {
		try {
			return await client.get('getyears');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateProfile(profileId, campaignId) {
		try {
			return await client.post('updateprofile', {
				profile: profileId,
				campaign: campaignId,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async editDropfileDocument(documentId, newName) {
		try {
			return await client.post('editdocumentdropfile', {
				did: documentId,
				name: newName,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async reorderDropfileDocuments(orderedDocuments) {
		try {
			return await client.post('updateorderdropfiledocuments', {
				documents: JSON.stringify(orderedDocuments),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async deleteDropfileDocument(documentId) {
		try {
			return await client.post('deletedocumentdropfile', { did: documentId });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getCampaignsByProgramId(programId) {
		try {
			return await client.get('getCampaignsByProgramId&program_id=' + programId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getCampaignLanguages(campaignId) {
		try {
			return await client.get('getcampaignlanguages&campaign_id=' + campaignId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getCampaignUsercategories(campaignId) {
		try {
			return await client.get('getcampaignusercategories&campaign_id=' + campaignId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getImportModel(campaignId, options, format = 'xlsx') {
		try {
			return await client.get('getimportmodel', {
				id: campaignId,
				format: format,
				status: options.status,
				forms: options.forms,
				evaluations: options.evaluations,
				validators: options.validators,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async scanImportFile(csvImport) {
		try {
			return await client.post('scanimportfile', csvImport);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async importFiles(csvImport) {
		try {
			return await client.post('importfiles', csvImport);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async isImportActivated() {
		try {
			return await client.get('isimportactivated');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getUserIdByFnum(fnum) {
		try {
			return await client.get('getuseridfromfnum', {
				fnum: fnum,
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},

	async publishCampaign(campaignId) {
		try {
			return await client.post('publishcampaign', {
				id: campaignId,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async needMoreInfo(campaignId) {
		try {
			return await client.get('needmoreinfo', {
				id: campaignId,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getAvailableChoices(fnum, search, filters = {}) {
		let filtersJSON = {};
		for (const filter of filters) {
			if (filter.value !== '') {
				filtersJSON[filter.key] = filter.value;
			}
		}

		try {
			return await client.get('getavailablechoices', {
				fnum: fnum,
				search: search,
				filters: JSON.stringify(filtersJSON),
			});
		} catch (e) {
			return {
				status: false,
				error: e.message,
			};
		}
	},
};
