<script>
import ProductCard from '@/components/Payment/ProductCard.vue';

export default {
	name: 'Marketplace',
	components: { ProductCard },
	props: {
		products: {
			type: Array,
			required: true,
		},
		currency: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			selectedProducts: [],
		};
	},
	methods: {
		selectProduct(product) {
			const productIndex = this.selectedProducts.findIndex((p) => p.id === product.id);
			if (productIndex !== -1) {
				this.selectedProducts.splice(productIndex, 1);
			} else {
				this.selectedProducts.push(product);
			}
		},
		addToCart() {
			this.$emit('addToCart', this.selectedProducts);
			this.selectedProducts = [];
		},
		close() {
			this.$emit('close');
		},
	},
	computed: {
		totalAmount() {
			return this.selectedProducts.reduce((total, product) => total + product.price, 0);
		},
	},
};
</script>

<template>
	<div id="marketplace" class="tw-flex tw-h-full tw-flex-col tw-justify-start tw-gap-8 tw-pb-[184px]">
		<div id="marketplace-header">
			<p class="tw-text-center">{{ translate('COM_EMUNDUS_MARKETPLACE_INTRO') }}</p>
			<div id="marketplace-filters"></div>
		</div>

		<div id="marketplace-products" class="tw-flex tw-h-[45rem] tw-flex-col tw-gap-6 tw-overflow-y-auto tw-p-1">
			<product-card
				v-for="product in products"
				:product="product"
				:key="product.id"
				@click="selectProduct(product)"
				class="tw-my-4 tw-cursor-pointer tw-duration-300"
				:class="{
					'!tw-bg-main-50 tw-shadow-table-border-profile': selectedProducts.some((p) => p.id === product.id),
				}"
			>
			</product-card>
		</div>

		<div
			id="marketplace-footer"
			class="tw-absolute tw-bottom-0 tw-left-0 tw-flex tw-w-full tw-flex-col tw-gap-6 tw-rounded-2xl tw-border tw-border-neutral-300 tw-bg-neutral-0 tw-p-8"
		>
			<div id="marketplace-recap" class="tw-flex tw-justify-start">
				<p>
					{{ selectedProducts.length + ' ' + translate('COM_EMUNDUS_MARKETPLACE_SELECTED') + ' : ' }}
					<strong>+{{ totalAmount + ' ' + currency.symbol }}</strong>
				</p>
			</div>

			<div id="marketplace-actions" class="tw-flex tw-justify-between">
				<button class="tw-btn-secondary" @click="close">
					{{ translate('COM_EMUNDUS_ACTIONS_CANCEL') }}
				</button>
				<button class="tw-btn-primary" @click="addToCart">
					{{ translate('COM_EMUNDUS_ADD_TO_CART') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
