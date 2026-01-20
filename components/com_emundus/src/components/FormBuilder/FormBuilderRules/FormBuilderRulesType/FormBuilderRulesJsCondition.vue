<template>
	<div
		id="form-builder-rules-js-condition"
		class="tw-w-full tw-self-start"
		:class="{ 'tw-rounded tw-bg-neutral-300 tw-p-2': multiple }"
	>
		<div class="tw-flex tw-items-center tw-justify-end">
			<button v-if="index !== 0" type="button" @click="$emit('remove-condition', index)" class="tw-w-auto">
				<span class="material-symbols-outlined tw-text-red-600">close</span>
			</button>
		</div>

		<div class="tw-flex">
			<p class="tw-mr-2 tw-mt-3 tw-font-bold">
				{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_IF') }}
			</p>

			<div class="tw-ml-2 tw-flex tw-w-full tw-flex-col">
				<div class="tw-flex tw-items-center tw-gap-2">
					<div class="tw-flex tw-items-center">
						<select v-model="conditionData.type" class="tw-rounded tw-border tw-border-neutral-500 tw-p-2">
							<option v-for="type in fieldType" :key="type" :value="type">
								{{ translate('COM_EMUNDUS_FORMBUILDER_RULE_TYPE_' + type.toUpperCase()) }}
							</option>
						</select>
					</div>
					<div class="tw-flex tw-w-full tw-items-center">
						<multiselect
							v-model="conditionData.field"
							label="label_tag"
							:custom-label="labelTranslate"
							track-by="name"
							:options="elementsOptions"
							:multiple="false"
							:taggable="false"
							select-label=""
							selected-label=""
							deselect-label=""
							:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELD')"
							:close-on-select="true"
							:clear-on-select="false"
							:searchable="true"
							:allow-empty="true"
						></multiselect>
					</div>
				</div>

				<div class="tw-mt-4">
					<div class="tw-flex tw-items-center tw-gap-3">
						<span
							v-for="operator in operators"
							:key="operator.id"
							class="tw-ml-1 tw-cursor-pointer tw-rounded-lg tw-border tw-border-neutral-500 tw-p-2"
							@click="conditionData.state = operator.value"
							:class="{
								'label-darkblue': conditionData.state == operator.value,
							}"
						>
							{{ translate(operator.label) }}
						</span>
					</div>

					<div class="tw-mt-6" v-if="conditionData.state !== 'empty' && conditionData.state !== '!empty'">
						<multiselect
							v-if="
								conditionData.field &&
								(options_plugins.includes(conditionData.field.plugin) || conditionData.field.plugin == 'yesno')
							"
							v-model="conditionData.values"
							label="value"
							track-by="primary_key"
							:options="options"
							:multiple="false"
							:taggable="false"
							select-label=""
							selected-label=""
							deselect-label=""
							:placeholder="translate('COM_EMUNDUS_FORM_BUILDER_RULE_SELECT_FIELD')"
							:close-on-select="true"
							:clear-on-select="false"
							:searchable="true"
							:allow-empty="true"
						></multiselect>
						<input v-else-if="conditionData.field" v-model="conditionData.values" />
					</div>
				</div>
			</div>
		</div>
	</div>
</template>

<script>
import formBuilderMixin from '@/mixins/formbuilder.js';
import globalMixin from '@/mixins/mixin.js';
import fabrikMixin from '@/mixins/fabrik.js';
import errorMixin from '@/mixins/errors.js';

import formBuilderService from '@/services/formbuilder.js';
import { useGlobalStore } from '@/stores/global.js';

import Multiselect from 'vue-multiselect';
import { watch } from 'vue';

