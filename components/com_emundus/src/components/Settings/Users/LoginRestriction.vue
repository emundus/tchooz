<script>
import Parameter from '@/components/Utils/Parameter.vue';

import settingsService from '@/services/settings';

import alerts from '@/mixins/alerts.js';

export default {
	name: 'LoginRestriction',
	components: { Parameter },
	mixins: [alerts],
	data() {
		return {
			loading: true,

			fields: [
				{
					param: 'restrict_login',
					type: 'toggle',
					placeholder: '',
					value: false,
					label: 'COM_EMUNDUS_SETTINGS_RESTRICT_LOGIN',
					helptext: 'COM_EMUNDUS_SETTINGS_RESTRICT_LOGIN_HELPTEXT',
					displayed: true,
					hideLabel: true,
					optional: true,
					reload: 0,
				},
				{
					param: 'restrict_login_patterns',
					type: 'textarea',
					placeholder: '@example\\.com$',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_RESTRICT_LOGIN_PATTERNS',
					helptext: 'COM_EMUNDUS_SETTINGS_RESTRICT_LOGIN_PATTERNS_HELPTEXT',
					displayed: false,
					displayedOn: 'restrict_login',
					displayedOnValue: 1,
					optional: true,
				},
				{
					param: 'restrict_login_message',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_SETTINGS_RESTRICT_LOGIN_MESSAGE',
					helptext: 'COM_EMUNDUS_SETTINGS_RESTRICT_LOGIN_MESSAGE_HELPTEXT',
					displayed: false,
					displayedOn: 'restrict_login',
					displayedOnValue: 1,
					optional: true,
				},
			],
		};
	},
	created() {
		this.init();
	},
	methods: {
		init() {
			this.loading = true;

			settingsService.getEmundusParams().then((response) => {
				const params = response.data.emundus;

				// Toggle is bound with numeric true/false values (1 / 0), not booleans.
				const enabled = parseInt(params.restrict_login) === 1 ? 1 : 0;
				this.fields[0].value = enabled;
				this.fields[1].value = params.restrict_login_patterns || '';
				this.fields[2].value = params.restrict_login_message || '';

				this.checkConditional(this.fields[0], '', enabled);

				this.loading = false;
			});
		},

		saveLoginRestriction() {
			this.loading = true;

			let params = [];
			this.fields.forEach((field) => {
				params.push({
					component: 'emundus',
					param: field.param,
					value: field.type === 'toggle' ? (field.value ? 1 : 0) : field.value,
				});
			});

			settingsService
				.saveParams(params)
				.then(() => {
					Swal.fire({
						icon: 'success',
						title: this.translate('COM_EMUNDUS_ONBOARD_SUCCESS'),
						showConfirmButton: false,
						timer: 3000,
					}).then(() => {
						this.init();
					});
				})
				.catch(() => {
					this.alertError('COM_EMUNDUS_SETTINGS_RESTRICT_LOGIN_ERROR');
					this.loading = false;
				});
		},

		checkConditional(parameter, oldValue, value) {
			// Show/hide fields that depend on the toggled parameter.
			let fields = this.fields.filter((field) => field.displayedOn === parameter.param);

			for (let field of fields) {
				field.displayed = field.displayedOnValue == value;
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
					:ref="'login_restriction_' + field.param"
					:parameter-object="field"
					:help-text-type="'above'"
					:key="field.reload"
					@valueUpdated="checkConditional"
				/>
			</div>

			<div>
				<button class="tw-btn-primary tw-float-right tw-w-fit" @click="saveLoginRestriction()">
					<span>{{ translate('COM_EMUNDUS_ONBOARD_SAVE') }}</span>
				</button>
			</div>
		</div>
		<div v-else class="em-loader" />
	</div>
</template>

<style scoped></style>
