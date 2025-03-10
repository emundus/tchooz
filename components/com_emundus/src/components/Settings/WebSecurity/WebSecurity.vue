<template>
	<div class="tw-w-full tw-rounded-2xl tw-p-6 tw-bg-white tw-border tw-border-neutral-300 tw-relative">
		<Tabs :tabs="tabs"></Tabs>

		<template v-if="!loading && tabs[0].active">
			<Info
				v-if="current_requests.length > 0"
				:text="current_requests_pending"
				:icon="'warning'"
				:bg-color="'tw-bg-orange-50'"
				:icon-type="'material-symbols-outlined'"
				:icon-color="'tw-text-orange-600'"
			/>
			<Info v-if="this.livesite" :text="information" :class="'tw-mt-3'"></Info>

			<div class="tw-mt-7 tw-flex tw-flex-col" v-if="!this.loading">
				<div v-for="(parameter, index) in parameters" class="tw-mb-7" v-show="parameter.displayed">
					<Parameter
						:parameter-object="parameter"
						@valueUpdated="checkConditional"
						:multiselect-options="parameter.multiselectOptions ? parameter.multiselectOptions : null"
					/>

					<Info
						v-if="parameter.param === 'new_address'"
						:text="new_address_warning"
						:icon="'warning'"
						:bg-color="'tw-bg-orange-50'"
						:icon-type="'material-symbols-outlined'"
						:icon-color="'tw-text-orange-600'"
						:class="'tw-mt-7'"
					/>

					<!--          <Info
                        v-if="parameter.param == 'use_own_ssl_certificate' && parameter.value == true"
                        :text="own_ssl_ask"
                        :bg-color="'tw-bg-neutral-300'"
                        :display-icon="false"
                        :class="'tw-mt-2'"
                    />-->
				</div>

				<!-- RESUME -->
				<template class="tw-hidden">
					<div id="web_security_resume">
						<Info :text="resume" />
						<br />
						<p>{{ translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_CONFIRMATION_TEXT') }}</p>
					</div>
				</template>

				<div class="tw-self-end">
					<button type="button" :disabled="disabledSubmit" class="tw-btn-primary" @click="sendRequest">
						<span class="material-symbols-outlined tw-mr-2">send</span
						>{{ translate('COM_EMUNDUS_ONBOARD_TRANSLATION_TOOL_SUGGEST_LANGUAGE_SEND') }}
					</button>
				</div>
			</div>
		</template>

		<template v-if="!loading && tabs[1].active">
			<History
				:extension="'com_emundus.settings.web_security'"
				:columns="['title', 'message_language_key', 'log_date', 'user_id', 'status']"
			/>
		</template>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<script>
import settingsService from '@/services/settings';
import Info from '@/components/Utils/Info.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import Tabs from '@/components/Utils/Tabs.vue';
import History from '@/views/History.vue';

export default {
	name: 'WebSecurity',
	components: { History, Tabs, Parameter, Info },
	props: {},

	data() {
		return {
			livesite: null,
			ssl_cert: null,
			current_requests: null,

			parameters: [
				{
					param: 'update_web_address',
					type: 'yesno',
					value: 0,
					label: 'COM_EMUNDUS_GLOBAL_WEB_SECURITY_UPDATE_WEB_ADDRESS',
					helptext: 'COM_EMUNDUS_GLOBAL_WEB_SECURITY_UPDATE_WEB_ADDRESS_HELPTEXT',
					displayed: true,
				},
				{
					param: 'new_address',
					type: 'text',
					value: '',
					placeholder: 'https://example.tchooz.app',
					label: 'COM_EMUNDUS_GLOBAL_WEB_SECURITY_NEW_ADDRESS',
					displayed: false,
					displayedOn: 'update_web_address',
				},
				{
					param: 'use_own_ssl_certificate',
					type: 'yesno',
					value: 0,
					label: 'COM_EMUNDUS_GLOBAL_WEB_SECURITY_USE_OWN_SSL_CERTIFICATE',
					helptext: 'COM_EMUNDUS_GLOBAL_WEB_SECURITY_USE_OWN_SSL_CERTIFICATE_HELPTEXT',
					displayed: true,
				},
				{
					param: 'technical_contacts',
					type: 'multiselect',
					optional: 0,
					multiselectOptions: {
						noOptions: true,
						multiple: true,
						taggable: true,
						searchable: true,
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						tagValidations: ['email'],
						options: [],
					},
					value: [],
					label: 'COM_EMUNDUS_GLOBAL_WEB_SECURITY_TECHNICAL_CONTACTS',
					helptext: 'COM_EMUNDUS_GLOBAL_WEB_SECURITY_TECHNICAL_CONTACTS_HELPTEXT',
					placeholder: 'user1@example.fr, user2@example.fr',
					displayed: true,
				},
			],

			tabs: [
				{
					id: 1,
					name: 'COM_EMUNDUS_GLOBAL_WEB_SECURITY_REQUEST',
					icon: 'send',
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
		};
	},

	created() {
		this.getLivesite();
		this.getSSLInfo();
		this.getCurrentRequests();
	},
	methods: {
		getLivesite() {
			settingsService.getLiveSite().then((response) => {
				this.livesite = response.data;
			});
		},

		getSSLInfo() {
			settingsService.getsslinfo().then((response) => {
				this.ssl_cert = response.data;
			});
		},

		getCurrentRequests() {
			settingsService.getHistory('com_emundus.settings.web_security', true).then((response) => {
				this.current_requests = response.data;
			});
		},

		checkConditional(parameter, oldValue, value) {
			let paramsToShow = this.parameters.find((param) => param.displayedOn === parameter.param);
			if (paramsToShow) {
				paramsToShow.displayed = value == 1;
			}
		},

		sendRequest() {
			Swal.fire({
				title: this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_CONFIRMATION'),
				html: document.querySelector('#web_security_resume').outerHTML,
				showCancelButton: true,
				confirmButtonText: this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_CONFIRMATION_BUTTON'),
				cancelButtonText: this.translate('COM_EMUNDUS_ONBOARD_CANCEL'),
				reverseButtons: true,
				customClass: {
					title: 'em-swal-title',
					cancelButton: 'em-swal-cancel-button',
					confirmButton: 'em-swal-confirm-button',
				},
			}).then((result) => {
				if (result.isConfirmed) {
					let data = [];
					this.parameters.forEach((param) => {
						data[param.param] = param.value;
					});

					Swal.fire({
						position: 'center',
						iconHtml:
							'<img class="em-sending-email-img tw-w-1/2 tw-max-w-none" src="/media/com_emundus/images/tchoozy/complex-illustrations/sending-message.svg"/>',
						title: Joomla.Text._('COM_EMUNDUS_GLOBAL_WEB_SECURITY_REQUEST_PENDING'),
						showCancelButton: false,
						showConfirmButton: false,
						customClass: {
							icon: 'em-swal-icon',
						},
					});

					settingsService.sendRequest(data).then((response) => {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_REQUEST_SENT'),
							icon: 'success',
							showConfirmButton: false,
							customClass: {
								title: 'em-swal-title',
							},
							timer: 3000,
						});
					});
				}
			});
		},
	},
	computed: {
		current_requests_pending() {
			return '<p>' + this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_CURRENT_REQUESTS_PENDING') + '</p>';
		},
		information() {
			let text =
				'<p>' + this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_YOUR_LIVESITE') + '<b>' + this.livesite + '</b></p>';
			if (this.ssl_cert) {
				text +=
					'<br/><p>' +
					this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_SSL_CERT') +
					'<b>' +
					this.ssl_cert.type +
					'</b></p>';
			}
			return text;
		},
		new_address_warning() {
			return (
				'<p>' +
				this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_NEW_ADDRESS_WARNING') +
				'<b>' +
				this.livesite +
				'</b></p><p>' +
				this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_NEW_ADDRESS_WARNING_2') +
				'</p>'
			);
		},
		own_ssl_ask() {
			return '<p>' + this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_OWN_SSL_ASK') + '</p>';
		},
		resume() {
			let resume = '<p>' + this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_RESUME') + '</p>';
			resume += '<ul>';

			// Check if we need to update the web address
			let update_web_address = this.parameters.find((param) => param.param === 'update_web_address');
			let new_address = this.parameters.find((param) => param.param === 'new_address');
			if (update_web_address.value == 1) {
				resume +=
					'<li>' +
					this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_NEW_ADDRESS_TO_UPDATE') +
					'<b>"' +
					new_address.value +
					'"</b></li>';
			}

			// Check if we need to use our own SSL certificate
			let use_own_ssl_certificate = this.parameters.find((param) => param.param === 'use_own_ssl_certificate');
			if (use_own_ssl_certificate.value == 1) {
				resume += '<li>' + this.translate('COM_EMUNDUS_GLOBAL_WEB_SECURITY_OWN_SSL_CERTIFICATE') + '</li>';
			}

			resume += '</ul>';
			return resume;
		},
		loading() {
			if (this.livesite === null || this.ssl_cert === null || this.current_requests === null) {
				return true;
			} else {
				return false;
			}
		},
		disabledSubmit() {
			let disabled = true;

			let update_web_address = this.parameters.find((param) => param.param === 'update_web_address');
			let new_address = this.parameters.find((param) => param.param === 'new_address');
			let use_own_ssl_certificate = this.parameters.find((param) => param.param === 'use_own_ssl_certificate');
			let technical_contacts = this.parameters.find((param) => param.param === 'technical_contacts');

			if (update_web_address.value == 1) {
				if (new_address.value !== '') {
					disabled = false;
				} else {
					return true;
				}
			} else if (use_own_ssl_certificate.value == 1) {
				disabled = false;
			} else {
				return true;
			}

			if (technical_contacts.value.length > 0) {
				disabled = false;
			} else {
				return true;
			}

			return disabled;
		},
	},
};
</script>

<style scoped></style>
