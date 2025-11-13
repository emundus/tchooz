<script>
import paymentService from '@/services/payment.js';
import Parameter from '@/components/Utils/Parameter.vue';
import { useGlobalStore } from '@/stores/global.js';
import alerts from '@/mixins/alerts.js';

export default {
	name: 'AlterFilesProducts',
	components: { Parameter },
	props: {
		fnums: {
			type: Array,
			required: true,
		},
	},
	mixins: [alerts],
	data() {
		return {
			fields: [
				{
					param: 'addOrRemove',
					label: 'COM_EMUNDUS_PAYMENT_ADD_OR_REMOVE',
					optional: false,
					value: 'add',
					displayed: true,
					multiple: false,
					type: 'select',
					options: [
						{ value: 'add', label: this.translate('COM_EMUNDUS_ADD') },
						{ value: 'remove', label: this.translate('COM_EMUNDUS_REMOVE') },
					],
				},
				{
					param: 'selectedProducts',
					label: 'COM_EMUNDUS_PAYMENT_SELECT_PRODUCTS',
					optional: false,
					value: [],
					displayed: true,
					multiple: true,
					type: 'multiselect',
					options: [],
					reload: 0,
					multiselectOptions: {
						options: [],
						noOptions: false,
						multiple: true,
						taggable: false,
						searchable: true,
						internalSearch: true,
						asyncRoute: 'getproducts',
						asyncController: 'payment',
						asyncCallback: async (response, parameter) => {
							return await this.searchableCallback(response, parameter);
						},
						optionsLimit: 100,
						optionsPlaceholder: 'COM_EMUNDUS_MULTISELECT_ADDKEYWORDS',
						selectLabel: 'PRESS_ENTER_TO_SELECT',
						selectGroupLabel: 'PRESS_ENTER_TO_SELECT_GROUP',
						selectedLabel: 'SELECTED',
						deselectedLabel: 'PRESS_ENTER_TO_REMOVE',
						deselectGroupLabel: 'PRESS_ENTER_TO_DESELECT_GROUP',
						noOptionsText: 'COM_EMUNDUS_MULTISELECT_NOKEYWORDS',
						noResultsText: 'COM_EMUNDUS_MULTISELECT_NORESULTS',
						// Can add tag validations (ex. email, phone, regex)
						tagValidations: [],
						tagRegex: '',
						trackBy: 'value',
						label: 'label',
					},
				},
			],
		};
	},
	methods: {
		alterFilesProducts() {
			const productIds = this.fields
				.find((field) => field.param === 'selectedProducts')
				.value.map((item) => item.value);

			if (this.fnums.length > 0 && productIds.length > 0) {
				const addOrRemove = this.fields.find((field) => field.param === 'addOrRemove').value;

				paymentService.alterFilesProducts(this.fnums, productIds, addOrRemove).then((response) => {
					if (response.status) {
						this.alertSuccess('COM_EMUNDUS_PAYMENT_ALTER_FILES_PRODUCTS_SUCCESS');
					} else {
						this.alertError('COM_EMUNDUS_PAYMENT_ALTER_FILES_PRODUCTS_ERROR');
					}
				});
			} else {
				this.alertError(this.translate('COM_EMUNDUS_PAYMENT_NO_FILES_OR_PRODUCTS_SELECTED'));
			}
		},
		searchableCallback(response, parameter) {
			const options = [];
			if (response.status && response.data.datas.length > 0) {
				response.data.datas.forEach((item) => {
					options.push({ value: item.id, label: item.label[useGlobalStore().getShortLang] + ' (' + item.price + ')' });
				});
			}
			parameter.multiselectOptions.options = options;
			return options;
		},
	},
};
</script>

<template>
	<div id="alter-files-products">
		<div class="tw-mb-8">
			<Parameter
				v-for="field in fields"
				class="tw-mb-4"
				:key="field.param + '-' + field.reload"
				:parameter-object="field"
				:multiselect-options="field.type === 'multiselect' ? field.multiselectOptions : null"
			></Parameter>
		</div>

		<div class="tw-flex tw-flex-row tw-justify-end">
			<button @click="alterFilesProducts" class="tw-btn-primary">
				{{ translate('COM_EMUNDUS_PAYMENT_ALTER_FILES_PRODUCTS_BTN') }}
			</button>
		</div>
	</div>
</template>

<style scoped></style>
