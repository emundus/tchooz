<script>
import Parameter from '@/components/Utils/Parameter.vue';
import Info from '@/components/Utils/Info.vue';

import settingsService from '@/services/settings';
import { useGlobalStore } from '@/stores/global.js';

export default {
	name: 'Messenger',
	components: { Parameter, Info },
	emits: ['messengerSaved'],
	props: {
		addon: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			loading: false,

			fields: [
				{
					param: 'messenger_anonymous_coordinator',
					type: 'toggle',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_ANONYMOUS_COORDINATOR',
					displayed: true,
					hideLabel: true,
					optional: true,
				},
				{
					param: 'messenger_notifications_on_send',
					type: 'toggle',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_SEND_SUMMARY_EMAILS',
					displayed: true,
					hideLabel: true,
					optional: true,
				},
				{
					param: 'messenger_notify_frequency',
					type: 'select',
					placeholder: '',
					value: 'daily',
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_FREQUENCY',
					helptext: '',
					displayed: false,
					displayedOn: 'messenger_notifications_on_send',
					displayedOnValue: 1,
					options: [
						{
							label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_FREQUENCY_DAILY',
							value: 'daily',
						},
						{
							label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_FREQUENCY_WEEKLY',
							value: 'weekly',
						},
						{
							label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_FREQUENCY_CUSTOM',
							value: 'custom',
						},
					],
					reload: 0,
					optional: true,
				},
				{
					param: 'messenger_notify_frequency_custom',
					type: 'text',
					placeholder: '',
					value: '',
					concatValue: '',
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_FREQUENCY_CUSTOM_FIELD',
					helptext: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_FREQUENCY_CUSTOM_FIELD_HELPTEXT',
					displayed: false,
					displayedOn: 'messenger_notify_frequency',
					displayedOnValue: 'custom',
					optional: true,
					splitField: true,
					secondParameterType: 'select',
					secondParameterDefault: 'daily',
					secondParameterOptions: [
						{
							value: 'daily',
							label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_FREQUENCY_CUSTOM_FIELD_DAYS',
						},
						{
							value: 'weekly',
							label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_FREQUENCY_CUSTOM_FIELD_WEEKS',
						},
					],
					splitChar: ' ',
				},
				{
					param: 'messenger_notify_groups',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getavailablegroups',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [],
						optionsLimit: 30,
						label: 'label',
						trackBy: 'value',
					},
					value: [],
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_USERS_WITH_GROUPS',
					helptext: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_USERS_WITH_GROUPS_HELPTEXT',
					displayed: false,
					displayedOn: 'messenger_notifications_on_send',
					displayedOnValue: 1,
					optional: true,
				},
				{
					param: 'messenger_notify_users',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getavailablemanagers',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [],
						optionsLimit: 30,
						label: 'name',
						trackBy: 'value',
					},
					value: 0,
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_USERS',
					displayed: false,
					displayedOn: 'messenger_notifications_on_send',
					displayedOnValue: 1,
					optional: true,
				},
				{
					param: 'messenger_notify_users_programs',
					type: 'toggle',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_NOTIFY_USERS_ASSOCIATED',
					displayed: false,
					displayedOn: 'messenger_notifications_on_send',
					displayedOnValue: 1,
					hideLabel: true,
					optional: true,
				},
			],

			emailLink: '',
		};
	},
	created() {
		let configuration = JSON.parse(this.addon.configuration);

		this.fields.forEach((field) => {
			field.value = configuration[field.param] || '';
		});

		settingsService
			.redirectJRoute('index.php?option=com_emundus&view=emails', useGlobalStore().getCurrentLang, false)
			.then((response) => {
				console.log(response);
				this.emailLink = response;
			});
	},
	methods: {
		setupMessenger() {
			let data = {};
			for (let field of this.fields) {
				if (field.concatValue) {
					data[field.param] = field.concatValue;
				} else {
					data[field.param] = field.value;
				}
			}

			let customFrequency = this.fields.find((field) => field.param === 'messenger_notify_frequency_custom');
			if (data['messenger_notify_frequency'] === 'custom' && customFrequency) {
				if (customFrequency.concatValue.includes('daily')) {
					if (customFrequency.value > 24) {
						Swal.fire({
							icon: 'error',
							title: this.translate('COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_SETUP_FREQUENCY_ERROR'),
							text: this.translate('COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_SETUP_FREQUENCY_ERROR_DAILY_DESC'),
						});
						return;
					}
				} else {
					if (customFrequency.value > 7) {
						Swal.fire({
							icon: 'error',
							title: this.translate('COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_SETUP_FREQUENCY_ERROR'),
							text: this.translate('COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_SETUP_FREQUENCY_ERROR_WEEKLY_DESC'),
						});
						return;
					}
				}
			}

			this.loading = true;
			settingsService.setupMessenger(data).then((response) => {
				if (response.status) {
					Swal.fire({
						icon: 'success',
						title: this.translate('COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_SETUP_SUCCESS'),
						text: this.translate('COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_SETUP_SUCCESS_DESC'),
						showConfirmButton: false,
						timer: 3000,
					}).then(() => {
						this.$emit('messengerSaved');
					});
				}

				this.loading = false;
			});
		},

		checkConditional(parameter, oldValue, value) {
			// Find all fields that are displayed based on the current field
			let fields = this.fields.filter((field) => field.displayedOn === parameter.param);

			// Check if the current field is displayed based on the value
			for (let field of fields) {
				field.displayed = field.displayedOnValue == value;
				if (!field.displayed) {
					if (field.default) {
						field.value = field.default;
					} else {
						field.value = '';
					}
					this.checkConditional(field, field.value, '');
				}
			}
		},
	},
	computed: {
		disabledSubmit: function () {
			return this.fields.some((field) => {
				if (!field.optional) {
					return field.value === '' || field.value === 0;
				} else {
					return false;
				}
			});
		},

		emailsShortcuts() {
			return this.translate('COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_NOTIFICATIONS_EMAIL_SHORTCUT').replace(
				'{{emailLink}}',
				this.emailLink,
			);
		},
	},
};
</script>

<template>
	<div
		class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<h3>{{ translate('COM_EMUNDUS_SETTINGS_ADDONS_MESSENGER_SETUP') }}</h3>

		<div class="tw-mt-2">
			<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
				<div v-for="field in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
					<Parameter
						:ref="'teams_' + field.param"
						:parameter-object="field"
						:help-text-type="'above'"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
						@valueUpdated="checkConditional"
					/>

					<Info class="tw-mt-2" v-if="field.param === 'messenger_notifications_on_send'" :text="emailsShortcuts" />
				</div>

				<div>
					<button class="tw-btn-primary tw-float-right tw-w-fit" :disabled="disabledSubmit" @click="setupMessenger()">
						<span>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE_BUTTON') }}</span>
					</button>
				</div>
			</div>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<style scoped></style>
