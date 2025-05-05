import { FetchClient } from './fetchClient.js';

const client = new FetchClient('files');

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
};
