<script>
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import AutomationConditionGroup from '@/components/Automation/AutomationConditionGroup.vue';
import AutomationAction from '@/components/Automation/AutomationAction.vue';
import { useAutomationStore } from '@/stores/automation.js';
import { useGlobalStore } from '@/stores/global.js';
import AutomationActionsList from '@/components/Automation/AutomationActionsList.vue';
import Modal from '@/components/Modal.vue';
import settingsService from '@/services/settings.js';
import { Slider } from '@emundus/ui';

export default {
	name: 'ApplicationFileCustomAction',
	components: {
		Modal,
		AutomationActionsList,
		AutomationAction,
		AutomationConditionGroup,
		ParameterForm,
		Slider,
	},
	props: {
		customAction: {
			type: Object,
			required: true,
		},
	},
	setup() {
		const automationStore = useAutomationStore();
		const globalStore = useGlobalStore();

		return {
			automationStore,
			globalStore,
		};
	},
	created() {
		this.actualLanguage = this.globalStore.getShortLang;
		this.selectedLang = this.actualLanguage;

		this.normalizeLabel();

		this.formGroups[0].parameters.forEach((parameter) => {
			if (this.customAction[parameter.param]) {
				parameter.value = this.customAction[parameter.param];
			}
		});

		this.getLanguages();
	},
	data() {
		return {
			languages: [],
			actualLanguage: '',
			selectedLang: '',
			formGroups: [
				{
					id: 'default-group',
					title: '',
					description: '',
					helpTextType: 'above',
					parameters: [
						{
							param: 'icon',
							label: 'COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_ICON',
							optional: true,
							value: '',
							displayed: true,
							type: 'text',
							helptext: 'COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_ICON_HELPTEXT',
						},
					],
				},
			],
		};
	},
	computed: {
		languageOptions() {
			return this.languages.map((language) => {
				const countryCode = language.lang_code.split('-')[1]?.toLowerCase();
				return {
					label: this.stripLanguageSuffix(language.title_native),
					value: language.sef,
					lang_id: language.lang_id,
					icon: `flag_round_${countryCode}`,
				};
			});
		},
	},
	methods: {
		stripLanguageSuffix(label) {
			return label ? label.replace(/\s*\([^)]*\)\s*$/, '') : label;
		},
		normalizeLabel() {
			// Legacy custom actions stored the label as a plain string ; convert it to a
			// per-language object keyed by the language sef so it can be translated.
			if (typeof this.customAction.label !== 'object' || this.customAction.label === null) {
				const legacy = this.customAction.label || '';
				this.customAction.label = legacy ? { [this.actualLanguage]: legacy } : {};
			}
		},
		getLanguages() {
			settingsService.getActiveLanguages().then((response) => {
				if (response && response.data) {
					this.languages = response.data;

					// Ensure every active language has a (possibly empty) entry so v-model stays reactive.
					this.languages.forEach((language) => {
						if (typeof this.customAction.label[language.sef] === 'undefined') {
							this.customAction.label[language.sef] = '';
						}
					});
				}
			});
		},
		onRemove() {
			this.$emit('remove', this.customAction);
		},
		onRemoveAction() {
			this.customAction.action = null;
		},
		onSelectAction(action) {
			this.customAction.action = {
				id: Math.floor(Math.random() * 1000000000),
				...action,
				parameter_values: {},
			};
		},
		closeModal(refName) {
			if (this.$refs[refName]) {
				this.$refs[refName].close();
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
		onParameterUpdated(param, group, rowIndex) {
			if (this.customAction.hasOwnProperty(param.param)) {
				this.customAction[param.param] = param.value;
			}
		},
	},
};
</script>

<template>
	<div class="custom-action tw-flex tw-flex-col tw-gap-4 tw-rounded-coordinator tw-p-4 tw-shadow">
		<div class="tw-flex tw-flex-row tw-justify-between">
			<h4>{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION') }}</h4>
			<span class="material-symbols-outlined tw-cursor-pointer tw-text-red-500" @click="onRemove">close</span>
		</div>
		<div v-if="languageOptions.length > 1" class="tw-flex tw-items-center tw-gap-3">
			<label class="tw-mb-0 tw-whitespace-nowrap tw-font-medium">
				{{ translate('COM_EMUNDUS_ACTION_TRANSLATION') }}
			</label>
			<Slider v-model="selectedLang" :options="languageOptions" />
		</div>

		<div>
			<label class="tw-font-medium">
				{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_LABEL') }}
				<span class="tw-text-red-600">*</span>
			</label>
			<input
				type="text"
				v-model="customAction.label[selectedLang]"
				required
				class="form-control fabrikinput tw-mt-1 tw-w-full"
			/>
		</div>

		<ParameterForm :groups="formGroups" @parameterValueUpdated="onParameterUpdated" />

		<h5>
			{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_CONDITION_GROUP_TITLE') }}
		</h5>
		<AutomationConditionGroup
			:operators-field-mapping="automationStore.operatorsFieldMapping"
			:operators="automationStore.operators"
			:conditions-list="automationStore.conditionsList"
			:condition-group="customAction.conditions"
		/>

		<h5>
			{{ translate('COM_EMUNDUS_APPLICATIONS_CUSTOM_ACTION_TITLE') }}
		</h5>
		<AutomationAction
			v-if="customAction.action !== null"
			:action="customAction.action"
			:event="{}"
			:target-predefinitions="[]"
			:customTargets="false"
			@remove-action="onRemoveAction"
		/>

		<div v-if="customAction.action === null">
			<div class="tw-flex tw-w-full tw-flex-row tw-justify-end">
				<button
					class="not-to-close-modal tw-btn-primary-blue tw-btn-primary tw-text-white"
					@click="openModal('actionsListModal')"
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
					:actions="automationStore.actionsList"
					@select-action="onSelectAction"
					@close="closeModal('actionsListModal')"
				/>
			</Modal>
		</div>
	</div>
</template>

<style scoped></style>
