<template>
	<div id="form-builder-currency">
		<div v-if="loading" class="em-loader"></div>
		<div v-else class="currency-block tw-relative tw-flex tw-w-full tw-items-center">
			<input
				class="currency"
				readonly
				type="text"
				:value="this.element.params['all_currencies_options']['all_currencies_options0'].minimal_value"
			/>
			<span class="currency-icon">
				{{ currencyIcon }}
			</span>
		</div>
	</div>
</template>

<script>
import formbuilderService from '@/services/formbuilder.js';

export default {
	props: {
		element: {
			type: Object,
			required: true,
		},
		type: {
			type: String,
			required: true,
		},
	},
	data() {
		return {
			loading: false,
			currencyOptions: [],
			editable: false,
			dynamicComponent: 0,
		};
	},
	created() {
		this.getCurrencies();
	},
	methods: {
		getCurrencies() {
			formbuilderService.getCurrencies().then((response) => {
				this.currencyOptions = response.data;
			});
		},
	},
	watch: {},
	computed: {
		currencyIcon() {
			const matchingCurrency =
				this.currencyOptions.length > 0
					? this.currencyOptions.find((currency) => {
							if (currency.iso3 === this.element.params['all_currencies_options']['all_currencies_options0'].iso3) {
								return true;
							}
						})
					: null;

			return matchingCurrency
				? matchingCurrency.symbol
				: this.element.params['all_currencies_options']['all_currencies_options0'].iso3;
		},
	},
};
</script>

<style lang="scss">
#form-builder-currency {
	.currency-icon {
		position: absolute;
		right: 0;
		width: auto !important;
		padding-right: var(--em-spacing-4);
	}

	.currency-block {
		height: var(--em-form-height);
		font-size: var(--em-form-font-size);
	}
}
</style>
