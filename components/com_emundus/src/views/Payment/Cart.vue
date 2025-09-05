<script>
import CustomerAddress from '@/views/Payment/CustomerAddress.vue';
import Info from '@/components/utils/Info.vue';
import Modal from '@/components/Modal.vue';
import Marketplace from '@/views/Payment/Marketplace.vue';
import CartDiscount from '@/views/Payment/CartDiscount.vue';

import paymentService from '@/services/payment.js';
import errors from '@/mixins/errors.js';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'Cart',
	props: {
		cart: {
			type: Object,
			required: true,
		},
		step: {
			type: Object,
			required: true,
		},
		paymentMethods: {
			type: Array,
			default: () => [],
		},
		manualPaymentMethods: {
			type: Array,
			default: () => [],
		},
		isManager: {
			type: Boolean,
			default: false,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			// Add any local state here if needed
			paymentChoice: 'total',
			loading: false,
			alterationToEdit: null,
			discountModalKey: 0,
			customExternalReference: '',
		};
	},
	mixins: [errors, alerts],
	components: {
		CustomerAddress,
		Info,
		Marketplace,
		Modal,
		CartDiscount,
	},
	created() {
		if (!this.cart.selected_payment_method) {
			this.cart.selected_payment_method = {
				id: 0,
				label: '',
			};
		}
	},
	methods: {
		onAddProductsToCart(products) {
			this.addProductsToCart(products);
			this.onCloseMarketplace();
		},
		onAddDiscountToCart(alteration) {
			paymentService.addAlterationToCart(this.cart.id, alteration).then((response) => {
				if (response.status) {
					this.fillCart(response.data);
				} else {
					console.error('Error adding discount to cart:', response.message);
				}
			});

			this.onCloseDiscountModal();
		},
		onUpdateDiscount(alteration) {
			paymentService.updateCartAlteration(this.cart.id, alteration).then((response) => {
				if (response.status) {
					this.fillCart(response.data);
				} else {
					console.error('Error updating discount', response.message);
				}
			});

			this.onCloseDiscountModal();
		},
		addProductsToCart(products) {
			if (products.length > 0) {
				this.cart.products.push(...products);

				paymentService
					.addProductsToCart(
						this.cart.id,
						products.map((product) => product.id),
					)
					.then((response) => {
						if (response.status) {
							this.fillCart(response.data);
						} else {
							console.error('Error adding products to cart:', response.message);
						}
					})
					.catch((error) => {
						console.error('Error adding products to cart:', error);
					});
			}
		},
		addProductToCart(productId) {
			const product = this.cart.available_products.find((available_product) => available_product.id === productId);
			if (product) {
				this.cart.products.push(product);

				paymentService
					.addProductToCart(this.cart.id, productId)
					.then((response) => {
						if (response.status) {
							this.fillCart(response.data);
						} else {
							console.error('Error adding product to cart:', response.message);
						}
					})
					.catch((error) => {
						console.error('Error adding product to cart:', error);
					});
			}
		},
		removeProductFromCart(productId) {
			const productIndex = this.cart.products.findIndex((product) => product.id === productId);
			if (productIndex !== -1) {
				const product = this.cart.products[productIndex];
				this.cart.products.splice(productIndex, 1);

				paymentService
					.removeProductFromCart(this.cart.id, productId)
					.then((response) => {
						if (response.status) {
							this.fillCart(response.data);
						} else {
							console.error('Error removing product from cart:', response.message);
						}
					})
					.catch((error) => {
						console.error('Error removing product from cart:', error);
					});
			}
		},
		editAlteration(alteration) {
			this.openDiscountModal({
				id: alteration.id,
				discount_id: 0,
				product_id: alteration.product_id ? alteration.product_id : 0,
				label: '',
				description: alteration.description,
				amount: -alteration.amount, // in front only discounts so we put the sign back to normal
				type: alteration.type,
			});
		},
		removeAlterationFromCart(alteration) {
			if (this.isManager && alteration.automation !== 1) {
				paymentService.removeAlterationFromCart(this.cart.id, alteration.id).then((response) => {
					if (response.status) {
						this.fillCart(response.data);
					} else {
						console.error('Error removing alteration from cart:', response.message);
					}
				});
			}
		},
		isMandatory(product) {
			const availableProduct = this.cart.available_products.find(
				(available_product) => available_product.id === product.id,
			);

			return product.mandatory || (availableProduct && availableProduct.mandatory == 1);
		},

		checkoutCart() {
			this.loading = true;

			paymentService.checkoutCart(this.cart.id, this.customExternalReference).then((response) => {
				if (response.status) {
					if (response.data && response.data.transaction_confirmed) {
						if (response.data.message) {
							this.alertSuccess(response.data.message).then(() => {
								if (response.data.redirect) {
									window.location.href = response.data.redirect;
								}
							});
						} else if (response.data.redirect) {
							window.location.href = response.data.redirect;
						} else {
							window.location.href = '/';
						}
					} else {
						// data contains form, with the action, the method and the fields
						const form = response.data;
						const formElement = document.createElement('form');

						if (form.type === 'form') {
							formElement.setAttribute('method', form.method);
							formElement.setAttribute('action', form.action);
							formElement.setAttribute('target', '_self');
							formElement.setAttribute('id', 'checkout-form');
							formElement.setAttribute('style', 'display: none;');

							if (form.fields && typeof form.fields === 'object') {
								for (const [key, value] of Object.entries(form.fields)) {
									const input = document.createElement('input');
									input.setAttribute('type', 'hidden');
									input.setAttribute('name', key);
									input.setAttribute('value', value);
									formElement.appendChild(input);
								}

								document.body.appendChild(formElement);
								formElement.submit();
								document.body.removeChild(formElement);
							} else {
								this.displayError('COM_EMUNDUS_ERROR_OCCURED', 'COM_EMUNDUS_CART_FAILED_TO_PROCESS_PAYMENT');
							}
						} else if (form.type === 'redirect') {
							window.location.href = form.action;
						}
					}
					this.loading = false;
				} else {
					this.loading = false;
					this.displayError('COM_EMUNDUS_ERROR_OCCURED', response.msg);
				}
			});
		},
		confirmCart() {
			if (this.isManager && !this.readOnly) {
				if (!this.manualPaymentMethods.includes(this.cart.selected_payment_method.name)) {
					this.customExternalReference = '';
				}

				this.alertConfirm('COM_EMUNDUS_CONFIRM_CART', 'COM_EMUNDUS_CONFIRM_CART_HELPTEXT').then((result) => {
					if (result.value) {
						paymentService.confirmCart(this.cart.id, this.customExternalReference).then((response) => {
							if (response.status) {
								this.alertSuccess('COM_EMUNDUS_CART_CONFIRMED');
								window.location.reload();
							} else {
								this.displayError('COM_EMUNDUS_ERROR_OCCURED', response.msg);
							}
						});
					}
				});
			}
		},
		onSelectPaymentMethod() {
			if (this.cart.selected_payment_method && this.cart.selected_payment_method.id) {
				paymentService
					.selectPaymentMethod(this.cart.id, this.cart.selected_payment_method.id)
					.then((response) => {
						if (response.status) {
							this.fillCart(response.data);
						} else {
							this.displayError('COM_EMUNDUS_ERROR_OCCURED', response.msg);
						}
					})
					.catch((error) => {
						console.error('Error selecting payment method:', error);
					});
			}
		},
		fillCart(newCart) {
			this.cart.products = newCart.products;
			this.cart.total = newCart.total;
			this.cart.displayed_total = newCart.displayed_total;
			this.cart.payment_methods = newCart.payment_methods;
			this.cart.selected_payment_method = newCart.selected_payment_method
				? newCart.selected_payment_method
				: { id: 0, label: '' };
			this.cart.number_installment_debit = newCart.number_installment_debit;
			this.cart.total_advance = newCart.total_advance;
			this.cart.displayed_total_advance = newCart.displayed_total_advance;
			this.cart.pay_advance = newCart.pay_advance;
			this.cart.advance_type = newCart.advance_type;
			this.cart.alterations = newCart.alterations;
			this.cart.available_products = newCart.available_products;
			this.cart.customer = newCart.customer;
			this.cart.amounts_by_iterations = newCart.amounts_by_iterations;

			this.$emit('updateCart');
		},

		onUpdateInstallmentDebitNumber() {
			// verify if the number of installments is valid (inside values given by installmentOptions)
			if (
				this.cart.number_installment_debit < 0 ||
				!this.installmentOptions.some((option) => option.value === this.cart.number_installment_debit)
			) {
				this.cart.number_installment_debit = 0;
			}

			if (this.cart.number_installment_debit > 0 && this.cart.number_installment_debit) {
				paymentService
					.updateInstallmentDebitNumber(this.cart.id, this.cart.number_installment_debit)
					.then((response) => {
						if (response.status) {
							this.fillCart(response.data);
						} else {
							this.displayError('COM_EMUNDUS_ERROR_OCCURED', response.msg);
						}
					});
			}
		},
		onUpdateInstallmentMonthDay() {
			if (
				this.step.installment_monthday < 1 &&
				this.cart.installment_monthday > 1 &&
				this.cart.installment_monthday < 32
			) {
				paymentService.updateInstallmentMonthday(this.cart.id, this.cart.installment_monthday).then((response) => {
					if (response.status) {
						this.fillCart(response.data);
					} else {
						this.displayError('COM_EMUNDUS_ERROR_OCCURED', response.msg);
					}
				});
			} else {
				this.displayError('COM_EMUNDUS_CANNOT_UPDATE_INSTALLMENT_MONTHDAY');
				this.cart.installment_monthday = 1;
			}
		},
		onChangePayAdvance() {
			switch (this.step.advance_type) {
				case 0: // forbidden to pay advance
					this.cart.pay_advance = 0;
					break;
				case 1: // free to pay advance
					break;
				case 2: // force to pay advance
					this.cart.pay_advance = 1;
					break;
			}

			paymentService.updatePayAdvance(this.cart.id, this.cart.pay_advance).then((response) => {
				if (!response.status) {
					this.displayError('COM_EMUNDUS_ERROR_OCCURED', response.msg);
				}
			});
		},
		openMarketplace() {
			this.$refs.modalMarketplace.open();
		},
		onCloseMarketplace() {
			this.$refs.modalMarketplace.close();
		},
		openDiscountModal(alteration = null) {
			if (this.isManager) {
				if (alteration !== null) {
					this.alterationToEdit = alteration;
				} else {
					this.alterationToEdit = null;
				}
				this.discountModalKey++;

				this.$refs.modalDiscount.open();
			}
		},
		onCloseDiscountModal() {
			this.alterationToEdit = null;
			this.$refs.modalDiscount.close();
		},
	},
	computed: {
		nonSelectedProducts() {
			return this.cart && this.cart.available_products && this.cart.available_products.length > 0
				? this.cart.available_products.filter((product) => {
						return !this.cart.products.some((p) => p.id === product.id);
					})
				: [];
		},
		alterationsByProduct() {
			let alterations = {};

			if (this.cart.alterations) {
				this.cart.alterations.forEach((alteration) => {
					if (alteration.product_id && alteration.product_id > 0) {
						if (!alterations[alteration.product_id]) {
							alterations[alteration.product_id] = [];
						}
						alterations[alteration.product_id].push(alteration);
					}
				});
			}

			return alterations;
		},
		cartAlterations() {
			return this.cart && this.cart.alterations
				? this.cart.alterations.filter((alteration) => {
						return !alteration.product_id || alteration.product_id === 0 || alteration.product_id === null;
					})
				: [];
		},
		displayedMehtods() {
			let methods = this.isManager ? this.paymentMethods : this.cart.payment_methods;

			return methods.filter((method) => {
				if (method.name === 'sepa') {
					let displaySepa = false;
					this.step.installment_rules.forEach((rule) => {
						if (rule.from_amount <= this.cart.total && rule.to_amount >= this.cart.total) {
							displaySepa = true;
						}
					});

					return displaySepa;
				}

				return true;
			});
		},
		installmentOptions() {
			let options = [];

			this.step.installment_rules.forEach((rule) => {
				if (rule.from_amount <= this.cart.total && rule.to_amount >= this.cart.total) {
					for (let i = rule.min_installments; i <= rule.max_installments; i++) {
						// only add the option if it is not already in the list
						if (!options.some((option) => option.value === i)) {
							// add the option to the list
							options.push({
								label: `${i} ${this.translate('COM_EMUNDUS_CART_INSTALLMENT_NUMBER_TIMES')}`,
								value: i,
							});
						}
					}
				}
			});

			// sort the options by value
			options.sort((a, b) => {
				return a.value - b.value;
			});

			return options;
		},
		installmentRecap() {
			if (!this.cart.amounts_by_iterations || this.cart.amounts_by_iterations.length === 0) {
				return '';
			}

			const firstAmount = this.cart.amounts_by_iterations[0];
			const allSame = this.cart.amounts_by_iterations.every((amount) => amount === firstAmount);

			if (allSame) {
				return `${this.translate('COM_EMUNDUS_CART_INSTALLMENT_NUMBER_RECAP')} ${this.cart.amounts_by_iterations.length} ${this.translate('COM_EMUNDUS_CART_INSTALLMENT_NUMBER_TIMES')} ${firstAmount} ${this.cart.currency.symbol}`;
			} else {
				let firstCount = 1;
				let secondAmount = null;
				let secondCount = 0;

				for (let i = 1; i < this.cart.amounts_by_iterations.length; i++) {
					if (this.cart.amounts_by_iterations[i] === firstAmount) {
						firstCount++;
					} else {
						secondAmount = this.cart.amounts_by_iterations[i];
						secondCount++;
					}
				}

				return `${this.translate('COM_EMUNDUS_CART_INSTALLMENT_NUMBER_RECAP')} ${firstCount} ${this.translate('COM_EMUNDUS_CART_INSTALLMENT_NUMBER_TIMES')} ${firstAmount} ${this.cart.currency.symbol} ${this.translate('COM_EMUNDUS_CART_INSTALLMENT_RECAP_THEN')} ${secondCount} ${this.translate('COM_EMUNDUS_CART_INSTALLMENT_NUMBER_TIMES')} ${secondAmount} ${this.cart.currency.symbol}`;
			}
		},
	},
};
</script>

