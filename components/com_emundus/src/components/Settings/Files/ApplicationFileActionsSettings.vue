<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import SiteSettings from '@/components/Settings/SiteSettings.vue';
import ApplicationFileCustomAction from '@/components/Settings/Files/ApplicationFileCustomAction.vue';
import settingsService from '@/services/settings.js';
import { useAutomationStore } from '@/stores/automation.js';

export default {
	name: 'ApplicationFileActionsSettings',
	components: {
		SiteSettings,
		ParameterForm,
		ApplicationFileCustomAction,
	},
	data() {
		return {
			customActions: [],
			customActionInstance: {
				id: 0,
				label: '',
				icon: '',
				conditionGroup: null,
				action: null,
			},
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
	},
};
</script>

<template>
	<div>
		<SiteSettings :json_source="'settings/sections/file-actions-settings.js'" />

		<div class="tw-mt-4 tw-flex tw-flex-col tw-gap-4" v-if="automationStore.conditionsList.length > 0">
			<h3>{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTIONS') }}</h3>

			<ApplicationFileCustomAction v-for="action in customActions" :key="action.id" :customAction="action" />

			<div class="tw-flex tw-w-full tw-flex-row tw-justify-end">
				<button class="tw-btn-secondary">
					{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_ADD') }}
				</button>
			</div>

			<div class="tw-flex tw-w-full tw-flex-row tw-justify-end">
				<button class="tw-btn-primary">
					{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTIONS_SAVE') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
