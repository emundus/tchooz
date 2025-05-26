<script>
/* Components */
import Info from '@/components/Utils/Info.vue';
import Parameter from '@/components/Utils/Parameter.vue';

/* Services */
import settingsService from '@/services/settings';
import Tabs from '@/components/Utils/Tabs.vue';
import History from '@/views/History.vue';

export default {
	name: 'YousignSetup',
	components: { History, Tabs, Parameter, Info },
	props: {
		app: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			loading: false,

			sign_count: 0,

			tabs: [
				{
					id: 1,
					name: 'COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SETUP',
					icon: 'encrypted',
					active: true,
					displayed: true,
				},
				{
					id: 2,
					name: 'COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_QUOTA',
					icon: 'format_quote',
					active: false,
					displayed: true,
				},
				{
					id: 3,
					name: 'COM_EMUNDUS_GLOBAL_HISTORY',
					icon: 'history',
					active: false,
					displayed: true,
				},
			],

			fields: [
				{
					param: 'mode',
					type: 'toggle',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_PRODUCTION_MODE',
					displayed: true,
					hideLabel: true,
					optional: true,
				},
				{
					param: 'create_webhook',
					type: 'toggle',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_CREATE_WEBHOOK',
					displayed: false,
					hideLabel: true,
					optional: true,
					displayedOn: 'mode',
					displayedOnValue: 1,
				},
				{
					param: 'token',
					type: 'password',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_BEARER_TOKEN',
					helptext: '',
					displayed: true,
				},
			],
		};
	},
	created() {
		let config = JSON.parse(this.app.config);
		let consumptions = this.app.consumptions ? JSON.parse(this.app.consumptions) : null;

		if (consumptions && consumptions.api.electronic_signature) {
			this.sign_count = consumptions.api.electronic_signature;
		}

		if (typeof config['authentication'] !== 'undefined') {
			this.fields.forEach((field) => {
				if (config['authentication'][field.param]) {
					field.value = config['authentication'][field.param];
				} else {
					field.value = config[field.param] || '';
				}
			});
		}
	},
	methods: {
		setupYousign() {
			this.loading = true;

			let setup = {};

			const yousignValidationFailed = this.fields.some((field) => {
				let ref_name = 'yousign_' + field.param;

				if (!this.$refs[ref_name][0].validate()) {
					// Return true to indicate validation failed
					return true;
				}

				setup[field.param] = field.value;
				return false;
			});

			if (yousignValidationFailed) return;

			settingsService.setupApp(this.app.id, setup).then((response) => {
				if (response.status) {
					Swal.fire({
						icon: 'success',
						title: this.translate('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SETUP_SUCCESS'),
						text: this.translate('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SETUP_SUCCESS_DESC'),
						showConfirmButton: false,
						timer: 3000,
					}).then(() => {
						this.$emit('yousignInstalled');
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
	},
};
</script>

<template>
	<div
		class="tw-relative tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<Tabs :tabs="tabs" />

		<div class="tw-mt-2">
			<div class="tw-flex tw-flex-col tw-gap-6" v-if="tabs[0].active">
				<div class="tw-flex tw-flex-col tw-gap-2">
					<h3>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SETUP') }}</h3>
					<p
						class="tw-text-medium tw-text-sm tw-text-neutral-800"
						v-html="translate('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SETUP_DESC')"
					></p>
				</div>

				<div v-for="field in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
					<Parameter :ref="'yousign_' + field.param" :parameter-object="field" @valueUpdated="checkConditional" />

					<Info
						class="tw-mt-3"
						v-if="field.param === 'create_webhook'"
						title="COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_CREATE_WEBHOOK_HELP_TITLE"
						text="COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_CREATE_WEBHOOK_HELP"
						:accordion="true"
					/>
				</div>

				<div>
					<button class="tw-btn-primary tw-float-right tw-w-fit" :disabled="disabledSubmit" @click="setupYousign()">
						<span v-if="app.enabled === 0 && app.config === '{}'">{{
							translate('COM_EMUNDUS_SETTINGS_INTEGRATION_ADD')
						}}</span>
						<span v-else>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE_BUTTON') }}</span>
					</button>
				</div>
			</div>

			<div v-else-if="tabs[1].active">
				<h3 class="tw-mb-2">{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_YOUSIGN_SIGN_COUNT') }}</h3>

				<label class="tw-text-3xl">
					{{ this.sign_count }}
				</label>
			</div>

			<div v-if="tabs[2].active">
				<History
					:extension="'com_emundus.yousign'"
					:columns="['title', 'status', 'fnum', 'log_date', 'user_id', 'diff']"
				/>
			</div>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<style scoped></style>