export default {
	components: {
		Multiselect,
	},
	props: {
		page: {
			type: Object,
			default: () => ({}),
		},
		condition: {
			type: Object,
			default: () => ({}),
		},
		index: {
			type: Number,
			default: 0,
		},
		elements: {
			type: Array,
			default: () => [],
		},
		userProfileElements: {
			type: Array,
			default: () => [],
		},
		multiple: {
			type: Boolean,
			default: false,
		},
	},
	mixins: [formBuilderMixin, globalMixin, errorMixin, fabrikMixin],
	data() {
		return {
			loading: false,
			elementsOptions: [],
			operators: [
				{
					id: 1,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_EQUALS',
					value: '=',
				},
				{
					id: 2,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_NOT_EQUALS',
					value: '!=',
				},
				{
					id: 3,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_GREATER_THAN',
					value: '>',
				},
				{
					id: 4,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_GREATER_THAN_OR_EQUALS',
					value: '>=',
				},
				{
					id: 5,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_LESS_THAN',
					value: '<',
				},
				{
					id: 6,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_LESS_THAN_OR_EQUALS',
					value: '<=',
				},
				{
					id: 7,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_EMPTY',
					value: 'empty',
				},
				{
					id: 8,
					label: 'COM_EMUNDUS_FORMBUILDER_RULE_OPERATOR_NOT_EMPTY',
					value: '!empty',
				},
			],
			options: [],
			options_plugins: ['dropdown', 'databasejoin', 'radiobutton', 'checkbox'],
			fieldType: ['form', 'user'],
			conditionData: null,
		};
	},
	created() {
		this.conditionData = this.condition;
	},
	mounted() {
		if (this.conditionData.type === 'form') {
			this.elementsOptions = this.elements;
		} else if (this.conditionData.type === 'user') {
			this.elementsOptions = this.userProfileElements;
		}

		if (this.page.id) {
			console.log(this.conditionData.field);
			console.log(this.elementsOptions);
			this.conditionData.field = this.elementsOptions.find((element) => element.name === this.conditionData.field);
			if (this.conditionData.field) {
				this.defineOptions(this.conditionData.field);
			}
		}

		watch(
			() => this.conditionData.field,
			(val, oldVal) => {
				if (typeof oldVal === 'object') {
					this.conditionData.values = '';
				}
				this.options = [];

				if (val) {
					this.defineOptions(val);
				}
			},
		);
	},
	methods: {
		labelTranslate({ label }) {
			let labelTranslated = label ? label : '';

			// If labelTranslated is empty, we try to find an other language
			if (labelTranslated === '' && label) {
				let labels = Object.values(label);
				labels.forEach((label) => {
					if (label !== '') {
						labelTranslated = label;
					}
				});
			}

			return labelTranslated;
		},
		defineOptions(val) {
			if (this.options_plugins.includes(val.plugin)) {
				if (val.plugin == 'databasejoin') {
					this.loading = true;

					this.getDatabasejoinOptions(
						val.params.join_db_name,
						val.params.join_key_column,
						val.params.join_val_column,
						val.params.join_val_column_concat,
					).then((response) => {
						if (response.status && response.data != '') {
							this.options = response.options;

							if (this.conditionData.values) {
								this.conditionData.values = this.options.find(
									(option) => option.primary_key == this.conditionData.values,
								);
							}
						} else {
							this.displayError(this.translate('COM_EMUNDUS_FORM_BUILDER_ERROR'), this.translate(response.msg));
						}
						this.loading = false;
					});
				} else {
					var ctr = 0;
					Object.values(val.params.sub_options.sub_values).forEach((option, key) => {
						let new_option = {
							primary_key: option,
							value: val.params.sub_options.sub_labels[key],
						};

						this.options.push(new_option);

						ctr++;
						if (ctr === val.params.sub_options.sub_values.length) {
							if (this.conditionData.values) {
								this.conditionData.values = this.options.find(
									(option) => option.primary_key == this.conditionData.values,
								);
							}
						}

						this.loading = false;
					});
				}
			}

			if (val.plugin == 'yesno') {
				this.options = [
					{
						primary_key: 0,
						value: this.translate('COM_EMUNDUS_FORMBUILDER_NO'),
					},
					{
						primary_key: 1,
						value: this.translate('COM_EMUNDUS_FORMBUILDER_YES'),
					},
				];

				if (this.conditionData.values) {
					this.conditionData.values = this.options.find((option) => option.primary_key == this.conditionData.values);
				}
			}
		},
	},
	watch: {
		'conditionData.type': function (newType, oldType) {
			if (newType !== oldType && oldType !== null) {
				this.conditionData.field = null;
				this.conditionData.values = '';

				if (newType === 'form') {
					this.elementsOptions = this.elements;
				} else if (newType === 'user') {
					this.elementsOptions = this.userProfileElements;
				}
			}
		},
	},
	computed: {
		conditionLabel() {
			return `-- ${this.index + 1} --`;
		},
	},
};
</script>
