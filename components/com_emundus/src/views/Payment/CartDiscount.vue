<script>
import paymentService from '@/services/payment.js';

export default {
	name: 'CartDiscount',
	props: {
		concernedProduct: {
			type: Number,
			default: 0,
		},
		selectedAlteration: {
			type: Object,
			required: {
				id: 0,
				discount_id: 0,
				product_id: 0,
				label: '',
				description: '',
				amount: 0,
				type: 'fixed',
			},
		},
		products: [],
	},
	data() {
		return {
			alteration: {
				id: 0,
				discount_id: 0,
				product_id: 0,
				label: '',
				description: '',
				amount: 0,
				type: 'fixed',
			},
			discounts: [],
			types: [
				{
					id: 'fixed',
					name: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT_TYPE_FIXED',
				},
				{
					id: 'percentage',
					name: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT_TYPE_PERCENTAGE',
				},
			],
		};
	},
	created() {
		this.getDiscounts();
		if (this.selectedAlteration) {
			this.alteration = this.selectedAlteration;
		}
	},
	methods: {
		getDiscounts() {
			paymentService
				.getDiscounts()
				.then((response) => {
					this.discounts = response.data.datas;
				})
				.catch((error) => {
					this.$emit('error', error);
				});
		},
		areMandatoryFieldsFilled() {
			let filled = true;

			let mandatoryFields = ['amount', 'type'];

			mandatoryFields.forEach((field) => {
				if (this.alteration[field] === '' || this.alteration[field] === 0) {
					filled = false;
				}
			});

			return filled;
		},
		onClickAddDiscount() {
			if (this.areMandatoryFieldsFilled()) {
				let discountAlteration = this.alteration;
				discountAlteration.amount = -this.alteration.amount;

				this.$emit('addDiscount', this.alteration);
				// Reset the alteration object
				this.alteration = {
					discount_id: 0,
					product_id: 0,
					label: '',
					description: '',
					amount: 0,
					type: 'fixed',
				};
			} else {
				console.log('missing mandatory fields');
			}
		},
		onClickUpdateDiscount() {
			if (this.areMandatoryFieldsFilled()) {
				let discountAlteration = this.alteration;
				discountAlteration.amount = -this.alteration.amount;

				this.$emit('editDiscount', this.alteration);
				// Reset the alteration object
				this.alteration = {
					discount_id: 0,
					product_id: 0,
					label: '',
					description: '',
					amount: 0,
					type: 'fixed',
				};
			} else {
				console.log('missing mandatory fields');
			}
		},
		onSelectDiscount() {
			if (this.alteration.discount_id > 0) {
				let discount = this.discounts.find((discount) => {
					return discount.id === this.alteration.discount_id;
				});

				if (discount) {
					this.alteration.label = discount.label.fr;
					this.alteration.description = discount.description;
					this.alteration.amount = discount.value;
					this.alteration.type = discount.type;
				}
			}
		},
	},
};
</script>

<template>
	<div id="cart-discount" class="tw-flex tw-h-full tw-flex-col tw-justify-between">
		<div id="cart-discount-form" class="tw-flex tw-flex-col tw-gap-8 tw-overflow-y-auto">
			<div class="tw-flex tw-flex-col">
				<label for="discount"> {{ translate('COM_EMUNDUS_CART_DISCOUNT_SELECT_DISCOUNT_LABEL') }} </label>
				<select name="discount" v-model="alteration.discount_id" @change="onSelectDiscount">
					<option value="0">{{ translate('COM_EMUNDUS_CART_EXCEPTIONNAL_DISCOUNT') }}</option>
					<option v-for="discount in discounts" :key="discount.id" :value="discount.id">
						{{ discount.label.fr }}
					</option>
				</select>
			</div>

			<div class="tw-flex tw-flex-col">
				<label for="product"> {{ translate('COM_EMUNDUS_CART_DISCOUNT_SELECT_PRODUCT_CONCERNED_LABEL') }} </label>
				<select name="product" v-model="alteration.product_id">
					<option value="0">{{ translate('PLEASE_SELECT') }}</option>
					<option v-for="product in products" :key="product.id" :value="product.id">
						{{ product.label }}
					</option>
				</select>
			</div>

			<!--<div class="tw-mb-4 tw-flex tw-flex-col">
				<label for="label"> {{ translate('COM_EMUNDUS_CART_DISCOUNT_LABEL') }} </label>
				<input type="text" name="label" v-model="alteration.label" :disabled="alteration.discount_id > 0" />
			</div>-->

			<div class="tw-flex tw-flex-col">
				<label for="description"> {{ translate('COM_EMUNDUS_CART_DISCOUNT_DESCRIPTION_LABEL') }} </label>
				<textarea name="description" v-model="alteration.description" :disabled="alteration.discount_id > 0"></textarea>
			</div>

			<div class="tw-grid tw-grid-cols-[minmax(0,1fr)_max-content] tw-gap-6">
				<div class="tw-col-[1] tw-flex tw-w-full tw-flex-col">
					<label for="amount"> {{ translate('COM_EMUNDUS_CART_DISCOUNT_AMOUNT_LABEL') }} </label>
					<input type="number" name="amount" v-model="alteration.amount" :disabled="alteration.discount_id > 0" />
				</div>

				<div class="tw-col-[2] tw-flex tw-w-fit tw-flex-col">
					<label for="type"> {{ translate('COM_EMUNDUS_CART_DISCOUNT_TYPE_LABEL') }} </label>
					<select name="type" v-model="alteration.type" :disabled="alteration.discount_id > 0">
						<option v-for="type in types" :key="type.id" :value="type.id">
							{{ translate(type.name) }}
						</option>
					</select>
				</div>
			</div>
		</div>
		<div id="cart-discount-actions" class="tw-mt-4 tw-flex tw-justify-between">
			<button class="tw-btn-secondary" @click="$emit('close')">
				{{ translate('COM_EMUNDUS_ACTIONS_CANCEL') }}
			</button>
			<button
				v-if="alteration.id < 1"
				class="tw-btn-primary"
				:disabled="!areMandatoryFieldsFilled"
				@click="onClickAddDiscount"
			>
				{{ translate('COM_EMUNDUS_ADD') }}
			</button>
			<button v-else class="tw-btn-primary" :disabled="!areMandatoryFieldsFilled" @click="onClickUpdateDiscount">
				{{ translate('COM_EMUNDUS_SAVE') }}
			</button>
		</div>
	</div>
</template>

<style scoped>
#cart-discount {
	height: 100%;
}

#cart-discount-form {
	height: 85%;
}
</style>
