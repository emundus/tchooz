<script>
import paymentService from '@/services/payment.js';
import Info from '@/components/Utils/Info.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import settingsService from '@/services/settings.js';

export default {
	name: 'PaymentAddon',
	components: { Info, Parameter },
	emits: ['addonSaved'],
	props: {
		addon: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			currencies: [],
			fields: [
				{
					param: 'currency_id',
					type: 'select',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_SETTINGS_ADDONS_PAYMENT_CURRENCY',
					helptext: '',
					displayed: true,
					options: this.currencies,
					reload: 0,
				},
			],
			loading: true,
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
		this.getCurrencies();
	},
	methods: {
		getCurrencies() {
			paymentService.getCurrencies().then((response) => {
				if (response.status) {
					this.currencies = response.data.map((currency) => ({
						value: currency.id,
						label: currency.name + ' (' + currency.symbol + ')',
					}));

					this.fields.forEach((field) => {
						if (field.param === 'currency_id') {
							field.options = this.currencies;
						}
					});

					this.loading = false;
				}
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
						text: this.translate('COM_EMUNDUS_PAYMENT_ADDON_CONFIGURATION_SAVED'),
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
};
</script>

<template>
	<div
		id="addon-payment"
		class="tw-mb-6 tw-flex tw-w-full tw-flex-col tw-justify-between tw-gap-3 tw-rounded-[15px] tw-border tw-border-neutral-300 tw-bg-white tw-p-4 tw-font-medium tw-text-black rtl:tw-text-right"
	>
		<h3>{{ translate('COM_EMUNDUS_PAYMENT_ADDON_CONFIGURATION') }}</h3>

		<div
			v-for="field in displayedFields"
			:key="field.param"
			:class="{
				'tw-flex tw-w-1/2 tw-items-end tw-justify-between tw-gap-2': field.param === 'name',
				'tw-w-full': field.param !== 'name',
			}"
		>
			<Parameter
				:class="{ 'tw-w-full': field.param === 'name' }"
				:ref="'event_' + field.param"
				:key="field.reload ? field.reload : field.param"
				:parameter-object="field"
				:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
				:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
				@valueUpdated="checkConditional"
			/>
		</div>

		<div id="actions" class="tw-flex tw-justify-end">
			<button class="tw-btn-primary" @click="saveAddon()">
				{{ translate('SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
