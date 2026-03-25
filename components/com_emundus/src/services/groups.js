import { FetchClient } from './fetchClient.js';

const client = new FetchClient('groups');

export default {
	async getGroups() {
		try {
			return await client.get('getgroups');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	getUsersToShareTo() {
		return client.get('getuserstoshareto').then((data) => {
			if (data.status) {
				// add group id and group label to each user
				data.data.forEach((user) => {
					user.group_id = 1;
					user.group_label = '';
					user.label = user.name;
				});

				return data.data;
			} else {
				return [];
			}
		});
	},
	getGroupsToShareTo() {
		return client.get('getgroupstoshareto').then((data) => {
			if (data.status) {
				data.data.forEach((group) => {
					group.group_id = 2;
					group.group_label = '';
					group.label = group.label + ' (' + group.id + ')';
				});

				return data.data;
			} else {
				return [];
			}
		});
	},

	async getGroup(id) {
		try {
			return await client.get('getgroup', { id: id });
		} catch (e) {
			return {
				status: false,
				error: e,
				msg: e.message,
			};
		}
	},

	async saveGroup(data) {
		try {
			return await client.post('savegroup', data);
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async associatePrograms(groupId, programCodes) {
		try {
			return await client.post('associateprograms', { group_id: groupId, program_codes: programCodes });
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},

	async getAccessRights(groupId) {
		try {
			return await client.get('getaccessrights', { group_id: groupId });
		} catch (e) {
			return {
				status: false,
				error: e,
				msg: e.message,
			};
		}
	},

	async updateAccessRights(groupId, accessRights) {
		try {
			accessRights = JSON.stringify(accessRights);

			return await client.post('updateaccessrights', { group_id: groupId, access_rights: accessRights });
		} catch (e) {
			return {
				status: false,
				error: e,
				msg: e.message,
			};
		}
	},

	async getUsersGroup(groupId) {
		try {
			return await client.get('getusersgroup', { group_id: groupId });
		} catch (e) {
			return {
				status: false,
				error: e,
				msg: e.message,
			};
		}
	},
};
