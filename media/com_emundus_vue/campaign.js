import { f as FetchClient } from "./app_emundus.js";
const client = new FetchClient("campaign");
const campaignService = {
  async get(task, params) {
    try {
      return client.get(task, params);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async updateDocument(params, create = false) {
    const task = create ? "createdocument" : "updatedocument";
    try {
      return await client.post(task, params);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async setDocumentMandatory(params) {
    try {
      return await client.post("updatedocumentmandatory", params);
    } catch (error) {
      return {
        status: false,
        error
      };
    }
  },
  async getAllCampaigns(filter = "", sort = "DESC", recherche = "", lim = 9999, page = 0, program = "all") {
    try {
      const response = await client.get("getallcampaign", {
        filter,
        sort,
        recherche,
        lim,
        page,
        program
      });
      return response.data;
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async createCampaign(form) {
    if (!form.label || !form.start_date || !form.end_date) {
      return {
        status: false,
        msg: "Label, start date and end date are required"
      };
    }
    try {
      const data = {
        label: JSON.stringify(form.label),
        start_date: form.start_date,
        end_date: form.end_date,
        short_description: form.short_description,
        description: form.description,
        training: form.training,
        year: form.year,
        published: form.published,
        is_limited: form.is_limited,
        profile_id: form.profile_id,
        limit: form.limit,
        limit_status: form.limit_status,
        alias: form.alias,
        pinned: form.pinned
      };
      return await client.post(`createcampaign`, {
        body: JSON.stringify(form)
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
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
          cid
        });
      } catch (e) {
        return {
          status: false,
          msg: e.message
        };
      }
    }
  },
  async pinCampaign(cid) {
    if (cid < 1) {
      return {
        status: false,
        msg: "Invalid campaign ID"
      };
    }
    try {
      const formData = new FormData();
      formData.append("cid", cid);
      return await client.post(`pincampaign`, formData, {
        "Content-Type": "multipart/form-data"
      });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getCampaignMoreFormUrl(cid) {
    if (cid < 1) {
      return {
        status: false,
        msg: "Invalid campaign ID"
      };
    }
    try {
      return await client.get(`getcampaignmoreformurl&cid=${cid}`);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getAllItemsAlias(campaignId) {
    try {
      return await client.get("getallitemsalias&campaign_id=" + campaignId);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getProgrammeByCampaignID(campaignId) {
    try {
      return await client.get("getProgrammeByCampaignID&campaign_id=" + campaignId);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getCampaignById(campaignId) {
    try {
      return await client.get("getcampaignbyid&id=" + campaignId);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getYears() {
    try {
      return await client.get("getyears");
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async updateProfile(profileId, campaignId) {
    try {
      return await client.post("updateprofile", { profile: profileId, campaign: campaignId });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async editDropfileDocument(documentId, newName) {
    try {
      return await client.post("editdocumentdropfile", { did: documentId, name: newName });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async reorderDropfileDocuments(orderedDocuments) {
    try {
      return await client.post("updateorderdropfiledocuments", { documents: JSON.stringify(orderedDocuments) });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async deleteDropfileDocument(documentId) {
    try {
      return await client.post("deletedocumentdropfile", { did: documentId });
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getCampaignsByProgramId(programId) {
    try {
      return await client.get("getCampaignsByProgramId&program_id=" + programId);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  },
  async getCampaignLanguages(campaignId) {
    try {
      return await client.get("getcampaignlanguages&campaign_id=" + campaignId);
    } catch (e) {
      return {
        status: false,
        msg: e.message
      };
    }
  }
};
export {
  campaignService as c
};
