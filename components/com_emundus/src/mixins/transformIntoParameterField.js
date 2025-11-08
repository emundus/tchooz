export default {
	methods: {
		fromAutomationFieldToParameter(field, value = null) {
			if (!field) {
				return null;
			}

			let parameter = {
				param: field.name,
				label: field.label,
				optional: !field.required,
				value: value,
				displayed: true,
			};

			switch (field.type) {
				case 'choice':
					console.log(field, 'fromAutomationFieldToParameter');

					parameter.options = field.choices.map((choice) => ({
						value: choice.value,
						label: choice.label,
					}));

					if (field.multiple) {
						let asyncRoute = '';
						let asyncController = '';
						let asyncAttributes = [];
						if (field.research) {
							if (field.research.controller) {
								asyncController = field.research.controller;
							}

							if (field.research.method) {
								asyncRoute = field.research.method;
							}
							asyncAttributes.push(field.name);
						}

						parameter.multiple = true;
						parameter.type = 'multiselect';
						parameter.multiselectOptions = {
							options: parameter.options,
							noOptions: false,
							multiple: true,
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
				default:
					parameter.type = 'text';
					break;
			}

			return parameter;
		},
	},
};
