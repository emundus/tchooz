import { FetchClient } from './fetchClient.js';

const client = new FetchClient('files');
const labelsClient = new FetchClient('labels');
export default {
	async getFnums() {
		try {
			return await client.get('getallfnums');
		} catch (e) {
			return false;
		}
	},
	async getFnumInfos(fnum) {
		if (fnum) {
			try {
				return await client.get('getfnuminfos', {
					fnum: fnum,
				});
			} catch (e) {
				return false;
			}
		} else {
			return {
				status: false,
				message: 'Fnum is required',
			};
		}
	},
	async isDataAnonymized() {
		try {
			return await client.get('isdataanonymized');
		} catch (e) {
			return {
				status: false,
				message: e.message,
			};
		}
	},
	async getAllStatus() {
		try {
			return await client.get('index.php?option=com_emundus&controller=files&task=getstate');
		} catch (e) {
			return {
				status: false,
				message: e.message,
			};
		}
	},
	async getProfiles() {
		try {
			return await client.get('getprofiles');
		} catch (e) {
			return {
				status: false,
				message: e.message,
			};
		}
	},
	async getFileIdFromFnum(fnum) {
		try {
			return await client.get('getFileIdFromFnum', {
				fnum: fnum,
			});
		} catch (e) {
			return {
				status: false,
				message: e.message,
			};
		}
	},
	async renderEmundusTags(string, fnum) {
		try {
			const response = await client.get('index.php?option=com_emundus&controller=files&task=renderemundustags', {
				params: {
					string: string,
					fnum: fnum,
				},
			});

			return response.data;
		} catch (e) {
			return {
				status: false,
				message: e.message,
			};
		}
	},
	async getFileSynthesis(fnum) {
		try {
			return await client.get('getFileSynthesis&fnum=' + fnum);
		} catch (e) {
			return false;
		}
	},

	async getUpdateOwnerFiles() {
		try {
			return await client.get('getupdateownerfiles');
		} catch (e) {
			return false;
		}
	},

	async updateOwner(ownerId) {
		try {
			return await client.post('updateowner', { owner: ownerId });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async deleteApplicationTag(idTag, fnum) {
		try {
			return await client.post('deletetags', {
				// todo: one day replace this shitty controller method, parameters make no sense
				tag: idTag,
				fnums: JSON.stringify({ 1: fnum }),
			});
		} catch (e) {
			return {
				status: false,
			};
		}
	},

	async getApplicationTags(fnum) {
		try {
			return await labelsClient.get('getapplicationtags', { fnum: fnum });
		} catch (e) {
			return { status: false, data: [] };
		}
	},

	async getAvailableTags() {
		try {
			return await labelsClient.get('getavailabletags');
		} catch (e) {
			return { status: false, data: [] };
		}
	},

	async addApplicationTag(fnum, tagId = 0, newTag = '', newTagClass = 'label-default') {
		const payload = {
			fnums: JSON.stringify([fnum]),
		};

		if (newTag) {
			payload.newTag = newTag;
			payload.newTagClass = newTagClass;
		} else {
			payload.tag = tagId;
		}

		try {
			return await client.post('tagfile', payload);
		} catch (e) {
			return { status: false, msg: e.message };
		}
	},
};
