export class FetchClient {
	constructor(controller) {
		this.baseUrl = '/index.php?option=com_emundus&controller=' + controller;
	}

	async get(task, params, signal = null) {
		let url = this.baseUrl + '&task=' + task;

		if (params) {
			for (let key in params) {
				url += '&' + key + '=' + params[key];
			}
		}

		let headers = {};
		if (typeof Joomla !== 'undefined' && Joomla && Joomla.getOptions) {
			var csrf = Joomla.getOptions('csrf.token', '');
			if (csrf) {
				headers = {
					'X-CSRF-Token': csrf,
				};
			}
		}

		let options = {
			method: 'GET',
			headers: headers,
		};
		if (signal) {
			options.signal = signal;
		}

		return fetch(url, options)
			.then(async (response) => {
				if (response.ok) {
					return response.json();
				} else {
					let errorMessage = 'An error occurred.';
					try {
						const errorData = await response.json();
						errorMessage = errorData.message || JSON.stringify(errorData);
					} catch (e) {
						try {
							errorMessage = await response.text();
						} catch (_) {}
					}
					throw new Error(errorMessage);
				}
			})
			.then((data) => {
				return data;
			})
			.catch((error) => {
				throw new Error(error.message);
			});
	}

	async post(task, data, headers = null, timeout = 10000) {
		let url = this.baseUrl + '&task=' + task;

		let formData = new FormData();
		for (let key in data) {
			formData.append(key, data[key]);
		}

		let parameters = {
			method: 'POST',
			body: formData,
		};

		let baseHeaders = {};
		if (typeof Joomla !== 'undefined' && Joomla && Joomla.getOptions) {
			var csrf = Joomla.getOptions('csrf.token', '');
			if (csrf) {
				baseHeaders = {
					'X-CSRF-Token': csrf,
				};
			}
		}

		if (headers) {
			headers = Object.assign(baseHeaders, headers);
		} else {
			headers = baseHeaders;
		}

		parameters.headers = headers;

		let timeoutId = null;
		if (timeout) {
			const controller = new AbortController();
			timeoutId = setTimeout(() => controller.abort(), timeout);
			parameters.signal = controller.signal;
		}

		return fetch(url, parameters)
			.then(async (response) => {
				if (timeout && timeoutId) {
					clearTimeout(timeoutId);
				}
				if (response.ok) {
					return response.json();
				} else {
					const errorText = await response.text();
					throw new Error(errorText);
				}
			})
			.then((data) => {
				return data;
			})
			.catch((error) => {
				if (timeout && timeoutId) {
					clearTimeout(timeoutId);
				}
				if (error.name === 'TimeoutError') {
					throw new Error('The request timed out. ' + error.message + '.');
				} else {
					throw new Error(error.message);
				}
			});
	}

	async delete(task, params) {
		let url = this.baseUrl + '&task=' + task;

		if (params) {
			for (let key in params) {
				url += '&' + key + '=' + params[key];
			}
		}

		return fetch(url, {
			method: 'DELETE',
		})
			.then((response) => {
				if (response.ok) {
					return response.json();
				} else {
					throw new Error(
						'An error occurred while fetching the data. ' + response.status + ' ' + response.statusText + '.',
					);
				}
			})
			.then((data) => {
				return data;
			})
			.catch((error) => {
				throw new Error('An error occurred while fetching the data. ' + error.message + '.');
			});
	}
}
