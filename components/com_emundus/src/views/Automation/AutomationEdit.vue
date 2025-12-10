<script>
import Parameter from '@/components/Utils/Parameter.vue';
import Back from '@/components/Utils/Back.vue';
import automationService from '@/services/automation.js';
import alert from '@/mixins/alerts.js';
import AlertError from '@/errors/AlertError';
import AutomationAction from '@/components/Automation/AutomationAction.vue';
import AutomationActionsList from '@/components/Automation/AutomationActionsList.vue';
import AutomationConditionGroup from '@/components/Automation/AutomationConditionGroup.vue';
import Modal from '@/components/Modal.vue';
import AutomationEvent from '@/components/Automation/AutomationEvent.vue';
import AutomationEventsList from '@/components/Automation/AutomationEventsList.vue';
import { newConditionGroup } from '@/components/Automation/conditionGroup.js';
import { useAutomationStore } from '@/stores/automation.js';

export default {
	name: 'AutomationEdit',
	components: {
		AutomationEventsList,
		AutomationEvent,
		AutomationActionsList,
		AutomationAction,
		AutomationConditionGroup,
		Parameter,
		Back,
		Modal,
	},
	props: {
		automation: {
			type: Object,
			required: true,
		},
		operators: {
			type: Array,
			required: true,
		},
		operatorsFieldMapping: {
			type: Object,
			required: true,
		},
		events: {
			type: Array,
			required: true,
		},
		targetPredefinitions: {
			type: Array,
			default: () => [],
		},
		eventDefinitions: {
			type: Array,
			required: true,
		},
	},
	mixins: [alert],
	data() {
		return {
			actions: [],
			conditionsList: [],
			fields: [
				{
					param: 'name',
					type: 'text',
					maxlength: 255,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_AUTOMATION_NAME',
					helptext: '',
					displayed: true,
				},
				{
					param: 'description',
					type: 'textarea',
					maxlength: 1000,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_AUTOMATION_DESCRIPTION',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'published',
					type: 'toggle',
					placeholder: '',
					value: 0,
					trueValue: 1,
					falseValue: 0,
					label: 'COM_EMUNDUS_AUTOMATION_PUBLISHED',
					helptext: '',
					displayed: true,
					optional: true,
					hideLabel: true,
				},
			],
		};
	},
	created() {
		const automationStore = useAutomationStore();
		automationStore.setOperators(this.operators);
		automationStore.setOperatorsFieldMapping(this.operatorsFieldMapping);
		automationStore.setEvents(this.events);
		automationStore.setEventDefinitions(this.eventDefinitions);

		this.getActionsList();
		this.getConditionsList();

		this.fields.forEach((field) => {
			if (this.automation[field.param] !== undefined) {
				field.value = this.automation[field.param];
			}
		});

		if (this.automation.conditions_groups.length === 0) {
			this.automation.conditions_groups.push(newConditionGroup());
		}
	},
	methods: {
		getActionsList() {
			automationService.getActionsList().then((response) => {
				if (response && response.status && response.data) {
					this.actions = response.data;
				} else {
					this.alertError('COM_EMUNDUS_AUTOMATION_ACTIONS_ERROR', response.msg);
				}
			});
		},
		getConditionsList() {
			const eventId = this.automation && this.automation.event !== null ? this.automation.event.id : null;

			automationService.getConditionsList(eventId, this.automation.id).then((response) => {
				if (response && response.status && response.data) {
					this.conditionsList = response.data;
					useAutomationStore().setConditionsList(this.conditionsList);
				} else {
					this.alertError('COM_EMUNDUS_AUTOMATION_CONDITIONS_ERROR', response.msg);
				}
			});
		},
		onSelectEvent(event) {
			this.automation.event = event;

			if (this.automation.event) {
				this.getConditionsList();

				// todo: put this in cache and store to avoid multiple calls for the same event
				automationService
					.getConditionsFields('context_data', { eventName: this.automation.event.name })
					.then((response) => {
						if (response && response.status && response.data) {
							this.conditionsList = this.conditionsList.map((condition) => {
								if (condition.targetType === 'context_data') {
									condition.fields = response.data;
								}
								return condition;
							});
						} else {
							this.alertError('COM_EMUNDUS_AUTOMATION_CONDITIONS_ERROR', response.msg);
						}
					});
			}

			this.closeModal('eventsListModal');
		},
		onSelectAction(action) {
			this.closeModal('actionsListModal');
			this.automation.actions.push({
				id: Math.floor(Math.random() * 1000000000),
				...action,
				parameter_values: {},
				targets: [],
			});
		},
		addConditionGroup(group = null) {
			if (group === null) {
				group = newConditionGroup(this.automation.conditions_groups[0].id);
			}

			this.automation.conditions_groups[0].subGroups.push(group);
		},
		onRemoveAction(action) {
			this.automation.actions = this.automation.actions.filter((a) => a.id !== action.id);
		},
		onRemoveConditionGroup(group) {
			if (group.parent_id === null || group.parent_id === undefined || group.parent_id === 0) {
				// do nothing, top level groups cannot be removed this way
				return;
			}

			this.automation.conditions_groups.foreach((g) => {
				if (g.id === group.parent_id) {
					g.subGroups = g.subGroups.filter((sg) => sg.id !== group.id);
				}
			});
		},
		onRemoveCondition(groupId, condition) {
			// find the group
			const group = this.automation.conditions_groups.find((g) => g.id === groupId);
			if (group) {
				group.conditions = group.conditions.filter((c) => c.id !== condition.id);
			}
		},
		onChangeOperator(groupId, newOperator) {
			// find the group
			const group = this.automation.conditions_groups.find((g) => g.id === groupId);
			if (group) {
				group.operator = newOperator;
			}
		},
		openModal(refName) {
			if (this.$refs[refName]) {
				this.$refs[refName].open();

				this.$nextTick(() => {
					// Focus the search input when the component is mounted
					const searchInput = this.$refs[refName].$el.querySelector('#search-input, #search-inputx');
					if (searchInput) {
						searchInput.focus();
					}
				});
			}
		},
		closeModal(refName) {
			if (this.$refs[refName]) {
				this.$refs[refName].close();
			}
		},
		onParameterValueUpdated(parameter) {
			switch (parameter.type) {
				case 'multiselect':
					this.automation[parameter.param] = parameter.value.map((item) => item.value);
					break;
				default:
					this.automation[parameter.param] = parameter.value;
					break;
			}
		},
		verifyRequiredFieldsFilled() {
			this.fields.forEach((field) => {
				if (field.displayed && !field.optional) {
					if (
						this.automation[field.param] === undefined ||
						this.automation[field.param] === null ||
						this.automation[field.param] === ''
					) {
						throw new AlertError(
							'COM_EMUNDUS_AUTOMATION_REQUIRED_FIELD_ERROR',
							this.translate('COM_EMUNDUS_AUTOMATION_REQUIRED_FIELD_ERROR_DESC') + this.translate(field.label),
						);
					}
				}
			});

			if (this.automation.event === null || this.automation.event === undefined || this.automation.event.id < 1) {
				throw new AlertError('COM_EMUNDUS_AUTOMATION_NO_EVENT_ERROR', 'COM_EMUNDUS_AUTOMATION_NO_EVENT_ERROR_DESC');
			}

			this.verifyActionsNotEmpty();
		},
		verifyActionsNotEmpty() {
			if (this.automation.actions.length === 0) {
				throw new AlertError('COM_EMUNDUS_AUTOMATION_NO_ACTIONS_ERROR', 'COM_EMUNDUS_AUTOMATION_NO_ACTIONS_ERROR_DESC');
			}

			this.automation.actions.forEach((action, index) => {
				action.parameters.forEach((param) => {
					// todo: consider display rules
					if (param.displayed === false || param.hidden === true || param.displayRules.length > 0) {
						return;
					}

					if (param.group && param.group.isRepeatable) {
						if (action.parameter_values[param.group.name]) {
							action.parameter_values[param.group.name].forEach((row, rowIndex) => {
								if (
									param.required &&
									(row[param.name] === undefined || row[param.name] === null || row[param.name] === '')
								) {
									throw new AlertError(
										'COM_EMUNDUS_AUTOMATION_ACTION_PARAM_REQUIRED_ERROR',
										`${param.label} (Row ${rowIndex + 1})`,
									);
								}
							});
						}
					} else if (
						param.required &&
						(action.parameter_values[param.name] === undefined ||
							action.parameter_values[param.name] === null ||
							action.parameter_values[param.name] === '')
					) {
						throw new AlertError('COM_EMUNDUS_AUTOMATION_ACTION_PARAM_REQUIRED_ERROR', param.label);
					}
				});

				if (action.supported_target_types.length > 0) {
					if (action.targets.length === 0) {
						throw new AlertError(
							'COM_EMUNDUS_AUTOMATION_ACTION_NO_TARGETS_ERROR',
							this.translate('COM_EMUNDUS_AUTOMATION_ACTION_NO_TARGETS_ERROR_DESC'),
						);
					}
				}
			});
		},
		saveAutomation() {
			try {
				this.verifyRequiredFieldsFilled();
			} catch (error) {
				if (error instanceof AlertError) {
					this.alertError(error.getMessage(), error.getDescription());
				} else {
					throw error;
				}

				return;
			}

			automationService.saveAutomation(this.automation).then((response) => {
				if (response && response.status) {
					this.$emit('automation-saved', response.data);
					this.alertSuccess('COM_EMUNDUS_AUTOMATION_SAVED').then(() => {
						if (this.automation.id != response.data.id && response.redirect) {
							window.location.href = response.redirect;
						}
					});
				} else {
					this.alertError('COM_EMUNDUS_AUTOMATION_SAVE_ERROR', response.msg);
				}
			});
		},
	},
	computed: {
		displayedFields() {
			return this.fields.filter((field) => field.displayed);
		},
		firstLevelConditionGroups() {
			return this.automation.conditions_groups.filter(
				(group) => group.parent_id === null || group.parent_id === undefined || group.parent_id === 0,
			);
		},
	},
};
</script>

