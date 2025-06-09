<script>
import Parameter from '@/components/Utils/Parameter.vue';
import paymentService from '@/services/payment.js';
import Modal from '@/components/Modal.vue';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'CustomerAddress',
	components: { Modal, Parameter },
	props: {
		cartId: {
			type: Number,
			required: true,
		},
		customer: {
			type: Object,
			required: true,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},
	mixins: [alerts],
	data() {
		return {
			mode: 'preview',
			countryOptions: [],

			fields: [
				{
					param: 'firstname',
					type: 'text',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_FIRSTNAME',
					helptext: '',
					displayed: true,
				},
				{
					param: 'lastname',
					type: 'text',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_LASTNAME',
					helptext: '',
					displayed: true,
				},
				{
					param: 'email',
					type: 'email',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_EMAIL',
					helptext: '',
					displayed: true,
				},
				{
					param: 'phone_1',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_PHONE',
					helptext: '',
					displayed: true,
					tagValidations: ['phone'],
					optional: true,
				},
			],
			addressFields: [
				{
					param: 'address1',
					type: 'text',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_ADDRESS_1',
					helptext: '',
					displayed: true,
				},
				{
					param: 'address2',
					type: 'text',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_ADDRESS_2',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'zip',
					type: 'text',
					maxlength: 5,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_ZIPCODE',
					helptext: '',
					displayed: true,
				},
				{
					param: 'city',
					type: 'text',
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_CITY',
					helptext: '',
					displayed: true,
				},
				{
					param: 'country',
					type: 'select',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_CUSTOMER_COUNTRY',
					helptext: '',
					displayed: true,
					options: this.countryOptions,
				},
			],
			loading: false,
		};
	},
	created() {
		this.getCountries();

		// if no fields are set in customer.address, set mode to edit
		if (
			!this.readOnly &&
			(!this.customer.address ||
				!this.customer.address.address1 ||
				!this.customer.address.city ||
				!this.customer.address.zip ||
				!this.customer.address.country)
		) {
			this.mode = 'edit';
		}

		this.fields.forEach((field) => {
			if (field.param in this.customer) {
				field.value = this.customer[field.param];
			}
		});
		if (this.customer.address) {
			this.addressFields.forEach((field) => {
				if (field.param in this.customer.address) {
					field.value = this.customer.address[field.param];
				}
			});
		}
	},
	methods: {
		getCountries() {
			paymentService
				.getCountries()
				.then((response) => {
					if (response.status) {
						this.countryOptions = response.data;

						this.addressFields.forEach((field) => {
							if (field.param === 'country') {
								field.options = this.countryOptions.map((country) => ({
									value: country.id,
									label: country.label,
								}));
							}
						});
					} else {
						console.error('Error fetching countries:', response.message);
					}
				})
				.catch((error) => {
					console.error('Error fetching countries:', error);
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
		saveCustomerAddress() {
			this.loading = true;

			// verify mandatory fields are filled
			const mandatoryFields = this.displayedFields.filter((field) => !field.optional);
			const missingFields = mandatoryFields.filter((field) => !field.value || field.value === '');
			if (missingFields.length > 0) {
				this.alertError('COM_EMUNDUS_MANDATORY_FIELDS_MISSING', missingFields.map((field) => field.label).join(', '));
				this.loading = false;
				return;
			}

			const customerData = {
				...this.customer,
				...this.fields.reduce((acc, field) => {
					acc[field.param] = field.value;
					return acc;
				}, {}),
			};

			this.addressFields.forEach((field) => {
				customerData[field.param] = field.value;
			});

			paymentService
				.saveCustomer(customerData, this.cartId)
				.then((response) => {
					if (response.status) {
						// Update the customer object with the new data
						this.fields.forEach((field) => {
							this.customer[field.param] = field.value;
						});

						if (!this.customer.address) {
							this.customer.address = {};
						}
						this.addressFields.forEach((field) => {
							this.customer.address[field.param] = field.value;
						});

						this.mode = 'preview';
					} else {
						this.alertError('COM_EMUNDUS_ERROR_SAVING_CUSTOMER');
					}
				})
				.catch((error) => {
					console.error('Error updating customer address:', error);
				})
				.finally(() => {
					this.loading = false;
				});
		},
	},
	computed: {
		displayedFields() {
			return this.fields
				.filter((field) => field.displayed)
				.concat(this.addressFields.filter((field) => field.displayed));
		},
		selectedCountry() {
			return this.customer.address && this.countryOptions.length > 0
				? this.countryOptions.find((country) => country.id === this.customer.address.country)
				: null;
		},
	},
};
</script>

<template>
	<div id="customer-address">
		<transition name="slide">
			<div v-if="mode === 'preview'" id="preview">
				<div class="address tw-flex tw-flex-col tw-gap-2">
					<p class="tw-grid tw-grid-cols-2">
						<strong>{{ translate('COM_EMUNDUS_CUSTOMER_NAME') }}</strong>
						<span>{{ customer.firstname + ' ' + customer.lastname }}</span>
					</p>
					<hr class="tw-m-0" />

					<p class="tw-grid tw-grid-cols-2">
						<strong>{{ translate('COM_EMUNDUS_CUSTOMER_EMAIL') }}</strong>
						<span>{{ customer.email }}</span>
					</p>
					<hr class="tw-m-0" />

					<div v-if="customer.phone_1" class="tw-flex tw-flex-col tw-gap-2">
						<p class="tw-grid tw-grid-cols-2">
							<strong>{{ translate('COM_EMUNDUS_CUSTOMER_PHONE') }}</strong>
							<span>{{ customer.phone_1 }}</span>
						</p>
						<hr class="tw-m-0" />
					</div>

					<div v-if="customer.address" class="tw-flex tw-flex-col tw-gap-2">
						<p class="tw-grid tw-grid-cols-2">
							<strong>{{ translate('COM_EMUNDUS_CUSTOMER_ADDRESS_1') }}</strong>
							<span>{{ customer.address.address1 }}</span>
						</p>
						<hr class="tw-m-0" />

						<div v-if="customer.address.address2" class="tw-flex tw-flex-col tw-gap-2">
							<p class="tw-grid tw-grid-cols-2">
								<strong>{{ translate('COM_EMUNDUS_CUSTOMER_ADDRESS_2') }}</strong>
								<span>{{ customer.address.address2 }}</span>
							</p>
							<hr class="tw-m-0" />
						</div>

						<p class="tw-grid tw-grid-cols-2">
							<strong>{{ translate('COM_EMUNDUS_CUSTOMER_ZIPCODE') }}</strong>
							<span>{{ customer.address.zip }}</span>
						</p>
						<hr class="tw-m-0" />

						<p class="tw-grid tw-grid-cols-2">
							<strong>{{ translate('COM_EMUNDUS_CUSTOMER_CITY') }}</strong>
							<span>{{ customer.address.city }}</span>
						</p>
						<hr class="tw-m-0" />

						<div v-if="selectedCountry" class="tw-flex tw-flex-col tw-gap-2">
							<p class="tw-grid tw-grid-cols-2">
								<strong>{{ translate('COM_EMUNDUS_CUSTOMER_COUNTRY') }}</strong>
								<span>{{ selectedCountry.label }}</span>
							</p>
						</div>
					</div>

					<div v-if="!readOnly" class="tw-mt-4 tw-flex">
						<button class="not-to-close-modal tw-btn-secondary tw-gap-2" @click="mode = 'edit'">
							<span class="material-symbols-outlined">edit</span>
							{{ translate('COM_EMUNDUS_EDIT_CUSTOMER_ADDRESS') }}
						</button>
					</div>
				</div>
			</div>
			<div v-else class="tw-flex tw-flex-col tw-gap-4">
				<!--<modal>
					name="customer-address-modal"
					:title="'COM_EMUNDUS_EDIT_CUSTOMER_ADDRESS'"
					:center="true"
					:width="'auto'"
					:classes="'tw-p-4 tw-rounded-lg tw-shadow-sm tw-border tw-min-w-[70vw] tw-max-w-[95vw]'"
				>-->
				<div
					v-for="field in displayedFields"
					:key="field.param"
					class="tw-mt-4"
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
				<div class="tw-mt-4 tw-flex tw-flex-wrap tw-justify-between tw-gap-2">
					<button class="tw-btn-secondary" @click="mode = 'preview'">
						{{ translate('COM_EMUNDUS_CANCEL_CUSTOMER_ADDRESS') }}
					</button>
					<button class="tw-btn-primary" @click="saveCustomerAddress">
						{{ translate('COM_EMUNDUS_SAVE_CUSTOMER_ADDRESS') }}
					</button>
				</div>
				<!-- </modal>-->
			</div>
		</transition>
	</div>
</template>

<style scoped></style>
