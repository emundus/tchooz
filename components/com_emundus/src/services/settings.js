/* jshint esversion: 8 */
import { FetchClient } from './fetchClient.js';

const fetchClient = new FetchClient('settings');

export default {
	async getActiveLanguages() {
		try {
			return await fetchClient.get('getactivelanguages');
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},
	async removeParameter(param) {
		const data = {
			param: param,
		};

		try {
			return await fetchClient.post('removeparam', data);
		} catch (e) {
			return {
				status: false,
				error: e,
			};
		}
	},
	async checkFirstDatabaseJoin() {
		try {
			return await fetchClient.get('checkfirstdatabasejoin');
		} catch (e) {
			return {
				status: false,
				message: e.message,
			};
		}
	},
	async getEmundusParams() {
		try {
			return await fetchClient.get('getemundusparams');
		} catch (e) {
			return false;
		}
	},
	async getOnboardingLists() {
		return fetch('index.php?option=com_emundus&controller=settings&task=getonboardinglists')
			.then((response) => {
				if (response.ok) {
					return response.json();
				} else {
					throw new Error('Get onboarding lists fetch failed');
				}
			})
			.then((data) => {
				return data;
			})
			.catch((error) => {
				return {
					status: false,
					msg: error.message,
				};
			});
	},

	async getOffset() {
		try {
			return await fetchClient.get('getOffset');
		} catch (e) {
			return false;
		}
	},

	async redirectJRoute(link, language = 'fr-FR', redirect = true) {
		let formDatas = new FormData();
		formDatas.append('link', link);
		formDatas.append('redirect_language', language);

		try {
			const response = await fetch(
				window.location.origin + '/index.php?option=com_emundus&controller=settings&task=redirectjroute',
				{
					method: 'POST',
					body: formDatas,
				},
			);

			if (!response.ok) {
				throw new Error(Joomla.Text._('COM_EMUNDUS_ERROR_OCCURED'));
			}

			const result = await response.json();

			if (result.status) {
				if (redirect) {
					window.location.href = window.location.origin + '/' + result.data;
				}
				return result.data;
			}

			return null; // Retourner null si le statut n'est pas valide
		} catch (error) {
			window.location.reload();
			throw error; // Relancer l'erreur si besoin pour une gestion en amont
		}
	},

	async getSEFLink(link, language = 'fr-FR') {
		try {
			return await fetchClient.post('redirectjroute', { link: link, redirect_language: language });
		} catch (e) {
			return false;
		}
	},

	async getTimezoneList() {
		try {
			return await fetchClient.get('gettimezonelist');
		} catch (e) {
			return false;
		}
	},

	async saveParams(params) {
		const formData = new FormData();
		Object.keys(params).forEach((key) => {
			formData.append('params[]', JSON.stringify(params[key]));
		});

		fetch(window.location.origin + '/index.php?option=com_emundus&controller=settings&task=updateemundusparams', {
			method: 'POST',
			body: formData,
		})
			.then((response) => {
				if (response.ok) {
					return response.json();
				}

				throw new Error(Joomla.JText._('COM_EMUNDUS_ERROR_OCCURED'));
			})
			.then((result) => {
				if (result.status) {
					return result.data;
				}
			});
	},

	async saveColors(preset) {
		let data = {};
		data.preset = JSON.stringify(preset);

		try {
			return await fetchClient.post('updatecolor', data);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getStatus() {
		try {
			return await fetchClient.get('getstatus');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getTags() {
		try {
			return await fetchClient.get('gettags');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getEmailSender() {
		try {
			return await fetchClient.get('getemailsender');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getLogo() {
		try {
			return await fetchClient.get('getlogo');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getVariables() {
		try {
			return await fetchClient.get('geteditorvariables');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getAllArticleNeedToModify() {
		try {
			return await fetchClient.get('getAllArticleNeedToModify');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getMedia() {
		try {
			return await fetchClient.get('getmedia');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateTagOrdering(orderedTags) {
		if (orderedTags.length > 0) {
			const data = {
				tags: orderedTags.join(','),
			};

			return fetchClient.post('updatetagsorder', data);
		} else {
			return {
				status: false,
				message: 'WRONG_PARAMETERS',
			};
		}
	},
	async getEmailParameters() {
		try {
			return await fetchClient.get('getemailparameters');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async testEmail(data) {
		try {
			return await fetchClient.post('testemail', data, null, 5000);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async saveEmailParameters(data) {
		try {
			return await fetchClient.post('saveemailparameters', data);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getLiveSite() {
		try {
			return await fetchClient.get('getlivesite');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getsslinfo() {
		try {
			return await fetchClient.get('getsslinfo');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async sendRequest(data) {
		try {
			return await fetchClient.post('sendwebsecurityrequest', data);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getHistory(extension, only_pending = false, page = 1, limit = 10, itemId = 0) {
		try {
			return await fetchClient.get('gethistory', {
				extension: extension,
				only_pending: only_pending,
				page: page,
				limit: limit,
				item_id: itemId,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async updateHistoryStatus(id, status) {
		try {
			return await fetchClient.post('updatehistorystatus', { id: id, status: status });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getAsyncOptions(route, query, options = {}) {
		try {
			return await fetchClient.get(route, query, options.signal);
		} catch (e) {
			if (e.name === 'AbortError') {
				return null;
			}
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getApps() {
		try {
			return await fetchClient.get('getapps');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getApp(app_id = 0, app_type = '') {
		try {
			return await fetchClient.get('getapp', { app_id: app_id, app_type: app_type });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async setupApp(app_id, setup) {
		try {
			return await fetchClient.post('setupapp', { app_id: app_id, setup: JSON.stringify(setup) });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async toggleAppEnabled(app_id, enabled) {
		try {
			return await fetchClient.post('disableapp', { app_id: app_id, enabled: enabled });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getAddons() {
		try {
			return await fetchClient.get('getaddons');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async toggleAddonEnabled(addon_type, enabled) {
		try {
			return await fetchClient.post('toggleaddon', { addon_type: addon_type, enabled: enabled });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async setupMessenger(setup) {
		try {
			return await fetchClient.post('setupmessenger', { setup: JSON.stringify(setup) });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async historyRetryEvent(actionLogId) {
		try {
			return await fetchClient.post('historyretryevent', { action_log_row_id: actionLogId });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
