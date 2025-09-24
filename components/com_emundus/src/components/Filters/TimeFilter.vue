<template>
	<div
		class="time-filter em-mb-8 tw-w-full tw-rounded-coordinator-form tw-border tw-border-neutral-400 tw-bg-white tw-p-2 tw-shadow-standard"
	>
		<div class="tw-flex tw-items-center tw-justify-between">
			<p class="recap-label" :title="filter.label">{{ filter.label }}</p>
			<div>
				<span
					v-if="!filter.default"
					class="material-symbols-outlined tw-cursor-pointer tw-text-red-600"
					@click="$.emit('remove-filter')"
					>close</span
				>
				<span v-if="opened === false" class="material-symbols-outlined tw-cursor-pointer" @click="opened = !opened"
					>keyboard_arrow_down</span
				>
				<span v-else class="material-symbols-outlined tw-cursor-pointer" @click="opened = !opened"
					>keyboard_arrow_up</span
				>
			</div>
		</div>
		<section v-if="!opened" class="recap tw-mt-2 tw-flex tw-items-center">
			<span class="recap-operator label label-darkblue em-mr-4"> {{ selectedOperatorLabel }}</span>
			<p class="recap-value tw-flex tw-flex-wrap tw-items-center tw-gap-2">
				<span v-if="filter.value[0]">{{ filter.value[0] }}</span>
				<span v-if="['between', '!between'].includes(filter.operator) && filter.value[1]">
					{{ translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_AND') }} {{ filter.value[1] }}</span
				>
			</p>
		</section>
		<section v-else class="default-filter-options tw-mt-2">
			<div class="operators-selection tw-flex tw-flex-wrap tw-items-center tw-gap-2">
				<div
					v-for="operator in operators"
					:key="filter.uid + '-' + operator.value"
					class="tw-rounded-coordinator tw-p-2"
					:class="{
						'label-default': operator.value !== filter.operator,
						'label-darkblue': operator.value === filter.operator,
					}"
				>
					<input
						class="hidden label"
						type="radio"
						:id="filter.uid + '-operator-' + operator.value"
						:value="operator.value"
						v-model="filter.operator"
					/>
					<label :for="filter.uid + '-operator-' + operator.value" style="margin: 0">{{ operator.label }}</label>
				</div>
			</div>
			<hr />
			<input type="time" :id="filter.uid + '-start_date'" v-model="filter.value[0]" />
			<div v-if="filter.operator === 'between' || filter.operator === '!between'" class="tw-mt-2">
				<p>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_AND') }}</p>
				<input class="tw-mt-2" type="time" :id="filter.uid + '-end_date'" v-model="filter.value[1]" />
			</div>
		</section>
	</div>
</template>

<script>
export default {
	name: 'TimeFilter',
	props: {
		menuId: {
			type: Number,
			required: true,
		},
		filter: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			opened: false,
			operators: [
				{ value: '=', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS') },
				{ value: '!=', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS_NOT') },
				{ value: 'superior', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_SUPERIOR_TO') },
				{ value: 'inferior', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_INFERIOR_TO') },
				{
					value: 'superior_or_equal',
					label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_SUPERIOR_OR_EQUAL_TO'),
				},
				{
					value: 'inferior_or_equal',
					label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_INFERIOR_OR_EQUAL_TO'),
				},
				{ value: 'between', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_BETWEEN') },
				{ value: '!between', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_NOT_BETWEEN') },
			],
		};
	},
	computed: {
		selectedOperatorLabel() {
			const selectedOperator = this.operators.find((operator) => {
				return operator.value === this.filter.operator;
			});
			return selectedOperator ? selectedOperator.label : '';
		},
	},
};
</script>

<style scoped></style>
