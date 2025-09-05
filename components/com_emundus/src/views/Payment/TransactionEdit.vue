<script>
import paymentService from '@/services/payment';
import Back from '@/components/Utils/Back.vue';
import alerts from '@/mixins/alerts';

export default {
	name: 'TransactionEdit',
	components: { Back },
	props: {
		transaction: {
			type: Object,
			required: true,
		},
		statuses: {
			type: Array,
			default: () => [],
		},
		services: {
			type: Array,
			default: () => [],
		},
	},
	mixins: [alerts],
	data() {
		return {
			backUrl: 'index.php?option=com_emundus&view=payment&layout=transactions',
		};
	},
	methods: {
		saveTransaction() {
			paymentService.editTransaction(this.transaction).then((response) => {
				if (response.status) {
					this.alertSuccess('COM_EMUNDUS_TRANSACTION_EDIT_SUCCESS');
				} else {
					this.alertError('COM_EMUNDUS_TRANSACTION_EDIT_ERROR', response.msg);
				}
			});
		},
		getServiceName(serviceId) {
			const service = this.services.find((s) => s.id === serviceId);
			return service ? service.name : this.translate('COM_EMUNDUS_TRANSACTION_UNKNOWN_SERVICE');
		},
	},
};
</script>

<template>
	<div
		id="transaction-edit"
		class="tw-mb-6 tw-flex tw-flex-col tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<Back :link="backUrl" :class="'tw-mb-4'" />

		<div id="fields">
			<div class="tw-flex tw-flex-col tw-gap-3 tw-p-4">
				<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
					<div>
						<strong>{{ translate('COM_EMUNDUS_TRANSACTION_FNUM') }}</strong>
					</div>
					<div>{{ transaction.fnum }}</div>
				</div>
				<hr class="tw-m-0" />

				<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
					<div>
						<strong>{{ translate('COM_EMUNDUS_TRANSACTION_ID') }}</strong>
					</div>
					<div>{{ transaction.id }}</div>
				</div>
				<hr class="tw-m-0" />

				<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
					<div>
						<strong>{{ translate('COM_EMUNDUS_TRANSACTION_EXTERNAL_REFERENCE') }}</strong>
					</div>
					<input type="text" v-model="transaction.external_reference" name="external_reference" />
				</div>
				<hr class="tw-m-0" />

				<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
					<div>
						<strong>{{ translate('COM_EMUNDUS_TRANSACTION_DATE') }}</strong>
					</div>
					<div>{{ transaction.created_at }}</div>
				</div>
				<hr class="tw-m-0" />

				<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
					<div>
						<strong>{{ translate('COM_EMUNDUS_TRANSACTION_AMOUNT') }}</strong>
					</div>
					<input type="number" v-model="transaction.amount" name="amount" />
				</div>
				<hr class="tw-m-0" />

				<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
					<div>
						<strong>{{ translate('COM_EMUNDUS_TRANSACTION_STATUS') }}</strong>
					</div>
					<select v-model="transaction.status">
						<option v-for="status in statuses" :key="status.value" :value="status.value">
							{{ status.label }}
						</option>
					</select>
				</div>
				<hr class="tw-m-0" />

				<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
					<div>
						<strong>{{ translate('COM_EMUNDUS_TRANSACTION_PAYMENT_METHOD') }}</strong>
					</div>
					<div>{{ transaction.payment_method.label }}</div>
				</div>
				<hr class="tw-m-0" />

				<div class="tw-grid tw-grid-cols-2 tw-items-center tw-gap-3">
					<div>
						<strong>{{ translate('COM_EMUNDUS_TRANSACTION_SYNCHRONIZER') }}</strong>
					</div>
					<div>{{ getServiceName(transaction.synchronizer_id) }}</div>
				</div>
				<hr class="tw-m-0" />
			</div>
		</div>

		<div id="actions">
			<div class="tw-flex tw-justify-end tw-gap-3">
				<button class="tw-btn-primary" @click="saveTransaction">
					{{ translate('COM_EMUNDUS_TRANSACTION_SAVE') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
