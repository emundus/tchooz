// stores/automation.js
import { defineStore } from 'pinia';

export const useAutomationStore = defineStore('automation', {
	state: () => ({
		operators: [],
		operatorsFieldMapping: {},
		events: [],
		eventDefinitions: [],
		conditionsList: [],
	}),
	actions: {
		setOperators(operators) {
			this.operators = operators;
		},
		setOperatorsFieldMapping(mapping) {
			this.operatorsFieldMapping = mapping;
		},
		setEvents(events) {
			this.events = events;
		},
		setEventDefinitions(definitions) {
			this.eventDefinitions = definitions;
		},
		setConditionsList(conditions) {
			this.conditionsList = conditions;
		},
	},
	getters: {
		getEventDefinitionByName(state) {
			return (name) => state.eventDefinitions.find((def) => def.name === name);
		},
		doesEventContainsFile(state) {
			return (eventName) => {
				const eventDef = state.eventDefinitions.find((def) => def.name === eventName);
				return eventDef && eventDef.supportsTargetPredefinitionsCategories
					? eventDef.supportsTargetPredefinitionsCategories.includes('file')
					: false;
			};
		},
		doesEventContainsFromCategories(state) {
			return (eventName, predefinitionFromCategories) => {
				const eventDef = state.eventDefinitions.find((def) => def.name === eventName);
				// at least one category should match
				return eventDef && eventDef.supportsTargetPredefinitionsCategories
					? eventDef.supportsTargetPredefinitionsCategories.some((category) =>
							predefinitionFromCategories.includes(category),
						)
					: false;
			};
		},
	},
});
