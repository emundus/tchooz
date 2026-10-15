<template>
	<div
		class="orderlist-filter tw-mb-4 tw-w-full"
		:id="'filter-id-' + filter.uid"
		:ref="'filter-id-' + filter.uid"
		@click="toggleOpened"
	>
		<div class="tw-flex tw-items-center tw-justify-between">
			<p class="recap-label" :title="filter.label">{{ filter.label }}</p>
			<div class="tw-flex tw-items-center">
				<Button
					icon="refresh"
					variant="neutral"
					emphasis="ghost"
					:aria-label="translate('MOD_EMUNDUS_FILTERS_RESET')"
					@click="resetFilter"
				/>
				<Button
					v-if="!filter.default"
					icon="close"
					variant="error"
					emphasis="ghost"
					class="remove-filter-btn"
					@click="$emit('remove-filter')"
				/>
			</div>
		</div>
		<div
			class="orderlist-filter-card tw-rounded-coordinator-form tw-border tw-border-neutral-400 tw-bg-white tw-p-2 tw-shadow-standard"
		>
			<section class="recap tw-mt-2 tw-flex tw-items-center" :class="{ hidden: opened }">
				<div v-if="completeCriteria.length > 0" class="tw-flex tw-flex-wrap tw-items-center tw-gap-2">
					<template v-for="(criterion, index) in completeCriteria" :key="index">
						<Tag v-if="index > 0" variant="primary" :label="selectedAndorLabel" />
						<Tag variant="neutral" :label="criterionRecap(criterion)" />
					</template>
				</div>
				<p v-else class="tw-text-neutral-500">{{ translate('MOD_EMUNDUS_FILTERS_PLEASE_SELECT') }}</p>
			</section>
			<section class="default-filter-options tw-mt-2 tw-flex tw-flex-col tw-gap-2" :class="{ hidden: !opened }">
				<div class="operators-selection tw-flex tw-flex-wrap tw-items-center tw-gap-2">
					<Button
						v-for="operator in operators"
						:key="filter.uid + '-' + operator.value"
						emphasis="activation"
						:label="operator.label"
						:active="operator.value === filter.operator"
						@update:active="filter.operator = operator.value"
					/>
				</div>
				<template v-if="filter.value.length > 1">
					<hr />
					<div class="andor-selection tw-flex tw-items-center tw-gap-2">
						<Button
							v-for="andor in andorOperators"
							:key="filter.uid + '-' + andor.value"
							emphasis="activation"
							:label="andor.label"
							:active="andor.value === filter.andorOperator"
							@update:active="filter.andorOperator = andor.value"
						/>
					</div>
				</template>
				<hr />
				<div
					v-for="(criterion, index) in filter.value"
					:key="filter.uid + '-criterion-' + index"
					class="tw-flex tw-items-end tw-gap-2"
				>
					<div class="tw-flex tw-min-w-0 tw-flex-1 tw-flex-col">
						<label :for="filter.uid + '-choice-' + index" class="tw-mb-1 tw-text-sm">
							{{ translate('MOD_EMUNDUS_FILTERS_ORDERLIST_SELECT_CHOICE') }}
						</label>
						<multiselect
							:id="filter.uid + '-choice-' + index"
							v-model="criterion.values"
							:options="choiceValues"
							:custom-label="choiceLabel"
							:multiple="true"
							:taggable="false"
							:searchable="false"
							:close-on-select="false"
							:clear-on-select="false"
							:allow-empty="true"
							:show-labels="false"
							:placeholder="translate('MOD_EMUNDUS_FILTERS_PLEASE_SELECT')"
						></multiselect>
					</div>
					<span class="tw-whitespace-nowrap tw-pb-2 tw-text-sm">{{ connectorLabel }}</span>
					<div class="tw-flex tw-w-24 tw-flex-col">
						<label :for="filter.uid + '-rank-' + index" class="tw-mb-1 tw-text-sm">
							{{ translate('MOD_EMUNDUS_FILTERS_ORDERLIST_SELECT_RANK') }}
						</label>
						<select :id="filter.uid + '-rank-' + index" v-model="criterion.rank">
							<option value="">-</option>
							<option v-for="rank in ranks" :key="rank" :value="rank" :disabled="isRankTaken(rank, index)">
								{{ translate('MOD_EMUNDUS_FILTERS_ORDERLIST_RANK', { rank: rank }) }}
							</option>
						</select>
					</div>
					<Button
						icon="delete"
						variant="error"
						emphasis="ghost"
						:aria-label="translate('MOD_EMUNDUS_FILTERS_ORDERLIST_REMOVE_CRITERION')"
						@click="removeCriterion(index)"
					/>
				</div>
				<Button
					v-if="filter.value.length < ranks.length"
					icon="add"
					variant="primary"
					class="tw-w-fit"
					:label="translate('MOD_EMUNDUS_FILTERS_ORDERLIST_ADD_CRITERION')"
					@click="addCriterion"
				/>
			</section>
			<span class="material-symbols-outlined toggle-open-close tw-cursor-pointer">{{
				opened ? 'keyboard_arrow_up' : 'keyboard_arrow_down'
			}}</span>
		</div>
	</div>
