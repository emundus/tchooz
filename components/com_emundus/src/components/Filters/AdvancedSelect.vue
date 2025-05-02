<template>
	<div id="advanced-select" class="advanced-search tw-w-full">
		<input
			type="text"
			v-model="search"
			class="tw-w-full"
			:placeholder="translate('MOD_EMUNDUS_FILTERS_GLOBAL_SEARCH_PLACEHOLDER')"
			@focusin="opened = true"
		/>

		<ul
			:class="{
				'tw-mt-1 tw-w-full tw-rounded-coordinator tw-border tw-border-neutral-400 tw-bg-white !tw-pl-0 tw-shadow-standard': true,
				hidden: opened === false,
			}"
		>
			<div v-for="group in groupedFilters" :key="group.id">
				<li class="tw-mb-2 tw-mt-2 tw-pl-2">
					<strong>{{ group.label }}</strong>
				</li>
				<li
					v-for="option in group.options"
					:key="option.id"
					class="tw-mb-2 tw-cursor-pointer tw-pl-4"
					@click="onClick(option.id)"
				>
					{{ option.group_label ? option.group_label + ' - ' : '' }} {{ option.label }}
				</li>
			</div>
		</ul>
	</div>
</template>

<script>
export default {
	name: 'AdvancedSelect',
	props: {
		menuId: {
			type: Number,
			required: true,
		},
		filters: {
			type: Array,
			required: true,
		},
	},
	data() {
		return {
			groupedOptions: [],
			search: '',
			selected: -1,
			opened: false,
		};
	},
	mounted() {
		document.addEventListener('click', this.handleClickOutside);
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	},
	methods: {
		onClick(id) {
			this.$emit('filter-selected', id);
			this.opened = false;
			this.search = '';
		},
		handleClickOutside(event) {
			if (!this.$el.contains(event.target)) {
				this.opened = false;
				this.search = '';
			}
		},
	},
	computed: {
		groupedFilters() {
			const groups = [];
			const alreadyAdded = [];

			this.displayedFilters.forEach((filter) => {
				if (!alreadyAdded.includes(filter.form_id)) {
					groups.push({
						id: filter.form_id,
						label: filter.form_label,
						options: [],
					});
					alreadyAdded.push(filter.form_id);
				}

				const currentFilterGroup = groups.find((group) => group.id === filter.form_id);
				currentFilterGroup.options.push(filter);
			});

			return groups;
		},
		displayedFilters() {
			return this.filters.filter((filter) => {
				return (
					filter.label.toLowerCase().includes(this.search.toLowerCase()) ||
					filter.form_label.toLowerCase().includes(this.search.toLowerCase())
				);
			});
		},
	},
};
</script>

<style scoped>
#advanced-select ul {
	list-style-type: none;
	max-height: 300px;
	overflow-y: auto;
}

#advanced-select ul li.tw-cursor-pointer:hover {
	background-color: #e4e4e4;
}
</style>
