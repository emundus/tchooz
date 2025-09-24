<template>
	<div
		class="multi-select-filter tw-mb-4 tw-w-full"
		:id="'filter-id-' + filter.uid"
		:ref="'filter-id-' + filter.uid"
		@click="toggleOpened"
	>
		<div class="tw-flex tw-items-center tw-justify-between">
			<p class="recap-label" :title="translate(filter.label)">{{ translate(filter.label) }}</p>
			<div class="tw-flex tw-items-center">
				<span
					@mouseenter="resetHover = true"
					@mouseleave="resetHover = false"
					class="material-symbols-outlined reset-filter-btn tw-cursor-pointer"
					:class="{ 'tw-text-blue-400': resetHover }"
					@click="resetFilter"
					:title="translate('MOD_EMUNDUS_FILTERS_RESET')"
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
			class="multi-select-filter-card tw-mt-1 tw-rounded-coordinator-form tw-border tw-border-neutral-400 tw-bg-white tw-p-2"
		>
			<section class="recap" :class="{ hidden: opened }">
				<div
					v-if="filter.value && filter.value.length > 0 && !filter.value.includes('all')"
					class="em-flex-column-start"
				>
					<div class="recap-value tw-flex tw-flex-wrap tw-items-center tw-gap-2">
						<span class="recap-operator label label-darkblue"> {{ selectedOperatorLabel }}</span>
						<div
							v-for="(value, index) in filter.value.slice(0, 2)"
							:key="value"
							class="tw-flex tw-flex-wrap tw-items-center tw-gap-2"
						>
							<span class="label label-default">{{ translate(selectedValuesLabels[index]) }}</span>
							<span v-if="filter.value.length > 1 && index == 0" class="label label-darkblue">
								{{ selectedAndorOperatorLabel }}
							</span>
						</div>
						<div v-if="filter.value.length > 2">
							<span class="label label-default">
								+ {{ filter.value.length - 2 }} {{ translate('MOD_EMUNDUS_FILTERS_MORE_VALUES') }}</span
							>
						</div>
					</div>
				</div>
				<p v-else class="tw-cursor-pointer tw-text-neutral-500">{{ translate('MOD_EMUNDUS_FILTERS_PLEASE_SELECT') }}</p>
			</section>
			<section class="multi-select-filter-options" :class="{ hidden: !opened }">
				<div class="operators-selection tw-flex tw-flex-wrap tw-items-center tw-gap-2">
					<div
						v-for="operator in displayedOperators"
						:key="filter.uid + '-' + operator.value"
						class="tw-cursor-pointer tw-rounded-coordinator tw-p-2"
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
						<label :for="filter.uid + '-operator-' + operator.value" style="margin: 0" class="tw-cursor-pointer">{{
							operator.label
						}}</label>
					</div>
				</div>
				<hr />
				<div class="andor-selection tw-flex tw-items-center tw-gap-2" v-if="displayedAndorOperators.length > 0">
					<div
						v-for="andor in displayedAndorOperators"
						:key="filter.uid + '-' + andor.value"
						class="tw-rounded-coordinator tw-p-2"
						:class="{
							'label-default': andor.value !== filter.andorOperator,
							'label-darkblue': andor.value === filter.andorOperator,
						}"
					>
						<input
							class="hidden label"
							type="radio"
							:id="filter.uid + '-andor-' + andor.value"
							:value="andor.value"
							v-model="filter.andorOperator"
						/>
						<label :for="filter.uid + '-andor-' + andor.value" style="margin: 0">{{ andor.label }}</label>
					</div>
				</div>
				<hr v-if="displayedAndorOperators.length > 0" />
				<input
					class="tw-w-full tw-rounded-coordinator-form tw-border tw-border-neutral-400 tw-bg-white tw-p-2 tw-shadow-standard"
					:id="filter.uid + '-filter-search'"
					:ref="filter.uid + '-search-input'"
					type="text"
					:placeholder="translate('MOD_EMUNDUS_FILTERS_FILTER_SEARCH')"
					v-model="search"
					@keyup="onSearchChange"
				/>
				<div class="values-selection tw-mb-2 tw-mt-2">
					<div class="tw-flex tw-items-center">
						<input
							:name="filter.uid + '-filter-value'"
							:id="filter.uid + '-filter-value-all'"
							type="checkbox"
							value="all"
							v-model="filter.value"
							@click="onClickAll"
						/>
						<label :for="filter.uid + '-filter-value-all'" style="margin: 0">{{ translate('ALL') }}</label>
					</div>
					<div
						v-for="value in searchedValues"
						:key="value.value"
						class="em-filter-value-checkbox tw-mb-1 tw-flex tw-items-center"
						@click="onClickSpecificValue()"
						:class="{
							'disabled hidden':
								countFilterValues &&
								value.hasOwnProperty('count') &&
								value.count == 0 &&
								!filter.value.includes(value.value),
						}"
					>
						<input
							:name="filter.uid + '-filter-value'"
							:id="filter.uid + '-filter-value-' + value.value"
							type="checkbox"
							:value="value.value"
							v-model="filter.value"
						/>
						<label :for="filter.uid + '-filter-value-' + value.value" style="margin: 0">
							<span>{{ translate(value.label) }} </span>
							<span v-if="countFilterValues && value.hasOwnProperty('count')" class="em-gray-color">
								({{ value.count }})</span
							>
						</label>
					</div>
				</div>
			</section>
			<span class="material-symbols-outlined toggle-open-close tw-cursor-pointer">{{
				opened ? 'keyboard_arrow_up' : 'keyboard_arrow_down'
			}}</span>
		</div>
	</div>
