<script>
import Back from '@/components/Utils/Back.vue';
import Parameter from '@/components/Utils/Parameter.vue';
import paymentService from '@/services/payment.js';
import campaignsService from '@/services/campaigns.js';

import ProductCategoryEdit from '@/views/Payment/ProductCategoryEdit.vue';

export default {
	name: 'ProductEdit',
	components: { Parameter, Back, ProductCategoryEdit },
	props: {
		productId: {
			type: Number,
			default: 0,
		},
	},
	data() {
		return {
			categories: [],
			currencies: [],
			campaigns: [],

			product: {
				id: this.productId,
				label: '',
				price: 0,
				description: '',
				category_id: 0,
				currency_id: 1,
				quantity: 0,
				illimited: 1,
				available_from: '',
				available_to: '',
				published: 1,
				campaigns: [],
			},

			fields: [
				{
					param: 'campaigns',
					type: 'multiselect',
					multiselectOptions: {
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: false,
						asyncRoute: '',
						optionsPlaceholder: '',
						selectLabel: '',
						selectGroupLabel: '',
						selectedLabel: '',
						deselectedLabel: '',
						deselectGroupLabel: '',
						noOptionsText: '',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						tagValidations: [],
						options: this.campaigns,
						optionsLimit: 30,
						label: 'label',
						trackBy: 'value',
					},
					value: [],
					label: 'COM_EMUNDUS_PRODUCT_CAMPAIGNS',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'label',
					type: 'text',
					maxlength: 150,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_PRODUCT_LABEL',
					helptext: '',
					displayed: true,
				},
				{
					param: 'category_id',
					type: 'select',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_PRODUCT_CATEGORY',
					helptext: '',
					displayed: true,
					options: this.categories,
					reload: 0,
					optional: true,
					addNew: {
						label: 'COM_EMUNDUS_PRODUCT_CATEGORY_ADD',
						component: ProductCategoryEdit,
						componentProps: {
							categoryId: 0,
						},
					},
				},
				{
					param: 'description',
					type: 'textarea',
					maxlength: 255,
					placeholder: '',
					value: '',
					label: 'COM_EMUNDUS_PRODUCT_DESCRIPTION',
					helptext: '',
					displayed: true,
					optional: true,
				},
				{
					param: 'picture',
					type: 'file',
					value: '',
					label: 'COM_EMUNDUS_PRODUCT_PICTURE',
					helptext: '',
					displayed: false,
					optional: true,
				},
				{
					param: 'price',
					type: 'number',
					placeholder: '',
					value: 0.0,
					label: 'COM_EMUNDUS_PRODUCT_PRICE',
					step: 0.01,
					pattern: '^[0-9]+(\\.[0-9]{1,2})?$',
					max: 99999999,
					maxlength: 8,
					editable: true,
					displayed: true,
				},
				{
					param: 'currency_id',
					type: 'select',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_PRODUCT_CURRENCY',
					helptext: '',
					displayed: false,
					options: this.currencies,
					reload: 0,
				},
				{
					param: 'illimited',
					type: 'toggle',
					value: 1,
					label: 'COM_EMUNDUS_PRODUCT_ILLIMITED',
					helptext: '',
					displayed: false,
				},
				{
					param: 'quantity',
					type: 'number',
					placeholder: '',
					value: 0,
					label: 'COM_EMUNDUS_PRODUCT_QUANTITY',
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
					label: 'COM_EMUNDUS_PRODUCT_AVAILABLE_FROM',
					helptext: '',
					displayed: false,
				},
				{
					param: 'available_to',
					type: 'datetime',
					value: '',
					label: 'COM_EMUNDUS_PRODUCT_AVAILABLE_TO',
					helptext: '',
					displayed: false,
				},
				{
					param: 'published',
					type: 'toggle',
					value: 1,
					label: 'COM_EMUNDUS_PRODUCT_PUBLISHED',
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
		//this.getCurrencies();
		this.loading = true;
		this.getProductCategories();
		this.getCampaigns().then((response) => {
			if (this.productId) {
				this.getProduct(this.productId);
			} else {
				this.loading = false;
			}
		});
	},
	methods: {
		async getCampaigns() {
			await campaignsService.getAllCampaigns().then((response) => {
				if (response.status) {
					this.campaigns = response.data.datas.map((campaign) => {
						return {
							value: campaign.id,
							label: campaign.label.fr,
						};
					});

					this.fields.forEach((field) => {
						if (field.param === 'campaigns') {
							field.multiselectOptions.options = this.campaigns;
						}
					});
				}
			});
		},
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
		getProductCategories() {
			paymentService.getProductCategories().then((response) => {
				this.categories = response.data.map((category) => ({
					value: category.id,
					label: category.label,
				}));

				this.categories.unshift({
					value: 0,
					label: this.translate('COM_EMUNDUS_PRODUCT_CATEGORY_SELECT'),
				});

				this.fields.forEach((field) => {
					if (field.param === 'category_id') {
						field.options = this.categories;
					}
				});
			});
		},
		getProduct(productId) {
			paymentService
				.getProduct(productId)
				.then((response) => {
					let productEntity = response.data;

					if (productEntity.price) {
						productEntity.price = parseFloat(productEntity.price);
					}

					this.product = {
						id: productEntity.id,
						label: productEntity.label,
						price: productEntity.price,
						description: productEntity.description,
						category_id: productEntity.category ? productEntity.category.id : 0,
						currency_id: productEntity.currency.id,
						quantity: productEntity.quantity,
						illimited: productEntity.illimited ? 1 : 0,
						available_from: productEntity.available_from,
						available_to: productEntity.available_to,
						published: productEntity.published ? 1 : 0,
						campaigns: productEntity.campaigns,
					};

					this.fields.forEach((field) => {
						if (field.param in this.product) {
							if (field.param === 'campaigns') {
								field.value = this.product.campaigns.map((campaignId) => {
									return this.campaigns.find((c) => c.value == campaignId);
								});
							} else {
								field.value = this.product[field.param];
							}
						}
					});

					this.loading = false;
				})
				.catch((error) => {
					console.error('Error fetching product:', error);
				});
		},
		// Hooks
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

		saveProduct() {
			this.loading = true;
			const productData = {
				...this.product,
				...this.fields.reduce((acc, field) => {
					if (field.type === 'multiselect') {
						acc[field.param] = field.value.map((item) => item.value);
					} else if (field.type === 'datetime' && field.value) {
						acc[field.param] = new Date(field.value).toISOString();
					} else {
						acc[field.param] = field.value;
					}
					return acc;
				}, {}),
			};

			paymentService
				.saveProduct(productData)
				.then((response) => {
					if (response.status) {
						Swal.fire({
							title: this.translate('COM_EMUNDUS_PRODUCT_SAVED'),
							icon: 'success',
							showCancelButton: false,
							showConfirmButton: false,
							timer: 2000,
						}).then((e) => {
							window.location.href = this.backUrl;
						});
					}
					this.loading = false;
				})
				.catch((error) => {
					console.error('Error saving product:', error);
					this.loading = false;
				});
		},

		onNewValueAdded(field) {
			if (field.param === 'category_id') {
				this.getProductCategories();
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
		id="product-edit"
		class="tw-mb-6 tw-flex tw-flex-col tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<Back :link="backUrl" :class="'tw-mb-4'" />
		<h1>{{ this.productId > 0 ? translate('COM_EMUNDUS_PRODUCT_EDIT') : translate('COM_EMUNDUS_PRODUCT_ADD') }}</h1>

		<div id="product" class="tw-mt-7 tw-flex tw-flex-col tw-gap-6" v-if="!loading">
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
					@newValueAdded="onNewValueAdded"
				/>
			</div>
		</div>

		<div class="tw-mt-7 tw-flex tw-justify-end">
			<button type="button" class="tw-btn-primary tw-cursor-pointer" @click="saveProduct">
				{{ translate('COM_EMUNDUS_PRODUCT_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
