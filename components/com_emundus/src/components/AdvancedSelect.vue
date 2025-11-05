<template>
	<div
		id="advanced-select"
		class="advanced-search tw-w-full"
		:class="{
			'absolute-behave': positionAbsolute === true,
		}"
	>
		<input
			type="text"
			v-model="search"
			class="tw-w-full"
			:placeholder="translate('MOD_EMUNDUS_FILTERS_GLOBAL_SEARCH_PLACEHOLDER')"
			@focusin="opened = true"
		/>

		<ul
			:class="{
				'em-border-radius-8 em-border-neutral-400 em-box-shadow em-white-bg em-mt-4 tw-w-full !tw-pl-0': true,
				hidden: opened === false,
			}"
		>
			<template v-for="group in groupedFilters" :key="group.id">
				<li class="em-mt-8 em-mb-8 em-pl-8">
					<strong>{{ group.label }}</strong>
				</li>
				<li
					v-for="option in group.options"
					:key="option.id"
					class="em-mb-8 em-pl-16 em-pointer"
					@click="onClick(option.id)"
				>
					{{ option.group_label ? option.group_label + ' - ' : '' }} {{ option.label }}
				</li>
			</template>
		</ul>
	</div>
</template>

<script>
export default {
	name: 'AdvancedSelect.vue',
	props: {
		filters: {
			type: Array,
			required: true,
		},
		closeOnChoose: {
			type: Boolean,
			default: true,
		},
		positionAbsolute: {
			type: Boolean,
			default: false,
		},
		maxHeight: {
			type: Number,
			default: 300,
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
		// set max height
		this.$el.querySelector('ul').style.maxHeight = `${this.maxHeight}px`;
	},
	beforeUnmount() {
		document.removeEventListener('click', this.handleClickOutside);
	},
	methods: {
		onClick(id) {
			this.$emit('filter-selected', id);
			if (this.closeOnChoose) {
				this.opened = false;
				this.search = '';
			}
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
	overflow-y: auto;
}

#advanced-select ul li.em-pointer:hover {
	background-color: #e4e4e4;
}

#advanced-select {
	&.absolute-behave {
		position: relative;

		ul {
			position: absolute;
			z-index: 2;
		}
	}
}
</style>