</template>

<script>
export default {
	name: 'MultiSelect',
	props: {
		menuId: {
			type: Number,
			required: true,
		},
		filter: {
			type: Object,
			required: true,
		},
		countFilterValues: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			opened: false,
			operators: [
				{ value: 'IN', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS'), display: true },
				{ value: 'NOT IN', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS_NOT'), display: true },
			],
			andorOperators: [
				{ value: 'OR', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_OR'), display: true },
				{ value: 'AND', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_AND'), display: false },
			],
			search: '',
			resetHover: false,
			originalFilterValue: null,
			originalFilterOperator: null,
			originalFilterAndorOperator: null,
		};
	},
	beforeMount() {
		if (this.filter.value === null || this.filter.value === undefined) {
			this.filter.value = [];
		}

		if (this.filter.andorOperators) {
			this.andorOperators = this.andorOperators.map((andor) => {
				andor.display = this.filter.andorOperators.includes(andor.value);
				return andor;
			});
		}

		if (this.filter.operators) {
			this.operators = this.operators.map((operator) => {
				operator.display = this.filter.operators.includes(operator.value);
				return operator;
			});
		}
	},
	mounted() {
		this.filter.operator =
			this.filter.operator === '=' || this.filter.operator === null || typeof this.filter.operator === 'undefined'
				? 'IN'
				: this.filter.operator;
		if (this.filter.andorOperator === null || typeof this.filter.andorOperator === 'undefined') {
			this.filter.andorOperator = 'OR';
		}
		this.originalFilterValue = this.filter.value;
		this.originalFilterOperator = this.filter.operator;
		this.originalFilterAndorOperator = this.filter.andorOperator;
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	},
	methods: {
		onClickSpecificValue() {
			if (this.filter.value.includes('all')) {
				this.filter.value = this.filter.value.filter((value) => {
					return value !== 'all';
				});
			}
		},
		onClickAll() {
			if (this.filter.value.includes('all')) {
				this.filter.value = this.filter.value.filter((value) => {
					return !this.searchedValues.find((searchedValue) => {
						return searchedValue.value === value;
					});
				});
				this.filter.value = this.filter.value.filter((value) => {
					return value !== 'all';
				});
			} else {
				let allValues = this.searchedValues.map((value) => {
					return value.value;
				});
				allValues.push('all');
				allValues.forEach((value) => {
					if (!this.filter.value.includes(value)) {
						this.filter.value.push(value);
					}
				});
			}
		},
		allValuesAreSelected() {
			return this.filter.values.every((value) => {
				return this.filter.value.includes(value.value);
			});
		},
		onSearchChange() {
			if (!this.allValuesAreSelected()) {
				// If all is not selected, remove 'all' from selected values
				this.filter.value = this.filter.value.filter((value) => {
					return value !== 'all';
				});
			}
		},
		resetFilter(event) {
			this.filter.operator = 'IN';
			this.filter.andorOperator = 'OR';
			this.search = '';
			this.filter.value = [];

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
				(event.target.closest('.multi-select-filter-options') || event.target.classList.contains('remove-filter-btn'))
			) {
				return;
			}

			this.opened = !this.opened;
			if (this.opened === false) {
				document.removeEventListener('click', this.handleClickOutside);
				this.onCloseCard();
			} else {
				this.$nextTick(() => {
					this.$refs[this.filter.uid + '-search-input'].focus();
				});
				document.addEventListener('click', this.handleClickOutside);
			}
		},
		onCloseCard() {
			this.search = '';
			this.onSearchChange();
			const valueDifferences =
				this.filter.value && Array.isArray(this.filter.value)
					? this.filter.value
							.filter((x) => !this.originalFilterValue.includes(x))
							.concat(this.originalFilterValue.filter((x) => !this.filter.value.includes(x)))
					: [];
			const operatorDifferences = this.filter.operator !== this.originalFilterOperator;
			const andorOperatorDifferences = this.filter.andorOperator !== this.originalFilterAndorOperator;

			if (valueDifferences.length > 0 || operatorDifferences || andorOperatorDifferences) {
				this.originalFilterValue = this.filter.value;
				this.originalFilterOperator = this.filter.operator;
				this.originalFilterAndorOperator = this.filter.andorOperator;
				this.$emit('filter-changed');
			}
		},
		handleClickOutside(event) {
			if (this.opened) {
				const clickedElement = event.target;
				const componentElement = this.$refs['filter-id-' + this.filter.uid]; // Élément racine

				if (
					clickedElement &&
					componentElement &&
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
		displayedAndorOperators() {
			return this.andorOperators.filter((andor) => {
				return andor.display;
			});
		},
		displayedOperators() {
			return this.operators.filter((operator) => {
				return operator.display;
			});
		},
		selectedAndorOperatorLabel() {
			const selectedAndorOperator = this.andorOperators.find((andor) => {
				return andor.value === this.filter.andorOperator;
			});
			return selectedAndorOperator ? selectedAndorOperator.label : '';
		},
		selectedValuesLabels() {
			let labels = [];

			this.filter.value.forEach((value) => {
				const selectedValue = this.filter.values.find((filterValue) => {
					return filterValue.value === value;
				});

				if (selectedValue) {
					labels.push(selectedValue.label);
				}
			});

			return labels;
		},
		searchedValues() {
			return this.filter.values && this.filter.values.length > 0
				? this.filter.values.filter((value) => {
						if (value.label) {
							return value.label.toLowerCase().includes(this.search.toLowerCase());
						} else {
							return false;
						}
					})
				: [];
		},
	},
};
</script>

<style scoped>
.values-selection {
	max-height: 180px;
	overflow-y: auto;
}

.multi-select-filter-card {
	position: relative;
}

.multi-select-filter-card .toggle-open-close {
	position: absolute;
	top: 4px;
	right: 4px;
}

span.label {
	font-weight: normal !important;
	display: flex !important;
	width: fit-content;
}

.recap {
	overflow: hidden;
}

.em-filter-value-checkbox.disabled {
	pointer-events: none;
	opacity: 0.5;
}
</style>
