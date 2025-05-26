import { FetchClient } from './fetchClient.js';

const client = new FetchClient('payment');

export default {
	async getProduct(productId) {
		try {
			return await client.get('getProductById&id=' + productId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getProducts() {
		try {
			return await client.get('getproducts');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async saveProduct(product) {
		try {
			return await client.post('saveProduct', product);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getDiscount(discountId) {
		try {
			return await client.get('getDiscountById&id=' + discountId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async getDiscounts() {
		try {
			return await client.get('getDiscounts');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},

	async saveDiscount(discount) {
		try {
			return await client.post('saveDiscount', discount);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async saveCustomer(customer) {
		try {
			return await client.post('saveCustomer', customer);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getProductCategory(categoryId) {
		try {
			return await client.get('getProductCategoryById&id=' + categoryId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getProductCategories() {
		try {
			return await client.get('getProductCategories');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async saveProductCategory(category) {
		try {
			return client.post('saveProductCategory', {
				id: category.id,
				label: category.label,
				published: category.published,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getCurrencies() {
		try {
			return await client.get('getCurrencies');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getPaymentMethods() {
		try {
			return await client.get('getPaymentMethods');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getPaymentServices() {
		try {
			return await client.get('getPaymentServices');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async savePaymentStepRules(step) {
		try {
			return await client.post('savePaymentStepRules', step);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async addProductsToCart(cartId, products) {
		try {
			return await client.post('addProductToCart&cart_id=' + cartId + '&product_ids=' + products.join(','));
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async addProductToCart(cartId, productId) {
		try {
			return await client.post('addProductToCart&cart_id=' + cartId + '&product_id=' + productId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async removeProductFromCart(cartId, productId) {
		try {
			return await client.post('removeProductFromCart&cart_id=' + cartId + '&product_id=' + productId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async removeAlterationFromCart(cartId, alterationId) {
		try {
			return await client.post('removeAlterationFromCart&cart_id=' + cartId + '&alteration_id=' + alterationId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async selectPaymentMethod(cartId, paymentMethodId) {
		try {
			return await client.post('selectPaymentMethod&cart_id=' + cartId + '&payment_method_id=' + paymentMethodId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updatePayAdvance(cartId, payAdvance) {
		try {
			return await client.post('updatePayAdvance&cart_id=' + cartId + '&pay_advance=' + payAdvance);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateInstallmentDebitNumber(cartId, number) {
		try {
			return await client.post('updateInstallmentDebitNumber&cart_id=' + cartId + '&number=' + number);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateInstallmentMonthday(cartId, monthday) {
		try {
			return await client.post('updateInstallmentMonthday&cart_id=' + cartId + '&monthday=' + monthday);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async checkoutCart(cartId) {
		try {
			return await client.post('checkoutCart&cart_id=' + cartId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async confirmCart(cartId) {
		try {
			return await client.post('confirmCart&cart_id=' + cartId);
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async addAlterationToCart(cartId, alteration) {
		try {
			return await client.post('addAlterationToCart', {
				cart_id: cartId,
				...alteration,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async updateCartAlteration(cartId, alteration) {
		try {
			return await client.post('updateCartAlteration', {
				cart_id: cartId,
				...alteration,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getCountries() {
		try {
			return await client.get('getCountries');
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async getTransationsQueueHistory(synchronizerId) {
		try {
			return await client.get('getTransationsQueueHistory', {
				synchronizer_id: synchronizerId,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
	async editTransaction(transaction) {
		if (!transaction || !transaction.id || !transaction.external_reference || !transaction.status) {
			return {
				status: false,
				msg: 'Invalid transaction data',
			};
		}

		try {
			return await client.post('editTransaction', {
				id: transaction.id,
				reference: transaction.external_reference,
				status: transaction.status,
			});
		} catch (e) {
			return {
				status: false,
				msg: e.message,
			};
		}
	},
};
