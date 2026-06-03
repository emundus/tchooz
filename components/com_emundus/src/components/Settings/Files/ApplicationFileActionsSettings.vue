<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import SiteSettings from '@/components/Settings/SiteSettings.vue';
import ApplicationFileCustomAction from '@/components/Settings/Files/ApplicationFileCustomAction.vue';
import settingsService from '@/services/settings.js';
import { useAutomationStore } from '@/stores/automation.js';
import { newConditionGroup } from '@/components/Automation/conditionGroup.js';
import Info from '@/components/Utils/Info.vue';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'ApplicationFileActionsSettings',
	components: {
		Info,
		SiteSettings,
		ParameterForm,
		ApplicationFileCustomAction,
	},
	mixins: [alerts],
	data() {
		return {
			customActions: [],
		};
	},
	setup() {
		const automationStore = useAutomationStore();

		return {
			automationStore,
		};
	},
	mounted() {
		this.getAvailableConditionsForCustomActions();
		this.getCustomActions();
	},
	methods: {
		getAvailableConditionsForCustomActions() {
			settingsService.getAvailableConditionsForCustomActions().then((response) => {
				if (response && response.data) {
					this.automationStore.setConditionsList(response.data);
				}
			});
		},
		getCustomActions() {
			settingsService.getApplicationFileCustomActions().then((response) => {
				if (response && response.data) {
					this.customActions = response.data;
				}
			});
		},
		addCustomAction() {
			this.customActions.push({
				id: Math.floor(Math.random() * 1000000000),
				label: '',
				icon: '',
				conditions: newConditionGroup(0),
				action: null,
			});
		},
		onRemove(actionToRemove) {
			this.customActions = this.customActions.filter((action) => {
				return action.id != actionToRemove.id;
			});
		},
		save() {
			settingsService.saveApplicationFileCustomActions(this.customActions).then((response) => {
				if (response.status) {
					this.alertSuccess('COM_EMUNDUS_APPLICATION_FILE_CUSTOM_ACTIONS_SAVED');
				} else {
					this.alertError('COM_EMUNDUS_APPLICATION_FILE_CUSTOM_ACTIONS_FAILURE');
				}
			});
		},
	},
};
</script>

<template>
	<div>
		<SiteSettings :json_source="'settings/sections/file-actions-settings.js'" />

		<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-4" v-if="automationStore.conditionsList.length > 0">
			<h3>{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTIONS') }}</h3>

			<Info :text="'COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTIONS_INTRO'" />

			<ApplicationFileCustomAction
				v-for="action in customActions"
				:key="action.id"
				:customAction="action"
				@remove="onRemove"
			/>

			<div class="tw-flex tw-w-full tw-flex-row tw-justify-end">
				<button class="tw-btn-secondary" @click="addCustomAction">
					{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_ADD') }}
				</button>
			</div>

			<div class="tw-flex tw-w-full tw-flex-row tw-justify-end">
				<button class="tw-btn-primary" @click="save">
					{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTIONS_SAVE') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