</template>

<script>
import { Button, Tag } from '@emundus/ui';
import Multiselect from 'vue-multiselect';

export default {
	name: 'OrderlistFilter',
	components: { Button, Tag, Multiselect },
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
	emits: ['remove-filter', 'filter-changed'],
	data() {
		return {
			opened: false,
			operators: [
				{ value: '=', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS') },
				{ value: '!=', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_IS_NOT') },
			],
			andorOperators: [
				{ value: 'AND', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_AND') },
				{ value: 'OR', label: this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_OR') },
			],
			originalState: '',
		};
	},
	created() {
		if (!Array.isArray(this.filter.value)) {
			this.filter.value = [];
		}
		this.filter.value.forEach((criterion) => {
			if (!Array.isArray(criterion.values)) {
				criterion.values = [];
			}
		});
		if (this.filter.value.length < 1) {
			this.addCriterion();
		}
		if (!['=', '!='].includes(this.filter.operator)) {
			this.filter.operator = '=';
		}
		if (!['AND', 'OR'].includes(this.filter.andorOperator)) {
			this.filter.andorOperator = 'AND';
		}

		this.originalState = this.state;
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	},
	methods: {
		addCriterion() {
			this.filter.value.push({ rank: '', values: [] });
		},
		removeCriterion(index) {
			this.filter.value.splice(index, 1);

			if (this.filter.value.length < 1) {
				this.addCriterion();
			}
		},
		isRankTaken(rank, index) {
			return this.filter.value.some((criterion, i) => i !== index && criterion.rank == rank);
		},
		criterionRecap(criterion) {
			const values = criterion.values
				.map(this.choiceLabel)
				.join(' ' + this.translate('MOD_EMUNDUS_FILTERS_FILTER_OPERATOR_OR') + ' ');

			return (
				values +
				' ' +
				this.connectorLabel +
				' ' +
				this.translate('MOD_EMUNDUS_FILTERS_ORDERLIST_RANK', { rank: criterion.rank })
			);
		},
		choiceLabel(value) {
			const choice = this.filter.values.find((choice) => choice.value === value);
			return choice ? choice.label : value;
		},
		resetFilter(event) {
			this.filter.value = [];
			this.filter.operator = '=';
			this.filter.andorOperator = 'AND';
			this.addCriterion();

			if (this.opened) {
				this.opened = false;
				document.removeEventListener('click', this.handleClickOutside);
			}
			this.onCloseCard();

			event.stopPropagation();
		},
		// An element removed by its own click (last criterion added, row or selected choice deleted) is detached
		// from the document when the click bubbles up here: it must not be taken for a click outside the card.
		toggleOpened(event = null) {
			if (
				event &&
				(!event.target.isConnected ||
					event.target.closest('.default-filter-options') ||
					event.target.closest('.remove-filter-btn'))
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
			if (this.state !== this.originalState) {
				this.originalState = this.state;
				this.$emit('filter-changed');
			}
		},
		handleClickOutside(event) {
			if (this.opened) {
				const clickedElement = event.target;
				const componentElement = this.$refs['filter-id-' + this.filter.uid];

				if (
					clickedElement &&
					clickedElement.isConnected &&
					!componentElement.contains(clickedElement) &&
					!clickedElement.closest('#' + componentElement.id)
				) {
					this.toggleOpened(event);
				}
			}
		},
	},
	computed: {
		choiceValues() {
			return this.filter.values.map((choice) => choice.value);
		},
		ranks() {
			return Array.from({ length: this.filter.values.length }, (_, i) => i + 1);
		},
		completeCriteria() {
			return this.filter.value
				.filter((criterion) => criterion.rank !== '' && criterion.values.length > 0)
				.sort((a, b) => a.rank - b.rank);
		},
		// What the query depends on: incomplete rows and the AND/OR of a single row do not change the result.
		state() {
			return JSON.stringify({
				criteria: this.completeCriteria,
				operator: this.filter.operator,
				andor: this.completeCriteria.length > 1 ? this.filter.andorOperator : null,
			});
		},
		connectorLabel() {
			return this.translate(
				this.filter.operator === '!='
					? 'MOD_EMUNDUS_FILTERS_ORDERLIST_IS_NOT_AT_RANK'
					: 'MOD_EMUNDUS_FILTERS_ORDERLIST_IS_AT_RANK',
			);
		},
		selectedAndorLabel() {
			const andor = this.andorOperators.find((andor) => andor.value === this.filter.andorOperator);
			return andor ? andor.label : '';
		},
	},
};
</script>

<style scoped>
.orderlist-filter-card {
	position: relative;
}

.orderlist-filter-card .toggle-open-close {
	position: absolute;
	top: 4px;
	right: 4px;
}
</style>
