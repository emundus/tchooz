<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import AutomationConditionGroup from '@/components/Automation/AutomationConditionGroup.vue';
import AutomationAction from '@/components/Automation/AutomationAction.vue';
import { useAutomationStore } from '@/stores/automation.js';
import AutomationActionsList from '@/components/Automation/AutomationActionsList.vue';
import Modal from '@/components/Modal.vue';

export default {
	name: 'ApplicationFileCustomAction',
	components: {
		Modal,
		AutomationActionsList,
		AutomationAction,
		AutomationConditionGroup,
		ParameterForm,
	},
	props: {
		customAction: {
			type: Object,
			required: true,
		},
	},
	setup() {
		const automationStore = useAutomationStore();

		return {
			automationStore,
		};
	},
	created() {
		this.formGroups[0].parameters.forEach((parameter) => {
			if (this.customAction[parameter.param]) {
				parameter.value = this.customAction[parameter.param];
			}
		});
	},
	data() {
		return {
			formGroups: [
				{
					id: 'default-group',
					title: '',
					description: '',
					helpTextType: 'above',
					parameters: [
						{
							param: 'label',
							label: 'COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_LABEL',
							optional: false,
							value: '',
							displayed: true,
							type: 'text',
						},
						{
							param: 'icon',
							label: 'COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_ICON',
							optional: false,
							value: '',
							displayed: true,
							type: 'text',
						},
					],
				},
			],
		};
	},
	methods: {
		onRemoveAction() {
			this.customAction.action = null;
		},
		onSelectAction(action) {
			this.customAction.action = action;
		},
		closeModal(refName) {
			if (this.$refs[refName]) {
				this.$refs[refName].close();
			}
		},
		openModal(refName) {
			console.log(this.$refs[refName]);

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
	},
};
</script>

<template>
	<div class="custom-action tw-flex tw-flex-col tw-gap-4 tw-rounded-coordinator tw-p-4 tw-shadow">
		<div class="tw-flex tw-flex-row tw-justify-between">
			<h4>{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION') }}</h4>
			<span class="material-symbols-outlined tw-cursor-pointer tw-text-red-500">close</span>
		</div>
		<ParameterForm :groups="formGroups" />
		<AutomationConditionGroup
			:operators-field-mapping="automationStore.operatorsFieldMapping"
			:operators="automationStore.operators"
			:conditions-list="automationStore.conditionsList"
			:condition-group="customAction.conditions"
		/>
		<AutomationAction
			v-if="customAction.action !== null"
			:action="customAction.action"
			:event="{}"
			:target-predefinitions="[]"
			:customTargets="false"
			@remove-action="onRemoveAction"
		/>

		<div v-if="customAction.action === null">
			<button
				class="not-to-close-modal tw-btn-primary-blue tw-btn-primary tw-text-white"
				@click="openModal('actionsListModal')"
			>
				{{ translate('COM_EMUNDUS_AUTOMATION_ADD_ACTION') }}
			</button>

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
					:actions="automationStore.actionsList"
					@select-action="onSelectAction"
					@close="closeModal('actionsListModal')"
				/>
			</Modal>
		</div>
	</div>
</template>

<style scoped></style>
