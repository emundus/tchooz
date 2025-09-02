<script>
import Back from '@/components/Utils/Back.vue';
import Info from '@/components/utils/Info.vue';

import Multiselect from 'vue-multiselect';
import paymentService from '@/services/payment.js';
import Parameter from '@/components/Utils/Parameter.vue';

import alerts from '@/mixins/alerts.js';
export default {
	name: 'WorkflowPaymentStep',
	props: {
		workflow: {
			type: Object,
			required: true,
		},
		step: {
			type: Object,
			required: true,
		},
		previous_payment_steps: {
			type: Array,
			required: true,
		},
	},
	mixins: [alerts],
	data() {
		return {
			products: [],
			mandatoryProducts: [],
			optionalProducts: [],
			paymentServices: [],
			paymentMethods: [],
			adjustBalanceFields: [
				{
					param: 'description',
					type: 'wysiwig',
					placeholder: '',
					value: this.step.description,
					label: 'COM_EMUNDUS_PAYMENT_STEP_DESCRIPTION',
					helptext: 'COM_EMUNDUS_PAYMENT_STEP_DESCRIPTION_HELPTEXT',
					optional: true,
					displayed: true,
				},
				{
					param: 'adjust_balance',
					type: 'toggle',
					value: this.step.adjust_balance,
					label: 'COM_EMUNDUS_PAYMENT_STEP_ADJUST_BALANCE',
					helptext: '',
					displayed: true,
					hideLabel: true,
				},
				{
					param: 'adjust_balance_step_id',
					type: 'select',
					placeholder: '',
					value: this.step.adjust_balance_step_id,
					label: 'COM_EMUNDUS_PAYMENT_STEP_ADJUST_BALANCE_STEP_ID',
					helptext: '',
					options: this.previous_payment_steps.map((step) => {
						return { value: step.id, label: step.label };
					}),
					displayed: true,
				},
			],

			fields: [
				{
					param: 'advance_type',
					type: 'radiobutton',
					value: this.step.advance_type,
					label: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE',
					helptext: '',
					options: [
						{ value: 0, label: this.translate('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE_0') },
						{ value: 1, label: this.translate('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE_1') },
						{ value: 2, label: this.translate('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE_2') },
					],
					displayed: true,
				},
				{
					param: 'is_advance_amount_editable_by_applicant',
					type: 'toggle',
					value: this.step.is_advance_amount_editable_by_applicant,
					label: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_TYPE_EDITABLE',
					helptext: '',
					displayed: true,
					hideLabel: true,
				},
				{
					param: 'advance_amount',
					type: 'number',
					placeholder: '',
					value: this.step.advance_amount,
					label: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT',
					step: 0.01,
					pattern: '^[0-9]+(\\.[0-9]{1,2})?$',
					max: 99999999,
					maxlength: 8,
					editable: true,
					displayed: true,
				},
				{
					param: 'advance_amount_type',
					type: 'select',
					placeholder: '',
					value: this.step.advance_amount_type,
					label: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT_TYPE',
					helptext: '',
					options: [
						{ value: 'fixed', label: this.translate('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT_TYPE_FIXED') },
						{
							value: 'percentage',
							label: this.translate('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_ADVANCE_AMOUT_TYPE_PERCENTAGE'),
						},
					],
					displayed: true,
				},
			],

			installmentFields: [
				{
					param: 'installment_monthday',
					type: 'number',
					placeholder: '',
					value: this.step.installment_monthday,
					label: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_INSTALLMENT_DAY',
					helptext: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_INSTALLMENT_DAY_HELPTEXT',
					step: 1,
					min: 1,
					max: 31,
					maxlength: 8,
					editable: true,
					displayed: true,
				},
				{
					param: 'installment_effect_date',
					type: 'date',
					placeholder: '',
					value: this.step.installment_effect_date ? this.step.installment_effect_date : null,
					label: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_INSTALLMENT_EFFECT_DATE',
					helptext: 'COM_EMUNDUS_PAYMENT_STEP_PAYMENT_INSTALLMENT_EFFECT_DATE_HELPTEXT',
					editable: true,
					displayed: true,
					classes: 'tw-w-fit',
					placement: 'top',
					allownull: true,
				},
			],

			backUrl: '/index.php?option=com_emundus&view=workflows&layout=edit&wid=' + this.workflow.id,
		};
	},
	components: { Parameter, Multiselect, Back, Info },
	created() {
		this.mandatoryProducts = this.step.products
			.filter((product) => product.mandatory)
			.map((product) => {
				return { id: product.id, name: product.label };
			});
		this.optionalProducts = this.step.products
			.filter((product) => !product.mandatory)
			.map((product) => {
				return { id: product.id, name: product.label };
			});

		this.getPaymentMethods();
		this.getPaymentServices();
		this.getProducts();

		this.adjustBalanceFields.forEach((field) => {
			if (this.step[field.param]) {
				field.value = this.step[field.param];
			}
		});

		this.fields.forEach((field) => {
			if (this.step[field.param]) {
				field.value = this.step[field.param];
			}
		});
	},
	methods: {
		getPaymentMethods() {
			paymentService.getPaymentMethods().then((response) => {
				if (response.status) {
					this.paymentMethods = response.data.map((method) => {
						return { id: method.id, label: method.label, name: method.name, services: method.services };
					});
				} else {
				}
			});
		},
		getPaymentServices() {
			paymentService.getPaymentServices().then((response) => {
				if (response.status) {
					this.paymentServices = response.data.map((service) => {
						return { id: service.id, name: service.name };
					});
				}
			});
		},
		getProducts() {
			paymentService.getProducts().then((response) => {
				if (response.status) {
					this.products = response.data.datas.map((product) => {
						return { id: product.id, name: product.label.fr };
					});
				} else {
				}
			});
		},
		saveStep() {
			const step = {
				id: this.step.id,
				description: this.step.description,
				mandatoryProducts: this.mandatoryProducts.map((product) => product.id),
				optionalProducts: this.optionalProducts.map((product) => product.id),
				paymentMethods: this.step.payment_methods.map((method) => method.id),
				synchronizerId: this.step.synchronizer_id,
				advanceType: this.step.advance_type,
				advanceAmountEditableByApplicant: this.step.is_advance_amount_editable_by_applicant,
				advanceAmount: this.step.advance_amount,
				advanceAmountType: this.step.advance_amount_type,
				adjustBalance: this.step.adjust_balance,
				adjustBalanceStepId: this.step.adjust_balance_step_id,
				installmentRules: JSON.stringify(this.step.installment_rules),
				installmentMonthday: this.step.installment_monthday,
				installmentEffectDate:
					this.step.installment_effect_date !== null && this.step.installment_effect_date !== '00:00'
						? this.step.installment_effect_date
						: '',
			};

			paymentService.savePaymentStepRules(step).then((response) => {
				if (response.status) {
					this.alertSuccess('COM_EMUNDUS_PAYMENT_STEP_SAVED');
				} else {
					this.alertError('COM_EMUNDUS_PAYMENT_STEP_NOT_SAVED', response.msg);
				}
			});
		},
		onFieldUpdate(parameter, oldValue, value) {
			if (parameter.param === 'adjust_balance') {
				if (value == 1) {
					// force the advance type to 0
					this.step.advance_type = 0;
					this.fields.find((field) => field.param === 'advance_type').value = 0;
				}
			}

			this.step[parameter.param] = value;
		},

		addInstallmentRule() {
			this.step.installment_rules.push({
				from_amount: 0.0,
				to_amount: 0.0,
				min_installments: 1,
				max_installments: 1,
			});
		},
		removeInstallmentRule(index) {
			this.step.installment_rules.splice(index, 1);
		},
	},
	computed: {
		displayedPaymentMethods() {
			return this.paymentMethods.filter((method) => {
				// if a service is selected, check that is id appears in payment methods.services array
				if (this.step.synchronizer_id && this.step.synchronizer_id != 0) {
					return method.services.includes(this.step.synchronizer_id);
				} else {
					return true;
				}
			});
		},

		displayedAdjustBalanceFields() {
			return this.adjustBalanceFields.filter((field) => {
				if (field.param === 'adjust_balance_step_id') {
					return this.step.adjust_balance == 1;
				} else {
					return true;
				}
			});
		},

		displayedFields() {
			return this.fields.filter((field) => {
				if (field.param === 'advance_type') {
					const adjust_balance_field = this.adjustBalanceFields.find((f) => f.param === 'adjust_balance');

					if (adjust_balance_field && adjust_balance_field.value == 1) {
						return false;
					}
					return true;
				} else if (field.param === 'is_advance_amount_editable_by_applicant') {
					return this.step.advance_type != 0;
				} else if (field.param === 'advance_amount' || field.param === 'advance_amount_type') {
					return this.step.advance_type != 0 && this.step.is_advance_amount_editable_by_applicant == 0;
				} else {
					return true;
				}
			});
		},
		isSepaSelected() {
			return this.step.payment_methods.some((method) => {
				return method.name === 'sepa';
			});
		},
	},
};
</script>