<template>
	<div
		id="cart"
		class="tw-mb-6 tw-flex tw-flex-col tw-gap-8 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 !tw-bg-white tw-p-6 tw-shadow-standard"
	>
		<h1 v-if="!isManager">{{ translate('COM_EMUNDUS_CART') }}</h1>
		<h1 v-else>{{ translate('COM_EMUNDUS_CART_MANAGER') }}</h1>

		<div id="recap" class="tw-flex tw-flex-col tw-gap-6">
			<h2 v-if="!isManager">{{ translate('COM_EMUNDUS_CART_RECAP') }}</h2>
			<h2 v-else>{{ translate('COM_EMUNDUS_CART_MANAGER_RECAP') + ' ' + step.label }}</h2>

			<div class="tw-flex tw-flex-col tw-gap-2 tw-rounded-2xl tw-border tw-border-neutral-300 tw-p-6">
				<div v-for="(product, index) in cart.products" :key="product.id">
					<div class="tw-flex tw-flex-row tw-items-start tw-justify-between tw-gap-2">
						<div class="tw-flex tw-flex-row tw-items-center tw-justify-start tw-gap-3">
							<span
								v-if="!isMandatory(product) && !readOnly"
								class="material-symbols-outlined tw-cursor-pointer tw-rounded-lg tw-border tw-border-red-500 tw-bg-red-500 tw-p-2.5 tw-text-neutral-0 hover:tw-bg-neutral-0 hover:tw-text-red-500"
								@click="removeProductFromCart(product.id)"
								>remove_shopping_cart</span
							>
							<div class="tw-flex tw-flex-col tw-justify-between tw-gap-1">
								<p>{{ product.label }}</p>
								<p class="tw-text-sm tw-text-gray-500">{{ product.description }}</p>
							</div>
						</div>
						<div class="tw-flex tw-flex-grow tw-justify-end">
							<p class="tw-whitespace-nowrap tw-text-base tw-text-neutral-900">{{ product.displayed_price }}</p>
						</div>
					</div>

					<!-- display product alterations if there are, it is in cart.alterations and it contains the product_id -->
					<div
						v-for="alteration in alterationsByProduct[product.id]"
						:key="alteration.id"
						class="tw-my-4 tw-flex tw-items-center tw-justify-between"
					>
						<div class="tw-flex tw-flex-row tw-items-center tw-gap-3">
							<div
								v-if="isManager === true && alteration.automation !== 1 && !readOnly"
								class="tw-flex tw-flex-row tw-gap-2"
							>
								<button class="tw-btn-red tw-p-2.5" @click="removeAlterationFromCart(alteration)">
									<span class="material-symbols-outlined">delete</span>
								</button>
								<button
									v-if="alteration.discount_id < 1"
									class="not-to-close-modal tw-btn-primary tw-p-2.5"
									@click="editAlteration(alteration)"
								>
									<span class="material-symbols-outlined">edit</span>
								</button>
							</div>
							<p class="tw-text-sm tw-text-gray-500">— {{ this.translate(alteration.description) }}</p>
						</div>
						<p class="tw-whitespace-nowrap tw-text-base tw-text-gray-500">
							<span>{{ alteration.displayed_amount }}</span>
							<span v-if="alteration.type === 'fixed' || alteration.type === 'adjust_balance'">
								{{ cart.currency.symbol }}
							</span>
							<span v-else-if="alteration.type === 'percentage'"> % </span>
						</p>
					</div>
					<hr
						v-if="index + 1 !== cart.products.length || cartAlterations.length > 0"
						:class="{ 'tw-mb-2': index + 1 === cart.products.length }"
					/>
				</div>

				<div id="cart-alterations" v-if="cartAlterations.length > 0">
					<div
						v-for="(alteration, number) in cartAlterations"
						:key="alteration.id"
						class="tw-mb-4 tw-flex tw-flex-col tw-gap-3"
					>
						<div class="tw-flex tw-flex-row tw-items-center tw-justify-between tw-gap-2">
							<div class="tw-flex tw-flex-row tw-items-center tw-gap-3">
								<div
									v-if="
										isManager === true &&
										alteration.automation !== 1 &&
										alteration.type !== 'adjust_balance' &&
										!readOnly
									"
									class="tw-flex tw-flex-row tw-gap-2"
								>
									<button
										v-if="alteration.discount_id < 1"
										class="not-to-close-modal tw-btn-primary tw-p-2.5"
										@click="editAlteration(alteration)"
									>
										<span class="material-symbols-outlined">edit</span>
									</button>
									<button class="tw-btn-red tw-p-2.5" @click="removeAlterationFromCart(alteration)">
										<span class="material-symbols-outlined">delete</span>
									</button>
								</div>
								<p>{{ this.translate(alteration.description) }}</p>
							</div>
							<p class="tw-text-base tw-text-gray-500">
								<span>{{ alteration.amount }}</span>
								<span v-if="alteration.type === 'fixed' || alteration.type === 'adjust_balance'">
									{{ cart.currency.symbol }}
								</span>
								<span v-else-if="alteration.type === 'percentage'"> % </span>
							</p>
						</div>
						<hr v-if="number + 1 !== cartAlterations.length" class="tw-m-0" />
					</div>
				</div>

				<div id="cart-actions" class="tw-flex tw-flex-row tw-flex-wrap tw-justify-end tw-gap-2">
					<button
						v-if="nonSelectedProducts.length > 0 && !readOnly"
						class="not-to-close-modal tw-btn-primary"
						@click="openMarketplace"
					>
						<span class="material-symbols-outlined tw-mr-2">add_circle</span>
						<span>{{ translate('COM_EMUNDUS_ADD_PRODUCT') }}</span>
					</button>

					<button
						v-if="isManager === true && !readOnly"
						class="not-to-close-modal tw-btn-primary"
						@click="(e) => openDiscountModal(null)"
					>
						<span class="material-symbols-outlined tw-mr-2">sell</span>
						<span>{{ translate('COM_EMUNDUS_ADD_DISCOUNT') }}</span>
					</button>
				</div>

				<div class="tw-flex tw-justify-between tw-border-t tw-border-neutral-500 tw-pt-4">
					<p :class="{ 'tw-text-base tw-font-bold': step.advance_type !== 2 }">{{ translate('COM_EMUNDUS_TOTAL') }}</p>
					<p :class="{ 'tw-text-base tw-font-bold': step.advance_type !== 2 }">
						{{ cart.displayed_total }}
					</p>
				</div>
				<div v-if="step.advance_type !== 0" class="tw-flex tw-justify-between">
					<p v-if="step.advance_type !== 2" class="tw-text-sm">
						{{ translate('COM_EMUNDUS_TOTAL_ADVANCE') }}
					</p>
					<p v-else class="tw-text-base tw-font-bold">{{ translate('COM_EMUNDUS_TOTAL_ADVANCE_FORCED') }}</p>

					<p :class="{ 'tw-text-sm': step.advance_type === 1, 'tw-text-base tw-font-bold': step.advance_type !== 1 }">
						{{ cart.displayed_total_advance }}
					</p>
				</div>
			</div>
		</div>

		<modal
			v-if="!readOnly"
			id="marketplace-modal"
			:name="'modalMarketplace'"
			:center="true"
			:open-on-create="false"
			ref="modalMarketplace"
			:classes="'tw-rounded-coordinator-cards tw-border tw-border-neutral-300 !tw-bg-white tw-p-8 tw-shadow tw-drop-shadow-lg tw-flex tw-flex-col tw-gap-8'"
			:title="'COM_EMUNDUS_ADD_PRODUCT'"
			:height="'80%'"
			:width="'80%'"
		>
			<marketplace
				:products="nonSelectedProducts"
				:currency="cart.currency"
				@addToCart="onAddProductsToCart"
				@close="onCloseMarketplace"
			>
			</marketplace>
		</modal>

		<modal
			v-if="isManager && !readOnly"
			id="discount-modal"
			:name="'modalDiscount'"
			:center="true"
			:open-on-create="false"
			ref="modalDiscount"
			:classes="'tw-rounded-coordinator-cards tw-border tw-border-neutral-300 !tw-bg-white tw-p-8 tw-shadow tw-drop-shadow-lg tw-flex tw-flex-col tw-gap-8'"
			:title="this.alterationToEdit !== null ? 'COM_EMUNDUS_EDIT_DISCOUNT' : 'COM_EMUNDUS_ADD_DISCOUNT'"
			:height="'80%'"
			:width="'80%'"
		>
			<CartDiscount
				:key="discountModalKey"
				:selected-alteration="alterationToEdit"
				:products="cart.products"
				@close="onCloseDiscountModal"
				@addDiscount="onAddDiscountToCart"
				@editDiscount="onUpdateDiscount"
				@updateDiscount="onUpdateDiscount"
			></CartDiscount>
		</modal>

		<div id="payment-rules" class="tw-flex tw-flex-col tw-gap-6">
			<h2>{{ translate('COM_EMUNDUS_CART_PAYMENT_RULES') }}</h2>

			<Info v-if="step.description.length > 0" :text="step.description" />

			<div v-if="step.advance_type === 1" class="tw-flex tw-flex-col tw-gap-2">
				<label class="tw-mb-0">{{ translate('COM_EMUNDUS_CART_PAYMENT_PAY_ADVANCE_OR_TOTAL_LABEL') }}</label>

				<div class="tw-flex tw-flex-col tw-gap-1">
					<div class="tw-flex tw-flex-row tw-items-center">
						<input
							class="tw-cursor-pointer"
							type="radio"
							id="payment-total"
							name="payment-advance"
							value="0"
							v-model="cart.pay_advance"
							@change="onChangePayAdvance"
						/>
						<label for="payment-total" class="tw-mb-0 tw-cursor-pointer">{{
							translate('COM_EMUNDUS_CART_PAYMENT_PAY_TOTAL') + '(' + cart.displayed_total + ')'
						}}</label>
					</div>

					<div class="tw-flex tw-flex-row tw-items-center">
						<input
							class="tw-cursor-pointer"
							type="radio"
							id="payment-advance"
							name="payment-advance"
							value="1"
							v-model="cart.pay_advance"
							@change="onChangePayAdvance"
						/>
						<label for="payment-advance" class="tw-mb-0 tw-cursor-pointer">{{
							translate('COM_EMUNDUS_CART_PAYMENT_PAY_ADVANCE') + '(' + cart.displayed_total_advance + ')'
						}}</label>
					</div>
				</div>
			</div>

			<div id="payment-method" class="tw-flex tw-flex-col tw-gap-2">
				<label class="tw-mb-0">{{ translate('COM_EMUNDUS_CART_PAYMENT_METHOD') }}</label>

				<Info
					v-if="cart.payment_methods.length === 0"
					class="tw-mt-4"
					text="COM_EMUNDUS_CART_NO_PAYMENT_METHODS"
					icon="error"
					iconColor="tw-text-red-500"
					bgColor="tw-bg-red-50"
				/>

				<div id="payment-methods" class="tw-flex tw-flex-col tw-gap-2">
					<!-- radio inputs to select the payment method -->

					<div
						v-if="!readOnly"
						v-for="method in displayedMehtods"
						:key="method.id"
						class="tw-flex tw-flex-row tw-items-center"
					>
						<input
							type="radio"
							:id="'payment-method-' + method.id"
							:name="'payment-method'"
							:value="method.id"
							class="peer tw-mb-0 tw-hidden tw-w-fit"
							v-model="cart.selected_payment_method.id"
							@change="onSelectPaymentMethod"
						/>
						<label :for="'payment-method-' + method.id" class="tw-m-0 tw-cursor-pointer">
							{{ method.label }}
						</label>
					</div>

					<p v-else>{{ cart.selected_payment_method.label }}</p>
				</div>

				<div
					v-if="isManager && manualPaymentMethods.includes(cart.selected_payment_method.name) && !readOnly"
					id="override_transaction_reference"
					class="tw-mt-4"
				>
					<label>{{ translate('COM_EMUNDUS_CART_OVERRIDE_EXTERNAL_REFERENCE') }}</label>
					<input
						type="text"
						v-model="customExternalReference"
						:placeholder="translate('COM_EMUNDUS_CART_OVERRIDE_EXTERNAL_REFERENCE_PLACEHOLDER')"
					/>
				</div>

				<div v-if="cart.selected_payment_method.name === 'sepa'" class="tw-mt-4">
					<div class="tw-flex tw-flex-row tw-items-center tw-gap-2">
						<div class="tw-flex tw-flex-col" v-if="!readOnly">
							<label for="installment-debit-options">{{ translate('COM_EMUNDUS_CART_INSTALLMENT_NUMBER') }}</label>
							<select
								v-model="cart.number_installment_debit"
								name="installment-debit-options"
								@change="onUpdateInstallmentDebitNumber"
							>
								<option v-for="option in installmentOptions" :key="option.value" :value="option.value">
									{{ option.label }}
								</option>
							</select>
						</div>
						<p v-if="cart.number_installment_debit > 0">
							{{ installmentRecap }}
						</p>
					</div>

					<div id="installment_monthday" class="tw-mt-4">
						<p v-if="step.installment_monthday > 0">
							{{
								translate('COM_EMUNDUS_CART_INSTALLMENT_MONTHDAY_WILL_BE') +
								step.installment_monthday +
								translate('COM_EMUNDUS_CART_INSTALLMENT_DAY_MONTHLY')
							}}
						</p>

						<div v-else-if="!readOnly">
							<label>{{ translate('COM_EMUNDUS_CART_INSTALLMENT_MONTHDAY_LABEL') }}</label>
							<input
								class="tw-mt-2"
								type="number"
								min="1"
								max="31"
								step="1"
								v-model="cart.installment_monthday"
								@change="onUpdateInstallmentMonthDay"
							/>
						</div>

						<p v-else>
							{{
								translate('COM_EMUNDUS_CART_INSTALLMENT_MONTHDAY_WILL_BE') +
								cart.installment_monthday +
								translate('COM_EMUNDUS_CART_INSTALLMENT_DAY_MONTHLY')
							}}
						</p>
					</div>

					<div v-if="step.display_installment_effect_date" class="tw-mt-4">
						<p>
							{{ translate('COM_EMUNDUS_CART_INSTALLMENT_WILL_BEGIN') + ' ' + step.display_installment_effect_date }}
						</p>
					</div>
				</div>
			</div>
		</div>

		<div id="customer" class="tw-flex tw-flex-col tw-gap-6">
			<h2>{{ translate('COM_EMUNDUS_CART_CUSTOMER_ADDRESS') }}</h2>
			<CustomerAddress :cart-id="cart.id" :customer="cart.customer" :readOnly="readOnly"></CustomerAddress>
		</div>

		<div class="tw-flex tw-justify-end">
			<button v-if="isManager !== true && !readOnly" id="checkout" class="tw-btn-primary" @click="checkoutCart">
				{{ translate('COM_EMUNDUS_CHECKOUT') }}

				(<span v-if="cart.pay_advance == 0">{{ cart.displayed_total }}</span>
				<span v-else-if="cart.pay_advance == 1">{{ cart.displayed_total_advance }}</span
				>)
			</button>

			<button v-if="isManager === true && !readOnly" id="confirm-cart" class="tw-btn-primary" @click="confirmCart">
				{{ translate('COM_EMUNDUS_CONFIRM_CART') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
