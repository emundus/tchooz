/* jshint esversion: 8 */
import client from './axiosClient';

export default {
	async getUserById(id) {
		try {
			const response = await client().get('index.php?option=com_emundus&controller=users&task=getuserbyid', {
				params: {
					id: id,
				},
			});

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getUserNameById(id) {
		try {
			const response = await client().get('index.php?option=com_emundus&controller=users&task=getUserNameById', {
				params: {
					id: id,
				},
			});

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getAccessRights(id, fnum) {
		try {
			const response = await client().get(
				'index.php?option=com_emundus&controller=users&task=getattachmentaccessrights',
				{
					params: {
						id: id,
						fnum: fnum,
					},
				},
			);

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getAcl(action, crud = 'r', fnum = null) {
		try {
			const response = await client().get('index.php?option=com_emundus&controller=users&task=getacl', {
				params: {
					action: action,
					crud: crud,
					fnum: fnum,
				},
			});

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getAllAccessRights() {
		try {
			const response = await client().get('index.php?option=com_emundus&controller=users&task=getuseraccessrights');

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getProfiles() {
		try {
			const response = await client().get('index.php?option=com_emundus&controller=users&task=getprofiles');
			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getColumnsFromProfileForm() {
		try {
			const response = await client().get(
				'index.php?option=com_emundus&controller=users&task=getcolumnsfromprofileform',
			);
			return response.data;
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},
};
