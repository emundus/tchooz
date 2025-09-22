<template>
	<div
		class="default-filter tw-mb-4 tw-w-full"
		:id="'filter-id-' + filter.uid"
		:ref="'filter-id-' + filter.uid"
		@click="toggleOpened"
	>
		<div class="tw-flex tw-items-center tw-justify-between">
			<p class="recap-label" :title="filter.label">{{ filter.label }}</p>
			<div class="tw-flex tw-items-center">
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
			class="default-filter-card em-mt-4 tw-rounded-coordinator-form tw-border tw-border-neutral-400 tw-bg-white tw-p-2 tw-shadow-standard"
		>
			<section class="recap" :class="{ hidden: opened }">
				<div v-if="filter.value" class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
					<span class="recap-operator label label-darkblue"> {{ selectedOperatorLabel }}</span>
					<span class="recap-value label label-default"> {{ filter.value }}</span>
				</div>
				<p v-else class="tw-text-neutral-500">{{ translate('MOD_EMUNDUS_FILTERS_PLEASE_SELECT') }}</p>
			</section>
			<section class="default-filter-options tw-mt-2" :class="{ hidden: !opened }">
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
				<input
					:ref="filter.uid + '-value-input'"
					:type="this.type === 'number' ? 'number' : 'text'"
					v-model="filter.value"
					@keyup.enter="onCloseCard"
				/>
			</section>
			<span class="material-symbols-outlined toggle-open-close tw-cursor-pointer">{{
				opened ? 'keyboard_arrow_up' : 'keyboard_arrow_down'
			}}</span>
		</div>
	</div>
</template>

<script>
export default {
	name: 'DefaultFilter',
	props: {
		menuId: {
			type: Number,
			required: true,
		},
		filter: {
			type: Object,
			required: true,
		},
		type: {
			type: String,
			default: 'text',
		},
	},
	data() {
		return {
			opened: false,
			operators: [
				{ value: '=', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS') },
				{ value: '!=', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS_NOT') },
				{ value: 'LIKE', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_CONTAINS') },
				{ value: 'NOT LIKE', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_DOES_NOT_CONTAIN') },
			],
			resetHover: false,
		};
	},
	mounted() {
		if (this.type === 'number') {
			this.operators = [
				{ value: '=', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_EQUAL_TO') },
				{ value: '!=', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_NOT_EQUAL_TO') },
				{ value: 'superior', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_SUPERIOR_TO') },
				{
					value: 'superior_or_equal',
					label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_SUPERIOR_OR_EQUAL_TO'),
				},
				{ value: 'inferior', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_INFERIOR_TO') },
				{
					value: 'inferior_or_equal',
					label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_INFERIOR_OR_EQUAL_TO'),
				},
			];
		}

		this.originalFilterValue = this.filter.value;
		this.originalFilterOperator = this.filter.operator;
		document.addEventListener('click', this.handleClickOutside);
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	},

	methods: {
		resetFilter(event) {
			this.filter.operator = '=';
			this.filter.value = '';

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
				// focus on -value-input
				this.$nextTick(() => {
					this.$refs[this.filter.uid + '-value-input'].focus();
				});
				document.addEventListener('click', this.handleClickOutside);
			}
		},
		onCloseCard(event = null) {
			if (event !== null) {
				event.stopPropagation();
			}

			if (this.opened) {
				this.opened = false;
			}

			const valueDifferences = this.filter.value != this.originalFilterValue;
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
.default-filter-card {
	position: relative;
}

.default-filter-card .toggle-open-close {
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
</style>
