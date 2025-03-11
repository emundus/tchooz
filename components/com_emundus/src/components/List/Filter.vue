<script>
export default {
	name: 'Filter',
	props: {
		filter: {
			type: Object,
			required: true,
		},
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
		<div class="tw-flex tw-items-center tw-justify-between">
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
			<input type="date" v-model="filter.value" @change="onChangeFilter(filter)" />
		</template>
	</div>
</template>

<style scoped></style>
