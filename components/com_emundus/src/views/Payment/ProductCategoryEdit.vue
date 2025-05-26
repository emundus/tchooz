<script>
import paymentService from '@/services/payment.js';

export default {
	name: 'ProductCategoryEdit',
	props: {
		categoryId: {
			type: Number,
			default: 0,
		},
	},
	data() {
		return {
			category: {
				id: 0,
				label: '',
				published: 1,
			},
		};
	},
	created() {
		if (this.categoryId) {
			this.getCategory(this.categoryId);
		}
	},
	methods: {
		getCategory(id) {
			paymentService.getProductCategory(id).then((response) => {
				if (response.status) {
					this.category = response.data.map((category) => ({
						id: category.id,
						label: category.label,
						published: category.published,
					}));
				} else {
					console.error('Error fetching category:', response.message);
				}
			});
		},
		saveCategory() {
			const data = {
				id: this.category.id,
				label: this.category.label,
				published: this.category.published,
			};

			paymentService.saveProductCategory(data).then((response) => {
				if (response.status) {
					this.$emit('saved', response.data);
					this.$emit('close');
				} else {
					console.error('Error saving category:', response.message);
				}
			});
		},
	},
};
</script>

<template>
	<div
		id="product-category"
		class="tw-flex tw-flex-col tw-gap-8 tw-rounded-2xl tw-border-neutral-300 tw-bg-white tw-p-8 tw-shadow-standard"
	>
		<h1 class="tw-text-center">{{ translate('COM_EMUNDUS_PRODUCT_CATEGORY_ADD') }}</h1>

		<div class="">
			<label for="category-label"></label>
			<input id="category-label" type="text" v-model="category.label" />
		</div>

		<div class="tw-flex tw-justify-end">
			<button class="tw-btn-primary" @click="saveCategory">
				{{ translate('COM_EMUNDUS_PRODUCT_CATEGORY_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