<template>
	<div
		id="automation"
		class="tw-mb-4 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<Back :link="'index.php?option=com_emundus&view=automation'" class="tw-mb-4" />
		<h1 v-if="automation.id > 0">{{ translate('COM_EMUNDUS_AUTOMATION_EDIT') }}</h1>
		<h1 v-else>{{ translate('COM_EMUNDUS_AUTOMATION_ADD') }}</h1>
		<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-2">
			<Parameter
				v-for="field in displayedFields"
				:key="field.reload + field.param"
				:class="{ 'tw-w-full': field.param === 'name' }"
				:ref="'event_' + field.param"
				:parameter-object="field"
				:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
				:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
				@valueUpdated="onParameterValueUpdated"
			/>
		</div>

		<hr class="tw-mt-8" />
		<h2 class="tw-mb-4 tw-text-yellow-600">{{ translate('COM_EMUNDUS_AUTOMATION_EVENT_TITLE') }}</h2>
		<AutomationEvent v-if="automation.event" :event="automation.event" />
		<p v-else>{{ translate('COM_EMUNDUS_AUTOMATION_PLEASE_SELECT_EVENT') }}</p>
		<Modal
			:name="'events-list-modal'"
			ref="eventsListModal"
			:title="translate('COM_EMUNDUS_AUTOMATION_SELECT_EVENT')"
			:title-classes="'tw-text-yellow-600'"
			:center="true"
			:open-on-create="false"
			:width="'80%'"
			:click-to-close="true"
			:classes="'tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card tw-max-h-full tw-overflow-y-auto'"
		>
			<AutomationEventsList :events="events" @select-event="onSelectEvent" @close="closeModal('eventsListModal')" />
		</Modal>

		<div class="tw-flex tw-w-full tw-flex-row tw-justify-end">
			<button
				class="not-to-close-modal tw-btn-primary tw-mt-4 tw-border-yellow-600 tw-bg-yellow-600 tw-text-white"
				@click="openModal('eventsListModal')"
			>
				{{ translate('COM_EMUNDUS_AUTOMATION_SELECT_EVENT') }}
			</button>
		</div>
		<hr class="tw-mt-8" />
		<h2 class="tw-mb-4 tw-text-orange-600">{{ translate('COM_EMUNDUS_AUTOMATION_CONDITIONS') }}</h2>
		<div
			v-if="automation.conditions_groups.length > 0 && conditionsList.length > 0"
			class="tw-flex tw-flex-col tw-gap-4"
		>
			<AutomationConditionGroup
				v-for="conditionGroup in firstLevelConditionGroups"
				:key="conditionGroup.id"
				:condition-group="conditionGroup"
				:conditions-list="conditionsList"
				:operators="operators"
				:operatorsFieldMapping="operatorsFieldMapping"
				@remove-condition-group="onRemoveConditionGroup"
				@remove-condition="onRemoveCondition"
				@operator-change="onChangeOperator"
			/>
		</div>
		<hr class="tw-mt-8" />
		<h2 class="tw-mb-4 tw-text-blue-500">{{ translate('COM_EMUNDUS_AUTOMATION_ACTIONS') }}</h2>
		<div v-if="actions.length > 0" class="tw-flex tw-flex-col tw-gap-4">
			<AutomationAction
				v-for="action in automation.actions"
				:key="action.id"
				:event="automation.event"
				:action="action"
				:target-predefinitions="targetPredefinitions"
				@remove-action="onRemoveAction"
			/>
			<p v-if="automation.actions.length < 1">{{ translate('COM_EMUNDUS_AUTOMATION_NO_ACTIONS') }}</p>

			<div class="tw-flex tw-w-full tw-flex-row tw-justify-end">
				<button
					id="add-action"
					@click="openModal('actionsListModal')"
					class="not-to-close-modal tw-btn-primary-blue tw-btn-primary tw-text-white"
				>
					{{ translate('COM_EMUNDUS_AUTOMATION_ADD_ACTION') }}
				</button>
			</div>
			<Modal
				:name="'actions-list-modal'"
				ref="actionsListModal"
				:title="translate('COM_EMUNDUS_AUTOMATION_ADD_ACTION')"
				:title-classes="'tw-text-blue-500'"
				:center="true"
				:open-on-create="false"
				:width="'80%'"
				:classes="'tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card'"
			>
				<AutomationActionsList
					:actions="actions"
					@select-action="onSelectAction"
					@close="closeModal('actionsListModal')"
				/>
			</Modal>
		</div>

		<div class="tw-mt-4 tw-flex tw-w-full tw-flex-row tw-justify-between">
			<Back :link="'index.php?option=com_emundus&view=automation'" class="tw-mb-4" />
			<button class="tw-btn-primary" @click="saveAutomation">
				{{ translate('COM_EMUNDUS_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style>
.tw-btn-primary-orange {
	background-color: var(--orange-600) !important;
	border-color: var(--orange-600) !important;
	color: white;

	&:hover {
		background-color: white !important;
		color: var(--orange-600) !important;
	}
}

.tw-btn-secondary-orange {
	background-color: white !important;
	border-color: var(--orange-600) !important;
	color: var(--orange-600) !important;

	&:hover {
		background-color: var(--orange-600) !important;
		color: white !important;
	}
}

.tw-btn-primary-blue {
	background-color: var(--blue-500) !important;
	border-color: var(--blue-500) !important;
	color: white;

	&:hover {
		background-color: white !important;
		color: var(--blue-500) !important;
	}
}

.tw-btn-secondary-blue {
	background-color: white !important;
	border-color: var(--blue-500) !important;
	color: var(--blue-500) !important;

	&:hover {
		background-color: var(--blue-500) !important;
		color: white !important;
	}
}
</style>
