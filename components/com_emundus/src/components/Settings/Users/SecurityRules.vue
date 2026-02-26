<script>
import Parameter from '@/components/Utils/Parameter.vue';

import settingsService from '@/services/settings';

import alerts from '@/mixins/alerts.js';

export default {
	name: 'SecurityRules',
	components: { Parameter },
	mixins: [alerts],
	data() {
		return {
			loading: true,

			fields: [
				{
					param: 'disable_inactive_accounts_after_delay',
					type: 'text',
					placeholder: '',
					value: 12,
					label: 'COM_EMUNDUS_SETTINGS_DISABLE_INACTIVE_ACCOUNTS_AFTER_DELAY',
					displayed: true,
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

			settingsService.getEmundusParams().then((response) => {
				this.fields[0].value = response.data.emundus.disable_inactive_accounts_after_delay;

				this.loading = false;
			});
		},

		saveSecurityRules() {
			this.loading = true;

			let params = [];
			this.fields.forEach((field) => {
				params.push({
					component: 'emundus',
					param: field.param,
					value: field.value,
				});
			});

			settingsService
				.saveParams(params)
				.then((response) => {
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
					this.alertError('COM_EMUNDUS_SETTINGS_SECURITY_RULES_ERROR');
					this.loading = false;
				});
		},
	},
};
</script>

<template>
	<div class="tw-mt-2">
		<div class="tw-flex tw-flex-col tw-gap-6" v-if="!loading">
			<div v-for="field in fields" :key="field.param" class="tw-flex tw-items-end tw-gap-2" v-show="field.displayed">
				<Parameter
					:ref="'security_rules_' + field.param"
					:parameter-object="field"
					:help-text-type="'above'"
					:key="field.reload"
				/>

				<span
					v-if="['disable_inactive_accounts_after_delay', 'delete_testing_accounts_after_delay'].includes(field.param)"
					>mois</span
				>
			</div>

			<div>
				<button class="tw-btn-primary tw-float-right tw-w-fit" @click="saveSecurityRules()">
					<span>{{ translate('COM_EMUNDUS_ONBOARD_SAVE') }}</span>
				</button>
			</div>
		</div>
		<div v-else class="em-loader" />
	</div>
</template>

<style scoped></style>
