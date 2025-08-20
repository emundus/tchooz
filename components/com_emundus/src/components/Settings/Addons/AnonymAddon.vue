<script>
import { defineComponent } from 'vue';
import Parameter from '@/components/Utils/Parameter.vue';
import settingsService from '@/services/settings.js';

export default defineComponent({
	name: 'AnonymAddon',
	components: { Parameter },
	props: {
		addon: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			loading: true,
			fields: [
				{
					param: 'token_duration_validity',
					type: 'number',
					placeholder: '',
					value: 30,
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_ANONYM_TOKEN_DURATION_VALIDITY',
					helptext: '',
					displayed: true,
					reload: 0,
				},
				{
					param: 'token_duration_validity_unit',
					type: 'select',
					placeholder: '',
					value: 'day',
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_ANONYM_TOKEN_DURATION_VALIDITY_UNIT',
					helptext: '',
					displayed: true,
					options: [
						{ label: 'COM_EMUNDUS_SETTINGS_ADDONS_ANONYM_TOKEN_DURATION_VALIDITY_UNIT_MONTH', value: 'month' },
						{ label: 'COM_EMUNDUS_SETTINGS_ADDONS_ANONYM_TOKEN_DURATION_VALIDITY_UNIT_WEEK', value: 'week' },
						{ label: 'COM_EMUNDUS_SETTINGS_ADDONS_ANONYM_TOKEN_DURATION_VALIDITY_UNIT_DAY', value: 'day' },
						{ label: 'COM_EMUNDUS_SETTINGS_ADDONS_ANONYM_TOKEN_DURATION_VALIDITY_UNIT_HOUR', value: 'hour' },
						{ label: 'COM_EMUNDUS_SETTINGS_ADDONS_ANONYM_TOKEN_DURATION_VALIDITY_UNIT_MINUTE', value: 'minute' },
					],
				},
			],
		};
	},
	created() {
		this.addon.configuration =
			typeof this.addon.configuration === 'string' ? JSON.parse(this.addon.configuration) : this.addon.configuration;
	},
	mounted() {
		this.fields.forEach((field) => {
			if (field.param in this.addon.configuration) {
				field.value = this.addon.configuration[field.param];
			}
		});

		this.loading = false;
	},
	methods: {
		saveAddon() {
			for (let field of this.fields) {
				if (field.param in this.addon.configuration) {
					this.addon.configuration[field.param] = field.value;
				}
			}

			settingsService.saveAddon(this.addon).then((response) => {
				if (response.status) {
					Swal.fire({
						icon: 'success',
						title: this.translate('COM_EMUNDUS_SUCCESS'),
						text: this.translate('COM_EMUNDUS_ANONYM_ADDON_CONFIGURATION_SAVED'),
						showConfirmButton: false,
						delay: 2000,
					});
					this.$emit('addonSaved');
				} else {
					Swal.fire({
						icon: 'error',
						title: this.translate('COM_EMUNDUS_ERROR'),
						text: this.translate(response.message),
						showConfirmButton: false,
						delay: 2000,
					});
				}
			});
		},
	},
	computed: {
		displayedFields() {
			return !this.loading ? this.fields.filter((field) => field.displayed) : [];
		},
	},
});
</script>

<template>
	<div
		id="addon-anonym"
		class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<h3>{{ translate('COM_EMUNDUS_ANONYM_ADDON_CONFIGURATION') }}</h3>

		<div class="tw-flex tw-w-full tw-flex-row tw-justify-between tw-gap-4">
			<div
				v-for="field in displayedFields"
				:key="field.param"
				:class="{
					'tw-w-3/4': field.param === 'token_duration_validity',
					'tw-w-1/4': field.param !== 'token_duration_validity',
				}"
			>
				<Parameter
					:class="{ 'tw-w-full': field.param === 'name' }"
					:ref="'event_' + field.param"
					:key="field.reload ? field.reload : field.param"
					:parameter-object="field"
					:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
					:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
				/>
			</div>
		</div>

		<div id="actions" class="tw-flex tw-justify-end">
			<button class="tw-btn-primary" @click="saveAddon()">
				{{ translate('SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
