<template>
	<div class="tw-relative tw-w-full tw-rounded-2xl tw-border tw-border-neutral-300 tw-bg-white tw-p-6">
		<Tabs :tabs="tabs"></Tabs>

		<template v-if="!loading && tabs[0].active">
			<Info :text="'COM_EMUNDUS_GLOBAL_PARAMS_SECTION_EMAIL_HELPTEXT'" />

			<!-- ERROR MESSAGE -->
			<template class="tw-hidden">
				<div id="error_message_test" class="tw-mt-7">
					<p class="tw-mb-2 tw-text-center tw-text-red-500">
						{{ translate('COM_EMUNDUS_GLOBAL_EMAIL_ERRORS_DETAILS') }}
					</p>
					<Info
						:text="errorMessage"
						:bg-color="'tw-bg-red-50'"
						:icon="'error'"
						:icon-color="'tw-text-red-600'"
						:text-color="'tw-text-red-500'"
					/>
					<br />
				</div>
			</template>

			<!-- MAILONLINE -->
			<div class="tw-mt-7">
				<Parameter :key="mailonline_key" :parameter-object="mailonline_parameter" @valueUpdated="showDisableWarning" />
			</div>

			<template v-if="displayEmailParameters">
				<!-- REPLYTO -->
				<div class="tw-mt-7 tw-grid tw-grid-cols-2 tw-gap-7">
					<div
						v-for="parameter in reply_to_parameters"
						:key="parameter.param"
						class="tw-w-full"
						v-show="parameter.displayed"
					>
						<Parameter :parameter-object="parameter" />
					</div>
				</div>

				<!-- ENABLE CUSTOM -->
				<div class="tw-mt-7">
					<Parameter :parameter-object="custom_enable_parameter" />
				</div>

				<template v-if="displayCustomParameters">
					<!-- EMAIL SENDER PARAM -->
					<div class="tw-mt-7" style="width: 40%">
						<Parameter :parameter-object="email_sender_param" />
					</div>

					<!-- SMTP PARAMETERS -->
					<div class="tw-mt-7 tw-flex tw-gap-7">
						<div
							v-for="parameter in smtp_parameters"
							:key="parameter.param"
							class="tw-w-full"
							v-show="parameter.displayed"
						>
							<Parameter :parameter-object="parameter" />
							<div
								v-if="parameter.param == 'custom_email_smtpport' && incorrectPort"
								class="tw-flex tw-items-start"
								@click="showPortWarning"
							>
								<span class="material-symbols-outlined tw-scale-75 tw-pr-2 tw-text-orange-600">warning</span>
								<p>
									{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING') }}
									<u class="tw-ml-1">{{ translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_SEE_ALL') }}</u
									>.
								</p>
							</div>
						</div>
					</div>

					<div class="tw-mt-7">
						<Parameter :parameter-object="smtp_security_parameter" />
					</div>

					<!-- ENABLE SMTP AUTH -->
					<div class="tw-mt-7">
						<Parameter :parameter-object="enable_smtp_auth" />
					</div>

					<!-- SMTP AUTH PARAMETERS -->
					<template v-if="displaySmtpAuthParameters">
						<div class="tw-mt-7 tw-flex tw-gap-7">
							<div
								v-for="parameter in smtp_auth_parameters"
								:key="parameter.param"
								class="tw-w-full"
								v-show="parameter.displayed"
							>
								<Parameter :parameter-object="parameter" />
							</div>
						</div>
					</template>
				</template>

				<template v-else>
					<div class="tw-mt-7 tw-flex tw-items-end tw-gap-2">
						<Parameter :parameter-object="default_email_sender_param" />
						<div class="tw-mb-3 tw-flex tw-gap-1">
							<span>@</span>
							<span>{{ default_mail_from_server }}</span>
						</div>
					</div>
				</template>

				<!-- TEST SEND EMAIL && SAVE CONFIGURATION -->
				<div class="tw-mt-7 tw-flex tw-justify-between">
					<button type="button" :disabled="disabledSubmit" class="tw-btn-tertiary tw-cursor-pointer" @click="testEmail">
						<span class="material-symbols-outlined tw-mr-2">send</span
						>{{ translate('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_BT') }}
					</button>

					<button
						type="button"
						:disabled="disabledSubmit"
						class="tw-btn-primary tw-cursor-pointer"
						@click="saveConfiguration"
					>
						{{ translate('COM_EMUNDUS_ONBOARD_SAVE') }}
					</button>
				</div>
			</template>
		</template>

		<template v-if="!loading && tabs[1].active">
			<History
				:extension="'com_emundus.settings.email'"
				:columns="['title', 'message_language_key', 'log_date', 'user_id']"
			/>
		</template>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import mixin from '@/mixins/mixin';
import Swal from 'sweetalert2';
import Parameter from '@/components/Utils/Parameter.vue';
import Info from '@/components/Utils/Info.vue';
import settingsService from '@/services/settings';
import Tabs from '@/components/Utils/Tabs.vue';
import History from '@/views/History.vue';

export default {
	name: 'EditEmailJoomla',
	components: { History, Tabs, Info, Parameter },
	props: {},

	mixins: [mixin],

	data() {
		return {
			loading: true,
			errorMessage: '',

			tabs: [
				{
					id: 1,
					name: 'COM_EMUNDUS_GLOBAL_EMAIL',
					icon: 'email',
					active: true,
					displayed: true,
				},
				{
					id: 2,
					name: 'COM_EMUNDUS_GLOBAL_HISTORY',
					icon: 'history',
					active: false,
					displayed: true,
				},
			],

			mailonline_key: 0,

			mailonline_parameter: {
				param: 'mailonline',
				type: 'toggle',
				value: 0,
				label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_ENABLE',
				displayed: true,
				hideLabel: true,
			},
			reply_to_parameters: [
				{
					param: 'replyto',
					type: 'email',
					placeholder: 'no-reply@tchooz.app',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_REPLYTO',
					helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_REPLYTO_ADRESS_HELPTEXT',
					displayed: true,
					optional: true,
				},
				{
					param: 'replytoname',
					type: 'text',
					placeholder: 'Tchooz',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_REPLY_TO_NAME',
					helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_REPLYTO_HELPTEXT',
					displayed: true,
					optional: true,
				},
				{
					param: 'fromname',
					type: 'text',
					placeholder: 'Tchooz',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER_NAME',
					helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER_NAME_HELPTEXT',
					displayed: true,
					optional: false,
				},
			],
			custom_enable_parameter: {
				param: 'custom_email_conf',
				type: 'toggle',
				value: 0,
				label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CUSTOM',
				displayed: true,
				hideLabel: true,
			},
			default_email_sender_param: {
				param: 'default_email_mailfrom',
				type: 'text',
				placeholder: '',
				value: '',
				label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER',
				helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER_ADRESS_HELPTEXT',
				displayed: true,
			},
			email_sender_param: {
				param: 'custom_email_mailfrom',
				type: 'text',
				placeholder: '',
				value: '',
				concatValue: '',
				label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER',
				helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SENDER_ADRESS_HELPTEXT',
				displayed: true,
				splitField: true,
				splitChar: '@',
			},
			smtp_parameters: [
				{
					param: 'custom_email_smtphost',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_HOST',
					helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_HOSTSMTP_HELPTEXT',
					displayed: true,
				},
				{
					param: 'custom_email_smtpport',
					type: 'text',
					placeholder: '465',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_PORT',
					helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_HELPTEXT',
					displayed: true,
				},
			],
			enable_smtp_auth: {
				param: 'custom_email_smtpauth',
				type: 'toggle',
				value: 1,
				label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_ENABLE',
				displayed: true,
				hideLabel: true,
			},
			smtp_security_parameter: {
				param: 'custom_email_smtpsecure',
				type: 'select',
				value: 0,
				label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_SECURITY',
				helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_SECURITY_HELPTEXT',
				options: [
					{
						value: 'none',
						label: 'COM_EMUNDUS_FILTERS_CHECK_NONE',
					},
					{
						value: 'ssl',
						label: 'SSL',
					},
					{
						value: 'tls',
						label: 'TLS',
					},
				],
				displayed: true,
			},
			smtp_auth_parameters: [
				{
					param: 'custom_email_smtpuser',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_USERNAME',
					helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_HOSTSMTP_HELPTEXT',
					displayed: true,
				},
				{
					param: 'custom_email_smtppass',
					type: 'password',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_CONFIGURATION_SMTP_PASSWORD',
					helptext: 'COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_HOSTPASSWORD_HELPTEXT',
					displayed: true,
				},
			],

			default_mail_from_server: 'tchooz.io',
		};
	},

	created() {
		this.getEmailParameters();
	},
	mounted() {},

	methods: {
		getEmailParameters() {
			settingsService.getEmailParameters().then((response) => {
				if (response.status) {
					this.mailonline_parameter.value = response.data.mailonline ? 1 : 0;
					this.reply_to_parameters[0].value = response.data.replyto;
					this.reply_to_parameters[1].value = response.data.replytoname;
					this.reply_to_parameters[2].value = response.data.fromname;
					this.custom_enable_parameter.value = response.data.custom_email_conf;
					this.email_sender_param.value = response.data.custom_email_mailfrom;
					this.smtp_parameters[0].value = response.data.custom_email_smtphost;
					this.smtp_parameters[1].value = response.data.custom_email_smtpport;
					this.enable_smtp_auth.value = response.data.custom_email_smtpauth;
					this.smtp_security_parameter.value = response.data.custom_email_smtpsecure;
					this.smtp_auth_parameters[0].value = response.data.custom_email_smtpuser;
					this.smtp_auth_parameters[1].value = response.data.custom_email_smtppass;

					this.default_email_sender_param.value = response.data.default_email_mailfrom;
					this.default_email_sender_param.value = this.default_email_sender_param.value.split('@');
					this.default_mail_from_server = this.default_email_sender_param.value[1];
					this.default_email_sender_param.value = this.default_email_sender_param.value[0];

					this.loading = false;
				}
			});
		},

		async testEmail(testing_email = null) {
			this.loading = true;

			const parameters = this.prepareParameters(testing_email);

			settingsService.testEmail(parameters).then(async (response) => {
				this.loading = false;
				if (response.status) {
					Swal.fire({
						title: response.title,
						html: response.text,
						confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_SAVE'),
						cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
						showCancelButton: true,
						reverseButtons: true,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-cancel-button',
							cancelButton: 'em-swal-confirm-button',
							htmlContainer: 'tw-text-center',
						},
						didOpen: () => {
							document.querySelector('#sendEmailNew').addEventListener('click', () => {
								// Check if value is email
								let value = document.querySelector('#otherEmail').value;
								const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
								if (value === '' || !regex.exec(value)) {
									document.querySelector('#otherEmail').classList.add('!tw-border-red-500');
									return;
								}
								this.testEmail(document.querySelector('#otherEmail').value);
								Swal.close();
							});
						},
					}).then((result) => {
						if (result.isConfirmed) {
							this.saveConfiguration();
						}
					});
				} else {
					this.errorMessage = response.desc;
					if (!this.errorMessage) {
						this.errorMessage = this.translate('COM_EMUNDUS_ERROR_SMTP_AUTH');
					}
					if (!response.title) {
						response.title = this.translate('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_TEST_MAIL_ERROR');
					}

					Swal.fire({
						title: response.title,
						html: response.text,
						cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
						showConfirmButton: false,
						customClass: {
							title: 'em-swal-title',
							cancelButton: 'em-swal-confirm-button',
							htmlContainer: 'tw-text-center',
						},
						didOpen() {
							if (response.desc !== '') {
								let errors = document.querySelector('#error_message_test');
								document.querySelector('#swal2-html-container').appendChild(errors);
							}
						},
					});
				}
			});
		},

		async saveConfiguration() {
			const parameters = this.prepareParameters();

			settingsService.saveEmailParameters(parameters).then(async (response) => {
				if (response.status) {
					Swal.fire({
						title: response.msg,
						text: response.desc,
						confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_DOSSIERS_CLOSE'),
						showCancelButton: false,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							htmlContainer: '!tw-text-center',
							actions: '!tw-justify-center',
						},
					});
				} else {
					Swal.fire({
						title: response.msg,
						text: response.desc,
						confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_DOSSIERS_CLOSE'),
						showCancelButton: false,
						customClass: {
							title: 'em-swal-title',
							confirmButton: 'em-swal-confirm-button',
							htmlContainer: '!tw-text-center',
							actions: '!tw-justify-center',
						},
					});
				}
			});
		},

		prepareParameters(testing_email = null) {
			this.email_sender_param.value = this.email_sender_param.concatValue;

			return {
				mailonline: this.mailonline_parameter.value,
				replyto: this.reply_to_parameters[0].value,
				replytoname: this.reply_to_parameters[1].value,
				fromname: this.reply_to_parameters[2].value,
				custom_email_conf: this.custom_enable_parameter.value,
				custom_email_mailfrom: this.email_sender_param.value,
				custom_email_smtphost: this.smtp_parameters[0].value,
				custom_email_smtpport: this.smtp_parameters[1].value,
				custom_email_smtpauth: this.enable_smtp_auth.value,
				custom_email_smtpuser: this.smtp_auth_parameters[0].value,
				custom_email_smtppass: this.smtp_auth_parameters[1].value,
				custom_email_smtpsecure: this.smtp_security_parameter.value,
				default_email_mailfrom: this.default_email_sender_param.value + '@' + this.default_mail_from_server,
				testing_email: typeof testing_email === 'string' ? testing_email : '',
			};
		},

		showPortWarning() {
			Swal.fire({
				html: `
    <div class="tw-flex tw-flex-col tw-items-center tw-justify-center tw-text-center tw--mt-5">
      <h2 class="tw-font-bold">
        ${this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_HELPTEXT_TITLE')}
      </h2>
      <p class="tw-text-center tw-mt-5 tw-text-neutral-700">
        ${this.translate('COM_EMUNDUS_ONBOARD_SETTINGS_EMAIL_PORT_WARNING_HELPTEXT_BODY')}
      </p>
    </div>
  `,
				showCancelButton: false,
				showConfirmButton: true,
				confirmButtonText: this.translate('COM_EMUNDUS_SWAL_OK_BUTTON'),
				reverseButtons: true,
				customClass: {
					confirmButton: 'em-swal-confirm-button',
					actions: 'em-swal-single-action',
					popup: 'tw-px-6 tw-py-4 tw-flex tw-justify-center tw-items-center',
				},
			});
		},

		showDisableWarning(parameter, oldVal, value) {
			if (oldVal === null) {
				return;
			}

			if (value != 1) {
				Swal.fire({
					title: this.translate('COM_EMUNDUS_SURE_TO_DISABLE'),
					text: this.translate('COM_EMUNDUS_GLOBAL_PARAMS_SECTION_MAIL_SURE_TO_DISABLE_TEXT'),
					showCancelButton: true,
					showConfirmButton: true,
					confirmButtonText: this.translate('COM_EMUNDUS_ONBOARD_OK'),
					cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
					reverseButtons: true,
					customClass: {
						title: 'em-swal-title',
						cancelButton: 'em-swal-cancel-button',
						confirmButton: 'em-swal-confirm-button',
					},
				}).then((response) => {
					if (!response.isConfirmed) {
						this.mailonline_parameter.value = oldVal;
						this.mailonline_key = Math.random();
					} else {
						this.saveConfiguration();
					}
				});
			}
		},
	},

	computed: {
		disabledSubmit() {
			return (
				this.mailonline_parameter.value == 0 ||
				this.default_email_sender_param.value == '' ||
				(this.custom_enable_parameter.value == 1 &&
					(this.email_sender_param.value == '' ||
						this.email_sender_param.value == null ||
						this.smtp_parameters[0].value == '' ||
						this.smtp_parameters[0].value == null))
			);
		},

		displayEmailParameters() {
			return this.mailonline_parameter.value == 1;
		},

		displayCustomParameters() {
			return this.custom_enable_parameter.value == 1;
		},

		displaySmtpAuthParameters() {
			return this.enable_smtp_auth.value == 1;
		},

		incorrectPort() {
			if (
				this.smtp_parameters[1].value !== null &&
				this.smtp_parameters[1].value !== '' &&
				!['25', '465', '587'].includes(this.smtp_parameters[1].value.toString())
			) {
				return true;
			} else {
				return false;
			}
		},
	},
};
</script>

<style scoped></style>
