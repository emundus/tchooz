<script>
import Parameter from '@/components/Utils/Parameter.vue';

import settingsService from '@/services/settings';

import alerts from '@/mixins/alerts.js';

export default {
	name: 'MultifactorAuthentication',
	components: { Parameter },
	mixins: [alerts],
	data() {
		return {
			loading: true,

			fields: [
				{
					param: '2fa_available_methods',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: [
							{ value: 'totp', label: this.translate('COM_EMUNDUS_SETTINGS_2FA_AVAILABLE_METHODS_TOTP') },
							{ value: 'email', label: this.translate('COM_EMUNDUS_SETTINGS_2FA_AVAILABLE_METHODS_EMAIL') },
						],
						optionsLimit: 30,
						label: 'label',
						trackBy: 'value',
					},
					value: [],
					label: 'COM_EMUNDUS_SETTINGS_2FA_AVAILABLE_METHODS',
					helptext: 'COM_EMUNDUS_SETTINGS_2FA_AVAILABLE_METHODS_HELPTEXT',
					displayed: true,
				},
				{
					param: '2fa_force_for_profiles',
					type: 'toggle',
					placeholder: '',
					value: false,
					label: 'COM_EMUNDUS_SETTINGS_2FA_FORCE_FOR_PROFILES',
					displayed: true,
					hideLabel: true,
					optional: true,
					reload: 0,
				},
				{
					param: '2fa_mandatory_profiles',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: 'getavailableprofiles',
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
					value: [],
					label: 'COM_EMUNDUS_SETTINGS_2FA_USERS_WITH_PROFILES',
					helptext: 'COM_EMUNDUS_SETTINGS_2FA_USERS_WITH_PROFILES_HELPTEXT',
					displayed: false,
					displayedOn: '2fa_force_for_profiles',
					displayedOnValue: 1,
					optional: true,
				},
				{
					param: '2fa_for_sso',
					type: 'toggle',
					placeholder: '',
					value: false,
					label: 'COM_EMUNDUS_SETTINGS_2FA_FOR_SSO',
					displayed: true,
					hideLabel: true,
					optional: true,
					displayedOn: '2fa_force_for_profiles',
					displayedOnValue: 1,
					reload: 0,
				},
			],

			profiles: [],
		};
	},
	created() {
		this.init();
	},
	methods: {
		init() {
			this.loading = true;

			settingsService.get2faEnableMethods().then((response) => {
				if (response.status) {
					this.fields[0].value = this.fields[0].multiselectOptions.options.filter((option) =>
						response.data.includes(option.value),
					);

					settingsService.get2faParameters().then((paramsResponse) => {
						if (paramsResponse.status) {
							this.profiles = paramsResponse.data.profiles;
							this.fields[2].value = paramsResponse.data.mfaForSso;

							this.loading = false;
						}
					});
				}
			});
		},

		save2fa() {
			this.loading = true;

			let data = {};
			for (let field of this.fields) {
				if (field.type === 'multiselect' && field.value.length > 0) {
					data[field.param] = field.value.map((item) => item.value);
				} else {
					data[field.param] = field.value;
				}
			}

			settingsService
				.save2faConfig(data)
				.then((response) => {
					if (response.status) {
						Swal.fire({
							icon: 'success',
							title: this.translate('COM_EMUNDUS_SETTINGS_2FA_SETUP_SUCCESS'),
							showConfirmButton: false,
							timer: 3000,
						}).then(() => {
							this.init();
						});
					} else {
						this.alertError('COM_EMUNDUS_ONBOARD_SETTINGS_2FA_SAVE_ERROR', response.message);
					}
				})
				.catch(() => {
					this.alertError('COM_EMUNDUS_ONBOARD_SETTINGS_2FA_SAVE_ERROR');
					this.loading = false;
				});
		},

		onAjaxOptionsLoaded(options, parameter_name) {
			// Add applicant option
			if (parameter_name === '2fa_mandatory_profiles') {
				let applicantOption = {
					value: 'applicant',
					name: this.translate('COM_EMUNDUS_SETTINGS_2FA_APPLICANT'),
				};
				options.push(applicantOption);

				this.fields.find((field) => field.param === '2fa_mandatory_profiles').multiselectOptions.options = options;

				if (this.profiles.length > 0) {
					this.fields.find((field) => field.param === '2fa_mandatory_profiles').value = options.filter((option) =>
						this.profiles.includes(option.value),
					);

					this.fields[1].value = '1';

					// Check conditional fields
					this.checkConditional(this.fields[1], this.fields[1].value, this.fields[1].value);

					this.fields[1].reload++;
				}
			}
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
};
</script>

<template>
	<div class="tw-mt-2">
		<div class="tw-flex tw-flex-col tw-gap-6" v-if="!loading">
			<div v-for="field in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
				<Parameter
					:ref="'2fa_' + field.param"
					:parameter-object="field"
					:help-text-type="'above'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
					:key="field.reload"
					@valueUpdated="checkConditional"
					@ajax-options-loaded="onAjaxOptionsLoaded"
				/>
			</div>

			<div>
				<button class="tw-btn-primary tw-float-right tw-w-fit" @click="save2fa()">
					<span>{{ translate('COM_EMUNDUS_ONBOARD_SAVE') }}</span>
				</button>
			</div>
		</div>
		<div v-else class="em-loader" />
	</div>
</template>

<style scoped></style>