<template>
	<Back :link="backUrl" :class="'tw-mb-4'" />
	<div
		id="payment-step"
		class="tw-mb-4 tw-flex tw-flex-col tw-gap-8 tw-rounded-coordinator-cards tw-border tw-border-neutral-300 tw-bg-white tw-p-6 tw-shadow-card"
	>
		<h1>{{ translate('COM_EMUNDUS_PAYMENT_STEP_CONFIGURATION') }}</h1>

		<div>
			<p>
				<strong>{{ translate('COM_EMUNDUS_WORKFLOW') }}</strong> {{ ' : ' + this.workflow.label }}
			</p>
			<p>
				<strong>{{ translate('COM_EMUNDUS_WORKFLOW_STEP') }}</strong> {{ ' : ' + this.step.label }}
			</p>
		</div>

		<div class="tw-flex tw-flex-col tw-gap-6">
			<div id="adjust-balance" class="tw-flex tw-flex-col tw-gap-6">
				<div v-for="field in displayedAdjustBalanceFields" :key="field.param" class="tw-flex-col tw-gap-6">
					<Parameter
						:class="{ 'tw-w-full': field.param === 'name' }"
						:ref="'event_' + field.param"
						:key="field.reload ? field.reload : field.param"
						:parameter-object="field"
						:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
						@valueUpdated="onFieldUpdate"
					/>
				</div>
			</div>

			<div id="mandatory-products">
				<label class="tw-flex tw-items-end tw-font-medium">{{
					translate('COM_EMUNDUS_PAYMENT_STEP_MANDATORY_PRODUCTS')
				}}</label>
				<Multiselect
					v-model="mandatoryProducts"
					:options="products"
					:multiple="true"
					:close-on-select="false"
					:show-labels="false"
					:taggable="true"
					placeholder="Select mandatory products"
					label="name"
					track-by="id"
				></Multiselect>
			</div>

			<div id="optional-products">
				<label class="tw-flex tw-items-end tw-font-medium">{{
					translate('COM_EMUNDUS_PAYMENT_STEP_OPTIONAL_PRODUCTS')
				}}</label>
				<Multiselect
					v-model="optionalProducts"
					:options="products"
					:multiple="true"
					:close-on-select="false"
					:show-labels="false"
					:taggable="true"
					placeholder="Select optional products"
					label="name"
					track-by="id"
				></Multiselect>
			</div>

			<div class="tw-flex tw-flex-col">
				<label class="tw-flex tw-items-end">
					<span class="tw-font-medium">{{ translate('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_SERVICE') }}</span>
					<span class="tw-ml-1 tw-text-red-600">*</span>
				</label>

				<Info
					v-if="paymentServices.length === 0"
					text="COM_EMUNDUS_PAYMENT_STEP_NO_PAYMENT_SERVICE"
					class="tw-mb-2"
					icon="warning"
					iconColor="tw-text-red-500"
					bgColor="tw-bg-orange-100"
				/>

				<select v-else v-model="step.synchronizer_id">
					<option value="0">{{ translate('COM_EMUNDUS_PAYMENT_STEP_SELECT_PAYMENT_SERVICE') }}</option>
					<option v-for="service in paymentServices" :key="'service-' + service.id" :value="service.id">
						{{ service.name }}
					</option>
				</select>
			</div>

			<div>
				<label class="tw-flex tw-items-end">
					<span class="tw-font-medium">{{ translate('COM_EMUNDUS_PAYMENT_STEP_PAYMENT_METHODS') }}</span>
					<span class="tw-ml-1 tw-text-red-600">*</span>
				</label>
				<Multiselect
					v-model="step.payment_methods"
					:options="displayedPaymentMethods"
					:multiple="true"
					:close-on-select="false"
					:show-labels="false"
					:taggable="true"
					placeholder="Select payment methods"
					label="label"
					track-by="id"
				>
				</Multiselect>
			</div>

			<div v-if="isSepaSelected" id="step-installment-rules" class="tw-flex tw-flex-col tw-gap-3">
				<h2 class="tw-mb-0 tw-flex tw-items-end tw-font-medium">
					{{ translate('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULES') }}
				</h2>

				<div v-for="field in installmentFields" :key="field.param" class="tw-flex-col tw-gap-6">
					<Parameter
						:class="{ 'tw-w-full': field.param === 'name' }"
						:ref="'event_' + field.param"
						:key="field.reload ? field.reload : field.param"
						:parameter-object="field"
						:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
						@valueUpdated="onFieldUpdate"
					/>
				</div>

				<Info
					:accordion="true"
					title="COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULES_INFO_TITLE"
					text="COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULES_INFO"
					class="tw-mt-2"
				/>

				<div
					v-if="step.installment_rules.length > 0"
					id="step-installment-rules-header"
					class="tw-flex tw-items-end tw-gap-2 tw-pr-12"
				>
					<span class="tw-h-fit tw-w-full">{{
						translate('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULE_FROM_AMOUNT')
					}}</span>
					<span class="tw-h-fit tw-w-full">{{ translate('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULE_TO_AMOUNT') }}</span>
					<span class="tw-h-fit tw-w-full">{{
						translate('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULE_MIN_INSTALLMENTS')
					}}</span>
					<span class="tw-h-fit tw-w-full">{{
						translate('COM_EMUNDUS_PAYMENT_STEP_INSTALLMENT_RULE_MAX_INSTALLMENTS')
					}}</span>
				</div>

				<div v-for="(rule, index) in step.installment_rules" :key="index">
					<div class="tw-flex tw-gap-2">
						<input type="number" v-model="rule.from_amount" name="rule_from_amount" />
						<input type="number" v-model="rule.to_amount" name="rule_to_amount" />
						<input type="number" v-model="rule.min_installments" name="rule_min_installments" />
						<input type="number" v-model="rule.max_installments" name="rule_max_installments" />
						<button class="tw-btn-red tw-px-[9px] tw-py-2" @click="removeInstallmentRule(index)">
							<span class="material-symbols-outlined">delete</span>
						</button>
					</div>
				</div>

				<div class="tw-flex tw-w-full tw-flex-row">
					<button class="tw-btn-secondary tw-gap-2" @click="addInstallmentRule">
						<span class="material-symbols-outlined">add_circle</span>
						{{ translate('COM_EMUNDUS_PAYMENT_STEP_ADD_INSTALLMENT_RULE') }}
					</button>
				</div>
			</div>

			<div class="tw-grid tw-grid-cols-[minmax(0,1fr)_max-content] tw-gap-6">
				<div
					v-for="field in displayedFields"
					:key="field.param"
					class="tw-grid-cols-[minmax(0,1fr)_max-content] tw-flex-col tw-gap-6"
					:class="{
						'tw-col-span-2': field.param !== 'advance_amount' && field.param !== 'advance_amount_type',
						'tw-col-[1] tw-w-full': field.param === 'advance_amount',
						'tw-col-[2] tw-w-fit': field.param === 'advance_amount_type',
					}"
				>
					<Parameter
						:class="{ 'tw-w-full': field.param === 'name' }"
						:ref="'event_' + field.param"
						:key="field.reload ? field.reload : field.param"
						:parameter-object="field"
						:help-text-type="field.helpTextType ? field.helpTextType : 'above'"
						:multiselect-options="field.multiselectOptions ? field.multiselectOptions : null"
						@valueUpdated="onFieldUpdate"
					/>
				</div>
			</div>

			<div class="tw-flex tw-justify-end">
				<button class="tw-btn-primary" @click="saveStep">
					{{ translate('SAVE') }}
				</button>
			</div>
		</div>
	</div>
</template>

<style scoped></style>
