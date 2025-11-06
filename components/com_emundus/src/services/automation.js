import { FetchClient } from './fetchClient.js';

const client = new FetchClient('automation');

export default {
	async getEventsList() {
		try {
			return await client.get('getEventsList');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getActionsList() {
		try {
			return await client.get('getActionsList');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getConditionsList(eventId, automationId = null) {
		try {
			return await client.get('getConditionsList', { event_id: eventId, automation_id: automationId });
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getTargetConditionsList(type, contextPossessFile = false) {
		try {
			return await client.get('getTargetConditionsList', {
				type: type,
				context_possess_file: contextPossessFile ? 1 : 0,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getConditionsFields(type, parameters = {}) {
		try {
			return await client.get('getConditionsFields', {
				type: type,
				parameters: JSON.stringify(parameters),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async saveAutomation(automation) {
		if (automation.name.length < 1 || automation.event === null || automation.actions.length < 1) {
			return {
				status: false,
				msg: 'MISSING_REQUIRED_FIELDS',
			};
		}

		let automationObject = {
			id: automation.id,
			name: automation.name,
			description: automation.description,
			published: automation.published,
			event: automation.event.id,
			conditions_groups: automation.conditions_groups.map((group) => {
				return {
					id: group.id,
					operator: group.operator,
					parent_id: group.parent_id,
					conditions: group.conditions.map((condition) => {
						let value = null;
						// if condition value is an array of objects with value and label, convert it to an array of values
						if (
							Array.isArray(condition.value) &&
							condition.value.length > 0 &&
							typeof condition.value[0] === 'object' &&
							condition.value[0] !== null &&
							'value' in condition.value[0]
						) {
							value = condition.value.map((v) => v.value);
						}

						return {
							id: condition.id,
							type: condition.type,
							value: value !== null ? value : condition.value,
							target:
								typeof condition.target === 'object' && 'value' in condition.target
									? condition.target.value
									: condition.target,
							operator: condition.operator,
							group_id: condition.group_id,
						};
					}),
				};
			}),
			actions: automation.actions.map((action) => {
				return {
					id: action.id,
					type: action.type,
					parameter_values: action.parameter_values,
					targets: action.targets.map((target) => {
						return {
							id: target.id,
							type: target.type,
							predefinition: target.predefinition ? target.predefinition.name : '',
							conditions: target.conditions.map((condition) => {
								let value = null;
								// if condition value is an array of objects with value and label, convert it to an array of values
								if (
									Array.isArray(condition.value) &&
									condition.value.length > 0 &&
									typeof condition.value[0] === 'object' &&
									condition.value[0] !== null &&
									'value' in condition.value[0]
								) {
									value = condition.value.map((v) => v.value);
								}

								return {
									id: condition.id,
									type: condition.type,
									value: value !== null ? value : condition.value,
									target:
										typeof condition.target === 'object' && 'value' in condition.target
											? condition.target.value
											: condition.target,
									operator: condition.operator,
									group_id: condition.group_id,
								};
							}),
						};
					}),
				};
			}),
		};

		try {
			return await client.post('saveAutomation', {
				automation: JSON.stringify(automationObject),
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
