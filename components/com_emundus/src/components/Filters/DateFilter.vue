<template>
	<div
		class="date-filter tw-mb-4 tw-w-full"
		:id="'filter-id-' + filter.uid"
		:ref="'filter-id-' + filter.uid"
		@click="toggleOpened"
	>
		<div class="tw-flex tw-items-center tw-justify-between">
			<p class="recap-label" :title="filter.label">{{ filter.label }}</p>
			<div>
				<span
					@mouseenter="resetHover = true"
					@mouseleave="resetHover = false"
					class="material-symbols-outlined reset-filter-btn tw-cursor-pointer"
					:class="{ 'tw-text-blue-400': resetHover }"
					@click="resetFilter"
					:alt="translate('MOD_EMUNDUS_FILTERS_RESET')"
					>refresh</span
				>
				<span
					v-if="!filter.default"
					class="material-symbols-outlined remove-filter-btn tw-cursor-pointer tw-text-red-600"
					@click="$.emit('remove-filter')"
					>close</span
				>
			</div>
		</div>
		<div
			class="date-filter-card tw-rounded-coordinator tw-border tw-border-neutral-400 tw-bg-white tw-p-2 tw-shadow-standard"
		>
			<section class="recap tw-mt-2 tw-flex tw-items-center" :class="{ hidden: opened }">
				<div v-if="filter.value[0]" class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
					<span class="recap-operator label label-darkblue"> {{ selectedOperatorLabel }}</span>
					<p class="recap-value tw-flex tw-flex-wrap tw-items-center tw-gap-2">
						<span v-if="filter.value[0]" class="label label-default">{{ formattedDate(filter.value[0]) }}</span>
						<span
							v-if="['between', '!between'].includes(filter.operator) && filter.value[1]"
							class="label label-default"
						>
							{{ translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_AND') }} {{ formattedDate(filter.value[1]) }}</span
						>
					</p>
				</div>
				<p v-else class="tw-text-neutral-500">{{ translate('MOD_EMUNDUS_FILTERS_PLEASE_SELECT') }}</p>
			</section>
			<section class="default-filter-options tw-mt-2" :class="{ hidden: !opened }">
				<div class="operators-selection tw-flex tw-flex-wrap tw-items-center tw-gap-2">
					<div
						v-for="operator in operators"
						:key="filter.uid + '-' + operator.value"
						class="em-p-6-10 tw-rounded-coordinator"
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
						<label :for="filter.uid + '-operator-' + operator.value" style="margin: 0" class="tw-text-sm">{{
							operator.label
						}}</label>
					</div>
				</div>
				<hr />
				<input type="date" :id="filter.uid + '-start_date'" v-model="filter.value[0]" />
				<div v-if="filter.operator === 'between' || filter.operator === '!between'" class="tw-mt-2">
					<p>{{ translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_AND') }}</p>
					<input class="tw-mt-2" type="date" :id="filter.uid + '-end_date'" v-model="filter.value[1]" />
				</div>
			</section>
			<span class="material-symbols-outlined toggle-open-close tw-cursor-pointer">{{
				opened ? 'keyboard_arrow_up' : 'keyboard_arrow_down'
			}}</span>
		</div>
	</div>
</template>

<script>
import date from '@/mixins/date.js';

export default {
	name: 'DateFilter.vue',
	mixins: [date],
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
			resetHover: false,
			originalFilterValue: null,
			originalFilterOperator: null,
		};
	},
	mounted() {
		if (this.filter.value === '' || this.filter.value === null || this.filter.value == 0) {
			this.filter.value = ['', ''];
		}

		this.originalFilterValue = JSON.parse(JSON.stringify(this.filter.value));
		this.originalFilterOperator = this.filter.operator;
		document.addEventListener('click', this.handleClickOutside);
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	},
	methods: {
		resetFilter(event) {
			this.filter.operator = '=';
			this.filter.value = ['', ''];

			if (this.opened) {
				this.opened = false;
				document.removeEventListener('click', this.handleClickOutside);
				this.onCloseCard();
			} else {
				this.onCloseCard();
			}

			event.stopPropagation();
		},
		toggleOpened(event = null) {
			if (
				event &&
				(event.target.closest('.default-filter-options') || event.target.classList.contains('remove-filter-btn'))
			) {
				return;
			}

			this.opened = !this.opened;
			if (this.opened === false) {
				document.removeEventListener('click', this.handleClickOutside);
				this.onCloseCard();
			} else {
				document.addEventListener('click', this.handleClickOutside);
			}
		},
		onCloseCard() {
			const valueDifferences =
				this.filter.value[0] !== this.originalFilterValue[0] || this.filter.value[1] !== this.originalFilterValue[1];
			const operatorDifferences = this.filter.operator !== this.originalFilterOperator;

			if (valueDifferences || operatorDifferences) {
				this.originalFilterValue = this.filter.value;
				this.originalFilterOperator = this.filter.operator;
				this.$emit('filter-changed');
			}
		},
		handleClickOutside(event) {
			if (this.opened) {
				const clickedElement = event.target;
				const componentElement = this.$refs['filter-id-' + this.filter.uid]; // Élément racine

				if (
					clickedElement &&
					!componentElement.contains(clickedElement) &&
					!clickedElement.closest('#' + componentElement.id)
				) {
					this.toggleOpened(event);
				}
			}
		},
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

<style scoped>
.date-filter-card {
	position: relative;
}

.date-filter-card .toggle-open-close {
	position: absolute;
	top: 4px;
	right: 4px;
}

span.label {
	font-weight: normal !important;
	display: flex !important;
	width: fit-content;
}
</style>
