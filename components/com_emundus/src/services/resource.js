import { FetchClient } from './fetchClient.js';

const client = new FetchClient('resource');

const UPLOAD_TIMEOUT = 60000;

export default {
	/**
	 * Upload one or several documents into the resources module.
	 * Each file is sent as an independent request (the backend imports one document at a time).
	 *
	 * @param {File[]} files    Native files selected/dropped by the user.
	 * @param {number|null} folderId Destination folder id, null for the root.
	 * @returns {Promise<{status: boolean, imported: Array, errors: Array}>}
	 */
	async uploadFiles(files, folderId = null) {
		const imported = [];
		const errors = [];

		for (const file of files) {
			const payload = { file };
			if (folderId) {
				payload.folder_id = folderId;
			}

			try {
				const response = await client.post('import', payload, null, UPLOAD_TIMEOUT);

				if (response?.status) {
					imported.push(response.data);
				} else {
					errors.push({ name: file.name, msg: response?.msg || '' });
				}
			} catch (e) {
				errors.push({ name: file.name, msg: e.message });
			}
		}

		return {
			status: errors.length === 0,
			imported,
			errors,
		};
	},

	/**
	 * Flat list of every folder as select options, prefixed with a "root" option (value '').
	 *
	 * @returns {Promise<Array<{value: string, label: string}>>}
	 */
	async getFolderOptions() {
		try {
			const response = await client.get('getfolderoptions');
			return response?.status && Array.isArray(response.data) ? response.data : [];
		} catch (e) {
			return [];
		}
	},

	/**
	 * Get the server-rendered HTML preview of a stored document (PDF, image, Word, spreadsheet…).
	 * Always resolves to the same shape so the consumer never has to guard individual fields.
	 *
	 * @param {number} id Resource id.
	 * @returns {Promise<{status: boolean, content: string, style: string, overflowX: boolean, overflowY: boolean, msg: string, error: string}>}
	 */
	async getPreview(id) {
		const empty = { status: false, content: '', style: '', overflowX: false, overflowY: false, msg: '', error: '' };

		try {
			const response = await client.get('preview', { id });
			if (response?.status) {
				return { ...empty, status: true, ...response.data };
			}
			return { ...empty, error: response?.msg || '' };
		} catch (e) {
			return { ...empty, error: e.message };
		}
	},

	/**
	 * Register a download and get the public URL of a stored document.
	 *
	 * @param {number} id Resource id.
	 * @returns {Promise<string|null>}
	 */
	async getDownloadUrl(id) {
		try {
			const response = await client.get('download', { id });
			return response?.status ? response.data.url : null;
		} catch (e) {
			return null;
		}
	},

	/**
	 * Rename a resource (or folder). Accepts the list reference ("file-3" / "folder-5") so the
	 * backend can resolve the type and run the matching access check.
	 *
	 * @param {number|string} id   Resource reference ("file-3") or numeric id.
	 * @param {string}        name New name.
	 * @returns {Promise<{status: boolean, data: object|null, msg: string}>}
	 */
	async rename(id, name) {
		try {
			const response = await client.post('rename', { id, input: name });
			return { status: !!response?.status, data: response?.data || null, msg: response?.msg || '' };
		} catch (e) {
			return { status: false, data: null, msg: e.message };
		}
	},

	/**
	 * Get the access list (roles/groups/users) of a resource or a folder.
	 *
	 * @param {number|string} id Resource reference ("file-42" / "folder-5") or numeric file id.
	 * @returns {Promise<Array<{id: number, type: string, targetId: number, permission: string, targetLabel: string}>>}
	 */
	async getAccess(id) {
		try {
			const response = await client.get('getaccess', { id });
			return response?.status && Array.isArray(response.data) ? response.data : [];
		} catch (e) {
			return [];
		}
	},

	/**
	 * Replace the whole access list of a resource or a folder.
	 *
	 * @param {number|string} id Resource reference ("file-42" / "folder-5") or numeric file id.
	 * @param {Array<{type: string, target_id: number, permission: string}>} access Access entries.
	 * @returns {Promise<{status: boolean, msg: string}>}
	 */
	async saveAccess(id, access) {
		try {
			const response = await client.post('saveaccess', { id, access: JSON.stringify(access) });
			return { status: !!response?.status, msg: response?.msg || '' };
		} catch (e) {
			return { status: false, msg: e.message };
		}
	},

	/**
	 * Get the share link of a resource (null when none exists).
	 *
	 * @param {number} id Resource id.
	 * @returns {Promise<{id: number, code: string, hasPassword: boolean, expirationDate: string|null, isExpired: boolean}|null>}
	 */
	async getShareLink(id) {
		try {
			const response = await client.get('getsharelink', { id });
			return response?.status ? response.data : null;
		} catch (e) {
			return null;
		}
	},

	/**
	 * Create or update the share link of a resource.
	 *
	 * The request body is sent as FormData, which stringifies null to "null";
	 * so `password` is omitted when null (backend keeps the current one) and
	 * `expiration_date` is always a string ('' means "no expiration").
	 *
	 * @param {number} id Resource id.
	 * @param {{password: string|null, expirationDate: string|null}} options
	 *        password: string to set/clear (''), null to keep the current one.
	 *        expirationDate: 'Y-m-d H:i:s' string, '' or null for no expiration.
	 * @returns {Promise<{status: boolean, data: object|null, msg: string}>}
	 */
	async saveShareLink(id, { password = null, expirationDate = null } = {}) {
		try {
			const payload = { id, expiration_date: expirationDate || '' };
			if (password !== null && password !== undefined) {
				payload.password = password;
			}

			const response = await client.post('savesharelink', payload);
			return { status: !!response?.status, data: response?.data || null, msg: response?.msg || '' };
		} catch (e) {
			return { status: false, data: null, msg: e.message };
		}
	},

	/**
	 * Revoke (delete) the share link of a resource.
	 *
	 * @param {number} id Resource id.
	 * @returns {Promise<{status: boolean, msg: string}>}
	 */
	async revokeShareLink(id) {
		try {
			const response = await client.post('revokesharelink', { id });
			return { status: !!response?.status, msg: response?.msg || '' };
		} catch (e) {
			return { status: false, msg: e.message };
		}
	},
};
