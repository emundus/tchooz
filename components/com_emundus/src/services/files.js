import { FetchClient } from './fetchClient.js';

const client = new FetchClient('file');

export default {
	async getFiles(type = 'default', refresh = false, limit = 25, page = 0) {
		try {
			return await client.get('getfiles', {
				type: type,
				refresh: refresh,
			});
		} catch (e) {
			return false;
		}
	},

	async getColumns(type = 'default') {
		try {
			return await client.get('getcolumns', {
				type: type,
			});
		} catch (e) {
			return false;
		}
	},

	async getEvaluationFormByFnum(fnum, type) {
		try {
			return await client.get('getevaluationformbyfnum', {
				fnum: fnum,
				type: type,
			});
		} catch (e) {
			return false;
		}
	},

	async getMyEvaluation(fnum) {
		try {
			return await client.get('getmyevaluation', {
				fnum: fnum,
			});
		} catch (e) {
			return false;
		}
	},

	async checkAccess(fnum) {
		try {
			return await client.get('checkaccess', {
				fnum: fnum,
			});
		} catch (e) {
			return false;
		}
	},

	async getLimit(type = 'default') {
		try {
			return await client.get('getlimit', {
				type: type,
			});
		} catch (e) {
			return false;
		}
	},

	async getPage(type = 'default') {
		try {
			return await client.get('getpage', {
				type: type,
			});
		} catch (e) {
			return false;
		}
	},

	async updateLimit(limit) {
		try {
			return await client.get('updatelimit', {
				limit: limit,
			});
		} catch (e) {
			return false;
		}
	},

	async updatePage(page) {
		try {
			return await client.get('updatepage', {
				page: page,
			});
		} catch (e) {
			return false;
		}
	},

	async getSelectedTab(type) {
		try {
			return await client.get('getselectedtab', {
				type: type,
			});
		} catch (e) {
			return false;
		}
	},

	async setSelectedTab(tab, type = 'evaluation') {
		try {
			return await client.get('setselectedtab', {
				tab: tab,
				type: type,
			});
		} catch (e) {
			return false;
		}
	},

	async getFile(fnum, type = 'default') {
		try {
			return await client.get('getfile', {
				fnum: fnum,
				type: type,
			});
		} catch (e) {
			return false;
		}
	},

	async getFilters() {
		try {
			return await client.get('getfilters');
		} catch (e) {
			return false;
		}
	},

	async applyFilters(filters) {
		const data = {
			filters: JSON.stringify(filters),
		};

		try {
			return await client.post('applyfilters', data);
		} catch (e) {
			return false;
		}
	},

	async getComments(fnum) {
		try {
			const response = await client.get('getcomments', {
				fnum: fnum,
			});

			// make sure that response.data.data is an array and that every element has id property
			if (Array.isArray(response.data.data)) {
				const correctResponse = response.data.data.every((comment) => {
					if (!comment.hasOwnProperty('id')) {
						return false;
					}

					return true;
				});

				if (correctResponse) {
					return response.data;
				} else {
					return {
						data: [],
						status: false,
						msg: 'Invalid response',
					};
				}
			} else {
				return {
					data: [],
					status: false,
					msg: 'Invalid response',
				};
			}
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async saveComment(fnum, comment) {
		const formData = new FormData();
		formData.append('fnum', fnum);
		Object.keys(comment).forEach((key) => {
			formData.append(key, comment[key]);
		});

		try {
			const response = await client.post('savecomment', formData);

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async deleteComment(cid) {
		const formData = new FormData();
		formData.append('cid', cid);

		try {
			const response = await client.post('deletecomment', formData);

			return response.data;
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getApplicationForm(fnum) {
		try {
			// we want to get html from response
			const html = await fetch(
				'index.php?option=com_emundus&view=application&format=raw&layout=form&fnum=' + fnum + '&context=ranking',
				{
					method: 'GET',
					headers: {
						'Content-Type': 'text/html',
					},
				},
			)
				.then((response) => {
					if (response.ok) {
						return response.text();
					}
				})
				.then((data) => {
					return data;
				});

			return html;
		} catch (e) {
			return false;
		}
	},
};
