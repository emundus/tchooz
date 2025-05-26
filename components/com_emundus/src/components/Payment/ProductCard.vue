<script>
export default {
	name: 'ProductCard',
	props: {
		product: {
			type: Object,
			required: true,
		},
	},
};
</script>

<template>
	<div
		id="product-card"
		class="!tw-m-0 tw-flex tw-flex-row tw-justify-between tw-gap-6 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-8 tw-shadow-standard"
	>
		<div id="product-infos" class="tw-flex tw-flex-col tw-justify-between tw-gap-6">
			<div id="product-title">{{ product.label }}</div>
			<p id="product-description">{{ product.description }}</p>
			<div
				id="product-price"
				class="tw-flex tw-flex-row tw-items-center tw-gap-2 tw-rounded-full tw-border tw-border-neutral-700 tw-bg-neutral-300 tw-px-2 tw-py-1 tw-text-neutral-700"
			>
				<span class="material-symbols-outlined tw-cursor-pointer tw-text-2xl">payments</span>
				<span>{{ product.displayed_price }}</span>
			</div>
		</div>
		<!--<div id="product-image" class="tw-flex tw-h-[140px] tw-w-[140px] tw-min-w-[140px] tw-flex-col tw-gap-4">
			<img v-if="product.image" :src="product.image" :alt="product.label" class="tw-rounded-lg" />
			<img v-else src="https://picsum.photos/200" :alt="product.label" class="tw-rounded-lg" />
		</div>-->
	</div>
</template>

<style scoped></style>
