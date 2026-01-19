import providerService from '../services/provider.js';

export default {
	methods: {
		fromFieldEntityToParameter(field, value = null) {
			if (!field) {
				return null;
			}

			let parameter = {
				param: field.name,
				label: field.label,
				optional: !field.required,
				value: value,
				displayed: true,
				displayRules: field.displayRules ? field.displayRules : null,
				hideLabel: false,
				watchers: field.watchers ? field.watchers : [],
			};

			switch (field.type) {
				case 'choice':
					parameter.options = field.choices.map((choice) => ({
						value: choice.value,
						label: choice.label,
					}));

					if (field.multiple || (field.research && field.research.controller && field.research.method)) {
						let asyncRoute = '';
						let asyncController = '';
						let asyncAttributes = null;
						if (field.research) {
							if (field.research.controller) {
								asyncController = field.research.controller;
							}

							if (field.research.method) {
								asyncRoute = field.research.method;
							}

							if (field.research.params) {
								asyncAttributes = {};
								Object.keys(field.research.params).forEach((key) => {
									asyncAttributes[key] = field.research.params[key];
								});
							} else {
								asyncAttributes = [field.name];
							}
						}

						parameter.multiple = field.multiple;
						parameter.type = 'multiselect';
						parameter.multiselectOptions = {
							options: parameter.options,
							noOptions: false,
							multiple: field.multiple,
							taggable: false,
							searchable: true,
							internalSearch: true,
							asyncRoute: asyncRoute,
							asyncController: asyncController,
							asyncAttributes: asyncAttributes,
							optionsLimit: 100,
							optionsPlaceholder: 'COM_EMUNDUS_MULTISELECT_ADDKEYWORDS',
							selectLabel: 'PRESS_ENTER_TO_SELECT',
							selectGroupLabel: 'PRESS_ENTER_TO_SELECT_GROUP',
							selectedLabel: 'SELECTED',
							deselectedLabel: 'PRESS_ENTER_TO_REMOVE',
							deselectGroupLabel: 'PRESS_ENTER_TO_DESELECT_GROUP',
							noOptionsText: 'COM_EMUNDUS_MULTISELECT_NOKEYWORDS',
							noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
							// Can add tag validations (ex. email, phone, regex)
							tagValidations: [],
							tagRegex: '',
							trackBy: 'value',
							label: 'label',
						};
					} else {
						parameter.multiple = false;
						parameter.type = 'select';
					}
					break;
				case 'date':
					parameter.type = 'datetime';
					break;
				case 'numeric':
					parameter.type = 'number';
					parameter.max = field.max ? field.max : null;
					parameter.min = field.min ? field.min : null;
					break;
				case 'password':
					parameter.type = 'password';
					break;
				default:
					parameter.type = 'text';
					break;
			}

			return parameter;
		},

		async fieldsToParameterFormGroups(fields, values, display = 'block') {
			let defaultGroup = {
				id: 'default-group',
				title: '',
				description: '',
				parameters: [],
				display: display,
				isRepeatable: false,
			};
			let groups = [];

			if (Array.isArray(values)) {
				values = {};
			}

			const asyncRequests = [];

			fields.forEach((param) => {
				if (param.type === 'choice' && param.choices.length === 0 && param.optionsProvider) {
					let dependenciesValues = {};
					if (param.optionsProvider.dependencies.length > 0) {
						Object.keys(values).forEach((key) => {
							if (param.optionsProvider.dependencies.includes(key)) {
								if (values[key] !== null && values[key] !== undefined) {
									if (typeof values[key] === 'object' && values[key].hasOwnProperty('value')) {
										dependenciesValues[key] = values[key].value;
									} else {
										dependenciesValues[key] = values[key];
									}
								} else {
									dependenciesValues[key] = null;
								}
							}
						});
					}

					if (param.optionsProvider.method && param.optionsProvider.controller) {
						const req = providerService
							.requestData(param.optionsProvider.controller, param.optionsProvider.method, dependenciesValues)
							.then((response) => {
								if (response.status) {
									param.choices = response.data.map((item) => ({
										value: item.value,
										label: item.label,
									}));
								} else {
									param.choices = [];
								}

								if (param.group) {
									// group is an object with name, label and isRepeatable
									let group = groups.find((g) => g.id === param.group.name);
									if (!group) {
										group = {
											id: param.group.name,
											title: param.group.label || '',
											description: param.group.description || '',
											parameters: [],
											isRepeatable: param.group.isRepeatable || false,
											display: display,
											rows: [],
										};
										groups.push(group);
									}

									group.parameters.push(this.fromFieldEntityToParameter(param, values[param.name] ?? null));
								} else {
									defaultGroup.parameters.push(this.fromFieldEntityToParameter(param, values[param.name] ?? null));
								}
							})
							.catch((error) => {
								console.error(error);
							});

						asyncRequests.push(req);
					}
				} else {
					if (param.group) {
						// group is an object with name, label and isRepeatable
						let group = groups.find((g) => g.id === param.group.name);
						if (!group) {
							group = {
								id: param.group.name,
								title: param.group.label || '',
								description: param.group.description || '',
								parameters: [],
								isRepeatable: param.group.isRepeatable || false,
								display: display,
								rows: [],
							};
							groups.push(group);
						}

						group.parameters.push(this.fromFieldEntityToParameter(param, values[param.name] ?? null));
					} else {
						defaultGroup.parameters.push(this.fromFieldEntityToParameter(param, values[param.name] ?? null));
					}
				}
			});

			await Promise.all(asyncRequests);

			if (defaultGroup.parameters.length > 0) {
				groups.unshift(defaultGroup);
			}

			groups.forEach((group) => {
				if (group.isRepeatable) {
					if (values[group.id] && Array.isArray(values[group.id])) {
						group.rows = values[group.id].map((rowValues) => {
							return {
								parameters: group.parameters.map((parameter) => {
									let paramCopy = JSON.parse(JSON.stringify(parameter));
									// set value from rowValues
									if (rowValues && rowValues.hasOwnProperty(paramCopy.param)) {
										paramCopy.value = rowValues[paramCopy.param];
									} else {
										paramCopy.value = null;
									}
									return paramCopy;
								}),
							};
						});
					} else {
						group.rows = [];
					}
				}
			});

			return groups;
		},

		async provideParameterOptions(param, values) {
			let dependenciesValues = {};
			if (param.optionsProvider.dependencies.length > 0) {
				console.log(Object.keys(values), 'object keys');
				console.log(values, 'values');

				Object.keys(values).forEach((key) => {
					if (param.optionsProvider.dependencies.includes(key)) {
						if (values[key] !== null && values[key] !== undefined) {
							if (typeof values[key] === 'object' && values[key].hasOwnProperty('value')) {
								dependenciesValues[key] = values[key].value;
							} else {
								dependenciesValues[key] = values[key];
							}
						} else {
							dependenciesValues[key] = null;
						}
					}
				});
			}

			if (param.optionsProvider.method && param.optionsProvider.controller) {
				const promise = providerService
					.requestData(param.optionsProvider.controller, param.optionsProvider.method, dependenciesValues)
					.then((response) => {
						if (response.status) {
							param.choices = response.data.map((item) => ({
								value: item.value,
								label: item.label,
							}));
						}

						return param.choices;
					})
					.catch((error) => {
						console.error(error);
						return [];
					});

				// await the promise and return the result
				return await promise;
			} else {
				return [];
			}
		},
	},
};
