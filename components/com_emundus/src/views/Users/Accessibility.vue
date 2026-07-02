<script>
import Parameter from '@/components/Utils/Parameter.vue';

import usersService from '@/services/user';

import alerts from '@/mixins/alerts.js';
import ParameterForm from '@/components/Utils/Form/ParameterForm.vue';
import Back from '@/components/Utils/Back.vue';
import Button from '@/components/Atoms/Button.vue';
import Loader from '@/components/Atoms/Loader.vue';
import settingsService from '@/services/settings.js';

export default {
	name: 'Accessibility',
	components: { Loader, Button, Back, ParameterForm, Parameter },
	mixins: [alerts],
	data() {
		return {
			loading: true,

			formGroups: [
				{
					id: 'default-group',
					title: '',
					description: '',
					helpTextType: 'above',
					parameters: [
						{
							param: 'a11y_mono',
							type: 'toggle',
							value: 0,
							label: 'COM_EMUNDUS_USERS_ACCESSIBILITY_MONO',
							displayed: true,
							hideLabel: true,
						},
						{
							param: 'a11y_contrast',
							type: 'toggle',
							value: 0,
							label: 'COM_EMUNDUS_USERS_ACCESSIBILITY_HIGH_CONTRAST',
							displayed: true,
							hideLabel: true,
						},
						{
							param: 'a11y_highlight',
							type: 'toggle',
							value: 0,
							label: 'COM_EMUNDUS_USERS_ACCESSIBILITY_HIGHLIGHT_LINK',
							displayed: true,
							hideLabel: true,
						},
						{
							param: 'a11y_font',
							type: 'toggle',
							value: 0,
							label: 'COM_EMUNDUS_USERS_ACCESSIBILITY_INCREASE_FONT_SIZE',
							displayed: true,
							hideLabel: true,
						},
					],
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

			usersService.getAccessibilityUserSettings().then((response) => {
				if (response.status) {
					Object.entries(response.data).forEach(([key, value]) => {
						const param = this.formGroups[0].parameters.find((p) => p.param === key);
						if (param) {
							param.value = value != 0 ? 1 : 0;
						}
					});
					this.loading = false;
				} else {
					this.loading = false;
				}
			});
		},

		save() {
			let accessibilityForm = {};

			const formValidationFailed = this.formGroups[0].parameters.some((field) => {
				if (field.displayed) {
					let ref_name = 'field_' + field.param;

					if (this.$refs[ref_name] && !this.$refs[ref_name][0].validate()) {
						// Return true to indicate validation failed
						return true;
					}

					accessibilityForm[field.param] = field.value;

					return false;
				}
			});
			if (formValidationFailed) return;

			this.loading = true;
			usersService.saveAccessibilitySettings(accessibilityForm).then((response) => {
				if (response.status) {
					this.alertSuccess(this.translate(response.msg));
					this.loading = false;
				} else {
					this.alertError(this.translate('COM_EMUNDUS_USERS_ACCESSIBILITY_SETTINGS_ERROR'));
				}
			});
		},
	},
};
</script>

<template>
	<div class="tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card">
		<div
			class="tw-group tw-flex tw-w-fit tw-cursor-pointer tw-items-center tw-font-semibold tw-text-link-regular"
			@click="window.history.back()"
		>
			<span class="material-symbols-outlined tw-mr-1">navigate_before</span>
			<span class="group-hover:tw-underline">{{ translate('BACK') }}</span>
		</div>

		<div class="tw-mt-4">
			<h1>
				{{ translate('COM_EMUNDUS_USERS_ACCESSIBILITY_TITLE') }}
			</h1>
			<p>
				{{ translate('COM_EMUNDUS_USERS_ACCESSIBILITY_DESCRIPTION') }}
			</p>
		</div>

		<div class="tw-flex tw-flex-wrap tw-justify-start">
			<div v-if="!loading" class="tw-w-full">
				<ParameterForm id="acessibility-parameters-form" :groups="formGroups" />

				<div class="tw-mt-4 tw-flex tw-w-full tw-justify-end">
					<Button @click="save">
						{{ translate('COM_EMUNDUS_SAVE') }}
					</Button>
				</div>
			</div>

			<Loader v-else />
		</div>
	</div>
</template>

<style scoped></style>
