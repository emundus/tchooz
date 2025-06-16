<script>
import List from '@/views/List.vue';
export default {
	name: 'Transactions',
	components: {
		List,
	},
	props: {
		defaultFilter: {
			type: String,
			default: '',
		},
		readOnly: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			config: {
				transactions: {
					title: 'COM_EMUNDUS_ONBOARD_TRANSACTIONS',
					intro: 'COM_EMUNDUS_ONBOARD_TRANSACTIONS_INTRO',
					tabs: [
						{
							title: 'COM_EMUNDUS_ONBOARD_TRANSACTIONS',
							key: 'transactions',
							controller: 'payment',
							getter: 'gettransactions',
							noData: 'COM_EMUNDUS_ONBOARD_NO_TRANSACTIONS',
							actions: [
								{
									action: 'preview',
									label: 'COM_EMUNDUS_ONBOARD_VISUALIZE',
									controller: 'payment',
									name: 'preview',
									title: 'COM_EMUNDUS_TRANSACTION_DETAILS',
									method: (item) => {
										return this.previewTransaction(item);
									},
								},
							],
							filters: [
								{
									label: 'COM_EMUNDUS_ONBOARD_TRANSACTION_FILTER_STATUS',
									allLabel: 'COM_EMUNDUS_ONBOARD_TRANSACTION_FILTER_STATUS_ALL',
									getter: 'getfiltertransactionstatus',
									controller: 'payment',
									key: 'status',
									values: null,
									multiselect: true,
									alwaysDisplay: true,
								},
								{
									label: 'COM_EMUNDUS_ONBOARD_TRANSACTION_FILTER_METHOD',
									allLabel: 'COM_EMUNDUS_ONBOARD_TRANSACTION_FILTER_METHOD_ALL',
									getter: 'getfiltertransactionmethods',
									controller: 'payment',
									key: 'payment_method_id',
									values: null,
									multiselect: true,
									alwaysDisplay: true,
								},
							],
						},
					],
				},
			},
		};
	},
	created() {
		if (!this.readOnly) {
			this.config.transactions.tabs[0].actions.push({
				action: 'index.php?option=com_emundus&view=payment&layout=transactionedit&id=%id%',
				label: 'COM_EMUNDUS_ONBOARD_MODIFY',
				controller: 'payment',
				type: 'redirect',
				name: 'edit',
			});

			this.config.transactions.tabs[0].actions.push({
				action: 'confirmtransaction&id=%id%',
				label: 'COM_EMUNDUS_ONBOARD_CONFIRM_TRANSACTION',
				controller: 'payment',
				name: 'confirm',
				type: 'action',
				method: 'post',
			});

			this.config.transactions.tabs[0].actions.push({
				action: 'canceltransaction&id=%id%',
				label: 'COM_EMUNDUS_ONBOARD_CANCEL_TRANSACTION',
				controller: 'payment',
				name: 'cancel',
				type: 'action',
				method: 'post',
			});

			this.config.transactions.tabs[0].actions.push({
				action: 'exporttransaction',
				label: 'COM_EMUNDUS_ONBOARD_EXPORT_TRANSACTION',
				controller: 'payment',
				name: 'exportcsv',
				type: 'action',
				method: 'post',
				multiple: true,
				exportModal: true,
			});

			this.config.transactions.tabs[0].filters.push({
				label: 'COM_EMUNDUS_ONBOARD_TRANSACTION_FILTER_APPLICANT',
				allLabel: 'COM_EMUNDUS_ONBOARD_TRANSACTION_FILTER_APPLICANT_ALL',
				getter: 'getfiltertransactionsapplicants',
				controller: 'payment',
				key: 'applicant_id',
				values: null,
				multiselect: true,
				alwaysDisplay: true,
			});

			this.config.transactions.tabs[0].filters.push({
				label: 'COM_EMUNDUS_ONBOARD_TRANSACTION_FILTER_FILES',
				allLabel: 'COM_EMUNDUS_ONBOARD_TRANSACTION_FILTER_FILES_ALL',
				getter: 'getfiltertransactionsfiles',
				controller: 'payment',
				key: 'fnum',
				values: null,
				multiselect: true,
				alwaysDisplay: true,
			});
		}
	},
	methods: {
		previewTransaction(transaction) {
			let html = '<div class="tw-flex tw-flex-col tw-gap-2">';

			transaction.additional_columns
				.filter((column) => {
					return column.display === 'all' || column.display === 'table';
				})
				.forEach((column) => {
					html +=
						'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
						'<div><strong>' +
						column.key +
						'</strong></div>' +
						'<div>' +
						column.value +
						'</div>' +
						'</div> <hr class="tw-m-0">';
				});

			html +=
				'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
				'<div><strong>' +
				this.translate('COM_EMUNDUS_TRANSACTION_EXTERNAL_REFERENCE') +
				'</strong></div>' +
				'<div>' +
				transaction.external_reference +
				'</div>' +
				'</div> <hr class="tw-m-0">';

			html +=
				'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
				'<div><strong>' +
				this.translate('COM_EMUNDUS_TRANSACTION_PRODUCT_QUANTITY') +
				'</strong></div>' +
				'<div>' +
				(transaction.data.products && transaction.data.products.length ? transaction.data.products.length : 0) +
				'</div>' +
				'</div> <hr class="tw-m-0">';

			if (transaction.data.products && transaction.data.products.length > 0) {
				html +=
					'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_PRODUCT_QUANTITY') +
					'</strong></div>' +
					'<div>' +
					transaction.data.products.length +
					'</div>' +
					'</div> <hr class="tw-m-0">';

				// set header, label, price and description
				html +=
					'<div class="tw-grid tw-grid-cols-3 tw-items-center">' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_PRODUCT_LABEL') +
					'</strong></div>' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_PRODUCT_PRICE') +
					'</strong></div>' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_PRODUCT_DESCRIPTION') +
					'</strong></div>' +
					'</div> <hr class="tw-m-0">';

				html += '<div class="tw-grid tw-grid-cols-3 tw-items-center">';
				transaction.data.products.forEach((product) => {
					html +=
						'<div>' +
						product.label +
						'</div>' +
						'<div>' +
						product.displayed_price +
						'</div>' +
						'<div>' +
						product.description +
						'</div>';
				});
				html += '</div>';
			}

			if (transaction.data.alterations && transaction.data.alterations.length > 0) {
				html +=
					'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_ALTERATIONS') +
					'</strong></div>' +
					'<div>' +
					transaction.data.alterations.length +
					'</div>' +
					'</div> <hr class="tw-m-0">';

				// set header, description and amount (type can be fixed or percentage)
				html +=
					'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_ALTERATION_AMOUNT') +
					'</strong></div>' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_ALTERATION_DESCRIPTION') +
					'</strong></div>' +
					'</div> <hr class="tw-m-0">';

				transaction.data.alterations.forEach((alteration) => {
					const amount =
						alteration.type === 'fixed' || alteration.type === 'adjust_balance'
							? alteration.displayed_amount + transaction.currency.symbol
							: alteration.displayed_amount + '%';

					html +=
						'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
						'<div>' +
						amount +
						'</div>' +
						'<div>' +
						alteration.description +
						'</div>' +
						'</div> <hr class="tw-m-0">';
				});
			}

			if (transaction.data.installment) {
				// data.installment is an object that contains number_installment_debit, installment_monthday, installment_effect_date and amounts_by_iteration
				html +=
					'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_INSTALLMENT_NUMBER_DEBIT') +
					'</strong></div>' +
					'<div>' +
					transaction.data.installment.number_installment_debit +
					'</div>' +
					'</div> <hr class="tw-m-0">';
				html +=
					'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_INSTALLMENT_MONTHDAY') +
					'</strong></div>' +
					'<div>' +
					transaction.data.installment.installment_monthday +
					'</div>' +
					'</div> <hr class="tw-m-0">';
				html +=
					'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_INSTALLMENT_EFFECT_DATE') +
					'</strong></div>' +
					'<div>' +
					transaction.data.installment.installment_effect_date +
					'</div>' +
					'</div> <hr class="tw-m-0">';

				// header, iteration and amount
				html +=
					'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_INSTALLMENT_ITERATION') +
					'</strong></div>' +
					'<div><strong>' +
					this.translate('COM_EMUNDUS_TRANSACTION_INSTALLMENT_AMOUNT') +
					'</strong></div>' +
					'</div> <hr class="tw-m-0">';
				transaction.data.installment.amounts_by_iteration.forEach((amount, index) => {
					html +=
						'<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">' +
						'<div>' +
						(index + 1) +
						'</div>' +
						'<div>' +
						amount +
						'</div>' +
						'</div> <hr class="tw-m-0">';
				});
			}

			html += '</div>';

			return html;
		},
	},
};
</script>

<template>
	<div id="transactions-list">
		<list
			:default-lists="config"
			:default-type="'transactions'"
			:default-filter="defaultFilter"
			:encoded="false"
		></list>
	</div>
</template>

<style scoped></style>
