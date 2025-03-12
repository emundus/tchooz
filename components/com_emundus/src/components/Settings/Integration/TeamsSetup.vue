<script>
/* Components */
import Info from '@/components/Utils/Info.vue';
import Parameter from '@/components/Utils/Parameter.vue';

/* Services */
import settingsService from '@/services/settings';

export default {
	name: 'TeamsSetup',
	components: { Parameter, Info },
	props: {
		app: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			loading: false,

			fields: [
				{
					param: 'client_id',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_ID',
					helptext: '',
					displayed: true,
				},
				{
					param: 'client_secret',
					type: 'password',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_CLIENT_SECRET',
					helptext: '',
					displayed: true,
				},
				{
					param: 'tenant_id',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_TENANT_ID',
					helptext: '',
					displayed: true,
				},
				{
					param: 'email',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_EMAIL',
					helptext: '',
					displayed: true,
				},
			],
		};
	},
	created() {
		let config = JSON.parse(this.app.config);

		if (typeof config['authentication'] !== 'undefined') {
			this.fields.forEach((field) => {
				field.value = config['authentication'][field.param] || '';
			});
		}
	},
	methods: {
		setupTeams() {
			this.loading = true;

			let setup = {};

			const teamsValidationFailed = this.fields.some((field) => {
				let ref_name = 'teams_' + field.param;

				if (!this.$refs[ref_name][0].validate()) {
					// Return true to indicate validation failed
					return true;
				}

				setup[field.param] = field.value;
				return false;
			});

			if (teamsValidationFailed) return;

			settingsService.setupApp(this.app.id, setup).then((response) => {
				if (response.status) {
					Swal.fire({
						icon: 'success',
						title: this.translate('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_SUCCESS'),
						text: this.translate('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_SUCCESS_DESC'),
						showConfirmButton: false,
						timer: 3000,
					}).then(() => {
						this.$emit('teamsInstalled');
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
		class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<Info
			:text="'COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_REQUIREMENTS'"
			:icon="'warning'"
			:bg-color="'tw-bg-orange-100'"
			:icon-type="'material-symbols-outlined'"
			:icon-color="'tw-text-orange-600'"
			:class="'tw-mb-4'"
		/>
		<h3>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP') }}</h3>

		<div class="tw-mt-2">
			<p class="tw-text-medium tw-text-sm tw-text-neutral-800">
				{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_TEAMS_SETUP_DESC') }}
			</p>

			<div class="tw-mt-7 tw-flex tw-flex-col tw-gap-6">
				<div v-for="field in fields" :key="field.param" class="tw-w-full" v-show="field.displayed">
					<Parameter :ref="'teams_' + field.param" :parameter-object="field" :help-text-type="'above'" />
				</div>

				<div>
					<button class="tw-btn-primary tw-float-right tw-w-fit" :disabled="disabledSubmit" @click="setupTeams()">
						<span v-if="app.enabled === 0 && app.config === '{}'">{{
							translate('COM_EMUNDUS_SETTINGS_INTEGRATION_ADD')
						}}</span>
						<span v-else>{{ translate('COM_EMUNDUS_SETTINGS_INTEGRATION_UPDATE') }}</span>
					</button>
				</div>
			</div>
		</div>

		<div class="em-page-loader" v-if="loading"></div>
	</div>
</template>

<style scoped></style>
