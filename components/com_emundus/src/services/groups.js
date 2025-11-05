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
};
