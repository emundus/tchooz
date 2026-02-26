<script>
import Multiselect from 'vue-multiselect';

export default {
	name: 'Filter',
	emits: ['change-filter', 'remove-filter'],
	components: {
		Multiselect,
	},
	props: {
		filter: {
			type: Object,
			required: true,
		},
	},
	created() {
		if (this.filter.type === 'multiselect' && this.filter.value && typeof this.filter.value !== 'object') {
			if (this.filter.multiple) {
				this.filter.value = this.filter.value.split(',').map((val) => {
					const matched = this.filter.options.find((opt) => opt.value == val);
					return matched ? matched : null;
				});

				this.filter.value = this.filter.value.filter((v) => v !== null);
			} else {
				const matched = this.filter.options.find((opt) => opt.value == this.filter.value);
				if (matched) {
					this.filter.value = matched;
				}
			}
		}
	},
	methods: {
		onChangeFilter(filter) {
			this.$emit('change-filter', filter);
		},
		removeFilter(filter) {
			this.$emit('remove-filter', filter);
		},
	},
};
</script>

<template>
	<div>
		<div class="tw-mb-2 tw-flex tw-items-center tw-justify-between">
			<label class="!tw-mb-0 tw-font-medium">
				{{ translate(filter.label) }}
			</label>
			<span
				v-if="!filter.alwaysDisplay"
				class="material-icons-outlined tw-cursor-pointer tw-text-red-500"
				@click="removeFilter(filter)"
			>
				close
			</span>
		</div>

		<template v-if="filter.type === 'select'">
			<select v-model="filter.value" @change="onChangeFilter(filter)" class="tw-w-full">
				<option v-for="option in filter.options" :key="option.value" :value="option.value">
					{{ translate(option.label) }}
				</option>
			</select>
		</template>
		<template v-else-if="filter.type === 'date'">
			<input
				type="date"
				class="tw-cursor-pointer !tw-rounded-coordinator"
				v-model="filter.value"
				@change="onChangeFilter(filter)"
			/>
		</template>
		<template v-else-if="filter.type === 'time'">
			<input type="time" v-model="filter.value" @change="onChangeFilter(filter)" />
		</template>

		<template v-else-if="filter.type === 'multiselect'">
			<multiselect
				v-model="filter.value"
				:options="filter.options"
				:multiple="filter.multiple || false"
				:searchable="true"
				:close-on-select="true"
				:clear-on-select="false"
				:preserve-search="true"
				:select-label="''"
				:deselect-label="''"
				:selectedLabel="''"
				:placeholder="translate('COM_EMUNDUS_ONBOARD_REGISTRANT_FILTER_SEARCH_PLACEHOLDER')"
				label="label"
				track-by="value"
				@select="onChangeFilter(filter)"
				@remove="onChangeFilter(filter)"
			>
				<template #noResult>{{ translate('COM_EMUNDUS_MULTISELECT_NORESULTS') }}</template>
			</multiselect>
		</template>
	</div>
</template>

<style scoped></style>
