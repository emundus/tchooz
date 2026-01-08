/* jshint esversion: 8 */
import { FetchClient } from './fetchClient.js';
import DOMPurify from 'dompurify';

const client = new FetchClient('comments');

export default {
	async getComments(ccid) {
		if (ccid > 0) {
			try {
				const response = await client.get('getcomments', {
					ccid: ccid,
				});

				if (response.status) {
					response.data = response.data.map((comment) => {
						comment.comment_body = DOMPurify.sanitize(comment.comment_body, {
							ALLOWED_TAGS: [],
							ALLOWED_ATTR: [],
						});
						return comment;
					});
				}

				return response;
			} catch (e) {
				return {
					status: false,
					msg: e.message,
				};
			}
		}
	},
	async addComment(ccid, comment, target, visible_to_applicant = false, parent_id = 0) {
		if (ccid > 0 && comment.length > 0) {
			try {
				visible_to_applicant = visible_to_applicant ? 1 : 0;

				const params = {
					ccid: ccid,
					comment: comment,
					target: JSON.stringify(target),
					visible_to_applicant: visible_to_applicant,
					parent_id: parent_id,
				};

				return await client.post('addcomment', params);
			} catch (e) {
				return {
					status: false,
					msg: e.message,
				};
			}
		} else {
			return {
				status: false,
				msg: 'Invalid data',
			};
		}
	},
	async updateComment(comment_id, comment) {
		if (comment_id > 0 && comment.length > 0) {
			try {
				const params = {
					comment_id: comment_id,
					comment: comment,
				};

				return await client.post('updatecomment', params);
			} catch (e) {
				return {
					status: false,
					msg: e.message,
				};
			}
		} else {
			return {
				status: false,
				msg: 'Invalid data',
			};
		}
	},
	async updateCommentOpenedState(comment_id, opened = 1) {
		if (comment_id > 0) {
			try {
				const params = {
					comment_id: comment_id,
					opened: opened,
				};

				return await client.post('updatecommentopenedstate', params);
			} catch (e) {
				return {
					status: false,
					msg: e.message,
				};
			}
		}
	},
	async deleteComment(comment_id) {
		if (comment_id > 0) {
			try {
				const params = {
					comment_id: comment_id,
				};

				return await client.post('deletecomment', params);
			} catch (e) {
				return {
					status: false,
					msg: e.message,
				};
			}
		} else {
			return {
				status: false,
				msg: 'Invalid data',
			};
		}
	},
	async getTargetableElements(ccid) {
		try {
			return await client.get('gettargetableelements', {
				ccid: ccid,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getMenuItemForFormId(ccid, formId) {
		try {
			return await client.get('getMenuItemForFormId', {
				ccid: ccid,
				form_id: formId,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
				data: null,
			};
		}
	},
};
