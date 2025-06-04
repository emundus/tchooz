<script>
import Tabs from '@/components/Utils/Tabs.vue';
import Cart from '@/views/Payment/Cart.vue';
import Transactions from '@/views/Payment/Transactions.vue';

export default {
	name: 'CartAppFile',
	props: {
		fnum: {
			type: String,
			required: true,
		},
		cart: {
			type: Object,
			required: true,
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
		paymentMethods: {
			type: Array,
			default: () => [],
		},
	},
	components: { Tabs, Cart, Transactions },
	data() {
		return {
			step: {},
			cartKey: 0,
			tabs: [
				{
					id: 'cart',
					name: 'COM_EMUNDUS_CART_TAB_TITLE',
					active: true,
					displayed: true,
				},
				{
					id: 'transactions',
					name: 'COM_EMUNDUS_TRANSACTIONS_TAB_TITLE',
					active: false,
					displayed: true,
				},
			],
			selectedTab: 'cart',
		};
	},
	created() {
		this.step = this.cart && this.cart.payment_step !== undefined ? this.cart.payment_step : {};
	},
	methods: {
		onChangeTabActive(id) {
			this.selectedTab = id;
		},
	},
};
</script>

<template>
	<div id="cart-app-file">
		<Tabs
			:classes="'tw-overflow-x-auto tw-right-6 tw-flex tw-items-center tw-gap-2 tw-ml-4'"
			:tabs="tabs"
			@changeTabActive="onChangeTabActive"
		></Tabs>

		<Cart
			v-if="selectedTab === 'cart'"
			:cart="cart"
			:step="step"
			:key="cartKey"
			:is-manager="true"
			:readOnly="readOnly"
			:paymentMethods="paymentMethods"
			:manualPaymentMethods="['cheque', 'transfer']"
			@updateCart="cartKey++"
		>
		</Cart>
		<div v-else class="em-card-shadow tw-rounded-2xl tw-border tw-border-neutral-300 tw-bg-white tw-p-6">
			<Transactions :default-filter="'fnum=' + fnum"></Transactions>
		</div>
	</div>
</template>

<style scoped></style>
