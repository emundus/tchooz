<script>
import Tabs from '@/components/Utils/Tabs.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import Info from '@/components/Utils/Info.vue';
import settingsService from '@/services/settings.js';

export default {
	name: 'StripeSetup',
	components: { Tabs, Parameter, Info },

	props: {
		app: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			loading: false,
			authFields: [
				{
					param: 'client_secret',
					type: 'password',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_STRIPE_SETUP_CLIENT_SECRET',
					helptext: '',
					displayed: true,
					configEntry: 'authentication',
				},
				{
					param: 'webhook_secret',
					type: 'password',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_STRIPE_SETUP_WEBHOOK_SECRET',
					helptext: '',
					displayed: true,
					configEntry: 'authentication',
				},
			],
			fieldsToSave: [],
			tabs: [
				{
					id: 'auth',
					name: 'COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_AUTH',
					active: true,
					displayed: true,
				},
			],
			selectedTab: 'auth',
		};
	},
	created() {
		let config = JSON.parse(this.app.config);

		this.authFields.forEach((field) => {
			if (field.configEntry && field.configEntry !== '') {
				field.value = config[field.configEntry][field.param] || '';
			} else {
				field.value = config[field.param] || '';
			}
		});
	},
	methods: {
		onChangeTabActive(id) {
			this.selectedTab = id;
		},

		setupStripe() {
			// save only selected tab fields
			let setup = {};

			if (this.fieldsToSave.length < 1) {
				this.loading = false;
				return;
			}

			this.authFields.forEach((field) => {
				if (this.fieldsToSave.includes(field.param)) {
					if (field.configEntry && field.configEntry !== '') {
						setup[field.configEntry] = setup[field.configEntry] || {};
						setup[field.configEntry][field.param] = field.value;
					} else {
						setup[field.param] = field.value;
					}
				}
			});

			settingsService.setupApp(this.app.id, setup).then((response) => {
				if (response.status) {
					Swal.fire({
						icon: 'success',
						title: this.translate(response.message),
						showConfirmButton: false,
						timer: 3000,
					});
				} else {
					Swal.fire({
						icon: 'error',
						title: this.translate('COM_EMUNDUS_ONBOARD_ERROR_MESSAGE'),
						text: response.message,
						showConfirmButton: false,
						timer: 3000,
					});
				}
			});
			this.loading = false;
		},

		parameterNeedSaving(needSaving, parameter) {
			if (needSaving) {
				if (!this.fieldsToSave.find((field) => field.param === parameter.param)) {
					this.fieldsToSave.push(parameter.param);
				}
			} else {
				this.fieldsToSave = this.fieldsToSave.filter((field) => field !== parameter.param);
			}
		},
	},
};
</script>

<template>
	<div
		class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<Tabs
			:classes="'tw-overflow-x-auto tw-absolute tw-right-6 tw-flex tw-items-center tw-justify-end tw-gap-2 tw-top-[59px] tw-right-[50px]'"
			:tabs="tabs"
			@changeTabActive="onChangeTabActive"
		></Tabs>

		<h3>{{ translate('COM_EMUNDUS_STRIPE_SETUP_TITLE') }}</h3>

		<div v-if="selectedTab === 'auth'" class="tw-flex tw-flex-col tw-gap-4">
			<div v-for="field in authFields" :key="field.param" class="tw-w-full" v-show="field.displayed">
				<Parameter
					:ref="'auth_' + field.param"
					:parameter-object="field"
					:help-text-type="'above'"
					@needSaving="parameterNeedSaving"
				/>
			</div>
		</div>

		<div class="tw-flex tw-justify-end">
			<button class="tw-btn-primary" @click="setupStripe">
				{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_SOGECOMMERCE_SETUP_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
