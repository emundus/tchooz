<script>
import Parameter from '@/components/Utils/Parameter.vue';
import Back from '@/components/Utils/Back.vue';
import paymentService from '@/services/payment.js';

export default {
	name: 'DiscountEdit',
	components: { Back, Parameter },
	props: {
		discountId: {
			type: Number,
			default: 0,
		},
	},
	data() {
		return {
			currencies: [],
			types: [
				{ value: 'fixed', label: 'COM_EMUNDUS_DISCOUNT_TYPE_FIXED' },
				{ value: 'percentage', label: 'COM_EMUNDUS_DISCOUNT_TYPE_PERCENTAGE' },
			],
			discount: {
				id: this.discountId,
				label: '',
				value: 0,
				description: '',
				currency_id: 1,
				type: 'fixed',
				quantity: 0,
				available_from: '',
				available_to: '',
				published: 1,
			},

			fields: [
				{
					param: 'label',
					type: 'text',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_DISCOUNT_LABEL',
					helptext: '',
					displayed: true,
				},
				{
					param: 'description',
					type: 'textarea',
					maxlength: 255,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_DISCOUNT_DESCRIPTION',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'value',
					type: 'number',
					placeholder: '',
					value: 0.0,
					label: 'COM_EMUNDUS_DISCOUNT_VALUE',
					step: 0.01,
					pattern: '^[0-9]+(\\.[0-9]{1,2})?$',
					max: 99999999,
					maxlength: 8,
					editable: true,
					displayed: true,
				},
				{
					param: 'type',
					type: 'select',
					placeholder: '',
					value: 'fixed',
					label: 'COM_EMUNDUS_DISCOUNT_TYPE',
					helptext: '',
					displayed: true,
					options: [],
					classes: 'tw-w-full',
				},
				{
					param: 'currency_id',
					type: 'select',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_DISCOUNT_CURRENCY',
					helptext: '',
					displayed: false,
					options: this.currencies,
					reload: 0,
					/*displayedOn: 'type',
					displayedOnValue: 'fixed',*/
				},
				{
					param: 'quantity',
					type: 'number',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_DISCOUNT_QUANTITY',
					helptext: '',
					displayed: false,
					displayedOn: 'illimited',
					displayedOnValue: 0,
					optional: true,
				},
				{
					param: 'available_from',
					type: 'datetime',
					value: '',
					label: 'COM_EMUNDUS_DISCOUNT_AVAILABLE_FROM',
					helptext: '',
					displayed: false,
				},
				{
					param: 'available_to',
					type: 'datetime',
					value: '',
					label: 'COM_EMUNDUS_DISCOUNT_AVAILABLE_TO',
					helptext: '',
					displayed: false,
				},
				{
					param: 'published',
					type: 'toggle',
					value: 1,
					label: 'COM_EMUNDUS_DISCOUNT_PUBLISHED',
					helptext: '',
					displayed: true,
					hideLabel: true,
				},
			],

			loading: true,
			backUrl: 'index.php?option=com_emundus&view=payment&layout=products',
		};
	},
	created() {
		this.loading = true;
		this.getCurrencies();
		this.fields.forEach((field) => {
			if (field.param === 'type') {
				field.options = this.types;
			}
		});

		if (this.discountId > 0) {
			this.getDiscount(this.discountId);
		} else {
			this.loading = false;
		}
	},
	methods: {
		getCurrencies() {
			paymentService.getCurrencies().then((response) => {
				this.currencies = response.data.map((currency) => ({
					value: currency.id,
					label: currency.name + ' (' + currency.symbol + ')',
				}));
				this.fields.forEach((field) => {
					if (field.param === 'currency_id') {
						field.options = this.currencies;
					}
				});
			});
		},
		getDiscount(id) {
			paymentService.getDiscount(id).then((response) => {
				if (response.status) {
					const discountEntity = response.data;

					this.discount = {
						id: discountEntity.id,
						label: discountEntity.label,
						value: discountEntity.value,
						description: discountEntity.description,
						currency_id: discountEntity.currency ? discountEntity.currency.id : 0,
						type: discountEntity.type,
						quantity: discountEntity.quantity,
						available_from: discountEntity.available_from,
						available_to: discountEntity.available_to,
						published: discountEntity.published ? 1 : 0,
					};

					this.fields.forEach((field) => {
						if (field.param in this.discount) {
							field.value = this.discount[field.param];
						}
					});

					this.loading = false;
				} else {
					this.loading = false;
				}
			});
		},
		saveDiscount() {
			this.loading = true;

			const discountData = {
				...this.discount,
				...this.fields.reduce((acc, field) => {
					if (field.type === 'datetime') {
						if (field.value) {
							acc[field.param] = new Date(field.value).toISOString();
						} else {
							acc[field.param] = '';
						}
					} else {
						acc[field.param] = field.value;
					}
					return acc;
				}, {}),
			};

			paymentService.saveDiscount(discountData).then((response) => {
				if (response.status) {
					Swal.fire({
						title: this.translate('COM_EMUNDUS_DISCOUNT_SAVED'),
						icon: 'success',
						showCancelButton: false,
						showConfirmButton: false,
						timer: 2000,
					}).then((e) => {
						window.location.href = this.backUrl;
					});
				}
			});

			this.loading = false;
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
		displayedFields() {
			return this.fields.filter((field) => field.displayed);
		},
	},
};
</script>

<template>
	<div
		id="discount-edit"
		class="tw-mb-6 tw-flex tw-flex-col tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<Back :link="backUrl" :class="'tw-mb-4'" />
		<h1>{{ this.discountId > 0 ? translate('COM_EMUNDUS_DISCOUNT_EDIT') : translate('COM_EMUNDUS_DISCOUNT_ADD') }}</h1>

		<div id="discount" class="tw-mt-7 tw-grid tw-grid-cols-[minmax(0,1fr)_max-content] tw-gap-6" v-if="!loading">
			<div
				v-for="field in displayedFields"
				:key="field.param"
				:class="{
					'tw-flex tw-w-1/2 tw-items-end tw-justify-between tw-gap-2': field.param === 'name',
					'tw-col-span-2': field.param !== 'value' && field.param !== 'type',
					'tw-col-[1] tw-w-full': field.param === 'value',
					'tw-col-[2] tw-w-fit': field.param === 'type',
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
		</div>

		<div class="tw-mt-7 tw-flex tw-justify-end">
			<button type="button" class="tw-btn-primary tw-cursor-pointer" @click="saveDiscount">
				{{ translate('COM_EMUNDUS_DISCOUNT_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
